=== WP-eDel post copies ===
Contributors: Esteban Truelsegaard
Donate link: http://www.netmdp.com
Tags: posts, copies, duplicate posts, delete copies, delete, erase, cron, squedule, squedule delete
Requires at least: 2.7
Tested up to: 3.5.1
Stable tag: 3.10

== Description ==
This plugin search for duplicated title name posts in the categories that you selected and let you TRASH all duplicated posts in manual mode or automatic scheduled with Wordpress Cron.
The plugin use the wordpress delete_post function then send to trash and delete custom fields too.

You can read this in spanish.  Puedes leerlo en español aquí: [NetMDP](http://www.netmdp.com/2010/03/etruel-del-post-copies/)

== Installation ==
1. Upload `plugin-name.php` to the `/wp-content/plugins/` folder
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Customize options under Options section in Wordpress Backend, eDel Posts Copies item. 
NOTE: May be the firsdt time you see something rare... save options for fix.

Upgrading from Version 2.00
You must deactivate old plugin and then upload and activate this.

Upgrading to Version 3.10
You must activate Check on title and/or Check on content on Settings page.

== Frequently Asked Questions ==
Nothing for now.  You can ask in plugin URI: [NetMDP](http://www.netmdp.com/2010/03/etruel-del-post-copies/)

== Screenshots ==
1. Options Page of plugin.
2. Logs saved shows at bottom of Options Page.

== Changelog ==

= 3.10 =
* Added options to search duplicates for title or content. 
* Added scrolled log to bottom of page. 

= 3.02 =
* some fixes in main query. 

= 3.01 =
* Added Option for show posts that will be deleted.
* Added Option for Ignore Categories.
* Optimized querys in both cases, with and without categories.

= 3.0Beta =
* Added Categories Option for check duplicated only in selected categories.
* Fixed scheduled feature that work diferent in newers versions of WP.
* Thanks to "Neso" for his production. ;)

= 2.0 =
* Added scheduled feature, icons, and cleaned some codes.
* First public release.

= 1.0 =
* Initial plugin. Private. Based in others plugins like Deleted duplicated post and so on..
Just click for delete.	
