<?php

namespace BCF\contracts\interfaces;

interface Custom_Fields_Builder extends Builder{

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
  public function register_meta_box( $key, $args );

  /**
   * --------------------------------------------------------------------------
   * Regsiters the custom field
   * --------------------------------------------------------------------------
   *
   * @param array $args
   * @param string $screen
   * @param string $meta_box_key
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
   * @Return: void
   * */
  public function regsiter_custom_field( $args, $screen, $meta_box_key = "" );

}
