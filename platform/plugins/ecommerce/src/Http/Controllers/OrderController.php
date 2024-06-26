<?php

namespace Botble\Ecommerce\Http\Controllers;

use Assets;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Enums\ShippingCodStatusEnum;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Enums\ShippingStatusEnum;
use Botble\Ecommerce\Events\OrderConfirmedEvent;
use Botble\Ecommerce\Http\Requests\AddressRequest;
use Botble\Ecommerce\Http\Requests\ApplyCouponRequest;
use Botble\Ecommerce\Http\Requests\CreateOrderRequest;
use Botble\Ecommerce\Http\Requests\CreateShipmentRequest;
use Botble\Ecommerce\Http\Requests\RefundRequest;
use Botble\Ecommerce\Http\Requests\UpdateOrderRequest;
use Botble\Ecommerce\Http\Resources\OrderAddressResource;
use Botble\Ecommerce\Repositories\Interfaces\AddressInterface;
use Botble\Ecommerce\Repositories\Interfaces\BillOrderInterface;
use Botble\Ecommerce\Repositories\Interfaces\CustomerInterface;
use Botble\Ecommerce\Repositories\Interfaces\OrderAddressInterface;
use Botble\Ecommerce\Repositories\Interfaces\OrderHistoryInterface;
use Botble\Ecommerce\Repositories\Interfaces\OrderInterface;
use Botble\Ecommerce\Repositories\Interfaces\OrderProductInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductInterface;
use Botble\Ecommerce\Repositories\Interfaces\ShipmentHistoryInterface;
use Botble\Ecommerce\Repositories\Interfaces\ShipmentInterface;
use Botble\Ecommerce\Repositories\Interfaces\StoreLocatorInterface;
use Botble\Ecommerce\Services\HandleApplyCouponService;
use Botble\Ecommerce\Services\HandleShippingFeeService;
use Botble\Ecommerce\Tables\OrderIncompleteTable;
use Botble\Ecommerce\Tables\OrderTable;
use Botble\Marketplace\Repositories\Interfaces\CommissionsInterface;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Carbon\Carbon;
use EcommerceHelper;
use EmailHandler;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use MarketplaceHelper;
use OrderHelper;
use RvMedia;
use Throwable;
use DB;

class OrderController extends BaseController
{
    /**
     * @var OrderInterface
     */
    protected $orderRepository;

    /**
     * @var CustomerInterface
     */
    protected $customerRepository;

    /**
     * @var OrderHistoryInterface
     */
    protected $orderHistoryRepository;

    /**
     * @var ProductInterface
     */
    protected $productRepository;

    /**
     * @var ShipmentInterface
     */
    protected $shipmentRepository;

    /**
     * @var OrderHistoryInterface
     */
    protected $orderAddressRepository;

    /**
     * @var PaymentInterface
     */
    protected $paymentRepository;

    /**
     * @var StoreLocatorInterface
     */
    protected $storeLocatorRepository;

    /**
     * @var OrderProductInterface
     */
    protected $orderProductRepository;

    /**
     * @var AddressInterface
     */
    protected $addressRepository;

    protected $billOrderRepository;

    protected $commissionRepo;

    /**
     * @param OrderInterface $orderRepository
     * @param CustomerInterface $customerRepository
     * @param OrderHistoryInterface $orderHistoryRepository
     * @param ProductInterface $productRepository
     * @param ShipmentInterface $shipmentRepository
     * @param OrderAddressInterface $orderAddressRepository
     * @param PaymentInterface $paymentRepository
     * @param StoreLocatorInterface $storeLocatorRepository
     * @param OrderProductInterface $orderProductRepository
     * @param AddressInterface $addressRepository
     */
    public function __construct(
        OrderInterface $orderRepository,
        CustomerInterface $customerRepository,
        OrderHistoryInterface $orderHistoryRepository,
        ProductInterface $productRepository,
        ShipmentInterface $shipmentRepository,
        OrderAddressInterface $orderAddressRepository,
        PaymentInterface $paymentRepository,
        StoreLocatorInterface $storeLocatorRepository,
        OrderProductInterface $orderProductRepository,
        AddressInterface $addressRepository,
        BillOrderInterface $billOrderRepository,
        CommissionsInterface $commissionRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->orderHistoryRepository = $orderHistoryRepository;
        $this->productRepository = $productRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->paymentRepository = $paymentRepository;
        $this->storeLocatorRepository = $storeLocatorRepository;
        $this->orderProductRepository = $orderProductRepository;
        $this->addressRepository = $addressRepository;
        $this->billOrderRepository = $billOrderRepository;
        $this->commissionRepo = $commissionRepository;
    }

    /**
     * @param OrderTable $dataTable
     * @return Factory|View
     * @throws Throwable
     */
    public function index(OrderTable $dataTable)
    {
        page_title()->setTitle(trans('plugins/ecommerce::order.menu'));

        return $dataTable->renderTable();
    }

