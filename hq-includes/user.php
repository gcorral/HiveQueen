<?php
/**
 * HiveQueen User API
 *
 * @package HiveQueen
 * @subpackage Users
 */

/**
 * Authenticate user with remember capability.
 *
 * The credentials is an array that has 'user_login', 'user_password', and
 * 'remember' indices. If the credentials is not given, then the log in form
 * will be assumed and used if set.
 *
 * The various authentication cookies will be set by this function and will be
 * set for a longer period depending on if the 'remember' credential is set to
 * true.
 *
 * @since 0.0.1
 *
 * @global string $auth_secure_cookie
 *
 * @param array       $credentials   Optional. User info in order to sign on.
 * @param string|bool $secure_cookie Optional. Whether to use secure cookie.
 * @return HQ_User|HQ_Error HQ_User on success, HQ_Error on failure.
 */
function hq_signon( $credentials = array(), $secure_cookie = '' ) {
        if ( empty($credentials) ) {
                if ( ! empty($_POST['log']) )
                        $credentials['user_login'] = $_POST['log'];
                if ( ! empty($_POST['pwd']) )
                        $credentials['user_password'] = $_POST['pwd'];
                if ( ! empty($_POST['rememberme']) )
                        $credentials['remember'] = $_POST['rememberme'];
        }

        if ( !empty($credentials['remember']) )
                $credentials['remember'] = true;
        else
                $credentials['remember'] = false;

        /**
         * Fires before the user is authenticated.
         *
         * The variables passed to the callbacks are passed by reference,
         * and can be modified by callback functions.
         *
         * @since 0.0.1
         *
         * @todo Decide whether to deprecate the hq_authenticate action.
         *
         * @param string $user_login    Username, passed by reference.
         * @param string $user_password User password, passed by reference.
         */
        do_action_ref_array( 'hq_authenticate', array( &$credentials['user_login'], &$credentials['user_password'] ) );

        if ( '' === $secure_cookie )
                $secure_cookie = is_ssl();

        /**
         * Filter whether to use a secure sign-on cookie.
         *
         * @since 0.0.1
         *
         * @param bool  $secure_cookie Whether to use a secure sign-on cookie.
         * @param array $credentials {
         *     Array of entered sign-on data.
         *
         *     @type string $user_login    Username.
         *     @type string $user_password Password entered.
         *     @type bool   $remember      Whether to 'remember' the user. Increases the time
         *                                 that the cookie will be kept. Default false.
         * }
         */
        $secure_cookie = apply_filters( 'secure_signon_cookie', $secure_cookie, $credentials );

        global $auth_secure_cookie; // XXX ugly hack to pass this to hq_authenticate_cookie
        $auth_secure_cookie = $secure_cookie;

        add_filter('authenticate', 'hq_authenticate_cookie', 30, 3);

        $user = hq_authenticate($credentials['user_login'], $credentials['user_password']);

        if ( is_hq_error($user) ) {
                if ( $user->get_error_codes() == array('empty_username', 'empty_password') ) {
                        $user = new HQ_Error('', '');
                }

                return $user;
        }

        hq_set_auth_cookie($user->ID, $credentials['remember'], $secure_cookie);
        /**
         * Fires after the user has successfully logged in.
         *
         * @since 0.0.1
         *
         * @param string  $user_login Username.
         * @param HQ_User $user       HQ_User object of the logged-in user.
         */
        do_action( 'hq_login', $user->user_login, $user );
        return $user;
}

/**
 * Authenticate the user using the username and password.
 *
 * @since 0.0.1
 *
 * @param HQ_User|HQ_Error|null $user     HQ_User or HQ_Error object from a previous callback. Default null.
 * @param string                $username Username for authentication.
 * @param string                $password Password for authentication.
 * @return HQ_User|HQ_Error HQ_User on success, HQ_Error on failure.
 */
function hq_authenticate_username_password($user, $username, $password) {
        if ( $user instanceof HQ_User ) {
                return $user;
        }

        if ( empty($username) || empty($password) ) {
                if ( is_hq_error( $user ) )
                        return $user;

                $error = new HQ_Error();

                if ( empty($username) )
                        $error->add('empty_username', __('<strong>ERROR</strong>: The username field is empty.'));

                if ( empty($password) )
                        $error->add('empty_password', __('<strong>ERROR</strong>: The password field is empty.'));

                return $error;
        }

        $user = get_user_by('login', $username);

        if ( !$user )
                return new HQ_Error( 'invalid_username', sprintf( __( '<strong>ERROR</strong>: Invalid username. <a href="%s">Lost your password?</a>' ), hq_lostpassword_url() ) );

        /**
         * Filter whether the given user can be authenticated with the provided $password.
         *
         * @since 0.0.1
         *
         * @param HQ_User|HQ_Error $user     HQ_User or HQ_Error object if a previous
         *                                   callback failed authentication.
         * @param string           $password Password to check against the user.
         */
        $user = apply_filters( 'hq_authenticate_user', $user, $password );
        if ( is_hq_error($user) )
                return $user;

        if ( !hq_check_password($password, $user->user_pass, $user->ID) )
                return new HQ_Error( 'incorrect_password', sprintf( __( '<strong>ERROR</strong>: The password you entered for the username <strong>%1$s</strong> is incorrect. <a href="%2$s">Lost your password?</a>' ),
                $username, hq_lostpassword_url() ) );

        return $user;
}

/**
 * Authenticate the user using the HiveQueen auth cookie.
 *
 * @since 0.0.1
 *
 * @global string $auth_secure_cookie
 *
 * @param HQ_User|HQ_Error|null $user     HQ_User or HQ_Error object from a previous callback. Default null.
 * @param string                $username Username. If not empty, cancels the cookie authentication.
 * @param string                $password Password. If not empty, cancels the cookie authentication.
 * @return HQ_User|HQ_Error HQ_User on success, HQ_Error on failure.
 */
function hq_authenticate_cookie($user, $username, $password) {
        if ( $user instanceof HQ_User ) {
                return $user;
        }

        if ( empty($username) && empty($password) ) {
                $user_id = hq_validate_auth_cookie();
                if ( $user_id )
                        return new HQ_User($user_id);

                global $auth_secure_cookie;

                if ( $auth_secure_cookie )
                        $auth_cookie = SECURE_AUTH_COOKIE;
                else
                        $auth_cookie = AUTH_COOKIE;

                if ( !empty($_COOKIE[$auth_cookie]) )
                        return new HQ_Error('expired_session', __('Please log in again.'));

                // If the cookie is not set, be silent.
        }

        return $user;
}

