<?php

namespace BCF\contracts\interfaces;

interface Builder {

  /**
   * --------------------------------------------------------------------------
   * Regsiters the custom field
   * --------------------------------------------------------------------------
   *
   * @param string       $screen
   * @param array        $args
   * @param array|string $section
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
  public function regsiter_custom_field( $args, $screen, $section );

}
