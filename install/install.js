//
// Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
// demo: https://paste.boxlabs.uk/
// https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
//
// Licensed under GNU General Public License, version 3 or later.
// See LICENCE for details.

if (typeof jQuery === 'undefined') {
    console.error('jQuery failed to load from CDN. Please check your network or ad blocker.');
} else {
    console.log('jQuery loaded successfully.');
}

$(document).ready(function() {
    console.log('Document ready. Binding form handlers.');
    
    $('#db-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Database form submitted:', $(this).serialize());
        $.ajax({
            url: 'configure.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('configure.php response:', response);
                if (response.status === 'error') {
                    $('#error-details').text(response.message);
                    $('#alertfailed').show();
                    $('#install').show();
                    $('#configure').hide();
                } else {
                    $('#install').hide();
                    $('#configure').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('configure.php AJAX error:', status, error);
                $('#error-details').text('AJAX error: ' + status + ' - ' + error);
                $('#alertfailed').show();
            }
        });
    });

    $('#admin-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Admin form submitted:', $(this).serialize());
        $('#alertfailed').hide();
        $('#install').hide();
        $('#configure').hide();
        $('#pre_load').show();
        $.ajax({
            url: 'install.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('install.php response:', response);
                $('#logpanel').show();
                $('#log').html(response.message);
                $('#pre_load').hide();
            },
            error: function(xhr, status, error) {
                console.error('install.php AJAX error:', status, error);
                $('#logpanel').show();
                $('#log').html('An error occurred during installation: ' + status + ' - ' + error);
                $('#pre_load').hide();
            }
        });
    });
});