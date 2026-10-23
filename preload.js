const { contextBridge, ipcRenderer } = require('electron');
contextBridge.exposeInMainWorld('shopFinder', {
  list: () => ipcRenderer.invoke('db:list'), importDomains: () => ipcRenderer.invoke('db:import'),
  discover: opts => ipcRenderer.invoke('search:discover', opts), inspect: (domain, opts) => ipcRenderer.invoke('site:inspect', domain, opts),
  save: record => ipcRenderer.invoke('db:save', record), clearDb: () => ipcRenderer.invoke('db:clear'), checked: () => ipcRenderer.invoke('db:checked'), mark: d => ipcRenderer.invoke('db:mark', d), exportCsv: () => ipcRenderer.invoke('export:csv'), info: () => ipcRenderer.invoke('app:info'),
  onLog: callback => ipcRenderer.on('task:log', (_event, message) => callback(message))
});
