<?php
/*
Plugin Name: Habbo User Lookup Database
Description: A plugin to search, display Habbo user profiles, and create dynamic profile pages using the Habbo Origins API.
Version: 2.0
Author: Cai
*/

// Enqueue scripts and styles for the plugin
function habbo_profile_enqueue_scripts() {
    wp_enqueue_style('habbo-profile-style', plugins_url('/css/habbo-profile-style.css', __FILE__));
    wp_enqueue_script('habbo-profile-script', plugins_url('/js/habbo-profile-script.js', __FILE__), array('jquery'), null, true);

    // Pass ajaxurl to the script
    wp_localize_script('habbo-profile-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'habbo_profile_enqueue_scripts');

// Create table to store user profiles
function habbo_profile_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'habbo_profiles';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(255) NOT NULL UNIQUE,
        motto text DEFAULT NULL,
        figureString varchar(255) DEFAULT NULL,
        memberSince datetime DEFAULT NULL,
        lastAccessTime datetime DEFAULT NULL,
        online tinyint(1) DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'habbo_profile_create_table');

// Register shortcodes
function habbo_profile_register_shortcodes() {
    add_shortcode('habbo_profile_search', 'habbo_profile_search_shortcode');
    add_shortcode('habbo_profile_list_all', 'habbo_profile_list_all_shortcode');
    add_shortcode('habbo_profile_list_limited', 'habbo_profile_list_limited_shortcode');
}
add_action('init', 'habbo_profile_register_shortcodes');

// Shortcode to display the search form
function habbo_profile_search_shortcode() {
    ob_start();
    ?>
    <div class="habbo-profile-search-container">
        <form id="habbo-profile-form">
            <input type="text" id="habbo-username" placeholder="Enter Habbo Username" required>
            <button type="submit">Search</button>
        </form>

        <div id="habbo-profile-result" style="display:none;">
            <img id="habbo-avatar" src="" alt="Habbo Avatar">
            <div id="habbo-profile-info">
                <h2 id="habbo-username-display"></h2>
                <p><strong>Motto:</strong> <span id="habbo-motto"></span></p>
                <p><strong>Member Since:</strong> <span id="habbo-member-since"></span></p>
                <p><strong>Last Online:</strong> <span id="habbo-last-online"></span></p>
                <p><strong>Status:</strong> <span id="habbo-status"></span></p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Shortcode to list all users
function habbo_profile_list_all_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'habbo_profiles';

    $users = $wpdb->get_results("SELECT * FROM $table_name ORDER BY username ASC");

    if (!$users) {
        return '<p>No users found.</p>';
    }

    ob_start();
    echo '<div class="habbo-user-list-grid">';
    foreach ($users as $user) {
        // Sanitize username for URL (replace period with hyphen)
        $sanitized_username = str_replace('.', '-', $user->username);
        $profile_url = site_url('/habbo-profile/' . $sanitized_username);
        $avatar_url = "https://www.habbo.com/habbo-imaging/avatarimage?figure=" . esc_attr($user->figureString) . "&size=s";
        ?>
        <div class="habbo-user-card">
            <img class="habbo-user-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="Avatar of <?php echo esc_attr($user->username); ?>" />
            <a class="habbo-user-name" href="<?php echo esc_url($profile_url); ?>">
                <?php echo esc_html($user->username); ?>
            </a>
        </div>
        <?php
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('habbo_profile_list_all', 'habbo_profile_list_all_shortcode');

// Shortcode to list the last 10 most recently searched users
function habbo_profile_list_limited_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'habbo_profiles';

    // Get the last 10 most recently searched users, sorted by lastAccessTime
    $users = $wpdb->get_results("SELECT * FROM $table_name ORDER BY lastAccessTime DESC LIMIT 10");

    if (!$users) {
        return '<p>No users found.</p>';
    }

    ob_start();
    echo '<div class="habbo-user-list-grid">';
    foreach ($users as $user) {
        // Sanitize username for URL (replace period with hyphen)
        $sanitized_username = str_replace('.', '-', $user->username);
        $profile_url = site_url('/habbo-profile/' . $sanitized_username);
        $avatar_url = "https://www.habbo.com/habbo-imaging/avatarimage?figure=" . esc_attr($user->figureString) . "&size=s";
        ?>
        <div class="habbo-user-card">
            <img class="habbo-user-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="Avatar of <?php echo esc_attr($user->username); ?>" />
            <a class="habbo-user-name" href="<?php echo esc_url($profile_url); ?>">
                <?php echo esc_html($user->username); ?>
            </a>
        </div>
        <?php
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('habbo_profile_list_limited', 'habbo_profile_list_limited_shortcode');

// Rewrite rules for dynamic profile pages (/habbo-profile/{username})
function habbo_profile_rewrite_rules() {
    add_rewrite_rule('^habbo-profile/([^/]*)/?', 'index.php?habbo_profile_user=$matches[1]', 'top');
}
add_action('init', 'habbo_profile_rewrite_rules');

function habbo_profile_query_vars($vars) {
    $vars[] = 'habbo_profile_user';
    return $vars;
}
add_filter('query_vars', 'habbo_profile_query_vars');

// Template redirect for user profiles (handling sanitized URLs)
function habbo_profile_template_redirect() {
    // Capture the sanitized username from the URL (with hyphens instead of periods)
    $sanitized_username = get_query_var('habbo_profile_user');
    
    if ($sanitized_username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'habbo_profiles';

        // Convert back to the original username (replace hyphens with periods)
        $original_username = str_replace('-', '.', $sanitized_username);

        // Fetch the user profile from the database using the original username
        $user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE username = %s", $original_username));

        if ($user_data) {
            // Load the WordPress theme's header, content, and footer
            get_header();

            ?>
            <div class="habbo-profile-container">
                <img src="https://www.habbo.com/habbo-imaging/avatarimage?figure=<?php echo esc_attr($user_data->figureString); ?>" alt="Habbo Avatar" class="habbo-avatar">
                <div id="habbo-profile-info">
                    <h2><?php echo esc_html($user_data->username); ?></h2>
                    <p><strong>Motto:</strong> <?php echo esc_html($user_data->motto); ?></p>
                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user_data->memberSince)); ?></p>
                    <p><strong>Last Online:</strong> <?php echo date('F j, Y, g:i a', strtotime($user_data->lastAccessTime)); ?></p>
                    <p><strong>Status:</strong> <?php echo $user_data->online ? 'Online' : 'Offline'; ?></p>
                </div>
            </div>
            <?php

            get_footer();
        } else {
            // Handle case where user data is not found
            wp_die('User profile not found.');
        }

        exit;
    }
}
add_action('template_redirect', 'habbo_profile_template_redirect');

// AJAX handler to fetch Habbo profile data
function habbo_profile_ajax_handler() {
    global $wpdb;

    $username = sanitize_text_field($_POST['username']);

    // Fetch profile from Habbo API
    $response = wp_remote_get("https://origins.habbo.com/api/public/users?name=" . $username);

    if (is_wp_error($response)) {
        wp_send_json_error('Could not retrieve data.');
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($data['name'])) {
        wp_send_json_error('User not found.');
    }

    // Prepare data
    $profile_data = array(
        'name' => $data['name'],
        'motto' => !empty($data['motto']) ? $data['motto'] : 'No Motto',
        'figureString' => $data['figureString'],
        'memberSince' => !empty($data['memberSince']) ? date('Y-m-d H:i:s', strtotime($data['memberSince'])) : null,
        'lastAccessTime' => !empty($data['lastAccessTime']) ? date('Y-m-d H:i:s', strtotime($data['lastAccessTime'])) : null,
        'online' => $data['online'] ? 1 : 0
    );

    // Insert or update profile in the database
    $table_name = $wpdb->prefix . 'habbo_profiles';
    $wpdb->replace(
        $table_name,
        array(
            'username' => $profile_data['name'],
            'motto' => $profile_data['motto'],
            'figureString' => $profile_data['figureString'],
            'memberSince' => $profile_data['memberSince'],
            'lastAccessTime' => $profile_data['lastAccessTime'],
            'online' => $profile_data['online']
        )
    );

    wp_send_json_success($profile_data);
}
add_action('wp_ajax_habbo_profile', 'habbo_profile_ajax_handler');
add_action('wp_ajax_nopriv_habbo_profile', 'habbo_profile_ajax_handler');
