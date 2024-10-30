<?php

/**
 * Plugin Name: Internal Page Title
 * Description: Allows to add an additional page title that is only for internal use. 
 * Author: Nerd Cow Ltd.
 * Author URI: https://nerdcow.co.uk
 * Version: 1.0
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 **/

# Adds a box to the main column on the Post and Page edit screens:
function nc_internal_page_title($post_type) {

    # Allowed post types to show meta box:
    $post_types = array( 'page' );

    if (in_array($post_type, $post_types)) {

        # Add a meta box to the administrative interface:
        add_meta_box(
            'nc-internal-page-title-meta-box', // HTML 'id' attribute of the edit screen section.
            'Internal page title',              // Title of the edit screen section, visible to user.
            'nc_internal_page_title_meta_box', // Function that prints out the HTML for the edit screen section.
            $post_type,          // The type of Write screen on which to show the edit screen section.
            'advanced',          // The part of the page where the edit screen section should be shown.
            'high'               // The priority within the context where the boxes should show.
        );

    }

}

# Callback that prints the box content:
function nc_internal_page_title_meta_box($post) {

    # Use `get_post_meta()` to retrieve an existing value from the database and use the value for the form:
    $internal_page_title = get_post_meta($post->ID, '_internal_page_title', true);

    # Form field to display:
    ?>

        <label class="screen-reader-text" for="nc_internal_page_title">Internal page title</label>
        <input id="nc_internal_page_title" type="text" autocomplete="off" value="<?=esc_attr($internal_page_title)?>" name="nc_internal_page_title" placeholder="Internal page title">

    <?php

    # Display the nonce hidden form field:
    wp_nonce_field(
        plugin_basename(__FILE__), // Action name.
        'nc_internal_page_title_meta_box'        // Nonce name.
    );

}

/**
 * @see https://wordpress.stackexchange.com/a/16267/32387
 */

# Save our custom data when the post is saved:
function nc_internal_page_title_save_postdata($post_id) {

    # Is the current user is authorised to do this action?
    if ((($_POST['post_type'] === 'page') && current_user_can('edit_page', $post_id) || current_user_can('edit_post', $post_id))) { // If it's a page, OR, if it's a post, can the user edit it? 

        # Stop WP from clearing custom fields on autosave:
        if ((( ! defined('DOING_AUTOSAVE')) || ( ! DOING_AUTOSAVE)) && (( ! defined('DOING_AJAX')) || ( ! DOING_AJAX))) {

            # Nonce verification:
            if (wp_verify_nonce($_POST['nc_internal_page_title_meta_box'], plugin_basename(__FILE__))) {

                # Get the posted internal page title:
                $internal_page_title = sanitize_text_field($_POST['nc_internal_page_title']);

                # Add, update or delete?
                if ($internal_page_title !== '') {

                    # Internal page title exists, so add OR update it:
                    add_post_meta($post_id, '_internal_page_title', $internal_page_title, true) OR update_post_meta($post_id, '_internal_page_title', $internal_page_title);

                } else {

                    # Internal page title empty or removed:
                    delete_post_meta($post_id, '_internal_page_title');

                }

            }

        }

    }

}

# Get the internal page title:
function nc_get_internal_page_title($post_id = FALSE) {

    $post_id = ($post_id) ? $post_id : get_the_ID();

    return apply_filters('nc_the_internal_page_title', get_post_meta($post_id, '_internal_page_title', TRUE));

}

# Display internal page title (this will feel better when OOP):
function nc_the_internal_page_title() {

    echo nc_get_internal_page_title(get_the_ID());

}

# Conditional checker:
function nc_has_subtitle($post_id = FALSE) {

    if (nc_get_internal_page_title($post_id)) return TRUE;

}

# Define the custom box:
add_action('add_meta_boxes', 'nc_internal_page_title');

# Do something with the data entered:
add_action('save_post', 'nc_internal_page_title_save_postdata');

/**
 * @see https://wordpress.stackexchange.com/questions/36600
 * @see https://wordpress.stackexchange.com/questions/94530/
 */

# Now move advanced meta boxes after the title:
function nc_move_internal_page_title() {

    # Get the globals:
    global $post, $wp_meta_boxes;

    # Output the "advanced" meta boxes:
    do_meta_boxes(get_current_screen(), 'advanced', $post);

    # Remove the initial "advanced" meta boxes:
    unset($wp_meta_boxes['page']['advanced']);

}

add_action('edit_form_after_title', 'nc_move_internal_page_title');

# Add some CSS to wp admin
if( is_admin() )
{
    function nc_internal_page_title_css() {
    ?>
    <style>
        #nc-internal-page-title-meta-box {
            background: none;
            border: 0px;
            margin: 20px 0px 0px;
        }
        
        #nc-internal-page-title-meta-box h2,
        #nc-internal-page-title-meta-box button {
            display: none;
        }
        
        #nc-internal-page-title-meta-box .inside {
            padding: 0px;
            margin: 0px;
        }
        
        #nc-internal-page-title-meta-box input {
            width: 100%;
        }
    </style>
    <?php
    }
    
    add_action( 'admin_head', 'nc_internal_page_title_css' );
}

function nc_display_internal_page_title( $title, $id ) {
    
    if( 
        'page' == get_post_type() &&
        is_admin() &&
        'edit-page' == get_current_screen()->id
    )
    {
        $ipt = get_post_meta( $id, '_internal_page_title', true );
        
        if( '' != $ipt )
            return $ipt;
    }
    
    return $title;
    
}

add_filter( 'the_title', 'nc_display_internal_page_title', 10, 2 );