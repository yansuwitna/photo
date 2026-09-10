param (
    [Parameter(Mandatory=$true)]
    [string]$ImagePath,
    [Parameter(Mandatory=$false)]
    [string]$PrinterName = "",
    [Parameter(Mandatory=$false)]
    [int]$Copies = 1,
    [Parameter(Mandatory=$false)]
    [string]$PaperSize = "4R",
    [Parameter(Mandatory=$false)]
    [string]$Orientation = "Auto"
)

$ErrorActionPreference = "Stop"

$img = $null
$doc = $null

try {
    if (-not (Test-Path -Path $ImagePath -PathType Leaf)) {
        throw "File gambar tidak ditemukan: $ImagePath"
    }

    Add-Type -AssemblyName System.Drawing

    $installedPrinters = [System.Drawing.Printing.PrinterSettings]::InstalledPrinters
    if ($installedPrinters.Count -eq 0) {
        throw "Tidak ada printer yang terpasang di sistem Windows."
    }

    $targetPrinter = $PrinterName
    if (-not $targetPrinter -or $targetPrinter.Trim() -eq "") {
        $defaultPrinterQuery = Get-CimInstance Win32_Printer | Where-Object { $_.Default -eq $true } | Select-Object -First 1 -ExpandProperty Name
        $targetPrinter = if ($defaultPrinterQuery) { $defaultPrinterQuery } else { $installedPrinters[0] }
    }

    $printerFound = $false
    foreach ($p in $installedPrinters) {
        if ($p.Trim().ToLower() -eq $targetPrinter.Trim().ToLower()) {
            $targetPrinter = $p
            $printerFound = $true
            break
        }
    }

    if (-not $printerFound) {
        # Coba pencarian parsial (misal 'EPSON' atau 'L1210')
        foreach ($p in $installedPrinters) {
            if ($p.ToLower().Contains($targetPrinter.ToLower())) {
                $targetPrinter = $p
                $printerFound = $true
                break
            }
        }
    }

    if (-not $printerFound) {
        throw "Printer '$targetPrinter' tidak ditemukan di daftar printer Windows."
    }

    # Cek status kesiapan printer fisik
    $isVirtual = ($targetPrinter -match "PDF|XPS|OneNote|Fax|Virtual")
    if (-not $isVirtual) {
        $pInfo = Get-CimInstance Win32_Printer -Filter "Name = '$targetPrinter'" -ErrorAction SilentlyContinue
        if ($pInfo) {
            if ($pInfo.WorkOffline) {
                throw "Printer '$targetPrinter' sedang OFFLINE. Pastikan kabel USB terpasang ke komputer dan tombol power printer menyala."
            }
            if ($pInfo.PrinterStatus -eq 7) {
                throw "Status printer '$targetPrinter' adalah Offline. Periksa kabel USB dan daya printer."
            }
            if ($pInfo.PrinterStatus -eq 5) {
                throw "Printer '$targetPrinter' membutuhkan perhatian: kertas habis atau terjadi paper jam."
            }
        }
    }

    $img = [System.Drawing.Image]::FromFile($ImagePath)
    $doc = New-Object System.Drawing.Printing.PrintDocument
    $doc.PrinterSettings.PrinterName = $targetPrinter
    $doc.PrinterSettings.Copies = [Math]::Max(1, $Copies)

    # Dukungan cetak otomatis tanpa dialog untuk printer virtual (PDF)
    $outputPdfPath = ""
    if ($targetPrinter -match "PDF|XPS") {
        $pdfDir = Join-Path (Split-Path -Path $ImagePath -Parent) "printed_output"
        if (-not (Test-Path $pdfDir)) { New-Item -ItemType Directory -Path $pdfDir -Force | Out-Null }
        $pdfBaseName = [System.IO.Path]::GetFileNameWithoutExtension($ImagePath)
        $outputPdfPath = Join-Path $pdfDir "$pdfBaseName.pdf"
        $doc.PrinterSettings.PrintToFile = $true
        $doc.PrinterSettings.PrintFileName = $outputPdfPath
    }

    # Set Orientation
    $isLandscape = $false
    if ($Orientation -eq "Landscape") {
        $isLandscape = $true
    } elseif ($Orientation -eq "Portrait") {
        $isLandscape = $false
    } else {
        # Auto: sesuaikan dengan rasio gambar
        $isLandscape = ($img.Width -gt $img.Height)
    }
    $doc.DefaultPageSettings.Landscape = $isLandscape
    $doc.PrinterSettings.DefaultPageSettings.Landscape = $isLandscape

    # Set Margins to 0 untuk cetak fit to page
    $doc.DefaultPageSettings.Margins = New-Object System.Drawing.Printing.Margins(0, 0, 0, 0)
    $doc.OriginAtMargins = $false

    # Pilih ukuran kertas yang cocok jika tersedia di driver
    $paperMatched = $false
    $requestedPaper = $PaperSize.ToLower().Trim()
    foreach ($ps in $doc.PrinterSettings.PaperSizes) {
        $nameLower = $ps.PaperName.ToLower()
        if ($requestedPaper -match "4r|4x6|10x15|photo|foto") {
            if ($nameLower -match "10\s*x\s*15|4\s*x\s*6|4r|foto|photo|kg") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $doc.PrinterSettings.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        } elseif ($requestedPaper -match "a4") {
            if ($nameLower -match "^a4") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $doc.PrinterSettings.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        } elseif ($requestedPaper -match "strip|2x6") {
            if ($nameLower -match "2\s*x\s*6|strip") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $doc.PrinterSettings.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        } elseif ($requestedPaper -match "5r|5x7|13x18") {
            if ($nameLower -match "5\s*x\s*7|13\s*x\s*18|5r") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $doc.PrinterSettings.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        } elseif ($requestedPaper -match "a6|105x148") {
            if ($nameLower -match "^a6") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $doc.PrinterSettings.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        }
    }

    # Fallback jika Strip 2x6 diminta tapi driver tidak punya ukuran 2x6: gunakan kertas 4R (10x15 cm)
    if (-not $paperMatched -and ($requestedPaper -match "strip|2x6")) {
        foreach ($ps in $doc.PrinterSettings.PaperSizes) {
            $nameLower = $ps.PaperName.ToLower()
            if ($nameLower -match "10\s*x\s*15|4\s*x\s*6|4r|photo") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $doc.PrinterSettings.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        }
    }

    # Event PrintPage handler
    $doc.add_PrintPage({
        param($sender, $ev)

        $bounds = $ev.PageBounds
        $pageW = $bounds.Width
        $pageH = $bounds.Height
        $imgW = $img.Width
        $imgH = $img.Height

        $ev.Graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        $ev.Graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
        $ev.Graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
        $ev.Graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality

        # Cek apakah single photostrip (~1:3) dicetak pada kertas 4R (~2:3) -> otomatis double strip
        $isStrip = ($imgH / $imgW -ge 2.2)
        $isPaper4R = ($pageW -gt 0 -and $pageH -gt 0 -and [Math]::Abs(($pageH / $pageW) - 1.5) -lt 0.3)

        if ($isStrip -and $isPaper4R) {
            # Double Strip 2x6 berdampingan pada lembar 4R
            $halfW = [int]($pageW / 2)
            $ratio = [Math]::Min([double]$halfW / $imgW, [double]$pageH / $imgH)
            $renderW = [int]($imgW * $ratio)
            $renderH = [int]($imgH * $ratio)
            $offsetY = [int](($pageH - $renderH) / 2)

            # Strip Kiri
            $offsetX1 = [int](($halfW - $renderW) / 2)
            $dest1 = New-Object System.Drawing.Rectangle($offsetX1, $offsetY, $renderW, $renderH)
            $ev.Graphics.DrawImage($img, $dest1, 0, 0, $imgW, $imgH, [System.Drawing.GraphicsUnit]::Pixel)

            # Strip Kanan
            $offsetX2 = $halfW + [int](($halfW - $renderW) / 2)
            $dest2 = New-Object System.Drawing.Rectangle($offsetX2, $offsetY, $renderW, $renderH)
            $ev.Graphics.DrawImage($img, $dest2, 0, 0, $imgW, $imgH, [System.Drawing.GraphicsUnit]::Pixel)

            # Garis potong halus di tengah
            $pen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(210, 210, 210), 1)
            $pen.DashStyle = [System.Drawing.Drawing2D.DashStyle]::Dash
            $ev.Graphics.DrawLine($pen, $halfW, 0, $halfW, $pageH)
            $pen.Dispose()
        } else {
            # Best Fit to Printable Area
            $ratioX = [double]$pageW / $imgW
            $ratioY = [double]$pageH / $imgH
            $ratio = [Math]::Min($ratioX, $ratioY)

            $renderW = [int]($imgW * $ratio)
            $renderH = [int]($imgH * $ratio)
            $offsetX = [int](($pageW - $renderW) / 2)
            $offsetY = [int](($pageH - $renderH) / 2)

            $dest = New-Object System.Drawing.Rectangle($offsetX, $offsetY, $renderW, $renderH)
            $ev.Graphics.DrawImage($img, $dest, 0, 0, $imgW, $imgH, [System.Drawing.GraphicsUnit]::Pixel)
        }

        $ev.HasMorePages = $false
    })

    # Eksekusi cetak langsung ke Windows Print Spooler
    $doc.Print()

    $result = @{
        success = $true
        printer = $targetPrinter
        copies = $doc.PrinterSettings.Copies
        paper_size = if ($doc.DefaultPageSettings.PaperSize) { $doc.DefaultPageSettings.PaperSize.PaperName } else { $PaperSize }
        landscape = $doc.DefaultPageSettings.Landscape
        message = "Pencetakan berhasil dikirim langsung ke printer $targetPrinter."
        pdf_path = $outputPdfPath
    }

    ConvertTo-Json -InputObject $result -Compress
}
catch {
    $errResult = @{
        success = $false
        printer = $targetPrinter
        message = "Gagal mencetak: " + $_.Exception.Message
    }
    ConvertTo-Json -InputObject $errResult -Compress
}
finally {
    if ($img) { $img.Dispose() }
    if ($doc) { $doc.Dispose() }
}
