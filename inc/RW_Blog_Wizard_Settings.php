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

        wp_redirect(admin_url('network/settings.php?page='.RW_Blog_Wizard::$plugin_base_name));
        exit;

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
        return get_option( 'rw_blog_wizard_type', 'Blog' );
    }

    static public function get_template_description($blog_id) {

        global $switched;

        switch_to_blog($blog_id); //switched to blog id 2, for example


        $url = home_url() .'/instruction/';
        $blog_name = get_blog_details($blog_id)->blogname;

        // Get latest Post
        $postid = url_to_postid( $url  );

        if(! $postid ){

            $postid = wp_insert_post( array(
                'post_title'    => wp_strip_all_tags( 'instruction' ),
                'post_content'  => '<h1>Beschreibung des Templates fehlt</h1><p>Bitte <a href="'.$url.'">diese Seite bearbeiten</a></p>',
                'post_status'   => 'publish',
                'post_author'   => 1
            ));

        }
        $post = get_post( $postid );
        $content =  apply_filters( 'the_content', $post->post_content );

        if(has_post_thumbnail($postid)){
            $size = 'thumbnail' ;
            $thumb_id =  get_post_meta( $postid, '_thumbnail_id', true );
            $thumb_url= wp_get_attachment_image_url( $thumb_id, $size );
            $thumb = '<img src="'.$thumb_url.'" style="max-width: 250px ; max-height: 250px;">';

        }else{
            $thumb = '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Dummy_Logo.jpg/120px-Dummy_Logo.jpg">';
        }


        restore_current_blog(); //switched back to main site

        return '
            <table>
                <tr>
                    <td class="thumbnail">'.$thumb.'</td>
                    <td>'.do_shortcode( $content, true) .'</td>
                </tr>
            </table>
       
        ';

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
        if(is_multisite()){

            add_submenu_page(
                'settings.php',
                'Blog Wizard',
                __('Blog Wizard', RW_Blog_Wizard::$textdomain ),
                'manage_network_options',
                RW_Blog_Wizard::$plugin_base_name,
                array( 'RW_Blog_Wizard_Settings','create_options')
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
            <h2><?php _e( 'Blog Wizard ', RW_Blog_Wizard::$textdomain ); ?><?php _e( 'Settings');?></h2>
            <p><?php _e( 'Wähle einen Typ, der am ehehesten der geplanten Anwendung entspricht', RW_Blog_Wizard::$textdomain ); ?></p>
            <form method="POST" action="<?php echo $form_action; ?>"><fieldset class="widefat">

                    <h2>Neue Vorlage generieren</h2>
                    <table>
                        <tr>
                            <td>Name</td>
                            <td><input name="new-blog-type" value=""></td>
                        </tr>
                    </table>


                    <?php

                    if(is_multisite()){
                        wp_nonce_field('rw_blog_wizard_network_settings');
                    }else{
                        settings_fields( 'rw_blog_wizard_options' );
                    }

                    $blog_list = get_sites(  array(
                        'search'=> 'template-'
                    ));

                    $select_options = '<table border="1">';

                    $blog_type = self::get_blog_type();

                    foreach ($blog_list AS $blog) {
                        $blog_id = get_object_vars($blog)["blog_id"];
                        $blog_name = get_blog_details($blog_id)->blogname;

                        $checked = ($blog_type == $blog_name )? 'checked': '';

                        $select_options .= '<tr>
                                                <td><input type="radio" value="'.$blog_id.'" id="blog-'.$blog_id.'" name="blogtype" '.$checked.'></td>
                                                <td><label for="blog-'.$blog_id.'">'. $blog_name.'</label></td>
                                                <td>'.self::get_template_description($blog_id).'</td>
                                            </tr>';
                    }

                    $select_options .= '<table>';

                    ?>
                    <?php echo $select_options;?>
                    <br/>
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes' )?>" />
            </form>
        </div>
        <?php
    }


}
