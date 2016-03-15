<?php
/**
 * Loads the correct template based on the visitor's url
 * @package HiveQueen
 */


if ( defined('HQ_USE_THEMES') && HQ_USE_THEMES )
	/**
	 * Fires before determining which template to load.
	 *
	 * @since 0.0.1
	 */
	do_action( 'template_redirect' );


/**
 * Filter whether to allow 'HEAD' requests to generate content.
 *
 * Provides a significant performance bump by exiting before the page
 * content loads for 'HEAD' requests. See #14348.
 *
 * @since 0.0.1
 *
 * @param bool $exit Whether to exit without generating any content for 'HEAD' requests. Default true.
 */
if ( 'HEAD' === $_SERVER['REQUEST_METHOD'] && apply_filters( 'exit_on_http_head', true ) )
	exit();


// Process feeds and trackbacks even if not using themes.
if ( is_robots() ) :
	/**
	 * Fired when the template loader determines a robots.txt request.
	 *
	 * @since 0.0.1
	 */
	do_action( 'do_robots' );
	return;
elseif ( is_feed() ) :
	do_feed();
	return;
elseif ( is_trackback() ) :
	include( ABSPATH . 'hq-trackback.php' );
	return;
endif;

if ( defined('HQ_USE_THEMES') && HQ_USE_THEMES ) :
        
        //print("Llega Aqui ====>");

	$template = false;
	if     ( is_404()            && $template = get_404_template()            ) :
	elseif ( is_search()         && $template = get_search_template()         ) :
	elseif ( is_front_page()     && $template = get_front_page_template()     ) :
	elseif ( is_home()           && $template = get_home_template()           ) :
        //TODO: Goyo 
	//elseif ( is_post_type_archive() && $template = get_post_type_archive_template() ) :
	//elseif ( is_tax()            && $template = get_taxonomy_template()       ) :
	//elseif ( is_attachment()     && $template = get_attachment_template()     ) :
		remove_filter('the_content', 'prepend_attachment');
	elseif ( is_single()         && $template = get_single_template()         ) :
	elseif ( is_page()           && $template = get_page_template()           ) :
	elseif ( is_singular()       && $template = get_singular_template()       ) :
	elseif ( is_category()       && $template = get_category_template()       ) :
	elseif ( is_tag()            && $template = get_tag_template()            ) :
	elseif ( is_author()         && $template = get_author_template()         ) :
        //TODO: Goyo
	//elseif ( is_date()           && $template = get_date_template()           ) :
	//elseif ( is_archive()        && $template = get_archive_template()        ) :
	//elseif ( is_comments_popup() && $template = get_comments_popup_template() ) :
	elseif ( is_paged()          && $template = get_paged_template()          ) :
	else :
		$template = get_index_template();
	endif;
	/**
	 * Filter the path of the current template before including it.
	 *
	 * @since 0.0.1
	 *
	 * @param string $template The path of the template to include.
	 */
	if ( $template = apply_filters( 'template_include', $template ) )
		include( $template );
	return;
endif;
