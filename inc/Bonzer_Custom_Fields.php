<?php

namespace BCF;

use Bonzer\IOC_Container\facades\Container;

/**
 * 
 * Initializer
 * 
 * @package bonzer-custom-fields
 */
use Less_Parser;

class Bonzer_Custom_Fields {

  /**
   * @var string
   */
  protected $_page_id = 'bcf';

  /**
   * @var string
   */
  protected $_plugin_url;  

  /**
   * @var string
   */
  protected $_plugin_path;

  /**
   * @var Bonzer_Custom_Fields
   */
  private static $_instance;  

  /**
   * a key for storing BCF creator settings in database
   * 
   * @var string
   */
  private $_config_hash;

  /**
   * @var array
   */
  protected $_all_wordpress_pages;

  /**
   * @var array
   */
  protected $_pages_to_include = array(
    'index.php'              => true,
    'nav-menus.php'          => true,
    'users.php'              => false,
    'profile.php'            => true,
    'options-general.php'    => true,
    'options-writing.php'    => true,
    'options-reading.php'    => true,
    'options-discussion.php' => true,
    'options-media.php'      => true,
    'options-permalink.php'  => true,
  );

  /**
   * --------------------------------------------------------------------------
   * Class Constructor
   * --------------------------------------------------------------------------
   * 
   * @return Bonzer_Custom_Fields 
   * */
  protected function __construct() {

    $this->_config_hash = BCF__CONFIG__HASH;
    $this->_plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
    $this->_plugin_path = dirname( __DIR__ );

    if ( BCF__IS_DEV ) {

      $this->_complie_less($this->_plugin_path.'/assets/css/style.less', $this->_plugin_path.'/assets/css/style.css');

    }    
    
    // ADMIN SCRIPTS
    add_action( 'admin_enqueue_scripts', array( $this, 'load_assets') , 22 );
    add_action( 'admin_menu',            array( $this, 'register_page')  );   

    add_action('wp_ajax_bcf_load_post_types',       array( $this, 'get_all_post_types') );
    add_action('wp_ajax_bcf_load_taxonomies',       array( $this, 'get_taxonomies') );
    add_action('wp_ajax_bcf_save_config',           array( $this, 'save_config') );
    add_action('wp_ajax_bcf_load_config',           array( $this, 'load_config') );   
    add_action('wp_ajax_bcf_load_admin_menu_pages', array( $this, 'load_admin_menu_pages') );
    // add_action('wp_head',                           array( $this, 'vector_icons_styles') );

  }

  /**
   * --------------------------------------------------------------------------
   * Class Constructor Singleton
   * --------------------------------------------------------------------------
   * 
   * @return Bonzer_Custom_Fields 
   * */
  public static function get_instance() {

    if ( static::$_instance ) {
      return static::$_instance;
    }
    
    return static::$_instance = new static();

  }

  /**
   * --------------------------------------------------------------------------
   * Vector icons CSS | Removed
   * --------------------------------------------------------------------------
   * 
   * @return string - inline css 
   * */
  public function vector_icons_styles(){

    $vector_icons = Container::make( 'Bonzer\Inputs\fields\utils\Icons' )->vector_icons();
    ?>

    <style>

      .vector{
        width: 50px;
        height: 50px;
        background-size: cover;
        background-position: center center;
      }

      <?php

      array_walk( $vector_icons, function( $icon_type_data, $icon_type ) {

        $icon_type_data_keys = array_keys( $icon_type_data );

        array_walk( $icon_type_data_keys, function( $icon ) use ( $icon_type ) {

          $mappings = Container::make( 'Bonzer\Inputs\fields\utils\Icons' )->get_mappings();

          ?>
            .vector.<?php echo $icon; ?> {
              background-image: url('<?php echo $this->_plugin_url ?>assets/images/vectors/fallbacks/<?php echo $mappings[ $icon_type ]; ?>/100X100/<?php echo $icon; ?>.png');
            }
          <?php

        } );

      } );

      ?>
    </style>
    <?php
  }

  /**
   * --------------------------------------------------------------------------
   * Registers the page
   * --------------------------------------------------------------------------
   * 
   * @return void
   * */
  public function register_page() {

    add_menu_page( 
      __( 'Custom Fields Creator', 'bcf' ), 
      __( 'BCF', 'bcf' ), 
      'activate_plugins', 
      $this->_page_id, 
      array( $this, 'page'), 
      $this->_plugin_url .'assets/images/icon.png' 
    );
    
    add_action('admin_init', [$this, 'all_wordpress_pages'] );
    
  }

