<?php
/**
 * Option API
 *
 * @package HiveQueen
 * @subpackage Option
 */

/**
 * Retrieve option value based on name of option.
 *
 * If the option does not exist or does not have a value, then the return value
 * will be false. This is useful to check whether you need to install an option
 * and is commonly used during installation of plugin options and to test
 * whether upgrading is required.
 *
 * If the option was serialized then it will be unserialized when it is returned.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb
 *
 * @param string $option  Name of option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default Optional. Default value to return if the option does not exist.
 * @return mixed Value set for the option.
 */
function get_option( $option, $default = false ) {
        global $hqdb;

        $option = trim( $option );
        if ( empty( $option ) )
                return false;

        /**
         * Filter the value of an existing option before it is retrieved.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * Passing a truthy value to the filter will short-circuit retrieving
         * the option value, returning the passed value instead.
         *
         * @since 0.0.1
         *
         * @param bool|mixed $pre_option Value to return instead of the option value.
         *                               Default false to skip it.
         */
        //TODO: ???
        //$pre = apply_filters( 'pre_option_' . $option, false );
        //if ( false !== $pre )
        //        return $pre;

        if ( defined( 'HQ_SETUP_CONFIG' ) )
                return false;

        if ( ! defined( 'HQ_INSTALLING' ) ) {
                // prevent non-existent options from triggering multiple queries
                $notoptions = hq_cache_get( 'notoptions', 'options' );
                if ( isset( $notoptions[ $option ] ) ) {
                        /**
                         * Filter the default value for an option.
                         *
                         * The dynamic portion of the hook name, `$option`, refers to the option name.
                         *
                         * @since 0.0.1
                         *
                         * @param mixed $default The default value to return if the option does not exist
                         *                       in the database.
                         */
                        return apply_filters( 'default_option_' . $option, $default );
                }

                $alloptions = hq_load_alloptions();
                if ( isset( $alloptions[$option] ) ) {
                        $value = $alloptions[$option];
                } else {
                        $value = hq_cache_get( $option, 'options' );

                        if ( false === $value ) {
                                $row = $hqdb->get_row( $hqdb->prepare( "SELECT option_value FROM $hqdb->options WHERE option_name = %s LIMIT 1", $option ) );

                                // Has to be get_row instead of get_var because of funkiness with 0, false, null values
                                if ( is_object( $row ) ) {
                                        $value = $row->option_value;
                                        hq_cache_add( $option, $value, 'options' );
                                } else { // option does not exist, so we must cache its non-existence
                                        if ( ! is_array( $notoptions ) ) {
                                                 $notoptions = array();
                                        }
                                        $notoptions[$option] = true;
                                        hq_cache_set( 'notoptions', $notoptions, 'options' );

                                        /** This filter is documented in hq-includes/option.php */
                                        return apply_filters( 'default_option_' . $option, $default );
                                }
                        }
                }
        } else {
                $suppress = $hqdb->suppress_errors();
                $row = $hqdb->get_row( $hqdb->prepare( "SELECT option_value FROM $hqdb->options WHERE option_name = %s LIMIT 1", $option ) );
                $hqdb->suppress_errors( $suppress );
                if ( is_object( $row ) ) {
                        $value = $row->option_value;
                } else {
                        /** This filter is documented in hq-includes/option.php */
                        return apply_filters( 'default_option_' . $option, $default );
                }
        }

        // If home is not set use siteurl.
        if ( 'home' == $option && '' == $value )
                return get_option( 'siteurl' );

        if ( in_array( $option, array('siteurl', 'home', 'category_base', 'tag_base') ) )
                $value = untrailingslashit( $value );

        /**
         * Filter the value of an existing option.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 0.0.1 As 'option_' . $setting
         * @since 0.0.1
         *
         * @param mixed $value Value of the option. If stored serialized, it will be
         *                     unserialized prior to being returned.
         */
        return apply_filters( 'option_' . $option, maybe_unserialize( $value ) );
}

/**
 * Protect HiveQueen special option from being modified.
 *
 * Will die if $option is in protected list. Protected options are 'alloptions'
 * and 'notoptions' options.
 *
 * @since 0.0.1
 *
 * @param string $option Option name.
 */
function hq_protect_special_option( $option ) {
        if ( 'alloptions' === $option || 'notoptions' === $option )
                hq_die( sprintf( __( '%s is a protected HQ option and may not be modified' ), esc_html( $option ) ) );
}

/**
 * Print option value after sanitizing for forms.
 *
 * @since 0.0.1
 *
 * @param string $option Option name.
 */
function form_option( $option ) {
        echo esc_attr( get_option( $option ) );
}


/**
 * Loads and caches all autoloaded options, if available or all options.
 *
 * @since 0.0.1
 *
 * @global hqdb $hqdb
 *
 * @return array List of all options.
 */
function hq_load_alloptions() {
        global $hqdb;

        //if ( !defined( 'HQ_INSTALLING' ) || !is_multisite() )
        if ( !defined( 'HQ_INSTALLING' ) )
                $alloptions = hq_cache_get( 'alloptions', 'options' );
        else
                $alloptions = false;

        if ( !$alloptions ) {
                $suppress = $hqdb->suppress_errors();
                if ( !$alloptions_db = $hqdb->get_results( "SELECT option_name, option_value FROM $hqdb->options WHERE autoload = 'yes'" ) )
                        $alloptions_db = $hqdb->get_results( "SELECT option_name, option_value FROM $hqdb->options" );
                $hqdb->suppress_errors($suppress);
                $alloptions = array();
                foreach ( (array) $alloptions_db as $o ) {
                        $alloptions[$o->option_name] = $o->option_value;
                }
                //if ( !defined( 'HQ_INSTALLING' ) || !is_multisite() )
                if ( !defined( 'HQ_INSTALLING' ) )
                        hq_cache_add( 'alloptions', $alloptions, 'options' );
        }

        return $alloptions;
}

// TODO: **************************************** functions *************************************************

