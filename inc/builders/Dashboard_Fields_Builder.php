<?php
namespace BCF\builders;
/**
 * 
 * Dashboard_Fields_Builder
 * 
 * @package  bonzer-custom-fields
 */
use BCF\contracts\interfaces\Builder as Builder_Contract,
    Bonzer\Inputs_WP\contracts\interfaces\Inputs_Factory as Inputs_Factory_Contract;
use Bonzer\Exceptions\Invalid_Param_Exception,
Bonzer\Inputs_WP\factories\Input as Inputs_Factory;


class Dashboard_Fields_Builder implements Builder_Contract{

  /**
   * Class Instance
   *
   * @var Dashboard_Fields_Builder
   */
  private static $_instance;

  /**
   *
   * @var Inputs_Factory_Contract
   */
  protected $_Input_Factory;

  /**
   * All Fields
   *
   * @var array
   */
  protected $_fields = array();

  /**
   * All Metaboxes
   *
   * @var array
   */
  protected $_metaboxes = array();
  
  /**
   * --------------------------------------------------------------------------
   * Class Constructor
   * --------------------------------------------------------------------------
   * 
   * @Return Dashboard_Fields_Builder 
   * */
  protected function __construct( Inputs_Factory_Contract $input_factory = NULL ){

    $this->_Input_Factory = $input_factory ?: Inputs_Factory::get_instance();  

    add_action( 'wp_dashboard_setup', array( $this, 'create_options') );
  }

  /**
   * --------------------------------------------------------------------------
   * Singleton
   * --------------------------------------------------------------------------
   * 
   * @Return Dashboard_Fields_Builder 
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

    $this->_fields[ $section['metaBoxKey'] ][]  = $args;       
    $this->_metaboxes[ $section['metaBoxKey'] ] = $section;   
  }

  public function get_fields() {

    return $this->_fields;
  }

  public function create_options() {

    foreach ( $this->_fields as $section_key => $fields ) {

      wp_add_dashboard_widget(
        $section_key,         
        $this->_metaboxes[$section_key]['label'],     
        array( $this, 'dashboard_fields'),
        array( $this, 'save_options'),
        array( $fields, $section_key)
       );   
    }
  }

  public function dashboard_fields( $post, $args ){

    $key = $args['args'][1];
    ?>

    <p style="position:relative; margin:0; min-height: 15px;">
      <a style="position:absolute; top:-11px; right:0;" 
         href="<?php echo admin_url('index.php') ?>?edit=<?php echo $key; ?>#<?php echo $key; ?>">

          <i class="fa fa-pencil"></i> edit

      </a>
    </p>  

    <?php
    $fields = $args['args'][0];  

    foreach ( $fields as $field ) {

      $key            = 'bcf_index_' . str_replace( 'bcf_', '', $field['name'] );
      $field['value'] = get_option( $key );

      echo $this->_Input_Factory->create( $field[ 'type' ], $field );

    }
  }

  public function save_options( $post, $args ){  

    foreach ( $this->_fields[ $args['id'] ] as $field ) {

      $key = 'bcf_index_' . str_replace( 'bcf_', '', $field['name'] );

      if( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST[ $field['name'] ] ) ) {
        update_option( $key, trim( wp_kses_post( $_POST[ $field['name'] ] ) ) );
      }

      $field['value'] = get_option( $key );

      echo $this->_Input_Factory->create( $field[ 'type' ], $field );

    }    
  }
}