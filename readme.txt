=== Configurable Hotlink Protection ===
Contributors: deltafactory
Tags: htaccess, mod_rewrite, hotlink, protection
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.2

Save bandwidth by easily blocking links to video, audio, and other files from unapproved 3rd-party sites. Requires mod_rewrite.

== Description ==

Save bandwidth by easily blocking links to video, audio, and other files from unapproved 3rd-party sites.

= Features =
* Choose from a list of common file extensions and include your own
* Allow linking from multiple authorized websites
* Selectively control direct linking
* Generate the rules for your `.htaccess` file with minimal effort

This plugin modifies the site's .htaccess file and requires mod_rewrite or compatible modules like [ISAPI_rewrite for IIS](http://www.helicontech.com/isapi_rewrite/).

= Notes =
This plugin was inspired by the [Hotlink Protection](http://wordpress.org/extend/plugins/wordpress-automatic-image-hotlink-protection/) plugin. There was a need for a more flexible implementation and so this plugin was created.

== Installation ==

For most, the built-in plugin installation system for WordPress will be all you need.
Alternately, uploading the zip file to the installer should also work. For those who prefer manual installation:

1. Unzip `configurable-hotlink-protection.zip` and upload the contents into the `/wp-content/plugins` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure options under `Settings -> Hotlink Protection`

== Frequently Asked Questions ==

* Ask questions and we'll try to answer them.

== Screenshots ==

1. The settings screen for version 0.1

== Changelog ==

= 0.2 =
* Settings page layout improvements
* Added screenshot

= 0.1 =
* Initial release

== Upgrade Notice ==

= 0.2 =
Settings page layout improvements

= 0.1 =
Initial release

