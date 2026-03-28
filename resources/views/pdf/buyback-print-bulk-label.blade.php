<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
        }

        html,
        body {
            width: 80mm;
            height: 24mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
        }

        .page {
            width: 80mm;
            height: 24mm;
            page-break-after: always;
        }

        table {
            width: 80mm;
            height: 24mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            width: 32mm;
            /* 32 + 32 + (7+7 padding) = 80mm */
            height: 24mm;
            text-align: center;
            vertical-align: middle;
        }

        /* 🔥 spacing tengah */
        td:first-child {
            padding-right: 7mm;
        }

        td:last-child {
            padding-left: 7mm;
        }

        .qr img {
            width: 28px;
            height: 28px;
            margin-bottom: 2px;
        }

        .name {
            font-size: 7px;
            line-height: 1.1;
        }

        .weight,
        .price {
            font-size: 7px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    @php
        $chunks = $items->chunk(2)->map(fn($c) => $c->values());
    @endphp

    @foreach($chunks as $row)
        <div class="page">

            <table>
                <tr>
                    {{-- LABEL KIRI --}}
                    <td>
                        @if(isset($row[0]) && $row[0]->qr_base64)
                            <div class="qr">
                                <img src="data:image/png;base64,{{ $row[0]->qr_base64 }}">
                            </div>
                        @endif

                        @if(isset($row[0]))
                            <div class="name">
                                {{ $row[0]->manual_name ?? $row[0]->item?->name }}
                            </div>
                            <div class="weight">
                                {{ number_format($row[0]->weight, 2) }} gr.
                            </div>
                            <div class="price">
                                Rp {{ number_format($row[0]->item?->price_sell ?? 0, 0, ',', '.') }}
                            </div>
                        @endif
                    </td>

                    {{-- LABEL KANAN --}}
                    <td>
                        @if(isset($row[1]) && $row[1]->qr_base64)
                            <div class="qr">
                                <img src="data:image/png;base64,{{ $row[1]->qr_base64 }}">
                            </div>
                        @endif

                        @if(isset($row[1]))
                            <div class="name">
                                {{ $row[1]->manual_name ?? $row[1]->item?->name }}
                            </div>
                            <div class="weight">
                                {{ number_format($row[1]->weight, 2) }} gr.
                            </div>
                            <div class="price">
                                Rp {{ number_format($row[1]->item?->price_sell ?? 0, 0, ',', '.') }}
                            </div>
                        @endif
                    </td>

                </tr>
            </table>

        </div>
    @endforeach

</body>

</html>