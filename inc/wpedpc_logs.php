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
<!-- div id="message" class="updated fade"><p><?php //echo implode('<br />', $WPdpc_msg); ?></p></div  -->
<h2><?php _e('Del Post Copies Logs', self :: TEXTDOMAIN); ?></h2><br />
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div id="side-info-column" class="inner-sidebar">
			<?php include('myplugins.php'); ?>
		</div>
		<div id="post-body-content">
		<?php if(!empty($cfg['logs'])): ?>
		<div style="overflow-y: scroll; overflow-x: hidden; max-height: 700px;">
			<table class="widefat" style="overflow-y: scroll; overflow-x: hidden; max-height: 310px;">
				<thead>
					<tr>
						<th scope="col">#</th>
						<th scope="col"><?php _e('Date', self :: TEXTDOMAIN); ?></th>
						<th scope="col"><?php _e('Mode', self :: TEXTDOMAIN); ?></th>
						<th scope="col"><?php _e('Status', self :: TEXTDOMAIN); ?></th>
						<th scope="col"><?php _e('Finished In', self :: TEXTDOMAIN); ?></th>
						<th scope="col"><?php _e('Removed', self :: TEXTDOMAIN); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 0;
					foreach(array_reverse($cfg['logs']) AS $log):
						?>
						<tr>
							<td><?php echo ++$i; ?></td>
							<td><?php echo date('Y-m-d H:i:s', $log['started']); ?></td>
							<td><?php echo (intval($log['mode']) == 1) ? __('Manual', self :: TEXTDOMAIN) : __("Auto", self :: TEXTDOMAIN); ?></td>
							<td><?php echo (intval($log['status']) == 0) ? __('OK', self :: TEXTDOMAIN) : sprintf(__("%s errors", self :: TEXTDOMAIN), intval($log['status'])); ?></td>
							<td><?php echo round($log['took'], 3); ?> <?php _e('seconds', self :: TEXTDOMAIN); ?></td>
							<td><?php echo sprintf(__("%s posts", self :: TEXTDOMAIN), intval($log['removed'])); ?></td>
						</tr>
						<?php
						if($i >= 40)
							break;  //just 40 rows of log
					endforeach;
					?>
				</tbody>
			</table>
		</div>
  <?php endif; ?>
	</div>
</div>
