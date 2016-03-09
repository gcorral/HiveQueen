<?php
/**
 * API for creating dynamic sidebar without hardcoding functionality into
 * themes. Includes both internal HiveQueen routines and theme use routines.
 *
 * This functionality was found in a plugin before HiveQueen 0.1 release which
 * included it in the core from that point on.
 *
 * @package HiveQueen
 * @subpackage Widgets
 */

/**
 * This class must be extended for each widget and HQ_Widget::widget(), HQ_Widget::update()
 * and HQ_Widget::form() need to be over-ridden.
 *
 * @package HiveQueen
 * @subpackage Widgets
 * @since 0.0.1
 */
class HQ_Widget {

	/**
	 * Root ID for all widgets of this type.
	 *
	 * @since 0.0.1
	 * @access public
	 * @var mixed|string
	 */
	public $id_base;

	/**
	 * Name for this widget type.
	 *
	 * @since 0.0.1
	 * @access public
	 * @var string
	 */
	public $name;

	/**
	 * Option array passed to {@see hq_register_sidebar_widget()}.
	 *
	 * @since 0.0.1
	 * @access public
	 * @var array
	 */
	public $widget_options;

	/**
	 * Option array passed to {@see hq_register_widget_control()}.
	 *
	 * @since 0.0.1
	 * @access public
	 * @var array
	 */
	public $control_options;

	/**
	 * Unique ID number of the current instance.
	 *
	 * @since 0.0.1
	 * @access public
	 * @var bool|int
	 */
	public $number = false;

	/**
	 * Unique ID string of the current instance (id_base-number).
	 *
	 * @since 0.0.1
	 * @access public
	 * @var bool|string
	 */
	public $id = false;

	/**
	 * Whether the widget data has been updated.
	 *
	 * Set to true when the data is updated after a POST submit - ensures it does
	 * not happen twice.
	 *
	 * @since 0.0.1
	 * @access public
	 * @var bool
	 */
	public $updated = false;

	// Member functions that you must over-ride.

	/**
	 * Echo the widget content.
	 *
	 * Subclasses should over-ride this function to generate their widget code.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param array $args     Display arguments including before_title, after_title,
	 *                        before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		die('function HQ_Widget::widget() must be over-ridden in a sub-class.');
	}

	/**
	 * Update a particular instance.
	 *
	 * This function should check that $new_instance is set correctly. The newly-calculated
	 * value of `$instance` should be returned. If false is returned, the instance won't be
	 * saved/updated.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            {@see HQ_Widget::form()}.
	 * @param array $old_instance Old settings for this instance.
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	/**
	 * Output the settings update form.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param array $instance Current settings.
	 * @return string Default return is 'noform'.
	 */
	public function form($instance) {
		echo '<p class="no-options-widget">' . __('There are no options for this widget.') . '</p>';
		return 'noform';
	}

	// Functions you'll need to call.

	/**
	 * PHP5 constructor.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param string $id_base         Optional Base ID for the widget, lowercase and unique. If left empty,
	 *                                a portion of the widget's class name will be used Has to be unique.
	 * @param string $name            Name for the widget displayed on the configuration page.
	 * @param array  $widget_options  Optional. Widget options. See {@see hq_register_sidebar_widget()} for
	 *                                information on accepted arguments. Default empty array.
	 * @param array  $control_options Optional. Widget control options. See {@see hq_register_widget_control()}
	 *                                for information on accepted arguments. Default empty array.
	 */
	public function __construct( $id_base, $name, $widget_options = array(), $control_options = array() ) {
		$this->id_base = empty($id_base) ? preg_replace( '/(hq_)?widget_/', '', strtolower(get_class($this)) ) : strtolower($id_base);
		$this->name = $name;
		$this->option_name = 'widget_' . $this->id_base;
		$this->widget_options = hq_parse_args( $widget_options, array('classname' => $this->option_name) );
		$this->control_options = hq_parse_args( $control_options, array('id_base' => $this->id_base) );
	}

	/**
	 * PHP4 constructor
	 *
	 * @param string $id_base
	 * @param string $name
	 * @param array  $widget_options
	 * @param array  $control_options
	 */
	public function HQ_Widget( $id_base, $name, $widget_options = array(), $control_options = array() ) {
		_deprecated_constructor( 'HQ_Widget', '4.3.0' );
		HQ_Widget::__construct( $id_base, $name, $widget_options, $control_options );
	}

	/**
	 * Constructs name attributes for use in form() fields
	 *
	 * This function should be used in form() methods to create name attributes for fields to be saved by update()
	 *
	 * @param string $field_name Field name
	 * @return string Name attribute for $field_name
	 */
	public function get_field_name($field_name) {
		return 'widget-' . $this->id_base . '[' . $this->number . '][' . $field_name . ']';
	}

	/**
	 * Constructs id attributes for use in {@see HQ_Widget::form()} fields.
	 *
	 * This function should be used in form() methods to create id attributes
	 * for fields to be saved by {@see HQ_Widget::update()}.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param string $field_name Field name.
	 * @return string ID attribute for `$field_name`.
	 */
	public function get_field_id( $field_name ) {
		return 'widget-' . $this->id_base . '-' . $this->number . '-' . $field_name;
	}

	/**
	 * Register all widget instances of this widget class.
	 *
	 * @since 0.0.1
	 * @access private
	 */
	public function _register() {
		$settings = $this->get_settings();
		$empty = true;

		// When $settings is an array-like object, get an intrinsic array for use with array_keys().
		if ( $settings instanceof ArrayObject || $settings instanceof ArrayIterator ) {
			$settings = $settings->getArrayCopy();
		}

		if ( is_array( $settings ) ) {
			foreach ( array_keys( $settings ) as $number ) {
				if ( is_numeric( $number ) ) {
					$this->_set( $number );
					$this->_register_one( $number );
					$empty = false;
				}
			}
		}

		if ( $empty ) {
			// If there are none, we register the widget's existence with a generic template.
			$this->_set( 1 );
			$this->_register_one();
		}
	}

	/**
	 * Set the internal order number for the widget instance.
	 *
	 * @since 0.0.1
	 * @access private
	 *
	 * @param int $number The unique order number of this widget instance compared to other
	 *                    instances of the same class.
	 */
	public function _set($number) {
		$this->number = $number;
		$this->id = $this->id_base . '-' . $number;
	}

	/**
	 * @return callback
	 */
	public function _get_display_callback() {
		return array($this, 'display_callback');
	}
	/**
	 * @return callback
	 */
	public function _get_update_callback() {
		return array($this, 'update_callback');
	}
	/**
	 * @return callback
	 */
	public function _get_form_callback() {
		return array($this, 'form_callback');
	}

