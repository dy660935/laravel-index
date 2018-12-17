<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table='fb_user_account';

    /**
     * 给流水记录表里添加数据
     * @param $user_id
     * @param $cash_num
     */
    public function addAccount($user_id,$cash_num,$id_paid){

        date_default_timezone_set('PRC');

        $add_data=[
            'user_id'=>$user_id,
            'amount'=>$cash_num,
            'process_type'=>1,
            'id_paid'=>$id_paid,
            'created_at'=>date('Y-m-d H:i:s',time())
        ];

        $add_result=Account::insertGetId($add_data);

        if($add_result){

            $record_model = new Record();

            $record_model->addRecord($user_id,$add_result,$cash_num,2);

            return true;

        }else{

            return false;

        }

    }

    /*
     * 获取用户已经提现的金额
     */
    public function getUserAccountSum($user_id){

        $result=Account::where('id_paid',1)
            ->where(['user_id'=>$user_id])
            ->sum('amount');

        return $result;
    }

    public function getCashCount($user_id){

        $result=Account::where(['user_id'=>$user_id,'id_paid'=>1,'process_type'=>1])
            ->count();

        return $result;
    }

}
