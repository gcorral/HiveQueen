<?php
/**
 * Upgrade HiveQueen Page.
 *
 * @package HiveQueen
 * @subpackage Administration
 */

/**
 * We are upgrading HiveQueen.
 *
 * @since 0.0.1
 * @var bool
 */
define( 'HQ_INSTALLING', true );

/** Load HiveQueen Bootstrap */
require( dirname( dirname( __FILE__ ) ) . '/hq-load.php' );

nocache_headers();

timer_start();
require_once( ABSPATH . 'hq-admin/includes/upgrade.php' );

delete_site_transient('update_core');

if ( isset( $_GET['step'] ) )
        $step = $_GET['step'];
else
        $step = 0;

// Do it. No output.
if ( 'upgrade_db' === $step ) {
        hq_upgrade();
        die( '0' );
}

/**
 * @global string $hq_version
 * @global string $required_php_version
 * @global string $required_mysql_version
 * @global hqdb   $hqdb
 */
global $hq_version, $required_php_version, $required_mysql_version;

$step = (int) $step;

$php_version    = phpversion();
$mysql_version  = $hqdb->db_version();
$php_compat     = version_compare( $php_version, $required_php_version, '>=' );
if ( file_exists( HQ_CONTENT_DIR . '/db.php' ) && empty( $hqdb->is_mysql ) )
        $mysql_compat = true;
else
        $mysql_compat = version_compare( $mysql_version, $required_mysql_version, '>=' );

@header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
        <meta name="viewport" content="width=device-width" />
        <meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo get_option( 'blog_charset' ); ?>" />
        <title><?php _e( 'HiveQueen &rsaquo; Update' ); ?></title>
        <?php
        hq_admin_css( 'install', true );
        hq_admin_css( 'ie', true );
        ?>
</head>
<body class="hq-core-ui">
<h1 id="logo"><a href="<?php echo esc_url( __( 'https://github.com/gcorral/hivequeen' ) ); ?>" tabindex="-1"><?php _e( 'HiveQueen' ); ?></a></h1>

<?php if ( get_option( 'db_version' ) == $hq_db_version || !is_hq_installed() ) : ?>

<h2><?php _e( 'No Update Required' ); ?></h2>
<p><?php _e( 'Your HiveQueen database is already up-to-date!' ); ?></p>
<p class="step"><a class="button button-large" href="<?php echo get_option( 'home' ); ?>/"><?php _e( 'Continue' ); ?></a></p>

<?php elseif ( !$php_compat || !$mysql_compat ) :
        if ( !$mysql_compat && !$php_compat )
                printf( __('You cannot update because <a href="://github.com/gcorral/hivequeen">HiveQueen %1$s</a> requires PHP version %2$s or higher and MySQL version %3$s or higher. You are running PHP version %4$s and MySQL version %5$s.'), $hq_version, $required_php_version, $required_mysql_version, $php_version, $mysql_version );
        elseif ( !$php_compat )
                printf( __('You cannot update because <a href="https://github.com/gcorral/hivequeen">HiveQueen %1$s</a> requires PHP version %2$s or higher. You are running version %3$s.'), $hq_version, $required_php_version, $php_version );
        elseif ( !$mysql_compat )
                printf( __('You cannot update because <a href="https://github.com/gcorral/hivequeen">HiveQueen %1$s</a> requires MySQL version %2$s or higher. You are running version %3$s.'), $hq_version, $required_mysql_version, $mysql_version );
?>
<?php else :
switch ( $step ) :
        case 0:
                $goback = hq_get_referer();
                if ( $goback ) {
                        $goback = esc_url_raw( $goback );
                        $goback = urlencode( $goback );
                }

?>
<h2><?php _e( 'Database Update Required' ); ?></h2>
<p><?php _e( 'HiveQueen has been updated! Before we send you on your way, we have to update your database to the newest version.' ); ?></p>
<p><?php _e( 'The update process may take a little while, so please be patient.' ); ?></p>
<p class="step"><a class="button button-large" href="upgrade.php?step=1&amp;backto=<?php echo $goback; ?>"><?php _e( 'Update HiveQueen Database' ); ?></a></p>
<?php
                break;
        case 1:
                hq_upgrade();

                        $backto = !empty($_GET['backto']) ? hq_unslash( urldecode( $_GET['backto'] ) ) : __get_option( 'home' ) . '/';
                        $backto = esc_url( $backto );
                        $backto = hq_validate_redirect($backto, __get_option( 'home' ) . '/');
?>
<h2><?php _e( 'Update Complete' ); ?></h2>
        <p><?php _e( 'Your HiveQueen database has been successfully updated!' ); ?></p>
        <p class="step"><a class="button button-large" href="<?php echo $backto; ?>"><?php _e( 'Continue' ); ?></a></p>

<!--
<pre>
<?php printf( __( '%s queries' ), $hqdb->num_queries ); ?>

<?php printf( __( '%s seconds' ), timer_stop( 0 ) ); ?>
</pre>
-->

<?php
                break;
endswitch;
endif;
?>
</body>
</html>


