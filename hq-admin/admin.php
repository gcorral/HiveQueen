<?php
/**
 * HiveQueen Administration Bootstrap
 *
 * @package HiveQueen
 * @subpackage Administration
 */

/**
 * In HiveQueen Administration Screens
 *
 * @since 0.0.1
 */
if ( ! defined( 'HQ_ADMIN' ) ) {
	define( 'HQ_ADMIN', true );
}

if ( ! defined('HQ_NETWORK_ADMIN') )
	define('HQ_NETWORK_ADMIN', false);

if ( ! defined('HQ_USER_ADMIN') )
	define('HQ_USER_ADMIN', false);

if ( ! HQ_NETWORK_ADMIN && ! HQ_USER_ADMIN ) {
	define('HQ_BLOG_ADMIN', true);
}

if ( isset($_GET['import']) && !defined('HQ_LOAD_IMPORTERS') )
	define('HQ_LOAD_IMPORTERS', true);

require_once(dirname(dirname(__FILE__)) . '/hq-load.php');

nocache_headers();

if ( get_option('db_upgraded') ) {
	flush_rewrite_rules();
	update_option( 'db_upgraded',  false );

	/**
	 * Fires on the next page load after a successful DB upgrade.
	 *
	 * @since 0.0.1
	 */
	do_action( 'after_db_upgrade' );
} elseif ( get_option('db_version') != $hq_db_version && empty($_POST) ) {
	if ( !is_multisite() ) {
		hq_redirect( admin_url( 'upgrade.php?_hq_http_referer=' . urlencode( hq_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
		exit;

	/**
	 * Filter whether to attempt to perform the multisite DB upgrade routine.
	 *
	 * In single site, the user would be redirected to hq-admin/upgrade.php.
	 * In multisite, the DB upgrade routine is automatically fired, but only
	 * when this filter returns true.
	 *
	 * If the network is 50 sites or less, it will run every time. Otherwise,
	 * it will throttle itself to reduce load.
	 *
	 * @since 0.0.1
	 *
	 * @param bool true Whether to perform the Multisite upgrade routine. Default true.
	 */
	} elseif ( apply_filters( 'do_mu_upgrade', true ) ) {
		$c = get_blog_count();

		/*
		 * If there are 50 or fewer sites, run every time. Otherwise, throttle to reduce load:
		 * attempt to do no more than threshold value, with some +/- allowed.
		 */
		if ( $c <= 50 || ( $c > 50 && mt_rand( 0, (int)( $c / 50 ) ) == 1 ) ) {
			require_once( ABSPATH . HQINC . '/http.php' );
			$response = hq_remote_get( admin_url( 'upgrade.php?step=1' ), array( 'timeout' => 120, 'httpversion' => '1.1' ) );
			/** This action is documented in hq-admin/network/upgrade.php */
			do_action( 'after_mu_upgrade', $response );
			unset($response);
		}
		unset($c);
	}
}

require_once(ABSPATH . 'hq-admin/includes/admin.php');

auth_redirect();

// Schedule trash collection
if ( !hq_next_scheduled('hq_scheduled_delete') && !defined('HQ_INSTALLING') )
	hq_schedule_event(time(), 'daily', 'hq_scheduled_delete');

set_screen_options();

$date_format = get_option('date_format');
$time_format = get_option('time_format');

hq_enqueue_script( 'common' );




/**
 * $pagenow is set in vars.php
 * $hq_importers is sometimes set in hq-admin/includes/import.php
 * The remaining variables are imported as globals elsewhere, declared as globals here
 *
 * @global string $pagenow
 * @global array  $hq_importers
 * @global string $hook_suffix
 * @global string $plugin_page
 * @global string $typenow
 * @global string $taxnow
 */
global $pagenow, $hq_importers, $hook_suffix, $plugin_page, $typenow, $taxnow;

$page_hook = null;

$editing = false;

if ( isset($_GET['page']) ) {
	$plugin_page = hq_unslash( $_GET['page'] );
	$plugin_page = plugin_basename($plugin_page);
}

if ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) )
	$typenow = $_REQUEST['post_type'];
else
	$typenow = '';

if ( isset( $_REQUEST['taxonomy'] ) && taxonomy_exists( $_REQUEST['taxonomy'] ) )
	$taxnow = $_REQUEST['taxonomy'];
else
	$taxnow = '';

if ( HQ_NETWORK_ADMIN )
	require(ABSPATH . 'hq-admin/network/menu.php');
elseif ( HQ_USER_ADMIN )
	require(ABSPATH . 'hq-admin/user/menu.php');
else
	require(ABSPATH . 'hq-admin/menu.php');

if ( current_user_can( 'manage_options' ) ) {
	/**
	 * Filter the maximum memory limit available for administration screens.
	 *
	 * This only applies to administrators, who may require more memory for tasks like updates.
	 * Memory limits when processing images (uploaded or edited by users of any role) are
	 * handled separately.
	 *
	 * The HQ_MAX_MEMORY_LIMIT constant specifically defines the maximum memory limit available
	 * when in the administration back-end. The default is 256M, or 256 megabytes of memory.
	 *
	 * @since 0.0.1
	 *
	 * @param string 'HQ_MAX_MEMORY_LIMIT' The maximum HiveQueen memory limit. Default 256M.
	 */
	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', HQ_MAX_MEMORY_LIMIT ) );
}

/**
 * Fires as an admin screen or script is being initialized.
 *
 * Note, this does not just run on user-facing admin screens.
 * It runs on admin-ajax.php and admin-post.php as well.
 *
 * This is roughly analgous to the more general 'init' hook, which fires earlier.
 *
 * @since 0.0.1
 */
do_action( 'admin_init' );

if ( isset($plugin_page) ) {
	if ( !empty($typenow) )
		$the_parent = $pagenow . '?post_type=' . $typenow;
	else
		$the_parent = $pagenow;
	if ( ! $page_hook = get_plugin_page_hook($plugin_page, $the_parent) ) {
		$page_hook = get_plugin_page_hook($plugin_page, $plugin_page);

		// Backwards compatibility for plugins using add_management_page().
		if ( empty( $page_hook ) && 'edit.php' == $pagenow && '' != get_plugin_page_hook($plugin_page, 'tools.php') ) {
			// There could be plugin specific params on the URL, so we need the whole query string
			if ( !empty($_SERVER[ 'QUERY_STRING' ]) )
				$query_string = $_SERVER[ 'QUERY_STRING' ];
			else
				$query_string = 'page=' . $plugin_page;
			hq_redirect( admin_url('tools.php?' . $query_string) );
			exit;
		}
	}
	unset($the_parent);
}

$hook_suffix = '';
if ( isset( $page_hook ) ) {
	$hook_suffix = $page_hook;
} elseif ( isset( $plugin_page ) ) {
	$hook_suffix = $plugin_page;
} elseif ( isset( $pagenow ) ) {
	$hook_suffix = $pagenow;
}

set_current_screen();

// Handle plugin admin pages.
if ( isset($plugin_page) ) {
	if ( $page_hook ) {
		/**
		 * Fires before a particular screen is loaded.
		 *
		 * The load-* hook fires in a number of contexts. This hook is for plugin screens
		 * where a callback is provided when the screen is registered.
		 *
		 * The dynamic portion of the hook name, `$page_hook`, refers to a mixture of plugin
		 * page information including:
		 * 1. The page type. If the plugin page is registered as a submenu page, such as for
		 *    Settings, the page type would be 'settings'. Otherwise the type is 'toplevel'.
		 * 2. A separator of '_page_'.
		 * 3. The plugin basename minus the file extension.
		 *
		 * Together, the three parts form the `$page_hook`. Citing the example above,
		 * the hook name used would be 'load-settings_page_pluginbasename'.
		 *
		 * @see get_plugin_page_hook()
		 *
		 * @since 0.0.1
		 */
		do_action( 'load-' . $page_hook );
		if (! isset($_GET['noheader']))
			require_once(ABSPATH . 'hq-admin/admin-header.php');

		/**
		 * Used to call the registered callback for a plugin screen.
		 *
		 * @ignore
		 * @since 0.0.1
		 */
		do_action( $page_hook );
	} else {
		if ( validate_file($plugin_page) )
			hq_die(__('Invalid plugin page'));

		if ( !( file_exists(HQ_PLUGIN_DIR . "/$plugin_page") && is_file(HQ_PLUGIN_DIR . "/$plugin_page") ) && !( file_exists(HQMU_PLUGIN_DIR . "/$plugin_page") && is_file(HQMU_PLUGIN_DIR . "/$plugin_page") ) )
			hq_die(sprintf(__('Cannot load %s.'), htmlentities($plugin_page)));

		/**
		 * Fires before a particular screen is loaded.
		 *
		 * The load-* hook fires in a number of contexts. This hook is for plugin screens
		 * where the file to load is directly included, rather than the use of a function.
		 *
		 * The dynamic portion of the hook name, `$plugin_page`, refers to the plugin basename.
		 *
		 * @see plugin_basename()
		 *
		 * @since 0.0.1
		 */
		do_action( 'load-' . $plugin_page );

		if ( !isset($_GET['noheader']))
			require_once(ABSPATH . 'hq-admin/admin-header.php');

		if ( file_exists(HQMU_PLUGIN_DIR . "/$plugin_page") )
			include(HQMU_PLUGIN_DIR . "/$plugin_page");
		else
			include(HQ_PLUGIN_DIR . "/$plugin_page");
	}

	include(ABSPATH . 'hq-admin/admin-footer.php');

	exit();
} elseif ( isset( $_GET['import'] ) ) {

	$importer = $_GET['import'];

	if ( ! current_user_can('import') )
		hq_die(__('You are not allowed to import.'));

	if ( validate_file($importer) ) {
		hq_redirect( admin_url( 'import.php?invalid=' . $importer ) );
		exit;
	}

	if ( ! isset($hq_importers[$importer]) || ! is_callable($hq_importers[$importer][2]) ) {
		hq_redirect( admin_url( 'import.php?invalid=' . $importer ) );
		exit;
	}

	/**
	 * Fires before an importer screen is loaded.
	 *
	 * The dynamic portion of the hook name, `$importer`, refers to the importer slug.
	 *
	 * @since 0.0.1
	 */
	do_action( 'load-importer-' . $importer );

	$parent_file = 'tools.php';
	$submenu_file = 'import.php';
	$title = __('Import');

	if (! isset($_GET['noheader']))
		require_once(ABSPATH . 'hq-admin/admin-header.php');

	require_once(ABSPATH . 'hq-admin/includes/upgrade.php');

	define('HQ_IMPORTING', true);

	/**
	 * Whether to filter imported data through kses on import.
	 *
	 * Multisite uses this hook to filter all data through kses by default,
	 * as a super administrator may be assisting an untrusted user.
	 *
	 * @since 0.0.1
	 *
	 * @param bool false Whether to force data to be filtered through kses. Default false.
	 */
	if ( apply_filters( 'force_filtered_html_on_import', false ) ) {
		kses_init_filters();  // Always filter imported data with kses on multisite.
	}

	call_user_func($hq_importers[$importer][2]);

	include(ABSPATH . 'hq-admin/admin-footer.php');

	// Make sure rules are flushed
	flush_rewrite_rules(false);

	exit();
} else {
	/**
	 * Fires before a particular screen is loaded.
	 *
	 * The load-* hook fires in a number of contexts. This hook is for core screens.
	 *
	 * The dynamic portion of the hook name, `$pagenow`, is a global variable
	 * referring to the filename of the current page, such as 'admin.php',
	 * 'post-new.php' etc. A complete hook for the latter would be
	 * 'load-post-new.php'.
	 *
	 * @since 0.0.1
	 */
	do_action( 'load-' . $pagenow );

	/*
	 * The following hooks are fired to ensure backward compatibility.
	 * In all other cases, 'load-' . $pagenow should be used instead.
	 */
	if ( $typenow == 'page' ) {
		if ( $pagenow == 'post-new.php' )
			do_action( 'load-page-new.php' );
		elseif ( $pagenow == 'post.php' )
			do_action( 'load-page.php' );
	}  elseif ( $pagenow == 'edit-tags.php' ) {
		if ( $taxnow == 'category' )
			do_action( 'load-categories.php' );
		elseif ( $taxnow == 'link_category' )
			do_action( 'load-edit-link-categories.php' );
	}
}

if ( ! empty( $_REQUEST['action'] ) ) {
	/**
	 * Fires when an 'action' request variable is sent.
	 *
	 * The dynamic portion of the hook name, `$_REQUEST['action']`,
	 * refers to the action derived from the `GET` or `POST` request.
	 *
	 * @since 0.0.1
	 */
	do_action( 'admin_action_' . $_REQUEST['action'] );
}
