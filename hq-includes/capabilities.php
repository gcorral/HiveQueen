<?php
/**
 * HiveQueen Roles and Capabilities.
 *
 * @package HiveQueen
 * @subpackage User
 */

/**
 * Hive User Roles.
 *
 * The role option is simple, the structure is organized by role name that store
 * the name in value of the 'name' key. The capabilities are stored as an array
 * in the value of the 'capability' key.
 *
 *     array (
 *              'rolename' => array (
 *                      'name' => 'rolename',
 *                      'capabilities' => array()
 *              )
 *     )
 *
 * @since 0.0.1
 * @package HiveQueen
 * @subpackage User
 */
class HQ_Roles {
        /**
         * List of roles and capabilities.
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
        public $roles;

        /**
         * List of the role objects.
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
        public $role_objects = array();

        /**
         * List of role names.
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
        public $role_names = array();

       /**
         * Option name for storing role list.
         *
         * @since 0.0.1
         * @access public
         * @var string
         */
        public $role_key;

        /**
         * Whether to use the database for retrieval and storage.
         *
         * @since 0.0.1
         * @access public
         * @var bool
         */
        public $use_db = true;

        /**
         * Constructor
         *
         * @since 0.0.1
         */
        public function __construct() {
                $this->_init();
        }

        /**
         * Make private/protected methods readable for backwards compatibility.
         *
         * @since 0.0.1
         * @access public
         *
         * @param callable $name      Method to call.
         * @param array    $arguments Arguments to pass when calling.
         * @return mixed|false Return value of the callback, false otherwise.
         */
        public function __call( $name, $arguments ) {
                if ( '_init' === $name ) {
                        return call_user_func_array( array( $this, $name ), $arguments );
                }
                return false;
        }


        /**
         * Set up the object properties.
         *
         * The role key is set to the current prefix for the $hqdb object with
         * 'user_roles' appended. If the $hq_user_roles global is set, then it will
         * be used and the role option will not be updated or used.
         *
         * @since 0.0.1
         * @access protected
         *
         * @global hqdb  $hqdb          HiveQueen database abstraction object.
         * @global array $hq_user_roles Used to set the 'roles' property value.
         */
        protected function _init() {
                global $hqdb, $hq_user_roles;
                $this->role_key = $hqdb->get_blog_prefix() . 'user_roles';
                if ( ! empty( $hq_user_roles ) ) {
                        $this->roles = $hq_user_roles;
                        $this->use_db = false;
                } else {
                        $this->roles = get_option( $this->role_key );
                }

                if ( empty( $this->roles ) )
                        return;

                $this->role_objects = array();
                $this->role_names =  array();
                foreach ( array_keys( $this->roles ) as $role ) {
                        $this->role_objects[$role] = new HQ_Role( $role, $this->roles[$role]['capabilities'] );
                        $this->role_names[$role] = $this->roles[$role]['name'];
                }
        }

        /**
         * Reinitialize the object
         *
         * Recreates the role objects. This is typically called only by switch_to_blog()
         * after switching hqdb to a new blog ID.
         *
         * @since 3.5.0
         * @access public
         *
         * @global hqdb $hqdb
         */
        public function reinit() {
                // There is no need to reinit if using the hq_user_roles global.
                if ( ! $this->use_db )
                        return;

                global $hqdb;

                // Duplicated from _init() to avoid an extra function call.
                $this->role_key = $hqdb->get_blog_prefix() . 'user_roles';
                $this->roles = get_option( $this->role_key );
                if ( empty( $this->roles ) )
                        return;

                $this->role_objects = array();
                $this->role_names =  array();
                foreach ( array_keys( $this->roles ) as $role ) {
                        $this->role_objects[$role] = new HQ_Role( $role, $this->roles[$role]['capabilities'] );
                        $this->role_names[$role] = $this->roles[$role]['name'];
                }
        }


        /**
         * Add role name with capabilities to list.
         *
         * Updates the list of roles, if the role doesn't already exist.
         *
         * The capabilities are defined in the following format `array( 'read' => true );`
         * To explicitly deny a role a capability you set the value for that capability to false.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         * @param string $display_name Role display name.
         * @param array $capabilities List of role capabilities in the above format.
         * @return HQ_Role|void HQ_Role object, if role is added.
         */
        public function add_role( $role, $display_name, $capabilities = array() ) {
                if ( isset( $this->roles[$role] ) )
                        return;

                $this->roles[$role] = array(
                        'name' => $display_name,
                        'capabilities' => $capabilities
                        );
                if ( $this->use_db )
                        update_option( $this->role_key, $this->roles );
                $this->role_objects[$role] = new HQ_Role( $role, $capabilities );
                $this->role_names[$role] = $display_name;
                return $this->role_objects[$role];
        }

