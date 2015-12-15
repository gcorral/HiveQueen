<?php
/**
 * Main HiveQueen API
 *
 * @package HiveQueen
 */

require( ABSPATH . HQINC . '/option.php' );

/**
 * Convert given date string into a different format.
 *
 * $format should be either a PHP date format string, e.g. 'U' for a Unix
 * timestamp, or 'G' for a Unix timestamp assuming that $date is GMT.
 *
 * If $translate is true then the given date and format string will
 * be passed to date_i18n() for translation.
 *
 * @since 0.1
 *
 * @param string $format    Format of the date to return.
 * @param string $date      Date string to convert.
 * @param bool   $translate Whether the return date should be translated. Default true.
 * @return string|int|bool Formatted date string or Unix timestamp. False if $date is empty.
 */
function mysql2date( $format, $date, $translate = true ) {
        if ( empty( $date ) )
                return false;

        if ( 'G' == $format )
                return strtotime( $date . ' +0000' );

        $i = strtotime( $date );

        if ( 'U' == $format )
                return $i;

        if ( $translate )
                return date_i18n( $format, $i );
        else
                return date( $format, $i );
}

/**
 * Retrieve the current time based on specified type.
 *
 * The 'mysql' type will return the time in the format for MySQL DATETIME field.
 * The 'timestamp' type will return the current timestamp.
 * Other strings will be interpreted as PHP date formats (e.g. 'Y-m-d').
 *
 * If $gmt is set to either '1' or 'true', then both types will use GMT time.
 * if $gmt is false, the output is adjusted with the GMT offset in the WordPress option.
 *
 * @since 0.0.1
 *
 * @param string   $type Type of time to retrieve. Accepts 'mysql', 'timestamp', or PHP date
 *                       format string (e.g. 'Y-m-d').
 * @param int|bool $gmt  Optional. Whether to use GMT timezone. Default false.
 * @return int|string Integer if $type is 'timestamp', string otherwise.
 */
function current_time( $type, $gmt = 0 ) {
        switch ( $type ) {
                case 'mysql':
                        return ( $gmt ) ? gmdate( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s', ( time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
                case 'timestamp':
                        return ( $gmt ) ? time() : time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
                default:
                        return ( $gmt ) ? date( $type ) : date( $type, time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
        }
}

/**
 * Retrieve the date in localized format, based on timestamp.
 *
 * If the locale specifies the locale month and weekday, then the locale will
 * take over the format for the date. If it isn't, then the date format string
 * will be used instead.
 *
 * @since 0.1
 *
 * @global HQ_Locale $hq_locale
 *
 * @param string   $dateformatstring Format to display the date.
 * @param bool|int $unixtimestamp    Optional. Unix timestamp. Default false.
 * @param bool     $gmt              Optional. Whether to use GMT timezone. Default false.
 *
 * @return string The date, translated if locale specifies it.
 */
function date_i18n( $dateformatstring, $unixtimestamp = false, $gmt = false ) {
        global $hq_locale;
        $i = $unixtimestamp;

        if ( false === $i ) {
                if ( ! $gmt )
                        $i = current_time( 'timestamp' );
                else
                        $i = time();
                // we should not let date() interfere with our
                // specially computed timestamp
                $gmt = true;
        }

        /*
         * Store original value for language with untypical grammars.
         */
        $req_format = $dateformatstring;

        $datefunc = $gmt? 'gmdate' : 'date';

        if ( ( !empty( $hq_locale->month ) ) && ( !empty( $hq_locale->weekday ) ) ) {
                $datemonth = $hq_locale->get_month( $datefunc( 'm', $i ) );
                $datemonth_abbrev = $hq_locale->get_month_abbrev( $datemonth );
                $dateweekday = $hq_locale->get_weekday( $datefunc( 'w', $i ) );
                $dateweekday_abbrev = $hq_locale->get_weekday_abbrev( $dateweekday );
                $datemeridiem = $hq_locale->get_meridiem( $datefunc( 'a', $i ) );
                $datemeridiem_capital = $hq_locale->get_meridiem( $datefunc( 'A', $i ) );
                $dateformatstring = ' '.$dateformatstring;
                $dateformatstring = preg_replace( "/([^\\\])D/", "\\1" . backslashit( $dateweekday_abbrev ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])F/", "\\1" . backslashit( $datemonth ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])l/", "\\1" . backslashit( $dateweekday ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])M/", "\\1" . backslashit( $datemonth_abbrev ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])a/", "\\1" . backslashit( $datemeridiem ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])A/", "\\1" . backslashit( $datemeridiem_capital ), $dateformatstring );

                $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
        }
        $timezone_formats = array( 'P', 'I', 'O', 'T', 'Z', 'e' );
        $timezone_formats_re = implode( '|', $timezone_formats );
        if ( preg_match( "/$timezone_formats_re/", $dateformatstring ) ) {
                $timezone_string = get_option( 'timezone_string' );
                if ( $timezone_string ) {
                        $timezone_object = timezone_open( $timezone_string );
                        $date_object = date_create( null, $timezone_object );
                        foreach( $timezone_formats as $timezone_format ) {
                                if ( false !== strpos( $dateformatstring, $timezone_format ) ) {
                                        $formatted = date_format( $date_object, $timezone_format );
                                        $dateformatstring = ' '.$dateformatstring;
                                        $dateformatstring = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $formatted ), $dateformatstring );
                                        $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
                                }
                        }
                }
        }
        $j = @$datefunc( $dateformatstring, $i );

        /**
         * Filter the date formatted based on the locale.
         *
         * @since 0.0.1
         *
         * @param string $j          Formatted date string.
         * @param string $req_format Format to display the date.
         * @param int    $i          Unix timestamp.
         * @param bool   $gmt        Whether to convert to GMT for time. Default false.
         */
        $j = apply_filters( 'date_i18n', $j, $req_format, $i, $gmt );
        return $j;
}

/**
 * Convert integer number to format based on the locale.
 *
 * @since 0.0.1
 *
 * @global HQ_Locale $hq_locale
 *
 * @param int $number   The number to convert based on locale.
 * @param int $decimals Optional. Precision of the number of decimal places. Default 0.
 * @return string Converted number in string format.
 */
function number_format_i18n( $number, $decimals = 0 ) {
        global $hq_locale;
        $formatted = number_format( $number, absint( $decimals ), $wp_locale->number_format['decimal_point'], $hq_locale->number_format['thousands_sep'] );

        /**
         * Filter the number formatted based on the locale.
         *
         * @since  0.0.1
         *
         * @param string $formatted Converted number in string format.
         */
        return apply_filters( 'number_format_i18n', $formatted );
}

/**
 * Convert number of bytes largest unit bytes will fit into.
 *
 * It is easier to read 1 kB than 1024 bytes and 1 MB than 1048576 bytes. Converts
 * number of bytes to human readable number by taking the number of that unit
 * that the bytes will go into it. Supports TB value.
 *
 * Please note that integers in PHP are limited to 32 bits, unless they are on
 * 64 bit architecture, then they have 64 bit size. If you need to place the
 * larger size then what PHP integer type will hold, then use a string. It will
 * be converted to a double, which should always have 64 bit length.
 *
 * Technically the correct unit names for powers of 1024 are KiB, MiB etc.
 *
 * @since 0.0.1
 *
 * @param int|string $bytes    Number of bytes. Note max integer size for integers.
 * @param int        $decimals Optional. Precision of number of decimal places. Default 0.
 * @return string|false False on failure. Number string on success.
 */
function size_format( $bytes, $decimals = 0 ) {
        $quant = array(
                // ========================= Origin ====
                'TB' => 1099511627776,  // pow( 1024, 4)
                'GB' => 1073741824,     // pow( 1024, 3)
                'MB' => 1048576,        // pow( 1024, 2)
                'kB' => 1024,           // pow( 1024, 1)
                'B'  => 1,              // pow( 1024, 0)
        );

        foreach ( $quant as $unit => $mag ) {
                if ( doubleval( $bytes ) >= $mag ) {
                        return number_format_i18n( $bytes / $mag, $decimals ) . ' ' . $unit;
                }
        }

        return false;
}

/**
 * Get the week start and end from the datetime or date string from MySQL.
 *
 * @since 0.1
 *
 * @param string     $mysqlstring   Date or datetime field type from MySQL.
 * @param int|string $start_of_week Optional. Start of the week as an integer. Default empty string.
 * @return array Keys are 'start' and 'end'.
 */
function get_weekstartend( $mysqlstring, $start_of_week = '' ) {
        // MySQL string year.
        $my = substr( $mysqlstring, 0, 4 );

        // MySQL string month.
        $mm = substr( $mysqlstring, 8, 2 );

        // MySQL string day.
        $md = substr( $mysqlstring, 5, 2 );

        // The timestamp for MySQL string day.
        $day = mktime( 0, 0, 0, $md, $mm, $my );

        // The day of the week from the timestamp.
        $weekday = date( 'w', $day );

        if ( !is_numeric($start_of_week) )
                $start_of_week = get_option( 'start_of_week' );

        if ( $weekday < $start_of_week )
                $weekday += 7;

        // The most recent week start day on or before $day.
        $start = $day - DAY_IN_SECONDS * ( $weekday - $start_of_week );

        // $start + 7 days - 1 second.
        $end = $start + 7 * DAY_IN_SECONDS - 1;
        return compact( 'start', 'end' );
}

/**
 * Unserialize value only if it was serialized.
 *
 * @since 0.0.1
 *
 * @param string $original Maybe unserialized original, if is needed.
 * @return mixed Unserialized data can be any type.
 */
function maybe_unserialize( $original ) {
        if ( is_serialized( $original ) ) // don't attempt to unserialize data that wasn't serialized going in
                return @unserialize( $original );
        return $original;
}

/**
 * Check value to find if it was serialized.
 *
 * If $data is not an string, then returned value will always be false.
 * Serialized data is always a string.
 *
 * @since 0.0.1
 *
 * @param string $data   Value to check to see if was serialized.
 * @param bool   $strict Optional. Whether to be strict about the end of the string. Default true.
 * @return bool False if not serialized and true if it was.
 */
