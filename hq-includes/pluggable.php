<?php
/**
 * These functions can be replaced via plugins. If plugins do not redefine these
 * functions, then these will be used instead.
 *
 * @package HiveQueen
 */

if ( !function_exists('hq_set_current_user') ) :
/**
 * Changes the current user by ID or name.
 *
 * Set $id to null and specify a name if you do not know a user's ID.
 *
 * Some HivenQuenn functionality is based on the current user and not based on
 * the signed in user. Therefore, it opens the ability to edit and perform
 * actions on users who aren't signed in.
 *
 * @since 0.0.1
 * @global HQ_User $current_user The current user object which holds the user data.
 *
 * @param int    $id   User ID
 * @param string $name User's username
 * @return HQ_User Current user User object
 */
function hq_set_current_user($id, $name = '') {
        global $current_user;

        if ( isset( $current_user ) && ( $current_user instanceof HQ_User ) && ( $id == $current_user->ID ) )
                return $current_user;

        $current_user = new HQ_User( $id, $name );

        setup_userdata( $current_user->ID );

        /**
         * Fires after the current user is set.
         *
         * @since 0.0.1
         */
        do_action( 'set_current_user' );

        return $current_user;
}
endif;

if ( !function_exists('hq_get_current_user') ) :
/**
 * Retrieve the current user object.
 *
 * @since 0.0.1
 *
 * @global HQ_User $current_user
 *
 * @return HQ_User Current user HQ_User object
 */
function hq_get_current_user() {
        global $current_user;

        get_currentuserinfo();

        return $current_user;
}
endif;

if ( !function_exists('get_currentuserinfo') ) :
/**
 * Populate global variables with information about the currently logged in user.
 *
 * Will set the current user, if the current user is not set. The current user
 * will be set to the logged-in person. If no user is logged-in, then it will
 * set the current user to 0, which is invalid and won't have any permissions.
 *
 * @since 0.1
 *
 * @global HQ_User $current_user Checks if the current user is set
 *
 * @return false|void False on XML-RPC Request and invalid auth cookie.
 */
function get_currentuserinfo() {
        global $current_user;

        if ( ! empty( $current_user ) ) {
                if ( $current_user instanceof HQ_User )
                        return;

                // Upgrade stdClass to HQ_User
                if ( is_object( $current_user ) && isset( $current_user->ID ) ) {
                        $cur_id = $current_user->ID;
                        $current_user = null;
                        hq_set_current_user( $cur_id );
                        return;
                }

                // $current_user has a junk value. Force to HQ_User with ID 0.
                $current_user = null;
                hq_set_current_user( 0 );
                return false;
        }

        if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
                hq_set_current_user( 0 );
                return false;
        }

        /**
         * Filter the current user.
         *
         * The default filters use this to determine the current user from the
         * request's cookies, if available.
         *
         * Returning a value of false will effectively short-circuit setting
         * the current user.
         *
         * @since 0.0.1
         *
         * @param int|bool $user_id User ID if one has been determined, false otherwise.
         */
        $user_id = apply_filters( 'determine_current_user', false );
        if ( ! $user_id ) {
                hq_set_current_user( 0 );
                return false;
        }

        hq_set_current_user( $user_id );
}
endif;
if ( !function_exists('get_userdata') ) :
/**
 * Retrieve user info by user ID.
 *
 * @since 0.1
 *
 * @param int $user_id User ID
 * @return HQ_User|false HQ_User object on success, false on failure.
 */
function get_userdata( $user_id ) {
        return get_user_by( 'id', $user_id );
}
endif;

if ( !function_exists('get_user_by') ) :
/**
 * Retrieve user info by a given field
 *
 * @since 0.0.1
 *
 * @param string     $field The field to retrieve the user with. id | slug | email | login
 * @param int|string $value A value for $field. A user ID, slug, email address, or login name.
 * @return HQ_User|false HQ_User object on success, false on failure.
 */
function get_user_by( $field, $value ) {
        $userdata = HQ_User::get_data_by( $field, $value );

        if ( !$userdata )
                return false;

        $user = new HQ_User;
        $user->init( $userdata );

        return $user;
}
endif;

if ( !function_exists('cache_users') ) :
/**
 * Retrieve info for user lists to prevent multiple queries by get_userdata()
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb
 *
 * @param array $user_ids User ID numbers list
 */
function cache_users( $user_ids ) {
        global $hqdb;

        $clean = _get_non_cached_ids( $user_ids, 'users' );

        if ( empty( $clean ) )
                return;

        $list = implode( ',', $clean );

        $users = $hqdb->get_results( "SELECT * FROM $hqdb->users WHERE ID IN ($list)" );

        $ids = array();
        foreach ( $users as $user ) {
                update_user_caches( $user );
                $ids[] = $user->ID;
        }
        update_meta_cache( 'user', $ids );
}
endif;

if ( !function_exists( 'hq_mail' ) ) :
/**
 * Send mail, similar to PHP's mail
 *
 * A true return value does not automatically mean that the user received the
 * email successfully. It just only means that the method used was able to
 * process the request without any errors.
 *
 * Using the two 'hq_mail_from' and 'hq_mail_from_name' hooks allow from
 * creating a from address like 'Name <email@address.com>' when both are set. If
 * just 'hq_mail_from' is set, then just the email address will be used with no
 * name.
 *
 * The default content type is 'text/plain' which does not allow using HTML.
 * However, you can set the content type of the email by using the
 * 'hq_mail_content_type' filter.
 *
 * The default charset is based on the charset used on the blog. The charset can
 * be set using the 'hq_mail_charset' filter.
 *
 * @since 0.0.1
 *
 * @global PHPMailer $phpmailer
 *
 * @param string|array $to          Array or comma-separated list of email addresses to send message.
 * @param string       $subject     Email subject
 * @param string       $message     Message contents
 * @param string|array $headers     Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 * @return bool Whether the email contents were sent successfully.
 */
