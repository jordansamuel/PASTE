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

    // Handle database configuration form submission
    $('#db-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Database form submitted:', $(this).serialize());
        $('#install').hide();
        $('#configure').hide();
        $('#pre_load').show();
        $('#alertfailed').hide();
        $('#admin-alertfailed').hide(); // Explicitly hide admin error

        $.ajax({
            url: 'configure.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('configure.php response:', response);
                $('#pre_load').hide();
                if (response.status === 'success') {
                    $('#configure').show();
                    $('#logpanel').show();
                    $('#log').html(response.message);
                } else {
                    $('#install').show();
                    $('#alertfailed').show();
                    $('#error-details').text(response.message || 'Unknown error occurred.');
                }
            },
            error: function(xhr, status, error) {
                console.error('configure.php AJAX error:', status, error, xhr.responseText);
                $('#pre_load').hide();
                $('#install').show();
                $('#alertfailed').show();
                $('#error-details').text('Configuration failed: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : status + ' - ' + error));
            }
        });
    });

    // Handle admin configuration form submission
    $('#admin-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Admin form submitted:', $(this).serialize());
        $('#configure').hide();
        $('#pre_load').show();
        $('#alertfailed').hide();
        $('#admin-alertfailed').hide();

        $.ajax({
            url: 'install.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('install.php response:', response);
                $('#pre_load').hide();
                $('#logpanel').show();
                $('#log').html(response.message);
            },
            error: function(xhr, status, error) {
                console.error('install.php AJAX error:', status, error, xhr.responseText);
                $('#pre_load').hide();
                $('#configure').show();
                $('#admin-alertfailed').show();
                $('#admin-error-details').text('Error admin setup failed: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : status + ' - ' + error));
            }
        });
    });

    // Warn about OAuth without HTTPS
    $('#enablegoog, #enablefb').on('change', function() {
        if (($(this).attr('id') === 'enablegoog' && $(this).val() === 'yes') || 
            ($(this).attr('id') === 'enablefb' && $(this).val() === 'yes')) {
            if (window.location.protocol !== 'https:') {
                alert('Warning: Enabling OAuth without HTTPS is insecure and may cause issues with OAuth providers. Consider enabling SSL/TLS.');
            }
        }
    });
});