function is_serialized( $data, $strict = true ) {
        // if it isn't a string, it isn't serialized.
        if ( ! is_string( $data ) ) {
                return false;
        }
        $data = trim( $data );
        if ( 'N;' == $data ) {
                return true;
        }
        if ( strlen( $data ) < 4 ) {
                return false;
        }
        if ( ':' !== $data[1] ) {
                return false;
        }
        if ( $strict ) {
                $lastc = substr( $data, -1 );
                if ( ';' !== $lastc && '}' !== $lastc ) {
                        return false;
                }
        } else {
                $semicolon = strpos( $data, ';' );
                $brace     = strpos( $data, '}' );
                // Either ; or } must exist.
                if ( false === $semicolon && false === $brace )
                        return false;
                // But neither must be in the first X characters.
                if ( false !== $semicolon && $semicolon < 3 )
                        return false;
                if ( false !== $brace && $brace < 4 )
                        return false;
        }
        $token = $data[0];
        switch ( $token ) {
                case 's' :
                        if ( $strict ) {
                                if ( '"' !== substr( $data, -2, 1 ) ) {
                                        return false;
                                }
                        } elseif ( false === strpos( $data, '"' ) ) {
                                return false;
                        }
                        // or else fall through
                case 'a' :
                case 'O' :
                        return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
                case 'b' :
                case 'i' :
                case 'd' :
                        $end = $strict ? '$' : '';
                        return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
        }
        return false;
}

/**
 * Check whether serialized data is of string type.
 *
 * @since 0.0.1
 *
 * @param string $data Serialized data.
 * @return bool False if not a serialized string, true if it is.
 */
function is_serialized_string( $data ) {
        // if it isn't a string, it isn't a serialized string.
        if ( ! is_string( $data ) ) {
                return false;
        }
        $data = trim( $data );
        if ( strlen( $data ) < 4 ) {
                return false;
        } elseif ( ':' !== $data[1] ) {
                return false;
        } elseif ( ';' !== substr( $data, -1 ) ) {
                return false;
        } elseif ( $data[0] !== 's' ) {
                return false;
        } elseif ( '"' !== substr( $data, -2, 1 ) ) {
                return false;
        } else {
                return true;
        }
}

/**
 * Serialize data, if needed.
 *
 * @since 0.0.1
 *
 * @param string|array|object $data Data that might be serialized.
 * @return mixed A scalar data
 */
function maybe_serialize( $data ) {
        if ( is_array( $data ) || is_object( $data ) )
                return serialize( $data );

        // Double serialization is required for backward compatibility.
        if ( is_serialized( $data, false ) )
                return serialize( $data );

        return $data;
}

/**
 * Retrieve post title from XMLRPC XML.
 *
 * If the title element is not part of the XML, then the default post title from
 * the $post_default_title will be used instead.
 *
 * @since 0.1
 *
 * @global string $post_default_title Default XML-RPC post title.
 *
 * @param string $content XMLRPC XML Request content
 * @return string Post title
 */
function xmlrpc_getposttitle( $content ) {
        global $post_default_title;
        if ( preg_match( '/<title>(.+?)<\/title>/is', $content, $matchtitle ) ) {
                $post_title = $matchtitle[1];
        } else {
                $post_title = $post_default_title;
        }
        return $post_title;
}

/**
 * Retrieve the post category or categories from XMLRPC XML.
 *
 * If the category element is not found, then the default post category will be
 * used. The return type then would be what $post_default_category. If the
 * category is found, then it will always be an array.
 *
 * @since 0.1
 *
 * @global string $post_default_category Default XML-RPC post category.
 *
 * @param string $content XMLRPC XML Request content
 * @return string|array List of categories or category name.
 */
function xmlrpc_getpostcategory( $content ) {
        global $post_default_category;
        if ( preg_match( '/<category>(.+?)<\/category>/is', $content, $matchcat ) ) {
                $post_category = trim( $matchcat[1], ',' );
                $post_category = explode( ',', $post_category );
        } else {
                $post_category = $post_default_category;
        }
        return $post_category;
}

/**
 * XMLRPC XML content without title and category elements.
 *
 * @since 0.1
 *
 * @param string $content XML-RPC XML Request content.
 * @return string XMLRPC XML Request content without title and category elements.
 */
function xmlrpc_removepostdata( $content ) {
        $content = preg_replace( '/<title>(.+?)<\/title>/si', '', $content );
        $content = preg_replace( '/<category>(.+?)<\/category>/si', '', $content );
        $content = trim( $content );
        return $content;
}

/**
 * Use RegEx to extract URLs from arbitrary content.
 *
 * @since 0.0.1
 *
 * @param string $content Content to extract URLs from.
 * @return array URLs found in passed string.
 */
function hq_extract_urls( $content ) {
        preg_match_all(
                "#([\"']?)("
                        . "(?:([\w-]+:)?//?)"
                        . "[^\s()<>]+"
                        . "[.]"
                        . "(?:"
                                . "\([\w\d]+\)|"
                                . "(?:"
                                        . "[^`!()\[\]{};:'\".,<>«»“”‘’\s]|"
                                        . "(?:[:]\d+)?/?"
                                . ")+"
                        . ")"
                . ")\\1#",
                $content,
                $post_links
        );

        $post_links = array_unique( array_map( 'html_entity_decode', $post_links[2] ) );

        return array_values( $post_links );
}

/**
 * Check content for video and audio links to add as enclosures.
 *
 * Will not add enclosures that have already been added and will
 * remove enclosures that are no longer in the post. This is called as
 * pingbacks and trackbacks.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb
 *
 * @param string $content Post Content.
 * @param int    $post_ID Post ID.
 */
function do_enclose( $content, $post_ID ) {
        global $hqdb;

        //TODO: Tidy this ghetto code up and make the debug code optional
        include_once( ABSPATH . HQINC . '/class-IXR.php' );

        $post_links = array();

        $pung = get_enclosed( $post_ID );

        $post_links_temp = hq_extract_urls( $content );

        foreach ( $pung as $link_test ) {
                if ( ! in_array( $link_test, $post_links_temp ) ) { // link no longer in post
                        $mids = $hqdb->get_col( $hqdb->prepare("SELECT meta_id FROM $hqdb->postmeta WHERE post_id = %d AND meta_key = 'enclosure' AND meta_value LIKE %s", $post_ID, $hqdb->esc_like( $link_test ) . '%') );
                        foreach ( $mids as $mid )
                                delete_metadata_by_mid( 'post', $mid );
                }
        }

        foreach ( (array) $post_links_temp as $link_test ) {
                if ( !in_array( $link_test, $pung ) ) { // If we haven't pung it already
                        $test = @parse_url( $link_test );
                        if ( false === $test )
                                continue;
                        if ( isset( $test['query'] ) )
                                $post_links[] = $link_test;
                        elseif ( isset($test['path']) && ( $test['path'] != '/' ) &&  ($test['path'] != '' ) )
                                $post_links[] = $link_test;
                }
        }

        foreach ( (array) $post_links as $url ) {
                if ( $url != '' && !$hqdb->get_var( $hqdb->prepare( "SELECT post_id FROM $hqdb->postmeta WHERE post_id = %d AND meta_key = 'enclosure' AND meta_value LIKE %s", $post_ID, $hqdb->esc_like( $url ) . '%' ) ) ) {

                        if ( $headers = hq_get_http_headers( $url) ) {
                                $len = isset( $headers['content-length'] ) ? (int) $headers['content-length'] : 0;
                                $type = isset( $headers['content-type'] ) ? $headers['content-type'] : '';
                                $allowed_types = array( 'video', 'audio' );

                                // Check to see if we can figure out the mime type from
                                // the extension
                                $url_parts = @parse_url( $url );
                                if ( false !== $url_parts ) {
                                        $extension = pathinfo( $url_parts['path'], PATHINFO_EXTENSION );
                                        if ( !empty( $extension ) ) {
                                                foreach ( hq_get_mime_types() as $exts => $mime ) {
                                                        if ( preg_match( '!^(' . $exts . ')$!i', $extension ) ) {
                                                                $type = $mime;
                                                                break;
                                                        }
                                                }
                                        }
                                }

                                if ( in_array( substr( $type, 0, strpos( $type, "/" ) ), $allowed_types ) ) {
                                        add_post_meta( $post_ID, 'enclosure', "$url\n$len\n$mime\n" );
                                }
                       }
                }
        }
}

/**
 * Perform a HTTP HEAD or GET request.
 *
 * If $file_path is a writable filename, this will do a GET request and write
 * the file to that path.
 *
 * @since 0.0.1
 *
 * @param string      $url       URL to fetch.
 * @param string|bool $file_path Optional. File path to write request to. Default false.
 * @param int         $red       Optional. The number of Redirects followed, Upon 5 being hit,
 *                               returns false. Default 1.
 * @return bool|string False on failure and string of headers if HEAD request.
 */
function hq_get_http( $url, $file_path = false, $red = 1 ) {
        @set_time_limit( 60 );

        if ( $red > 5 )
                return false;

        $options = array();
        $options['redirection'] = 5;

        if ( false == $file_path )
                $options['method'] = 'HEAD';
        else
                $options['method'] = 'GET';

        $response = hq_safe_remote_request( $url, $options );

        if ( is_hq_error( $response ) )
                return false;

        $headers = hq_remote_retrieve_headers( $response );
        $headers['response'] = hq_remote_retrieve_response_code( $response );

        // HQ_HTTP no longer follows redirects for HEAD requests.
        if ( 'HEAD' == $options['method'] && in_array($headers['response'], array(301, 302)) && isset( $headers['location'] ) ) {
                return hq_get_http( $headers['location'], $file_path, ++$red );
        }

        if ( false == $file_path )
                return $headers;

        // GET request - write it to the supplied filename
        $out_fp = fopen($file_path, 'w');
        if ( !$out_fp )
                return $headers;

        fwrite( $out_fp,  hq_remote_retrieve_body( $response ) );
        fclose($out_fp);
        clearstatcache();

        return $headers;
}

/**
 * Retrieve HTTP Headers from URL.
 *
 * @since 0.0.1
 *
 * @param string $url        URL to retrieve HTTP headers from.
 * @param bool   $deprecated Not Used.
 * @return bool|string False on failure, headers on success.
 */
function hq_get_http_headers( $url, $deprecated = false ) {
        if ( !empty( $deprecated ) )
                _deprecated_argument( __FUNCTION__, '2.7' );

        $response = hq_safe_remote_head( $url );

        if ( is_hq_error( $response ) )
                return false;

        return hq_remote_retrieve_headers( $response );
}

/**
 * Whether the publish date of the current post in the loop is different from the
 * publish date of the previous post in the loop.
 *
 * @since 0.1
 *
 * @global string $currentday  The day of the current post in the loop.
 * @global string $previousday The day of the previous post in the loop.
 *
 * @return int 1 when new day, 0 if not a new day.
 */
function is_new_day() {
        global $currentday, $previousday;
        if ( $currentday != $previousday )
                return 1;
        else
                return 0;
}

