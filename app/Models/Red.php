<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Red extends Base
{
    protected $table='fb_red_envelopes';

    /**
     * 获取用户未提现的红包总额
     * @param $user_id
     * @return float|int
     */
    public function getUserRedSum($user_id,$where=""){
        if($where){
            $result=DB::table('fb_red_envelopes')
                ->where('red_envelopes_status',1)
                ->where('user_id',$user_id)
                ->where($where)
                ->sum('red_envelopes_money');
        }else{

            $result=DB::table('fb_red_envelopes')
                ->where('red_envelopes_status',1)
                ->where('user_id',$user_id)
                ->sum('red_envelopes_money');
        }
        if($result){

            return $result;

        }else{
            return 0;
        }
    }

    /**
     * 获取已经提现的红包
     * @param $user_id
     * @return mixed
     */
    public function getUserRedCashSum($user_id){

        $result=DB::table('fb_red_envelopes')
            ->where('red_envelopes_status',2)
            ->where('user_id',$user_id)
            ->sum('red_envelopes_money');

        return $result;

    }

    /*
     * 给邀请人红包
     */
    public function addUserRed($user_id,$id){

        if ($this->getUserRed($user_id)){

            #根据id获取用户邀请好友所的红包总额
            $bonus_user_num=$this->getUserRedSum($user_id);//总和
            $bonus_max_num = Config('api.bonus_max_num');//18.88
            $bonus_one_max_num = Config('api.bonus_one_max_num');
            $bonus_num = Config('api.bonus_num');

            $bonus_max_num=number_format($bonus_max_num,2);
            $bonus_user_num=number_format($bonus_user_num,2);


            $overplus_num=$bonus_max_num-$bonus_user_num;
            $overplus_num=number_format($overplus_num,2);

            if($overplus_num==0){

                return false;

            }else {

                #根据红包的总额与邀请好友红包的上线进行对比
                if ($overplus_num <= $bonus_one_max_num) {

                    $bonus_user_add = $overplus_num;

                } else {

                    $bonus_user_add = array_rand($bonus_num, 1);

                    $bonus_user_add = $bonus_num[$bonus_user_add];
                }
            }

        }else{

            $bonus_user_add = Config('api.bonus_first_num');

        }

            $res=$this->giveUserAdd($user_id,$bonus_user_add,$id);

            return $res;
    }

    protected function giveUserAdd($user_id,$add_num,$bonus_from){
        date_default_timezone_set('PRC');
        $now = date("Y-m-d H:i:s" ,time());

        $insertData = [
            'user_id' => $user_id,
            'is_copy' => 1,
            'red_envelopes_status'=> 1,
            'created_at' => $now,
            'red_envelopes_money'=>$add_num,
            'bonus_from'=>$bonus_from,
            'bonus_type'=>1
        ];

        $res=DB::table('fb_red_envelopes')->insertGetId($insertData);

        if($res){

            $record_model = new Record();

            $record_model->addRecord($user_id,$res,$add_num,1);

            return true;

        }else{

            return false;

        }

    }

    public function getUserRed($user_id){

        $result=Red::where(['user_id'=>$user_id])
            ->get(['bonus_from','bonus_type','red_envelopes_money','created_at']);
        if($result){
            $result=$result->toArray();
            $user_model = new User();
            foreach ($result as $k=>$v){
                $time=strtotime($v['created_at']);
                $result[$k]['created_at'] =date("Y.m.d H:i:s",$time);
                if($v['bonus_type']==1){
                    $user_info=$user_model->getUserInfo($v['bonus_from']);
                    if($user_info){
                        $result[$k]['user_name']=$user_info['user_name'];
                        $result[$k]['red_envelopes_money'] ='+'.$v['red_envelopes_money'];
                        $result[$k]['mark'] ='注册成功';
                    }

                }else{
                    $result[$k]['user_name']='最低价小助手';
                    $result[$k]['red_envelopes_money'] ='+'.$v['red_envelopes_money'];
                    $result[$k]['mark'] ='系统奖励';
                }
            }
            return $result;
        }else{
            return false;
        }
    }



    public function getCashCount($user_id){

        $result=DB::table('fb_red_envelopes')
            ->where('red_envelopes_status',2)
            ->where('user_id',$user_id)
            ->count();

        return $result;
    }


    public function getFristCash($user_id){

        $result=DB::table('fb_red_envelopes')
            ->where(['user_id'=>$user_id,'red_envelopes_status'=>1])
            ->orderBy('created_at','asc')
            ->first(['id','created_at']);

        if($result){

            $result=get_object_vars($result);

            $result['created_at'] = strtotime($result['created_at']);

        }else{

            $result=false;
        }

        return $result;
    }
}
