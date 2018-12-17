<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Record extends Base
{
    protected $table='fb_flow_record';

    /**
     * 增加流水记录
     * @param $user_id
     * @param $type_id
     * @param $amount
     * @param $process_type
     * @return bool
     */
    public function addRecord($user_id,$type_id,$amount,$process_type){

        date_default_timezone_set('PRC');

        $now = date("Y-m-d H:i:s" ,time());

        $insertData=[
            'user_id'=>$user_id,
            'type_id'=>$type_id,
            'amount'=>$amount,
            'created_at' => $now,
            'process_type'=>$process_type,
        ];
        $res=DB::table('fb_flow_record')->insert($insertData);

        if($res){

            return true;

        }else{

            return false;

        }
    }

    /**
     * 获取用户的流水记录
     * @param $user_id
     */
    public function getUserRecord($user_id){

        $sql="select f.*,u.user_wechat_nickname,r.bonus_from,a.id_paid from fb_flow_record as f left join fb_red_envelopes as r on r.id=f.type_id LEFT JOIN fb_user_account as a ON a.id=f.type_id LEFT JOIN fb_user as u on u.id=f.user_id WHERE f.user_id =$user_id order by f.created_at desc ";

        $record_data = DB::select( $sql );



        if($record_data){

            $result=$this->getArrayByObject($record_data);

            $user_model = new User();

            foreach ($result as $k=>$v){
                $time=strtotime($v['created_at']);
                $result[$k]['created_at'] =date("Y.m.d H:i:s",$time);
                if($v['process_type']==1){
                    $user_info=$user_model->getUserInfo($v['bonus_from']);
                    if($user_info){
                        $result[$k]['user_name']=$user_info['user_name'];
                        $result[$k]['red_envelopes_money'] ='+'.$v['amount'];
                        $result[$k]['mark'] ='注册成功';
                    }
                }elseif($v['id_paid']==1){
                    $result[$k]['user_name']=$v['user_wechat_nickname'];
                    $result[$k]['red_envelopes_money'] ='-'.$v['amount'];
                    $result[$k]['mark'] ='提现成功';
                }else{
                    $result[$k]['user_name']=$v['user_wechat_nickname'];
                    $result[$k]['red_envelopes_money'] ='-'.$v['amount'];
                    $result[$k]['mark'] ='提现中';
                }
            }

            return $result;

        }else{

            return false;
        }

    }

}
