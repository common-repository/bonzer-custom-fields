<?php
namespace BCF;

/**
 * Options Factory
 * 
 * @package  bonzer-custom-fields
 */
use Bonzer\Exceptions\Base_Exception,
    Bonzer\Events\Event;
use Bonzer\Events\contracts\interfaces\Event as Event_Interface;

class Options_Factory {

  /**
   * @var Options_Factory
   */
  private static $_instance;  

  /**
   * @var string
   */
  private $_config_hash;

  /**
   * @var string
   */
  private $_config;

  /**
   * @var string
   */
  private $_env;

  /**
   * @var array
   */
  protected  $_inputs_assets;

  /**
   * @var string
   */
  protected  $_plugin_url;

  /**
   * @var array
   */
  protected $_jquery_ui_handles = array(
    "jquery-ui-core",
    "jquery-ui-widget",
    "jquery-ui-mouse",
    "jquery-ui-accordion",
    "jquery-ui-autocomplete",
    "jquery-ui-slider",
    "jquery-ui-tabs",
    "jquery-ui-sortable",
    "jquery-ui-draggable",
    "jquery-ui-droppable",
    "jquery-ui-selectable",
    "jquery-ui-position",
    "jquery-ui-datepicker",
    "jquery-ui-resizable",
    "jquery-ui-dialog",
    "jquery-ui-button",
  );

  /**
   * @var array
   */
  protected $_builders = array(
    'custom_fields'    => '\\BCF\\builders\\Custom_Fields_Builder',
    'taxonomy_fields'  => '\\BCF\\builders\\Taxonomy_Fields_Builder',
    'setting_fields'   => '\\BCF\\builders\\Settings_Fields_Builder',
    'profile_fields'   => '\\BCF\\builders\\Profile_Fields_Builder',
    'dashboard_fields' => '\\BCF\\builders\\Dashboard_Fields_Builder',
  );

  /**
   * Event_Interface
   *
   * @var string
   */
  protected $_Event;

  /**
   * --------------------------------------------------------------------------
   * Class Constructor
   * --------------------------------------------------------------------------
   * 
   * @return Options_Factory 
   * */
  protected function __construct( Event_Interface $event = NULL ) {

    $this->_plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
    $this->_Event       = $event ?: Event::get_instance();
    $this->_config_hash = BCF__CONFIG__HASH;
    $this->_config      = $this->_load_config();

    $this->_init_env();
    $this->_init_inputs_config();

    // Custom Fields
    $this->_build_custom_fields();
    $this->_register_fields_for_taxonomies();
    $this->_register_fields_for_pages();    
  }

  /**
   * --------------------------------------------------------------------------
   * Class Constructor
   * --------------------------------------------------------------------------
   * 
   * @return Options_Factory 
   * */
  public static function get_instance( Event_Interface $event = NULL ) {

    if ( static::$_instance ) {
      return static::$_instance;
    }

    return static::$_instance = new static( $event );
  }

  /**
   * --------------------------------------------------------------------------
   * Initializes the environment as: development | production
   * --------------------------------------------------------------------------
   * 
   * @return void 
   * */
  protected function _init_env() {

    if ( bcf_is_dev() ) {

      $this->_env = 'development';

    } else {

      $this->_env = 'production';

    }
  }

