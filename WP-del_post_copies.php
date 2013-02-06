<?php
/*
Plugin Name:  WP-eDel post copies
Plugin URI: http://www.netmdp.com/2010/03/etruel-del-post-copies/
Description: This plugin search for duplicated title name posts in the categories that you selected and let you TRASH all duplicated posts in manual mode or automatic scheduled with Wordpress Cron.  The plugin use the wordpress delete_post function then send to trash and delete custom fields too.
Author: etruel
Author URI: http://www.netmdp.com/
Version: 3.02
Requires at least: 2.7
Tested up to: 3.0.5
*/

add_action('activate_WP-del_post_copies/WP-del_post_copies.php', 'etruel_del_post_copies_install');
function etruel_del_post_copies_install() {
	$options = array('period' => '0 3 * * *',  'schedule' => time(), 'active' => 0, 'limit' => 100, 'categories' => array(), 'allcat' => 1 );
	add_option('WP-del_post_copies_options', $options, '', 'no');
}
	
add_action('deactivate_WP-del_post_copies/WP-del_post_copies.php', 'etruel_del_post_copies_uninstall');	
function etruel_del_post_copies_uninstall()
{
	wp_clear_scheduled_hook('edpc_cron');	
	delete_option('WP-del_post_copies_options');
}

//Calcs next run for a cron string as timestamp
function WPdpc_cron_next_FIX($cronstring) {
	//Cronstring zerlegen
	list($cronstr['minutes'],$cronstr['hours'],$cronstr['mday'],$cronstr['mon'],$cronstr['wday'])=explode(' ',$cronstring,5);

	//make arrys form string
	foreach ($cronstr as $key => $value) {
		if (strstr($value,','))
			$cronarray[$key]=explode(',',$value);
		else
			$cronarray[$key]=array(0=>$value);
	}
	//make arrys complete with ranges and steps
	foreach ($cronarray as $cronarraykey => $cronarrayvalue) {
		$cron[$cronarraykey]=array();
		foreach ($cronarrayvalue as $key => $value) {
			//steps
			$step=1;
			if (strstr($value,'/'))
				list($value,$step)=explode('/',$value,2);
			//replase weekeday 7 with 0 for sundays
			if ($cronarraykey=='wday')
				$value=str_replace('7','0',$value);
			//ranges
			if (strstr($value,'-')) {
				list($first,$last)=explode('-',$value,2);
				if (!is_numeric($first) or !is_numeric($last) or $last>60 or $first>60) //check
					return false;
				if ($cronarraykey=='minutes' and $step<5)  //set step ninmum to 5 min.
					$step=5;
				$range=array();
				for ($i=$first;$i<=$last;$i=$i+$step)
					$range[]=$i;
				$cron[$cronarraykey]=array_merge($cron[$cronarraykey],$range);
			} elseif ($value=='*') {
				$range=array();
				if ($cronarraykey=='minutes') {
					if ($step<5) //set step ninmum to 5 min.
						$step=5;
					for ($i=0;$i<=59;$i=$i+$step)
						$range[]=$i;
				}
				if ($cronarraykey=='hours') {
					for ($i=0;$i<=23;$i=$i+$step)
						$range[]=$i;
				}
				if ($cronarraykey=='mday') {
					for ($i=$step;$i<=31;$i=$i+$step)
						$range[]=$i;
				}
				if ($cronarraykey=='mon') {
					for ($i=$step;$i<=12;$i=$i+$step)
						$range[]=$i;
				}
				if ($cronarraykey=='wday') {
					for ($i=0;$i<=6;$i=$i+$step)
						$range[]=$i;
				}
				$cron[$cronarraykey]=array_merge($cron[$cronarraykey],$range);
			} else {
				//Month names
				if (strtolower($value)=='jan')
					$value=1;
				if (strtolower($value)=='feb')
					$value=2;
				if (strtolower($value)=='mar')
					$value=3;
				if (strtolower($value)=='apr')
					$value=4;
				if (strtolower($value)=='may')
					$value=5;
				if (strtolower($value)=='jun')
					$value=6;
				if (strtolower($value)=='jul')
					$value=7;
				if (strtolower($value)=='aug')
					$value=8;
				if (strtolower($value)=='sep')
					$value=9;
				if (strtolower($value)=='oct')
					$value=10;
				if (strtolower($value)=='nov')
					$value=11;
				if (strtolower($value)=='dec')
					$value=12;
				//Week Day names
				if (strtolower($value)=='sun')
					$value=0;
				if (strtolower($value)=='sat')
					$value=6;
				if (strtolower($value)=='mon')
					$value=1;
				if (strtolower($value)=='tue')
					$value=2;
				if (strtolower($value)=='wed')
					$value=3;
				if (strtolower($value)=='thu')
					$value=4;
				if (strtolower($value)=='fri')
					$value=5;
				if (!is_numeric($value) or $value>60) //check
					return false;
				$cron[$cronarraykey]=array_merge($cron[$cronarraykey],array(0=>$value));
			}
		}
	}

	//calc next timestamp
	$currenttime=current_time('timestamp');
	foreach (array(date('Y'),date('Y')+1) as $year) {
		foreach ($cron['mon'] as $mon) {
			foreach ($cron['mday'] as $mday) {
				foreach ($cron['hours'] as $hours) {
					foreach ($cron['minutes'] as $minutes) {
						$timestamp=mktime($hours,$minutes,0,$mon,$mday,$year);
						if (in_array(date('w',$timestamp),$cron['wday']) and $timestamp>$currenttime) {
								return $timestamp;
						}
					}
				}
			}
		}
	}
	return false;
}
//*********************************************************************************************************

