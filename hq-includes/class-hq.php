<?php
/**
 * HiveQueen environment setup class.
 *
 * @package HiveQueen
 * @since 0.0.1
 */
class HQ {
        /**
         * Public query variables.
         *
         * Long list of public query variables.
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
         //TODO: Fix
         //public $public_query_vars = array('m', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's', 'search', 'exact', 'sentence', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 'author', 'order', 'orderby', 'year', 'monthnum', 'day', 'hour', 'minute', 'second', 'name', 'category_name', 'tag', 'feed', 'author_name', 'static', 'pagename', 'page_id', 'error', 'comments_popup', 'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots', 'taxonomy', 'term', 'cpage', 'post_type');
         public $public_query_vars = array('m', 'p', 'posts', 'w', 'cat', 's', 'search', 'exact', 'sentence', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 'author', 'order', 'orderby', 'year', 'monthnum', 'day', 'hour', 'minute', 'second', 'name', 'category_name', 'tag', 'feed', 'author_name', 'static', 'pagename', 'page_id', 'error', 'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots', 'taxonomy', 'term', 'cpage', 'post_type');

        /**
         * Private query variables.
         *
         * Long list of private query variables.
         *
         * @since 0.0.1
         * @var array
         */
         //TODO: Fix
         //public $private_query_vars = array( 'offset', 'posts_per_page', 'posts_per_archive_page', 'showposts', 'nopaging', 'post_type', 'post_status', 'category__in', 'category__not_in', 'category__and', 'tag__in', 'tag__not_in', 'tag__and', 'tag_slug__in', 'tag_slug__and', 'tag_id', 'post_mime_type', 'perm', comments_per_page', 'post__in', 'post__not_in', 'post_parent', 'post_parent__in', 'post_parent__not_in' );
         public $private_query_vars = array( 'offset', 'posts_per_page', 'posts_per_archive_page', 'showposts', 'nopaging', 'post_type', 'post_status', 'category__in', 'category__not_in', 'category__and', 'tag__in', 'tag__not_in', 'tag__and', 'tag_slug__in', 'tag_slug__and', 'tag_id', 'post_mime_type', 'perm', 'post__in', 'post__not_in', 'post_parent', 'post_parent__in', 'post_parent__not_in' );

        /**
         * Extra query variables set by the user.
         *
         * @since 0.0.1
         * @var array
         */
        public $extra_query_vars = array();

        /**
         * Query variables for setting up the HiveQueen Query Loop.
         *
         * @since 0.0.1
         * @var array
         */
        public $query_vars;

        /**
         * String parsed to set the query variables.
         *
         * @since 0.0.1
         * @var string
         */
        public $query_string;

        /**
         * Permalink or requested URI.
         *
         * @since 0.0.1
         * @var string
         */
        public $request;

        /**
         * Rewrite rule the request matched.
         *
         * @since 0.0.1
         * @var string
         */
        public $matched_rule;

        /**
         * Rewrite query the request matched.
         *
         * @since 0.0.1
         * @var string
         */
        public $matched_query;

        /**
         * Whether already did the permalink.
         *
         * @since 0.0.1
         * @var bool
         */
        public $did_permalink = false;

        /**
         * Add name to list of public query variables.
         *
         * @since 0.0.1
         *
         * @param string $qv Query variable name.
         */
        public function add_query_var($qv) {
                if ( !in_array($qv, $this->public_query_vars) )
                        $this->public_query_vars[] = $qv;
        }

