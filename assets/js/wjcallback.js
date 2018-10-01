(function($)
{
	$(document).ready(function()
	{

        $('.wjcallback').on('click', '.wjcallback-link', function(event) {
            event.preventDefault();
            $('body').append('<div id="wjcallback-modal"></div>');
            var wjcmodal = $('#wjcallback-modal');
            wjcmodal.load('/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm');

            setTimeout(function () {
                wjcmodal.addClass('show');
            }, 3);

        	$(document).on('mouseup', function(event) {
                var wjcmodalch = wjcmodal.children('.wjcallbackform');
        		if (!wjcmodalch.is(event.target)
        		    && wjcmodalch.has(event.target).length === 0) {
                    wjcmodal_remove(wjcmodal);
        		}
        	});

            wjcmodal.on('click', '.close', function(event) {
                wjcmodal_remove(wjcmodal);
            });

        });
	})

    function wjcmodal_remove(wjcmodal) {
        wjcmodal.removeClass('show');
        setTimeout(function () {
            wjcmodal.remove();
        }, 500);
    };

})(jQuery);
