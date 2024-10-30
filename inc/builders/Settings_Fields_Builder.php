<?php
namespace BCF\builders;
/**
 * 
 * Settings_Fields_Builder
 * 
 * @package  bonzer-custom-fields
 */
use BCF\contracts\interfaces\Builder as Builder_Contract,
    Bonzer\Inputs_WP\contracts\interfaces\Inputs_Factory as Inputs_Factory_Contract;
use Bonzer\Exceptions\Invalid_Param_Exception,
    Bonzer\Inputs_WP\factories\Input as Inputs_Factory;

class Settings_Fields_Builder implements Builder_Contract{

  /**
   * Class Instance
   *
   * @var Settings_Fields_Builder
   */
  private static $_instance;

  /**
   * All Taxonomy Fields
   *
   * @var array
   */
  protected $_fields = array();

  /**
   *
   * @var Inputs_Factory_Contract
   */
  protected $_Input_Factory;
  
  /**
   * --------------------------------------------------------------------------
   * Class Constructor
   * --------------------------------------------------------------------------
   * 
   * @Return Settings_Fields_Builder 
   * */
  protected function __construct( Inputs_Factory_Contract $input_factory = NULL ){

    $this->_Input_Factory = $input_factory ?: Inputs_Factory::get_instance();
    
    add_action('admin_init', array( $this, 'create_options' ) );
  }

  /**
   * --------------------------------------------------------------------------
   * Singleton
   * --------------------------------------------------------------------------
   * 
   * @Return Settings_Fields_Builder 
   * */
  public static function get_instance( Inputs_Factory_Contract $input_factory = NULL ) {

    if ( ! static::$_instance ) {
      return static::$_instance = new static( $input_factory );
    }

    return static::$_instance;
  }

  /**
   * --------------------------------------------------------------------------
   * Regsiters the custom field
   * --------------------------------------------------------------------------
   *
   * @param array $args
   * @param string $taxonomy
   *
   *
   * Valid $args keys
   *
   *  $args = array(
   *    'type'    => 'text',  // text , textarea , checkbox , radio , select , color , icon , multi-select , multi-upload , upload , editor
   *    'id'      => '',
   *    'label'   => '',
   *    'value'   => '',
   *    'desc'    => '',
   *    'options' => array(),
   *  );
   *
   * @return: void
   * */
  public function regsiter_custom_field( $args, $page, $section ) {

    $this->_fields[ $page ][] = array( $args, $section );   

  }

  public function get_fields() {
    return $this->_fields;
  }

  public function create_options() {

    foreach ( $this->_fields as $page => $fields ) {

      if ( preg_match('/options-/', $page) ) {

        $settings_page = str_replace( array('options-', '.php'), '', $page);

        foreach ($fields as $field_section) {

          list( $field, $section ) = $field_section;

          if ( ! empty( $section ) ) {

            add_settings_section(
              $section['metaBoxKey'],
              $section['label'],
              function ( $args ){},
              $settings_page
            );

          }

          $field['name']  = "bcf_options-{$settings_page}_{$field['id']}";
          $field['value'] = get_option( $field['name'], $field['value'] );

          if ( isset( $field['desc'] ) && ! empty( $field['desc'] ) ) {
            $field['label'] = $field['label'] . '<p class="bcf-desc">'. $field['desc'] .'</p>';
          }          

          add_settings_field(
            $field['name'],
            $field['label'],
            function ( $args ){
              echo $this->_Input_Factory->create( $args['type'], $args );
            },
            $settings_page,
            $section ? $section['metaBoxKey'] : 'default',
            array_merge( $field, array('label_for' => $field['id'] ) )
          );

          register_setting( $settings_page, $field['name'] ); 

        }

      }

    }
  }
}