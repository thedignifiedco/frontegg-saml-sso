<?php
/**
 * Plugin Name: Frontegg SAML SSO
 * Plugin URI: https://frontegg.com
 * Description: Replaces the WordPress login/logout with secure Frontegg SAML SSO.
 * Version: 1.0.1
 * Author: Frontegg
 * Author URI: https://frontegg.com/why-frontegg
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: frontegg-saml-sso
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('FRONTEGG_SAML_VERSION', '1.0.1');

class Frontegg_SAML_SSO {
    private $settings;

    public function __construct() {
        $this->settings = get_option('frontegg_saml_settings', []);

        add_action('plugins_loaded', [$this, 'maybe_flush_rewrite']);
        add_action('admin_notices', [$this, 'show_update_notice']);
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('init', [$this, 'register_saml_endpoints']);
        add_action('template_redirect', [$this, 'handle_saml_response']);
        add_action('template_redirect', [$this, 'handle_saml_logout_response']);
        add_action('login_form', [$this, 'redirect_to_saml_login']);
        add_filter('logout_url', [$this, 'custom_logout_url'], 10, 2);
    }

    public function maybe_flush_rewrite() {
        $current_version = get_option('frontegg_saml_version');
        if ($current_version !== FRONTEGG_SAML_VERSION) {
            flush_rewrite_rules();
            update_option('frontegg_saml_version', FRONTEGG_SAML_VERSION);
            set_transient('frontegg_saml_updated_notice', true, 30);
        }
    }

    public function show_update_notice() {
        if (get_transient('frontegg_saml_updated_notice')) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Frontegg SAML SSO plugin updated successfully.</strong> Permalinks have been refreshed.</p></div>';
            delete_transient('frontegg_saml_updated_notice');
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

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_frontegg_saml_sso') return;
        wp_enqueue_style('frontegg-saml-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', [], FRONTEGG_SAML_VERSION);
        wp_enqueue_script('frontegg-saml-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', [], FRONTEGG_SAML_VERSION, true);
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) wp_die(__('Unauthorized', 'frontegg-saml-sso'));

        $error_message = '';
        $success_message = '';

        if (isset($_POST['frontegg_saml_save']) && check_admin_referer('frontegg_saml_save_action')) {
            $input = wp_unslash($_POST['frontegg_saml']);
            $certificate = trim($input['certificate'] ?? '');

            $this->settings = [
                'sso_url' => sanitize_text_field($input['sso_url'] ?? ''),
                'slo_url' => sanitize_text_field($input['slo_url'] ?? ''),
                'post_logout_redirect' => sanitize_text_field($input['post_logout_redirect'] ?? ''),
                'certificate' => $certificate,
            ];

            if (!empty($certificate) && !$this->validate_certificate($certificate)) {
                $error_message = 'The provided certificate is invalid. Please paste a valid X.509 PEM certificate.';
            } else {
                update_option('frontegg_saml_settings', $this->settings);
                $success_message = 'Settings saved successfully.';
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
        if (!empty($this->settings['sso_url'])) {
            $sso_url = esc_url_raw($this->settings['sso_url']);
            $request = $this->generate_login_request();
            wp_redirect($sso_url . '?SAMLRequest=' . urlencode($request));
            exit;
        }
    }

    public function generate_login_request() {
        $entity_id = $this->get_entity_id();
        $acs_url = $this->get_acs_url();
        $sso_url = esc_url_raw($this->settings['sso_url'] ?? '');
        $request_id = '_' . wp_generate_password(12, false);
        $issue_instant = gmdate('Y-m-d\TH:i:s\Z');

        $saml_request = "<samlp:AuthnRequest xmlns:samlp=\"urn:oasis:names:tc:SAML:2.0:protocol\" ID=\"{$request_id}\" Version=\"2.0\" IssueInstant=\"{$issue_instant}\" Destination=\"{$sso_url}\" ProtocolBinding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST\" AssertionConsumerServiceURL=\"{$acs_url}\">";
        $saml_request .= "<saml:Issuer xmlns:saml=\"urn:oasis:names:tc:SAML:2.0:assertion\">{$entity_id}</saml:Issuer>";
        $saml_request .= "<samlp:NameIDPolicy Format=\"urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress\" AllowCreate=\"true\" />";
        $saml_request .= "</samlp:AuthnRequest>";

        return base64_encode(gzdeflate($saml_request));
    }

    public function handle_saml_response() {
        if (get_query_var('saml_response') !== '1') return;

        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field($_SERVER['REQUEST_METHOD']) : '';
        if ($method !== 'POST' || !isset($_POST['SAMLResponse'])) {
            status_header(400);
            exit('SAML Response not found.');
        }

        $saml = sanitize_text_field(wp_unslash($_POST['SAMLResponse']));
        $email = $this->extract_email_from_saml_response($saml);

        if (!$email) {
            status_header(500);
            exit('SAML Error: Failed to extract email from SAML Response.');
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            $user_id = wp_create_user($email, wp_generate_password(), $email);
            $user = get_user_by('id', $user_id);
        }

        if ($user) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);
            do_action('wp_login', $user->user_login, $user);
        }

        wp_redirect(home_url());
        exit;
    }

    public function extract_email_from_saml_response($saml_response) {
        $decoded = base64_decode($saml_response);
        if (!$decoded || !preg_match('/<saml:NameID[^>]*>([^<]+)<\/saml:NameID>/', $decoded, $matches)) {
            return false;
        }
        return sanitize_email($matches[1]);
    }

    private function generate_logout_request($name_id) {
        $entity_id = $this->get_entity_id();
        $slo_url = $this->settings['slo_url'] ?? '';
        $request_id = '_' . uniqid();
        $issue_instant = gmdate('Y-m-d\TH:i:s\Z');

        $saml_request = '<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ';
        $saml_request .= 'xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ';
        $saml_request .= 'ID="' . $request_id . '" Version="2.0" IssueInstant="' . $issue_instant . '" ';
        $saml_request .= 'Destination="' . esc_url_raw($slo_url) . '">';
        $saml_request .= '<saml:Issuer>' . esc_html($entity_id) . '</saml:Issuer>';
        $saml_request .= '<saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">' . esc_html($name_id) . '</saml:NameID>';
        $saml_request .= '</samlp:LogoutRequest>';

        return base64_encode(gzdeflate($saml_request));
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

    public function handle_saml_logout_response() {
        if (get_query_var('saml_logout_response') !== '1') return;

        wp_logout();

        $redirect = !empty($this->settings['post_logout_redirect']) ? esc_url_raw($this->settings['post_logout_redirect']) : home_url();
        if (isset($_GET['RelayState'])) {
            $relay = sanitize_text_field(wp_unslash($_GET['RelayState']));
            if (!empty($relay)) $redirect = esc_url_raw($relay);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public function validate_certificate($pem) {
        if (empty($pem)) return false;
        $pem = trim($pem);
        if (stripos($pem, 'BEGIN CERTIFICATE') === false || stripos($pem, 'END CERTIFICATE') === false) {
            return false;
        }
        return (@openssl_x509_read($pem) !== false);
    }

    public function get_entity_id() {
        return home_url('/frontegg-sp-metadata');
    }

    public function get_acs_url() {
        return rtrim(home_url('/saml-response'), '/');
    }

    public function get_slo_response_url() {
        return home_url('/saml-logout-response');
    }
}

new Frontegg_SAML_SSO();

register_activation_hook(__FILE__, function () {
    require_once plugin_dir_path(__FILE__) . 'frontegg-saml-sso.php';
    $plugin = new Frontegg_SAML_SSO();
    $plugin->register_saml_endpoints();
    flush_rewrite_rules();
    add_option('frontegg_saml_version', FRONTEGG_SAML_VERSION);
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
