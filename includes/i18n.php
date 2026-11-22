<?php
if (!defined('ABSPATH')) exit;

function cmsf_is_english_locale() {
    $locale = strtolower((string) get_locale());
    return strpos($locale, 'uk') !== 0;
}

/** Ukrainian source strings => English admin strings. */
function cmsf_i18n_dictionary() {
    static $map = null;
    if ($map !== null) return $map;
    $pairs = [
      'Пошук сайтів'=>'Website search','Пошук'=>'Search','Pro / Активація'=>'Pro / Activation','Свій список доменів'=>'Custom domain list','Свій список'=>'Custom list',
      'Збережені списки'=>'Saved lists','Списки'=>'Lists','Шаблони листів'=>'Email templates','Налаштування'=>'Settings','Журнал'=>'Log','+ Новий шаблон'=>'+ New template','Змінні:'=>'Variables:',
      'UA Web Finder 2.6.0 — пошук сайтів'=>'UA Web Finder 2.6.0 — Website search',
      'Шаблон пошуку (збережені параметри)'=>'Search preset (saved parameters)','— обрати збережений шаблон —'=>'— select a saved preset —',
      'Зберегти поточні параметри як шаблон'=>'Save current settings as a preset','Видалити шаблон'=>'Delete preset',
      'Назва списку'=>'List name','Країна'=>'Country','Мова сайту'=>'Website language','Україна'=>'Ukraine','Європа'=>'Europe',
      'США'=>'USA','Увесь світ'=>'Worldwide','Російська'=>'Russian','Англійська'=>'English','Українська'=>'Ukrainian','Будь-яка'=>'Any',
      'Тип сайту'=>'Website type','Будь-який'=>'Any','Лише інтернет-магазини'=>'Online stores only','Лише каталоги / оголошення'=>'Directories / classifieds only',
      'Ліміт доменів для перевірки'=>'Domain check limit','Категорії (через кому)'=>'Categories (comma-separated)',
      'Теги / ключові слова'=>'Tags / keywords','Додатковий запит (для пошуковиків)'=>'Additional search query',
      'необов’язково'=>'optional','необов\'язково'=>'optional',
      'Відсіювати сайти, на головній сторінці яких немає жодної з вказаних категорій / тегів'=>'Exclude websites whose homepage contains none of the specified categories / tags',
      'CMS / платформа / плагін (можна кілька; без вибору = будь-яка визначена CMS)'=>'CMS / platform / plugin (select multiple; leave empty for any detected CMS)',
      'CMS / платформа / плагін (без вибору = будь-яка визначена CMS)'=>'CMS / platform / plugin (leave empty for any detected CMS)',
      'Де шукати кандидатів'=>'Candidate sources','Список доменів зони (рекомендовано)'=>'Domain-zone list (recommended)',
      'Список доменів зони'=>'Domain-zone list',
      '— безкоштовний рейтинг популярних сайтів (Majestic): потрібні зони беруться звідти, а кожен домен перевіряється по коду. Категорії/теги тут не використовуються.'=>'A free ranking of popular websites (Majestic): domains from the selected zones are retrieved and checked by their code. Categories/tags are not used here.',
      'шукають за категоріями/тегами, але з сервера їх часто блокують; заблокований пошуковик плагін сам пропускає. Найкраще залишати обидва.'=>'Search by categories/tags, but servers often block them. The plugin automatically skips blocked search engines. It is best to keep both sources enabled.',
      'Пошуковики (Bing, DuckDuckGo, Mojeek)'=>'Search engines (Bing, DuckDuckGo, Mojeek)',
      'Почати пошук'=>'Start search','Зупинити'=>'Stop','Знайдені сайти'=>'Found websites','Результат'=>'Results',
      'Вставте домени або завантажте файл TXT/CSV. Плагін відвідає кожен сайт і визначить CMS, версію, країну, мову та контакти. Результат зберігається як окремий список.'=>'Paste domains or upload a TXT/CSV file. The plugin will visit each website and detect its CMS, version, country, language and contacts. Results are saved as a separate list.',
      'Домени (по одному в рядок, через кому чи пробіл)'=>'Domains (one per line, comma- or space-separated)',
      'Відсіяти за параметрами (інакше збережуться всі доступні сайти з визначеною CMS)'=>'Apply filters (otherwise all reachable websites with a detected CMS are saved)',
      'Перевірити домени'=>'Check domains','До всіх списків'=>'Back to all lists','Назва шаблону'=>'Template name',
      'Тема (група)'=>'Topic (group)','Тема листа (Subject)'=>'Email subject','Текст листа'=>'Email body','Формат листа'=>'Email format',
      'Звичайний текст'=>'Plain text','Зберегти'=>'Save','Зберегти як новий'=>'Save as new','Попередній перегляд'=>'Preview',
      'Відправка листів'=>'Email delivery','Ім’я відправника'=>'Sender name','Ім\'я відправника'=>'Sender name',
      'Email відправника'=>'Sender email','Пауза між листами, сек.'=>'Delay between emails, sec.',
      'Підпис / футер (додається до кожного листа)'=>'Signature / footer (added to every email)',
      'Перевірка сайтів'=>'Website checks','Паралельних перевірок (1–8)'=>'Concurrent checks (1–8)',
      'Роздільник у CSV'=>'CSV delimiter','Остання версія CMS'=>'Latest CMS version',
      'Визначити зараз автоматично'=>'Detect automatically now','Зберегти налаштування'=>'Save settings',
      'Тестовий лист'=>'Test email','Надіслати тестовий лист'=>'Send test email',
      'Визначається автоматично (WordPress API та GitHub). Якщо автоматика помиляється або недоступна — впишіть версію вручну, вона матиме пріоритет.'=>'Detected automatically (WordPress API and GitHub). If automatic detection is incorrect or unavailable, enter a version manually; it will take priority.',
      'Перевірте, що пошта сервера взагалі відправляє листи.'=>'Check whether the server can send email.',
      'Reply-To (необов\'язково)'=>'Reply-To (optional)','; (для українського Excel)'=>'; (for Ukrainian Excel)',', (кома)'=>', (comma)',
      'Зберегти налаштування Freemius'=>'Save Freemius settings','Пробний період'=>'Trial period','Пробний період:'=>'Trial period:','залишилося '=>' remaining ',' днів із 30.'=>' days out of 30.','Переглянути Pro та активувати'=>'View Pro and activate','Пробний період завершився.'=>'The trial period has ended.',
      'Збір email'=>'Email collection','CMS Web Finder — збір контактів'=>'CMS Web Finder — Contact collection','1. Зібрати email із сайту'=>'1. Collect emails from a website','Назва списку'=>'List name','Максимальна кількість сторінок'=>'Maximum number of pages','Глибина переходів'=>'Crawl depth','Почати збір'=>'Start collection','Пауза'=>'Pause','Продовжити'=>'Resume','Очікування запуску.'=>'Waiting to start.','2. Збережені списки контактів'=>'2. Saved contact lists','Домен'=>'Domain','Сторінок'=>'Pages','Статус'=>'Status','3. Перегляд знайдених email'=>'3. View collected emails','Виберіть список'=>'Select a list','— Спочатку виберіть список —'=>'— Select a list first —','Пошук email або сторінки'=>'Search email or page','Фільтр'=>'Filter','Усі адреси'=>'All addresses','Не в блеклисті'=>'Not blacklisted','У блеклисті'=>'Blacklisted','Показати список'=>'Show list','Копіювати всі email'=>'Copy all emails','Виберіть список, щоб переглянути його адреси.'=>'Select a list to view its addresses.','Розгорнути список email'=>'Expand email list','Сторінка, де знайдено'=>'Source page','Дата'=>'Date','Блеклист'=>'Blacklist','Адреси ще не завантажені.'=>'Addresses have not been loaded yet.','← Назад'=>'← Back','Сторінка 1'=>'Page 1','Далі →'=>'Next →','4. Створити кампанію'=>'4. Create a campaign','Список адрес'=>'Address list','— Оберіть список —'=>'— Select a list —','Шаблон листа'=>'Email template','— Оберіть шаблон —'=>'— Select a template —','Назва кампанії'=>'Campaign name','Перед кожним листом адреса повторно перевіряється за глобальним блеклистом.
                У кожному листі є персональне посилання для відписки.'=>'Each address is checked against the global blacklist before sending. Each email contains a personal unsubscribe link.','Створити та запустити кампанію'=>'Create and launch campaign','5. Глобальний блеклист'=>'5. Global blacklist','Причина блокування'=>'Block reason','Додати до блеклиста'=>'Add to blacklist','Невідома помилка'=>'Unknown error','Помилка:'=>'Error:','Не вдалося з’єднатися з WordPress. '=>'Could not connect to WordPress. ','Сервер повернув HTML замість JSON. '=>'The server returned HTML instead of JSON. ','Відповідь: '=>'Response: ','HTTP помилка '=>'HTTP error ','WordPress повернув невідому помилку.'=>'WordPress returned an unknown error.','— Виберіть список —'=>'— Select a list —','Сервер повернув неправильний формат списків.'=>'The server returned an invalid list format.','Спочатку виберіть список.'=>'Select a list first.','Завантаження адрес...'=>'Loading addresses...','Неправильний формат відповіді зі списком адрес.'=>'Invalid response format for the address list.','За цими параметрами адрес не знайдено.'=>'No addresses found for these filters.','Немає даних'=>'No data','Не заблокована'=>'Not blocked','Знайдено записів: '=>'Records found: ','Сторінка '=>'Page ',' із '=>' of ','Вкажіть домен сайту.'=>'Enter a website domain.','Створення списку...'=>'Creating list...','Сервер не повернув ID списку.'=>'The server did not return a list ID.','Сканування розпочато.'=>'Scanning started.','Сканування завершено.'=>'Scanning completed.','Сканування триває...'=>'Scanning in progress...','Перевірено сторінок: '=>'Pages checked: ',' · Знайдено email: '=>' · Emails found: ',' · У черзі: '=>' · Queued: ','Спочатку запустіть сканування.'=>'Start scanning first.','Сканування призупинено.'=>'Scanning paused.','Сканування зупинено.'=>'Scanning stopped.','Сканування продовжено.'=>'Scanning resumed.','Позначте адреси для видалення.'=>'Select addresses to delete.','Видалити вибрані адреси зі списку?'=>'Delete selected addresses from the list?','Видалено записів: '=>'Records deleted: ','Браузер не дозволив скопіювати текст.'=>'The browser did not allow copying the text.','У списку поки немає адрес.'=>'There are no addresses in this list yet.','Скопійовано адрес: '=>'Addresses copied: ','Введіть email.'=>'Enter an email address.','Блеклист поки порожній.'=>'The blacklist is empty.','Причина'=>'Reason','Джерело'=>'Source','Дія'=>'Action','Розблокувати'=>'Unblock','Розблокувати '=>'Unblock ','Оберіть список і шаблон листа.'=>'Select a list and an email template.','Запустити кампанію? '=>'Launch the campaign? ','Перед відправленням кожна адреса '=>'Before sending, each address ','перевірятиметься за блеклистом.'=>'will be checked against the blacklist.','Створення кампанії...'=>'Creating campaign...','Сервер не повернув ID кампанії.'=>'The server did not return a campaign ID.','Пошук'=>'Search','Назва кампанії'=>'Campaign name','Копіювати'=>'Copy','Експорт CSV'=>'Export CSV','Видалити вибрані'=>'Delete selected','Показати список'=>'Show list','Зупинити'=>'Stop','Почати збір'=>'Start collection','Збережені списки'=>'Saved lists','Списки'=>'Lists','Дата'=>'Date','Статус'=>'Status','Активуйте UA Web Finder Pro'=>'Activate UA Web Finder Pro','щоб продовжити роботу.'=>'to continue using the plugin.','Pro активовано.'=>'Pro is active.',
      'Пробний період завершився'=>'Trial period ended','Pro активовано'=>'Pro is active',
      'Після завершення пробного періоду запуск нових пошуків, перевірок і розсилок буде призупинено. Збережені дані залишаться в базі WordPress.'=>'After the trial ends, new searches, checks and email campaigns will be paused. Your saved data will remain in the WordPress database.',
      'Щоб продовжити пошук, перевірку сайтів і розсилки, активуйте UA Web Finder Pro.'=>'Activate UA Web Finder Pro to continue searching, checking websites and sending emails.',
      'Що входить у Pro'=>'What is included in Pro','Довічний доступ Pro активовано для цієї інсталяції WordPress.'=>'Lifetime Pro access is activated for this WordPress installation.',
      'Придбати / активувати Pro через Freemius'=>'Purchase / activate Pro via Freemius',
      'Посилання на оплату ще не додано. Вставте hosted checkout URL нижче.'=>'The checkout link has not been added yet. Paste your hosted checkout URL below.',
      'Ціна та валюта відображаються на сторінці захищеної оплати Freemius — плагін не підміняє ціну локальним значенням.'=>'The price and currency are displayed on Freemius secure checkout. The plugin does not override the price.',
      'Підключення Freemius (без SDK)'=>'Freemius setup (no SDK)','Hosted Checkout URL'=>'Hosted Checkout URL',
      'Freemius Product Secret Key'=>'Freemius Product Secret Key','Lifetime Plan ID'=>'Lifetime Plan ID',
      'Пробний період завершився. Активуйте UA Web Finder Pro у розділі «Pro / Активація».'=>'The trial has ended. Activate UA Web Finder Pro under “Pro / Activation”.',
      'Немає прав'=>'Permission denied','Помилка'=>'Error','Сервер повернув не JSON (можливо, помилка PHP або тайм-аут)'=>'The server returned non-JSON (possible PHP error or timeout)','Результат перевірки не збережено'=>'The check result was not saved','Сервер постійно повертає помилки:'=>'The server keeps returning errors:','Пошук: домен, назва, email, нотатка'=>'Search: domain, name, email, note','Знайдені'=>'Found','Відхилені'=>'Rejected','Недоступні'=>'Unavailable','Усі'=>'All','Відправка: усі'=>'Delivery: all','Надіслано успішно'=>'Sent successfully','Помилка відправки'=>'Sending failed','Версія від'=>'Version from','Версія до'=>'Version to','Є email'=>'Has email','Нотатки: усі'=>'Notes: all','З нотатками'=>'With notes','Без нотаток'=>'Without notes','Застаріла версія'=>'Outdated version','Пропускати тих, кому цей шаблон уже надіслано'=>'Skip recipients who have already received this template','Редагувати'=>'Edit','Усі шаблони'=>'All templates','Тема листа'=>'Email subject','Текст листа (змінні: {url} {domain} {site} {site_title} {cms} {version} {latest_version})'=>'Email body (variables: {url} {domain} {site} {site_title} {cms} {version} {latest_version})','Скасувати'=>'Cancel','Надіслано'=>'Sent','немає email'=>'no email','застаріла'=>'outdated','Збережено у списках:'=>'Saved in lists:','Вибрано:'=>'Selected:','Вибрати всі '=>'Select all ','Тут поки нічого немає'=>'Nothing here yet','Продовжити перевірку ('=>'Continue checking (','Спершу позначте сайти галочками.'=>'Select websites using the checkboxes first.','Видалити вибрані сайти ('=>'Delete selected websites (','Перевірено '=>'Checked ','Перевірку завершено'=>'Check completed','Без теми'=>'No topic','— оберіть шаблон —'=>'— select a template —','+ Створити новий шаблон…'=>'+ Create a new template…','Зберегти зміни'=>'Save changes','Зберегти шаблон'=>'Save template','Уже надіслані листи та відмітки залишаться.'=>'Previously sent emails and status records will remain.','Шаблон збережено і вибрано'=>'Template saved and selected','Оберіть шаблон листа (або створіть новий і збережіть його).'=>'Select an email template (or create and save a new one).','Позначте сайти галочками або натисніть «Вибрати всі».'=>'Select websites using the checkboxes or click “Select all”.','Надіслати лист «'=>'Send email “','на '=>' to ',' сайтів?'=>' websites?','Оброблено '=>'Processed ','успішно:'=>'successful:','помилок:'=>'errors:','пропущено:'=>'skipped:',' — зупинено'=>' — stopped',' — завершено'=>' — completed','Назва шаблону пошуку (така сама назва оновить існуючий):'=>'Search preset name (an existing preset with the same name will be updated):','Параметри завантажено'=>'Settings loaded','Шаблон збережено'=>'Preset saved','Спершу оберіть шаблон зі списку.'=>'Select a template from the list first.','Видалити шаблон пошуку «'=>'Delete search preset “','Стан списку зон — '=>'Domain-zone list status — ','Завантажується автоматично при першому пошуку (~100 МБ, кілька хвилин).'=>'It downloads automatically on the first search (~100 MB, a few minutes).','готовий ('=>'ready (',' доменів)'=>' domains)','не завантажено'=>'not downloaded','Вставте домени або виберіть файл.'=>'Paste domains or choose a file.','Оберіть, де шукати кандидатів.'=>'Choose where to search for candidates.','Оберіть країну.'=>'Choose a country.','Будь-яка CMS'=>'Any CMS','Свій список · '=>'Custom list · ','Створення списку…'=>'Creating list…','Список «'=>'List “','» створено (id '=>'” created (id ','Додано доменів: '=>'Domains added: ',' із '=>' of ','Пошук '=>'Search ','Заблоковані пошуковики: '=>'Blocked search engines: ','Завантаження списку доменів зони .' =>'Downloading the domain list for zone .','Список зон підготовлено.'=>'Zone list prepared.','Завантаження списку доменів: '=>'Downloading domain list: ','Додаю домени зони .' =>'Adding domains from zone .',' до черги…'=>' to queue…','Зона .' =>'Zone .','Перевірка по коду…'=>'Checking website code…','підходять:'=>'matching:','відхилено:'=>'rejected:','сайт недоступний:'=>'website unavailable:','збої плагіна/сервера:'=>'plugin/server errors:','останній:'=>'last:','Помилка:'=>'Error:','Зупиняю після поточних запитів…'=>'Stopping after current requests…','Список не знайдено'=>'List not found','Порожня назва'=>'Name cannot be empty',
      'Знайдений'=>'Found','У черзі'=>'Queued','Відхилений'=>'Rejected','Недоступний'=>'Unavailable',
      'Вибрати всі на сторінці'=>'Select all on this page','Вибрати всі'=>'Select all','Зняти вибір'=>'Clear selection',
      'Експорт CSV'=>'Export CSV','Видалити вибрані'=>'Delete selected','Повторити недоступні'=>'Retry unavailable',
      'Шаблон листа'=>'Email template','Надіслати вибраним'=>'Send to selected','Зупинити перевірку'=>'Stop checking','Обрати шаблон'=>'Select a template','Статус списку'=>'List status',
      'Сайт ↕'=>'Website ↕','Версія'=>'Version','Мова'=>'Language','Телефон'=>'Phone','Нотатка'=>'Note','Відправлено'=>'Sent',
      'Знайдено:'=>'Found:','У черзі:'=>'Queued:','Відхилено:'=>'Rejected:','Недоступні:'=>'Unavailable:','Надіслано:'=>'Sent:',
      'Помилки відправки:'=>'Email errors:','Вибрано:'=>'Selected:','Сторінка '=>'Page ','всього'=>'total',
      'Немає email'=>'No email','Застаріла'=>'Outdated','Збережено'=>'Saved','Завантаження списку доменів'=>'Downloading domain list','Стан списку зон'=>'Domain-zone list status','Видалити цей сайт зі списку?'=>'Delete this website from the list?',
      'Спершу оберіть шаблон зі списку.'=>'Select a template from the list first.',
      'Видалити шаблон?'=>'Delete this template?','Шаблонів ще немає.'=>'No templates yet.',
      'Створіть перший у розділі «Пошук».'=>'Create your first one under “Search”.',
      'Відкрити'=>'Open','Перейменувати'=>'Rename','Видалити'=>'Delete','Створено'=>'Created',
      'Сайтів'=>'Websites','Надіслано успішно'=>'Sent successfully','Помилка відправки'=>'Sending failed',
      'З нотатками'=>'With notes','Без нотаток'=>'Without notes','Нотатки: усі'=>'Notes: all',
      'Відправка: усі'=>'Delivery: all','Ще не надсилали'=>'Not sent yet','Усі CMS'=>'All CMS',
      'Версія від'=>'Version from','Версія до'=>'Version to','Є email'=>'Has email',
      'Параметри завантажено'=>'Settings loaded','Шаблон збережено'=>'Preset saved','Спершу оберіть шаблон'=>'Select a preset first',
      'Видалено'=>'Deleted','Вставте домени або виберіть файл.'=>'Paste domains or choose a file.',
      'Оберіть, де шукати кандидатів.'=>'Choose where to search for candidates.','Оберіть країну.'=>'Choose a country.',
      'Створення списку…'=>'Creating list…','Перевірка по коду…'=>'Checking website code…',
      'Зупинено.'=>'Stopped.','Готово.'=>'Done.','Помилка:'=>'Error:',
      'Завантаження…'=>'Loading…','Оброблено '=>'Processed ','успішно:'=>'successful:','помилок:'=>'errors:',
      'Пропущено'=>'Skipped','Скасувати'=>'Cancel','Зберегти зміни'=>'Save changes','Зберегти шаблон'=>'Save template',
      'Список'=>'List','Світ'=>'World','Без теми'=>'No topic','Пошук за параметрами'=>'Search by criteria',
    ];
    $map = $pairs;
    return $map;
}

