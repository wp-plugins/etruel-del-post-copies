<?php
/**
 * @package WordPress_Plugins
 * @subpackage WP-eDel post copies
*/
if(!defined('WP_ADMIN') OR !current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));

etruel_del_post_copies_locale();
$cfg = get_option('WP-del_post_copies_options'); 
if($_POST['quickdo'] == 'WPdpc_logerase') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = array();
	update_option('WP-del_post_copies_options', $cfg);
}
elseif($_POST['quickdo'] == 'WPdpc_now') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = etruel_del_post_copies_run('now');
}
elseif($_POST['quickdo'] == 'WPdpc_counter') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = etruel_del_post_copies_run('counter');
}
elseif($_POST['do'] == 'WPdpc_setup')
{
	check_admin_referer('WPdpc_options');
	$temp['period']		=	intval($_POST['severy']) * intval($_POST['speriod']);
	$temp['active']		=	(bool)$_POST['active'];
	$temp['limit']			=	intval($_POST['limit']);
	$temp['logs']			=	$cfg['logs'];
	
	$timenow 				= 	time();
	$year 					= 	date('Y', $timenow);
	$month  				= 	date('n', $timenow);
	$day   					= 	date('j', $timenow);
	$hours   				= 	intval($_POST['hours']);
	$minutes 				= 	intval($_POST['minutes']);
	$seconds 				= 	intval($_POST['seconds']);
	$temp['schedule'] 	= 	mktime($hours, $minutes, $seconds, $month, $day, $year);
	update_option('WP-del_post_copies_options', $temp);

	if($cfg['active'] AND !$temp['active']) $clear = true;
	if(!$cfg['active'] AND $temp['active']) $schedule = true;
	if($cfg['active'] AND $temp['active'] AND (array($hours, $minutes, $seconds) != explode('-', date('G-i-s', $cfg['schedule'])) OR $temp['period'] != $cfg['period']) ) {
		$clear = true;
		$schedule = true;
	}
	if($clear) 		wp_clear_scheduled_hook('wp_edpc_sched');
	if($schedule) 	wp_schedule_event($temp['schedule'], 'WP_del_post_copies', 'wp_edpc_sched');
	$cfg = $temp;
	?><div id="message" class="updated fade"><p><?php _e('Options saved.') ?></p></div><?php
}

$is_safe_mode = ini_get('safe_mode') == '1' ? 1 : 0;

?>  