  /**
   * --------------------------------------------------------------------------
   * Initializes fields configuration
   * --------------------------------------------------------------------------
   * 
   * @return void 
   * */
  protected function _init_inputs_config() {

    $this->_inputs_assets = array(

      'js' => array(
        'jquery-ui'             => $this->_plugin_url.'assets/dist/inputs/js/jquery-ui.min.js', // only tooltip
        'chosen'                => $this->_plugin_url.'assets/dist/inputs/js/chosen.jquery.min.js',
        'spectrum'              => $this->_plugin_url.'assets/dist/inputs/js/spectrum.js',
        'jquery.ui.touch-punch' => $this->_plugin_url.'assets/dist/inputs/js/jquery.ui.touch-punch.min.js'
      ),

      'css' => array(
        'jquery-ui'       => $this->_plugin_url.'assets/dist/inputs/css/jquery-ui.min.css',
        'chosen'          => $this->_plugin_url.'assets/dist/inputs/css/chosen.min.css',
        'spectrum'        => $this->_plugin_url.'assets/dist/inputs/css/spectrum.css',
        'jquery-ui-theme' => $this->_plugin_url.'assets/dist/inputs/css/jquery-ui.theme.min.css' // only tooltip
      ),
    );

    \Bonzer\Inputs\config\Configurer::get_instance( array(
      'load_assets_automatically' => false, // recommended option is false. Default is true so library doesn’t break if you don’t configure
      'css_excluded'              => [], // keys for css files you don’t want the library to load. You should be responsible for loading them. 
      'js_excluded'               => ['jquery'],  // keys for js files you don’t want the library to load. 
      'env'                       => $this->_env, // development | production
      'is_admin'                  => true, // a flag that tell when the fields are opened in ADMIN mode, helpful for Exception handling
      'style'                     => $this->_get_style(), // 1,2,3
    ) );

    $Assets_Loader = \Bonzer\Inputs_WP\Assets_Loader::get_instance();

    $this->_Event->listen('inputs_js_start', function () {

      ?>
      bonzer_inputs.base_url = '<?php echo home_url(); ?>';
      <?php

    } );

    add_action('admin_head', function (){   

      foreach ( $this->_jquery_ui_handles as $handle ) {
        wp_enqueue_script( $handle );
      }     

      $Assets_Loader = \Bonzer\Inputs_WP\Assets_Loader::get_instance();

      $Assets_Loader->load_head_fragment( $this->_inputs_assets['css'] ); 

    }, 9999);

    add_action('admin_footer', function (){

      $Assets_Loader = \Bonzer\Inputs_WP\Assets_Loader::get_instance();

      $Assets_Loader->load_before_body_close_fragment( $this->_inputs_assets['js'] );

    }, 9999);
  }

  protected function _get_style(){

    if ( isset( $this->_config['pluginSettings'] ) && isset( $this->_config['pluginSettings']['inputsStyle'] ) ) {

      return $this->_config['pluginSettings']['inputsStyle'];

    }

    return '1';
  }

  protected function _load_config() {

    $config = get_option( $this->_config_hash );

    return json_decode( $config, TRUE );
  }

  protected function _build_custom_fields() {

    $this->_register_meta_boxes_for_custom_post_types();
    $this->_register_fields_for_post_types();
  }

  protected function _register_meta_boxes_for_custom_post_types(){

    $builder               = $this->_builders['custom_fields'];
    $BUILDER               = $builder::get_instance();
    $meta_boxes            = $this->get_metaboxes();
    $metaboxes_for_screens = $this->_get_meta_boxes_for_all_screens();

    foreach ( $metaboxes_for_screens as $screen => $metaboxes_for_screen ) {    

      $metabox_for_screen = array();

      foreach ( $metaboxes_for_screen as $meta_box_key ) {

        $args = array_merge( [
                  'screen' => $screen 
                ], $meta_boxes[ $meta_box_key ] );
        
        try {

          $BUILDER->register_meta_box( "{$meta_box_key}_{$screen}", $args);

        } catch ( Base_Exception $e ) {

          echo $e->get_Message();

        }  

      }
    }    
  }

  protected function _register_fields_for_post_types() {

    $fields               = $this->get_registered_fields(); 
    $builder              = $this->_builders['custom_fields'];
    $BUILDER              = $builder::get_instance();
    $registered_metaboxes = $this->get_metaboxes();

    foreach ( $fields as $field ) {

      $post_types = $field['applicable_for']['post_types'];

      if ( !empty( $post_types ) ) {

        foreach ( $post_types as $screen ) {

          $meta_box_key = $field['meta_box'] && array_key_exists( $field['meta_box'], $registered_metaboxes ) ? "{$field['meta_box']}_{$screen}" 
                                                                                                              : "bcf_meta_box_general_{$screen}";
          try {

            $BUILDER->regsiter_custom_field( $field['args'], $screen, $meta_box_key );

          } catch ( Base_Exception $e ) {

            echo $e->get_Message();

          } 

        }
      }
    }
  }

