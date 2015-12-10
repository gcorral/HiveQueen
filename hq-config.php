<?php
/**
 * The base configuration for HiveQueen
 *
 * The hq-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "hp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Database table prefix
 * * ABSPATH
 *
 *
 * @package HiveQueen
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for HiveQueen */
define('DB_NAME', '');

/** MySQL database username */
define('DB_USER', '');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', '');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');


/**
 * HiveQueen Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'hq_';


/**
 * For developers: HiveQueen debugging mode.
 *
 *
 */
define('HQ_DEBUG', false);

/* That's all, stop editing! */

/** Absolute path to the HiveQueen directory. */
if ( !defined('ABSPATH') )
        define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up HiveQueen vars and included files. */
require_once(ABSPATH . 'hq-settings.php');

