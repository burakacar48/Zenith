console.log('Electron modülü yüklemeye çalışılıyor...');
let app, BrowserWindow, dialog, shell, Menu;

try {
    const electron = require('electron');
    console.log('Electron modülü başarıyla yüklendi:', typeof electron);
    console.log('Electron içeriği:', Object.keys(electron));
    
    app = electron.app;
    BrowserWindow = electron.BrowserWindow;
    dialog = electron.dialog;
    shell = electron.shell;
    Menu = electron.Menu;
    
    console.log('App objesi:', typeof app);
    console.log('BrowserWindow objesi:', typeof BrowserWindow);
} catch (error) {
    console.error('Electron yüklenirken hata:', error);
    process.exit(1);
}

const path = require('path');
const { spawn } = require('child_process');
// Built-in fetch Node.js 18+ ile birlikte geliyor

let mainWindow;
let flaskProcess = null;
const FLASK_PORT = 5001;
const FLASK_URL = `http://127.0.0.1:${FLASK_PORT}`;

// Flask sunucusunu başlat
function startFlaskServer() {
    return new Promise((resolve, reject) => {
        const serverPath = path.join(__dirname, '..', 'server');
        
        console.log('Flask sunucusu başlatılıyor...', serverPath);
        
        // Python ile Flask uygulamasını başlat (production modunda)
        flaskProcess = spawn('python', ['app.py'], {
            cwd: serverPath,
            stdio: 'pipe',
            env: {
                ...process.env,
                FLASK_ENV: 'production',
                FLASK_DEBUG: '0'
            }
        });
        
        let serverReady = false;
        let resolveTimeout = null;
        
        flaskProcess.stdout.on('data', (data) => {
            const output = data.toString();
            console.log('Flask:', output);
            
            // Flask sunucusunun başladığını kontrol et
            if ((output.includes('Running on') || output.includes('* Running on')) && !serverReady) {
                serverReady = true;
                console.log('Flask sunucusu başarıyla başlatıldı!');
                
                // 1 saniye bekleyip resolve et (debug restart'ı için)
                if (resolveTimeout) clearTimeout(resolveTimeout);
                resolveTimeout = setTimeout(() => {
                    if (serverReady) {
                        resolve();
                    }
                }, 1500);
            }
        });
        
        flaskProcess.stderr.on('data', (data) => {
            const output = data.toString();
            console.error('Flask Hata:', output);
            
            // Flask sunucusunun başladığını kontrol et (stderr'da da olabilir)
            if ((output.includes('Running on') || output.includes('* Running on')) && !serverReady) {
                serverReady = true;
                console.log('Flask sunucusu başarıyla başlatıldı! (stderr\'dan algılandı)');
                
                // 1 saniye bekleyip resolve et
                if (resolveTimeout) clearTimeout(resolveTimeout);
                resolveTimeout = setTimeout(() => {
                    if (serverReady) {
                        resolve();
                    }
                }, 1500);
            }
        });
        
        flaskProcess.on('close', (code) => {
            console.log(`Flask sunucusu kapandı. Kod: ${code}`);
            if (!serverReady) {
                reject(new Error(`Flask sunucusu başlatılamadı. Çıkış kodu: ${code}`));
            }
        });
        
        flaskProcess.on('error', (error) => {
            console.error('Flask başlatma hatası:', error);
            reject(error);
        });
        
        // 15 saniye timeout (debug restart için daha uzun)
        setTimeout(() => {
            if (!serverReady) {
                reject(new Error('Flask sunucusu 15 saniye içinde başlatılamadı'));
            }
        }, 15000);
    });
}

// Flask sunucusunun hazır olup olmadığını kontrol et
async function checkFlaskServer() {
    try {
        // Node.js 18+ built-in fetch kullan
        const response = await fetch(FLASK_URL);
        return response.ok;
    } catch (error) {
        return false;
    }
}