function etruel_del_post_copies_run($mode = 'auto') {
	global $wpdb, $wp_locale, $current_blog;
	$cfg = get_option('WP-del_post_copies_options'); 
	if(!$cfg['active'] AND $mode == 'auto') return;
	if($mode == 'auto') etruel_del_post_copies_locale();
	$limite = (intval($cfg['limit']) > 0 && $mode <> 'counter') ? " LIMIT 0, ". strval(intval($cfg['limit'])) : "";
	$categories = implode(",", $cfg['categories']);
	$timenow 	= time();
	$mtime 		= explode(' ', microtime());
	$time_start = $mtime[1] + $mtime[0];
	$date 		= date('m.d.y-H.i.s', $timenow);
	$wp_posts 				= $wpdb->prefix . "posts";
	$wp_terms 				= $wpdb->prefix . "terms";
	$wp_term_taxonomy 		= $wpdb->prefix . "term_taxonomy";
	$wp_term_relationships 	= $wpdb->prefix . "term_relationships";
   $blog_id 	= $current_blog->blog_id;
   $baseurl		= WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/';  
	
	//error_reporting(E_ALL);
	error_reporting(0); 
	if($mode == 'now' ) echo "<div class='updated fade'>Deleting: <br />";
	
	
	if($cfg['allcat']) {
		$query="select bad_rows.*, ok_id, post_date, ok_date
		from $wp_posts as bad_rows
		inner join (
			select $wp_posts.post_title, $wp_posts.id, $wp_posts.post_date as ok_date, MIN( $wp_posts.id ) AS ok_id
			from $wp_posts
			WHERE (
				(`post_status` = 'published') OR 
				(`post_status` = 'publish' )
			)
			group by post_title
			having count(*) > 1
			) as good_rows on good_rows.post_title = bad_rows.post_title
		and good_rows.ok_id <> bad_rows.id 
		ORDER BY post_title ".$limite ;
	}else{
		/******************************** ** This remain only for testing 
		SELECT wp2.post_title, wp2.id, wp2.post_date, wp_terms.term_id, ok_id, ok_date, okcateg_id
		FROM wp_terms 
		INNER JOIN wp_term_taxonomy ON wp_terms.term_id = wp_term_taxonomy.term_id
		INNER JOIN wp_term_relationships ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id
		INNER JOIN wp_posts as wp2 ON wp2.ID = wp_term_relationships.object_id
		INNER JOIN (
			SELECT wp_posts.post_title, wp_posts.id, wp_posts.post_date as ok_date, MIN( wp_posts.id ) AS ok_id, wp_terms.term_id as okcateg_id
			FROM wp_terms
				INNER JOIN wp_term_taxonomy ON wp_terms.term_id = wp_term_taxonomy.term_id
				INNER JOIN wp_term_relationships ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id
				INNER JOIN wp_posts ON wp_posts.ID = wp_term_relationships.object_id
			WHERE taxonomy =  'category'
				AND wp_posts.post_type =  'post'
				AND ((post_status =  'published') OR (post_status =  'publish'))
				 AND (wp_terms.term_id IN ( 14,3,75,55,66,146,86,87,76,77 ))
			GROUP BY post_title 
			HAVING COUNT( * ) >1
		) as good_rows ON good_rows.post_title = wp2.post_title AND good_rows.ok_id <> wp2.id AND good_rows.okcateg_id = wp_terms.term_id
		WHERE taxonomy =  'category'
			AND wp2.post_type =  'post'
			AND ((wp2.post_status =  'published') OR (wp2.post_status =  'publish'))
			AND (wp_terms.term_id IN ( 14,3,75,55,66,146,86,87,76,77 ))
		ORDER BY post_title ASC 
		LIMIT 0 , 20
		***************************************/	
		$query="SELECT wp2.post_title, wp2.id as ID, wp2.post_date, $wp_terms.term_id, ok_id, ok_date, okcateg_id
		FROM $wp_terms 
		INNER JOIN $wp_term_taxonomy ON $wp_terms.term_id = $wp_term_taxonomy.term_id
		INNER JOIN $wp_term_relationships ON $wp_term_relationships.term_taxonomy_id = $wp_term_taxonomy.term_taxonomy_id
		INNER JOIN $wp_posts as wp2 ON wp2.ID = $wp_term_relationships.object_id
		INNER JOIN (
			SELECT $wp_posts.post_title, $wp_posts.id, $wp_posts.post_date as ok_date, MIN( $wp_posts.id ) AS ok_id, $wp_terms.term_id as okcateg_id
			FROM $wp_terms
				INNER JOIN $wp_term_taxonomy ON $wp_terms.term_id = $wp_term_taxonomy.term_id
				INNER JOIN $wp_term_relationships ON $wp_term_relationships.term_taxonomy_id = $wp_term_taxonomy.term_taxonomy_id
				INNER JOIN $wp_posts ON $wp_posts.ID = $wp_term_relationships.object_id
			WHERE taxonomy =  'category'
				AND $wp_posts.post_type =  'post'
				AND ((post_status =  'published') OR (post_status =  'publish'))
				 AND ($wp_terms.term_id IN ( $categories ))
			GROUP BY post_title, $wp_terms.term_id 
			HAVING COUNT( * ) >1
		) as good_rows ON good_rows.post_title = wp2.post_title AND good_rows.ok_id <> wp2.id AND good_rows.okcateg_id = $wp_terms.term_id
		WHERE taxonomy =  'category'
			AND wp2.post_type =  'post'
			AND ((wp2.post_status =  'published') OR (wp2.post_status =  'publish'))
			AND ($wp_terms.term_id IN ( $categories ))
		ORDER BY post_title ASC ".$limite ;
	}
	
	if($mode == 'show' ) {
		$dupes = $wpdb->get_results($query) ;
		$dispcount= 0;
		echo "<div class=\"wrap\">
		<h2>". __('Showing posts to delete', 'WP-del_post_copies') . "</h2><h4><a style=\"cursor:pointer;float:right;\" onclick=\"jQuery('#delbox').toggle();\">". __('Show/Hide', 'WP-del_post_copies') . "</a></h4><br />";
		echo "<table class=\"widefat\">		<thead>
		  <tr>
			<th scope=\"col\">" . __('Post ID', 'WP-del_post_copies') . "</th>
			<th scope=\"col\">" . __('Title', 'WP-del_post_copies') . "</th>
			<th scope=\"col\">" . __('Cat. ID', 'WP-del_post_copies') . "</th>
			<th scope=\"col\">" . __('Post Date', 'WP-del_post_copies') . "</th>
			<th scope=\"col\">" . __('Correct Post ID', 'WP-del_post_copies') . "</th>
			<th scope=\"col\">" . __('Correct Post Date', 'WP-del_post_copies') . "</th>
		  </tr>
		</thead>
		<tbody id=\"delbox\">
		" ;

		foreach ($dupes as $dupe) {
			$postid		= $dupe->ID;
			$title		= $dupe->post_title;
			$cat_id		= ($cfg['allcat']) ? "" : $dupe->term_id ;
			$postdate	= $dupe->post_date;
			$perma		= get_permalink($postid);
			$okpostid	= $dupe->ok_id;
			$okpostdate	= $dupe->ok_date;
			$okperma		= get_permalink($okpostid);
			$mensaje 	= "";
			if ($postid<>''){  // Muestro una linea con el mensaje
				$custom_field_keys = get_post_custom_keys($postid);
				$claves = "";
				if(isset($custom_field_keys)) {    // (is_array($custom_field_keys))
					foreach ( $custom_field_keys as $key => $value ) {
						$valuet = trim($value);
						if ( '_' == $valuet{0}) continue;
						$claves = ":<br> Meta key: '$key', value: '$value'<br>";
					}
				}
				$mensaje = "<tr>
					<td>$postid</td>
					<td><a href=\"$perma\" target=\"_Blank\">$title</a>" .$claves. "</td>
					<td>$cat_id</td>
					<td>$postdate</td>
					<td><a href=\"$okperma\" target=\"_Blank\">$okpostid</a></td>
					<td>$okpostdate</td>
				</tr>";
				$dispcount++;
			}
			echo $mensaje;
		}
		echo "<tr>
		<td colspan=\"5\">" . __('Total', 'WP-del_post_copies') . ": $dispcount</td></tr>
		</tbody>
		</table>
		</div>";
	
	}else	if($mode == 'counter' ) {
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
			$mensaje="";
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
		$status_mode 	=   ($mode == 'auto') ? 0 : 1;
	
		$cfg['logs'][]  =   array('started'=>current_time('timestamp'), 'took'=>$time_total, 'mode'=>$status_mode, 'status'=>$statuserr, 'removed'=>$dispcount);
		$cfg['schedule']= 	WPdpc_cron_next_FIX($cfg['period']); 
				
		update_option('WP-del_post_copies_options', $cfg);

	}
	if ($mode == 'now' ) echo "<p>Total: <strong>$dispcount</strong> ".__('deleted posts copies!', 'WP_del_post_copies')."</p></div>";
	if ($mode == 'counter' ) echo "<div class='updated fade'><p>Total: <strong>$dispcount</strong> ".__('posts copies!', 'WP_del_post_copies')."</p></div>";
	define('WP_DEL_POST_COPIES_RETURN', false);
	return ($mode == 'auto' ? true : $cfg['logs']);
}

