<?php

namespace App\Http\Controllers;

use App\Models\Strategy;
use Illuminate\Http\Request;

class StrategyController extends CommonController
{
    /**
     * 攻略详情页面
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function strategyDetail(Request $request)
    {

        $strategy_id = $request->post("strategy_id",'');

        if (empty($strategy_id)){

            return $this->errorCode('参数有误', '', 1000);

        } else {

            $strategy_model = new Strategy();

            $strategy_info = $strategy_model->strategyInfoFind($strategy_id);

            if($strategy_info){

                return $this->successCode($strategy_info, '成功', 1);

            }else{

                return $this->errorCode('参数有误', '', 1000);

            }

        }
    }


    //攻略列表
    public function strategyIndex(){

        $strategy_type = request()->post('strategy_type');

        $p = request()->post( "p" , '' );

        if(empty($strategy_type)){

            $strategy_type = 0;
        }

        $strategy_all = $this-> strategyTabPublic($strategy_type,$p);

        if($strategy_all){

            return $this -> successCode($strategy_all ,'成功',1);

        }else{

            return $this->errorCode('暂无数据', '', 1000);
        }

    }

    //攻略tab
    public function strategyTabPublic($strategy_type,$p)
    {
        //获取热门、攻略
        $strategy_model = new Strategy();

        $limit=$this->getLimitNum($p);

        $strategy_all = $strategy_model -> getStrategyIndex($strategy_type,$limit);

        return $strategy_all;
    }
}
