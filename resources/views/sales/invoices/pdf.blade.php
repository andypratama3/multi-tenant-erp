<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
    .header { text-align: center; margin-bottom: 24px; }
    .company-name { font-size: 20px; font-weight: bold; color: #1a1a2e; }
    .company-meta { font-size: 10px; color: #666; margin-top: 3px; }
    .doc-title { font-size: 16px; color: #555; margin-top: 4px; }
    .meta-table { width: 100%; margin-bottom: 20px; border: none; }
    .meta-table td { border: none; padding: 3px 8px; vertical-align: top; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.items th, table.items td { border: 1px solid #ddd; padding: 6px 8px; }
    table.items th { background-color: #f0f4ff; text-align: left; }
    .text-right { text-align: right; }
    .totals { width: 280px; float: right; margin-top: 16px; }
    .totals table { width: 100%; border-collapse: collapse; }
    .totals td { padding: 4px 8px; }
    .totals .grand-total { font-weight: bold; border-top: 2px solid #333; }
    .footer { clear: both; margin-top: 50px; text-align: center; font-size: 10px; color: #888; }
    .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
</style>
</head>
<body>

<div class="header">
    <div class="company-name">{{ config('company.name') }}</div>
    <div class="company-meta">
        {{ config('company.address') }}
        @if(config('company.phone')) &middot; Telp: {{ config('company.phone') }} @endif
        @if(config('company.email')) &middot; {{ config('company.email') }} @endif
        @if(config('company.tax_id')) &middot; NPWP: {{ config('company.tax_id') }} @endif
    </div>
    <div class="doc-title">FAKTUR / INVOICE</div>
</div>

<table class="meta-table">
    <tr>
        <td style="width:55%">
            <strong>Kepada Yth:</strong><br>
            <strong>{{ $invoice->customer->name }}</strong><br>
            @if($invoice->customer->address)
                {{ $invoice->customer->address }}<br>
            @endif
            @if($invoice->customer->phone)
                Telp: {{ $invoice->customer->phone }}<br>
            @endif
            @if($invoice->customer->tax_id)
                NPWP: {{ $invoice->customer->tax_id }}
            @endif
        </td>
        <td style="width:45%; text-align:right">
            <table style="float:right; border:none;">
                <tr><td style="border:none; padding:3px;"><strong>No. Invoice</strong></td><td style="border:none; padding:3px;">: {{ $invoice->invoice_number }}</td></tr>
                <tr><td style="border:none; padding:3px;"><strong>Tanggal</strong></td><td style="border:none; padding:3px;">: {{ \Carbon\Carbon::parse($invoice->issued_at)->translatedFormat('d F Y') }}</td></tr>
                <tr><td style="border:none; padding:3px;"><strong>Jatuh Tempo</strong></td><td style="border:none; padding:3px;">: {{ \Carbon\Carbon::parse($invoice->due_at)->translatedFormat('d F Y') }}</td></tr>
                <tr><td style="border:none; padding:3px;"><strong>Status</strong></td><td style="border:none; padding:3px;">: {{ strtoupper($invoice->status) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width:5%">No</th>
            <th>Produk / Deskripsi</th>
            <th class="text-right" style="width:10%">Qty</th>
            <th class="text-right" style="width:16%">Harga Satuan</th>
            <th class="text-right" style="width:8%">Diskon</th>
            <th class="text-right" style="width:8%">Pajak</th>
            <th class="text-right" style="width:16%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->product->name }}</td>
            <td class="text-right">{{ number_format((float)$item->quantity, 2) }}</td>
            <td class="text-right">{{ \App\Support\Money::formatIdr($item->unit_price) }}</td>
            <td class="text-right">{{ $item->discount_rate }}%</td>
            <td class="text-right">{{ $item->tax_rate }}%</td>
            <td class="text-right">{{ \App\Support\Money::formatIdr($item->subtotal) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <table>
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ \App\Support\Money::formatIdr($invoice->subtotal) }}</td>
        </tr>
        @if((float)$invoice->discount_amount > 0)
        <tr>
            <td>Diskon</td>
            <td class="text-right">- {{ \App\Support\Money::formatIdr($invoice->discount_amount) }}</td>
        </tr>
        @endif
        <tr>
            <td>Pajak (PPN)</td>
            <td class="text-right">{{ \App\Support\Money::formatIdr($invoice->tax_amount) }}</td>
        </tr>
        <tr class="grand-total">
            <td><strong>TOTAL</strong></td>
            <td class="text-right"><strong>{{ \App\Support\Money::formatIdr($invoice->total) }}</strong></td>
        </tr>
        @if((float)$invoice->paid_amount > 0)
        <tr>
            <td>Dibayar</td>
            <td class="text-right">{{ \App\Support\Money::formatIdr($invoice->paid_amount) }}</td>
        </tr>
        <tr>
            <td>Sisa</td>
            <td class="text-right">{{ \App\Support\Money::formatIdr(\App\Support\Money::sub($invoice->total, $invoice->paid_amount)) }}</td>
        </tr>
        @endif
    </table>
</div>

<div style="clear:both"></div>

@if(config('company.bank_name') || config('company.bank_account'))
<div style="margin-top:18px; padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0;">
    <strong>Pembayaran melalui transfer:</strong><br>
    {{ config('company.bank_name') }}
    @if(config('company.bank_account')) &middot; No. Rek: {{ config('company.bank_account') }} @endif
    @if(config('company.bank_holder')) &middot; a.n. {{ config('company.bank_holder') }} @endif
</div>
@endif

<div style="margin-top:40px; display:flex; justify-content:space-between;">
    <div style="text-align:center; width:200px;">
        <div style="margin-bottom:50px;">Hormat kami,</div>
        <div style="border-top:1px solid #333; padding-top:4px;">{{ config('company.name') }}</div>
    </div>
    <div style="text-align:center; width:200px;">
        <div style="margin-bottom:50px;">Diterima oleh,</div>
        <div style="border-top:1px solid #333; padding-top:4px;">{{ $invoice->customer->name }}</div>
    </div>
</div>

<div class="footer">
    Terima kasih atas kepercayaan Anda &mdash; {{ config('company.name') }}
</div>
</body>
</html>
