<?php
/**
 * Class RW_Blog_Wizard_Core
 *
 * Autoloader for the plugin
 *
 * @package   RW Blog Wizard
 * @author    Joachim Happel
 * @license   GPL-2.0+
 * @link      https://github.com/rpi-virtuell/rw-blog-wizard
 */
class RW_Blog_Wizard_Core {

    /**
     * Constructor
     *
     * @since   0.0.2
     * @access  public
     */
    function __construct() {

    }
    /**
     * runs on action hook init
     *
     * @since   0.0.2
     * @access  public
     * @static
     * @return  void
     * @use_action: init
     */
    public static function init() {

        if(get_current_blog_id() == 1){
            $p_args =  array(
                'labels'             => array(
                    'name'=>'Extensions',
                    'singular_name'=>'Extension',
                ),
                'has_archive' => false,
                'public' => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => array( 'slug' => 'ext' ),
                'capability_type'    => false,
                'has_archive'        => false,
                'hierarchical'       => false,
                'menu_position'      => 66,
                'supports'           => array( 'title', 'editor', 'thumbnail' ,'custom-fields', 'excerpt')
            );
            $ext_args = array(
                'labels'             => array(
                    'name'=>'Plugin Gruppen',
                    'singular_name'=>'Plugin Gruppe',
                ),
                'has_archive' => false,
                'public' => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => array( 'slug' => 'extgroup' ),
                'capability_type'    => false,
                'has_archive'        => false,
                'hierarchical'       => false,
                'menu_position'      => 67,
                'supports'           => array( 'title', 'editor', 'thumbnail' )
            );
        }else{
            $p_args =  array(
                'labels'             => 'Plugins',
                'has_archive' => false,
                'public' => false,
                'show_ui'            => false,
                'show_in_menu'       => false,
                'query_var'          => false,
                'has_archive'        => false,
                'hierarchical'       => false
            );
            $ext_args =  array(
                'labels'             => 'Plugingroups',
                'has_archive' => false,
                'public' => false,
                'show_ui'            => false,
                'show_in_menu'       => false,
                'query_var'          => false,
                'has_archive'        => false,
                'hierarchical'       => false
            );
        }




        register_post_type( 'rw-plugin' , $p_args);
        register_post_type( 'rw-plugingroup' , $ext_args);

    }


    /**
     * Load custom Stylesheet
     *
     * @since   0.0.2
     * @access  public
     * @static
     * @return  void
     * @use_action: wp_enqueue_scripts
     */
    public static function enqueue_style() {
        wp_enqueue_style( 'customStyle',RW_Blog_Wizard::$plugin_url . '/css/style.css' );
    }

    /**
     * Load custom javascript
     *
     * @since   0.0.2
     * @access  public
     * @static
     * @return  void
     * @use_action: wp_enqueue_scripts
     */
    public static function enqueue_js() {

        wp_enqueue_script( 'rw_blog_wizard_ajax_script',RW_Blog_Wizard::$plugin_url . '/js/javascript.js' );

    }

    //TODO: add wp_ajax-response-actions in rootfile
    //TODO: add javasript ajax calls
    //TODO: add corresponding response functions like this
    /**
     * Example of Ajax response
     *
     * @since   0.0.2
     * @access  public
     * @static
     * @return  void
     * @use_action: wp_ajax_rw_blog_wizard_core_ajaxresponse
     */
    public static function ajaxresponse(){

        echo json_encode(
            array(
                    'success' =>  true
                ,   'msg'=>'Ajax Example. <em>Users time on click</em> : <b>'.
                            $_POST['message'] .
                            '</b> ( scripts locatet in: inc/'.
                            basename(__FILE__).' (Line: '.__LINE__.
                            ') | js/javascript.js )'
            )
        );

        die();

    }

    /**
     *
     * @global type $wpdb
     * @param int $clone_from_blog_id the blog id which we are going to clone

     */

    public static function clone_blog( $clone_from_blog_id, $clone_to_blog_id , $type ){

        $clone_from_blog_id = intval($clone_from_blog_id);
        $clone_to_blog_id = intval($clone_to_blog_id);

        $blogname = get_blog_details()->blogname;

        $user_id = get_current_user_id();

        if(!class_exists('MUCD_Duplicate')) {
            wp_die("plugin multisite-clone-duplicator is not installed or activated");
        }

        if( $clone_from_blog_id == 0 || $clone_to_blog_id < 2 || $clone_from_blog_id == $clone_to_blog_id ){

            die('wp_clone_error');

            return new WP_Error('wp_clone_error', 'Nicht zulässige Auswahl');

        }

        MUCD_Files::copy_files($clone_from_blog_id, $clone_to_blog_id);
        MUCD_Data::copy_data($clone_from_blog_id, $clone_to_blog_id);
        // MUCD_Duplicate::copy_users($clone_from_blog_id, $clone_to_blog_id);


        update_blog_option( $clone_to_blog_id, 'mucd_duplicable', "no");
        update_blog_option( $clone_to_blog_id, 'rw_blog_wizard_type', $type);

        $form_message['msg'] = MUCD_NETWORK_PAGE_DUPLICATE_NOTICE_CREATED;
        $form_message['site_id'] = $clone_to_blog_id;


        MUCD_Duplicate::write_log('End site duplication : new site ID = ' . $clone_to_blog_id);

        add_user_to_blog($clone_to_blog_id, $user_id, 'administrator');
        update_blog_option($clone_to_blog_id, 'blogname', $blogname);

        wp_cache_flush();

        return $form_message;


    }

}