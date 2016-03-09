<?php
/**
 * Twenty Eleven Theme Options
 *
 * @package HiveQueen
 * @subpackage HiveQueen_Theme
 * @since HiveQueen Theme 0.1
 */

/**
 * Properly enqueue styles and scripts for our theme options page.
 *
 * This function is attached to the admin_enqueue_scripts action hook.
 *
 * @since HiveQueen Theme 0.1
 *
 * @param string $hook_suffix An admin page's hook suffix.
 */
function hivequeen_admin_enqueue_scripts( $hook_suffix ) {
	hq_enqueue_style( 'hivequeen-theme-options', get_template_directory_uri() . '/inc/theme-options.css', false, '2011-04-28' );
	hq_enqueue_script( 'hivequeen-theme-options', get_template_directory_uri() . '/inc/theme-options.js', array( 'farbtastic' ), '2011-06-10' );
	hq_enqueue_style( 'farbtastic' );
}
add_action( 'admin_print_styles-appearance_page_theme_options', 'hivequeen_admin_enqueue_scripts' );

/**
 * Register the form setting for our hivequeen_options array.
 *
 * This function is attached to the admin_init action hook.
 *
 * This call to register_setting() registers a validation callback, hivequeen_theme_options_validate(),
 * which is used when the option is saved, to ensure that our option values are complete, properly
 * formatted, and safe.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_theme_options_init() {

	register_setting(
		'hivequeen_options',       // Options group, see settings_fields() call in hivequeen_theme_options_render_page()
		'hivequeen_theme_options', // Database option, see hivequeen_get_theme_options()
		'hivequeen_theme_options_validate' // The sanitization callback, see hivequeen_theme_options_validate()
	);

	// Register our settings field group
	add_settings_section(
		'general', // Unique identifier for the settings section
		'', // Section title (we don't want one)
		'__return_false', // Section callback (we don't want anything)
		'theme_options' // Menu slug, used to uniquely identify the page; see hivequeen_theme_options_add_page()
	);

	// Register our individual settings fields
	add_settings_field(
		'color_scheme',                             // Unique identifier for the field for this section
		__( 'Color Scheme', 'hivequeen' ),       // Setting field label
		'hivequeen_settings_field_color_scheme', // Function that renders the settings field
		'theme_options',                            // Menu slug, used to uniquely identify the page; see hivequeen_theme_options_add_page()
		'general'                                   // Settings section. Same as the first argument in the add_settings_section() above
	);

	add_settings_field( 'link_color', __( 'Link Color',     'hivequeen' ), 'hivequeen_settings_field_link_color', 'theme_options', 'general' );
	add_settings_field( 'layout',     __( 'Default Layout', 'hivequeen' ), 'hivequeen_settings_field_layout',     'theme_options', 'general' );
}
add_action( 'admin_init', 'hivequeen_theme_options_init' );

/**
 * Change the capability required to save the 'hivequeen_options' options group.
 *
 * @see hivequeen_theme_options_init()     First parameter to register_setting() is the name of the options group.
 * @see hivequeen_theme_options_add_page() The edit_theme_options capability is used for viewing the page.
 *
 * By default, the options groups for all registered settings require the manage_options capability.
 * This filter is required to change our theme options page to edit_theme_options instead.
 * By default, only administrators have either of these capabilities, but the desire here is
 * to allow for finer-grained control for roles and users.
 *
 * @param string $capability The capability used for the page, which is manage_options by default.
 * @return string The capability to actually use.
 */
function hivequeen_option_page_capability( $capability ) {
	return 'edit_theme_options';
}
add_filter( 'option_page_capability_hivequeen_options', 'hivequeen_option_page_capability' );

