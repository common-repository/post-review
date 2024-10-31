<?php
global $wp_version;

/* Define the custom box */
add_action('add_meta_boxes', 'add_post_review_custom_box');

if ($wp_version < 3.0) {
    /* backwards compatible (before WP 3.0) */
    add_action('admin_init', 'add_post_review_custom_box', 1);
}

/* Do something with the data entered */
add_action('save_post', 'post_review_save_postdata');

function add_post_review_custom_box() {
    add_meta_box('post_review_choose_sectionid', __('Post Review', 'post_review_textdomain'), 'post_review_inner_custom_box', 'post', 'side', 'high');
    add_meta_box('post_review_choose_sectionid', __('Post Review', 'post_review_textdomain'), 'post_review_inner_custom_box', 'page', 'side', 'high');
}

function post_review_inner_custom_box($post) {
    // Use nonce for verification
    wp_nonce_field(plugin_basename(__FILE__), 'post_review_noncename');

    // The actual fields for data entry
    ?>
    <input type="checkbox" id="post_review_display_rating" name="post_review_display_rating" <?php
    if (is_array(get_option('what_posts'))) {
        if (in_array($post->ID, get_option('what_posts'))) {
            echo 'checked=checked';
        }
    }
    ?> value="1"/>
    <label for="post_review_display_rating">
        <?Php
        if ($post->post_type == 'post')
            echo _e("Don't allow review on this post", 'post_review_textdomain');
        else
            echo _e("Allow review on this page", 'post_review_textdomain');
        ?>

    </label>
    <?php
}

/* When the post is saved, saves our custom data */

function post_review_save_postdata($post_id) {
    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times

    if (!wp_verify_nonce($_POST['post_review_noncename'], plugin_basename(__FILE__)))
        return;


    // Check permissions
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id))
            return;
    }
    else {
        if (!current_user_can('edit_post', $post_id))
            return;
    }

    // OK, we're authenticated: we need to find and save the data

    $rate_or_not = $_POST['post_review_display_rating'];

    if ($rate_or_not == 1) {
        $dont_display_rating_on = get_option('what_posts');
        if (!@in_array($post_id, $dont_display_rating_on)) {
            $dont_display_rating_on[] = $post_id;
        }
        update_option('what_posts', $dont_display_rating_on);
    }
    else {
        foreach ($dont_display_rating_on as &$post_ID) {
            if ($post_ID == $post_id) {
                unset($post_ID);
            }
        }
        update_option('what_posts', $dont_display_rating_on);
    }

    // Do something with $mydata 
    // probably using add_post_meta(), update_post_meta(), or 
    // a custom table (see Further Reading section below)
}
?>
