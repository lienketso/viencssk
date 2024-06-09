<?php

namespace Botble\Ecommerce\Commands;

use Botble\Ecommerce\Repositories\Interfaces\CustomerInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class ShareWalletCommand extends Command
{
    public $customer;
    protected $signature = 'cms:wallet:share';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chia hoa hồng từ ví tạm tính vào các ví điểm và ví tiền mặt';

    public function __construct(CustomerInterface $customerRepository)
    {
        parent::__construct();
        $this->customer = $customerRepository;
    }

    public function handle()
    {

        try {
            $customers = $this->customer->getModel()->where('wallet','>',0)->get();
            foreach ($customers as $c){
                //Cộng tiền vào ví điểm
                if($c->wallet<10000000){
                    $point = $c->wallet*0.8;
                    $cash = $c->wallet*0.2;
                }elseif($c->wallet>=10000000 && $c->wallet<50000000){
                    $point = $c->wallet*0.7;
                    $cash = $c->wallet*0.3;
                }elseif($c->wallet>=50000000 && $c->wallet<200000000){
                    $point = $c->wallet*0.6;
                    $cash = $c->wallet*0.4;
                }elseif($c->wallet>=200000000 && $c->wallet<1000000000){
                    $point = $c->wallet*0.5;
                    $cash = $c->wallet*0.5;
                }elseif($c->wallet>=200000000 && $c->wallet<1000000000){
                    $point = $c->wallet*0.4;
                    $cash = $c->wallet*0.6;
                }
                DB::table('ec_customers')->where('id', $c->id)->increment('ubgxu', $point);
                //Cộng tiền vào ví tiền mặt
                DB::table('ec_customer_wallet')->where('customer_id', '=', $c->id)->increment('amount', $cash);
                //Reset ví tạm tính về 0
                DB::table('ec_customers')->where('id', $c->id)->update(['wallet'=>0]);
            }

            $this->info('Cron job chia hoa hồng vào các ví thành công');
        }catch (\Exception $e){
            DB::rollBack();
            return $e->getMessage();
        }

    }

}