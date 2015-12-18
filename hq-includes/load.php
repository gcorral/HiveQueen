<?php
/**
 * These functions are needed to load HiveQueen.
 *
 * @internal This file must be parsable by PHP4.
 *
 * @package HiveQueen
 */


/**
 * Turn register globals off.
 *
 * @since 0.0.1
 * @access private
 */
function hq_unregister_GLOBALS() {
        if ( !ini_get( 'register_globals' ) )
                return;

        if ( isset( $_REQUEST['GLOBALS'] ) )
                die( 'GLOBALS overwrite attempt detected' );

        // Variables that shouldn't be unset
        $no_unset = array( 'GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES', 'table_prefix' );

        $input = array_merge( $_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset( $_SESSION ) && is_array( $_SESSION ) ? $_SESSION : array() );
        foreach ( $input as $k => $v )
                if ( !in_array( $k, $no_unset ) && isset( $GLOBALS[$k] ) ) {
                        unset( $GLOBALS[$k] );
                }
}

/**
 * Fix `$_SERVER` variables for various setups.
 *
 * @since 0.0.1
 * @access private
 *
 * @global string $PHP_SELF The filename of the currently executing script,
 *                          relative to the document root.
 */
function hq_fix_server_vars() {
        global $PHP_SELF;

        $default_server_values = array(
                'SERVER_SOFTWARE' => '',
                'REQUEST_URI' => '',
        );

        $_SERVER = array_merge( $default_server_values, $_SERVER );

        // Fix for IIS when running with PHP ISAPI
        if ( empty( $_SERVER['REQUEST_URI'] ) || ( PHP_SAPI != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ) {

                // IIS Mod-Rewrite
                if ( isset( $_SERVER['HTTP_X_ORIGINAL_URL'] ) ) {
                        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
                }
                // IIS Isapi_Rewrite
                elseif ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) {
                        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
                } else {
                        // Use ORIG_PATH_INFO if there is no PATH_INFO
                        if ( !isset( $_SERVER['PATH_INFO'] ) && isset( $_SERVER['ORIG_PATH_INFO'] ) )
                                $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];

                        // Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
                        if ( isset( $_SERVER['PATH_INFO'] ) ) {
                                if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] )
                                        $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
                                else
                                        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
                        }


                        // Append the query string if it exists and isn't null
                        if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
                                $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
                        }
                }
        }

        // Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
        if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && ( strpos( $_SERVER['SCRIPT_FILENAME'], 'php.cgi' ) == strlen( $_SERVER['SCRIPT_FILENAME'] ) - 7 ) )
                $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];

        // Fix for Dreamhost and other PHP as CGI hosts
        if ( strpos( $_SERVER['SCRIPT_NAME'], 'php.cgi' ) !== false )
                unset( $_SERVER['PATH_INFO'] );

        // Fix empty PHP_SELF
        $PHP_SELF = $_SERVER['PHP_SELF'];
        if ( empty( $PHP_SELF ) )
                $_SERVER['PHP_SELF'] = $PHP_SELF = preg_replace( '/(\?.*)?$/', '', $_SERVER["REQUEST_URI"] );
}


/**
 * Check for the required PHP version, and the MySQL extension or
 * a database drop-in.
 *
 * Dies if requirements are not met.
 *
 * @since 0.0.1
 * @access private
 *
 * @global string $required_php_version The required PHP version string.
 * @global string $hq_version           The HiveQueen version string.
 */
function hq_check_php_mysql_versions() {
        global $required_php_version, $hq_version;
        $php_version = phpversion();

        if ( version_compare( $required_php_version, $php_version, '>' ) ) {
                hq_load_translations_early();
                header( 'Content-Type: text/html; charset=utf-8' );
                die( sprintf( __( 'Your server is running PHP version %1$s but HiveQueen %2$s requires at least %3$s.' ), $php_version, $hq_version, $required_php_version ) );
        }

        if ( ! extension_loaded( 'mysql' ) && ! extension_loaded( 'mysqli' ) && ! file_exists( HQ_CONTENT_DIR . '/db.php' ) ) {
                hq_load_translations_early();
                 header( 'Content-Type: text/html; charset=utf-8' );
                die( __( 'Your PHP installation appears to be missing the MySQL extension which is required by HiveQueen.' ) );
        }
}

