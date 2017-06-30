<?php
/**
 * Class RW_Blog_Wizard_Settings
 *
 * Creates s setting page and a menu entry in wp backend
 *
 * @package      RW Blog Wizard
 * @author       Joachim Happel
 * @license      GPL-2.0+
 * @link         https://github.com/rpi-virtuell/rw-blog-wizard
 * @since        0.0.2
 */
class RW_Blog_Wizard_Settings {

    /**
     * Register all settings
     *
     * Register all the settings, the plugin uses.
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */
    static public function register_settings() {

        register_setting( 'rw_blog_wizard_options', 'rw_blog_wizard_type' );
        register_setting( 'rw_blog_wizard_options', 'rw_blog_wizard_plugins' );
        register_setting( 'rw_blog_wizard_options', 'rw_blog_wizard_options' );

    }
    /**
     * save all network settings
     *
     * Register all the settings, the plugin uses.
     *
     * @since   0.2.0
     * @access  public
     * @static
     * @return  void
     * @useaction  admin_post_rw_blog_wizard_network_settings
     */
    static public function network_settings() {

        check_admin_referer('rw_blog_wizard_network_settings');

        if(!current_user_can('manage_network_options')) wp_die('FU');

        $new_blog = isset($_POST['new-blog-type'])? trim($_POST['new-blog-type']):false ;

        if($new_blog){
            // create a new blog if not exits

            $new_blog  = str_replace('template-','', $new_blog);

            $slug = 'template-' . preg_replace('[^a-z0-9-]','',$new_blog);

            $path = '/'.$slug.'/';

            $sites = get_sites(array(
                'path'=> $path
            ));

            if(count($sites) > 0 ){
                wp_redirect(admin_url('network/settings.php?blog_wizard_notice=site_exists&slug='.$new_blog.'&page='.RW_Blog_Wizard::$plugin_base_name .''));
                exit;
            }


            $id = wpmu_create_blog( get_current_site()->domain, $path,  $new_blog, get_current_user_id() );

            if(!$id){
                wp_redirect(admin_url('network/settings.php?blog_wizard_notice=not_created&slug='.$new_blog.'&page='.RW_Blog_Wizard::$plugin_base_name .''));
                exit;
            }


        }

        wp_redirect(admin_url('network/settings.php?blog_wizard_notice=site_created&page='.RW_Blog_Wizard::$plugin_base_name));
        exit;


        $options = array(
            'rw_blog_wizard_type',
            'rw_blog_wizard_plugins',
            'rw_blog_wizard_options'
        );

        foreach($options as $option){
            if( isset( $_POST[ $option ] ) ) {
                update_site_option( $option, ( $_POST[$option ] ) );
            }else{
                delete_site_option( $option );
            }
        }



    }

