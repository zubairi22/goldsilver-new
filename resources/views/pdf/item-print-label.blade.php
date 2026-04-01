<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
        }

        /* TABLE UTAMA */
        table {
            width: 80mm;
            table-layout: fixed;
            border-collapse: collapse;
        }

        /* CELL LABEL */
        td {
            height: 22mm;
            vertical-align: top;
            text-align: center;
        }

        /* QR */
        .qr img {
            width: 28px;
            height: 28px;
            margin-bottom: 4px;
            margin-top: 5px;
        }

        /* TEXT */
        .name {
            font-size: 6px;
            line-height: 1.1;
        }

        .weight,
        .price {
            font-size: 6px;
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
                <colgroup>
                    <col style="width: 20mm;">
                    <col style="width: 40mm;">
                    <col style="width: 20mm;">
                </colgroup>
                <tr>
                    {{-- LABEL KIRI --}}
                    <td>
                        @if(isset($row[0]) && $row[0]->qr_base64)
                            <div class="qr">
                                <img src="data:image/png;base64,{{ $row[0]->qr_base64 }}">
                            </div>
                        @endif

                        @if(isset($row[0]))
                            <div class="name">{{ $row[0]->name }}</div>
                            <div class="weight">
                                {{ number_format($row[0]->weight, 2) }} gr.
                            </div>
                            <div class="price">
                                Rp {{ number_format($row[0]->price_sell ?? 0, 0, ',', '.') }}
                            </div>
                        @endif
                    </td>

                    <td></td>

                    {{-- LABEL KANAN --}}
                    <td>
                        @if(isset($row[1]) && $row[1]->qr_base64)
                            <div class="qr">
                                <img src="data:image/png;base64,{{ $row[1]->qr_base64 }}">
                            </div>
                        @endif

                        @if(isset($row[1]))
                            <div class="name">{{ $row[1]->name }}</div>
                            <div class="weight">
                                {{ number_format($row[1]->weight, 2) }} gr.
                            </div>
                            <div class="price">
                                Rp {{ number_format($row[1]->price_sell ?? 0, 0, ',', '.') }}
                            </div>
                        @endif
                    </td>

                </tr>
            </table>

        </div>
    @endforeach

</body>

</html>