  /**
   * --------------------------------------------------------------------------
   * Custom Fields Creator
   * --------------------------------------------------------------------------
   * 
   * @return void 
   * */
  public function page() {

    ?>
    <header id="bonzer-custom-fields-creator-header">

      <img src="<?php echo esc_url( "{$this->_plugin_url}assets/images/logo_bc_1.png" ) ; ?>" 
           role="Logo" 
           alt="Logo" />

      <h1>
        <?php 
          /* translators: 1. Opening <small> tag, 2. Creator text, 3. Closing </small> tag */
          echo sprintf(
                  esc_html__( 'Bonzer Custom Fields %1$s%2$s%3$s', 'bcf' ), 
                  '<small>', 
                  esc_html__( 'Creator', 'bcf' ), 
                  '</small>' 
                );
        ?>
      </h1>
      
      <div class="clear"></div>
    
    </header>
    
    <div id="bonzer-custom-fields-creator"></div>

    <?php
  }

  /**
   * --------------------------------------------------------------------------
   * Loads Assets
   * --------------------------------------------------------------------------
   * 
   * @return void 
   * */
  public function load_assets() {
    
    wp_enqueue_style( 'bcf-admin-css', $this->_plugin_url .'assets/css/admin.css', [], '0.0.1', 'all' );
    wp_enqueue_style( 'bcf-fontello-arrows', $this->_plugin_url .'assets/css/fontello-arrows.css', [], '0.0.1', 'all' );

    if ( ! ( isset( $_GET['page'] ) && $_GET['page'] && $_GET['page'] == 'bcf') ) {
      return;
    }

    wp_enqueue_media();

    $bundle = BCF__IS_DEV ? 'bundle.js' : 'bundle.prod.js';
    
    $js_files = array(
      array(
        'handle' => 'bcf-bundle',
        'src' => $this->_plugin_url. 'assets/dist/' . $bundle,
        'dep' => array('jquery','jquery-ui-sortable', 'media-upload'),
        'ver' => '0.0.1',
        'media' => 'all',
        'in_footer' => TRUE
      )
    );
    foreach ( $js_files as $js_file ) {

      wp_enqueue_script( $js_file[ 'handle' ], $js_file[ 'src' ], $js_file[ 'dep' ], $js_file[ 'ver' ], $js_file[ 'in_footer' ] );
    
    }

    wp_localize_script( 'bcf-bundle', 'bcf_ajax', [
      'ajax_url' => admin_url( 'admin-ajax.php' ),
      'data'     => '',
      'nonce'    => wp_create_nonce( 'bcf--bonzer-com--nonce' ),
    ] );

    $css_files = array(
      array(
        'handle' => 'bcf-fontello-arrows',
        'src' => $this->_plugin_url. 'assets/css/fontello-arrows.css',
        'dep' => array(),
        'ver' => '0.0.1',
        'media' => 'all',
      ),
      array(
        'handle' => 'bcf-styles',
        'src' => $this->_plugin_url. 'assets/css/style.css',
        'dep' => array(),
        'ver' => '0.0.1',
        'media' => 'all',
      )
    );

    foreach ( $css_files as $css_file ) {

      wp_enqueue_style( $css_file[ 'handle' ], $css_file[ 'src' ], $css_file[ 'dep' ], $css_file[ 'ver' ], $css_file[ 'media' ] );
    
    }
  }

  /**
   * --------------------------------------------------------------------------
   * Compile Less to Css
   * --------------------------------------------------------------------------
   * 
   * @param string $from
   * @param string $to
   * 
   * @return void 
   * */
  protected function _complie_less( $from, $to ) {

    $parser = new Less_Parser();

    $parser->parseFile( $from );
    $css = $parser->getCss();

    file_put_contents( $to, $css );

  }

  /**
   * --------------------------------------------------------------------------
   * GET post types
   * --------------------------------------------------------------------------
   * 
   * @return void 
   * */
  public function get_all_post_types() {

    $post_types = get_post_types( [
      'public'   => true,
    ] );

    wp_send_json( array_keys( $post_types ) );

  }  

  /**
   * --------------------------------------------------------------------------
   * GET Taxonomies
   * --------------------------------------------------------------------------
   * 
   * @Return void 
   * */
  public function get_taxonomies() {

    $response   = array();
    $taxonomies = get_taxonomies( array(
      'public' => true,
    ), 'objects');

    foreach ( $taxonomies as $taxonomy ) {

      $response[] = [
        'title' => $taxonomy->labels->name,
        'slug'  => $taxonomy->name,
      ];

    }

    wp_send_json( $response );

  }

