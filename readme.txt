=== Ocasio Hide Version ===
Contributors: ocas
Tags: hide version, remove version, wordpress version, security, remove ver
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Removes the WordPress version number from your website source code, feeds, and asset URLs for enhanced security.

== Description ==

Broadcasting your exact WordPress version tells hackers and automated bots which security holes to target on your website.

Ocasio Hide Version stops this leak instantly. It strips the generator meta tag from your site header, cleans version tags from all your RSS and Atom feeds, and removes the `?ver=` query strings from your CSS and JavaScript files.

It runs quietly in lightweight PHP with zero database clutter and zero front-end overhead.

Managed directly from your unified Ocasio Plugins dashboard or dedicated settings card.

= Features =

* **Generator Tag Removal:** Automatically strips the WordPress version generator tag from your site's `<head>`.
* **Feed Security:** Removes version tags from RSS, Atom, RDF, and OPML feeds.
* **Asset Query String Cleanup:** Strips `?ver=x.x.x` query strings from stylesheets, scripts, and modules.
* **Zero Bloat:** Lightweight PHP execution with 0 bytes of extra CSS or JavaScript added to public pages.
* **Instant Dashboard Switch:** Turn the feature on or off anytime with a single toggle.

== Installation ==

1. Upload the `ocasio-hide-version` folder to your `/wp-content/plugins/` directory, or install the zip file directly through your WordPress admin screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open **Ocasio Plugins -> Dashboard** in your sidebar to verify the Active on Site switch is enabled.

== Frequently Asked Questions ==

= Why should I hide my WordPress version? =
When bots scan your site, they look for outdated WordPress version numbers to target known software flaws. Hiding your version makes it much harder for automated tools to profile your site.

= Does this break stylesheet or script caching? =
No. It only removes the trailing `?ver=x.x.x` query string from your asset links, allowing standard browser and CDN caching to work normally.

= Does this slow down my website? =
No. It doesn't load any external stylesheets, JavaScript files, or tracking scripts. It runs on lightweight WordPress PHP filters.

= Does it change anything in my database? =
No. It only filters output dynamically on the fly and doesn't alter your database records.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Automated generator meta tag removal from site header.
* Automated version stripping from RSS, Atom, RDF, and OPML feeds.
* Query string `?ver=` removal from CSS, JS, and script modules.
* Integrated into the unified Ocasio Plugins suite dashboard.
