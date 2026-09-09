use serde_json::Value;

#[tauri::command]
pub fn detect_printers() -> Value {
    serde_json::json!({
        "printers": [
            {
                "name": "DNP DS-RX1HS",
                "status": "ready",
                "paper_remaining": 380
            }
        ]
    })
}

#[tauri::command]
pub fn submit_print_job(file_path: String, copies: u32, paper_size: String) -> Value {
    println!("Native Printer Bridge printing: {} (copies: {}, size: {})", file_path, copies, paper_size);
    serde_json::json!({
        "success": true,
        "job_id": "NATIVE-JOB-1",
        "copies": copies,
        "paper_size": paper_size
    })
}