(function($) {
    $(document).ready(function() {
        $('.wjcallback').on('click', '.wjcallback-link', function(event) {
            event.preventDefault();
            $('body').append('<div id="wjcallback-modal"></div>');
			$('body').append('<div id="wjcallback-loader"></div>');
			var loader = $('#wjcallback-loader');
            var wjcmodal = $('#wjcallback-modal');
			var module_id = $(this).closest(".wjcallback").attr('data-id');
            var itemid = $(this).closest(".wjcallback").attr('data-itemid');
            if (itemid) {
                itemid = '&Itemid=' + itemid;
            }
            wjcmodal.load('/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm&modid='+module_id+itemid, function() {
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

                wjcmodal.itemid = itemid;
                wjcform_submit(wjcmodal);

            }); // End Load
        });

        $('.wjcallbackform.embeddedform').each(function(index, el) {
            wjcform_submit($(this));
        });

    });

    function wjcmodal_remove(wjcmodal) {
        wjcmodal.removeClass('show');
        setTimeout(function() {
            wjcmodal.remove();
        }, 500);
    };

    function wjcform_submit(wjcmodal) {
        wjcmodal.on('submit', 'form', function(event) {
            event.preventDefault();
            var module_id = $(this).closest(".wjcallbackform").attr('data-id');
            var wjcmodal = $('#WJCForm'+module_id);
            var itemid = $(this).closest(".wjcallbackform").attr('data-itemid');
            if (itemid) {
                itemid = '&Itemid=' + itemid;
            }

            $('body').append('<div id="wjcallback-loader"></div>');
            var loader = $('#wjcallback-loader');
            $.ajax({
                type: 'POST',
                url: '/index.php?option=com_ajax&module=wedal_joomla_callback&format=json&method=sendForm&modid='+module_id+itemid+'&page='+encodeURIComponent(window.location.href),
                data: $(this).serialize(),
                dataType: 'json',
                success: function(data) {
                    console.log(data);

                    if (!data.data.data.error) {
                        loader.remove();
                        $('#WJCForm' + module_id + ' .modal-footer').hide();
                        $('#WJCForm' + module_id + ' .modal-body').html(data.data.data.message);
                    } else {
                        alert(data.data.data.message);
                    }
                }
            });
        });
        return true;
    };

})(jQuery);
