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

jQuery(document).ready(function($) {
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