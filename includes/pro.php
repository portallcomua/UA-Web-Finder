<?php
if (!defined('ABSPATH')) exit;

/**
 * Freemius hosted-checkout bridge. No Freemius SDK is loaded.
 * Purchase activation is accepted only from a signed Freemius redirect.
 */
function cmsf_trial_started_at() {
    $started = (int) get_option('cmsf_trial_started_at', 0);
    if (!$started) {
        $started = time();
        add_option('cmsf_trial_started_at', $started, '', false);
    }
    return $started;
}
function cmsf_trial_days_left() {
    $end = cmsf_trial_started_at() + (30 * DAY_IN_SECONDS);
    return max(0, (int) ceil(($end - time()) / DAY_IN_SECONDS));
}
function cmsf_pro_is_active() {
    return (bool) get_option('cmsf_pro_activated', false);
}
function cmsf_trial_is_active() {
    return cmsf_trial_days_left() > 0;
}
function cmsf_feature_access_allowed() {
    return cmsf_pro_is_active() || cmsf_trial_is_active();
}
function cmsf_pro_options() {
    return [
        'checkout_url' => (string) get_option('cmsf_freemius_checkout_url', ''),
        'secret_key' => (string) get_option('cmsf_freemius_secret_key', ''),
        'plan_id' => (string) get_option('cmsf_freemius_plan_id', ''),
    ];
}