  protected function _register_fields_for_taxonomies() {

    $fields     = $this->get_registered_fields(); 
    $builder    = $this->_builders['taxonomy_fields'];
    $meta_boxes = $this->get_metaboxes();
    $BUILDER    = $builder::get_instance();

    foreach ( $fields as $field ) {

      $taxonomies  = $field['applicable_for']['taxonomies'];
      $section_key = $field['meta_box'] ? $field['meta_box'] : NULL;
      $section     = $section_key ? $meta_boxes[ $section_key ] : NULL;

      if ( !empty( $taxonomies ) ) {

        foreach ( $taxonomies as $taxonomy ) {    

          try {

            $BUILDER->regsiter_custom_field( $field['args'], $taxonomy, $section );

          } catch ( Base_Exception $e ) {

            echo $e->get_Message();
          } 

        }
      }
    }

    $registered_fields = $BUILDER->get_fields();

    foreach ( $registered_fields as $taxonomy => $fields ) {

      $BUILDER->create_fields($taxonomy);

    }    
  }

  protected function _register_fields_for_pages() {

    $fields     = $this->get_registered_fields();     
    $meta_boxes = $this->get_metaboxes();

    foreach ( $fields as $field ) {

      $pages        = $field['applicable_for']['pages'];
      $section_key  = isset($field['meta_box']) ? $field['meta_box'] : NULL;
      $section      = $section_key ? $meta_boxes[$section_key] : NULL;

      if ( !empty( $pages ) ) {

        foreach ( $pages as $page ) {    

          try {

            switch ( $page ) {
              case 'profile.php':
                $builder = $this->_builders['profile_fields'];
                break;

              case 'index.php':
                $builder = $this->_builders['dashboard_fields'];
                break;

              default:
                $builder = $this->_builders['setting_fields'];
                break;
            }

            $BUILDER = $builder::get_instance();
            $BUILDER->regsiter_custom_field( $field['args'], $page, $section );

          } catch ( Base_Exception $e ) {

            echo $e->get_Message();

          } 

        }
      }
    }
  }

  protected function _get_meta_boxes_for_all_screens() {

    $metaboxes  = array();
    $meta_boxes = $this->get_metaboxes();
    $fields     = $this->get_registered_fields(); 

    foreach ( $fields as $field ) {

      foreach ( $field['applicable_for']['post_types'] as $post_type ) {

        $metaboxes[ $post_type ][] = $field['meta_box'] && array_key_exists( $field['meta_box'], $meta_boxes ) 
                                     ? $field['meta_box'] 
                                     : 'bcf_meta_box_general';

      }

    }

    return $metaboxes;
  }

  /**
   * --------------------------------------------------------------------------
   * Gets all the created fields data
   * --------------------------------------------------------------------------
   * 
   * @return array 
   * */
  public function get_registered_fields(){

    $fields   = array();
    $defaults = array(
      'name'        => '',
      'id'          => '',
      'label'       => '',
      'placeholder' => '',
      'value'       => '',
      'desc'        => '',
      'options'     => array(),
      'attrs'       => '',
    );

    if ( isset( $this->_config['fields'] ) && isset( $this->_config['fields']['fieldBakers'] ) && is_array( $this->_config['fields']['fieldBakers'] ) ) {
      
      foreach ( $this->_config['fields']['fieldBakers'] as $fieldBaker ) {

        $fieldCreator       = $fieldBaker['fieldCreator'];
        $postTypesSelected  = array();
        $metaBoxSelected    = null;
        $taxonomiesSelected = array();
        $pagesSelected      = array();
        $raw_options        = isset( $fieldCreator['options'] ) ? $fieldCreator['options'] : '';
        $optionsFor         = isset( $fieldCreator['optionsFor'] ) ? $fieldCreator['optionsFor'] : 'user';

        if ( $optionsFor == 'developer' ) {
          $raw_options = $fieldCreator['optionsDeveloper']['code'];
        }

        if ( isset( $fieldBaker['selectBoxes'] ) ) {

          $postTypesSelected  = isset( $fieldBaker['selectBoxes']['postTypesSelected'] ) ? $fieldBaker['selectBoxes']['postTypesSelected'] : [];
          $metaBoxSelected    = isset( $fieldBaker['metaBoxSelected'] ) ? $fieldBaker['metaBoxSelected'] : null;
          $taxonomiesSelected = isset( $fieldBaker['selectBoxes']['taxonomiesSelected'] ) ? $fieldBaker['selectBoxes']['taxonomiesSelected'] : [];

        }

        if ( isset( $fieldBaker['pageSelector'] ) ) {
          $pagesSelected = isset( $fieldBaker['pageSelector']['pagesSelected'] ) ? $fieldBaker['pageSelector']['pagesSelected'] : [];
        }

        $fields[] = array(
          'args' => array(
            'type'        => $fieldCreator['type'],
            'label'       => $fieldCreator['name'],
            'id'          => BCF__PREFIX."{$fieldCreator['fieldKey']}",
            'name'        => BCF__PREFIX."{$fieldCreator['fieldKey']}",
            'desc'        => isset( $fieldCreator['desc'] ) ? $fieldCreator['desc'] : $defaults['desc'],
            'options'     => $this->_build_options( $optionsFor, $raw_options, $fieldCreator['fieldKey'] ),
            'placeholder' => isset( $fieldCreator['placeholder'] ) ? $fieldCreator['placeholder'] : $defaults['placeholder'],
            'value'       => isset( $fieldCreator['defaultValue'] ) ? $fieldCreator['defaultValue'] : $defaults['value'],
          ),
          'applicable_for' => array(
            'post_types' => $postTypesSelected,
            'taxonomies' => $taxonomiesSelected,
            'pages'      => $pagesSelected,
          ),
          'meta_box' => $metaBoxSelected
        );

      }
    }

    return $fields;
  }

