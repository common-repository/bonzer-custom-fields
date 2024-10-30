<?php
namespace BCF\builders;

/**
 * 
 * Custom_Fields_Builder
 * 
 * @package  bonzer-custom-fields
 */

use BCF\contracts\interfaces\Custom_Fields_Builder as Custom_Fields_Builder_Contract,
    Bonzer\Inputs_WP\contracts\interfaces\Inputs_Factory as Inputs_Factory_Contract;
    
use Bonzer\Exceptions\Invalid_Param_Exception,
    Bonzer\Inputs_WP\factories\Input as Inputs_Factory;

class Custom_Fields_Builder implements Custom_Fields_Builder_Contract {

  use \BCF\traits\Attr;

  /**
   * Class Instance
   *
   * @var Custom_Fields_Builder
   */
  private static $_instance;

  /**
   * Meta Boxes
   *
   * @var array
   */
  protected static $_meta_boxes;

  /**
   * Meta Boxes Screens
   *
   * @var array
   */
  protected static $_meta_boxes_screens;

  /**
   * Custom Fields
   *
   * @var array
   */
  protected static $_custom_fields;

  /**
   * Key Regex
   *
   * @var string
   */
  protected $_valid_key_regex = '/^[A-Za-z0-9_\-]+$/';

  /**
   * Label Regex
   *
   * @var string
   */
  protected $_valid_label_regex = '/^[A-Za-z0-9_\s]+$/';

  /**
   * Valid Meta Boxes Positions
   *
   * @var array
   */
  protected $_valid_positions = array(
    'normal',
    'advanced',
    'side' 
  );

  /**
   * Valid Priorities
   *
   * @var array
   */
  protected $_valid_priorities = array(
    'high',
    'low',
    'default',
    'core'
  );
  
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
   * @return Custom_Fields_Builder 
   * */
  private function __construct( Inputs_Factory_Contract $input_factory = NULL) {

    $this->_Input_Factory = $input_factory ?: Inputs_Factory::get_instance();

    add_action( 'add_meta_boxes', array( $this, 'create_meta_boxes' ) );
    add_action( 'save_post',      array( $this, 'save_meta' ), 11, 3 );
  }

  /**
   * --------------------------------------------------------------------------
   * Singleton
   * --------------------------------------------------------------------------
   * 
   * @return Custom_Fields_Builder 
   * */
  public static function get_instance( Inputs_Factory_Contract $input_factory = NULL ) {

    if ( is_null( self::$_instance ) ) {

      return self::$_instance = new self( $input_factory );

    }

    return self::$_instance;
  }

  /**
   * --------------------------------------------------------------------------
   * Registers Meta Box
   * --------------------------------------------------------------------------
   * 
   * @param string $key
   * @param array $args
   * 
   * @return void 
   * */
  public function register_meta_box( $key, $args ) {

    if ( ! preg_match( $this->_valid_key_regex, $key ) ) {

      /* translators: %1$s: The invalid key name */
      $key_error = sprintf( 
        esc_html__( '%1$s %2$s is invalid!', 'bcf' ), 
        '$key', 
        esc_html( $key ) 
      );

      throw new Invalid_Param_Exception( $key_error );
    }

    if ( ! preg_match( $this->_valid_key_regex, $args['screen'] ) ) {

      /* translators: %1$s: The screen name */
      $screen_error = sprintf( 
        esc_html__( '%1$s %2$s is invalid!', 'bcf' ), 
        '$screen', 
        esc_html( $args['screen'] ) 
      );

      throw new Invalid_Param_Exception( $screen_error );
    }

    if ( ! in_array( $args[ 'position' ], $this->_valid_positions ) ) {

      /* translators: %1$s: The position, %2$s: The valid positions list */
      $position_error = sprintf( 
        esc_html__( '%1$s %2$s is invalid!, it must be in %3$s', 'bcf' ), 
        '$position', 
        esc_html( $args['position'] ), 
        esc_html( implode( ', ', $this->_valid_positions ) ) 
      );

      throw new Invalid_Param_Exception( $position_error );
    }
    
    if ( ! in_array( $args[ 'priority' ], $this->_valid_priorities ) ) {

      /* translators: %1$s: The priority, %2$s: The valid priorities list */
      $priority_error = sprintf( 
        esc_html__( '%1$s %2$s is invalid!, it must be in %3$s', 'bcf' ), 
        '$priority', 
        esc_html( $args['priority'] ), 
        esc_html( implode( ', ', $this->_valid_priorities ) ) 
      );

      throw new Invalid_Param_Exception( $priority_error );
    }

    static::$_meta_boxes[ $key ] = $args;

  }