/**
 * Build URL query based on an associative and, or indexed array.
 *
 * This is a convenient function for easily building url queries. It sets the
 * separator to '&' and uses _http_build_query() function.
 *
 * @since 0.0.1
 *
 * @see _http_build_query() Used to build the query
 * @see http://us2.php.net/manual/en/function.http-build-query.php for more on what
 *              http_build_query() does.
 *
 * @param array $data URL-encode key/value pairs.
 * @return string URL-encoded string.
 */
function build_query( $data ) {
        return _http_build_query( $data, null, '&', '', false );
}

/**
 * From php.net (modified by Mark Jaquith to behave like the native PHP5 function).
 *
 * @since 0.0.1
 * @access private
 *
 * @see http://us1.php.net/manual/en/function.http-build-query.php
 *
 * @param array|object  $data       An array or object of data. Converted to array.
 * @param string        $prefix     Optional. Numeric index. If set, start parameter numbering with it.
 *                                  Default null.
 * @param string        $sep        Optional. Argument separator; defaults to 'arg_separator.output'.
 *                                  Default null.
 * @param string        $key        Optional. Used to prefix key name. Default empty.
 * @param bool          $urlencode  Optional. Whether to use urlencode() in the result. Default true.
 *
 * @return string The query string.
 */
function _http_build_query( $data, $prefix = null, $sep = null, $key = '', $urlencode = true ) {
        $ret = array();

        foreach ( (array) $data as $k => $v ) {
                if ( $urlencode)
                        $k = urlencode($k);
                if ( is_int($k) && $prefix != null )
                        $k = $prefix.$k;
                if ( !empty($key) )
                        $k = $key . '%5B' . $k . '%5D';
                if ( $v === null )
                        continue;
                elseif ( $v === false )
                        $v = '0';

                if ( is_array($v) || is_object($v) )
                        array_push($ret,_http_build_query($v, '', $sep, $k, $urlencode));
                elseif ( $urlencode )
                        array_push($ret, $k.'='.urlencode($v));
                else
                        array_push($ret, $k.'='.$v);
        }

        if ( null === $sep )
                $sep = ini_get('arg_separator.output');

        return implode($sep, $ret);
}

/**
 * Retrieve a modified URL query string.
 *
 * You can rebuild the URL and append a new query variable to the URL query by
 * using this function. You can also retrieve the full URL with query data.
 *
 * Adding a single key & value or an associative array. Setting a key value to
 * an empty string removes the key. Omitting oldquery_or_uri uses the $_SERVER
 * value. Additional values provided are expected to be encoded appropriately
 * with urlencode() or rawurlencode().
 *
 * @since 0.0.1
 *
 * @param string|array $param1 Either newkey or an associative_array.
 * @param string       $param2 Either newvalue or oldquery or URI.
 * @param string       $param3 Optional. Old query or URI.
 * @return string New URL query string.
 */
function add_query_arg() {
        $args = func_get_args();
        if ( is_array( $args[0] ) ) {
                if ( count( $args ) < 2 || false === $args[1] )
                        $uri = $_SERVER['REQUEST_URI'];
                else
                        $uri = $args[1];
        } else {
                if ( count( $args ) < 3 || false === $args[2] )
                        $uri = $_SERVER['REQUEST_URI'];
                else
                        $uri = $args[2];
        }

        if ( $frag = strstr( $uri, '#' ) )
                $uri = substr( $uri, 0, -strlen( $frag ) );
        else
                $frag = '';

        if ( 0 === stripos( $uri, 'http://' ) ) {
                $protocol = 'http://';
                $uri = substr( $uri, 7 );
        } elseif ( 0 === stripos( $uri, 'https://' ) ) {
                $protocol = 'https://';
                $uri = substr( $uri, 8 );
        } else {
                $protocol = '';
        }

        if ( strpos( $uri, '?' ) !== false ) {
                list( $base, $query ) = explode( '?', $uri, 2 );
                $base .= '?';
        } elseif ( $protocol || strpos( $uri, '=' ) === false ) {
                $base = $uri . '?';
                $query = '';
        } else {
                $base = '';
                $query = $uri;
        }

        hq_parse_str( $query, $qs );
        $qs = urlencode_deep( $qs ); // this re-URL-encodes things that were already in the query string
        if ( is_array( $args[0] ) ) {
                foreach ( $args[0] as $k => $v ) {
                        $qs[ $k ] = $v;
                }
        } else {
                $qs[ $args[0] ] = $args[1];
        }

        foreach ( $qs as $k => $v ) {
                if ( $v === false )
                        unset( $qs[$k] );
        }

        $ret = build_query( $qs );
        $ret = trim( $ret, '?' );
        $ret = preg_replace( '#=(&|$)#', '$1', $ret );
        $ret = $protocol . $base . $ret . $frag;
        $ret = rtrim( $ret, '?' );
        return $ret;
}


/**
 * Removes an item or list from the query string.
 *
 * @since 0.0.1
 *
 * @param string|array $key   Query key or keys to remove.
 * @param bool|string  $query Optional. When false uses the $_SERVER value. Default false.
 * @return string New URL query string.
 */
function remove_query_arg( $key, $query = false ) {
        if ( is_array( $key ) ) { // removing multiple keys
                foreach ( $key as $k )
                        $query = add_query_arg( $k, false, $query );
                return $query;
        }
        return add_query_arg( $key, false, $query );
}

/**
 * Walks the array while sanitizing the contents.
 *
 * @since 0.1
 *
 * @param array $array Array to walk while sanitizing contents.
 * @return array Sanitized $array.
 */
function add_magic_quotes( $array ) {
        foreach ( (array) $array as $k => $v ) {
                if ( is_array( $v ) ) {
                        $array[$k] = add_magic_quotes( $v );
                } else {
                        $array[$k] = addslashes( $v );
                }
        }
        return $array;
}

/**
 * HTTP request for URI to retrieve content.
 *
 * @since 0.0.1
 *
 * @see hq_safe_remote_get()
 *
 * @param string $uri URI/URL of web page to retrieve.
 * @return false|string HTTP content. False on failure.
 */
function hq_remote_fopen( $uri ) {
        $parsed_url = @parse_url( $uri );

        if ( !$parsed_url || !is_array( $parsed_url ) )
                return false;

        $options = array();
        $options['timeout'] = 10;

        $response = hq_safe_remote_get( $uri, $options );

        if ( is_hq_error( $response ) )
                return false;

        return hq_remote_retrieve_body( $response );
}

/**
 * Set up the HiveQueen query.
 *
 * @since 0.0.1
 *
 * @global HQ       $hq_locale
 * @global HQ_Query $hq_query
 * @global HQ_Query $hq_the_query
 *
 * @param string|array $query_vars Default HQ_Query arguments.
 */
function hq( $query_vars = '' ) {
        global $hq, $hq_query, $hq_the_query;
        $hq->main( $query_vars );

        if ( !isset($hq_the_query) )
                $hq_the_query = $hq_query;
}

/**
 * Retrieve the description for the HTTP status.
 *
 * @since 0.0.1
 *
 * @global array $hq_header_to_desc
 *
 * @param int $code HTTP status code.
 * @return string Empty string if not found, or description if found.
 */
function get_status_header_desc( $code ) {
        global $wp_header_to_desc;

        $code = absint( $code );

        if ( !isset( $wp_header_to_desc ) ) {
                $wp_header_to_desc = array(
                        100 => 'Continue',
                        101 => 'Switching Protocols',
                        102 => 'Processing',

                        200 => 'OK',
                        201 => 'Created',
                        202 => 'Accepted',
                        203 => 'Non-Authoritative Information',
                        204 => 'No Content',
                        205 => 'Reset Content',
                        206 => 'Partial Content',
                        207 => 'Multi-Status',
                        226 => 'IM Used',

                        300 => 'Multiple Choices',
                        301 => 'Moved Permanently',
                        302 => 'Found',
                        303 => 'See Other',
                        304 => 'Not Modified',
                        305 => 'Use Proxy',
                        306 => 'Reserved',
                        307 => 'Temporary Redirect',
                        400 => 'Bad Request',
                        401 => 'Unauthorized',
                        402 => 'Payment Required',
                        403 => 'Forbidden',
                        404 => 'Not Found',
                        405 => 'Method Not Allowed',
                        406 => 'Not Acceptable',
                        407 => 'Proxy Authentication Required',
                        408 => 'Request Timeout',
                        409 => 'Conflict',
                        410 => 'Gone',
                        411 => 'Length Required',
                        412 => 'Precondition Failed',
                        413 => 'Request Entity Too Large',
                        414 => 'Request-URI Too Long',
                        415 => 'Unsupported Media Type',
                        416 => 'Requested Range Not Satisfiable',
                        417 => 'Expectation Failed',
                        418 => 'I\'m a teapot',
                        422 => 'Unprocessable Entity',
                        423 => 'Locked',
                        424 => 'Failed Dependency',
                        426 => 'Upgrade Required',
                        428 => 'Precondition Required',
                        429 => 'Too Many Requests',
                        431 => 'Request Header Fields Too Large',

                        500 => 'Internal Server Error',
                        501 => 'Not Implemented',
                        502 => 'Bad Gateway',
                        503 => 'Service Unavailable',
                        504 => 'Gateway Timeout',
                        505 => 'HTTP Version Not Supported',
                        506 => 'Variant Also Negotiates',
                        507 => 'Insufficient Storage',
                        510 => 'Not Extended',
                        511 => 'Network Authentication Required',
                );
        }

        if ( isset( $hq_header_to_desc[$code] ) )
                return $hq_header_to_desc[$code];
        else
                return '';
}

/**
 * Set HTTP status header.
 *
 * @since 0.0.1
 *
 * @see get_status_header_desc()
 *
 * @param int $code HTTP status code.
 */
function status_header( $code ) {
        $description = get_status_header_desc( $code );

        if ( empty( $description ) )
                return;

        $protocol = $_SERVER['SERVER_PROTOCOL'];
        if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
                $protocol = 'HTTP/1.0';
        $status_header = "$protocol $code $description";
        if ( function_exists( 'apply_filters' ) )

                /**
                 * Filter an HTTP status header.
                 *
                 * @since 0.0.1
                 *
                 * @param string $status_header HTTP status header.
                 * @param int    $code          HTTP status code.
                 * @param string $description   Description for the status code.
                 * @param string $protocol      Server protocol.
                 */
                $status_header = apply_filters( 'status_header', $status_header, $code, $description, $protocol );

        @header( $status_header, true, $code );
}

/**
 * Get the header information to prevent caching.
 *
 * The several different headers cover the different ways cache prevention
 * is handled by different browsers
 *
 * @since 0.0.1
 *
 * @return array The associative array of header names and field values.
 */
