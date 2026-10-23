const { normalizeDomain } = require('./database');

const enc = encodeURIComponent;
// ctx.ua = шукаємо лише по Україні → просимо в пошуковика український регіон/мову
const SOURCES = {
  duckduckgo: { label: 'DuckDuckGo', pages: 1, url: (q, ctx) => `https://html.duckduckgo.com/html/?q=${enc(q)}${ctx.ua ? '&kl=ua-uk' : ''}` },
  bing: { label: 'Bing', pages: 2, url: (q, ctx, p = 0) => `https://www.bing.com/search?q=${enc(q)}&count=50${p ? `&first=${p * 50 + 1}` : ''}${ctx.ua ? '&cc=UA&setlang=uk' : ''}` },
  mojeek: { label: 'Mojeek', pages: 2, url: (q, ctx, p = 0) => `https://www.mojeek.com/search?q=${enc(q)}${p ? `&s=${p * 10 + 1}` : ''}` },
  google: { label: 'Google', pages: 1, url: (q, ctx) => `https://www.google.com/search?q=${enc(q)}&num=50${ctx.ua ? '&hl=uk&gl=ua' : ''}` }
};
const BLOCKED = /(^|\.)(duckduckgo|google|bing|mojeek|facebook|instagram|youtube|linkedin|wikipedia|reddit|pinterest|tiktok|x|twitter|microsoft|apple|amazon|yahoo|wordpress|woocommerce|hivepress|github|stackoverflow|medium|quora|telegram|whatsapp|vk|w3|schema|cloudflare|trustpilot)\./i;
const ALL_PLATFORMS = /усі платформи|інші \/ усі платформи/i;

const split = value => String(value || '').split(/[,;\n]/).map(s => s.trim()).filter(Boolean).slice(0, 10);

// Фрази кнопки «в кошик» залежно від обраних мов — по ним пошуковик знаходить саме магазини
function cartPhrases(langs) {
  const any = !langs.length || langs.includes('Будь-яка мова');
  const out = [];
  if (any || langs.includes('Українська')) out.push('"Додати в кошик"');
  if (langs.includes('Російська')) out.push('"В корзину"');
  if (any || langs.includes('Англійська')) out.push('"Add to cart"');
  return out;
}

function regionSuffixes(regions) {
  const worldwide = !regions.length || regions.includes('Увесь світ');
  const s = [];
  if (worldwide) s.push('');
  if (regions.includes('Україна')) s.push('site:.ua');
  if (regions.includes('Європа')) s.push('(site:.eu OR site:.de OR site:.pl OR site:.fr OR site:.it OR site:.es)');
  if (regions.includes('США')) s.push('(site:.us OR "United States")');
  return s.length ? s : [''];
}

function makeQueries(o) {
  const platforms = o.platforms || [];
  const allPlatforms = !platforms.length || platforms.some(p => ALL_PLATFORMS.test(p));
  const specificPlatforms = platforms.filter(p => !ALL_PLATFORMS.test(p));
  const extra = String(o.query || '').trim();
  const terms = [...new Set([...split(o.categoriesText), ...split(o.tagsText), ...(extra ? [extra] : [])])].slice(0, 12);
  const langs = o.languages || [];
  const base = [];

  if (specificPlatforms.some(p => /^WooCommerce$/i.test(p))) {
    const phrases = cartPhrases(langs);
    const anyLang = !langs.length || langs.includes('Будь-яка мова');
    const defaults = [];
    if (anyLang || langs.includes('Українська') || langs.includes('Російська')) defaults.push('інтернет-магазин');
    if (anyLang || langs.includes('Англійська')) defaults.push('online store');
    for (const term of (terms.length ? terms : defaults)) {
      for (const ph of phrases) base.push(`"${term}" ${ph}`);
      // «Футпринти» стандартної розмітки WooCommerce, які видно в тексті сторінки товару
      if (anyLang || langs.includes('Українська')) base.push(`"${term}" "Додати в кошик" "Додаткова інформація"`);
      if (anyLang || langs.includes('Англійська')) base.push(`"${term}" "Add to cart" "Related products"`);
      base.push(`WooCommerce "${term}"`);
    }
  }
  if (specificPlatforms.some(p => /HivePress/i.test(p))) {
    for (const term of (terms.length ? terms : ['directory', 'classifieds', 'listings'])) base.push(`"HivePress" "${term}"`, `WordPress HivePress ${term}`);
  }
  if (specificPlatforms.some(p => /^WordPress$/i.test(p))) {
    for (const term of (terms.length ? terms : ['business website'])) base.push(`WordPress "${term}"`, `"wp-content/themes" "${term}"`);
  }
  for (const platform of specificPlatforms.filter(p => !/^WooCommerce$|HivePress|^WordPress$/i.test(p)).slice(0, 8)) {
    for (const term of (terms.length ? terms : ['official website'])) base.push(`"${platform}" "${term}"`, `${platform} ${term} website`);
  }
  if (allPlatforms) {
    for (const term of (terms.length ? terms : ['business website'])) base.push(`інтернет-магазин ${term}`, `company ${term} official website`, `business directory ${term}`);
  }

  // Регіональне обмеження застосовується до КОЖНОГО запиту.
  // Раніше «site:.ua» додавалось лише до перших 8, а решта запитів ішла по всьому світу.
  const suffixes = regionSuffixes(o.regions || []);
  const out = [];
  for (const q of base) for (const s of suffixes) out.push(`${q} ${s}`.trim());
  return [...new Set(out)].slice(0, 40);
}

