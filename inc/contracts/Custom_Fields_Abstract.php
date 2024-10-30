<?php
/**
 * Description of Abstract_Custom_Fields
 *
 * @author Paras Ralhan
 */

namespace Inc\contracts;

use Inc\custom_fields\Custom_Fields_Builder;
use Inc\exceptions\Base_Exception;

abstract class Custom_Fields_Abstract {

  protected $_screen = 'post';

  protected $_meta_boxes = array();

  protected $_custom_fields = array();

  protected $_builder;

  protected function __construct() {

    add_action( 'save_post', array( $this, 'save_custom_meta' ), 15, 3 );

    $this->_builder = Custom_Fields_Builder::get_instance();

    $this->_register_meta_boxes();
    $this->_regsiter_fields();
    
  }

  /**
   * --------------------------------------------------------------------------
   * Regsiter All the metaboxes for specific post type
   * --------------------------------------------------------------------------
   *
   * @Return void
   * */
  public function _register_meta_boxes() {

    if ( empty( $this->_meta_boxes ) ) {
      return;
    }

    array_walk( $this->_meta_boxes, function( $meta_box, $key ) {

      try {

        $this->_builder->register_meta_box( 
          $key, 
          $meta_box['label'], 
          $meta_box['screen'], 
          $meta_box['location'], 
          $meta_box['priority'] 
        );
      
      } catch ( Base_Exception $e ) {

        echo $e->get_Message();

      }

    } );
  }

  /**
   * --------------------------------------------------------------------------
   * Regsiter Custom Fields
   * --------------------------------------------------------------------------
   *
   * @Return void
   * */
  protected function _regsiter_fields() {

    if ( empty( $this->_custom_fields ) ) {
      return;
    }

    array_walk( $this->_custom_fields, function( $field ) {

      $has_meta_key = isset( $field[ 'meta_box_key' ] ) && ! empty( $field[ 'meta_box_key' ] );
      $meta_box_key = $has_meta_key ? $field['meta_box_key'] : array_keys( $this->_meta_boxes )[0];

      try {

        $this->_builder->regsiter_custom_field( $this->_screen, $field, $meta_box_key );

      } catch ( Base_Exception $e ) {

        echo $e->get_Message();

      }

    } );
  }
  
  public function save_custom_meta( $post_id, $post, $update ) {

    if ( $this->_screen != $post->post_type ) {
      return;
    }

    $meta   = get_post_meta( $post_id, "_bcf_{$this->_screen}_meta" );
    $author = get_user_meta( $post->post_author, '', TRUE );

    $meta[ $this->_screen . '_title_h']   = strip_tags( $post->post_title );
    $meta[ $this->_screen . '_content_h'] = strip_tags( $post->post_content );
    $meta[ $this->_screen . '_author_h']  = $author['nickname'];

    update_post_meta( $post_id, "_bcf_{$this->_screen}_meta", $meta );

    array_walk( $meta, function ( $value, $key ) use ( $post_id ) {

      update_post_meta( $post_id, $key, $value );

    } );

  }

}
