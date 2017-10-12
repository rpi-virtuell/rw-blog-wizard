<?php
/**
 * @TODO Dateien umbennen in ./inc
 * RW_Blog_Wizard_Autoloader  > RW_Your_Plugin_Name_Autoloader
 * RW_Blog_Wizard_Installation  > RW_Your_Plugin_Name_Installation
 * RW_Blog_Wizard_Core  > RW_Your_Plugin_Name_Core
 *
 * @TODO Suchen und ersetzen:
 * search-replace  'RW Blog Wizard'     'RW Your Plugin Name'
 * search-replace  'RW_Blog_Wizard'     'RW_Your_Plugin_Name'
 * search-replace  'rw_blog_wizard'     'rw_your_plugin_name'
 * search-replace  'rw-blog-wizard'     'rw-your-plugin-name'
 * search-replace  'Joachim Happel'        'Your Name'
 * search-replace  'http://joachim-happel.de'   'Authors url'
 *
 */
/**
 *
 * @package   RW Blog Wizard
 * @author    Joachim Happel
 * @license   GPL-2.0+
 * @link      https://github.com/rpi-virtuell/rw-blog-wizard
 */

/*
 * Plugin Name:       RW Blog Wizard
 * Plugin URI:        https://github.com/rpi-virtuell/rw-blog-wizard
 * Description:       Multisiteplugin, das dem Administrator eines Blogs die Möglichkeit gibt, vorkonfigurierte Plugins und Themes zu laden.
 * Version:           0.0.5
 * Author:            Joachim Happel
 * Author URI:        http://joachim-happel.de
 * License:           GNU General Public License v2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:       /languages
 * Text Domain:       rw-blog-wizard
 * Network:           true
 * GitHub Plugin URI: https://github.com/rpi-virtuell/rw-blog-wizard
 * GitHub Branch:     master
 * Requires WP:       4.0
 * Requires PHP:      5.3
 */


class RW_Blog_Wizard {
    /**
     * Plugin version
     *
     * @var     string
     * @since   0.0.1
     * @access  public
     */
    static public $version = "0.0.5";

    /**
     * Singleton object holder
     *
     * @var     mixed
     * @since   0.0.1
     * @access  private
     */
    static private $instance = NULL;

    /**
     * @var     mixed
     * @since   0.0.1
     * @access  public
     */
    static public $plugin_name = NULL;

    /**
     * @var     mixed
     * @since   0.0.1
     * @access  public
     */
    static public $textdomain = NULL;

    /**
     * @var     mixed
     * @since   0.0.1
     * @access  public
     */
    static public $plugin_base_name = NULL;

    /**
     * @var     mixed
     * @since   0.0.1
     * @access  public
     */
    static public $plugin_url = NULL;

    /**
     * @var     string
     * @since   0.0.1
     * @access  public
     */
    static public $plugin_filename = __FILE__;

    /**
     * @var     string
     * @since   0.0.1
     * @access  public
     */
    static public $plugin_version = '';


    /**
     * @var     array
     * @since   0.0.2
     * @access  public
     */
    static public $notice = array( 'label'=>'info' , 'message'=>'' );

    /**
     * @var     string
     * @since   0.0.2
     * @access  public
     */
    static public $plugin_dir = NULL;


    /**
     * Plugin constructor.
     *
     * @since   0.0.1
     * @access  public
     * @uses    plugin_basename
     * @action  rw_blog_wizard_init
     */
    public function __construct () {
        // set the textdomain variable
        self::$textdomain = self::get_textdomain();

        // The Plugins Name
        self::$plugin_name = $this->get_plugin_header( 'Name' );

        // The Plugins Basename
        self::$plugin_base_name = plugin_basename( __FILE__ );

        // The Plugins Version
        self::$plugin_version = $this->get_plugin_header( 'Version' );


        // absolute path to plugins root
        self::$plugin_dir = plugin_dir_path(__FILE__);

        // url to plugins root
        self::$plugin_url = plugins_url('/',__FILE__);

        // Load the textdomain
        $this->load_plugin_textdomain();

        // Add Filter & Actions
        // - https://codex.wordpress.org/Plugin_API/Action_Reference
        // - https://codex.wordpress.org/Plugin_API/Filter_Reference


        //@TODO  Hier Filter und Actions einbinden.


        add_action('init',                       array( 'RW_Blog_Wizard_Core','init' ) );
        do_action( 'rw_blog_wizard_init' );

        add_action( 'admin_post_rw_blog_wizard_network_settings',
                                                 array( 'RW_Blog_Wizard_Settings', 'network_settings' ) );
        add_action( 'admin_post_rw_blog_wizard_plugin_options_action',
                                                 array( 'RW_Blog_Wizard_Settings', 'edit_and_combine_plugins_action' ) );
        add_action( 'admin_post_rw_blog_wizard_activate_selected_plugin_bundle',
                                                 array( 'RW_Blog_Wizard_Settings', 'activate_selected_plugin_bundle' ) );
        add_action( 'admin_post_rw_blog_wizard_deactivate_dashboard_welcome',
                                                 array( 'RW_Blog_Wizard_Settings', 'set_no_blog_type' ) );
        add_action( 'admin_post_rw_blog_wizard_plugin_create_new_blog_from_template',
                                                 array( 'RW_Blog_Wizard_Settings', 'create_new_blog_from_template' ) );
        add_action( 'admin_init',                array( 'RW_Blog_Wizard_Settings', 'register_blog_type' ) );
        add_action( 'admin_menu',                array( 'RW_Blog_Wizard_Settings', 'admin_menu' ) );
        add_action( 'network_admin_menu',        array( 'RW_Blog_Wizard_Settings', 'admin_menu' ) );


        //enable and load css and js files
         add_action( 'wp_enqueue_scripts',       array( 'RW_Blog_Wizard_Core','enqueue_style' ) );
         add_action( 'wp_enqueue_scripts',       array( 'RW_Blog_Wizard_Core','enqueue_js' ) );
         do_action( 'rw_blog_wizard_enqueue' );

        //enable ajax examples
         add_action( 'admin_enqueue_scripts',    array( 'RW_Blog_Wizard_Core','enqueue_js' ) );
         add_action( 'admin_enqueue_scripts',    array( 'RW_Blog_Wizard_Core','enqueue_style' ) );
        // add_action( 'wp_ajax_rw_blog_wizard_core_ajaxresponse' ,array( 'RW_Blog_Wizard_Core','ajaxresponse' )  );

        //enable an widget
        //add_action('widgets_init',             array( 'RW_Blog_Wizard_Widget','init' ) );
        //do_action( 'rw_blog_wizard_widget_init' );

        function my_network_admin_notices() {
            echo '<div class="update-nag">Configuration of Foo is not complete!</div>';
        }
        add_action( 'network_admin_notices', array( 'RW_Blog_Wizard_Settings', 'admin_notice' ) );
        add_action( 'admin_notices', array( 'RW_Blog_Wizard_Settings', 'admin_notice' ) );

        add_action('wp_dashboard_setup',array( 'RW_Blog_Wizard_Core','setup_dashboard_widgets' ),9999);

        add_action( 'save_post',  array( 'RW_Blog_Wizard_Settings', 'on_save_template_description_set_public' ), 10, 3  );



    }

