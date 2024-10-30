<?php

namespace BCF;
/**
 * 
 * Previewer
 * 
 * @package  bonzer-custom-fields
 * @author  Paras Ralhan <ralhan.paras@gmail.com>
 */

use Bonzer\IOC_Container\facades\Container;

class Previewer {

  /**
   * @var Previewer
   */
  private static $_instance;  

  protected $_inputs = array(
    'text' => array(
      'id'          => 'text',
      'placeholder' => 'Hello',
      'desc'        => 'Description Here'
    ),
    'textarea' => array(
      'id'          => 'textarea',
      'placeholder' => 'Write Something',
      'desc'        => 'Description Here'
    ),
    'multi-text' => array(
      'id'          => 'multi-text-input',
      'placeholder' => 'Write something & hit enter',
      'desc'        => 'Description Here'
    ),
    'select' => array(
      'id'          => 'select-input',
      'placeholder' => 'Select Options',
      'options'     => array(
        'hello' => 'Hello',
        'world' => 'World',
      ),
      'desc'        => 'Description Here'
    ),
    'multi-select' => array(
      'id'          => 'multi-select-input',
      'placeholder' => 'Select Options',
      'options' => array(
        'hello' => 'Hello',
        'world' => 'World',
      ),
      'desc'        => 'Description Here'
    ),
    'radio'   => array(
      'id'    => 'radio-input',
      'value' => 'hello',
      'options' => array(
        'hello' => 'Hello',
        'world' => 'World',
      ),
      'desc' => 'Description Here'
    ),
    'checkbox' => array(
      'id'   => 'checkbox-input',
      'desc' => 'Description Here'
    ),
    'calendar' => array(
      'id'          => 'calendar',
      'placeholder' => '23-Mar-2018',
      'desc'        => 'Description Here'
    ),
    'multi-text-calendar' => array(
      'id'          => 'multiple-dates',
      'placeholder' => 'Select Date',
      'desc'        => 'Description Here'
    ),
    'color' => array(
      'id'          => 'color-input',
      'placeholder' => '#dddddd',
      'desc'        => 'Description Here'
    ),
    'icon' => array(
      'id'          => 'icon-input',
      'placeholder' => 'Select icon',
      'desc'        => 'Description Here'
    ),
    'heading' => array(
      'id'    => 'heading-input',
      'value' => 'Section Heading',
      'desc'  => 'Description Here'
    ),
  );

  /**
   * --------------------------------------------------------------------------
   * Constructor
   * --------------------------------------------------------------------------
   * 
   * @return Previewer 
   * */
  protected function __construct() {

    add_action('wp_ajax_load_inputs_preview', array( $this, 'load_preview' ) );

  }

  /**
   * --------------------------------------------------------------------------
   * Singleton
   * --------------------------------------------------------------------------
   * 
   * @return Previewer 
   * */
  public static function get_instance() {

    if ( static::$_instance ) {
      return static::$_instance;
    }

    return static::$_instance = new static();
  }

  public function load_preview() {   

    $input = Container::make('Bonzer\Inputs_WP\factories\Input');

    foreach ( $this->_inputs as $key => $args ){
      echo $input->create( $key, $args );
    }

    wp_die();
  }

}