        /**
         * Remove role by name.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         */
        public function remove_role( $role ) {
                if ( ! isset( $this->role_objects[$role] ) )
                        return;

                unset( $this->role_objects[$role] );
                unset( $this->role_names[$role] );
                unset( $this->roles[$role] );

                if ( $this->use_db )
                        update_option( $this->role_key, $this->roles );

                if ( get_option( 'default_role' ) == $role )
                        update_option( 'default_role', 'subscriber' );
        }

        /**
         * Add capability to role.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         * @param string $cap Capability name.
         * @param bool $grant Optional, default is true. Whether role is capable of performing capability.
         */
        public function add_cap( $role, $cap, $grant = true ) {
                if ( ! isset( $this->roles[$role] ) )
                        return;

                $this->roles[$role]['capabilities'][$cap] = $grant;
                if ( $this->use_db )
                        update_option( $this->role_key, $this->roles );
        }

        /**
         * Remove capability from role.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         * @param string $cap Capability name.
         */
        public function remove_cap( $role, $cap ) {
                if ( ! isset( $this->roles[$role] ) )
                        return;

                unset( $this->roles[$role]['capabilities'][$cap] );
                if ( $this->use_db )
                        update_option( $this->role_key, $this->roles );
        }

        /**
         * Retrieve role object by name.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         * @return HQ_Role|null HQ_Role object if found, null if the role does not exist.
         */
        public function get_role( $role ) {
                if ( isset( $this->role_objects[$role] ) )
                        return $this->role_objects[$role];
                else
                        return null;
        }

        /**
         * Retrieve list of role names.
         *
         * @since 0.0.1
         * @access public
         *
         * @return array List of role names.
         */
        public function get_names() {
                return $this->role_names;
        }

        /**
         * Whether role name is currently in the list of available roles.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name to look up.
         * @return bool
         */
        public function is_role( $role ) {
                return isset( $this->role_names[$role] );
        }
}

/**
 * HiveQueen Role class.
 *
 * @since 0.0.1
 * @package HiveQueen
 * @subpackage User
 */
class HQ_Role {
        /**
         * Role name.
         *
         * @since 0.0.1
         * @access public
         * @var string
         */
        public $name;

        /**
         * List of capabilities the role contains.
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
        public $capabilities;

        /**
         * Constructor - Set up object properties.
         *
         * The list of capabilities, must have the key as the name of the capability
         * and the value a boolean of whether it is granted to the role.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         * @param array $capabilities List of capabilities.
         */
        public function __construct( $role, $capabilities ) {
                $this->name = $role;
                $this->capabilities = $capabilities;
        }

        /**
         * Assign role a capability.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $cap Capability name.
         * @param bool $grant Whether role has capability privilege.
         */
        public function add_cap( $cap, $grant = true ) {
                $this->capabilities[$cap] = $grant;
                hq_roles()->add_cap( $this->name, $cap, $grant );
        }

        /**
         * Remove capability from role.
         *
         * This is a container for {@link HQ_Roles::remove_cap()} to remove the
         * capability from the role. That is to say, that {@link
         * HQ_Roles::remove_cap()} implements the functionality, but it also makes
         * sense to use this class, because you don't need to enter the role name.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $cap Capability name.
         */
        public function remove_cap( $cap ) {
                unset( $this->capabilities[$cap] );
                hq_roles()->remove_cap( $this->name, $cap );
        }

        /**
         * Whether role has capability.
         *
         * The capabilities is passed through the 'role_has_cap' filter. The first
         * parameter for the hook is the list of capabilities the class has
         * assigned. The second parameter is the capability name to look for. The
         * third and final parameter for the hook is the role name.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $cap Capability name.
         * @return bool True, if user has capability. False, if doesn't have capability.
         */
        public function has_cap( $cap ) {
                /**
                 * Filter which capabilities a role has.
                 *
                 * @since 0.0.1
                 *
                 * @param array  $capabilities Array of role capabilities.
                 * @param string $cap          Capability name.
                 * @param string $name         Role name.
                 */
                $capabilities = apply_filters( 'role_has_cap', $this->capabilities, $cap, $this->name );
                if ( !empty( $capabilities[$cap] ) )
                        return $capabilities[$cap];
                else
                        return false;
        }

}


