<?php
/**
 * @package WordPress_Plugins
 * @subpackage WP-eDel post copies
*/
error_reporting(0);

if(!defined('WP_ADMIN') OR !current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));

etruel_del_post_copies_locale();

//*****************************************************************************************
// ** Muestro Categorías seleccionables 
function WPdpc_edit_cat_row($category, $level, &$data) {  
	$category = get_category( $category );
	$name = $category->cat_name;
	echo '
	<li style="margin-left:'.$level.'5px" class="jobtype-select checkbox">
	<input type="checkbox" value="' . $category->cat_ID . '" id="category_' . $category->cat_ID . '" name="categories[]" class="catbox" ';
	echo (in_array($category->cat_ID, $data )) ? 'checked="checked"' : '' ;
	echo '>
    <label for="category_' . $category->cat_ID . '">' . $name . '</label></li>';
}

function WPdpc_adminEditCategories(&$data, $parent = 0, $level = 0, $categories = 0)  {    
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
  				echo "\t" . WPdpc_edit_cat_row($category, $level, $data);
  				if ( isset($children[$category->term_id]) )
  					WPdpc_adminEditCategories($data, $category->term_id, $level + 1, $categories );
  			}
  		}
  		$output = ob_get_contents();
  		ob_end_clean();

  		echo $output;
  	} else {
  		return false;
  	}
}
//Calcs next run for a cron string as timestamp
function WPdpc_cron_next($cronstring) {
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


function WPdpc_save_options($cfg)  {    
	$temp['period']	=	intval($_POST['severy']) * intval($_POST['speriod']);
	$temp['active']	=	(bool)$_POST['active'];
	
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
	$temp['limit']	 = intval($_POST['limit']);
	$temp['allcat'] = (bool)$_POST['allcat'];
	
	// Primero proceso las categorias nuevas si las hay y las agrego al final del array
	   # New categories
    if(isset($_POST['newcat'])) {
      foreach($_POST['newcat'] as $k => $on) {
        $catname = $_POST['newcatname'][$k];
        if(!empty($catname))  {
		   $_POST['categories'][] = wp_insert_category(array('cat_name' => $catname));
        }
      }
    }
    # All: Las elegidas + las nuevas ya agregadas
    if(isset($_POST['categories']))  $temp['categories']=(array)$_POST['categories'];
		else $temp['categories']=array();

	$temp['schedule'] 	= 	WPdpc_cron_next($temp['period']); 
	$temp['logs']		=	$cfg['logs'];
	update_option('WP-del_post_copies_options', $temp);

	if($cfg['active'] AND !$temp['active']) $clear = true;
	if(!$cfg['active'] AND $temp['active']) $schedule = true;
	if($cfg['active'] AND $temp['active'] AND ($temp['period'] != $cfg['period']) ) {
		$clear = true;
		$schedule = true;
	}
	if($clear) 		wp_clear_scheduled_hook('edpc_cron');
	if($schedule) 	wp_schedule_event(0, 'edpc_int', 'edpc_cron');

	return $temp;
}
/*********************** END FUNCTIONS *****************************************/

$cfg = get_option('WP-del_post_copies_options'); 

if(isset($_POST["ctdel"]))
{
if($_POST["titledel"])
		{
	
		update_option('titledel',1);
		}
		else
		{
		update_option('titledel',0);
		}
		
		
		
			if($_POST["contentdel"])
		{
		update_option('contentdel',1);
		}
		else
		{
		update_option('contentdel',0);
		}
		}
		
		
if($_POST['quickdo'] == 'WPdpc_logerase') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = array();
	update_option('WP-del_post_copies_options', $cfg);
}
elseif($_POST['quickdo'] == 'WPdpc_now') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = etruel_del_post_copies_run('now');
}
elseif($_POST['quickdo'] == 'WPdpc_show') {
	check_admin_referer('WPdpc_quickdo');
	//$cfg = WPdpc_save_options($cfg);
	$cfg['logs'] = etruel_del_post_copies_run('show');
}
elseif($_POST['quickdo'] == 'WPdpc_counter') {
	check_admin_referer('WPdpc_quickdo');
	$cfg['logs'] = etruel_del_post_copies_run('counter');
}
elseif($_POST['do'] == 'WPdpc_setup') {
	check_admin_referer('WPdpc_options');
	$cfg = WPdpc_save_options($cfg);
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
				<option value="WPdpc_show"><?php _e('Show Copies', 'WP-del_post_copies'); ?></option>
				<option value="WPdpc_now"><?php _e('Delete Copies Now', 'WP-del_post_copies'); ?></option>
				<option value="WPdpc_logerase"><?php _e('Erase Logs', 'WP-del_post_copies'); ?></option>
			</select>
			<input style="display:inline;" type="submit" name="submit" class="button" value="<?php _e('Go', 'WP-del_post_copies'); ?>" /> 
		</form></li>
	</ul>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
	<?php wp_nonce_field('WPdpc_options'); ?>
	<table class="form-table" style="width: auto;border: 1px solid;">
		<tr>
			<th scope="row" colspan="3" nowrap="nowrap"><b><?php _e('Details:', 'WP-del_post_copies'); ?></b></th>
		</tr>
		<tr valign="top">
			<td colspan="2"><?php _e('Limit per time:', 'WP-del_post_copies'); ?> <input class="small-text" type="text" value=<?php echo $cfg['limit']; ?> name="limit">:<?php _e('0 delete ALL copies at once.(Not Recommended)', 'WP-del_post_copies'); ?>
			<br/><br/>
	<input type="checkbox" value="1" <?php if(get_option('titledel')==1) { echo " checked = 'checked' ";} ?> name="titledel" id="titledel" /> Check On Title<br/>
			<input type="checkbox" value="1"  <?php if(get_option('contentdel')==1) { echo " checked = 'checked' ";} ?>  name="contentdel" id="contentdel" /> Check On Content
			<input type="hidden" value="1" name="ctdel" />
			</td>
			<td rowspan="3">  <!-- Categories -->
				<input style="display:inline" onclick="if(this.checked==0){jQuery('.catbox').removeAttr('disabled');}else{jQuery('.catbox').attr('disabled','true');};" type="checkbox" id="allcat" name="allcat" value="1" <?php echo ($cfg['allcat'] ? 'checked="checked"' : ''); ?> /><b><?php _e('Ignore Categories', 'WP-del_post_copies'); ?></b> <br />
				<div id="cat-box" class="postbox widefat">
					<span style="float:left; padding: 08px 30px 0 5px; "><input type="checkbox" style="display:inline" onclick="jQuery('.checkbox').children('input').attr('checked', this.checked);" name="todas" value="1" class="catbox"><b>Select all</b> </span>
					<h3 class="hndle" style="margin: 0pt; padding: 6px; height: 16px;"><span><?PHP _e('Categories','WP-del_post_copies'); ?></span></h3>
					<div class="inside" style="overflow-y: scroll; overflow-x: hidden; max-height: 310px;">
					<ul id="categories" style="font-size: 11px;">
						<?php WPdpc_adminEditCategories($cfg['categories']) ?>
					</ul> <script>if(jQuery("#allcat").is(":checked")){jQuery('.catbox').attr('disabled','true');}else{jQuery('.catbox').removeAttr('disabled');};</script>
					</div>
					<div id="major-publishing-actions">
					<a href="JavaScript:Void(0);" id="quick_add" onclick="arand=Math.floor(Math.random()*101);jQuery('#categories').append('&lt;li&gt;&lt;input type=&quot;checkbox&quot; name=&quot;newcat[]&quot; checked=&quot;checked&quot;&gt; &lt;input type=&quot;text&quot; id=&quot;newcatname'+arand+'&quot; class=&quot;input_text&quot; name=&quot;newcatname[]&quot;&gt;&lt;/li&gt;');jQuery('#newcatname'+arand).focus();" style="font-weight: bold; text-decoration: none;" ><?PHP _e('Quick add','WP-del_post_copies'); ?>.</a>
					</div>
				</div>	
			</td>
		<tr valign="top">
			<td colspan="2">
				<div id="jobschedule" class="postbox">
				<h3 class="hndle"><span><?PHP _e('Schedule','WP-del_post_copies'); ?></span></h3>
				<div class="inside">
					<b><?php _e('Active:', 'WP-del_post_copies'); ?></b> <input style="display:inline" 
						onclick="if(this.checked==1){ jQuery('#timetable').show();}else{jQuery('#timetable').hide();}" type="checkbox" name="active" value="1" <?php echo ($cfg['active'] ? 'checked="checked"' : ''); ?> /><br />
					<?PHP list($cronstr['minutes'],$cronstr['hours'],$cronstr['mday'],$cronstr['mon'],$cronstr['wday'])=explode(' ',$cfg['period'],5);    ?>
					<div id="timetable" style="display:<?php echo ($cfg['active'] ? 'block' : 'none'); ?>;">
					<div style="width:130px; float: left;">
						<b><?PHP _e('Weekday:','WP-del_post_copies'); ?></b><br />
						<select name="cronwday[]" id="cronwday" style="height:135px;" multiple="multiple">
						<?PHP 
						if (strstr($cronstr['wday'],'*/'))
							$wday=explode('/',$cronstr['wday']);
						else
							$wday=explode(',',$cronstr['wday']);
						?>
						<option value="*"<?PHP selected(in_array('*',$wday,true),true,true); ?>><?PHP _e('Any (*)','WP-del_post_copies'); ?></option>
						<option value="0"<?PHP selected(in_array('0',$wday,true),true,true); ?>><?PHP _e('Sunday'); ?></option>
						<option value="1"<?PHP selected(in_array('1',$wday,true),true,true); ?>><?PHP _e('Monday'); ?></option>
						<option value="2"<?PHP selected(in_array('2',$wday,true),true,true); ?>><?PHP _e('Tuesday'); ?></option>
						<option value="3"<?PHP selected(in_array('3',$wday,true),true,true); ?>><?PHP _e('Wednesday'); ?></option>
						<option value="4"<?PHP selected(in_array('4',$wday,true),true,true); ?>><?PHP _e('Thursday'); ?></option>
						<option value="5"<?PHP selected(in_array('5',$wday,true),true,true); ?>><?PHP _e('Friday'); ?></option>
						<option value="6"<?PHP selected(in_array('6',$wday,true),true,true); ?>><?PHP _e('Saturday'); ?></option>
						</select>
					</div>
					<div style="width:85px; float: left;">
						<b><?PHP _e('Days:','WP-del_post_copies'); ?></b><br />
						<?PHP 
						if (strstr($cronstr['mday'],'*/'))
							$mday=explode('/',$cronstr['mday']);
						else
							$mday=explode(',',$cronstr['mday']);
						?>
						<select name="cronmday[]" id="cronmday" style="height:135px;" multiple="multiple">
						<option value="*"<?PHP selected(in_array('*',$mday,true),true,true); ?>><?PHP _e('Any (*)','WP-del_post_copies'); ?></option>
						<?PHP
						for ($i=1;$i<=31;$i++) {
							echo "<option value=\"".$i."\"".selected(in_array("$i",$mday,true),true,false).">".$i."</option>";
						}
						?>
						</select>
					</div>					
					<div style="width:130px; float: left;">
						<b><?PHP _e('Months:','WP-del_post_copies'); ?></b><br />
						<?PHP 
						if (strstr($cronstr['mon'],'*/'))
							$mon=explode('/',$cronstr['mon']);
						else
							$mon=explode(',',$cronstr['mon']);
						?>
						<select name="cronmon[]" id="cronmon" style="height:135px;" multiple="multiple">
						<option value="*"<?PHP selected(in_array('*',$mon,true),true,true); ?>><?PHP _e('Any (*)','WP-del_post_copies'); ?></option>
						<option value="1"<?PHP selected(in_array('1',$mon,true),true,true); ?>><?PHP _e('January'); ?></option>
						<option value="2"<?PHP selected(in_array('2',$mon,true),true,true); ?>><?PHP _e('February'); ?></option>
						<option value="3"<?PHP selected(in_array('3',$mon,true),true,true); ?>><?PHP _e('March'); ?></option>
						<option value="4"<?PHP selected(in_array('4',$mon,true),true,true); ?>><?PHP _e('April'); ?></option>
						<option value="5"<?PHP selected(in_array('5',$mon,true),true,true); ?>><?PHP _e('May'); ?></option>
						<option value="6"<?PHP selected(in_array('6',$mon,true),true,true); ?>><?PHP _e('June'); ?></option>
						<option value="7"<?PHP selected(in_array('7',$mon,true),true,true); ?>><?PHP _e('July'); ?></option>
						<option value="8"<?PHP selected(in_array('8',$mon,true),true,true); ?>><?PHP _e('Augest'); ?></option>
						<option value="9"<?PHP selected(in_array('9',$mon,true),true,true); ?>><?PHP _e('September'); ?></option>
						<option value="10"<?PHP selected(in_array('10',$mon,true),true,true); ?>><?PHP _e('October'); ?></option>
						<option value="11"<?PHP selected(in_array('11',$mon,true),true,true); ?>><?PHP _e('November'); ?></option>
						<option value="12"<?PHP selected(in_array('12',$mon,true),true,true); ?>><?PHP _e('December'); ?></option>
						</select>
					</div>
					<div style="width:85px; float: left;">
						<b><?PHP _e('Hours:','WP-del_post_copies'); ?></b><br />
						<?PHP 
						if (strstr($cronstr['hours'],'*/'))
							$hours=explode('/',$cronstr['hours']);
						else
							$hours=explode(',',$cronstr['hours']);
						?>
						<select name="cronhours[]" id="cronhours" style="height:135px;" multiple="multiple">
						<option value="*"<?PHP selected(in_array('*',$hours,true),true,true); ?>><?PHP _e('Any (*)','WP-del_post_copies'); ?></option>
						<?PHP
						for ($i=0;$i<24;$i++) {
							echo "<option value=\"".$i."\"".selected(in_array("$i",$hours,true),true,false).">".$i."</option>";
						}
						?>
						</select>
					</div>					
					<div style="width:85px; float: left;">
						<b><?PHP _e('Minutes: ','WP-del_post_copies'); ?></b><br />
						<?PHP 
						if (strstr($cronstr['minutes'],'*/'))
							$minutes=explode('/',$cronstr['minutes']);
						else
							$minutes=explode(',',$cronstr['minutes']);
						?>
						<select name="cronminutes[]" id="cronminutes" style="height:135px;" multiple="multiple">
						<option value="*"<?PHP selected(in_array('*',$minutes,true),true,true); ?>><?PHP _e('Any (*)','WP-del_post_copies'); ?></option>
						<?PHP
						for ($i=0;$i<60;$i=$i+5) {
							echo "<option value=\"".$i."\"".selected(in_array("$i",$minutes,true),true,false).">".$i."</option>";
						}
						?>
						</select>
					</div>
					<br class="clear" />
					<?PHP 
					_e('Working as <a href="http://wikipedia.org/wiki/Cron" target="_blank">Cron</a> job schedule:','WP-del_post_copies'); echo ' <i>'.$cfg['period'].'</i><br />'; 
					_e('Next runtime:'); echo ' '.date('D, j M Y H:i',WPdpc_cron_next($cfg['period']))." (".WPdpc_cron_next($cfg['period']).")";
					_e('<br>H. Squeduled:'); echo ' '.date('D, j M Y H:i',$cfg['schedule'])." (".$cfg['schedule'].")";
					_e('<br>Time 	    :'); echo ' '.date('D, j M Y H:i',current_time('timestamp'))." (".current_time('timestamp').")";
					?>
					</div>
				</div>
				</div>
			</td>
		 </tr>
		 <tr>
			<td colspan="3" align="left">
				<input type="hidden" name="do" value="WPdpc_setup" />
				<input style="margin-right:50px;" type="submit" name="submit" class="button" value="<?php _e('Save Changes', 'WP-del_post_copies'); ?>" /> 
			</td>
		</tr> 
	</table>
	</form>
</div>
<br />
<br />
<p>Copyright &copy; 2013 <a href="http://www.netmdp.com" target="_blank">Esteban Truelsegaard</a></p>
<br />
<!-- div id="message" class="updated fade"><p><?php //echo implode('<br />', $WPdpc_msg); ?></p></div  -->
<?php if(!empty($cfg['logs'])): ?>
<div class="wrap">
<h2><?php _e('Del Post Copies Logs', 'WP-del_post_copies'); ?></h2><br />
<div style="overflow-y: scroll; overflow-x: hidden; max-height: 310px;">
<table class="widefat" style="overflow-y: scroll; overflow-x: hidden; max-height: 310px;">
<thead>
  <tr>
	<th scope="col">#</th>
	<th scope="col"><?php _e('Date', 'WP-del_post_copies'); ?></th>
	<th scope="col"><?php _e('Mode', 'WP-del_post_copies'); ?></th>
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
	<td><?php echo (intval($log['mode'])==1)? __('Manual', 'WP-del_post_copies') : __("Auto", 'WP-del_post_copies'); ?></td>
	<td><?php echo (intval($log['status'])==0)? __('OK', 'WP-del_post_copies') : sprintf(__("%s errors", 'WP-del_post_copies'), intval($log['status'])); ?></td>
	<td><?php echo round($log['took'], 3); ?> <?php _e('seconds', 'WP-del_post_copies'); ?></td>
	<td><?php echo sprintf(__("%s posts", 'WP-del_post_copies'), intval($log['removed'])); ?></td>
  </tr>
  <?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php endif; ?>