	/**
	 * Determine whether the current request is inside the Customizer preview.
	 *
	 * If true -- the current request is inside the Customizer preview, then
	 * the object cache gets suspended and widgets should check this to decide
	 * whether they should store anything persistently to the object cache,
	 * to transients, or anywhere else.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @global HQ_Customize_Manager $hq_customize
	 *
	 * @return bool True if within the Customizer preview, false if not.
	 */
	public function is_preview() {
		global $hq_customize;
		return ( isset( $hq_customize ) && $hq_customize->is_preview() ) ;
	}

	/**
	 * Generate the actual widget content (Do NOT override).
	 *
	 * Finds the instance and calls {@see HQ_Widget::widget()}.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param array     $args        Display arguments. See {@see HQ_Widget::widget()} for information
	 *                               on accepted arguments.
	 * @param int|array $widget_args {
	 *     Optional. Internal order number of the widget instance, or array of multi-widget arguments.
	 *     Default 1.
	 *
	 *     @type int $number Number increment used for multiples of the same widget.
	 * }
	 */
	public function display_callback( $args, $widget_args = 1 ) {
		if ( is_numeric( $widget_args ) ) {
			$widget_args = array( 'number' => $widget_args );
		}

		$widget_args = hq_parse_args( $widget_args, array( 'number' => -1 ) );
		$this->_set( $widget_args['number'] );
		$instances = $this->get_settings();

		if ( array_key_exists( $this->number, $instances ) ) {
			$instance = $instances[ $this->number ];

			/**
			 * Filter the settings for a particular widget instance.
			 *
			 * Returning false will effectively short-circuit display of the widget.
			 *
			 * @since 0.0.1
			 *
			 * @param array     $instance The current widget instance's settings.
			 * @param HQ_Widget $this     The current widget instance.
			 * @param array     $args     An array of default widget arguments.
			 */
			$instance = apply_filters( 'widget_display_callback', $instance, $this, $args );

			if ( false === $instance ) {
				return;
			}

			$was_cache_addition_suspended = hq_suspend_cache_addition();
			if ( $this->is_preview() && ! $was_cache_addition_suspended ) {
				hq_suspend_cache_addition( true );
			}

			$this->widget( $args, $instance );

			if ( $this->is_preview() ) {
				hq_suspend_cache_addition( $was_cache_addition_suspended );
			}
		}
	}

	/**
	 * Deal with changed settings (Do NOT override).
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @global array $hq_registered_widgets
	 *
	 * @param int $deprecated Not used.
	 */
	public function update_callback( $deprecated = 1 ) {
		global $hq_registered_widgets;

		$all_instances = $this->get_settings();

		// We need to update the data
		if ( $this->updated )
			return;

		if ( isset($_POST['delete_widget']) && $_POST['delete_widget'] ) {
			// Delete the settings for this instance of the widget
			if ( isset($_POST['the-widget-id']) )
				$del_id = $_POST['the-widget-id'];
			else
				return;

			if ( isset($hq_registered_widgets[$del_id]['params'][0]['number']) ) {
				$number = $hq_registered_widgets[$del_id]['params'][0]['number'];

				if ( $this->id_base . '-' . $number == $del_id )
					unset($all_instances[$number]);
			}
		} else {
			if ( isset($_POST['widget-' . $this->id_base]) && is_array($_POST['widget-' . $this->id_base]) ) {
				$settings = $_POST['widget-' . $this->id_base];
			} elseif ( isset($_POST['id_base']) && $_POST['id_base'] == $this->id_base ) {
				$num = $_POST['multi_number'] ? (int) $_POST['multi_number'] : (int) $_POST['widget_number'];
				$settings = array( $num => array() );
			} else {
				return;
			}

			foreach ( $settings as $number => $new_instance ) {
				$new_instance = stripslashes_deep($new_instance);
				$this->_set($number);

				$old_instance = isset($all_instances[$number]) ? $all_instances[$number] : array();

				$was_cache_addition_suspended = hq_suspend_cache_addition();
				if ( $this->is_preview() && ! $was_cache_addition_suspended ) {
					hq_suspend_cache_addition( true );
				}

				$instance = $this->update( $new_instance, $old_instance );

				if ( $this->is_preview() ) {
					hq_suspend_cache_addition( $was_cache_addition_suspended );
				}

				/**
				 * Filter a widget's settings before saving.
				 *
				 * Returning false will effectively short-circuit the widget's ability
				 * to update settings.
				 *
				 * @since 0.0.1
				 *
				 * @param array     $instance     The current widget instance's settings.
				 * @param array     $new_instance Array of new widget settings.
				 * @param array     $old_instance Array of old widget settings.
				 * @param HQ_Widget $this         The current widget instance.
				 */
				$instance = apply_filters( 'widget_update_callback', $instance, $new_instance, $old_instance, $this );
				if ( false !== $instance ) {
					$all_instances[$number] = $instance;
				}

				break; // run only once
			}
		}

		$this->save_settings($all_instances);
		$this->updated = true;
	}

	/**
	 * Generate the widget control form (Do NOT override).
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param int|array $widget_args Widget instance number or array of widget arguments.
	 * @return string|null
	 */
	public function form_callback( $widget_args = 1 ) {
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );

		$widget_args = hq_parse_args( $widget_args, array( 'number' => -1 ) );
		$all_instances = $this->get_settings();

		if ( -1 == $widget_args['number'] ) {
			// We echo out a form where 'number' can be set later
			$this->_set('__i__');
			$instance = array();
		} else {
			$this->_set($widget_args['number']);
			$instance = $all_instances[ $widget_args['number'] ];
		}

		/**
		 * Filter the widget instance's settings before displaying the control form.
		 *
		 * Returning false effectively short-circuits display of the control form.
		 *
		 * @since 0.0.1
		 *
		 * @param array     $instance The current widget instance's settings.
		 * @param HQ_Widget $this     The current widget instance.
		 */
		$instance = apply_filters( 'widget_form_callback', $instance, $this );

