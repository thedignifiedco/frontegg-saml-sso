<?php
/**
 * Plugin Name: Frontegg SAML SSO for WordPress
 * Plugin URI: https://frontegg.com
 * Description: Adds SAML Single Sign-On (SSO) authentication to WordPress using Frontegg as the Identity Provider.
 * Version: 1.0.0
 * Author: Frontegg
 * Author URI: https://frontegg.com
 * License: MIT License
 * License URI: https://mit-license.org/
 * Text Domain: frontegg-saml
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 */

define('FRONTEGG_SAML_VERSION', '1.0.0');

if (!defined('ABSPATH'))
    exit;

class Frontegg_SAML_SSO
{

    private $settings;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $this->settings = get_option('frontegg_saml_settings', []);

        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('init', [$this, 'register_saml_endpoints']);
        add_action('template_redirect', [$this, 'handle_saml_response']);
        add_action('template_redirect', [$this, 'handle_saml_logout_response']);
        add_action('login_form', [$this, 'redirect_to_saml_login']);
        add_filter('logout_url', [$this, 'custom_logout_url'], 10, 2);
    }

    public function register_admin_page()
    {
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

    public function render_admin_page()
    {
        $error_message = '';
        $success_message = '';

        if (!empty($_POST['frontegg_saml_save']) && check_admin_referer('frontegg_saml_save_action')) {
            $input = wp_unslash($_POST['frontegg_saml']);
            $sso_url = sanitize_text_field($input['sso_url'] ?? '');
            $slo_url = sanitize_text_field($input['slo_url'] ?? '');
            $post_logout_redirect = sanitize_text_field($input['post_logout_redirect'] ?? '');
            $certificate = isset($input['certificate']) ? trim($input['certificate']) : '';

            if (!empty($certificate) && !$this->validate_certificate($certificate)) {
                $error_message = '❌ Invalid certificate. Please paste a valid X.509 PEM certificate.';
            } else {
                update_option('frontegg_saml_settings', [
                    'sso_url' => $sso_url,
                    'slo_url' => $slo_url,
                    'post_logout_redirect' => $post_logout_redirect,
                    'certificate' => $certificate,
                ]);
                $success_message = '✅ Settings saved successfully.';
                $this->settings = get_option('frontegg_saml_settings', []);
            }
        }

        $settings = $this->settings;
        $plugin = $this;

        if (!empty($success_message)) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($success_message) . '</p></div>';
        }
        if (!empty($error_message)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
        }

        include plugin_dir_path(__FILE__) . 'admin-settings.php';
    }

    public function register_saml_endpoints()
    {
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

    public function redirect_to_saml_login()
    {
        $sso_url = $this->settings['sso_url'] ?? '';
        if (!empty($sso_url)) {
            $saml_request = $this->generate_saml_request();
            wp_redirect($sso_url . '?SAMLRequest=' . urlencode($saml_request));
            exit;
        }
    }

    private function generate_saml_request()
    {
        $entity_id = $this->get_entity_id();
        $acs_url = $this->get_acs_url();
        $request_id = '_' . uniqid();
        $issue_instant = gmdate('Y-m-d\TH:i:s\Z');

        $saml_request = <<<XML
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="$request_id" Version="2.0" IssueInstant="$issue_instant" Destination="{$this->settings['sso_url']}" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" AssertionConsumerServiceURL="$acs_url">
    <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">$entity_id</saml:Issuer>
    <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress" AllowCreate="true" />
</samlp:AuthnRequest>
XML;

        return base64_encode(gzdeflate($saml_request));
    }

    public function handle_saml_response()
    {
        if (get_query_var('saml_response') != '1')
            return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['SAMLResponse'])) {
            status_header(400);
            error_log('[Frontegg SSO] Missing or invalid SAMLResponse POST parameter.');
            exit('Invalid SAML Response');
        }

        // --- Step 1: Decode and extract user email ---
        $user_email = $this->extract_email_from_saml_response($_POST['SAMLResponse']);

        if (!$user_email) {
            status_header(500);
            error_log('[Frontegg SSO] Failed to extract email from SAML response.');
            exit('SAML Error: Failed to extract user');
        }

        error_log('[Frontegg SSO] Extracted user email: ' . sanitize_email($user_email));

        // --- Step 2: Login or Register ---
        $user = get_user_by('email', $user_email);
        if (!$user) {
            $password = wp_generate_password();
            $user_id = wp_create_user($user_email, $password, $user_email);
            if (is_wp_error($user_id)) {
                status_header(500);
                error_log('[Frontegg SSO] User creation failed: ' . $user_id->get_error_message());
                exit('SAML Error: Failed to create user');
            }
            $user = get_user_by('id', $user_id);
            error_log('[Frontegg SSO] Created new user: ' . $user_email);
        } else {
            error_log('[Frontegg SSO] Existing user found: ' . $user_email);
        }

        // --- Step 3: Set session properly ---
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true); // true = persistent cookie
        do_action('wp_login', $user->user_login, $user);
        error_log('[Frontegg SSO] User authenticated and session created.');

        // --- Step 4: Redirect after cookie is set ---
        wp_safe_redirect(home_url());
        exit;
    }

    public function handle_saml_logout_response()
    {
        if (get_query_var('saml_logout_response') != '1')
            return;

        $redirect_url = home_url();
        if (!empty($this->settings['post_logout_redirect'])) {
            $redirect_url = esc_url_raw($this->settings['post_logout_redirect']);
        }

        if (!empty($_GET['RelayState'])) {
            $redirect_url = esc_url_raw($_GET['RelayState']);
        }

        wp_logout();
        wp_safe_redirect($redirect_url);
        exit;
    }

    private function extract_email_from_saml_response($response)
    {
        $decoded = base64_decode($response);
        if (!$decoded)
            return false;

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($decoded);
        if (!$xml)
            return false;

        $namespaces = $xml->getNamespaces(true);
        $assertion = $xml->children($namespaces['saml'])->Assertion;
        if (!$assertion)
            return false;

        $subject = $assertion->children($namespaces['saml'])->Subject;
        $name_id = $subject->children($namespaces['saml'])->NameID ?? '';

        $email = trim((string) $name_id);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }

    private function login_or_register_user($email)
    {
        $user = get_user_by('email', $email);
        if (!$user) {
            $user_id = wp_create_user($email, wp_generate_password(), $email);
            $user = get_user_by('id', $user_id);
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        do_action('wp_login', $user->user_login, $user);
    }

    public function custom_logout_url($logout_url, $redirect)
    {
        $slo_url = $this->settings['slo_url'] ?? '';
        if (empty($slo_url))
            return $logout_url;

        $current_user = wp_get_current_user();
        if (!$current_user || empty($current_user->user_email))
            return $logout_url;

        $name_id = $current_user->user_email;
        $saml_request = $this->generate_logout_request($name_id);
        $vendor_host = urlencode(home_url());
        $relay_state = urlencode(home_url('/saml-logout-response'));

        return $slo_url . '?SAMLRequest=' . urlencode($saml_request) . '&vendorHost=' . $vendor_host . '&RelayState=' . $relay_state;
    }

    private function generate_logout_request($name_id)
    {
        $entity_id = $this->get_entity_id();
        $slo_url = $this->settings['slo_url'] ?? '';
        $request_id = '_' . uniqid();
        $issue_instant = gmdate('Y-m-d\TH:i:s\Z');

        $saml_request = <<<XML
<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="{$request_id}" Version="2.0" IssueInstant="{$issue_instant}" Destination="{$slo_url}">
    <saml:Issuer>{$entity_id}</saml:Issuer>
    <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">{$name_id}</saml:NameID>
</samlp:LogoutRequest>
XML;

        return base64_encode(gzdeflate($saml_request));
    }

    public function get_entity_id()
    {
        return get_site_url() . '/frontegg-sp-metadata';
    }

    public function get_acs_url()
    {
        return get_site_url() . '/saml-response';
    }

    public function get_slo_response_url()
    {
        return get_site_url() . '/saml-logout-response';
    }

    public function validate_certificate($pem)
    {
        $clean_pem = trim($pem);
        if (empty($clean_pem))
            return false;
        $resource = openssl_x509_read($clean_pem);
        if (!$resource)
            return false;
        openssl_x509_free($resource);
        return true;
    }
}

new Frontegg_SAML_SSO();

// Activation + Deactivation
register_activation_hook(__FILE__, function () {
    if (!get_option('frontegg_saml_settings'))
        add_option('frontegg_saml_settings', []);
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $links[] = '<a href="' . esc_url(admin_url('admin.php?page=frontegg_saml_sso')) . '">Settings</a>';
    return $links;
});

add_action('plugins_loaded', function() {
    $stored_version = get_option('frontegg_saml_version');
    if ($stored_version !== FRONTEGG_SAML_VERSION) {
        flush_rewrite_rules();
        update_option('frontegg_saml_version', FRONTEGG_SAML_VERSION);
        error_log('[Frontegg SSO] Rewrite rules flushed automatically due to version change.');
    }
});
