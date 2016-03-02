<?php
/**
 * Bootstrap file for setting the ABSPATH constant
 * and loading the hq-config.php file. The hq-config.php
 * file will then load the hq-settings.php file, which
 * will then set up the HiveQueen environment.
 *
 * If the hq-config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * hq-config.php file.
 *
 * Will also search for hq-config.php in HiveQueen' parent
 * directory to allow the HiveQueen directory to remain
 * untouched.
 *
 * @internal This file must be parsable by PHP4.
 *
 * @package HiveQueen
 */

/** Define ABSPATH as this file's directory */
define( 'ABSPATH', dirname(__FILE__) . '/' );


error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );

/*
 * If hq-config.php exists in the HiveQueen root, or if it exists in the root and hq-settings.php
 * doesn't, load hq-config.php. The secondary check for hq-settings.php has the added benefit
 * of avoiding cases where the current directory is a nested installation, e.g. / is HiveQueen(a)
 * and /blog/ is HiveQueen(b).
 *
 * If neither set of conditions is true, initiate loading the setup process.
 */
if ( file_exists( ABSPATH . 'hq-config.php') ) {

	/** The config file resides in ABSPATH */
	require_once( ABSPATH . 'hq-config.php' );


} elseif ( file_exists( dirname(ABSPATH) . '/hq-config.php' ) && ! file_exists( dirname(ABSPATH) . '/hq-settings.php' ) ) {

	/** The config file resides one level above ABSPATH but is not part of another install */
	require_once( dirname(ABSPATH) . '/hq-config.php' );

} else {

	// A config file doesn't exist

	define( 'HQINC', 'hq-includes' );
	require_once( ABSPATH . HQINC . '/load.php' );

	// Standardize $_SERVER variables across setups.
	hq_fix_server_vars();

	require_once( ABSPATH . HQINC . '/functions.php' );

	$path = hq_guess_url() . '/hq-admin/setup-config.php';

	/*
	 * We're going to redirect to setup-config.php. While this shouldn't result
	 * in an infinite loop, that's a silly thing to assume, don't you think? If
	 * we're traveling in circles, our last-ditch effort is "Need more help?"
	 */
	if ( false === strpos( $_SERVER['REQUEST_URI'], 'setup-config' ) ) {
		header( 'Location: ' . $path );
		exit;
	}

	define( 'HQ_CONTENT_DIR', ABSPATH . 'hq-content' );
	require_once( ABSPATH . HQINC . '/version.php' );

	hq_check_php_mysql_versions();
	hq_load_translations_early();

	// Die with an error message
	$die  = __( "There doesn't seem to be a <code>hq-config.php</code> file. I need this before we can get started." ) . '</p>';
	$die .= '<p>' . __( "Need more help? <a href='https://codex.wordpress.org/Editing_hq-config.php'>We got it</a>." ) . '</p>';
	$die .= '<p>' . __( "You can create a <code>hq-config.php</code> file through a web interface, but this doesn't work for all server setups. The safest way is to manually create the file." ) . '</p>';
	$die .= '<p><a href="' . $path . '" class="button button-large">' . __( "Create a Configuration File" ) . '</a>';

	hq_die( $die, __( 'HiveQueen &rsaquo; Error' ) );
}


