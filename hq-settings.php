<?php
/**
 * Used to set up and fix common variables and include
 * the HiveQueen procedural and class library.
 *
 * Allows for some configuration in hq-config.php 
 *
 * @internal This file must be parsable by PHP4.
 *
 * @package HiveQueen
 */

/**
 * Stores the location of the HiveQueen directory of functions, classes, and core content.
 *
 * @since 0.0.1
 */
define( 'HQINC', 'hq-includes' );

// Include files required for initialization.
require( ABSPATH . HQINC . '/load.php' );
require( ABSPATH . HQINC . '/default-constants.php' );

/*
 * These can't be directly globalized in version.php. When updating,
 * we're including version.php from another install and don't want
 * these values to be overridden if already set.
 */
global $hq_version, $hq_db_version, $tinymce_version, $required_php_version, $required_mysql_version;
require( ABSPATH . HQINC . '/version.php' );

// Set initial default constants including HQ_MEMORY_LIMIT, HQ_MAX_MEMORY_LIMIT, HQ_DEBUG, SCRIPT_DEBUG, HQ_CONTENT_DIR and HQ_CACHE.
hq_initial_constants();

// Check for the required PHP version and for the MySQL extension or a database drop-in.
hq_check_php_mysql_versions();

// Disable magic quotes at runtime. Magic quotes are added using hqdb later in hq-settings.php.
@ini_set( 'magic_quotes_runtime', 0 );
@ini_set( 'magic_quotes_sybase',  0 );
// HiveQueen calculates offsets from UTC.
date_default_timezone_set( 'UTC' );

// Turn register_globals off.
hq_unregister_GLOBALS();

// Standardize $_SERVER variables across setups.
hq_fix_server_vars();

// Check if we have received a request due to missing favicon.ico
hq_favicon_request();

// Check if we're in maintenance mode.
hq_maintenance();

// Start loading timer.
timer_start();

// Check if we're in HQ_DEBUG mode.
hq_debug_mode();

// For an advanced caching plugin to use. Uses a static drop-in because you would only want one.
/* TODO: disable
if ( HQ_CACHE )
        HQ_DEBUG ? include( HQ_CONTENT_DIR . '/advanced-cache.php' ) : @include( HQ_CONTENT_DIR . '/advanced-cache.php' );
*/

// Define HQ_LANG_DIR if not set.
hq_set_lang_dir();

// Load early HiveQueen files.
// TODO: by the way
//require( ABSPATH . HQINC . '/compat.php' );
require( ABSPATH . HQINC . '/functions.php' );
require( ABSPATH . HQINC . '/class-hq.php' );
require( ABSPATH . HQINC . '/class-hq-error.php' );
require( ABSPATH . HQINC . '/plugin.php' );
require( ABSPATH . HQINC . '/pomo/mo.php' );

// Include the hqdb class and, if present, a db.php database drop-in.
require_hq_db();

// Set the database table prefix and the format specifiers for database table columns.
$GLOBALS['table_prefix'] = $table_prefix;
hq_set_hqdb_vars();

// Start the HiveQueen object cache, or an external object cache if the drop-in is present.
// TODO: disable ????
//hq_start_object_cache();

// Attach the default filters.
/* TODO: disable 
require( ABSPATH . HQINC . '/default-filters.php' );
*/

// Initialize multisite if enabled.
/* TODO: disable multisite seems have no sense in HiveQueen
if ( is_multisite() ) {
        require( ABSPATH . HQINC . '/ms-blogs.php' );
        require( ABSPATH . HQINC . '/ms-settings.php' );
} elseif ( ! defined( 'MULTISITE' ) ) {
        define( 'MULTISITE', false );
}
*/

register_shutdown_function( 'shutdown_action_hook' );

// Stop most of HiveQueen from being loaded if we just want the basics.
if ( SHORTINIT )
        return false;

// Load the L10n library.
require_once( ABSPATH . HQINC . '/l10n.php' );

// Run the installer if WordPress is not installed.
hq_not_installed();