/**
 * Don't load all of HiveQueen when handling a favicon.ico request.
 *
 * Instead, send the headers for a zero-length favicon and bail.
 *
 * @since 0.0.1
 */
function hq_favicon_request() {
        if ( '/favicon.ico' == $_SERVER['REQUEST_URI'] ) {
                header('Content-Type: image/vnd.microsoft.icon');
                header('Content-Length: 0');
                exit;
        }
}

/**
 * Die with a maintenance message when conditions are met.
 *
 * Checks for a file in the HiveQueen root directory named ".maintenance".
 * This file will contain the variable $upgrading, set to the time the file
 * was created. If the file was created less than 10 minutes ago, WordPress
 * enters maintenance mode and displays a message.
 *
 * The default message can be replaced by using a drop-in (maintenance.php in
 * the hq-content directory).
 *
 * @since 0.0.1
 * @access private
 *
 * @global int $upgrading the unix timestamp marking when upgrading HiveQueen began.
 */
function hq_maintenance() {
        if ( !file_exists( ABSPATH . '.maintenance' ) || defined( 'HQ_INSTALLING' ) )
                return;

        global $upgrading;

        include( ABSPATH . '.maintenance' );
        // If the $upgrading timestamp is older than 10 minutes, don't die.
        if ( ( time() - $upgrading ) >= 600 )
                return;

        if ( file_exists( HQ_CONTENT_DIR . '/maintenance.php' ) ) {
                require_once( HQ_CONTENT_DIR . '/maintenance.php' );
                die();
        }

        hq_load_translations_early();

        $protocol = $_SERVER["SERVER_PROTOCOL"];
        if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
                $protocol = 'HTTP/1.0';
        header( "$protocol 503 Service Unavailable", true, 503 );
        header( 'Content-Type: text/html; charset=utf-8' );
        header( 'Retry-After: 600' );
?>
        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml"<?php if ( is_rtl() ) echo ' dir="rtl"'; ?>>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title><?php _e( 'Maintenance' ); ?></title>

        </head>
        <body>
                <h1><?php _e( 'Briefly unavailable for scheduled maintenance. Check back in a minute.' ); ?></h1>
        </body>
        </html>
<?php
        die();
}


/**
 * Start the HiveQueen micro-timer.
 *
 * @since 0.0.1
 * @access private
 *
 * @global float $timestart Unix timestamp set at the beginning of the page load.
 * @see timer_stop()
 *
 * @return bool Always returns true.
 */
function timer_start() {
        global $timestart;
        $timestart = microtime( true );
        return true;
}

/**
 * Retrieve or display the time from the page start to when function is called.
 *
 * @since 0.0.1
 *
 * @global float   $timestart Seconds from when timer_start() is called.
 * @global float   $timeend   Seconds from when function is called.
 *
 * @param int|bool $display   Whether to echo or return the results. Accepts 0|false for return,
 *                            1|true for echo. Default 0|false.
 * @param int      $precision The number of digits from the right of the decimal to display.
 *                            Default 3.
 * @return string The "second.microsecond" finished time calculation. The number is formatted
 *                for human consumption, both localized and rounded.
 */
function timer_stop( $display = 0, $precision = 3 ) {
        global $timestart, $timeend;
        $timeend = microtime( true );
        $timetotal = $timeend - $timestart;
        $r = ( function_exists( 'number_format_i18n' ) ) ? number_format_i18n( $timetotal, $precision ) : number_format( $timetotal, $precision );
        if ( $display )
                echo $r;
        return $r;
}

