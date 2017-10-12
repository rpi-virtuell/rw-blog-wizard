<?php
/**
 * put your custom functions here
 */

/**
 * @example create top message on a Blogsite
 *
 * @param $post_object
 */
function rw_blog_wizard_action( $post_object ) {
    ?>
        <div class="rw-blog-wizard-message">
        Message to the <a href="#">World</a>
    </div>
    <?php
}
//add_action( 'wp_head', 'rw_blog_wizard_action' );