/**
 * For Multisite blogs, check if the authenticated user has been marked as a
 * spammer, or if the user's primary blog has been marked as spam.
 *
 * @since 0.0.1
 *
 * @param QH_User|HQ_Error|null $user HQ_User or HQ_Error object from a previous callback. Default null.
 * @return HQ_User|HQ_Error HQ_User on success, HQ_Error if the user is considered a spammer.
 */
function hq_authenticate_spam_check( $user ) {
        //TODO: Goyo no multisite
        //if ( $user instanceof HQ_User && is_multisite() ) {
        if ( $user instanceof HQ_User && false ) {
                /**
                 * Filter whether the user has been marked as a spammer.
                 *
                 * @since 0.0.1
                 *
                 * @param bool    $spammed Whether the user is considered a spammer.
                 * @param HQ_User $user    User to check against.
                 */
                $spammed = apply_filters( 'check_is_user_spammed', is_user_spammy(), $user );

                if ( $spammed )
                        return new HQ_Error( 'spammer_account', __( '<strong>ERROR</strong>: Your account has been marked as a spammer.' ) );
        }
        return $user;
}

/**
 * Validate the logged-in cookie.
 *
 * Checks the logged-in cookie if the previous auth cookie could not be
 * validated and parsed.
 *
 * This is a callback for the determine_current_user filter, rather than API.
 *
 * @since 0.0.1
 *
 * @param int|bool $user_id The user ID (or false) as received from the
 *                       determine_current_user filter.
 * @return int|false User ID if validated, false otherwise. If a user ID from
 *                   an earlier filter callback is received, that value is returned.
 */
function hq_validate_logged_in_cookie( $user_id ) {
        if ( $user_id ) {
                return $user_id;
        }

        if ( is_blog_admin() || is_network_admin() || empty( $_COOKIE[LOGGED_IN_COOKIE] ) ) {
                return false;
        }

        return hq_validate_auth_cookie( $_COOKIE[LOGGED_IN_COOKIE], 'logged_in' );
}


/**
 * Number of posts user has written.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb HiveQueen database object for queries.
 *
 * @param int          $userid      User ID.
 * @param array|string $post_type   Optional. Single post type or array of post types to count the number of posts for. Default 'post'.
 * @param bool         $public_only Optional. Whether to only return counts for public posts. Default false.
 * @return int Number of posts the user has written in this post type.
 */
function count_user_posts( $userid, $post_type = 'post', $public_only = false ) {
        global $hqdb;

        $where = get_posts_by_author_sql( $post_type, true, $userid, $public_only );

        $count = $hqdb->get_var( "SELECT COUNT(*) FROM $hqdb->posts $where" );

        /**
         * Filter the number of posts a user has written.
         *
         * @since 0.0.1
         *
         * @param int          $count       The user's post count.
         * @param int          $userid      User ID.
         * @param string|array $post_type   Single post type or array of post types to count the number of posts for.
         * @param bool         $public_only Whether to limit counted posts to public posts.
         */
        return apply_filters( 'get_usernumposts', $count, $userid, $post_type, $public_only );
}

/**
 * Number of posts written by a list of users.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb
 *
 * @param array        $users       Array of user IDs.
 * @param string|array $post_type   Optional. Single post type or array of post types to check. Defaults to 'post'.
 * @param bool         $public_only Optional. Only return counts for public posts.  Defaults to false.
 * @return array Amount of posts each user has written.
 */
function count_many_users_posts( $users, $post_type = 'post', $public_only = false ) {
        global $hqdb;

        $count = array();
        if ( empty( $users ) || ! is_array( $users ) )
                return $count;

        $userlist = implode( ',', array_map( 'absint', $users ) );
        $where = get_posts_by_author_sql( $post_type, true, null, $public_only );

        $result = $hqdb->get_results( "SELECT post_author, COUNT(*) FROM $hqdb->posts $where AND post_author IN ($userlist) GROUP BY post_author", ARRAY_N );
        foreach ( $result as $row ) {
                $count[ $row[0] ] = $row[1];
        }

        foreach ( $users as $id ) {
                if ( ! isset( $count[ $id ] ) )
                        $count[ $id ] = 0;
        }

        return $count;
}

//
// User option functions
//

/**
 * Get the current user's ID
 *
 * @since MU
 *
 * @return int The current user's ID
 */
function get_current_user_id() {
        if ( ! function_exists( 'hq_get_current_user' ) )
                return 0;
        $user = hq_get_current_user();
        return ( isset( $user->ID ) ? (int) $user->ID : 0 );
}

/**
 * Retrieve user option that can be either per Site or per Network.
 *
 * If the user ID is not given, then the current user will be used instead. If
 * the user ID is given, then the user data will be retrieved. The filter for
 * the result, will also pass the original option name and finally the user data
 * object as the third parameter.
 *
 * The option will first check for the per site name and then the per Network name.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb HiveQueen database object for queries.
 *
 * @param string $option     User option name.
 * @param int    $user       Optional. User ID.
 * @param string $deprecated Use get_option() to check for an option in the options table.
 * @return mixed User option value on success, false on failure.
 */
function get_user_option( $option, $user = 0, $deprecated = '' ) {
        global $hqdb;

        if ( !empty( $deprecated ) )
                _deprecated_argument( __FUNCTION__, '3.0' );

        if ( empty( $user ) )
                $user = get_current_user_id();

        if ( ! $user = get_userdata( $user ) )
                return false;

        $prefix = $hqdb->get_blog_prefix();
        if ( $user->has_prop( $prefix . $option ) ) // Blog specific
                $result = $user->get( $prefix . $option );
        elseif ( $user->has_prop( $option ) ) // User specific and cross-blog
                $result = $user->get( $option );
        else
                $result = false;

        /**
         * Filter a specific user option value.
         *
         * The dynamic portion of the hook name, `$option`, refers to the user option name.
         *
         * @since 0.0.1
         *
         * @param mixed   $result Value for the user's option.
         * @param string  $option Name of the option being retrieved.
         * @param HQ_User $user   HQ_User object of the user whose option is being retrieved.
         */
        return apply_filters( "get_user_option_{$option}", $result, $option, $user );
}

/**
 * Update user option with global blog capability.
 *
 * User options are just like user metadata except that they have support for
 * global blog options. If the 'global' parameter is false, which it is by default
 * it will prepend the HiveQueen table prefix to the option name.
 *
 * Deletes the user option if $newvalue is empty.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb HiveQueen database object for queries.
 *
 * @param int    $user_id     User ID.
 * @param string $option_name User option name.
 * @param mixed  $newvalue    User option value.
 * @param bool   $global      Optional. Whether option name is global or blog specific.
 *                            Default false (blog specific).
 * @return int|bool User meta ID if the option didn't exist, true on successful update,
 *                  false on failure.
 */