		$return = null;
		if ( false !== $instance ) {
			$return = $this->form($instance);

			/**
			 * Fires at the end of the widget control form.
			 *
			 * Use this hook to add extra fields to the widget form. The hook
			 * is only fired if the value passed to the 'widget_form_callback'
			 * hook is not false.
			 *
			 * Note: If the widget has no form, the text echoed from the default
			 * form method can be hidden using CSS.
			 *
			 * @since 0.0.1
			 *
			 * @param HQ_Widget $this     The widget instance, passed by reference.
			 * @param null      $return   Return null if new fields are added.
			 * @param array     $instance An array of the widget's settings.
			 */
			do_action_ref_array( 'in_widget_form', array( &$this, &$return, $instance ) );
		}
		return $return;
	}

	/**
	 * Register an instance of the widget class.
	 *
	 * @since 0.0.1
	 * @access private
	 *
	 * @param integer $number Optional. The unique order number of this widget instance
	 *                        compared to other instances of the same class. Default -1.
	 */
	public function _register_one( $number = -1 ) {
		hq_register_sidebar_widget(	$this->id, $this->name,	$this->_get_display_callback(), $this->widget_options, array( 'number' => $number ) );
		_register_widget_update_callback( $this->id_base, $this->_get_update_callback(), $this->control_options, array( 'number' => -1 ) );
		_register_widget_form_callback(	$this->id, $this->name,	$this->_get_form_callback(), $this->control_options, array( 'number' => $number ) );
	}

	/**
	 * Save the settings for all instances of the widget class.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param array $settings Multi-dimensional array of widget instance settings.
	 */
	public function save_settings( $settings ) {
		$settings['_multiwidget'] = 1;
		update_option( $this->option_name, $settings );
	}

	/**
	 * Get the settings for all instances of the widget class.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @return array Multi-dimensional array of widget instance settings.
	 */
	public function get_settings() {

		$settings = get_option( $this->option_name );

		if ( false === $settings && isset( $this->alt_option_name ) ) {
			$settings = get_option( $this->alt_option_name );
		}

		if ( ! is_array( $settings ) && ! ( $settings instanceof ArrayObject || $settings instanceof ArrayIterator ) ) {
			$settings = array();
		}

		if ( ! empty( $settings ) && ! isset( $settings['_multiwidget'] ) ) {
			// Old format, convert if single widget.
			$settings = hq_convert_widget_settings( $this->id_base, $this->option_name, $settings );
		}

		unset( $settings['_multiwidget'], $settings['__i__'] );
		return $settings;
	}
}

/**
 * Singleton that registers and instantiates HQ_Widget classes.
 *
 * @package HiveQueen
 * @subpackage Widgets
 * @since 0.0.1
 */
class HQ_Widget_Factory {
	public $widgets = array();

	/**
	 * PHP5 constructor.
	 */
	public function __construct() {
		add_action( 'widgets_init', array( $this, '_register_widgets' ), 100 );
	}

	/**
	 * PHP4 constructor.
	 */
	public function HQ_Widget_Factory() {
		_deprecated_constructor( 'HQ_Widget_Factory', '4.2.0' );
		self::__construct();
	}

	/**
	 * Register a widget subclass.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param string $widget_class The name of a {@see HQ_Widget} subclass.
	 */
	public function register( $widget_class ) {
		$this->widgets[$widget_class] = new $widget_class();
	}

	/**
	 * Un-register a widget subclass.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @param string $widget_class The name of a {@see HQ_Widget} subclass.
	 */
	public function unregister( $widget_class ) {
		unset( $this->widgets[ $widget_class ] );
	}

	/**
	 * Utility method for adding widgets to the registered widgets global.
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @global array $hq_registered_widgets
	 */
	public function _register_widgets() {
		global $hq_registered_widgets;
		$keys = array_keys($this->widgets);
		$registered = array_keys($hq_registered_widgets);
		$registered = array_map('_get_widget_id_base', $registered);

		foreach ( $keys as $key ) {
			// don't register new widget if old widget with the same id is already registered
			if ( in_array($this->widgets[$key]->id_base, $registered, true) ) {
				unset($this->widgets[$key]);
				continue;
			}

			$this->widgets[$key]->_register();
		}
	}
}

/* Global Variables */

/** @ignore */
global $hq_registered_sidebars, $hq_registered_widgets, $hq_registered_widget_controls, $hq_registered_widget_updates;

/**
 * Stores the sidebars, since many themes can have more than one.
 *
 * @global array $hq_registered_sidebars
 * @since 0.0.1
 */
$hq_registered_sidebars = array();

/**
 * Stores the registered widgets.
 *
 * @global array $hq_registered_widgets
 * @since 0.0.1
 */
$hq_registered_widgets = array();

/**
 * Stores the registered widget control (options).
 *
 * @global array $hq_registered_widget_controls
 * @since 0.0.1
 */
$hq_registered_widget_controls = array();
/**
 * @global array $hq_registered_widget_updates
 */
$hq_registered_widget_updates = array();

/**
 * Private
 *
 * @global array $_hq_sidebars_widgets
 */
$_hq_sidebars_widgets = array();

/**
 * Private
 *
 * @global array $_hq_deprecated_widgets_callbacks
 */
$GLOBALS['_hq_deprecated_widgets_callbacks'] = array(
	'hq_widget_pages',
	'hq_widget_pages_control',
	'hq_widget_calendar',
	'hq_widget_calendar_control',
	'hq_widget_archives',
	'hq_widget_archives_control',
	'hq_widget_links',
	'hq_widget_meta',
	'hq_widget_meta_control',
	'hq_widget_search',
	'hq_widget_recent_entries',
	'hq_widget_recent_entries_control',
	'hq_widget_tag_cloud',
	'hq_widget_tag_cloud_control',
	'hq_widget_categories',
	'hq_widget_categories_control',
	'hq_widget_text',
	'hq_widget_text_control',
	'hq_widget_rss',
	'hq_widget_rss_control',
	'hq_widget_recent_comments',
	'hq_widget_recent_comments_control'
);

/* Template tags & API functions */

/**
 * Register a widget
 *
 * Registers a HQ_Widget widget
 *
 * @since 0.0.1
 *
 * @see HQ_Widget
 *
 * @global HQ_Widget_Factory $hq_widget_factory
 *
 * @param string $widget_class The name of a class that extends HQ_Widget
 */
function register_widget($widget_class) {
	global $hq_widget_factory;

	$hq_widget_factory->register($widget_class);
}

/**
 * Unregister a widget
 *
 * Unregisters a HQ_Widget widget. Useful for unregistering default widgets.
 * Run within a function hooked to the widgets_init action.
 *
 * @since 0.0.1
 *
 * @see HQ_Widget
 *
 * @global HQ_Widget_Factory $hq_widget_factory
 *
 * @param string $widget_class The name of a class that extends HQ_Widget
 */
function unregister_widget($widget_class) {
	global $hq_widget_factory;

	$hq_widget_factory->unregister($widget_class);
}

/**
 * Creates multiple sidebars.
 *
 * If you wanted to quickly create multiple sidebars for a theme or internally.
 * This function will allow you to do so. If you don't pass the 'name' and/or
 * 'id' in `$args`, then they will be built for you.
 *
 * @since 0.0.1
 *
 * @see register_sidebar() The second parameter is documented by register_sidebar() and is the same here.
 *
 * @global array $hq_registered_sidebars
 *
 * @param int          $number Optional. Number of sidebars to create. Default 1.
 * @param array|string $args {
 *     Optional. Array or string of arguments for building a sidebar.
 *
 *     @type string $id   The base string of the unique identifier for each sidebar. If provided, and multiple
 *                        sidebars are being defined, the id will have "-2" appended, and so on.
 *                        Default 'sidebar-' followed by the number the sidebar creation is currently at.
 *     @type string $name The name or title for the sidebars displayed in the admin dashboard. If registering
 *                        more than one sidebar, include '%d' in the string as a placeholder for the uniquely
 *                        assigned number for each sidebar.
 *                        Default 'Sidebar' for the first sidebar, otherwise 'Sidebar %d'.
 * }
 */
