const { app, BrowserWindow, ipcMain, dialog } = require('electron');
const path = require('path');
const fs = require('fs');
const { discoverDomains } = require('./search');
const { inspectSite } = require('./scraper');
const db = require('./database');
const { exportCsv } = require('./export');
let win;
function createWindow() {
  win = new BrowserWindow({ width: 1200, height: 850, minWidth: 900, minHeight: 650, title: 'UA Web Finder', webPreferences: { preload: path.join(__dirname, 'preload.js'), contextIsolation: true, nodeIntegration: false } });
  win.loadFile('index.html');
}
app.whenReady().then(() => { db.init(app.getPath('userData')); createWindow(); app.on('activate', () => { if (!BrowserWindow.getAllWindows().length) createWindow(); }); });
app.on('window-all-closed', () => { if (process.platform !== 'darwin') app.quit(); });
ipcMain.handle('db:list', () => db.list());
ipcMain.handle('db:import', async () => { const r = await dialog.showOpenDialog(win, { title: 'Імпорт доменів', properties: ['openFile'], filters: [{ name: 'Текстові файли', extensions: ['txt','csv'] }] }); if (r.canceled || !r.filePaths[0]) return []; return fs.readFileSync(r.filePaths[0], 'utf8').split(/\r?\n/).map(x => x.trim()).filter(Boolean); });
ipcMain.handle('search:discover', async (e, opts) => discoverDomains(opts, m => win && win.webContents.send('task:log', m)));
ipcMain.handle('site:inspect', async (e, domain, opts) => inspectSite(domain, opts));
ipcMain.handle('db:save', (e, record) => db.upsert(record));
ipcMain.handle('db:clear', () => db.clear());
ipcMain.handle('db:checked', () => db.checkedList());
ipcMain.handle('db:mark', (e, d) => db.mark(d));
app.on('before-quit', () => db.flush());
ipcMain.handle('export:csv', async () => { const r = await dialog.showSaveDialog(win, { title: 'Зберегти CSV', defaultPath: 'ua-web-finder.csv', filters: [{ name: 'CSV', extensions: ['csv'] }] }); if (r.canceled || !r.filePath) return { canceled: true }; exportCsv(db.list(), r.filePath); return { canceled: false, filePath: r.filePath }; });
ipcMain.handle('app:info', () => ({ version: app.getVersion() }));
