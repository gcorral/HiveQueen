<?php
/**
 * HiveQueen Administration Importer API.
 *
 * @package HiveQueen
 * @subpackage Administration
 */

/**
 * Retrieve list of importers.
 *
 * @since 0.0.1
 *
 * @global array $hq_importers
 * @return array
 */
function get_importers() {
	global $hq_importers;
	if ( is_array( $hq_importers ) ) {
		uasort( $hq_importers, '_usort_by_first_member' );
	}
	return $hq_importers;
}

/**
 * Sorts a multidimensional array by first member of each top level member
 *
 * Used by uasort() as a callback, should not be used directly.
 *
 * @since 0.0.1
 * @access private
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function _usort_by_first_member( $a, $b ) {
	return strnatcasecmp( $a[0], $b[0] );
}

/**
 * Register importer for HiveQueen.
 *
 * @since 0.0.1
 *
 * @global array $hq_importers
 *
 * @param string   $id          Importer tag. Used to uniquely identify importer.
 * @param string   $name        Importer name and title.
 * @param string   $description Importer description.
 * @param callback $callback    Callback to run.
 * @return HQ_Error Returns HQ_Error when $callback is HQ_Error.
 */
function register_importer( $id, $name, $description, $callback ) {
	global $hq_importers;
	if ( is_hq_error( $callback ) )
		return $callback;
	$hq_importers[$id] = array ( $name, $description, $callback );
}

/**
 * Cleanup importer.
 *
 * Removes attachment based on ID.
 *
 * @since 0.0.1
 *
 * @param string $id Importer ID.
 */
function hq_import_cleanup( $id ) {
	hq_delete_attachment( $id );
}

/**
 * Handle importer uploading and add attachment.
 *
 * @since 0.0.1
 *
 * @return array Uploaded file's details on success, error message on failure
 */
function hq_import_handle_upload() {
	if ( ! isset( $_FILES['import'] ) ) {
		return array(
			'error' => __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.' )
		);
	}

	$overrides = array( 'test_form' => false, 'test_type' => false );
	$_FILES['import']['name'] .= '.txt';
	$upload = hq_handle_upload( $_FILES['import'], $overrides );

	if ( isset( $upload['error'] ) ) {
		return $upload;
	}

	// Construct the object array
	$object = array(
		'post_title' => basename( $upload['file'] ),
		'post_content' => $upload['url'],
		'post_mime_type' => $upload['type'],
		'guid' => $upload['url'],
		'context' => 'import',
		'post_status' => 'private'
	);

	// Save the data
	$id = hq_insert_attachment( $object, $upload['file'] );

	/*
	 * Schedule a cleanup for one day from now in case of failed
	 * import or missing hq_import_cleanup() call.
	 */
	hq_schedule_single_event( time() + DAY_IN_SECONDS, 'importer_scheduled_cleanup', array( $id ) );

	return array( 'file' => $upload['file'], 'id' => $id );
}

/**
 * Returns a list from HiveQueen.org of popular importer plugins.
 *
 * @since 0.0.1
 *
 * @return array Importers with metadata for each.
 */
function hq_get_popular_importers() {
	include( ABSPATH . HQINC . '/version.php' ); // include an unmodified $hq_version

	$locale = get_locale();
	$popular_importers = get_site_transient( 'popular_importers_' . $locale );

	if ( ! $popular_importers ) {
		$url = add_query_arg( 'locale', get_locale(), 'http://api.wordpress.org/core/importers/1.1/' );
		$options = array( 'user-agent' => 'HiveQueen/' . $hq_version . '; ' . home_url() );
		$response = hq_remote_get( $url, $options );
		$popular_importers = json_decode( hq_remote_retrieve_body( $response ), true );

		if ( is_array( $popular_importers ) )
			set_site_transient( 'popular_importers_' . $locale, $popular_importers, 2 * DAY_IN_SECONDS );
		else
			$popular_importers = false;
	}

	if ( is_array( $popular_importers ) ) {
		// If the data was received as translated, return it as-is.
		if ( $popular_importers['translated'] )
			return $popular_importers['importers'];

		foreach ( $popular_importers['importers'] as &$importer ) {
			$importer['description'] = translate( $importer['description'] );
			if ( $importer['name'] != 'HiveQueen' )
				$importer['name'] = translate( $importer['name'] );
		}
		return $popular_importers['importers'];
	}

	return array(
		// slug => name, description, plugin slug, and register_importer() slug
		'blogger' => array(
			'name' => __( 'Blogger' ),
			'description' => __( 'Install the Blogger importer to import posts, comments, and users from a Blogger blog.' ),
			'plugin-slug' => 'blogger-importer',
			'importer-id' => 'blogger',
		),
		'hqcat2tag' => array(
			'name' => __( 'Categories and Tags Converter' ),
			'description' => __( 'Install the category/tag converter to convert existing categories to tags or tags to categories, selectively.' ),
			'plugin-slug' => 'hqcat2tag-importer',
			'importer-id' => 'hq-cat2tag',
		),
		'livejournal' => array(
			'name' => __( 'LiveJournal' ),
			'description' => __( 'Install the LiveJournal importer to import posts from LiveJournal using their API.' ),
			'plugin-slug' => 'livejournal-importer',
			'importer-id' => 'livejournal',
		),
		'movabletype' => array(
			'name' => __( 'Movable Type and TypePad' ),
			'description' => __( 'Install the Movable Type importer to import posts and comments from a Movable Type or TypePad blog.' ),
			'plugin-slug' => 'movabletype-importer',
			'importer-id' => 'mt',
		),
		'opml' => array(
			'name' => __( 'Blogroll' ),
			'description' => __( 'Install the blogroll importer to import links in OPML format.' ),
			'plugin-slug' => 'opml-importer',
			'importer-id' => 'opml',
		),
		'rss' => array(
			'name' => __( 'RSS' ),
			'description' => __( 'Install the RSS importer to import posts from an RSS feed.' ),
			'plugin-slug' => 'rss-importer',
			'importer-id' => 'rss',
		),
		'tumblr' => array(
			'name' => __( 'Tumblr' ),
			'description' => __( 'Install the Tumblr importer to import posts &amp; media from Tumblr using their API.' ),
			'plugin-slug' => 'tumblr-importer',
			'importer-id' => 'tumblr',
		),
		'hivequeen' => array(
			'name' => 'HiveQueen',
			'description' => __( 'Install the HiveQueen importer to import posts, pages, comments, custom fields, categories, and tags from a HiveQueen export file.' ),
			'plugin-slug' => 'wordpress-importer',
			'importer-id' => 'wordpress',
		),
	);
}
