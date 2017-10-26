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


    public static function setup_dashboard_widgets() {

        global $wp_meta_boxes;

        $blog = get_blog_details();
        $blog->post_count;
		

        if( !is_network_admin() &&  get_current_blog_id() > 1 && $blog->post_count < 2 ) {

            //remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
            remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

            remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
            remove_meta_box('dashboard_primary', 'dashboard', 'side');
            remove_meta_box('dashboard_secondary', 'dashboard', 'side');

            if (empty(get_option('rw_blog_wizard_type'))) {
                unset($wp_meta_boxes['dashboard']);
                wp_add_dashboard_widget('rw_blog_wizard_widget', 'Hilfe zum Einstieg', array('RW_Blog_Wizard_Core', 'display_rw_blog_wizard_widget'));

                remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
                remove_meta_box('dashboard_activity', 'dashboard', 'side');
                remove_meta_box('dashboard_right_now', 'dashboard', 'side');
                remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
                remove_action('welcome_panel', 'wp_welcome_panel');

            }
        }
		if( !current_user_can('administrator')){
			remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
            remove_meta_box('dashboard_primary', 'dashboard', 'side');
            
		}
    }


    public static function display_rw_blog_wizard_widget(){

        $nonce = wp_create_nonce( 'rw_blog_wizard_deactivate_dashboard_welcome' );
        $endsetuplink = admin_url('admin-post.php?action=rw_blog_wizard_deactivate_dashboard_welcome&_wpnonce='.$nonce);

        ?>
        <div class="welcome-panel-content">
            <h2>Willkommen in unserem Blogssystem!</h2>
            <p class="about-description">Wir haben einige Links zusammengestellt, um dir den Start zu erleichtern:</p>
            <div class="welcome-panel-column-container">

                    <h3>Jetzt deine Seite konfigurieren</h3>
                    <p>Wähle aus verschiedenen Vorlagen eine am ehesten geeignete Webseite aus, die du anschließend mit deinen eigenen inhalten befüllen kannst. </p>
                    <a class="button button-primary button-hero load-customize hide-if-no-customize" href="<?php echo admin_url('options-general.php?page='.urlencode(RW_Blog_Wizard::$plugin_base_name));?>">Vorlage aussuchen</a>


            </div>
            <div class="welcome-panel-column-container">
                <h2>Du benötigst weitere Hilfe zum Einstieg?</h2>

                <ul>
                    <li><a href="http://fragen.rpi-virtuell.de/" class="welcome-icon welcome-learn-more">Nutze unser öffentliches Hilfesystem</a></li>
                    <li><a href="https://codex.wordpress.org/First_Steps_With_WordPress" class="welcome-icon welcome-learn-more">Erfahre mehr über den Einstieg</a></li>

                </ul>
            </div>
            <div class="welcome-panel-column-container">
                <h2>Erfahrenener Blogger?</h2>


                <p>Du kennst dich schon etwas aus? Dann kannst du dieses Widget ausblendeb und gleich auf deiner neuen Seite mit dem Bloggen starten</p>

                <p><a class="button button-secondary load-customize hide-if-no-customize" href="<?php echo $endsetuplink;?>">Sofort starten</a></p>

                <!--<p><a href="<?php echo admin_url('admin.php?page=rw-addons');?>" class="welcome-icon welcome-learn-more">Möglichkeiten, dein System später zu erweitern.</a></p>-->


            </div>

        </div>
        <?php
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
        //wp_enqueue_style( 'customStyle',RW_Blog_Wizard::$plugin_url . '/css/style.css' );
        wp_enqueue_style( 'jquery-ui-Style',"//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" );
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

        //wp_enqueue_script( 'rw_blog_wizard_ajax_script',RW_Blog_Wizard::$plugin_url . '/js/javascript.js' );
        wp_enqueue_script( 'jquery-ui','//code.jquery.com/ui/1.12.1/jquery-ui.js' );

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
     *  clones all content, files and options from one multisite blog (temptlate) to an other.
     *
     *  @requires plugin multisite-clone-duplicator
     *
     *  @params int $clone_from_blog_id the blog id which we are going to clone
     *  @params int $clone_to_blog_id the blog id which we are copy to
     *  @params string $type the template name
     */
    public static function clone_blog( $clone_from_blog_id, $clone_to_blog_id , $type ){

        $clone_from_blog_id = intval($clone_from_blog_id);
        $clone_to_blog_id = intval($clone_to_blog_id);

        $blogname = get_blog_option($clone_to_blog_id,'blogname');
        $description = get_blog_option($clone_to_blog_id,'blogdescription');
        $admin_email = get_blog_option($clone_to_blog_id,'admin_email');

        $user_id = get_current_user_id();

        add_user_to_blog($clone_from_blog_id, $user_id, 'administrator');

        if(!class_exists('MUCD_Duplicate')) {
            wp_die("plugin multisite-clone-duplicator is not installed or activated");
        }

        if( $clone_from_blog_id == 0 || $clone_to_blog_id < 2 || $clone_from_blog_id == $clone_to_blog_id ){

            die('wp_clone_error');

            return new WP_Error('wp_clone_error', 'Nicht zulässige Auswahl');

        }

        MUCD_Files::copy_files($clone_from_blog_id, $clone_to_blog_id);
        MUCD_Data::copy_data($clone_from_blog_id, $clone_to_blog_id);
        //MUCD_Duplicate::copy_users($clone_from_blog_id, $clone_to_blog_id);

        remove_user_from_blog($user_id, $clone_from_blog_id);

        update_blog_option( $clone_to_blog_id, 'mucd_duplicable', "no");
        update_blog_option( $clone_to_blog_id, 'rw_blog_wizard_type', $type);

        global $wpdb;
        $new_table_prefix = $wpdb->get_blog_prefix( $clone_to_blog_id );
        $sql = "UPDATE {$new_table_prefix}options SET option_value='{$blogname}' WHERE option_name = 'blogname';";
        $wpdb->query( $sql );
        $sql = "UPDATE {$new_table_prefix}options SET option_value='{$description }' WHERE option_name = 'blogdescription';";
        $wpdb->query( $sql );
        $sql = "UPDATE {$new_table_prefix}options SET option_value='{$admin_email }' WHERE option_name = 'admin_email';";
        $wpdb->query( $sql );


        wp_cache_flush();

        return '';


    }

}