function hq_get_nocache_headers() {
        $headers = array(
                'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT',
                'Cache-Control' => 'no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
        );

        if ( function_exists('apply_filters') ) {
                /**
                 * Filter the cache-controlling headers.
                 *
                 * @since 0.0.1
                 *
                 * @see hq_get_nocache_headers()
                 *
                 * @param array $headers {
                 *     Header names and field values.
                 *
                 *     @type string $Expires       Expires header.
                 *     @type string $Cache-Control Cache-Control header.
                 *     @type string $Pragma        Pragma header.
                 * }
                 */
                $headers = (array) apply_filters( 'nocache_headers', $headers );
        }
        $headers['Last-Modified'] = false;
        return $headers;
}

/**
 * Set the headers to prevent caching for the different browsers.
 *
 * Different browsers support different nocache headers, so several
 * headers must be sent so that all of them get the point that no
 * caching should occur.
 *
 * @since 0.0.1
 *
 * @see hq_get_nocache_headers()
 */
function nocache_headers() {
        $headers = hq_get_nocache_headers();

        unset( $headers['Last-Modified'] );

        // In PHP 5.3+, make sure we are not sending a Last-Modified header.
        if ( function_exists( 'header_remove' ) ) {
                @header_remove( 'Last-Modified' );
        } else {
                // In PHP 5.2, send an empty Last-Modified header, but only as a
                // last resort to override a header already sent. #WP23021
                foreach ( headers_list() as $header ) {
                        if ( 0 === stripos( $header, 'Last-Modified' ) ) {
                                $headers['Last-Modified'] = '';
                                break;
                        }
                }
        }

        foreach( $headers as $name => $field_value )
                @header("{$name}: {$field_value}");
}

/**
 * Set the headers for caching for 10 days with JavaScript content type.
 *
 * @since 0.0.1
 */
function cache_javascript_headers() {
        $expiresOffset = 10 * DAY_IN_SECONDS;

        header( "Content-Type: text/javascript; charset=" . get_bloginfo( 'charset' ) );
        header( "Vary: Accept-Encoding" ); // Handle proxies
        header( "Expires: " . gmdate( "D, d M Y H:i:s", time() + $expiresOffset ) . " GMT" );
}

/**
 * Retrieve the number of database queries during the WordPress execution.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb HiveQueen database abstraction object.
 *
 * @return int Number of database queries.
 */
function get_num_queries() {
        global $hqdb;
        return $hqdb->num_queries;
}

/**
 * Whether input is yes or no.
 *
 * Must be 'y' to be true.
 *
 * @since 0.0.1
 *
 * @param string $yn Character string containing either 'y' (yes) or 'n' (no).
 * @return bool True if yes, false on anything else.
 */
function bool_from_yn( $yn ) {
        return ( strtolower( $yn ) == 'y' );
}

/**
 * Load the feed template from the use of an action hook.
 *
 * If the feed action does not have a hook, then the function will die with a
 * message telling the visitor that the feed is not valid.
 *
 * It is better to only have one hook for each feed.
 *
 * @since 0.0.1
 *
 * @global HQ_Query $hq_query Used to tell if the use a comment feed.
 */
function do_feed() {
        global $hq_query;

        $feed = get_query_var( 'feed' );

        // Remove the pad, if present.
        $feed = preg_replace( '/^_+/', '', $feed );

        if ( $feed == '' || $feed == 'feed' )
                $feed = get_default_feed();

        $hook = 'do_feed_' . $feed;
        if ( ! has_action( $hook ) )
                hq_die( __( 'ERROR: This is not a valid feed template.' ), '', array( 'response' => 404 ) );

        /**
         * Fires once the given feed is loaded.
         *
         * The dynamic hook name, $hook, refers to the feed name.
         *
         * @since 0.0.1
         *
         * @param bool $is_comment_feed Whether the feed is a comment feed.
         */
        do_action( $hook, $hq_query->is_comment_feed );
}

/**
 * Load the RDF RSS 0.91 Feed template.
 *
 * @since 0.0.1
 *
 * @see load_template()
 */
function do_feed_rdf() {
        load_template( ABSPATH . HQINC . '/feed-rdf.php' );
}

/**
 * Load the RSS 1.0 Feed Template.
 *
 * @since 0.0.1
 *
 * @see load_template()
 */
function do_feed_rss() {
        load_template( ABSPATH . HQINC . '/feed-rss.php' );
}

/**
 * Load either the RSS2 comment feed or the RSS2 posts feed.
 *
 * @since 0.0.1
 *
 * @see load_template()
 *
 * @param bool $for_comments True for the comment feed, false for normal feed.
 */
function do_feed_rss2( $for_comments ) {
        if ( $for_comments )
                load_template( ABSPATH . HQINC . '/feed-rss2-comments.php' );
        else
                load_template( ABSPATH . HQINC . '/feed-rss2.php' );
}

/**
 * Load either Atom comment feed or Atom posts feed.
 *
 * @since 0.0.1
 *
 * @see load_template()
 *
 * @param bool $for_comments True for the comment feed, false for normal feed.
 */
function do_feed_atom( $for_comments ) {
        if ($for_comments)
                load_template( ABSPATH . HQINC . '/feed-atom-comments.php');
        else
                load_template( ABSPATH . HQINC . '/feed-atom.php' );
}

/**
 * Display the robots.txt file content.
 *
 * The echo content should be with usage of the permalinks or for creating the
 * robots.txt file.
 *
 * @since 0.0.1
 */
function do_robots() {
        header( 'Content-Type: text/plain; charset=utf-8' );

        /**
         * Fires when displaying the robots.txt file.
         *
         * @since 0.0.1
         */
        do_action( 'do_robotstxt' );

        $output = "User-agent: *\n";
        $public = get_option( 'blog_public' );
        if ( '0' == $public ) {
                $output .= "Disallow: /\n";
        } else {
                $site_url = parse_url( site_url() );
                $path = ( !empty( $site_url['path'] ) ) ? $site_url['path'] : '';
                $output .= "Disallow: $path/hq-admin/\n";
        }

        /**
         * Filter the robots.txt output.
         *
         * @since 0.0.1
         *
         * @param string $output Robots.txt output.
         * @param bool   $public Whether the site is considered "public".
         */
        echo apply_filters( 'robots_txt', $output, $public );
}

/**
 * Test whether HiveQueen is already installed.
 *
 * The cache will be checked first. If you have a cache plugin, which saves
 * the cache values, then this will work. If you use the default HiveQueen
 * cache, and the database goes away, then you might have problems.
 *
 * Checks for the 'siteurl' option for whether HiveQueen is installed.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb HiveQueen database abstraction object.
 *
 * @return bool Whether the blog is already installed.
 */
function is_hq_installed() {
        global $hqdb;

        /*
         * Check cache first. If options table goes away and we have true
         * cached, oh well.
         */
        //TODO: no cache 
        // if ( hq_cache_get( 'is_hq_installed' ) )
        //        return true;

        $suppress = $hqdb->suppress_errors();
        if ( ! defined( 'HQ_INSTALLING' ) ) {
                $alloptions = hq_load_alloptions();
        }
        // If siteurl is not set to autoload, check it specifically
        if ( !isset( $alloptions['siteurl'] ) )
                $installed = $hqdb->get_var( "SELECT option_value FROM $hqdb->options WHERE option_name = 'siteurl'" );
        else
                $installed = $alloptions['siteurl'];
        $hqdb->suppress_errors( $suppress );

        $installed = !empty( $installed );

        //TODO: no cache 
        //hq_cache_set( 'is_hq_installed', $installed );

        if ( $installed )
                return true;

        // If visiting repair.php, return true and let it take over.
        if ( defined( 'HQ_REPAIRING' ) )
                return true;

        $suppress = $hqdb->suppress_errors();

        /*
         * Loop over the HQ tables. If none exist, then scratch install is allowed.
         * If one or more exist, suggest table repair since we got here because the
         * options table could not be accessed.
         */
        $hq_tables = $hqdb->tables();
        foreach ( $hq_tables as $table ) {
                // The existence of custom user tables shouldn't suggest an insane state or prevent a clean install.
                if ( defined( 'CUSTOM_USER_TABLE' ) && CUSTOM_USER_TABLE == $table )
                        continue;
                if ( defined( 'CUSTOM_USER_META_TABLE' ) && CUSTOM_USER_META_TABLE == $table )
                        continue;

                if ( ! $hqdb->get_results( "DESCRIBE $table;" ) )
                        continue;

                // One or more tables exist. We are insane.

                hq_load_translations_early();

                // Die with a DB error.
                $hqdb->error = sprintf( __( 'One or more database tables are unavailable. The database may need to be <a href="%s">repaired</a>.' ), 'maint/repair.php?referrer=is_hq_installed' );
                dead_db();
        }

        $hqdb->suppress_errors( $suppress );
        // TODO: no chache 
        //hq_cache_set( 'is_hq_installed', false );

        return false;
}

/**
 * Retrieve URL with nonce added to URL query.
 *
 * @since 0.0.1
 *
 * @param string     $actionurl URL to add nonce action.
 * @param int|string $action    Optional. Nonce action name. Default -1.
 * @param string     $name      Optional. Nonce name. Default '_wpnonce'.
 * @return string Escaped URL with nonce action added.
 */
function hq_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
        $actionurl = str_replace( '&amp;', '&', $actionurl );
        return esc_html( add_query_arg( $name, hq_create_nonce( $action ), $actionurl ) );
}

/**
 * Retrieve or display nonce hidden field for forms.
 *
 * The nonce field is used to validate that the contents of the form came from
 * the location on the current site and not somewhere else. The nonce does not
 * offer absolute protection, but should protect against most cases. It is very
 * important to use nonce field in forms.
 *
 * The $action and $name are optional, but if you want to have better security,
 * it is strongly suggested to set those two parameters. It is easier to just
 * call the function without any parameters, because validation of the nonce
 * doesn't require any parameters, but since crackers know what the default is
 * it won't be difficult for them to find a way around your nonce and cause
 * damage.
 *
 * The input name will be whatever $name value you gave. The input value will be
 * the nonce creation value.
 *
 * @since 0.0.1
 *
 * @param int|string $action  Optional. Action name. Default -1.
 * @param string     $name    Optional. Nonce name. Default '_hqnonce'.
 * @param bool       $referer Optional. Whether to set the referer field for validation. Default true.
 * @param bool       $echo    Optional. Whether to display or return hidden form field. Default true.
 * @return string Nonce field HTML markup.
 */
