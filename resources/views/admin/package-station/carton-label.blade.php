<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label {{ $label['carton_number'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body{margin:0;padding:24px;background:#f1f3f5;font-family:"Roboto","Segoe UI",Arial,sans-serif;color:#111}
        .label{width:100mm;margin:0 auto;padding:6mm;border:2px solid #111;background:#fff}
        .label h1{margin:0 0 2mm;font-size:13pt}.label .code{margin:3mm 0;padding:3mm;border:2px dashed #111;text-align:center;font:700 15pt/1.2 "Courier New",monospace;letter-spacing:.04em;word-break:break-all}
        .label dl{margin:0;display:grid;grid-template-columns:auto 1fr;gap:1mm 4mm;font-size:9pt}.label dt{color:#555}.label dd{margin:0;font-weight:600;text-align:right}
        .actions{width:100mm;margin:12px auto 0;text-align:center}.actions button{padding:8px 18px;font-size:14px;cursor:pointer}
        @media print{body{padding:0;background:#fff}.actions{display:none}.label{border:0}}
    </style>
</head>
<body>
    <div class="label">
        <h1>{{ $label['company'] }}</h1>
        <div>Order {{ $label['order_number'] }}@if ($label['invoice_number']) · Invoice {{ $label['invoice_number'] }}@endif</div>
        <div class="code">{{ $label['qr_payload'] }}</div>
        <dl>
            <dt>Carton</dt><dd>{{ $label['carton_sequence'] }}</dd>
            <dt>Carton type</dt><dd>{{ $label['carton_type'] ?? '—' }}</dd>
            <dt>Products</dt><dd>{{ $label['sku_count'] }} SKU · {{ rtrim(rtrim($label['total_quantity'], '0'), '.') }} units</dd>
            <dt>Gross weight</dt><dd>{{ number_format((float) ($label['verified_gross_weight_g'] ?? $label['system_weight_g']), 1) }} g @if (! $label['verified_gross_weight_g'])(system)@endif</dd>
            <dt>Packed by</dt><dd>{{ $label['packed_by'] ?? '—' }}</dd>
            <dt>Verified by</dt><dd>{{ $label['verified_by'] ?? '—' }}</dd>
            <dt>Seal</dt><dd>{{ $label['seal_number'] ?? '—' }}</dd>
        </dl>
    </div>
    <div class="actions"><button type="button" onclick="window.print()">Print label</button></div>
</body>
</html>
