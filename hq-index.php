<?php
/**
 * Front to the HiveQueen application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells HiveQueen to load the theme.
 *
 * @package HiveQueen
 */

/**
 * Tells HiveQueen to load the HiveQueen theme and output it.
 *
 * @var bool
 */
define('HQ_USE_THEMES', true);

/** Loads the HiveQueen Environment and Template */
require( dirname( __FILE__ ) . '/hq-site-header.php' );
