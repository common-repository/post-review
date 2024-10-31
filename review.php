<?php

/*
  Plugin Name: Post Review
  Description: Add star rating to comment feature of Wordpress
  Version: 0.1
  Author: Global Net One
  License: Modified BSD license
  Author URI: http://globalnet-1.com
 */

require_once(dirname(__FILE__) . '/review_settings.php');
require_once(dirname(__FILE__) . '/review_meta_box.php');

wp_enqueue_script('jquery');
wp_enqueue_script('jquery.rating', plugin_dir_url(__FILE__) . 'js/jquery.rating.pack.js', array('jquery'));

function my_custom_comments_list($comments) {
    foreach ($comments as &$comment) {
        $rating = get_comment_meta($comment->comment_ID, 'post_review_rating', true);
        if ($rating == '') {
            $rating = 'false';
            continue;
        }
        $comment->comment_content .= '<div class="star-block">';
        for ($i = 0; $i < 5; $i++) {
            $comment->comment_content .= '<input type="radio" name="star-' . $comment->comment_ID . '-review" class="star star-' . $comment->comment_ID . '" value="' . ($i + 1) . '"/>';
        }
        $comment->comment_content .= '</div>';
        $comment->comment_content .= '<script type="text/javascript">';
        $comment->comment_content .= 'jQuery(document).ready(function(){jQuery("input.star-' . $comment->comment_ID . '").rating("select", ' . ($rating - 1) . ');});';
        $comment->comment_content .= 'jQuery(document).ready(function(){jQuery("input.star-' . $comment->comment_ID . '").rating("readOnly", true);});';
        $comment->comment_content .= '</script>';
    }
    return $comments;
}

add_filter('comments_array', 'my_custom_comments_list');

function my_custom_comment_form_defaults($default) {
    global $post;
    global $current_user;

    if (0 == $current_user->ID) {
        if (isset($_COOKIE['post_review_rating_' . $post->ID . '_' . COOKIEHASH])) {
            return $default;
        }
    }
    else {
        $comments = get_comments('post_id=' . $post->ID . '&user_id=' . $current_user->ID);
        foreach ($comments as $comment) {
            if (is_numeric(get_comment_meta($comment->comment_ID, 'post_review_rating', true))) {
                return $default;
            }
        }
    }

    if (@in_array($post->ID, get_option('what_posts'))) {
        return $default;
    }


    $cat = get_the_category($post->ID);
    if (!@in_array($cat[0]->cat_ID, get_option('what_categories')) && $post->post_type == 'post') {
        return $default;
    }

    $default['comment_field'] .= '<p id="star-block">';
    $default['comment_field'] .= '<label>Rate:</label><br/>';
    for ($i = 0; $i < 5; $i++) {
        $default['comment_field'] .= '<input name="review" type="radio" class="star" value="' . ($i + 1) . '"/>';
    }
    $default['comment_field'] .='</p>';
    return $default;
}

add_filter('comment_form_defaults', 'my_custom_comment_form_defaults');

function my_custom_comments_template($file) {
    echo '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . 'css/jquery.rating.css" type="text/css">';
    return $file;
}

add_filter('comments_template', 'my_custom_comments_template');

function my_comment_form_rating_field() {
    global $post;
    global $current_user;

    $output = '';

    if (0 == $current_user->ID) {
        if (isset($_COOKIE['post_review_rating_' . $post->ID . '_' . COOKIEHASH])) {
            return $output;
        }
    }
    else {
        $comments = get_comments('post_ID=' . $post->ID . '&user_id=' . $current_user->ID);
        foreach ($comments as $comment) {
            if (is_numeric(get_comment_meta($comment->comment_ID, 'post_review_rating', true))) {
                return $output;
            }
        }
    }
    if (@in_array($post->ID, get_option('what_posts'))) {
        return $output;
    }

    $cat = get_the_category($post->ID);
    if (!@in_array($cat[0]->cat_ID, get_option('what_categories')) && $post->post_type == 'post') {
        return $output;
    }

    $output = '<br>';
    for ($i = 0; $i < 5; $i++) {
        $output .= '<input name="review" type="radio" class="star" value="' . ($i + 1) . '"/>';
    }
    echo $output;
}

add_action('comment_form_rating_field', 'my_comment_form_rating_field');

function my_custom_pre_comment_on_post() {
    global $wpdb;

    $comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;
    $comment_author = ( isset($_POST['author']) ) ? trim(strip_tags($_POST['author'])) : null;
    $comment_author_email = ( isset($_POST['email']) ) ? trim($_POST['email']) : null;
    $comment_author_url = ( isset($_POST['url']) ) ? trim($_POST['url']) : null;
    $comment_content = ( isset($_POST['comment']) ) ? trim($_POST['comment']) : null;
    $comment_rating = ( isset($_POST['review']) ) ? trim($_POST['review']) : null;

// If the user is logged in
    $user = wp_get_current_user();
    if ($user->ID) {
        $user_ID = $user->ID;
        if (empty($user->display_name))
            $user->display_name = $user->user_login;
        $comment_author = $wpdb->escape($user->display_name);
        $comment_author_email = $wpdb->escape($user->user_email);
        $comment_author_url = $wpdb->escape($user->user_url);
        if (current_user_can('unfiltered_html')) {
            if (wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $_POST['_wp_unfiltered_html_comment']) {
                kses_remove_filters(); // start with a clean slate
                kses_init_filters(); // set up the filters
            }
        }
    }
    else {
        if (get_option('comment_registration') || 'private' == $status)
            wp_die(__('Sorry, you must be logged in to post a comment.'));
    }

    $comment_type = '';

    if (get_option('require_name_email') && !$user->ID) {
        if (6 > strlen($comment_author_email) || '' == $comment_author)
            wp_die(__('Error: please fill the required fields (name, email).'));
        elseif (!is_email($comment_author_email))
            wp_die(__('Error: please enter a valid email address.'));
    }

    if ('' == $comment_content)
        wp_die(__('Error: please type a comment.'));

    $comment_parent = isset($_POST['comment_parent']) ? absint($_POST['comment_parent']) : 0;

    $commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

    $comment_id = wp_new_comment($commentdata);
    if ($comment_rating != null) {
        add_comment_meta($comment_id, 'post_review_rating', $comment_rating);
        $comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
        setcookie('post_review_rating_' . $comment_post_ID . '_' . COOKIEHASH, $comment_rating, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
    }

    $comment = get_comment($comment_id);
    if (!$user->ID) {
        $comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
        setcookie('comment_author_' . COOKIEHASH, $comment->comment_author, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
        setcookie('comment_author_email_' . COOKIEHASH, $comment->comment_author_email, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
        setcookie('comment_author_url_' . COOKIEHASH, esc_url($comment->comment_author_url), time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
    }

    $location = empty($_POST['redirect_to']) ? get_comment_link($comment_id) : $_POST['redirect_to'] . '#comment-' . $comment_id;
    $location = apply_filters('comment_post_redirect', $location, $comment);

    wp_redirect($location);
    exit;
}

add_action('pre_comment_on_post', 'my_custom_pre_comment_on_post');
?>