        /**
         * Set the value of a query variable.
         *
         * @since 0.0.1
         *
         * @param string $key Query variable name.
         * @param mixed $value Query variable value.
         */
        public function set_query_var($key, $value) {
                $this->query_vars[$key] = $value;
        }
        /**
         * Parse request to find correct WordPress query.
         *
         * Sets up the query variables based on the request. There are also many
         * filters and actions that can be used to further manipulate the result.
         *
         * @since 0.0.1
         *
         * @global HQ_Rewrite $hq_rewrite
         *
         * @param array|string $extra_query_vars Set the extra query variables.
         */
        public function parse_request($extra_query_vars = '') {
                global $hq_rewrite;

                /**
                 * Filter whether to parse the request.
                 *
                 * @since 0.0.1
                 *
                 * @param bool         $bool             Whether or not to parse the request. Default true.
                 * @param HQ           $this             Current WordPress environment instance.
                 * @param array|string $extra_query_vars Extra passed query variables.
                 */
                if ( ! apply_filters( 'do_parse_request', true, $this, $extra_query_vars ) )
                        return;

                $this->query_vars = array();
                $post_type_query_vars = array();

                if ( is_array( $extra_query_vars ) ) {
                        $this->extra_query_vars = & $extra_query_vars;
                } elseif ( ! empty( $extra_query_vars ) ) {
                        parse_str( $extra_query_vars, $this->extra_query_vars );
                }
                // Process PATH_INFO, REQUEST_URI, and 404 for permalinks.

                // Fetch the rewrite rules.
                $rewrite = $hq_rewrite->hq_rewrite_rules();

                if ( ! empty($rewrite) ) {
                        // If we match a rewrite rule, this will be cleared.
                        $error = '404';
                        $this->did_permalink = true;

                        $pathinfo = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
                        list( $pathinfo ) = explode( '?', $pathinfo );
                        $pathinfo = str_replace( "%", "%25", $pathinfo );

                        list( $req_uri ) = explode( '?', $_SERVER['REQUEST_URI'] );
                        $self = $_SERVER['PHP_SELF'];
                        $home_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
                        $home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

                        // Trim path info from the end and the leading home path from the
                        // front. For path info requests, this leaves us with the requesting
                        // filename, if any. For 404 requests, this leaves us with the
                        // requested permalink.
                        $req_uri = str_replace($pathinfo, '', $req_uri);
                        $req_uri = trim($req_uri, '/');
                        $req_uri = preg_replace( $home_path_regex, '', $req_uri );
                        $req_uri = trim($req_uri, '/');
                        $pathinfo = trim($pathinfo, '/');
                        $pathinfo = preg_replace( $home_path_regex, '', $pathinfo );
                        $pathinfo = trim($pathinfo, '/');
                        $self = trim($self, '/');
                        $self = preg_replace( $home_path_regex, '', $self );
                        $self = trim($self, '/');

                        // The requested permalink is in $pathinfo for path info requests and
                        //  $req_uri for other requests.
                        if ( ! empty($pathinfo) && !preg_match('|^.*' . $hq_rewrite->index . '$|', $pathinfo) ) {
                                $request = $pathinfo;
                        } else {
                                // If the request uri is the index, blank it out so that we don't try to match it against a rule.
                                if ( $req_uri == $hq_rewrite->index )
                                        $req_uri = '';
                                $request = $req_uri;
                        }

                        $this->request = $request;

                        // Look for matches.
                        $request_match = $request;
                        if ( empty( $request_match ) ) {
                                // An empty request could only match against ^$ regex
                                if ( isset( $rewrite['$'] ) ) {
                                        $this->matched_rule = '$';
                                        $query = $rewrite['$'];
                                        $matches = array('');
                                }
                        } else {
                                foreach ( (array) $rewrite as $match => $query ) {
                                        // If the requesting file is the anchor of the match, prepend it to the path info.
                                        if ( ! empty($req_uri) && strpos($match, $req_uri) === 0 && $req_uri != $request )
                                                $request_match = $req_uri . '/' . $request;

                                        if ( preg_match("#^$match#", $request_match, $matches) ||
                                                preg_match("#^$match#", urldecode($request_match), $matches) ) {
                                                if ( $hq_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
                                                        // This is a verbose page match, let's check to be sure about it.
                                                        if ( ! get_page_by_path( $matches[ $varmatch[1] ] ) )
                                                                continue;
                                                }

                                                // Got a match.
                                                $this->matched_rule = $match;
                                                break;
                                        }
                                }
                        }

                        if ( isset( $this->matched_rule ) ) {
                                // Trim the query of everything up to the '?'.
                                $query = preg_replace("!^.+\?!", '', $query);

                                // Substitute the substring matches into the query.
                                $query = addslashes(HQ_MatchesMapRegex::apply($query, $matches));

                                $this->matched_query = $query;

                                // Parse the query.
                                parse_str($query, $perma_query_vars);

                                // If we're processing a 404 request, clear the error var since we found something.
                                if ( '404' == $error )
                                        unset( $error, $_GET['error'] );
                        }

                        // If req_uri is empty or if it is a request for ourself, unset error.
                        if ( empty($request) || $req_uri == $self || strpos($_SERVER['PHP_SELF'], 'hq-admin/') !== false ) {
                                unset( $error, $_GET['error'] );

                                if ( isset($perma_query_vars) && strpos($_SERVER['PHP_SELF'], 'hq-admin/') !== false )
                                        unset( $perma_query_vars );

                                $this->did_permalink = false;
                        }
                }

                /**
                 * Filter the query variables whitelist before processing.
                 *
                 * Allows (publicly allowed) query vars to be added, removed, or changed prior
                 * to executing the query. Needed to allow custom rewrite rules using your own arguments
                 * to work, or any other custom query variables you want to be publicly available.
                 *
                 * @since 0.0.1
                 *
                 * @param array $public_query_vars The array of whitelisted query variables.
                 */
                $this->public_query_vars = apply_filters( 'query_vars', $this->public_query_vars );

                foreach ( get_post_types( array(), 'objects' ) as $post_type => $t )
                        if ( $t->query_var )
                                $post_type_query_vars[$t->query_var] = $post_type;

                foreach ( $this->public_query_vars as $hqvar ) {
                        if ( isset( $this->extra_query_vars[$hqvar] ) )
                                $this->query_vars[$hqvar] = $this->extra_query_vars[$hqvar];
                        elseif ( isset( $_POST[$hqvar] ) )
                                $this->query_vars[$hqvar] = $_POST[$hqvar];
                        elseif ( isset( $_GET[$hqvar] ) )
                                $this->query_vars[$hqvar] = $_GET[$hqvar];
                        elseif ( isset( $perma_query_vars[$hqvar] ) )
                                $this->query_vars[$hqvar] = $perma_query_vars[$hqvar];

                        if ( !empty( $this->query_vars[$hqvar] ) ) {
                                if ( ! is_array( $this->query_vars[$hqvar] ) ) {
                                        $this->query_vars[$hqvar] = (string) $this->query_vars[$hqvar];
                                } else {
                                        foreach ( $this->query_vars[$hqvar] as $vkey => $v ) {
                                                if ( !is_object( $v ) ) {
                                                        $this->query_vars[$hqvar][$vkey] = (string) $v;
                                                }
                                        }
                                }

                                if ( isset($post_type_query_vars[$hqvar] ) ) {
                                        $this->query_vars['post_type'] = $post_type_query_vars[$hqvar];
                                        $this->query_vars['name'] = $this->query_vars[$hqvar];
                                }
                        }
                }

                // Convert urldecoded spaces back into +
                //TODO: Goyo no taxonomies
                //foreach ( get_taxonomies( array() , 'objects' ) as $taxonomy => $t )
                //        if ( $t->query_var && isset( $this->query_vars[$t->query_var] ) )
                //                $this->query_vars[$t->query_var] = str_replace( ' ', '+', $this->query_vars[$t->query_var] );

                // Limit publicly queried post_types to those that are publicly_queryable
                if ( isset( $this->query_vars['post_type']) ) {
                        $queryable_post_types = get_post_types( array('publicly_queryable' => true) );
                        if ( ! is_array( $this->query_vars['post_type'] ) ) {
                                if ( ! in_array( $this->query_vars['post_type'], $queryable_post_types ) )
                                        unset( $this->query_vars['post_type'] );
                        } else {
                                $this->query_vars['post_type'] = array_intersect( $this->query_vars['post_type'], $queryable_post_types );
                        }
                }

                // Resolve conflicts between posts with numeric slugs and date archive queries.
                $this->query_vars = hq_resolve_numeric_slug_conflicts( $this->query_vars );

                foreach ( (array) $this->private_query_vars as $var) {
                        if ( isset($this->extra_query_vars[$var]) )
                                $this->query_vars[$var] = $this->extra_query_vars[$var];
                }

                if ( isset($error) )
                        $this->query_vars['error'] = $error;

                /**
                 * Filter the array of parsed query variables.
                 *
                 * @since 0.0.1
                 *
                 * @param array $query_vars The array of requested query variables.
                 */
                $this->query_vars = apply_filters( 'request', $this->query_vars );

                /**
                 * Fires once all query variables for the current request have been parsed.
                 *
                 * @since 0.0.1
                 *
                 * @param HQ &$this Current WordPress environment instance (passed by reference).
                 */
                do_action_ref_array( 'parse_request', array( &$this ) );
        }

