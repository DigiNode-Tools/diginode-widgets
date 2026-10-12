=== DigiNode Widgets ===
Contributors: saltedlolly
Tags: digibyte, diginode, release, banner, shortcode
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show the latest DigiNode Tools release on your site.

== Description ==

Adds the [diginode_latest_release] shortcode: a small banner with the latest DigiNode Tools release (version, date, a one-line summary and a link to the release notes). When changes are waiting to be released, it also shows the date of the next release. It updates itself, and uses your site's font and colours.

Options: lines="1", next="no", logo="yes|no", label="...", link="https://...". See https://github.com/DigiNode-Tools/diginode-widgets

The banner's information comes from a public file published with each DigiNode Tools release, on GitHub: https://raw.githubusercontent.com/DigiNode-Tools/diginode-tools/main/docs/latest-release.json (checked at most once an hour). No information about your site or its visitors is sent anywhere.

== Changelog ==

= 1.0.0 =
* First release: the latest release banner.