function hq_nonce_field( $action = -1, $name = "_hqnonce", $referer = true , $echo = true ) {
        $name = esc_attr( $name );
        $nonce_field = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . hq_create_nonce( $action ) . '" />';

        if ( $referer )
                $nonce_field .= hq_referer_field( false );

        if ( $echo )
                echo $nonce_field;

        return $nonce_field;
}

/**
 * Retrieve or display referer hidden field for forms.
 *
 * The referer link is the current Request URI from the server super global. The
 * input name is '_hq_http_referer', in case you wanted to check manually.
 *
 * @since 0.0.1
 *
 * @param bool $echo Optional. Whether to echo or return the referer field. Default true.
 * @return string Referer field HTML markup.
 */
function hq_referer_field( $echo = true ) {
        $referer_field = '<input type="hidden" name="_hq_http_referer" value="'. esc_attr( hq_unslash( $_SERVER['REQUEST_URI'] ) ) . '" />';

        if ( $echo )
                echo $referer_field;
        return $referer_field;
}

/**
 * Retrieve or display original referer hidden field for forms.
 *
 * The input name is '_hq_original_http_referer' and will be either the same
 * value of hq_referer_field(), if that was posted already or it will be the
 * current page, if it doesn't exist.
 *
 * @since 0.0.1
 *
 * @param bool   $echo         Optional. Whether to echo the original http referer. Default true.
 * @param string $jump_back_to Optional. Can be 'previous' or page you want to jump back to.
 *                             Default 'current'.
 * @return string Original referer field.
 */
function hq_original_referer_field( $echo = true, $jump_back_to = 'current' ) {
        if ( ! $ref = hq_get_original_referer() ) {
                $ref = 'previous' == $jump_back_to ? hq_get_referer() : hq_unslash( $_SERVER['REQUEST_URI'] );
        }
        $orig_referer_field = '<input type="hidden" name="_hq_original_http_referer" value="' . esc_attr( $ref ) . '" />';
        if ( $echo )
                echo $orig_referer_field;
        return $orig_referer_field;
}

/**
 * Retrieve referer from '_hq_http_referer' or HTTP referer.
 *
 * If it's the same as the current request URL, will return false.
 *
 * @since 0.0.1
 *
 * @return false|string False on failure. Referer URL on success.
 */
function hq_get_referer() {
        if ( ! function_exists( 'hq_validate_redirect' ) )
                return false;
        $ref = false;
        if ( ! empty( $_REQUEST['_hq_http_referer'] ) )
                $ref = hq_unslash( $_REQUEST['_hq_http_referer'] );
        elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) )
                $ref = hq_unslash( $_SERVER['HTTP_REFERER'] );

        if ( $ref && $ref !== hq_unslash( $_SERVER['REQUEST_URI'] ) )
                return hq_validate_redirect( $ref, false );
        return false;
}

/**
 * Retrieve original referer that was posted, if it exists.
 *
 * @since 0.0.1
 *
 * @return string|false False if no original referer or original referer if set.
 */
function hq_get_original_referer() {
        if ( ! empty( $_REQUEST['_hq_original_http_referer'] ) && function_exists( 'hq_validate_redirect' ) )
                return hq_validate_redirect( hq_unslash( $_REQUEST['_hq_original_http_referer'] ), false );
        return false;
}

/**
 * Recursive directory creation based on full path.
 *
 * Will attempt to set permissions on folders.
 *
 * @since 0.0.1
 *
 * @param string $target Full path to attempt to create.
 * @return bool Whether the path was created. True if path already exists.
 */
function hq_mkdir_p( $target ) {
        $wrapper = null;

        // Strip the protocol.
        if ( hq_is_stream( $target ) ) {
                list( $wrapper, $target ) = explode( '://', $target, 2 );
        }

        // From php.net/mkdir user contributed notes.
        $target = str_replace( '//', '/', $target );

        // Put the wrapper back on the target.
        if ( $wrapper !== null ) {
                $target = $wrapper . '://' . $target;
        }

        /*
         * Safe mode fails with a trailing slash under certain PHP versions.
         * Use rtrim() instead of untrailingslashit to avoid formatting.php dependency.
         */
        $target = rtrim($target, '/');
        if ( empty($target) )
                $target = '/';

        if ( file_exists( $target ) )
                return @is_dir( $target );

        // We need to find the permissions of the parent folder that exists and inherit that.
        $target_parent = dirname( $target );
        while ( '.' != $target_parent && ! is_dir( $target_parent ) ) {
                $target_parent = dirname( $target_parent );
        }

        // Get the permission bits.
        if ( $stat = @stat( $target_parent ) ) {
                $dir_perms = $stat['mode'] & 0007777;
        } else {
                $dir_perms = 0777;
        }

        if ( @mkdir( $target, $dir_perms, true ) ) {

                /*
                 * If a umask is set that modifies $dir_perms, we'll have to re-set
                 * the $dir_perms correctly with chmod()
                 */
                if ( $dir_perms != ( $dir_perms & ~umask() ) ) {
                        $folder_parts = explode( '/', substr( $target, strlen( $target_parent ) + 1 ) );
                        for ( $i = 1, $c = count( $folder_parts ); $i <= $c; $i++ ) {
                                @chmod( $target_parent . '/' . implode( '/', array_slice( $folder_parts, 0, $i ) ), $dir_perms );
                        }
                }

                return true;
        }

        return false;
}

/**
 * Test if a give filesystem path is absolute.
 *
 * For example, '/foo/bar', or 'c:\windows'.
 *
 * @since 0.0.1
 *
 * @param string $path File path.
 * @return bool True if path is absolute, false is not absolute.
 */
function path_is_absolute( $path ) {
        /*
         * This is definitive if true but fails if $path does not exist or contains
         * a symbolic link.
         */
        if ( realpath($path) == $path )
                return true;

        if ( strlen($path) == 0 || $path[0] == '.' )
                return false;

        // Windows allows absolute paths like this.
        if ( preg_match('#^[a-zA-Z]:\\\\#', $path) )
                return true;

        // A path starting with / or \ is absolute; anything else is relative.
        return ( $path[0] == '/' || $path[0] == '\\' );
}

/**
 * Join two filesystem paths together.
 *
 * For example, 'give me $path relative to $base'. If the $path is absolute,
 * then it the full path is returned.
 *
 * @since 0.0.1
 *
 * @param string $base Base path.
 * @param string $path Path relative to $base.
 * @return string The path with the base or absolute path.
 */
function path_join( $base, $path ) {
        if ( path_is_absolute($path) )
                return $path;

        return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Normalize a filesystem path.
 *
 * Replaces backslashes with forward slashes for Windows systems, and ensures
 * no duplicate slashes exist.
 *
 * @since 0.0.1
 *
 * @param string $path Path to normalize.
 * @return string Normalized path.
 */
function hq_normalize_path( $path ) {
        $path = str_replace( '\\', '/', $path );
        $path = preg_replace( '|/+|','/', $path );
        return $path;
}

/**
 * Determine a writable directory for temporary files.
 *
 * Function's preference is the return value of sys_get_temp_dir(),
 * followed by your PHP temporary upload directory, followed by HQ_CONTENT_DIR,
 * before finally defaulting to /tmp/
 *
 * In the event that this function does not find a writable location,
 * It may be overridden by the HQ_TEMP_DIR constant in your hq-config.php file.
 *
 * @since 0.0.1
 *
 * @staticvar string $temp
 *
 * @return string Writable temporary directory.
 */
function get_temp_dir() {
        static $temp = '';
        if ( defined('HQ_TEMP_DIR') )
                return trailingslashit(HQ_TEMP_DIR);

        if ( $temp )
                return trailingslashit( $temp );

        if ( function_exists('sys_get_temp_dir') ) {
                $temp = sys_get_temp_dir();
                if ( @is_dir( $temp ) && hq_is_writable( $temp ) )
                        return trailingslashit( $temp );
        }

        $temp = ini_get('upload_tmp_dir');
        if ( @is_dir( $temp ) && hq_is_writable( $temp ) )
                return trailingslashit( $temp );

        $temp = HQ_CONTENT_DIR . '/';
        if ( is_dir( $temp ) && hq_is_writable( $temp ) )
                return $temp;

        return '/tmp/';
}

/**
 * Determine if a directory is writable.
 *
 * This function is used to work around certain ACL issues in PHP primarily
 * affecting Windows Servers.
 *
 * @since 0.0.1
 *
 * @see win_is_writable()
 *
 * @param string $path Path to check for write-ability.
 * @return bool Whether the path is writable.
 */
function hq_is_writable( $path ) {
        if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) )
                return win_is_writable( $path );
        else
                return @is_writable( $path );
}

/**
 * Workaround for Windows bug in is_writable() function
 *
 * PHP has issues with Windows ACL's for determine if a
 * directory is writable or not, this works around them by
 * checking the ability to open files rather than relying
 * upon PHP to interprate the OS ACL.
 *
 * @since 0.0.1
 *
 * @see http://bugs.php.net/bug.php?id=27609
 * @see http://bugs.php.net/bug.php?id=30931
 *
 * @param string $path Windows path to check for write-ability.
 * @return bool Whether the path is writable.
 */
function win_is_writable( $path ) {

        if ( $path[strlen( $path ) - 1] == '/' ) { // if it looks like a directory, check a random file within the directory
                return win_is_writable( $path . uniqid( mt_rand() ) . '.tmp');
        } elseif ( is_dir( $path ) ) { // If it's a directory (and not a file) check a random file within the directory
                return win_is_writable( $path . '/' . uniqid( mt_rand() ) . '.tmp' );
        }
        // check tmp file for read/write capabilities
        $should_delete_tmp_file = !file_exists( $path );
        $f = @fopen( $path, 'a' );
        if ( $f === false )
                return false;
        fclose( $f );
        if ( $should_delete_tmp_file )
                unlink( $path );
        return true;
}

/**
 * Get an array containing the current upload directory's path and url.
 *
 * Checks the 'upload_path' option, which should be from the web root folder,
 * and if it isn't empty it will be used. If it is empty, then the path will be
 * 'HQ_CONTENT_DIR/uploads'. If the 'UPLOADS' constant is defined, then it will
 * override the 'upload_path' option and 'HQ_CONTENT_DIR/uploads' path.
 *
 * The upload URL path is set either by the 'upload_url_path' option or by using
 * the 'HQ_CONTENT_URL' constant and appending '/uploads' to the path.
 *
 * If the 'uploads_use_yearmonth_folders' is set to true (checkbox if checked in
 * the administration settings panel), then the time will be used. The format
 * will be year first and then month.
 *
 * If the path couldn't be created, then an error will be returned with the key
 * 'error' containing the error message. The error suggests that the parent
 * directory is not writable by the server.
 *
 * On success, the returned array will have many indices:
 * 'path' - base directory and sub directory or full path to upload directory.
 * 'url' - base url and sub directory or absolute URL to upload directory.
 * 'subdir' - sub directory if uploads use year/month folders option is on.
 * 'basedir' - path without subdir.
 * 'baseurl' - URL path without subdir.
 * 'error' - set to false.
 *
 * @since 0.0.1
 *
 * @param string $time Optional. Time formatted in 'yyyy/mm'. Default null.
 * @return array See above for description.
 */