function cmsf_translate_admin_html($html) {
    if (!cmsf_is_english_locale() || !is_string($html) || $html === '') return $html;
    // Keep executable JavaScript and CSS intact; translate rendered admin HTML only.
    $chunks = preg_split('~(<script\\b[^>]*>.*?</script>|<style\\b[^>]*>.*?</style>)~is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $dict = cmsf_i18n_dictionary();
    $safe = array_filter($dict, function ($key) { return function_exists('mb_strlen') ? mb_strlen($key, 'UTF-8') >= 8 : strlen($key) >= 16; }, ARRAY_FILTER_USE_KEY);
    foreach ($chunks as $i => $chunk) {
        if ($i % 2 !== 0) continue;
        $chunk = preg_replace_callback('~>([^<>]+)<~u', function ($m) use ($dict, $safe) {
            $raw = $m[1];
            $decoded = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
            $trimmed = trim($decoded);
            if (isset($dict[$trimmed])) {
                $leading = substr($decoded, 0, strlen($decoded) - strlen(ltrim($decoded)));
                $trailing = substr($decoded, strlen(rtrim($decoded)));
                return '>' . esc_html($leading . $dict[$trimmed] . $trailing) . '<';
            }
            return '>' . strtr($raw, $safe) . '<';
        }, $chunk);
        $chunk = preg_replace_callback('/\\b(placeholder|title|aria-label)=(["\\\'])(.*?)\\2/i', function ($m) use ($dict, $safe) {
            $value = html_entity_decode($m[3], ENT_QUOTES, 'UTF-8');
            $translated = isset($dict[$value]) ? $dict[$value] : strtr($value, $safe);
            return $m[1] . '=' . $m[2] . esc_attr($translated) . $m[2];
        }, $chunk);
        $chunks[$i] = $chunk;
    }
    return implode('', $chunks);
}
add_action('admin_init', function () {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (strpos($page, 'cmsf') === 0 || $page === 'cms-checker') ob_start('cmsf_translate_admin_html');
}, 0);
