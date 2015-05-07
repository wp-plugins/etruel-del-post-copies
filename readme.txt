=== WP-eDel post copies ===
Contributors: etruel
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VTNR4BH8XPDR6
Tags: posts, copies, duplicate posts, delete copies, delete, erase, cron, squedule, squedule delete
Requires at least: 3.9
Tested up to: 4.2.2
Stable tag: 4.0.1

This plugin searches duplicate posts by title or content, filtering by category and can permanently delete them with images or send them to the trash in manual mode or automatic squeduled with Wordpress cron.

== Description ==
This plugin searches duplicate posts by checking the title or content, filtering by category and can permanently delete them or send them to the trash in manual mode or automatic scheduled with Wordpress cron.
In version 4.0 multiple filters were added.  Support for post types, post status, exclude post (types) with ID, select which ID, first or last must remain, deleting others.  Custom fields of every post are also deleted from postmeta table.
And as a special feature, erasing images of two different manners, images attached to posts can be trash or delete permanently and also can delete images added in posts content by html tag <img>.  
The images in posts content can be deleted from the folder if they are hosted locally. 

= Some Features =

* Allow limit the query to avoid timeouts or high server load performing Mysql queries.
* Allow trash or permanent delete post or any post types, public or private as well images or attachments to every post.
* Also delete custom meta fields values.
* Allow to delete attachments.
* Allow to search and permanent delete images in posts content from the folder if they are hosted locally.
* Allow filter by post status, revisions or inherit also.
* Allow filter by any or some categories.  But if ignore categories, the query is too much quickly.
* Allow Ignore posts to delete by post IDs.
* You can preview a  table of posts before delete.
* You can manually delete a single post via ajax in the preview table.

Is probable that if there is a large amount of duplicate posts, due to each server timeouts, the query is interrupted when proceeding manually and therefore the log can't be recorded. To avoid this decreases the "Limit per time" value. A value of 100 or 150 is suitable, but also with 10 at a time works well.

PLEASE MAKE BACKUPs OF YOUR DATABASE AND FILES BEFORE USE.  This will avoid you many problems if something goes wrong.

= Add-On =
Very soon you will also use the new Add-On [WP-eDel-Oldest-Posts](http://etruel.com/downloads/wp-edel-oldest-post/) to select a date to delete all posts published before that date and/or you can establish a period with a cron job to continuously deleting old posts and just remains that period on database.

DISCLAIMER:
This plugin is to delete posts and/or images and other. Use with very much caution.
The use of this plugin and its extensions is at your own risk. I will not be liable of third party for difficulty in use, inaccuracy or incompleteness of information, use of this information or results arising from the use of it, computer viruses, malicious code, loss of data, compatibility issues or otherwise. I will not be liable to you or any third party of any direct, indirect, special incidental, consequential, exemplary or punitive damages ( including lost of profit, lost of data, cost to procure replacement services or business opportunities) arising out of your use of plugin, or any other thing I provide in the site or link to another, or any acts omissions, defect, deficit, security breaches, or delays, regardless of the basis of the claim or if I have been advised of the possibility of such damage or loss.

== Installation ==
You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip plugin archive and put the folder into your plugins folder (/wp-content/plugins/).
2. Activate the plugin from the Plugins menu.

== Screenshots ==
1. Options Page of plugin.
2. You can see a table with the posts to delete and its details or attachments.
3. The logs are also in a new tab saving time to load the page.

== Changelog ==

= 4.0.1 =
* Tested Up to WP 4.2.2
* Fixed the site crash issue reported by asisrodriguez. Thanks!
* Better Readme file. (this :)
* New icons.

= 4.0 =
* Added options to search duplicates by post types.
* Category option only works with posts. (ToDo custom tax for post types)
* Added options to search duplicates by post status.
* Added option to delete images attached to a post.
* Added option to search and delete images in content before delete a post.
* Better style on table showing posts to delete.
* Added option to delete a single post by click.
* Fixed scheduled cron jobs.
* Almost all plugin recoded to make it pluggable to add-ons and Wordpress better practices.

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

== Upgrade Notice ==
1. Must upgrade. 