// Беремо лише органічні результати видачі, а не всі посилання сторінки (меню, реклама, «схожі запити»)
function resultHrefs(html, source) {
  const tagHref = re => [...html.matchAll(re)].map(m => (m[0].match(/href\s*=\s*["']([^"']+)["']/i) || [])[1]).filter(Boolean);
  let hs = [];
  if (source === 'bing') hs = [...html.matchAll(/<li[^>]+class=["'][^"']*\bb_algo\b[^"']*["'][\s\S]*?<h2[^>]*>\s*<a[^>]+href=["']([^"']+)["']/ig)].map(m => m[1]);
  else if (source === 'duckduckgo') hs = tagHref(/<a\b[^>]*class=["'][^"']*result__a[^"']*["'][^>]*>/ig);
  else if (source === 'mojeek') hs = tagHref(/<a\b[^>]*class=["'][^"']*\b(?:title|ob)\b[^"']*["'][^>]*>/ig);
  if (hs.length) return hs;
  const all = []; const re = /href\s*=\s*["']([^"']+)["']/ig; let m;
  while ((m = re.exec(html))) all.push(m[1]);
  return all;
}

function extract(html, source) {
  const out = new Set();
  for (const href of resultHrefs(html, source)) {
    const raw = href.replace(/&amp;/g, '&').replace(/&#x2F;/gi, '/');
    try {
      let u = new URL(raw, SOURCES[source]?.url('test', {}) || 'https://example.com');
      if (source === 'duckduckgo' && u.searchParams.has('uddg')) u = new URL(u.searchParams.get('uddg'));
      if (source === 'google' && u.pathname === '/url' && u.searchParams.has('q')) u = new URL(u.searchParams.get('q'));
      if (source === 'bing' && u.hostname.endsWith('bing.com') && u.searchParams.has('u')) {
        let target = u.searchParams.get('u');
        if (target.startsWith('a1')) target = Buffer.from(target.slice(2), 'base64').toString('utf8');
        if (/^https?:\/\//i.test(target)) u = new URL(target);
      }
      if (!/^https?:$/.test(u.protocol)) continue;
      const host = normalizeDomain(u.hostname.replace(/^www\./i, ''));
      if (!host || !host.includes('.') || BLOCKED.test(host)) continue;
      if (/\.(css|js|png|jpg|jpeg|gif|svg|webp|pdf|ico|woff2?)$/i.test(u.pathname)) continue;
      if (/^(translate|maps|accounts|support|policies|cache)\./i.test(host)) continue;
      out.add(host);
    } catch {}
  }
  return [...out];
}

async function searchSource(source, query, ctx, page) {
  const res = await fetch(SOURCES[source].url(query, ctx, page), {
    headers: {
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
      'Accept-Language': ctx.ua ? 'uk-UA,uk;q=0.9,en;q=0.6' : 'en-US,en;q=0.9,uk;q=0.8'
    },
    signal: AbortSignal.timeout(14000), redirect: 'follow'
  });
  // DuckDuckGo відповідає 202 + сторінка «anomaly», коли бачить бота
  if (res.status === 202) throw new Error('джерело показало перевірку або блокування (HTTP 202)');
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const html = await res.text();
  if (/unusual traffic|captcha|verify you are human|access denied|anomaly-modal|are you a robot/i.test(html.slice(0, 50000))) throw new Error('джерело показало перевірку або блокування');
  return extract(html, source);
}

const LIST_SOURCES = { majestic: { label: 'Список доменів Majestic' } };
const EU_TLDS = 'at be bg hr cy cz dk ee fi fr de gr hu ie it lv lt lu mt nl pl pt ro sk si es se eu no ch is li'.split(' ');

// Безкоштовний список ~1 млн найпопулярніших доменів світу. Фільтруємо за зоною (.ua тощо), далі кожен домен перевіряється по коду.
async function fetchMajestic(tlds, limit, log) {
  log('Завантаження списку Majestic (~100 МБ, може тривати кілька хвилин)...');
  const res = await fetch('https://downloads.majestic.com/majestic_million.csv', { signal: AbortSignal.timeout(600000) });
  if (!res.ok) throw new Error('Majestic HTTP ' + res.status);
  const reader = res.body.getReader(), dec = new TextDecoder();
  let buf = '', di = -1; const out = [];
  while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    buf += dec.decode(value, { stream: true });
    let nl;
    while ((nl = buf.indexOf('\n')) >= 0) {
      const line = buf.slice(0, nl).replace(/\r$/, '').replace(/^\uFEFF/, ''); buf = buf.slice(nl + 1);
      if (di < 0) { di = line.split(',').indexOf('Domain'); if (di < 0) throw new Error('Невідомий формат списку Majestic'); continue; }
      const d = (line.split(',')[di] || '').toLowerCase();
      if (!d) continue;
      if (!tlds || tlds.includes(d.split('.').pop())) { out.push(d); if (out.length >= limit) { await reader.cancel(); return out; } }
    }
  }
  return out;
}

async function discoverDomains(o, log = () => {}) {
  const limit = Math.max(1, Math.min(50000, Number(o.limit) || 100));
  const imported = (o.importedDomains || []).map(x => {
    try { return normalizeDomain(new URL(x.includes('://') ? x : 'https://' + x).hostname); }
    catch { return normalizeDomain(String(x).split(/[;,\s]/)[0]); }
  }).filter(x => x && x.includes('.'));
  const found = new Set(imported);
  const allSel = o.sources || ['duckduckgo', 'bing', 'mojeek'];
  const useMajestic = allSel.includes('majestic');
  const requested = allSel.filter(s => SOURCES[s]);
  const sources = requested.length || useMajestic ? requested : ['duckduckgo', 'bing', 'mojeek'];
  const queries = sources.length ? makeQueries(o) : [];
  const regions = o.regions || [];
  const ctx = { ua: regions.length === 1 && regions[0] === 'Україна' };
  const uaOnly = ctx.ua;   // усі запити мають site:.ua, тож інші зони — це сміття зі сторінки видачі
  const stats = Object.fromEntries(sources.map(s => [s, 0]));
  log(`Імпортовано доменів: ${imported.length}`);
  log(`Джерела пошуку: ${sources.map(s => SOURCES[s].label).join(', ')}`);
  log(`Підготовлено пошукових запитів: ${queries.length}`);
  if (useMajestic) {
    const rg = o.regions || [];
    let tlds = null;
    if (rg.length && !rg.includes('Увесь світ')) { tlds = []; if (rg.includes('Україна')) tlds.push('ua'); if (rg.includes('Європа')) tlds.push(...EU_TLDS); if (rg.includes('США')) tlds.push('us'); }
    try { const list = await fetchMajestic(tlds, limit, log); list.forEach(d => found.add(d)); log(`Majestic: отримано ${list.length} доменів зони ${tlds ? tlds.map(t => '.' + t).join(', ') : 'будь-якої'}`); }
    catch (e) { log('Majestic: ' + e.message); }
  }
  const failed = new Set();
  for (const q of queries) {
    if (found.size >= limit) break;
    log(`Запит: ${q}`);
    for (const source of sources) {
      if (found.size >= limit) break;
      if (failed.has(source)) continue;
      for (let page = 0; page < SOURCES[source].pages; page++) {
        try {
          let domains = await searchSource(source, q, ctx, page);
          if (uaOnly) domains = domains.filter(d => /\.ua$/i.test(d));
          const before = found.size;
          domains.forEach(d => found.add(d));
          const added = found.size - before;
          stats[source] += added;
          log(`${SOURCES[source].label}${page ? ' (стор. ' + (page + 1) + ')' : ''}: ${domains.length} доменів у відповіді, нових ${added}`);
          if (!domains.length) break;
        } catch (e) {
          log(`${SOURCES[source].label}: ${e.message}`);
          if (/HTTP 403|HTTP 429|HTTP 202|блокування|перевірку/i.test(e.message)) failed.add(source);
          break;
        }
        await new Promise(r => setTimeout(r, 650));
      }
    }
  }
  log('Внесок джерел (нових доменів): ' + sources.map(s => `${SOURCES[s].label} ${stats[s]}${failed.has(s) ? ' [заблоковано]' : ''}`).join(', '));
  if (found.size === 0) log('Жодного домену не отримано. Пошуковики можуть блокувати автоматичні запити; спробуйте інше джерело або імпорт TXT/CSV.');
  else log(`Унікальних доменів у результаті: ${found.size}. Це лише кандидати — відбір за CMS/країною/мовою відбувається на кроці «Перевірити контакти».`);
  const result = [...found].slice(0, limit);
  if (result.length && result.length <= 100) log('Знайдені домени:\n' + result.join('\n'));
  else if (result.length) log(`Знайдено ${result.length} доменів (перші 30):\n` + result.slice(0, 30).join('\n'));
  return result;
}
module.exports = { discoverDomains, makeQueries, extract };