function hq_upload_dir( $time = null ) {
        $siteurl = get_option( 'siteurl' );
        $upload_path = trim( get_option( 'upload_path' ) );

        if ( empty( $upload_path ) || 'hq-content/uploads' == $upload_path ) {
                $dir = HQ_CONTENT_DIR . '/uploads';
        } elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
                // $dir is absolute, $upload_path is (maybe) relative to ABSPATH
                $dir = path_join( ABSPATH, $upload_path );
        } else {
                $dir = $upload_path;
        }

        if ( !$url = get_option( 'upload_url_path' ) ) {
                if ( empty($upload_path) || ( 'hq-content/uploads' == $upload_path ) || ( $upload_path == $dir ) )
                        $url = HQ_CONTENT_URL . '/uploads';
                else
                        $url = trailingslashit( $siteurl ) . $upload_path;
        }

        /*
         * Honor the value of UPLOADS. This happens as long as ms-files rewriting is disabled.
         * We also sometimes obey UPLOADS when rewriting is enabled -- see the next block.
         */
        if ( defined( 'UPLOADS' ) && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
                $dir = ABSPATH . UPLOADS;
                $url = trailingslashit( $siteurl ) . UPLOADS;
        }

        $basedir = $dir;
        $baseurl = $url;

        $subdir = '';
        if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
                // Generate the yearly and monthly dirs
                if ( !$time )
                        $time = current_time( 'mysql' );
                $y = substr( $time, 0, 4 );
                $m = substr( $time, 5, 2 );
                $subdir = "/$y/$m";
        }

        $dir .= $subdir;
        $url .= $subdir;

        /**
         * Filter the uploads directory data.
         *
         * @since 0.0.1
         *
         * @param array $uploads Array of upload directory data with keys of 'path',
         *                       'url', 'subdir, 'basedir', and 'error'.
         */
        $uploads = apply_filters( 'upload_dir',
                array(
                        'path'    => $dir,
                        'url'     => $url,
                        'subdir'  => $subdir,
                        'basedir' => $basedir,
                        'baseurl' => $baseurl,
                        'error'   => false,
                ) );

        // Make sure we have an uploads directory.
        if ( ! hq_mkdir_p( $uploads['path'] ) ) {
                if ( 0 === strpos( $uploads['basedir'], ABSPATH ) )
                        $error_path = str_replace( ABSPATH, '', $uploads['basedir'] ) . $uploads['subdir'];
                else
                        $error_path = basename( $uploads['basedir'] ) . $uploads['subdir'];

                $message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $error_path );
                $uploads['error'] = $message;
        }

        return $uploads;
}


/**
 * Get a filename that is sanitized and unique for the given directory.
 *
 * If the filename is not unique, then a number will be added to the filename
 * before the extension, and will continue adding numbers until the filename is
 * unique.
 *
 * The callback is passed three parameters, the first one is the directory, the
 * second is the filename, and the third is the extension.
 *
 * @since 0.0.1
 *
 * @param string   $dir                      Directory.
 * @param string   $filename                 File name.
 * @param callback $unique_filename_callback Callback. Default null.
 * @return string New filename, if given wasn't unique.
 */
function hq_unique_filename( $dir, $filename, $unique_filename_callback = null ) {
        // Sanitize the file name before we begin processing.
        $filename = sanitize_file_name($filename);

        // Separate the filename into a name and extension.
        $info = pathinfo($filename);
        $ext = !empty($info['extension']) ? '.' . $info['extension'] : '';
        $name = basename($filename, $ext);

        // Edge case: if file is named '.ext', treat as an empty name.
        if ( $name === $ext )
                $name = '';

        /*
         * Increment the file number until we have a unique file to save in $dir.
         * Use callback if supplied.
         */
        if ( $unique_filename_callback && is_callable( $unique_filename_callback ) ) {
                $filename = call_user_func( $unique_filename_callback, $dir, $name, $ext );
        } else {
                $number = '';

                // Change '.ext' to lower case.
                if ( $ext && strtolower($ext) != $ext ) {
                        $ext2 = strtolower($ext);
                        $filename2 = preg_replace( '|' . preg_quote($ext) . '$|', $ext2, $filename );

                        // Check for both lower and upper case extension or image sub-sizes may be overwritten.
                        while ( file_exists($dir . "/$filename") || file_exists($dir . "/$filename2") ) {
                                $new_number = $number + 1;
                                $filename = str_replace( "$number$ext", "$new_number$ext", $filename );
                                $filename2 = str_replace( "$number$ext2", "$new_number$ext2", $filename2 );
                                $number = $new_number;
                        }
                        return $filename2;
                }

                while ( file_exists( $dir . "/$filename" ) ) {
                        if ( '' == "$number$ext" )
                                $filename = $filename . ++$number . $ext;
                        else
                                $filename = str_replace( "$number$ext", ++$number . $ext, $filename );
                }
        }

        return $filename;
}


/**
 * Create a file in the upload folder with given content.
 *
 * If there is an error, then the key 'error' will exist with the error message.
 * If success, then the key 'file' will have the unique file path, the 'url' key
 * will have the link to the new file. and the 'error' key will be set to false.
 *
 * This function will not move an uploaded file to the upload folder. It will
 * create a new file with the content in $bits parameter. If you move the upload
 * file, read the content of the uploaded file, and then you can give the
 * filename and content to this function, which will add it to the upload
 * folder.
 *
 * The permissions will be set on the new file automatically by this function.
 *
 * @since 0.0.1
 *
 * @param string       $name       Filename.
 * @param null|string  $deprecated Never used. Set to null.
 * @param mixed        $bits       File content
 * @param string       $time       Optional. Time formatted in 'yyyy/mm'. Default null.
 * @return array
 */
function hq_upload_bits( $name, $deprecated, $bits, $time = null ) {
        if ( !empty( $deprecated ) )
                _deprecated_argument( __FUNCTION__, '2.0' );

        if ( empty( $name ) )
                return array( 'error' => __( 'Empty filename' ) );

        $wp_filetype = hq_check_filetype( $name );
        if ( ! $wp_filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) )
                return array( 'error' => __( 'Invalid file type' ) );

        $upload = hq_upload_dir( $time );

        if ( $upload['error'] !== false )
                return $upload;

        /**
         * Filter whether to treat the upload bits as an error.
         *
         * Passing a non-array to the filter will effectively short-circuit preparing
         * the upload bits, returning that value instead.
         *
         * @since 0.0.1
         *
         * @param mixed $upload_bits_error An array of upload bits data, or a non-array error to return.
         */
        $upload_bits_error = apply_filters( 'wp_upload_bits', array( 'name' => $name, 'bits' => $bits, 'time' => $time ) );
        if ( !is_array( $upload_bits_error ) ) {
                $upload[ 'error' ] = $upload_bits_error;
                return $upload;
        }

        $filename = hq_unique_filename( $upload['path'], $name );

        $new_file = $upload['path'] . "/$filename";
        if ( ! hq_mkdir_p( dirname( $new_file ) ) ) {
                if ( 0 === strpos( $upload['basedir'], ABSPATH ) )
                        $error_path = str_replace( ABSPATH, '', $upload['basedir'] ) . $upload['subdir'];
                else
                        $error_path = basename( $upload['basedir'] ) . $upload['subdir'];

                $message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $error_path );
                return array( 'error' => $message );
        }

        $ifp = @ fopen( $new_file, 'hq' );
        if ( ! $ifp )
                return array( 'error' => sprintf( __( 'Could not write file %s' ), $new_file ) );

        @fwrite( $ifp, $bits );
        fclose( $ifp );
        clearstatcache();

        // Set correct file permissions
        $stat = @ stat( dirname( $new_file ) );
        $perms = $stat['mode'] & 0007777;
        $perms = $perms & 0000666;
        @ chmod( $new_file, $perms );
        clearstatcache();

        // Compute the URL
        $url = $upload['url'] . "/$filename";

        return array( 'file' => $new_file, 'url' => $url, 'error' => false );
}

/**
 * Retrieve the file type based on the extension name.
 *
 * @since 0.0.1
 *
 * @param string $ext The extension to search.
 * @return string|void The file type, example: audio, video, document, spreadsheet, etc.
 */
function hq_ext2type( $ext ) {
        $ext = strtolower( $ext );

        /**
         * Filter file type based on the extension name.
         *
         * @since 0.0.1
         *
         * @see hq_ext2type()
         *
         * @param array $ext2type Multi-dimensional array with extensions for a default set
         *                        of file types.
         */
        $ext2type = apply_filters( 'ext2type', array(
                'image'       => array( 'jpg', 'jpeg', 'jpe',  'gif',  'png',  'bmp',   'tif',  'tiff', 'ico' ),
                'audio'       => array( 'aac', 'ac3',  'aif',  'aiff', 'm3a',  'm4a',   'm4b',  'mka',  'mp1',  'mp2',  'mp3', 'ogg', 'oga', 'ram', 'wav', 'wma' ),
                'video'       => array( '3g2',  '3gp', '3gpp', 'asf', 'avi',  'divx', 'dv',   'flv',  'm4v',   'mkv',  'mov',  'mp4',  'mpeg', 'mpg', 'mpv', 'ogm', 'ogv', 'qt',  'rm', 'vob', 'wmv' ),
                'document'    => array( 'doc', 'docx', 'docm', 'dotm', 'odt',  'pages', 'pdf',  'xps',  'oxps', 'rtf',  'wp', 'wpd', 'psd', 'xcf' ),
                'spreadsheet' => array( 'numbers',     'ods',  'xls',  'xlsx', 'xlsm',  'xlsb' ),
                'interactive' => array( 'swf', 'key',  'ppt',  'pptx', 'pptm', 'pps',   'ppsx', 'ppsm', 'sldx', 'sldm', 'odp' ),
                'text'        => array( 'asc', 'csv',  'tsv',  'txt' ),
                'archive'     => array( 'bz2', 'cab',  'dmg',  'gz',   'rar',  'sea',   'sit',  'sqx',  'tar',  'tgz',  'zip', '7z' ),
                'code'        => array( 'css', 'htm',  'html', 'php',  'js' ),
        ) );

        foreach ( $ext2type as $type => $exts )
                if ( in_array( $ext, $exts ) )
                        return $type;
}

