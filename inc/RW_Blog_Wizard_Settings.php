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
        self::set_blog_type();
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

            $slug = strtolower('template-' . preg_replace('[^a-z0-9-]','',$new_blog) );

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

        return get_option( 'rw_blog_wizard_type' );
    }

    static public function add_blog_template_form() {

        if(is_network_admin()):?>

            <form method="POST" action="<?php echo admin_url('admin-post.php?action=rw_blog_wizard_network_settings') ?>">
                <h2>Vorlage für neue Websites erstellen</h2>
                <table>
                    <tr>
                        <td>Name</td>
                        <td>
                            <input name="new-blog-type" value="" placeholder="Neuer Blog Typ">
                            <br>
                            <span>Gib eine kurze und aussgekräftige Bezeichnung für den neuen Bblog Typ an</span>
                            <?php  wp_nonce_field('rw_blog_wizard_network_settings'); ?>
                        </td>

                    </tr>
                </table>
                <input type="submit" class="button-primary" value="<?php _e('Add Blog' )?>" />
            </form>
            <hr>
        <?php endif;

    }

    /**
     * Returns a Blog-Typ Description from the instruction page
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */


    static public function get_template_description($blog_id, $checked) {

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


        $info = $checked?'<br><br><span>Aktuelle Konfiguration<span>':'';


        return '
        <td><label for="blog-'.$blog_id.'"><b style="font-size: 3em">'. $blog_name . '</b>'.$info.'</label></td>
        <td class="thumbnail"><label for="blog-'.$blog_id.'">'.$thumb.'</label></td>
        <td><label for="blog-'.$blog_id.'">'.do_shortcode( $content, true) .'<p>'.$edit_link.'</p></label></td>
        ';
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


        $select_options = '
                <style>tr.checked td{ background-color: #fff; }</style>
                <table class="rw-blog-types" style="width:100%">
                <tr>
                    <th>&nbsp;</th>
                    <th colspan="2"><b style="font-size: 1.8em">Typ</b></th>
                    <th><b style="font-size: 1.8em">Wozu sich dieser Typ besonders eignet</b></th>
                </tr>
                <tr>
                    <td colspan="4" style="border-top: 2px solid darkgray "></td>    
                </tr>
                ';

        $blog_type = self::get_blog_type();

        foreach ($blog_list AS $blog) {
            $blog_id = get_object_vars($blog)["blog_id"];
            $blog_name = get_blog_details($blog_id)->blogname;
            $path = str_replace('/','',get_blog_details($blog_id)->path);
            $slug = strtolower(str_replace('template-','', $path));
            $checked = ($blog_type == $slug )? 'checked': '';


            $select_options .= '<tr class="'.$checked.'">';
            if(!$network){
                $select_options .= '<td><input type="radio" value="'.$slug.'" id="blog-'.$blog_id.'" name="rw_blog_wizard_type" '.$checked.'></td>';
            }else{
                $select_options .= '<td></td>';
            }
            $select_options .= self::get_template_description($blog_id, $checked?true:false );
            $select_options .= '</tr>';
        }
        $select_options .= '
                <tr>
                    <td colspan="4" style="border-bottom: 2px solid darkgray "></td>    
                </tr>
            <table>';


        ?>
            <form method="POST" action="options.php">
                <fieldset class="widefat">
                    <?php settings_fields( 'rw_blog_wizard_options' )?>
                    <?php do_settings_sections( 'rw_blog_wizard_options' ); ?>
                    <?php echo $select_options; ?>
                </fieldset>
                <input type="submit" class="button-primary" name="rw-blog-reset" value="Webseite formatieren und installieren" title="Alle Inhalte gehen verloren" /> &nbsp;
                <input type="submit" class="button-secondary" name="rw-blog-config" value="Nur die Konfiguration ändern" title="Inhalte werden nicht gelöscht." /> &nbsp;
                <button class="button" onclick="location.href='?'">Abbrechen</button>
           </form>
        <?php
    }


    static public function set_blog_type(){

        if(!current_user_can('manage_options')) wp_die('FU');

        $blog_type = isset($_POST['rw_blog_wizard_type'])? trim($_POST['rw_blog_wizard_type']):false ;
        $path = '/template-'. $blog_type.'/';

        if( isset( $_POST[ 'rw_blog_wizard_type' ] )  ) {

            $template_blogs =  get_sites(array( 'path' => $path ));

            if(count($template_blogs)==1){
                $template_blog = $template_blogs[0];
            }

            if(!$template_blog){
                var_dump( new WP_Error('rw-wizzard-clone-error', 'Die Vorlage konte nicht ermittelt werden. Es wurden keine Änderungen vorgenommen.', $_POST[ 'rw_blog_wizard_type' ] ) );
                die();
            }


            if(isset( $_POST[ 'rw-blog-reset' ] )  && $template_blog->blog_id > 0 ){

                RW_Blog_Wizard_Core::clone_blog($template_blog->blog_id ,get_current_blog_id(), true);

            }elseif(isset( $_POST[ 'rw-blog-config' ] )){
                RW_Blog_Wizard_Core::clone_blog($template_blog->blog_id ,get_current_blog_id());
            }
        }


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
                'Einrichtiungshilfe',
                'Einrichtiungshilfe',
                'manage_network_options',
                RW_Blog_Wizard::$plugin_base_name,
                array('RW_Blog_Wizard_Settings', 'create_options')
            );

            add_submenu_page(
                'settings.php',
                'Erweiterungen',
                'Erweiterungen',
                'manage_network_options',
                'erweiterungen',
                array('RW_Blog_Wizard_Settings', 'plugin_options')
            );

        }

        add_options_page(
            'Einrichtiungshilfe',
            'Einrichtiungshilfe',
            'manage_options',
            RW_Blog_Wizard::$plugin_base_name,
            array( 'RW_Blog_Wizard_Settings', 'create_options' )
        );
        add_options_page(
            'Erweiterungen',
            'Erweiterungen',
            'manage_options',
            RW_Blog_Wizard::$plugin_base_name,
            array( 'RW_Blog_Wizard_Settings', 'plugin_options' )
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

        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        ?>
        <div class="wrap"  id="rw_blog_wizard_main_settings">
            <h2>Einrichtungshilfe</h2>
            <?php  if(is_network_admin()): ?>
                <h1>Vorkonfigurierte Webseiten</h1>
                <p>Hier kannst du weitere Vorlagen generien, die als Kopiervorlage für User dienen, die Ihr blog neu erstellen wollen.</p>
            <?php else: ?>
                <?php if(get_option('rw_blog_wizard_type')):?>
                    <h2>Alles Zurücksetzen: Webseite neu installieren</h2>
                    <div class="notice error">
                        <p>
                            Du hast deine Webseite bereits mit der Einrichtungshilfe konfiguriert!<br>
                            Du kannst hier erneut <strong>alle deine Einstellungen und Inhalte löschen und</strong> deine Website <strong>mit neuen Einstellungen aufsetzen</strong>.
                        </p>
                    </div>
                <?php else: ?>
                    <h2>Wähle einen geeigneten Typ für deine Webseite</h2>
                <?php endif; ?>
                <p>
                    Wofür möchtest du deine Seite nutzen? <br>
                    Wähle aus den folgenden Beschreibungen eine aus, die am ehesten deinen Zwecken entsprechen wird. <br>
                    Anschließend klicke auf "Änderungen übernehmen". Deine Webseite wird dann entsprechend deiner Auswahl konfiguriert und du kannst loslegen.
                </p>

            <?php endif; ?>
            <hr />
            <?php self::add_blog_template_form();?>
            <?php self::blog_template_list();?>

        </div>
        <?php
    }


    static public function plugin_options(){
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        $the_plugs = get_site_option('active_sitewide_plugins');
        ?>
        <div class="wrap"  id="rw_blog_wizard_main_settings">

            <h2>Erweiterungen</h2>

            <table class="form-table">

                <?php

                $network_activated_plugins=array();
                foreach($the_plugs as $key => $value) {
                    $network_activated_plugins[] = $key;
                }


                $all_plugins = get_plugins();
                foreach($all_plugins as $plugin_file=>$plugin_obj){
                    if(!in_array($plugin_file, $network_activated_plugins)):?>
                    <form>
                        <tr>
                            <td><?php echo $plugin_obj['Title']; ?><br><?php echo $plugin_file; ?></td>
                            <td style="width:40%">
                                <textarea style="width:100%" name="description"><?php echo $plugin_obj['Description']; ?></textarea>
                            </td>
                            <td style="width:20%">
                                <textarea style="width:100%" name="required_plugins" placeholder="zusätzliche plugins installieren (eines pro Zeile: dir/file.php)"></textarea>
                            </td>

                            <td>
                                <select name="group">
                                    <option value="seo">SEO</option>
                                    <option value="content">Content</option>
                                </select>
                            </td>
                            <td><input type="checkbox" name="allowed">freischalten</td>
                            <td>
                                <input type="hidden" name="plugin-file" value="<?php echo $plugin_file;?>">
                                <input type="submit" value="Speichern">
                            </td>
                        </tr>
                    <form>
                    <?php endif;
                }
                ?>
            </table>
        </div>
        <?php
    }
}