/**
 * Set PHP error reporting based on HiveQueen debug settings.
 *
 * Uses three constants: `HQ_DEBUG`, `HQ_DEBUG_DISPLAY`, and `HQ_DEBUG_LOG`.
 * All three can be defined in hq-config.php, and by default are set to false.
 *
 * When `HQ_DEBUG` is true, all PHP notices are reported. HiveQueen will also
 * display internal notices: when a deprecated HiveQueen function, function
 * argument, or file is used. Deprecated code may be removed from a later
 * version.
 *
 * It is strongly recommended that plugin and theme developers use `HQ_DEBUG`
 * in their development environments.
 *
 * `HQ_DEBUG_DISPLAY` and `HQ_DEBUG_LOG` perform no function unless `HQ_DEBUG`
 * is true.
 *
 * When `HQ_DEBUG_DISPLAY` is true, HiveQueen will force errors to be displayed.
 * `HQ_DEBUG_DISPLAY` defaults to true. Defining it as null prevents WordPress
 * from changing the global configuration setting. Defining `HQ_DEBUG_DISPLAY`
 * as false will force errors to be hidden.
 *
 * When `HQ_DEBUG_LOG` is true, errors will be logged to debug.log in the content
 * directory.
 *
 * Errors are never displayed for XML-RPC requests.
 *
 * @since 0.0.1
 * @access private
 */
function hq_debug_mode() {
        if ( HQ_DEBUG ) {
                error_reporting( E_ALL );

                if ( HQ_DEBUG_DISPLAY )
                        ini_set( 'display_errors', 1 );
                elseif ( null !== HQ_DEBUG_DISPLAY )
                        ini_set( 'display_errors', 0 );

                if ( HQ_DEBUG_LOG ) {
                        ini_set( 'log_errors', 1 );
                        ini_set( 'error_log', HQ_CONTENT_DIR . '/debug.log' );
                }
        } else {
                error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
        }
        if ( defined( 'XMLRPC_REQUEST' ) )
                ini_set( 'display_errors', 0 );
}

/**
 * Set the location of the language directory.
 *
 * To set directory manually, define the `HQ_LANG_DIR` constant
 * in hq-config.php.
 *
 * If the language directory exists within `HQ_CONTENT_DIR`, it
 * is used. Otherwise the language directory is assumed to live
 * in `HQINC`.
 *
 * @since 0.0.1
 * @access private
 */
function hq_set_lang_dir() {
        if ( !defined( 'HQ_LANG_DIR' ) ) {
                if ( file_exists( HQ_CONTENT_DIR . '/languages' ) && @is_dir( HQ_CONTENT_DIR . '/languages' ) || !@is_dir(ABSPATH . HQINC . '/languages') ) {
                        /**
                         * Server path of the language directory.
                         *
                         * No leading slash, no trailing slash, full path, not relative to ABSPATH
                         *
                         * @since 0.0.1
                         */
                        define( 'HQ_LANG_DIR', HQ_CONTENT_DIR . '/languages' );
                        if ( !defined( 'LANGDIR' ) ) {
                                // Old static relative path maintained for limited backwards compatibility - won't work in some cases
                                define( 'LANGDIR', 'hq-content/languages' );
                        }
                } else {
                        /**
                         * Server path of the language directory.
                         *
                         * No leading slash, no trailing slash, full path, not relative to `ABSPATH`.
                         *
                         * @since 2.1.0
                         */
                        define( 'HQ_LANG_DIR', ABSPATH . HQINC . '/languages' );
                        if ( !defined( 'LANGDIR' ) ) {
                                // Old relative path maintained for backwards compatibility
                                define( 'LANGDIR', HQINC . '/languages' );
                        }
                }
        }
}

/**
 * Load the database class file and instantiate the `$hqdb` global.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb The HiveQueen database class.
 */
function require_hq_db() {
        global $hqdb;

        require_once( ABSPATH . HQINC . '/hq-db.php' );
        if ( file_exists( HQ_CONTENT_DIR . '/db.php' ) )
                require_once( HQ_CONTENT_DIR . '/db.php' );

        if ( isset( $hqdb ) )
                return;

        $hqdb = new hqdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
}