    /**
     * @return Factory|View
     */
    public function create()
    {
        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/jquery.textarea_autosize.js',
                'vendor/core/plugins/ecommerce/js/order-create.js',
            ])
            ->addScripts(['blockui', 'input-mask']);

        if (EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()) {
            Assets::addScriptsDirectly('vendor/core/plugins/location/js/location.js');
        }

        page_title()->setTitle(trans('plugins/ecommerce::order.create'));

        return view('plugins/ecommerce::orders.create');
    }

    /**
     * @param CreateOrderRequest $request
     * @param BaseHttpResponse $response
     */
    public function store(CreateOrderRequest $request, BaseHttpResponse $response)
    {
        $request->merge([
            'amount'               => $request->input('amount') + $request->input('shipping_amount') - ($request->input('discount_amount') + $request->input('ubgxu_checkout')),
            'user_id'              => $request->input('customer_id') ?? 0,
            'shipping_method'      => $request->input('shipping_method', ShippingMethodEnum::DEFAULT),
            'shipping_option'      => $request->input('shipping_option'),
            'shipping_amount'      => $request->input('shipping_amount'),
            'tax_amount'           => session('tax_amount', 0),
            'sub_total'            => $request->input('amount'),
            'coupon_code'          => $request->input('coupon_code'),
            'discount_amount'      => $request->input('discount_amount'),
            'discount_description' => $request->input('discount_description'),
            'description'          => $request->input('note'),
            'is_confirmed'         => 1,
            'status'               => OrderStatusEnum::PROCESSING,
        ]);

        $storeIds = [];
        $totalTax = 0;
        foreach ($request->input('products', []) as $productItem) {
            $product = $this->productRepository->findById(Arr::get($productItem, 'id'));
            if (!$product) {
                continue;
            }

            if (is_plugin_active('marketplace') && $product->original_product->store_id && $product->original_product->store->id) {
                $storeIds[] = $product->original_product->store_id;
            }

//            if (count(array_unique($storeIds)) > 1) {
//                return $response
//                    ->setError()
//                    ->setMessage(trans('plugins/marketplace::order.products_are_from_different_vendors'));
//            }

            if ($product->with_storehouse_management && $product->quantity < Arr::get($productItem, 'quantity', 1)) {
                return $response
                    ->setError()
                    ->setMessage(trans('plugins/ecommerce::order.one_or_more_products_dont_have_enough_quantity'));
            }
           $totalTax = $totalTax+$product->getTaxAmountbyPrice();
        }

        $order = $this->orderRepository->createOrUpdate($request->input());
        $order->tax_amount = $totalTax;
        $order->save();

        if ($order) {
            //nếu sử dụng điểm để thanh toán
            if($request->input('ubgxu_checked')=='on'){
                $ubgxucheckout = $request->input('ubgxu_checkout');

                $customerInfor = $this->customerRepository->findById($request->input('customer_id'));
                $paidUbgXuAmount = 0;
                if($customerInfor->ubgxu>=$request->input('amount')){
                    $paidUbgXuAmount = $ubgxucheckout;
                }else{
                    $paidUbgXuAmount = $customerInfor->ubgxu;
                }
                $paidUbgXuAmount =  round($paidUbgXuAmount,0,PHP_ROUND_HALF_DOWN);
                $order->paid_by_ubgxu = $paidUbgXuAmount;
                $order->save();
                DB::beginTransaction();
                try{
                    //Trừ xu trong tài khoản
                    if($customerInfor->ubgxu>0) {
                        DB::table('ec_customers')->where('id', $request->input('customer_id'))->decrement('ubgxu', $paidUbgXuAmount);
                        //Ghi log giao dịch xu
                        DB::table('ubgxu_pay_log')->insert([
                            'content' => 'Bạn vừa sử dụng '.number_format($paidUbgXuAmount).' xu cho việc thanh toán đơn hàng số '.get_order_code($order->id),
                            'customer_id' => $request->input('customer_id'),
                            'created_at' => Carbon::now()
                        ]);
                        //Tạo giao dịch xu
                        DB::table('ubgxu_transaction')->insert([
                            'customer_id' => $request->input('customer_id'),
                            'amount' => $paidUbgXuAmount,
                            'description' => 'Bạn vừa sử dụng '.number_format($paidUbgXuAmount).' xu cho việc thanh toán đơn hàng số '.get_order_code($order->id),
                            'transaction_type' => 'decrease',
                            'transaction_source' => 'https://ezlife.vn',
                            'total_day_refund' => 0,
                            'rest_cashback_amount' => 0,
                            'compare_code' => get_order_code($order->id),
                            'created_at' => Carbon::now(),
                            'status' => 'completed'
                        ]);
                    }
                }catch (Exception $exception){
                    DB::rollBack();
                }

                DB::commit();
            }

            $this->orderHistoryRepository->createOrUpdate([
                'action'      => 'create_order_from_payment_page',
                'description' => trans('plugins/ecommerce::order.create_order_from_payment_page'),
                'order_id'    => $order->id,
            ]);

            $this->orderHistoryRepository->createOrUpdate([
                'action'      => 'create_order',
                'description' => trans('plugins/ecommerce::order.new_order',
                    ['order_id' => get_order_code($order->id)]),
                'order_id'    => $order->id,
            ]);

            $this->orderHistoryRepository->createOrUpdate([
                'action'      => 'confirm_order',
                'description' => trans('plugins/ecommerce::order.order_was_verified_by'),
                'order_id'    => $order->id,
                'user_id'     => Auth::id(),
            ]);

            $payment = $this->paymentRepository->createOrUpdate([
                'amount'          => $order->amount,
                'currency'        => cms_currency()->getDefaultCurrency()->title,
                'payment_channel' => $request->input('payment_method'),
                'status'          => $request->input('payment_status', PaymentStatusEnum::PENDING),
                'payment_type'    => 'confirm',
                'order_id'        => $order->id,
                'charge_id'       => Str::upper(Str::random(10)),
                'user_id'         => Auth::id(),
            ]);

            $order->payment_id = $payment->id;

            $order->save();

            if ($request->input('payment_status') === PaymentStatusEnum::COMPLETED) {
                $this->orderHistoryRepository->createOrUpdate([
                    'action'      => 'confirm_payment',
                    'description' => trans('plugins/ecommerce::order.payment_was_confirmed_by', [
                        'money' => format_price($order->amount),
                    ]),
                    'order_id'    => $order->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            if ($request->input('customer_address.name')) {
                $this->orderAddressRepository->create([
                    'name'     => $request->input('customer_address.name'),
                    'phone'    => $request->input('customer_address.phone'),
                    'email'    => $request->input('customer_address.email'),
                    'state'    => $request->input('customer_address.state'),
                    'city'     => $request->input('customer_address.city'),
                    'zip_code' => $request->input('customer_address.zip_code'),
                    'country'  => $request->input('customer_address.country'),
                    'address'  => $request->input('customer_address.address'),
                    'order_id' => $order->id,
                ]);
            } elseif ($request->input('customer_id')) {
                $customer = $this->customerRepository->findById($request->input('customer_id'));
                $this->orderAddressRepository->create([
                    'name'     => $customer->name,
                    'phone'    => $customer->phone,
                    'email'    => $customer->email,
                    'order_id' => $order->id,
                ]);
            }

            foreach ($request->input('products', []) as $productItem) {
                $product = $this->productRepository->findById(Arr::get($productItem, 'id'));
                if (!$product) {
                    continue;
                }

                $data = [
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->original_product->name,
                    'qty'          => Arr::get($productItem, 'quantity', 1),
                    'weight'       => $product->weight * Arr::get($productItem, 'quantity', 1),
                    'price'        => $product->front_sale_price,
                    'tax_amount'   => $product->getTaxAmountbyPrice(),
                    'options'      => [],
                ];

                $this->orderProductRepository->create($data);

                $ids = [$product->id];
                if ($product->is_variation && $product->original_product) {
                    $ids[] = $product->original_product->id;
                }

                $this->productRepository
                    ->getModel()
                    ->whereIn('id', $ids)
                    ->where('with_storehouse_management', 1)
                    ->where('quantity', '>=', Arr::get($productItem, 'quantity', 1))
                    ->decrement('quantity', Arr::get($productItem, 'quantity', 1));

                //Tạo bản ghi hoa hồng cho người dùng
                try {
                    $level_1 = intval(get_ecommerce_setting('consumer_level_1'));
                    $level_2 = intval(get_ecommerce_setting('consumer_level_2'));
                    $level_3 = intval(get_ecommerce_setting('consumer_level_3'));
                    $agency = intval(get_ecommerce_setting('agency_level'));
                    $customerComission = $this->customerRepository->findById($request->input('customer_id'));
                    $affiliateIdd = 0;
                    if (!is_null($customerComission->presenter_id || $customerComission->presenter_id > 0)) {
                        $affiliateIdd = $customerComission->presenter_id;
                    }
                    //Số phần trăm mà nhà cung cấp chiết khấu cho sản phẩm
                    $productComissionPercent = $product->commission;
                    //Số tiền chiết khấu
                    $moneyComit = ($product->price * Arr::get($productItem, 'quantity', 1)) * ($productComissionPercent / 100);
                    //Chiết khấu cộng đồng người tiêu dùng bằng 60%
                    $moneyCommission = $moneyComit * (60 / 100);
                    $consumerAmount = $moneyCommission * ($level_1 / 100);
                    //cộng tiền 5% cho người tiêu dùng mua hàng
                    $comiss = [
                        'customer_id' => $request->input('customer_id'),
                        'customer_level' => 1,
                        'presenter_id' => $affiliateIdd,
                        'product_id' => $product->id,
                        'order_id' => $order->id,
                        'amount' => $product->price * Arr::get($productItem, 'quantity', 1),
                        'percentage' => $level_1,
                        'commission' => $consumerAmount,
                        'description' => 'Tiền hoa hồng cho người tiêu dùng',
                        'is_agency' => 0,
                        'status' => 'disable'
                    ];
                    $consumer = $this->commissionRepo->create($comiss);

                    if ($affiliateIdd != 0) {
                        $userLv2 = $this->customerRepository->getModel()->where('id', $affiliateIdd)->first();
                        //người giới thiệu lv2
                        $commissionAmountLv2 = $moneyCommission * ($level_2 / 100);
                        $comissAff = [
                            'customer_id' => $affiliateIdd,
                            'customer_level' => 2,
                            'presenter_id' => $userLv2->presenter_id,
                            'product_id' => $product->id,
                            'order_id' => $order->id,
                            'amount' => $product->price * Arr::get($productItem, 'quantity', 1),
                            'percentage' => $level_2,
                            'commission' => $commissionAmountLv2,
                            'description' => 'Tiền hoa hồng cho người giới thiệu thứ 2',
                            'is_agency' => 0,
                            'status' => 'disable'
                        ];
                        $this->commissionRepo->create($comissAff);

                        $affiliateId2 = 0;
                        //Người giới thiệu lv3
                        if (!is_null($userLv2->presenter_id) || $userLv2->presenter_id != 0) {
                            $affiliateId2 = $userLv2->presenter_id;
                            $userLv3 = $this->customerRepository->getModel()->where('id', $affiliateId2)->first();
                            $commissionAmountLv3 = $moneyCommission * ($level_3 / 100);
                            $commissLv3 = [
                                'customer_id' => $affiliateId2,
                                'customer_level' => 3,
                                'presenter_id' => 0,
                                'product_id' => $product->id,
                                'order_id' => $order->id,
                                'amount' => $product->price * Arr::get($productItem, 'quantity', 1),
                                'percentage' => $level_3,
                                'commission' => $commissionAmountLv3,
                                'description' => 'Tiền hoa hồng cho người giới thiệu vị trí 3',
                                'is_agency' => 0,
                                'status' => 'disable'
                            ];
                            $this->commissionRepo->create($commissLv3);
                            //nếu người giới thiệu lv3 là đại lý
                            if ($userLv3->is_affiliater == 1) {
                                $commissionAcency2 = $moneyCommission * ($agency / 100);
                                $comissDL2 = [
                                    'customer_id' => $userLv3->id,
                                    'customer_level' => 4,
                                    'presenter_id' => $userLv3->presenter_id,
                                    'product_id' => $product->id,
                                    'order_id' => $order->id,
                                    'amount' => $product->price * Arr::get($productItem, 'quantity', 1),
                                    'percentage' => $agency,
                                    'commission' => $commissionAcency2,
                                    'description' => 'Tiền hoa hồng cho tài khoản đại lý',
                                    'is_agency' => 1,
                                    'status' => 'disable'
                                ];
                                $daily2 = $this->commissionRepo->create($comissDL2);
                            }
                        }
                    }
                }catch (Exception $e){
                    return $response->setMessage($e->getMessage());
                }

            }

            if (is_plugin_active('marketplace') && count($storeIds) > 0) {
                $order->store_id = Arr::first($storeIds);
                $order->save();

                $this->shipmentRepository->createOrUpdate([
                    'order_id'   => $order->id,
                    'user_id'    => 0,
                    'weight'     => $order->products_weight,
                    'cod_amount' => $order->payment->status != PaymentStatusEnum::COMPLETED ? $order->amount : 0,
                    'cod_status' => ShippingCodStatusEnum::PENDING,
                    'type'       => $order->shipping_method,
                    'status'     => ShippingStatusEnum::PENDING,
                    'price'      => $order->shipping_amount,
                    'store_id'   => $order->store_id,
                ]);

                MarketplaceHelper::sendMailToVendorAfterProcessingOrder($order);
            }
        }

        return $response
            ->setData($order)
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    /**
     * @param int $id
     * @return Factory|View
     */
    public function edit($id)
    {
        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/jquery.textarea_autosize.js',
                'vendor/core/plugins/ecommerce/js/order.js',
            ])
            ->addScripts(['blockui', 'input-mask']);

        if (EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()) {
            Assets::addScriptsDirectly('vendor/core/plugins/location/js/location.js');
        }

        $order = $this->orderRepository->findOrFail($id, ['products', 'user']);

        page_title()->setTitle(trans('plugins/ecommerce::order.edit_order', ['code' => get_order_code($id)]));

        $weight = $order->products_weight;

        $defaultStore = get_primary_store_locator();

        return view('plugins/ecommerce::orders.edit', compact('order', 'weight', 'defaultStore'));
    }

    /**
     * @param int $id
     * @param UpdateOrderRequest $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function update($id, UpdateOrderRequest $request, BaseHttpResponse $response)
    {
        $order = $this->orderRepository->findOrFail($id);
        $order->fill($request->input());

        $order = $this->orderRepository->createOrUpdate($order);

        event(new UpdatedContentEvent(ORDER_MODULE_SCREEN_NAME, $request, $order));

        return $response
            ->setPreviousUrl(route('orders.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    /**
     * @param int $id
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function destroy($id, Request $request, BaseHttpResponse $response)
    {
        $order = $this->orderRepository->findOrFail($id);

        try {
            $this->orderRepository->deleteBy(['id' => $id]);
            event(new DeletedContentEvent(ORDER_MODULE_SCREEN_NAME, $request, $order));
            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function deletes(Request $request, BaseHttpResponse $response)
    {
        $ids = $request->input('ids');
        if (empty($ids)) {
            return $response
                ->setError()
                ->setMessage(trans('core/base::notices.no_select'));
        }

        foreach ($ids as $id) {
            $order = $this->orderRepository->findOrFail($id);
            $this->orderRepository->delete($order);
            event(new DeletedContentEvent(ORDER_MODULE_SCREEN_NAME, $request, $order));
        }

        return $response->setMessage(trans('core/base::notices.delete_success_message'));
    }

    /**
     * @param int $orderId
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getGenerateInvoice($orderId, Request $request)
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if (!$order->isInvoiceAvailable()) {
            abort(404);
        }

        if ($request->input('type') == 'print') {
            return OrderHelper::streamInvoice($order);
        }

        return OrderHelper::downloadInvoice($order);
    }

    /**
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Throwable
     */
    public function postConfirm(Request $request, BaseHttpResponse $response)
    {
        $order = $this->orderRepository->findOrFail($request->input('order_id'));
        $order->is_confirmed = 1;
        if ($order->status == OrderStatusEnum::PENDING) {
            $order->status = OrderStatusEnum::PROCESSING;
        }

        $this->orderRepository->createOrUpdate($order);

        $this->orderHistoryRepository->createOrUpdate([
            'action'      => 'confirm_order',
            'description' => trans('plugins/ecommerce::order.order_was_verified_by'),
            'order_id'    => $order->id,
            'user_id'     => Auth::id(),
        ]);

        $payment = $this->paymentRepository->getFirstBy(['order_id' => $order->id]);

        if ($payment) {
            $payment->user_id = Auth::id();
            $payment->save();
        }

        event(new OrderConfirmedEvent($order, Auth::user()));

        $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);
        if ($mailer->templateEnabled('order_confirm')) {
            OrderHelper::setEmailVariables($order);
            $mailer->sendUsingTemplate(
                'order_confirm',
                $order->user->email ?: $order->address->email
            );
        }

        return $response->setMessage(trans('plugins/ecommerce::order.confirm_order_success'));
    }

    /**
     * @param int $id
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Throwable
     */
    public function postResendOrderConfirmationEmail($id, BaseHttpResponse $response)
    {
        $order = $this->orderRepository->findOrFail($id);
        $result = OrderHelper::sendOrderConfirmationEmail($order);

        if (!$result) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/ecommerce::order.error_when_sending_email'));
        }

        return $response->setMessage(trans('plugins/ecommerce::order.sent_confirmation_email_success'));
    }

    /**
     * @param int $orderId
     * @param HandleShippingFeeService $shippingFeeService
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse|Factory|View
     * @throws Throwable
     */
    public function getShipmentForm(
        $orderId,
        HandleShippingFeeService $shippingFeeService,
        Request $request,
        BaseHttpResponse $response
    ) {
        $order = $this->orderRepository->findOrFail($orderId);

        $weight = 0;
        if ($request->has('weight')) {
            $weight = $request->input('weight');
        } else {
            $weight = $order->products_weight;
        }

        $shippingData = [
            'address'     => $order->address->address,
            'country'     => $order->address->country,
            'state'       => $order->address->state,
            'city'        => $order->address->city,
            'weight'      => $weight,
            'order_total' => $order->amount,
        ];

        $shipping = $shippingFeeService->execute($shippingData);

        $storeLocators = $this->storeLocatorRepository->allBy(['is_shipping_location' => true]);

        $url = route('orders.create-shipment', $order->id);

        if ($request->has('view')) {
            return view('plugins/ecommerce::orders.shipment-form',
                compact('order', 'weight', 'shipping', 'storeLocators', 'url'));
        }

        return $response->setData(view('plugins/ecommerce::orders.shipment-form',
            compact('order', 'weight', 'shipping', 'storeLocators', 'url'))->render());
    }

    /**
     * @param int $id
     * @param CreateShipmentRequest $request
     * @param BaseHttpResponse $response
     * @param ShipmentHistoryInterface $shipmentHistoryRepository
     * @return BaseHttpResponse
     * @throws FileNotFoundException
     * @throws Throwable
     */
    public function postCreateShipment(
        $id,
        CreateShipmentRequest $request,
        BaseHttpResponse $response,
        ShipmentHistoryInterface $shipmentHistoryRepository
    ) {
        $order = $this->orderRepository->findOrFail($id);
        $result = $response;

        $shipment = [
            'order_id'   => $order->id,
            'user_id'    => Auth::id(),
            'weight'     => $order->products_weight,
            'note'       => $request->input('note'),
            'cod_amount' => $request->input('cod_amount') ?? ($order->payment->status != PaymentStatusEnum::COMPLETED ? $order->amount : 0),
            'cod_status' => 'pending',
            'type'       => $request->input('method'),
            'status'     => ShippingStatusEnum::DELIVERING,
            'price'      => $order->shipping_amount,
            'store_id'   => $request->input('store_id'),
        ];

        $store = $this->storeLocatorRepository->findById($request->input('store_id'));

        if (!$store) {
            $defaultStore = $this->storeLocatorRepository->getFirstBy(['is_primary' => true]);
            $shipment['store_id'] = $defaultStore ?? ($defaultStore ? $defaultStore->id : null);
        }

        switch ($request->input('method')) {
            default:
                $result = $result->setMessage(trans('plugins/ecommerce::order.order_was_sent_to_shipping_team'));
                break;
        }

        if (!$result->isError()) {
            $this->orderRepository->createOrUpdate([
                'status'          => OrderStatusEnum::PROCESSING,
                'shipping_method' => $request->input('method'),
                'shipping_option' => $request->input('option'),
            ], compact('id'));

            $shipment = $this->shipmentRepository->createOrUpdate($shipment);

            $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);
            if ($mailer->templateEnabled('customer_delivery_order')) {
                OrderHelper::setEmailVariables($order);
                $mailer->sendUsingTemplate(
                    'customer_delivery_order',
                    $order->user->email ?: $order->address->email
                );
            }

            $this->orderHistoryRepository->createOrUpdate([
                'action'      => 'create_shipment',
                'description' => $result->getMessage() . ' ' . trans('plugins/ecommerce::order.by_username'),
                'order_id'    => $id,
                'user_id'     => Auth::id(),
            ]);

            $shipmentHistoryRepository->createOrUpdate([
                'action'      => 'create_from_order',
                'description' => trans('plugins/ecommerce::order.shipping_was_created_from'),
                'shipment_id' => $shipment->id,
                'order_id'    => $id,
                'user_id'     => Auth::id(),
            ]);
        }

        return $result;
    }

    /**
     * @param int $id
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function postCancelShipment($id, BaseHttpResponse $response)
    {
        $shipment = $this->shipmentRepository->createOrUpdate(['status' => ShippingStatusEnum::CANCELED],
            compact('id'));

        $this->orderHistoryRepository->createOrUpdate([
            'action'      => 'cancel_shipment',
            'description' => trans('plugins/ecommerce::order.shipping_was_canceled_by'),
            'order_id'    => $shipment->order_id,
            'user_id'     => Auth::id(),
        ]);

        return $response
            ->setData([
                'status'      => ShippingStatusEnum::CANCELED,
                'status_text' => ShippingStatusEnum::CANCELED()->label(),
            ])
            ->setMessage(trans('plugins/ecommerce::order.shipping_was_canceled_success'));
    }

    /**
     * @param int $id
     * @param AddressRequest $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Throwable
     */
    public function postUpdateShippingAddress($id, AddressRequest $request, BaseHttpResponse $response)
    {
        $address = $this->orderAddressRepository->createOrUpdate($request->input(), compact('id'));

        if (!$address) {
            abort(404);
        }

        if ($address->order->status == OrderStatusEnum::CANCELED) {
            abort(401);
        }

        return $response
            ->setData([
                'line'   => view('plugins/ecommerce::orders.shipping-address.line', compact('address'))->render(),
                'detail' => view('plugins/ecommerce::orders.shipping-address.detail', compact('address'))->render(),
            ])
            ->setMessage(trans('plugins/ecommerce::order.update_shipping_address_success'));
    }

    /**
     * @param int $id
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Throwable
     */
    public function postCancelOrder($id, BaseHttpResponse $response)
    {
        $order = $this->orderRepository->findOrFail($id);

        if (!$order->canBeCanceledByAdmin()) {
            abort(403);
        }

        OrderHelper::cancelOrder($order);

        $this->orderHistoryRepository->createOrUpdate([
            'action'      => 'cancel_order',
            'description' => trans('plugins/ecommerce::order.order_was_canceled_by'),
            'order_id'    => $order->id,
            'user_id'     => Auth::id(),
        ]);

        // Hoàn xu khi hủy đơn hàng
        if ($order->paid_by_ubgxu > 0) {
            $customer = $this->customerRepository->findById($order->user_id);
            if ($customer != null) {

                DB::beginTransaction();
                try {
                    //Trả xu về tài khoản
                    DB::table('ec_customers')->where('id', $order->user_id)->increment('ubgxu', $order->paid_by_ubgxu);

                    //Ghi log giao dịch trả xu
                    DB::table('ubgxu_pay_log')->insert([
                        'content' => 'Bạn nhận được '.number_format($order->paid_by_ubgxu).' điểm qua việc hủy đơn hàng '.get_order_code($order->id),
                        'customer_id' => $order->user_id,
                        'created_at' => Carbon::now()
                    ]);

                    //Tạo giao dịch xu trả xu
                    DB::table('ubgxu_transaction')->insert([
                        'customer_id' => $order->user_id,
                        'amount' => $order->paid_by_ubgxu,
                        'description' => 'Bạn nhận được '.number_format($order->paid_by_ubgxu).' điểm qua việc hủy đơn hàng '.get_order_code($order->id),
                        'transaction_type' => 'increase',
                        'transaction_source' => 'https://ezlife.vn',
                        'total_day_refund' => 0,
                        'rest_cashback_amount' => 0,
                        'compare_code' => get_order_code($order->id),
                        'created_at' => Carbon::now(),
                        'status' => 'completed'
                    ]);
                } catch (Exception $e) {
                    DB::rollBack();
                }
                DB::commit();
            }
        }

        return $response->setMessage(trans('plugins/ecommerce::order.cancel_success'));
    }

    /**
     * @param int $id
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws FileNotFoundException
     * @throws Throwable
     */
    public function postConfirmPayment($id, BaseHttpResponse $response)
    {
        $order = $this->orderRepository->findOrFail($id, ['payment']);

        if ($order->status === OrderStatusEnum::PENDING) {
            $order->status = OrderStatusEnum::PROCESSING;
        }

        $this->orderRepository->createOrUpdate($order);

        OrderHelper::confirmPayment($order);

        return $response->setMessage(trans('plugins/ecommerce::order.confirm_payment_success'));
    }

    /**
     * @param int $id
     * @param RefundRequest $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function postRefund($id, RefundRequest $request, BaseHttpResponse $response)
    {
        $order = $this->orderRepository->findOrFail($id);
        if ($request->input('refund_amount') > ($order->payment->amount - $order->payment->refunded_amount)) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/ecommerce::order.refund_amount_invalid', [
                    'price' => format_price($order->payment->amount - $order->payment->refunded_amount,
                        get_application_currency()),
                ]));
        }

        foreach ($request->input('products', []) as $productId => $quantity) {
            $orderProduct = $this->orderProductRepository->getFirstBy([
                'product_id' => $productId,
                'order_id'   => $id,
            ]);
            if ($quantity > ($orderProduct->qty - $orderProduct->restock_quantity)) {
                $response
                    ->setError()
                    ->setMessage(trans('plugins/ecommerce::order.number_of_products_invalid'));
                break;
            }
        }

        $response = apply_filters(ACTION_BEFORE_POST_ORDER_REFUND_ECOMMERCE, $response, $order, $request);

        if ($response->isError()) {
            return $response;
        }

        $payment = $order->payment;
        if (!$payment) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/ecommerce::order.cannot_found_payment_for_this_order'));
        }

        $refundAmount = $request->input('refund_amount');
        if ($paymentService = get_payment_is_support_refund_online($payment)) {
            $paymentResponse = (new $paymentService);
            if (method_exists($paymentService, 'setCurrency')) {
                $paymentResponse = $paymentResponse->setCurrency($payment->currency);
            }
            $optionRefunds = [
                'refund_note' => $request->input('refund_note'),
                'order_id'    => $order->id,
            ];
            $paymentResponse = $paymentResponse->refundOrder($payment->charge_id, $refundAmount, $optionRefunds);
            if (Arr::get($paymentResponse, 'error', true)) {
                return $response
                    ->setError()
                    ->setMessage(Arr::get($paymentResponse, 'message', ''));
            }
            $refundData = (array)Arr::get($paymentResponse, 'data', []);
            $refundData['_data_request'] = $request->except(['_token']) + [
                    'currency'   => $payment->currency,
                    'created_at' => now(),
                ];
            $metadata = $payment->metadata;
            $refunds = Arr::get($metadata, 'refunds', []);
            $refunds[] = $refundData;
            Arr::set($metadata, 'refunds', $refunds);

            $payment->metadata = $metadata;
        }

        $payment->refunded_amount += $request->input('refund_amount');
        if ($payment->refunded_amount == $payment->amount) {
            $payment->status = PaymentStatusEnum::REFUNDED;
        }
        $payment->refund_note = $request->input('refund_note');
        $this->paymentRepository->createOrUpdate($payment);

        foreach ($request->input('products', []) as $productId => $quantity) {
            $product = $this->productRepository->findById($productId);
            if ($product && $product->with_storehouse_management) {
                $product->quantity += $quantity;
                $this->productRepository->createOrUpdate($product);
            }

            $orderProduct = $this->orderProductRepository->getFirstBy([
                'product_id' => $productId,
                'order_id'   => $id,
            ]);

            if ($orderProduct) {
                $orderProduct->restock_quantity += $quantity;
                $this->orderProductRepository->createOrUpdate($orderProduct);
            }
        }

        if ($request->input('refund_amount', 0) > 0) {
            $this->orderHistoryRepository->createOrUpdate([
                'action'      => 'refund',
                'description' => trans('plugins/ecommerce::order.refund_success_with_price', [
                    'price' => format_price($request->input('refund_amount')),
                ]),
                'order_id'    => $order->id,
                'user_id'     => Auth::id(),
                'extras'      => json_encode([
                    'amount' => $request->input('refund_amount'),
                    'method' => $payment->payment_channel ?? PaymentMethodEnum::COD,
                ]),
            ]);
        }

        $response->setMessage(trans('plugins/ecommerce::order.refund_success'));

        $response = apply_filters(ACTION_AFTER_POST_ORDER_REFUNDED_ECOMMERCE, $response, $order, $request);

        return $response;
    }

    /**
     * @param Request $request
     * @param BaseHttpResponse $response
     * @param HandleShippingFeeService $shippingFeeService
     * @return BaseHttpResponse
     */
    public function getAvailableShippingMethods(
        Request $request,
        BaseHttpResponse $response,
        HandleShippingFeeService $shippingFeeService
    ) {
        $weight = 0;
        $orderAmount = 0;

        foreach ($request->input('products', []) as $productId) {
            $product = $this->productRepository->findById($productId);
            if ($product) {
                $weight += $product->weight * $product->qty;
                $orderAmount += $product->front_sale_price;
            }
        }

        $weight = $weight > 0.1 ? $weight : 0.1;

        $shippingData = [
            'address'     => $request->input('address'),
            'country'     => $request->input('country'),
            'state'       => $request->input('state'),
            'city'        => $request->input('city'),
            'weight'      => $weight,
            'order_total' => $orderAmount,
        ];

        $shipping = $shippingFeeService->execute($shippingData);

        $result = [];
        foreach ($shipping as $key => $shippingItem) {
            foreach ($shippingItem as $subKey => $subShippingItem) {
                $result[$key . ';' . $subKey . ';' . $subShippingItem['price']] = [
                    'name'  => $subShippingItem['name'],
                    'price' => format_price($subShippingItem['price'], null, true),
                ];
            }
        }

        return $response->setData($result);
    }

    /**
     * @param ApplyCouponRequest $request
     * @param HandleApplyCouponService $handleApplyCouponService
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Throwable
     */
    public function postApplyCoupon(
        ApplyCouponRequest $request,
        HandleApplyCouponService $handleApplyCouponService,
        BaseHttpResponse $response
    ) {
        $result = $handleApplyCouponService->applyCouponWhenCreatingOrderFromAdmin($request);

        if ($result['error']) {
            return $response
                ->setError()
                ->withInput()
                ->setMessage($result['message']);
        }

        return $response
            ->setData(Arr::get($result, 'data', []))
            ->setMessage(trans('plugins/ecommerce::order.applied_coupon_success',
                ['code' => $request->input('coupon_code')]));
    }

    /**
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse|Factory|View
     */
    public function getReorder(Request $request, BaseHttpResponse $response)
    {
        if (!$request->input('order_id')) {
            return $response
                ->setError()
                ->setNextUrl(route('orders.index'))
                ->setMessage(trans('plugins/ecommerce::order.order_is_not_existed'));
        }

        page_title()->setTitle(trans('plugins/ecommerce::order.reorder'));

        $order = $this->orderRepository->findById($request->input('order_id'));

        if (!$order) {
            return $response
                ->setError()
                ->setNextUrl(route('orders.index'))
                ->setMessage(trans('plugins/ecommerce::order.order_is_not_existed'));
        }

        $productIds = $order->products->pluck('product_id')->all();

        $products = $this->productRepository
            ->getModel()
            ->whereIn('id', $productIds)
            ->get();

        foreach ($products as &$availableProduct) {
            $availableProduct->image_url = RvMedia::getImageUrl(Arr::first($availableProduct->images) ?? null, 'thumb',
                false, RvMedia::getDefaultImage());
            $availableProduct->price = $availableProduct->front_sale_price;
            $availableProduct->product_link = route('products.edit', $availableProduct->original_product->id);
            $availableProduct->select_qty = 1;
            $availableProduct->product_id = $availableProduct->id;

            if (is_plugin_active('marketplace') && $availableProduct->original_product->store_id && $availableProduct->original_product->store->name) {
                $availableProduct->product_name = $availableProduct->name . ' (' . $availableProduct->original_product->store->name . ')';
            }

            $orderProduct = $order->products->where('product_id', $availableProduct->id)->first();

            if ($orderProduct) {
                $availableProduct->select_qty = $orderProduct->qty;
            }

            $availableProduct->variations = $availableProduct->original_product->variations;

            foreach ($availableProduct->variations as &$variation) {
                $variation->price = $variation->product->front_sale_price;
                foreach ($variation->variationItems as &$variationItem) {
                    $variationItem->attribute_title = $variationItem->attribute->title;
                }
            }
        }

        $customer = null;
        $customerAddresses = [];
        $customerOrderNumbers = 0;
        if ($order->user_id) {
            $customer = $this->customerRepository->findById($order->user_id);
            $customer->avatar = (string)$customer->avatar_url;

            if ($customer) {
                $customerOrderNumbers = $customer->orders()->count();
            }

            $customerAddresses = OrderAddressResource::collection($customer->addresses);
        }

        $customerAddress = $order->address;

        $customerAddress->city_name = $order->address->city_name;
        $customerAddress->state_name = $order->address->state_name;
        $customerAddress->country_name = $order->address->country_name;

        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/jquery.textarea_autosize.js',
                'vendor/core/plugins/ecommerce/js/order-create.js',
            ])
            ->addScripts(['blockui', 'input-mask']);

        return view('plugins/ecommerce::orders.reorder', compact(
            'order',
            'products',
            'productIds',
            'customer',
            'customerAddresses',
            'customerAddress',
            'customerOrderNumbers'
        ));
    }

    /**
     * @param OrderIncompleteTable $dataTable
     * @return Factory|View
     * @throws Throwable
     */
    public function getIncompleteList(OrderIncompleteTable $dataTable)
    {
        page_title()->setTitle(trans('plugins/ecommerce::order.incomplete_order'));

        return $dataTable->renderTable();
    }

    /**
     * @param int $id
     * @return Factory|View
     */
    public function getViewIncompleteOrder($id)
    {
        page_title()->setTitle(trans('plugins/ecommerce::order.incomplete_order'));

        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/jquery.textarea_autosize.js',
                'vendor/core/plugins/ecommerce/js/order-incomplete.js',
            ]);

        $order = $this->orderRepository
            ->getModel()
            ->where('id', $id)
            ->where('is_finished', 0)
            ->with(['products', 'user'])
            ->firstOrFail();

        return view('plugins/ecommerce::orders.view-incomplete-order', compact('order'));
    }

    /**
     * @param int $id
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Throwable
     */
    public function postSendOrderRecoverEmail($id, BaseHttpResponse $response)
    {
        $order = $this->orderRepository->findOrFail($id);

        $email = $order->user->email ?: $order->address->email;

        if (!$email) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/ecommerce::order.error_when_sending_email'));
        }

        try {
            $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);

            $order->dont_show_order_info_in_product_list = true;
            OrderHelper::setEmailVariables($order);

            $mailer->sendUsingTemplate('order_recover', $email);

            return $response->setMessage(trans('plugins/ecommerce::order.sent_email_incomplete_order_success'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/ecommerce::order.error_when_sending_email'));
        }
    }
}
