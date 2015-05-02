<?php
/*
Plugin Name:  WP-eDel post copies
Plugin URI: http://www.netmdp.com/2010/03/etruel-del-post-copies/
Description: This plugin searches duplicate posts by title or content, filtering by category and can permanently delete them with images or send them to the trash in manual mode or automatic squeduled with Wordpress cron.
Author: etruel
Author URI: http://www.netmdp.com/
Version: 4.0
Requires at least: 4.1
Tested up to: 4.2
*/

register_activation_hook( plugin_basename( __FILE__ ), array( 'WPeDelPostCopies', 'activate' ) );
register_deactivation_hook( plugin_basename( __FILE__ ), array( 'WPeDelPostCopies', 'deactivate' ) );
register_uninstall_hook( plugin_basename( __FILE__ ), array( 'WPeDelPostCopies', 'uninstall' ) );

add_action( 'init', array( 'WPeDelPostCopies', 'init' ), 999 );

//cron work
function awpedpc_cron_function() {
	$cfg = get_option('WP-del_post_copies_options');	
//	$cuerpo = '<pre>'.print_r($cfg,1).'</pre>\n WP_DEL_POST_COPIES_RETURN='. WP_DEL_POST_COPIES_RETURN;
//	$cuerpo .= "\n-- {$cfg['schedule']}<=".current_time('timestamp');
//	wp_mail('etruel@gmail.com', 'Cron gol10', $cuerpo);
	if (!$cfg['active'])return false;
	if ($cfg['schedule']<=current_time('timestamp')) {
		if(isset($cfg['doingcron']) && $cfg['doingcron']) return false;
//		$cfg['logs'][]  = array('started'=>current_time('timestamp'), 'took'=>'not real', 'mode'=>'status_mode', 'status'=>'testing', 'removed'=>0);
//		update_option('WP-del_post_copies_options', $cfg);
		WPeDelPostCopies::etruel_del_post_copies_run();
	}
}
//Add cron interval
function aedpc_add_new_cron_schedule( $schedules ){
   $schedules['five-min'] = array ( 'interval' => 300,
									 'display'  => 'Every 5 Minutes' );
   return $schedules;
}
add_action( 'wpedpc_cron_hook', 'awpedpc_cron_function' );
add_filter( 'cron_schedules', 'aedpc_add_new_cron_schedule' );

if(!defined('WP_ADMIN')) return; //wp_die(__('You do not have sufficient permissions to access this page.'));

//$WPeDelPostCopies = new WPeDelPostCopies();
class WPeDelPostCopies {
	const STORE_URL = 'http://etruel.com';
	//const ITEM_NAME = 'WPeDelPostCopies';
	const TEXTDOMAIN = 'WP-del_post_copies';
	const AUTHOR = 'Esteban Truelsegaard';
	private static $name = '';
	private static $version = '';
	public static $uri = '';
	public static $dir = '';		/** filesystem path to the plugin with trailing slash */

	public static function init() {
		self :: $uri = plugin_dir_url( __FILE__ );
		self :: $dir = plugin_dir_path( __FILE__ );
		self :: etruel_del_post_copies_locale();
		$plugin_data = get_plugin_data( __FILE__ );
		self :: $name = $plugin_data['Name'];
		self :: $version = $plugin_data['Version'];

		new self( TRUE ); // call __construct
	}

	function __construct( $hook_in = FALSE ) {
		$cfg = get_option('WP-del_post_copies_options');	
		//add_action('deactivate_WP-del_post_copies/WP-del_post_copies.php',array( $this, 'etruel_del_post_copies_uninstall') );	
		add_action( 'wp_ajax_delapost',array( $this, 'delapost') );
		
		if(!$cfg['active']) {
			//wp_clear_scheduled_hook('wpedpc_cron_hook');
		}
		
		wp_register_style( 'oplugincss', plugin_dir_url( __FILE__ ).'oplugins.css');
		wp_register_script( 'opluginjs', plugin_dir_url( __FILE__ ).'oplugins.js');
		add_action('admin_menu',array( $this, 'delpostcopies_admin_menu') );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		wp_enqueue_style( 'oplugincss' );
		wp_enqueue_script( 'opluginjs' );
		add_action('admin_print_scripts', array( $this,'edpc_js'), 999) ;
	}