function update_user_option( $user_id, $option_name, $newvalue, $global = false ) {
        global $hqdb;

        if ( !$global )
                $option_name = $hqdb->get_blog_prefix() . $option_name;

        return update_user_meta( $user_id, $option_name, $newvalue );
}

/**
 * Delete user option with global blog capability.
 *
 * User options are just like user metadata except that they have support for
 * global blog options. If the 'global' parameter is false, which it is by default
 * it will prepend the WordPress table prefix to the option name.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb HiveQueen database object for queries.
 *
 * @param int    $user_id     User ID
 * @param string $option_name User option name.
 * @param bool   $global      Optional. Whether option name is global or blog specific.
 *                            Default false (blog specific).
 * @return bool True on success, false on failure.
 */
function delete_user_option( $user_id, $option_name, $global = false ) {
        global $hqdb;

        if ( !$global )
                $option_name = $hqdb->get_blog_prefix() . $option_name;
        return delete_user_meta( $user_id, $option_name );
}

/**
 * HiveQueen User Query class.
 *
 * @since 0.0.1
 *
 * @see HQ_User_Query::prepare_query() for information on accepted arguments.
 */
class HQ_User_Query {

        /**
         * Query vars, after parsing
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
        public $query_vars = array();

        /**
         * List of found user ids
         *
         * @since 0.0.1
         * @access private
         * @var array
         */
        private $results;

        /**
         * Total number of found users for the current query
         *
         * @since 0.0.1
         * @access private
         * @var int
         */
        private $total_users = 0;

        /**
         * Metadata query container.
         *
         * @since 0.0.1
         * @access public
         * @var object HQ_Meta_Query
         */
        public $meta_query = false;

        private $compat_fields = array( 'results', 'total_users' );

        // SQL clauses
        public $query_fields;
        public $query_from;
        public $query_where;
        public $query_orderby;
        public $query_limit;

        /**
         * PHP5 constructor.
         *
         * @since 0.0.1
         *
         * @param null|string|array $args Optional. The query variables.
         */
        public function __construct( $query = null ) {
                if ( ! empty( $query ) ) {
                        $this->prepare_query( $query );
                        $this->query();
                }
        }

