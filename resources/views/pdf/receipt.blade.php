<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Penjualan</title>

    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {

            padding: 6px;
            font-size: 11px;
        }

        th {
            background: #f3f3f3;
        }

        .bold { font-weight: bold; }

    </style>
</head>

<body>

{{-- ================= HEADER ================= --}}

<table style="width:100%; background: {{ $color }}; padding:10px 15px; border-collapse: collapse;">
    <tr>
        {{-- LEFT SIDE --}}
        <td style="width:70%; padding:10px; vertical-align:top;">
            <table style="border-collapse: collapse;">
                <tr>
                    @if($store->logo ?? false)
                        <td style="padding-right:12px; vertical-align:top;">
                            <img src="{{ asset('storage/' . $store->logo) }}" width="70">
                        </td>
                    @endif

                    <td style="vertical-align:top;">
                        <h1 style="margin:0; font-size:22px; font-weight:bold;">
                            {{ strtoupper($store->store_name) }}
                        </h1>

                        <p style="margin:3px 0; line-height:1.4; font-size:12px;">
                            JUAL BELI RUPA-RUPA PERHIASAN EMAS, TERIMA PESANAN <br>
                            {{ $store->address ?? '' }} <br>
                            HP/WA: {{ $store->phone }}
                            @if($store->instagram)
                                — Instagram: {{ $store->instagram }}
                            @endif
                        </p>
                    </td>
                </tr>
            </table>
        </td>

        {{-- RIGHT SIDE --}}
        <td style="width:30%; text-align:center; vertical-align:top; padding:10px;">

            <div style="font-size:12px; margin-bottom:4px;">
                {{ now()->format('d-m-Y') }} <br>
                {{ now()->format('H:i:s') }}
            </div>

            <img src="{{ public_path('storage/' . $sale->qr_path) }}"
                 width="85"
                 style="margin: 5px auto; display:block;">

            <b style="margin-top:4px; display:block;">Nota {{ $sale->invoice_no }}</b>
        </td>
    </tr>
</table>


{{-- ================= ITEM TABLE ================= --}}

<table style="border: 1px solid #ccc; border-collapse: collapse;">
    <thead>
    <tr>
        <th style="width:30px; text-align:center;">No</th>
        <th style="width:90px; text-align:center;">Foto</th>
        <th>Nama Barang</th>
        <th style="width:70px; text-align:center;">Berat</th>
        <th style="width:110px; text-align:center;">Subtotal</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($sale->items as $index => $item)
        <tr>
            <td style="text-align:center;">{{ $index + 1 }}</td>

            <td style="text-align:center;">
                @if ($item->manual_image)
                    <img src="{{ asset($item->manual_image) }}" width="70">
                @else
                    <img src="{{ asset($item->item->image) }}" width="70">
                @endif
            </td>

            <td>{{ $item->manual_name ?? $item->item->name }}</td>

            <td style="text-align:center;">
                {{ number_format($item->weight, 2, ',', '.') }} g
            </td>

            <td style="text-align:center; font-weight:bold;">
                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>


{{-- ================= FOOTER ================= --}}

<table style="width:100%; border-collapse: collapse;">
    <tr>

        {{-- PERHATIAN --}}
        <td style="width:70%; vertical-align:top; padding:12px; background:#FFF7C2; border:1px solid #E5D389;">
            <b style="font-size:12px;">PERHATIAN:</b><br><br>

            <div style="font-size:11px; line-height:1.4;">
                {!! nl2br($footer ?? '') !!}
            </div>
        </td>

        {{-- JUMLAH & KETERANGAN --}}
        <td style="width:30%; vertical-align:top; padding:12px; border:1px solid #ccc;">
            <table style="width:100%; border-collapse: collapse; font-size:12px;">
                <tr>
                    <td class="bold" style="padding:5px 0;">Jumlah:</td>
                    <td class="bold" style="text-align:right; padding:5px 0;">
                        Rp {{ number_format($sale->total_price, 0, ',', '.') }}
                    </td>
                </tr>

                @if ($sale->notes)
                    <tr>
                        <td class="bold" style="padding-top:8px;">Keterangan:</td>
                        <td style="text-align:right; padding-top:8px;">
                            {{ $sale->notes }}
                        </td>
                    </tr>
                @endif
            </table>
        </td>

    </tr>
</table>

</body>
</html>
