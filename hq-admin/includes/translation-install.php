<?php
/**
 * HiveQueen Translation Install Administration API
 *
 * @package HiveQueen
 * @subpackage Administration
 */


/**
 * Retrieve translations from HiveQueen Translation API.
 *
 * @since 0.0.1
 *
 * @param string       $type Type of translations. Accepts 'plugins', 'themes', 'core'.
 * @param array|object $args Translation API arguments. Optional.
 * @return object|HQ_Error On success an object of translations, HQ_Error on failure.
 */
function translations_api( $type, $args = null ) {
	include( ABSPATH . HQINC . '/version.php' ); // include an unmodified $hq_version

	if ( ! in_array( $type, array( 'plugins', 'themes', 'core' ) ) ) {
		return	new HQ_Error( 'invalid_type', __( 'Invalid translation type.' ) );
	}

	/**
	 * Allows a plugin to override the HiveQueen.org Translation Install API entirely.
	 *
	 * @since 0.0.1
	 *
	 * @param bool|array  $result The result object. Default false.
	 * @param string      $type   The type of translations being requested.
	 * @param object      $args   Translation API arguments.
	 */
	$res = apply_filters( 'translations_api', false, $type, $args );

	if ( false === $res ) {
		$url = $http_url = 'http://api.wordpress.org/translations/' . $type . '/1.0/';
		if ( $ssl = hq_http_supports( array( 'ssl' ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}

		$options = array(
			'timeout' => 3,
			'body' => array(
				'hq_version' => $hq_version,
				'locale'     => get_locale(),
				'version'    => $args['version'], // Version of plugin, theme or core
			),
		);

		if ( 'core' !== $type ) {
			$options['body']['slug'] = $args['slug']; // Plugin or theme slug
		}

		$request = hq_remote_post( $url, $options );

		if ( $ssl && is_hq_error( $request ) ) {
			trigger_error( __( 'An unexpected error occurred. Something may be wrong with HiveQueen.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.' ) . ' ' . __( '(HiveQueen could not establish a secure connection to HiveQueen.org. Please contact your server administrator.)' ), headers_sent() || HQ_DEBUG ? E_USER_WARNING : E_USER_NOTICE );

			$request = hq_remote_post( $http_url, $options );
		}

		if ( is_hq_error( $request ) ) {
			$res = new HQ_Error( 'translations_api_failed', __( 'An unexpected error occurred. Something may be wrong with HiveQueen.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.' ), $request->get_error_message() );
		} else {
			$res = json_decode( hq_remote_retrieve_body( $request ), true );
			if ( ! is_object( $res ) && ! is_array( $res ) ) {
				$res = new HQ_Error( 'translations_api_failed', __( 'An unexpected error occurred. Something may be wrong with HiveQueen.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.' ), hq_remote_retrieve_body( $request ) );
			}
		}
	}

	/**
	 * Filter the Translation Install API response results.
	 *
	 * @since 0.0.1
	 *
	 * @param object|HQ_Error $res  Response object or HQ_Error.
	 * @param string          $type The type of translations being requested.
	 * @param object          $args Translation API arguments.
	 */
	return apply_filters( 'translations_api_result', $res, $type, $args );
}

/**
 * Get available translations from the HiveQueen.org API.
 *
 * @since 0.0.1
 *
 * @see translations_api()
 *
 * @return array Array of translations, each an array of data. If the API response results
 *               in an error, an empty array will be returned.
 */
function hq_get_available_translations() {
	if ( ! defined( 'HQ_INSTALLING' ) && false !== ( $translations = get_site_transient( 'available_translations' ) ) ) {
		return $translations;
	}

	include( ABSPATH . HQINC . '/version.php' ); // include an unmodified $hq_version

	$api = translations_api( 'core', array( 'version' => $hq_version ) );

	if ( is_hq_error( $api ) || empty( $api['translations'] ) ) {
		return array();
	}

	$translations = array();
	// Key the array with the language code for now.
	foreach ( $api['translations'] as $translation ) {
		$translations[ $translation['language'] ] = $translation;
	}

	if ( ! defined( 'HQ_INSTALLING' ) ) {
		set_site_transient( 'available_translations', $translations, 3 * HOUR_IN_SECONDS );
	}

	return $translations;
}

/**
 * Output the select form for the language selection on the installation screen.
 *
 * @since 0.0.1
 *
 * @global string $hq_local_package
 *
 * @param array $languages Array of available languages (populated via the Translation API).
 */
function hq_install_language_form( $languages ) {
	global $hq_local_package;

	$installed_languages = get_available_languages();

	echo "<label class='screen-reader-text' for='language'>Select a default language</label>\n";
	echo "<select size='14' name='language' id='language'>\n";
	echo '<option value="" lang="en" selected="selected" data-continue="Continue" data-installed="1">English (United States)</option>';
	echo "\n";

	if ( ! empty( $hq_local_package ) && isset( $languages[ $hq_local_package ] ) ) {
		if ( isset( $languages[ $hq_local_package ] ) ) {
			$language = $languages[ $hq_local_package ];
			printf( '<option value="%s" lang="%s" data-continue="%s"%s>%s</option>' . "\n",
				esc_attr( $language['language'] ),
				esc_attr( current( $language['iso'] ) ),
				esc_attr( $language['strings']['continue'] ),
				in_array( $language['language'], $installed_languages ) ? ' data-installed="1"' : '',
				esc_html( $language['native_name'] ) );

			unset( $languages[ $hq_local_package ] );
		}
	}

	foreach ( $languages as $language ) {
		printf( '<option value="%s" lang="%s" data-continue="%s"%s>%s</option>' . "\n",
			esc_attr( $language['language'] ),
			esc_attr( current( $language['iso'] ) ),
			esc_attr( $language['strings']['continue'] ),
			in_array( $language['language'], $installed_languages ) ? ' data-installed="1"' : '',
			esc_html( $language['native_name'] ) );
	}
	echo "</select>\n";
	echo '<p class="step"><span class="spinner"></span><input id="language-continue" type="submit" class="button button-primary button-large" value="Continue" /></p>';
}

/**
 * Download a language pack.
 *
 * @since 0.0.1
 *
 * @see hq_get_available_translations()
 *
 * @param string $download Language code to download.
 * @return string|bool Returns the language code if successfully downloaded
 *                     (or already installed), or false on failure.
 */
function hq_download_language_pack( $download ) {
	// Check if the translation is already installed.
	if ( in_array( $download, get_available_languages() ) ) {
		return $download;
	}

	if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
		return false;
	}

	// Confirm the translation is one we can download.
	$translations = hq_get_available_translations();
	if ( ! $translations ) {
		return false;
	}
	foreach ( $translations as $translation ) {
		if ( $translation['language'] === $download ) {
			$translation_to_load = true;
			break;
		}
	}

	if ( empty( $translation_to_load ) ) {
		return false;
	}
	$translation = (object) $translation;

	require_once ABSPATH . 'hq-admin/includes/class-hq-upgrader.php';
	$skin = new Automatic_Upgrader_Skin;
	$upgrader = new Language_Pack_Upgrader( $skin );
	$translation->type = 'core';
	$result = $upgrader->upgrade( $translation, array( 'clear_update_cache' => false ) );

	if ( ! $result || is_hq_error( $result ) ) {
		return false;
	}

	return $translation->language;
}

/**
 * Check if HiveQueen has access to the filesystem without asking for
 * credentials.
 *
 * @since 0.0.1
 *
 * @return bool Returns true on success, false on failure.
 */
function hq_can_install_language_pack() {
	if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
		return false;
	}

	require_once ABSPATH . 'hq-admin/includes/class-hq-upgrader.php';
	$skin = new Automatic_Upgrader_Skin;
	$upgrader = new Language_Pack_Upgrader( $skin );
	$upgrader->init();

	$check = $upgrader->fs_connect( array( HQ_CONTENT_DIR, HQ_LANG_DIR ) );

	if ( ! $check || is_hq_error( $check ) ) {
		return false;
	}

	return true;
}