        /**
         * Send additional HTTP headers for caching, content type, etc.
         *
         * Sets the X-Pingback header, 404 status (if 404), Content-type. If showing
         * a feed, it will also send last-modified, etag, and 304 status if needed.
         *
         * @since 0.0.1
         */
        public function send_headers() {
                $headers = array('X-Pingback' => get_bloginfo('pingback_url'));
                $status = null;
                $exit_required = false;

                if ( is_user_logged_in() )
                        $headers = array_merge($headers, hq_get_nocache_headers());
                if ( ! empty( $this->query_vars['error'] ) ) {
                        $status = (int) $this->query_vars['error'];
                        if ( 404 === $status ) {
                                if ( ! is_user_logged_in() )
                                        $headers = array_merge($headers, hq_get_nocache_headers());
                                $headers['Content-Type'] = get_option('html_type') . '; charset=' . get_option('blog_charset');
                        } elseif ( in_array( $status, array( 403, 500, 502, 503 ) ) ) {
                                $exit_required = true;
                        }
                } elseif ( empty( $this->query_vars['feed'] ) ) {
                        $headers['Content-Type'] = get_option('html_type') . '; charset=' . get_option('blog_charset');
                } else {
                        // We're showing a feed, so HQ is indeed the only thing that last changed
                        //TODO: Goyo no commnets
                        //if ( !empty($this->query_vars['withcomments'])
                        //        || false !== strpos( $this->query_vars['feed'], 'comments-' )
                        //        || ( empty($this->query_vars['withoutcomments'])
                        //                && ( !empty($this->query_vars['p'])
                        //                        || !empty($this->query_vars['name'])
                        //                        || !empty($this->query_vars['page_id'])
                        //                        || !empty($this->query_vars['pagename'])
                        //                        || !empty($this->query_vars['attachment'])
                        //                        || !empty($this->query_vars['attachment_id'])
                        //               )
                        //        )
                        //)
                        //        $hq_last_modified = mysql2date('D, d M Y H:i:s', get_lastcommentmodified('GMT'), 0).' GMT';
                        //else
                                $hq_last_modified = mysql2date('D, d M Y H:i:s', get_lastpostmodified('GMT'), 0).' GMT';
                        $hq_etag = '"' . md5($hq_last_modified) . '"';
                        $headers['Last-Modified'] = $hq_last_modified;
                        $headers['ETag'] = $hq_etag;

                        // Support for Conditional GET
                        if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
                                $client_etag = hq_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] );
                        else $client_etag = false;