  /**
   * --------------------------------------------------------------------------
   * Load Config 
   * --------------------------------------------------------------------------
   * 
   * @Return void 
   * */
  public function load_config() {

    $config = get_option( $this->_config_hash );

    $config = str_replace('(php|', '<?php', $config );

    if ( $config ) {

      wp_send_json( json_decode( $config ) );

    } else {

      wp_send_json( array(
        'bcf_load_config_status' => 'failure'
      ) );

    }    

  }

  /**
   * --------------------------------------------------------------------------
   * SAVE Config
   * --------------------------------------------------------------------------
   * 
   * @Return void 
   * */
  public function save_config() {

    $data = stripcslashes( $_POST['data'] );

    $nonce_str = 'bcf--bonzer-com--nonce';

    if ( !check_ajax_referer( $nonce_str, 'security', false ) ) {

      wp_send_json( array(
        'status' => 'failure',
      ) );

    }

    $this->_save_php_code_fragments( $data );

    $config = $data;

    update_option( $this->_config_hash, str_replace('<?php', '(php|', $config ) );

    wp_send_json( array(
      'status' => 'success',
    ) );

  }

  protected function _save_php_code_fragments( $config ) {

    $config = json_decode( $config, true );
    $dir    = __DIR__."/codes/";

    if ( !( file_exists( $dir ) && is_dir( $dir ) ) ) {
      mkdir( $dir );
    }

    $this->_delete_files_from_directory( $dir );

    if ( isset( $config['fields'] ) && isset( $config['fields']['fieldBakers'] ) 
                                    && is_array( $config['fields']['fieldBakers'] ) ) {

      foreach ( $config['fields']['fieldBakers'] as $fieldBaker ) {

        $fieldCreator = $fieldBaker['fieldCreator'];
        $fieldKey     = $fieldBaker['fieldCreator']['fieldKey'];
        $optionsFor   = isset( $fieldCreator['optionsFor'] ) ? $fieldCreator['optionsFor'] : 'user';

        if ( $optionsFor == 'developer' ) {

          $code = str_replace( ["\r\n"], '', $fieldCreator['optionsDeveloper']['code'] );
          $file = $dir."{$fieldKey}.php";

          file_put_contents( $file, $code ); 

        }
      }
    }    
  }

  public function _delete_files_from_directory( $dir ){

    $files = glob("{$dir}*"); // get all file names

    foreach( $files as $file ) { 

      if( is_file( $file ) ) {
        unlink( $file ); 
      }

    }
  }

  /**
   * --------------------------------------------------------------------------
   * Load Settings Pages 
   * --------------------------------------------------------------------------
   * 
   * @return void 
   * */
  public function load_admin_menu_pages() {

    $all_pages = require __DIR__.'/all_pages.php';

    wp_send_json( $all_pages );

  }

  /**
   * --------------------------------------------------------------------------
   * Dump Settings Wordpress Pages in File
   * --------------------------------------------------------------------------
   * 
   * @return void 
   * */
  public function all_wordpress_pages(){

    global $submenu, $menu, $pagenow, $_registered_pages, $admin_page_hooks;

    $all_menus         = array();
    $_pages_to_include = array_keys( $this->_pages_to_include );

    foreach ( $menu as $menu_item ) {

      $sub_menu = array();

      if ( ! in_array( $menu_item[2], $_pages_to_include ) ) {
        continue;
      }

      if ( array_key_exists( $menu_item[2], $submenu ) ) {

        foreach ( $submenu[ $menu_item[2] ] as $submenu_item ) {

          if ( !in_array( $submenu_item[2], $_pages_to_include ) ) {
            continue;
          }

          $sub_menu[] = array(
            'title'     => $submenu_item[0],
            'link'      => $submenu_item[2],
            'as_option' => $this->_pages_to_include[ $submenu_item[2] ] ? 1 : 0,
          );

        }

      }

      if ( empty( $menu_item[0] ) || $menu_item[2] == 'bcf' ) {
        continue;
      }

      $all_menus[] = array(
        'title'     => $menu_item[0],
        'link'      => $menu_item[2],
        'icon'      => $menu_item[6],
        'as_option' => $this->_pages_to_include[ $menu_item[2] ] ? 1 : 0,
        'submenu'   => $sub_menu
      );

    }

    file_put_contents(__DIR__.'/all_pages.php', '<?php return '.var_export( $all_menus, true ).';');

  }


}
