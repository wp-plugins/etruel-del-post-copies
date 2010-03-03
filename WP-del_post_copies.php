<?php
/*
Plugin Name:  WP-eDel post copies
Plugin URI: http://www.netmdp.com/2010/03/etruel-del-post-copies/
Description: This plugin search for duplicated title name posts and let you TRASH all duplicated posts in manual mode or automatic scheduled with Wordpress Cron.  The plugin use the wordpress delete_post function then send to trash and delete custom fields too.
Author: etruel
Author URI: http://www.netmdp.com/
Version: 2.00
Requires at least: 2.7
Tested up to: 2.9.2
*/

add_action('activate_WP-del_post_copies/WP-del_post_copies.php', 'etruel_del_post_copies_install');
function etruel_del_post_copies_install() {
	$options = array('period' => 86400,  'schedule' => time(), 'active' => 0, 'limit' => 100);
	add_option('WP-del_post_copies_options', $options, '', 'no');
}
	
add_action('deactivate_WP-del_post_copies/WP-del_post_copies.php', 'etruel_del_post_copies_uninstall');	
function etruel_del_post_copies_uninstall()
{
	wp_clear_scheduled_hook('wp_edpc_sched');	
	delete_option('WP-del_post_copies_options');
}

function etruel_del_post_copies_run($mode = 'auto') {
   global $wpdb, $wp_locale, $current_blog;
	if(defined('WP_DEL_POST_COPIES_RETURN')) return;
	$cfg = get_option('WP-del_post_copies_options'); 
	if(!$cfg['active'] AND $mode == 'auto') return;
	if($mode == 'auto') etruel_del_post_copies_locale();
	$limite = (intval($cfg['limit']) > 0 && $mode <> 'counter') ? " LIMIT ". strval(intval($cfg['limit'])) : "";

	define('WP_DEL_POST_COPIES_RETURN', true);
	$timenow 	= time();
	$mtime 		= explode(' ', microtime());
	$time_start = $mtime[1] + $mtime[0];
	$date 		= date('m.d.y-H.i.s', $timenow);
	$table_name = $wpdb->prefix . "posts";  
   $blog_id 	= $current_blog->blog_id;
   $baseurl		= WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/';  

	error_reporting(0); 
	if($mode == 'now' ) echo "<div class='updated fade'>Deleting: <br />";

	$query="select bad_rows.*
	from $table_name as bad_rows
	inner join (
		select post_title,id, MIN(id) as min_id
		from $table_name
		WHERE (
			(`post_status` = 'published') OR 
			(`post_status` = 'publish' )
		)
		group by post_title
		having count(*) > 1
		) as good_rows on good_rows.post_title = bad_rows.post_title
	and good_rows.min_id <> bad_rows.id".$limite ;

	if($mode == 'counter' ) {
//		$dispcount = $wpdb->query($query);
//		**************************************PROBAR EN EL MYSQL HASTA CONSEGUIR EL TOTAL DE LOS REPETIDOS******************************
		/*SELECT SUM( 1 ) AS mycounter, post_title FROM wp_posts WHERE (`post_status` = 'published' ) OR (`post_status` = 'publish' ) GROUP BY post_title HAVING COUNT( * ) >1  */
		$dispcount = $wpdb->get_col( $wpdb->prepare( "SELECT SUM(base.countr) as mycounter FROM (SELECT SUM(1) as countr, post_title FROM wp_posts WHERE (`post_status` = 'published') OR (`post_status` = 'publish' ) GROUP BY post_title HAVING COUNT(*) > 1) as base" )) ;
		
/*		$colcount = $wpdb->get_col( $wpdb->prepare( "SELECT SUM(1) as mycounter FROM $table_name WHERE (`post_status` = 'published') OR (`post_status` = 'publish' ) GROUP BY post_title HAVING COUNT(*) > 1" ));
		echo "<div class='updated fade'>".count($colcount)."<br />";
		foreach ($colcount as $id) { 
			echo $id['mycounter']." <br />";
		}
		echo "</div>";
		$dispcount = array_sum($colcount);
*/		//$dispcount = count($colcount);
	}else{
		$dupes = $wpdb->get_results($query) ;
		$dispcount= 0;
		$$statuserr=0;
		foreach ($dupes as $dupe) {
			$postid=$dupe->ID;
			$title=$dupe->post_title;
			$perma=get_permalink($postid);
			if ($postid<>''){
				$custom_field_keys = get_post_custom_keys($postid);
				foreach ( $custom_field_keys as $key => $value ) {
					delete_post_meta($postid, $key, '');
					$mensaje = sprintf(__("-- Post META key:'%s', value: '%s'. Deleted! ", 'WP-del_post_copies'),$key,$value)."<br>";
				}
				$result = wp_delete_post($postid);
				if (!$result) {  
					$mensaje = sprintf(__("!! Problem deleting post %s - %s !!", 'WP-del_post_copies'),$postid,$perma)."<br>". $mensaje;
					$statuserr++;
				}
				else {  
					$mensaje = sprintf(__("'%s' (ID #%s) Deleted!", 'WP-del_post_copies'),$title,$postid)."<br>". $mensaje;
					$dispcount++;
				}
				if ($mode == 'now' ) echo $mensaje;
			}
		}      
		$mtime 			= 	explode(' ', microtime());
		$time_end 		= 	$mtime[1] + $mtime[0];
		$time_total 	= 	$time_end - $time_start;
		$cfg['logs'][] = array('started'=>$timenow, 'took'=>$time_total, 'status'=>$statuserr, 'removed'=>$dispcount);
		update_option('WP-del_post_copies_options', $cfg);
	}
	if ($mode == 'now' ) echo "<p>Total: <strong>$dispcount</strong> ".__('deleted posts copies!', 'WP_del_post_copies')."</p></div>";
	if ($mode == 'counter' ) echo "<div class='updated fade'><p>Total: <strong>$dispcount</strong> ".__('posts copies!', 'WP_del_post_copies')."</p></div>";

	return ($mode == 'auto' ? true : $cfg['logs']);
}

function etruel_del_post_copies_locale() {
	load_plugin_textdomain('WP-del_post_copies', 'wp-content/plugins/WP-del_post_copies');
}

function edpc_interval() {
	$cfg = get_option('WP-del_post_copies_options');
	$cfg['period'] = ($cfg['period'] == 0) ? 86400 : $cfg['period'];
	return array('WP_del_post_copies' => array('interval' => $cfg['period'], 'display' => __('Del Post Copies Interval', 'WP_del_post_copies')));
}
add_filter('cron_schedules', 'edpc_interval');
add_action('wp_edpc_sched', 'etruel_del_post_copies_run');

function delpostcopies_admin_menu() {
		add_options_page('Del Post Copies Options Page', '<img src="'.WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/wpedpc.sb.png"/>eDel Post Copies', 'manage_options', dirname(__FILE__).'/WP-del_post_copies-options.php');
}
add_action('admin_menu', 'delpostcopies_admin_menu');


?>
