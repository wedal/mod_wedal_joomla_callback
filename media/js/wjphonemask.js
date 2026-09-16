/*
 * Маска ввода телефона.
 *
 * Файл самостоятельный и от модуля не зависит: подключите его к шаблону и добавьте
 * нужному полю атрибут data-phonemask. Значение атрибута — формат номера, где
 * знак # означает цифру, а остальные символы выводятся как есть:
 *
 *     <input type="tel" name="phone" data-phonemask="+7 (###) ###-##-##">
 *
 * Пустое поле показывает формат в плейсхолдере, поэтому код страны виден до ввода.
 * Полноту номера проверяет браузер: атрибут pattern строится из той же маски, и
 * неполный номер форму не отправит. Пустое необязательное поле проверку проходит,
 * пустое обязательное браузер отклонит сам.
 *
 * Атрибут можно повесить и на контейнер. Тогда маску получит поле input.phone внутри
 * него, а если такого нет — первое поле ввода. 
 *
 * Разметку после загрузки страницы, обработает вызов
 * WJPhoneMask.apply(element). Отдельное поле можно взять и напрямую:
 * WJPhoneMask.applyTo(input, '+7 (###) ###-##-##').
 */
var WJPhoneMask = {

    /**
     * Применяет маску ко всем полям с атрибутом data-phonemask.
     *
     * @param   Element  root  Где искать поля. По умолчанию — весь документ.
     *
     * @return  number  Сколько полей получили маску.
     */
    apply(root) {
        let scope = root || document;
        let elements = [];

        if (typeof scope.hasAttribute === 'function' && scope.hasAttribute('data-phonemask')) {
            elements.push(scope);
        }

        if (typeof scope.querySelectorAll === 'function') {
            elements = elements.concat(Array.prototype.slice.call(scope.querySelectorAll('[data-phonemask]')));
        }

        let applied = 0;

        elements.forEach((element) => {
            if (WJPhoneMask.applyTo(WJPhoneMask.field(element), element.getAttribute('data-phonemask'))) {
                applied++;
            }
        });

        return applied;
    },

    /**
     * Поле, которому принадлежит атрибут: либо оно само, либо поле внутри контейнера.
     *
     * @param   Element  element  Элемент с атрибутом data-phonemask.
     *
     * @return  Element|null
     */
    field(element) {
        if (element.tagName === 'INPUT') {
            return element;
        }

        if (typeof element.querySelector !== 'function') {
            return null;
        }

        return element.querySelector('input.phone') || element.querySelector('input');
    },

    /**
     * Включает маску на конкретном поле.
     *
     * @param   Element  input  Поле ввода.
     * @param   string   mask   Формат номера, цифры обозначены знаком #.
     *
     * @return  bool  false — поле уже с маской либо маска пустая или без единого места под цифру.
     */
    applyTo(input, mask) {
        if (!input || !mask || mask.indexOf('#') === -1 || input.wjphonemask) {
            return false;
        }

        input.wjphonemask = mask;
        input.placeholder = mask.replace(/#/g, '_');
        input.pattern = mask.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/#/g, '\\d');
        input.addEventListener('input', (event) => WJPhoneMask.format(input, event.inputType));

        return true;
    },

    /**
     * Приводит значение поля к маске после каждого ввода.
     *
     * @param   Element  input      Поле с маской.
     * @param   string   inputtype  Вид правки из события input.
     *
     * @return  void
     */
    format(input, inputtype) {
        let mask = input.wjphonemask;
        let digits = WJPhoneMask.digits(mask, input.value);

        if (inputtype && inputtype.indexOf('delete') === 0 && WJPhoneMask.masked(mask, digits) !== input.value) {
            digits = digits.slice(0, -1);
        }

        input.value = digits === '' ? '' : WJPhoneMask.masked(mask, digits);
    },

    /**
     * Возвращает цифры, введённые пользователем. Разделители маски и её собственные цифры (код страны) пропускаются.
     *
     * @param   string  mask   Формат номера.
     * @param   string  value  Текущее значение поля.
     *
     * @return  string
     */
    digits(mask, value) {
        let digits = '';
        let pos = 0;

        for (let i = 0; i < mask.length && pos < value.length; i++) {
            if (mask.charAt(i) !== '#') {
                if (value.charAt(pos) === mask.charAt(i)) {
                    pos++;
                }

                continue;
            }

            while (pos < value.length && (value.charAt(pos) < '0' || value.charAt(pos) > '9')) {
                pos++;
            }

            if (pos < value.length) {
                digits += value.charAt(pos++);
            }
        }

        return digits;
    },

    /**
     * Собирает значение поля по маске. Разделители перед следующей цифрой подставляются сразу, чтобы их не набирали вручную.
     *
     * @param   string  mask    Формат номера.
     * @param   string  digits  Введённые цифры.
     *
     * @return  string
     */
    masked(mask, digits) {
        let value = '';
        let pos = 0;

        for (let i = 0; i < mask.length; i++) {
            if (mask.charAt(i) !== '#') {
                value += mask.charAt(i);

                continue;
            }

            if (pos >= digits.length) {
                break;
            }

            value += digits.charAt(pos++);
        }

        return value;
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => WJPhoneMask.apply(document));
} else {
    WJPhoneMask.apply(document);
}
