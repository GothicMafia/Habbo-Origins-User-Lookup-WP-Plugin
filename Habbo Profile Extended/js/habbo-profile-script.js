jQuery(document).ready(function($) {
    $('#habbo-profile-form').on('submit', function(e) {
        e.preventDefault();

        var username = $('#habbo-username').val();

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'habbo_profile',
                username: username
            },
            success: function(response) {
                if (response.success) {
                    $('#habbo-avatar').attr('src', "https://www.habbo.com/habbo-imaging/avatarimage?figure=" + response.data.figureString);
                    $('#habbo-username-display').text(response.data.name);
                    $('#habbo-motto').text(response.data.motto);
                    $('#habbo-member-since').text(response.data.memberSince);
                    $('#habbo-last-online').text(response.data.lastAccessTime);
                    $('#habbo-status').text(response.data.online ? 'Online' : 'Offline');
                    $('#habbo-profile-result').show();
                } else {
                    alert('User not found.');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + " - " + error);
            }
        });
    });
});
