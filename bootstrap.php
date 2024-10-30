<?php
require 'vendor/autoload.php';
require_once 'constants.php';

function bcf_is_dev() {  
  return BCF__IS_DEV;  
}

function bcf_print_r( $content ) {
  
  ?>
    <pre>
      <?php print_r( $content ); ?>
    </pre>
  <?php
}


