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
    static public function register_blog_type() {

		if( current_user_can('administrator')){
			$admin_url = admin_url('index.php?blog_wizard_notice=');
		
			register_setting( 'rw_blog_wizard_options', 'rw_blog_wizard_type' );
			
			if(self::set_blog_type()){
				wp_redirect($admin_url.'site_cloned');
				exit;
			}	
		}
	
        
		


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

        $blog_slug = sanitize_title($new_blog);

        if($new_blog){
            // create a new blog if not exits

            $blog_slug  = str_replace('template-','', $blog_slug);

            $path = '/template-' .$blog_slug.'/';

            $sites = get_sites(array(
                'path'=> $path
            ));

            if(count($sites) > 0 ){
                wp_redirect(admin_url('network/settings.php?blog_wizard_notice=site_exists&slug='.$blog_slug.'&page='.RW_Blog_Wizard::$plugin_base_name .''));
                exit;
            }

            $id = wpmu_create_blog( get_current_site()->domain, $path,  $new_blog, 1, array('mature' => 1, 'public' => 0),1 );

            if(!$id){
                wp_redirect(admin_url('network/settings.php?blog_wizard_notice=not_created&slug='.$new_blog.'&page='.RW_Blog_Wizard::$plugin_base_name .''));
                exit;
            }


        }

        wp_redirect(admin_url('network/settings.php?blog_wizard_notice=site_created&page='.RW_Blog_Wizard::$plugin_base_name));
        exit;

    }


    /**
     * @link https://wordpress.stackexchange.com/questions/10309/how-to-display-wordpress-user-registration-form-in-front-end-of-the-website
     */
    static public function form_create_new_site(){
        if(!is_user_logged_in()){
            echo "Bitte zuerst anmelden";
        }
        ?>
        <form method="POST" action="<?php echo admin_url('admin-post.php?action=rw_blog_wizard_plugin_create_new_blog_from_template') ?>">
            <h2>Neue Websites erstellen</h2>
            <table>
                <tr>
                    <td>
                        Titel der Seite:
                    </td>
                    <td>
                        <input tape="hidden" name="url" value="<?php echo $_GET['template-url'];?>">

                        <?php  wp_nonce_field('rw_blog_wizard_plugin_create_new_blog_from_template'); ?>
                    </td>

                </tr>
                <tr>
                    <td>
                        Adresse:
                    </td>
                    <td>
                        <?php echo get_site_url(1);?><input type="text" name="slug" />
                    </td>

                </tr>
            </table>
            <input type="submit" class="button-primary" value="Erstell mir eine Webseite" />
        </form>
        <?php
    }

    static public function create_new_blog_from_template(){

        check_admin_referer('rw_blog_wizard_plugin_create_new_blog_from_template');

        $template_url = trim($_POST['url']);

        if(preg_match('#/template-[^/]+/#',$template_url, $matches)>0){
            $template_path =  $matches[0];
        }

        $from_blog = false;
        $slug = sanitize_title($_POST['slug']);
        $from_blog_result = get_sites( array('path'=>$template_path) );

        if(count($from_blog_result)>0){
            $from_blog = $from_blog_result[0];
        }
        if($from_blog){

            $user = wp_get_current_user();

            $user_id = get_current_user_id();

            $from_blog_id = $from_blog->blog_id;

            add_user_to_blog($from_blog_id, $user_id, 'administrator');

            $data = array();

            $data['email'] = $user->user_email;
            $data['domain'] = $slug;
            $data['newdomain'] = $from_blog->domain;
            $data['path'] = "/{$slug}/";
            $data['title'] = $_POST['title'];
            $data['from_site_id'] = 1;
            $data['keep_users'] = false;
            $data['copy_files'] = 'yes';
            $data['public'] = 1;
            $data['network_id'] = 1;
            $data['blog_type'] = $_POST['blog_type'];

            $to_site_id = wpmu_create_blog( $data['newdomain'] , $data['path'], $data['title'], $user_id , array(
                    'public' => $data['public']
            ) );
            MUCD_Duplicate::bypass_server_limit();
            MUCD_Files::copy_files($from_blog_id, $to_site_id);
            MUCD_Data::copy_data($from_blog_id, $to_site_id);

            remove_user_from_blog($user_id, $from_blog_id);

            update_blog_option( $to_site_id, 'mucd_duplicable', "no");
            update_blog_option( $to_site_id, 'rw_blog_wizard_type', $data['blog_type'] );
/*
            global $wpdb;
            $new_table_prefix = $wpdb->get_blog_prefix( $to_site_id );
            $sql = "UPDATE {$new_table_prefix}options SET option_value='{$data['title']}' WHERE option_name = 'blogname';";
            $wpdb->query( $sql );
            $sql = "UPDATE {$new_table_prefix}options SET option_value='{$data['email']}' WHERE option_name = 'admin_email';";
            $wpdb->query( $sql );

*/
            wp_cache_flush();
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
                case 'site_cloned':
                    $message = 'Deine Seite wurde erfolgreich konfiguriert und kann nun mit Inhalt gefüllt werden. Happy Blogging!';
                    $notice_type = 'success';
                    break;
                case 'plugin_activated':
                    $message = 'Die Erweiterung wurde erfolgreich installiert und kann nun verwendet werden.';
                    $notice_type = 'success';
                    break;
                case 'plugin_deactivated':
                    $message = 'Die Erweiterung wurde deinstalliert';
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

    static public function form_add_new_template() {

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
        $blog_name = get_blog_option($blog_id,'blogname');

        // get instruction page
        $postid = url_to_postid( $url  );

        $edit_url = get_edit_post_link( $postid );

        $edit_link = (is_super_admin() && is_network_admin())? '<a href="'.$edit_url.'">Bearbeiten</a>': '';

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

    static public function loop_blog_templates() {




        $network = is_network_admin();

        if($network){
            $args = array(
                'search'=> 'template-'
            );
        }else{
            $args =  array(
                'search'=> 'template-',
                'mature'=>0
            );
        }

        $blog_list = get_sites(  $args );



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
            $checked = ($blog_type == $slug && !$network)? 'checked': '';


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

        if($network) {
            echo $select_options;
        }else{
        ?>
            <form method="POST" action="options.php">
                <fieldset class="widefat">
                    <?php settings_fields( 'rw_blog_wizard_options' )?>
                    <?php do_settings_sections( 'rw_blog_wizard_options' ); ?>
                    <?php echo $select_options; ?>
                </fieldset>
                <input type="submit" class="button-primary" name="rw-blog-reset" value="Webseite formatieren und installieren" title="Alle Inhalte gehen verloren" /> &nbsp;
                <button class="button" onclick="location.href='?'">Abbrechen</button>
           </form>
        <?php
        }
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


            if(isset( $_POST[ 'rw-blog-reset' ] )  && $template_blog->blog_id > 1 ){

                $blog_id = get_current_blog_id();

                RW_Blog_Wizard_Core::clone_blog($template_blog->blog_id ,$blog_id, $blog_type);

                return true;

            }

        }
        return false;


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


    static public function admin_menu() {
        if(is_multisite()) {

            add_submenu_page(
                'settings.php',
                'Einrichtungshilfe',
                'Einrichtungshilfe',
                'manage_network_options',
                RW_Blog_Wizard::$plugin_base_name,
                array('RW_Blog_Wizard_Settings', 'display_blog_templates')
            );

            add_submenu_page(
                'settings.php',
                'Erweiterungen',
                'Erweiterungen',
                'manage_network_options',
                'erweiterungen',
                array('RW_Blog_Wizard_Settings', 'edit_and_combine_plugins')
            );

        }

        if(!is_network_admin()){
            $blog = get_blog_details();

            if(strpos ( $blog->path, 'template-')=== false  && get_current_blog_id() > 1 && $blog->post_count < 3 ) {

                add_options_page(
                    'Einrichtiungshilfe',
                    'Einrichtiungshilfe',
                    'manage_options',
                    RW_Blog_Wizard::$plugin_base_name,
                    array('RW_Blog_Wizard_Settings', 'display_blog_templates')
                );
            }


            add_menu_page(
                'Erweiterungen',
                'Erweiterungen',
                'manage_options',
                'rw-addons',
                array( 'RW_Blog_Wizard_Settings', 'plugin_selector' ),
                'dashicons-hammer',
                80
            );
        }

    }


    /**
     * Displays the settings page "Einrichtungshilfe"
     *
     * @since   0.1
     * @access  public
     * @static
     *
     * @return  void
     */
    static public function display_blog_templates() {

        if( get_current_blog_id() == 1 && !is_network_admin() ){

            echo '
            <div class="notice error">
                Für die Hauptseite steht keine Einrichtshilfe zur Verfügung.
            </div>';
            exit;
        }

        $blog = get_blog_details();

        if(strpos ( $blog->path, 'template-')> 0 ){
            echo '
            <div class="notice error">
                Du kannst ein Template Blog nicht überschreiben.
            </div>';
            exit;
        }


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
            <?php self::form_add_new_template();?>
            <?php self::loop_blog_templates();?>

        </div>
        <?php
    }

    static public function get_all_plugins(){

        $all_plugins = get_plugins();
        $plugins = array_keys($all_plugins);

        return $plugins;
    }
    static public function get_active_plugins(){

        $site_plugs = get_site_option('active_sitewide_plugins');
        $site_plugs = array_keys($site_plugs);

        $active_plugins = get_option('active_plugins');
        $plugins = array_merge($site_plugs, $active_plugins);

        return $plugins;
    }

    /**
     * Display all Plugins in single Forms, so you can change the description and create plugin bundles
     * @since   0.1
     * @access  public
     * @static
     *
     */

    static public function edit_and_combine_plugins(){
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        $the_plugs = get_site_option('active_sitewide_plugins');
        $the_plugs = array_keys($the_plugs);

        $posts = get_posts(array(
            'post_type'=>'rw-plugin',
            'post_status'=>'any',
            'numberposts'       => -1,
            'orderby'=>'post_title',
            'order'=>'ASC'
        ));
        $sub_plugs=$group_options=array();
        foreach ($posts as $p){
			
	        $plugs = get_metadata('post',$p->ID,'plugin_collection', true);

			//var_dump($plugs);
			
	        if(!empty($plugs)){
                $included = explode("\n",$plugs);
                foreach ( $included as $incplugs ){
                    $sub_plugs[] = trim($incplugs);
                }
            }
            $plugins[trim($p->post_title)] = $p;

        }

        $hide_plugins = array_unique(array_merge($sub_plugs,$the_plugs));



        $group_options = get_posts(array(
            'post_type' => 'rw-plugingroup',
            'post_status' => 'any',
            'numberposts'       => -1,
            'orderby'=>'post_title',
            'order'=>'ASC'
        ));

        //echo '<pre>'; var_dump($hide_plugins); echo '</pre>';
        //echo '<pre>'; var_dump($group_options); echo '</pre>';

        $all_plugins = get_plugins();

        ?>
        <div class="wrap"  id="rw_blog_wizard_main_settings">

            <h2>Erweiterungen</h2>

            <table class="form-table">

                <?php


                foreach($all_plugins as $plugin_file=>$plugin_obj){
                    //echo '<pre>'; var_dump($plugin_obj); echo '</pre>';
                    if(!in_array($plugin_file, $hide_plugins)):

                        if(isset($plugins[$plugin_file])){

                            $p = $plugins[$plugin_file];
	                        $description = $p->post_content;
                            $plugin_url = get_post_meta($p->ID,'plugin_url',true);
                            $plugin_url = ($plugin_url)?$plugin_url:$plugin_obj['PluginURI'];
                            $plugin_title = $p->post_content_filtered;
                            $plugin_title = ($plugin_title)?$plugin_title:$plugin_obj['Title'];

	                        $required_plugins = get_post_meta($p->ID,'plugin_collection', true);
	                        //var_dump($required_plugins);
                            $options='';
                            foreach ($group_options as $go){
                                $plugin_group_id =  $go->ID;
                                $group_id = get_post_meta($p->ID,'plugin_group_id',true);

                                $selected = (intval($plugin_group_id) === intval($group_id))?'selected':'';
                                $options.='<option value="'.$go->ID.'" '.$selected.'>'.$go->post_title.'</option>';
                            }
                            $checked = $p->post_status=='publish'?'checked':'';
                            $has_parent = !empty($p->post_parent)?'green':'orange';
                        }else{
	                        //echo '<pre>'; var_dump($plugin_file); echo '</pre>';

	                        $plugin_url = $plugin_obj['PluginURI'];
                            $plugin_title = $plugin_obj['Title'];
                            $description = $plugin_obj['Description'];
                            $required_plugins = '';
                            $options='';
                            foreach ($group_options as $go){
                                $options.='<option value="'.$go->ID.'">'.$go->post_title.'</option>';
                            }
                            $checked = '';
                            $has_parent = 'darkred';
                        }




                        ?>

                        <tr>
                                <td><a name="rw-plugin-<?php echo $p->ID;?>"></a></td>
                                <td colspan="5"></td>
                        </tr>
                            <tr>
                                <form method="post" action="<?php echo admin_url('admin-post.php?action=rw_blog_wizard_plugin_options_action') ?>#rw-plugin-<?php echo $p->ID;?>">
                                <td>

                                    <strong style="font-size:1.2em; color:<?php echo $has_parent; ?>"><?php echo $plugin_title; ?></strong>
                                    <br>
                                    <?php echo $plugin_file; ?>
                                    <br>
                                    <input type="text" name="plugin-title" value="<?php echo $plugin_title; ?>">
                                </td>
                                <td style="width:40%">
                                    <textarea style="width:100%" name="description"><?php echo $description; ?></textarea>
                                    <input  type="text" name="url" value="<?php echo $plugin_url; ?>" style="width:100%">
                                </td>
                                <td style="width:20%">
                                    <textarea style="width:100%; height:80px" name="required_plugins" placeholder="Gemeinsam mit folgenden Plugins installieren (eines pro Zeile: dir/file.php)"><?php echo $required_plugins;?></textarea>
                                </td>

                                <td>
                                    <select name="rw-group" style="width:150px">
                                        <option></option>
                                        <?php echo $options ?>
                                    </select><br>
                                    <input  type="text" placeholder="Neue Kategorie" name="new-group" style="width:150px">
                                </td>
                                <td><input type="checkbox" name="allowed" <?php echo $checked; ?>>freischalten</td>
                                <td>
                                    <input type="hidden" name="plugin-file" value="<?php echo $plugin_file;?>">
                                    <input type="hidden" name="plugin-author" value="<?php echo $plugin_obj['AuthorName']; ?>">
                                    <?php  wp_nonce_field('rw_blog_wizard_plugin_options_action'); ?>
                                    <input type="submit" value="Speichern">
                                </td>
                                </form>
                            </tr>

                    <?php endif;
                }

                //echo '<pre>'; var_dump($plugin_obj); echo '</pre>';

                $p = get_page_by_title('rw-hidden-plugins',OBJECT, 'rw-plugin');
                if($p){
	                $post_meta = get_post_meta($p->ID,'plugin_collection', true);
	               // $plugs = explode("\n",$post_meta->plugin_collection);
	                $required_plugins = $post_meta;


                }else{
                    $required_plugins = '';
                }
                ?>
                <form method="post" action="<?php echo admin_url('admin-post.php?action=rw_blog_wizard_plugin_options_action') ?>">
                    <tr>
                        <td><strong style="font-size:1.2em">Verborgene Plugins</strong><br>Ein Plugin pro Zeile</td>
                        <td><strong>Diese Plugins sollen in der Auswahlliste nicht angezeigt werden:</strong></td>
                        <td>
                            <textarea style="width:100%; height:150px" name="required_plugins" placeholder="(pro Zeile: dir/file.php)"><?php echo $required_plugins;?></textarea>
                        </td>
                        <td colspan="2"></td>
                        <td>
                            <input type="hidden" name="plugin-file" value="rw-hidden-plugins">
                            <input type="hidden" name="description" value="Diese Plugins werden in der Auswahlliste nicht angezeigt">
                            <input type="hidden" name="plugin-title" value="Verborgene Plugins">
                            <?php  wp_nonce_field('rw_blog_wizard_plugin_options_action'); ?>
                            <input type="submit" value="Speichern">

                        </td>
                    </tr>
                </form>
            </table>
        </div>
        <?php
    } // end function edit_and_combine_plugins


    /**
     * saves custom plugin description and plugin groups in custom post types
     *
     * @since   0.1
     * @access  public
     * @static
     * @useaction hook admin_post_rw_blog_wizard_plugin_options_action
     */
    static public function edit_and_combine_plugins_action(){

        check_admin_referer('rw_blog_wizard_plugin_options_action');

        if(!current_user_can('manage_network_options')) wp_die('FU');

        $post = get_page_by_title($_POST['plugin-file'],OBJECT, 'rw-plugin');


        if(!empty(trim($_POST['new-group']))){
            $group_name = trim($_POST['new-group']);
            $group = sanitize_title($group_name);
            $group_id = false;
        }else{
            $group_id = $_POST['rw-group'];
        }


        if(intval($group_id) === 0){
            $groups = get_posts(array(
                    'title' => $group_name ,
                    'post_type' => 'rw-plugingroup',
                    'posts_per_page' => 1)
            );

            if(count($groups)>0){
                $plugin_group = $groups[0];
                $group_id = $plugin_group->ID;
            }else{
                $group_id = wp_insert_post(array(
                    'post_title'   => $group_name,
                    'post_name'   => $group,
                    'post_type' => 'rw-plugingroup',
                    'post_status' => 'publish'
                ));
            }
        }


        if($post!==null){
            wp_update_post(array(
                'ID'           => $post->ID,
                'post_content'  => $_POST['description'],
                'post_content_filtered'  => $_POST['plugin-title'],
                'post_status'   => $_POST['allowed']?'publish':'private',
                'post_excerpt'   => Null,
                'post_mime_type' => Null,
                'post_password' => Null,
                'post_parent' => $group_id?$group_id:0,
                'post_type'   => 'rw-plugin',
                'meta_input' => array(
                    'plugin_url' => isset($_POST['url'])?$_POST['url']:Null,
                    'plugin_author' =>isset( $_POST['plugin-author'])? $_POST['plugin-author']:Null,
                    'plugin_collection' =>isset( $_POST['required_plugins'] ) ? $_POST['required_plugins']:Null  ,
                    'plugin_group_id' =>$group_id
                ),
            ));
        }else{


            $postid = wp_insert_post( array(
                'post_title'    => $_POST['plugin-file'],
                'post_content_filtered'  => $_POST['plugin-title'],
                'post_content'  => $_POST['description']?$_POST['description']:'',
                'post_status'   => $_POST['allowed']?'publish':'private',
                'post_parent' => $group_id?$group_id:0,
                'post_type'   => 'rw-plugin',
                'meta_input' => array(
                    'plugin_url' => isset($_POST['url'])?$_POST['url']:Null,
                    'plugin_author' =>isset( $_POST['plugin-author'])? $_POST['plugin-author']:Null,
                    'plugin_collection' =>isset( $_POST['required_plugins'] ) ? $_POST['required_plugins']:Null  ,
                    'plugin_group_id' =>$group_id
                )

            ));



        }// end if




        /*
        //automatic dlete unused plugin groups
        $groups = get_posts(array(
            'post_type' => 'rw-plugingroup',
            'post_status' => 'any'
        ));

        foreach ($groups as $g){
            $plugns = get_posts(array(
                'meta_key' => 'plugin_group_id',
                'meta_value' => $g->ID,
                'post_type' => 'rw-plugin',
                'post_status' => 'any'
            ));

            if(count($plugns)<1){
                //var_dump('delete: '.$g->ID );
                wp_delete_post( $g->ID );
            }
        }
        //
        */

        wp_redirect(admin_url('network/settings.php?page=erweiterungen'));
        exit;




    }// end function edit_and_combine_plugins_action


    /**
     * @todo Aktivieren Button
     */
    static public function plugin_selector(){

      //  var_dump(admin_url('admin-post.php')); die();

        $installed_plugins = self::get_all_plugins();
        $active_plugins = self::get_active_plugins();



        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        switch_to_blog(1);
        $posts = get_posts(array(
            'post_type'=>'rw-plugin',
            'post_status'=>'publish',
            'numberposts'       => -1,
            'orderby'=>'post_title',
            'order'=>'ASC'
        ));
        $plugins = $groups = array();

        $groups = get_posts(array(
            'post_type' => 'rw-plugingroup',
            'post_status' => 'any',
            'numberposts'       => -1,
            'orderby'=>'post_title',
            'order'=>'ASC'
        ));


        foreach ($posts as $p){
            //$group_id =  get_post_meta($p->ID,'plugin_group_id',true);
            $group_id =  $p->post_parent;
            $plugins[$group_id][]=$p;
        }


        restore_current_blog();





        ?>
        <div class="wrap"  id="rw_blog_wizard_main_settings">
         <h2>Erweiterungen (Plugins)</h2>
        <div style="float: left; width:20%;min-width:300px; padding:0">
            <div style="margin:10px">
                <p>
                    Du brauchst weitere Funktionen? Hier findest du unter den verschiedenen Rubriken wertvolle Erweiterungen, die deine Webseite um viele zusätzliche Funktionen erweitern kann. <br>
                    Bedenke jedoch, dass dein System mit jeder aktivierten Erweiterung langsamer wird. </p>
                <p>
                    Fragen dazu gerne in unserem <a href="http://fragen.rpi-virtuell.de/">Offenen Hilfesystem</a>
                </p>
            </div>
        </div>
        <div style="width:70%; min-width:400px; padding-left:20px;float: right">
            <div id="accordion">
                <?php
                //var_dump($groups);
                $nonce = wp_create_nonce( 'rw_blog_wizard_activate_selected_plugin_bundle' );
                foreach($groups as $go):

                    if(isset($go->ID) && isset($plugins[$go->ID]) && count($plugins[$go->ID])>0): ?>
                        <h3><img src="<?php echo get_the_post_thumbnail_url($go,array(80,80))?>"> <?php echo $go->post_title; ?></h3>
                        <div>
                            <quote><?php echo $go->post_content; ?></quote>

                            <?php
                                foreach ($plugins[$go->ID] as $plugin){
                                    if(in_array($plugin->post_title, $installed_plugins)){
                                        $meta = get_post_meta($plugin->ID);
                                        $active = in_array($plugin->post_title,$active_plugins);
                                        ?>
                                        <div style="float:left; width:400px; background-color: #fff; margin:10px 10px 0 0; padding:20px">
                                            <h3><?php echo $plugin->post_content_filtered; ?></h3>
                                            <p><?php echo $plugin->post_content; ?></p>
                                            <p>Entwickler: <?php echo (isset($meta['plugin_author'][0])?$meta['plugin_author'][0]:''); ?></p>
                                            <p><a href="<?php echo (isset($meta['plugin_url'][0])?$meta['plugin_url'][0]:''); ?>">Mehr über dieses Erweiterung ..</a></p>
                                            <?php if(!$active):?>
                                                <a href="<?php echo admin_url('admin-post.php?action=rw_blog_wizard_activate_selected_plugin_bundle&do=activate&id='.$plugin->ID.'&_wpnonce='.$nonce); ?>"
                                                    class="button button-primary"><?php esc_html_e('Activate');?></a>
                                            <?php else:?>
                                                <a href="<?php echo admin_url('admin-post.php?action=rw_blog_wizard_activate_selected_plugin_bundle&do=deactivate&id='.$plugin->ID.'&_wpnonce='.$nonce); ?>"
                                                   class="button button-secondary"><?php esc_html_e('Deactivate');?></a>
                                            <?php endif?>

                                        </div>
                                    <?php
                                    }
                                }
                            ?>
                        </div>
                    <?php  endif;
                 endforeach;?>

            </div>
        </div>
        <script>

            jQuery( "#accordion" ).accordion({
                collapsible: true,
                active: false,
                heightStyle: "content"
            });

        </script>
        <?php

    }//end function plugin_selector

    static function activate_selected_plugin_bundle()
    {
        //global $switched;

        check_admin_referer('rw_blog_wizard_activate_selected_plugin_bundle');

        $do_activate = ($_GET['do'] == 'activate')?true:false;


        switch_to_blog(1);
        $plugin = get_post($_GET['id']);
        $post_id=$_GET['id'];
        $activate[] = trim($plugin->post_title);
        $post_meta = get_metadata('post',  $post_id, 'plugin_collection', true);

	    restore_current_blog();

	    $sub_plugs = explode("\n",$post_meta);


        foreach ($sub_plugs as $p){
            if(!empty(trim($p))){
                $activate[] = trim($p);
            }
        }
        if($do_activate) {
            foreach ($activate as $plugin){
               // var_dump(( $plugin)); die();
                activate_plugins( plugin_basename( $plugin) ,false, false );
            }
        }else{
            rsort($activate);
            foreach ($activate as $plugin){
                deactivate_plugins( plugin_basename( $plugin ) );
            }
        }

        $message = ($do_activate)?'plugin_activated':'plugin_deactivated';



        wp_redirect(admin_url('admin.php?blog_wizard_notice='.$message.'&page=rw-addons'));
    }

    static function set_no_blog_type(){
        check_admin_referer('rw_blog_wizard_deactivate_dashboard_welcome');


        update_option('rw_blog_wizard_type','nowizard');
        wp_redirect(admin_url('index.php'));
        exit;
    }

    static function on_save_template_description_set_public($post_id, $post, $update ){
        if(preg_match('#/template-[^/]+/instruction/#',$post->guid, $matches)>0){
            //get the blog path


            $blogs = get_sites(array(
                    'path'=>str_replace('instruction/','',$matches[0])
            ));
            if(isset($blogs[0])){

                if($post->post_status == 'publish'){
                    $blogdetail= array('mature' => 0);
                }else{
                    $blogdetail= array('mature' => 1);
                }

                $blog = $blogs[0];
                update_blog_details($blog->blog_id, $blogdetail);
            }
        }

    }

}
