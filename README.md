# Frontegg SAML SSO for WordPress

> Adds SAML Single Sign-On (SSO) authentication to WordPress using Frontegg as the Identity Provider.

---

## Features

- Frontegg SAML SSO integration for WordPress
- Single Logout (SLO) support
- Customizable ACS & SLO URLs
- Automatic user provisioning
- Automatic login, logout, and metadata endpoints

---

## Requirements

- WordPress >= 5.0
- PHP >= 7.4
- Frontegg SAML Application setup (see [Frontegg Docs](https://developers.frontegg.com/guides/management/frontegg-idp/via-saml))
- HTTPS enabled on your WordPress site

---

## Installation

1. Clone this repository into your `wp-content/plugins` directory:
   ```bash
   git clone https://github.com/thedignifiedco/frontegg-wordpress.git

2. Activate the plugin in the WordPress admin under Plugins.
3. Configure the plugin under Frontegg SAML SSO in your WordPress admin menu.

---

## Usage

1. Setup your Frontegg SAML Application
2. Copy ACS URL, Entity ID, and Logout URL from the plugin settings page
3. Paste them into your Frontegg SAML settings
4. Paste the Frontegg SAML Certificate into the plugin configuration

