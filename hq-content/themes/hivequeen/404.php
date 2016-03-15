<?php
/**
 * Template for displaying 404 pages (Not Found)
 *
 * @package HiveQueen
 * @subpackage HiveQueen_Theme
 * @since HiveQueen Theme 0.1
 */

get_header(); ?>

	<div id="primary">
		<div id="content" role="main">

			<article id="post-0" class="post error404 not-found">
				<header class="entry-header">
					<h1 class="entry-title"><?php _e( 'This is somewhat embarrassing, isn&rsquo;t it?', 'hivequeen' ); ?></h1>
				</header>

				<div class="entry-content">
					<p><?php _e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching, or one of the links below, can help.', 'hivequeen' ); ?></p>

					<?php //TODO: get_search_form(); ?>

					<?php //TODO: the_widget( 'HQ_Widget_Recent_Posts', array( 'number' => 10 ), array( 'widget_id' => '404' ) ); ?>

                                        <!--
					<div class="widget">
						<h2 class="widgettitle"><?php _e( 'Most Used Categories', 'hivequeen' ); ?></h2>
						<ul>
						<?php //TODO: hq_list_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'show_count' => 1, 'title_li' => '', 'number' => 10 ) ); ?>
						</ul>
					</div>
                                        -->

					<?php
					/* translators: %1$s: smilie */
                                        //TODO: !!!
					//$archive_content = '<p>' . sprintf( __( 'Try looking in the monthly archives. %1$s', 'hivequeen' ), convert_smilies( ':)' ) ) . '</p>';
					//the_widget( 'HQ_Widget_Archives', array( 'count' => 0, 'dropdown' => 1 ), array( 'after_title' => '</h2>' . $archive_content ) );
					?>

					<?php //TODO: the_widget( 'HQ_Widget_Tag_Cloud' ); ?>

				</div><!-- .entry-content -->
			</article><!-- #post-0 -->

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_footer(); ?>
