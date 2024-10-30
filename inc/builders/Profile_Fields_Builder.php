<?php
namespace BCF\builders;
/**
 * 
 * Profile_Fields_Builder
 * 
 * @package  bonzer-custom-fields
 */
use BCF\contracts\interfaces\Builder as Builder_Contract,
    Bonzer\Inputs_WP\contracts\interfaces\Inputs_Factory as Inputs_Factory_Contract;

use Bonzer\Exceptions\Invalid_Param_Exception,
    Bonzer\Inputs_WP\factories\Input as Inputs_Factory;

class Profile_Fields_Builder implements Builder_Contract{

  /**
   *
   * @var Inputs_Factory_Contract
   */
  protected $_Input_Factory;

  /**
   * Class Instance
   *
   * @var Profile_Fields_Builder
   */
  private static $_instance;

  /**
   * All Taxonomy Fields
   *
   * @var array
   */
  protected $_fields = [];
  
  /**
   * --------------------------------------------------------------------------
   * Class Constructor
   * --------------------------------------------------------------------------
   * 
   * @Return Profile_Fields_Builder 
   * */
  protected function __construct( Inputs_Factory_Contract $input_factory = NULL ){

    $this->_Input_Factory = $input_factory ?: Inputs_Factory::get_instance();
    
    add_action('show_user_profile',       array( $this, 'create_options' ), 999);
    add_action('personal_options_update', array( $this, 'save_options'), 999);
  }

  /**
   * --------------------------------------------------------------------------
   * Singleton
   * --------------------------------------------------------------------------
   * 
   * @Return Profile_Fields_Builder 
   * */
  public static function get_instance( Inputs_Factory_Contract $input_factory = NULL ) {

    if ( is_null( static::$_instance ) ) {

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

    $section = $section ? $section['label'] : __('BCF General', 'bcf');
    $this->_fields[ $section ][] = $args;   
  }

  public function get_fields() {

    return $this->_fields;
  }

  public function create_options() {

    $user_id = get_current_user_id();

    foreach ( $this->_fields as $section => $fields ) {
      ?>

        <div class="profile-meta-box">

          <h2><?php echo $section; ?></h2>

          <?php
            foreach ( $fields as $field ) {

             $meta           =  get_user_meta( $user_id, $field['name'], true );
             $field['value'] = $meta;

             echo $this->_Input_Factory->create( $field[ 'type' ], $field );

            }
          ?>
        </div>

      <?php      
    }
  }

  public function save_options( $user_id ){  

    if ( $_POST ) {

      foreach ( $this->_fields as $section => $fields ) {

        foreach ( $fields as $field ) {

          $value = trim( wp_kses_post( $_POST[ $field['name'] ] ) );

          update_user_meta( $user_id, $field['name'], $value );

        }      

      }

    }
  }
}