/**
 * Set the database table prefix and the format specifiers for database
 * table columns.
 *
 * Columns not listed here default to `%s`.
 *
 * @since 0.0.1
 * @access private
 *
 * @global hqdb   $hqdb         The HiveQueen database class.
 * @global string $table_prefix The database table prefix.
 */
function hq_set_hqdb_vars() {
        global $hqdb, $table_prefix;
        if ( !empty( $hqdb->error ) )
                dead_db();

        //TODO: Redefine db
        /*
        $hqdb->field_types = array( 'post_author' => '%d', 'post_parent' => '%d', 'menu_order' => '%d', 'term_id' => '%d', 'term_group' => '%d', 'term_taxonomy_id' => '%d',
                'parent' => '%d', 'count' => '%d','object_id' => '%d', 'term_order' => '%d', 'ID' => '%d', 'comment_ID' => '%d', 'comment_post_ID' => '%d', 'comment_parent' => '%d',
                'user_id' => '%d', 'link_id' => '%d', 'link_owner' => '%d', 'link_rating' => '%d', 'option_id' => '%d', 'blog_id' => '%d', 'meta_id' => '%d', 'post_id' => '%d',
                'user_status' => '%d', 'umeta_id' => '%d', 'comment_karma' => '%d', 'comment_count' => '%d',
                // multisite:
                'active' => '%d', 'cat_id' => '%d', 'deleted' => '%d', 'lang_id' => '%d', 'mature' => '%d', 'public' => '%d', 'site_id' => '%d', 'spam' => '%d',
        );
        */
        $hqdb->field_types = array( 'menu_order' => '%d', 'term_id' => '%d', 'term_group' => '%d',
                'parent' => '%d', 'count' => '%d','object_id' => '%d', 'term_order' => '%d', 'ID' => '%d',
                'user_id' => '%d', 'option_id' => '%d', 'user_status' => '%d',
        );

        $prefix = $hqdb->set_prefix( $table_prefix );

        if ( is_hq_error( $prefix ) ) {
                hq_load_translations_early();
                hq_die( __( '<strong>ERROR</strong>: <code>$table_prefix</code> in <code>hq-config.php</code> can only contain numbers, letters, and underscores.' ) );
        }
}

/**
 * Toggle `$_hq_using_ext_object_cache` on and off without directly
 * touching global.
 *
 * @since 0.0.1
 *
 * @global bool $_hq_using_ext_object_cache
 *
 * @param bool $using Whether external object cache is being used.
 * @return bool The current 'using' setting.
 */
function hq_using_ext_object_cache( $using = null ) {
        global $_hq_using_ext_object_cache;
        $current_using = $_hq_using_ext_object_cache;
        if ( null !== $using )
                $_hq_using_ext_object_cache = $using;
        return $current_using;
}

/**
 * Start the HiveQueen object cache.
 *
 * If an object-cache.php file exists in the hp-content directory,
 * it uses that drop-in as an external object cache.
 *
 * @since 0.0.1
 * @access private
 *
 * @global int $blog_id Blog ID.
 */
