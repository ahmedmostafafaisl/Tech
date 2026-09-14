{{-- resources/views/pdf/partials/header.blade.php --}}

<div id="page-header">
    <div class="header">
        <table class="header-layout">
            <tr>
                <td class="company-info">
                    {{ $invoice['company']['address'] }}<br>
                    {{ $invoice['company']['city'] }}<br>
                    {{ $invoice['company']['country'] }}<br>
                    {{ $labels['unified_no'] }}: {{ $invoice['company']['phone'] }}<br>
                    {{ $invoice['company']['email'] }}<br>
                    {{ $labels['tax_no'] }}: {{ $invoice['company']['vat_no'] }}
                </td>

                <td class="brand-block">
                    <div class="logo-slot">
                        <img src="{{ $logoSrc }}" alt="Logo">
                    </div>

                    <div class="company-name-ar">
                        {{ $invoice['company']['name_ar'] }}
                    </div>

                    <div class="company-name-en">
                        {{ $invoice['company']['name_en'] }}
                    </div>

                    <div class="company-tagline">
                        {{ $invoice['company']['tagline'] }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="invoice-title">
            {{ $labels['invoice_title'] }} - Simplified Tax Invoice
        </div>

        <div class="invoice-number">
            {{ $invoice['number'] }}
        </div>
    </div>
</div>
