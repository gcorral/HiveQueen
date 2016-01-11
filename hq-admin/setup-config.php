<?php
/**
 * Retrieves and creates the hq-config.php file.
 *
 * The permissions for the base directory must allow for writing files in order
 * for the hq-config.php to be created using this page.
 *
 * @internal This file must be parsable by PHP4.
 *
 * @package HiveQueen
 * @subpackage Administration
 */

/**
 * We are installing.
 */
define('HQ_INSTALLING', true);

/**
 * We are blissfully unaware of anything.
 */
define('HQ_SETUP_CONFIG', true);

/**
 * Disable error reporting
 *
 * Set this to error_reporting( -1 ) for debugging
 */
error_reporting(-1);

define( 'ABSPATH', dirname( dirname( __FILE__ ) ) . '/' );

require( ABSPATH . 'hq-settings.php' );

/** Load HiveQueen Administration Upgrade API */
require_once( ABSPATH . 'hq-admin/includes/upgrade.php' );

/** Load HiveQueen Translation Install API */
// TODO: disabled
//require_once( ABSPATH . 'hq-admin/includes/translation-install.php' );

nocache_headers();

// Support hq-config-sample.php one level up, for the develop repo.
if ( file_exists( ABSPATH . 'hq-config-sample.php' ) )
        $config_file = file( ABSPATH . 'hq-config-sample.php' );
elseif ( file_exists( dirname( ABSPATH ) . '/hq-config-sample.php' ) )
        $config_file = file( dirname( ABSPATH ) . '/hq-config-sample.php' );
else
        hq_die( __( 'Sorry, I need a hq-config-sample.php file to work from.'
 ) );

