param (
    [Parameter(Mandatory=$false)]
    [string]$ServerUrl = "http://127.0.0.1:8000",
    [Parameter(Mandatory=$false)]
    [string]$AgentToken = "1234",
    [Parameter(Mandatory=$false)]
    [int]$PollInterval = 2
)

$Host.UI.RawUI.WindowTitle = "PhotoBooth Print Bridge Agent"
Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host "       PHOTOBOOTH PRO - REMOTE PRINT BRIDGE AGENT (PC CLIENT)     " -ForegroundColor Yellow
Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host "Server Target   : $ServerUrl" -ForegroundColor White
Write-Host "Interval Polling: $PollInterval detik" -ForegroundColor White
Write-Host ""

Add-Type -AssemblyName System.Drawing

# 1. Deteksi Printer Windows Lokal
$installedPrinters = [System.Drawing.Printing.PrinterSettings]::InstalledPrinters
if ($installedPrinters.Count -eq 0) {
    Write-Host "[PERINGATAN] Tidak ada printer terpasang di sistem Windows ini!" -ForegroundColor Red
} else {
    Write-Host "[OK] Terdeteksi $($installedPrinters.Count) printer di PC ini:" -ForegroundColor Green
    foreach ($p in $installedPrinters) {
        Write-Host "  - $p" -ForegroundColor Gray
    }
}

# 2. Daftarkan Printer ke Server Cloud / Online
$syncUrl = "$($ServerUrl.TrimEnd('/'))/api/agent/sync-printers"
$headers = @{
    "X-Agent-Token" = $AgentToken
    "Accept" = "application/json"
    "Content-Type" = "application/json"
}

$printersPayload = @()
foreach ($p in $installedPrinters) {
    $printersPayload += $p
}

$computerName = $env:COMPUTERNAME
$body = @{
    client_name = $computerName
    printers = $printersPayload
} | ConvertTo-Json

try {
    Write-Host "`nMenghubungkan & mendaftarkan printer ke server cloud..." -ForegroundColor Cyan
    $syncResponse = Invoke-RestMethod -Uri $syncUrl -Method Post -Headers $headers -Body $body -TimeoutSec 10
    if ($syncResponse.success) {
        Write-Host "[SUKSES] Printer berhasil didaftarkan ke server online!" -ForegroundColor Green
    }
} catch {
    Write-Host "[INFO] Tidak dapat mendaftarkan otomatis ke $syncUrl ($($_.Exception.Message)). Pastikan server aktif." -ForegroundColor Yellow
}

Write-Host "`n[STANDBY] Mendengarkan perintah cetak dari Tablet Kiosk..." -ForegroundColor Green
Write-Host "Tekan Ctrl+C untuk menghentikan agent.`n" -ForegroundColor DarkGray

$jobsUrl = "$($ServerUrl.TrimEnd('/'))/api/agent/jobs"
$tempDir = Join-Path $env:TEMP "photobooth_print_cache"
if (-not (Test-Path $tempDir)) {
    New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$printScript = Join-Path $scriptDir "print_windows.ps1"

while ($true) {
    try {
        $pendingRes = Invoke-RestMethod -Uri $jobsUrl -Method Get -Headers $headers -TimeoutSec 5 -ErrorAction Stop
        if ($pendingRes.success -and $pendingRes.jobs.Count -gt 0) {
            foreach ($job in $pendingRes.jobs) {
                Write-Host "[PRINT JOB #$($job.id)] Menerima tugas cetak dari Tablet Kiosk!" -ForegroundColor Yellow
                Write-Host "  - Sesi ID     : $($job.session_id)" -ForegroundColor White
                Write-Host "  - Printer     : $($job.printer_name)" -ForegroundColor White
                Write-Host "  - Ukuran/Kopi : $($job.paper_size) / $($job.copies) salinan" -ForegroundColor White
                Write-Host "  - URL Gambar  : $($job.file_url)" -ForegroundColor Gray

                if (-not $job.file_url) {
                    Write-Host "  [GAGAL] URL file gambar kosong." -ForegroundColor Red
                    continue
                }

                # Update status menjadi 'printing'
                $updateUrl = "$($ServerUrl.TrimEnd('/'))/api/agent/jobs/$($job.id)/update"
                $statusBody = @{ status = "printing"; progress = 30 } | ConvertTo-Json
                Invoke-RestMethod -Uri $updateUrl -Method Post -Headers $headers -Body $statusBody -TimeoutSec 5 | Out-Null

                # Download gambar ke file lokal sementara
                $localImage = Join-Path $tempDir "job_$($job.id)_$([System.IO.Path]::GetRandomFileName()).jpg"
                Invoke-WebRequest -Uri $job.file_url -OutFile $localImage -TimeoutSec 30

                if (Test-Path $localImage) {
                    Write-Host "  [OK] Gambar berhasil diunduh ($((Get-Item $localImage).Length) bytes)." -ForegroundColor Green
                    Write-Host "  Mengirim ke spooler printer '$($job.printer_name)'..." -ForegroundColor Cyan

                    # Eksekusi pencetakan via print_windows.ps1
                    $printOut = & powershell -NoProfile -ExecutionPolicy Bypass -File $printScript -ImagePath $localImage -PrinterName $job.printer_name -Copies $job.copies -PaperSize $job.paper_size
                    $printJson = $printOut | ConvertFrom-Json -ErrorAction SilentlyContinue

                    if ($printJson -and $printJson.success) {
                        Write-Host "  [SUKSES] Dokumen berhasil dicetak secara fisik!" -ForegroundColor Green
                        $completeBody = @{ status = "completed"; progress = 100 } | ConvertTo-Json
                        Invoke-RestMethod -Uri $updateUrl -Method Post -Headers $headers -Body $completeBody -TimeoutSec 5 | Out-Null
                    } else {
                        $errMsg = if ($printJson) { $printJson.message } else { "Gagal mencetak ke driver printer." }
                        Write-Host "  [GAGAL] $errMsg" -ForegroundColor Red
                        $failBody = @{ status = "failed"; error_message = $errMsg } | ConvertTo-Json
                        Invoke-RestMethod -Uri $updateUrl -Method Post -Headers $headers -Body $failBody -TimeoutSec 5 | Out-Null
                    }

                    # Hapus file cache
                    Remove-Item $localImage -Force -ErrorAction SilentlyContinue
                }
            }
        }
    } catch {
        # Koneksi timeout atau offline sementara, lanjut polling
    }

    Start-Sleep -Seconds $PollInterval
}