/**
 * HiveQueen User class.
 *
 * @since 0.0.1
 * @package HiveQueen
 * @subpackage User
 *
 * @property string $nickname
 * @property string $user_description
 * @property string $user_firstname
 * @property string $user_lastname
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property string $user_status
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 */
class HQ_User {
        /**
         * User data container.
         *
         * @since 0.0.1
         * @var object
         */
        public $data;

        /**
         * The user's ID.
         *
         * @since 0.0.1
         * @access public
         * @var int
         */
        public $ID = 0;

        /**
         * The individual capabilities the user has been given.
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
        public $caps = array();

        /**
         * User metadata option name.
         *
         * @since 0.0.1
         * @access public
         * @var string
         */
        public $cap_key;

        /**
         * The roles the user is part of.
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
        public $roles = array();

        /**
         * All capabilities the user has, including individual and role based.
         *
         * @since 0.0.1
         * @access public
         * @var array
         */
        public $allcaps = array();

        /**
         * The filter context applied to user data fields.
         *
         * @since 0.0.1
         * @access private
         * @var string
         */
        var $filter = null;

        /**
         * @static
         * @access private
         * @var array
         */
        private static $back_compat_keys;

        /**
         * Constructor
         *
         * Retrieves the userdata and passes it to {@link HQ_User::init()}.
         *
         * @since 0.0.1
         * @access public
         *
         * @global hqdb $hqdb
         *
         * @param int|string|stdClass|HQ_User $id User's ID, a HQ_User object, or a user object from the DB.
         * @param string $name Optional. User's username
         * @param int $blog_id Optional Blog ID, defaults to current blog.
         */
        public function __construct( $id = 0, $name = '', $blog_id = '' ) {
                if ( ! isset( self::$back_compat_keys ) ) {
                        $prefix = $GLOBALS['hqdb']->prefix;
                        self::$back_compat_keys = array(
                                'user_firstname' => 'first_name',
                                'user_lastname' => 'last_name',
                                'user_description' => 'description',
                                'user_level' => $prefix . 'user_level',
                                $prefix . 'usersettings' => $prefix . 'user-settings',
                                $prefix . 'usersettingstime' => $prefix . 'user-settings-time',
                        );
                }

                if ( $id instanceof HQ_User ) {
                        $this->init( $id->data, $blog_id );
                        return;
                } elseif ( is_object( $id ) ) {
                        $this->init( $id, $blog_id );
                        return;
                }

                if ( ! empty( $id ) && ! is_numeric( $id ) ) {
                        $name = $id;
                        $id = 0;
                }

                if ( $id ) {
                        $data = self::get_data_by( 'id', $id );
                } else {
                        $data = self::get_data_by( 'login', $name );
                }

                if ( $data ) {
                        $this->init( $data, $blog_id );
                } else {
                        $this->data = new stdClass;
                }
        }

        /**
         * Sets up object properties, including capabilities.
         *
         * @param object $data User DB row object
         * @param int $blog_id Optional. The blog id to initialize for
         */
        public function init( $data, $blog_id = '' ) {
                $this->data = $data;
                $this->ID = (int) $data->ID;

                $this->for_blog( $blog_id );
        }

        /**
         * Return only the main user fields
         *
         * @since 0.0.1
         *
         * @static
         *
         * @global hqdb $hqdb
         *
         * @param string $field The field to query against: 'id', 'slug', 'email' or 'login'
         * @param string|int $value The field value
         * @return object|false Raw user object
         */
        public static function get_data_by( $field, $value ) {
                global $hqdb;

                if ( 'id' == $field ) {
                        // Make sure the value is numeric to avoid casting objects, for example,
                        // to int 1.
                        if ( ! is_numeric( $value ) )
                                return false;
                        $value = intval( $value );
                        if ( $value < 1 )
                                return false;
                } else {
                        $value = trim( $value );
                }

                if ( !$value )
                        return false;

                switch ( $field ) {
                        case 'id':
                                $user_id = $value;
                                $db_field = 'ID';
                                break;
                        case 'slug':
                                $user_id = hq_cache_get($value, 'userslugs');
                                $db_field = 'user_nicename';
                                break;
                        case 'email':
                                $user_id = hq_cache_get($value, 'useremail');
                                $db_field = 'user_email';
                                break;
                        case 'login':
                                $value = sanitize_user( $value );
                                $user_id = hq_cache_get($value, 'userlogins');
                                $db_field = 'user_login';
                                break;
                        default:
                                return false;
                }

                if ( false !== $user_id ) {
                        if ( $user = hq_cache_get( $user_id, 'users' ) )
                                return $user;
                }

                if ( !$user = $hqdb->get_row( $hqdb->prepare(
                        "SELECT * FROM $hqdb->users WHERE $db_field = %s", $value
                ) ) )
                        return false;

                update_user_caches( $user );

                return $user;
        }

