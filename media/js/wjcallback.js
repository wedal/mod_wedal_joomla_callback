(function($) {
    $(document).ready(function() {
        $('body').on('click', '.wjcallback .wjcallback-link', function(event) {
            event.preventDefault();
            $('body').append('<div id="wjcallback-modal"></div><div id="wjcallback-loader"></div>');

			let loader = $('#wjcallback-loader');
            let wjcmodal = $('#wjcallback-modal');
			let module_id = $(this).closest(".wjcallback").attr('data-id');
            let itemid = $(this).closest(".wjcallback").attr('data-itemid');
            if (itemid) {
                itemid = '&Itemid=' + itemid;
            }
            wjcmodal.load('/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm&modid='+module_id+itemid, function() {
				loader.remove();

                $('body').addClass('wjcallback-body-scrolloff');
                wjcmodal.addClass('show');

                wjcmodal.on('click', '.close', function() {
                    wjcmodal_remove(wjcmodal);
                });

                wjcmodal.itemid = itemid;
                wjcform_submit(wjcmodal);
            }); // End Load
        });

        $('.wjcallbackform.embeddedform').each(function() {
            wjcform_submit($(this));
        });

        $(document).on('mouseup', function(event) {
            wjcmodal = $('#wjcallback-modal');

            if (wjcmodal.length === 0) {
                return;
            }

            var wjcmodalch = wjcmodal.children('.wjcallbackform');
            if (!wjcmodalch.is(event.target) &&
                wjcmodalch.has(event.target).length === 0) {
                wjcmodal_remove(wjcmodal);
            }
        });

    });

    function wjcmodal_remove(wjcmodal) {
        wjcmodal.removeClass('show');

        setTimeout(function() {
            wjcmodal.remove();
            $('body').removeClass('wjcallback-body-scrolloff');
        }, 500);
    }

    function wjcform_submit(wjcmodal) {
        wjcmodal.on('submit', 'form', function(event) {
            event.preventDefault();
            let module_id = $(this).closest(".wjcallbackform").attr('data-id');

            var itemid = $(this).closest(".wjcallbackform").attr('data-itemid');
            if (itemid) {
                itemid = '&Itemid=' + itemid;
            }

            $('body').append('<div id="wjcallback-loader"></div>');
            let loader = $('#wjcallback-loader');
            $.ajax({
                type: 'POST',
                url: '/index.php?option=com_ajax&module=wedal_joomla_callback&format=json&method=sendForm&modid='+module_id+itemid+'&page='+encodeURIComponent(window.location.href),
                data: $(this).serialize(),
                dataType: 'json',
                success: function(data) {
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
    }

})(jQuery);
