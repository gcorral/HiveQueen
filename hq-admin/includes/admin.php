<?php
/**
 * Includes all of the HiveQueen Administration API files.
 *
 * @package HiveQueen
 * @subpackage Administration
 */

if ( ! defined('HQ_ADMIN') ) {
	/*
	 * This file is being included from a file other than hq-admin/admin.php, so
	 * some setup was skipped. Make sure the admin message catalog is loaded since
	 * load_default_textdomain() will not have done so in this context.
	 */
	load_textdomain( 'default', HQ_LANG_DIR . '/admin-' . get_locale() . '.mo' );
}

/** HiveQueen Administration Hooks */
require_once(ABSPATH . 'hq-admin/includes/admin-filters.php');

/** HiveQueen Bookmark Administration API */
require_once(ABSPATH . 'hq-admin/includes/bookmark.php');

/** HiveQueen Comment Administration API */
require_once(ABSPATH . 'hq-admin/includes/comment.php');

/** HiveQueen Administration File API */
require_once(ABSPATH . 'hq-admin/includes/file.php');

/** HiveQueen Image Administration API */
require_once(ABSPATH . 'hq-admin/includes/image.php');

/** HiveQueen Media Administration API */
require_once(ABSPATH . 'hq-admin/includes/media.php');

/** HiveQueen Import Administration API */
require_once(ABSPATH . 'hq-admin/includes/import.php');

/** HiveQueen Misc Administration API */
require_once(ABSPATH . 'hq-admin/includes/misc.php');

/** HiveQueen Plugin Administration API */
require_once(ABSPATH . 'hq-admin/includes/plugin.php');

/** HiveQueen Post Administration API */
require_once(ABSPATH . 'hq-admin/includes/post.php');

/** HiveQueen Administration Screen API */
require_once(ABSPATH . 'hq-admin/includes/screen.php');

/** HiveQueen Taxonomy Administration API */
require_once(ABSPATH . 'hq-admin/includes/taxonomy.php');

/** HiveQueen Template Administration API */
require_once(ABSPATH . 'hq-admin/includes/template.php');

/** HiveQueen List Table Administration API and base class */
require_once(ABSPATH . 'hq-admin/includes/class-hq-list-table.php');
require_once(ABSPATH . 'hq-admin/includes/list-table.php');

/** HiveQueen Theme Administration API */
require_once(ABSPATH . 'hq-admin/includes/theme.php');

/** HiveQueen User Administration API */
require_once(ABSPATH . 'hq-admin/includes/user.php');

/** HiveQueen Site Icon API */
require_once(ABSPATH . 'hq-admin/includes/class-hq-site-icon.php');

/** HiveQueen Update Administration API */
require_once(ABSPATH . 'hq-admin/includes/update.php');

/** HiveQueen Deprecated Administration API */
require_once(ABSPATH . 'hq-admin/includes/deprecated.php');

/** HiveQueen Multisite support API */
if ( is_multisite() ) {
	require_once(ABSPATH . 'hq-admin/includes/ms-admin-filters.php');
	require_once(ABSPATH . 'hq-admin/includes/ms.php');
	require_once(ABSPATH . 'hq-admin/includes/ms-deprecated.php');
}
