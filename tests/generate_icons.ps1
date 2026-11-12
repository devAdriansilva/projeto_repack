Add-Type -AssemblyName System.Drawing

function Create-RepackIcon([int]$size, [string]$outputPath) {
    $bmp = New-Object System.Drawing.Bitmap($size, $size)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic

    # 1. Fundo com cantos arredondados (squircle moderno verde esmeralda / petroleo)
    $bgBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(0, 0)),
        (New-Object System.Drawing.Point($size, $size)),
        [System.Drawing.ColorTranslator]::FromHtml('#0F172A'),
        [System.Drawing.ColorTranslator]::FromHtml('#1E293B')
    )
    $g.FillRectangle($bgBrush, 0, 0, $size, $size)

    # 2. Quadrado interno arredondado verde esmeralda
    $margin = [int]($size * 0.08)
    $innerSize = $size - (2 * $margin)
    $innerRect = New-Object System.Drawing.Rectangle($margin, $margin, $innerSize, $innerSize)
    $pt2X = [int]($size - $margin)
    $pt2Y = [int]($size - $margin)
    $p1 = New-Object System.Drawing.Point($margin, $margin)
    $p2 = New-Object System.Drawing.Point($pt2X, $pt2Y)
    $c1 = [System.Drawing.ColorTranslator]::FromHtml('#10B981')
    $c2 = [System.Drawing.ColorTranslator]::FromHtml('#059669')
    $emeraldBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush($p1, $p2, $c1, $c2)
    
    $radius = [int]($size * 0.22)
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $path.AddArc($innerRect.X, $innerRect.Y, $radius, $radius, 180, 90)
    $path.AddArc($innerRect.Right - $radius, $innerRect.Y, $radius, $radius, 270, 90)
    $path.AddArc($innerRect.Right - $radius, $innerRect.Bottom - $radius, $radius, $radius, 0, 90)
    $path.AddArc($innerRect.X, $innerRect.Bottom - $radius, $radius, $radius, 90, 90)
    $path.CloseFigure()
    $g.FillPath($emeraldBrush, $path)

    # Borda sutil de destaque
    $borderPen = New-Object System.Drawing.Pen([System.Drawing.ColorTranslator]::FromHtml('#6EE7B7'), [float]($size * 0.02))
    $g.DrawPath($borderPen, $path)

    # 3. Ícone estilizado de Pack / Caixa de Repack no centro
    $iconY = [int]($size * 0.22)
    $iconW = [int]($size * 0.38)
    $iconH = [int]($size * 0.28)
    $iconX = [int](($size - $iconW) / 2)
    
    $boxPen = New-Object System.Drawing.Pen([System.Drawing.Color]::White, [float]($size * 0.04))
    $boxPen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $boxPen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    
    # Caixa com linhas industriais
    $g.DrawRectangle($boxPen, $iconX, $iconY, $iconW, $iconH)
    $g.DrawLine($boxPen, $iconX, [int]($iconY + $iconH * 0.45), [int]($iconX + $iconW), [int]($iconY + $iconH * 0.45))
    
    # 4. Texto 'REPACK' em caixa alta nítido
    $fontSize = [float]($size * 0.16)
    $font = New-Object System.Drawing.Font('Arial', $fontSize, [System.Drawing.FontStyle]::Bold)
    $textBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $format = New-Object System.Drawing.StringFormat
    $format.Alignment = [System.Drawing.StringAlignment]::Center
    $format.LineAlignment = [System.Drawing.StringAlignment]::Center
    
    $textRect = New-Object System.Drawing.RectangleF(0, [float]($size * 0.58), [float]$size, [float]($size * 0.28))
    $g.DrawString('REPACK', $font, $textBrush, $textRect, $format)

    $bmp.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose()
    $bmp.Dispose()
    Write-Host "Criado com sucesso: $outputPath ($size x $size)"
}

Create-RepackIcon 512 'public\assets\img\icon-512.png'
Create-RepackIcon 192 'public\assets\img\icon-192.png'
Create-RepackIcon 180 'public\assets\img\apple-touch-icon.png'
Create-RepackIcon 64  'public\assets\img\favicon.png'
