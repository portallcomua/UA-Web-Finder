const fs = require('fs'); const path = require('path'); let dbFile, checkedFile, checked = new Set(), timer;
function init(userDataPath) { const dir = path.join(userDataPath, 'UA-Shop-Finder'); fs.mkdirSync(dir, { recursive: true }); dbFile = path.join(dir, 'sites.json'); if (!fs.existsSync(dbFile)) fs.writeFileSync(dbFile, '[]', 'utf8'); checkedFile = path.join(dir, 'checked.json'); try { checked = new Set(JSON.parse(fs.readFileSync(checkedFile, 'utf8'))); } catch { checked = new Set(); } }
function read() { try { return JSON.parse(fs.readFileSync(dbFile, 'utf8')); } catch { return []; } }
function write(rows) { fs.writeFileSync(dbFile, JSON.stringify(rows, null, 2), 'utf8'); }
function normalizeDomain(s='') { return String(s).toLowerCase().replace(/^https?:\/\//,'').replace(/^www\./,'').split(/[/?#]/)[0].trim(); }
function list() { return read().sort((a,b) => (b.updatedAt||'').localeCompare(a.updatedAt||'')); }
function upsert(record) { const rows=read(), key=normalizeDomain(record.domain), i=rows.findIndex(x=>normalizeDomain(x.domain)===key); const saved={...(i>=0?rows[i]:{}),...record,updatedAt:new Date().toISOString()}; if(i>=0) rows[i]=saved; else rows.push(saved); write(rows); return saved; }
function flush() { clearTimeout(timer); timer = null; try { fs.writeFileSync(checkedFile, JSON.stringify([...checked]), 'utf8'); } catch {} }
function mark(domain) { checked.add(normalizeDomain(domain)); if (!timer) timer = setTimeout(flush, 2000); return true; }
function checkedList() { return [...checked]; }
function clear() { write([]); checked = new Set(); flush(); return true; }
module.exports={init,list,upsert,clear,mark,checkedList,flush,normalizeDomain};