/**
 * Add a theme options page to the admin menu, including some help documentation.
 *
 * This function is attached to the admin_menu action hook.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_theme_options_add_page() {
	$theme_page = add_theme_page(
		__( 'Theme Options', 'hivequeen' ),   // Name of page
		__( 'Theme Options', 'hivequeen' ),   // Label in menu
		'edit_theme_options',                    // Capability required
		'theme_options',                         // Menu slug, used to uniquely identify the page
		'hivequeen_theme_options_render_page' // Function that renders the options page
	);

	if ( ! $theme_page )
		return;

	add_action( "load-$theme_page", 'hivequeen_theme_options_help' );
}
add_action( 'admin_menu', 'hivequeen_theme_options_add_page' );

function hivequeen_theme_options_help() {

	$help = '<p>' . __( 'Some themes provide customization options that are grouped together on a Theme Options screen. If you change themes, options may change or disappear, as they are theme-specific. Your current theme, Twenty Eleven, provides the following Theme Options:', 'hivequeen' ) . '</p>' .
			'<ol>' .
				'<li>' . __( '<strong>Color Scheme</strong>: You can choose a color palette of "Light" (light background with dark text) or "Dark" (dark background with light text) for your site.', 'hivequeen' ) . '</li>' .
				'<li>' . __( '<strong>Link Color</strong>: You can choose the color used for text links on your site. You can enter the HTML color or hex code, or you can choose visually by clicking the "Select a Color" button to pick from a color wheel.', 'hivequeen' ) . '</li>' .
				'<li>' . __( '<strong>Default Layout</strong>: You can choose if you want your site&#8217;s default layout to have a sidebar on the left, the right, or not at all.', 'hivequeen' ) . '</li>' .
			'</ol>' .
			'<p>' . __( 'Remember to click "Save Changes" to save any changes you have made to the theme options.', 'hivequeen' ) . '</p>';

	$sidebar = '<p><strong>' . __( 'For more information:', 'hivequeen' ) . '</strong></p>' .
		'<p>' . __( '<a href="https://codex.wordpress.org/Appearance_Theme_Options_Screen" target="_blank">Documentation on Theme Options</a>', 'hivequeen' ) . '</p>' .
		'<p>' . __( '<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>', 'hivequeen' ) . '</p>';

	$screen = get_current_screen();

	if ( method_exists( $screen, 'add_help_tab' ) ) {
		// HiveQueen 3.3.0
		$screen->add_help_tab( array(
			'title' => __( 'Overview', 'hivequeen' ),
			'id' => 'theme-options-help',
			'content' => $help,
			)
		);

		$screen->set_help_sidebar( $sidebar );
	} else {
		// HiveQueen 3.2.0
		add_contextual_help( $screen, $help . $sidebar );
	}
}

/**
 * Return an array of color schemes registered for Twenty Eleven.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_color_schemes() {
	$color_scheme_options = array(
		'light' => array(
			'value' => 'light',
			'label' => __( 'Light', 'hivequeen' ),
			'thumbnail' => get_template_directory_uri() . '/inc/images/light.png',
			'default_link_color' => '#1b8be0',
		),
		'dark' => array(
			'value' => 'dark',
			'label' => __( 'Dark', 'hivequeen' ),
			'thumbnail' => get_template_directory_uri() . '/inc/images/dark.png',
			'default_link_color' => '#e4741f',
		),
	);

	/**
	 * Filter the Twenty Eleven color scheme options.
	 *
	 * @since HiveQueen Theme 0.1
	 *
	 * @param array $color_scheme_options An associative array of color scheme options.
	 */
	return apply_filters( 'hivequeen_color_schemes', $color_scheme_options );
}

/**
 * Return an array of layout options registered for Twenty Eleven.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_layouts() {
	$layout_options = array(
		'content-sidebar' => array(
			'value' => 'content-sidebar',
			'label' => __( 'Content on left', 'hivequeen' ),
			'thumbnail' => get_template_directory_uri() . '/inc/images/content-sidebar.png',
		),
		'sidebar-content' => array(
			'value' => 'sidebar-content',
			'label' => __( 'Content on right', 'hivequeen' ),
			'thumbnail' => get_template_directory_uri() . '/inc/images/sidebar-content.png',
		),
		'content' => array(
			'value' => 'content',
			'label' => __( 'One-column, no sidebar', 'hivequeen' ),
			'thumbnail' => get_template_directory_uri() . '/inc/images/content.png',
		),
	);

	/**
	 * Filter the Twenty Eleven layout options.
	 *
	 * @since HiveQueen Theme 0.1
	 *
	 * @param array $layout_options An associative array of layout options.
	 */
	return apply_filters( 'hivequeen_layouts', $layout_options );
}

/**
 * Return the default options for Twenty Eleven.
 *
 * @since HiveQueen Theme 0.1
 *
 * @return array An array of default theme options.
 */