/* TODO: no cache 
function hq_start_object_cache() {
        global $blog_id;

        $first_init = false;
        if ( ! function_exists( 'hq_cache_init' ) ) {
                if ( file_exists( HQ_CONTENT_DIR . '/object-cache.php' ) ) {
                        require_once ( HQ_CONTENT_DIR . '/object-cache.php' );
                        if ( function_exists( 'hq_cache_init' ) )
                                hq_using_ext_object_cache( true );
                }

                $first_init = true;
        } elseif ( ! hq_using_ext_object_cache() && file_exists( HQ_CONTENT_DIR . '/object-cache.php' ) ) {
                //
                // Sometimes advanced-cache.php can load object-cache.php before
                // it is loaded here. This breaks the function_exists check above
                // and can result in `$_hq_using_ext_object_cache` being set
                // incorrectly. Double check if an external cache exists.
                // 
                hq_using_ext_object_cache( true );
        }

        if ( ! hq_using_ext_object_cache() )
                require_once ( ABSPATH . HQINC . '/cache.php' );

        // 
        // If cache supports reset, reset instead of init if already
        // initialized. Reset signals to the cache that global IDs
        // have changed and it may need to update keys and cleanup caches.
        //
        if ( ! $first_init && function_exists( 'hq_cache_switch_to_blog' ) )
                hq_cache_switch_to_blog( $blog_id );
        elseif ( function_exists( 'hq_cache_init' ) )
                hq_cache_init();

        if ( function_exists( 'hq_cache_add_global_groups' ) ) {
                //TODO: fix filelds
                hq_cache_add_global_groups( array( 'users', 'userlogins', 'usermeta', 'user_meta', 'useremail', 'userslugs', 'site-transient', 'site-options', 'site-lookup', 'blog-lookup', 'blog-details', 'rss', 'global-posts', 'blog-id-cache' ) );
                hq_cache_add_non_persistent_groups( array( 'comment', 'counts', 'plugins' ) );
        }
}
*/

/**
 * Redirect to the installer if HiveQueen is not installed.
 *
 * @since 0.0.1
 * @access private
 */
function hq_not_installed() {
        //if ( ! is_blog_installed() && ! defined( 'HQ_INSTALLING' ) ) {
        if ( ! is_hq_installed() && ! defined( 'HQ_INSTALLING' ) ) {
                nocache_headers();

                require( ABSPATH . HQINC . '/kses.php' );
                require( ABSPATH . HQINC . '/pluggable.php' );
                require( ABSPATH . HQINC . '/formatting.php' );

                $link = hq_guess_url() . '/hq-admin/install.php';

                hq_redirect( $link );
                die();
        }
}

/**
 * Retrieve an array of must-use plugin files.
 *
 * The default directory is hq-content/mu-plugins. To change the default
 * directory manually, define `WQMU_PLUGIN_DIR` and `HQMU_PLUGIN_URL`
 * in hq-config.php.
 *
 * @since 0.0.1
 * @access private
 *
 * @return array Files to include.
 */
function hq_get_mu_plugins() {
        $mu_plugins = array();
        if ( !is_dir( HQMU_PLUGIN_DIR ) )
                return $mu_plugins;
        if ( ! $dh = opendir( HQMU_PLUGIN_DIR ) )
                return $mu_plugins;
        while ( ( $plugin = readdir( $dh ) ) !== false ) {
                if ( substr( $plugin, -4 ) == '.php' )
                        $mu_plugins[] = HQMU_PLUGIN_DIR . '/' . $plugin;
        }
        closedir( $dh );
        sort( $mu_plugins );

        return $mu_plugins;
}

/**
 * Retrieve an array of active and valid plugin files.
 *
 * While upgrading or installing HiveQueen, no plugins are returned.
 *
 * The default directory is hq-content/plugins. To change the default
 * directory manually, define `HQ_PLUGIN_DIR` and `HQ_PLUGIN_URL`
 * in hq-config.php.
 *
 * @since 0.0.1
 * @access private
 *
 * @return array Files.
 */
function hq_get_active_and_valid_plugins() {
        $plugins = array();
        $active_plugins = (array) get_option( 'active_plugins', array() );

        // Check for hacks file if the option is enabled
        if ( get_option( 'hack_file' ) && file_exists( ABSPATH . 'my-hacks.php' ) ) {
                _deprecated_file( 'my-hacks.php', '1.5' );
                array_unshift( $plugins, ABSPATH . 'my-hacks.php' );
        }

        if ( empty( $active_plugins ) || defined( 'HQ_INSTALLING' ) )
                return $plugins;

        $network_plugins = is_multisite() ? hq_get_active_network_plugins() : false;

        foreach ( $active_plugins as $plugin ) {
                if ( ! validate_file( $plugin ) // $plugin must validate as file
                        && '.php' == substr( $plugin, -4 ) // $plugin must end with '.php'
                        && file_exists( HQ_PLUGIN_DIR . '/' . $plugin ) // $plugin must exist
                        // not already included as a network plugin
                        && ( ! $network_plugins || ! in_array( HQ_PLUGIN_DIR . '/' . $plugin, $network_plugins ) )
                        )
                $plugins[] = HQ_PLUGIN_DIR . '/' . $plugin;
        }
        return $plugins;
}

