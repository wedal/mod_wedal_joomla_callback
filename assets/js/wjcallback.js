(function($) {
    $(document).ready(function() {
        $('.wjcallback').on('click', '.wjcallback-link', function(event) {
            event.preventDefault();

            $('body').append('<div id="wjcallback-modal"></div>');
			$('body').append('<div id="wjcallback-loader"></div>');
			var loader = $('#wjcallback-loader');
            var wjcmodal = $('#wjcallback-modal');
			var module_id = $(this).closest(".wjcallback").attr('data-id');
            wjcmodal.load('/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm&modid='+module_id, function() {
				loader.remove();
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

                    wjcmodal_id = wjcmodal.find('.wjcallbackform').attr('id');

                    if (form_validate(wjcmodal_id)) {
						$('body').append('<div id="wjcallback-loader"></div>');
						var loader = $('#wjcallback-loader');
                        $.ajax({
                            type: 'POST',
                            url: '/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=sendForm&modid='+wjcmodal_id,
                            data: $(this).serialize(),
                            dataType: 'json',
                            success: function(data) {
                                //console.log(data);
								loader.remove();
                                $('#WJCForm'+module_id+' .modal-footer').hide();
                                $('#WJCForm'+module_id+' .modal-body').html(data['message']);
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

    function form_validate(wjcmodal_id) {
        var wjcmodal = $('#wjcallback-modal');
        window.reEmail = /^([a-z0-9\.\-\_])+\@(([a-zA-Z0-9\-\_])+\.)+([a-zA-Z0-9]{2,6})+$/i;
        if (!wjcmodal.find('#' + wjcmodal_id + '_name').val() && wjcmodal.find('#' + wjcmodal_id + '_name').hasClass('required')) {
            wjcmodal.find('#' + wjcmodal_id + '_name').focus();
            alert(wjcmodal.find('#' + wjcmodal_id + '_name').attr('data-error'));
            return false;
        } else if (!wjcmodal.find('#' + wjcmodal_id + '_phone').val() && wjcmodal.find('#' + wjcmodal_id + '_phone').hasClass('required')) {
            wjcmodal.find('#' + wjcmodal_id + '_phone').focus();
            alert(wjcmodal.find('#' + wjcmodal_id + '_phone').attr('data-error'));
            return false;

        } else if ((!reEmail.test(wjcmodal.find('#' + wjcmodal_id + '_email').val()) || !wjcmodal.find('#' + wjcmodal_id + '_email').val()) && wjcmodal.find('#' + wjcmodal_id + '_email').hasClass('required')) {
            wjcmodal.find('#' + wjcmodal_id + '_email').focus();
            alert(wjcmodal.find('#' + wjcmodal_id + '_email').attr('data-error'));
            return false;

        } else if (!wjcmodal.find('#' + wjcmodal_id + '_comment').val() && wjcmodal.find('#' + wjcmodal_id + '_comment').hasClass('required')) {
            wjcmodal.find('#' + wjcmodal_id + '_comment').focus();
            alert(wjcmodal.find('#' + wjcmodal_id + '_comment').attr('data-error'));
            return false;

        } else {
            return true;
        }
    };

})(jQuery);