<div class="wrap"> 
	<h2><?php echo '<img src="'.WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/wpedpc.png"/>'; ?><?php _e('WP-eDel post copies Options', 'WP-del_post_copies'); ?></h2>
	<ul class="subsubsub">
		<li><form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
			<?php wp_nonce_field('WPdpc_quickdo'); ?>
			<select name="quickdo" style="display:inline;">
				<!-- option value="WPdpc_counter"><?php _e('Count Copies (do nothing)', 'WP-del_post_copies'); ?></option  -->
				<option value="WPdpc_now"><?php _e('Delete Copies Now', 'WP-del_post_copies'); ?></option>
				<option value="WPdpc_logerase"><?php _e('Erase Logs', 'WP-del_post_copies'); ?></option>
			</select>
			<input style="display:inline;" type="submit" name="submit" class="button" value="<?php _e('Go', 'WP-del_post_copies'); ?>" /> 
		</form></li>
	</ul>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
	<?php wp_nonce_field('WPdpc_options'); ?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row" nowrap="nowrap"><?php _e('Details:', 'WP-del_post_copies'); ?></th>
			<td><?php _e('Limit per time:', 'WP-del_post_copies'); ?> <input class="small-text" type="text" value=<?php echo $cfg['limit']; ?> name="limit">:<?php _e('0 delete ALL copies at once.(Not Recommended)', 'WP-del_post_copies'); ?>
			</td>
		<tr valign="top">
			<th scope="row" nowrap="nowrap"><?php _e('Scheduled Delete Copies:', 'WP-del_post_copies'); ?></th>
			<td><b><?php _e('Active:', 'WP-del_post_copies'); ?></b> <input style="display:inline" 
				onclick="if(this.checked==1){ jQuery('#timetable').show();jQuery('#timetable2').show();}else{jQuery('#timetable').hide();jQuery('#timetable2').hide();}" type="checkbox" name="active" value="1" <?php echo ($cfg['active'] ? 'checked="checked"' : ''); ?> /><br />		
				<?php 
				list($hours, $minutes, $seconds) = explode('-', date('G-i-s', $cfg['schedule'])); 
				$times = array('hours', 'minutes', 'seconds');
				$periods = array(3600 => __('Hour(s)', 'WP-del_post_copies'), 86400 => __('Day(s)', 'WP-del_post_copies'), 604800 => __('Week(s)', 'WP-del_post_copies'), 2592000 => __('Month(s)', 'WP-del_post_copies'));
				$tmonth	=	$cfg['period'] / 2592000;
				$tweek	=	$cfg['period'] / 604800;
				$tday	=	$cfg['period'] / 86400;
				$thour	=	$cfg['period'] / 3600;
				
				if(is_int($tmonth) 		AND $tmonth > 0)	{	$speriod = 2592000;	$severy	= $tmonth;	}
				elseif(is_int($tweek) 	AND $tweek > 0)		{	$speriod = 604800;	$severy	= $tweek;	}
				elseif(is_int($tday) 	AND $tday > 0)		{	$speriod = 86400;	$severy	= $tday;	}
				elseif(is_int($thour)	AND $thour > 0)		{	$speriod = 3600;	$severy	= $thour;	}
				?><div id="timetable" style="display:<?php echo ($cfg['active'] ? 'block' : 'none'); ?>;">
				<label><?php _e('Run Every', 'WP-del_post_copies'); ?>:
					<select name="severy">
					<?php for ($i = 1; $i <= 12; $i++): $selected = ($severy == $i) ? 'selected' : ''; ?>
					<option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?></option>
					<?php endfor; ?>
					</select></label>&nbsp;
			   
				<select name="speriod"><?php
					foreach($periods as $period => $display):
					$selected = ($period == $speriod) ? 'selected' : ''; ?>
					<option value="<?php echo $period; ?>" <?php echo $selected; ?>><?php echo $display; ?></option>
					<?php endforeach; ?>
					</select><br /><?php
				
				foreach($times AS $time):
					$max = $time == 'hours' ? 24 : 60; ?><label>
					<?php
					if($time == 'hours')  _e('Hours', 'WP-del_post_copies');
					elseif($time == 'minutes')  _e('Minutes', 'WP-del_post_copies');
					elseif($time == 'seconds')  _e('Seconds', 'WP-del_post_copies');
					?>: <select name="<?php echo $time; ?>">
					<?php for ($i = 0; $i<$max; $i++): $selected = ($$time == $i) ? 'selected' : ''; ?>
					<option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?></option>
					<?php endfor; ?>
					</select></label>&nbsp;<?php
				endforeach;
				?></div>
		</tr>
		 <tr>
			<th scope="row" nowrap="nowrap"><?php _e('Server Dates/Times', 'WP-del_post_copies'); ?></th>
			<td>
				<?php _e('Current Date:', 'WP-del_post_copies'); 
						echo date('Y-m-d H:i:s', time()); ?><br/><?php
						if($next_scheduled = wp_next_scheduled('wp_edpc_sched')):
							?><div id="timetable2" style="display:<?php echo ($cfg['active'] ? 'block' : 'none'); ?>;">
								<br/><b><?php _e('Next Schedule is on: ', 'WP-del_post_copies');  
							?></b><?php echo date('Y-m-d H:i:s', $next_scheduled); ?></div><?php
						endif;
				?>
			</td>
		 </tr>
		 <tr>
			<td colspan="2" align="center">
				<input type="hidden" name="do" value="WPdpc_setup" />
				<input type="submit" name="submit" class="button" value="<?php _e('Save Changes', 'WP-del_post_copies'); ?>" /> 
			</td>
		</tr> 
	</table>
	</form>
</div>
<br />
<p>Copyright &copy; 2010 <a href="http://www.netmdp.com" target="_blank">Esteban Truelsegaard</a></p>
<br />
<!-- div id="message" class="updated fade"><p><?php //echo implode('<br />', $WPdpc_msg); ?></p></div  -->
<?php if(!empty($cfg['logs'])): ?>
<div class="wrap">
<h2><?php _e('Del Post Copies Logs', 'WP-del_post_copies'); ?></h2><br />
<table class="widefat">
<thead>
  <tr>
	<th scope="col">#</th>
	<th scope="col"><?php _e('Date', 'WP-del_post_copies'); ?></th>
	<th scope="col"><?php _e('Status', 'WP-del_post_copies'); ?></th>
	<th scope="col"><?php _e('Finished In', 'WP-del_post_copies'); ?></th>
	<th scope="col"><?php _e('Removed', 'WP-del_post_copies'); ?></th>
  </tr>
</thead>
<tbody>
<?php 
$i = 0;
foreach($cfg['logs'] AS $log): ?>
  <tr>
	<td><?php echo ++$i; ?></td>
	<td><?php echo date('Y-m-d H:i:s', $log['started']); ?></td>
	<td><?php echo (intval($log['status'])==0)? __('OK', 'WP-del_post_copies') : sprintf(__("%s errors", 'WP-del_post_copies'), intval($log['status'])); ?></td>
	<td><?php echo round($log['took'], 3); ?> <?php _e('seconds', 'WP-del_post_copies'); ?></td>
	<td><?php echo sprintf(__("%s posts", 'WP-del_post_copies'), intval($log['removed'])); ?></td>
  </tr>
  <?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif;

?>