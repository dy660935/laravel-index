<?php

namespace App\Http\Controllers;

use App\Libs\payment\EnterprisePayment;
use App\Models\Account;
use App\Models\Red;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    //获取sessionKey 返回授权头像
    public function sessionKey(){

        $code = $_REQUEST['code'];

        $appId = "wxe7a9a545c82bce8b";

        $app_secret = "ce54a4bfa4a4993cb2b47ca340f9c4ee";

        $api = "https://api.weixin.qq.com/sns/jscode2session?appid=$appId&secret=$app_secret&js_code=$code&grant_type=authorization_code";

        $result = httpGet($api);

        $result = json_decode($result, true);

        if (!empty($result['openid'])) {

            session_start();

            $session_id = session_id();

            $info = $result['session_key'] . $result['openid'];

            $session_key = $result['session_key'];

            $_SESSION['session'] = $info;

            $_SESSION['session_key'] = $session_key;

            $res['rd_session'] = $session_id;

            $user_model = new User();

            $user_id = $user_model ->getUserId($result['openid']);

            if(empty($user_id)){

                $res['is_login'] = 0;

            }else{

                $res['is_login'] = 1;

            }
            //授权头像
            $img = Config('api.imgDomain');

            $data = Config("api.own_config");

            $res['share_img'] = $img.$data['share_img'];

        } else {
            $res['errcode'] = 1;
            $res['errmsg'] = 'no openid';
        }

        $json_info = [
            'status_code' => 1,
            'message' => '',
            'data'=>$res
        ];
        return   $json_info;
    }

    //登录接口
    public function loginDo(Request $request)
    {
        $userInfo = $request->post('userinfo');

        $userInfo = json_decode($userInfo, true);

        $session = $request->post('session');

        $flag = $request->post('share_only','');

        $open_id = $this->GetOpenId($session);

    //	file_put_contents(__DIR__ . '/tuling.log', print_r($session, true).PHP_EOL , FILE_APPEND);
    //	file_put_contents(__DIR__ . '/tuling.log', print_r($flag, true).PHP_EOL , FILE_APPEND);

        $result = DB::table('fb_user')->where('user_open_id' ,$open_id)->first();

        if ($result) {

            $json_info = [
                'status_code' => 1,
                'message' => '成功',
                'data' =>''
            ];

            return   $json_info;

        } else {

            $time = date('Y-m-d H:i:s',time());

            if(empty($flag)){

                $insertData = [
                    'user_wechat_nickname' => $userInfo['nickName'],
                    'user_define_nickname' => $userInfo['nickName'],
                    'user_avatar' => $userInfo['avatarUrl'],
                    'user_login_time' => $time,
                    'user_genter' => $userInfo['gender'],
                    'user_status' => 1,
                    'user_open_id' => $open_id,
                    'created_at' => date("Y-m-d H:i:s",time())
                ];

                $id = DB::table('fb_user')->insertGetId($insertData);

            }else{

                $select_res = DB::table('fb_user')
                    ->where('user_only_num', $flag)
                    ->first(['id','user_open_id']);

                $select_res=get_object_vars($select_res);

                $insertData = [
                    'user_wechat_nickname' => $userInfo['nickName'],
                    'user_define_nickname' => $userInfo['nickName'],
                    'user_avatar' => $userInfo['avatarUrl'],
                    'user_login_time' => $time,
                    'user_genter' => $userInfo['gender'],
                    'user_status' => 1,
                    'user_open_id' => $open_id,
                    'parent_id' => $select_res['id'],
                    'created_at' => date("Y-m-d H:i:s",time())
                ];

                $id = DB::table('fb_user')->insertGetId($insertData);

                if($select_res['id']!=$id){
                    #需要给邀请用户发红包
                    $red_model = new Red();

                    $add_result=$red_model->addUserRed($select_res['id'],$id);

                    $bonus_user_add = Config('api.bonus_first_num');

                    #查看红包记录表中记录

                    $cash_result=$red_model->getCashCount($select_res['id']);

                    if($cash_result==0){

                        $this->FristCash($select_res['id'],$select_res['user_open_id'],$bonus_user_add);
                    }
                }
            }


            if ($id) {

                $json_info = [
                    'status_code' => 1,
                    'message' => '成功',
                    'data' =>''
                ];
                return $json_info;

            } else {

                $json_info = [
                    'status_code' => 1000,
                    'message' => '失败',
                    'data' =>''
                ];
                return $json_info;
            }
        }
    }


    /**
     * 获取用户的openID
     * @param $session_id
     * @return bool|string
     */
    public function GetOpenId($session_id)
    {
        session_id($session_id);

        session_start();

        $session_value = $_SESSION['session'];

        $session_key_value = $_SESSION['session_key'];

        $count = strlen($session_key_value);

        $open_id = substr($session_value, $count);

        return $open_id;
    }



    public function FristCash($user_id,$open_id,$cash_num){

        #调用提现接口进行提现
        $payment_model=new EnterprisePayment();

        $cash_result=$payment_model->weixin_pay_person($open_id,$cash_num);

        if($cash_result['return_code']=='SUCCESS' && $cash_result['result_code']=='SUCCESS'){

            $cash_result['user_open_id']=$open_id;

            $cash_result['user_id']=$user_id;

            $cash_result=json_encode($cash_result);

            $data_time='['.date('Y-m-d H:i:s',time()).'] :';

            $this->write_log($data_time.$cash_result,1);

            #把红包记录状态改为已经提现状态
            $update_result=DB::table('fb_red_envelopes')
                ->where('user_id', $user_id)
                ->update(['red_envelopes_status' => 2]);

            $account_model = new Account();

            $account_model->addAccount($user_id,$cash_num,1);

            return true;

        }else{

            $cash_result['user_open_id']=$open_id;

            $cash_result['user_id']=$user_id;

            $cash_result=json_encode($cash_result);

            $data_time='['.date('Y-m-d H:i:s',time()).'] :';

            $this->write_log($data_time.$cash_result,2);

            return false;
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