function hq_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        // Compact the input, apply the filters, and extract them back out

        /**
         * Filter the hq_mail() arguments.
         *
         * @since 0.0.1
         *
         * @param array $args A compacted array of hq_mail() arguments, including the "to" email,
         *                    subject, message, headers, and attachments values.
         */
        $atts = apply_filters( 'hq_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

        if ( isset( $atts['to'] ) ) {
                $to = $atts['to'];
        }

        if ( isset( $atts['subject'] ) ) {
                $subject = $atts['subject'];
        }

        if ( isset( $atts['message'] ) ) {
                $message = $atts['message'];
        }

        if ( isset( $atts['headers'] ) ) {
                $headers = $atts['headers'];
        }
        if ( isset( $atts['attachments'] ) ) {
                $attachments = $atts['attachments'];
        }

        if ( ! is_array( $attachments ) ) {
                $attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
        }
        global $phpmailer;

        // (Re)create it, if it's gone missing
        if ( ! ( $phpmailer instanceof PHPMailer ) ) {
                require_once ABSPATH . HQINC . '/class-phpmailer.php';
                require_once ABSPATH . HQINC . '/class-smtp.php';
                $phpmailer = new PHPMailer( true );
        }

        // Headers
        if ( empty( $headers ) ) {
                $headers = array();
        } else {
                if ( !is_array( $headers ) ) {
                        // Explode the headers out, so this function can take both
                        // string headers and an array of headers.
                        $tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
                } else {
                        $tempheaders = $headers;
                }
                $headers = array();
                $cc = array();
                $bcc = array();

                // If it's actually got contents
                if ( !empty( $tempheaders ) ) {
                        // Iterate through the raw headers
                        foreach ( (array) $tempheaders as $header ) {
                                if ( strpos($header, ':') === false ) {
                                        if ( false !== stripos( $header, 'boundary=' ) ) {
                                                $parts = preg_split('/boundary=/i', trim( $header ) );
                                                $boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
                                        }
                                        continue;
                                }
                                // Explode them out
                                list( $name, $content ) = explode( ':', trim( $header ), 2 );

                                // Cleanup crew
                                $name    = trim( $name    );
                                $content = trim( $content );

                                switch ( strtolower( $name ) ) {
                                        // Mainly for legacy -- process a From: header if it's there
                                        case 'from':
                                                $bracket_pos = strpos( $content, '<' );
                                                if ( $bracket_pos !== false ) {
                                                        // Text before the bracketed email is the "From" name.
                                                        if ( $bracket_pos > 0 ) {
                                                                $from_name = substr( $content, 0, $bracket_pos - 1 );
                                                                $from_name = str_replace( '"', '', $from_name );
                                                                $from_name = trim( $from_name );
                                                        }

                                                        $from_email = substr( $content, $bracket_pos + 1 );
                                                        $from_email = str_replace( '>', '', $from_email );
                                                        $from_email = trim( $from_email );

                                                // Avoid setting an empty $from_email.
                                                } elseif ( '' !== trim( $content ) ) {
                                                        $from_email = trim( $content );
                                                }
                                                break;
                                        case 'content-type':
                                                if ( strpos( $content, ';' ) !== false ) {
                                                        list( $type, $charset_content ) = explode( ';', $content );
                                                        $content_type = trim( $type );
                                                        if ( false !== stripos( $charset_content, 'charset=' ) ) {
                                                                $charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
                                                        } elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
                                                                $boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
                                                                $charset = '';
                                                        }

                                                // Avoid setting an empty $content_type.
                                                } elseif ( '' !== trim( $content ) ) {
                                                        $content_type = trim( $content );
                                                }
                                                break;
                                        case 'cc':
                                                $cc = array_merge( (array) $cc, explode( ',', $content ) );
                                                break;
                                        case 'bcc':
                                                $bcc = array_merge( (array) $bcc, explode( ',', $content ) );
                                                break;
                                        default:
                                                // Add it to our grand headers array
                                                $headers[trim( $name )] = trim( $content );
                                                break;
                                }
                        }
                }
        }

        // Empty out the values that may be set
        $phpmailer->ClearAllRecipients();
        $phpmailer->ClearAttachments();
        $phpmailer->ClearCustomHeaders();
        $phpmailer->ClearReplyTos();

        // From email and name
        // If we don't have a name from the input headers
        if ( !isset( $from_name ) )
                $from_name = 'HiveQueen';
        /* If we don't have an email from the input headers default to wordpress@$sitename
         * Some hosts will block outgoing mail from this address if it doesn't exist but
         * there's no easy alternative. Defaulting to admin_email might appear to be another
         * option but some hosts may refuse to relay mail from an unknown domain. See
         */

        if ( !isset( $from_email ) ) {
                // Get the site domain and get rid of www.
                $sitename = strtolower( $_SERVER['SERVER_NAME'] );
                if ( substr( $sitename, 0, 4 ) == 'www.' ) {
                        $sitename = substr( $sitename, 4 );
                }

                $from_email = 'hivequeen@' . $sitename;
        }

        /**
         * Filter the email address to send from.
         *
         * @since 0.0.1
         *
         * @param string $from_email Email address to send from.
         */
        $phpmailer->From = apply_filters( 'hq_mail_from', $from_email );

        /**
         * Filter the name to associate with the "from" email address.
         *
         * @since 0.0.1
         *
         * @param string $from_name Name associated with the "from" email address.
         */
        $phpmailer->FromName = apply_filters( 'hq_mail_from_name', $from_name );

        // Set destination addresses
        if ( !is_array( $to ) )
                $to = explode( ',', $to );

        foreach ( (array) $to as $recipient ) {
                try {
                        // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                        $recipient_name = '';
                        if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
                                if ( count( $matches ) == 3 ) {
                                        $recipient_name = $matches[1];
                                        $recipient = $matches[2];
                                }
                        }
                        $phpmailer->AddAddress( $recipient, $recipient_name);
                } catch ( phpmailerException $e ) {
                        continue;
                }
        }

        // Set mail's subject and body
        $phpmailer->Subject = $subject;
        $phpmailer->Body    = $message;

        // Add any CC and BCC recipients
        if ( !empty( $cc ) ) {
                foreach ( (array) $cc as $recipient ) {
                        try {
                                // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                                $recipient_name = '';
                                if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
                                        if ( count( $matches ) == 3 ) {
                                                $recipient_name = $matches[1];
                                                $recipient = $matches[2];
                                        }
                                }
                                $phpmailer->AddCc( $recipient, $recipient_name );
                        } catch ( phpmailerException $e ) {
                                continue;
                        }
                }
        }

        if ( !empty( $bcc ) ) {
                foreach ( (array) $bcc as $recipient) {
                        try {
                                // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                                $recipient_name = '';
                                if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
                                        if ( count( $matches ) == 3 ) {
                                                $recipient_name = $matches[1];
                                                $recipient = $matches[2];
                                        }
                                }
                                $phpmailer->AddBcc( $recipient, $recipient_name );
                        } catch ( phpmailerException $e ) {
                                continue;
                        }
                }
        }

        // Set to use PHP's mail()
        $phpmailer->IsMail();

        // Set Content-Type and charset
        // If we don't have a content-type from the input headers
        if ( !isset( $content_type ) )
                $content_type = 'text/plain';

        /**
         * Filter the hq_mail() content type.
         *
         * @since 0.0.1
         *
         * @param string $content_type Default hq_mail() content type.
         */
        $content_type = apply_filters( 'hq_mail_content_type', $content_type );

        $phpmailer->ContentType = $content_type;

        // Set whether it's plaintext, depending on $content_type
        if ( 'text/html' == $content_type )
                $phpmailer->IsHTML( true );
        // If we don't have a charset from the input headers
        if ( !isset( $charset ) )
                $charset = get_bloginfo( 'charset' );

        // Set the content-type and charset

        /**
         * Filter the default hq_mail() charset.
         *
         * @since 0.0.1
         *
         * @param string $charset Default email charset.
         */
        $phpmailer->CharSet = apply_filters( 'hq_mail_charset', $charset );

        // Set custom headers
        if ( !empty( $headers ) ) {
                foreach( (array) $headers as $name => $content ) {
                        $phpmailer->AddCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
                }

                if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
                        $phpmailer->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
        }

        if ( !empty( $attachments ) ) {
                foreach ( $attachments as $attachment ) {
                        try {
                                $phpmailer->AddAttachment($attachment);
                        } catch ( phpmailerException $e ) {
                                continue;
                        }
                }
        }

        /**
         * Fires after PHPMailer is initialized.
         *
         * @since 0.0.0
         *
         * @param PHPMailer &$phpmailer The PHPMailer instance, passed by reference.
         */
        do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

        // Send!
        try {
                return $phpmailer->Send();
        } catch ( phpmailerException $e ) {
                return false;
        }
}
endif;