// Load most of HiveQueen.
// TODO: disable all
//require( ABSPATH . HQINC . '/class-hq-walker.php' );
//require( ABSPATH . HQINC . '/class-hq-ajax-response.php' );
require( ABSPATH . HQINC . '/formatting.php' );
require( ABSPATH . HQINC . '/capabilities.php' );
require( ABSPATH . HQINC . '/query.php' );
//require( ABSPATH . HQINC . '/date.php' );
//require( ABSPATH . HQINC . '/theme.php' );
//require( ABSPATH . HQINC . '/class-hq-theme.php' );
require( ABSPATH . HQINC . '/template.php' );
require( ABSPATH . HQINC . '/user.php' );
//require( ABSPATH . HQINC . '/session.php' );
//require( ABSPATH . HQINC . '/meta.php' );
require( ABSPATH . HQINC . '/general-template.php' );
//require( ABSPATH . HQINC . '/link-template.php' );
//require( ABSPATH . HQINC . '/author-template.php' );
//require( ABSPATH . HQINC . '/post.php' );
//require( ABSPATH . HQINC . '/post-template.php' );
//require( ABSPATH . HQINC . '/revision.php' );
//require( ABSPATH . HQINC . '/post-formats.php' );
//require( ABSPATH . HQINC . '/post-thumbnail-template.php' );
//require( ABSPATH . HQINC . '/category.php' );
//require( ABSPATH . HQINC . '/category-template.php' );
//require( ABSPATH . HQINC . '/comment.php' );
//require( ABSPATH . HQINC . '/comment-template.php' );
//require( ABSPATH . HQINC . '/rewrite.php' );
//require( ABSPATH . HQINC . '/feed.php' );
//require( ABSPATH . HQINC . '/bookmark.php' );
//require( ABSPATH . HQINC . '/bookmark-template.php' );
require( ABSPATH . HQINC . '/kses.php' );
//require( ABSPATH . HQINC . '/cron.php' );
//require( ABSPATH . HQINC . '/deprecated.php' );
require( ABSPATH . HQINC . '/script-loader.php' );
//require( ABSPATH . HQINC . '/taxonomy.php' );
//require( ABSPATH . HQINC . '/update.php' );
//require( ABSPATH . HQINC . '/canonical.php' );
//require( ABSPATH . HQINC . '/shortcodes.php' );
//require( ABSPATH . HQINC . '/class-wp-embed.php' );
//require( ABSPATH . HQINC . '/media.php' );
//require( ABSPATH . HQINC . '/http.php' );
//require( ABSPATH . HQINC . '/class-http.php' );
//require( ABSPATH . HQINC . '/widgets.php' );
//require( ABSPATH . HQINC . '/nav-menu.php' );
//require( ABSPATH . HQINC . '/nav-menu-template.php' );
//require( ABSPATH . HQINC . '/admin-bar.php' );


// Load multisite-specific files.
/* TODO: disable multisite
if ( is_multisite() ) {
        require( ABSPATH . HQINC . '/ms-functions.php' );
        require( ABSPATH . HQINC . '/ms-default-filters.php' );
        require( ABSPATH . HQINC . '/ms-deprecated.php' );
}
*/

// Define constants that rely on the API to obtain the default value.
// Define must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
/* TODO: not plugins 
wp_plugin_directory_constants();

$GLOBALS['hq_plugin_paths'] = array();
*/

// Load must-use plugins.
/* TODO: not plugins 
foreach ( hq_get_mu_plugins() as $mu_plugin ) {
        include_once( $mu_plugin );
}
unset( $mu_plugin );
*/

// Load network activated plugins.
/* TODO: not plugins
if ( is_multisite() ) {
        foreach( hq_get_active_network_plugins() as $network_plugin ) {
                hq_register_plugin_realpath( $network_plugin );
                include_once( $network_plugin );
        }
        unset( $network_plugin );
}
*/

/**
 * Fires once all must-use and network-activated plugins have loaded.
 *
 * @since 0.0.1
 */
/* TODO: not pluings
do_action( 'muplugins_loaded' );
if ( is_multisite() )
        ms_cookie_constants(  );
*/

// Define constants after multisite is loaded.
/* TODO: not multisite
hq_cookie_constants();
*/

// Define and enforce our SSL constants
// TODO: no ssl
//hq_ssl_constants();

// Create common globals.
require( ABSPATH . HQINC . '/vars.php' );

// Make taxonomies and posts available to plugins and themes.
// @plugin authors: warning: these get registered again on the init hook.
/* TODO: taxonomies not used
create_initial_taxonomies();
create_initial_post_types();
*/

// Register the default theme directory root
// TODO: redefine theme 
//register_theme_directory( get_theme_root() );

// Load active plugins.
/* TODO: not pluings 
foreach ( hq_get_active_and_valid_plugins() as $plugin ) {
        hq_register_plugin_realpath( $plugin );
        include_once( $plugin );
}
unset( $plugin );
*/

// Load pluggable functions.
require( ABSPATH . HQINC . '/pluggable.php' );
// TODO: ?????
//require( ABSPATH . HQINC . '/pluggable-deprecated.php' );

// Set internal encoding.
// TODO: ???
hq_set_internal_encoding();

