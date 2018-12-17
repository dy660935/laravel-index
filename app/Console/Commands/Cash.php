<?php

namespace App\Console\Commands;

use App\Libs\payment\EnterprisePayment;
use App\Models\Account;
use App\Models\Record;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Cash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cash:ok';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getArrayByObject( $object )
    {

        if( !$object || !is_array( $object ) ) return [];

        foreach( $object as $k => $v ) {

            $data[ $k ] = (array) $v;
        }

        return $data;
    }


    private function write_log($data,$type){

        $years = date('Y-m');

        if($type==1){

            $url='/opt/logs/'.$years.'/'.date('Ymd').'_cash_success_log.txt';

        }else{

            $url='/opt/logs/'.$years.'/'.date('Ymd').'_cash_error_log.txt';

        }

        //设置路径目录信息
        $dir_name=dirname($url);
        //目录不存在就创建
        if(!file_exists($dir_name))
        {
            $res = mkdir(iconv("GBK","UTF-8", $dir_name),0777,true);
        }
        $fp = fopen($url,"a");
        fwrite($fp,var_export($data,true)."\r\n");
        fclose($fp);
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->write_log(123);die;
        #查询未到账的订单

        $data = DB::table('fb_user_account as a')
            ->leftJoin('fb_user as u', 'u.id', '=', 'a.user_id')
            ->where(['id_paid'=>2])
            ->get(['a.id','user_id','amount','process_type','id_paid','u.created_at','user_wechat_nickname as user_name','user_open_id']);

        if($data){

            foreach ( $data as $k => $v ) {

                $created_time = strtotime( $v->created_at );

                #10天之后时间
                $new_time=$created_time+3600*24*10;

                if(time()>=$new_time) {

                    $payment_model=new EnterprisePayment();

                    $res=$payment_model->weixin_pay_person($v->user_open_id,$v->amount);

                    if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){

                        #把红包记录状态改为已经提现状态
                        $update_result=DB::table('fb_user_account')
                            ->where('id', $v->id)
                            ->update(['id_paid' => 1]);

                        $res['user_open_id']=$v->user_open_id;

                        $res['user_id']=$v->user_id;

                        $res=json_encode($res);

                        $data_time='['.date('Y-m-d H:i:s',time()).'] :';

                        $this->write_log($data_time.$res,1);

                        #提现成功之后的逻辑
                        return    $this->successCode('','ok',1);

                    }else{

                        $res['user_open_id']=$v->user_open_id;

                        $res['user_id']=$v->user_id;

                        $res=json_encode($res);

                        $data_time='['.date('Y-m-d H:i:s',time()).'] :';

                        $this->write_log($data_time.$res,2);

                        return    $this->errorCode($res['err_code_des'],'',1000);
                    }
                }

            }


        }

    }
}
