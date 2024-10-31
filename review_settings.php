<?php
/* call create menu function */
add_action('admin_menu', 'pr_create_menu');
/* call register settings function */
add_action('admin_init', 'register_pgsettings');

function pr_create_menu() {
    /* create new top-level menu */
    add_posts_page('Post Review - Settings', 'Post Review', 'manage_options', 'pr_settings', 'pr_settings_page');
}

function register_pgsettings() {
    /* register our settings */
    register_setting('pr-settings-group', 'what_categories');
}

function pr_settings_page() {
    ?>
    <div class="wrap">
        <h2>Post Review</h2>
        <?php if ($_GET['settings-updated'] == 'true'): ?>
            <div id="setting-error-settings_updated" class="updated settings-error"> 
                <p><strong>Settings saved.</strong></p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields('pr-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Which categories to allowed review?', 'post_review'); ?></th>
                    <td>
                        <div style="overflow:auto; height: 150px; width:300px; padding:.5em .9em; border-style:solid; border-width: 1px; border-color: #DFDFDF; background-color:white; display: block; line-height: 1.4em; color:#333">
                            <ul>
                                <?php
                                $categories = get_categories(array('orderby' => 'name', 'order' => 'ASC', 'hide_empty' => 0));
                                ?>
                                <?php foreach ($categories as $category): ?>
                                    <li>
                                        <label for="post_review_effective_category-<?php echo $category->cat_ID ?>"><input type="checkbox" id="post_review_effective_category-<?php echo $category->cat_ID ?>" value="<?php echo $category->cat_ID ?>" <?php if (@in_array($category->cat_ID, get_option('what_categories'))) echo 'checked=checked' ?> name="what_categories[]"/>&nbsp;<?php echo $category->cat_name; ?></label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php } ?>