if ( !function_exists('hq_authenticate') ) :
/**
 * Checks a user's login information and logs them in if it checks out.
 *
 * @since 0.0.1
 *
 * @param string $username User's username
 * @param string $password User's password
 * @return HQ_User|HQ_Error HQ_User object if login successful, otherwise HQ_Error object.
 */
function hq_authenticate($username, $password) {
        $username = sanitize_user($username);
        $password = trim($password);

        /**
         * Filter the user to authenticate.
         *
         * If a non-null value is passed, the filter will effectively short-circuit
         * authentication, returning an error instead.
         *
         * @since 0.0.1
         *
         * @param null|HQ_User $user     User to authenticate.
         * @param string       $username User login.
         * @param string       $password User password
         */
        $user = apply_filters( 'authenticate', null, $username, $password );

        if ( $user == null ) {
                // TODO what should the error message be? (Or would these even happen?)
                // Only needed if all authentication handlers fail to return anything.
                $user = new HQ_Error('authentication_failed', __('<strong>ERROR</strong>: Invalid username or incorrect password.'));
        }

        $ignore_codes = array('empty_username', 'empty_password');

        if (is_hq_error($user) && !in_array($user->get_error_code(), $ignore_codes) ) {
                /**
                 * Fires after a user login has failed.
                 *
                 * @since 0.0.1
                 *
                 * @param string $username User login.
                 */
                do_action( 'hq_login_failed', $username );
        }

        return $user;
}
endif;

if ( !function_exists('hq_logout') ) :
/**
 * Log the current user out.
 *
 * @since 0.0.1
 */
function hq_logout() {
        hq_destroy_current_session();
        hq_clear_auth_cookie();

        /**
         * Fires after a user is logged-out.
         *
         * @since 0.0.1
         */
        do_action( 'hq_logout' );
}
endif;

if ( !function_exists('hq_validate_auth_cookie') ) :
/**
 * Validates authentication cookie.
 *
 * The checks include making sure that the authentication cookie is set and
 * pulling in the contents (if $cookie is not used).
 *
 * Makes sure the cookie is not expired. Verifies the hash in cookie is what is
 * should be and compares the two.
 *
 * @since 0.0.1
 *
 * @global int $login_grace_period
 *
 * @param string $cookie Optional. If used, will validate contents instead of cookie's
 * @param string $scheme Optional. The cookie scheme to use: auth, secure_auth, or logged_in
 * @return false|int False if invalid cookie, User ID if valid.
 */