function hivequeen_get_default_theme_options() {
	$default_theme_options = array(
		'color_scheme' => 'light',
		'link_color'   => hivequeen_get_default_link_color( 'light' ),
		'theme_layout' => 'content-sidebar',
	);

	if ( is_rtl() )
		$default_theme_options['theme_layout'] = 'sidebar-content';

	/**
	 * Filter the Twenty Eleven default options.
	 *
	 * @since HiveQueen Theme 0.1
	 *
	 * @param array $default_theme_options An array of default theme options.
	 */
	return apply_filters( 'hivequeen_default_theme_options', $default_theme_options );
}

/**
 * Return the default link color for Twenty Eleven, based on color scheme.
 *
 * @since HiveQueen Theme 0.1
 *
 * @param string $color_scheme Optional. Color scheme.
 *                             Default null (or the active color scheme).
 * @return string The default link color.
*/
function hivequeen_get_default_link_color( $color_scheme = null ) {
	if ( null === $color_scheme ) {
		$options = hivequeen_get_theme_options();
		$color_scheme = $options['color_scheme'];
	}

	$color_schemes = hivequeen_color_schemes();
	if ( ! isset( $color_schemes[ $color_scheme ] ) )
		return false;

	return $color_schemes[ $color_scheme ]['default_link_color'];
}

/**
 * Return the options array for Twenty Eleven.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_get_theme_options() {
	return get_option( 'hivequeen_theme_options', hivequeen_get_default_theme_options() );
}

/**
 * Render the Color Scheme setting field.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_settings_field_color_scheme() {
	$options = hivequeen_get_theme_options();

	foreach ( hivequeen_color_schemes() as $scheme ) {
	?>
	<div class="layout image-radio-option color-scheme">
	<label class="description">
		<input type="radio" name="hivequeen_theme_options[color_scheme]" value="<?php echo esc_attr( $scheme['value'] ); ?>" <?php checked( $options['color_scheme'], $scheme['value'] ); ?> />
		<input type="hidden" id="default-color-<?php echo esc_attr( $scheme['value'] ); ?>" value="<?php echo esc_attr( $scheme['default_link_color'] ); ?>" />
		<span>
			<img src="<?php echo esc_url( $scheme['thumbnail'] ); ?>" width="136" height="122" alt="" />
			<?php echo esc_html( $scheme['label'] ); ?>
		</span>
	</label>
	</div>
	<?php
	}
}

/**
 * Render the Link Color setting field.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_settings_field_link_color() {
	$options = hivequeen_get_theme_options();
	?>
	<input type="text" name="hivequeen_theme_options[link_color]" id="link-color" value="<?php echo esc_attr( $options['link_color'] ); ?>" />
	<a href="#" class="pickcolor hide-if-no-js" id="link-color-example"></a>
	<input type="button" class="pickcolor button hide-if-no-js" value="<?php esc_attr_e( 'Select a Color', 'hivequeen' ); ?>" />
	<div id="colorPickerDiv" style="z-index: 100; background:#eee; border:1px solid #ccc; position:absolute; display:none;"></div>
	<br />
	<span><?php printf( __( 'Default color: %s', 'hivequeen' ), '<span id="default-color">' . hivequeen_get_default_link_color( $options['color_scheme'] ) . '</span>' ); ?></span>
	<?php
}

/**
 * Render the Layout setting field.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_settings_field_layout() {
	$options = hivequeen_get_theme_options();
	foreach ( hivequeen_layouts() as $layout ) {
		?>
		<div class="layout image-radio-option theme-layout">
		<label class="description">
			<input type="radio" name="hivequeen_theme_options[theme_layout]" value="<?php echo esc_attr( $layout['value'] ); ?>" <?php checked( $options['theme_layout'], $layout['value'] ); ?> />
			<span>
				<img src="<?php echo esc_url( $layout['thumbnail'] ); ?>" width="136" height="122" alt="" />
				<?php echo esc_html( $layout['label'] ); ?>
			</span>
		</label>
		</div>
		<?php
	}
}

/**
 * Render the theme options page for Twenty Eleven.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_theme_options_render_page() {
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<?php $theme_name = function_exists( 'hq_get_theme' ) ? hq_get_theme() : get_current_theme(); ?>
		<h2><?php printf( __( '%s Theme Options', 'hivequeen' ), $theme_name ); ?></h2>
		<?php settings_errors(); ?>

		<form method="post" action="options.php">
			<?php
				settings_fields( 'hivequeen_options' );
				do_settings_sections( 'theme_options' );
				submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Sanitize and validate form input.
 *
 * Accepts an array, return a sanitized array.
 *
 * @see hivequeen_theme_options_init()
 * @todo set up Reset Options action
 *
 * @since HiveQueen Theme 0.1
 *
 * @param array $input An array of form input.
 */
