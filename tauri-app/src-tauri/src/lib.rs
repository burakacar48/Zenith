use std::process::{Command, Child};
use std::sync::Mutex;
use once_cell::sync::Lazy;
use anyhow::Result;
use tauri::Manager;

// Flask sunucusu için global referans
static FLASK_PROCESS: Lazy<Mutex<Option<Child>>> = Lazy::new(|| Mutex::new(None));

async fn start_flask_server() -> Result<()> {
    let current_exe = std::env::current_exe()?;
    let app_dir = current_exe.parent().unwrap();
    
    // Server klasörü yolunu bul
    let server_path = if app_dir.join("server").exists() {
        app_dir.join("server")
    } else if app_dir.parent().unwrap().join("server").exists() {
        app_dir.parent().unwrap().join("server")
    } else {
        // Development modunda
        let mut path = app_dir.to_path_buf();
        while path.parent().is_some() {
            path = path.parent().unwrap().to_path_buf();
            if path.join("server").exists() {
                break;
            }
        }
        path.join("server")
    };

    log::info!("Flask sunucusu başlatılıyor: {:?}", server_path);
    
    // Python ile Flask uygulamasını başlat
    // Windows'ta python.exe kullanılmalı
    let python_cmd = if cfg!(target_os = "windows") {
        "python.exe"
    } else {
        "python3"
    };
    
    let mut cmd = Command::new(python_cmd);
    cmd.arg("app.py")
       .current_dir(&server_path)
       .env("FLASK_ENV", "production")
       .env("FLASK_DEBUG", "0")
       .env("PYTHONIOENCODING", "utf-8");

    // Windows'ta konsol penceresini gizle
    #[cfg(target_os = "windows")]
    {
        use std::os::windows::process::CommandExt;
        const CREATE_NO_WINDOW: u32 = 0x08000000;
        cmd.creation_flags(CREATE_NO_WINDOW);
    }

    let child = cmd.spawn()?;
    
    // Global referansa kaydet (lock'u hemen bırak)
    {
        let mut flask_process = FLASK_PROCESS.lock().unwrap();
        *flask_process = Some(child);
    }
    
    // Flask'ın başlaması için biraz bekle
    tokio::time::sleep(tokio::time::Duration::from_secs(3)).await;
    
    log::info!("Flask sunucusu başlatıldı");
    Ok(())
}

async fn check_flask_ready() -> bool {
    match reqwest::get("http://127.0.0.1:5001").await {
        Ok(response) => response.status().is_success(),
        Err(_) => false,
    }
}

fn stop_flask_server() {
    let mut flask_process = FLASK_PROCESS.lock().unwrap();
    if let Some(mut child) = flask_process.take() {
        log::info!("Flask sunucusu kapatılıyor...");
        let _ = child.kill();
        let _ = child.wait();
        log::info!("Flask sunucusu kapatıldı");
    }
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .plugin(
            tauri_plugin_log::Builder::default()
                .level(log::LevelFilter::Info)
                .build(),
        )
        .setup(|app| {
            let app_handle = app.handle().clone();
            
            // Başlangıçta splash ekranını göster
            if let Some(splash) = app_handle.get_webview_window("splashscreen") {
                let _ = splash.show();
            }
            
            // Flask sunucusunu başlat
            tauri::async_runtime::spawn(async move {
                match start_flask_server().await {
                    Ok(_) => {
                        // Flask'ın hazır olmasını bekle
                        for _ in 0..10 {
                            if check_flask_ready().await {
                                log::info!("Flask sunucusu hazır!");
                                
                                // Ana pencereyi göster ve splash ekranını kapat
                                if let Some(main_window) = app_handle.get_webview_window("main") {
                                    let _ = main_window.show();
                                    let _ = main_window.set_focus();
                                }
                                
                                if let Some(splash) = app_handle.get_webview_window("splashscreen") {
                                    let _ = splash.close();
                                }
                                
                                break;
                            }
                            tokio::time::sleep(tokio::time::Duration::from_secs(1)).await;
                        }
                    }
                    Err(e) => {
                        log::error!("Flask sunucusu başlatılamadı: {}", e);
                    }
                }
            });

            Ok(())
        })
        .on_window_event(|_window, event| {
            if let tauri::WindowEvent::CloseRequested { .. } = event {
                stop_flask_server();
            }
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
