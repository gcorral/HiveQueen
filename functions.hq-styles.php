<?php
/**
 * BackPress Styles Procedural API
 *
 * @since 0.0.1
 *
 * @package HiveQueen
 * @subpackage BackPress
 */

/**
 * Initialize $hq_styles if it has not been set.
 *
 * @global HQ_Styles $hq_styles
 *
 * @since 0.0.1
 *
 * @return HQ_Styles HQ_Styles instance.
 */
function hq_styles() {
	global $hq_styles;
	if ( ! ( $hq_styles instanceof HQ_Styles ) ) {
		$hq_styles = new HQ_Styles();
	}
	return $hq_styles;
}

/**
 * Display styles that are in the $handles queue.
 *
 * Passing an empty array to $handles prints the queue,
 * passing an array with one string prints that style,
 * and passing an array of strings prints those styles.
 *
 * @global HQ_Styles $hq_styles The HQ_Styles object for printing styles.
 *
 * @since 0.0.1
 *
 * @param string|bool|array $handles Styles to be printed. Default 'false'.
 * @return array On success, a processed array of HQ_Dependencies items; otherwise, an empty array.
 */
function hq_print_styles( $handles = false ) {
	if ( '' === $handles ) { // for hq_head
		$handles = false;
	}
	/**
	 * Fires before styles in the $handles queue are printed.
	 *
	 * @since 0.0.1
	 */
	if ( ! $handles ) {
		do_action( 'hq_print_styles' );
	}

	_hq_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	global $hq_styles;
	if ( ! ( $hq_styles instanceof HQ_Styles ) ) {
		if ( ! $handles ) {
			return array(); // No need to instantiate if nothing is there.
		}
	}

	return hq_styles()->do_items( $handles );
}

/**
 * Add extra CSS styles to a registered stylesheet.
 *
 * Styles will only be added if the stylesheet in already in the queue.
 * Accepts a string $data containing the CSS. If two or more CSS code blocks
 * are added to the same stylesheet $handle, they will be printed in the order
 * they were added, i.e. the latter added styles can redeclare the previous.
 *
 * @see HQ_Styles::add_inline_style()
 *
 * @since 0.0.1
 *
 * @param string $handle Name of the stylesheet to add the extra styles to. Must be lowercase.
 * @param string $data   String containing the CSS styles to be added.
 * @return bool True on success, false on failure.
 */
function hq_add_inline_style( $handle, $data ) {
	_hq_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	if ( false !== stripos( $data, '</style>' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Do not pass style tags to hq_add_inline_style().' ), '3.7' );
		$data = trim( preg_replace( '#<style[^>]*>(.*)</style>#is', '$1', $data ) );
	}

	return hq_styles()->add_inline_style( $handle, $data );
}

/**
 * Register a CSS stylesheet.
 *
 * @see HQ_Dependencies::add()
 * @link http://www.w3.org/TR/CSS2/media.html#media-types List of CSS media types.
 *
 * @since 0.0.1
 * @since 0.0.1
 *
 * @param string      $handle Name of the stylesheet.
 * @param string|bool $src    Path to the stylesheet from the HiveQueen root directory. Example: '/css/mystyle.css'.
 * @param array       $deps   An array of registered style handles this stylesheet depends on. Default empty array.
 * @param string|bool $ver    String specifying the stylesheet version number. Used to ensure that the correct version
 *                            is sent to the client regardless of caching. Default 'false'. Accepts 'false', 'null', or 'string'.
 * @param string      $media  Optional. The media for which this stylesheet has been defined.
 *                            Default 'all'. Accepts 'all', 'aural', 'braille', 'handheld', 'projection', 'print',
 *                            'screen', 'tty', or 'tv'.
 * @return bool Whether the style has been registered. True on success, false on failure.
 */
function hq_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	_hq_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	return hq_styles()->add( $handle, $src, $deps, $ver, $media );
}

/**
 * Remove a registered stylesheet.
 *
 * @see HQ_Dependencies::remove()
 *
 * @since 0.0.1
 *
 * @param string $handle Name of the stylesheet to be removed.
 */
function hq_deregister_style( $handle ) {
	_hq_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	hq_styles()->remove( $handle );
}

/**
 * Enqueue a CSS stylesheet.
 *
 * Registers the style if source provided (does NOT overwrite) and enqueues.
 *
 * @see HQ_Dependencies::add(), HQ_Dependencies::enqueue()
 * @link http://www.w3.org/TR/CSS2/media.html#media-types List of CSS media types.
 *
 * @since 0.0.1
 *
 * @param string      $handle Name of the stylesheet.
 * @param string|bool $src    Path to the stylesheet from the root directory of HiveQueen. Example: '/css/mystyle.css'.
 * @param array       $deps   An array of registered style handles this stylesheet depends on. Default empty array.
 * @param string|bool $ver    String specifying the stylesheet version number, if it has one. This parameter is used
 *                            to ensure that the correct version is sent to the client regardless of caching, and so
 *                            should be included if a version number is available and makes sense for the stylesheet.
 * @param string      $media  Optional. The media for which this stylesheet has been defined.
 *                            Default 'all'. Accepts 'all', 'aural', 'braille', 'handheld', 'projection', 'print',
 *                            'screen', 'tty', or 'tv'.
 */
function hq_enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' ) {
	_hq_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	$hq_styles = hq_styles();

	if ( $src ) {
		$_handle = explode('?', $handle);
		$hq_styles->add( $_handle[0], $src, $deps, $ver, $media );
	}
	$hq_styles->enqueue( $handle );
}

/**
 * Remove a previously enqueued CSS stylesheet.
 *
 * @see HQ_Dependencies::dequeue()
 *
 * @since 0.0.1
 *
 * @param string $handle Name of the stylesheet to be removed.
 */
function hq_dequeue_style( $handle ) {
	_hq_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	hq_styles()->dequeue( $handle );
}

/**
 * Check whether a CSS stylesheet has been added to the queue.
 *
 * @since 0.0.1
 *
 * @param string $handle Name of the stylesheet.
 * @param string $list   Optional. Status of the stylesheet to check. Default 'enqueued'.
 *                       Accepts 'enqueued', 'registered', 'queue', 'to_do', and 'done'.
 * @return bool Whether style is queued.
 */
function hq_style_is( $handle, $list = 'enqueued' ) {
	_hq_scripts_maybe_doing_it_wrong( __FUNCTION__ );

	return (bool) hq_styles()->query( $handle, $list );
}

/**
 * Add metadata to a CSS stylesheet.
 *
 * Works only if the stylesheet has already been added.
 *
 * Possible values for $key and $value:
 * 'conditional' string      Comments for IE 6, lte IE 7 etc.
 * 'rtl'         bool|string To declare an RTL stylesheet.
 * 'suffix'      string      Optional suffix, used in combination with RTL.
 * 'alt'         bool        For rel="alternate stylesheet".
 * 'title'       string      For preferred/alternate stylesheets.
 *
 * @see HQ_Dependency::add_data()
 *
 * @since 0.0.1
 *
 * @param string $handle Name of the stylesheet.
 * @param string $key    Name of data point for which we're storing a value.
 *                       Accepts 'conditional', 'rtl' and 'suffix', 'alt' and 'title'.
 * @param mixed  $value  String containing the CSS data to be added.
 * @return bool True on success, false on failure.
 */
function hq_style_add_data( $handle, $key, $value ) {
	return hq_styles()->add_data( $handle, $key, $value );
}