                        $client_last_modified = empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? '' : trim($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                        // If string is empty, return 0. If not, attempt to parse into a timestamp
                        $client_modified_timestamp = $client_last_modified ? strtotime($client_last_modified) : 0;

                        // Make a timestamp for our most recent modification...
                        $hq_modified_timestamp = strtotime($hq_last_modified);

                        if ( ($client_last_modified && $client_etag) ?
                                         (($client_modified_timestamp >= $hq_modified_timestamp) && ($client_etag == $hq_etag)) :
                                         (($client_modified_timestamp >= $hq_modified_timestamp) || ($client_etag == $hq_etag)) ) {
                                $status = 304;
                                $exit_required = true;
                        }
                }

                /**
                 * Filter the HTTP headers before they're sent to the browser.
                 *
                 * @since 0.0.1
                 *
                 * @param array $headers The list of headers to be sent.
                 * @param HQ    $this    Current HiveQueen environment instance.
                 */
                $headers = apply_filters( 'hq_headers', $headers, $this );

                if ( ! empty( $status ) )
                        status_header( $status );

                // If Last-Modified is set to false, it should not be sent (no-cache situation).
                if ( isset( $headers['Last-Modified'] ) && false === $headers['Last-Modified'] ) {
                        unset( $headers['Last-Modified'] );

                        // In PHP 5.3+, make sure we are not sending a Last-Modified header.
                        if ( function_exists( 'header_remove' ) ) {
                                @header_remove( 'Last-Modified' );
                        } else {
                                // In PHP 5.2, send an empty Last-Modified header, but only as a
                                foreach ( headers_list() as $header ) {
                                        if ( 0 === stripos( $header, 'Last-Modified' ) ) {
                                                $headers['Last-Modified'] = '';
                                                break;
                                        }
                                }
                        }
                }