function etruel_del_post_copies_locale() {
	load_plugin_textdomain('WP-del_post_copies', 'wp-content/plugins/WP-del_post_copies');
}

//cron work
function edpc_cron() {	
	$cfg = get_option('WP-del_post_copies_options');	
	if (!$cfg['active'])return false;
	if ($cfg['schedule']<=current_time('timestamp')) {
		if(!defined('WP_DEL_POST_COPIES_RETURN')) {
			define('WP_DEL_POST_COPIES_RETURN', true);
			etruel_del_post_copies_run();
		}
	}
}
//Add cron interval
function edpc_intervals($schedules) {
	$intervals['edpc_int']=array('interval' => '300', 'display' => __('Del Post Copies Interval', 'WP_del_post_copies'));
	$schedules=array_merge($intervals,$schedules);
	return $schedules;
}
//add cron intervals
add_filter('cron_schedules', 'edpc_intervals');
//Actions for Cron job
add_action('edpc_cron', 'edpc_cron');
//test if cron active
if (!(wp_next_scheduled('edpc_cron')))
	wp_schedule_event(0, 'edpc_int', 'edpc_cron');

function delpostcopies_admin_menu() {
		add_options_page('Del Post Copies Options Page', '<img src="'.WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/wpedpc.sb.png"/>eDel Post Copies', 'manage_options', dirname(__FILE__).'/WP-del_post_copies-options.php');
}
add_action('admin_menu', 'delpostcopies_admin_menu');


?>
