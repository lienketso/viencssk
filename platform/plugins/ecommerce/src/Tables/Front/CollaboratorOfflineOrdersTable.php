<?php
/**
 * CollaboratorOfflineOrdersTable.php
 * Created by: trainheartnet
 * Created at: 10/05/2022
 * Contact me at: longlengoc90@gmail.com
 */


namespace Botble\Ecommerce\Tables\Front;

use Botble\Marketplace\Repositories\Interfaces\StoreInterface;
use Botble\Ecommerce\Repositories\Interfaces\BillOrderInterface;
use Botble\Ecommerce\Repositories\Interfaces\OrderInterface;
use Botble\Table\Abstracts\TableAbstract;
use Illuminate\Contracts\Routing\UrlGenerator;
use Yajra\DataTables\DataTables;
use Html;
use BaseHelper;
use EcommerceHelper;

class CollaboratorOfflineOrdersTable extends TableAbstract
{
    /**
     * @var bool
     */
    protected $hasActions = false;

    /**
     * @var bool
     */
    protected $hasFilter = false;

    /**
     * @var bool
     */
    protected $hasCheckbox = false;

    /**
     * @var bool
     */
    protected $hasOperations = false;

    protected $orderOff;
    protected $storeRepository;

    /**
     * OrderTable constructor.
     * @param DataTables $table
     * @param UrlGenerator $urlGenerator
     * @param BillOrderInterface $orderRepository
     */
    public function __construct(DataTables $table, UrlGenerator $urlGenerator,
                                BillOrderInterface $orderRepository, OrderInterface $orderOffRepository, StoreInterface $sroreRepository)
    {
        $this->repository = $orderRepository;
        $this->orderOff = $orderOffRepository;
        $this->storeRepository = $sroreRepository;
        parent::__construct($table, $urlGenerator);
    }

    /**
     * {@inheritDoc}
     */
    public function ajax()
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('avatar', function ($item) {
                if ($this->request()->input('action') == 'excel' ||
                    $this->request()->input('action') == 'csv') {
                    return $item->user->avatar_url;
                }

                return Html::tag('img', '', ['src' => $item->user->avatar_url, 'alt' => clean($item->user->name), 'width' => 50]);
            })
            ->editColumn('id', function ($item) {
                return get_order_code($item->id);
            })
            ->editColumn('checkbox', function ($item) {
                return $this->getCheckbox($item->id);
            })
            ->editColumn('status', function ($item) {
                return clean($item->status->toHtml());
            })
            ->editColumn('process_affiliate', function ($item) {
                return clean($item->process_affiliate->toHtml());
            })
            ->editColumn('payment_method', function ($item) {
                return clean($item->payment->payment_channel->label() ?: '&mdash;');
            })
            ->editColumn('amount', function ($item) {
                return format_price($item->amount);
            })
            ->editColumn('shipping_amount', function ($item) {
                return format_price($item->shipping_amount);
            })
            ->editColumn('user_id', function ($item) {
                return clean($item->user->name ?: $item->address->name);
            })
            ->editColumn('created_at', function ($item) {
                return BaseHelper::formatDate($item->created_at);
            });

        if (EcommerceHelper::isTaxEnabled()) {
            $data = $data->editColumn('tax_amount', function ($item) {
                return format_price($item->tax_amount);
            });
        }

//        $data = $data
//            ->addColumn('operations', function ($item) {
//                return view(MarketplaceHelper::viewPath('dashboard.table.actions'), [
//                    'edit'   => 'marketplace.vendor.orders.edit',
//                    'delete' => 'marketplace.vendor.orders.destroy',
//                    'item'   => $item,
//                ])->render();
//            });

        return $this->toJson($data);
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        $userStore = $this->storeRepository->getModel()->where('customer_id',auth('customer')->id())->first();
        $query = $this->orderOff->getModel()
            ->select('*')
            ->where('store_id', $userStore->id)->where('order_resource','shop');

        return $this->applyScopes($query);
    }

    /**
     * {@inheritDoc}
     */
    public function columns()
    {
        $columns = [
            'id'      => [
                'title' => 'Mã đơn',
                'width' => '20px',
                'class' => 'text-start',
            ],
            'avatar'      => [
                'title' => trans('plugins/ecommerce::customer.avatar'),
                'class' => 'text-center',
            ],
            'user_id' => [
                'title' => trans('plugins/ecommerce::order.customer_label'),
                'class' => 'text-start',
            ],
            'amount'  => [
                'title' => 'Giá trị đơn',
                'class' => 'text-center',
            ],
        ];

        if (EcommerceHelper::isTaxEnabled()) {
            $columns['tax_amount'] = [
                'title' => trans('plugins/ecommerce::order.tax_amount'),
                'class' => 'text-center',
            ];
        }

        $columns += [
            'payment_method'  => [
                'name'  => 'payment_id',
                'title' => trans('plugins/ecommerce::order.payment_method'),
                'class' => 'text-start',
            ],
            'process_affiliate'  => [
                'name'  => 'process_affiliate',
                'title' => 'Trả Hoa hồng',
                'class' => 'text-center',
            ],
            'status'          => [
                'title' => trans('core/base::tables.status'),
                'class' => 'text-center',
            ],
            'created_at'      => [
                'title' => trans('core/base::tables.created_at'),
                'width' => '100px',
                'class' => 'text-start',
            ],
        ];

        return $columns;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultButtons(): array
    {
        return [
            'export',
            'create',
            'reload',
        ];
    }
}