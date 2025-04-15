=== Frontegg SAML SSO ===
Contributors: Frontegg, Dignified Sorinolu-Bimpe
Tags: saml, sso, login, authentication, single sign-on
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Securely replace WordPress login and logout flows with Frontegg’s enterprise-ready SAML SSO authentication.

== Description ==

This plugin replaces the native WordPress login experience with secure, standards-based Single Sign-On (SSO) via Frontegg using the SAML 2.0 protocol.

- Supports login, logout, and user auto-provisioning
- Provides SP metadata (Entity ID, ACS URL, Logout Response URL)
- Fully compatible with Frontegg’s Identity Provider (IdP)
- No code configuration with a polished WordPress admin UI
- Fully compliant with WordPress Plugin Review Guidelines

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/frontegg-saml-sso`
2. Activate the plugin through the WordPress Admin
3. Go to the **Frontegg SAML SSO** menu item
4. Follow the step-by-step tabs to configure and copy required data
5. Login and logout will now be handled via Frontegg

== Screenshots ==

1. Settings UI with tabs: Create App, Copy SP Metadata, Configure Frontegg IdP
2. Copy-paste-friendly fields for Entity ID, ACS URL, and Logout URL
3. Certificate input and post-logout redirect support

== Changelog ==

= 1.0.0 =
* Initial stable release.
* Fully supports Frontegg login, logout, and user session creation.
* Includes SP metadata generation, copy UI, and branded tabbed admin panel.

== Upgrade Notice ==

= 1.0.0 =
First public release of the plugin. Replace your WordPress login with SAML SSO via Frontegg.

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