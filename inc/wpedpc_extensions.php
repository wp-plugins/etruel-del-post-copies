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
$extensions = array(
	'wpedpc-oldest-posts' => (object) array(
		'url'       => 'http://etruel.com/downloads/wp-edel-oldest-post/',
		'title'     => __( 'WP-eDel-Oldest-Post', 'WP_del_post_copies' ),
		'desc'      => __( 'Enabled WP-eDel post copies to delete dated posts. As prior certain date or prior to certains months ago.', 'WP_del_post_copies' ),
		'installed' => false,
	)
);

if ( class_exists( 'WP_eDel_Oldest_Post' ) ) {
	$extensions['wpedpc-oldest-posts']->installed = true;
}
//echo ('<pre>'.print_r($cfg,1).'</pre>'); 
?>
<script type="text/javascript" charset="utf8" >
	jQuery(document).ready(function($) {
		$("#tabs").tabs();
	});
</script>
<div id="post-body">
<div id="post-body-content">
<div class="wrap wpedpc_table_page">
	<div id="tabs">
	<ul class="tabNavigation"><h2 id="wpedpc-title" class="nav-tab-wrapper" ><?php _e( 'WP-eDel post copies Extensions', 'WP_del_post_copies' ); ?></h2>
		<li><a href="#premium"><?php _e( 'Premium Extensions', 'WP_del_post_copies' ); ?></a></li>
		<li><a href="#licenses"><?php _e( 'Licenses', 'WP_del_post_copies' ); ?></a></li>
	</ul>
		<div id="premium">
			<?php
			foreach ( $extensions as $id => $extension ) {
				$utm = '#utm_source=WP_del_post_copies-config&utm_medium=banner&utm_campaign=extension-page-banners';
				?>
				<div class="extension <?php echo esc_attr( $id ); ?>">
					<a target="_blank" href="<?php echo esc_url( $extension->url . $utm ); ?>">
						<h3><?php echo esc_html( $extension->title ); ?></h3>
					</a>

					<p><?php echo esc_html( $extension->desc ); ?></p>

					<p>
						<?php if ( $extension->installed ) : ?>
							<button class="button-primary installed">Installed</button>
						<?php else : ?>
							<a target="_blank" href="<?php echo esc_url( $extension->url . $utm ); ?>" class="button-primary">
								<?php _e( 'Get this extension', 'WP_del_post_copies' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
			<?php
			}
			unset( $extensions, $id, $extension, $utm );
			?>
		</div>
		<div id="licenses">
			<?php
			/**
			 * Display license page
			 */
			settings_errors();
			if ( ! has_action( 'wpedpc_licenses_forms' ) ) {
				echo '<div class="msg"><p>', __( 'This is where you would enter the license keys for one of our premium plugins, should you activate one.', 'WP_del_post_copies' ), '</p></div>';
			}
			else {
				do_action( 'wpedpc_licenses_forms' );
			}
			?>
		</div>
	</div>
</div>
</div>
</div>
