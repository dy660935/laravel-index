<?php
return [
    //线上环境
    'onlineEnvironment' =>[
        'test' => 'http://xcx.qqzdj.com.cn',
        'formal' => 'http://testxcx.qqzdj.com.cn'
    ],
    //图片域名
    'imgDomain' => 'http://six.qqzdj.com.cn',
    //每页展示条数
    'p_num' => 10,
    //首页图片
    'brand_message' => [
        [
        'brand_name' => '品牌分类',
        'brand_img' => '/config/HomeBestPrice2.png',
        'url' => '/pages/classify/classify'],
        [
        'brand_name' => '热门攻略',
        'brand_img' => '/config/HomeBestTopic2.png',
        'url' => '/pages/strategy_index/strategy_index'],
        [
        'brand_name' => '现金红包',
        'brand_img' => '/config/HomeCash2.png',
        'url' => '/pages/user/user']
    ],
    'category_name' =>[
        'id'=> 0,
        'category_name' => '热门'
    ],
    'category_index' => [
        'id'=>0,
        'category_name'=>'热门品牌'
    ],
    'bonus_max_num' =>18.88,
    'bonus_one_max_num' =>0.88,
    'bonus_one_min_num' =>0.3,
    'bonus_first_num' =>0.36,
    'bonus_num'=>[
        0=>0.11,
        1=>0.17,
        2=>0.18,
        3=>0.20,
        4=>0.22,
        5=>0.23,
        6=>0.33,
        7=>0.52,
        8=>0.66,
        9=>0.88,
    ],
    'own_config'=>[
        #我的详情红包头像
        'head_img'=>'/config/hongbao2.png',
        #我的详情页面的字
        'own_one'=>'您有红包',
        'own_two'=>'元待领取',
        'own_three'=>'邀请好友, 立即提现',
        #我的页面邀请的信息
        'share_title'=>'帮你找到iPhone Max全球最低价，比国内省一半？',
        'share_img'=>'/config/share.png',

        #首页鸭子
        'index_img'=>'/config/headImg.png',

        'user_name'=>'智能比价鸭',
        'vipServerTag'=>'/config/VipServiceTag.png',
        'red_message'=>'[现金红包]',
        'contents'=>'带我回家，帮你比价',
        'moneyBg'=>'/config/MoneyBg.png',
        'moneyBgCopy'=>'/config/MoneyBgCopy.png',
        'getMoney'=>'/config/GetMoney.png',
        'chatInfo'=>'添加比价鸭微信，随时查到最低价',
        'chatInfo_two'=>'现在添加，送专属红包哦',
        'chatCopy'=>'点击一键复制',
        'wechat_num'=>'Haimianing',
        'wechat'=>'微信',
        'wechat_img'=>'/config/go.png',
        'wechat_cha_img'=>'/config/cha.png',
        'wechat_qian_img'=>'/config/qian.png',
        'wechat_hou_img'=>'/config/hou.png',
    ],
    'sphinx' => [
        //development
        'host' => env('SPHINX_HOST'),//sphinx服务ip
        'enable' => env('SPHINX_ENABLE')//是否启用sphinx 1=启用 0=不启用
        //online
//        'host' => '127.0.0.1',//sphinx服务ip
//        'enable' => 0//是否启用sphinx 1=启用 0=不启用
    ],
    'weChatGroup' =>[
        '/weChatGroup/ava01.png',
        '/weChatGroup/ava02.png',
        '/weChatGroup/ava03.png',
        '/weChatGroup/ava04.png',
        '/weChatGroup/ava05.png',
        '/weChatGroup/ava06.png',
        '/weChatGroup/ava07.png',
        '/weChatGroup/ava08.png',
    ],
];
