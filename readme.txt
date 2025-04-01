=== Frontegg SAML SSO for WordPress ===
Contributors: Dignified Sorinolu-Bimpe
Tags: saml, sso, single sign-on, frontegg, authentication
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT License
License URI: https://mit-license.org/

== Description ==
Adds SAML Single Sign-On (SSO) authentication to WordPress using Frontegg as your Identity Provider (IdP).

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/frontegg-saml-sso` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Follow the instructions in the plugin settings page to configure SAML with your Frontegg application.

== Frequently Asked Questions ==

= Will this replace the default WordPress login? =
Yes, it will route users through Frontegg.

= Does it support Single Logout (SLO)? =
Yes, if configured properly in Frontegg and WordPress.

= Do I need an SSL certificate? =
Yes. Your WordPress site must be served over HTTPS.

== Changelog ==

= 1.0.0 =
* Initial release with:
    - SSO
    - SLO
    - Admin Settings Page
    - Metadata endpoint
    - Automatic rewrite rules management

== Upgrade Notice ==
Always backup your site before updating.
