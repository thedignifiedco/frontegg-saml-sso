<?php /** @var Frontegg_SAML_SSO $plugin */ ?>
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

    <h2 class="nav-tab-wrapper">
        <a href="#tab1" class="nav-tab nav-tab-active">1. Create a Frontegg SAML Application</a>
        <a href="#tab2" class="nav-tab">2. Copy the details below to Frontegg</a>
        <a href="#tab3" class="nav-tab">3. Configure your Frontegg IdP</a>
    </h2>

    <div id="tab1-content" class="tab-content" style="display:block;">
        <p>
            First add this website as a new SAML app in your Frontegg Dashboard using <a
                href="https://developers.frontegg.com/guides/management/frontegg-idp/via-saml" target="_blank"
                rel="noopener noreferrer">this guide</a>.
        </p>
    </div>

    <div id="tab2-content" class="tab-content" style="display:none;">
        <table class="form-table">
            <tr>
                <th>SP Entity ID</th>
                <td>
                    <code id="sp-entity"><?php echo esc_html($plugin->get_entity_id()); ?></code>
                    <button type="button" class="button" onclick="copyToClipboard('sp-entity')">Copy</button>
                </td>
            </tr>
            <tr>
                <th>ACS URL</th>
                <td>
                    <code id="sp-acs"><?php echo esc_html($plugin->get_acs_url()); ?></code>
                    <button type="button" class="button" onclick="copyToClipboard('sp-acs')">Copy</button>
                </td>
            </tr>
            <tr>
                <th>Logout Response URL</th>
                <td>
                    <code id="sp-slo"><?php echo esc_html($plugin->get_slo_response_url()); ?></code>
                    <button type="button" class="button" onclick="copyToClipboard('sp-slo')">Copy</button>
                </td>
            </tr>
        </table>
    </div>

    <div id="tab3-content" class="tab-content" style="display:none;">
        <form method="post">
            <?php wp_nonce_field('frontegg_saml_save_action'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="sso_url">SSO URL</label></th>
                    <td><input type="url" name="frontegg_saml[sso_url]" id="sso_url"
                            value="<?php echo esc_attr($settings['sso_url'] ?? ''); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="slo_url">SLO URL</label></th>
                    <td><input type="url" name="frontegg_saml[slo_url]" id="slo_url"
                            value="<?php echo esc_attr($settings['slo_url'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="certificate">Certificate</label></th>
                    <td>
                        <textarea name="frontegg_saml[certificate]" id="certificate" rows="8"
                            class="large-text code"><?php echo esc_textarea($settings['certificate'] ?? ''); ?></textarea>
                        <p class="description">Paste the full X.509 PEM certificate including the
                            <code>-----BEGIN CERTIFICATE-----</code> lines.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="post_logout_redirect">Post Logout Redirect (optional)</label></th>
                    <td>
                        <input type="url" name="frontegg_saml[post_logout_redirect]" id="post_logout_redirect"
                            value="<?php echo esc_attr($settings['post_logout_redirect'] ?? ''); ?>"
                            class="regular-text">
                        <p class="description">Users would be redirected to this page after logout. If empty, defaults
                            to homepage.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="frontegg_saml_save" class="button button-primary" value="Save Settings">
            </p>
        </form>
    </div>

    <hr>
    <p style="opacity: 0.6; font-size: 11px;">Frontegg SAML SSO version <?php echo esc_html(FRONTEGG_SAML_VERSION); ?>
        &mdash; Replaces the WordPress login screen.</p>
</div>