function hq_validate_auth_cookie($cookie = '', $scheme = '') {
        if ( ! $cookie_elements = hq_parse_auth_cookie($cookie, $scheme) ) {
                /**
                 * Fires if an authentication cookie is malformed.
                 *
                 * @since 0.0.1
                 *
                 * @param string $cookie Malformed auth cookie.
                 * @param string $scheme Authentication scheme. Values include 'auth', 'secure_auth',
                 *                       or 'logged_in'.
                 */
                do_action( 'auth_cookie_malformed', $cookie, $scheme );
                return false;
        }

        $scheme = $cookie_elements['scheme'];
        $username = $cookie_elements['username'];
        $hmac = $cookie_elements['hmac'];
        $token = $cookie_elements['token'];
        $expired = $expiration = $cookie_elements['expiration'];

        // Allow a grace period for POST and AJAX requests
        if ( defined('DOING_AJAX') || 'POST' == $_SERVER['REQUEST_METHOD'] ) {
                $expired += HOUR_IN_SECONDS;
        }

        // Quick check to see if an honest cookie has expired
        if ( $expired < time() ) {
                /**
                 * Fires once an authentication cookie has expired.
                 *
                 * @since 0.0.1
                 *
                 * @param array $cookie_elements An array of data for the authentication cookie.
                 */
                do_action( 'auth_cookie_expired', $cookie_elements );
                return false;
        }

        $user = get_user_by('login', $username);
        if ( ! $user ) {
                /**
                 * Fires if a bad username is entered in the user authentication process.
                 *
                 * @since 0.0.1
                 *
                 * @param array $cookie_elements An array of data for the authentication cookie.
                 */
                do_action( 'auth_cookie_bad_username', $cookie_elements );
                return false;
        }

        $pass_frag = substr($user->user_pass, 8, 4);

        $key = hq_hash( $username . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
        $hash = hash_hmac( $algo, $username . '|' . $expiration . '|' . $token, $key );

        if ( ! hash_equals( $hash, $hmac ) ) {
                /**
                 * Fires if a bad authentication cookie hash is encountered.
                 *
                 * @since 0.0.1
                 *
                 * @param array $cookie_elements An array of data for the authentication cookie.
                 */
                do_action( 'auth_cookie_bad_hash', $cookie_elements );
                return false;
        }

        $manager = HQ_Session_Tokens::get_instance( $user->ID );
        if ( ! $manager->verify( $token ) ) {
                do_action( 'auth_cookie_bad_session_token', $cookie_elements );
                return false;
        }

        // AJAX/POST grace period set above
        if ( $expiration < time() ) {
                $GLOBALS['login_grace_period'] = 1;
        }

        /**
         * Fires once an authentication cookie has been validated.
         *
         * @since 0.0.1
         *
         * @param array   $cookie_elements An array of data for the authentication cookie.
         * @param HQ_User $user            User object.
         */
        do_action( 'auth_cookie_valid', $cookie_elements, $user );

        return $user->ID;
}
endif;
if ( !function_exists('hq_generate_auth_cookie') ) :
/**
 * Generate authentication cookie contents.
 *
 * @since 0.0.1
 *
 * @param int    $user_id    User ID
 * @param int    $expiration Cookie expiration in seconds
 * @param string $scheme     Optional. The cookie scheme to use: auth, secure_auth, or logged_in
 * @param string $token      User's session token to use for this cookie
 * @return string Authentication cookie contents. Empty string if user does not exist.
 */
function hq_generate_auth_cookie( $user_id, $expiration, $scheme = 'auth', $token = '' ) {
        $user = get_userdata($user_id);
        if ( ! $user ) {
                return '';
        }

        if ( ! $token ) {
                $manager = HQ_Session_Tokens::get_instance( $user_id );
                $token = $manager->create( $expiration );
        }

        $pass_frag = substr($user->user_pass, 8, 4);

        $key = hq_hash( $user->user_login . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
        $hash = hash_hmac( $algo, $user->user_login . '|' . $expiration . '|' . $token, $key );

        $cookie = $user->user_login . '|' . $expiration . '|' . $token . '|' . $hash;

        /**
         * Filter the authentication cookie.
         *
         * @since 0.0.1
         *
         * @param string $cookie     Authentication cookie.
         * @param int    $user_id    User ID.
         * @param int    $expiration Authentication cookie expiration in seconds.
         * @param string $scheme     Cookie scheme used. Accepts 'auth', 'secure_auth', or 'logged_in'.
         * @param string $token      User's session token used.
         */
        return apply_filters( 'auth_cookie', $cookie, $user_id, $expiration, $scheme, $token );
}
endif;

if ( !function_exists('hq_parse_auth_cookie') ) :
/**
 * Parse a cookie into its components
 *
 * @since 0.0.1
 *
 * @param string $cookie
 * @param string $scheme Optional. The cookie scheme to use: auth, secure_auth, or logged_in
 * @return array|false Authentication cookie components
 */
function hq_parse_auth_cookie($cookie = '', $scheme = '') {
        if ( empty($cookie) ) {
                switch ($scheme){
                        case 'auth':
                                $cookie_name = AUTH_COOKIE;
                                break;
                        case 'secure_auth':
                                $cookie_name = SECURE_AUTH_COOKIE;
                                break;
                        case "logged_in":
                                $cookie_name = LOGGED_IN_COOKIE;
                                break;
                        default:
                                if ( is_ssl() ) {
                                        $cookie_name = SECURE_AUTH_COOKIE;
                                        $scheme = 'secure_auth';
                                } else {
                                        $cookie_name = AUTH_COOKIE;
                                        $scheme = 'auth';
                                }
            }

                if ( empty($_COOKIE[$cookie_name]) )
                        return false;
                $cookie = $_COOKIE[$cookie_name];
        }

        $cookie_elements = explode('|', $cookie);
        if ( count( $cookie_elements ) !== 4 ) {
                return false;
        }

        list( $username, $expiration, $token, $hmac ) = $cookie_elements;

        return compact( 'username', 'expiration', 'token', 'hmac', 'scheme' );
}
endif;

if ( !function_exists('hq_set_auth_cookie') ) :
/**
 * Sets the authentication cookies based on user ID.
 *
 * The $remember parameter increases the time that the cookie will be kept. The
 * default the cookie is kept without remembering is two days. When $remember is
 * set, the cookies will be kept for 14 days or two weeks.
 *
 * @since 0.0.1
 *
 * @param int    $user_id  User ID
 * @param bool   $remember Whether to remember the user
 * @param mixed  $secure   Whether the admin cookies should only be sent over HTTPS.
 *                         Default is_ssl().
 * @param string $token    Optional. User's session token to use for this cookie.
 */
function hq_set_auth_cookie( $user_id, $remember = false, $secure = '', $token = '' ) {
        if ( $remember ) {
                /**
                 * Filter the duration of the authentication cookie expiration period.
                 *
                 * @since 0.0.1
                 *
                 * @param int  $length   Duration of the expiration period in seconds.
                 * @param int  $user_id  User ID.
                 * @param bool $remember Whether to remember the user login. Default false.
                 */
                $expiration = time() + apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember );

                /*
                 * Ensure the browser will continue to send the cookie after the expiration time is reached.
                 * Needed for the login grace period in hq_validate_auth_cookie().
                 */
                $expire = $expiration + ( 12 * HOUR_IN_SECONDS );
        } else {
                /** This filter is documented in hq-includes/pluggable.php */
                $expiration = time() + apply_filters( 'auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, $remember );
                $expire = 0;
        }

        if ( '' === $secure ) {
                $secure = is_ssl();
        }

        // Frontend cookie is secure when the auth cookie is secure and the site's home URL is forced HTTPS.
        $secure_logged_in_cookie = $secure && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );

        /**
         * Filter whether the connection is secure.
         *
         * @since 0.0.1
         *
         * @param bool $secure  Whether the connection is secure.
         * @param int  $user_id User ID.
         */
        $secure = apply_filters( 'secure_auth_cookie', $secure, $user_id );

        /**
         * Filter whether to use a secure cookie when logged-in.
         *
         * @since 0.0.1
         *
         * @param bool $secure_logged_in_cookie Whether to use a secure cookie when logged-in.
         * @param int  $user_id                 User ID.
         * @param bool $secure                  Whether the connection is secure.
         */
        $secure_logged_in_cookie = apply_filters( 'secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure );

        if ( $secure ) {
                $auth_cookie_name = SECURE_AUTH_COOKIE;
                $scheme = 'secure_auth';
        } else {
                $auth_cookie_name = AUTH_COOKIE;
                $scheme = 'auth';
        }

        if ( '' === $token ) {
                $manager = HQ_Session_Tokens::get_instance( $user_id );
                $token   = $manager->create( $expiration );
        }

        $auth_cookie = hq_generate_auth_cookie( $user_id, $expiration, $scheme, $token );
        $logged_in_cookie = hq_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );

        /**
         * Fires immediately before the authentication cookie is set.
         *
         * @since 0.0.1
         *
         * @param string $auth_cookie Authentication cookie.
         * @param int    $expire      Login grace period in seconds. Default 43,200 seconds, or 12 hours.
         * @param int    $expiration  Duration in seconds the authentication cookie should be valid.
         *                            Default 1,209,600 seconds, or 14 days.
         * @param int    $user_id     User ID.
         * @param string $scheme      Authentication scheme. Values include 'auth', 'secure_auth', or 'logged_in'.
         */
        do_action( 'set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme );

        /**
         * Fires immediately before the secure authentication cookie is set.
         *
         * @since 0.0.1
         *
         * @param string $logged_in_cookie The logged-in cookie.
         * @param int    $expire           Login grace period in seconds. Default 43,200 seconds, or 12 hours.
         * @param int    $expiration       Duration in seconds the authentication cookie should be valid.
         *                                 Default 1,209,600 seconds, or 14 days.
         * @param int    $user_id          User ID.
         * @param string $scheme           Authentication scheme. Default 'logged_in'.
         */
        do_action( 'set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in' );

        setcookie($auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN, $secure, true);
        setcookie($auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, $secure, true);
        setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true);
        if ( COOKIEPATH != SITECOOKIEPATH )
                setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true);
}
endif;