// Check if hq-config.php has been created
if ( file_exists( ABSPATH . 'hq-config.php' ) )
        hq_die( '<p>' . sprintf( __( "The file 'hq-config.php' already exists. If you need to reset any of the configuration items in this
 file, please delete it first. You may try <a href='%s'>installing now</a>." ), 'install.php' ) . '</p>' );

// Check if hq-config.php exists above the root directory but is not part of another install
if ( file_exists(ABSPATH . '../hq-config.php' ) && ! file_exists( ABSPATH . '../hq-settings.php' ) )
        hq_die( '<p>' . sprintf( __( "The file 'hq-config.php' already exists one level above your HiveQueen installation. If you need to reset any of the configuration items in this file, please delete it first. You may try <a href='install.php'>installing now</a>."), 'install.php' ) . '</p>' );

$step = isset( $_GET['step'] ) ? (int) $_GET['step'] : -1;

/**
 * Display setup hq-config.php file header.
 *
 * @ignore
 * @since 2.3.0
 *
 * @global string    $hq_local_package
 * @global HQ_Locale $hq_locale
 *
 * @param string|array $body_classes
 */
function setup_config_display_header( $body_classes = array() ) {
        $body_classes = (array) $body_classes;
        $body_classes[] = 'hq-core-ui';
        if ( is_rtl() ) {
                $body_classes[] = 'rtl';
        }

        header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml"<?php if ( is_rtl() ) echo ' dir="rtl"'; ?>>
<head>
        <meta name="viewport" content="width=device-width" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?php _e( 'HiveQueen &rsaquo; Setup Configuration File' ); ?></title>
        <?php hq_admin_css( 'install', true ); ?>
</head>
<body class="<?php echo implode( ' ', $body_classes ); ?>">
<h1 id="logo"><a href="<?php esc_attr_e( 'https://github.com/gcorral/hivequeen.git' ); ?>" tabindex="-1"><?php _e( 'HiveQueen' ); ?></a></h1>
<?php
} // end function setup_config_display_header();

$language = '';
if ( ! empty( $_REQUEST['language'] ) ) {
        $language = preg_replace( '/[^a-zA-Z_]/', '', $_REQUEST['language'] );
} elseif ( isset( $GLOBALS['hq_local_package'] ) ) {
        $language = $GLOBALS['hq_local_package'];
}

switch($step) {
        case -1:
                if ( hq_can_install_language_pack() && empty( $language ) && ( $languages = hq_get_available_translations() ) ) {
                        setup_config_display_header( 'language-chooser' );
                        echo '<form id="setup" method="post" action="?step=0">';
                        hq_install_language_form( $languages );
                        echo '</form>';
                        break;
                }

                // Deliberately fall through if we can't reach the translations API.

        case 0:
                if ( ! empty( $language ) ) {
                        $loaded_language = hq_download_language_pack( $language );
                        if ( $loaded_language ) {
                                load_default_textdomain( $loaded_language );
                                $GLOBALS['hq_locale'] = new HQ_Locale();
                        }
                }

                setup_config_display_header();
                $step_1 = 'setup-config.php?step=1';
                if ( isset( $_REQUEST['noapi'] ) ) {
                        $step_1 .= '&amp;noapi';
                }
                if ( ! empty( $loaded_language ) ) {
                        $step_1 .= '&amp;language=' . $loaded_language;
                }
?>

<p><?php _e( 'Welcome to HiveQueen. Before getting started, we need some information on the database. You will need to know the following items before proceeding.' ) ?></p>
<ol>
        <li><?php _e( 'Database name' ); ?></li>
        <li><?php _e( 'Database username' ); ?></li>
        <li><?php _e( 'Database password' ); ?></li>
        <li><?php _e( 'Database host' ); ?></li>
        <!-- <li><?php _e( 'Table prefix (if you want to run more than one HiveQueen in a single database)' ); ?></li> -->
</ol>
<p>
        <?php _e( 'We&#8217;re going to use this information to create a <code>hq-config.php</code> file.' ); ?>
        <strong><?php _e( "If for any reason this automatic file creation doesn&#8217;t work, don&#8217;t worry. All this does is fill in the database information to a configuration file. You may also simply open <code>hq-config-sample.php</code> in a text editor, fill in your information, and save it as <code>hq-config.php</code>." ); ?></strong>
        <!-- <?php _e( "Need more help? <a href='https://codex.wordpress.org/Editing_wp-config.php'>We got it</a>." ); ?> -->
</p>
<p><?php _e( "In all likelihood, these items were supplied to you by your Web Host. If you do not have this information, then you will need to contact them before you can continue. If you&#8217;re all ready&hellip;" ); ?></p>

<p class="step"><a href="<?php echo $step_1; ?>" class="button button-large"><?php _e( 'Let&#8217;s go!' ); ?></a></p>
<?php
        break;

        case 1:
                load_default_textdomain( $language );
                $GLOBALS['hq_locale'] = new HQ_Locale();

                setup_config_display_header();
        ?>
<form method="post" action="setup-config.php?step=2">
        <p><?php _e( "Below you should enter your database connection details. If you&#8217;re not sure about these, contact your host." ); ?></p>
        <table class="form-table">
                <tr>
                        <th scope="row"><label for="dbname"><?php _e( 'Database Name' ); ?></label></th>
                        <td><input name="dbname" id="dbname" type="text" size="25" value="hivequeen" /></td>
                        <td><?php _e( 'The name of the database you want to run HQ in.' ); ?></td>
                </tr>
                <tr>
                        <th scope="row"><label for="uname"><?php _e( 'User Name' ); ?></label></th>
                        <td><input name="uname" id="uname" type="text" size="25" value="<?php echo htmlspecialchars( _x( 'username', 'example username' ), ENT_QUOTES ); ?>" /></td>
                        <td><?php _e( 'Your MySQL username' ); ?></td>
                </tr>
                <tr>
                        <th scope="row"><label for="pwd"><?php _e( 'Password' ); ?></label></th>
                        <td><input name="pwd" id="pwd" type="text" size="25" value="<?php echo htmlspecialchars( _x( 'password', 'example password' ), ENT_QUOTES ); ?>" autocomplete="off" /></td>
                        <td><?php _e( '&hellip;and your MySQL password.' ); ?></td>
                </tr>
                <tr>
                        <th scope="row"><label for="dbhost"><?php _e( 'Database Host' ); ?></label></th>
                        <td><input name="dbhost" id="dbhost" type="text" size="25" value="localhost" /></td>
                        <td><?php _e( 'You should be able to get this info from your web host, if <code>localhost</code> does not work.' ); ?></td>
                </tr>
                <tr>
                        <th scope="row"><label for="prefix"><?php _e( 'Table Prefix' ); ?></label></th>
                        <!-- <td><input name="prefix" id="prefix" type="text" value="hq_" size="25" /></td>
                        <td><?php _e( 'If you want to run multiple HiveQueen installations in a single database, change this.' ); ?></td> -->
                </tr>
        </table>
        <?php if ( isset( $_GET['noapi'] ) ) { ?><input name="noapi" type="hidden" value="1" /><?php } ?>
        <input type="hidden" name="language" value="<?php echo esc_attr( $language ); ?>" />
        <p class="step"><input name="submit" type="submit" value="<?php echo htmlspecialchars( __( 'Submit' ), ENT_QUOTES ); ?>" class="button button-large" /></p>
</form>
<?php
        break;

        case 2:
        load_default_textdomain( $language );
        $GLOBALS['hq_locale'] = new HQ_Locale();

        $dbname = trim( hq_unslash( $_POST[ 'dbname' ] ) );
        $uname = trim( hq_unslash( $_POST[ 'uname' ] ) );
        $pwd = trim( hq_unslash( $_POST[ 'pwd' ] ) );
        $dbhost = trim( hq_unslash( $_POST[ 'dbhost' ] ) );
        $prefix = trim( hq_unslash( $_POST[ 'prefix' ] ) );

        $step_1 = 'setup-config.php?step=1';
        $install = 'install.php';
        if ( isset( $_REQUEST['noapi'] ) ) {
                $step_1 .= '&amp;noapi';
        }

        if ( ! empty( $language ) ) {
                $step_1 .= '&amp;language=' . $language;
                $install .= '?language=' . $language;
        } else {
                $install .= '?language=en_US';
        }

        $tryagain_link = '</p><p class="step"><a href="' . $step_1 . '" onclick="javascript:history.go(-1);return false;" class="button button-large">' . __( 'Try again' ) . '</a>';

        if ( empty( $prefix ) )
                hq_die( __( '<strong>ERROR</strong>: "Table Prefix" must not be empty.' . $tryagain_link ) );

        // Validate $prefix: it can only contain letters, numbers and underscores.
        if ( preg_match( '|[^a-z0-9_]|i', $prefix ) )
                hq_die( __( '<strong>ERROR</strong>: "Table Prefix" can only contain numbers, letters, and underscores.' . $tryagain_link ) );

        // Test the db connection.
        /**#@+
         * @ignore
         */
        define('DB_NAME', $dbname);
        define('DB_USER', $uname);
        define('DB_PASSWORD', $pwd);
        define('DB_HOST', $dbhost);
        /**#@-*/

        // Re-construct $hqdb with these new values.
        unset( $hqdb );
        require_hq_db();

        /*
         * The hqdb constructor bails when HQ_SETUP_CONFIG is set, so we must
         * fire this manually. We'll fail here if the values are no good.
         */
        $hqdb->db_connect();
        if ( ! empty( $hqdb->error ) )
                hq_die( $hqdb->error->get_error_message() . $tryagain_link );

        // Fetch or generate keys and salts.
        /* goyo disable:
        $no_api = isset( $_POST['noapi'] );
        if ( ! $no_api ) {
                $secret_keys = hq_remote_get( 'https://api.wordpress.org/secret-key/1.1/salt/' );
        }

        if ( $no_api || is_wp_error( $secret_keys ) ) {
                $secret_keys = array();
                for ( $i = 0; $i < 8; $i++ ) {
                        $secret_keys[] = wp_generate_password( 64, true, true );
                }
        } else {
                $secret_keys = explode( "\n", wp_remote_retrieve_body( $secret_keys ) );
                foreach ( $secret_keys as $k => $v ) {
                        $secret_keys[$k] = substr( $v, 28, 64 );
                }
        }
        */

        $key = 0;
        // Not a PHP5-style by-reference foreach, as this file must be parseable by PHP4.
        foreach ( $config_file as $line_num => $line ) {
                if ( '$table_prefix  =' == substr( $line, 0, 16 ) ) {
                        $config_file[ $line_num ] = '$table_prefix  = \'' . addcslashes( $prefix, "\\'" ) . "';\r\n";
                        continue;
                }

                if ( ! preg_match( '/^define\(\'([A-Z_]+)\',([ ]+)/', $line, $match ) )
                        continue;

                $constant = $match[1];
                $padding  = $match[2];

                switch ( $constant ) {
                        case 'DB_NAME'     :
                        case 'DB_USER'     :
                        case 'DB_PASSWORD' :
                        case 'DB_HOST'     :
                                $config_file[ $line_num ] = "define('" . $constant . "'," . $padding . "'" . addcslashes( constant( $constant ), "\\'" ) . "');\r\n";
                                break;
                        case 'DB_CHARSET'  :
                                if ( 'utf8mb4' === $wpdb->charset || ( ! $wpdb->charset && $wpdb->has_cap( 'utf8mb4' ) ) ) {
                                        $config_file[ $line_num ] = "define('" . $constant . "'," . $padding . "'utf8mb4');\r\n";
                                }
                                break;
                        case 'AUTH_KEY'         :
                        case 'SECURE_AUTH_KEY'  :
                        case 'LOGGED_IN_KEY'    :
                        case 'NONCE_KEY'        :
                        case 'AUTH_SALT'        :
                        case 'SECURE_AUTH_SALT' :
                        case 'LOGGED_IN_SALT'   :
                        case 'LOGGED_IN_SALT'   :
                        case 'NONCE_SALT'       :
                                $config_file[ $line_num ] = "define('" . $constant . "'," . $padding . "'" . $secret_keys[$key++] . "');\r\n";
                                break;
                }
        }
        unset( $line );

        if ( ! is_writable(ABSPATH) ) :
                setup_config_display_header();
?>
<p><?php _e( "Sorry, but I can&#8217;t write the <code>hq-config.php</code> file." ); ?></p>
<p><?php _e( 'You can create the <code>hq-config.php</code> manually and paste the following text into it.' ); ?></p>
<textarea id="hq-config" cols="98" rows="15" class="code" readonly="readonly"><?php
                foreach( $config_file as $line ) {
                        echo htmlentities($line, ENT_COMPAT, 'UTF-8');
                }
?></textarea>
<p><?php _e( 'After you&#8217;ve done that, click &#8220;Run the install.&#8221;' ); ?></p>
<p class="step"><a href="<?php echo $install; ?>" class="button button-large"><?php _e( 'Run the install' ); ?></a></p>
<script>
(function(){
if ( ! /iPad|iPod|iPhone/.test( navigator.userAgent ) ) {
        var el = document.getElementById('hq-config');
        el.focus();
        el.select();
}
})();
</script>
<?php
        else :
                /*
                 * If this file doesn't exist, then we are using the hq-config-sample.php
                 * file one level up, which is for the develop repo.
                 */
                if ( file_exists( ABSPATH . 'hq-config-sample.php' ) )
                        $path_to_hq_config = ABSPATH . 'hq-config.php';
                else
                        $path_to_hq_config = dirname( ABSPATH ) . '/hq-config.php';

                $handle = fopen( $path_to_hq_config, 'w' );
                foreach( $config_file as $line ) {
                        fwrite( $handle, $line );
                }
                fclose( $handle );
                chmod( $path_to_hq_config, 0666 );
                setup_config_display_header();
?>
<p><?php _e( "All right, sparky! You&#8217;ve made it through this part of the installation. HiveQueen can now communicate with your database. If you are ready, time now to&hellip;" ); ?></p>

<p class="step"><a href="<?php echo $install; ?>" class="button button-large"><?php _e( 'Run the install' ); ?></a></p>
<?php
        endif;
        break;
}
?>
<?php hq_print_scripts( 'language-chooser' ); ?>
</body>
</html>

