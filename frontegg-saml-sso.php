<?php
/**
 * Plugin Name: Frontegg SAML SSO
 * Plugin URI: https://frontegg.com
 * Description: Replaces the WordPress login/logout with secure Frontegg SAML SSO.
 * Version: 1.0.0
 * Author: Frontegg
 * Author URI: https://frontegg.com/why-frontegg
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: frontegg-saml-sso
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('FRONTEGG_SAML_VERSION', '1.0.0');

class Frontegg_SAML_SSO {

    private $settings;

    public function __construct() {
        $this->settings = get_option('frontegg_saml_settings', []);

        add_action('plugins_loaded', [$this, 'maybe_flush_rewrite']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('init', [$this, 'register_saml_endpoints']);
        add_action('template_redirect', [$this, 'handle_saml_response']);
        add_action('template_redirect', [$this, 'handle_saml_logout_response']);
        add_action('login_form', [$this, 'redirect_to_saml_login']);
        add_filter('logout_url', [$this, 'custom_logout_url'], 10, 2);
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_frontegg_saml_sso') {
            return;
        }
    
        wp_enqueue_script(
            'frontegg-saml-admin-js',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            [],
            FRONTEGG_SAML_VERSION,
            true
        );
    
        wp_enqueue_style(
            'frontegg-saml-admin-css',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            [],
            FRONTEGG_SAML_VERSION
        );
    }    

    public function maybe_flush_rewrite() {
        $stored_version = get_option('frontegg_saml_version');
        if ($stored_version !== FRONTEGG_SAML_VERSION) {
            flush_rewrite_rules();
            update_option('frontegg_saml_version', FRONTEGG_SAML_VERSION);
        }
    }

    public function register_admin_page() {
        add_menu_page(
            'Frontegg SAML SSO',
            'Frontegg SAML SSO',
            'manage_options',
            'frontegg_saml_sso',
            [$this, 'render_admin_page'],
            plugin_dir_url(__FILE__) . 'assets/img/frontegg-icon.png',
            80
        );
    }

    public function render_admin_page() {
        $error_message = '';
        $success_message = '';

        if (isset($_POST['frontegg_saml_save']) && check_admin_referer('frontegg_saml_save_action')) {
            $input = isset($_POST['frontegg_saml']) ? wp_unslash($_POST['frontegg_saml']) : [];

            $sso_url = sanitize_text_field($input['sso_url'] ?? '');
            $slo_url = sanitize_text_field($input['slo_url'] ?? '');
            $certificate = trim($input['certificate'] ?? '');
            $post_logout_redirect = sanitize_text_field($input['post_logout_redirect'] ?? '');

            if (!empty($certificate) && !$this->validate_certificate($certificate)) {
                $error_message = 'Invalid certificate. Please paste a valid X.509 PEM certificate.';
            } else {
                update_option('frontegg_saml_settings', [
                    'sso_url' => $sso_url,
                    'slo_url' => $slo_url,
                    'certificate' => $certificate,
                    'post_logout_redirect' => $post_logout_redirect,
                ]);
                $success_message = 'Settings saved successfully.';
                $this->settings = get_option('frontegg_saml_settings', []);
            }
        }

        $settings = $this->settings;
        $plugin = $this;
        include plugin_dir_path(__FILE__) . 'admin-settings.php';
    }

    public function register_saml_endpoints() {
        add_rewrite_tag('%saml_response%', '1');
        add_rewrite_tag('%saml_logout_response%', '1');
        add_rewrite_tag('%frontegg_sp_metadata%', '1');

        add_rewrite_rule('^saml-response/?$', 'index.php?saml_response=1', 'top');
        add_rewrite_rule('^saml-logout-response/?$', 'index.php?saml_logout_response=1', 'top');
        add_rewrite_rule('^frontegg-sp-metadata/?$', 'index.php?frontegg_sp_metadata=1', 'top');

        add_filter('query_vars', function ($vars) {
            $vars[] = 'saml_response';
            $vars[] = 'saml_logout_response';
            $vars[] = 'frontegg_sp_metadata';
            return $vars;
        });
    }

    public function redirect_to_saml_login() {
        $sso_url = $this->settings['sso_url'] ?? '';
        if (!empty($sso_url)) {
            $saml_request = $this->generate_saml_request();
            wp_redirect($sso_url . '?SAMLRequest=' . urlencode($saml_request));
            exit;
        }
    }

    private function generate_saml_request() {
        $entity_id = $this->get_entity_id();
        $acs_url = $this->get_acs_url();
        $request_id = '_' . uniqid();
        $issue_instant = gmdate('Y-m-d\TH:i:s\Z');

        $xml  = '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ';
        $xml .= 'ID="' . $request_id . '" Version="2.0" IssueInstant="' . $issue_instant . '" ';
        $xml .= 'Destination="' . $this->settings['sso_url'] . '" ';
        $xml .= 'ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" ';
        $xml .= 'AssertionConsumerServiceURL="' . $acs_url . '">';
        $xml .= '<saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $entity_id . '</saml:Issuer>';
        $xml .= '<samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress" AllowCreate="true" />';
        $xml .= '</samlp:AuthnRequest>';

        return base64_encode(gzdeflate($xml));
    }

    public function handle_saml_response() {
        if (get_query_var('saml_response') !== '1') return;

        // POSTs to this endpoint come from Frontegg's IdP — nonce validation not possible here
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        if ($method !== 'POST' || !isset($_POST['SAMLResponse'])) {
            status_header(400);
            exit('Invalid SAML Response');
        }

        $saml = sanitize_text_field(wp_unslash($_POST['SAMLResponse']));
        $user_email = $this->extract_email_from_saml_response($saml);
        if (!$user_email) {
            status_header(500);
            exit('SAML Error: Failed to extract user');
        }

        $user = get_user_by('email', $user_email);
        if (!$user) {
            $user_id = wp_create_user($user_email, wp_generate_password(), $user_email);
            if (is_wp_error($user_id)) {
                status_header(500);
                exit('Failed to create user');
            }
            $user = get_user_by('id', $user_id);
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        wp_redirect(home_url());
        exit;
    }

    public function handle_saml_logout_response() {
        if (get_query_var('saml_logout_response') !== '1') return;

        wp_logout();

        $redirect = $this->settings['post_logout_redirect'] ?? home_url();
        if (isset($_GET['RelayState'])) {
            $relay = sanitize_text_field(wp_unslash($_GET['RelayState']));
            $redirect = esc_url_raw($relay);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    private function extract_email_from_saml_response($base64_response) {
        $decoded = base64_decode($base64_response);
        if (!$decoded) return false;

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($decoded);
        if (!$xml) return false;

        $namespaces = $xml->getNamespaces(true);
        $assertion = $xml->children($namespaces['saml'])->Assertion ?? null;
        if (!$assertion) return false;

        $subject = $assertion->children($namespaces['saml'])->Subject ?? null;
        $name_id = $subject ? $subject->children($namespaces['saml'])->NameID : null;

        return isset($name_id) ? sanitize_email(trim((string)$name_id)) : false;
    }

    public function custom_logout_url($logout_url, $redirect) {
        $slo_url = $this->settings['slo_url'] ?? '';
        if (empty($slo_url)) return $logout_url;

        $current_user = wp_get_current_user();
        if (!$current_user || empty($current_user->user_email)) return $logout_url;

        $saml_request = $this->generate_logout_request($current_user->user_email);
        $vendor_host = urlencode(home_url());
        $relay_state = urlencode($this->get_slo_response_url());

        return $slo_url . '?SAMLRequest=' . urlencode($saml_request) . '&vendorHost=' . $vendor_host . '&RelayState=' . $relay_state;
    }

    private function generate_logout_request($name_id) {
        $entity_id = $this->get_entity_id();
        $slo_url = $this->settings['slo_url'] ?? '';
        $request_id = '_' . uniqid();
        $issue_instant = gmdate('Y-m-d\TH:i:s\Z');

        $saml_request = '<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ';
        $saml_request .= 'xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ';
        $saml_request .= 'ID="' . $request_id . '" Version="2.0" IssueInstant="' . $issue_instant . '" ';
        $saml_request .= 'Destination="' . $slo_url . '">';
        $saml_request .= '<saml:Issuer>' . $entity_id . '</saml:Issuer>';
        $saml_request .= '<saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">' . $name_id . '</saml:NameID>';
        $saml_request .= '</samlp:LogoutRequest>';

        return base64_encode(gzdeflate($saml_request));
    }

    public function get_entity_id() {
        return home_url('/frontegg-sp-metadata');
    }

    public function get_acs_url() {
        return home_url('/saml-response');
    }

    public function get_slo_response_url() {
        return home_url('/saml-logout-response');
    }

    public function validate_certificate($pem) {
        if (empty($pem)) return false;
        $cert = trim($pem);
        $res = openssl_x509_read($cert);
        if ($res === false) return false;
        openssl_x509_free($res);
        return true;
    }
}

new Frontegg_SAML_SSO();

register_activation_hook(__FILE__, function() {
    if (!get_option('frontegg_saml_settings')) {
        add_option('frontegg_saml_settings', []);
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
