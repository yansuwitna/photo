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

    $img = [System.Drawing.Image]::FromFile($ImagePath)
    $doc = New-Object System.Drawing.Printing.PrintDocument
    $doc.PrinterSettings.PrinterName = $targetPrinter
    $doc.PrinterSettings.Copies = [Math]::Max(1, $Copies)

    # Set Orientation
    if ($Orientation -eq "Landscape") {
        $doc.DefaultPageSettings.Landscape = $true
    } elseif ($Orientation -eq "Portrait") {
        $doc.DefaultPageSettings.Landscape = $false
    } else {
        # Auto: sesuaikan dengan rasio gambar
        $doc.DefaultPageSettings.Landscape = ($img.Width -gt $img.Height)
    }

    # Set Margins to 0 untuk cetak borderless / fit to printable area
    $doc.DefaultPageSettings.Margins = New-Object System.Drawing.Printing.Margins(0, 0, 0, 0)
    $doc.OriginAtMargins = $false

    # Pilih ukuran kertas yang cocok jika tersedia di driver
    $paperMatched = $false
    $requestedPaper = $PaperSize.ToLower()
    foreach ($ps in $doc.PrinterSettings.PaperSizes) {
        $nameLower = $ps.PaperName.ToLower()
        if ($requestedPaper -match "4r|4x6") {
            if ($nameLower -match "4\s*x\s*6|10\s*x\s*15|4r|photo|foto") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        } elseif ($requestedPaper -match "a4") {
            if ($nameLower -match "^a4") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        } elseif ($requestedPaper -match "5r|5x7") {
            if ($nameLower -match "5\s*x\s*7|13\s*x\s*18|5r") {
                $doc.DefaultPageSettings.PaperSize = $ps
                $paperMatched = $true
                break
            }
        }
    }

    # Event PrintPage handler
    $doc.add_PrintPage({
        param($sender, $ev)

        $bounds = $ev.PageBounds
        if ($ev.MarginBounds.Width -gt 0 -and $ev.MarginBounds.Height -gt 0) {
            # Gunakan MarginBounds atau PageBounds yang tersedia
            $bounds = $ev.PageBounds
        }

        # Hitung scaling aspect ratio agar tidak terdistorsi (Best Fit)
        $ratioX = [double]$bounds.Width / [double]$img.Width
        $ratioY = [double]$bounds.Height / [double]$img.Height
        $ratio = [Math]::Min($ratioX, $ratioY)

        $renderW = [int]($img.Width * $ratio)
        $renderH = [int]($img.Height * $ratio)
        $offsetX = $bounds.X + [int](($bounds.Width - $renderW) / 2)
        $offsetY = $bounds.Y + [int](($bounds.Height - $renderH) / 2)

        $ev.Graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        $ev.Graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
        $ev.Graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
        $ev.Graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality

        $ev.Graphics.DrawImage($img, $offsetX, $offsetY, $renderW, $renderH)
        $ev.HasMorePages = $false
    })

    # Eksekusi cetak langsung ke Windows Print Spooler
    $doc.Print()

    $result = @{
        success = $true
        printer = $targetPrinter
        copies = $doc.PrinterSettings.Copies
        paper_size = $doc.DefaultPageSettings.PaperSize.PaperName
        landscape = $doc.DefaultPageSettings.Landscape
        message = "Pencetakan berhasil dikirim langsung ke printer $targetPrinter."
    }

    ConvertTo-Json -InputObject $result -Compress
}
catch {
    $errResult = @{
        success = $false
        printer = $targetPrinter
        message = "Gagal mencetak ke printer: " + $_.Exception.Message
    }
    ConvertTo-Json -InputObject $errResult -Compress
}
finally {
    if ($img) { $img.Dispose() }
    if ($doc) { $doc.Dispose() }
}
