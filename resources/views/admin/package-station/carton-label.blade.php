<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label {{ $carton->carton_number }}</title>
    <style>
        .screen-only{width:100mm;margin:12px auto 24px;text-align:center}.screen-only button{padding:8px 18px;font-size:14px;cursor:pointer}
        @media screen{body{padding:16px 0;background:#e9ecef}.label{margin:0 auto;background:#fff}}
        @media print{.screen-only{display:none}}
    </style>
    <script src="{{ asset('pack-pos/carton-label.js') }}?v={{ filemtime(public_path('pack-pos/carton-label.js')) }}"></script>
</head>
<body>
    <div id="label-sheet"></div>
    <div class="screen-only"><button type="button" onclick="window.print()">Print label</button></div>
    <script>
        window.__PACK_POS_LABEL__ = @json($labelConfig);
        var style = document.createElement('style');
        style.textContent = window.CartonLabel.css;
        document.head.appendChild(style);
        document.getElementById('label-sheet').innerHTML = window.CartonLabel.html(@json($labelData));
    </script>
</body>
</html>
