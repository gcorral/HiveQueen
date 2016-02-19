<?php
/**
 * HiveQueen scripts and styles default loader.
 *
 * Most of the functionality that existed here was moved to
 * {@link http://backpress.automattic.com/ BackPress}. HiveQueen themes and
 * plugins will only be concerned about the filters and actions set in this
 * file.
 *
 * Several constants are used to manage the loading, concatenating and compression of scripts and CSS:
 * define('SCRIPT_DEBUG', true); loads the development (non-minified) versions of all scripts and CSS, and disables compression and concatenation,
 * define('CONCATENATE_SCRIPTS', false); disables compression and concatenation of scripts and CSS,
 * define('COMPRESS_SCRIPTS', false); disables compression of scripts,
 * define('COMPRESS_CSS', false); disables compression of CSS,
 * define('ENFORCE_GZIP', true); forces gzip for compression (default is deflate).
 *
 * The globals $concatenate_scripts, $compress_scripts and $compress_css can be set by plugins
 * to temporarily override the above settings. Also a compression test is run once and the result is saved
 * as option 'can_compress_scripts' (0/1). The test will run again if that option is deleted.
 *
 * @package HiveQueen
 */

/** BackPress: HiveQueen Dependencies Class */
require( ABSPATH . HQINC . '/class.hq-dependencies.php' );

/** BackPress: HiveQueen Scripts Class */
# TODO:
#require( ABSPATH . HQINC . '/class.hq-scripts.php' );

/** BackPress: HiveQueen Scripts Functions */
# TODO:
#require( ABSPATH . HQINC . '/functions.hq-scripts.php' );

/** BackPress: HiveQueen Styles Class */
require( ABSPATH . HQINC . '/class.hq-styles.php' );

/** BackPress: HiveQueen Styles Functions */
require( ABSPATH . HQINC . '/functions.hq-styles.php' );

/**
 * Register all HiveQueen scripts.
 *
 * Localizes some of them.
 * args order: $scripts->add( 'handle', 'url', 'dependencies', 'query-string', 1 );
 * when last arg === 1 queues the script for the footer
 *
 * @since 0.0.1
 *
 * @param HQ_Scripts $scripts HQ_Scripts object.
 */