// Run hq_cache_postload() if object cache is enabled and the function exists.
/* TODO: cache not used
if ( HQ_CACHE && function_exists( 'hq_cache_postload' ) )
        hq_cache_postload();
*/

/**
 * Fires once activated plugins have loaded.
 *
 * Pluggable functions are also available at this point in the loading order.
 *
 * @since 0.0.1
 */
/* TODO: not used plugins
do_action( 'plugins_loaded' );
*/

// Define constants which affect functionality if not already defined.
// TODO: ????
hq_functionality_constants();

// Add magic quotes and set up $_REQUEST ( $_GET + $_POST )
// TODO: ???
hq_magic_quotes();

/**
 * Fires when comment cookies are sanitized.
 *
 * @since 0.0.1
 */
/* TODO: not cookies 
do_action( 'sanitize_comment_cookies' );
*/

/**
 * HiveQueen Query object
 * @global object $hq_the_query
 * @since 0.0.1
 */
// TODOD: ???
$GLOBALS['hq_the_query'] = new HQ_Query();

/**
 * Holds the reference to @see $hq_the_query
 * Use this global for HiveQueen queries
 * @global object $hq_query
 * @since 0.0.1
 */
$GLOBALS['hq_query'] = $GLOBALS['hq_the_query'];

/**
 * Holds the HiveQueen Rewrite object for creating pretty URLs
 * @global object $hq_rewrite
 * @since 1.5.0
 */
// TODO: ???
//$GLOBALS['hq_rewrite'] = new HQ_Rewrite();

/**
 * HiveQueen Object
 * @global object $hq
 * @since 0.0.1
 */
$GLOBALS['hq'] = new HQ();

/**
 * HiveQueen Widget Factory Object
 * @global object $hq_widget_factory
 * @since 0.0.1
 */
/* TODO: ??? disable
$GLOBALS['hq_widget_factory'] = new HQ_Widget_Factory();
*/

/**
 * HiveQueen User Roles
 * @global object $hq_roles
 * @since 0.0.1
 */
// TODO: ???
$GLOBALS['hq_roles'] = new HQ_Roles();

/**
 * Fires before the theme is loaded.
 *
 * @since 0.0.1
 */
//TODO: ???
//do_action( 'setup_theme' );

// Define the template related constants.
//hq_templating_constants(  );

// Load the default text localization domain.
// TODO: ???
//load_default_textdomain();

$locale = get_locale();
$locale_file = HQ_LANG_DIR . "/$locale.php";
//TODO: Fix 
//if ( ( 0 === validate_file( $locale ) ) && is_readable( $locale_file ) )
//        require( $locale_file );
//unset( $locale_file );

// Pull in locale data after loading text domain.
require_once( ABSPATH . HQINC . '/locale.php' );

/**
 * HiveQueen Locale object for loading locale domain date and various strings.
 * @global object $hq_locale
 * @since 0.0.1
 */
$GLOBALS['hq_locale'] = new HQ_Locale();

// Load the functions for the active theme, for both parent and child theme if applicable.
if ( ! defined( 'HQ_INSTALLING' ) || 'hq-activate.php' === $pagenow ) {
        if ( TEMPLATEPATH !== STYLESHEETPATH && file_exists( STYLESHEETPATH . '/functions.php' ) )
                include( STYLESHEETPATH . '/functions.php' );
        if ( file_exists( TEMPLATEPATH . '/functions.php' ) )
                include( TEMPLATEPATH . '/functions.php' );
}

/**
 * Fires after the theme is loaded.
 *
 * @since 0.0.1
 */
do_action( 'after_setup_theme' );

// Set up current user.
$GLOBALS['hq']->init();

/**
 * Fires after HiveQueen has finished loading but before any headers are sent.
 *
 * Most of HQ is loaded at this stage, and the user is authenticated. HQ continues
 * to load on the init hook that follows (e.g. widgets), and many plugins instantiate
 * themselves on it for all sorts of reasons (e.g. they need a user, a taxonomy, etc.).
 *
 * If you wish to plug an action once HQ is loaded, use the hq_loaded hook below.
 *
 * @since 0.0.1
 */
do_action( 'init' );


// Check site status
/* TODO: not multisite
if ( is_multisite() ) {
        if ( true !== ( $file = ms_site_check() ) ) {
                require( $file );
                die();
        }
        unset($file);
}
*/

/**
 * This hook is fired once HQ, all plugins, and the theme are fully loaded and instantiated.
 *
 * AJAX requests should use hq-admin/admin-ajax.php. admin-ajax.php can handle requests for
 * users not logged in.
 *
 * @since 0.0.1
 */
do_action( 'hq_loaded' );