                foreach( (array) $headers as $name => $field_value )
                        @header("{$name}: {$field_value}");

                if ( $exit_required )
                        exit();

                /**
                 * Fires once the requested HTTP headers for caching, content type, etc. have been sent.
                 *
                 * @since 0.0.1
                 *
                 * @param HQ &$this Current HiveQueen environment instance (passed by reference).
                 */
                do_action_ref_array( 'send_headers', array( &$this ) );
        }

        /**
         * Sets the query string property based off of the query variable property.
         *
         * The 'query_string' filter is deprecated, but still works. Plugins should
         * use the 'request' filter instead.
         *
         * @since 0.0.1
         */
        public function build_query_string() {
                $this->query_string = '';
                foreach ( (array) array_keys($this->query_vars) as $hqvar) {
                        if ( '' != $this->query_vars[$hqvar] ) {
                                $this->query_string .= (strlen($this->query_string) < 1) ? '' : '&';
                                if ( !is_scalar($this->query_vars[$hqvar]) ) // Discard non-scalars.
                                        continue;
                                $this->query_string .= $hqvar . '=' . rawurlencode($this->query_vars[$hqvar]);
                        }
                }

                //TODO: Goyo debug
                //printf("build_query_string: %s", $this->query_string);

                if ( has_filter( 'query_string' ) ) {  // Don't bother filtering and parsing if no plugins are hooked in.
                        /**
                         * Filter the query string before parsing.
                         *
                         * @since 0.0.1
                         * @deprecated 0.0.1 Use 'query_vars' or 'request' filters instead.
                         *
                         * @param string $query_string The query string to modify.
                         */
                        $this->query_string = apply_filters( 'query_string', $this->query_string );
                        parse_str($this->query_string, $this->query_vars);
                }

        }

        /**
         * Set up the HiveQueen Globals.
         *
         * The query_vars property will be extracted to the GLOBALS. So care should
         * be taken when naming global variables that might interfere with the
         * HiveQueen environment.
         *
         * @global HQ_Query     $hq_query
         * @global string       $query_string Query string for the loop.
         * @global array        $posts The found posts.
         * @global HQ_Post|null $post The current post, if available.
         * @global string       $request The SQL statement for the request.
         * @global int          $more Only set, if single page or post.
         * @global int          $single If single page or post. Only set, if single page or post.
         * @global HQ_User      $authordata Only set, if author archive.
         *
         * @since 0.0.1
         */
        public function register_globals() {
                global $hq_query;

                // Extract updated query vars back into global namespace.
                foreach ( (array) $hq_query->query_vars as $key => $value ) {
                        $GLOBALS[ $key ] = $value;
                }

                $GLOBALS['query_string'] = $this->query_string;
                $GLOBALS['posts'] = & $hq_query->posts;
                $GLOBALS['post'] = isset( $hq_query->post ) ? $hq_query->post : null;
                $GLOBALS['request'] = $hq_query->request;

                if ( $hq_query->is_single() || $hq_query->is_page() ) {
                        $GLOBALS['more']   = 1;
                        $GLOBALS['single'] = 1;
                }

                if ( $hq_query->is_author() && isset( $hq_query->post ) )
                        $GLOBALS['authordata'] = get_userdata( $hq_query->post->post_author );
        }

        /**
         * Set up the current user.
         *
         * @since 0.0.1
         */
        public function init() {
                hq_get_current_user();
        }

        /**
         * Set up the Loop based on the query variables.
         *
         * @since 0.0.1
         *
         * @global HQ_Query $hq_the_query
         */
        public function query_posts() {
                global $hq_the_query;
                $this->build_query_string();
                $hq_the_query->query($this->query_vars);
        }

        /**
         * Set the Headers for 404, if nothing is found for requested URL.
         *
         * Issue a 404 if a request doesn't match any posts and doesn't match
         * any object (e.g. an existing-but-empty category, tag, author) and a 404 was not already
         * issued, and if the request was not a search or the homepage.
         *
         * Otherwise, issue a 200.
         *
         * @since 2.0.0
         *
         * @global HQ_Query $hq_query
         */
        public function handle_404() {
                global $hq_query;

                // If we've already issued a 404, bail.
                if ( is_404() )
                        return;

                // Never 404 for the admin, robots, or if we found posts.
                if ( is_admin() || is_robots() || $hq_query->posts ) {
                        status_header( 200 );
                        return;
                }

                // We will 404 for paged queries, as no posts were found.
                if ( ! is_paged() ) {

                        // Don't 404 for authors without posts as long as they matched an author on this site.
                        $author = get_query_var( 'author' );
                        if ( is_author() && is_numeric( $author ) && $author > 0 && is_user_member_of_blog( $author ) ) {
                                status_header( 200 );
                                return;
                        }

                        // Don't 404 for these queries if they matched an object.
                        if ( ( is_tag() || is_category() || is_tax() || is_post_type_archive() ) && get_queried_object() ) {
                        //if ( ( is_tag() || is_category() || is_tax() ) && get_queried_object() ) {
                                status_header( 200 );
                                return;
                        }

                        // Don't 404 for these queries either.
                        if ( is_home() || is_search() || is_feed() ) {
                                status_header( 200 );
                                return;
                        }
                }

                // Guess it's time to 404.
                //print("Is set 404");
                $hq_query->set_404();
                status_header( 404 );
                nocache_headers();
        }

        /**
         * Sets up all of the variables required by the HiveQueen environment.
         *
         * The action 'hq' has one parameter that references the HQ object. It
         * allows for accessing the properties and methods to further manipulate the
         * object.
         *
         * @since 0.0.1
         *
         * @param string|array $query_args Passed to {@link parse_request()}
         */
        public function main($query_args = '') {

                print("hq->main(): Begin");

                $this->init();


                $this->parse_request($query_args);
                $this->send_headers();
                $this->query_posts();
                $this->handle_404();
                $this->register_globals();
 
                 

                /**
                 * Fires once the HiveQueen environment has been set up.
                 *
                 * @since 0.0.1
                 *
                 * @param HQ &$this Current HiveQueen environment instance (passed by reference).
                 */
                do_action_ref_array( 'hq', array( &$this ) );

                //TODO: test
                //if (!is_user_logged_in()) {
                //   auth_redirect();
                //}

                print("hq->main(): End");

        }

}