	/**************************/// Calcs next run for a cron string as timestamp
	public static function WPdpc_cron_next($cronstring) { 
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
	
	

	public static function activate() {
		$options = array(
			'period' => '0 3 * * *',  
			'schedule' => time(), 
			'active' => 0, 
			'limit' => 100, 
			'movetotrash' => 1, 
			'deletemedia' => 0, 
			'delimgcontent' => 0, 
			'minmax' => "MIN", 
			'excluded_ids' => "", 
			'categories' => array(), 
			'logs' => array(), 
			'allcat' => 1,
			'cpostypes' => Array (
				'post' => 1,
				'page' => 1,
				'revision' => 1,
				'nav_menu_item' => 1,
			),
			'cposstatuses' => Array (
				'publish' => 1,
				'future' => 1,
				'draft' => 1,
				'pending' => 1,
				'private' => 1,
				'inherit' => 1,
			),
		);
		add_option('WP-del_post_copies_options', $options, '', 'no');
		update_option('titledel', 1);
		update_option('contentdel', 0);
		wp_schedule_event( time(), 'five-min', 'wpedpc_cron_hook' );
	}
	
	public static function deactivate() {
		wp_clear_scheduled_hook('wpedpc_cron_hook');	
	}

	public static function uninstall() {
		wp_clear_scheduled_hook('wpedpc_cron_hook');	
		delete_option('WP-del_post_copies_options');
	}


	public static function etruel_del_post_copies_locale() {
		//load_plugin_textdomain(self :: TEXTDOMAIN, WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) ); // 'wp-content/plugins/WP-del_post_copies');
		load_plugin_textdomain( self :: TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/' ); 
	}

	//*********************************************************************************************************
	static function parseImages($text){    
		preg_match_all('/<img(.+?)src=\"(.+?)\"(.*?)>/', $text, $out);  //for tag img
		preg_match_all('/<link rel=\"(.+?)\" type=\"image\/jpg\" href=\"(.+?)\"(.+?)\/>/', $text, $out2); // for rel=enclosure
		array_push($out,$out2);  // sum all items to array 
		return $out;
	}
	/**
	  * Get relative path
	  * @param $baseUrl base url
	  * @param $relative relative url
	  * @return absolute url version of relative url
	  */
	 private static function getRelativeUrl($baseUrl, $relative){
		 $schemes = array('http', 'https', 'ftp');
		 foreach($schemes as $scheme){
			 if(strpos($relative, "{$scheme}://") === 0) //if not relative
				 return $relative;
		 }

		 $urlInfo = parse_url($baseUrl);

		 $basepath = $urlInfo['path'];
		 $basepathComponent = explode('/', $basepath);
		 $resultPath = $basepathComponent;
		 $relativeComponent = explode('/', $relative);
		 $last = array_pop($relativeComponent);
		 foreach($relativeComponent as $com){
			 if($com === ''){
				 $resultPath = array('');
			 } else if ($com == '.'){
				 $cur = array_pop($resultPath);
				 if($cur === ''){
					 array_push($resultPath, $cur);
				 } else {
					 array_push($resultPath, '');
				 }
			 } else if ($com == '..'){
				 if(count($resultPath) > 1)
					 array_pop($resultPath);
				 array_pop($resultPath);
				 array_push($resultPath, '');
			 } else {
				 if(count($resultPath) > 1)
					 array_pop($resultPath);
				 array_push($resultPath, $com);
				 array_push($resultPath, '');
			 }
		 }
		 array_pop($resultPath);
		 array_push($resultPath, $last);
		 $resultPathReal = implode('/', $resultPath);
		 return $urlInfo['scheme'] . '://' . $urlInfo['host'] . $resultPathReal;
	 }
	private static function getReadUrl($url){
		$headers = get_headers($url);
		foreach($headers as $header){
			$parts = explode(':', $header, 2);
			if(strtolower($parts[0]) == 'location')
				return trim($parts[1]);
		}
		return $url;
	}

	private static function get_domain($url) {
		$pieces = parse_url($url);
		$domain = isset($pieces['host']) ? $pieces['host'] : '';
		if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
			return $regs['domain'];
		}
		return false;
	}

	// USAR  wp_delete_post_link('Borrar', '<p>', '</p>');
	private static function wp_delete_post_link($link = 'Delete', $before = '', $after = '') {
		echo self::edpc_get_delete_post_link($link, $before, $after);
	}
	private static function edpc_get_delete_post_link($link = 'Delete', $before = '', $after = '', $postid = 0, $ajaxcall=false ) {
		$nogo="";
		if($ajaxcall) $nogo=" onclick='return false;' ";
		if( $postid==0 ){
			global $post;
			if ( $post->post_type == 'page' ) {
				if ( !current_user_can( 'edit_page', $post->ID ) ) return;
			} else {
				if ( !current_user_can( 'edit_post', $post->ID ) ) return;
			}
			$link = "<a href='" . wp_nonce_url( get_bloginfo('url') . "/wp-admin/post.php?action=delete&amp;post=" . $post->ID, 'delete-post_' . $post->ID) . "'$nogo>".$link."</a>";
		}else{
			if ( !current_user_can( 'edit_post', $postid ) ) return;
			$link = "<a href='" . wp_nonce_url( get_bloginfo('url') . "/wp-admin/post.php?action=delete&amp;post=" . $postid, 'delete-post_' . $postid) . "'$nogo>".$link."</a>";
		}
		return $before . $link . $after;
	}


	private static function delapost() {
		// http://gol10.com.ar/wp-admin/post.php?action=delete&post=93501&_wpnonce=c9b7ceb480
		global $wpdb;
		$url = parse_url($_POST['url']);
		parse_str($url['query'], $path);
		$nonce = $path['_wpnonce'];
		//----
		$postid = $_POST['data'];
		$dev['postid'] = $postid;
		if (!wp_verify_nonce( $nonce, 'delete-post_' . $postid ) ) {
			 wp_die( 'Security check' ); 
		} else {
			//$dev['msg'] = "URL<pre>".print_r($url,1)."</pre>";
			//$dev['msg'] .= "PATH<pre>".print_r($path,1)."</pre>";
			$dev['success'] = false;
			$dev['msg']="";
			//----
			$cfg = get_option('WP-del_post_copies_options'); 
			$deletemedia = $cfg['deletemedia'];
			$delimgcontent = $cfg['delimgcontent'];
			$movetotrash = $cfg['movetotrash'];
			$force_delete = !$movetotrash;
			//----
			$wp_posts = $wpdb->prefix . "posts";
			$perma	  = get_permalink($postid);
			if ($postid<>''){
				if($deletemedia) {
					$dev['msg'] .= __("Attached images:", self :: TEXTDOMAIN)."<br>";
					$sql = "SELECT ID FROM $wp_posts WHERE post_parent = $postid AND post_type = 'attachment'";
					$ids = $wpdb->get_col($sql);
					foreach ( $ids as $id ) {		
						wp_delete_attachment($id, $force_delete);
						if($force_delete) unlink(get_attached_file($id));
						$dev['msg'] .= sprintf(__("-- Post image id:'%s' Deleted! ", self :: TEXTDOMAIN),$id)."<br>";
					}
				}
				if($delimgcontent) {  //images in content					
					$dev['msg'] .= __("In content images:", self :: TEXTDOMAIN)."<br>";
					$sql = "SELECT ID, post_content FROM $wp_posts WHERE ID = $postid";
					$wpcontent = $wpdb->get_col($sql);
					$images = self::parseImages($wpcontent);
					$images = $images[2];  //lista de url de imagenes
					$itemUrl = $perma;  //self::getReadUrl($perma);
					$images = array_values(array_unique($images));
					if( sizeof($images) ) { // Si hay alguna imagen en el contenido
						$img_new_url = array();
						foreach($images as $imagen_src) {
							$imagen_src_real = self::getRelativeUrl($itemUrl, $imagen_src);
							if(self::get_domain($imagen_src) == self::get_domain(home_url())){
								$file = $_SERVER['DOCUMENT_ROOT'] .str_replace( home_url(), "",$imagen_src_real );
								if (file_exists( $file )) {
									unlink($file);
									$dev['msg'] .= sprintf(__("-- img file:'%s' Deleted! ", self :: TEXTDOMAIN),$file)."<br>";
								}
							}else{
								// image are external. Different domain.";
							}
						}
					}
				}

				$custom_field_keys = get_post_custom_keys($postid);
				foreach ( $custom_field_keys as $key => $value ) {
					delete_post_meta($postid, $key, '');
					$dev['msg'] .= sprintf(__("-- Post META key:'%s', value: '%s'. Deleted! ", self :: TEXTDOMAIN),$key,$value)."<br>";
				}
				$result = wp_delete_post($postid, $force_delete);
				if (!$result) {  
					$dev['msg'] = sprintf(__("Problem deleting post %s - %s !!", self :: TEXTDOMAIN),$postid,$perma)."<br>". $dev['msg'];
				}
				else {
					$dev['success'] = true;
					$dev['msg'] = sprintf(__("'%s' (ID #%s) Deleted!", self :: TEXTDOMAIN),$title,$postid)."<br>". $dev['msg'];
				}
			}else{
				$dev['msg'] = sprintf(__("Problem deleting post %s - %s !!", self :: TEXTDOMAIN),$postid,$perma);
			}
			wp_send_json($dev);
		}
	}


	//******************* MAIN FUNCTION **************************************************************************************
	public static function etruel_del_post_copies_run($mode = 'auto') {
		global $wpdb, $wp_locale, $current_blog;
		$cfg = get_option('WP-del_post_copies_options'); 
		if(!$cfg['active'] AND $mode == 'auto') return;
		$cfg['doingcron']=true;
		update_option('WP-del_post_copies_options', $cfg);
		if($mode == 'auto') self::etruel_del_post_copies_locale();
		$limite = (intval($cfg['limit']) > 0 && $mode <> 'counter') ? " LIMIT 0, ". strval(intval($cfg['limit'])) : "";
		$cpostypes = $cfg['cpostypes'];
			$aposttypes = array();
			foreach ($cpostypes  as $postype => $value) {
				$aposttypes[]= $postype ;
			}
		$cpostypes = "'".implode("','", $aposttypes)."'";
		$cposstatuses = $cfg['cposstatuses'];
			$apoststatuses = array();
			foreach ($cposstatuses  as $postat => $value) {
				$apoststatuses[]= $postat ;
			}
		$cposstatuses = "'".implode("','", $apoststatuses)."'";
		$deletemedia = $cfg['deletemedia'];
		$delimgcontent = $cfg['delimgcontent'];
		$movetotrash = $cfg['movetotrash'];
		$force_delete = !$movetotrash;
		$MINMAX = $cfg['minmax'];
		if(is_null($MINMAX)) $MINMAX = "MIN";
		$categories = implode(",", $cfg['categories']);
		$excluded_ids = ($cfg['excluded_ids']=="") ? "-1" : $cfg['excluded_ids'];
		$timenow 	= time();
		$mtime 		= explode(' ', microtime());
		$time_start = $mtime[1] + $mtime[0];
		$date 		= date('m.d.y-H.i.s', $timenow);
		$wp_posts 				= $wpdb->prefix . "posts";
		$wp_terms 				= $wpdb->prefix . "terms";
		$wp_term_taxonomy 		= $wpdb->prefix . "term_taxonomy";
		$wp_term_relationships 	= $wpdb->prefix . "term_relationships";
		//$blog_id 	= $current_blog->blog_id;
		//$baseurl		= WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/';  

		$fields2compare = " ";

		error_reporting(E_ALL);
		//error_reporting(0); 
		if($mode == 'now' ) echo "<div class='updated fade'>Deleting: <br />";


		if($cfg['allcat']) {
			if(get_option('titledel')==1 && get_option('contentdel')==1) {
				$fields2compare="AND good_rows.post_title = bad_rows.post_title AND good_rows.post_content <> bad_rows.post_content ";
			} elseif(get_option('contentdel')==1)	{  //only content
				$fields2compare="AND good_rows.post_content <> bad_rows.post_content ";
			} else { //only title
				$fields2compare="AND good_rows.post_title = bad_rows.post_title ";
			}
			$query="SELECT bad_rows.*, ok_id, post_date, ok_date
				FROM $wp_posts AS bad_rows
				INNER JOIN (
					SELECT $wp_posts.post_title,$wp_posts.post_content, $wp_posts.id, $wp_posts.post_date AS ok_date, $MINMAX( $wp_posts.id ) AS ok_id
					FROM $wp_posts
					WHERE (
						$wp_posts.post_status IN ($cposstatuses) 
						AND $wp_posts.post_type IN ( $cpostypes ) 
					)
					GROUP BY post_title
					having count(*) > 1
					) AS good_rows ON good_rows.ok_id <> bad_rows.id $fields2compare
				WHERE (
					bad_rows.post_status IN ($cposstatuses) 
					AND bad_rows.id NOT IN ($excluded_ids) 
					AND bad_rows.post_type IN ( $cpostypes ) 
				)
				ORDER BY post_title ".$limite ;

		}else{  // with selected categories
			 if(get_option('titledel')==1 && get_option('contentdel')==1) {
				$fields2compare="good_rows.post_title = bad_rows.post_title OR good_rows.post_content = bad_rows.post_content ";
			 } elseif(get_option('contentdel')==1)	{  //only content
				$fields2compare="good_rows.post_content = bad_rows.post_content ";
			} else { //only title
				$fields2compare="good_rows.post_title = bad_rows.post_title ";
			}
			$query="SELECT bad_rows.post_title, bad_rows.post_content, bad_rows.id as ID, bad_rows.post_date, $wp_terms.term_id, ok_id, ok_date, okcateg_id
				FROM $wp_terms 
				INNER JOIN $wp_term_taxonomy ON $wp_terms.term_id = $wp_term_taxonomy.term_id
				INNER JOIN $wp_term_relationships ON $wp_term_relationships.term_taxonomy_id = $wp_term_taxonomy.term_taxonomy_id
				INNER JOIN $wp_posts AS bad_rows ON bad_rows.ID = $wp_term_relationships.object_id
				INNER JOIN (
					SELECT $wp_posts.post_title,$wp_posts.post_content, $wp_posts.id, $wp_posts.post_date AS ok_date, $MINMAX( $wp_posts.id ) AS ok_id, $wp_terms.term_id AS okcateg_id
					FROM $wp_terms
						INNER JOIN $wp_term_taxonomy ON $wp_terms.term_id = $wp_term_taxonomy.term_id
						INNER JOIN $wp_term_relationships ON $wp_term_relationships.term_taxonomy_id = $wp_term_taxonomy.term_taxonomy_id
						INNER JOIN $wp_posts ON $wp_posts.ID = $wp_term_relationships.object_id
					WHERE taxonomy =  'category'
						AND $wp_posts.post_type IN ( $cpostypes ) 
						AND post_status IN ($cposstatuses) 
						 AND ($wp_terms.term_id IN ( $categories ))
					GROUP BY post_title, $wp_terms.term_id 
					HAVING COUNT( * ) >1
				) AS good_rows ON $fields2compare AND good_rows.ok_id <> bad_rows.id AND good_rows.okcateg_id = $wp_terms.term_id
				WHERE taxonomy =  'category'
					AND bad_rows.post_type IN ( $cpostypes ) 
					AND bad_rows.post_status IN ($cposstatuses) 
					AND bad_rows.id NOT IN ($excluded_ids) 
					AND ($wp_terms.term_id IN ( $categories ))
				ORDER BY post_title ASC ".$limite ;
		}

		$query=  apply_filters('wpedpc_after_query', $query, $cfg);

		if($mode == 'show' ) {
			$dupes = $wpdb->get_results($query) ;
			$dispcount= 0;
			echo "<div class=\"wrap\">
			<h2>". __('Showing posts to delete', self :: TEXTDOMAIN) . "</h2><h4><a style=\"cursor:pointer;float:right;\" onclick=\"jQuery('#delbox').toggle();\">". __('Show/Hide', self :: TEXTDOMAIN) . "</a></h4><br />";
			echo "<table class=\"widefat\">		<thead>
			  <tr>
				<th scope=\"col\">" . __('Post ID', self :: TEXTDOMAIN) . "</th>
				<th scope=\"col\">" . __('Title', self :: TEXTDOMAIN) . "</th>
				<th scope=\"col\">" . __('Category', self :: TEXTDOMAIN) . "</th>
				<th scope=\"col\">" . __('Post Date', self :: TEXTDOMAIN) . "</th>
				<th scope=\"col\">" . __('Correct Post ID', self :: TEXTDOMAIN) . "</th>
				<th scope=\"col\">" . __('Correct Post Date', self :: TEXTDOMAIN) . "</th>
			  </tr>
			</thead>
			<tbody id=\"delbox\">
			" ;

			if(!empty($dupes))
			foreach ($dupes as $dupe) {
				$postid		= $dupe->ID;
				$title		= $dupe->post_title;
				$wpcontent	= $dupe->post_content;
				$cat_id		= ($cfg['allcat']) ? "" : $dupe->term_id ;
				if(!empty($cat_id))
					$cat_name	= get_cat_name( $cat_id );
				else
					$cat_name	= get_the_category_list(', ', '', $postid);
				$postdate	= date( get_option('date_format').' H:i:s', strtotime($dupe->post_date));
				$perma		= get_permalink($postid);
				$okpostid	= $dupe->ok_id;
				$okpostdate	= date( get_option('date_format').' H:i:s', strtotime($dupe->ok_date));
				$okperma	= get_permalink($okpostid);
				$mensaje 	= "";
				if ($postid<>''){  // Muestro una linea con el mensaje
					$cmedias = "";
					if($deletemedia) {  //images attached
						$media = get_children(array('post_parent' => $postid,'post_type' => 'attachment'));
						if (!empty($media)){
							foreach ($media as $file) {
								$cmedias .= "<b>Attached Media</b> ID: '{$file->ID}', title: '{$file->guid}'<br>";
							}
						}
					}
					if($delimgcontent) {  //images in content					
						$images = self::parseImages($wpcontent);
						$images = $images[2];  //lista de url de imagenes
						$itemUrl = $perma;  //self::getReadUrl($perma);
						$images = array_values(array_unique($images));
						if( sizeof($images) ) { // Si hay alguna imagen en el contenido
							$img_new_url = array();
							foreach($images as $imagen_src) {
								$imagen_src_real = self::getRelativeUrl($itemUrl, $imagen_src);
								$cmedias .= "<b>Img in content</b>: $imagen_src_real";
								if(self::get_domain($imagen_src) == self::get_domain(home_url())){
									$file = $_SERVER['DOCUMENT_ROOT'] .str_replace( home_url(), "",$imagen_src_real );
									//$cmedias .= "<br>".$_SERVER['DOCUMENT_ROOT'] .$file ."<br>";
									if (file_exists( $file )) $cmedias .= "<br>" . __("Exist: ", self :: TEXTDOMAIN); 
										else $cmedias .=  __("Don't Exist: ", self :: TEXTDOMAIN);
									$cmedias .= "Img in folder: ". $file . "<br>";
								}else{
									$cmedias .= "<b> External. Different domain.</b><br>";
								}
							}
						}
					}

					$custom_field_keys = get_post_custom_keys($postid);
					$claves = "";
					if(isset($custom_field_keys)) {    // (is_array($custom_field_keys))
						foreach ( $custom_field_keys as $key => $value ) {
							$valuet = trim($value);
							if ( '_' == $valuet{0}) continue;
							$claves .= "<b>Meta key</b>: '$key', value: '$value'<br>";
						}
					}
					$mensaje = "<tr>
						<td><a href=\"$perma\" target=\"_Blank\" title=\"Open post in new tab.\" >$postid</a>".self::edpc_get_delete_post_link('X', '<div class=\'postdel\' rel="'.$postid.'">', '</div>',$postid, true)."</td>
						<td><a title=\"View Details.\" class=\"clickdetail\">$title</a><br><span class=\"rowdetail\" style=\"display: none;\">" .$claves.$cmedias. "</span></td>
						<td>$cat_id :: $cat_name</td>
						<td>$postdate</td>
						<td><a href=\"$okperma\" target=\"_Blank\">$okpostid</a>".self::edpc_get_delete_post_link('X', '<div class=\'postdel\' rel="'.$okpostid.'">', '</div>',$okpostid, true)."</td>
						<td>$okpostdate</td>
					</tr>";
					$dispcount++;
				}
				echo $mensaje;
			}
			echo "<tr>
			<td colspan=\"6\">" . __('Total', self :: TEXTDOMAIN) . ": $dispcount</td></tr>
			</tbody>
			</table>
			</div>";

		}else	if($mode == 'counter' ) {
	//		$dispcount = $wpdb->query($query);
	//		**************************************PROBAR EN EL MYSQL HASTA CONSEGUIR EL TOTAL DE LOS REPETIDOS******************************
			/*SELECT SUM( 1 ) AS mycounter, post_title FROM wp_posts WHERE (`post_status` = 'published' ) OR (`post_status` = 'publish' ) GROUP BY post_title HAVING COUNT( * ) >1  */
			$dispcount = $wpdb->get_col( $wpdb->prepare( "SELECT SUM(base.countr) AS mycounter FROM (SELECT SUM(1) AS countr, post_title FROM wp_posts WHERE (`post_status` = 'published') OR (`post_status` = 'publish' ) GROUP BY post_title HAVING COUNT(*) > 1) AS base" )) ;

	/*		$colcount = $wpdb->get_col( $wpdb->prepare( "SELECT SUM(1) AS mycounter FROM $table_name WHERE (`post_status` = 'published') OR (`post_status` = 'publish' ) GROUP BY post_title HAVING COUNT(*) > 1" ));
			echo "<div class='updated fade'>".count($colcount)."<br />";
			foreach ($colcount as $id) { 
				echo $id['mycounter']." <br />";
			}
			echo "</div>";
			$dispcount = array_sum($colcount);
	*/		//$dispcount = count($colcount);

		}else{  //*************************************  mode = DELETE   *********************
			$dupes = $wpdb->get_results($query) ;
			$dispcount= 0;
			$statuserr=0;
			foreach ($dupes as $dupe) {
				$postid		= $dupe->ID;
				$title		= $dupe->post_title;
				$wpcontent	= $dupe->post_content;
				$perma		= get_permalink($postid);
				$mensaje="";
				if ($postid<>''){
					if($deletemedia) {
	//				    $media = get_children(array('post_parent' => $postid,'post_type' => 'attachment'));
	//					if (!empty($media)){
	//						foreach ($media as $file) {
	//							wp_delete_attachment($file->ID, $force_delete);
	//							if($force_delete) unlink(get_attached_file($file->ID));
	//							$mensaje = sprintf(__("-- Post Image id:'%s', value: '%s'. Deleted! ", self :: TEXTDOMAIN),$file->ID,$file->title)."<br>";
	//						}
	//					}
						$sql = "SELECT ID FROM $wp_posts WHERE post_parent = $postid AND post_type = 'attachment'";
						$ids = $wpdb->get_col($sql);
						foreach ( $ids as $id ) {		
							wp_delete_attachment($id, $force_delete);
							if($force_delete) unlink(get_attached_file($id));
							$mensaje .= sprintf(__("-- Post image id:'%s' Deleted! ", self :: TEXTDOMAIN),$id)."<br>";
						}
					}
					if($delimgcontent) {  //images in content					
						$images = self::parseImages($wpcontent);
						$images = $images[2];  //lista de url de imagenes
						$itemUrl = $perma;  //self::getReadUrl($perma);
						$images = array_values(array_unique($images));
						if( sizeof($images) ) { // Si hay alguna imagen en el contenido
							$img_new_url = array();
							foreach($images as $imagen_src) {
								$imagen_src_real = self::getRelativeUrl($itemUrl, $imagen_src);
								if(self::get_domain($imagen_src) == self::get_domain(home_url())){
									$file = $_SERVER['DOCUMENT_ROOT'] .str_replace( home_url(), "",$imagen_src_real );
									if (file_exists( $file )) {
										unlink($file);
										$mensaje .= sprintf(__("-- img file:'%s' Deleted! ", self :: TEXTDOMAIN),$file)."<br>";
									}
								}else{
									// image are external. Different domain.";
								}
							}
						}
					}

					$custom_field_keys = get_post_custom_keys($postid);
					foreach ( $custom_field_keys as $key => $value ) {
						delete_post_meta($postid, $key, '');
						$mensaje .= sprintf(__("-- Post META key:'%s', value: '%s'. Deleted! ", self :: TEXTDOMAIN),$key,$value)."<br>";
					}
					$result = wp_delete_post($postid, $force_delete);
					if (!$result) {  
						$mensaje = sprintf(__("!! Problem deleting post %s - %s !!", self :: TEXTDOMAIN),$postid,$perma)."<br>". $mensaje;
						$statuserr++;
					}
					else {  
						$mensaje = sprintf(__("'%s' (ID #%s) Deleted!", self :: TEXTDOMAIN),$title,$postid)."<br>". $mensaje;
						$dispcount++;
					}
					if ($mode == 'now' ) echo $mensaje;
				}
			}   
			$mtime 			= explode(' ', microtime());
			$time_end 		= $mtime[1] + $mtime[0];
			$time_total 	= $time_end - $time_start;
			$status_mode 	= ($mode == 'auto') ? 0 : 1;

			$cfg['logs'][]  = array('started'=>current_time('timestamp'), 'took'=>$time_total, 'mode'=>$status_mode, 'status'=>$statuserr, 'removed'=>$dispcount);
			$cfg['schedule']= self::WPdpc_cron_next($cfg['period']); 
			if($mode == 'auto') $cfg['doingcron']=false;

			update_option('WP-del_post_copies_options', $cfg);

		}
		if ($mode == 'now' ) echo "<p>Total: <strong>$dispcount</strong> ".__('deleted posts copies!', 'WP_del_post_copies')."</p></div>";
		if ($mode == 'counter' ) echo "<div class='updated fade'><p>Total: <strong>$dispcount</strong> ".__('posts copies!', 'WP_del_post_copies')."</p></div>";

		return ($mode == 'auto' ? true : $cfg['logs']);
	}

	// ** Muestro Categorías seleccionables 
	public static function WPdpc_edit_cat_row($category, $level, &$data) {  
		$category = get_category( $category );
		$name = $category->cat_name;
		echo '
		<li style="margin-left:'.$level.'5px" class="jobtype-select checkbox checkbox_cat">
		<input type="checkbox" value="' . $category->cat_ID . '" id="category_' . $category->cat_ID . '" name="categories[]" class="catbox" ';
		if(is_array($data)) echo (in_array($category->cat_ID, $data )) ? 'checked="checked"' : '' ;
		echo '>
		<label for="category_' . $category->cat_ID . '">' . $name . '</label></li>';
	}

	public static function WPdpc_adminEditCategories(&$data, $parent = 0, $level = 0, $categories = 0)  {    
		if ( !$categories )
			$categories = get_categories(array('hide_empty' => 0));

		if(function_exists('_get_category_hierarchy'))
		  $children = _get_category_hierarchy();
		elseif(function_exists('_get_term_hierarchy'))
		  $children = _get_term_hierarchy('category');
		else
		  $children = array();

		if ( $categories ) {
			ob_start();
			foreach ( $categories as $category ) {
				if ( $category->parent == $parent) {
					echo "\t" . self::WPdpc_edit_cat_row($category, $level, $data);
					if ( isset($children[$category->term_id]) )
						self::WPdpc_adminEditCategories($data, $category->term_id, $level + 1, $categories );
				}
			}
			$output = ob_get_contents();
			ob_end_clean();

			echo $output;
		} else {
			return false;
		}
	}

	
	public static function WPdpc_save_options($cfg)  {    
		//$temp['period']	= intval($_POST['severy']) * intval($_POST['speriod']);
		$temp['active']			= isset($_POST['active'])		? (bool)$_POST['active']:false;
		$temp['movetotrash']	= isset($_POST['movetotrash'])	? (bool)$_POST['movetotrash']:false;
		$temp['deletemedia']	= isset($_POST['deletemedia'])	? (bool)$_POST['deletemedia']:false;
		$temp['delimgcontent']	= isset($_POST['delimgcontent'])? (bool)$_POST['delimgcontent']:false;
		// ****************** Cron Data
		if ($_POST['cronminutes'][0]=='*' or empty($_POST['cronminutes'])) {
			if (!empty($_POST['cronminutes'][1]))
				$_POST['cronminutes']=array('*/'.$_POST['cronminutes'][1]);
			else
				$_POST['cronminutes']=array('*');
		}
		if ($_POST['cronhours'][0]=='*' or empty($_POST['cronhours'])) {
			if (!empty($_POST['cronhours'][1]))
				$_POST['cronhours']=array('*/'.$_POST['cronhours'][1]);
			else
				$_POST['cronhours']=array('*');
		}
		if ($_POST['cronmday'][0]=='*' or empty($_POST['cronmday'])) {
			if (!empty($_POST['cronmday'][1]))
				$_POST['cronmday']=array('*/'.$_POST['cronmday'][1]);
			else
				$_POST['cronmday']=array('*');
		}
		if ($_POST['cronmon'][0]=='*' or empty($_POST['cronmon'])) {
			if (!empty($_POST['cronmon'][1]))
				$_POST['cronmon']=array('*/'.$_POST['cronmon'][1]);
			else
				$_POST['cronmon']=array('*');
		}
		if ($_POST['cronwday'][0]=='*' or empty($_POST['cronwday'])) {
			if (!empty($_POST['cronwday'][1]))
				$_POST['cronwday']=array('*/'.$_POST['cronwday'][1]);
			else
				$_POST['cronwday']=array('*');
		}
		$temp['period'] = implode(",",$_POST['cronminutes']).' '.implode(",",$_POST['cronhours']).' '.implode(",",$_POST['cronmday']).' '.implode(",",$_POST['cronmon']).' '.implode(",",$_POST['cronwday']);
		//********* end cron data
		$temp['limit']	 = intval($_POST['limit']);
		$temp['allcat'] = (bool)$_POST['allcat'];

		if(isset($_POST['categories']))  $temp['categories']=(array)$_POST['categories'];
			else $temp['categories']=array();

		if(isset($_POST['cpostypes']))  $temp['cpostypes']=(array)$_POST['cpostypes'];
			else $temp['cpostypes']=array();

		if(isset($_POST['cposstatuses']))  $temp['cposstatuses']=(array)$_POST['cposstatuses'];
			else $temp['cposstatuses']=array();

		if(isset($_POST['minmax']))  $temp['minmax']=$_POST['minmax'];
			else $temp['minmax']="MIN";

		if(isset($_POST['excluded_ids']))  $temp['excluded_ids']=$_POST['excluded_ids'];
			else $temp['excluded_ids']="";

		$temp['schedule'] 	= 	self::WPdpc_cron_next($temp['period']); 
		$temp['logs']		=	$cfg['logs'];

		$temp = apply_filters('wpedpc_before_save',$temp);
		//wp_die('<pre'.print_r($temp).'</pre>');
		update_option('WP-del_post_copies_options', $temp);

//		if($cfg['active'] AND !$temp['active']) $clear = true;  //desactivó el cron
//		if(!$cfg['active'] AND $temp['active']) $schedule = true;  //activo el cron
//		if($cfg['active'] AND $temp['active'] AND ($temp['period'] != $cfg['period']) ) {  // cambió el periodo
//			$clear = true;
//			$schedule = true;
//		}
		//if($clear) 	wp_clear_scheduled_hook('wpedpc_cron_hook');
		//if($schedule) wp_schedule_event( time(), 'five-min', 'wpedpc_cron_hook' );

		return $temp;
	}


	public static function delpostcopies_admin_menu() {
		global $edpc_opt_page;
		$edpc_opt_page = add_options_page(
			'Del Post Copies Options Page', 
			'<img src="'.WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/wpedpc.sb.png"/>eDel Post Copies', 
			'manage_options', 
			'edpc_options',
			array( __CLASS__, 'edpc_options_page') );
	}
	
	public static function edpc_options_page() {
		include 'WP-del_post_copies-options.php';
	}

	
	public static function edpc_js() {
		global $edpc_opt_page;
		$screen = get_current_screen();
		// Check if current screen is this
		//die(print_r($screen,1).$edpc_opt_page);
		if ( $screen->id != $edpc_opt_page )
			return;

?><style>.widefat tr:nth-child(odd) {background-color: #eee;}
.postdel{font-size: 12px;font-weight: bold;float: left;padding: 0 5px 0px;background: rgb(255, 116, 116);margin: 0 5px;border-radius: 8px;}
.postdel a {color: white;}
.postdel:hover {background: red;cursor: pointer;}
.description{font-size: 11px !important;}
.postbox {padding: 5px;}
.postbox h3 {margin-left: 5px;}
#edpc-table td {vertical-align: top;padding: 0 12px;}
#edpc-table th {padding: 10px 10px 0;}
input#gosubmit {background: greenyellow;z-index: 20;position: absolute;border: 0;border-radius: 20px;padding: 1px 7px;margin: 9px 7px;font-weight: bold;}
img#goman {position: absolute;left: 258px;height: 75px;z-index: 10;}
@media screen and (max-width: 782px){
	img#goman {left: 300px}
	input#gosubmit {padding: 5px 7px;}
}
select#quickdo {cursor: pointer;cursor: hand;}
</style>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$(".clickdetail").click(function(){
			$(this).next().next('.rowdetail').fadeToggle();
		});
		$("#edpcsettings input").each(
			function(index, value) {
				$(this).change(cambio_edpcsettings)
			}
		);
		$(".postdel").click(function(){
			var url = $(this).children('a').attr('href');
			var data = $(this).attr('rel');
			var action = 'delapost';
			$.post(ajaxurl, { action: action, url: url , data: data }, function(response) {
				if(response.success) var msg = '<div id="message" class="updated fade"><p>'+response.msg+'</p></div>';
				else var msg = '<div id="message" class="error fade"><p>'+response.msg+'</p></div>';
				 $('.wrap:first').children('h4:first').before(msg);
				 var $obj= $('.postdel[rel="'+response.postid+'"]');
				 $obj.html('X').off('click').css({"background-color":"grey","cursor":"default"});
				 $obj.prev('a').css({"color":"red"});
				 
				//alert(msg);
			});
		});
	});

	function cambio_edpcsettings(){
		jQuery('#submit').css('background-color','coral');
		jQuery('#gosubmit').prop('disabled',true);
		jQuery('#gosubmit').attr('title','You must Save Changes below before "Go"');
		jQuery('#cancelchg').show();
	}
</script>
	<?php 
		do_action('wpedpc_scripts'); 
	} 

} // class
?>