        /**
         * Prepare the query variables.
         *
         * @since 0.0.1
         * @access public
         *
         * @global hqdb $hqdb
         * @global int  $blog_id
         *
         * @param string|array $query {
         *     Optional. Array or string of Query parameters.
         *
         *     @type int          $blog_id             The site ID. Default is the global blog id.
         *     @type string       $role                Role name. Default empty.
         *     @type string       $meta_key            User meta key. Default empty.
         *     @type string       $meta_value          User meta value. Default empty.
         *     @type string       $meta_compare        Comparison operator to test the `$meta_value`. Accepts '=', '!=',
         *                                             '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
         *                                             'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS', 'REGEXP',
         *                                             'NOT REGEXP', or 'RLIKE'. Default '='.
         *     @type array        $include             An array of user IDs to include. Default empty array.
         *     @type array        $exclude             An array of user IDs to exclude. Default empty array.
         *     @type string       $search              Search keyword. Searches for possible string matches on columns.
         *                                             When `$search_columns` is left empty, it tries to determine which
         *                                             column to search in based on search string. Default empty.
         *     @type array        $search_columns      Array of column names to be searched. Accepts 'ID', 'login',
         *                                             'nicename', 'email', 'url'. Default empty array.
         *     @type string|array $orderby             Field(s) to sort the retrieved users by. May be a single value,
         *                                             an array of values, or a multi-dimensional array with fields as
         *                                             keys and orders ('ASC' or 'DESC') as values. Accepted values are
         *                                             'ID', 'display_name' (or 'name'), 'user_login' (or 'login'),
         *                                             'user_nicename' (or 'nicename'), 'user_email' (or 'email'),
         *                                             'user_url' (or 'url'), 'user_registered' (or 'registered'),
         *                                             'post_count', 'meta_value', 'meta_value_num', the value of
         *                                             `$meta_key`, or an array key of `$meta_query`. To use
         *                                             'meta_value' or 'meta_value_num', `$meta_key` must be also be
         *                                             defined. Default 'user_login'.
         *     @type string       $order               Designates ascending or descending order of users. Order values
         *                                             passed as part of an `$orderby` array take precedence over this
         *                                             parameter. Accepts 'ASC', 'DESC'. Default 'ASC'.
         *     @type int          $offset              Number of users to offset in retrieved results. Can be used in
         *                                             conjunction with pagination. Default 0.
         *     @type int          $number              Number of users to limit the query for. Can be used in
         *                                             conjunction with pagination. Value -1 (all) is not supported.
         *                                             Default empty (all users).
         *     @type bool         $count_total         Whether to count the total number of users found. If pagination
         *                                             is not needed, setting this to false can improve performance.
         *                                             Default true.
         *     @type string|array $fields              Which fields to return. Single or all fields (string), or array
         *                                             of fields. Accepts 'ID', 'display_name', 'login', 'nicename',
         *                                             'email', 'url', 'registered'. Use 'all' for all fields and
         *                                             'all_with_meta' to include meta fields. Default 'all'.
         *     @type string       $who                 Type of users to query. Accepts 'authors'.
         *                                             Default empty (all users).
         *     @type bool|array   $has_published_posts Pass an array of post types to filter results to users who have
         *                                             published posts in those post types. `true` is an alias for all
         *                                             public post types.
         * }
         */
        public function prepare_query( $query = array() ) {
                global $hqdb;

                if ( empty( $this->query_vars ) || ! empty( $query ) ) {
                        $this->query_limit = null;
                        $this->query_vars = hq_parse_args( $query, array(
                                'blog_id' => $GLOBALS['blog_id'],
                                'role' => '',
                                'meta_key' => '',
                                'meta_value' => '',
                                'meta_compare' => '',
                                'include' => array(),
                                'exclude' => array(),
                                'search' => '',
                                'search_columns' => array(),
                                'orderby' => 'login',
                                'order' => 'ASC',
                                'offset' => '',
                                'number' => '',
                                'count_total' => true,
                                'fields' => 'all',
                                'who' => '',
                                'has_published_posts' => null,
                        ) );
                }

                /**
                 * Fires before the HQ_User_Query has been parsed.
                 *
                 * The passed HQ_User_Query object contains the query variables, not
                 * yet passed into SQL.
                 *
                 * @since 0.0.1
                 *
                 * @param HQ_User_Query $this The current HQ_User_Query instance,
                 *                            passed by reference.
                 */
                do_action( 'pre_get_users', $this );

                $qv =& $this->query_vars;

                if ( is_array( $qv['fields'] ) ) {
                        $qv['fields'] = array_unique( $qv['fields'] );

                        $this->query_fields = array();
                        foreach ( $qv['fields'] as $field ) {
                                $field = 'ID' === $field ? 'ID' : sanitize_key( $field );
                                $this->query_fields[] = "$hqdb->users.$field";
                        }
                        $this->query_fields = implode( ',', $this->query_fields );
                } elseif ( 'all' == $qv['fields'] ) {
                        $this->query_fields = "$hqdb->users.*";
                } else {
                        $this->query_fields = "$hqdb->users.ID";
                }

                if ( isset( $qv['count_total'] ) && $qv['count_total'] )
                        $this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;

                $this->query_from = "FROM $hqdb->users";
                $this->query_where = "WHERE 1=1";

                // Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
                if ( ! empty( $qv['include'] ) ) {
                        $include = hq_parse_id_list( $qv['include'] );
                } else {
                        $include = false;
                }

                $blog_id = 0;
                if ( isset( $qv['blog_id'] ) ) {
                        $blog_id = absint( $qv['blog_id'] );
                }

                if ( isset( $qv['who'] ) && 'authors' == $qv['who'] && $blog_id ) {
                        $qv['meta_key'] = $hqdb->get_blog_prefix( $blog_id ) . 'user_level';
                        $qv['meta_value'] = 0;
                        $qv['meta_compare'] = '!=';
                        $qv['blog_id'] = $blog_id = 0; // Prevent extra meta query
                }

                if ( $qv['has_published_posts'] && $blog_id ) {
                        if ( true === $qv['has_published_posts'] ) {
                                $post_types = get_post_types( array( 'public' => true ) );
                        } else {
                                $post_types = (array) $qv['has_published_posts'];
                        }

                        foreach ( $post_types as &$post_type ) {
                                $post_type = $hqdb->prepare( '%s', $post_type );
                        }

                        $posts_table = $hqdb->get_blog_prefix( $blog_id ) . 'posts';
                        $this->query_where .= " AND $hqdb->users.ID IN ( SELECT DISTINCT $posts_table.post_author FROM $posts_table WHERE $posts_table.post_status = 'publish' AND $posts_table.post_type IN ( " . join( ", ", $post_types ) . " ) )";
                }

                // Meta query.
                $this->meta_query = new HQ_Meta_Query();
                $this->meta_query->parse_query_vars( $qv );

                $role = '';
                if ( isset( $qv['role'] ) ) {
                        $role = trim( $qv['role'] );
                }

                if ( $blog_id && ( $role || is_multisite() ) ) {
                        $cap_meta_query = array();
                        $cap_meta_query['key'] = $hqdb->get_blog_prefix( $blog_id ) . 'capabilities';

                        if ( $role ) {
                                $cap_meta_query['value'] = '"' . $role . '"';
                                $cap_meta_query['compare'] = 'like';
                        }

                        if ( empty( $this->meta_query->queries ) ) {
                                $this->meta_query->queries = array( $cap_meta_query );
                        } elseif ( ! in_array( $cap_meta_query, $this->meta_query->queries, true ) ) {
                                // Append the cap query to the original queries and reparse the query.
                                $this->meta_query->queries = array(
                                        'relation' => 'AND',
                                        array( $this->meta_query->queries, $cap_meta_query ),
                                );
                        }

                        $this->meta_query->parse_query_vars( $this->meta_query->queries );
                }

                if ( ! empty( $this->meta_query->queries ) ) {
                        $clauses = $this->meta_query->get_sql( 'user', $hqdb->users, 'ID', $this );
                        $this->query_from .= $clauses['join'];
                        $this->query_where .= $clauses['where'];

                        if ( $this->meta_query->has_or_relation() ) {
                                $this->query_fields = 'DISTINCT ' . $this->query_fields;
                        }
                }

                // sorting
                $qv['order'] = isset( $qv['order'] ) ? strtoupper( $qv['order'] ) : '';
                $order = $this->parse_order( $qv['order'] );

                if ( empty( $qv['orderby'] ) ) {
                        // Default order is by 'user_login'.
                        $ordersby = array( 'user_login' => $order );
                } elseif ( is_array( $qv['orderby'] ) ) {
                        $ordersby = $qv['orderby'];
                } else {
                        // 'orderby' values may be a comma- or space-separated list.
                        $ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
                }

                $orderby_array = array();
                foreach ( $ordersby as $_key => $_value ) {
                        if ( ! $_value ) {
                                continue;
                        }

                        if ( is_int( $_key ) ) {
                                // Integer key means this is a flat array of 'orderby' fields.
                                $_orderby = $_value;
                                $_order = $order;
                        } else {
                                // Non-integer key means this the key is the field and the value is ASC/DESC.
                                $_orderby = $_key;
                                $_order = $_value;
                        }

                        $parsed = $this->parse_orderby( $_orderby );

                        if ( ! $parsed ) {
                                continue;
                        }

                        $orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
                }

                // If no valid clauses were found, order by user_login.
                if ( empty( $orderby_array ) ) {
                        $orderby_array[] = "user_login $order";
                }

                $this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

                // limit
                if ( isset( $qv['number'] ) && $qv['number'] ) {
                        if ( $qv['offset'] )
                                $this->query_limit = $hqdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
                        else
                                $this->query_limit = $hqdb->prepare("LIMIT %d", $qv['number']);
                }

                $search = '';
                if ( isset( $qv['search'] ) )
                        $search = trim( $qv['search'] );

                if ( $search ) {
                        $leading_wild = ( ltrim($search, '*') != $search );
                        $trailing_wild = ( rtrim($search, '*') != $search );
                        if ( $leading_wild && $trailing_wild )
                                $wild = 'both';
                        elseif ( $leading_wild )
                                $wild = 'leading';
                        elseif ( $trailing_wild )
                                $wild = 'trailing';
                        else
                                $wild = false;
                        if ( $wild )
                                $search = trim($search, '*');

                        $search_columns = array();
                        if ( $qv['search_columns'] )
                                $search_columns = array_intersect( $qv['search_columns'], array( 'ID', 'user_login', 'user_email', 'user_url', 'user_nicename' ) );
                        if ( ! $search_columns ) {
                                if ( false !== strpos( $search, '@') )
                                        $search_columns = array('user_email');
                                elseif ( is_numeric($search) )
                                        $search_columns = array('user_login', 'ID');
                                elseif ( preg_match('|^https?://|', $search) && ! ( is_multisite() && hq_is_large_network( 'users' ) ) )
                                        $search_columns = array('user_url');
                                else
                                        $search_columns = array('user_login', 'user_url', 'user_email', 'user_nicename', 'display_name');
                        }

                        /**
                         * Filter the columns to search in a HQ_User_Query search.
                         *
                         * The default columns depend on the search term, and include 'user_email',
                         * 'user_login', 'ID', 'user_url', 'display_name', and 'user_nicename'.
                         *
                         * @since 0.0.1
                         *
                         * @param array         $search_columns Array of column names to be searched.
                         * @param string        $search         Text being searched.
                         * @param HQ_User_Query $this           The current HQ_User_Query instance.
                         */
                        $search_columns = apply_filters( 'user_search_columns', $search_columns, $search, $this );

                        $this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
                }

                if ( ! empty( $include ) ) {
                        // Sanitized earlier.
                        $ids = implode( ',', $include );
                        $this->query_where .= " AND $hqdb->users.ID IN ($ids)";
                } elseif ( ! empty( $qv['exclude'] ) ) {
                        $ids = implode( ',', hq_parse_id_list( $qv['exclude'] ) );
                        $this->query_where .= " AND $hqdb->users.ID NOT IN ($ids)";
                }

                // Date queries are allowed for the user_registered field.
                if ( ! empty( $qv['date_query'] ) && is_array( $qv['date_query'] ) ) {
                        $date_query = new HQ_Date_Query( $qv['date_query'], 'user_registered' );
                        $this->query_where .= $date_query->get_sql();
                }

                /**
                 * Fires after the HQ_User_Query has been parsed, and before
                 * the query is executed.
                 *
                 * The passed HQ_User_Query object contains SQL parts formed
                 * from parsing the given query.
                 *
                 * @since 0.0.1
                 *
                 * @param HQ_User_Query $this The current HQ_User_Query instance,
                 *                            passed by reference.
                 */
                do_action_ref_array( 'pre_user_query', array( &$this ) );
        }

