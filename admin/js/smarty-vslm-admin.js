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

    $(document).ready(function($) {
        // Generate License Key function using jQuery
        function generateLicenseKey($inputElement) {
            const key = [...Array(4)].map(() =>
                Math.random().toString(36).substring(2, 6).toUpperCase()
            ).join("-");
            $inputElement.val(key); // Use jQuery to set the value
        }

        // e listener for Generate Key button
        $('.smarty-vslm-generate-key-button').on('click', function () {
            const $inputElement = $('#smarty_vslm_license_key'); // Target the input field
            generateLicenseKey($inputElement); // Pass the input element to the function
        });

        $('.smarty-vslm-copy-key-link').on('click', function (e) {
            e.preventDefault();
            const licenseKey = $(this).data('license-key');
            
            if (!navigator.clipboard || !navigator.clipboard.writeText) {
                console.error('Clipboard API is not supported in this environment.');
                alert('Copying to clipboard is not supported in your browser.');
                return;
            }
        
            navigator.clipboard.writeText(licenseKey)
                .then(function () {
                    alert('License key copied to clipboard');
                })
                .catch(function (err) {
                    console.error('Could not copy text: ', err);
                    alert('Failed to copy license key.');
                });
        });        

        $('.smarty-vslm-show-key-link').on('click', function (e) {
            e.preventDefault();
            var $wrapper = $(this).closest('.smarty-vslm-license-key-wrapper');
            $wrapper.find('.smarty-vslm-masked-key').text($wrapper.find('.smarty-vslm-full-key').val());
            $(this).hide();
            $wrapper.find('.smarty-vslm-hide-key-link').show();
        });

        $('.smarty-vslm-hide-key-link').on('click', function (e) {
            e.preventDefault();
            var $wrapper = $(this).closest('.smarty-vslm-license-key-wrapper');
            var maskedKey = $wrapper.find('.smarty-vslm-full-key').val().substring(0, 4) + '-XXXX-XXXX-XXXX';
            $wrapper.find('.smarty-vslm-masked-key').text(maskedKey);
            $(this).hide();
            $wrapper.find('.smarty-vslm-show-key-link').show();
        });
    });
})(jQuery);