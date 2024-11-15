jQuery(document).ready(function ($) {
    // Handle CK Key generation
    $('#smarty_vslm_generate_ck_key').on('click', function (e) {
        e.preventDefault(); // Prevent the default form action

        $.ajax({
            url: smarty_vslm_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'smarty_vslm_generate_ck_key'
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
            url: smarty_vslm_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'smarty_vslm_generate_cs_key'
            },
            success: function (response) {
                if (response.success) {
                    $('#smarty_vslm_cs_key').val(response.data); // Update the CS Key field with the new value
                }
            }
        });
    });
});
