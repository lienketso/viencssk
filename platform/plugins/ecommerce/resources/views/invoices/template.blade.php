<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ trans('plugins/ecommerce::order.invoice_for_order') }} {{ get_order_code($order->id) }}</title>

    @if (get_ecommerce_setting('using_custom_font_for_invoice', 0) == 1 && get_ecommerce_setting('invoice_font_family'))
        <link href="https://fonts.googleapis.com/css?family={{ urlencode(get_ecommerce_setting('invoice_font_family')) }}:400,500,600,700,900&display=swap" rel="stylesheet">
    @endif
    <style>
        body {
            font-size: 15px;
            font-family: '{{ get_ecommerce_setting('using_custom_font_for_invoice', 0) == 1 ? get_ecommerce_setting('invoice_font_family', 'DejaVu Sans') : 'DejaVu Sans' }}', Arial, sans-serif !important;
        }

        table {
            border-collapse : collapse;
            width           : 100%
        }

        table tr td {
            padding : 0
        }

        table tr td:last-child {
            text-align : right
        }

        .bold, strong {
            font-weight : 700
        }

        .right {
            text-align : right
        }

        .large {
            font-size : 1.75em
        }

        .total {
            color       : #000;
            font-weight : 700
        }

        .logo-container {
            margin : 20px 0 50px
        }

        .invoice-info-container {
            font-size : .875em
        }

        .invoice-info-container td {
            padding : 4px 0
        }

        .line-items-container {
            font-size : .875em;
            margin    : 70px 0
        }

        .line-items-container th {
            border-bottom  : 1px solid #000;
            color          : #000;
            font-size      : .75em;
            padding        : 10px 0 15px;
            text-align     : left;
            text-transform : uppercase
        }

        .line-items-container th:last-child {
            text-align : right
        }

        .line-items-container td {
            padding : 10px 0
        }

        .line-items-container tbody tr:first-child td {
            padding-top : 25px
        }

        .line-items-container.has-bottom-border tbody tr:last-child td {
            border-bottom  : 2px solid #ddd;
            padding-bottom : 25px
        }
        .line-items-container th.heading-description {
            width : 150px
        }
        .line-items-container th.heading-quantity {
            width : 50px
        }

        .line-items-container th.heading-price {
            text-align : right;
            width      : 100px
        }

        .line-items-container th.heading-subtotal {
            width : 100px
        }

        .payment-info {
            font-size   : .875em;
            line-height : 1.5;
            width       : 38%
        }

        small {
            font-size : 80%
        }

        .stamp {
            border         : 1px solid #000;
            color          : #000;
            display        : inline-block;
            font-size      : 18px;
            font-weight    : 700;
            left           : 30%;
            line-height    : 1;
            opacity        : .5;
            padding        : .3rem .75rem;
            position       : fixed;
            text-transform : uppercase;
            top            : 40%;
            transform      : rotate(-14deg)
        }

        .is-failed {
            border-color : #d23;
            color        : #d23
        }

        .is-completed {
            border-color : #0a9928;
            color        : #0a9928
        }

        .rtl {
            direction   : rtl;
            font-family : Tahoma, Arial, sans-serif
        }

        .rtl table {
            text-align : right
        }

        .rtl .line-items-container th.heading-price, .rtl .line-items-container th:last-child, .rtl .right, .rtl table tr td:last-child {
            text-align : left
        }
    </style>
</head>
<body @if (BaseHelper::siteLanguageDirection() == 'rtl') dir="rtl" @endif>

@if (get_ecommerce_setting('enable_invoice_stamp', 1) == 1)
    @if ($order->status == \Botble\Ecommerce\Enums\OrderStatusEnum::CANCELED && trim($order->status->label()))
        <span class="stamp @if ($order->status == \Botble\Ecommerce\Enums\OrderStatusEnum::CANCELED) is-failed @endif">
            {{ $order->status->label() }}
        </span>

    @elseif (trim($order->payment->status->label()))
        <span class="stamp @if ($order->payment->status == \Botble\Payment\Enums\PaymentStatusEnum::COMPLETED) is-completed @else is-failed @endif">
            {{ $order->payment->status->label() }}
        </span>
    @endif
@endif

@php
    $logo = theme_option('logo_in_invoices') ?: theme_option('logo');
@endphp

<table class="invoice-info-container">
    <tr>
        <td>
            <div class="logo-container">
                @if ($logo)
                    <img src="{{ RvMedia::getImageUrl($logo) }}"
                         style="width:100%; max-width:150px;" alt="{{ theme_option('site_title') }}">
                @endif
            </div>
        </td>
        <td>
            <p><strong>{{ $order->created_at->format('d/m/Y') }}</strong></p>
            <p><strong>{{ trans('plugins/ecommerce::order.invoice') }}</strong> {{ get_order_code($order->id) }}</p>
        </td>
    </tr>
</table>

