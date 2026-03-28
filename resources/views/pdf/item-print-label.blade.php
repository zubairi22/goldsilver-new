<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 5px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
        }

        .container {
            width: 400px;
            margin-left: 0;
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
            width: 40%;
            float: left;
            text-align: center;
        }

        .label.right {
            float: right;
        }

        .qr {
            margin-bottom: 5px;
        }

        .qr img {
            width: 35px;
            height: 35px;
        }

        .name {
            font-size: 8px;
        }

        .weight {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .price {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
        }
    </style>
</head>

<body>

    <div class="container">

        @php
            $chunks = $items->chunk(2)->map(fn($c) => $c->values());
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
                        {{ $row[0]->name }}
                    </div>

                    <div class="weight">
                        {{ number_format($row[0]->weight, 2) }} gr.
                    </div>

                    <div class="price">
                        Rp {{ number_format($row[0]->price_sell ?? 0, 0, ',', '.') }}
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
                            {{ $row[1]->name }}
                        </div>

                        <div class="weight">
                            {{ number_format($row[1]->weight, 2) }} gr.
                        </div>

                        <div class="price">
                            Rp {{ number_format($row[1]->price_sell ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                @endif

            </div>
        @endforeach

    </div>

</body>

</html>