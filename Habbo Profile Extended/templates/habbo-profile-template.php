<?php
// Get the header of the current theme
get_header();

global $wpdb;

// Get the username from the URL
$username = get_query_var('habbo_profile_user');

// Increment the visit count
habbo_profile_increment_visit_count($username);

// Fetch the user profile from the database
$table_name = $wpdb->prefix . 'habbo_profiles';
$user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE username = %s", $username));

if ($user_data) {
    // Fetch avatar, motto, etc.
    $avatar_url = "https://www.habbo.com/habbo-imaging/avatarimage?figure=" . esc_attr($user_data->figureString);
    $motto = esc_html($user_data->motto);
    $member_since = !empty($user_data->memberSince) ? date('F j, Y', strtotime($user_data->memberSince)) : 'Unknown';
    $last_online = !empty($user_data->lastAccessTime) ? date('F j, Y, g:i a', strtotime($user_data->lastAccessTime)) : 'Unknown';
    $status = $user_data->online ? 'Online' : 'Offline';
    ?>

    <div class="habbo-profile-container">
        <img src="<?php echo esc_url($avatar_url); ?>" alt="Habbo Avatar" class="habbo-avatar">
        <div id="habbo-profile-info">
            <h2><?php echo esc_html($user_data->username); ?></h2>
            <p><strong>Motto:</strong> <?php echo $motto; ?></p>
            <p><strong>Member Since:</strong> <?php echo $member_since; ?></p>
            <p><strong>Last Online:</strong> <?php echo $last_online; ?></p>
            <p><strong>Status:</strong> <?php echo $status; ?></p>
            
            <!-- Display Search and Visit Counts -->
            <?php habbo_profile_display_search_and_visit_count($username); ?>
        </div>
    </div>

    <?php
} else {
    echo '<p>User profile not found.</p>';
}

// Get the footer of the current theme
get_footer();
?>