  /**
   * --------------------------------------------------------------------------
   * Gets all the created metaboxes data
   * --------------------------------------------------------------------------
   * 
   * @return array 
   * */
  public function get_metaboxes() {

    $metaboxes = array(
      'bcf_meta_box_general' => array(
        'label'      => __('BCF General', 'bcf'),
        'position'   => 'normal',
        'priority'   => 'default',
        'metaBoxKey' => 'bcf_meta_box_general'
      )
    );

    if ( isset( $this->_config['metaBoxes'] ) && is_array( $this->_config['metaBoxes'] ) ) {

      foreach ( $this->_config['metaBoxes'] as $metabox ) {

        unset( $metabox['index'], $metabox['isOpen'] );

        $metaboxes[ $metabox['metaBoxKey'] ] = array(
          'label'      => $metabox['title'],
          'position'   => $metabox['position'],
          'priority'   => $metabox['priority'],
          'metaBoxKey' => $metabox['metaBoxKey']
        );

      }
    }

    return $metaboxes;    
  }

  /**
   * @param string $options_for
   * @param string $raw_options
   * @param string $fieldKey
   *
   * @return array 
   * */
  protected function _build_options( $options_for, $raw_options, $fieldKey ) {

    $options = array();

    switch ( $options_for ) {

      case 'user':
        $options = $this->_build_options_for_user($raw_options);
        break;

      case 'developer':
        $options = $this->_build_options_for_developer($fieldKey);
        break;     

      default:
        $options = $this->_build_options_for_user($raw_options);
        break;
    }

    return $options;
  }

  /**
   * @param string $raw_options
   *
   * @return array 
   * */
  protected function _build_options_for_user( $raw_options ){

    $options       = array();
    $options_array = explode("\n", $raw_options );

    foreach ( $options_array as $option ) {

      if ( preg_match('/---/', $option) ) {

        list( $key, $value ) = explode('---', $option );

        $options[ trim( $key ) ] = trim( $value );

      } else {

        $options[ $option ] = $option;

      }

    }

    return $options;
  }

  /**
   * @param string $fieldKey
   *
   * @return array 
   * */
  protected function _build_options_for_developer( $fieldKey ){

    $file = __DIR__ . "/codes/{$fieldKey}.php";

    if ( file_exists( $file ) && is_file( $file ) ) {

      $raw_options = file_get_contents( $file );

    } else {

      return array(
        'error' => "File {$file} does not exists!"
      );

    }

    $output = shell_exec('php -l '.$file);

    if ( preg_match('/Errors parsing/i', $output) ) {

      $output_error = str_replace( [$file, __('Parse error: syntax error,', 'bcf'), __('Errors parsing', 'bcf') ], '', $output);     

      return [
        'error' => sprintf(__("php code for %s is not valid! %s", 'bcf'), $fieldKey, $output_error)
      ];

    }
    
    return call_user_func( function ( $raw_options, $fieldKey ) {   

        $options      = str_replace('<?php', '',$raw_options );
        $eval_options = eval($options);

        if ( ! is_array( $eval_options ) ) {

          return array(
            'error' => sprintf( __('php code for %s is not valid! it should return an associative array', 'bcf'), $fieldKey) 
          );

        }  

        return $eval_options;
      }, 
      $raw_options, 
      $fieldKey 
    );
  }
}