/**
 * Helper class to remove the need to use eval to replace $matches[] in query strings.
 *
 * @since 0.0.1
 */
class HQ_MatchesMapRegex {
        /**
         * store for matche
         *
         * @access private
         * @var array
         */
        private $_matches;

        /**
         * store for mapping result
         *
         * @access public
         * @var string
         */
        public $output;

        /**
         * subject to perform mapping on (query string containing $matches[] references
         *
         * @access private
         * @var string
         */
        private $_subject;

        /**
         * regexp pattern to match $matches[] references
         *
         * @var string
         */
        public $_pattern = '(\$matches\[[1-9]+[0-9]*\])'; // magic number

        /**
         * constructor
         *
         * @param string $subject subject if regex
         * @param array  $matches data to use in map
         */
        public function __construct($subject, $matches) {
                $this->_subject = $subject;
                $this->_matches = $matches;
                $this->output = $this->_map();
        }

        /**
         * Substitute substring matches in subject.
         *
         * static helper function to ease use
         *
         * @static
         * @access public
         *
         * @param string $subject subject
         * @param array  $matches data used for substitution
         * @return string
         */
        public static function apply($subject, $matches) {
                $oSelf = new HQ_MatchesMapRegex($subject, $matches);
                return $oSelf->output;
        }

        /**
         * do the actual mapping
         *
         * @access private
         * @return string
         */
        private function _map() {
                $callback = array($this, 'callback');
                return preg_replace_callback($this->_pattern, $callback, $this->_subject);
        }

        /**
         * preg_replace_callback hook
         *
         * @access public
         * @param  array $matches preg_replace regexp matches
         * @return string
         */
        public function callback($matches) {
                $index = intval(substr($matches[0], 9, -1));
                return ( isset( $this->_matches[$index] ) ? urlencode($this->_matches[$index]) : '' );
        }
}


