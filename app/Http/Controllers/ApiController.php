<?php

namespace App\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Districts;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderAddress;
use Botble\Ecommerce\Models\OrderProduct;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Province;
use Botble\Ecommerce\Models\Ward;
use Botble\Ecommerce\Repositories\Interfaces\AddressInterface;
use Botble\Ecommerce\Repositories\Interfaces\CustomerInterface;
use Botble\Ecommerce\Repositories\Interfaces\OrderInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductInterface;
use Botble\Location\Http\Resources\CityResource;
use Botble\Location\Http\Resources\StateResource;
use Botble\Location\Models\City;
use Botble\Location\Models\State;
use Botble\Location\Repositories\Eloquent\CityRepository;
use Botble\Location\Repositories\Eloquent\StateRepository;
use Botble\Marketplace\Models\Commissions;
use Botble\Marketplace\Repositories\Interfaces\CommissionsInterface;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiController extends BaseController
{
    protected $commisionRepo;
    protected $customerRepository;
    protected $productRepository;
    protected $paymentRepository;
    protected $orderRepository;
    protected $customerAddressRepository;

    public function __construct(CommissionsInterface $commisionRepo, CustomerInterface $customerRepository,
                                ProductInterface $productRepository, PaymentInterface $paymentRepository,
                                OrderInterface $orderRepository, AddressInterface $address)
    {
        $this->commisionRepo = $commisionRepo;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
        $this->customerAddressRepository = $address;
    }

    public function demoApi(){
       return 'aaaa';
    }

    public function postCheckOutMini(Request $request, OrderProduct $orderProductRepository, OrderInterface $orderRepository){
        $phone = $request->phone;
        $name = $request->post('name','User Mini App');
        $email= $request->post('email','');
        $state = $request->post('state','');
        $city = $request->post('city','') ;
        $address = $request->post('address','');
        $amount = $request->post('amount',0);
        $subtotal = $request->post('sub_total','');
        $shipping_method = $request->post('shipping_method',ShippingMethodEnum::DEFAULT);
        $shipping_amount = $request->post('shipping_amount','');
        $payment_method = $request->post('payment_method','');

        $productItem = $request->products;
        $productItemJson = json_encode($productItem);
        $listproduct = json_decode($productItemJson);

        $validator = \Validator::make($request->all(), [
            'phone' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 500);
        }
    try {
        $user_id = 0;
        $userInfor = $this->customerRepository->getFirstBy(['phone' => $phone]);

        if (is_null($userInfor) || empty($userInfor)) {
            $users = [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'password' => Hash::make('ezlife.vn'),
            ];
            $userBooking = $this->customerRepository->create($users);
        } else {
            $userBooking = $userInfor;
        }

        $level_1 = intval(get_ecommerce_setting('consumer_level_1'));
        $level_2 = intval(get_ecommerce_setting('consumer_level_2'));
        $level_3 = intval(get_ecommerce_setting('consumer_level_3'));
        $agency = intval(get_ecommerce_setting('agency_level'));

        $presenterLv1 = 0;
        if ($userBooking) {
            $user_id = $userBooking->id;
            $presenterLv1 = $userBooking->presenter_id;
            //Xử lý xu
            $paidWithUbgXu = $request->ubg_xu_checkout == 'on';
            $paidUbgXuAmount = 0;

            if ($paidWithUbgXu) {
                $ubgxu = $userBooking->ubgxu;
                if ($ubgxu >= $amount) {
                    $paidUbgXuAmount = $amount;
                    $amount = 0;
                } else {
                    $amount -= $ubgxu;
                    $paidUbgXuAmount = $ubgxu;
                }
            }
        }
        $token = md5(Str::random(40));
        $input = [
            'user_id' => $user_id,
            'amount' => $amount,
            'sub_total' => $subtotal,
            'token' => $token,
            'status' => 'pending',
            'order_resource' => 'Zalo App',
            'shipping_method' => $shipping_method,
            'shipping_amount' => $shipping_amount,
            'paid_by_ubgxu' => $paidUbgXuAmount,
        ];
        //create order
        $orderCreate = $orderRepository->createOrUpdate($input);
        $totalTax = 0;
        $storeIds = [];
        //create order product
        foreach ($listproduct as $p) {
            $product = Product::find($p->id);
            $data = [
                'order_id' => $orderCreate->id,
                'product_id' => $p->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'qty' => $p->qty,
                'tax_amount'=>$product->getTaxAmountbyPrice()
            ];
            $createProductOrder = $orderProductRepository->create($data);
            if ($product->original_product->store_id && $product->original_product->store->id) {
                $storeIds[] = $product->original_product->store_id;
            }
            //trừ số lượng sản phẩm trong kho
            $this->productRepository
                ->getModel()
                ->where([
                    'id' => $p->id,
                    'with_storehouse_management' => 1,
                ])
                ->where('quantity', '>=', $p->qty)
                ->decrement('quantity', $p->qty);

            if ($userBooking) {
                //create commission for customer
                //Số phần trăm mà nhà cung cấp chiết khấu cho sản phẩm
                $productComissionPercent = $product->commission;
                //Số tiền chiết khấu
                $moneyComit = ($product->price * $p->qty) * ($productComissionPercent / 100);
                //Chiết khấu cộng đồng người tiêu dùng bằng 60%
                $moneyCommission = $moneyComit * (60 / 100);
                $commissionAmount = $moneyCommission * ($level_1 / 100);
                //người tiêu dùng
                $comiss = [
                    'customer_id' => $user_id,
                    'customer_level' => 1,
                    'presenter_id' => $presenterLv1,
                    'product_id' => $p->id,
                    'order_id' => $orderCreate->id,
                    'amount' => $product->price * $p->qty,
                    'percentage' => $level_1,
                    'commission' => $commissionAmount,
                    'description' => 'Tiền hoa hồng cho người tiêu dùng',
                    'is_agency' => 0,
                    'status' => 'disable'
                ];
                $consumer = $this->commisionRepo->create($comiss);
                if (!is_null($presenterLv1) || $presenterLv1 != 0) {
                    $userLv2 = $this->customerRepository->getModel()->where('id', $presenterLv1)->first();
                    //người giới thiệu lv2
                    if (!is_null($userLv2)) {
                        $commissionAmountLv2 = $moneyCommission * ($level_2 / 100);
                        $comissAff = [
                            'customer_id' => $userLv2->id,
                            'customer_level' => 2,
                            'presenter_id' => $userLv2->presenter_id,
                            'product_id' => $p->id,
                            'order_id' => $orderCreate->id,
                            'amount' => $product->price * $p->qty,
                            'percentage' => $level_2,
                            'commission' => $commissionAmountLv2,
                            'description' => 'Tiền hoa hồng cho người giới thiệu vị trí 2',
                            'is_agency' => 0,
                            'status' => 'disable'
                        ];
                        $this->commisionRepo->create($comissAff);
                        //nếu có lv3
                        if ($userLv2->presenter_id != 0 || !is_null($userLv2->presenter_id)) {
                            $affiliateId2 = $userLv2->presenter_id;
                            $userLv3 = $this->customerRepository->getModel()->where('id', $affiliateId2)->first();
                            $commissionAmountLv3 = $moneyCommission * ($level_3 / 100);
                            $commissLv3 = [
                                'customer_id' => $affiliateId2,
                                'customer_level' => 3,
                                'presenter_id' => 0,
                                'product_id' => $p->id,
                                'order_id' => $orderCreate->id,
                                'amount' => $product->price * $p->qty,
                                'percentage' => $level_3,
                                'commission' => $commissionAmountLv3,
                                'description' => 'Tiền hoa hồng cho người giới thiệu vị trí 3',
                                'is_agency' => 0,
                                'status' => 'disable'
                            ];
                            $this->commisionRepo->create($commissLv3);
                            //nếu người giới thiệu lv3 là đại lý
                            if ($userLv3->is_affiliater == 1) {
                                $commissionAcency2 = $moneyCommission * ($agency / 100);
                                $comissDL2 = [
                                    'customer_id' => $userLv3->id,
                                    'customer_level' => 4,
                                    'presenter_id' => $userLv3->presenter_id,
                                    'product_id' => $p->id,
                                    'order_id' => $orderCreate->id,
                                    'amount' => $product->price * $p->qty,
                                    'percentage' => $agency,
                                    'commission' => $commissionAcency2,
                                    'description' => 'Tiền hoa hồng cho tài khoản đại lý',
                                    'is_agency' => 1,
                                    'status' => 'disable'
                                ];
                                $daily2 = $this->commisionRepo->create($comissDL2);
                            }
                        }
                    }
                }
            }

            $totalTax = $totalTax + $product->getTaxAmountbyPrice();

        }

        //create payment
        $chargeId = Str::upper(Str::random(10));
        switch ($request->input('payment_method')) {
            case PaymentMethodEnum::COD:
                $minimumOrderAmount = setting('payment_cod_minimum_amount', 0);
                if ($minimumOrderAmount > $amount) {
                    $paymentData['error'] = true;
                    $paymentData['message'] = __('Minimum order amount to use COD (Cash On Delivery) payment method is :amount, you need to buy more :more to place an order!', ['amount' => format_price($minimumOrderAmount), 'more' => format_price($minimumOrderAmount - Cart::instance('cart')->rawSubTotal())]);
                    break;
                }
                $payment_channel = PaymentMethodEnum::COD;
                break;

            case PaymentMethodEnum::BANK_TRANSFER:
                $payment_channel = PaymentMethodEnum::BANK_TRANSFER;
                break;
            case PaymentMethodEnum::VNPAY:
                $payment = app(PaymentInterface::class)->getFirstBy(['order_id' => $orderCreate->id]);
                if ($payment) {
                    $paymentData['charge_id'] = $payment->charge_id;
                }
                $payment_channel = PaymentMethodEnum::VNPAY;
                break;
            default:
                $payment_channel = PaymentMethodEnum::COD;
                break;
        }
        $payment = $this->paymentRepository->create([
            'amount' => $amount,
            'currency' => 'VND',
            'charge_id' => $chargeId,
            'order_id' => $orderCreate->id,
            'customer_id' => $userBooking->id,
            'payment_channel' => $payment_channel,
            'status' => 'pending',
        ]);
        $orderCreate->payment_id = $payment->id;
        $orderCreate->tax_amount = $totalTax;
        if (count($storeIds) > 0) {
            $orderCreate->store_id = Arr::first($storeIds);
        }
        $orderCreate->save();

        //create order address
        $addressData = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'order_id' => $orderCreate->id,
            'country' => 'VN',
            'state' => $state,
            'city' => $city,
            'address' => $address
        ];
        OrderAddress::create($addressData);
        $responseData = [
            'order' => $orderCreate,
            'status' => 200,
            'error'=>'no',
            'message' => 'Success full create order !'
        ];

        if ($paidWithUbgXu) {
            DB::beginTransaction();
            try {
                //Trừ xu trong tài khoản
                DB::table('ec_customers')->where('id', $userBooking->id)->decrement('ubgxu', $paidUbgXuAmount);

                //Ghi log giao dịch xu
                DB::table('ubgxu_pay_log')->insert([
                    'content' => 'Bạn vừa sử dụng ' . number_format($paidUbgXuAmount) . ' điểm cho việc thanh toán đơn hàng số ' . get_order_code($orderCreate->id),
                    'customer_id' => $userBooking->id,
                    'created_at' => Carbon::now()
                ]);

                //Tạo giao dịch xu
                DB::table('ubgxu_transaction')->insert([
                    'customer_id' => $userBooking->id,
                    'amount' => $paidUbgXuAmount,
                    'description' => 'Bạn vừa sử dụng ' . number_format($paidUbgXuAmount) . ' điểm cho việc thanh toán đơn hàng số ' . get_order_code($orderCreate->id),
                    'transaction_type' => 'decrease',
                    'transaction_source' => 'https://ezlife.vn',
                    'total_day_refund' => 0,
                    'rest_cashback_amount' => 0,
                    'compare_code' => get_order_code($orderCreate->id),
                    'created_at' => Carbon::now(),
                    'status' => 'completed'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
            }
            DB::commit();
        }

        $response = response()->json($responseData);
        return $response;
    }catch (\Exception $e){
            $responseData = [
                'order' => [],
                'status' => 500,
                'error'=>'yes',
                'message' => $e->getMessage()
            ];
        $response = response()->json($responseData);
        return $response;
    }

    }

    //customer info
    public function getCustomerByPhone(Request $request){
        $data = [];
        $phone = $request->phone;
        $customer = $this->customerRepository->getModel()->with(['addresses'])->where('phone',$phone)->first();
//        if(is_null($customer)){
//            $data['errors'] = 'Không tồn tại user này !';
//            return response()->json($data);
//        }
        return response()->json($customer);
    }

    public function searchProduct(Request $request){
        $name = $request->name;
        $products = Product::orderBy('name','asc')
            ->where('name','LIKE','%'.$name.'%')->get()->map(function ($product){
                $product->image = asset('https://ezlife.vn/storage/' . $product->image);
                return $product;
            });
        return response()->json($products);
    }

    public function getAllProductAndVariations(Request $request, BaseHttpResponse $response){
        $availableProducts = $this->productRepository
            ->getModel()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->where('is_variation', '<>', 1)
            ->where('name', 'LIKE', '%' . $request->input('keyword') . '%')
            ->select([
                'ec_products.*',
            ])
            ->distinct('ec_products.id')
            ->leftJoin('ec_product_variations', 'ec_product_variations.configurable_product_id', '=', 'ec_products.id')
            ->leftJoin('ec_product_variation_items', 'ec_product_variation_items.variation_id', '=',
                'ec_product_variations.id')
            ->simplePaginate(5);

        foreach ($availableProducts as &$availableProduct) {
            /**
             * @var Product $availableProduct
             */
            $tax_rate = $availableProduct->tax->percentage;
            $tax_amount = $availableProduct->price * ($tax_rate/100);
            $availableProduct->image_url = \RvMedia::getImageUrl(Arr::first($availableProduct->images) ?? null, 'thumb',
                false, \RvMedia::getDefaultImage());
            $availableProduct->price = $availableProduct->front_sale_price+$tax_amount;
            $availableProduct->product_link = route('products.edit', $availableProduct->original_product->id);
            $availableProduct->is_out_of_stock = $availableProduct->isOutOfStock();

            if (is_plugin_active('marketplace') && $availableProduct->store_id && $availableProduct->store->name) {
                $availableProduct->name = $availableProduct->name . ' (' . $availableProduct->store->name . ')';
            }

            foreach ($availableProduct->variations as &$variation) {
                $variation->price = $variation->product->front_sale_price;
                $variation->is_out_of_stock = $variation->product->isOutOfStock();
                $variation->quantity = $variation->product->quantity;
                foreach ($variation->variationItems as &$variationItem) {
                    $variationItem->attribute_title = $variationItem->attribute->title;
                }
            }
        }

        return $response->setData($availableProducts);
    }

    public function getListCustomerForSearch(Request $request, BaseHttpResponse $response)
    {
        $customers = $this->customerRepository
            ->getModel()
            ->where('phone', 'LIKE', '%' . $request->input('keyword') . '%')
            ->simplePaginate(5);

        foreach ($customers as &$customer) {
            $customer->avatar_url = (string)$customer->avatar_url;
        }

        return $response->setData($customers);
    }

    //get list country
    public function getListCountry(BaseHttpResponse $response)
    {
        return $response->setData(\EcommerceHelper::getAvailableCountries());
    }
    public function ajaxGetStates(Request $request, BaseHttpResponse $response, StateRepository $stateRepository)
    {
        $params = [
            'select'    => ['states.id', 'states.name'],
            'condition' => [
                'states.status' => BaseStatusEnum::PUBLISHED,
            ],
            'order_by'  => ['order' => 'ASC', 'created_at' => 'DESC'],
        ];

        if ($request->input('country_id') && $request->input('country_id') != 'null') {
            $params['condition']['states.country_id'] = $request->input('country_id');
        }

        $data = $stateRepository->advancedGet($params);

        $data->prepend(new State(['id' => 0, 'name' => trans('plugins/location::city.select_state')]));

        return $response->setData(StateResource::collection($data));
    }

    public function ajaxGetCities(Request $request, BaseHttpResponse $response, CityRepository $cityRepository)
    {
        $params = [
            'select' => ['cities.id', 'cities.name'],
            'condition' => [
                'cities.status' => BaseStatusEnum::PUBLISHED,
            ],
            'order_by'  => ['order' => 'ASC', 'created_at' => 'DESC'],
        ];

        if ($request->input('state_id') && $request->input('state_id') != 'null') {
            $params['condition']['cities.state_id'] = $request->input('state_id');
        }

        $data = $cityRepository->advancedGet($params);

        $data->prepend(new City(['id' => 0, 'name' => trans('plugins/location::city.select_city')]));

        return $response->setData(CityResource::collection($data));
    }

    public function getCustomerOrderNumbers($id, BaseHttpResponse $response)
    {
        $customer = $this->customerRepository->findById($id);
        if (!$customer) {
            return $response->setData(0);
        }

        return $response->setData($customer->orders()->count());
    }
    //get list order by phone
    public function getListOrderByPhone(Request $request){
        $userPhone = $this->customerRepository->getModel()->where('phone',$request->phone)->first();
        $response = [];
        if(!$userPhone){
            $response['status'] = 404;
            $response['error'] = 'yes';
            $response['orders'] = [];
            $response['message'] = 'Không tồn tại user nào với số điện thoại này';
        }else{
            $query = Order::query();
            if(!is_null($request->status)){
                $query->where('status',$request->status); //completed / pending / processing / completed
            }
            $data = $query->where('user_id',$userPhone->id)->paginate(10);
            $response['status'] = 200;
            $response['error'] = 'no';
            $response['orders'] = $data;
            $response['message'] = 'Success';
        }

        return response()->json($response);
    }
    //Chi tiết đơn hàng
    public function getOrderInfo(Request $request){
        $id = $request->id;
        $orderInfor = $this->orderRepository->getFirstBy(['id'=>$id]);
        if(!$orderInfor){
            $response['status'] = 404;
            $response['error'] = 'yes';
            $response['orders'] = [];
            $response['message'] = 'Không có đơn hàng nào với id này !';
        }else{
            $response['status'] = 200;
            $response['error'] = 'no';
            $response['orders'] = $orderInfor;
            $response['message'] = 'Success';
        }
        return response()->json($response);
    }

    public function getNotification(Request $request){
        $phone = $request->phone;
        $userPhone = $this->customerRepository->getModel()->where('phone',$phone)->first();
        //return response()->json($userPhone);
        if(!$userPhone){
            $response = '{}';
            return response()->json($response);
        }else{
            $list = [];
            $listNoti = Commissions::orderBy('created_at','desc')
                ->where('customer_id',$userPhone->id)
                ->where('status','active')->take(6)->get();
            foreach($listNoti as $d){
                $list[] = [
                    'id'=>$d->id,
                    'status'=>'active',
                    'title'=>'Cộng tiền hoa hồng +'.number_format($d->commission),
                    'content'=>'+'.number_format($d->commission).' điểm từ giao dịch đơn hàng '.get_order_code($d->order_id),
                    'image'=>'https://ezlife.vn/storage/icons/dollar.png'
                ];
            }
            $response = $list;
            return response()->json($response);
        }
    }

    public function postEditCustomer(Request $request){
        $phone = $request->phone;
        $email = $request->post('email','');
        $nguoigioithieu = $request->post('presenter_code',NULL);
        $infoPresenterID = null;
        if(!is_null($nguoigioithieu)){
            $infoPresenter = $this->customerRepository->getModel()->where('affiliation_id',$nguoigioithieu);
            if(!is_null($infoPresenter)){
                $infoPresenterID = $infoPresenter->id;
            }
        }
        $userPhone = $this->customerRepository->getModel()->where('phone',$phone)->first();

        if(!$userPhone){
            $create = $this->customerRepository->createOrUpdate([
                'name'=>$request->name,
                'email'=>$request->email,
                'phone'=>$phone,
                'password'=>Hash::make('ezlife.vn'),
                'is_verified'=>1,
                'presenter_id'=>$infoPresenterID
            ]);
            $create->affiliation_id = '100'.$create->id;
            $create->save();

            //update customer_address
            $userAddress = $this->customerAddressRepository->createOrUpdate([
                'name'=>$request->name,
                'customer_id'=>$create->id,
                'email'=>$email,
                'phone'=>$request->phone,
                'country'=>'VN',
                'state'=>$request->state,
                'city'=>$request->city,
                'ward'=>$request->ward,
                'address'=>$request->address
            ],['customer_id'=>$create->id]);

            return response()->json($create);
        }else{
            $userPhone->name = $request->name;
            $userPhone->email = $request->email;
            $userPhone->presenter_id = $infoPresenterID;
            $userPhone->save();
            //update customer_address
            $userAddress = $this->customerAddressRepository->createOrUpdate([
                'name'=>$request->name,
                'customer_id'=>$userPhone->id,
                'email'=>$email,
                'phone'=>$request->phone,
                'country'=>'VN',
                'state'=>$request->state,
                'city'=>$request->city,
                'ward'=>$request->ward,
                'address'=>$request->address
            ],['customer_id'=>$userPhone->id]);

            return response()->json($userAddress);
        }


    }

    public function getListCity(Request $request){
        $list = [];
        $cities = Province::orderBy('gso_id','asc')->get();
        foreach($cities as $c){
            $list[] = [
                'value'=>$c->id,
                'label'=>$c->name
            ];
        }
        return response()->json($list);
    }
    public function getDistrictbyCity(Request $request){
        $list = [];
        $districts = Districts::orderBy('name','asc')->where('province_id',$request->province_id)->get();
        foreach($districts as $c){
            $list[] = [
                'value'=>$c->id,
                'label'=>$c->name
            ];
        }
        return response()->json($list);
    }
    public function getWardByDistrict(Request $request){
        $list = [];
        $wards = Ward::orderBy('name','asc')->where('district_id',$request->district_id)->get();
        foreach($wards as $c){
            $list[] = [
                'value'=>$c->id,
                'label'=>$c->name
            ];
        }
        return response()->json($list);
    }


}