       /**
         * Execute the query, with the current variables.
         *
         * @since 0.0.1
         *
         * @global hqdb $hqdb HiveQueen database object for queries.
         */
        public function query() {
                global $hqdb;

                $qv =& $this->query_vars;

                $query = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

                if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] ) {
                        $this->results = $hqdb->get_results( $query );
                } else {
                        $this->results = $hqdb->get_col( $query );
                }

                /**
                 * Filter SELECT FOUND_ROWS() query for the current HQ_User_Query instance.
                 *
                 * @since 0.0.1
                 *
                 * @global hqdb $hqdb HiveQuenn database abstraction object.
                 *
                 * @param string $sql The SELECT FOUND_ROWS() query for the current HQ_User_Query.
                 */
                if ( isset( $qv['count_total'] ) && $qv['count_total'] )
                        $this->total_users = $hqdb->get_var( apply_filters( 'found_users_query', 'SELECT FOUND_ROWS()' ) );

                if ( !$this->results )
                        return;

                if ( 'all_with_meta' == $qv['fields'] ) {
                        cache_users( $this->results );

                        $r = array();
                        foreach ( $this->results as $userid )
                                $r[ $userid ] = new HQ_User( $userid, '', $qv['blog_id'] );

                        $this->results = $r;
                } elseif ( 'all' == $qv['fields'] ) {
                        foreach ( $this->results as $key => $user ) {
                                $this->results[ $key ] = new HQ_User( $user, '', $qv['blog_id'] );
                        }
                }
        }

        /**
         * Retrieve query variable.
         *
         * @since 0.0.0
         * @access public
         *
         * @param string $query_var Query variable key.
         * @return mixed
         */
        public function get( $query_var ) {
                if ( isset( $this->query_vars[$query_var] ) )
                        return $this->query_vars[$query_var];

                return null;
        }

        /**
         * Set query variable.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $query_var Query variable key.
         * @param mixed $value Query variable value.
         */
        public function set( $query_var, $value ) {
                $this->query_vars[$query_var] = $value;
        }

        /**
         * Used internally to generate an SQL string for searching across multiple columns
         *
         * @access protected
         * @since 0.0.1
         *
         * @global hqdb $hqdb
         *
         * @param string $string
         * @param array  $cols
         * @param bool   $wild   Whether to allow wildcard searches. Default is false for Network Admin, true for single site.
         *                       Single site allows leading and trailing wildcards, Network Admin only trailing.
         * @return string
         */
        protected function get_search_sql( $string, $cols, $wild = false ) {
                global $hqdb;

                $searches = array();
                $leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
                $trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
                $like = $leading_wild . $hqdb->esc_like( $string ) . $trailing_wild;

                foreach ( $cols as $col ) {
                        if ( 'ID' == $col ) {
                                $searches[] = $hqdb->prepare( "$col = %s", $string );
                        } else {
                                $searches[] = $hqdb->prepare( "$col LIKE %s", $like );
                        }
                }

                return ' AND (' . implode(' OR ', $searches) . ')';
        }

        /**
         * Return the list of users.
         *
         * @since 0.0.1
         * @access public
         *
         * @return array Array of results.
         */
        public function get_results() {
                return $this->results;
        }

        /**
         * Return the total number of users for the current query.
         *
         * @since 0.0.1
         * @access public
         *
         * @return int Number of total users.
         */
        public function get_total() {
                return $this->total_users;
        }

        /**
         * Parse and sanitize 'orderby' keys passed to the user query.
         *
         * @since 0.0.1
         * @access protected
         *
         * @global hqdb $hqdb HiveQueen database abstraction object.
         *
         * @param string $orderby Alias for the field to order by.
         * @return string Value to used in the ORDER clause, if `$orderby` is valid.
         */
        protected function parse_orderby( $orderby ) {
                global $hqdb;

                $meta_query_clauses = $this->meta_query->get_clauses();

                $_orderby = '';
                if ( in_array( $orderby, array( 'login', 'nicename', 'email', 'url', 'registered' ) ) ) {
                        $_orderby = 'user_' . $orderby;
                } elseif ( in_array( $orderby, array( 'user_login', 'user_nicename', 'user_email', 'user_url', 'user_registered' ) ) ) {
                        $_orderby = $orderby;
                } elseif ( 'name' == $orderby || 'display_name' == $orderby ) {
                        $_orderby = 'display_name';
                } elseif ( 'post_count' == $orderby ) {
                        // todo: avoid the JOIN
                        $where = get_posts_by_author_sql( 'post' );
                        $this->query_from .= " LEFT OUTER JOIN (
                                SELECT post_author, COUNT(*) as post_count
                                FROM $hqdb->posts
                                $where
                                GROUP BY post_author
                        ) p ON ({$hqdb->users}.ID = p.post_author)
                        ";
                        $_orderby = 'post_count';
                } elseif ( 'ID' == $orderby || 'id' == $orderby ) {
                        $_orderby = 'ID';
                } elseif ( 'meta_value' == $orderby || $this->get( 'meta_key' ) == $orderby ) {
                        $_orderby = "$hqdb->usermeta.meta_value";
                } elseif ( 'meta_value_num' == $orderby ) {
                        $_orderby = "$hqdb->usermeta.meta_value+0";
                } elseif ( 'include' === $orderby && ! empty( $this->query_vars['include'] ) ) {
                        $include = hq_parse_id_list( $this->query_vars['include'] );
                        $include_sql = implode( ',', $include );
                        $_orderby = "FIELD( $hqdb->users.ID, $include_sql )";
                } elseif ( isset( $meta_query_clauses[ $orderby ] ) ) {
                        $meta_clause = $meta_query_clauses[ $orderby ];
                        $_orderby = sprintf( "CAST(%s.meta_value AS %s)", esc_sql( $meta_clause['alias'] ), esc_sql( $meta_clause['cast'] ) );
                }

                return $_orderby;
        }

        /**
         * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
         *
         * @since 0.0.1
         * @access protected
         *
         * @param string $order The 'order' query variable.
         * @return string The sanitized 'order' query variable.
         */
        protected function parse_order( $order ) {
                if ( ! is_string( $order ) || empty( $order ) ) {
                        return 'DESC';
                }

                if ( 'ASC' === strtoupper( $order ) ) {
                        return 'ASC';
                } else {
                        return 'DESC';
                }
        }

        /**
         * Make private properties readable for backwards compatibility.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $name Property to get.
         * @return mixed Property.
         */
        public function __get( $name ) {
                if ( in_array( $name, $this->compat_fields ) ) {
                        return $this->$name;
                }
        }

       /**
         * Make private properties settable for backwards compatibility.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $name  Property to check if set.
         * @param mixed  $value Property value.
         * @return mixed Newly-set property.
         */
        public function __set( $name, $value ) {
                if ( in_array( $name, $this->compat_fields ) ) {
                        return $this->$name = $value;
                }
        }

        /**
         * Make private properties checkable for backwards compatibility.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $name Property to check if set.
         * @return bool Whether the property is set.
         */
        public function __isset( $name ) {
                if ( in_array( $name, $this->compat_fields ) ) {
                        return isset( $this->$name );
                }
        }

        /**
         * Make private properties un-settable for backwards compatibility.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $name Property to unset.
         */
        public function __unset( $name ) {
                if ( in_array( $name, $this->compat_fields ) ) {
                        unset( $this->$name );
                }
        }

        /**
         * Make private/protected methods readable for backwards compatibility.
         *
         * @since 0.0.1
         * @access public
         *
         * @param callable $name      Method to call.
         * @param array    $arguments Arguments to pass when calling.
         * @return mixed Return value of the callback, false otherwise.
         */
        public function __call( $name, $arguments ) {
                if ( 'get_search_sql' === $name ) {
                        return call_user_func_array( array( $this, $name ), $arguments );
                }
                return false;
        }
}

