<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;
use Botble\Base\Traits\EnumCastable;
use Botble\Ecommerce\Enums\OrderCashbackTypeEnum;
use Botble\Ecommerce\Enums\OrderProcessAffiliateEnums;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Enums\ShippingStatusEnum;
use Botble\Ecommerce\Repositories\Interfaces\ShipmentInterface;
use Botble\Payment\Models\Payment;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use EcommerceHelper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OrderHelper;

class Order extends BaseModel
{
    use EnumCastable;

    /**
     * @var string
     */
    protected $table = 'ec_orders';

    /**
     * @var array
     */
    protected $fillable = [
        'status',
        'user_id',
        'amount',
        'tax_amount',
        'shipping_method',
        'shipping_option',
        'shipping_amount',
        'description',
        'coupon_code',
        'discount_amount',
        'sub_total',
        'is_confirmed',
        'discount_description',
        'is_finished',
        'token',
        'affliate_user_id',
        'commit_no_refund',
        'process_affiliate',
        'order_type',
        'group_buy_product_id',
        'group_buy_order_id',
        'order_province',
        'order_district',
        'paid_by_ubgxu',
        'cashback_type',
        'order_resource'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'status'          => OrderStatusEnum::class,
        'shipping_method' => ShippingMethodEnum::class,
        'process_affiliate' => OrderProcessAffiliateEnums::class,
        'cashback_type' => OrderCashbackTypeEnum::class
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function (Order $order) {
            app(ShipmentInterface::class)->deleteBy(['order_id' => $order->id]);
            OrderHistory::where('order_id', $order->id)->delete();
            OrderProduct::where('order_id', $order->id)->delete();
            OrderAddress::where('order_id', $order->id)->delete();
            app(PaymentInterface::class)->deleteBy(['order_id' => $order->id]);
        });
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Customer::class, 'user_id', 'id')->withDefault();
    }

    /**
     * @return mixed
     */
    public function getUserNameAttribute()
    {
        return $this->user->name;
    }

    /**
     * @return HasOne
     */
    public function address()
    {
        return $this->hasOne(OrderAddress::class, 'order_id')->withDefault();
    }

    /**
     * @return string
     */
    public function getFullAddressAttribute()
    {
        return $this->address->address . ', ' . $this->address->city_name . ', ' . $this->address->state_name . ', ' . $this->address->country_name . (EcommerceHelper::isZipCodeEnabled() ? ', ' . $this->address->zip_code : '');
    }

    /**
     * @return HasMany
     */
    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id')->with(['product']);
    }

    /**
     * @return HasMany
     */
    public function histories()
    {
        return $this->hasMany(OrderHistory::class, 'order_id')->with(['user', 'order']);
    }

    /**
     * @return array|null|string
     */
    public function getShippingMethodNameAttribute()
    {
        return OrderHelper::getShippingMethod(
            $this->attributes['shipping_method'],
            $this->attributes['shipping_option']
        );
    }

    /**
     * @return HasOne
     */
    public function shipment()
    {
        return $this->hasOne(Shipment::class)->withDefault();
    }

    /**
     * @return BelongsTo
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id')->withDefault();
    }

    /**
     * @return bool
     */
    public function canBeCanceled()
    {
        if ($this->shipment && in_array($this->shipment->status, [ShippingStatusEnum::PICKED, ShippingStatusEnum::DELIVERED, ShippingStatusEnum::AUDITED])) {
            return false;
        }

        return in_array($this->status, [OrderStatusEnum::PENDING, OrderStatusEnum::PROCESSING]);
    }

    /**
     * @return bool
     */
    public function canBeCanceledByAdmin()
    {
        if ($this->shipment && in_array($this->shipment->status, [ShippingStatusEnum::DELIVERED, ShippingStatusEnum::AUDITED])) {
            return false;
        }

        return !in_array($this->status, [OrderStatusEnum::COMPLETED, OrderStatusEnum::CANCELED]);
    }

    /**
     * @return bool
     */
    public function getIsFreeShippingAttribute()
    {
        return $this->shipping_amount == 0 && $this->discount_amount == 0 && $this->coupon_code;
    }

    /**
     * @return string
     */
    public function getAmountFormatAttribute()
    {
        return format_price($this->amount);
    }

    /**
     * @return bool
     */
    public function getDiscountAmountFormatAttribute()
    {
        return format_price($this->shipping_amount);
    }

    /**
     * @return bool
     */
    public function isInvoiceAvailable(): bool
    {
        return !EcommerceHelper::disableOrderInvoiceUntilOrderConfirmed() || $this->is_confirmed;
    }

    /**
     * @return float|int
     */
    public function getProductsWeightAttribute()
    {
        $weight = 0;

        foreach ($this->products as $product) {
            if ($product && $product->weight) {
                $weight += $product->weight * $product->qty;
            }
        }

        $weight = $weight ?: 0.1;

        return $weight;
    }
}
