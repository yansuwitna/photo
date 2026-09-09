// Prevents additional console window on Windows in release
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

mod camera_bridge;
mod printer_bridge;

use camera_bridge::{detect_connected_cameras, capture_camera};
use printer_bridge::{detect_printers, submit_print_job};

#[tauri::command]
fn get_system_status() -> serde_json::Value {
    serde_json::json!({
        "status": "online",
        "kiosk": true,
        "version": "1.0.0",
        "platform": std::env::consts::OS,
    })
}

fn main() {
    tauri::Builder::default()
        .invoke_handler(tauri::generate_handler![
            get_system_status,
            detect_connected_cameras,
            capture_camera,
            detect_printers,
            submit_print_job
        ])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}