  /**
   * --------------------------------------------------------------------------
   * Regsiters the custom field
   * --------------------------------------------------------------------------
   *
   * @param array $args
   * @param string $screen
   * @param string $meta_box_key
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
  public function regsiter_custom_field( $args, $screen, $meta_box_key = "" ) {

    if ( ! $this->_meta_boxes_exists( $screen ) ) {

      /* translators: %1$s: The screen name, %2$s: The class name */
      $no_meta_box_error = sprintf( 
        esc_html__( 'No meta box is currently registered for screen "%1$s". Please register a meta box via %2$s\'s register_meta_box method.', 'bcf' ), 
        esc_html( $screen ), 
        esc_html( __CLASS__ ) 
      );

      throw new Invalid_Param_Exception( $no_meta_box_error );
    }

    if ( ! preg_match( $this->_valid_key_regex, $screen ) ) {

      /* translators: %1$s: The screen name */
      $screen_invalid_error = sprintf( 
        esc_html__( '%1$s %2$s is invalid!', 'bcf' ), 
        '$screen', 
        esc_html( $screen ) 
      );

      throw new Invalid_Param_Exception( $screen_invalid_error );
    }

    if ( ! isset( $args[ 'id' ] ) ) {

      /* translators: %1$s: The argument name */
      $args_id_required_error = sprintf( 
        esc_html__( '%1$s is required!', 'bcf' ), 
        '$args["id"]' 
      );

      throw new Invalid_Param_Exception( $args_id_required_error );
    }

    if ( ! empty( $meta_box_key ) ) {

      if ( ! $this->_meta_box_exists( $meta_box_key ) ) {

        /* translators: %1$s: The meta box key, %2$s: The list of registered meta boxes */
        $meta_box_key_invalid_error = sprintf( 
          esc_html__( '%1$s %2$s is invalid! It is not registered. Registered ones are %3$s.', 'bcf' ), 
          '$meta_box_key', 
          esc_html( $meta_box_key ), 
          esc_html( implode( ', ', array_keys( static::$_meta_boxes ) ) ) 
        );

        throw new Invalid_Param_Exception( $meta_box_key_invalid_error );
      }

    }

    $defaults = [
      'type' => 'text', // text , textarea , checkbox , radio , select , color , icon , multi-select , multi-upload , upload
    ];    

    if ( empty( $meta_box_key ) ) {

      $registered_meta_boxes = $this->_get_regsitered_meta_boxes( $screen );
      $meta_box_key          = $registered_meta_boxes[0];

    }

    $args_parsed = $this->parse_attrs( $defaults, $args );

    static::$_custom_fields[ $screen ]['args'][]                             = $args_parsed;
    static::$_custom_fields[ $screen ][ $args_parsed['id'] ]['meta_box_key'] = $meta_box_key;
  }

  /**
   * @param string $screen
   * 
   * @return bool 
   * */
  protected function _meta_boxes_exists( $screen ) {

    $regsitered_meta_boxes = $this->_get_regsitered_meta_boxes( $screen );

    return count( $regsitered_meta_boxes ) > 0;
  }

  /**
   * @param string $meta_box_key
   * 
   * @Return bool 
   * */
  protected function _meta_box_exists( $meta_box_key ) {

    return is_array( static::$_meta_boxes ) && in_array( $meta_box_key, array_keys( static::$_meta_boxes ) );
  }

  /**
   * --------------------------------------------------------------------------
   * Registered Meta Boxes
   * --------------------------------------------------------------------------
   * 
   * @param string $screen
   * 
   * @return array 
   * */
  protected function _get_regsitered_meta_boxes( $screen ) {

    $registerd_meta_boxes = array();

    if ( is_array( static::$_meta_boxes ) ) {

      foreach ( static::$_meta_boxes as $meta_box_key => $args ) {

        if ( $args['screen'] == $screen ) {
          $registerd_meta_boxes[] = $meta_box_key;
        }

      }

    }
    
    return $registerd_meta_boxes;
  }

  /**
   * --------------------------------------------------------------------------
   * Hook Meta Boxes on Screen
   * --------------------------------------------------------------------------
   * 
   * @Return void 
   * */
  public function create_meta_boxes() {

    if ( ! is_array( static::$_meta_boxes ) ) {
      return;
    }

    foreach ( static::$_meta_boxes as $key => $args ) {

      static::$_meta_boxes_screens[] = $args[ 'screen' ];

      add_meta_box( 
        $key, 
        $args['label'], 
        array( $this, 'create_custom_fields'), 
        $args['screen'], 
        $args['position'], 
        $args['priority'], 
        array(
          'meta_box_key' => $key, 
          'screen'       => $args[ 'screen' ] 
        ) 
      );

    }
  }

  /**
   * --------------------------------------------------------------------------
   * Fill Registered Meta Boxes with Respective Custom Fields
   * --------------------------------------------------------------------------
   * 
   * @param object $post
   * @param array $metabox_args
   * 
   * @return void 
   * */
  public function create_custom_fields( $post, $metabox_args ) {

    list( $screen, $meta_box_key ) = array(
      $metabox_args['args']['screen'],
      $metabox_args['args']['meta_box_key'] 
    );

    if ( $screen != get_post_type() || ! isset( static::$_custom_fields[ $screen ]['args'] ) ) {
      return;
    }

    $_meta = get_post_meta( $post->ID, "_bcf_{$screen}_meta", true );
    $meta  = $_meta ? json_decode( $_meta, true ) : array();
    
    ?>
      <div class="onload custom-fields-wrapper border-box <?php echo esc_attr( $screen ); ?>-custom-fields-wrapper">
        
        <?php wp_nonce_field( "{$screen}_custom_meta", "{$screen}_custom_meta" ); ?>

        <?php     
        foreach ( static::$_custom_fields[ $screen ]['args'] as $custom_field ) {

          $field_value           = get_post_meta($post->ID, $custom_field['id'], true);
          $saved_field_value     = isset( $meta[ $custom_field[ 'id' ] ] ) ? $field_value : '';
          $value                 = $custom_field[ 'type' ] == 'heading' ? $custom_field[ 'value' ] : $saved_field_value;
          $custom_field['value'] = $value;
          $registerd_meta_boxes  = $this->_get_regsitered_meta_boxes( $screen );

          if ( empty( static::$_custom_fields[ $screen ][ $custom_field['id'] ]['meta_box_key'] ) && $meta_box_key == $registerd_meta_boxes[0] ) {

            echo $this->_Input_Factory->create( $custom_field['type'], $custom_field );

          } elseif ( static::$_custom_fields[ $screen ][ $custom_field['id'] ]['meta_box_key'] == $meta_box_key ) {

            echo $this->_Input_Factory->create( $custom_field['type'], $custom_field );

          }

        }
        ?>

      </div>    
    <?php
  }

  /**
   * --------------------------------------------------------------------------
   * Saves the Meta
   * --------------------------------------------------------------------------
   * 
   * @param int     $post_id
   * @param WP_Post $post
   * @param bool    $update
   * 
   * @return void 
   * */
  public function save_meta( $post_id, $post, $update ) {

    $screen = $post->post_type;

    if ( ! isset( $_POST["{$screen}_custom_meta"] ) ) {
      return;
    }

    if ( ! wp_verify_nonce( $_POST["{$screen}_custom_meta"], "{$screen}_custom_meta" ) ) {
      return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return;
    }

    if ( ! $this->_custom_fields_exists( $screen ) ) {
      return;
    }

    $meta = array();

    foreach ( static::$_custom_fields[ $screen ]['args'] as $custom_field ) {

      $posted_value = trim( wp_kses_post( $_POST[ $custom_field['id'] ] ) );

      $meta[ $custom_field['id'] ] = $posted_value;

      update_post_meta( $post_id, $custom_field['id'], $posted_value );

    }

    if ( ! empty( $meta ) ) {

      update_post_meta( $post_id, "_bcf_{$screen}_meta", json_encode( $meta ) );

    } else {

      delete_post_meta( $post_id, "_bcf_{$screen}_meta" );

    }
  }

  /**
   * 
   * @param string $screen
   * 
   * @return bool 
   * */
  protected function _custom_fields_exists( $screen ) {

    $all_custom_fields = static::$_custom_fields[ $screen ]['args'];

    return is_array( $all_custom_fields ) && count( $all_custom_fields ) > 0;
  }

}
