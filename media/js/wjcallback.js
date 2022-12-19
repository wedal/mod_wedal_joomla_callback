document.addEventListener('DOMContentLoaded', () => {

    let module_options = Joomla.getOptions('wedal_joomla_callback');
    let ya_counter = null;
    if (typeof ym !== 'undefined') {
        ya_counter = ym['a'][0][0];
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.wjcallback-link')) {
            return;
        }
        event.preventDefault();

        if (ya_counter && event.target.closest('.wjcallback-link').getAttribute('data-ym-aimid')) {
            ym(ya_counter, 'reachGoal', event.target.closest('.wjcallback-link').getAttribute('data-ym-aimid'));
        }

        let modal_div = document.createElement('div');
        modal_div.id = "wjcallback-modal";
        document.body.append(modal_div);

        let loader_div = document.createElement('div');
        loader_div.id = "wjcallback-loader";
        document.body.append(loader_div);

        let loader = document.getElementById('wjcallback-loader');
        let wjcmodal = document.getElementById('wjcallback-modal');
        let module_id = event.target.getAttribute('data-id');
        let itemid = '';

        if (module_options['itemid']) {
            itemid = '&Itemid=' + module_options['itemid'];
        }

        let url = '/index.php?option=com_ajax&module=wedal_joomla_callback&format=raw&method=getForm&modid=' + module_id + itemid;

        fetch(url)
            .then(response => response.text())
            .then((response) => {
                wjcmodal.innerHTML = response;
                loader.remove();

                body_scrolloff('add');

                wjcmodal.classList.add('show');

                executeScriptElements(wjcmodal);

                wjcmodal.querySelector('.modal-header .close').addEventListener('click', (event) => {
                   wjcmodal_remove(wjcmodal);
                });

                document.dispatchEvent(new CustomEvent('wjcOnFormPopupAfterLoad', {detail: wjcmodal}));
            });
    });

    document.addEventListener('submit', (event) => {
        if (!event.target.closest('form') || !event.target.closest('.wjcallbackform')) {
            return;
        }
        event.preventDefault();

        let module_id = event.target.closest(".wjcallbackform").getAttribute('data-id');
        let loader_div = document.createElement('div');
        loader_div.id = "wjcallback-loader";
        document.body.append(loader_div);

        let loader = document.getElementById('wjcallback-loader');
        let url = '/index.php?option=com_ajax&module=wedal_joomla_callback&format=json&method=sendForm&modid=' + module_id + '&page=' + encodeURIComponent(window.location.href);
        let formdata = new FormData(event.target.closest('form'));

        if(!document.dispatchEvent(new CustomEvent('wjcOnFormBeforeSubmit', {detail: event.target, cancelable: true}))) {
            return;
        }

        fetch(url, {
            method: 'POST',
            body: formdata
        })
        .then(response => response.text())
        .then((response) => {
            loader.remove();
            let responce_obj = JSON.parse(response);

            if (!responce_obj.data.data.error) {
                document.dispatchEvent(new CustomEvent('wjcOnFormAfterSubmit', {detail: event.target}));

                event.target.closest('form').querySelector('.modal-footer').style.display = 'none';
                event.target.closest('form').querySelector('.modal-body').innerHTML = responce_obj.data.data.message;

                if (ya_counter && event.target.closest('form').getAttribute('data-ym-aimid')) {
                    ym(ya_counter, 'reachGoal', event.target.closest('form').getAttribute('data-ym-aimid'));
                }
            } else {
                alert(responce_obj.data.data.message);
            }
         });
    });

    document.addEventListener('mousedown', (event) => {
        let wjcmodal = document.getElementById('wjcallback-modal');

        if (!wjcmodal) {
            return;
        }

        if (!wjcmodal.querySelector('.wjcallbackform').contains(event.target)) {
            wjcmodal_remove(wjcmodal);
        }
    });
});

function wjcmodal_remove(wjcmodal) {

    if(!document.dispatchEvent(new CustomEvent('wjcOnFormBeforeClose', {detail: wjcmodal, cancelable: true}))) {
        return;
    }

    wjcmodal.classList.remove('show');

    setTimeout(function () {
        wjcmodal.remove();
        body_scrolloff('remove');
    }, 500);
}

function body_scrolloff(event) {
    const scrollbarWidth = parseInt(window.innerWidth) - parseInt(document.documentElement.clientWidth);

    if (event === 'add') {
        document.body.classList.add('wjcallback-body-scrolloff');
        document.body.style.paddingRight = scrollbarWidth + 'px';
    }

    if (event === 'remove') {
        document.body.classList.remove('wjcallback-body-scrolloff');
        document.body.style.paddingRight = '';
    }
}

function executeScriptElements(containerElement) {
    const scriptElements = containerElement.querySelectorAll("script");

    Array.from(scriptElements).forEach((scriptElement) => {
        const clonedElement = document.createElement("script");

        Array.from(scriptElement.attributes).forEach((attribute) => {
            clonedElement.setAttribute(attribute.name, attribute.value);
        });

        clonedElement.text = scriptElement.text;

        scriptElement.parentNode.replaceChild(clonedElement, scriptElement);
    });
}