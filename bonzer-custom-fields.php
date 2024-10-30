<?php
/*
**************************************************************************

Plugin Name: Bonzer Custom Fields
Description: Create custom fields anywhere in the Wordress Admin
Version: 1.1
Author: Paras Ralhan
Author URI: http://parasralhan.com
Text Domain:  bcf
License: GPL2

**************************************************************************

Bonzer Custom Fields is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Bonzer Custom Fields is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Bonzer Custom Fields. If not, see <http://www.gnu.org/licenses/>.

**************************************************************************
*/

require 'bootstrap.php';

add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ($links){

  $links = array_merge( [
              '<a href="' . esc_url( admin_url( '/admin.php?page=bcf' ) ) . '">' . __( 'Create', 'bcf' ) . '</a>'
           ], $links );

  return $links;

} ); 

BCF\Bonzer_Custom_Fields::get_instance();
BCF\Options_Factory::get_instance();
BCF\Previewer::get_instance();