if ( !function_exists('hq_clear_auth_cookie') ) :
/**
 * Removes all of the cookies associated with authentication.
 *
 * @since 0.0.1
 */
function hq_clear_auth_cookie() {
        /**
         * Fires just before the authentication cookies are cleared.
         *
         * @since 0.0.1
         */
        do_action( 'clear_auth_cookie' );

        setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH,   COOKIE_DOMAIN );
        setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH,   COOKIE_DOMAIN );
        setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN );
        setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN );
        setcookie( LOGGED_IN_COOKIE,   ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,          COOKIE_DOMAIN );
        setcookie( LOGGED_IN_COOKIE,   ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH,      COOKIE_DOMAIN );

        // Old cookies
        setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
        setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
        setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
        setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );

        // Even older cookies
        setcookie( USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
        setcookie( PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
        setcookie( USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
        setcookie( PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
}
endif;

if ( !function_exists('is_user_logged_in') ) :
/**
 * Checks if the current visitor is a logged in user.
 *
 * @since 0.0.1
 *
 * @return bool True if user is logged in, false if not logged in.
 */
function is_user_logged_in() {
        $user = hq_get_current_user();

        return $user->exists();
}
endif;

if ( !function_exists('auth_redirect') ) :
/**
 * Checks if a user is logged in, if not it redirects them to the login page.
 *
 * @since 0.0.1
 */
function auth_redirect() {
        // Checks if a user is logged in, if not redirects them to the login page

        $secure = ( is_ssl() || force_ssl_admin() );

        /**
         * Filter whether to use a secure authentication redirect.
         *
         * @since 0.0.1
         *
         * @param bool $secure Whether to use a secure authentication redirect. Default false.
         */
        $secure = apply_filters( 'secure_auth_redirect', $secure );

        // If https is required and request is http, redirect
        if ( $secure && !is_ssl() && false !== strpos($_SERVER['REQUEST_URI'], 'hq-admin') ) {
                if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
                        hq_redirect( set_url_scheme( $_SERVER['REQUEST_URI'], 'https' ) );
                        exit();
                } else {
                        hq_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
                        exit();
                }
        }

        if ( is_user_admin() ) {
                $scheme = 'logged_in';
        } else {
                /**
                 * Filter the authentication redirect scheme.
                 *
                 * @since 0.0.1
                 *
                 * @param string $scheme Authentication redirect scheme. Default empty.
                 */
                $scheme = apply_filters( 'auth_redirect_scheme', '' );
        }

        if ( $user_id = hq_validate_auth_cookie( '',  $scheme) ) {
                /**
                 * Fires before the authentication redirect.
                 *
                 * @since 0.0.1
                 *
                 * @param int $user_id User ID.
                 */
                do_action( 'auth_redirect', $user_id );

                // If the user wants ssl but the session is not ssl, redirect.
                if ( !$secure && get_user_option('use_ssl', $user_id) && false !== strpos($_SERVER['REQUEST_URI'], 'hq-admin') ) {
                        if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
                                hq_redirect( set_url_scheme( $_SERVER['REQUEST_URI'], 'https' ) );
                                exit();
                        } else {
                                hq_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
                                exit();
                        }
                }

                return;  // The cookie is good so we're done
        }

        // The cookie is no good so force login
        nocache_headers();

        $redirect = ( strpos( $_SERVER['REQUEST_URI'], '/options.php' ) && hq_get_referer() ) ? hq_get_referer() : set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

        $login_url = hq_login_url($redirect, true);

        hq_redirect($login_url);
        exit();
}
endif;

if ( !function_exists('check_admin_referer') ) :
/**
 * Makes sure that a user was referred from another admin page.
 *
 * To avoid security exploits.
 *
 * @since 0.0.1
 *
 * @param int|string $action    Action nonce.
 * @param string     $query_arg Optional. Key to check for nonce in `$_REQUEST` (since 2.5).
 *                              Default '_hqnonce'.
 * @return false|int False if the nonce is invalid, 1 if the nonce is valid and generated between
 *                   0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
 */
function check_admin_referer( $action = -1, $query_arg = '_hqnonce' ) {
        if ( -1 == $action )
                _doing_it_wrong( __FUNCTION__, __( 'You should specify a nonce action to be verified by using the first parameter.' ), '3.2' );

        $adminurl = strtolower(admin_url());
        $referer = strtolower(hq_get_referer());
        $result = isset($_REQUEST[$query_arg]) ? hq_verify_nonce($_REQUEST[$query_arg], $action) : false;

        /**
         * Fires once the admin request has been validated or not.
         *
         * @since 0.0.1
         *
         * @param string    $action The nonce action.
         * @param false|int $result False if the nonce is invalid, 1 if the nonce is valid and generated between
         *                          0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
         */
        do_action( 'check_admin_referer', $action, $result );

        if ( ! $result && ! ( -1 == $action && strpos( $referer, $adminurl ) === 0 ) ) {
                hq_nonce_ays( $action );
                die();
        }

        return $result;
}
endif;

if ( !function_exists('check_ajax_referer') ) :
/**
 * Verifies the AJAX request to prevent processing requests external of the blog.
 *
 * @since 0.0.1
 *
 * @param int|string   $action    Action nonce.
 * @param false|string $query_arg Optional. Key to check for the nonce in `$_REQUEST` (since 2.5). If false,
 *                                `$_REQUEST` values will be evaluated for '_ajax_nonce', and '_hqpnonce'
 *                                (in that order). Default false.
 * @param bool         $die       Optional. Whether to die early when the nonce cannot be verified.
 *                                Default true.
 * @return false|int False if the nonce is invalid, 1 if the nonce is valid and generated between
 *                   0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
 */
function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
        $nonce = '';

        if ( $query_arg && isset( $_REQUEST[ $query_arg ] ) )
                $nonce = $_REQUEST[ $query_arg ];
        elseif ( isset( $_REQUEST['_ajax_nonce'] ) )
                $nonce = $_REQUEST['_ajax_nonce'];
        elseif ( isset( $_REQUEST['_hqnonce'] ) )
                $nonce = $_REQUEST['_hqnonce'];

        $result = hq_verify_nonce( $nonce, $action );

        if ( $die && false === $result ) {
                if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
                        hq_die( -1 );
                else
                        die( '-1' );
        }

        /**
         * Fires once the AJAX request has been validated or not.
         *
         * @since 0.0.1
         *
         * @param string    $action The AJAX nonce action.
         * @param false|int $result False if the nonce is invalid, 1 if the nonce is valid and generated between
         *                          0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
         */
        do_action( 'check_ajax_referer', $action, $result );

        return $result;
}
endif;

