<?php

define('BCF__CONFIG__HASH', 'bcf____config'.md5('bcf____config'));
define('BCF__PREFIX', 'bcf_');

if ( defined('BCF__IS_MY_LOCALHOST') && BCF__IS_MY_LOCALHOST) {
  define('BCF__IS_DEV', true);
} else {
  define('BCF__IS_DEV', false);
}