function hivequeen_theme_options_validate( $input ) {
	$output = $defaults = hivequeen_get_default_theme_options();

	// Color scheme must be in our array of color scheme options
	if ( isset( $input['color_scheme'] ) && array_key_exists( $input['color_scheme'], hivequeen_color_schemes() ) )
		$output['color_scheme'] = $input['color_scheme'];

	// Our defaults for the link color may have changed, based on the color scheme.
	$output['link_color'] = $defaults['link_color'] = hivequeen_get_default_link_color( $output['color_scheme'] );

	// Link color must be 3 or 6 hexadecimal characters
	if ( isset( $input['link_color'] ) && preg_match( '/^#?([a-f0-9]{3}){1,2}$/i', $input['link_color'] ) )
		$output['link_color'] = '#' . strtolower( ltrim( $input['link_color'], '#' ) );

	// Theme layout must be in our array of theme layout options
	if ( isset( $input['theme_layout'] ) && array_key_exists( $input['theme_layout'], hivequeen_layouts() ) )
		$output['theme_layout'] = $input['theme_layout'];

	/**
	 * Filter the Twenty Eleven sanitized form input array.
	 *
	 * @since HiveQueen Theme 0.1
	 *
	 * @param array $output   An array of sanitized form output.
	 * @param array $input    An array of un-sanitized form input.
	 * @param array $defaults An array of default theme options.
	 */
	return apply_filters( 'hivequeen_theme_options_validate', $output, $input, $defaults );
}

/**
 * Enqueue the styles for the current color scheme.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_enqueue_color_scheme() {
	$options = hivequeen_get_theme_options();
	$color_scheme = $options['color_scheme'];

	if ( 'dark' == $color_scheme )
		hq_enqueue_style( 'dark', get_template_directory_uri() . '/colors/dark.css', array(), null );

	/**
	 * Fires after the styles for the Twenty Eleven color scheme are enqueued.
	 *
	 * @since HiveQueen Theme 0.1
	 *
	 * @param string $color_scheme The color scheme.
	 */
	do_action( 'hivequeen_enqueue_color_scheme', $color_scheme );
}
add_action( 'hq_enqueue_scripts', 'hivequeen_enqueue_color_scheme' );

/**
 * Add a style block to the theme for the current link color.
 *
 * This function is attached to the hq_head action hook.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_print_link_color_style() {
	$options = hivequeen_get_theme_options();
	$link_color = $options['link_color'];

	$default_options = hivequeen_get_default_theme_options();

	// Don't do anything if the current link color is the default.
	if ( $default_options['link_color'] == $link_color )
		return;
?>
	<style>
		/* Link color */
		a,
		#site-title a:focus,
		#site-title a:hover,
		#site-title a:active,
		.entry-title a:hover,
		.entry-title a:focus,
		.entry-title a:active,
		.widget_hivequeen_ephemera .comments-link a:hover,
		section.recent-posts .other-recent-posts a[rel="bookmark"]:hover,
		section.recent-posts .other-recent-posts .comments-link a:hover,
		.format-image footer.entry-meta a:hover,
		#site-generator a:hover {
			color: <?php echo $link_color; ?>;
		}
		section.recent-posts .other-recent-posts .comments-link a:hover {
			border-color: <?php echo $link_color; ?>;
		}
		article.feature-image.small .entry-summary p a:hover,
		.entry-header .comments-link a:hover,
		.entry-header .comments-link a:focus,
		.entry-header .comments-link a:active,
		.feature-slider a.active {
			background-color: <?php echo $link_color; ?>;
		}
	</style>
<?php
}
add_action( 'hq_head', 'hivequeen_print_link_color_style' );

/**
 * Add Twenty Eleven layout classes to the array of body classes.
 *
 * @since HiveQueen Theme 0.1
 *
 * @param array $existing_classes An array of existing body classes.
 */
