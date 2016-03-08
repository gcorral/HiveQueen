<?php
/**
 * Defines constants and global variables that can be overridden, generally in hq-config.php.
 *
 * @package HiveQueen
 */

/**
 * Defines initial HiveQueen constants
 *
 * @see hq_debug_mode()
 *
 * @since 0.0.1
 */
function hq_initial_constants() {
        global $blog_id;

        // set memory limits
        if ( !defined('HQ_MEMORY_LIMIT') ) {
                define('HQ_MEMORY_LIMIT', '40M');
        }

        if ( ! defined( 'HQ_MAX_MEMORY_LIMIT' ) ) {
                define( 'HQ_MAX_MEMORY_LIMIT', '256M' );
        }

        /**
         * The $blog_id global, which you can change in the config allows you to create a simple
         * multiple blog installation using just one HiveQueen and changing $blog_id around.
         *
         * @global int $blog_id
         * @since 0.0.1
         */
        if ( ! isset($blog_id) )
                $blog_id = 1;

        // set memory limits.
        if ( function_exists( 'memory_get_usage' ) ) {
                $current_limit = @ini_get( 'memory_limit' );
                $current_limit_int = intval( $current_limit );
                if ( false !== strpos( $current_limit, 'G' ) )
                        $current_limit_int *= 1024;
                $hq_limit_int = intval( HQ_MEMORY_LIMIT );
                if ( false !== strpos( HQ_MEMORY_LIMIT, 'G' ) )
                        $hq_limit_int *= 1024;

                if ( -1 != $current_limit && ( -1 == HQ_MEMORY_LIMIT || $current_limit_int < $hq_limit_int ) )
                        @ini_set( 'memory_limit', HQ_MEMORY_LIMIT );
        }
        if ( !defined('HQ_CONTENT_DIR') )
                define( 'HQ_CONTENT_DIR', ABSPATH . 'hq-content' ); // no trailing slash, full paths only - HQ_CONTENT_URL is defined further down

        // Add define('HQ_DEBUG', true); to hq-config.php to enable display of notices during development.
        if ( !defined('HQ_DEBUG') )
                define( 'HQ_DEBUG', false );

        // Add define('HQ_DEBUG_DISPLAY', null); to hq-config.php use the globally configured setting for
        // display_errors and not force errors to be displayed. Use false to force display_errors off.
        if ( !defined('HQ_DEBUG_DISPLAY') )
                define( 'HQ_DEBUG_DISPLAY', true );

        // Add define('HQ_DEBUG_LOG', true); to enable error logging to hq-content/debug.log.
        if ( !defined('HQ_DEBUG_LOG') )
                define('HQ_DEBUG_LOG', false);

        if ( !defined('HQ_CACHE') )
                define('HQ_CACHE', false);

        // Add define('SCRIPT_DEBUG', true); to hq-config.php to enable loading of non-minified,
        // non-concatenated scripts and stylesheets.
        if ( ! defined( 'SCRIPT_DEBUG' ) ) {
                if ( ! empty( $GLOBALS['hq_version'] ) ) {
                        $develop_src = false !== strpos( $GLOBALS['hq_version'], '-src' );
                } else {
                        $develop_src = false;
                }

                define( 'SCRIPT_DEBUG', $develop_src );
        }

        /**
         * Private
         */
        if ( !defined('MEDIA_TRASH') )
                define('MEDIA_TRASH', false);

        if ( !defined('SHORTINIT') )
                define('SHORTINIT', false);

        // Constants for features added to HQ that should short-circuit their plugin implementations
        define( 'HQ_FEATURE_BETTER_PASSWORDS', true );

        // Constants for expressing human-readable intervals
        // in their respective number of seconds.
        define( 'MINUTE_IN_SECONDS', 60 );
        define( 'HOUR_IN_SECONDS',   60 * MINUTE_IN_SECONDS );
        define( 'DAY_IN_SECONDS',    24 * HOUR_IN_SECONDS   );
        define( 'WEEK_IN_SECONDS',    7 * DAY_IN_SECONDS    );
        define( 'YEAR_IN_SECONDS',  365 * DAY_IN_SECONDS    );
}


/**
 * Defines functionality related HiveQueen constants
 *
 * @since 0.0.1
 */
function hq_functionality_constants() {
        /**
         * @since 0.0.1
         */
        if ( !defined( 'AUTOSAVE_INTERVAL' ) )
                define( 'AUTOSAVE_INTERVAL', 60 );

        /**
         * @since 0.0.1
         */
        if ( !defined( 'EMPTY_TRASH_DAYS' ) )
                define( 'EMPTY_TRASH_DAYS', 30 );

        if ( !defined('HQ_POST_REVISIONS') )
                define('HQ_POST_REVISIONS', true);

        /**
         * @since 0.0.1
         */
        if ( !defined( 'HQ_CRON_LOCK_TIMEOUT' ) )
                define('HQ_CRON_LOCK_TIMEOUT', 60);  // In seconds
}

/**
 * Defines templating related HiveQueen constants
 *
 * @since 0.0.1
 */
function hq_templating_constants() {
        /**
         * Filesystem path to the current active template directory
         * @since 0.0.1
         */
        define('TEMPLATEPATH', get_template_directory());

        /**
         * Filesystem path to the current active template stylesheet directory
         * @since 0.0.1
         */
        define('STYLESHEETPATH', get_stylesheet_directory());

        /**
         * Slug of the default theme for this install.
         * Used as the default theme when installing new sites.
         * Will be used as the fallback if the current theme doesn't exist.
         * @since 0.0.1
         */
        if ( !defined('HQ_DEFAULT_THEME') )
                define( 'HQ_DEFAULT_THEME', 'hivequeen' );

}


