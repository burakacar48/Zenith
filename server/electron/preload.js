const { contextBridge, ipcRenderer } = require('electron');

// Güvenli API'leri renderer process'e açığa çıkar
contextBridge.exposeInMainWorld('electronAPI', {
    // Pencere kontrolleri
    minimizeWindow: () => ipcRenderer.invoke('minimize-window'),
    maximizeWindow: () => ipcRenderer.invoke('maximize-window'),
    closeWindow: () => ipcRenderer.invoke('close-window'),
    
    // Uygulama bilgileri
    getVersion: () => ipcRenderer.invoke('get-version'),
    
    // Sistem bilgileri (isteğe bağlı)
    platform: process.platform,
    
    // Debug için
    isDev: process.env.NODE_ENV === 'development'
});

// Konsol logları için
window.addEventListener('DOMContentLoaded', () => {
    console.log('Zenith Cafe Manager - Electron Preload Script Yüklendi');
});