/**
 * Retrieve list of users matching criteria.
 *
 * @since 0.0.1
 *
 * @see HQ_User_Query
 *
 * @param array $args Optional. Arguments to retrieve users. See {@see HQ_User_Query::prepare_query()}
 *                    for more information on accepted arguments.
 * @return array List of users.
 */
function get_users( $args = array() ) {

        $args = hq_parse_args( $args );
        $args['count_total'] = false;

        $user_search = new HQ_User_Query($args);

        return (array) $user_search->get_results();
}

//
// Private helper functions
//

/**
 * Set up global user vars.
 *
 * Used by hq_set_current_user() for back compat. Might be deprecated in the future.
 *
 * @since 0.0.1
 *
 * @global string $user_login    The user username for logging in
 * @global object $userdata      User data.
 * @global int    $user_level    The level of the user
 * @global int    $user_ID       The ID of the user
 * @global string $user_email    The email address of the user
 * @global string $user_url      The url in the user's profile
 * @global string $user_identity The display name of the user
 *
 * @param int $for_user_id Optional. User ID to set up global data.
 */
function setup_userdata($for_user_id = '') {
        global $user_login, $userdata, $user_level, $user_ID, $user_email, $user_url, $user_identity;

        if ( '' == $for_user_id )
                $for_user_id = get_current_user_id();
        $user = get_userdata( $for_user_id );

        if ( ! $user ) {
                $user_ID = 0;
                $user_level = 0;
                $userdata = null;
                $user_login = $user_email = $user_url = $user_identity = '';
                return;
        }

        $user_ID    = (int) $user->ID;
        $user_level = (int) $user->user_level;
        $userdata   = $user;
        $user_login = $user->user_login;
        $user_email = $user->user_email;
        $user_url   = $user->user_url;
        $user_identity = $user->display_name;
}


/**
 * Checks whether the given username exists.
 *
 * @since 0.0.1
 *
 * @param string $username Username.
 * @return int|false The user's ID on success, and false on failure.
 */
function username_exists( $username ) {
        if ( $user = get_user_by( 'login', $username ) ) {
                return $user->ID;
        }
        return false;
}


/**
 * A simpler way of inserting a user into the database.
 *
 * Creates a new user with just the username, password, and email. For more
 * complex user creation use {@see hq_insert_user()} to specify more information.
 *
 * @since 0.0.1
 * @see hq_insert_user() More complete way to create a new user
 *
 * @param string $username The user's username.
 * @param string $password The user's password.
 * @param string $email    Optional. The user's email. Default empty.
 * @return int|HQ_Error The new user's ID.
 */
function hq_create_user($username, $password, $email = '') {
        $user_login = hq_slash( $username );
        $user_email = hq_slash( $email    );
        $user_pass = $password;

        $userdata = compact('user_login', 'user_email', 'user_pass');
        return hq_insert_user($userdata);
}