function hivequeen_layout_classes( $existing_classes ) {
	$options = hivequeen_get_theme_options();
	$current_layout = $options['theme_layout'];

	if ( in_array( $current_layout, array( 'content-sidebar', 'sidebar-content' ) ) )
		$classes = array( 'two-column' );
	else
		$classes = array( 'one-column' );

	if ( 'content-sidebar' == $current_layout )
		$classes[] = 'right-sidebar';
	elseif ( 'sidebar-content' == $current_layout )
		$classes[] = 'left-sidebar';
	else
		$classes[] = $current_layout;

	/**
	 * Filter the Twenty Eleven layout body classes.
	 *
	 * @since HiveQueen Theme 0.1
	 *
	 * @param array  $classes        An array of body classes.
	 * @param string $current_layout The current theme layout.
	 */
	$classes = apply_filters( 'hivequeen_layout_classes', $classes, $current_layout );

	return array_merge( $existing_classes, $classes );
}
add_filter( 'body_class', 'hivequeen_layout_classes' );

/**
 * Implements Twenty Eleven theme options into Customizer
 *
 * @since HiveQueen Theme 0.1
 *
 * @param object $hq_customize Customizer object.
 */
function hivequeen_customize_register( $hq_customize ) {
	$hq_customize->get_setting( 'blogname' )->transport = 'postMessage';
	$hq_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
	$hq_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	$options  = hivequeen_get_theme_options();
	$defaults = hivequeen_get_default_theme_options();

	$hq_customize->add_setting( 'hivequeen_theme_options[color_scheme]', array(
		'default'    => $defaults['color_scheme'],
		'type'       => 'option',
		'capability' => 'edit_theme_options',
	) );

	$schemes = hivequeen_color_schemes();
	$choices = array();
	foreach ( $schemes as $scheme ) {
		$choices[ $scheme['value'] ] = $scheme['label'];
	}

	$hq_customize->add_control( 'hivequeen_color_scheme', array(
		'label'    => __( 'Color Scheme', 'hivequeen' ),
		'section'  => 'colors',
		'settings' => 'hivequeen_theme_options[color_scheme]',
		'type'     => 'radio',
		'choices'  => $choices,
		'priority' => 5,
	) );

	// Link Color (added to Color Scheme section in Customizer)
	$hq_customize->add_setting( 'hivequeen_theme_options[link_color]', array(
		'default'           => hivequeen_get_default_link_color( $options['color_scheme'] ),
		'type'              => 'option',
		'sanitize_callback' => 'sanitize_hex_color',
		'capability'        => 'edit_theme_options',
	) );

	$hq_customize->add_control( new HQ_Customize_Color_Control( $hq_customize, 'link_color', array(
		'label'    => __( 'Link Color', 'hivequeen' ),
		'section'  => 'colors',
		'settings' => 'hivequeen_theme_options[link_color]',
	) ) );

	// Default Layout
	$hq_customize->add_section( 'hivequeen_layout', array(
		'title'    => __( 'Layout', 'hivequeen' ),
		'priority' => 50,
	) );

	$hq_customize->add_setting( 'hivequeen_theme_options[theme_layout]', array(
		'type'              => 'option',
		'default'           => $defaults['theme_layout'],
		'sanitize_callback' => 'sanitize_key',
	) );

	$layouts = hivequeen_layouts();
	$choices = array();
	foreach ( $layouts as $layout ) {
		$choices[ $layout['value'] ] = $layout['label'];
	}

	$hq_customize->add_control( 'hivequeen_theme_options[theme_layout]', array(
		'section'    => 'hivequeen_layout',
		'type'       => 'radio',
		'choices'    => $choices,
	) );
}
add_action( 'customize_register', 'hivequeen_customize_register' );

/**
 * Bind JS handlers to make Customizer preview reload changes asynchronously.
 *
 * Used with blogname and blogdescription.
 *
 * @since HiveQueen Theme 0.1
 */
function hivequeen_customize_preview_js() {
	hq_enqueue_script( 'hivequeen-customizer', get_template_directory_uri() . '/inc/theme-customizer.js', array( 'customize-preview' ), '20150401', true );
}
add_action( 'customize_preview_init', 'hivequeen_customize_preview_js' );
