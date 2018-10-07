(function($)
{
	$(document).ready(function()
	{
        $('.wjcallback').on('click', '.wjcallback-link', function(event) {
            event.preventDefault();
            $('body').append('<div id="wjcallback-modal"></div>');
            var wjcmodal = $('#wjcallback-modal');
            wjcmodal.load('/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm', function(){

                wjcmodal.addClass('show');

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

                wjcmodal.on('submit','form', function(event){
                    event.preventDefault();
                    window.reEmail = /^([a-z0-9\.\-\_])+\@(([a-zA-Z0-9\-\_])+\.)+([a-zA-Z0-9]{2,6})+$/i;

                    wjcmodal_id = wjcmodal.find('.wjcallbackform').attr('id')

                    if (!wjcmodal.find('#'+wjcmodal_id+'_name').val() && wjcmodal.find('#'+wjcmodal_id+'_name').hasClass('required')) {
                        wjcmodal.find('#'+wjcmodal_id+'_name').focus();
                        alert("Укажите свое имя!");

                    } else if (!wjcmodal.find('#'+wjcmodal_id+'_phone').val() && wjcmodal.find('#'+wjcmodal_id+'_phone').hasClass('required')) {
                        wjcmodal.find('#'+wjcmodal_id+'_phone').focus();
                        alert("Введите телефон, пожалуйста!");

                    } else if (!wjcmodal.find('#'+wjcmodal_id+'_email').val() && wjcmodal.find('#'+wjcmodal_id+'_email').hasClass('required')) {
                             wjcmodal.find('#'+wjcmodal_id+'_email').focus();
                             alert("Введите E-mail, пожалуйста!");

                    } else if (!reEmail.test(wjcmodal.find('#'+wjcmodal_id+'_email').val()) && wjcmodal.find('#'+wjcmodal_id+'_email').hasClass('required')) {
                             alert( 'E-mail указан в неправильном формате.' );
                             wjcmodal.find('#'+wjcmodal_id+'_email').focus();

                    } else if (!wjcmodal.find('#'+wjcmodal_id+'_comment').val() && wjcmodal.find('#'+wjcmodal_id+'_comment').hasClass('required')) {
                          alert( 'Добавьте комментарий.' );
                          wjcmodal.find('#'+wjcmodal_id+'_comment').focus();

                    } else {

                        /*

                        $.ajax({
                           type: 'POST',
                           url: '/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm',
                           data: $(this).serialize(),
                           success: function(data)
                           {
                              // alert(data);
                           }
                         });
                        */

                        alert('Спасибо за запрос. В ближайшее время мы с вами свяжемся.');
                    }
                 });

            }); // End Load





        });




	});

    function wjcmodal_remove(wjcmodal) {
        wjcmodal.removeClass('show');
        setTimeout(function () {
            wjcmodal.remove();
        }, 500);
    };

})(jQuery);
