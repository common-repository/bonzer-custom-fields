<?php
namespace BCF\builders;
/**
 * 
 * Taxonomy_Fields_Builder
 * 
 * @package  bonzer-custom-fields
 * @author  Paras Ralhan <ralhan.paras@gmail.com>
 */
use BCF\contracts\interfaces\Builder as Builder_Contract,
    Bonzer\Inputs_WP\contracts\interfaces\Inputs_Factory as Inputs_Factory_Contract;
use Bonzer\Exceptions\Invalid_Param_Exception,
    Bonzer\Inputs_WP\factories\Input as Inputs_Factory;

class Taxonomy_Fields_Builder implements Builder_Contract {

  /**
   * Class Instance
   *
   * @var Taxonomy_Fields_Builder
   */
  private static $_instance;

  /**
   * All Taxonomy Fields
   *
   * @var array
   */
  protected $_fields = array();

  /**
   * All Taxonomy Sections
   *
   * @var array
   */
  protected $_sections = array();

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
   * @Return Taxonomy_Fields_Builder 
   * */
  protected function __construct( Inputs_Factory_Contract $input_factory = NULL ){

    $this->_Input_Factory = $input_factory ?: Inputs_Factory::get_instance();
    
    add_action( 'edit_term',    array( $this, 'save_meta_fields' ), 10, 3 );
    add_action( 'created_term', array( $this, 'save_meta_fields' ), 10, 3 );
  }

  /**
   * --------------------------------------------------------------------------
   * Singleton
   * --------------------------------------------------------------------------
   * 
   * @Return Taxonomy_Fields_Builder 
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
   * @param string $section
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
  public function regsiter_custom_field( $args, $taxonomy, $section ) {

    if ( ! $section ) {
      $section_key = 'bcf_general_meta_box';
    } else {
      $section_key = $section['metaBoxKey'];      
    }

    $this->_fields[ $taxonomy ][ $section_key ][] = $args;
    $this->_sections[ $section_key ]              = $section;
    
  }

  public function get_fields() {
    return $this->_fields;
  }

  /**
   * --------------------------------------------------------------------------
   * Regsiter Meta Fields
   * --------------------------------------------------------------------------
   * 
   * @param string $taxonomy
   * 
   * @return void 
   * */
  public function create_fields($taxonomy) {

    add_action( "{$taxonomy}_add_form_fields",  array( $this, 'add_taxonomy_fields' ) );
    add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edit_taxonomy_fields' ), 100, 2 );
  }

  /**
   * --------------------------------------------------------------------------
   * Add Taxonomy fields.
   * --------------------------------------------------------------------------
   *
   * @param string $taxonomy
   *
   * @return string - html
   * */
  public function add_taxonomy_fields( $taxonomy ) {
    ?>
      <div class="bonzer-form-fields-wrapper">

        <?php
        array_walk( $this->_fields[$taxonomy], function( $fields, $section_key ) {

          if ( $this->_sections[ $section_key ] ) {            
            echo "<h3>{$this->_sections[$section_key]['label']}</h3>";
          }

          foreach ( $fields as $field ) {
            ?>          

            <div class="form-field border-box onload">
              <?php echo $this->_Input_Factory->create( $field[ 'type' ], $field ); ?>
            </div>

            <?php
          }          
        } );
        ?>

      </div>
    <?php
  }

  /**
   * --------------------------------------------------------------------------
   * Edit Taxonomy thumbnail field.
   * --------------------------------------------------------------------------
   *
   * @param object $term
   * @param string $taxonomy
   *
   * @return string - html
   * */
  public function edit_taxonomy_fields( $term, $taxonomy ) {

    array_walk( $this->_fields[ $taxonomy ], function( $fields, $section_key ) use ( $term ) {

      if ( $this->_sections[ $section_key ] ) {
        ?>

        <tr>
          <th col-span="2">
            <h3><?php echo $this->_sections[$section_key]['label']; ?></h3>
          </th>
        </tr>

        <?php
      }

      foreach ( $fields as $field ) {

        $field['value'] = get_option( "{$field['id']}_{$term->term_id}" );
        $label          = isset( $field['label'] ) ? $field['label'] : ucwords( str_replace( '_', ' ', $field[ 'id' ] ) );
        ?>

        <tr class="form-field border-box onload">
          <th scope="row" valign="top">
            <label for="<?php echo esc_attr( $field['id'] ); ?>">
              <?php echo $label; ?>
            </label>
          </th>
          <td>
            <?php echo $this->_Input_Factory->create( $field[ 'type' ], $field ); ?>
          </td>
        </tr>

        <?php
      }      
    } );
  }

  /**
   * --------------------------------------------------------------------------
   * Save Taxonomy fields.
   * --------------------------------------------------------------------------
   *
   * @param int $term_id
   * @param int $tt_id
   * @param string $taxonomy
   *
   * @return: bool
   * */
  public function save_meta_fields( $term_id, $tt_id = '', $taxonomy = '' ) {

    array_walk( $this->_fields[ $taxonomy ], function( $fields ) use ( $term_id ) {

      foreach ( $fields as $field ) {

        if ( $_POST && isset( $_POST[ $field['id'] ] ) ) {

          $value = trim( wp_kses_post( $_POST[ $field[ 'id' ] ] ) );

          update_option( "{$field['id']}_{$term_id}", $value );
        }

      }
      
    } );

    return true;
  }

}