if ( !function_exists('hq_redirect') ) :
/**
 * Redirects to another page.
 *
 * @since 0.0.1
 *
 * @global bool $is_IIS
 *
 * @param string $location The path to redirect to.
 * @param int    $status   Status code to use.
 * @return bool False if $location is not provided, true otherwise.
 */
function hq_redirect($location, $status = 302) {
        global $is_IIS;

        /**
         * Filter the redirect location.
         *
         * @since 0.0.1
         *
         * @param string $location The path to redirect to.
         * @param int    $status   Status code to use.
         */
        $location = apply_filters( 'hq_redirect', $location, $status );

        /**
         * Filter the redirect status code.
         *
         * @since 0.0.1
         *
         * @param int    $status   Status code to use.
         * @param string $location The path to redirect to.
         */
        $status = apply_filters( 'hq_redirect_status', $status, $location );

        if ( ! $location )
                return false;

        $location = hq_sanitize_redirect($location);

        if ( !$is_IIS && PHP_SAPI != 'cgi-fcgi' )
                status_header($status); // This causes problems on IIS and some FastCGI setups

        header("Location: $location", true, $status);

        return true;
}
endif;

if ( !function_exists('hq_sanitize_redirect') ) :
/**
 * Sanitizes a URL for use in a redirect.
 *
 * @since 0.0.1
 *
 * @return string redirect-sanitized URL
 **/