/**
 * Set internal encoding.
 *
 * In most cases the default internal encoding is latin1, which is
 * of no use, since we want to use the `mb_` functions for `utf-8` strings.
 *
 * @since 0.0.1
 * @access private
 */
function hq_set_internal_encoding() {
        if ( function_exists( 'mb_internal_encoding' ) ) {
                $charset = get_option( 'blog_charset' );
                if ( ! $charset || ! @mb_internal_encoding( $charset ) )
                        mb_internal_encoding( 'UTF-8' );
        }
}

/**
 * Add magic quotes to `$_GET`, `$_POST`, `$_COOKIE`, and `$_SERVER`.
 *
 * Also forces `$_REQUEST` to be `$_GET + $_POST`. If `$_SERVER`,
 * `$_COOKIE`, or `$_ENV` are needed, use those superglobals directly.
 *
 * @since 0.0.1
 * @access private
 */
function hq_magic_quotes() {
        // If already slashed, strip.
        if ( get_magic_quotes_gpc() ) {
                $_GET    = stripslashes_deep( $_GET    );
                $_POST   = stripslashes_deep( $_POST   );
                $_COOKIE = stripslashes_deep( $_COOKIE );
        }

        // Escape with hqdb.
        $_GET    = add_magic_quotes( $_GET    );
        $_POST   = add_magic_quotes( $_POST   );
        $_COOKIE = add_magic_quotes( $_COOKIE );
        $_SERVER = add_magic_quotes( $_SERVER );

        // Force REQUEST to be GET + POST.
        $_REQUEST = array_merge( $_GET, $_POST );
}

/**
 * Runs just before PHP shuts down execution.
 *
 * @since 0.0.1
 * @access private
 */
function shutdown_action_hook() {
        /**
         * Fires just before PHP shuts down execution.
         *
         * @since 1.2.0
         */
        do_action( 'shutdown' );
      
        //TODO: disable cache
        //hq_cache_close();
}

/**
 * Copy an object.
 *
 * @since 0.0.1
 *
 * @param object $object The object to clone.
 * @return object The cloned object.
 */
function hq_clone( $object ) {
        // Use parens for clone to accommodate PHP 4. See #17880
        return clone( $object );
}

/**
 * Whether the current request is for an administrative interface page.
 *
 * Does not check if the user is an administrator; {@see current_user_can()}
 * for checking roles and capabilities.
 *
 * @since 0.0.1
 *
 * @global HQ_Screen $current_screen
 *
 * @return bool True if inside HiveQueen administration interface, false otherwise.
 */
function is_admin() {
        if ( isset( $GLOBALS['current_screen'] ) )
                return $GLOBALS['current_screen']->in_admin();
        elseif ( defined( 'HQ_ADMIN' ) )
                return HQ_ADMIN;

        return false;
}

/**
 * Whether the current request is for a site's admininstrative interface.
 *
 * e.g. `/hq-admin/`
 *
 * Does not check if the user is an administrator; {@see current_user_can()}
 * for checking roles and capabilities.
 *
 * @since 0.0.1
 *
 * @global HQ_Screen $current_screen
 *
 * @return bool True if inside HiveQueen blog administration pages.
 */
function is_blog_admin() {
        if ( isset( $GLOBALS['current_screen'] ) )
                return $GLOBALS['current_screen']->in_admin( 'site' );
        elseif ( defined( 'HQ_BLOG_ADMIN' ) )
                return HQ_BLOG_ADMIN;

        return false;
}

/**
 * Whether the current request is for the network administrative interface.
 *
 * e.g. `/hq-admin/network/`
 *
 * Does not check if the user is an administrator; {@see current_user_can()}
 * for checking roles and capabilities.
 *
 * @since 0.0.1
 *
 * @global HQ_Screen $current_screen
 *
 * @return bool True if inside HiveQueen network administration pages.
 */
