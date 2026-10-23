const { normalizeDomain } = require('./database');
const EMAIL = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/ig;
const ALL_PLATFORMS = /усі платформи|інші \/ усі платформи/i;
const EU = new Set('at be bg hr cy cz dk ee fi fr de gr hu ie it lv lt lu mt nl pl pt ro sk si es se eu no ch is li gb uk'.split(' '));

function clean(s) { return s.replace(/<script[\s\S]*?<\/script>/ig, ' ').replace(/<style[\s\S]*?<\/style>/ig, ' ').replace(/<[^>]+>/g, ' ').replace(/&nbsp;|&#160;/g, ' ').replace(/&amp;/g, '&').replace(/&#64;|&commat;/g, '@').replace(/\s+/g, ' ').trim(); }

function detectPlatform(html, headers = '') {
  const s = (html + ' ' + headers).toLowerCase();
  const wp = /wp-content\/|wp-includes\/|wp-json|<meta[^>]+name=["']generator["'][^>]+wordpress/;
  // Ознаки беремо з розмітки (шляхи, класи), а не з будь-якого слова в тексті сторінки
  const woo = /wp-content\/plugins\/woocommerce|woocommerce-(?:page|js|layout|general|no-js)|wc-ajax|wc_add_to_cart_params|<meta[^>]+generator[^>]+woocommerce|class=["'][^"']*\bwoocommerce\b/;
  const hive = /wp-content\/plugins\/hivepress|class=["'][^"']*\bhp-(?:listing|vendor|page|form|template)|hivepress-/;
  if (hive.test(s)) return 'WordPress + HivePress';
  if (woo.test(s)) return 'WooCommerce';
  if (wp.test(s)) return 'WordPress';
  const tests = [
    ['Joomla', /content="joomla|com_content|\/media\/system\/js/],
    ['OpenCart', /index\.php\?route=|catalog\/view\/theme|opencart/],
    ['CS-Cart', /cs-cart|cscart|\/design\/themes\/|tygh\./],
    ['Shopify', /cdn\.shopify\.com|shopify\.theme|x-shopify/],
    ['PrestaShop', /prestashop|\/modules\/ps_/],
    ['Magento / Adobe Commerce', /mage\/cookies|magento|static\/version\d+\/frontend/],
    ['Drupal', /drupalsettings|sites\/default\/files|content="drupal/],
    ['Wix', /wixstatic\.com|wix\.com/],
    ['Squarespace', /squarespace\.com|static1\.squarespace/],
    ['Webflow', /webflow\.com|website-files\.com/],
    ['BigCommerce', /bigcommerce\.com|cdn\d+\.bigcommerce/],
    ['Shopware', /shopware/],
    ['Tilda', /tildacdn\.com|tilda\.ws/],
    ['MODX', /modx|assets\/components\/modx/]
  ];
  for (const [n, r] of tests) if (r.test(s)) return n;
  return 'Не визначено';
}

function detectSiteType(html, title, text, platform) {
  const s = (String(title || '') + ' ' + String(text || '').slice(0, 12000)).toLowerCase();
  const h = String(html || '').slice(0, 250000).toLowerCase();
  if (/hivepress|hp-listing|hp-listings|directory|business directory|classifieds|оголошенн|каталог компан|каталог підприємств|довідник компан/i.test(s)) return 'Каталог / оголошення';
  // Раніше тут вистачало слів «купити» / «ціна» — через це новинні сайти вважались магазинами
  if (/WooCommerce|OpenCart|Shopify|PrestaShop|Magento|BigCommerce|Shopware|CS-Cart/i.test(platform) || /add to cart|додати в кошик|додати до кошика|в корзину|shopping cart|product-category|add-to-cart/.test(h)) return 'Інтернет-магазин (ознаки)';
  if (/новини|новости|news|редакц|університет|university|коледж|college|розклад занять|навчальн/i.test(s)) return 'Новини / освіта (можливо)';
  if (/послуги|services|наші послуги|our services|про компанію|about us|виробник|manufacturer/i.test(s)) return 'Компанія / послуги (можливо)';
  return 'Не визначено';
}

function textLang(s) {
  s = s.slice(0, 8000).toLowerCase();
  const uk = (s.match(/[іїєґ]/g) || []).length, en = (s.match(/\b(the|and|with|shop|contact|about|shipping|cart)\b/g) || []).length, ru = (s.match(/[ыэъё]/g) || []).length;
  if (uk >= 3) return 'uk'; if (ru >= 3) return 'ru'; if (en >= 3) return 'en'; return '';
}
const LANG_NAMES = { uk: 'Українська', ru: 'Російська', en: 'Англійська' };
const LANG_BY_UI = { 'Українська': 'uk', 'Російська': 'ru', 'Англійська': 'en' };

// Мови сайту: <html lang>, hreflang-версії та аналіз тексту
function detectLanguages(html, text) {
  const set = new Set();
  const m = html.match(/<html[^>]*\blang=["']?([a-zA-Z-]+)/i);
  if (m) set.add(m[1].slice(0, 2).toLowerCase());
  for (const h of html.matchAll(/hreflang=["']([a-zA-Z-]+)["']/ig)) set.add(h[1].slice(0, 2).toLowerCase());
  const t = textLang(text); if (t) set.add(t);
  return [...set].filter(x => /^[a-z]{2}$/.test(x));
}

function detectCountry(domain, text, phones, htmlLangFull, langs) {
  const tld = domain.split('.').pop().toLowerCase();
  const digits = phones.map(p => p.replace(/\D/g, ''));
  const sig = []; let code = '';
  if (tld === 'ua') { code = 'UA'; sig.push('.ua'); }
  if (digits.some(n => n.startsWith('380') && n.length === 12)) { code = code || 'UA'; sig.push('+380'); }
  if (/грн|₴|\buah\b/i.test(text.slice(0, 30000))) { code = code || 'UA'; sig.push('грн'); }
  const isCc = /^[a-z]{2}$/.test(tld);
  let weak = false;
  if (!code && langs.includes('uk') && !isCc) { weak = true; }
  if (!code && (tld === 'us' || /^en-us/i.test(htmlLangFull))) code = 'US';
  if (!code && isCc) code = tld === 'uk' ? 'GB' : tld.toUpperCase();
  const europe = EU.has(tld) || (code && EU.has(code.toLowerCase()));
  const label = code === 'UA' ? `Україна (${sig.join(', ')})` : weak ? 'Невизначено (лише українська мова, без .ua/+380/грн)' : code ? `Інша (${code})` : 'Невідомо';
  return { code, europe, label };
}

function platformMatch(selected, platform) {
  if (!selected.length || selected.some(p => ALL_PLATFORMS.test(p))) return true;
  return selected.some(p => p === platform || (p === 'WordPress' && /^(WordPress|WooCommerce)/.test(platform)));
}
function regionMatch(regions, c) {
  if (!regions.length || regions.includes('Увесь світ')) return true;
  return (regions.includes('Україна') && c.code === 'UA') || (regions.includes('Європа') && c.europe) || (regions.includes('США') && c.code === 'US');
}
function languageMatch(selected, langs) {
  if (!selected.length || selected.includes('Будь-яка мова')) return true;
  if (!langs.length) return false;
  return selected.some(s => langs.includes(LANG_BY_UI[s]));
}
function typeMatch(want, siteType) {
  if (!want || want === 'any') return true;
  if (want === 'shop') return /^Інтернет-магазин/.test(siteType);
  if (want === 'catalog') return /^Каталог/.test(siteType);
  return true;
}

function stems(term) { return term.toLowerCase().split(/[^\p{L}\p{N}]+/u).filter(w => w.length >= 4).map(w => w.slice(0, Math.max(4, w.length - 2))); }
function relevance(terms, hay) { hay = hay.toLowerCase(); return terms.filter(t => stems(t).some(s => hay.includes(s))).length; }

async function get(url) {
  const r = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', 'Accept-Language': 'uk-UA,uk;q=0.9,en;q=0.6' }, redirect: 'follow', signal: AbortSignal.timeout(12000) });
  if (!r.ok) throw Error('HTTP ' + r.status);
  if (!(r.headers.get('content-type') || '').includes('text/html')) throw Error('Не HTML');
  return { html: (await r.text()).slice(0, 2000000), headers: [...r.headers.entries()].map(x => x.join(':')).join(' '), url: r.url };
}

function extractContacts(html, text) {
  const mail = [...html.matchAll(/mailto:([^"'?\s>]+)/ig)].map(x => decodeURIComponent(x[1]));
  const emails = [...new Set([...mail, ...((html + ' ' + text).match(EMAIL) || [])])]
    .filter(x => !/(example\.com|wixpress\.com|sentry\.io)$/i.test(x) && !/\.(png|jpe?g|gif|webp|svg|css|js)$/i.test(x)).slice(0, 10);
  const tel = [...html.matchAll(/href=["']tel:([^"']+)/ig)].map(x => decodeURIComponent(x[1]).trim());
  const found = text.match(/(?:\+?\d[\d\s().-]{7,}\d)/g) || [];
  const phones = [...new Set([...tel, ...found].map(x => x.replace(/\s+/g, ' ').trim()).filter(x => { const n = x.replace(/\D/g, '').length; return n >= 9 && n <= 15; }))].slice(0, 10);
  const vm = html.match(/(?:viber:\/\/(?:chat|add)\?number=|https?:\/\/(?:www\.)?vb\.me\/)[^"' <]+/i);
  return { emails, phones, viber: vm ? vm[0].replace(/&amp;/g, '&') : '' };
}

async function wooStoreApi(origin) {
  for (const p of ['/wp-json/wc/store/v1/products?per_page=1', '/?rest_route=/wc/store/v1/products&per_page=1']) {
    try {
      const r = await fetch(origin + p, { headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0.0.0 Safari/537.36', Accept: 'application/json' }, signal: AbortSignal.timeout(8000) });
      if (r.ok && Array.isArray(await r.json())) return true;
    } catch {}
  }
  return false;
}

async function inspectSite(input, opts = {}) {
  const domain = normalizeDomain(input);
  if (!domain || !domain.includes('.')) throw Error('Некоректний домен');
  let page;
  try { page = await get(input.startsWith('http') ? input : 'https://' + domain); } catch (e) { page = await get('http://' + domain); }
  const { html, headers, url } = page;
  const text = clean(html);
  const t = html.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
  const title = t ? clean(t[1]).slice(0, 180) : domain;
  const meta = (html.match(/<meta[^>]+name=["'](?:description|keywords)["'][^>]*content=["'][^"']*/ig) || []).join(' ');

  let contacts = extractContacts(html, text);
  let contactPage = '';
  for (const m of html.matchAll(/<a\b[^>]*href=["']([^"'#]+)["'][^>]*>([\s\S]*?)<\/a>/ig)) {
    if (/contact|контакт|зв'яз|звяз|про нас|about/i.test(clean(m[2]) + ' ' + m[1])) { try { contactPage = new URL(m[1], url).href; break; } catch {} }
  }
  // Якщо на головній немає контактів — пробуємо сторінку контактів того ж сайту
  if (!contacts.emails.length && !contacts.phones.length && contactPage) {
    try {
      if (normalizeDomain(new URL(contactPage).hostname) === normalizeDomain(new URL(url).hostname)) {
        const c = await get(contactPage);
        const more = extractContacts(c.html, clean(c.html));
        contacts = { emails: more.emails, phones: more.phones, viber: contacts.viber || more.viber };
      }
    } catch {}
  }

  let platform = detectPlatform(html, headers);
  // Якщо це «голий» WordPress, а шукаємо WooCommerce — підтверджуємо через публічний Store API магазину
  if (platform === 'WordPress' && (opts.platforms || []).includes('WooCommerce')) {
    try { if (await wooStoreApi(new URL(url).origin)) platform = 'WooCommerce'; } catch {}
  }
  const siteType = detectSiteType(html, title, text, platform);
  const langs = detectLanguages(html, text);
  const htmlLang = (html.match(/<html[^>]*\blang=["']?([a-zA-Z-]+)/i) || [])[1] || '';
  const country = detectCountry(domain, text, contacts.phones, htmlLang, langs);

  const terms = opts.terms || [];
  const reasons = [];
  if (!platformMatch(opts.platforms || [], platform)) reasons.push(`платформа: ${platform}`);
  if (!regionMatch(opts.regions || [], country)) reasons.push(`країна: ${country.label}`);
  if (!languageMatch(opts.languages || [], langs)) reasons.push(`мова: ${langs.map(l => LANG_NAMES[l] || l).join('/') || 'невідомо'}`);
  if (!typeMatch(opts.siteType, siteType)) reasons.push(`тип: ${siteType}`);

  return {
    domain, name: title, platform, siteType, country: country.label,
    language: langs.length ? langs.map(l => LANG_NAMES[l] || l).join(', ') + ' (ймовірно)' : 'Невідомо',
    category: opts.category || '', tags: opts.tags || [],
    relevance: terms.length ? relevance(terms, title + ' ' + meta + ' ' + text.slice(0, 20000)) : '',
    email: contacts.emails.join('; '), phone: contacts.phones.join('; '), viber: contacts.viber, contactPage,
    source: 'Автоматична перевірка головної сторінки', checkedAt: new Date().toISOString(), status: 'Перевірено',
    matched: reasons.length === 0, rejectReason: reasons.join('; ')
  };
}
module.exports = { inspectSite, detectPlatform, detectSiteType, detectCountry, detectLanguages };
