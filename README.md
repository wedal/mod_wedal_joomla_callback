<div align="center">

# Wedal Joomla Callback

**A free AJAX callback form for Joomla — one module, unlimited forms, no extra components.**

[![Version](https://img.shields.io/badge/version-2.2.0-blue.svg)](https://github.com/wedal/mod_wedal_joomla_callback)
[![Joomla](https://img.shields.io/badge/Joomla-4.1%20%7C%205.x%20%7C%206.x-5091cd.svg)](https://www.joomla.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPLv3-green.svg)](LICENSE)

[English](#english) · [Русский](#русский)

</div>

---

## English

### What it is

Wedal Joomla Callback puts a contact form on your Joomla site with a few clicks. The form opens in a popup on click, or sits embedded in a page position. Everything runs over AJAX, so the page never reloads and the form markup never ships with the page.

The module was built for real client sites, where the form always has to look and behave a little differently. Customisation comes first: fields, layouts, CSS classes and delivery channels are all settings, not code changes.

### Highlights

| | |
| --- | --- |
| **One module, nothing else** | No component, no plugin, no library to install alongside it. |
| **Loads on demand** | The page carries only a link. The form itself arrives by AJAX on click. |
| **Many forms, one script** | Unlimited module copies on a page share a single JS and CSS file. |
| **Popup or embedded** | The same module renders as a modal window or as an inline form. |
| **Email, Telegram, SMS** | Each request can go to several inboxes, a Telegram chat and a phone at once. |
| **Layered spam protection** | Honeypot field, minimum fill time, rate limiting and any Joomla CAPTCHA plugin. |
| **Analytics built in** | Yandex.Metrica goals and Google Analytics 4 events fire on popup open and on submit. |
| **Fully overridable** | Button, form, email body and messages are ordinary Joomla layouts. |

### Requirements

- Joomla 4.1+, 5.x or 6.x
- PHP 8.1 or newer
- A working mail configuration in Joomla for email delivery

### Installation

1. Download the installation ZIP or build it from this repository.
2. Install it in Joomla through **System → Install → Extensions**.
3. Open **Content → Site Modules**, find *Wedal Joomla Callback*, assign a position and menu items, and publish it.

The module works straight after publishing. Every setting has a sensible default. 

### How the page stays light

Only the call button reaches the browser:

```html
<a data-id="110" class="wjcallback-link " href="#">Get Callback</a>
```

There is no hidden form in the HTML and no link to the form endpoint, so the bots that harvest forms from page source find nothing. The form is fetched when a visitor clicks, and the submission is sent back over AJAX without a page reload.

### Form fields

Turn each field on or off, and make it required or optional:

- **Name**
- **Email**
- **Phone**, with an optional input mask in any format you write with `#` and separators, for example `+7 (###) ###-##-##`
- **Comment**
- **Terms of service** consent, as a link, a checkbox, or both
- **Attachment**, single or multiple, restricted to images, PDF, Word and Excel files

For a logged-in visitor the name and email fields can be prefilled from the user account.

Need something else? The **Custom fields** tab accepts any [Joomla XML form field](https://manual.joomla.org/docs/general-concepts/forms-fields/standard-fields/), including your own field types:

```xml
<field name="myfield" type="text" default="" label="Enter some text" filter="STRING" />
```

### Where requests go

**Email.** A main recipient plus any number of additional addresses. The subject line, the greeting and the thank-you text are all configurable.

**Telegram.** Create a bot, add it to a chat, then paste the bot API key and the chat ID into the settings. Requests arrive as chat messages with an intro line of your choice.

**SMS.** Sending runs through [sms.ru](https://sms.ru). You choose which fields go into the message, per-field and total length limits, and whether to transliterate Cyrillic text. The delivery status can be appended to the notification email.

### Spam protection

Protection is on by default and combines four independent layers:

1. **Honeypot field** that only automated scripts fill in.
2. **Minimum fill time**, three seconds by default, rejecting instant submissions.
3. **Rate limiting** per visitor, three requests per hour by default, with a separate module-wide ceiling.
4. **CAPTCHA**, any Joomla captcha plugin you have installed, including the site-wide default.

Requests are also protected from CSRF by a Joomla token check on every submission.

### Analytics

Set a Yandex.Metrica goal ID or a Google Analytics 4 event name for the popup-open and form-submit events, and the module fires them for you:

```js
ym('XXXXXXXX', 'reachGoal', 'aimId')
gtag('event', 'eventName')
```

The Metrica counter ID is detected automatically. When Google Analytics is installed through Google Tag Manager and no `gtag()` function exists, the event is pushed to `dataLayer` instead, where a Custom Event trigger picks it up.

### Styling and layouts

The **Decoration** tab adds your own CSS classes to the call button, the submit button, the form, the field wrappers and the outer wrapper. That is usually enough to make the form match Bootstrap, UIkit or your own framework without touching a template.

For deeper changes, override the layouts in `templates/YOUR_TEMPLATE/html/mod_wedal_joomla_callback/`:

| Layout | What it renders |
| --- | --- |
| `default.php` | The call button |
| `default_popupform.php` | The popup form |
| `default_embeddedform.php` | The embedded form |
| `default_message.php` | The body of the notification email |

Alternative layouts also appear in the module's **Advanced** tab, so different module copies can use different designs.

### JavaScript events

The module dispatches custom events on `document`, which lets you hook in without editing its code:

| Event | Fires when | Cancelable |
| --- | --- | --- |
| `wjcOnFormPopupAfterLoad` | The popup form has loaded | no |
| `wjcOnFormBeforeSubmit` | Just before the request is sent | yes |
| `wjcOnFormAfterSubmit` | After a successful submission | no |
| `wjcOnFormBeforeClose` | Just before the popup closes | yes |

```js
document.addEventListener('wjcOnFormAfterSubmit', function (event) {
    console.log('Request sent from form', event.detail);
});
```

Cancelable events stop the action when a listener calls `preventDefault()`.

### License and support

Released under the GPLv3 license. Free for any site, commercial ones included. The code is fully open, with no hidden links and no encrypted files.

Paid customisation, improvements and other services are available from the author: [wedal@wedal.ru](mailto:wedal@wedal.ru) · [wedal.ru](https://wedal.ru)

---

## Русский

### Что это

Wedal Joomla Callback добавляет на сайт Joomla форму обратной связи за несколько кликов. Форма открывается во всплывающем окне по клику или встраивается прямо в позицию шаблона. Всё работает через AJAX: страница не перезагружается, а разметка формы вообще не попадает в исходный код страницы.

Модуль писался для реальных клиентских сайтов, где форма каждый раз должна выглядеть и вести себя чуть иначе. Поэтому во главе угла простота настройки: поля, макеты, CSS-классы и каналы доставки меняются настройками, а не правкой кода.

### Главное

| | |
| --- | --- |
| **Только модуль** | Никаких дополнительных компонентов, плагинов и библиотек. |
| **Загрузка по требованию** | На странице лежит только ссылка. Сама форма приходит по AJAX при клике. |
| **Много форм, один скрипт** | Любое число копий модуля на странице использует один JS- и один CSS-файл. |
| **Всплывающая или встроенная** | Один и тот же модуль рисует модальное окно или форму прямо на странице. |
| **Email, Telegram, SMS** | Заявка уходит сразу на несколько адресов, в чат Telegram и на телефон. |
| **Многослойный антиспам** | Поле-ловушка, минимальное время заполнения, лимит частоты и любая CAPTCHA Joomla. |
| **Встроенная аналитика** | Цели Яндекс.Метрики и события Google Analytics 4 на открытие и на отправку. |
| **Полностью переопределяемый** | Кнопка, форма, письмо и сообщения — обычные макеты Joomla. |

### Требования

- Joomla 4.1+, 5.x или 6.x
- PHP 8.1 или новее
- Настроенная отправка почты в Joomla

### Установка

1. Скачайте установочный ZIP-архив или соберите его из этого репозитория.
2. Установите его в Joomla через **Система → Установка → Расширения**.
3. Откройте **Материалы → Модули сайта**, найдите *Wedal Joomla Callback*, задайте позицию и пункты меню, опубликуйте модуль.

После публикации модуль уже работает: у всех настроек есть разумные значения по умолчанию.

### Почему страница остаётся лёгкой

В браузер попадает только кнопка вызова:

```html
<a data-id="110" class="wjcallback-link " href="#">Заказать обратный звонок</a>
```

В HTML нет ни скрытой формы, ни ссылки на её обработчик, поэтому боты, собирающие формы из исходного кода страницы, не находят ничего. Форма подгружается по клику посетителя, а заявка уходит по AJAX без перезагрузки страницы.

### Поля формы

Каждое поле включается и выключается отдельно, обязательность настраивается:

- **Имя**
- **Email**
- **Телефон** с маской ввода в любом формате из символов `#` и разделителей, например `+7 (###) ###-##-##`
- **Комментарий**
- **Согласие с условиями** — ссылкой, чекбоксом или и тем, и другим
- **Вложение**, одно или несколько, с ограничением по типам: изображения, PDF, Word и Excel

Авторизованному посетителю имя и email можно подставить из данных его учётной записи.

Нужно что-то ещё? На вкладке **Дополнительные поля** принимается любое [XML-поле формы Joomla](https://manual.joomla.org/docs/general-concepts/forms-fields/standard-fields/), включая собственные типы полей:

```xml
<field name="myfield" type="text" default="" label="Введите текст" filter="STRING" />
```

### Куда уходят заявки

**Email.** Основной получатель плюс произвольное число дополнительных адресов. Тема письма, заголовок формы и текст благодарности настраиваются.

**Telegram.** Создайте бота, добавьте его в чат, укажите в настройках API-ключ бота и ID чата. Заявки приходят сообщениями в чат с вашим вступительным текстом.

**SMS.** Отправка идёт через сервис [sms.ru](https://sms.ru) и оплачивается отдельно. Вы выбираете, какие поля попадут в сообщение, задаёте лимиты длины по полю и по всему тексту, включаете транслитерацию. Статус отправки можно добавить в письмо-уведомление.

### Защита от спама

Защита включена по умолчанию и складывается из четырёх независимых слоёв:

1. **Поле-ловушка**, которое заполняют только автоматические скрипты.
2. **Минимальное время заполнения**, по умолчанию три секунды, отсекает мгновенные отправки.
3. **Лимит частоты** по посетителю, по умолчанию три заявки в час, плюс отдельный общий потолок модуля.
4. **CAPTCHA** — любой установленный плагин капчи Joomla, в том числе выбранный на сайте по умолчанию.

Каждая отправка дополнительно проверяется токеном Joomla, что закрывает CSRF-атаки.

### Аналитика

Укажите идентификатор цели Яндекс.Метрики или имя события Google Analytics 4 для открытия формы и для отправки — модуль вызовет их сам:

```js
ym('XXXXXXXX', 'reachGoal', 'aimId')
gtag('event', 'eventName')
```

Номер счётчика Метрики определяется автоматически. Если Google Analytics подключён через Google Tag Manager и функции `gtag()` на странице нет, событие уходит в очередь `dataLayer`, где его ловит триггер Custom Event.

### Оформление и макеты

Вкладка **Оформление** добавляет ваши CSS-классы кнопке вызова, кнопке отправки, форме, обёрткам полей и внешней обёртке. Обычно этого достаточно, чтобы форма попала в стиль Bootstrap, UIkit или вашего фреймворка без правки шаблона.

Для более глубоких изменений переопределите макеты в `templates/ВАШ_ШАБЛОН/html/mod_wedal_joomla_callback/`:

| Макет | Что выводит |
| --- | --- |
| `default.php` | Кнопку вызова |
| `default_popupform.php` | Всплывающую форму |
| `default_embeddedform.php` | Встроенную форму |
| `default_message.php` | Тело письма-уведомления |

Альтернативные макеты появляются на вкладке **Дополнительно**, поэтому разные копии модуля могут использовать разное оформление.

### JavaScript-события

Модуль отправляет собственные события на `document`, что позволяет подключаться к нему без правки кода:

| Событие | Когда срабатывает | Отменяемое |
| --- | --- | --- |
| `wjcOnFormPopupAfterLoad` | Всплывающая форма загружена | нет |
| `wjcOnFormBeforeSubmit` | Непосредственно перед отправкой заявки | да |
| `wjcOnFormAfterSubmit` | После успешной отправки | нет |
| `wjcOnFormBeforeClose` | Непосредственно перед закрытием окна | да |

```js
document.addEventListener('wjcOnFormAfterSubmit', function (event) {
    console.log('Заявка отправлена из формы', event.detail);
});
```

Отменяемое событие прерывает действие, если обработчик вызовет `preventDefault()`.

### Лицензия и поддержка

Модуль распространяется по лицензии GPLv3. Он бесплатен для любых сайтов, включая коммерческие. Код полностью открыт, в нём нет скрытых ссылок и зашифрованных файлов.

Доработки, улучшения и другие услуги можно заказать у автора: [wedal@wedal.ru](mailto:wedal@wedal.ru) · [wedal.ru](https://wedal.ru)
