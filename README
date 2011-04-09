=== WP Parallel Loading System ===
Contributors: joseairosa
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=QMWD392MKS8UW&lc=PT&item_name=Jose%20P%2e%20Airosa&item_number=WordPress%20Plugin%20%28wp%2dpls%29&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: image optimization, loading, parallel, plugin, seo, speed improve, system, Wordpress
Requires at least: 2.9.0
Tested up to: 3.0.2
Stable tag: 0.1.9.1

The WP-PLS (short for Parallel Loading System) is a WordPress plugin that will enhance the loading efficiency of your Blog.

== Description ==

**Why use it?**

If you have an image intensive Blog, or even if you don’t, this plugin will boost the loading time of your Blog. The more images you have the more it will improve.

The amount of people that will visit your website for the first time or visit with an empty cache or disabled cache is huge, therefore, the best your website performs on that first visit the more chances that person will remain to view your website.

It is also known that nowadays, Google (and other search engines), are taking into account, for ranking purposes, your website page speed. Google Bot does not conserve a cache when it visits your website, so, the faster your website loads the more chances you have to rank higher.

This plug will not change anything on your Blogs Posts. Every change that it makes will be processed in real time, right before the HTML code of your Blog is sent to the browser.
There are however some changes needed on a few files, but the plugin will attempt to do everything by himself without causing harm to your files. For every file that he changes a backup is created, therefore, at any time, you can revert the changes back!



**How does it work?**

Standard HTTP v1.1 requests only allow 2 connections, at the same time, from the same domain.

This plugin will virtualize connections, through defined subdomains. You can have as many subdomains as you like, but I do recommend using a maximum of 5.



**Plugin features?**

* Automatic Image Optimization.
* Parallelize HTTP connections.
* Page speed improvement.
* Auto-Path find (Automatically find server root path, sub-domain path, plugin resources path…).
* Cache loss minimization.
* Sub-domain real-time health tracking.
* Auto recovery from resource files bad sync.
* Ability to activate / deactivate sub-domains
* Plugin Updates aware. (automatically re-sync files and database after an update)



**Requirements?**

The plugin is programmed to be aware of this requirements, and if not fulfilled it will not change anything on your Blog.
*It will not break your Blog code!*

* Your server needs to have a working GD Library module for PHP.
* You should have a PHP installation with a minimum version of 5.1.3
* Access to your server Administration Painel (cPanel, DirectAdmin, Plesk) in order to create new sub-domains for your domain.


== Installation ==

1. First you need to download it from WordPress Plugin Repository.
2. Upload the contents of the compacted file to your plugin folder on your WordPress installation.
3. Go to your WordPress Administration page and activate the Plugin (Plugins → Installed → WP-PLS → Activate)
4. Go to Settings → Parallel Loading System


== Frequently Asked Questions ==

= Plugin is telling me that I don't have permissions to edit the files =

If for any reason the plugin is telling you that it doesn’t have permissions to alter a given file you will need to access your server, using your favorite FTP Client (Filezilla, Cyberduck, FlashFXP…) and change the permissions of the file to 777. You can revert them back to 644 (normal permissions of a file) after the plugin finishes the modifications.

= My sub-domain health is is returning a 500 HTTP error code =

If you get a sub-domain health notification stating that your sub-domain is returning a 500 HTTP error code the most probable explanation is that your sub-domain folder has 777 permissions. Revert them back to 755 and you should be fine.

= I have activated the plugin but I get a permissions notification =

WP-PLS will automatically try to do everything for himself. One of his biggest restrictions is file permissions. If you get a notification that you should change the permissions of a given file access using your favorite FTP Client and change its permissions.
There are a lot of tutorials on how to do this as this is specific to your FTP Client

= I cannot see .htaccess on my FTP Client =
This is normal. A file started with a "." is interpreted as a hidden file, therefore, you need to activate "Show hidden files" on your FTP Client

= I'm using a NGINX HTTP server. Will it work? =
It will but it does require additional configuration and a bit more IT knowledge. All information will be given by the plugin if he sees fit. Let me know if you have any suggestion.

= I'm using Window Server. Will it work? =
I have been reported that it work with some limitations by a few users, but it does require additional configuration and a bit more IT knowledge. All information will be given by the plugin if he sees fit. Let me know if you have any suggestion.

= I'm getting a Warning: get_headers() =
This is probably because your sub-domain doesn't have http:// or https:// at the start.

= I've configured my sub-domain on apache level myself and its DocumentRoot folder is the same as my main domain. Will it work? =
Yes it will. Since version 0.1.3 that it will detect if we're dealing with WordPress .htaccess and if it's the case it will append to the file instead of replacing it.

= Is the plugin safe? =
Like everything in life, nothing is 100% safe. However I've implemented a lot of rules to the plugin to avoid any big issues.
If for any reason you have problems with the plugin there are always backups on each of the sub-domain folders. You just need to back it up with the one you like. "Nothing is lost, only transformed" :)

== Screenshots ==

1. Overall view of the active and inactive sub-domain management.
2. Adding new sub-domain form.
3. Results of before and after application of this plugin.
3. Results of before and after when viewed under FireBug Page Speed Performance Analizer.

== Changelog ==

= 0.1.9.1 =
* Fixed a bug where the version of the plugin was not updated at the plugin page

= 0.1.9 =
* Added support for timthumb.php and other thumbnail generators (Thank you all that sent feedback)

