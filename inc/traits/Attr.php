<?php

namespace BCF\traits;

trait Attr {

  protected static function attr_builder( $attrs ) {

    if ( !empty( $attrs ) && count( $attrs ) > 0 ) {

      foreach ( $attrs as $key => $value ) {
        $attr_str .= "{$key}='" . esc_attr($value) . "' ";
      }

    } else {

      $attr_str = '';

    }

    return $attr_str;
  }

  /**
   * --------------------------------------------------------------------------
   * parses the attrs
   * --------------------------------------------------------------------------
   * 
   * @param array $defaults
   * @param array $args
   * 
   * @Return void 
   * */
  protected static function parse_attrs( $defaults, $args ) {

    $args_parsed = array();
    $keys        = array();

    if ( is_array( $defaults ) ) {
      $keys = array_keys( $defaults );
    }

    if ( is_array( $args ) ) {
      $keys = array_merge( array_keys( $args ), $keys );
    }

    foreach ( $keys as $key ) {

      if( isset( $args[ $key ] ) ) {

        $value = $args[ $key ];

      } else {

        $value = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';

      }
      
      if ( is_array( $value ) ) {   

        if( isset( $defaults[ $key ] ) ) {

          $args_parsed[ $key ] = static::parse_attrs( $defaults[ $key ], $value );

        } else {

          $args_parsed[ $key ] = $value;
        }  

      } else {

        $args_parsed[ $key ] = isset( $args[ $key ] ) ? $args[ $key ] : $value;

      }

    }

    return $args_parsed;
  }

}
