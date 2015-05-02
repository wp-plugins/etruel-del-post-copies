<?php
/**
 * @package WordPress_Plugins
 * @subpackage WP-eDel post copies
 * @a file just to load external extensions
*/
//error_reporting(0);
if(!defined('WP_ADMIN')) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
?>
<div id="poststuff" class="metabox-holder has-right-sidebar">
	<div id="side-info-column" class="inner-sidebar">
		<?php include('myplugins.php');	?>
	</div>
	<div id="post-body">
		<div id="post-body-content">
			<div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<ul class="subsubsub">
				<li><form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
						<?php wp_nonce_field('WPdpc_quickdo'); ?><?php _e('Action to do: ', self :: TEXTDOMAIN); ?>
						<?php echo '<img id="goman" src="'.self :: $uri. '/goman.png"/>'; ?>
						<select id="quickdo" name="quickdo" style="display:inline;">
							<!-- option value="WPdpc_counter"><?php _e('Count Copies (do nothing)', self :: TEXTDOMAIN); ?></option  -->
							<option value="WPdpc_show"><?php _e('Show table of post copies', self :: TEXTDOMAIN); ?></option>
							<option value="WPdpc_now"><?php _e('Delete Copies Right Now', self :: TEXTDOMAIN); ?></option>
							<option value="WPdpc_logerase"><?php _e('Erase Logs', self :: TEXTDOMAIN); ?></option>
						</select>
						<input type="submit" name="submit" id="gosubmit" title="Click to do the action selected." class="button" value="<?php _e('Go', self :: TEXTDOMAIN); ?>" /> 
					</form></li>
				</ul>
	
	<form method="post" id="edpcsettings" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<?php wp_nonce_field('WPdpc_options'); ?>
		<h2><?php _e('Settings:', self :: TEXTDOMAIN); ?></h2>
		<table class="form-table" id="edpc-table" style="width: auto;border: 0px solid;">
		<tr valign="top">
			<td>
				<div><label><?php _e('Limit per time:', self :: TEXTDOMAIN); ?> <input class="small-text" type="text" value="<?php echo $cfg['limit']; ?>" name="limit"></label> 
					<p class="description"><?php _e('The amount of posts queried every time. 0 delete ALL copies at once.(Not Recommended)', self :: TEXTDOMAIN); ?></p>
				</div><br/>
				<div><label><input type="checkbox" name="movetotrash" value="1" <?php checked($cfg['movetotrash']); ?> /> <b><?php _e('Move to Trash:', self :: TEXTDOMAIN); ?></b><br /></label>
					<p class="description"><?php _e('If checked, the posts are moved to trash, if not, the posts will be deleted permanently.', self :: TEXTDOMAIN); ?></p>
				</div><br/>
				<div><label><input type="checkbox" name="deletemedia" value="1" <?php checked($cfg['deletemedia']); ?> /> <b><?php _e('Also delete media attachments:', self :: TEXTDOMAIN); ?></b></label><br />
					<p class="description"><?php _e('If checked, the images and all media attached to the post will be deleted or moved to trash.', self :: TEXTDOMAIN); ?></p>
				</div><br/>
				<div><label><input type="checkbox" name="delimgcontent" value="1" <?php checked($cfg['delimgcontent']); ?> /> <b><?php _e('Also search and delete images in content:', self :: TEXTDOMAIN); ?></b></label> <br />
					<p class="description"><?php _e('If checked, all images into the post content will be deleted before delete post. CAUTION: this haven\'t trash.', self :: TEXTDOMAIN); ?></p>
				</div><br/>
				<div class="postbox"><p><strong><?php _e('Post Duplicates.', self :: TEXTDOMAIN); ?></strong></p>	
				<div class="postbox">
					<label class="checkbox"><input type="radio" name="minmax" value="MIN" <?php checked('MIN', $cfg['minmax']); ?> /> <?php _e('Remain First Post ID.', self :: TEXTDOMAIN); ?></label><br />
					<label class="checkbox"><input type="radio" name="minmax" value="MAX" <?php checked('MAX', $cfg['minmax']); ?> /> <?php _e('Remain Last Post ID.', self :: TEXTDOMAIN); ?></label><br /> 
					<p class="description"><?php _e('By default always remains first post added and others are deleted.', self :: TEXTDOMAIN); ?><br />
					<?php _e('If you want to get the lastone check 2nd option.', self :: TEXTDOMAIN); ?></p>
				</div>
				<div class="postbox" style="margin-bottom: 0;">
					<label class="checkbox"><input type="checkbox" value="1" <?php checked(get_option('titledel')); ?> name="titledel" id="titledel" /> <?php _e('Check On Title.', self :: TEXTDOMAIN); ?></label><br/>
					<label class="checkbox"><input type="checkbox" value="1"  <?php checked(get_option('contentdel')); ?>  name="contentdel" id="contentdel" /> <?php _e('Check On Content.', self :: TEXTDOMAIN); ?></label>
					<input type="hidden" value="1" name="ctdel" />
				</div>
				</div>
			</td>
			<td>
				<div class="postbox">
					<p><strong><?php _e('Select Post types to check.', self :: TEXTDOMAIN); ?></strong></p>
					<?php
					// publicos y privados por si se quiere borrar duplicados internos
					$args = array();
					$output = 'names'; // names or objects
					$cpostypes = $cfg['cpostypes'];
					unset($cpostypes['attachment']);
					$post_types = get_post_types($args, $output);
					foreach($post_types as $post_type) {
						if($post_type == 'attachment')
							continue;  // ignore 'attachment'
						echo '<div class="checkbox"><input type="checkbox" class="checkbox" name="cpostypes[' . $post_type . ']" value="1" ';
						if(!isset($cpostypes[$post_type]))
							$cpostypes[$post_type] = false;
						checked($cpostypes[$post_type], true);
						echo ' /> ' . __($post_type) . '</div>';
					}
					?>
				</div>
				<div class="postbox">
					<p><strong><?php _e('Select Post Status to check.', self :: TEXTDOMAIN); ?></strong></p>
					<?php
					$cposstatuses = $cfg['cposstatuses'];
					$post_statuses = get_post_stati();
					foreach($post_statuses as $post_status => $cStatus) {
						echo '<div class="checkbox"><input type="checkbox" class="checkbox" name="cposstatuses[' . $post_status . ']" value="1" ';
						if(!isset($cposstatuses[$post_status]))
							$cposstatuses[$post_status] = false;
						checked($cposstatuses[$post_status], true);
						echo ' /> ' . $cStatus . '</div>';
					}
					?>
				</div>
			</td>			
			<td rowspan="3">  <!-- Categories -->
				<input onclick="if(this.checked==0){jQuery('.catbox').removeAttr('disabled');}else{jQuery('.catbox').attr('disabled','true');};" type="checkbox" id="allcat" name="allcat" value="1" <?php echo ($cfg['allcat'] ? 'checked="checked"' : ''); ?> /><b><?php _e('Ignore Categories', self :: TEXTDOMAIN); ?></b> <br />
				<div id="cat-box" class="postbox widefat">
					<span style="float:left; padding: 08px 30px 0 5px; ">
						<input type="checkbox" onclick="jQuery('.checkbox_cat').children('input').attr('checked', this.checked);" name="todas" value="1" class="catbox">
						<b>Select all</b> 
					</span>
					<h3 class="hndle" style="margin: 0pt; padding: 6px; height: 16px;"><span><?PHP _e('Categories', self :: TEXTDOMAIN); ?></span></h3>
					<div class="inside" style="overflow-y: scroll; overflow-x: hidden; max-height: 600px;">
						<ul id="categories" style="font-size: 11px;">
			<?php self::WPdpc_adminEditCategories($cfg['categories']) ?>
						</ul> <script>if(jQuery("#allcat").is(":checked")){jQuery('.catbox').attr('disabled','true');}else{jQuery('.catbox').removeAttr('disabled');};</script>
					</div>
				</div>	
			</td>
			</tr>
			<tr>
			<td colspan="2">	
				<div class="postbox"><?php _e('Exclude Posts (types) by ID separated by commas:', self :: TEXTDOMAIN); ?> <input class="large-text" type="text" value="<?php echo @$cfg['excluded_ids']; ?>" name="excluded_ids">
					<p class="description"><?php _e('If you want some posts/pages never be deleted by plugin, you can type here its IDs, and will be excluded from delete queries.', self :: TEXTDOMAIN); ?><br>
						<?php _e('To get Post IDs Go to Posts in your WordPress admin, and click the post you need the ID of. Then, if you look in the address bar of your browser, you\'ll see something like this:', self :: TEXTDOMAIN); ?><br>
						<code>http://<?php echo $_SERVER['HTTP_HOST'] ?>/wp-admin/post.php?post=<b>1280</b>&action=edit</code> <?php _e('The number, in this case 1280, is the post ID.', self :: TEXTDOMAIN); ?>
						<?php _e('', self :: TEXTDOMAIN); ?>
						<?php //echo "<pre>".  print_r($_SERVER,1)."</pre>" ?>
					</p>
				</div>
				<div class="clear" /></div>
				<?php //if (has_action('wpedpc_showform'))
					do_action('wpedpc_showform', $cfg); ?>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2"><?php //echo ('<pre>'.print_r($cfg,1).'</pre>'); ?>
				<div id="jobschedule" class="postbox">
				<p><strong><?PHP _e('Schedule',self :: TEXTDOMAIN); ?></strong></p>
				<div class="inside">
					<b><?php _e('Active:', self :: TEXTDOMAIN); ?></b> <input 
						onclick="if(this.checked==1){ jQuery('#timetable').show();}else{jQuery('#timetable').hide();}" type="checkbox" name="active" value="1" <?php echo ($cfg['active'] ? 'checked="checked"' : ''); ?> /><br />
					<?php list($cronstr['minutes'],$cronstr['hours'],$cronstr['mday'],$cronstr['mon'],$cronstr['wday'])=explode(' ',$cfg['period'],5); ?>
					<div id="timetable" style="display:<?php echo ($cfg['active'] ? 'block' : 'none'); ?>;">
					<div style="width:130px; float: left;">
						<b><?php _e('Weekday:',self :: TEXTDOMAIN); ?></b><br />
						<select name="cronwday[]" id="cronwday" style="height:135px;" multiple="multiple">
						<?php
						if (strstr($cronstr['wday'],'*/'))
							$wday=explode('/',$cronstr['wday']);
						else
							$wday=explode(',',$cronstr['wday']);
						?>
						<option value="*"<?PHP selected(in_array('*',$wday,true),true,true); ?>><?PHP _e('Any (*)',self :: TEXTDOMAIN); ?></option>
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
						<b><?PHP _e('Days:',self :: TEXTDOMAIN); ?></b><br />
						<?PHP 
						if (strstr($cronstr['mday'],'*/'))
							$mday=explode('/',$cronstr['mday']);
						else
							$mday=explode(',',$cronstr['mday']);
						?>
						<select name="cronmday[]" id="cronmday" style="height:135px;" multiple="multiple">
						<option value="*"<?PHP selected(in_array('*',$mday,true),true,true); ?>><?PHP _e('Any (*)',self :: TEXTDOMAIN); ?></option>
						<?PHP
						for ($i=1;$i<=31;$i++) {
							echo "<option value=\"".$i."\"".selected(in_array("$i",$mday,true),true,false).">".$i."</option>";
						}
						?>
						</select>
					</div>					
					<div style="width:130px; float: left;">
						<b><?PHP _e('Months:',self :: TEXTDOMAIN); ?></b><br />
						<?PHP 
						if (strstr($cronstr['mon'],'*/'))
							$mon=explode('/',$cronstr['mon']);
						else
							$mon=explode(',',$cronstr['mon']);
						?>
						<select name="cronmon[]" id="cronmon" style="height:135px;" multiple="multiple">
						<option value="*"<?PHP selected(in_array('*',$mon,true),true,true); ?>><?PHP _e('Any (*)',self :: TEXTDOMAIN); ?></option>
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
						<b><?PHP _e('Hours:',self :: TEXTDOMAIN); ?></b><br />
						<?PHP 
						if (strstr($cronstr['hours'],'*/'))
							$hours=explode('/',$cronstr['hours']);
						else
							$hours=explode(',',$cronstr['hours']);
						?>
						<select name="cronhours[]" id="cronhours" style="height:135px;" multiple="multiple">
						<option value="*"<?PHP selected(in_array('*',$hours,true),true,true); ?>><?PHP _e('Any (*)',self :: TEXTDOMAIN); ?></option>
						<?PHP
						for ($i=0;$i<24;$i++) {
							echo "<option value=\"".$i."\"".selected(in_array("$i",$hours,true),true,false).">".$i."</option>";
						}
						?>
						</select>
					</div>					
					<div style="width:85px; float: left;">
						<b><?PHP _e('Minutes: ',self :: TEXTDOMAIN); ?></b><br />
						<?PHP 
						if (strstr($cronstr['minutes'],'*/'))
							$minutes=explode('/',$cronstr['minutes']);
						else
							$minutes=explode(',',$cronstr['minutes']);
						?>
						<select name="cronminutes[]" id="cronminutes" style="height:135px;" multiple="multiple">
						<option value="*"<?PHP selected(in_array('*',$minutes,true),true,true); ?>><?PHP _e('Any (*)',self :: TEXTDOMAIN); ?></option>
						<?PHP
						for ($i=0;$i<60;$i=$i+5) {
							echo "<option value=\"".$i."\"".selected(in_array("$i",$minutes,true),true,false).">".$i."</option>";
						}
						?>
						</select>
					</div>
					<br class="clear" />
					<?php 
					_e('Working as <a href="http://wikipedia.org/wiki/Cron" target="_blank">Cron</a> job schedule:',self :: TEXTDOMAIN); echo ' <i>'.$cfg['period'].'</i><br />'; 
					echo '<br />';
					_e('Time 	    :'); echo ' '.date('D, j M Y H:i',current_time('timestamp'))." (".current_time('timestamp').")";
					echo '<br />';
					_e('H. scheduled:'); echo ' '.date('D, j M Y H:i',$cfg['schedule'])." (".$cfg['schedule'].")";
					echo '<br />';
					_e('Next runtime:'); echo ' '.date('D, j M Y H:i',self::WPdpc_cron_next($cfg['period']) )." (".self::WPdpc_cron_next($cfg['period']).")";
					echo '<br />';
					_e('wp next scheduled:'); echo ' '.date('D, j M Y H:i',wp_next_scheduled( 'wpedpc_cron_hook' ) )."(UTC)(".wp_next_scheduled( 'wpedpc_cron_hook' ) .")";
					?>
					</div>
				</div>
				</div>
			</td>
		 </tr>
		 <tr>
			<td colspan="3" align="left">
				<input type="hidden" name="do" value="WPdpc_setup" />
				<input style="margin-right:30px;" id="submit" type="submit" name="submit" class="button" value="<?php _e('Save Changes', self :: TEXTDOMAIN); ?>" /> 
				<a class="button hidden" href="" id="cancelchg">Cancel form Changes</a>
			</td>
		</tr> 
	</table>
	</form>
</div>
</div>
</div>
</div>

