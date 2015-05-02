<?php
/**
 * @package WordPress_Plugins
 * @subpackage WP-eDel post copies
*/
//error_reporting(0);
if(!defined('WP_ADMIN') OR !current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));

$cfg = get_option('WP-del_post_copies_options'); 

if(isset($_POST["ctdel"])) {
	if(isset($_POST["titledel"]) && $_POST["titledel"]) {
		update_option('titledel', 1);
	}else {
		update_option('titledel', 0);
	}

	if(isset($_POST["contentdel"]) && $_POST["contentdel"]) {
		update_option('contentdel', 1);
	}else {
		update_option('contentdel', 0);
	}
}
if(!isset($_POST['quickdo'])) $_POST['quickdo'] = "";
if(!isset($_POST['do'])) $_POST['do'] = "";

if($_POST['quickdo'] == 'WPdpc_logerase') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = array();
	update_option('WP-del_post_copies_options', $cfg);
}
elseif($_POST['quickdo'] == 'WPdpc_now') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = WPeDelPostCopies::etruel_del_post_copies_run('now');
}
elseif($_POST['quickdo'] == 'WPdpc_show') {
	check_admin_referer('WPdpc_quickdo');
	//$cfg = WPdpc_save_options($cfg);
	$cfg['logs'] = WPeDelPostCopies::etruel_del_post_copies_run('show');
}
elseif($_POST['quickdo'] == 'WPdpc_counter') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = WPeDelPostCopies::etruel_del_post_copies_run('counter');
}
elseif($_POST['do'] == 'WPdpc_setup') {
	check_admin_referer('WPdpc_options');
	$cfg = self::WPdpc_save_options($cfg);
	?><div id="message" class="updated fade"><p><?php _e('Options saved.') ?></p></div><?php
}

$is_safe_mode = ini_get('safe_mode') == '1' ? 1 : 0;
if(is_null($cfg['limit'])) $cfg['limit']=10;
if(is_null($cfg['minmax'])) $cfg['minmax']="MIN";
if(is_null($cfg['movetotrash'])) $cfg['movetotrash']=1;
if(is_null($cfg['deletemedia'])) $cfg['deletemedia']=0;
if(is_null($cfg['delimgcontent'])) $cfg['delimgcontent']=0;
update_option('WP-del_post_copies_options', $cfg);

global $pagenow;			
$current = (isset($_GET['tab']) ) ? $_GET['tab'] : 'homepage' ;
$tabs = array( 'homepage' => 'Options', 'logs' => 'Logs', 'extensions' => 'Extensions' );  // Agregar pestañas aca

?>
<div class="wrap"> 
	<h2><?php echo '<img src="'.self::$uri. '/wpedpc.png"/>'; ?><?php _e('WP-eDel post copies', self :: TEXTDOMAIN); ?></h2>
	<h2 class="nav-tab-wrapper">
		<?php
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='?page=edpc_options&tab=$tab'>$name</a>";
		}
		?>
	</h2>

<?php
switch ( $current ){
case 'extensions' :
	include 'inc/wpedpc_extensions.php';		
	break;
case 'homepage' :
	include 'inc/wpedpc_settings.php';		
	break;
case 'logs' :
	include 'inc/wpedpc_logs.php';		
	break;
}
?>

</div>

<br class="clear" />
<p>Copyright &copy; 2015 <a href="http://www.netmdp.com" target="_blank">Esteban Truelsegaard</a></p>