        /**
         * Makes private/protected methods readable for backwards compatibility.
         *
         * @since 0.0.1
         * @access public
         *
         * @param callable $name      Method to call.
         * @param array    $arguments Arguments to pass when calling.
         * @return mixed|false Return value of the callback, false otherwise.
         */
        public function __call( $name, $arguments ) {
                if ( '_init_caps' === $name ) {
                        return call_user_func_array( array( $this, $name ), $arguments );
                }
                return false;
        }

        /**
         * Magic method for checking the existence of a certain custom field
         *
         * @since 0.0.1
         * @param string $key
         * @return bool
         */
        public function __isset( $key ) {
                if ( 'id' == $key ) {
                        _deprecated_argument( 'HQ_User->id', '2.1', __( 'Use <code>HQ_User->ID</code> instead.' ) );
                        $key = 'ID';
                }

                if ( isset( $this->data->$key ) )
                        return true;

                if ( isset( self::$back_compat_keys[ $key ] ) )
                        $key = self::$back_compat_keys[ $key ];

                return metadata_exists( 'user', $this->ID, $key );
        }

        /**
         * Magic method for accessing custom fields
         *
         * @since 0.0.1
         * @param string $key
         * @return mixed
         */
        public function __get( $key ) {
                if ( 'id' == $key ) {
                        _deprecated_argument( 'HQ_User->id', '2.1', __( 'Use <code>HQ_User->ID</code> instead.' ) );
                        return $this->ID;
                }
                if ( isset( $this->data->$key ) ) {
                        $value = $this->data->$key;
                } else {
                        if ( isset( self::$back_compat_keys[ $key ] ) )
                                $key = self::$back_compat_keys[ $key ];
                        $value = get_user_meta( $this->ID, $key, true );
                }

                if ( $this->filter ) {
                        $value = sanitize_user_field( $key, $value, $this->ID, $this->filter );
                }

                return $value;
        }

        /**
         * Magic method for setting custom fields
         *
         * @since 0.0.1
         */
        public function __set( $key, $value ) {
                if ( 'id' == $key ) {
                        _deprecated_argument( 'HQ_User->id', '2.1', __( 'Use <code>HQ_User->ID</code> instead.' ) );
                        $this->ID = $value;
                        return;
                }

                $this->data->$key = $value;
        }

        /**
         * Determine whether the user exists in the database.
         *
         * @since 0.0.1
         * @access public
         *
         * @return bool True if user exists in the database, false if not.
         */
        public function exists() {
                return ! empty( $this->ID );
        }

        /**
         * Retrieve the value of a property or meta key.
         *
         * Retrieves from the users and usermeta table.
         *
         * @since 0.0.1
         *
         * @param string $key Property
         * @return mixed
         */
        public function get( $key ) {
                return $this->__get( $key );
        }

       /**
         * Determine whether a property or meta key is set
         *
         * Consults the users and usermeta tables.
         *
         * @since 0.0.1
         *
         * @param string $key Property
         * @return bool
         */
        public function has_prop( $key ) {
                return $this->__isset( $key );
        }

        /**
         * Return an array representation.
         *
         * @since 0.0.1
         *
         * @return array Array representation.
         */
        public function to_array() {
                return get_object_vars( $this->data );
        }

        /**
         * Set up capability object properties.
         *
         * Will set the value for the 'cap_key' property to current database table
         * prefix, followed by 'capabilities'. Will then check to see if the
         * property matching the 'cap_key' exists and is an array. If so, it will be
         * used.
         *
         * @access protected
         * @since 0.0.1
         *
         * @global hqdb $hqdb
         *
         * @param string $cap_key Optional capability key
         */
        protected function _init_caps( $cap_key = '' ) {
                global $hqdb;

                if ( empty($cap_key) )
                        $this->cap_key = $hqdb->get_blog_prefix() . 'capabilities';
                else
                        $this->cap_key = $cap_key;

                $this->caps = get_user_meta( $this->ID, $this->cap_key, true );

                if ( ! is_array( $this->caps ) )
                        $this->caps = array();

                $this->get_role_caps();
        }

