<?php
/**
 * CollaboratorOfflineOrderController.php
 * Created by: trainheartnet
 * Created at: 10/05/2022
 * Contact me at: longlengoc90@gmail.com
 */


namespace Botble\Ecommerce\Http\Controllers\Collaborator;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Repositories\Interfaces\OrderInterface;
use Botble\Ecommerce\Supports\EcommerceHelper;
use Botble\Ecommerce\Tables\Front\CollaboratorOfflineOrdersTable;
use Assets;

class CollaboratorOfflineOrderController extends BaseController
{
    /**
     * CollaboratorOfflineOrderController constructor.
     */
    protected $orderRepository;
    public function __construct(OrderInterface $orderRepository)
    {
        Assets::setConfig(config('plugins.marketplace.assets', []));
    }

    /**
     * @param CollaboratorOfflineOrdersTable $table
     * @return mixed
     * @throws \Throwable
     */
    public function index(CollaboratorOfflineOrdersTable $table)
    {
        page_title()->setTitle('Đơn hàng mua trực tiếp tại siêu thị');

        return $table->render('plugins/ecommerce::collaborator.dashboard.table.base');
    }

    public function editOrder($id){
        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/jquery.textarea_autosize.js',
                'vendor/core/plugins/ecommerce/js/order.js',
            ])
            ->addScripts(['blockui', 'input-mask']);

        if (\EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation()) {
            Assets::addScriptsDirectly('vendor/core/plugins/location/js/location.js');
        }

        $order = $this->orderRepository->findOrFail($id, ['products', 'user']);

        page_title()->setTitle(trans('plugins/ecommerce::order.edit_order', ['code' => get_order_code($id)]));

        $weight = $order->products_weight;

        $defaultStore = get_primary_store_locator();

        return view('plugins/ecommerce::collaborator.sale.edit-order', compact('order', 'weight', 'defaultStore'));
    }

}