function register_sidebars( $number = 1, $args = array() ) {
	global $hq_registered_sidebars;
	$number = (int) $number;

	if ( is_string($args) )
		parse_str($args, $args);

	for ( $i = 1; $i <= $number; $i++ ) {
		$_args = $args;

		if ( $number > 1 )
			$_args['name'] = isset($args['name']) ? sprintf($args['name'], $i) : sprintf(__('Sidebar %d'), $i);
		else
			$_args['name'] = isset($args['name']) ? $args['name'] : __('Sidebar');

		// Custom specified ID's are suffixed if they exist already.
		// Automatically generated sidebar names need to be suffixed regardless starting at -0
		if ( isset($args['id']) ) {
			$_args['id'] = $args['id'];
			$n = 2; // Start at -2 for conflicting custom ID's
			while ( isset($hq_registered_sidebars[$_args['id']]) )
				$_args['id'] = $args['id'] . '-' . $n++;
		} else {
			$n = count($hq_registered_sidebars);
			do {
				$_args['id'] = 'sidebar-' . ++$n;
			} while ( isset($hq_registered_sidebars[$_args['id']]) );
		}
		register_sidebar($_args);
	}
}

/**
 * Builds the definition for a single sidebar and returns the ID.
 *
 * Accepts either a string or an array and then parses that against a set
 * of default arguments for the new sidebar. HiveQueen will automatically
 * generate a sidebar ID and name based on the current number of registered
 * sidebars if those arguments are not included.
 *
 * When allowing for automatic generation of the name and ID parameters, keep
 * in mind that the incrementor for your sidebar can change over time depending
 * on what other plugins and themes are installed.
 *
 * If theme support for 'widgets' has not yet been added when this function is
 * called, it will be automatically enabled through the use of add_theme_support()
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_sidebars Stores the new sidebar in this array by sidebar ID.
 *
 * @param array|string $args {
 *     Optional. Array or string of arguments for the sidebar being registered.
 *
 *     @type string $name          The name or title of the sidebar displayed in the Widgets
 *                                 interface. Default 'Sidebar $instance'.
 *     @type string $id            The unique identifier by which the sidebar will be called.
 *                                 Default 'sidebar-$instance'.
 *     @type string $description   Description of the sidebar, displayed in the Widgets interface.
 *                                 Default empty string.
 *     @type string $class         Extra CSS class to assign to the sidebar in the Widgets interface.
 *                                 Default empty.
 *     @type string $before_widget HTML content to prepend to each widget's HTML output when
 *                                 assigned to this sidebar. Default is an opening list item element.
 *     @type string $after_widget  HTML content to append to each widget's HTML output when
 *                                 assigned to this sidebar. Default is a closing list item element.
 *     @type string $before_title  HTML content to prepend to the sidebar title when displayed.
 *                                 Default is an opening h2 element.
 *     @type string $after_title   HTML content to append to the sidebar title when displayed.
 *                                 Default is a closing h2 element.
 * }
 * @return string Sidebar ID added to $hq_registered_sidebars global.
 */
function register_sidebar($args = array()) {
	global $hq_registered_sidebars;

	$i = count($hq_registered_sidebars) + 1;

	$id_is_empty = empty( $args['id'] );

	$defaults = array(
		'name' => sprintf(__('Sidebar %d'), $i ),
		'id' => "sidebar-$i",
		'description' => '',
		'class' => '',
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => "</li>\n",
		'before_title' => '<h2 class="widgettitle">',
		'after_title' => "</h2>\n",
	);

	$sidebar = hq_parse_args( $args, $defaults );

	if ( $id_is_empty ) {
		/* translators: 1: the id argument, 2: sidebar name, 3: recommended id value */
		_doing_it_wrong( __FUNCTION__, sprintf( __( 'No %1$s was set in the arguments array for the "%2$s" sidebar. Defaulting to "%3$s". Manually set the %1$s to "%3$s" to silence this notice and keep existing sidebar content.' ), '<code>id</code>', $sidebar['name'], $sidebar['id'] ), '4.2.0' );
	}

	$hq_registered_sidebars[$sidebar['id']] = $sidebar;

	add_theme_support('widgets');

	/**
	 * Fires once a sidebar has been registered.
	 *
	 * @since 0.0.1
	 *
	 * @param array $sidebar Parsed arguments for the registered sidebar.
	 */
	do_action( 'register_sidebar', $sidebar );

	return $sidebar['id'];
}

/**
 * Removes a sidebar from the list.
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_sidebars Stores the new sidebar in this array by sidebar ID.
 *
 * @param string $name The ID of the sidebar when it was added.
 */
function unregister_sidebar( $name ) {
	global $hq_registered_sidebars;

	unset( $hq_registered_sidebars[ $name ] );
}

/**
 * Register an instance of a widget.
 *
 * The default widget option is 'classname' that can be overridden.
 *
 * The function can also be used to un-register widgets when `$output_callback`
 * parameter is an empty string.
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_widgets       Uses stored registered widgets.
 * @global array $hq_register_widget_defaults Retrieves widget defaults.
 * @global array $hq_registered_widget_updates
 * @global array $_hq_deprecated_widgets_callbacks
 *
 * @param int|string $id              Widget ID.
 * @param string     $name            Widget display title.
 * @param callback   $output_callback Run when widget is called.
 * @param array      $options {
 *     Optional. An array of supplementary widget options for the instance.
 *
 *     @type string $classname   Class name for the widget's HTML container. Default is a shortened
 *                               version of the output callback name.
 *     @type string $description Widget description for display in the widget administration
 *                               panel and/or theme.
 * }
 */
function hq_register_sidebar_widget( $id, $name, $output_callback, $options = array() ) {
	global $hq_registered_widgets, $hq_registered_widget_controls, $hq_registered_widget_updates, $_hq_deprecated_widgets_callbacks;

	$id = strtolower($id);

	if ( empty($output_callback) ) {
		unset($hq_registered_widgets[$id]);
		return;
	}

	$id_base = _get_widget_id_base($id);
	if ( in_array($output_callback, $_hq_deprecated_widgets_callbacks, true) && !is_callable($output_callback) ) {
		unset( $hq_registered_widget_controls[ $id ] );
		unset( $hq_registered_widget_updates[ $id_base ] );
		return;
	}

	$defaults = array('classname' => $output_callback);
	$options = hq_parse_args($options, $defaults);
	$widget = array(
		'name' => $name,
		'id' => $id,
		'callback' => $output_callback,
		'params' => array_slice(func_get_args(), 4)
	);
	$widget = array_merge($widget, $options);

	if ( is_callable($output_callback) && ( !isset($hq_registered_widgets[$id]) || did_action( 'widgets_init' ) ) ) {

		/**
		 * Fires once for each registered widget.
		 *
		 * @since 0.0.1
		 *
		 * @param array $widget An array of default widget arguments.
		 */
		do_action( 'hq_register_sidebar_widget', $widget );
		$hq_registered_widgets[$id] = $widget;
	}
}

