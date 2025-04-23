=== Frontegg SAML SSO ===
Contributors: Frontegg, Dignified Sorinolu-Bimpe
Tags: saml, sso, login, authentication, single sign-on
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace the WordPress login and logout flows with secure SAML-based authentication via Frontegg. Easily configure your SSO app from the admin panel.

== Description ==

Frontegg SAML SSO replaces the default WordPress login and logout experiences with seamless SAML authentication via [Frontegg](https://frontegg.com).

This plugin is designed for modern SaaS and enterprise WordPress environments where you need to enforce login via an external identity provider (IdP).

It includes:
- 🔐 Secure SAML 2.0 login and logout
- 📋 Admin-friendly configuration of SSO URLs and certificate
- 📎 Auto-generated SP (Service Provider) values (Entity ID, ACS URL, SLO URL)
- 🧭 Redirect control after logout
- 🔄 Auto-redirects from `wp-login.php` to Frontegg
- ✨ Clean and accessible admin UI using native WordPress components

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/frontegg-saml-sso/`
2. Activate the plugin from the Plugins menu in WordPress
3. Go to **Frontegg SAML SSO** in the admin menu
4. Follow the 4-step configuration:
   - **Step 1:** Create a SAML Application in your Frontegg Dashboard
   - **Step 2:** Copy SP values (Entity ID, ACS URL, Logout URL) into Frontegg
   - **Step 3:** Paste your Frontegg SSO/SLO URLs and certificate into WordPress
   - **Step 4 (Optional):** Set a custom redirect after logout

== Frequently Asked Questions ==

= Can I still use wp-login.php to log in? =
No. This plugin fully replaces the WordPress login screen with Frontegg's SSO flow.

= What happens if a user does not already exist in WordPress? =
The plugin auto-creates a new user using the email address from the SAML assertion.

= Where do I find my Frontegg SSO URL and certificate? =
In your Frontegg Dashboard under the SAML application settings.

= What should I use as my SAML ACS URL and Entity ID? =
After activation, visit the plugin settings page to view copy-paste ready values.

== Screenshots ==

1. Frontegg SAML SSO admin settings page
2. Copy-paste SP values to Frontegg
3. Configure SSO, SLO URLs, and certificate

== Changelog ==

= 1.0.1 =
* Full WordPress.org Plugin Check compliance
* Improved admin UX with accordion layout and inline feedback
* Added nonce validation, input sanitization, and rewrite rule safety
* Fixed logout flow redirect and session handling
* Updated SP values and copy buttons
* Added admin notice after version bump

= 1.0.0 =
* Initial stable release
* Basic SAML login/logout functionality with Frontegg
* Admin form for configuration
* Auto-create user from SAML response

== Upgrade Notice ==

= 1.0.1 =
All users should upgrade to ensure compatibility with WordPress security standards and plugin repository requirements.

== Frequently Asked Questions ==

= Will this replace the default login screen? =
Yes. When configured, users will be redirected to Frontegg's login instead of wp-login.php.

= Does it support user creation? =
Yes. If a user logs in from Frontegg and does not exist in WordPress, a new account will be automatically created.

= Can I configure a post-logout redirect? =
Yes. You can define a URL to redirect users to after successful logout from Frontegg.

= Is nonce verification used? =
Yes, for all admin operations. SAML POST responses (from the IdP) do not include nonce — those routes are documented as exceptions.

== License ==
This plugin is licensed under the GPL v2.0 or later. See LICENSE.txt for details.