function hq_sanitize_redirect($location) {
        $regex = '/
                (
                        (?: [\xC2-\xDF][\x80-\xBF]        # double-byte sequences   110xxxxx 10xxxxxx
                        |   \xE0[\xA0-\xBF][\x80-\xBF]    # triple-byte sequences   1110xxxx 10xxxxxx * 2
                        |   [\xE1-\xEC][\x80-\xBF]{2}
                        |   \xED[\x80-\x9F][\x80-\xBF]
                        |   [\xEE-\xEF][\x80-\xBF]{2}
                        |   \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
                        |   [\xF1-\xF3][\x80-\xBF]{3}
                        |   \xF4[\x80-\x8F][\x80-\xBF]{2}
                ){1,40}                              # ...one or more times
                )/x';
        $location = preg_replace_callback( $regex, '_hq_sanitize_utf8_in_redirect', $location );
        $location = preg_replace('|[^a-z0-9-~+_.?#=&;,/:%!*\[\]()]|i', '', $location);
        $location = hq_kses_no_null($location);

        // remove %0d and %0a from location
        $strip = array('%0d', '%0a', '%0D', '%0A');
        return _deep_replace( $strip, $location );
}

/**
 * URL encode UTF-8 characters in a URL.
 *
 * @ignore
 * @since 0.0.1
 * @access private
 *
 * @see hq_sanitize_redirect()
 */
function _hq_sanitize_utf8_in_redirect( $matches ) {
        return urlencode( $matches[0] );
}
endif;

if ( !function_exists('hq_safe_redirect') ) :
/**
 * Performs a safe (local) redirect, using hq_redirect().
 *
 * Checks whether the $location is using an allowed host, if it has an absolute
 * path. A plugin can therefore set or remove allowed host(s) to or from the
 * list.
 *
 * If the host is not allowed, then the redirect defaults to hq-admin on the siteurl
 * instead. This prevents malicious redirects which redirect to another host,
 * but only used in a few places.
 *
 * @since 0.0.1
 */
function hq_safe_redirect($location, $status = 302) {

        // Need to look at the URL the way it will end up in hq_redirect()
        $location = hq_sanitize_redirect($location);

        /**
         * Filter the redirect fallback URL for when the provided redirect is not safe (local).
         *
         * @since 0.0.1
         *
         * @param string $fallback_url The fallback URL to use by default.
         * @param int    $status       The redirect status.
         */
        $location = hq_validate_redirect( $location, apply_filters( 'hq_safe_redirect_fallback', admin_url(), $status ) );

        hq_redirect($location, $status);
}
endif;

if ( !function_exists('hq_validate_redirect') ) :
/**
 * Validates a URL for use in a redirect.
 *
 * Checks whether the $location is using an allowed host, if it has an absolute
 * path. A plugin can therefore set or remove allowed host(s) to or from the
 * list.
 *
 * If the host is not allowed, then the redirect is to $default supplied
 *
 * @since 0.0.1
 *
 * @param string $location The redirect to validate
 * @param string $default  The value to return if $location is not allowed
 * @return string redirect-sanitized URL
 **/
function hq_validate_redirect($location, $default = '') {
        $location = trim( $location );
        // browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'
        if ( substr($location, 0, 2) == '//' )
                $location = 'http:' . $location;

        // In php 5 parse_url may fail if the URL query part contains http://, bug #38143
        $test = ( $cut = strpos($location, '?') ) ? substr( $location, 0, $cut ) : $location;

        $lp  = parse_url($test);
        // Give up if malformed URL
        if ( false === $lp )
                return $default;

        // Allow only http and https schemes. No data:, etc.
        if ( isset($lp['scheme']) && !('http' == $lp['scheme'] || 'https' == $lp['scheme']) )
                return $default;

        // Reject if scheme is set but host is not. This catches urls like https:host.com for which parse_url does not set the host field.
        if ( isset($lp['scheme'])  && !isset($lp['host']) )
                return $default;

        $hqp = parse_url(home_url());

        /**
         * Filter the whitelist of hosts to redirect to.
         *
         * @since 0.0.1
         *
         * @param array       $hosts An array of allowed hosts.
         * @param bool|string $host  The parsed host; empty if not isset.
         */
        $allowed_hosts = (array) apply_filters( 'allowed_redirect_hosts', array($hqp['host']), isset($lp['host']) ? $lp['host'] : '' );

        if ( isset($lp['host']) && ( !in_array($lp['host'], $allowed_hosts) && $lp['host'] != strtolower($hqp['host'])) )
                $location = $default;

        return $location;
}
endif;

if ( ! function_exists('hq_notify_postauthor') ) :
/**
 * Notify an author (and/or others) of a comment/trackback/pingback on a post.
 *
 * @since 0.0.1
 *
 * @param int    $comment_id Comment ID
 * @param string $deprecated Not used
 * @return bool True on completion. False if no email addresses were specified.
 */
