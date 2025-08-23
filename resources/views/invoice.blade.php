<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }

        .no-border {
            border: none;
        }

        .border-left {
            border-left: 1px solid #000 ;
        }

        .padding-left {
            padding-left: 20px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .px-8 {
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
        }
    </style>
</head>
<body>

<hr>
<table class="no-border px-8">
    <tr>
        <td class="no-border">
            <strong>No Invoice</strong><br>
            {{ $transaction->transaction_number }}
        </td>
        <td class="no-border border-left padding-left">
            <strong>Tanggal</strong><br>
            {{ \Carbon\Carbon::parse($transaction->date)->translatedFormat('d F Y') }}
        </td>
        <td class="no-border border-left padding-left ">
            <strong>Jatuh Tempo</strong><br>
            {{ \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y') }}
        </td>
        <td class="no-border title" colspan="2">
            Invoice Penjualan
        </td>
    </tr>
</table>
<hr>

<br>
<table class="table">
    <tr>
        <td class="no-border" style="width: 30%; vertical-align: top;">
            <strong>Outlet</strong><br>
            {{ $outlet->name ?? 'Toko' }}<br>
            {{ $outlet->address ?? 'Jalan' }}<br>
            {{ $outlet->email ?? 'admin@temantekno.com' }}<br>
            {{ $outlet->phone_number ?? '08XX XXXX XXXX' }}
        </td>

        <td class="no-border" style="width: 30%; vertical-align: top;">
            <strong>Pelanggan</strong><br>
            {{ $transaction->customer->name }}<br>
            {{ $transaction->customer->address }}<br>
            {{ $transaction->customer->email }}<br>
            {{ $transaction->customer->phone }}
        </td>

        <td style="width: 40%; vertical-align: middle; text-align: center;">
            <table>
                <tr style="font-size: 16px">
                    <td class="no-border" style="padding: 6px;"><strong>Total</strong></td>
                    <td class="no-border" style="padding: 6px; text-align: right;"><strong>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<br><br>

<table>
    <thead>
    <tr>
        <th>No</th>
        <th>SKU</th>
        <th>Deskripsi</th>
        <th>QTY</th>
        <th>Unit</th>
        <th>Harga</th>
        <th>Diskon %</th>
        <th>Diskon Rp</th>
        <th>Subtotal</th>
    </tr>
    </thead>
    <tbody>
    @foreach($transaction->items as $i => $item)
        <tr>
            <td class="text-center no-border">{{ $i + 1 }}</td>
            <td class="text-center no-border">{{ $item->product->units[0]->pivot->sku ?? '-' }}</td>
            <td class="text-center no-border">{{ $item->product->name ?? '-' }}</td>
            <td class="text-center no-border">{{ $item->quantity }}</td>
            <td class="text-center no-border">{{ $item->unit->name ?? '-' }}</td>
            <td class="text-center no-border">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
            <td class="text-center no-border">{{ $item->discount_percentage ?? '0' }}%</td>
            <td class="text-center no-border">Rp {{ number_format($item->discount_value ?? 0, 0, ',', '.') }}</td>
            <td class="text-center no-border">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br>

<table style="width: 40%; margin-left: auto;">
    <tr>
        <td class="no-border text-right"><strong>Subtotal</strong></td>
        <td class="no-border text-right">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="no-border text-right"><strong>Pajak</strong></td>
        <td class="no-border text-right">Rp {{ number_format($transaction->tax ?? 0, 0, ',', '.') }}</td>
    </tr>
</table>

<br>

<hr>
<table style="width: 50%; float: right;">
    <tr>
        <td><strong>GRAND TOTAL</strong></td>
        <td class="text-right">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td><strong>TOTAL UANG MUKA</strong></td>
        <td class="text-right">Rp {{ number_format($transaction->payments->sum('amount'), 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td><strong>SISA TAGIHAN</strong></td>
        <td class="text-right">
            Rp {{ number_format(abs($transaction->grand_total - $transaction->payments->sum('amount')), 0, ',', '.') }}
        </td>
    </tr>
</table>

</body>
</html>
