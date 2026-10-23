const fs = require('fs');
const cols = [['domain','Домен'],['name','Назва сайту'],['platform','Платформа'],['siteType','Тип сайту'],['country','Країна (припущення)'],['language','Мова (припущення)'],['category','Категорія'],['tags','Теги'],['relevance','Збіг з категоріями/тегами'],['email','Email'],['phone','Телефон'],['viber','Viber'],['contactPage','Сторінка контактів'],['source','Джерело'],['checkedAt','Перевірено']];
function cell(v){let s=Array.isArray(v)?v.join('; '):(v==null?'':String(v));return '"'+s.replace(/"/g,'""')+'"';}
function exportCsv(rows,file){const out='\uFEFF'+[cols.map(c=>cell(c[1])).join(';'),...rows.map(r=>cols.map(c=>cell(r[c[0]])).join(';'))].join('\r\n');fs.writeFileSync(file,out,'utf8');}
module.exports={exportCsv};
