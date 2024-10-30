<?php

namespace BCF\contracts\interfaces;

interface Taxonomy_Fields_Builder {

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
   * @Return: void
   * */
  public function regsiter_custom_field( $args, $taxonomy, $section );

}