<table class="invoice-info-container">
    <tr>
        <td>
            <p>{{ get_ecommerce_setting('store_name') }}</p>
            <p>{{ get_ecommerce_setting('store_address') }}, {{ get_ecommerce_setting('store_city') }}, {{ get_ecommerce_setting('store_state') }}, {{ EcommerceHelper::getCountryNameById(get_ecommerce_setting('store_country')) }}</p>
            <p>{{ get_ecommerce_setting('store_phone') }}</p>
            @if (get_ecommerce_setting('store_vat_number'))
                <p>{{ trans('plugins/ecommerce::ecommerce.setting.vat_number') }}: {{ get_ecommerce_setting('store_vat_number') }}</p>
            @endif
        </td>
        <td>
            <p>{{ $order->address->name }}</p>
            <p>{{ $order->full_address }}</p>
            @if ($order->address->phone)
                <p>{{ $order->address->phone }}</p>
            @endif
        </td>
    </tr>
</table>

@if ($order->description)
    <table class="invoice-info-container">
        <tr style="text-align: left">
            <td style="text-align: left">
                <p>{{ trans('plugins/ecommerce::order.note') }}: {{ $order->description }}</p>
            </td>
        </tr>
    </table>
@endif

<table class="line-items-container">
    <thead>
    <tr>
        <th class="heading-description">Đơn hàng</th>
        <th class=""></th>
        <th class="heading-quantity">SL</th>
        <th class="heading-price"></th>
        <th class="heading-subtotal">Thành tiền</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($order->products as $orderProduct)
        @php
            $product = get_products([
                'condition' => [
                    'ec_products.status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED,
                    'ec_products.id'     => $orderProduct->product_id,
                ],
                'take'   => 1,
                'select' => [
                    'ec_products.id',
                    'ec_products.images',
                    'ec_products.name',
                    'ec_products.price',
                    'ec_products.sale_price',
                    'ec_products.sale_type',
                    'ec_products.start_date',
                    'ec_products.end_date',
                    'ec_products.sku',
                    'ec_products.is_variation',
                    'ec_products.tax_id',
                ],
            ]);
            $tax_rate = $product->tax->percentage;
            $tax_amount = $product->price * ($tax_rate/100);
        @endphp
        @if (!empty($product))
            <tr>
                <td colspan="5">
                    <p style="text-align:left">{{ $product->original_product->name }}</p>
                </td>

            </tr>
            <tr>
                <td>
                    {!! htmlentities(format_price($orderProduct->price+$orderProduct->tax_amount)) !!}
                </td>
                <td></td>
                <td colspan="2">
                    {{ $orderProduct->qty }}
                </td>

                <td class="bold">
                    @php
                        $priceWithTax = ($orderProduct->price+$orderProduct->tax_amount) * $orderProduct->qty;
                    @endphp
                    {!! htmlentities(format_price(ceil( $priceWithTax /100)*100)) !!}
                </td>
            </tr>
        @endif
    @endforeach

    <tr>
        <td colspan="4" class="right">
            Tổng tiền
        </td>
        <td class="bold">
            {!! htmlentities(format_price(ceil($order->sub_total/100)*100)) !!}
        </td>
    </tr>
    @if (EcommerceHelper::isTaxEnabled())
{{--        <tr>--}}
{{--            <td colspan="4" class="right">--}}
{{--                VAT--}}
{{--            </td>--}}
{{--            <td class="bold">--}}
{{--                {!! htmlentities(format_price($order->tax_amount)) !!}--}}
{{--            </td>--}}
{{--        </tr>--}}
    @endif
    <tr>
        <td colspan="4" class="right">
            Vận chuyển
        </td>
        <td class="bold">
            {!! htmlentities(format_price($order->shipping_amount)) !!}
        </td>
    </tr>
    <tr>
        <td colspan="4" class="right">
            Giảm giá
        </td>
        <td class="bold">
            {!! htmlentities(format_price($order->discount_amount)) !!}
        </td>
    </tr>
    </tbody>
</table>

<table class="line-items-container">
    <thead>
    <tr>
        <th>{{ trans('plugins/ecommerce::order.payment_info') }}</th>
        <th>{{ trans('plugins/ecommerce::order.total_amount') }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="payment-info">
            <div>
                {{ trans('plugins/ecommerce::order.payment_method') }}: <strong>{{ $order->payment->payment_channel->label() }}</strong>
            </div>
            <div>
                {{ trans('plugins/ecommerce::order.payment_status_label') }}: <strong>{{ $order->payment->status->label() }}</strong>
            </div>

            @if ($order->payment->payment_channel == \Botble\Payment\Enums\PaymentMethodEnum::BANK_TRANSFER && $order->payment->status == \Botble\Payment\Enums\PaymentStatusEnum::PENDING)
                <div>
                    {{ trans('plugins/ecommerce::order.payment_info') }}: <strong>{{ get_payment_setting('description', $order->payment->payment_channel) }}</strong>
                </div>
            @endif
        </td>
        <td class="large total">{!! htmlentities(format_price(ceil($order->amount/100)*100)) !!}</td>
    </tr>
    <tr>
        <td colspan="2">
            <img width="300"
                 src="https://img.vietqr.io/image/MBBANK-258868686868-print.png?amount={{$order->amount}}&addInfo=Thanh toán đơn hàng {{get_order_code($order->id)}}&accountName=CÔNG TY CP THƯƠNG MẠI ĐẦU TƯ VÀ CÔNG NGHỆ BCE"></td>
    </tr>
    </tbody>
</table>
</body>
</html>
