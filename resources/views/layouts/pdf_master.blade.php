<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', 'Dokumen PDF')</title>
    <style>
        /* RESET & BASE */
        @page { margin: 0cm; }
        body { 
            margin-top: 3.5cm; margin-left: 2cm; margin-right: 2cm; margin-bottom: 2cm; 
            font-family: sans-serif; font-size: 10pt; color: #333; line-height: 1.4; 
        }
        
        /* HEADER & FOOTER (Fixed Position) */
        header { 
            position: fixed; top: 0cm; left: 0cm; right: 0cm; height: 3cm; 
            background-color: #0f172a; color: white; padding: 0 2cm; line-height: 2cm; 
        }
        footer { 
            position: fixed; bottom: 0cm; left: 0cm; right: 0cm; height: 1.5cm; 
            background-color: #f3f4f6; text-align: center; line-height: 1.5cm; font-size: 8pt; color: #666; 
            border-top: 1px solid #ddd;
        }

        /* UTILITIES */
        .w-full { width: 100%; }
        .w-half { width: 50%; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .text-xs { font-size: 8pt; }
        .text-sm { font-size: 9pt; }
        .text-lg { font-size: 14pt; }
        .text-xl { font-size: 18pt; }
        .text-gray { color: #64748b; }
        .text-red { color: #ef4444; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        
        /* TABLE STYLING */
        table { border-collapse: collapse; width: 100%; margin-bottom: 1rem; }
        table th { 
            background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; 
            padding: 8px; text-align: left; font-size: 9pt; font-weight: bold; 
            text-transform: uppercase; color: #475569; 
        }
        table td { 
            border-bottom: 1px solid #f1f5f9; padding: 8px; vertical-align: top; 
        }
        table tr:last-child td { border-bottom: none; }
        
        /* NO BORDER TABLE (Layouting) */
        table.no-border th, table.no-border td { border: none; padding: 4px; }

        .company-name { font-size: 16pt; font-weight: bold; float: left; margin-top: 0.8cm;}
        .doc-title { float: right; font-size: 12pt; font-weight: normal; margin-top: 0.8cm; opacity: 0.8; }
    </style>
</head>
<body>
    <header>
        <div class="company-name">{{ config('app.name', 'Perusahaan Anda') }}</div>
        <div class="doc-title">@yield('header_right')</div>
    </header>

    <footer>
        Dicetak pada: {{ date('d/m/Y H:i') }} | Halaman <span class="page-number"></span>
    </footer>

    <main>
        @yield('content')
    </main>
    
    {{-- Script Nomor Halaman DOMPDF --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman {PAGE_NUM} / {PAGE_COUNT}";
            $size = 9;
            $font = $fontMetrics->getFont("sans-serif");
            $width = $fontMetrics->getTextWidth($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>