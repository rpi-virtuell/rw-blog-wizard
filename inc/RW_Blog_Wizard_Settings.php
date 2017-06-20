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
    static public function get_blog_default_type() {
        return get_site_option( 'rw_blog_wizard_type', 'blog' );
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
                __('Remote Auth Client', RW_Blog_Wizard::$textdomain ),
                'manage_network_options',
                RW_Blog_Wizard::$plugin_base_name,
                array( 'RW_Blog_Wizard_Settings','create_options')
            );

        }else{

            add_options_page(
                'Blog Wizard',
                __('Remote Auth Client', RW_Blog_Wizard::$textdomain ),
                'manage_options',
                RW_Blog_Wizard::$plugin_base_name,
                array( 'RW_Blog_Wizard_Settings', 'create_options' )
            );

        }


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
            <p><?php _e( 'Define Templates', RW_Blog_Wizard::$textdomain ); ?></p>
            <form method="POST" action="<?php echo $form_action; ?>"><fieldset class="widefat">

                    <?php
                    if(is_multisite()){
                        wp_nonce_field('rw_blog_wizard_network_settings');
                    }else{
                        settings_fields( 'rw_blog_wizard_options' );
                    }

                    $blog_list = get_sites(  array(
                        'search'=> 'template-'
                    ));

                    foreach ($blog_list AS $blog) {
                        $blog_id = get_object_vars($blog)["blog_id"];
                        $domain = get_object_vars($blog)["domain"];
                        $path = get_object_vars($blog)["path"];
                        $blog_name = get_blog_details($blog_id)->blogname;

                        echo $blog_name.': '.$domain.$path.'<br />';
                    }




                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="rw_blog_wizard_type"><?php _e( 'Typ', RW_Blog_Wizard::$textdomain ); ?></label>
                            </th>
                            <td>
                                <input id="rw_blog_wizard_type" class="regular-text" type="text" value="<?php echo self::get_blog_default_type(); ?>" aria-describedby="blog type" name="rw_blog_wizard_type">
                                <p id="blog-type-description" class="description"><?php _e( 'Choose a Usecase', RW_Blog_Wizard::$textdomain); ?></p>
                            </td>
                        </tr>


                    </table>

                    <br/>
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes' )?>" />
            </form>
        </div>
        <?php
    }


}
