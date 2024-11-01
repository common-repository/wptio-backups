jQuery(document).ready(function () {
//    jQuery('button').click(function() { 
//        var text = jQuery(this).attr('value'); 
//        wptioAjax.accessdrive(text);
//    }); 
    
    jQuery("#close").click(function () {

        jQuery("#messagebox").attr('hidden', true);


    });
    jQuery("#allDate").on('change', function () {
        var allDate = jQuery("#allDate").attr('checked') == 'checked';
        

        if (allDate) {
            jQuery("#from").attr('disabled', true);
            jQuery("#to").attr('disabled', true);
        } else
        {
            jQuery("#from").removeAttr('disabled');
            jQuery("#to").removeAttr('disabled');
        }
    });
});

