/**
 * Shopiwise Payment Gateway Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Bağlantı testi
        $('#shopiwise-test-connection').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $result = $('#shopiwise-connection-result');
            
            $button.prop('disabled', true);
            $result.html('<p>' + shopiwiseAdmin.testing_connection + '</p>');
            
            $.ajax({
                url: shopiwiseAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'shopiwise_test_connection',
                    nonce: shopiwiseAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<p style="color: green;">' + response.data + '</p>');
                    } else {
                        $result.html('<p style="color: red;">' + shopiwiseAdmin.connection_error + ' ' + response.data + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $result.html('<p style="color: red;">' + shopiwiseAdmin.connection_error + ' ' + error + '</p>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    });
})(jQuery); 