function is_network_admin() {
        if ( isset( $GLOBALS['current_screen'] ) )
                return $GLOBALS['current_screen']->in_admin( 'network' );
        elseif ( defined( 'HQ_NETWORK_ADMIN' ) )
                return HQ_NETWORK_ADMIN;

        return false;
}

/**
 * Whether the current request is for a user admin screen.
 *
 * e.g. `/hq-admin/user/`
 *
 * Does not inform on whether the user is an admin! Use capability
 * checks to tell if the user should be accessing a section or not
 * {@see current_user_can()}.
 *
 * @since 0.0.1
 *
 * @global HQ_Screen $current_screen
 *
 * @return bool True if inside HiveQueen user administration pages.
 */
function is_user_admin() {
        if ( isset( $GLOBALS['current_screen'] ) )
                return $GLOBALS['current_screen']->in_admin( 'user' );
        elseif ( defined( 'HQ_USER_ADMIN' ) )
                return HQ_USER_ADMIN;

        return false;
}



/**
 * Attempt an early load of translations.
 *
 * Used for errors encountered during the initial loading process, before
 * the locale has been properly detected and loaded.
 *
 * Designed for unusual load sequences (like setup-config.php) or for when
 * the script will then terminate with an error, otherwise there is a risk
 * that a file can be double-included.
 *
 * @since 0.0.1
 * @access private
 *
 * @global string    $text_direction
 * @global HQ_Locale $hq_locale      The HiveQueen date and time locale object.
 *
 * @staticvar bool $loaded
 */
function hq_load_translations_early() {
        global $text_direction, $hq_locale;

        static $loaded = false;
        if ( $loaded )
                return;
        $loaded = true;

        if ( function_exists( 'did_action' ) && did_action( 'init' ) )
                return;

        // We need $hq_local_package
        require ABSPATH . HQINC . '/version.php';

        // Translation and localization
        require_once ABSPATH . HQINC . '/pomo/mo.php';
        require_once ABSPATH . HQINC . '/l10n.php';
        require_once ABSPATH . HQINC . '/locale.php';

        // General libraries
        require_once ABSPATH . HQINC . '/plugin.php';

        $locales = $locations = array();

        while ( true ) {
                if ( defined( 'HQLANG' ) ) {
                        if ( '' == HQLANG )
                                break;
                        $locales[] = HQLANG;
                }

                if ( isset( $hq_local_package ) )
                        $locales[] = $hq_local_package;

                if ( ! $locales )
                        break;

                if ( defined( 'HQ_LANG_DIR' ) && @is_dir( HQ_LANG_DIR ) )
                        $locations[] = HQ_LANG_DIR;

                if ( defined( 'HQ_CONTENT_DIR' ) && @is_dir( HQ_CONTENT_DIR . '/languages' ) )
                        $locations[] = HQ_CONTENT_DIR . '/languages';

                if ( @is_dir( ABSPATH . 'hq-content/languages' ) )
                        $locations[] = ABSPATH . 'hq-content/languages';

                if ( @is_dir( ABSPATH . HQINC . '/languages' ) )
                        $locations[] = ABSPATH . HQINC . '/languages';

                if ( ! $locations )
                        break;
                $locations = array_unique( $locations );

                foreach ( $locales as $locale ) {
                        foreach ( $locations as $location ) {
                                if ( file_exists( $location . '/' . $locale . '.mo' ) ) {
                                        load_textdomain( 'default', $location . '/' . $locale . '.mo' );
                                        if ( defined( 'HQ_SETUP_CONFIG' ) && file_exists( $location . '/admin-' . $locale . '.mo' ) )
                                                load_textdomain( 'default', $location . '/admin-' . $locale . '.mo' );
                                        break 2;
                                }
                        }
                }

                break;
        }

        $hq_locale = new HQ_Locale();
}







