<?php

namespace Botble\Ecommerce\Supports;

use BaseHelper;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Supports\Helper;
use Botble\Ecommerce\Enums\CollaboratorRequestEnum;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Repositories\Interfaces\CustomerInterface;
use Botble\Ecommerce\Repositories\Interfaces\DistrictInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProvinceInterface;
use Botble\Ecommerce\Repositories\Interfaces\ReviewInterface;
use Botble\Ecommerce\Repositories\Interfaces\WardInterface;
use Botble\Location\Repositories\Interfaces\CityInterface;
use Botble\Location\Repositories\Interfaces\CountryInterface;
use Botble\Location\Repositories\Interfaces\StateInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EcommerceHelper
{
    /**
     * @return bool
     */
    public function isCartEnabled(): bool
    {
        return get_ecommerce_setting('shopping_cart_enabled', 1) == 1;
    }

    /**
     * @return bool
     */
    public function isWishlistEnabled(): bool
    {
        return get_ecommerce_setting('wishlist_enabled', 1) == 1;
    }

    /**
     * @return bool
     */
    public function isCompareEnabled(): bool
    {
        return get_ecommerce_setting('compare_enabled', 1) == 1;
    }

    /**
     * @return bool
     */
    public function isReviewEnabled(): bool
    {
        return get_ecommerce_setting('review_enabled', 1) == 1;
    }

    /**
     * @return bool
     */
    public function isOrderTrackingEnabled(): bool
    {
        return get_ecommerce_setting('order_tracking_enabled', 1) == 1;
    }

    /**
     * @param bool $isConvertToKB
     * @return int
     */
    public function reviewMaxFileSize($isConvertToKB = false): int
    {
        $size = (int) get_ecommerce_setting('review_max_file_size', 2);

        if (!$size) {
            $size = 2;
        } elseif ($size > 1024) {
            $size = 1024;
        }

        return $isConvertToKB ? $size * 1024 : $size;
    }

    /**
     * @return int
     */
    public function reviewMaxFileNumber(): int
    {
        $number = (int) get_ecommerce_setting('review_max_file_number', 6);

        if (!$number) {
            $number = 1;
        } elseif ($number > 100) {
            $number = 100;
        }

        return $number;
    }

    /**
     * @param int $productId
     * @param int $reviewsCount
     * @return Collection
     */
    public function getReviewsGroupedByProductId($productId, $reviewsCount = 0): Collection
    {
        if ($reviewsCount) {
            $reviews = app(ReviewInterface::class)->getGroupedByProductId($productId);
        } else {
            $reviews = collect([]);
        }

        $results = collect([]);
        for ($i = 5; $i >= 1; $i--) {
            if ($reviewsCount) {
                $review = $reviews->firstWhere('star', $i);
                $starCount = $review ? $review->star_count : 0;
                if ($starCount > 0) {
                    $starCount = $starCount / $reviewsCount * 100;
                }
            } else {
                $starCount = 0;
            }

            $results[] = [
                'star'    => $i,
                'count'   => $starCount,
                'percent' => ((int) ($starCount * 100)) / 100,
            ];
        }

        return $results;
    }

    /**
     * @return bool
     */
    public function isQuickBuyButtonEnabled(): bool
    {
        return get_ecommerce_setting('enable_quick_buy_button', 1) == 1;
    }

    /**
     * @return string
     */
    public function getQuickBuyButtonTarget(): string
    {
        return get_ecommerce_setting('quick_buy_target_page', 'checkout');
    }

    /**
     * @return bool
     */
    public function isZipCodeEnabled(): bool
    {
        return get_ecommerce_setting('zip_code_enabled', '0') == 1;
    }

    /**
     * @return bool
     */
    public function isDisplayProductIncludingTaxes(): bool
    {
        if (!$this->isTaxEnabled()) {
            return false;
        }

        return get_ecommerce_setting('display_product_price_including_taxes', '1') == 1;
    }

    /**
     * @return bool
     */
    public function isTaxEnabled(): bool
    {
        return get_ecommerce_setting('ecommerce_tax_enabled', 1) == 1;
    }

    /**
     * @return array
     */
    public function getAvailableCountries(): array
    {
        $countries = ['' => __('Select country...')];

        if ($this->loadCountriesStatesCitiesFromPluginLocation()) {
            $selectedCountries = app(CountryInterface::class)
                ->getModel()
                ->where('status', BaseStatusEnum::PUBLISHED)
                ->orderBy('is_default', 'DESC')
                ->pluck('name', 'id')
                ->all();

            if (!empty($selectedCountries)) {
                return $countries + $selectedCountries;
            }
        }

        try {
            $selectedCountries = json_decode(get_ecommerce_setting('available_countries'), true);
        } catch (Exception $exception) {
            $selectedCountries = [];
        }

        if (empty($selectedCountries)) {
            return $countries + Helper::countries();
        }

        foreach (Helper::countries() as $key => $item) {
            if (in_array($key, $selectedCountries)) {
                $countries[$key] = $item;
            }
        }

        return $countries;
    }

    /**
     * @param int $countryId
     * @return array
     */
    public function getAvailableStatesByCountry($countryId): array
    {
        if (!$this->loadCountriesStatesCitiesFromPluginLocation()) {
            return [];
        }

        $condition = [
            'status' => BaseStatusEnum::PUBLISHED,
        ];

        if ($this->isUsingInMultipleCountries()) {
            $condition['country_id'] = $countryId;
        }

        return app(StateInterface::class)
            ->getModel()
            ->where($condition)
            ->orderBy('is_default', 'DESC')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param int $stateId
     * @return array
     */
    public function getAvailableCitiesByState($stateId): array
    {
        if (!$this->loadCountriesStatesCitiesFromPluginLocation()) {
            return [];
        }

        return app(CityInterface::class)
            ->getModel()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->where('state_id', $stateId)
            ->orderBy('is_default', 'DESC')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array
     */
    public function getSortParams(): array
    {
        $sort = [
            'default_sorting' => __('Default'),
            'date_asc'        => __('Oldest'),
            'date_desc'       => __('Newest'),
            'price_asc'       => __('Price: low to high'),
            'price_desc'      => __('Price: high to low'),
            'name_asc'        => __('Name: A-Z'),
            'name_desc'       => __('Name : Z-A'),
        ];

        if ($this->isReviewEnabled()) {
            $sort += [
                'rating_asc'      => __('Rating: low to high'),
                'rating_desc'     => __('Rating: high to low'),
            ];
        }

        return $sort;
    }

    /**
     * @return array
     */
    public function getShowParams(): array
    {
        return [
            12 => 12,
            24 => 24,
            36 => 36,
        ];
    }

    /**
     * @return float
     */
    public function getMinimumOrderAmount()
    {
        return get_ecommerce_setting('minimum_order_amount', 0);
    }

    /**
     * @return bool
     */
    public function isEnabledGuestCheckout(): bool
    {
        return get_ecommerce_setting('enable_guest_checkout', 1) == 1;
    }

    /**
     * @return bool
     */
    public function showNumberOfProductsInProductSingle(): bool
    {
        return get_ecommerce_setting('show_number_of_products', 1) == 1;
    }

    /**
     * @return bool
     */
    public function showOutOfStockProducts(): bool
    {
        return get_ecommerce_setting('show_out_of_stock_products', 1) == 1;
    }

    /**
     * @return array
     */
    public function getDateRangeInReport(Request $request)
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        if ($request->input('date_from')) {
            try {
                $startDate = now()->createFromFormat('Y-m-d', $request->input('date_from'));
            } catch (Exception $ex) {
            }

            if (!$startDate) {
                $startDate = now()->subDays(29);
            }
        }

        if ($request->input('date_to')) {
            try {
                $endDate = now()->createFromFormat('Y-m-d', $request->input('date_to'));
            } catch (Exception $ex) {
            }

            if (!$endDate) {
                $endDate = now();
            }
        }

        if ($endDate->gt(now())) {
            $endDate = now();
        }

        if ($startDate->gt($endDate)) {
            $startDate = now()->subDays(29);
        }

        $predefinedRange = $request->input('predefined_range', trans('plugins/ecommerce::reports.ranges.last_30_days'));

        return [$startDate, $endDate, $predefinedRange];
    }

    /**
     * @return string
     */
    public function getSettingPrefix(): ?string
    {
        return config('plugins.ecommerce.general.prefix');
    }

    /**
     * @return bool
     */
    public function isPhoneFieldOptionalAtCheckout(): bool
    {
        return get_ecommerce_setting('make_phone_field_at_the_checkout_optional', 0) == 1;
    }

    /**
     * @return bool
     */
    public function isEnableEmailVerification(): bool
    {
        return get_ecommerce_setting('verify_customer_email', 0) == 1;
    }

    /**
     * @return bool
     */
    public function disableOrderInvoiceUntilOrderConfirmed(): bool
    {
        return get_ecommerce_setting('disable_order_invoice_until_order_confirmed', 0) == 1;
    }

    /**
     * @return string
     */
    public function getPhoneValidationRule(): string
    {
        $rule = BaseHelper::getPhoneValidationRule();
        if (EcommerceHelper::isPhoneFieldOptionalAtCheckout()) {
            return 'nullable|' . $rule;
        }

        return 'required|' . $rule;
    }

    /**
     * @param Product $product
     * @param int $star
     * @param int $perPage
     * @return Collection
     */
    public function getProductReviews(Product $product, int $star = 0, int $perPage = 10)
    {
        $condition = [
            'ec_reviews.status' => BaseStatusEnum::PUBLISHED,
        ];

        if ($star && $star >= 1 && $star <= 5) {
            $condition['ec_reviews.star'] = $star;
        }

        $ids = [$product->id];
        if ($product->variations->count()) {
            $ids = array_merge($ids, $product->variations->pluck('product_id')->toArray());
        }

        $reviews = app(ReviewInterface::class)
            ->getModel()
            ->select(['ec_reviews.*', 'ec_orders.created_at as order_created_at'])
            ->where($condition);

        if ($product->variations->count()) {
            $reviews
                ->whereHas('product.variations', function ($query) use ($ids) {
                    $query->whereIn('ec_product_variations.product_id', $ids);
                });
        } else {
            $reviews->where('ec_reviews.product_id', $product->id);
        }

        $reviews = $reviews
            ->leftJoin('ec_orders', function ($join) use ($ids) {
                $join
                    ->on('ec_orders.user_id', 'ec_reviews.customer_id')
                    ->where('ec_orders.status', OrderStatusEnum::COMPLETED)
                    ->join('ec_order_product', function ($join) use ($ids) {
                        $join
                            ->on('ec_order_product.order_id', 'ec_orders.id')
                            ->whereIn('ec_order_product.product_id', $ids);
                    });
            })
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->onEachSide(1)
            ->appends(['star' => $star]);

        return $reviews;
    }

    /**
     * @return string
     */
    public function getThousandSeparatorForInputMask(): string
    {
        return ',';
    }

    /**
     * @return string
     */
    public function getDecimalSeparatorForInputMask(): string
    {
        return '.';
    }

    /**
     * @return array
     */
    public function withReviewsCount(): array
    {
        $withCount = [];
        if (EcommerceHelper::isReviewEnabled()) {
            $withCount = [
                'reviews',
                'reviews as reviews_avg' => function ($query) {
                    $query->select(DB::raw('avg(star)'));
                },
            ];
        }

        return $withCount;
    }

    /**
     * @return bool
     */
    public function loadCountriesStatesCitiesFromPluginLocation(): bool
    {
        if (!is_plugin_active('location')) {
            return false;
        }

        return get_ecommerce_setting('load_countries_states_cities_from_location_plugin', 0) == 1;
    }

    /**
     * @return string|null
     */
    public function getCountryNameById($countryId): ?string
    {
        if (!$countryId) {
            return null;
        }

        if (EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()) {
            $countryName = app(CountryInterface::class)
                ->getModel()
                ->where('id', $countryId)
                ->value('name');

            if (!empty($countryName)) {
                return $countryName;
            }
        }

        return Helper::getCountryNameByCode($countryId);
    }

    /**
     * @return array
     */
    public function getStates(?string $countryCode): array
    {
        if (!$countryCode || !$this->loadCountriesStatesCitiesFromPluginLocation()) {
            return [];
        }

        return app(StateInterface::class)
            ->getModel()
            ->whereHas('country', function ($query) use ($countryCode) {
                return $query->where('code', $countryCode);
            })
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param int $stateId
     * @return array
     */
    public function getCities(int $stateId): array
    {
        if (!$stateId || !$this->loadCountriesStatesCitiesFromPluginLocation()) {
            return [];
        }

        return app(StateInterface::class)
            ->getModel()
            ->where('state_id', $stateId)
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return bool
     */
    public function isUsingInMultipleCountries(): bool
    {
        return count($this->getAvailableCountries()) > 2;
    }

    /**
     * @return string|int
     */
    public function getFirstCountryId()
    {
        return Arr::first(array_filter(array_keys($this->getAvailableCountries())));
    }

    /**
     * @return array
     */
    public function getAvailableProvinces() : array
    {
        $avaiableProvince = app(ProvinceInterface::class)->all();

        $provinces = [];

        foreach ($avaiableProvince as $key => $item) {
            $provinces[$item->id] = $item->name;
        }

        return $provinces;
    }

    /**
     * @param $provinceId
     * @return array
     */
    public function getAvailableDistrict($provinceId) : array
    {
        $avaiableDistrict = app(DistrictInterface::class)->allBy([
            'province_id' => $provinceId
        ]);

        $districts = [];

        foreach ($avaiableDistrict as $key => $item) {
            $districts[$item->id] = $item->name;
        }

        return $districts;
    }

    /**
     * @param $districtId
     * @return array
     */
    public function getAvaiableWard($districtId) : array
    {
        $avaiableWards = app(WardInterface::class)->allBy([
            'district_id' => $districtId
        ]);

        $wards = [];

        foreach ($avaiableWards as $key => $item) {
            $wards[$item->id] = $item->name;
        }

        return $wards;
    }

    /**
     * @return bool
     */
    public function isAffiliater($id = null)
    {
        if ($id == null) {
            $customer = auth('customer')->user();
        } else {
            $customer = app(CustomerInterface::class)->findById($id);
        }

        return $customer->is_affiliater == 1 && $customer->affiliater_status->getValue() == 2;
    }

    /**
     * @return string
     */
    public function generateAffiliateLink()
    {
        $customer = auth('customer')->user();
        return route('public.affiliate_register', ['aff_id' => base64_encode($customer->affiliation_id)]);
    }
}
