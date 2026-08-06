<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulletins - {{ $currentYear->name }}</title>
    <style>
        @page { 
            margin: 10mm; 
            size: A4;
        }
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 10px;
            color: #333;
        }
        .bulletin-page {
            page-break-after: always;
        }
        .bulletin-page:last-child {
            page-break-after: auto;
        }
    </style>
</head>
<body>
    @foreach($allData as $data)
        <div class="bulletin-page">
            {{-- On réutilise EXACTEMENT la même structure que pdf.report-card --}}
            @include('pdf.report-card', $data)
        </div>
    @endforeach
</body>
</html>