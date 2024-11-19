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

    function generateLicenseKey() {
        const key = [...Array(4)].map(() =>
            Math.random().toString(36).substring(2, 6).toUpperCase()
        ).join("-");
        document.getElementById("smarty_vslm_license_key").value = key;
    }

    function copyLicenseKey(element, licenseKey) {
        navigator.clipboard.writeText(licenseKey).then(function() {
            alert('License key copied to clipboard');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }

    $(document).ready(function($) {
        $('.smarty-vslm-show-key-link').on('click', function(event) {
            event.preventDefault();
            var $wrapper = $(this).closest('.smarty-vslm-license-key-wrapper');
            $wrapper.find('.smarty-vslm-masked-key').text($wrapper.find('.smarty-vslm-full-key').val());
            $(this).hide();
            $wrapper.find('.smarty-vslm-hide-key-link').show();
        });

        $('.smarty-vslm-hide-key-link').on('click', function(event) {
            event.preventDefault();
            var $wrapper = $(this).closest('.smarty-vslm-license-key-wrapper');
            var maskedKey = $wrapper.find('.smarty-vslm-full-key').val().substring(0, 4) + '-XXXX-XXXX-XXXX';
            $wrapper.find('.smarty-vslm-masked-key').text(maskedKey);
            $(this).hide();
            $wrapper.find('.smarty-vslm-show-key-link').show();
        });
    });
})(jQuery);