/**
 * Retrieve the file type from the file name.
 *
 * You can optionally define the mime array, if needed.
 *
 * @since 0.0.1
 *
 * @param string $filename File name or path.
 * @param array  $mimes    Optional. Key is the file extension with value as the mime type.
 * @return array Values with extension first and mime type.
 */
function hq_check_filetype( $filename, $mimes = null ) {
        if ( empty($mimes) )
                $mimes = get_allowed_mime_types();
        $type = false;
        $ext = false;

        foreach ( $mimes as $ext_preg => $mime_match ) {
                $ext_preg = '!\.(' . $ext_preg . ')$!i';
                if ( preg_match( $ext_preg, $filename, $ext_matches ) ) {
                        $type = $mime_match;
                        $ext = $ext_matches[1];
                        break;
                }
        }

        return compact( 'ext', 'type' );
}

/**
 * Attempt to determine the real file type of a file.
 *
 * If unable to, the file name extension will be used to determine type.
 *
 * If it's determined that the extension does not match the file's real type,
 * then the "proper_filename" value will be set with a proper filename and extension.
 *
 * Currently this function only supports validating images known to getimagesize().
 *
 * @since 3.0.0
 *
 * @param string $file     Full path to the file.
 * @param string $filename The name of the file (may differ from $file due to $file being
 *                         in a tmp directory).
 * @param array   $mimes   Optional. Key is the file extension with value as the mime type.
 * @return array Values for the extension, MIME, and either a corrected filename or false
 *               if original $filename is valid.
 */
function hq_check_filetype_and_ext( $file, $filename, $mimes = null ) {
        $proper_filename = false;

        // Do basic extension validation and MIME mapping
        $hq_filetype = hq_check_filetype( $filename, $mimes );
        $ext = $hq_filetype['ext'];
        $type = $hq_filetype['type'];

        // We can't do any further validation without a file to work with
        if ( ! file_exists( $file ) ) {
                return compact( 'ext', 'type', 'proper_filename' );
        }

        // We're able to validate images using GD
        if ( $type && 0 === strpos( $type, 'image/' ) && function_exists('getimagesize') ) {

                // Attempt to figure out what type of image it actually is
                $imgstats = @getimagesize( $file );

                // If getimagesize() knows what kind of image it really is and if the real MIME doesn't match the claimed MIME
                if ( !empty($imgstats['mime']) && $imgstats['mime'] != $type ) {
                        /**
                         * Filter the list mapping image mime types to their respective extensions.
                         *
                         * @since 0.0.1
                         *
                         * @param  array $mime_to_ext Array of image mime types and their matching extensions.
                         */
                        $mime_to_ext = apply_filters( 'getimagesize_mimes_to_exts', array(
                                'image/jpeg' => 'jpg',
                                'image/png'  => 'png',
                                'image/gif'  => 'gif',
                                'image/bmp'  => 'bmp',
                                'image/tiff' => 'tif',
                        ) );

                        // Replace whatever is after the last period in the filename with the correct extension
                        if ( ! empty( $mime_to_ext[ $imgstats['mime'] ] ) ) {
                                $filename_parts = explode( '.', $filename );
                                array_pop( $filename_parts );
                                $filename_parts[] = $mime_to_ext[ $imgstats['mime'] ];
                                $new_filename = implode( '.', $filename_parts );

                                if ( $new_filename != $filename ) {
                                        $proper_filename = $new_filename; // Mark that it changed
                                }
                                // Redefine the extension / MIME
                                $hq_filetype = hq_check_filetype( $new_filename, $mimes );
                                $ext = $hq_filetype['ext'];
                                $type = $hq_filetype['type'];
                        }
                }
        }

        /**
         * Filter the "real" file type of the given file.
         *
         * @since 0.0.1
         *
         * @param array  $hq_check_filetype_and_ext File data array containing 'ext', 'type', and
         *                                          'proper_filename' keys.
         * @param string $file                      Full path to the file.
         * @param string $filename                  The name of the file (may differ from $file due to
         *                                          $file being in a tmp directory).
         * @param array  $mimes                     Key is the file extension with value as the mime type.
         */
        return apply_filters( 'hq_check_filetype_and_ext', compact( 'ext', 'type', 'proper_filename' ), $file, $filename, $mimes );
}

/**
 * Retrieve list of mime types and file extensions.
 *
 * @since 0.0.1
 * @since 0.0.1 Support was added for GIMP (xcf) files.
 *
 * @return array Array of mime types keyed by the file extension regex corresponding to those types.
 */
function hq_get_mime_types() {
        /**
         * Filter the list of mime types and file extensions.
         *
         * This filter should be used to add, not remove, mime types. To remove
         * mime types, use the 'upload_mimes' filter.
         *
         * @since 0.0.0
         *
         * @param array $hq_get_mime_types Mime types keyed by the file extension regex
         *                                 corresponding to those types.
         */
        return apply_filters( 'mime_types', array(
        // Image formats.
        'jpg|jpeg|jpe' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'bmp' => 'image/bmp',
        'tiff|tif' => 'image/tiff',
        'ico' => 'image/x-icon',
        // Video formats.
        'asf|asx' => 'video/x-ms-asf',
        'wmv' => 'video/x-ms-wmv',
        'wmx' => 'video/x-ms-wmx',
        'wm' => 'video/x-ms-wm',
        'avi' => 'video/avi',
        'divx' => 'video/divx',
        'flv' => 'video/x-flv',
        'mov|qt' => 'video/quicktime',
        'mpeg|mpg|mpe' => 'video/mpeg',
        'mp4|m4v' => 'video/mp4',
        'ogv' => 'video/ogg',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',
        '3gp|3gpp' => 'video/3gpp', // Can also be audio
        '3g2|3gp2' => 'video/3gpp2', // Can also be audio
        // Text formats.
        'txt|asc|c|cc|h|srt' => 'text/plain',
        'csv' => 'text/csv',
        'tsv' => 'text/tab-separated-values',
        'ics' => 'text/calendar',
        'rtx' => 'text/richtext',
        'css' => 'text/css',
        'htm|html' => 'text/html',
        'vtt' => 'text/vtt',
        'dfxp' => 'application/ttaf+xml',
        // Audio formats.
        'mp3|m4a|m4b' => 'audio/mpeg',
        'ra|ram' => 'audio/x-realaudio',
        'wav' => 'audio/wav',
        'ogg|oga' => 'audio/ogg',
        'mid|midi' => 'audio/midi',
        'wma' => 'audio/x-ms-wma',
        'wax' => 'audio/x-ms-wax',
        'mka' => 'audio/x-matroska',
        // Misc application formats.
        'rtf' => 'application/rtf',
        'js' => 'application/javascript',
        'pdf' => 'application/pdf',
        'swf' => 'application/x-shockwave-flash',
        'class' => 'application/java',
        'tar' => 'application/x-tar',
        'zip' => 'application/zip',
        'gz|gzip' => 'application/x-gzip',
        'rar' => 'application/rar',
        '7z' => 'application/x-7z-compressed',
        'exe' => 'application/x-msdownload',
        'psd' => 'application/octet-stream',
        'xcf' => 'application/octet-stream',
        // MS Office formats.
        'doc' => 'application/msword',
        'pot|pps|ppt' => 'application/vnd.ms-powerpoint',
        'wri' => 'application/vnd.ms-write',
        'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
        'mdb' => 'application/vnd.ms-access',
        'mpp' => 'application/vnd.ms-project',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
        'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
        'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
        'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
        'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',
        'oxps' => 'application/oxps',
        'xps' => 'application/vnd.ms-xpsdocument',
        // OpenOffice formats.
        'odt' => 'application/vnd.oasis.opendocument.text',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odg' => 'application/vnd.oasis.opendocument.graphics',
        'odc' => 'application/vnd.oasis.opendocument.chart',
        'odb' => 'application/vnd.oasis.opendocument.database',
        'odf' => 'application/vnd.oasis.opendocument.formula',
        // WordPerfect formats.
        'wp|wpd' => 'application/wordperfect',
        // iWork formats.
        'key' => 'application/vnd.apple.keynote',
        'numbers' => 'application/vnd.apple.numbers',
        'pages' => 'application/vnd.apple.pages',
        ) );
}

/**
 * Retrieve list of allowed mime types and file extensions.
 *
 * @since 0.0.1
 *
 * @param int|HQ_User $user Optional. User to check. Defaults to current user.
 * @return array Array of mime types keyed by the file extension regex corresponding
 *               to those types.
 */
function get_allowed_mime_types( $user = null ) {
        $t = hq_get_mime_types();

        unset( $t['swf'], $t['exe'] );
        if ( function_exists( 'current_user_can' ) )
                $unfiltered = $user ? user_can( $user, 'unfiltered_html' ) : current_user_can( 'unfiltered_html' );

        if ( empty( $unfiltered ) )
                unset( $t['htm|html'] );

        /**
         * Filter list of allowed mime types and file extensions.
         *
         * @since 0.0.1
         *
         * @param array            $t    Mime types keyed by the file extension regex corresponding to
         *                               those types. 'swf' and 'exe' removed from full list. 'htm|html' also
         *                               removed depending on '$user' capabilities.
         * @param int|HQ_User|null $user User ID, User object or null if not provided (indicates current user).
         */
        return apply_filters( 'upload_mimes', $t, $user );
}

/**
 * Display "Are You Sure" message to confirm the action being taken.
 *
 * If the action has the nonce explain message, then it will be displayed
 * along with the "Are you sure?" message.
 *
 * @since 0.0.1
 *
 * @param string $action The nonce action.
 */
function hq_nonce_ays( $action ) {
        if ( 'log-out' == $action ) {
                $html = sprintf( __( 'You are attempting to log out of %s' ), get_bloginfo( 'name' ) ) . '</p><p>';
                $redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
                $html .= sprintf( __( "Do you really want to <a href='%s'>log out</a>?"), hq_logout_url( $redirect_to ) );
        } else {
                $html = __( 'Are you sure you want to do this?' );
                if ( hq_get_referer() )
                        $html .= "</p><p><a href='" . esc_url( remove_query_arg( 'updated', hq_get_referer() ) ) . "'>" . __( 'Please try again.' ) . "</a>";
        }

        hq_die( $html, __( 'HiveQueen Failure Notice' ), 403 );
}