/**
 * Retrieve description for widget.
 *
 * When registering widgets, the options can also include 'description' that
 * describes the widget for display on the widget administration panel or
 * in the theme.
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_widgets
 *
 * @param int|string $id Widget ID.
 * @return string|void Widget description, if available.
 */
function hq_widget_description( $id ) {
	if ( !is_scalar($id) )
		return;

	global $hq_registered_widgets;

	if ( isset($hq_registered_widgets[$id]['description']) )
		return esc_html( $hq_registered_widgets[$id]['description'] );
}

/**
 * Retrieve description for a sidebar.
 *
 * When registering sidebars a 'description' parameter can be included that
 * describes the sidebar for display on the widget administration panel.
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_sidebars
 *
 * @param string $id sidebar ID.
 * @return string|void Sidebar description, if available.
 */
function hq_sidebar_description( $id ) {
	if ( !is_scalar($id) )
		return;

	global $hq_registered_sidebars;

	if ( isset($hq_registered_sidebars[$id]['description']) )
		return esc_html( $hq_registered_sidebars[$id]['description'] );
}

/**
 * Remove widget from sidebar.
 *
 * @since 0.0.1
 *
 * @param int|string $id Widget ID.
 */
function hq_unregister_sidebar_widget($id) {

	/**
	 * Fires just before a widget is removed from a sidebar.
	 *
	 * @since 0.0.1
	 *
	 * @param int $id The widget ID.
	 */
	do_action( 'hq_unregister_sidebar_widget', $id );

	hq_register_sidebar_widget($id, '', '');
	hq_unregister_widget_control($id);
}

/**
 * Registers widget control callback for customizing options.
 *
 * The options contains the 'height', 'width', and 'id_base' keys. The 'height'
 * option is never used. The 'width' option is the width of the fully expanded
 * control form, but try hard to use the default width. The 'id_base' is for
 * multi-widgets (widgets which allow multiple instances such as the text
 * widget), an id_base must be provided. The widget id will end up looking like
 * `{$id_base}-{$unique_number}`.
 *
 * @since 0.0.1
 *
 * @todo Document `$options` as a hash notation, re: HQ_Widget::__construct() cross-reference.
 * @todo `$params` parameter?
 *
 * @global array $hq_registered_widget_controls
 * @global array $hq_registered_widget_updates
 * @global array $hq_registered_widgets
 * @global array $_hq_deprecated_widgets_callbacks
 *
 * @param int|string   $id               Sidebar ID.
 * @param string       $name             Sidebar display name.
 * @param callback     $control_callback Run when sidebar is displayed.
 * @param array|string $options          Optional. Widget options. See description above. Default empty array.
 */
function hq_register_widget_control( $id, $name, $control_callback, $options = array() ) {
	global $hq_registered_widget_controls, $hq_registered_widget_updates, $hq_registered_widgets, $_hq_deprecated_widgets_callbacks;

	$id = strtolower($id);
	$id_base = _get_widget_id_base($id);

	if ( empty($control_callback) ) {
		unset($hq_registered_widget_controls[$id]);
		unset($hq_registered_widget_updates[$id_base]);
		return;
	}

	if ( in_array($control_callback, $_hq_deprecated_widgets_callbacks, true) && !is_callable($control_callback) ) {
		unset( $hq_registered_widgets[ $id ] );
		return;
	}

	if ( isset($hq_registered_widget_controls[$id]) && !did_action( 'widgets_init' ) )
		return;

	$defaults = array('width' => 250, 'height' => 200 ); // height is never used
	$options = hq_parse_args($options, $defaults);
	$options['width'] = (int) $options['width'];
	$options['height'] = (int) $options['height'];

	$widget = array(
		'name' => $name,
		'id' => $id,
		'callback' => $control_callback,
		'params' => array_slice(func_get_args(), 4)
	);
	$widget = array_merge($widget, $options);

	$hq_registered_widget_controls[$id] = $widget;

	if ( isset($hq_registered_widget_updates[$id_base]) )
		return;

	if ( isset($widget['params'][0]['number']) )
		$widget['params'][0]['number'] = -1;

	unset($widget['width'], $widget['height'], $widget['name'], $widget['id']);
	$hq_registered_widget_updates[$id_base] = $widget;
}

/**
 * @global array $hq_registered_widget_updates
 *
 * @param string   $id_base
 * @param callable $update_callback
 * @param array    $options
 */
function _register_widget_update_callback($id_base, $update_callback, $options = array()) {
	global $hq_registered_widget_updates;

	if ( isset($hq_registered_widget_updates[$id_base]) ) {
		if ( empty($update_callback) )
			unset($hq_registered_widget_updates[$id_base]);
		return;
	}

	$widget = array(
		'callback' => $update_callback,
		'params' => array_slice(func_get_args(), 3)
	);

	$widget = array_merge($widget, $options);
	$hq_registered_widget_updates[$id_base] = $widget;
}

/**
 *
 * @global array $hq_registered_widget_controls
 *
 * @param int|string $id
 * @param string     $name
 * @param callable   $form_callback
 * @param array      $options
 */
function _register_widget_form_callback($id, $name, $form_callback, $options = array()) {
	global $hq_registered_widget_controls;

	$id = strtolower($id);

	if ( empty($form_callback) ) {
		unset($hq_registered_widget_controls[$id]);
		return;
	}

	if ( isset($hq_registered_widget_controls[$id]) && !did_action( 'widgets_init' ) )
		return;

	$defaults = array('width' => 250, 'height' => 200 );
	$options = hq_parse_args($options, $defaults);
	$options['width'] = (int) $options['width'];
	$options['height'] = (int) $options['height'];

	$widget = array(
		'name' => $name,
		'id' => $id,
		'callback' => $form_callback,
		'params' => array_slice(func_get_args(), 4)
	);
	$widget = array_merge($widget, $options);

	$hq_registered_widget_controls[$id] = $widget;
}

/**
 * Remove control callback for widget.
 *
 * @since 0.0.1
 *
 * @param int|string $id Widget ID.
 */
function hq_unregister_widget_control($id) {
	hq_register_widget_control( $id, '', '' );
}

/**
 * Display dynamic sidebar.
 *
 * By default this displays the default sidebar or 'sidebar-1'. If your theme specifies the 'id' or
 * 'name' parameter for its registered sidebars you can pass an id or name as the $index parameter.
 * Otherwise, you can pass in a numerical index to display the sidebar at that index.
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_sidebars
 * @global array $hq_registered_widgets
 *
 * @param int|string $index Optional, default is 1. Index, name or ID of dynamic sidebar.
 * @return bool True, if widget sidebar was found and called. False if not found or not called.
 */