/**
 * Insert a user into the database.
 *
 * Most of the `$userdata` array fields have filters associated with the values. Exceptions are
 * 'ID', 'rich_editing', 'comment_shortcuts', 'admin_color', 'use_ssl',
 * 'user_registered', and 'role'. The filters have the prefix 'pre_user_' followed by the field
 * name. An example using 'description' would have the filter called, 'pre_user_description' that
 * can be hooked into.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb HiveQueen database object for queries.
 *
 * @param array|object|HQ_User $userdata {
 *     An array, object, or HQ_User object of user data arguments.
 *
 *     @type int         $ID                   User ID. If supplied, the user will be updated.
 *     @type string      $user_pass            The plain-text user password.
 *     @type string      $user_login           The user's login username.
 *     @type string      $user_nicename        The URL-friendly user name.
 *     @type string      $user_url             The user URL.
 *     @type string      $user_email           The user email address.
 *     @type string      $display_name         The user's display name.
 *                                             Default is the the user's username.
 *     @type string      $nickname             The user's nickname.
 *                                             Default is the the user's username.
 *     @type string      $first_name           The user's first name. For new users, will be used
 *                                             to build the first part of the user's display name
 *                                             if `$display_name` is not specified.
 *     @type string      $last_name            The user's last name. For new users, will be used
 *                                             to build the second part of the user's display name
 *                                             if `$display_name` is not specified.
 *     @type string      $description          The user's biographical description.
 *     @type string|bool $rich_editing         Whether to enable the rich-editor for the user.
 *                                             False if not empty.
 *     @type string|bool $comment_shortcuts    Whether to enable comment moderation keyboard
 *                                             shortcuts for the user. Default false.
 *     @type string      $admin_color          Admin color scheme for the user. Default 'fresh'.
 *     @type bool        $use_ssl              Whether the user should always access the admin over
 *                                             https. Default false.
 *     @type string      $user_registered      Date the user registered. Format is 'Y-m-d H:i:s'.
 *     @type string|bool $show_admin_bar_front Whether to display the Admin Bar for the user on the
 *                                             site's frontend. Default true.
 *     @type string      $role                 User's role.
 * }
 * @return int|HQ_Error The newly created user's ID or a HQ_Error object if the user could not
 *                      be created.
 */
function hq_insert_user( $userdata ) {
        global $hqdb;
        if ( $userdata instanceof stdClass ) {
                $userdata = get_object_vars( $userdata );
        } elseif ( $userdata instanceof HQ_User ) {
                $userdata = $userdata->to_array();
        }
        // Are we updating or creating?
        if ( ! empty( $userdata['ID'] ) ) {
                $ID = (int) $userdata['ID'];
                $update = true;
                $old_user_data = HQ_User::get_data_by( 'id', $ID );
                // hashed in hq_update_user(), plaintext if called directly
                $user_pass = $userdata['user_pass'];
        } else {
                $update = false;
                // Hash the password
                $user_pass = hq_hash_password( $userdata['user_pass'] );
        }

        $sanitized_user_login = sanitize_user( $userdata['user_login'], true );

        /**
         * Filter a username after it has been sanitized.
         *
         * This filter is called before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $sanitized_user_login Username after it has been sanitized.
         */
        $pre_user_login = apply_filters( 'pre_user_login', $sanitized_user_login );

        //Remove any non-printable chars from the login string to see if we have ended up with an empty username
        $user_login = trim( $pre_user_login );

        if ( empty( $user_login ) ) {
                return new HQ_Error('empty_user_login', __('Cannot create a user with an empty login name.') );
        }
        if ( ! $update && username_exists( $user_login ) ) {
                return new HQ_Error( 'existing_user_login', __( 'Sorry, that username already exists!' ) );
        }

        // If a nicename is provided, remove unsafe user characters before
        // using it. Otherwise build a nicename from the user_login.
        if ( ! empty( $userdata['user_nicename'] ) ) {
                $user_nicename = sanitize_user( $userdata['user_nicename'], true );
        } else {
                $user_nicename = $user_login;
        }

        $user_nicename = sanitize_title( $user_nicename );

        // Store values to save in user meta.
        $meta = array();

        /**
         * Filter a user's nicename before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $user_nicename The user's nicename.
         */
        $user_nicename = apply_filters( 'pre_user_nicename', $user_nicename );

        $raw_user_url = empty( $userdata['user_url'] ) ? '' : $userdata['user_url'];

        /**
         * Filter a user's URL before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $raw_user_url The user's URL.
         */
        $user_url = apply_filters( 'pre_user_url', $raw_user_url );

        $raw_user_email = empty( $userdata['user_email'] ) ? '' : $userdata['user_email'];

        /**
         * Filter a user's email before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $raw_user_email The user's email.
         */
        $user_email = apply_filters( 'pre_user_email', $raw_user_email );

        /*
         * If there is no update, just check for `email_exists`. If there is an update,
         * check if current email and new email are the same, or not, and check `email_exists`
         * accordingly.
         */
        if ( ( ! $update || ( ! empty( $old_user_data ) && 0 !== strcasecmp( $user_email, $old_user_data->user_email ) ) )
                && ! defined( 'HQ_IMPORTING' )
                && email_exists( $user_email )
        ) {
                return new HQ_Error( 'existing_user_email', __( 'Sorry, that email address is already used!' ) );
        }
        $nickname = empty( $userdata['nickname'] ) ? $user_login : $userdata['nickname'];

        /**
         * Filter a user's nickname before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $nickname The user's nickname.
         */
        $meta['nickname'] = apply_filters( 'pre_user_nickname', $nickname );

        $first_name = empty( $userdata['first_name'] ) ? '' : $userdata['first_name'];

        /**
         * Filter a user's first name before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $first_name The user's first name.
         */
        $meta['first_name'] = apply_filters( 'pre_user_first_name', $first_name );
        $last_name = empty( $userdata['last_name'] ) ? '' : $userdata['last_name'];

        /**
         * Filter a user's last name before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $last_name The user's last name.
         */
        $meta['last_name'] = apply_filters( 'pre_user_last_name', $last_name );

        if ( empty( $userdata['display_name'] ) ) {
                if ( $update ) {
                        $display_name = $user_login;
                } elseif ( $meta['first_name'] && $meta['last_name'] ) {
                        /* translators: 1: first name, 2: last name */
                        $display_name = sprintf( _x( '%1$s %2$s', 'Display name based on first name and last name' ), $meta['first_name'], $meta['last_name'] );
                } elseif ( $meta['first_name'] ) {
                        $display_name = $meta['first_name'];
                } elseif ( $meta['last_name'] ) {
                        $display_name = $meta['last_name'];
                } else {
                        $display_name = $user_login;
                }
        } else {
                $display_name = $userdata['display_name'];
        }

        /**
         * Filter a user's display name before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $display_name The user's display name.
         */
        $display_name = apply_filters( 'pre_user_display_name', $display_name );

        $description = empty( $userdata['description'] ) ? '' : $userdata['description'];

        /**
         * Filter a user's description before the user is created or updated.
         *
         * @since 0.0.1
         *
         * @param string $description The user's description.
         */
        $meta['description'] = apply_filters( 'pre_user_description', $description );

        $meta['rich_editing'] = empty( $userdata['rich_editing'] ) ? 'true' : $userdata['rich_editing'];

        $meta['comment_shortcuts'] = empty( $userdata['comment_shortcuts'] ) || 'false' === $userdata['comment_shortcuts'] ? 'false' : 'true';

        $admin_color = empty( $userdata['admin_color'] ) ? 'fresh' : $userdata['admin_color'];
        $meta['admin_color'] = preg_replace( '|[^a-z0-9 _.\-@]|i', '', $admin_color );

        $meta['use_ssl'] = empty( $userdata['use_ssl'] ) ? 0 : $userdata['use_ssl'];

        $user_registered = empty( $userdata['user_registered'] ) ? gmdate( 'Y-m-d H:i:s' ) : $userdata['user_registered'];
        $meta['show_admin_bar_front'] = empty( $userdata['show_admin_bar_front'] ) ? 'true' : $userdata['show_admin_bar_front'];

        $user_nicename_check = $hqdb->get_var( $hqdb->prepare("SELECT ID FROM $hqdb->users WHERE user_nicename = %s AND user_login != %s LIMIT 1" , $user_nicename, $user_login));

        if ( $user_nicename_check ) {
                $suffix = 2;
                while ($user_nicename_check) {
                        $alt_user_nicename = $user_nicename . "-$suffix";
                        $user_nicename_check = $hqdb->get_var( $hqdb->prepare("SELECT ID FROM $hqdb->users WHERE user_nicename = %s AND user_login != %s LIMIT 1" , $alt_user_nicename, $user_login));
                        $suffix++;
                }
                $user_nicename = $alt_user_nicename;
        }

        $compacted = compact( 'user_pass', 'user_email', 'user_url', 'user_nicename', 'display_name', 'user_registered' );
        $data = hq_unslash( $compacted );

        if ( $update ) {
                if ( $user_email !== $old_user_data->user_email ) {
                        $data['user_activation_key'] = '';
                }
                $hqdb->update( $hqdb->users, $data, compact( 'ID' ) );
                $user_id = (int) $ID;
        } else {
                $hqdb->insert( $hqdb->users, $data + compact( 'user_login' ) );
                $user_id = (int) $hqdb->insert_id;
        }

        $user = new HQ_User( $user_id );

        // Update user meta.
        foreach ( $meta as $key => $value ) {
                update_user_meta( $user_id, $key, $value );
        }

        foreach ( hq_get_user_contact_methods( $user ) as $key => $value ) {
                if ( isset( $userdata[ $key ] ) ) {
                        update_user_meta( $user_id, $key, $userdata[ $key ] );
                }
        }

        if ( isset( $userdata['role'] ) ) {
                $user->set_role( $userdata['role'] );
        } elseif ( ! $update ) {
                $user->set_role(get_option('default_role'));
        }
        //TODO: Goyo no cache
        //hq_cache_delete( $user_id, 'users' );
        //hq_cache_delete( $user_login, 'userlogins' );

        if ( $update ) {
                /**
                 * Fires immediately after an existing user is updated.
                 *
                 * @since 0.0.1
                 *
                 * @param int    $user_id       User ID.
                 * @param object $old_user_data Object containing user's data prior to update.
                 */
                do_action( 'profile_update', $user_id, $old_user_data );
        } else {
                /**
                 * Fires immediately after a new user is registered.
                 *
                 * @since 0.0.1
                 *
                 * @param int $user_id User ID.
                 */
                do_action( 'user_register', $user_id );
        }

        return $user_id;
}


