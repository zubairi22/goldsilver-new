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

        .container {
            width: 400px;
            margin: 0 auto;
        }

        .row {
            width: 100%;
            margin-bottom: 15px;
        }

        .row:after {
            content: "";
            display: table;
            clear: both;
        }

        .label {
            width: 48%;
            float: left;
            text-align: center;
        }

        .label.right {
            float: right;
        }

        .qr img {
            width: 40px;
            height: 40px;
        }

        .name {
            font-size: 10px;
            font-weight: bold;
        }

        .weight {
            font-size: 9px;
        }

        .price {
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="container">

        @php
            $chunks = $items->chunk(2);
        @endphp

        @foreach($chunks as $row)
            <div class="row">

                <div class="label">
                    @if($row[0]->qr_base64)
                        <div class="qr">
                            <img src="data:image/png;base64,{{ $row[0]->qr_base64 }}">
                        </div>
                    @endif

                    <div class="name">
                        {{ $row[0]->manual_name ?? $row[0]->item?->name }}
                    </div>

                    <div class="weight">
                        {{ number_format($row[0]->weight, 2) }} gr.
                    </div>

                    <div class="price">
                        Rp {{ number_format($row[0]->item?->price_sell ?? 0, 0, ',', '.') }}
                    </div>
                </div>

                @if(isset($row[1]))
                    <div class="label right">
                        @if($row[1]->qr_base64)
                            <div class="qr">
                                <img src="data:image/png;base64,{{ $row[1]->qr_base64 }}">
                            </div>
                        @endif

                        <div class="name">
                            {{ $row[1]->manual_name ?? $row[1]->item?->name }}
                        </div>

                        <div class="weight">
                            {{ number_format($row[1]->weight, 2) }} gr.
                        </div>

                        <div class="price">
                            Rp {{ number_format($row[1]->item?->price_sell ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                @endif

            </div>
        @endforeach

    </div>

</body>

</html>