function hq_default_scripts( &$scripts ) {
	include( ABSPATH . HQINC . '/version.php' ); // include an unmodified $hq_version

	$develop_src = false !== strpos( $hq_version, '-src' );

	if ( ! defined( 'SCRIPT_DEBUG' ) ) {
		define( 'SCRIPT_DEBUG', $develop_src );
	}

	if ( ! $guessurl = site_url() ) {
		$guessed_url = true;
		$guessurl = hq_guess_url();
	}

	$scripts->base_url = $guessurl;
	$scripts->content_url = defined('HQ_CONTENT_URL')? HQ_CONTENT_URL : '';
	$scripts->default_version = get_bloginfo( 'version' );
	$scripts->default_dirs = array('/hq-admin/js/', '/hq-includes/js/');

	$suffix = SCRIPT_DEBUG ? '' : '.min';
	$dev_suffix = $develop_src ? '' : '.min';

	$scripts->add( 'utils', "/hq-includes/js/utils$suffix.js" );
	did_action( 'init' ) && $scripts->localize( 'utils', 'userSettings', array(
		'url' => (string) SITECOOKIEPATH,
		'uid' => (string) get_current_user_id(),
		'time' => (string) time(),
		'secure' => (string) ( 'https' === parse_url( site_url(), PHP_URL_SCHEME ) ),
	) );

	$scripts->add( 'common', "/hq-admin/js/common$suffix.js", array('jquery', 'hoverIntent', 'utils'), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'common', 'commonL10n', array(
		'warnDelete' => __( "You are about to permanently delete the selected items.\n  'Cancel' to stop, 'OK' to delete." ),
		'dismiss'    => __( 'Dismiss this notice.' ),
	) );

	$scripts->add( 'hq-a11y', "/hq-includes/js/hq-a11y$suffix.js", array( 'jquery' ), false, 1 );

	$scripts->add( 'sack', "/hq-includes/js/tw-sack$suffix.js", array(), '1.6.1', 1 );

	$scripts->add( 'quicktags', "/hq-includes/js/quicktags$suffix.js", array(), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'quicktags', 'quicktagsL10n', array(
		'closeAllOpenTags'      => __( 'Close all open tags' ),
		'closeTags'             => __( 'close tags' ),
		'enterURL'              => __( 'Enter the URL' ),
		'enterImageURL'         => __( 'Enter the URL of the image' ),
		'enterImageDescription' => __( 'Enter a description of the image' ),
		'textdirection'         => __( 'text direction' ),
		'toggleTextdirection'   => __( 'Toggle Editor Text Direction' ),
		'dfw'                   => __( 'Distraction-free writing mode' ),
		'strong'          => __( 'Bold' ),
		'strongClose'     => __( 'Close bold tag' ),
		'em'              => __( 'Italic' ),
		'emClose'         => __( 'Close italic tag' ),
		'link'            => __( 'Insert link' ),
		'blockquote'      => __( 'Blockquote' ),
		'blockquoteClose' => __( 'Close blockquote tag' ),
		'del'             => __( 'Deleted text (strikethrough)' ),
		'delClose'        => __( 'Close deleted text tag' ),
		'ins'             => __( 'Inserted text' ),
		'insClose'        => __( 'Close inserted text tag' ),
		'image'           => __( 'Insert image' ),
		'ul'              => __( 'Bulleted list' ),
		'ulClose'         => __( 'Close bulleted list tag' ),
		'ol'              => __( 'Numbered list' ),
		'olClose'         => __( 'Close numbered list tag' ),
		'li'              => __( 'List item' ),
		'liClose'         => __( 'Close list item tag' ),
		'code'            => __( 'Code' ),
		'codeClose'       => __( 'Close code tag' ),
		'more'            => __( 'Insert Read More tag' ),
	) );

	$scripts->add( 'colorpicker', "/hq-includes/js/colorpicker$suffix.js", array('prototype'), '3517m' );

	$scripts->add( 'editor', "/hq-admin/js/editor$suffix.js", array('utils','jquery'), false, 1 );

	// Back-compat for old DFW. To-do: remove at the end of 2016.
	$scripts->add( 'hq-fullscreen-stub', "/hq-admin/js/hq-fullscreen-stub$suffix.js", array(), false, 1 );

	$scripts->add( 'hq-ajax-response', "/hq-includes/js/hq-ajax-response$suffix.js", array('jquery'), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'hq-ajax-response', 'hqAjax', array(
		'noPerm' => __('You do not have permission to do that.'),
		'broken' => __('An unidentified error has occurred.')
	) );

	$scripts->add( 'hq-pointer', "/hq-includes/js/hq-pointer$suffix.js", array( 'jquery-ui-widget', 'jquery-ui-position' ), '20111129a', 1 );
	did_action( 'init' ) && $scripts->localize( 'hq-pointer', 'hqPointerL10n', array(
		'dismiss' => __('Dismiss'),
	) );

	$scripts->add( 'autosave', "/hq-includes/js/autosave$suffix.js", array('heartbeat'), false, 1 );

	$scripts->add( 'heartbeat', "/hq-includes/js/heartbeat$suffix.js", array('jquery'), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'heartbeat', 'heartbeatSettings',
		/**
		 * Filter the Heartbeat settings.
		 *
		 * @since 0.0.1
		 *
		 * @param array $settings Heartbeat settings array.
		 */
		apply_filters( 'heartbeat_settings', array() )
	);

	$scripts->add( 'hq-auth-check', "/hq-includes/js/hq-auth-check$suffix.js", array('heartbeat'), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'hq-auth-check', 'authcheckL10n', array(
		'beforeunload' => __('Your session has expired. You can log in again from this page or go to the login page.'),

		/**
		 * Filter the authentication check interval.
		 *
		 * @since 0.0.1
		 *
		 * @param int $interval The interval in which to check a user's authentication.
		 *                      Default 3 minutes in seconds, or 180.
		 */
		'interval' => apply_filters( 'hq_auth_check_interval', 3 * MINUTE_IN_SECONDS ),
	) );

	$scripts->add( 'hq-lists', "/hq-includes/js/hq-lists$suffix.js", array( 'hq-ajax-response', 'jquery-color' ), false, 1 );

	// HiveQueen no longer uses or bundles Prototype or script.aculo.us. These are now pulled from an external source.
	$scripts->add( 'prototype', 'https://ajax.googleapis.com/ajax/libs/prototype/1.7.1.0/prototype.js', array(), '1.7.1');
	$scripts->add( 'scriptaculous-root', 'https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/scriptaculous.js', array('prototype'), '1.9.0');
	$scripts->add( 'scriptaculous-builder', 'https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/builder.js', array('scriptaculous-root'), '1.9.0');
	$scripts->add( 'scriptaculous-dragdrop', 'https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/dragdrop.js', array('scriptaculous-builder', 'scriptaculous-effects'), '1.9.0');
	$scripts->add( 'scriptaculous-effects', 'https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/effects.js', array('scriptaculous-root'), '1.9.0');
	$scripts->add( 'scriptaculous-slider', 'https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/slider.js', array('scriptaculous-effects'), '1.9.0');
	$scripts->add( 'scriptaculous-sound', 'https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/sound.js', array( 'scriptaculous-root' ), '1.9.0' );
	$scripts->add( 'scriptaculous-controls', 'https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/controls.js', array('scriptaculous-root'), '1.9.0');
	$scripts->add( 'scriptaculous', false, array('scriptaculous-dragdrop', 'scriptaculous-slider', 'scriptaculous-controls') );

	// not used in core, replaced by Jcrop.js
	$scripts->add( 'cropper', '/hq-includes/js/crop/cropper.js', array('scriptaculous-dragdrop') );

	// jQuery
	$scripts->add( 'jquery', false, array( 'jquery-core', 'jquery-migrate' ), '1.11.3' );
	$scripts->add( 'jquery-core', '/hq-includes/js/jquery/jquery.js', array(), '1.11.3' );
	$scripts->add( 'jquery-migrate', "/hq-includes/js/jquery/jquery-migrate$suffix.js", array(), '1.2.1' );

	// full jQuery UI
	$scripts->add( 'jquery-ui-core', "/hq-includes/js/jquery/ui/core$dev_suffix.js", array('jquery'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-core', "/hq-includes/js/jquery/ui/effect$dev_suffix.js", array('jquery'), '1.11.4', 1 );

	$scripts->add( 'jquery-effects-blind', "/hq-includes/js/jquery/ui/effect-blind$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-bounce', "/hq-includes/js/jquery/ui/effect-bounce$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-clip', "/hq-includes/js/jquery/ui/effect-clip$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-drop', "/hq-includes/js/jquery/ui/effect-drop$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-explode', "/hq-includes/js/jquery/ui/effect-explode$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-fade', "/hq-includes/js/jquery/ui/effect-fade$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-fold', "/hq-includes/js/jquery/ui/effect-fold$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-highlight', "/hq-includes/js/jquery/ui/effect-highlight$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-puff', "/hq-includes/js/jquery/ui/effect-puff$dev_suffix.js", array('jquery-effects-core', 'jquery-effects-scale'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-pulsate', "/hq-includes/js/jquery/ui/effect-pulsate$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-scale', "/hq-includes/js/jquery/ui/effect-scale$dev_suffix.js", array('jquery-effects-core', 'jquery-effects-size'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-shake', "/hq-includes/js/jquery/ui/effect-shake$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-size', "/hq-includes/js/jquery/ui/effect-size$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-slide', "/hq-includes/js/jquery/ui/effect-slide$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-effects-transfer', "/hq-includes/js/jquery/ui/effect-transfer$dev_suffix.js", array('jquery-effects-core'), '1.11.4', 1 );

	$scripts->add( 'jquery-ui-accordion', "/hq-includes/js/jquery/ui/accordion$dev_suffix.js", array('jquery-ui-core', 'jquery-ui-widget'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-autocomplete', "/hq-includes/js/jquery/ui/autocomplete$dev_suffix.js", array('jquery-ui-menu'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-button', "/hq-includes/js/jquery/ui/button$dev_suffix.js", array('jquery-ui-core', 'jquery-ui-widget'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-datepicker', "/hq-includes/js/jquery/ui/datepicker$dev_suffix.js", array('jquery-ui-core'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-dialog', "/hq-includes/js/jquery/ui/dialog$dev_suffix.js", array('jquery-ui-resizable', 'jquery-ui-draggable', 'jquery-ui-button', 'jquery-ui-position'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-draggable', "/hq-includes/js/jquery/ui/draggable$dev_suffix.js", array('jquery-ui-mouse'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-droppable', "/hq-includes/js/jquery/ui/droppable$dev_suffix.js", array('jquery-ui-draggable'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-menu', "/hq-includes/js/jquery/ui/menu$dev_suffix.js", array( 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-position' ), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-mouse', "/hq-includes/js/jquery/ui/mouse$dev_suffix.js", array( 'jquery-ui-core', 'jquery-ui-widget' ), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-position', "/hq-includes/js/jquery/ui/position$dev_suffix.js", array('jquery'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-progressbar', "/hq-includes/js/jquery/ui/progressbar$dev_suffix.js", array('jquery-ui-core', 'jquery-ui-widget'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-resizable', "/hq-includes/js/jquery/ui/resizable$dev_suffix.js", array('jquery-ui-mouse'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-selectable', "/hq-includes/js/jquery/ui/selectable$dev_suffix.js", array('jquery-ui-mouse'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-selectmenu', "/hq-includes/js/jquery/ui/selectmenu$dev_suffix.js", array('jquery-ui-menu'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-slider', "/hq-includes/js/jquery/ui/slider$dev_suffix.js", array('jquery-ui-mouse'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-sortable', "/hq-includes/js/jquery/ui/sortable$dev_suffix.js", array('jquery-ui-mouse'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-spinner', "/hq-includes/js/jquery/ui/spinner$dev_suffix.js", array( 'jquery-ui-button' ), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-tabs', "/hq-includes/js/jquery/ui/tabs$dev_suffix.js", array('jquery-ui-core', 'jquery-ui-widget'), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-tooltip', "/hq-includes/js/jquery/ui/tooltip$dev_suffix.js", array( 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-position' ), '1.11.4', 1 );
	$scripts->add( 'jquery-ui-widget', "/hq-includes/js/jquery/ui/widget$dev_suffix.js", array('jquery'), '1.11.4', 1 );

	// deprecated, not used in core, most functionality is included in jQuery 1.3
	$scripts->add( 'jquery-form', "/hq-includes/js/jquery/jquery.form$suffix.js", array('jquery'), '3.37.0', 1 );

	// jQuery plugins
	$scripts->add( 'jquery-color', "/hq-includes/js/jquery/jquery.color.min.js", array('jquery'), '2.1.1', 1 );
	$scripts->add( 'suggest', "/hq-includes/js/jquery/suggest$suffix.js", array('jquery'), '1.1-20110113', 1 );
	$scripts->add( 'schedule', '/hq-includes/js/jquery/jquery.schedule.js', array('jquery'), '20m', 1 );
	$scripts->add( 'jquery-query', "/hq-includes/js/jquery/jquery.query.js", array('jquery'), '2.1.7', 1 );
	$scripts->add( 'jquery-serialize-object', "/hq-includes/js/jquery/jquery.serialize-object.js", array('jquery'), '0.2', 1 );
	$scripts->add( 'jquery-hotkeys', "/hq-includes/js/jquery/jquery.hotkeys$suffix.js", array('jquery'), '0.0.2m', 1 );
	$scripts->add( 'jquery-table-hotkeys', "/hq-includes/js/jquery/jquery.table-hotkeys$suffix.js", array('jquery', 'jquery-hotkeys'), false, 1 );
	$scripts->add( 'jquery-touch-punch', "/hq-includes/js/jquery/jquery.ui.touch-punch.js", array('jquery-ui-widget', 'jquery-ui-mouse'), '0.2.2', 1 );

	// Masonry v2 depended on jQuery. v3 does not. The older jquery-masonry handle is a shiv.
	// It sets jQuery as a dependency, as the theme may have been implicitly loading it this way.
	$scripts->add( 'masonry', "/hq-includes/js/masonry.min.js", array(), '3.1.2', 1 );
	$scripts->add( 'jquery-masonry', "/hq-includes/js/jquery/jquery.masonry$dev_suffix.js", array( 'jquery', 'masonry' ), '3.1.2', 1 );

	$scripts->add( 'thickbox', "/hq-includes/js/thickbox/thickbox.js", array('jquery'), '3.1-20121105', 1 );
	did_action( 'init' ) && $scripts->localize( 'thickbox', 'thickboxL10n', array(
			'next' => __('Next &gt;'),
			'prev' => __('&lt; Prev'),
			'image' => __('Image'),
			'of' => __('of'),
			'close' => __('Close'),
			'noiframes' => __('This feature requires inline frames. You have iframes disabled or your browser does not support them.'),
			'loadingAnimation' => includes_url('js/thickbox/loadingAnimation.gif'),
	) );

	$scripts->add( 'jcrop', "/hq-includes/js/jcrop/jquery.Jcrop.min.js", array('jquery'), '0.9.12');

	$scripts->add( 'swfobject', "/hq-includes/js/swfobject.js", array(), '2.2-20120417');

	// error message for both plupload and swfupload
	$uploader_l10n = array(
		'queue_limit_exceeded' => __('You have attempted to queue too many files.'),
		'file_exceeds_size_limit' => __('%s exceeds the maximum upload size for this site.'),
		'zero_byte_file' => __('This file is empty. Please try another.'),
		'invalid_filetype' => __('This file type is not allowed. Please try another.'),
		'not_an_image' => __('This file is not an image. Please try another.'),
		'image_memory_exceeded' => __('Memory exceeded. Please try another smaller file.'),
		'image_dimensions_exceeded' => __('This is larger than the maximum size. Please try another.'),
		'default_error' => __('An error occurred in the upload. Please try again later.'),
		'missing_upload_url' => __('There was a configuration error. Please contact the server administrator.'),
		'upload_limit_exceeded' => __('You may only upload 1 file.'),
		'http_error' => __('HTTP error.'),
		'upload_failed' => __('Upload failed.'),
		'big_upload_failed' => __('Please try uploading this file with the %1$sbrowser uploader%2$s.'),
		'big_upload_queued' => __('%s exceeds the maximum upload size for the multi-file uploader when used in your browser.'),
		'io_error' => __('IO error.'),
		'security_error' => __('Security error.'),
		'file_cancelled' => __('File canceled.'),
		'upload_stopped' => __('Upload stopped.'),
		'dismiss' => __('Dismiss'),
		'crunching' => __('Crunching&hellip;'),
		'deleted' => __('moved to the trash.'),
		'error_uploading' => __('&#8220;%s&#8221; has failed to upload.')
	);

	$scripts->add( 'plupload', '/hq-includes/js/plupload/plupload.full.min.js', array(), '2.1.1' );
	// Back compat handles:
	foreach ( array( 'all', 'html5', 'flash', 'silverlight', 'html4' ) as $handle ) {
		$scripts->add( "plupload-$handle", false, array( 'plupload' ), '2.1.1' );
	}

	$scripts->add( 'plupload-handlers', "/hq-includes/js/plupload/handlers$suffix.js", array( 'plupload', 'jquery' ) );
	did_action( 'init' ) && $scripts->localize( 'plupload-handlers', 'pluploadL10n', $uploader_l10n );

	$scripts->add( 'hq-plupload', "/hq-includes/js/plupload/hq-plupload$suffix.js", array( 'plupload', 'jquery', 'json2', 'media-models' ), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'hq-plupload', 'pluploadL10n', $uploader_l10n );

	// keep 'swfupload' for back-compat.
	$scripts->add( 'swfupload', '/hq-includes/js/swfupload/swfupload.js', array(), '2201-20110113');
	$scripts->add( 'swfupload-swfobject', '/hq-includes/js/swfupload/plugins/swfupload.swfobject.js', array('swfupload', 'swfobject'), '2201a');
	$scripts->add( 'swfupload-queue', '/hq-includes/js/swfupload/plugins/swfupload.queue.js', array('swfupload'), '2201');
	$scripts->add( 'swfupload-speed', '/hq-includes/js/swfupload/plugins/swfupload.speed.js', array('swfupload'), '2201');
	$scripts->add( 'swfupload-all', false, array('swfupload', 'swfupload-swfobject', 'swfupload-queue'), '2201');
	$scripts->add( 'swfupload-handlers', "/hq-includes/js/swfupload/handlers$suffix.js", array('swfupload-all', 'jquery'), '2201-20110524');
	did_action( 'init' ) && $scripts->localize( 'swfupload-handlers', 'swfuploadL10n', $uploader_l10n );

	$scripts->add( 'comment-reply', "/hq-includes/js/comment-reply$suffix.js", array(), false, 1 );

	$scripts->add( 'json2', "/hq-includes/js/json2$suffix.js", array(), '2011-02-23' );
	did_action( 'init' ) && $scripts->add_data( 'json2', 'conditional', 'lt IE 8' );

	$scripts->add( 'underscore', "/hq-includes/js/underscore$dev_suffix.js", array(), '1.6.0', 1 );
	$scripts->add( 'backbone', "/hq-includes/js/backbone$dev_suffix.js", array( 'underscore','jquery' ), '1.1.2', 1 );

	$scripts->add( 'hq-util', "/hq-includes/js/hq-util$suffix.js", array('underscore', 'jquery'), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'hq-util', '_hqUtilSettings', array(
		'ajax' => array(
			'url' => admin_url( 'admin-ajax.php', 'relative' ),
		),
	) );

	$scripts->add( 'hq-backbone', "/hq-includes/js/hq-backbone$suffix.js", array('backbone', 'hq-util'), false, 1 );

	$scripts->add( 'revisions', "/hq-admin/js/revisions$suffix.js", array( 'hq-backbone', 'jquery-ui-slider', 'hoverIntent' ), false, 1 );

	$scripts->add( 'imgareaselect', "/hq-includes/js/imgareaselect/jquery.imgareaselect$suffix.js", array('jquery'), false, 1 );

	$scripts->add( 'mediaelement', "/hq-includes/js/mediaelement/mediaelement-and-player.min.js", array('jquery'), '2.17.0', 1 );
	did_action( 'init' ) && $scripts->localize( 'mediaelement', 'mejsL10n', array(
		'language' => get_bloginfo( 'language' ),
		'strings'  => array(
			'Close'               => __( 'Close' ),
			'Fullscreen'          => __( 'Fullscreen' ),
			'Download File'       => __( 'Download File' ),
			'Download Video'      => __( 'Download Video' ),
			'Play/Pause'          => __( 'Play/Pause' ),
			'Mute Toggle'         => __( 'Mute Toggle' ),
			'None'                => __( 'None' ),
			'Turn off Fullscreen' => __( 'Turn off Fullscreen' ),
			'Go Fullscreen'       => __( 'Go Fullscreen' ),
			'Unmute'              => __( 'Unmute' ),
			'Mute'                => __( 'Mute' ),
			'Captions/Subtitles'  => __( 'Captions/Subtitles' )
		),
	) );


	$scripts->add( 'hq-mediaelement', "/hq-includes/js/mediaelement/hq-mediaelement.js", array('mediaelement'), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'mediaelement', '_hqmejsSettings', array(
		'pluginPath' => includes_url( 'js/mediaelement/', 'relative' ),
	) );

	$scripts->add( 'froogaloop',  "/hq-includes/js/mediaelement/froogaloop.min.js", array(), '2.0' );
	$scripts->add( 'hq-playlist', "/hq-includes/js/mediaelement/hq-playlist.js", array( 'hq-util', 'backbone', 'mediaelement' ), false, 1 );

	$scripts->add( 'zxcvbn-async', "/hq-includes/js/zxcvbn-async$suffix.js", array(), '1.0' );
	did_action( 'init' ) && $scripts->localize( 'zxcvbn-async', '_zxcvbnSettings', array(
		'src' => empty( $guessed_url ) ? includes_url( '/js/zxcvbn.min.js' ) : $scripts->base_url . '/hq-includes/js/zxcvbn.min.js',
	) );

	$scripts->add( 'password-strength-meter', "/hq-admin/js/password-strength-meter$suffix.js", array( 'jquery', 'zxcvbn-async' ), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'password-strength-meter', 'pwsL10n', array(
		'short'    => _x( 'Very weak', 'password strength' ),
		'bad'      => _x( 'Weak', 'password strength' ),
		'good'     => _x( 'Medium', 'password strength' ),
		'strong'   => _x( 'Strong', 'password strength' ),
		'mismatch' => _x( 'Mismatch', 'password mismatch' ),
	) );

	$scripts->add( 'user-profile', "/hq-admin/js/user-profile$suffix.js", array( 'jquery', 'password-strength-meter', 'hq-util' ), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'user-profile', 'userProfileL10n', array(
		'warn'     => __( 'Your new password has not been saved.' ),
		'show'     => __( 'Show' ),
		'hide'     => __( 'Hide' ),
		'cancel'   => __( 'Cancel' ),
		'ariaShow' => esc_attr__( 'Show password' ),
		'ariaHide' => esc_attr__( 'Hide password' ),
	) );

	$scripts->add( 'language-chooser', "/hq-admin/js/language-chooser$suffix.js", array( 'jquery' ), false, 1 );

	$scripts->add( 'user-suggest', "/hq-admin/js/user-suggest$suffix.js", array( 'jquery-ui-autocomplete' ), false, 1 );

	$scripts->add( 'admin-bar', "/hq-includes/js/admin-bar$suffix.js", array(), false, 1 );

	$scripts->add( 'hqlink', "/hq-includes/js/hqlink$suffix.js", array( 'jquery' ), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'hqlink', 'hqLinkL10n', array(
		'title' => __('Insert/edit link'),
		'update' => __('Update'),
		'save' => __('Add Link'),
		'noTitle' => __('(no title)'),
		'noMatchesFound' => __('No results found.')
	) );

	$scripts->add( 'hqdialogs', "/hq-includes/js/hqdialog$suffix.js", array( 'jquery-ui-dialog' ), false, 1 );

	$scripts->add( 'word-count', "/hq-admin/js/word-count$suffix.js", array(), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'word-count', 'wordCountL10n', array(
		/*
		 * translators: If your word count is based on single characters (e.g. East Asian characters),
		 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
		 * Do not translate into your own language.
		 */
		'type' => _x( 'words', 'Word count type. Do not translate!' ),
		'shortcodes' => ! empty( $GLOBALS['shortcode_tags'] ) ? array_keys( $GLOBALS['shortcode_tags'] ) : array()
	) );

	$scripts->add( 'media-upload', "/hq-admin/js/media-upload$suffix.js", array( 'thickbox', 'shortcode' ), false, 1 );

	$scripts->add( 'hoverIntent', "/hq-includes/js/hoverIntent$suffix.js", array('jquery'), '1.8.1', 1 );

	$scripts->add( 'customize-base',     "/hq-includes/js/customize-base$suffix.js",     array( 'jquery', 'json2', 'underscore' ), false, 1 );
	$scripts->add( 'customize-loader',   "/hq-includes/js/customize-loader$suffix.js",   array( 'customize-base' ), false, 1 );
	$scripts->add( 'customize-preview',  "/hq-includes/js/customize-preview$suffix.js",  array( 'customize-base' ), false, 1 );
	$scripts->add( 'customize-models',   "/hq-includes/js/customize-models.js", array( 'underscore', 'backbone' ), false, 1 );
	$scripts->add( 'customize-views',    "/hq-includes/js/customize-views.js",  array( 'jquery', 'underscore', 'imgareaselect', 'customize-models', 'media-editor', 'media-views' ), false, 1 );
	$scripts->add( 'customize-controls', "/hq-admin/js/customize-controls$suffix.js", array( 'customize-base', 'hq-a11y' ), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'customize-controls', '_hqCustomizeControlsL10n', array(
		'activate'           => __( 'Save &amp; Activate' ),
		'save'               => __( 'Save &amp; Publish' ),
		'saveAlert'          => __( 'The changes you made will be lost if you navigate away from this page.' ),
		'saved'              => __( 'Saved' ),
		'cancel'             => __( 'Cancel' ),
		'close'              => __( 'Close' ),
		'cheatin'            => __( 'Cheatin&#8217; uh?' ),
		'previewIframeTitle' => __( 'Site Preview' ),
		'loginIframeTitle'   => __( 'Session expired' ),
		'collapseSidebar'    => __( 'Collapse Sidebar' ),
		'expandSidebar'      => __( 'Expand Sidebar' ),
		// Used for overriding the file types allowed in plupload.
		'allowedFiles'       => __( 'Allowed Files' ),
	) );

	$scripts->add( 'customize-widgets', "/hq-admin/js/customize-widgets$suffix.js", array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'hq-backbone', 'customize-controls' ), false, 1 );
	$scripts->add( 'customize-preview-widgets', "/hq-includes/js/customize-preview-widgets$suffix.js", array( 'jquery', 'hq-util', 'customize-preview' ), false, 1 );

	$scripts->add( 'customize-nav-menus', "/hq-admin/js/customize-nav-menus$suffix.js", array( 'jquery', 'hq-backbone', 'customize-controls', 'accordion', 'nav-menu' ), false, 1 );
	$scripts->add( 'customize-preview-nav-menus', "/hq-includes/js/customize-preview-nav-menus$suffix.js", array( 'jquery', 'hq-util', 'customize-preview' ), false, 1 );

	$scripts->add( 'accordion', "/hq-admin/js/accordion$suffix.js", array( 'jquery' ), false, 1 );

	$scripts->add( 'shortcode', "/hq-includes/js/shortcode$suffix.js", array( 'underscore' ), false, 1 );
	$scripts->add( 'media-models', "/hq-includes/js/media-models$suffix.js", array( 'hq-backbone' ), false, 1 );
	did_action( 'init' ) && $scripts->localize( 'media-models', '_hqMediaModelsL10n', array(
		'settings' => array(
			'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
			'post' => array( 'id' => 0 ),
		),
	) );

	// To enqueue media-views or media-editor, call hq_enqueue_media().
	// Both rely on numerous settings, styles, and templates to operate correctly.
	$scripts->add( 'media-views',  "/hq-includes/js/media-views$suffix.js",  array( 'utils', 'media-models', 'hq-plupload', 'jquery-ui-sortable', 'hq-mediaelement' ), false, 1 );
	$scripts->add( 'media-editor', "/hq-includes/js/media-editor$suffix.js", array( 'shortcode', 'media-views' ), false, 1 );
	$scripts->add( 'media-audiovideo', "/hq-includes/js/media-audiovideo$suffix.js", array( 'media-editor' ), false, 1 );
	$scripts->add( 'mce-view', "/hq-includes/js/mce-view$suffix.js", array( 'shortcode', 'jquery', 'media-views', 'media-audiovideo' ), false, 1 );

	if ( is_admin() ) {
		$scripts->add( 'admin-tags', "/hq-admin/js/tags$suffix.js", array( 'jquery', 'hq-ajax-response' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'admin-tags', 'tagsl10n', array(
			'noPerm' => __('You do not have permission to do that.'),
			'broken' => __('An unidentified error has occurred.')
		));

		$scripts->add( 'admin-comments', "/hq-admin/js/edit-comments$suffix.js", array('hq-lists', 'quicktags', 'jquery-query'), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'admin-comments', 'adminCommentsL10n', array(
			'hotkeys_highlight_first' => isset($_GET['hotkeys_highlight_first']),
			'hotkeys_highlight_last' => isset($_GET['hotkeys_highlight_last']),
			'replyApprove' => __( 'Approve and Reply' ),
			'reply' => __( 'Reply' ),
			'warnQuickEdit' => __( "Are you sure you want to edit this comment?\nThe changes you made will be lost." ),
		) );

		$scripts->add( 'xfn', "/hq-admin/js/xfn$suffix.js", array('jquery'), false, 1 );

		$scripts->add( 'postbox', "/hq-admin/js/postbox$suffix.js", array('jquery-ui-sortable'), false, 1 );

		$scripts->add( 'tags-box', "/hq-admin/js/tags-box$suffix.js", array( 'jquery', 'suggest' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'tags-box', 'tagsBoxL10n', array(
			'tagDelimiter' => _x( ',', 'tag delimiter' ),
		) );

		$scripts->add( 'post', "/hq-admin/js/post$suffix.js", array( 'suggest', 'hq-lists', 'postbox', 'tags-box', 'underscore', 'word-count' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'post', 'postL10n', array(
			'ok' => __('OK'),
			'cancel' => __('Cancel'),
			'publishOn' => __('Publish on:'),
			'publishOnFuture' =>  __('Schedule for:'),
			'publishOnPast' => __('Published on:'),
			/* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
			'dateFormat' => __('%1$s %2$s, %3$s @ %4$s:%5$s'),
			'showcomm' => __('Show more comments'),
			'endcomm' => __('No more comments found.'),
			'publish' => __('Publish'),
			'schedule' => __('Schedule'),
			'update' => __('Update'),
			'savePending' => __('Save as Pending'),
			'saveDraft' => __('Save Draft'),
			'private' => __('Private'),
			'public' => __('Public'),
			'publicSticky' => __('Public, Sticky'),
			'password' => __('Password Protected'),
			'privatelyPublished' => __('Privately Published'),
			'published' => __('Published'),
			'saveAlert' => __('The changes you made will be lost if you navigate away from this page.'),
			'savingText' => __('Saving Draft&#8230;'),
		) );

		$scripts->add( 'press-this', "/hq-admin/js/press-this$suffix.js", array( 'jquery', 'tags-box' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'press-this', 'pressThisL10n', array(
			'newPost' => __( 'Title' ),
			'serverError' => __( 'Connection lost or the server is busy. Please try again later.' ),
			'saveAlert' => __( 'The changes you made will be lost if you navigate away from this page.' ),
			/* translators: %d: nth embed found in a post */
			'suggestedEmbedAlt' => __( 'Suggested embed #%d' ),
			/* translators: %d: nth image found in a post */
			'suggestedImgAlt' => __( 'Suggested image #%d' ),
		) );

		$scripts->add( 'editor-expand', "/hq-admin/js/editor-expand$suffix.js", array( 'jquery' ), false, 1 );

		$scripts->add( 'link', "/hq-admin/js/link$suffix.js", array( 'hq-lists', 'postbox' ), false, 1 );

		$scripts->add( 'comment', "/hq-admin/js/comment$suffix.js", array( 'jquery', 'postbox' ) );
		$scripts->add_data( 'comment', 'group', 1 );
		did_action( 'init' ) && $scripts->localize( 'comment', 'commentL10n', array(
			'submittedOn' => __( 'Submitted on:' ),
			/* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
			'dateFormat' => __( '%1$s %2$s, %3$s @ %4$s:%5$s' )
		) );

		$scripts->add( 'admin-gallery', "/hq-admin/js/gallery$suffix.js", array( 'jquery-ui-sortable' ) );

		$scripts->add( 'admin-widgets', "/hq-admin/js/widgets$suffix.js", array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), false, 1 );

		$scripts->add( 'theme', "/hq-admin/js/theme$suffix.js", array( 'hq-backbone', 'hq-a11y' ), false, 1 );

		$scripts->add( 'inline-edit-post', "/hq-admin/js/inline-edit-post$suffix.js", array( 'jquery', 'suggest' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'inline-edit-post', 'inlineEditL10n', array(
			'error' => __('Error while saving the changes.'),
			'ntdeltitle' => __('Remove From Bulk Edit'),
			'notitle' => __('(no title)'),
			'comma' => trim( _x( ',', 'tag delimiter' ) ),
		) );

		$scripts->add( 'inline-edit-tax', "/hq-admin/js/inline-edit-tax$suffix.js", array( 'jquery' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'inline-edit-tax', 'inlineEditL10n', array(
			'error' => __('Error while saving the changes.')
		) );

		$scripts->add( 'plugin-install', "/hq-admin/js/plugin-install$suffix.js", array( 'jquery', 'thickbox' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'plugin-install', 'plugininstallL10n', array(
			'plugin_information' => __('Plugin Information:'),
			'ays' => __('Are you sure you want to install this plugin?')
		) );

		$scripts->add( 'updates', "/hq-admin/js/updates$suffix.js", array( 'jquery', 'hq-util', 'hq-a11y' ) );
		did_action( 'init' ) && $scripts->localize( 'updates', '_hqUpdatesSettings', array(
			'ajax_nonce' => hq_create_nonce( 'updates' ),
			'l10n'       => array(
				'updating'          => __( 'Updating...' ),
				'updated'           => __( 'Updated!' ),
				/* translators: Error string for a failed update */
				'updateFailed'      => __( 'Update Failed: %s' ),
				/* translators: Plugin name and version */
				'updatingLabel'     => __( 'Updating %s...' ),
				/* translators: Plugin name and version */
				'updatedLabel'      => __( '%s updated!' ),
				/* translators: Plugin name and version */
				'updateFailedLabel' => __( '%s update failed' ),
				/* translators: JavaScript accessible string */
				'updatingMsg'       => __( 'Updating... please wait.' ),
				/* translators: JavaScript accessible string */
				'updatedMsg'        => __( 'Update completed successfully.' ),
				/* translators: JavaScript accessible string */
				'updateCancel'      => __( 'Update canceled.' ),
				'beforeunload'      => __( 'Plugin updates may not complete if you navigate away from this page.' ),
			)
		) );

		$scripts->add( 'farbtastic', '/hq-admin/js/farbtastic.js', array('jquery'), '1.2' );

		$scripts->add( 'iris', '/hq-admin/js/iris.min.js', array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), '1.0.7', 1 );
		$scripts->add( 'hq-color-picker', "/hq-admin/js/color-picker$suffix.js", array( 'iris' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'hq-color-picker', 'hqColorPickerL10n', array(
			'clear' => __( 'Clear' ),
			'defaultString' => __( 'Default' ),
			'pick' => __( 'Select Color' ),
			'current' => __( 'Current Color' ),
		) );

		$scripts->add( 'dashboard', "/hq-admin/js/dashboard$suffix.js", array( 'jquery', 'admin-comments', 'postbox' ), false, 1 );

		$scripts->add( 'list-revisions', "/hq-includes/js/hq-list-revisions$suffix.js" );

		$scripts->add( 'media-grid', "/hq-includes/js/media-grid$suffix.js", array( 'media-editor' ), false, 1 );
		$scripts->add( 'media', "/hq-admin/js/media$suffix.js", array( 'jquery' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'media', 'attachMediaBoxL10n', array(
			'error' => __( 'An error has occurred. Please reload the page and try again.' ),
		));

		$scripts->add( 'image-edit', "/hq-admin/js/image-edit$suffix.js", array('jquery', 'json2', 'imgareaselect'), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'image-edit', 'imageEditL10n', array(
			'error' => __( 'Could not load the preview image. Please reload the page and try again.' )
		));

		$scripts->add( 'set-post-thumbnail', "/hq-admin/js/set-post-thumbnail$suffix.js", array( 'jquery' ), false, 1 );
		did_action( 'init' ) && $scripts->localize( 'set-post-thumbnail', 'setPostThumbnailL10n', array(
			'setThumbnail' => __( 'Use as featured image' ),
			'saving' => __( 'Saving...' ),
			'error' => __( 'Could not set that as the thumbnail image. Try a different attachment.' ),
			'done' => __( 'Done' )
		) );

		// Navigation Menus
		$scripts->add( 'nav-menu', "/hq-admin/js/nav-menu$suffix.js", array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'hq-lists', 'postbox' ) );
		did_action( 'init' ) && $scripts->localize( 'nav-menu', 'navMenuL10n', array(
			'noResultsFound' => __( 'No results found.' ),
			'warnDeleteMenu' => __( "You are about to permanently delete this menu. \n 'Cancel' to stop, 'OK' to delete." ),
			'saveAlert' => __( 'The changes you made will be lost if you navigate away from this page.' ),
			'untitled' => _x( '(no label)', 'missing menu item navigation label' )
		) );

		$scripts->add( 'custom-header', "/hq-admin/js/custom-header.js", array( 'jquery-masonry' ), false, 1 );
		$scripts->add( 'custom-background', "/hq-admin/js/custom-background$suffix.js", array( 'hq-color-picker', 'media-views' ), false, 1 );
		$scripts->add( 'media-gallery', "/hq-admin/js/media-gallery$suffix.js", array('jquery'), false, 1 );

		$scripts->add( 'svg-painter', '/hq-admin/js/svg-painter.js', array( 'jquery' ), false, 1 );
	}
}

/**
 * Assign default styles to $styles object.
 *
 * Nothing is returned, because the $styles parameter is passed by reference.
 * Meaning that whatever object is passed will be updated without having to
 * reassign the variable that was passed back to the same value. This saves
 * memory.
 *
 * Adding default styles is not the only task, it also assigns the base_url
 * property, the default version, and text direction for the object.
 *
 * @since 0.0.1
 *
 * @param HQ_Styles $styles
 */
function hq_default_styles( &$styles ) {
	include( ABSPATH . HQINC . '/version.php' ); // include an unmodified $hq_version

	if ( ! defined( 'SCRIPT_DEBUG' ) )
		define( 'SCRIPT_DEBUG', false !== strpos( $hq_version, '-src' ) );

	if ( ! $guessurl = site_url() )
		$guessurl = hq_guess_url();

	$styles->base_url = $guessurl;
	$styles->content_url = defined('HQ_CONTENT_URL')? HQ_CONTENT_URL : '';
	$styles->default_version = get_bloginfo( 'version' );
	$styles->text_direction = function_exists( 'is_rtl' ) && is_rtl() ? 'rtl' : 'ltr';
	$styles->default_dirs = array('/hq-admin/', '/hq-includes/css/');

	$open_sans_font_url = '';

	/* translators: If there are characters in your language that are not supported
	 * by Open Sans, translate this to 'off'. Do not translate into your own language.
	 */
	if ( 'off' !== _x( 'on', 'Open Sans font: on or off' ) ) {
		$subsets = 'latin,latin-ext';

		/* translators: To add an additional Open Sans character subset specific to your language,
		 * translate this to 'greek', 'cyrillic' or 'vietnamese'. Do not translate into your own language.
		 */
		$subset = _x( 'no-subset', 'Open Sans font: add new subset (greek, cyrillic, vietnamese)' );

		if ( 'cyrillic' == $subset ) {
			$subsets .= ',cyrillic,cyrillic-ext';
		} elseif ( 'greek' == $subset ) {
			$subsets .= ',greek,greek-ext';
		} elseif ( 'vietnamese' == $subset ) {
			$subsets .= ',vietnamese';
		}

		// Hotlink Open Sans, for now
		$open_sans_font_url = "https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=$subsets";
	}

	// Register a stylesheet for the selected admin color scheme.
	$styles->add( 'colors', true, array( 'hq-admin', 'buttons', 'open-sans', 'dashicons' ) );

	$suffix = SCRIPT_DEBUG ? '' : '.min';

	// Admin CSS
	$styles->add( 'hq-admin',            "/hq-admin/css/hq-admin$suffix.css", array( 'open-sans', 'dashicons' ) );
	$styles->add( 'login',               "/hq-admin/css/login$suffix.css", array( 'buttons', 'open-sans', 'dashicons' ) );
	$styles->add( 'install',             "/hq-admin/css/install$suffix.css", array( 'buttons', 'open-sans' ) );
	$styles->add( 'hq-color-picker',     "/hq-admin/css/color-picker$suffix.css" );
	$styles->add( 'customize-controls',  "/hq-admin/css/customize-controls$suffix.css", array( 'hq-admin', 'colors', 'ie', 'imgareaselect' ) );
	$styles->add( 'customize-widgets',   "/hq-admin/css/customize-widgets$suffix.css", array( 'hq-admin', 'colors' ) );
	$styles->add( 'customize-nav-menus', "/hq-admin/css/customize-nav-menus$suffix.css", array( 'hq-admin', 'colors' ) );
	$styles->add( 'press-this',          "/hq-admin/css/press-this$suffix.css", array( 'open-sans', 'buttons' ) );

	$styles->add( 'ie', "/hq-admin/css/ie$suffix.css" );
	$styles->add_data( 'ie', 'conditional', 'lte IE 7' );

	// Common dependencies
	$styles->add( 'buttons',   "/hq-includes/css/buttons$suffix.css" );
	$styles->add( 'dashicons', "/hq-includes/css/dashicons$suffix.css" );
	$styles->add( 'open-sans', $open_sans_font_url );

	// Includes CSS
	$styles->add( 'admin-bar',         "/hq-includes/css/admin-bar$suffix.css", array( 'open-sans', 'dashicons' ) );
	$styles->add( 'hq-auth-check',     "/hq-includes/css/hq-auth-check$suffix.css", array( 'dashicons' ) );
	$styles->add( 'editor-buttons',    "/hq-includes/css/editor$suffix.css", array( 'dashicons' ) );
	$styles->add( 'media-views',       "/hq-includes/css/media-views$suffix.css", array( 'buttons', 'dashicons', 'hq-mediaelement' ) );
	$styles->add( 'hq-pointer',        "/hq-includes/css/hq-pointer$suffix.css", array( 'dashicons' ) );
	$styles->add( 'customize-preview', "/hq-includes/css/customize-preview$suffix.css" );

	// External libraries and friends
	$styles->add( 'imgareaselect',       '/hq-includes/js/imgareaselect/imgareaselect.css', array(), '0.9.8' );
	$styles->add( 'hq-jquery-ui-dialog', "/hq-includes/css/jquery-ui-dialog$suffix.css", array( 'dashicons' ) );
	$styles->add( 'mediaelement',        "/hq-includes/js/mediaelement/mediaelementplayer.min.css", array(), '2.17.0' );
	$styles->add( 'hq-mediaelement',     "/hq-includes/js/mediaelement/hq-mediaelement.css", array( 'mediaelement' ) );
	$styles->add( 'thickbox',            '/hq-includes/js/thickbox/thickbox.css', array( 'dashicons' ) );

	// Deprecated CSS
	$styles->add( 'media',      "/hq-admin/css/deprecated-media$suffix.css" );
	$styles->add( 'farbtastic', '/hq-admin/css/farbtastic.css', array(), '1.3u1' );
	$styles->add( 'jcrop',      "/hq-includes/js/jcrop/jquery.Jcrop.min.css", array(), '0.9.12' );
	$styles->add( 'colors-fresh', false, array( 'hq-admin', 'buttons' ) ); // Old handle.

	// RTL CSS
	$rtl_styles = array(
		// hq-admin
		'hq-admin', 'install', 'hq-color-picker', 'customize-controls', 'customize-widgets', 'customize-nav-menus', 'ie', 'login', 'press-this',
		// hq-includes
		'buttons', 'admin-bar', 'hq-auth-check', 'editor-buttons', 'media-views', 'hq-pointer',
		'hq-jquery-ui-dialog',
		// deprecated
		'media', 'farbtastic',
	);

	foreach ( $rtl_styles as $rtl_style ) {
		$styles->add_data( $rtl_style, 'rtl', 'replace' );
		if ( $suffix ) {
			$styles->add_data( $rtl_style, 'suffix', $suffix );
		}
	}
}

/**
 * Reorder JavaScript scripts array to place prototype before jQuery.
 *
 * @since 0.0.1
 *
 * @param array $js_array JavaScript scripts array
 * @return array Reordered array, if needed.
 */
function hq_prototype_before_jquery( $js_array ) {
	if ( false === $prototype = array_search( 'prototype', $js_array, true ) )
		return $js_array;

	if ( false === $jquery = array_search( 'jquery', $js_array, true ) )
		return $js_array;

	if ( $prototype < $jquery )
		return $js_array;

	unset($js_array[$prototype]);

	array_splice( $js_array, $jquery, 0, 'prototype' );

	return $js_array;
}

/**
 * Load localized data on print rather than initialization.
 *
 * These localizations require information that may not be loaded even by init.
 *
 * @since 0.0.1
 */
function hq_just_in_time_script_localization() {

	hq_localize_script( 'autosave', 'autosaveL10n', array(
		'autosaveInterval' => AUTOSAVE_INTERVAL,
		'blog_id' => get_current_blog_id(),
	) );

}

/**
 * Administration Screen CSS for changing the styles.
 *
 * If installing the 'hq-admin/' directory will be replaced with './'.
 *
 * The $_hq_admin_css_colors global manages the Administration Screens CSS
 * stylesheet that is loaded. The option that is set is 'admin_color' and is the
 * color and key for the array. The value for the color key is an object with
 * a 'url' parameter that has the URL path to the CSS file.
 *
 * The query from $src parameter will be appended to the URL that is given from
 * the $_hq_admin_css_colors array value URL.
 *
 * @since 0.0.1
 * @global array $_hq_admin_css_colors
 *
 * @param string $src    Source URL.
 * @param string $handle Either 'colors' or 'colors-rtl'.
 * @return string|false URL path to CSS stylesheet for Administration Screens.
 */
function hq_style_loader_src( $src, $handle ) {
	global $_hq_admin_css_colors;

	if ( defined('HQ_INSTALLING') )
		return preg_replace( '#^hq-admin/#', './', $src );

	if ( 'colors' == $handle ) {
		$color = get_user_option('admin_color');

		if ( empty($color) || !isset($_hq_admin_css_colors[$color]) )
			$color = 'fresh';

		$color = $_hq_admin_css_colors[$color];
		$parsed = parse_url( $src );
		$url = $color->url;

		if ( ! $url ) {
			return false;
		}

		if ( isset($parsed['query']) && $parsed['query'] ) {
			hq_parse_str( $parsed['query'], $qv );
			$url = add_query_arg( $qv, $url );
		}

		return $url;
	}

	return $src;
}

/**
 * Prints the script queue in the HTML head on admin pages.
 *
 * Postpones the scripts that were queued for the footer.
 * print_footer_scripts() is called in the footer to print these scripts.
 *
 * @since 0.0.1
 *
 * @see hq_print_scripts()
 *
 * @global bool $concatenate_scripts
 *
 * @return array
 */
function print_head_scripts() {
	global $concatenate_scripts;

	if ( ! did_action('hq_print_scripts') ) {
		/** This action is documented in hq-includes/functions.hq-scripts.php */
		do_action( 'hq_print_scripts' );
	}

	$hq_scripts = hq_scripts();

	script_concat_settings();
	$hq_scripts->do_concat = $concatenate_scripts;
	$hq_scripts->do_head_items();

	/**
	 * Filter whether to print the head scripts.
	 *
	 * @since 0.0.1
	 *
	 * @param bool $print Whether to print the head scripts. Default true.
	 */
	if ( apply_filters( 'print_head_scripts', true ) ) {
		_print_scripts();
	}

	$hq_scripts->reset();
	return $hq_scripts->done;
}

/**
 * Prints the scripts that were queued for the footer or too late for the HTML head.
 *
 * @since 0.0.1
 *
 * @global HQ_Scripts $hq_scripts
 * @global bool       $concatenate_scripts
 *
 * @return array
 */
function print_footer_scripts() {
	global $hq_scripts, $concatenate_scripts;

	if ( ! ( $hq_scripts instanceof HQ_Scripts ) ) {
		return array(); // No need to run if not instantiated.
	}
	script_concat_settings();
	$hq_scripts->do_concat = $concatenate_scripts;
	$hq_scripts->do_footer_items();

	/**
	 * Filter whether to print the footer scripts.
	 *
	 * @since 0.0.1
	 *
	 * @param bool $print Whether to print the footer scripts. Default true.
	 */
	if ( apply_filters( 'print_footer_scripts', true ) ) {
		_print_scripts();
	}

	$hq_scripts->reset();
	return $hq_scripts->done;
}

/**
 * Print scripts (internal use only)
 *
 * @ignore
 *
 * @global HQ_Scripts $hq_scripts
 * @global bool       $compress_scripts
 */
function _print_scripts() {
	global $hq_scripts, $compress_scripts;

	$zip = $compress_scripts ? 1 : 0;
	if ( $zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP )
		$zip = 'gzip';

	if ( $concat = trim( $hq_scripts->concat, ', ' ) ) {

		if ( !empty($hq_scripts->print_code) ) {
			echo "\n<script type='text/javascript'>\n";
			echo "/* <![CDATA[ */\n"; // not needed in HTML 5
			echo $hq_scripts->print_code;
			echo "/* ]]> */\n";
			echo "</script>\n";
		}

		$concat = str_split( $concat, 128 );
		$concat = 'load%5B%5D=' . implode( '&load%5B%5D=', $concat );

		$src = $hq_scripts->base_url . "/hq-admin/load-scripts.php?c={$zip}&" . $concat . '&ver=' . $hq_scripts->default_version;
		echo "<script type='text/javascript' src='" . esc_attr($src) . "'></script>\n";
	}

	if ( !empty($hq_scripts->print_html) )
		echo $hq_scripts->print_html;
}

/**
 * Prints the script queue in the HTML head on the front end.
 *
 * Postpones the scripts that were queued for the footer.
 * hq_print_footer_scripts() is called in the footer to print these scripts.
 *
 * @since 0.0.1
 *
 * @global HQ_Scripts $hq_scripts
 *
 * @return array
 */
function hq_print_head_scripts() {
	if ( ! did_action('hq_print_scripts') ) {
		/** This action is documented in hq-includes/functions.hq-scripts.php */
		do_action( 'hq_print_scripts' );
	}

	global $hq_scripts;

	if ( ! ( $hq_scripts instanceof HQ_Scripts ) ) {
		return array(); // no need to run if nothing is queued
	}
	return print_head_scripts();
}

/**
 * Private, for use in *_footer_scripts hooks
 *
 * @since 0.0.1
 */
function _hq_footer_scripts() {
	print_late_styles();
	print_footer_scripts();
}

/**
 * Hooks to print the scripts and styles in the footer.
 *
 * @since 0.0.1
 */
function hq_print_footer_scripts() {
	/**
	 * Fires when footer scripts are printed.
	 *
	 * @since 0.0.1
	 */
	do_action( 'hq_print_footer_scripts' );
}

/**
 * Wrapper for do_action('hq_enqueue_scripts')
 *
 * Allows plugins to queue scripts for the front end using hq_enqueue_script().
 * Runs first in hq_head() where all is_home(), is_page(), etc. functions are available.
 *
 * @since 0.0.1
 */
function hq_enqueue_scripts() {
	/**
	 * Fires when scripts and styles are enqueued.
	 *
	 * @since 0.0.1
	 */
	do_action( 'hq_enqueue_scripts' );
}

/**
 * Prints the styles queue in the HTML head on admin pages.
 *
 * @since 0.0.1
 *
 * @global bool $concatenate_scripts
 *
 * @return array
 */
function print_admin_styles() {
	global $concatenate_scripts;

	$hq_styles = hq_styles();

	script_concat_settings();
	$hq_styles->do_concat = $concatenate_scripts;
	$hq_styles->do_items(false);

	/**
	 * Filter whether to print the admin styles.
	 *
	 * @since 0.0.1
	 *
	 * @param bool $print Whether to print the admin styles. Default true.
	 */
	if ( apply_filters( 'print_admin_styles', true ) ) {
		_print_styles();
	}

	$hq_styles->reset();
	return $hq_styles->done;
}

/**
 * Prints the styles that were queued too late for the HTML head.
 *
 * @since 0.0.1
 *
 * @global HQ_Styles $hq_styles
 * @global bool      $concatenate_scripts
 *
 * @return array|void
 */
function print_late_styles() {
	global $hq_styles, $concatenate_scripts;

	if ( ! ( $hq_styles instanceof HQ_Styles ) ) {
		return;
	}

	$hq_styles->do_concat = $concatenate_scripts;
	$hq_styles->do_footer_items();

	/**
	 * Filter whether to print the styles queued too late for the HTML head.
	 *
	 * @since 0.0.1
	 *
	 * @param bool $print Whether to print the 'late' styles. Default true.
	 */
	if ( apply_filters( 'print_late_styles', true ) ) {
		_print_styles();
	}

	$hq_styles->reset();
	return $hq_styles->done;
}

/**
 * Print styles (internal use only)
 *
 * @ignore
 *
 * @global bool $compress_css
 */
function _print_styles() {
	global $compress_css;

	$hq_styles = hq_styles();

	$zip = $compress_css ? 1 : 0;
	if ( $zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP )
		$zip = 'gzip';

	if ( !empty($hq_styles->concat) ) {
		$dir = $hq_styles->text_direction;
		$ver = $hq_styles->default_version;
		$href = $hq_styles->base_url . "/hq-admin/load-styles.php?c={$zip}&dir={$dir}&load=" . trim($hq_styles->concat, ', ') . '&ver=' . $ver;
		echo "<link rel='stylesheet' href='" . esc_attr($href) . "' type='text/css' media='all' />\n";

		if ( !empty($hq_styles->print_code) ) {
			echo "<style type='text/css'>\n";
			echo $hq_styles->print_code;
			echo "\n</style>\n";
		}
	}

	if ( !empty($hq_styles->print_html) )
		echo $hq_styles->print_html;
}

/**
 * Determine the concatenation and compression settings for scripts and styles.
 *
 * @since 0.0.1
 *
 * @global bool $concatenate_scripts
 * @global bool $compress_scripts
 * @global bool $compress_css
 */
function script_concat_settings() {
	global $concatenate_scripts, $compress_scripts, $compress_css;

	$compressed_output = ( ini_get('zlib.output_compression') || 'ob_gzhandler' == ini_get('output_handler') );

	if ( ! isset($concatenate_scripts) ) {
		$concatenate_scripts = defined('CONCATENATE_SCRIPTS') ? CONCATENATE_SCRIPTS : true;
		if ( ! is_admin() || ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) )
			$concatenate_scripts = false;
	}

	if ( ! isset($compress_scripts) ) {
		$compress_scripts = defined('COMPRESS_SCRIPTS') ? COMPRESS_SCRIPTS : true;
		if ( $compress_scripts && ( ! get_site_option('can_compress_scripts') || $compressed_output ) )
			$compress_scripts = false;
	}

	if ( ! isset($compress_css) ) {
		$compress_css = defined('COMPRESS_CSS') ? COMPRESS_CSS : true;
		if ( $compress_css && ( ! get_site_option('can_compress_scripts') || $compressed_output ) )
			$compress_css = false;
	}
}