        /**
         * Retrieve all of the role capabilities and merge with individual capabilities.
         *
         * All of the capabilities of the roles the user belongs to are merged with
         * the users individual roles. This also means that the user can be denied
         * specific roles that their role might have, but the specific user isn't
         * granted permission to.
         *
         * @since 0.0.1
         * @access public
         *
         * @return array List of all capabilities for the user.
         */
        public function get_role_caps() {
                $hq_roles = hq_roles();

                //Filter out caps that are not role names and assign to $this->roles
                if ( is_array( $this->caps ) )
                        $this->roles = array_filter( array_keys( $this->caps ), array( $hq_roles, 'is_role' ) );

                //Build $allcaps from role caps, overlay user's $caps
                $this->allcaps = array();
                foreach ( (array) $this->roles as $role ) {
                        $the_role = $hq_roles->get_role( $role );
                        $this->allcaps = array_merge( (array) $this->allcaps, (array) $the_role->capabilities );
                }
                $this->allcaps = array_merge( (array) $this->allcaps, (array) $this->caps );

                return $this->allcaps;
        }

        /**
         * Add role to user.
         *
         * Updates the user's meta data option with capabilities and roles.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         */
        public function add_role( $role ) {
                $this->caps[$role] = true;
                update_user_meta( $this->ID, $this->cap_key, $this->caps );
                $this->get_role_caps();
                $this->update_user_level_from_caps();

                /**
                 * Fires immediately after the user has been given a new role.
                 *
                 * @since 0.0.1
                 *
                 * @param int    $user_id The user ID.
                 * @param string $role    The new role.
                 */
                do_action( 'add_user_role', $this->ID, $role );
        }

       /**
         * Remove role from user.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         */
        public function remove_role( $role ) {
                if ( !in_array($role, $this->roles) )
                        return;
                unset( $this->caps[$role] );
                update_user_meta( $this->ID, $this->cap_key, $this->caps );
                $this->get_role_caps();
                $this->update_user_level_from_caps();

                /**
                 * Fires immediately after a role as been removed from a user.
                 *
                 * @since 0.0.1
                 *
                 * @param int    $user_id The user ID.
                 * @param string $role    The removed role.
                 */
                do_action( 'remove_user_role', $this->ID, $role );
        }

        /**
         * Set the role of the user.
         *
         * This will remove the previous roles of the user and assign the user the
         * new one. You can set the role to an empty string and it will remove all
         * of the roles from the user.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $role Role name.
         */
        public function set_role( $role ) {
                if ( 1 == count( $this->roles ) && $role == current( $this->roles ) )
                        return;

                foreach ( (array) $this->roles as $oldrole )
                        unset( $this->caps[$oldrole] );

                $old_roles = $this->roles;
                if ( !empty( $role ) ) {
                        $this->caps[$role] = true;
                        $this->roles = array( $role => true );
                } else {
                        $this->roles = false;
                }
                update_user_meta( $this->ID, $this->cap_key, $this->caps );
                $this->get_role_caps();
                $this->update_user_level_from_caps();

                /**
                 * Fires after the user's role has changed.
                 *
                 * @since 0.0.1
                 *
                 * @param int    $user_id   The user ID.
                 * @param string $role      The new role.
                 * @param array  $old_roles An array of the user's previous roles.
                 */
                do_action( 'set_user_role', $this->ID, $role, $old_roles );
        }

       /**
         * Choose the maximum level the user has.
         *
         * Will compare the level from the $item parameter against the $max
         * parameter. If the item is incorrect, then just the $max parameter value
         * will be returned.
         *
         * Used to get the max level based on the capabilities the user has. This
         * is also based on roles, so if the user is assigned the Administrator role
         * then the capability 'level_10' will exist and the user will get that
         * value.
         *
         * @since 0.0.1
         * @access public
         *
         * @param int $max Max level of user.
         * @param string $item Level capability name.
         * @return int Max Level.
         */
        public function level_reduction( $max, $item ) {
                if ( preg_match( '/^level_(10|[0-9])$/i', $item, $matches ) ) {
                        $level = intval( $matches[1] );
                        return max( $max, $level );
                } else {
                        return $max;
                }
        }

