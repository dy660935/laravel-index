<?php

namespace App\Http\Controllers;

use App\Libs\payment\EnterprisePayment;
use App\Models\Account;
use App\Models\Record;
use App\Models\Red;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends CommonController
{
    /**
     * 我的首页
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userIndex(Request $request)
    {
        #获取用户的id
        $session_id = $request->post('session');

        $open_id=$this->getOpenId($session_id);

        $userModel = new User();

        $user_id=$userModel->getUserId($open_id);

        $md_res=$open_id.$user_id;

        $only_num=substr(md5($md_res),0,15);
        #增加用户的唯一标识
        $userModel->addUserOnlyNum($user_id,$only_num);

        #查出用户的基本信息
        $user_info = $userModel->getUserInfo($user_id);

        #查出用户红包钱
        $redModel = new Red();

        $red_money= $redModel->getUserRedSum($user_id);

        $cash_sum =$redModel->getUserRedCashSum($user_id);

        $zong_sum=$red_money+$cash_sum;

        $bonus_max_num = Config('api.bonus_max_num');

        #根据用户的id查出红包的记录
        $surplus_money = $bonus_max_num - $zong_sum;

        if($user_info){

            $user_info['red_money'] = number_format($zong_sum,2);

            $red_data=$this->getConfig();

            $red_data['own']['surplus_money']=number_format($surplus_money,2);

            $user_info['red_data'] =$red_data['own'];

            $user_info['share'] =$red_data['share'];

            $user_info['share']['share_only'] =$only_num;

            //获取用户是否邀请好友成功
            $info = DB::table('fb_user')
                ->where(['parent_id' => $user_id])
                ->first();

            if(empty($info)){
                $user_info['is_have'] = 1;
            }else{
                $user_info['is_have'] = 0;
            }

            return $this ->successCode($user_info,'成功',1);

        }else{

            return $this -> errorCode('参数有误','',1000);
        }

    }


    /**
     * 获取配置项我的页面的数据
     * @return mixed
     */
    protected function getConfig(){

        $own_config = Config('api.own_config');
        $frontend_img = Config('api.imgDomain');
        $heard_img=$own_config['head_img'];
        $data['own']['own_one']=$own_config['own_one'];
        $data['own']['own_two']=$own_config['own_two'];
        $data['own']['own_three']=$own_config['own_three'];
        $data['own']['head_img'] = $frontend_img.$heard_img;
        $data['share']['share_title']=$own_config['share_title'];
        $data['share']['share_img']=$frontend_img.$own_config['share_img'];

        return $data;
    }


    /**
     * 获取红包详情接口
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userBonusInfo(){

        #获取用户的id
        $session = request()->post('session');

        $open_id=$this->getOpenId($session);

        $userModel = new User();

        $user_id=$userModel->getUserId($open_id);

        $user_info = $userModel->getUserInfo($user_id);

        #获取该用户所有的红包数
        $red_model = new Red();
        #获取未提现的金额
        $red_sum=$red_model->getUserRedSum($user_id);

        #获取用户已经提现的红包数
        $cash_sum =$red_model->getUserRedCashSum($user_id);

        $user_info['red_num']=number_format($red_sum+$cash_sum,2);

        if($red_sum==0){

            $user_info['cash_num']=$red_sum;

        }else{

            $user_info['cash_num']=number_format($red_sum,2);

        }

        $record_model = new Record();

        $red_data=$record_model->getUserRecord($user_id);

        $data['user_info']=$user_info;

        if($red_data){

            $data['red_data']=$red_data;

        }else{

            $data['red_data']='';

        }

        return $this ->successCode($data,'成功',1);

    }


    /*
     * 用户提现接口
     */
    public function userCash(){

        #获取用户的id
        $session_id = request()->post('session');

        $open_id=$this->getOpenId($session_id);

        $userModel = new User();

        $user_id=$userModel->getUserId($open_id);

        #接受提现的金额
        $cash_num= request()->post('cash_num');

        #获取配置最小金额
        $bonus_one_min_num = Config('api.bonus_one_min_num');

        if($cash_num<$bonus_one_min_num){

            return   $this->errorCode('余额大于'.$bonus_one_min_num.'元  可提现','',1000);
        }

        #判断用户提现的金额是否超过可提现红包金额
        $red_model = new Red();

        #红包的金额
        $user_red_num=$red_model->getUserRedSum($user_id);

        #查询提现记录表中的数据是否大于2次

        $account_model = new Account();

        $cash_count=$account_model->getCashCount($user_id);


        if($user_red_num<$cash_num){

            return   $this->errorCode('余额大于'.$bonus_one_min_num.'元  可提现','',1000);

        }else{

            if($cash_count>=2){

                #把红包记录状态改为已经提现状态
                $update_result=DB::table('fb_red_envelopes')
                    ->where('user_id', $user_id)
                    ->update(['red_envelopes_status' => 2]);

                $account_model = new Account();

                $account_model->addAccount($user_id,$cash_num,2);

                return   $this->errorCode('提现成功'."\r\n". '审核通过后  将在10天内到账','',1000);

            }else{

                $add_result=$account_model->addAccount($user_id,$cash_num,1);

                if($add_result){

                    $payment_model=new EnterprisePayment();

                    $res=$payment_model->weixin_pay_person($open_id,$cash_num);

                    if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){

                        #把红包记录状态改为已经提现状态
                        $update_result=DB::table('fb_red_envelopes')
                            ->where('user_id', $user_id)
                            ->update(['red_envelopes_status' => 2]);

                        $res['user_open_id']=$open_id;

                        $res['user_id']=$user_id;

                        $res=json_encode($res);

                        $data_time='['.date('Y-m-d H:i:s',time()).'] :';

                        $this->write_log($data_time.$res,1);

                        #提现成功之后的逻辑
                        return    $this->successCode('','提现成功',1);

                    }else{

                        $res['user_open_id']=$open_id;

                        $res['user_id']=$user_id;

                        $res=json_encode($res);

                        $data_time='['.date('Y-m-d H:i:s',time()).'] :';

                        $this->write_log($data_time.$res,2);

                        return    $this->errorCode('提现失败,请稍后再试','',1000);
                    }

                }else{

                    return    $this->errorCode('提现失败,请稍后再试','',1000);
                }

            }

        }
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


}
