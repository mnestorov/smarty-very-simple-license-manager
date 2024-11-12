function generateLicenseKey() {
    const key = [...Array(4)].map(() =>
        Math.random().toString(36).substring(2, 6).toUpperCase()
    ).join("-");
    document.getElementById("license_key").value = key;
}

function copyLicenseKey(element, licenseKey) {
    navigator.clipboard.writeText(licenseKey).then(function() {
        alert('License key copied to clipboard');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

jQuery(document).ready(function($) {
    $('.show-key-link').on('click', function(event) {
        event.preventDefault();
        var $wrapper = $(this).closest('.license-key-wrapper');
        $wrapper.find('.masked-key').text($wrapper.find('.full-key').val());
        $(this).hide();
        $wrapper.find('.hide-key-link').show();
    });

    $('.hide-key-link').on('click', function(event) {
        event.preventDefault();
        var $wrapper = $(this).closest('.license-key-wrapper');
        var maskedKey = $wrapper.find('.full-key').val().substring(0, 4) + '-****-****-****';
        $wrapper.find('.masked-key').text(maskedKey);
        $(this).hide();
        $wrapper.find('.show-key-link').show();
    });
});