<?php

/**
 * Created by PhpStorm.
 * User: Joachim
 * Date: 20.06.2017
 * Time: 09:17
 *
 * Class RW_Blog_Wizard_Dashboard
 *
 * Autoloader for the plugin
 *
 * @package   RW Blog Wizard
 * @author    Joachim Happel
 * @license   GPL-2.0+
 * @link      https://github.com/rpi-virtuell/rw-blog-wizard
 */

class RW_Blog_Wizard_Dashboard
{
    function __construct()
    {
        add_action('get_user_metadata', array( $this , 'layers_child_welcome_panel' ) );
        add_action('admin_init', array( $this , 'layers_child_set_welcome_panel' ) );
    }

    function layers_child_welcome_panel($null, $object_id, $meta_key, $single) {

        // Only work with show_welcome_panel
        if($meta_key !== 'show_welcome_panel') { return null; }

        // If user has closed the panel, keep it closed
        $show_panel = get_user_meta( get_current_user_id(), 'layers_child_welcome_panel', true );
        if(empty($show_panel)) { return 0; }

        // Your Content Below
        echo 'your html or content goes here';

        ?>
        <a class="welcome-panel-close" href="<?php echo esc_url( admin_url( '?my_own_welcome=0' ) ); ?>"><?php _e('Dismiss this Message'); ?></a>
        <?php

        // Return 0 to suppress original panel
        return 0;
    }

    // Save user's choice on panel visibility
    function layers_child_set_welcome_panel() {
        if ( isset( $_GET['my_own_welcome'] ) ) {
            update_user_meta( get_current_user_id(), 'layers_child_welcome_panel', intval($_GET['layers_child_welcome']));
        }
    }
}