<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
        }

        .label {
            width: 200px;
            /* ukuran kecil */
            text-align: center;
        }

        .qr img {
            width: 70px;
            height: 70px;
            margin-bottom: 5px;
        }

        .name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .weight {
            font-size: 11px;
            margin-bottom: 3px;
        }

        .price {
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="label">

        @if($qr)
            <div class="qr">
                <img src="data:image/png;base64,{{ $qr }}">
            </div>
        @endif

        <div class="name">
            {{ $item->manual_name ?? $item->item?->name }}
        </div>

        <div class="weight">
            {{ number_format($item->weight, 2) }} gr.
        </div>

        <div class="price">
            Rp {{ number_format($item->item?->price_sell ?? 0, 0, ',', '.') }}
        </div>

    </div>

</body>

</html>