    /**
     * Creates an Instance of this Class
     *
     * @since   0.0.1
     * @access  public
     * @return  Object
     */
    public static function get_instance() {

        if ( NULL === self::$instance )
            self::$instance = new self;

        return self::$instance;
    }

    /**
     * Load the localization
     *
     * @since	0.0.1
     * @access	public
     * @uses	load_plugin_textdomain, plugin_basename
      * @return	void
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( self::get_textdomain(), false, apply_filters ( 'rw_blog_wizard_domain', dirname( plugin_basename( __FILE__ )) .  self::get_textdomain_path() ) );
    }

    /**
     * Get a value of the plugin header
     *
     * @since   0.0.1
     * @access	protected
     * @param	string $value
     * @uses	get_plugin_data, ABSPATH
     * @return	string The plugin header value
     */
    protected function get_plugin_header( $value = 'TextDomain' ) {

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php');
        }

        $plugin_data = get_plugin_data( __FILE__ );
        $plugin_value = $plugin_data[ $value ];

        return $plugin_value;
    }

    /**
     * get the textdomain
     *
     * @since   0.0.1
     * @static
     * @access	public
     * @return	string textdomain
     */
    public static function get_textdomain() {
        if( is_null( self::$textdomain ) )
            self::$textdomain = self::get_plugin_data( 'TextDomain' );

        return self::$textdomain;
    }

    /**
     * get the textdomain path
     *
     * @since   0.0.1
     * @static
     * @access	public
     * @return	string Domain Path
     */
    public static function get_textdomain_path() {
        return self::get_plugin_data( 'DomainPath' );
    }

    /**
     * return plugin comment data
     *
     * @since   0.0.1
     * @uses    get_plugin_data
     * @access  public
     * @param   $value string, default = 'Version'
     *		Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title
     * @return  string
     */
    public static function get_plugin_data( $value = 'Version' ) {

        if ( ! function_exists( 'get_plugin_data' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

        $plugin_data  = get_plugin_data ( __FILE__ );
        $plugin_value = $plugin_data[ $value ];

        return $plugin_value;
    }


    /**
     * creates an admin notification on admin pages
     *
     * @since   0.0.2
     * @uses     _notice_admin
     * @access  public
     * @param label         $value string,  default = 'info'
     *        error, warning, success, info
     * @param message       $value string
     * @param $dismissible  $value bool,  default = false
     *
     */
    public static function notice_admin($label=info, $message, $dismissible=false ) {
        $notice = array(
            'label'             =>  $label
        ,   'message'           =>  $message
        ,   'is-dismissible'    =>  (bool)$dismissible

        );
        self::_notice_admin($notice);
    }

    /**
     * creates an admin notification on admin pages
     *
     * @since   0.0.2
     * @uses     _notice_admin
     * @access  private
     * @param $value array
     * @link https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
     */

    static function _notice_admin($notice) {

        self::$notice = $notice;

        add_action( 'admin_notices',function(){

            $note = RW_Blog_Wizard::$notice;
            $note['IsDismissible'] =
                (isset($note['is-dismissible']) && $note['is-dismissible'] == true) ?
                    ' is-dismissible':'';
            ?>
            <div class="notice notice-<?php echo $note['label']?><?php echo $note['IsDismissible']?>">
                <p><?php echo __( $note['message'] ,RW_Blog_Wizard::get_textdomain() ); ?></p>
            </div>
            <?php
        });

    }

}

if ( class_exists( 'RW_Blog_Wizard' ) ) {


    add_action( 'plugins_loaded', array( 'RW_Blog_Wizard', 'get_instance' ) );

    require_once 'inc/RW_Blog_Wizard_Autoloader.php';
    RW_Blog_Wizard_Autoloader::register();

    register_activation_hook( __FILE__, array( 'RW_Blog_Wizard_Installation', 'on_activate' ) );
    register_uninstall_hook(  __FILE__,	array( 'RW_Blog_Wizard_Installation', 'on_uninstall' ) );
    register_deactivation_hook( __FILE__, array( 'RW_Blog_Wizard_Installation', 'on_deactivation' ) );
}