/**
 * Kill HiveQueen execution and display HTML message with error message.
 *
 * This function complements the `die()` PHP function. The difference is that
 * HTML will be displayed to the user. It is recommended to use this function
 * only when the execution should not continue any further. It is not recommended
 * to call this function very often, and try to handle as many errors as possible
 * silently or more gracefully.
 *
 * As a shorthand, the desired HTTP response code may be passed as an integer to
 * the `$title` parameter (the default title would apply) or the `$args` parameter.
 *
 * @since 0.0.1
 * @since 0.0.1 The `$title` and `$args` parameters were changed to optionally accept
 *              an integer to be used as the response code.
 *
 * @param string|HQ_Error  $message Optional. Error message. If this is a {@see HQ_Error} object,
 *                                  the error's messages are used. Default empty.
 * @param string|int       $title   Optional. Error title. If `$message` is a `HQ_Error` object,
 *                                  error data with the key 'title' may be used to specify the title.
 *                                  If `$title` is an integer, then it is treated as the response
 *                                  code. Default empty.
 * @param string|array|int $args {
 *     Optional. Arguments to control behavior. If `$args` is an integer, then it is treated
 *     as the response code. Default empty array.
 *
 *     @type int    $response       The HTTP response code. Default 500.
 *     @type bool   $back_link      Whether to include a link to go back. Default false.
 *     @type string $text_direction The text direction. This is only useful internally, when WordPress
 *                                  is still loading and the site's locale is not set up yet. Accepts 'rtl'.
 *                                  Default is the value of {@see is_rtl()}.
 * }
 */
function hq_die( $message = '', $title = '', $args = array() ) {

        if ( is_int( $args ) ) {
                $args = array( 'response' => $args );
        } elseif ( is_int( $title ) ) {
                $args  = array( 'response' => $title );
                $title = '';
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                /**
                 * Filter callback for killing HiveQueen execution for AJAX requests.
                 *
                 * @since 0.0.1
                 *
                 * @param callback $function Callback function name.
                 */
                $function = apply_filters( 'hq_die_ajax_handler', '_ajax_hq_die_handler' );
        } elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
                /**
                 * Filter callback for killing HiveQueen execution for XML-RPC requests.
                 *
                 * @since 0.0.1
                 *
                 * @param callback $function Callback function name.
                 */
                $function = apply_filters( 'hq_die_xmlrpc_handler', '_xmlrpc_hq_die_handler' );
        } else {
                /**
                 * Filter callback for killing HiveQueen execution for all non-AJAX, non-XML-RPC requests.
                 *
                 * @since 0.0.1
                 *
                 * @param callback $function Callback function name.
                 */
                $function = apply_filters( 'hq_die_handler', '_default_hq_die_handler' );
        }

        call_user_func( $function, $message, $title, $args );
}


/**
 * Kill HiveQueen execution and display HTML message with error message.
 *
 * This is the default handler for hq_die if you want a custom one for your
 * site then you can overload using the wp_die_handler filter in wp_die
 *
 * @since 0.0.1
 * @access private
 *
 * @param string       $message Error message.
 * @param string       $title   Optional. Error title. Default empty.
 * @param string|array $args    Optional. Arguments to control behavior. Default empty array.
 */
function _default_hq_die_handler( $message, $title = '', $args = array() ) {
        $defaults = array( 'response' => 500 );
        $r = hq_parse_args($args, $defaults);

        $have_gettext = function_exists('__');

        if ( function_exists( 'is_hq_error' ) && is_hq_error( $message ) ) {
                if ( empty( $title ) ) {
                        $error_data = $message->get_error_data();
                        if ( is_array( $error_data ) && isset( $error_data['title'] ) )
                                $title = $error_data['title'];
                }
                $errors = $message->get_error_messages();
                switch ( count( $errors ) ) {
                case 0 :
                        $message = '';
                        break;
                case 1 :
                        $message = "<p>{$errors[0]}</p>";
                        break;
                default :
                        $message = "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
                        break;
                }
        } elseif ( is_string( $message ) ) {
                $message = "<p>$message</p>";
        }

        if ( isset( $r['back_link'] ) && $r['back_link'] ) {
                $back_text = $have_gettext? __('&laquo; Back') : '&laquo; Back';
                $message .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
        }

        if ( ! did_action( 'admin_head' ) ) :
                if ( !headers_sent() ) {
                        status_header( $r['response'] );
                        nocache_headers();
                        header( 'Content-Type: text/html; charset=utf-8' );
                }

                if ( empty($title) )
                        $title = $have_gettext ? __('HiveQueen &rsaquo; Error') : 'HiveQueen &rsaquo; Error';

                $text_direction = 'ltr';
                if ( isset($r['text_direction']) && 'rtl' == $r['text_direction'] )
                        $text_direction = 'rtl';
                elseif ( function_exists( 'is_rtl' ) && is_rtl() )
                        $text_direction = 'rtl';
?>
<!DOCTYPE html>
<!-- Ticket #11289, IE bug fix: always pad the error page with enough characters such that it is greater than 512 bytes, even after gzip compression abcdefghijklmnopqrstuvwxyz1234567890aabbccddeeffgghhiijjkkllmmnnooppqqrrssttuuvvwwxxyyzz11223344556677889900abacbcbdcdcededfefegfgfhghgihihjijikjkjlklkmlmlnmnmononpopoqpqprqrqsrsrtstsubcbcdcdedefefgfabcadefbghicjkldmnoepqrfstugvwxhyz1i234j567k890laabmbccnddeoeffpgghqhiirjjksklltmmnunoovppqwqrrxsstytuuzvvw0wxx1yyz2z113223434455666777889890091abc2def3ghi4jkl5mno6pqr7stu8vwx9yz11aab2bcc3dd4ee5ff6gg7hh8ii9j0jk1kl2lmm3nnoo4p5pq6qrr7ss8tt9uuvv0wwx1x2yyzz13aba4cbcb5dcdc6dedfef8egf9gfh0ghg1ihi2hji3jik4jkj5lkl6kml7mln8mnm9ono
-->
<html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) ) language_attributes(); else echo "dir='$text_direction'"; ?>>
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width">
        <title><?php echo $title ?></title>
        <style type="text/css">
                html {
                        background: #f1f1f1;
                }
                body {
                        background: #fff;
                        color: #444;
                        font-family: "Open Sans", sans-serif;
                        margin: 2em auto;
                        padding: 1em 2em;
                        max-width: 700px;
                        -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                        box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                }
                h1 {
                        border-bottom: 1px solid #dadada;
                        clear: both;
                        color: #666;
                        font: 24px "Open Sans", sans-serif;
                        margin: 30px 0 0 0;
                        padding: 0;
                        padding-bottom: 7px;
                }
                #error-page {
                        margin-top: 50px;
                }
                #error-page p {
                        font-size: 14px;
                        line-height: 1.5;
                        margin: 25px 0 20px;
                }
                #error-page code {
                        font-family: Consolas, Monaco, monospace;
                }
                ul li {
                        margin-bottom: 10px;
                        font-size: 14px ;
                }
                a {
                        color: #21759B;
                        text-decoration: none;
                }
                a:hover {
                        color: #D54E21;
                }
                .button {
                        background: #f7f7f7;
                        border: 1px solid #cccccc;
                        color: #555;
                        display: inline-block;
                        text-decoration: none;
                        font-size: 13px;
                        line-height: 26px;
                        height: 28px;
                        margin: 0;
                        padding: 0 10px 1px;
                        cursor: pointer;
                        -webkit-border-radius: 3px;
                        -webkit-appearance: none;
                        border-radius: 3px;
                        white-space: nowrap;
                        -webkit-box-sizing: border-box;
                        -moz-box-sizing:    border-box;
                        box-sizing:         border-box;

                        -webkit-box-shadow: inset 0 1px 0 #fff, 0 1px 0 rgba(0,0,0,.08);
                        box-shadow: inset 0 1px 0 #fff, 0 1px 0 rgba(0,0,0,.08);
                        vertical-align: top;
                }

                .button.button-large {
                        height: 29px;
                        line-height: 28px;
                        padding: 0 12px;
                }

                .button:hover,
                .button:focus {
                        background: #fafafa;
                        border-color: #999;
                        color: #222;
                }

                .button:focus  {
                        -webkit-box-shadow: 1px 1px 1px rgba(0,0,0,.2);
                        box-shadow: 1px 1px 1px rgba(0,0,0,.2);
                }

                .button:active {
                        background: #eee;
                        border-color: #999;
                        color: #333;
                        -webkit-box-shadow: inset 0 2px 5px -3px rgba( 0, 0, 0, 0.5 );
                        box-shadow: inset 0 2px 5px -3px rgba( 0, 0, 0, 0.5 );
                }

                <?php if ( 'rtl' == $text_direction ) : ?>
                body { font-family: Tahoma, Arial; }
                <?php endif; ?>
        </style>
</head>
<body id="error-page">
<?php endif; // ! did_action( 'admin_head' ) ?>
        <?php echo $message; ?>
</body>
</html>
<?php
        die();
}

/**
 * Kill HiveQueen execution and display XML message with error message.
 *
 * This is the handler for hq_die when processing XMLRPC requests.
 *
 * @since 0.0.1
 * @access private
 *
 * @global hq_xmlrpc_server $hq_xmlrpc_server
 *
 * @param string       $message Error message.
 * @param string       $title   Optional. Error title. Default empty.
 * @param string|array $args    Optional. Arguments to control behavior. Default empty array.
 */
function _xmlrpc_hq_die_handler( $message, $title = '', $args = array() ) {
        global $hq_xmlrpc_server;
        $defaults = array( 'response' => 500 );

        $r = hq_parse_args($args, $defaults);

        if ( $hq_xmlrpc_server ) {
                $error = new IXR_Error( $r['response'] , $message);
                $hq_xmlrpc_server->output( $error->getXml() );
        }
        die();
}

/**
 * Kill HiveQueen ajax execution.
 *
 * This is the handler for hq_die when processing Ajax requests.
 *
 * @since 0.0.1
 * @access private
 *
 * @param string $message Optional. Response to print. Default empty.
 */
function _ajax_hq_die_handler( $message = '' ) {
        if ( is_scalar( $message ) )
                die( (string) $message );
        die( '0' );
}

/**
 * Kill HiveQueen execution.
 *
 * This is the handler for hq_die when processing APP requests.
 *
 * @since 0.0.1
 * @access private
 *
 * @param string $message Optional. Response to print. Default empty.
 */
function _scalar_hq_die_handler( $message = '' ) {
        if ( is_scalar( $message ) )
                die( (string) $message );
        die();
}

//TODO: ****************************************** Functions ********************************************************