    static public function admin_notice() {

        if(isset($_GET['blog_wizard_notice'])){
            switch($_GET['blog_wizard_notice']){
                case 'site_exists':
                    $message = 'Die Vorlage <strong><em>'.$_GET['slug'].'</em></strong> ist bereits vorhanden';
                    $notice_type = 'error';
                    break;
                case 'not_created':
                    $message = 'Es gab einem unbekanten Fehler ebim erstellen der neuen Seite. Die Vorlage konnte nicht erstellt werden.';
                    $notice_type = 'error';
                    break;
                case 'site_created':
                    $message = 'Die Vorlage wurde erfolgreich erstellt und kann nun eingerichtet werden';
                    $notice_type = 'success';
                    break;
                default:
                    $message = 'Unbekannter Fehler';
                    $notice_type = 'error';
            }
            ?>
            <div class="notice-<?php echo $notice_type;?> notice">
                <p><?php echo $message; ?></p>
            </div>
            <?php
        }
    }
    /**
     * Add a settings link to the  pluginlist
     *
     * @since   0.1
     * @access  public
     * @static
     * @param   string array links under the pluginlist
     * @return  array
     */
    static public function plugin_settings_link( $links ) {
        if(is_multisite()){
            $settings_link = '<a href="network/settings.php?page=' . RW_Blog_Wizard::$plugin_base_name . '">' . __( 'Settings' )  . '</a>';
            if(is_super_admin()){
                array_unshift($links, $settings_link);
            }
        }else{
            $settings_link = '<a href="options-general.php?page=' . RW_Blog_Wizard::$plugin_base_name . '">' . __( 'Settings' )  . '</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    /**
     * Get the Default type
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  string
     */
    static public function get_blog_type() {
        return get_site_option( 'rw_blog_wizard_type', 'Blog' );
    }

    /**
     * Returns a Blog-Typ Description from the instruction page
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */

    static public function get_template_description($blog_id) {

        global $switched;

        switch_to_blog($blog_id); //switched to blog id 2, for example


        $url = home_url() .'/instruction/';
        $blog_name = get_blog_details($blog_id)->blogname;

        // Get latest Post
        $postid = url_to_postid( $url  );

        $edit_url = get_edit_post_link( $postid );

        $edit_link = (is_super_admin() || is_admin())? '<a href="'.$edit_url.'">Bearbeiten</a>': '';

        if(! $postid ){

            $postid = wp_insert_post( array(
                'post_name'    => wp_strip_all_tags( 'instruction' ),
                'post_title'    => 'Instruktionen zu dieser Webseite',
                'post_content'  => '<h1>Beschreibung des Templates fehlt</h1>',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'   => 'page'
            ));

        }
        $post = get_post( $postid );
        $content =  apply_filters( 'the_content', $post->post_content );

        if(has_post_thumbnail($postid)){
            $size = 'thumbnail' ;
            $thumb_id =  get_post_meta( $postid, '_thumbnail_id', true );
            $thumb_url= wp_get_attachment_image_url( $thumb_id, $size );
            $thumb = '<div style="width:200px"><img src="'.$thumb_url.'" style="max-width: 200px ; max-height: 200px; width:100%"></div>';

        }else{
            $thumb = '<div style="width:200px"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Dummy_Logo.jpg/120px-Dummy_Logo.jpg" style="width:100%"></div>';
        }


        restore_current_blog(); //switched back to main site

        return '
            <table style="margin:0; padding: 0">
                <tr>
                    <td class="thumbnail">'.$thumb.'</td>
                    <td>'.do_shortcode( $content, true) .'<p>'.$edit_link.'</p></td>
                </tr>
            </table>
       
        ';
    }

    static public function add_blog_template_form() {
        if(is_network_admin()){?>
            <h2>Vorlage für neue Websites erstellen</h2>
                    <table>
                        <tr>
                            <td>Name</td>
                            <td>
                                <input name="new-blog-type" value="" placeholder="Neuer Blog Typ">
                                <br>
                                <span>Gib eine kurze und aussgekräftige Bezeichnung für den neuen Bblog Typ an</span>
                            </td>

                        </tr>
                    </table>
                    <input type="submit" class="button-primary" value="<?php _e('Add Blog' )?>" />
                    <hr>
        <?php }
    }

    /**
     * Returns a list of Blog-Types
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */

    static public function blog_template_list() {

        $network = is_network_admin();

        $blog_list = get_sites(  array(
            'search'=> 'template-'
        ));


        $select_options = '<h2>Vorlage erstellen</h2><table class="rw-blog-types">';

        $blog_type = self::get_blog_type();

        foreach ($blog_list AS $blog) {
            $blog_id = get_object_vars($blog)["blog_id"];
            $blog_name = get_blog_details($blog_id)->blogname;
            $checked = ($blog_type == $blog_name )? 'checked': '';


            $select_options .= '<tr>';
            if(!$network){
                $select_options .= '<td><input type="radio" value="'.$blog_id.'" id="blog-'.$blog_id.'" name="blogtype" '.$checked.'></td>';
            }
            $select_options .= '<td><b style="font-size: 3em">'. $blog_name.'</b></td>';
            $select_options .= '<td style="margin:0; padding: 0"><label for="blog-'.$blog_id.'">'.self::get_template_description($blog_id).'</label></td></tr>';
        }
        $select_options .= '<table>';


        echo  $select_options;
    }

    /**
     * Generate the options menu page
     *
     * Generate the options page under the options menu
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */


    static public function options_menu() {
        if(is_multisite()) {

            add_submenu_page(
                'settings.php',
                'Blog Wizard',
                __('Blog Wizard', RW_Blog_Wizard::$textdomain),
                'manage_network_options',
                RW_Blog_Wizard::$plugin_base_name,
                array('RW_Blog_Wizard_Settings', 'create_options')
            );

        }

        add_options_page(
            'Blog Wizard',
            __('Blog Wizard', RW_Blog_Wizard::$textdomain ),
            'manage_options',
            RW_Blog_Wizard::$plugin_base_name,
            array( 'RW_Blog_Wizard_Settings', 'create_options' )
        );




    }


    /**
     * Generate the options page for the plugin
     *
     * @since   0.1
     * @access  public
     * @static
     *
     * @return  void
     */
    static public function create_options() {
        if(is_network_admin()){
            $description = __( 'Preconfigured websites for different requirements and projects', RW_Blog_Wizard::$textdomain );

        }else{
            $description = 'Wofür möchtest du neue Seite nutzen? <br>Wähle aus den folgenden Beschreibungen eine aus, die am ehesten deinen Zwecken entsprechen wird. <br>Anschließend klicke auf "Änderungen übernehmen". Deine Webseite wird dann entsprechend deiner Auswahl vorkonfiguriert und du kannst sofot loslegen.';
        }


        if(is_multisite()){
            $form_action = admin_url('admin-post.php?action=rw_blog_wizard_network_settings');
        }else{
            $form_action = 'options.php';
        }

        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }


        ?>
        <div class="wrap"  id="rw_blog_wizard_main_settings">
            <h2><?php _e( 'Blog Wizard ', RW_Blog_Wizard::$textdomain ); ?><?php echo ( is_network_admin() ) ? __( 'Create Templates'):'';?></h2>
            <p><?php echo $description ?></p>
            <form method="POST" action="<?php echo $form_action; ?>"><fieldset class="widefat">



                    <?php
                    if(is_multisite()){
                        wp_nonce_field('rw_blog_wizard_network_settings');

                    }else{
                        settings_fields( 'rw_blog_wizard_options' );
                    }
                    ?>
                    <?php self::add_blog_template_form();?>
                    <?php self::blog_template_list();?>

            </form>
        </div>
        <?php
    }


}