/**
 * Checks whether the given email exists.
 *
 * @since 0.0.1
 *
 * @param string $email Email.
 * @return int|false The user's ID on success, and false on failure.
 */
function email_exists( $email ) {
        if ( $user = get_user_by( 'email', $email) ) {
                return $user->ID;
        }
        return false;
}

/**
 * Retrieve user meta field for a user.
 *
 * @since 0.0.1
 *
 * @param int    $user_id User ID.
 * @param string $key     Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool   $single  Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function get_user_meta($user_id, $key = '', $single = false) {
        return get_metadata('user', $user_id, $key, $single);
}


/**
 * Update user meta field based on user ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and user ID.
 *
 * If the meta field for the user does not exist, it will be added.
 *
 * @since 0.0.1
 *
 * @param int    $user_id    User ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function update_user_meta($user_id, $meta_key, $meta_value, $prev_value = '') {
        return update_metadata('user', $user_id, $meta_key, $meta_value, $prev_value);
}


/**
 * Set up the user contact methods.
 *
 * Default contact methods were removed in 3.6. A filter dictates contact methods.
 *
 * @since 0.0.1
 *
 * @param HQ_User $user Optional. HQ_User object.
 * @return array Array of contact methods and their labels.
 */
function hq_get_user_contact_methods( $user = null ) {
        $methods = array();
        if ( get_site_option( 'initial_db_version' ) < 23588 ) {
                $methods = array(
                        'aim'    => __( 'AIM' ),
                        'yim'    => __( 'Yahoo IM' ),
                        'jabber' => __( 'Jabber / Google Talk' )
                );
        }

        /**
         * Filter the user contact methods.
         *
         * @since 0.0.1
         *
         * @param array   $methods Array of contact methods and their labels.
         * @param HQ_User $user    HQ_User object.
         */
        return apply_filters( 'user_contactmethods', $methods, $user );
}

/**
 * The old private function for setting up user contact methods.
 *
 * @since 0.0.1
 * @access private
 */
function _hq_get_user_contactmethods( $user = null ) {
        return hq_get_user_contact_methods( $user );
}


/**
 * Add meta data field to a user.
 *
 * Post meta data is called "Custom Fields" on the Administration Screens.
 *
 * @since 0.0.1
 *
 * @param int    $user_id    User ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional, default is false. Whether the same key should not be added.
 * @return int|false Meta ID on success, false on failure.
 */
function add_user_meta($user_id, $meta_key, $meta_value, $unique = false) {
        return add_metadata('user', $user_id, $meta_key, $meta_value, $unique);
}


//TODO: **************************** functions ************************************************************

