=== ETH Redirect to Latest Post ===
Contributors: ethitter
Donate link: https://ethitter.com/donate/
Tags: latest, latest post, redirect, redirect latest, redirect post
Requires at least: 4.5
Tested up to: 6.0
Stable tag: 0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Redirect a chosen slug, "latest" by default, to, well, the most-recently-published post.

== Description ==

Once activated, a given slug will redirect to whatever is the most-recently-published post on the site. By default, the slug is `latest`, but it can be changed from the Permalinks settings screen.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/eth-redirect-to-latest` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. If desired, change the slug from the 'Permalinks' screen in WordPress.

== Frequently Asked Questions ==

= Can I redirect to something other than a post? =

Yes, using the `eth_redirect_to_latest_post_query_args` or `eth_redirect_to_latest_post_redirection` filters introduced in v0.3.

== Changelog ==

= 0.3 =
* Introduce filters to make redirection more flexible.
* Add unit tests and conform to coding standards.

= 0.2.2 =
* Handle sites using slug-only permalinks

= 0.2.1 =
* Correct Composer path error

= 0.2 =
* Initial release
