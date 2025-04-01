<?php /** @var Frontegg_SAML_SSO $plugin */ ?>
<div class="wrap">

    <h1>
        <img src="<?php echo esc_url(plugins_url('assets/img/frontegg-icon.png', __FILE__)); ?>"
            style="height:24px;vertical-align:middle;margin-right:5px;" />
        Frontegg SAML SSO Settings
    </h1>

    <h2 class="nav-tab-wrapper">
        <a href="#tab1" class="nav-tab nav-tab-active">1. Create a Frontegg SAML Application</a>
        <a href="#tab2" class="nav-tab">2. Copy the SP details into Frontegg</a>
        <a href="#tab3" class="nav-tab">3. Paste your Frontegg SAML Application details</a>
    </h2>

    <div id="tab1-content" class="tab-content" style="display:block;">
        <h2>Step 1 — Create a Frontegg SAML Application</h2>
        <p>
            First, add this website as a new SAML application in your Frontegg Dashboard following
            <a href="https://developers.frontegg.com/guides/management/frontegg-idp/via-saml" target="_blank">this
                guide</a>.
        </p>
    </div>

    <div id="tab2-content" class="tab-content" style="display:none;">
        <h2>Step 2 — Copy these details into Frontegg</h2>
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
        <h2>Step 3 — Paste your Frontegg SAML Application details below</h2>
        <form method="POST" action="">
            <?php wp_nonce_field('frontegg_saml_save_action'); ?>

            <table class="form-table">
                <tr>
                    <th>SSO URL</th>
                    <td><input type="url" name="frontegg_saml[sso_url]"
                            value="<?php echo esc_attr($settings['sso_url'] ?? ''); ?>" size="60" required></td>
                </tr>
                <tr>
                    <th>SLO URL</th>
                    <td><input type="url" name="frontegg_saml[slo_url]"
                            value="<?php echo esc_attr($settings['slo_url'] ?? ''); ?>" size="60"></td>
                </tr>
                <tr>
                    <th>Certificate (X.509 PEM)</th>
                    <td>
                        <textarea name="frontegg_saml[certificate]" rows="8" cols="80"
                            placeholder="-----BEGIN CERTIFICATE-----"><?php echo esc_textarea($settings['certificate'] ?? ''); ?></textarea>
                        <p class="description">Include the BEGIN/END lines exactly as provided by Frontegg.</p>
                        <?php if (!empty($settings['certificate'])): ?>
                            <?php if ($plugin->validate_certificate($settings['certificate'])): ?>
                                <p><strong>✅ Certificate is valid.</strong></p>
                            <?php else: ?>
                                <p><strong>⚠️ Certificate is invalid.</strong></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Post-Logout Redirect URL (Optional)</th>
                    <td><input type="url" name="frontegg_saml[post_logout_redirect]"
                            value="<?php echo esc_attr($settings['post_logout_redirect'] ?? ''); ?>" size="60">
                            <p class="description">Users would be redirected to this page after logout. If empty, defaults to homepage</p></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="frontegg_saml_save" class="button-primary" value="Save Settings" />
            </p>
        </form>
    </div>

    <hr>
    <p style="opacity:0.6;font-size:11px;">Frontegg SAML SSO Version <?php echo esc_html(FRONTEGG_SAML_VERSION); ?> |
        Powered by Frontegg</p>
</div>

<script>
    // Simple tabs
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            this.classList.add('nav-tab-active');
            document.querySelector(this.getAttribute('href') + '-content').style.display = 'block';
            history.replaceState(null, null, this.getAttribute('href'));
        });
    });
    // Auto-restore last tab
    document.addEventListener('DOMContentLoaded', () => {
        const hash = location.hash || '#tab1';
        document.querySelector('a[href="' + hash + '"]')?.click();
    });

    // Copy buttons
    function copyToClipboard(id) {
        const el = document.getElementById(id);
        navigator.clipboard.writeText(el.textContent).then(() => alert('Copied to clipboard!'));
    }
</script>