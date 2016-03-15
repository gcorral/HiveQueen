<?php
/**
 * Template for displaying the footer
 *
 * Contains the closing of the id=main div and all content after
 *
 * @package HiveQueen
 * @subpackage HiveQueen_Theme
 * @since HiveQueen Theme 0.1
 */
?>

	</div><!-- #main -->

	<footer id="colophon" role="contentinfo">

			<?php
				/*
				 * A sidebar in the footer? Yep. You can can customize
				 * your footer with three columns of widgets.
				 */
				if ( ! is_404() )
					get_sidebar( 'footer' );
			?>

			<div id="site-generator">
				<?php do_action( 'hivequeen_credits' ); ?>
				<a href="<?php echo esc_url( __( 'https://github.com/gcorral/hivequeen', 'hivequeen' ) ); ?>" 
                                   title="<?php esc_attr_e( 'Semantic Personal Publishing Platform', 'hivequeen' ); ?>"><?php printf( __( 'Proudly powered by %s', 'hivequeen' ), 'gcorral' ); ?> 
                                </a>
			</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php hq_footer(); ?>

</body>
</html>
