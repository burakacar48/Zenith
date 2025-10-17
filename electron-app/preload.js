const { contextBridge, ipcRenderer } = require('electron');

// Güvenli API'leri renderer process'e aktar
contextBridge.exposeInMainWorld('electronAPI', {
    // Uygulama bilgileri
    getVersion: () => process.versions.electron,
    getPlatform: () => process.platform,
    
    // Pencere kontrolleri
    minimize: () => ipcRenderer.invoke('window-minimize'),
    maximize: () => ipcRenderer.invoke('window-maximize'),
    close: () => ipcRenderer.invoke('window-close'),
    
    // Sistem bilgileri
    getAppInfo: () => ({
        name: 'Zenith Cafe Manager',
        version: '1.0.0',
        electron: process.versions.electron,
        node: process.versions.node,
        platform: process.platform
    }),
    
    // Debug için
    log: (message) => console.log('Renderer:', message),
    error: (message) => console.error('Renderer Error:', message)
});

// Console log'ları main process'e yönlendir
window.addEventListener('DOMContentLoaded', () => {
    // Sayfa yüklendiğinde bilgi ver
    console.log('Zenith Cafe Manager - Electron App başlatıldı');
    
    // Hata yakalama
    window.addEventListener('error', (event) => {
        console.error('Renderer Error:', event.error);
    });
    
    window.addEventListener('unhandledrejection', (event) => {
        console.error('Unhandled Rejection:', event.reason);
    });
});

// Flask uygulaması için özel ayarlar
window.addEventListener('DOMContentLoaded', () => {
    // Eğer Flask uygulaması yüklendiyse
    if (document.querySelector('title')?.textContent.includes('Flask')) {
        // Başlık güncelle
        document.title = 'Zenith Internet Cafe Manager';
    }
    
    // Local storage ve session storage erişimini etkinleştir
    try {
        localStorage.setItem('electron-app', 'true');
        sessionStorage.setItem('electron-app', 'true');
    } catch (e) {
        console.warn('Storage access limited:', e);
    }
});

// Network durumu kontrolü
window.addEventListener('online', () => {
    console.log('İnternet bağlantısı mevcut');
});

window.addEventListener('offline', () => {
    console.log('İnternet bağlantısı yok');
});