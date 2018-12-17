<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/5
 * Time: 14:58
 */

namespace App\Libs\payment;

/*
* 企业付款到零钱
**/
class EnterprisePayment
{

    public function weixin_pay_person($re_openid,$cash_num)
    {
        // 请求参数
        $data['mch_appid'] ='wxe7a9a545c82bce8b' ;//商户号appid
        $data['mchid'] = 1505675491;//商户账号
        $data['nonce_str'] = $this->get_unique_value();// 随机字符串
        //商户订单号，可以按要求自己组合28位的商户订单号
        $data['partner_trade_no'] = $this->get_tradeno($data['mchid']);
        $data['openid'] = $re_openid;//用户openid
        //$data['check_name'] = 'FORCE_CHECK';//校验用户姓名选项
        $data['check_name'] = 'NO_CHECK';//校验用户姓名选项
        //$data['re_user_name'] = $user_name;//校验用户姓名选项
        $data['amount'] = $cash_num*100;//金额,单位为分
        $data['desc'] = "恭喜你得到一个红包";//企业付款描述信息
        $data['spbill_create_ip'] = '154.8.174.77';//IP地址

        $appsecret = 'i571ln3Ew76bwJyvUKyf0IB2KCqhilGo';

        $data['sign'] = $this->sign($data, $appsecret);
        //接口地址
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";

        //将请求数据由数组转换成xml
        $xml = $this->arraytoxml($data);
        //进行请求操作
        $res = $this->curl($xml, $url);
        //将请求结果由xml转换成数组
        $arr = $this->xmltoarray($res);

        if (is_array($arr)) {
            $arr['total_amount'] = $data['amount'];
        }

        if (is_array($arr)) {
            $arr['partner_trade_no'] = $data['partner_trade_no'];
        }
        //请求信息和请求结果录入到数据库中

        // 输出请求结果数组
        return $arr;
    }

    public function create_rand_money($start = 30, $end = 100)
    {
        return mt_rand($start, $end);
    }

    public function sign($params, $appsecret)
    {
        ksort($params);
        $beSign = array_filter($params, 'strlen');
        $pairs = array();
        foreach ($beSign as $k => $v) {
            $pairs[] = "$k=$v";
        }

        $sign_data = implode('&', $pairs);
        $sign_data .= '&key=' . $appsecret;
        return strtoupper(md5($sign_data));
    }

    /*
     * 生成32位唯一随机字符串
     **/
    private function get_unique_value()
    {
        $str = uniqid(mt_rand(), 1);
        $str = sha1($str);
        return md5($str);
    }

    /*
     * 将数组转换成xml
     **/
    private function arraytoxml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $k => $v) {
            $xml .= "<" . $k . ">" . $v . "</" . $k . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /*
     * 将xml转换成数组
     **/
    private function xmltoarray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $arr = json_decode(json_encode($xmlstring), true);
        return $arr;
    }

    /*
     * 进行curl操作
     **/
    private function curl($param = "", $url) {
        $postUrl = $url;
        $curlPost = $param;
        //初始化curl
        $ch = curl_init();
        //抓取指定网页
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, 1);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //这个是证书的位置
        curl_setopt($ch, CURLOPT_SSLCERT, __DIR__ . '/cert/apiclient_cert.pem');
        //这个也是证书的位置
        curl_setopt($ch, CURLOPT_SSLKEY, __DIR__ . '/cert/apiclient_key.pem');
        //运行curl
        $data = curl_exec($ch);
        //关闭curl
        curl_close($ch);

        return $data;

    }

    public function get_tradeno($str)
    {

        return $str . date("Ymd", time()) . date("His", time()) . rand(1111, 9999);
    }

}