/** Verify Freemius' HMAC-signed hosted-checkout return URL. */
function cmsf_freemius_capture_purchase() {
    if (empty($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== 'cmsf-pro' || empty($_GET['signature'])) return;
    $o = cmsf_pro_options();
    if (!$o['secret_key']) return;

    $signature = sanitize_text_field(wp_unslash($_GET['signature']));
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $clean_uri = preg_replace('/([?&])signature=[^&]*&?/', '$1', $uri);
    $clean_uri = preg_replace('/[?&]$/', '', $clean_uri);
    $clean_uri = str_replace('?&', '?', $clean_uri);
    $absolute_url = home_url($clean_uri);
    $expected = hash_hmac('sha256', $absolute_url, $o['secret_key']);

    $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
    $plan_id = isset($_GET['plan_id']) ? sanitize_text_field(wp_unslash($_GET['plan_id'])) : '';
    $has_payment = !empty($_GET['payment_id']) || !empty($_GET['license_id']);
    if (!hash_equals($expected, $signature) || $action !== 'purchase' || !$has_payment) {
        add_settings_error('cmsf_pro', 'cmsf_invalid_purchase', 'Freemius повернув непідтверджені дані. Pro не активовано.', 'error');
        return;
    }
    if ($o['plan_id'] !== '' && $plan_id !== $o['plan_id']) {
        add_settings_error('cmsf_pro', 'cmsf_wrong_plan', 'Оплата підтверджена, але це не налаштований Pro-план. Pro не активовано.', 'error');
        return;
    }
    update_option('cmsf_pro_activated', 1, false);
    update_option('cmsf_pro_activated_at', time(), false);
    update_option('cmsf_pro_payment_id', sanitize_text_field(wp_unslash($_GET['payment_id'] ?? '')), false);
    update_option('cmsf_pro_plan_id_purchased', $plan_id, false);
    add_settings_error('cmsf_pro', 'cmsf_purchase_ok', 'Покупку Freemius перевірено. UA Web Finder Pro активовано на цьому сайті.', 'success');
}
add_action('admin_init', 'cmsf_freemius_capture_purchase', 1);

add_action('admin_post_cmsf_save_pro', function () {
    if (!current_user_can('manage_options')) wp_die('Недостатньо прав.');
    check_admin_referer('cmsf_save_pro');
    $url = esc_url_raw(trim(wp_unslash($_POST['checkout_url'] ?? '')));
    if ($url && (!wp_parse_url($url, PHP_URL_HOST) || !preg_match('~(^|\.)freemius\.com$~i', wp_parse_url($url, PHP_URL_HOST)))) {
        wp_safe_redirect(add_query_arg(['page'=>'cmsf-pro','cmsf_saved'=>'0'], admin_url('admin.php'))); exit;
    }
    update_option('cmsf_freemius_checkout_url', $url, false);
    $new_secret = sanitize_text_field(wp_unslash($_POST['secret_key'] ?? ''));
    if ($new_secret !== '') update_option('cmsf_freemius_secret_key', $new_secret, false);
    update_option('cmsf_freemius_plan_id', sanitize_text_field(wp_unslash($_POST['plan_id'] ?? '')), false);
    wp_safe_redirect(add_query_arg(['page'=>'cmsf-pro','cmsf_saved'=>'1'], admin_url('admin.php'))); exit;
});

add_action('admin_notices', function () {
    if (!is_admin() || empty($_GET['page']) || !in_array(sanitize_key(wp_unslash($_GET['page'])), ['cms-checker','cmsf-import','cmsf-lists','cmsf-templates','cmsf-settings','cmsf-pro','cmsf-contacts'], true)) return;
    if (cmsf_pro_is_active()) {
        echo '<div class="notice notice-success"><p><strong>UA Web Finder Pro</strong> — Pro активовано.</p></div>';
    } elseif (cmsf_trial_is_active()) {
        echo '<div class="notice notice-info"><p><strong>Пробний період:</strong> залишилося ' . esc_html(cmsf_trial_days_left()) . ' днів із 30. <a href="' . esc_url(admin_url('admin.php?page=cmsf-pro')) . '">Переглянути Pro та активувати</a></p></div>';
    } else {
        echo '<div class="notice notice-warning"><p><strong>Пробний період завершився.</strong> <a href="' . esc_url(admin_url('admin.php?page=cmsf-pro')) . '">Активуйте UA Web Finder Pro</a>, щоб продовжити роботу.</p></div>';
    }
});

add_action('admin_menu', function () {
    add_submenu_page('cms-checker', 'UA Web Finder Pro', 'Pro / Активація', 'manage_options', 'cmsf-pro', 'cmsf_pro_render_page');
}, 20);

function cmsf_pro_render_page() {
    if (!current_user_can('manage_options')) return;
    $o = cmsf_pro_options();
    $active = cmsf_pro_is_active();
    $days = cmsf_trial_days_left();
    $return_url = add_query_arg('page', 'cmsf-pro', admin_url('admin.php'));
    ?>
    <div class="wrap cmsf">
      <h1>UA Web Finder Pro</h1>
      <?php settings_errors('cmsf_pro'); ?>
      <?php if (isset($_GET['cmsf_saved'])): ?>
        <div class="notice notice-<?php echo $_GET['cmsf_saved'] === '1' ? 'success' : 'error'; ?> is-dismissible"><p><?php echo $_GET['cmsf_saved'] === '1' ? 'Налаштування Freemius збережено.' : 'Посилання має бути hosted checkout URL із домену freemius.com.'; ?></p></div>
      <?php endif; ?>
      <div class="cmsf-card">
        <?php if ($active): ?>
          <h2>Pro активовано</h2>
          <p>Довічний доступ Pro активовано для цієї інсталяції WordPress.</p>
        <?php elseif ($days > 0): ?>
          <h2>Пробний період</h2>
          <p><strong><?php echo esc_html($days); ?></strong> дн. залишилося з 30-денного пробного періоду.</p>
          <div class="cmsf-bar"><div style="width:<?php echo esc_attr((30-$days)/30*100); ?>%"></div></div>
          <p>Після завершення пробного періоду запуск нових пошуків, перевірок і розсилок буде призупинено. Збережені дані залишаться в базі WordPress.</p>
        <?php else: ?>
          <h2>Пробний період завершився</h2>
          <p>Щоб продовжити пошук, перевірку сайтів і розсилки, активуйте UA Web Finder Pro.</p>
        <?php endif; ?>
        <h2>Що входить у Pro</h2>
        <ul><li>Повний доступ до пошуку та перевірки сайтів.</li><li>Робота зі списками, контактами та шаблонами.</li><li>Довічна активація для цього сайту після одноразової покупки Pro.</li></ul>
        <?php if ($o['checkout_url']): ?>
          <p><a class="button button-primary button-hero" href="<?php echo esc_url($o['checkout_url']); ?>" target="_blank" rel="noopener">Придбати / активувати Pro через Freemius</a></p>
        <?php else: ?>
          <p>Посилання на оплату ще не додано. Вставте hosted checkout URL нижче.</p>
        <?php endif; ?>
        <p class="description">Ціна та валюта відображаються на сторінці захищеної оплати Freemius — плагін не підміняє ціну локальним значенням.</p>
      </div>
      <div class="cmsf-card">
        <h2>Підключення Freemius (без SDK)</h2>
        <p>У Freemius створіть одноразовий Lifetime-план, скопіюйте Production Hosted Checkout Link і налаштуйте перенаправлення після успішної покупки на цю адресу:</p>
        <p><code><?php echo esc_html($return_url); ?></code></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="cmsf_save_pro">
          <?php wp_nonce_field('cmsf_save_pro'); ?>
          <table class="form-table"><tbody>
            <tr><th><label for="cmsf-checkout-url">Hosted Checkout URL</label></th><td><input class="regular-text" type="url" id="cmsf-checkout-url" name="checkout_url" value="<?php echo esc_attr($o['checkout_url']); ?>" placeholder="https://checkout.freemius.com/..." /></td></tr>
            <tr><th><label for="cmsf-secret-key">Freemius Product Secret Key</label></th><td><input class="regular-text" type="password" autocomplete="new-password" id="cmsf-secret-key" name="secret_key" value="" placeholder="<?php echo $o['secret_key'] ? 'Налаштовано — залиште порожнім, щоб не змінювати' : 'Вставте Product Secret Key'; ?>" /><p class="description">Потрібен лише для перевірки підпису redirect. Не вставляйте його у публічні поля або листування.</p></td></tr>
            <tr><th><label for="cmsf-plan-id">Lifetime Plan ID</label></th><td><input class="regular-text" type="text" id="cmsf-plan-id" name="plan_id" value="<?php echo esc_attr($o['plan_id']); ?>" /><p class="description">Обмежує активацію саме вибраним планом. Візьміть ID з Freemius Dashboard.</p></td></tr>
          </tbody></table>
          <?php submit_button('Зберегти налаштування Freemius'); ?>
        </form>
      </div>
      <div class="notice notice-warning inline"><p><strong>Важливо:</strong> без SDK і без API/webhooks плагін перевіряє підписаний redirect після покупки. Автоматичне відкликання Pro після повернення коштів або chargeback не синхронізується; для цього потрібен окремий webhook/API-механізм.</p></div>
    </div>
    <?php
}

/** Restrict resource-consuming actions after the 30-day trial, while keeping saved data readable. */
function cmsf_trial_blocks_action($action) {
    if (cmsf_feature_access_allowed()) return false;
    $blocked = ['list_create','add_domains','zone_step','zone_take','search','inspect','next_queued','send','requeue','row_failed','cmsfc_start','cmsfc_step','cmsfc_campaign_start','cmsfc_campaign_step'];
    return in_array($action, $blocked, true);
}
