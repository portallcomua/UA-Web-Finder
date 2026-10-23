const $ = id => document.getElementById(id);
let fileDomains = [];   // домени, імпортовані з TXT/CSV
let queue = [];         // черга на перевірку
let busy = false, stopFlag = false;
const uniq = a => [...new Set(a)];
const splitTerms = v => String(v || '').split(/[,;\n]/).map(s => s.trim()).filter(Boolean);

function log(s) { const el = $('log'); el.textContent += `\n${s}`; if (el.textContent.length > 80000) el.textContent = el.textContent.slice(-60000); el.scrollTop = el.scrollHeight; }
function selected(id) { return [...$(id).selectedOptions].map(o => o.value || o.textContent); }
function opts() {
  return {
    regions: selected('regions'), languages: selected('languages'), platforms: selected('platforms'),
    siteType: $('siteType').value, strict: $('strict').checked,
    sources: [...document.querySelectorAll('input[name=source]:checked')].map(x => x.value),
    categoriesText: $('categories').value, tagsText: $('tags').value, query: $('query').value,
    limit: Number($('limit').value), importedDomains: fileDomains
  };
}
function setBusy(v) { busy = v; ['import', 'discover', 'inspect', 'export', 'clearDb'].forEach(id => $(id).disabled = v); $('stop').disabled = !v; }

async function refresh() {
  const data = await window.shopFinder.list();
  $('rows').innerHTML = '';
  for (const r of data) {
    const tr = document.createElement('tr');
    [r.domain, r.name, r.platform, r.country, r.language, r.siteType, r.email, r.phone, r.viber].forEach((v, i) => {
      const td = document.createElement('td');
      if (i === 0 && v) { const a = document.createElement('a'); a.href = 'https://' + v; a.textContent = v; a.target = '_blank'; a.rel = 'noreferrer'; td.appendChild(a); }
      else td.textContent = Array.isArray(v) ? v.join(', ') : (v || '');
      td.title = td.textContent; tr.appendChild(td);
    });
    $('rows').appendChild(tr);
  }
  $('count').textContent = `Збережено сайтів: ${data.length}`;
}

$('import').onclick = async () => {
  const a = await window.shopFinder.importDomains();
  fileDomains = uniq([...fileDomains, ...a]); queue = uniq([...queue, ...a]);
  log(`Імпортовано рядків: ${a.length}. Унікальних доменів у черзі: ${queue.length}`);
};

$('discover').onclick = async () => {
  if (busy) return; setBusy(true); $('progress').style.width = '5%';
  try {
    const settings = opts();
    if (!settings.sources.length) { log('Оберіть хоча б одне джерело пошуку.'); return; }
    if (!settings.platforms.length) log('УВАГА: платформу не вибрано — пошук і відбір піде без фільтра по CMS. Виберіть, наприклад, WooCommerce.');
    log('Початок пошуку доменів...');
    const found = await window.shopFinder.discover(settings);
    queue = uniq(found);   // стара черга більше не накопичується — береться лише свіжий результат
    log(`Знайдено доменів-кандидатів: ${found.length}`);
    $('progress').style.width = '100%';
    $('status').textContent = `У черзі ${queue.length} доменів. Натисніть «Перевірити контакти».`;
  } catch (e) { log('Помилка пошуку: ' + e.message); } finally { setBusy(false); }
};

$('stop').onclick = () => { stopFlag = true; log('Зупинка після завершення поточних перевірок...'); };

$('inspect').onclick = async () => {
  if (busy) return;
  let list = uniq(queue);
  if (!list.length) { log('Спочатку імпортуйте домени або виконайте пошук.'); return; }
  setBusy(true); stopFlag = false;
  const o = opts();
  try {
    if ($('skipChecked').checked) {
      const done = new Set(await window.shopFinder.checked());
      const before = list.length; list = list.filter(d => !done.has(d));
      if (before !== list.length) log(`Пропущено доменів, які вже перевірялись: ${before - list.length}`);
    }
    const terms = uniq([...splitTerms(o.categoriesText), ...splitTerms(o.tagsText), ...splitTerms(o.query)]);
    const filter = { category: o.categoriesText, tags: splitTerms(o.tagsText), terms, platforms: o.platforms, regions: o.regions, languages: o.languages, siteType: o.siteType };
    const verbose = list.length <= 100;
    let idx = 0, n = 0, saved = 0, skipped = 0, failed = 0;
    log(`Перевірка ${list.length} доменів у 8 потоків...`);
    const worker = async () => {
      while (!stopFlag) {
        const i = idx++; if (i >= list.length) return;
        const d = list[i];
        try {
          const record = await window.shopFinder.inspect(d, filter);
          await window.shopFinder.mark(d);
          if (o.strict && !record.matched) { skipped++; if (verbose) log(`${d}: пропущено (${record.rejectReason})`); }
          else { await window.shopFinder.save(record); saved++; log(`✔ ${d} — ${record.platform}, ${record.country}`); }
        } catch (e) { failed++; if (verbose) log(`Не вдалося перевірити ${d}: ${e.message}`); }
        n++;
        $('progress').style.width = `${Math.round(n / list.length * 100)}%`;
        $('status').textContent = `Перевірено ${n} із ${list.length}: збережено ${saved}, відсіяно ${skipped}, недоступні ${failed}`;
        if (n % 20 === 0) refresh();
      }
    };
    await Promise.all(Array.from({ length: 8 }, worker));
    log(`${stopFlag ? 'Зупинено' : 'Завершено'}. Збережено: ${saved}, відсіяно фільтрами: ${skipped}, не вдалося відкрити: ${failed}.`);
    await refresh();
  } finally { setBusy(false); }
};

$('export').onclick = async () => { const r = await window.shopFinder.exportCsv(); if (!r.canceled) log('CSV збережено: ' + r.filePath); };
$('clearDb').onclick = async () => { if (!confirm('Видалити всі збережені сайти з бази?')) return; await window.shopFinder.clearDb(); log('Базу очищено.'); await refresh(); };
$('refresh').onclick = refresh;
$('clearLog').onclick = () => $('log').textContent = 'Журнал очищено.';
window.shopFinder.onLog(log); refresh();
window.shopFinder.info().then(i => $('version').textContent = 'Версія ' + i.version);
