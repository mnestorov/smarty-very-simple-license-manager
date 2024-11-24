(function ($) {
	'use strict';

	/**
	 * All of the code for plugin admin JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed we will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables us to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 */

    $(document).ready(function ($) {
        // Handle CK Key generation
        $('#smarty_vslm_generate_ck_key').on('click', function (e) {
            e.preventDefault(); // Prevent the default form action

            $.ajax({
                url: smartyVerySimpleLicenseManager.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'vslm_generate_ck_key',
                    nonce: smartyVerySimpleLicenseManager.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        $('#smarty_vslm_ck_key').val(response.data); // Update the CK Key field with the new value
                    }
                }
            });
        });

        // Handle CS Key generation
        $('#smarty_vslm_generate_cs_key').on('click', function (e) {
            e.preventDefault(); // Prevent the default form action

            $.ajax({
                url: smartyVerySimpleLicenseManager.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'vslm_generate_cs_key',
                    nonce: smartyVerySimpleLicenseManager.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        $('#smarty_vslm_cs_key').val(response.data); // Update the CS Key field with the new value
                    }
                }
            });
        });

        // Delete admin logs
        $('#smarty-vslm-delete-logs-button').on('click', function(e) {
            e.preventDefault();

            if (confirm('Are you sure you want to delete all logs?')) {
                $.post(
                    smartyVerySimpleLicenseManager.ajaxUrl,
                    {
                        action: 'vslm_clear_logs',
                        nonce: smartyVerySimpleLicenseManager.nonce,
                    },
                    function(response) {
                        if (response.success) {
                            alert('Logs cleared.');
                            location.reload();
                        } else {
                            alert('Failed to clear logs.');
                        }
                    }
                );
            }
        });
    });
})(jQuery);