// Ana pencereyi oluştur
function createWindow() {
    // Ana pencereyi oluştur
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 1200,
        minHeight: 800,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            enableRemoteModule: false,
            preload: path.join(__dirname, 'preload.js'),
            webSecurity: false // Local sunucu için gerekli
        },
        icon: path.join(__dirname, 'assets', 'icon.png'),
        show: false, // Başlangıçta gizli
        titleBarStyle: 'default',
        title: 'Zenith Internet Cafe Manager'
    });

    // Pencere hazır olduğunda göster
    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
        
        // Development modunda DevTools'u aç
        if (process.argv.includes('--dev')) {
            mainWindow.webContents.openDevTools();
        }
    });

    // Loading sayfasını önce yükle
    mainWindow.loadFile('loading.html');

    // Flask sunucusu hazır olduğunda ana uygulamayı yükle
    startFlaskServer()
        .then(async () => {
            console.log('Flask sunucusu hazır, 2 saniye bekleyip uygulamayı yüklüyoruz...');
            
            // Biraz bekle ki sunucu tamamen hazır olsun
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            // Flask uygulamasını yükle
            mainWindow.loadURL(FLASK_URL);
        })
        .catch((error) => {
            console.error('Flask sunucusu başlatılamadı:', error);
            dialog.showErrorBox(
                'Sunucu Hatası',
                `Flask sunucusu başlatılamadı:\n${error.message}\n\nLütfen Python'un yüklü olduğundan ve server/ klasöründe gerekli bağımlılıkların kurulu olduğundan emin olun.`
            );
            app.quit();
        });

    // Pencere kapatıldığında
    mainWindow.on('closed', () => {
        mainWindow = null;
    });

    // Dış bağlantıları varsayılan tarayıcıda aç
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        shell.openExternal(url);
        return { action: 'deny' };
    });

    // Navigation olaylarını yakala
    mainWindow.webContents.on('will-navigate', (event, navigationUrl) => {
        const parsedUrl = new URL(navigationUrl);
        
        // Eğer dış bir URL ise, varsayılan tarayıcıda aç
        if (parsedUrl.origin !== FLASK_URL) {
            event.preventDefault();
            shell.openExternal(navigationUrl);
        }
    });
}

// Menü oluştur
function createMenu() {
    const template = [
        {
            label: 'Dosya',
            submenu: [
                {
                    label: 'Yeniden Yükle',
                    accelerator: 'CmdOrCtrl+R',
                    click: () => {
                        if (mainWindow) {
                            mainWindow.reload();
                        }
                    }
                },
                {
                    label: 'Geliştirici Araçları',
                    accelerator: 'F12',
                    click: () => {
                        if (mainWindow) {
                            mainWindow.webContents.toggleDevTools();
                        }
                    }
                },
                { type: 'separator' },
                {
                    label: 'Çıkış',
                    accelerator: process.platform === 'darwin' ? 'Cmd+Q' : 'Ctrl+Q',
                    click: () => {
                        app.quit();
                    }
                }
            ]
        },
        {
            label: 'Görünüm',
            submenu: [
                { role: 'reload', label: 'Yeniden Yükle' },
                { role: 'forceReload', label: 'Zorla Yeniden Yükle' },
                { role: 'toggleDevTools', label: 'Geliştirici Araçları' },
                { type: 'separator' },
                { role: 'resetZoom', label: 'Zoom Sıfırla' },
                { role: 'zoomIn', label: 'Yakınlaştır' },
                { role: 'zoomOut', label: 'Uzaklaştır' },
                { type: 'separator' },
                { role: 'togglefullscreen', label: 'Tam Ekran' }
            ]
        },
        {
            label: 'Pencere',
            submenu: [
                { role: 'minimize', label: 'Küçült' },
                { role: 'close', label: 'Kapat' }
            ]
        },
        {
            label: 'Yardım',
            submenu: [
                {
                    label: 'Hakkında',
                    click: () => {
                        dialog.showMessageBox(mainWindow, {
                            type: 'info',
                            title: 'Hakkında',
                            message: 'Zenith Internet Cafe Manager',
                            detail: 'Sürüm 1.0.0\n\nInternet kafe yönetim sistemi'
                        });
                    }
                }
            ]
        }
    ];

    const menu = Menu.buildFromTemplate(template);
    Menu.setApplicationMenu(menu);
}

// Uygulama hazır olduğunda
app.whenReady().then(() => {
    createWindow();
    createMenu();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow();
        }
    });
});

// Tüm pencereler kapatıldığında
app.on('window-all-closed', () => {
    // macOS'ta uygulamalar genellikle açık kalır
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

// Uygulama kapatılmadan önce
app.on('before-quit', () => {
    console.log('Uygulama kapatılıyor...');
    
    // Flask sunucusunu kapat
    if (flaskProcess) {
        console.log('Flask sunucusu kapatılıyor...');
        flaskProcess.kill('SIGTERM');
        
        // Eğer SIGTERM ile kapanmazsa SIGKILL kullan
        setTimeout(() => {
            if (flaskProcess && !flaskProcess.killed) {
                console.log('Flask sunucusu zorla kapatılıyor...');
                flaskProcess.kill('SIGKILL');
            }
        }, 5000);
    }
});

// Hata yakalama
process.on('uncaughtException', (error) => {
    console.error('Yakalanmamış hata:', error);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('İşlenmeyen promise reddi:', reason);
});