function hq_notify_postauthor( $comment_id, $deprecated = null ) {
        if ( null !== $deprecated ) {
                _deprecated_argument( __FUNCTION__, '3.8' );
        }

        $comment = get_comment( $comment_id );
        if ( empty( $comment ) )
                return false;

        $post    = get_post( $comment->comment_post_ID );
        $author  = get_userdata( $post->post_author );

        // Who to notify? By default, just the post author, but others can be added.
        $emails = array();
        if ( $author ) {
                $emails[] = $author->user_email;
        }

        /**
         * Filter the list of email addresses to receive a comment notification.
         *
         * By default, only post authors are notified of comments. This filter allows
         * others to be added.
         *
         * @since 0.0.1
         *
         * @param array $emails     An array of email addresses to receive a comment notification.
         * @param int   $comment_id The comment ID.
         */
        $emails = apply_filters( 'comment_notification_recipients', $emails, $comment_id );
        $emails = array_filter( $emails );

        // If there are no addresses to send the comment to, bail.
        if ( ! count( $emails ) ) {
                return false;
        }

        // Facilitate unsetting below without knowing the keys.
        $emails = array_flip( $emails );

        /**
         * Filter whether to notify comment authors of their comments on their own posts.
         *
         * By default, comment authors aren't notified of their comments on their own
         * posts. This filter allows you to override that.
         *
         * @since 0.0.1
         *
         * @param bool $notify     Whether to notify the post author of their own comment.
         *                         Default false.
         * @param int  $comment_id The comment ID.
         */
        $notify_author = apply_filters( 'comment_notification_notify_author', false, $comment_id );

        // The comment was left by the author
        if ( $author && ! $notify_author && $comment->user_id == $post->post_author ) {
                unset( $emails[ $author->user_email ] );
        }

        // The author moderated a comment on their own post
        if ( $author && ! $notify_author && $post->post_author == get_current_user_id() ) {
                unset( $emails[ $author->user_email ] );
        }

        // The post author is no longer a member of the blog
        if ( $author && ! $notify_author && ! user_can( $post->post_author, 'read_post', $post->ID ) ) {
                unset( $emails[ $author->user_email ] );
        }

        // If there's no email to send the comment to, bail, otherwise flip array back around for use below
        if ( ! count( $emails ) ) {
                return false;
        } else {
                $emails = array_flip( $emails );
        }

        $comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

        // The blogname option is escaped with esc_html on the way into the database in sanitize_option
        // we want to reverse this for the plain text arena of emails.
        $blogname = hq_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        switch ( $comment->comment_type ) {
                case 'trackback':
                        $notify_message  = sprintf( __( 'New trackback on your post "%s"' ), $post->post_title ) . "\r\n";
                        /* translators: 1: website name, 2: website IP, 3: website hostname */
                        $notify_message .= sprintf( __('Website: %1$s (IP: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
                        $notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
                        $notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment->comment_content ) . "\r\n\r\n";
                        $notify_message .= __( 'You can see all trackbacks on this post here:' ) . "\r\n";
                        /* translators: 1: blog name, 2: post title */
                        $subject = sprintf( __('[%1$s] Trackback: "%2$s"'), $blogname, $post->post_title );
                        break;
                case 'pingback':
                        $notify_message  = sprintf( __( 'New pingback on your post "%s"' ), $post->post_title ) . "\r\n";
                        /* translators: 1: website name, 2: website IP, 3: website hostname */
                        $notify_message .= sprintf( __('Website: %1$s (IP: %2$s, %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
                        $notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
                        $notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment->comment_content ) . "\r\n\r\n";
                        $notify_message .= __( 'You can see all pingbacks on this post here:' ) . "\r\n";
                        /* translators: 1: blog name, 2: post title */
                        $subject = sprintf( __('[%1$s] Pingback: "%2$s"'), $blogname, $post->post_title );
                        break;
                default: // Comments
                        $notify_message  = sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
                        /* translators: 1: comment author, 2: author IP, 3: author domain */
                        $notify_message .= sprintf( __( 'Author: %1$s (IP: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
                        $notify_message .= sprintf( __( 'E-mail: %s' ), $comment->comment_author_email ) . "\r\n";
                        $notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
                        $notify_message .= sprintf( __('Comment: %s' ), "\r\n" . $comment->comment_content ) . "\r\n\r\n";
                        $notify_message .= __( 'You can see all comments on this post here:' ) . "\r\n";
                        /* translators: 1: blog name, 2: post title */
                        $subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
                        break;
        }
        $notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
        $notify_message .= sprintf( __('Permalink: %s'), get_comment_link( $comment_id ) ) . "\r\n";

        if ( user_can( $post->post_author, 'edit_comment', $comment_id ) ) {
                if ( EMPTY_TRASH_DAYS )
                        $notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
                else
                        $notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
                $notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";
        }

        $hq_email = 'hivequeen@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));

        if ( '' == $comment->comment_author ) {
                $from = "From: \"$blogname\" <$hq_email>";
                if ( '' != $comment->comment_author_email )
                        $reply_to = "Reply-To: $comment->comment_author_email";
        } else {
                $from = "From: \"$comment->comment_author\" <$hq_email>";
                if ( '' != $comment->comment_author_email )
                        $reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
        }

        $message_headers = "$from\n"
                . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

        if ( isset($reply_to) )
                $message_headers .= $reply_to . "\n";

        /**
         * Filter the comment notification email text.
         *
         * @since 0.0.1
         *
         * @param string $notify_message The comment notification email text.
         * @param int    $comment_id     Comment ID.
         */
        $notify_message = apply_filters( 'comment_notification_text', $notify_message, $comment_id );

        /**
         * Filter the comment notification email subject.
         *
         * @since 0.0.1
         *
         * @param string $subject    The comment notification email subject.
         * @param int    $comment_id Comment ID.
         */
        $subject = apply_filters( 'comment_notification_subject', $subject, $comment_id );

        /**
         * Filter the comment notification email headers.
         *
         * @since 0.0.
         *
         * @param string $message_headers Headers for the comment notification email.
         * @param int    $comment_id      Comment ID.
         */
        $message_headers = apply_filters( 'comment_notification_headers', $message_headers, $comment_id );

        foreach ( $emails as $email ) {
                @hq_mail( $email, hq_specialchars_decode( $subject ), $notify_message, $message_headers );
        }

        return true;
}
endif;

//TODO: *************************************************** functions ******************************************
