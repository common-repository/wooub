;(function($) {
    'use strict';

    $(function() {
        $('input[name="phanes-uber-license-code[]"]').on('keyup', function() {
                
        if ( $(this).val().length == 4 )
            $(this).next('input[name="phanes-uber-license-code[]"]').focus();
        });
    });
}(jQuery))