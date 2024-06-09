<?php

namespace Botble\Ecommerce\Tables;

use BaseHelper;
use Botble\Ecommerce\Enums\CollaboratorLevelEnums;
use Botble\Ecommerce\Enums\CustomerStatusEnum;
use Botble\Ecommerce\Repositories\Interfaces\CustomerInterface;
use Botble\Table\Abstracts\TableAbstract;
use EcommerceHelper;
use Html;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class CollaboratorTable extends TableAbstract
{
    /**
     * @var bool
     */
    protected $hasActions = true;

    /**
     * @var bool
     */
    protected $hasFilter = true;

    /**
     * CustomerTable constructor.
     * @param DataTables $table
     * @param UrlGenerator $urlGenerator
     * @param CustomerInterface $customerRepository
     */
    public function __construct(DataTables $table, UrlGenerator $urlGenerator, CustomerInterface $customerRepository)
    {
        parent::__construct($table, $urlGenerator);

        $this->repository = $customerRepository;

        $this->hasOperations = true;
        $this->hasActions = false;
//        if (!Auth::user()->hasAnyPermission(['customers.edit', 'customers.destroy'])) {
//            $this->hasOperations = false;
//            $this->hasActions = false;
//        }
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
                    return $item->avatar_url;
                }

                return Html::tag('img', '', ['src' => $item->avatar_url, 'alt' => clean($item->name), 'width' => 50]);
            })
            ->editColumn('name', function ($item) {
                if (!Auth::user()->hasPermission('customers.edit')) {
                    return clean($item->name);
                }

                return Html::link(route('customers.edit', $item->id), clean($item->name));
            })
            ->editColumn('email', function ($item) {
                return clean($item->email);
            })
            ->editColumn('presenter_id', function ($item) {
                return $item->getParent->count() > 0 ? $item->getParent->first()->phone.' - '.$item->getParent->first()->name : '--';
            })
            ->editColumn('checkbox', function ($item) {
                return $this->getCheckbox($item->id);
            })
            ->editColumn('wallet', function ($item) {
                return $item->wallet ? format_price($item->wallet->amount) : format_price(0);
            })
            ->editColumn('ubgxu', function ($item) {
                return number_format($item->ubgxu);
            })
            ->editColumn('created_at', function ($item) {
                return BaseHelper::formatDate($item->created_at);
            })
            ->editColumn('collaborator_level', function ($item) {
                return clean($item->collaborator_level->toHtml());
            })
            ->editColumn('status', function ($item) {
                return clean($item->status->toHtml());
            });

        if (EcommerceHelper::isEnableEmailVerification()) {
            $data = $data
                ->addColumn('confirmed_at', function ($item) {
                    return $item->confirmed_at ? Html::tag('span', trans('core/base::base.yes'),
                        ['class' => 'text-success']) : trans('core/base::base.no');
                });
        }

        $data = $data
            ->addColumn('operations', function ($item) {
                return $this->getOperations('collaborator.edit', null, $item,
                    '<a href="'.route('customers.logged-in-as', $item->id).'" class="btn btn-icon btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-original-title="Đăng nhập bằng tài khoản này"><i class="fa fa-sign"></i></a>'
                );
            });

        return $this->toJson($data);
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        $query = $this->repository->getModel()->with(['wallet'])->select([
            'id',
            'name',
            'email',
            'avatar',
            'created_at',
            'status',
            'confirmed_at',
            'phone',
            'is_verified',
            'affiliater_status',
            'area',
            'ubgxu',
            'presenter_id',
            'collaborator_level'
        ])->where('is_verified', 1)->where('affiliater_status', 2);

        return $this->applyScopes($query);
    }

    /**
     * {@inheritDoc}
     */
    public function columns()
    {
        $columns = [
            'id'         => [
                'title' => trans('core/base::tables.id'),
                'width' => '20px',
                'class' => 'text-start',
            ],
            'avatar'      => [
                'title' => trans('plugins/ecommerce::customer.avatar'),
                'class' => 'text-center',
            ],
            'phone'      => [
                'title' => trans('plugins/ecommerce::customer.phone'),
                'class' => 'text-start',
            ],
            'presenter_id'       => [
                'title' => 'Người giới thiệu',
                'class' => 'text-start',
            ],
            'name'       => [
                'title' => trans('core/base::forms.name'),
                'class' => 'text-start',
            ],
            'wallet' => [
                'title' => 'Số dư khả dụng',
                'class' => 'text-center'
            ],
            'ubgxu' => [
                'title' => 'Số dư điểm',
                'class' => 'text-center'
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'width' => '100px',
                'class' => 'text-start',
            ],
            'area' => [
                'name'  => 'ec_customers.area',
                'title' => 'Khu vực HĐ',
                'class' => 'text-left',
                'width' => '100px',
            ],
            'collaborator_level'     => [
                'title' => 'Cấp độ đại lý',
                'width' => '100px',
            ],
            'status'     => [
                'title' => trans('core/base::tables.status'),
                'width' => '100px',
            ]
        ];

        if (EcommerceHelper::isEnableEmailVerification()) {
            $columns += [
                'confirmed_at' => [
                    'title' => trans('plugins/ecommerce::customer.email_verified'),
                    'width' => '100px',
                ],
            ];
        }

        return $columns;
    }

    /**
     * {@inheritDoc}
     */
    public function buttons()
    {
//        return [];
        return $this->addCreateButton(route('collaborator.create'), 'collaborator.index');
    }

    /**
     * {@inheritDoc}
     */
    public function bulkActions(): array
    {
        return $this->addDeleteAction(route('customers.deletes'), 'customers.destroy', parent::bulkActions());
    }

    /**
     * {@inheritDoc}
     */
    public function getBulkChanges(): array
    {
        return [
            'name'       => [
                'title'    => trans('core/base::tables.name'),
                'type'     => 'text',
                'validate' => 'required|max:120',
            ],
            'presenter_id'       => [
                'title'    => 'Người giới thiệu',
                'type' => 'select-search',
                'validate' => 'required',
                'callback' => 'getCustomers',
            ],
            'status'     => [
                'title'    => trans('core/base::tables.status'),
                'type'     => 'select',
                'choices'  => CustomerStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', CustomerStatusEnum::values()),
            ],
            'colalborator_level'     => [
                'title'    => 'Cấp độ đại lý',
                'type'     => 'select',
                'choices'  => CollaboratorLevelEnums::labels(),
                'validate' => 'required|in:' . implode(',', CustomerStatusEnum::values()),
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type'  => 'date',
            ],
            'area' => [
                'title' => 'Khu vực hoạt động',
                'type' => 'text',
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function renderTable($data = [], $mergeData = [])
    {
        if ($this->query()->count() === 0 &&
            $this->request()->input('filter_table_id') !== $this->getOption('id') && !$this->request()->ajax()
        ) {
            return view('plugins/ecommerce::customers.intro');
        }

        return parent::renderTable($data, $mergeData);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultButtons(): array
    {
        return [
            'export',
            'reload',
        ];
    }

    /**
     * @return array
     */
    public function getCustomers(): array
    {
        return $this->repository->pluck('name', 'id');
    }
}