= 0.1.8 =
* Changed the way the plugin detects what OS you're on. (Thank you Christian)
* Changed the way the plugin detects folder separators. (Thank you Christian)
* Added support for when your blog is itself installed on a subdomain
* Added information on adding index.php to your subdomains

= 0.1.7.1 =
* You can now change the root path at any time.
* Bug fixes.

= 0.1.7 =
* Few changes to better adapt to version 3.0 of WordPress

= 0.1.6.2 =
* Bug fixes. (Thank you Joe for the help)

= 0.1.6.1 =
* Bug fixes. (Thank you Jan Waldeck for the help)
* Added awareness to Cache plugins.

= 0.1.6 =
* New pre-defined file system has been added.
* Greatly improved compatibility with Plesk systems and systems that have open_basedir restrictions active.
* System should be more aware of your configuration and more adaptable.

= 0.1.5 =
* Better compatibility with a wider range of systems.
* BUG FIX - Plugin will now only change links that are inside a <img> tag.
* More flexibility when adding a sub-domain. It should not be so rigorous.
* Added a compatibility check for sub-domains health. Your system might not able to support the technology required to do these checks.

= 0.1.4 =
* Improved plugin flexibility.
* Added Options section on Plugin Administration.
* OPTIONS - You can now specify if you want to use Simple Method or Normal Method (Simple Method will not require most of the paths and configurations, however it will not be as effective and fast).
* OPTIONS - You can now also apply PLS to external images! This will be activated by default when you activate your plugin.
* When deactivated the plugin will revert and changes made to WordPress own .htaccess.

= 0.1.3 =
* Improved plugin code and performance.
* If the plugin detects WordPress own .htaccess it will not replace, instead there will be an append to the file. (A backup is always created)
* Backups are only created if the files have different sizes.
* Alert messages have been enhanced for better readability.
* Added checks for mod_rewrite and mod_headers. Plugin will not activate if these are not found. (Thank you Jochen)
* Added documentation to plugin page explaining how the plugin health works. (Thank you Jochen)
* Sub-domain will be automatically disabled if it's not healthy.
* Added preliminary support for NGINX and Windows Server. This is still work in progress. (Thank you Mastershake)
* Corrected "/" for Windows Server, "\" should now be used. (Thank you Ron)
* Added better information on adding a new Sub-Domain where http:// or https:// should be used. (Thank you Jochen)
* Added more cache control on images. This should improve even more the performance.
* Added third-party plugins compatibility. If you're using a plug that has known compatibility issue the plugin will report this on Compatibility list.
* Changed load_image.php code.
* Changed .htaccess code.
* Improved documentation on Plugin WordPress Repository. (FAQ)

= 0.1.2 =
* Improved plugin integrity
* Added backup functionality so that when the plugin tries to update system files on subdomains it will create a backup of the old file and place it on the same folder

= 0.1.1 =
* Corrected some bugs
* Added functionality to provide root path manually

= 0.1 =
* Initial release
* Automatic Image Optimization.
* Parallelize HTTP connections.
* Page speed improvement.
* Auto-Path find (Automatically find server root path, sub-domain path, plugin resources path…).
* Cache loss minimization.
* Sub-domain real-time health tracking.
* Auto recovery from resource files bad sync.
* Ability to activate / deactivate sub-domains
* Plugin Updates aware. (automatically re-sync files and database after an update)

== Upgrade Notice ==

= 0.1.9.1 =
* Version bug fix.

= 0.1.9 =
* Thumbnail generators support.

= 0.1.8 =
* Improvements and new functionalities.

= 0.1.7.1 =
* Bug fixes and new functionality.

= 0.1.7 =
* WordPress 3.0 compatibility.

= 0.1.6.2 =
* This update fixes a bug where you wouldn't be able to add a subdomain, even with Simple Method active.

= 0.1.6.1 =
* Bug fixes

= 0.1.6 =
* This update addresses bugs related with open_basedir restricted systems.

= 0.1.5 =
* The system should be more lightweight when it comes to add subdomains and a few bugs have been fixed.

= 0.1.4 =
* This update will allow you to load external images. A new section was also added where you can define some options.

= 0.1.3 =
* Added a lot of user requests and suggestions. This will improve greatly plugin stability.

= 0.1.2 =
* This is a security and integrity improvement with backup functionality.

= 0.1.1 =
* Bug correction and added functionality to provide root path manually.

= 0.1 =
* Initial release.

== Additional help ==

You can find additional information about adding new sub-domains to your server administration system (cPanel, DirectAdmin, Plesk) by visiting this plugin homepage: http://www.joseairosa.com/2010/05/17/wordpress-plugin-parallel-loading-system/

== What's in the cooking pan? ==

This was only the first release of the plugin. I've got a lot of new ideas that I would like to see implemented on next releases.
Some of them are:

* Also parallelize CSS and JavaScript loading.
* Add support to also load external images. At the moment it will only load locally stored images. (DONE)
* Add real-time rewrite functions to CSS and JavaScript. That way, images that have been called from the CSS file will also be parallelized.
* Overall improve of the code. (DONE & Ongoing)
* Add options to manage image quality rendering.
* Bug fixes (when found or reported).
* And much more...

If you have any features that you would like to see implemented, please don't hesitate to tweet me (http://twitter.com/joseairosa) or mail me (me@joseairosa.com) :)

== Thank you! ==
I would like to give a big thank you to Marco Sousa (http://twitter.com/h1brd) from Scarletbits.com (http://scarletbits.com) and Filipe Oliveira (http://twitter.com/bluekora) for the help and support given! Without them this would not be possible.