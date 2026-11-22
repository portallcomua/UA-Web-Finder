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
      'CMS / платформа / плагін (можна кілька; без вибору = будь-яка визначена CMS)'=>'CMS / platform / plugin (select multiple; leave empty for any detected CMS)',\n      'CMS / платформа / плагін (без вибору = будь-яка визначена CMS)'=>'CMS / platform / plugin (leave empty for any detected CMS)',\n      'Де шукати кандидатів'=>'Candidate sources','Список доменів зони (рекомендовано)'=>'Domain-zone list (recommended)',\n      'Список доменів зони'=>'Domain-zone list',\n      '— безкоштовний рейтинг популярних сайтів (Majestic): потрібні зони беруться звідти, а кожен домен перевіряється по коду. Категорії/теги тут не використовуються.'=>'A free ranking of popular websites (Majestic): domains from the selected zones are retrieved and checked by their code. Categories/tags are not used here.',\n      'шукають за категоріями/тегами, але з сервера їх часто блокують; заблокований пошуковик плагін сам пропускає. Найкраще залишати обидва.'=>'Search by categories/tags, but servers often block them. The plugin automatically skips blocked search engines. It is best to keep both sources enabled.',
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
      'Тестовий лист'=>'Test email','Надіслати тестовий лист'=>'Send test email',\n      'Визначається автоматично (WordPress API та GitHub). Якщо автоматика помиляється або недоступна — впишіть версію вручну, вона матиме пріоритет.'=>'Detected automatically (WordPress API and GitHub). If automatic detection is incorrect or unavailable, enter a version manually; it will take priority.',\n      'Перевірте, що пошта сервера взагалі відправляє листи.'=>'Check whether the server can send email.',\n      'Reply-To (необов\'язково)'=>'Reply-To (optional)','; (для українського Excel)'=>'; (for Ukrainian Excel)',', (кома)'=>', (comma)',
      'Зберегти налаштування Freemius'=>'Save Freemius settings','Пробний період'=>'Trial period',
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
      'Немає прав'=>'Permission denied','Список не знайдено'=>'List not found','Порожня назва'=>'Name cannot be empty',
      'Знайдений'=>'Found','У черзі'=>'Queued','Відхилений'=>'Rejected','Недоступний'=>'Unavailable',
      'Вибрати всі на сторінці'=>'Select all on this page','Вибрати всі'=>'Select all','Зняти вибір'=>'Clear selection',
      'Експорт CSV'=>'Export CSV','Видалити вибрані'=>'Delete selected','Повторити недоступні'=>'Retry unavailable',
      'Шаблон листа'=>'Email template','Надіслати вибраним'=>'Send to selected','Зупинити перевірку'=>'Stop checking','Обрати шаблон'=>'Select a template','Статус списку'=>'List status',
      'Сайт'=>'Website','Версія'=>'Version','Мова'=>'Language','Телефон'=>'Phone','Нотатка'=>'Note','Відправлено'=>'Sent',
      'Знайдено:'=>'Found:','У черзі:'=>'Queued:','Відхилено:'=>'Rejected:','Недоступні:'=>'Unavailable:','Надіслано:'=>'Sent:',
      'Помилки відправки:'=>'Email errors:','Вибрано:'=>'Selected:','Сторінка '=>'Page ','з'=>'of','всього'=>'total',
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
    $chunks = preg_split('~(<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>)~is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $dict = cmsf_i18n_dictionary();
    foreach ($chunks as $i => $chunk) {
        if ($i % 2 === 0) $chunks[$i] = strtr($chunk, $dict);
    }
    return implode('', $chunks);
}
add_action('admin_init', function () {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (strpos($page, 'cmsf') === 0 || $page === 'cms-checker') ob_start('cmsf_translate_admin_html');
}, 0);
