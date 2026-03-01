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
        }

        .label {
            width: 23%;
            display: inline-block;
            text-align: center;
            margin: 10px 1%;
            vertical-align: top;
        }

        .qr img {
            width: 70px;
            height: 70px;
        }

        .name {
            font-size: 11px;
            font-weight: bold;
        }

        .weight {
            font-size: 10px;
        }

        .price {
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    @foreach($items as $item)

        <div class="label">

            @if($item->qr_base64)
                <div class="qr">
                    <img src="data:image/png;base64,{{ $item->qr_base64 }}">
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

    @endforeach

</body>

</html>