function dynamic_sidebar($index = 1) {
	global $hq_registered_sidebars, $hq_registered_widgets;

	if ( is_int($index) ) {
		$index = "sidebar-$index";
	} else {
		$index = sanitize_title($index);
		foreach ( (array) $hq_registered_sidebars as $key => $value ) {
			if ( sanitize_title($value['name']) == $index ) {
				$index = $key;
				break;
			}
		}
	}

	$sidebars_widgets = hq_get_sidebars_widgets();
	if ( empty( $hq_registered_sidebars[ $index ] ) || empty( $sidebars_widgets[ $index ] ) || ! is_array( $sidebars_widgets[ $index ] ) ) {
		/** This action is documented in hq-includes/widgets.php */
		do_action( 'dynamic_sidebar_before', $index, false );
		/** This action is documented in hq-includes/widgets.php */
		do_action( 'dynamic_sidebar_after',  $index, false );
		/** This filter is documented in hq-includes/widgets.php */
		return apply_filters( 'dynamic_sidebar_has_widgets', false, $index );
	}

	/**
	 * Fires before widgets are rendered in a dynamic sidebar.
	 *
	 * Note: The action also fires for empty sidebars, and on both the front-end
	 * and back-end, including the Inactive Widgets sidebar on the Widgets screen.
	 *
	 * @since 0.0.1
	 *
	 * @param int|string $index       Index, name, or ID of the dynamic sidebar.
	 * @param bool       $has_widgets Whether the sidebar is populated with widgets.
	 *                                Default true.
	 */
	do_action( 'dynamic_sidebar_before', $index, true );
	$sidebar = $hq_registered_sidebars[$index];

	$did_one = false;
	foreach ( (array) $sidebars_widgets[$index] as $id ) {

		if ( !isset($hq_registered_widgets[$id]) ) continue;

		$params = array_merge(
			array( array_merge( $sidebar, array('widget_id' => $id, 'widget_name' => $hq_registered_widgets[$id]['name']) ) ),
			(array) $hq_registered_widgets[$id]['params']
		);

		// Substitute HTML id and class attributes into before_widget
		$classname_ = '';
		foreach ( (array) $hq_registered_widgets[$id]['classname'] as $cn ) {
			if ( is_string($cn) )
				$classname_ .= '_' . $cn;
			elseif ( is_object($cn) )
				$classname_ .= '_' . get_class($cn);
		}
		$classname_ = ltrim($classname_, '_');
		$params[0]['before_widget'] = sprintf($params[0]['before_widget'], $id, $classname_);

		/**
		 * Filter the parameters passed to a widget's display callback.
		 *
		 * Note: The filter is evaluated on both the front-end and back-end,
		 * including for the Inactive Widgets sidebar on the Widgets screen.
		 *
		 * @since 0.0.1
		 *
		 * @see register_sidebar()
		 *
		 * @param array $params {
		 *     @type array $args  {
		 *         An array of widget display arguments.
		 *
		 *         @type string $name          Name of the sidebar the widget is assigned to.
		 *         @type string $id            ID of the sidebar the widget is assigned to.
		 *         @type string $description   The sidebar description.
		 *         @type string $class         CSS class applied to the sidebar container.
		 *         @type string $before_widget HTML markup to prepend to each widget in the sidebar.
		 *         @type string $after_widget  HTML markup to append to each widget in the sidebar.
		 *         @type string $before_title  HTML markup to prepend to the widget title when displayed.
		 *         @type string $after_title   HTML markup to append to the widget title when displayed.
		 *         @type string $widget_id     ID of the widget.
		 *         @type string $widget_name   Name of the widget.
		 *     }
		 *     @type array $widget_args {
		 *         An array of multi-widget arguments.
		 *
		 *         @type int $number Number increment used for multiples of the same widget.
		 *     }
		 * }
		 */
		$params = apply_filters( 'dynamic_sidebar_params', $params );

		$callback = $hq_registered_widgets[$id]['callback'];

		/**
		 * Fires before a widget's display callback is called.
		 *
		 * Note: The action fires on both the front-end and back-end, including
		 * for widgets in the Inactive Widgets sidebar on the Widgets screen.
		 *
		 * The action is not fired for empty sidebars.
		 *
		 * @since 0.0.1
		 *
		 * @param array $widget_id {
		 *     An associative array of widget arguments.
		 *
		 *     @type string $name                Name of the widget.
		 *     @type string $id                  Widget ID.
		 *     @type array|callback $callback    When the hook is fired on the front-end, $callback is an array
		 *                                       containing the widget object. Fired on the back-end, $callback
		 *                                       is 'hq_widget_control', see $_callback.
		 *     @type array          $params      An associative array of multi-widget arguments.
		 *     @type string         $classname   CSS class applied to the widget container.
		 *     @type string         $description The widget description.
		 *     @type array          $_callback   When the hook is fired on the back-end, $_callback is populated
		 *                                       with an array containing the widget object, see $callback.
		 * }
		 */
		do_action( 'dynamic_sidebar', $hq_registered_widgets[ $id ] );

		if ( is_callable($callback) ) {
			call_user_func_array($callback, $params);
			$did_one = true;
		}
	}

	/**
	 * Fires after widgets are rendered in a dynamic sidebar.
	 *
	 * Note: The action also fires for empty sidebars, and on both the front-end
	 * and back-end, including the Inactive Widgets sidebar on the Widgets screen.
	 *
	 * @since 0.0.1
	 *
	 * @param int|string $index       Index, name, or ID of the dynamic sidebar.
	 * @param bool       $has_widgets Whether the sidebar is populated with widgets.
	 *                                Default true.
	 */
	do_action( 'dynamic_sidebar_after', $index, true );

	/**
	 * Filter whether a sidebar has widgets.
	 *
	 * Note: The filter is also evaluated for empty sidebars, and on both the front-end
	 * and back-end, including the Inactive Widgets sidebar on the Widgets screen.
	 *
	 * @since 0.0.1
	 *
	 * @param bool       $did_one Whether at least one widget was rendered in the sidebar.
	 *                            Default false.
	 * @param int|string $index   Index, name, or ID of the dynamic sidebar.
	 */
	return apply_filters( 'dynamic_sidebar_has_widgets', $did_one, $index );
}

/**
 * Whether widget is displayed on the front-end.
 *
 * Either $callback or $id_base can be used
 * $id_base is the first argument when extending HQ_Widget class
 * Without the optional $widget_id parameter, returns the ID of the first sidebar
 * in which the first instance of the widget with the given callback or $id_base is found.
 * With the $widget_id parameter, returns the ID of the sidebar where
 * the widget with that callback/$id_base AND that ID is found.
 *
 * NOTE: $widget_id and $id_base are the same for single widgets. To be effective
 * this function has to run after widgets have initialized, at action 'init' or later.
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_widgets
 *
 * @param string $callback      Optional, Widget callback to check.
 * @param int    $widget_id     Optional, but needed for checking. Widget ID.
 * @param string $id_base       Optional, the base ID of a widget created by extending HQ_Widget.
 * @param bool   $skip_inactive Optional, whether to check in 'hq_inactive_widgets'.
 * @return string|false False if widget is not active or id of sidebar in which the widget is active.
 */
