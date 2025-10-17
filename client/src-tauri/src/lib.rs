use tauri::Manager;
use std::process::Command;
use serde::{Deserialize, Serialize};

#[derive(Debug, Serialize, Deserialize)]
struct Game {
    id: i32,
    oyun_adi: String,
    calistirma_tipi: String,
    calistirma_verisi: String,
    launch_script: Option<String>,
}

#[derive(Debug, Serialize, Deserialize)]
struct LaunchData {
    yol: Option<String>,
    argumanlar: Option<String>,
    app_id: Option<String>,
}

// Oyun başlatma komutu
#[tauri::command]
async fn launch_game(game: String) -> Result<String, String> {
    let game: Game = serde_json::from_str(&game)
        .map_err(|e| format!("JSON parse hatası: {}", e))?;
    
    let launch_data: LaunchData = serde_json::from_str(&game.calistirma_verisi)
        .map_err(|e| format!("Launch data parse hatası: {}", e))?;

    if game.calistirma_tipi == "exe" {
        // Eğer özel launch script varsa
        if let Some(script) = &game.launch_script {
            if !script.trim().is_empty() {
                let mut script_content = script.clone();
                
                // Değişkenleri değiştir
                if let Some(ref yol) = launch_data.yol {
                    script_content = script_content.replace("%EXE_YOLU%", yol);
                }
                if let Some(ref args) = launch_data.argumanlar {
                    script_content = script_content.replace("%EXE_ARGS%", args);
                }

                // Windows'ta geçici bat dosyası oluştur ve çalıştır
                #[cfg(target_os = "windows")]
                {
                    let temp_dir = std::env::temp_dir();
                    let bat_path = temp_dir.join(format!("launch_{}.bat", game.id));
                    
                    std::fs::write(&bat_path, script_content)
                        .map_err(|e| format!("Bat dosyası yazma hatası: {}", e))?;
                    
                    Command::new("cmd")
                        .args(&["/C", bat_path.to_str().unwrap()])
                        .spawn()
                        .map_err(|e| format!("Bat çalıştırma hatası: {}", e))?;
                }
                
                return Ok("Oyun özel script ile başlatıldı".to_string());
            }
        }
        
        // Script yoksa direkt exe çalıştır
        if let Some(yol) = launch_data.yol {
            let args = launch_data.argumanlar.unwrap_or_default();
            
            #[cfg(target_os = "windows")]
            {
                Command::new("cmd")
                    .args(&["/C", "start", "", &yol, &args])
                    .spawn()
                    .map_err(|e| format!("EXE başlatma hatası: {}", e))?;
            }
            
            #[cfg(not(target_os = "windows"))]
            {
                Command::new(&yol)
                    .args(args.split_whitespace())
                    .spawn()
                    .map_err(|e| format!("Oyun başlatma hatası: {}", e))?;
            }
            
            Ok("Oyun başlatıldı".to_string())
        } else {
            Err("Oyun yolu bulunamadı".to_string())
        }
    } else if game.calistirma_tipi == "steam" {
        if let Some(app_id) = launch_data.app_id {
            let steam_url = format!("steam://run/{}", app_id);
            
            #[cfg(target_os = "windows")]
            {
                Command::new("cmd")
                    .args(&["/C", "start", &steam_url])
                    .spawn()
                    .map_err(|e| format!("Steam başlatma hatası: {}", e))?;
            }
            
            #[cfg(target_os = "linux")]
            {
                Command::new("xdg-open")
                    .arg(&steam_url)
                    .spawn()
                    .map_err(|e| format!("Steam başlatma hatası: {}", e))?;
            }
            
            #[cfg(target_os = "macos")]
            {
                Command::new("open")
                    .arg(&steam_url)
                    .spawn()
                    .map_err(|e| format!("Steam başlatma hatası: {}", e))?;
            }
            
            Ok("Steam oyunu başlatıldı".to_string())
        } else {
            Err("Steam App ID bulunamadı".to_string())
        }
    } else {
        Err("Bilinmeyen oyun tipi".to_string())
    }
}

// Lisans kontrolü için HTTP isteği
async fn check_license() -> Result<bool, String> {
    let server_url = "http://127.0.0.1:5001/api/internal/check_status";
    
    match reqwest::get(server_url).await {
        Ok(response) => {
            if response.status().is_success() {
                match response.json::<serde_json::Value>().await {
                    Ok(json) => {
                        if let Some(status) = json.get("status") {
                            Ok(status == "ok")
                        } else {
                            Err("Status alanı bulunamadı".to_string())
                        }
                    }
                    Err(e) => Err(format!("JSON parse hatası: {}", e))
                }
            } else {
                Err(format!("Sunucu hatası: {}", response.status()))
            }
        }
        Err(e) => Err(format!("Bağlantı hatası: {}", e))
    }
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .setup(|app| {
            // Lisans kontrolü yap
            let app_handle = app.handle().clone();
            tauri::async_runtime::spawn(async move {
                match check_license().await {
                    Ok(true) => {
                        log::info!("Lisans doğrulandı");
                    }
                    Ok(false) => {
                        log::error!("Lisans geçersiz");
                        std::process::exit(1);
                    }
                    Err(e) => {
                        log::error!("Lisans kontrolü başarısız: {}", e);
                        // Geliştirme modunda devam et, üretimde çık
                        #[cfg(not(debug_assertions))]
                        std::process::exit(1);
                    }
                }
            });
            
            Ok(())
        })
        .invoke_handler(tauri::generate_handler![launch_game])
        .run(tauri::generate_context!())
        .expect("Tauri uygulaması çalıştırılırken hata oluştu");
}