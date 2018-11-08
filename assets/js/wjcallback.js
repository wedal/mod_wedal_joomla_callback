(function($) {
    $(document).ready(function() {
        $('.wjcallback').on('click', '.wjcallback-link', function(event) {
            event.preventDefault();
            $('body').append('<div id="wjcallback-modal"></div>');
            var wjcmodal = $('#wjcallback-modal');
            wjcmodal.load('/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm', function() {

                wjcmodal.addClass('show');

                $(document).on('mouseup', function(event) {
                    var wjcmodalch = wjcmodal.children('.wjcallbackform');
                    if (!wjcmodalch.is(event.target) &&
                        wjcmodalch.has(event.target).length === 0) {
                        wjcmodal_remove(wjcmodal);
                    }
                });

                wjcmodal.on('click', '.close', function(event) {
                    wjcmodal_remove(wjcmodal);
                });

                wjcmodal.on('submit', 'form', function(event) {
                    event.preventDefault();
                    window.reEmail = /^([a-z0-9\.\-\_])+\@(([a-zA-Z0-9\-\_])+\.)+([a-zA-Z0-9]{2,6})+$/i;

                    wjcmodal_id = wjcmodal.find('.wjcallbackform').attr('id')

                    if (!wjcmodal.find('#' + wjcmodal_id + '_name').val() && wjcmodal.find('#' + wjcmodal_id + '_name').hasClass('required')) {
                        wjcmodal.find('#' + wjcmodal_id + '_name').focus();
                        alert(wjcmodal.find('#' + wjcmodal_id + '_name').attr('data-error'));

                    } else if (!wjcmodal.find('#' + wjcmodal_id + '_phone').val() && wjcmodal.find('#' + wjcmodal_id + '_phone').hasClass('required')) {
                        wjcmodal.find('#' + wjcmodal_id + '_phone').focus();
                        alert(wjcmodal.find('#' + wjcmodal_id + '_phone').attr('data-error'));

                    } else if ((!reEmail.test(wjcmodal.find('#' + wjcmodal_id + '_email').val()) || !wjcmodal.find('#' + wjcmodal_id + '_email').val()) && wjcmodal.find('#' + wjcmodal_id + '_email').hasClass('required')) {
                        wjcmodal.find('#' + wjcmodal_id + '_email').focus();
                        alert(wjcmodal.find('#' + wjcmodal_id + '_email').attr('data-error'));

                    } else if (!wjcmodal.find('#' + wjcmodal_id + '_comment').val() && wjcmodal.find('#' + wjcmodal_id + '_comment').hasClass('required')) {
                        wjcmodal.find('#' + wjcmodal_id + '_comment').focus();
                        alert(wjcmodal.find('#' + wjcmodal_id + '_comment').attr('data-error'));

                    } else {

                        $.ajax({
                            type: 'POST',
                            url: '/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=sendForm',
                            data: $(this).serialize(),
                            dataType: 'json',
                            success: function(data) {
                                console.log(data);
                                alert(data['message']);

                                if (!data['error']) {
                                    wjcmodal_remove(wjcmodal);
                                }
                            }
                        });
                    }
                });

            }); // End Load
        });
    });

    function wjcmodal_remove(wjcmodal) {
        wjcmodal.removeClass('show');
        setTimeout(function() {
            wjcmodal.remove();
        }, 500);
    };

})(jQuery);
