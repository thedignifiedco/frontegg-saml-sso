<?php if (!defined('ABSPATH'))
    exit; ?>

<div class="wrap">
    <h1>
        <?php
        echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/img/frontegg-icon.png') . '" alt="Frontegg Logo" height="24" style="vertical-align:middle;margin-right:5px;" />';
        ?>
        Frontegg SAML SSO Settings
    </h1>

    <?php if (!empty($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('frontegg_saml_save_action'); ?>

        <div class="frontegg-accordion">

            <!-- Step 1 -->
            <div class="accordion-item">
                <button type="button" class="accordion-toggle" aria-expanded="true">1. Create a Frontegg SAML
                    Application</button>
                <div class="accordion-panel" style="display:block;">
                    <p>
                        First, add this website as a new SAML app in your Frontegg Dashboard using
                        <a href="https://developers.frontegg.com/guides/management/frontegg-idp/via-saml"
                            target="_blank" rel="noopener noreferrer">this guide</a>.
                    </p>
                    <p><strong>Note:</strong> This plugin replaces the default WordPress login experience with Frontegg.
                    </p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="accordion-item">
                <button type="button" class="accordion-toggle" aria-expanded="false">2. Copy SP values to
                    Frontegg</button>
                <div class="accordion-panel">
                    <table class="form-table">
                        <tr>
                            <th>SP Entity ID</th>
                            <td>
                                <code id="sp-entity"><?php echo esc_html($plugin->get_entity_id()); ?></code>
                                <button type="button" class="button"
                                    onclick="copyToClipboard('sp-entity', this)">Copy</button>
                            </td>
                        </tr>
                        <tr>
                            <th>ACS URL</th>
                            <td>
                                <code id="sp-acs"><?php echo esc_html($plugin->get_acs_url()); ?></code>
                                <button type="button" class="button"
                                    onclick="copyToClipboard('sp-acs', this)">Copy</button>
                            </td>
                        </tr>
                        <tr>
                            <th>Logout Response URL</th>
                            <td>
                                <code id="sp-slo"><?php echo esc_html($plugin->get_slo_response_url()); ?></code>
                                <button type="button" class="button"
                                    onclick="copyToClipboard('sp-slo', this)">Copy</button>
                            </td>
                        </tr>
                    </table>
                    <div class="config-warning" style="margin-top: 1em;">
                        <p><strong>Important:</strong> Ensure these values are also added to your Frontegg <a
                                href="https://developers.frontegg.com/guides/env-settings/hosted-embedded"
                                target="_blank">Authorized Redirect URLs</a> and <a
                                href="https://developers.frontegg.com/guides/troubleshoot/errors/frontend-integration-issues#why-am-i-receiving-cors-errors-when-trying-to-load-my-app"
                                target="_blank">Allowed Origins</a>.</p>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="accordion-item">
                <button type="button" class="accordion-toggle" aria-expanded="false">3. Paste your Frontegg SAML
                    Application details</button>
                <div class="accordion-panel">
                    <table class="form-table">
                        <tr>
                            <th><label for="sso_url">SSO URL</label></th>
                            <td><input type="url" name="frontegg_saml[sso_url]" id="sso_url"
                                    value="<?php echo esc_attr($settings['sso_url'] ?? ''); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="slo_url">Logout URL</label></th>
                            <td><input type="url" name="frontegg_saml[slo_url]" id="slo_url"
                                    value="<?php echo esc_attr($settings['slo_url'] ?? ''); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="certificate">Certificate</label></th>
                            <td>
                                <textarea name="frontegg_saml[certificate]" id="certificate" class="large-text"
                                    rows="8"><?php echo esc_textarea($settings['certificate'] ?? ''); ?></textarea>
                                <p class="description">Paste the full X.509 PEM certificate including the
                                    <code>-----BEGIN CERTIFICATE-----</code> lines.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="accordion-item">
                <button type="button" class="accordion-toggle" aria-expanded="false">4. Customize Redirects</button>
                <div class="accordion-panel">
                    <table class="form-table">
                        <tr>
                            <th><label for="post_logout_redirect">Post Logout Redirect (optional)</label></th>
                            <td>
                                <input type="url" name="frontegg_saml[post_logout_redirect]" id="post_logout_redirect"
                                    value="<?php echo esc_attr($settings['post_logout_redirect'] ?? ''); ?>"
                                    class="regular-text">
                                <p class="description">Users will be redirected here after logout. If empty, it defaults
                                    to the homepage.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div><!-- /.frontegg-accordion -->

        <p class="submit">
            <input type="submit" name="frontegg_saml_save" class="button button-primary" value="Save Settings">
        </p>
    </form>

    <hr>
    <p style="opacity: 0.6; font-size: 11px;">
        Frontegg SAML SSO version <?php echo esc_html(FRONTEGG_SAML_VERSION); ?>
    </p>
</div>