function is_active_widget($callback = false, $widget_id = false, $id_base = false, $skip_inactive = true) {
	global $hq_registered_widgets;

	$sidebars_widgets = hq_get_sidebars_widgets();

	if ( is_array($sidebars_widgets) ) {
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( $skip_inactive && ( 'hq_inactive_widgets' === $sidebar || 'orphaned_widgets' === substr( $sidebar, 0, 16 ) ) ) {
				continue;
			}

			if ( is_array($widgets) ) {
				foreach ( $widgets as $widget ) {
					if ( ( $callback && isset($hq_registered_widgets[$widget]['callback']) && $hq_registered_widgets[$widget]['callback'] == $callback ) || ( $id_base && _get_widget_id_base($widget) == $id_base ) ) {
						if ( !$widget_id || $widget_id == $hq_registered_widgets[$widget]['id'] )
							return $sidebar;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Whether the dynamic sidebar is enabled and used by theme.
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_widgets
 * @global array $hq_registered_sidebars
 *
 * @return bool True, if using widgets. False, if not using widgets.
 */
function is_dynamic_sidebar() {
	global $hq_registered_widgets, $hq_registered_sidebars;
	$sidebars_widgets = get_option('sidebars_widgets');
	foreach ( (array) $hq_registered_sidebars as $index => $sidebar ) {
		if ( count($sidebars_widgets[$index]) ) {
			foreach ( (array) $sidebars_widgets[$index] as $widget )
				if ( array_key_exists($widget, $hq_registered_widgets) )
					return true;
		}
	}
	return false;
}

/**
 * Whether a sidebar is in use.
 *
 * @since 0.0.1
 *
 * @param string|int $index Sidebar name, id or number to check.
 * @return bool true if the sidebar is in use, false otherwise.
 */
function is_active_sidebar( $index ) {
	$index = ( is_int($index) ) ? "sidebar-$index" : sanitize_title($index);
	$sidebars_widgets = hq_get_sidebars_widgets();
	$is_active_sidebar = ! empty( $sidebars_widgets[$index] );

	/**
	 * Filter whether a dynamic sidebar is considered "active".
	 *
	 * @since 0.0.1
	 *
	 * @param bool       $is_active_sidebar Whether or not the sidebar should be considered "active".
	 *                                      In other words, whether the sidebar contains any widgets.
	 * @param int|string $index             Index, name, or ID of the dynamic sidebar.
	 */
	return apply_filters( 'is_active_sidebar', $is_active_sidebar, $index );
}

/* Internal Functions */

/**
 * Retrieve full list of sidebars and their widget instance IDs.
 *
 * Will upgrade sidebar widget list, if needed. Will also save updated list, if
 * needed.
 *
 * @since 0.0.1
 * @access private
 *
 * @global array $_hq_sidebars_widgets
 * @global array $sidebars_widgets
 *
 * @param bool $deprecated Not used (argument deprecated).
 * @return array Upgraded list of widgets to version 3 array format when called from the admin.
 */
function hq_get_sidebars_widgets( $deprecated = true ) {
	if ( $deprecated !== true )
		_deprecated_argument( __FUNCTION__, '2.8.1' );

	global $_hq_sidebars_widgets, $sidebars_widgets;

	// If loading from front page, consult $_hq_sidebars_widgets rather than options
	// to see if hq_convert_widget_settings() has made manipulations in memory.
	if ( !is_admin() ) {
		if ( empty($_hq_sidebars_widgets) )
			$_hq_sidebars_widgets = get_option('sidebars_widgets', array());

		$sidebars_widgets = $_hq_sidebars_widgets;
	} else {
		$sidebars_widgets = get_option('sidebars_widgets', array());
	}

	if ( is_array( $sidebars_widgets ) && isset($sidebars_widgets['array_version']) )
		unset($sidebars_widgets['array_version']);

	/**
	 * Filter the list of sidebars and their widgets.
	 *
	 * @since 0.0.1
	 *
	 * @param array $sidebars_widgets An associative array of sidebars and their widgets.
	 */
	return apply_filters( 'sidebars_widgets', $sidebars_widgets );
}

/**
 * Set the sidebar widget option to update sidebars.
 *
 * @since 0.0.1
 * @access private
 *
 * @param array $sidebars_widgets Sidebar widgets and their settings.
 */
function hq_set_sidebars_widgets( $sidebars_widgets ) {
	if ( !isset( $sidebars_widgets['array_version'] ) )
		$sidebars_widgets['array_version'] = 3;
	update_option( 'sidebars_widgets', $sidebars_widgets );
}

/**
 * Retrieve default registered sidebars list.
 *
 * @since 0.0.1
 * @access private
 *
 * @global array $hq_registered_sidebars
 *
 * @return array
 */
function hq_get_widget_defaults() {
	global $hq_registered_sidebars;

	$defaults = array();

	foreach ( (array) $hq_registered_sidebars as $index => $sidebar )
		$defaults[$index] = array();

	return $defaults;
}

/**
 * Convert the widget settings from single to multi-widget format.
 *
 * @since 0.0.1
 *
 * @global array $_hq_sidebars_widgets
 *
 * @param string $base_name
 * @param string $option_name
 * @param array  $settings
 * @return array
 */
function hq_convert_widget_settings($base_name, $option_name, $settings) {
	// This test may need expanding.
	$single = $changed = false;
	if ( empty($settings) ) {
		$single = true;
	} else {
		foreach ( array_keys($settings) as $number ) {
			if ( 'number' == $number )
				continue;
			if ( !is_numeric($number) ) {
				$single = true;
				break;
			}
		}
	}

	if ( $single ) {
		$settings = array( 2 => $settings );

		// If loading from the front page, update sidebar in memory but don't save to options
		if ( is_admin() ) {
			$sidebars_widgets = get_option('sidebars_widgets');
		} else {
			if ( empty($GLOBALS['_hq_sidebars_widgets']) )
				$GLOBALS['_hq_sidebars_widgets'] = get_option('sidebars_widgets', array());
			$sidebars_widgets = &$GLOBALS['_hq_sidebars_widgets'];
		}

		foreach ( (array) $sidebars_widgets as $index => $sidebar ) {
			if ( is_array($sidebar) ) {
				foreach ( $sidebar as $i => $name ) {
					if ( $base_name == $name ) {
						$sidebars_widgets[$index][$i] = "$name-2";
						$changed = true;
						break 2;
					}
				}
			}
		}

		if ( is_admin() && $changed )
			update_option('sidebars_widgets', $sidebars_widgets);
	}

	$settings['_multiwidget'] = 1;
	if ( is_admin() )
		update_option( $option_name, $settings );

	return $settings;
}

/**
 * Output an arbitrary widget as a template tag.
 *
 * @since 0.0.1
 *
 * @global HQ_Widget_Factory $hq_widget_factory
 *
 * @param string $widget   The widget's PHP class name (see default-widgets.php).
 * @param array  $instance Optional. The widget's instance settings. Default empty array.
 * @param array  $args {
 *     Optional. Array of arguments to configure the display of the widget.
 *
 *     @type string $before_widget HTML content that will be prepended to the widget's HTML output.
 *                                 Default `<div class="widget %s">`, where `%s` is the widget's class name.
 *     @type string $after_widget  HTML content that will be appended to the widget's HTML output.
 *                                 Default `</div>`.
 *     @type string $before_title  HTML content that will be prepended to the widget's title when displayed.
 *                                 Default `<h2 class="widgettitle">`.
 *     @type string $after_title   HTML content that will be appended to the widget's title when displayed.
 *                                 Default `</h2>`.
 * }
 */
function the_widget( $widget, $instance = array(), $args = array() ) {
	global $hq_widget_factory;

	$widget_obj = $hq_widget_factory->widgets[$widget];
	if ( ! ( $widget_obj instanceof HQ_Widget ) ) {
		return;
	}

	$before_widget = sprintf('<div class="widget %s">', $widget_obj->widget_options['classname'] );
	$default_args = array( 'before_widget' => $before_widget, 'after_widget' => "</div>", 'before_title' => '<h2 class="widgettitle">', 'after_title' => '</h2>' );

	$args = hq_parse_args($args, $default_args);
	$instance = hq_parse_args($instance);

	/**
	 * Fires before rendering the requested widget.
	 *
	 * @since 0.0.1
	 *
	 * @param string $widget   The widget's class name.
	 * @param array  $instance The current widget instance's settings.
	 * @param array  $args     An array of the widget's sidebar arguments.
	 */
	do_action( 'the_widget', $widget, $instance, $args );

	$widget_obj->_set(-1);
	$widget_obj->widget($args, $instance);
}

/**
 * Private
 *
 * @return string
 */
function _get_widget_id_base($id) {
	return preg_replace( '/-[0-9]+$/', '', $id );
}

/**
 * Handle sidebars config after theme change
 *
 * @access private
 * @since 0.0.1
 *
 * @global array $sidebars_widgets
 */
function _hq_sidebars_changed() {
	global $sidebars_widgets;

	if ( ! is_array( $sidebars_widgets ) )
		$sidebars_widgets = hq_get_sidebars_widgets();

	retrieve_widgets(true);
}

/**
 * Look for "lost" widgets, this has to run at least on each theme change.
 *
 * @since 0.0.1
 *
 * @global array $hq_registered_sidebars
 * @global array $sidebars_widgets
 * @global array $hq_registered_widgets
 *
 * @param string|bool $theme_changed Whether the theme was changed as a boolean. A value
 *                                   of 'customize' defers updates for the Customizer.
 * @return array|void
 */
function retrieve_widgets( $theme_changed = false ) {
	global $hq_registered_sidebars, $sidebars_widgets, $hq_registered_widgets;

	$registered_sidebar_keys = array_keys( $hq_registered_sidebars );
	$orphaned = 0;

	$old_sidebars_widgets = get_theme_mod( 'sidebars_widgets' );
	if ( is_array( $old_sidebars_widgets ) ) {
		// time() that sidebars were stored is in $old_sidebars_widgets['time']
		$_sidebars_widgets = $old_sidebars_widgets['data'];

		if ( 'customize' !== $theme_changed ) {
			remove_theme_mod( 'sidebars_widgets' );
		}

		foreach ( $_sidebars_widgets as $sidebar => $widgets ) {
			if ( 'hq_inactive_widgets' === $sidebar || 'orphaned_widgets' === substr( $sidebar, 0, 16 ) ) {
				continue;
			}

			if ( !in_array( $sidebar, $registered_sidebar_keys ) ) {
				$_sidebars_widgets['orphaned_widgets_' . ++$orphaned] = $widgets;
				unset( $_sidebars_widgets[$sidebar] );
			}
		}
	} else {
		if ( empty( $sidebars_widgets ) )
			return;

		unset( $sidebars_widgets['array_version'] );

		$old = array_keys($sidebars_widgets);
		sort($old);
		sort($registered_sidebar_keys);

		if ( $old == $registered_sidebar_keys )
			return;

		$_sidebars_widgets = array(
			'hq_inactive_widgets' => !empty( $sidebars_widgets['hq_inactive_widgets'] ) ? $sidebars_widgets['hq_inactive_widgets'] : array()
		);

		unset( $sidebars_widgets['hq_inactive_widgets'] );

		foreach ( $hq_registered_sidebars as $id => $settings ) {
			if ( $theme_changed ) {
				$_sidebars_widgets[$id] = array_shift( $sidebars_widgets );
			} else {
				// no theme change, grab only sidebars that are currently registered
				if ( isset( $sidebars_widgets[$id] ) ) {
					$_sidebars_widgets[$id] = $sidebars_widgets[$id];
					unset( $sidebars_widgets[$id] );
				}
			}
		}

		foreach ( $sidebars_widgets as $val ) {
			if ( is_array($val) && ! empty( $val ) )
				$_sidebars_widgets['orphaned_widgets_' . ++$orphaned] = $val;
		}
	}

	// discard invalid, theme-specific widgets from sidebars
	$shown_widgets = array();

	foreach ( $_sidebars_widgets as $sidebar => $widgets ) {
		if ( !is_array($widgets) )
			continue;

		$_widgets = array();
		foreach ( $widgets as $widget ) {
			if ( isset($hq_registered_widgets[$widget]) )
				$_widgets[] = $widget;
		}

		$_sidebars_widgets[$sidebar] = $_widgets;
		$shown_widgets = array_merge($shown_widgets, $_widgets);
	}

	$sidebars_widgets = $_sidebars_widgets;
	unset($_sidebars_widgets, $_widgets);

	// find hidden/lost multi-widget instances
	$lost_widgets = array();
	foreach ( $hq_registered_widgets as $key => $val ) {
		if ( in_array($key, $shown_widgets, true) )
			continue;

		$number = preg_replace('/.+?-([0-9]+)$/', '$1', $key);

		if ( 2 > (int) $number )
			continue;

		$lost_widgets[] = $key;
	}

	$sidebars_widgets['hq_inactive_widgets'] = array_merge($lost_widgets, (array) $sidebars_widgets['hq_inactive_widgets']);
	if ( 'customize' !== $theme_changed ) {
		hq_set_sidebars_widgets( $sidebars_widgets );
	}

	return $sidebars_widgets;
}
