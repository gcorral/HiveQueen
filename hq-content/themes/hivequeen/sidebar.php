<?php
/**
 * Sidebar containing the main widget area
 *
 * @package HiveQueen
 * @subpackage HiveQueen_Theme
 * @since HiveQueen Theme 0.1
 */

$options = hivequeen_get_theme_options();
$current_layout = $options['theme_layout'];

if ( 'content' != $current_layout ) :
?>
		<div id="secondary" class="widget-area" role="complementary">
			<?php if ( ! dynamic_sidebar( 'sidebar-1' ) ) : ?>

				<aside id="archives" class="widget">
					<h3 class="widget-title"><?php _e( 'Side Bar', 'hivequeen' ); ?></h3>
					<ul>
						<?php //TODO: Goyo no archives: hq_get_archives( array( 'type' => 'monthly' ) ); ?>
					</ul>
				</aside>

				<aside id="meta" class="widget">
					<h3 class="widget-title"><?php _e( 'Meta', 'hivequeen' ); ?></h3>
					<ul>
						<?php hq_register(); ?>
						<li><?php hq_loginout(); ?></li>
						<?php //TODO: Goyo no meta: hq_meta(); ?>
					</ul>
				</aside>

			<?php endif; // end sidebar widget area ?>
		</div><!-- #secondary .widget-area -->
<?php endif; ?>
