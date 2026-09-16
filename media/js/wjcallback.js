let wjcallback_ya_counter = null;

document.addEventListener('DOMContentLoaded', () => {

    if (typeof ym !== 'undefined' && Array.isArray(ym.a) && Array.isArray(ym.a[0])) {
        wjcallback_ya_counter = ym.a[0][0];
    }

    document.querySelectorAll('.wjcallbackform.embeddedform').forEach((container) => {
        wjcallback_request_form_state(container);
        wjcallback_apply_phonemask(container);
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.wjcallback-link')) {
            return;
        }
        event.preventDefault();

        wjcallback_reach_goals(event.target.closest('.wjcallback-link'));

        let modal_div = document.createElement('div');
        modal_div.id = "wjcallback-modal";
        document.body.append(modal_div);

        let loader_div = document.createElement('div');
        loader_div.id = "wjcallback-loader";
        document.body.append(loader_div);

        let loader = document.getElementById('wjcallback-loader');
        let wjcmodal = document.getElementById('wjcallback-modal');
        let module_id = event.target.closest('.wjcallback-link').getAttribute('data-id');

        let url = wjcallback_ajax_url('getForm', 'raw', module_id);

        fetch(url)
            .then(response => response.text())
            .then((response) => {
                wjcmodal.innerHTML = response;
                loader.remove();

                body_scrolloff('add');

                wjcmodal.classList.add('show');

                executeScriptElements(wjcmodal);

                wjcallback_apply_phonemask(wjcmodal.querySelector('.wjcallbackform'));

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

        let container = event.target.closest('.wjcallbackform');
        let module_id = container.getAttribute('data-id');
        let url = wjcallback_ajax_url('sendForm', 'json', module_id) + '&page=' + encodeURIComponent(window.location.href);

        if(!document.dispatchEvent(new CustomEvent('wjcOnFormBeforeSubmit', {detail: event.target, cancelable: true}))) {
            return;
        }

        let loader_div = document.createElement('div');
        loader_div.id = "wjcallback-loader";
        document.body.append(loader_div);

        let loader = document.getElementById('wjcallback-loader');

        Promise.resolve(container.wjcallback_state)
        .then(() => fetch(url, {
            method: 'POST',
            body: new FormData(event.target.closest('form'))
        }))
        .then(response => response.text())
        .then((response) => {
            loader.remove();

            let result = wjcallback_parse_response(response);

            if (!result) {
                alert(wjcallback_delivery_error());
                return;
            }

            if (!result.error) {
                document.dispatchEvent(new CustomEvent('wjcOnFormAfterSubmit', {detail: event.target}));

                event.target.closest('form').querySelector('.modal-footer').style.display = 'none';
                event.target.closest('form').querySelector('.modal-body').innerHTML = result.message;

                wjcallback_reach_goals(event.target.closest('form'));
            } else {
                alert(result.message || wjcallback_delivery_error());
            }
         })
        .catch(() => {
            let leftover_loader = document.getElementById('wjcallback-loader');

            if (leftover_loader) {
                leftover_loader.remove();
            }

            alert(wjcallback_delivery_error());
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

function wjcallback_reach_goals(element) {
    if (!element) {
        return;
    }

    let ym_aimid = element.getAttribute('data-ym-aimid');
    let ga_event = element.getAttribute('data-ga-event');

    if (ym_aimid && wjcallback_ya_counter) {
        ym(wjcallback_ya_counter, 'reachGoal', ym_aimid);
    }

    if (ga_event) {
        wjcallback_send_ga_event(ga_event);
    }
}

function wjcallback_send_ga_event(ga_event) {
    if (typeof gtag === 'function') {
        gtag('event', ga_event);

        return true;
    }

    if (Array.isArray(window.dataLayer)) {
        window.dataLayer.push({'event': ga_event});

        return true;
    }

    return false;
}

function wjcallback_ajax_url(method, format, module_id) {
    let options = Joomla.getOptions('wedal_joomla_callback');
    let itemid = options && options['itemid'] ? '&Itemid=' + options['itemid'] : '';
    let base = wjcallback_base_url();

    return base + '/index.php?option=com_ajax&module=wedal_joomla_callback&format=' + format + '&method=' + method + '&modid=' + module_id + itemid;
}

function wjcallback_base_url() {
    let options = Joomla.getOptions('wedal_joomla_callback');

    if (options && typeof options['baseurl'] === 'string') {
        return options['baseurl'].replace(/\/+$/, '');
    }

    let paths = Joomla.getOptions('system.paths');

    return paths && typeof paths['root'] === 'string' ? paths['root'].replace(/\/+$/, '') : '';
}

function wjcallback_request_form_state(container) {
    container.wjcallback_state = fetch(wjcallback_ajax_url('getFormState', 'json', container.getAttribute('data-id')))
        .then(response => response.text())
        .then((response) => {
            let state = wjcallback_parse_response(response);

            return state && state.token ? wjcallback_set_token(container, state.token) : false;
        })
        .catch(() => false);

    return container.wjcallback_state;
}

function wjcallback_apply_phonemask(container) {
    if (!container || typeof Maska === 'undefined' || typeof Maska.MaskInput !== 'function') {
        return false;
    }

    let mask = container.getAttribute('data-phonemask');
    let input = mask ? container.querySelector('input.phone') : null;

    if (!input) {
        return false;
    }

    if (input.wjcallback_mask) {
        return true;
    }

    input.wjcallback_mask = new Maska.MaskInput(input, {mask: mask, eager: true});

    return true;
}

function wjcallback_set_token(container, name) {
    let form = container.querySelector('form');

    if (!form) {
        return false;
    }

    let field = form.querySelector('input.wjcallback-token');

    if (!field) {
        field = document.createElement('input');
        field.type = 'hidden';
        field.className = 'wjcallback-token';
        form.append(field);
    }

    field.name = name;
    field.value = '1';

    return true;
}

function wjcallback_parse_response(response) {
    let parsed;

    try {
        parsed = JSON.parse(response);
    } catch (e) {
        return null;
    }

    let payload = parsed && typeof parsed === 'object' ? parsed.data : null;

    return payload && typeof payload === 'object' ? payload : null;
}

function wjcallback_delivery_error() {
    return Joomla.Text._('MOD_WEDAL_JOOMLA_CALLBACK_DELIVERY_ERROR');
}

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