        /**
         * Update the maximum user level for the user.
         *
         * Updates the 'user_level' user metadata (includes prefix that is the
         * database table prefix) with the maximum user level. Gets the value from
         * the all of the capabilities that the user has.
         *
         * @since 0.0.1
         * @access public
         *
         * @global hqdb $hqdb
         */
        public function update_user_level_from_caps() {
                global $hqdb;
                $this->user_level = array_reduce( array_keys( $this->allcaps ), array( $this, 'level_reduction' ), 0 );
                update_user_meta( $this->ID, $hqdb->get_blog_prefix() . 'user_level', $this->user_level );
        }

        /**
         * Add capability and grant or deny access to capability.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $cap Capability name.
         * @param bool $grant Whether to grant capability to user.
         */
        public function add_cap( $cap, $grant = true ) {
                $this->caps[$cap] = $grant;
                update_user_meta( $this->ID, $this->cap_key, $this->caps );
                $this->get_role_caps();
                $this->update_user_level_from_caps();
        }

        /**
         * Remove capability from user.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string $cap Capability name.
         */
        public function remove_cap( $cap ) {
                if ( ! isset( $this->caps[ $cap ] ) ) {
                        return;
                }
                unset( $this->caps[ $cap ] );
                update_user_meta( $this->ID, $this->cap_key, $this->caps );
                $this->get_role_caps();
                $this->update_user_level_from_caps();
        }

        /**
         * Remove all of the capabilities of the user.
         *
         * @since 0.0.1
         * @access public
         *
         * @global hqdb $hqdb
         */
        public function remove_all_caps() {
                global $hqdb;
                $this->caps = array();
                delete_user_meta( $this->ID, $this->cap_key );
                delete_user_meta( $this->ID, $hqdb->get_blog_prefix() . 'user_level' );
                $this->get_role_caps();
        }


        /**  
         * Whether user has capability or role name.
         *
         * This is useful for looking up whether the user has a specific role
         * assigned to the user. The second optional parameter can also be used to
         * check for capabilities against a specific object, such as a post or user.
         *
         * @since 0.0.1
         * @access public
         *
         * @param string|int $cap Capability or role name to search.
         * @return bool True, if user has capability; false, if user does not have capability.
         */
        public function has_cap( $cap ) {
                if ( is_numeric( $cap ) ) {
                        _deprecated_argument( __FUNCTION__, '2.0', __('Usage of user levels by plugins and themes is deprecated. Use roles and capabilities instead.') );
                        $cap = $this->translate_level_to_cap( $cap );
                }

                $args = array_slice( func_get_args(), 1 );
                $args = array_merge( array( $cap, $this->ID ), $args );
                $caps = call_user_func_array( 'map_meta_cap', $args );

                // Multisite super admin has all caps by definition, Unless specifically denied.
                if ( is_multisite() && is_super_admin( $this->ID ) ) {
                        if ( in_array('do_not_allow', $caps) )
                                return false;
                        return true;
                }

                /**
                 * Dynamically filter a user's capabilities.
                 *
                 * @since 0.0.1
                 *
                 * @param array   $allcaps An array of all the user's capabilities.
                 * @param array   $caps    Actual capabilities for meta capability.
                 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
                 * @param HQ_User $user    The user object.
                 */
                // Must have ALL requested caps
                $capabilities = apply_filters( 'user_has_cap', $this->allcaps, $caps, $args, $this );
                $capabilities['exist'] = true; // Everyone is allowed to exist
                foreach ( (array) $caps as $cap ) {
                        if ( empty( $capabilities[ $cap ] ) )
                                return false;
                }

                return true;
        }

        /**
         * Convert numeric level to level capability name.
         *
         * Prepends 'level_' to level number.
         *
         * @since 0.0.1
         * @access public
         *
         * @param int $level Level number, 1 to 10.
         * @return string
         */
        public function translate_level_to_cap( $level ) {
                return 'level_' . $level;
        }

        /**
         * Set the blog to operate on. Defaults to the current blog.
         *
         * @since 0.0.1
         *
         * @global hqdb $hqdb
         *
         * @param int $blog_id Optional Blog ID, defaults to current blog.
         */
        public function for_blog( $blog_id = '' ) {
                global $hqdb;
                if ( ! empty( $blog_id ) )
                        $cap_key = $hqdb->get_blog_prefix( $blog_id ) . 'capabilities';
                else
                        $cap_key = '';
                $this->_init_caps( $cap_key );
        }
}

//TODO: *************************************** functions ************************************************+

