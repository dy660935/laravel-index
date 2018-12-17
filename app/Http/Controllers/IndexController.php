<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Strategy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Libs\sphinx\SphinxClient;

class IndexController extends CommonController
{
    //

    public function index( Request $request )
    {

        $session = $request->post( 'session' , '' );

        $strategy_info = $this->getStrategyInfo();

        $brand_info = $this->getBrandInfo();

        $category_info = $this->getCategoryInfo();

        $strategy_model = new Strategy();

        $strategy = $strategy_model->getIndexStrategy();

        if( !empty( $strategy ) ) {
            $is_have_strategy = 1;
            $hot_info = $this->getHotInfo( $is_have_strategy );

        }
        else {

            $is_have_strategy = 0;
            $hot_info = $this->getHotInfo( $is_have_strategy );
        }


        //获取用户是否点击过复制
        $brand_info = $this->getUserRed( $session , $brand_info );

        if( !empty( $hot_info ) && !empty( $strategy ) ) {
            $strategy[ 'is_product' ] = 2;
            array_push( $hot_info , $strategy );
        }
        elseif(!empty( $hot_info )) {
            $hot_info = $hot_info;

        }else{

            $hot_info = [];

        }

        $json_data = [
            'strategy_info' => $strategy_info ,
            'brand_info' => $brand_info ,
            'category_info' => $category_info ,
            'hot_info' => $hot_info
        ];


        return $this->successCode( $json_data , '' , 1 );

    }

    /**
     * 好友领红包接口
     * @return array
     */
    public function getRedPackage()
    {
        $share_only = request()->post( 'share_only' , '' );
        if( empty( $share_only ) ) {

            $is_show = '';

        }
        else {

            $show_info = DB::table( 'fb_user' )
                ->where( [ 'user_only_num' => $share_only ] )
                ->select( 'user_avatar' , 'user_define_nickname' )
                ->first();
            if( !empty( $show_info ) ) {
                $is_show = [
                    'getAvatar' => $show_info->user_avatar ,
                    'nickName' => $show_info->user_define_nickname ,
                    'content' => '送你一只比价鸭，加它比价有红包'
                ];
            }
            else {

                $is_show = '';

            }
        }

        return $this->successCode( $is_show , '' , 200 );
    }

    //攻略轮播图
    public function getStrategyInfo()
    {
        $strategyModel = new Strategy();

        return $strategyModel->getSlider();
    }

    //品牌比价、购物攻略、现金红包
    public function getBrandInfo()
    {
        $brand_info = Config( 'api.brand_message' );

        foreach( $brand_info as $k => $v ) {

            $brand_info[ $k ][ 'brand_img' ] = Config( 'api.imgDomain' ) . $v[ 'brand_img' ];

        }

        return $brand_info;
    }

    /**
     * 获取分类名
     * @return array
     */
    public function getCategoryInfo()
    {
        $category_model = new Category();

        $category_name = Config( 'api.category_name' );

        return $category_model->getIndexCategoryInfo( $category_name );
    }

    /**
     * 获取首页热卖商品
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHotInfo( $is_have_strategy = "" )
    {
        $product_model = new Product();

        $hot_product = $product_model->getIndexCategoryProduct( 0 , '' , $is_have_strategy );

        return $hot_product;
    }


    public function getUserRed( $session , $brand_info )
    {

        if( !empty( $session ) ) {
            //获取用户的user_id
            $open_id = $this->getOpenId( $session );

            $user_model = new User();

            $user_id = $user_model->getUserId( $open_id );

            if( $user_id != false && !empty( $user_id ) ) {

                $redPackage = $this->getUserRedStatus( $user_id );

                foreach( $brand_info as $k => $v ) {

                    if( $v[ 'brand_name' ] == '现金红包' ) {

                        if( $redPackage[ 'is_copy' ] == 1 ) {

                            $brand_info[ $k ][ 'is_have' ] = 1;

                        }
                        else {

                            $brand_info[ $k ][ 'is_have' ] = 0;

                        }
                    }
                }

            }

        }
        else {

            foreach( $brand_info as $k => $v ) {

                if( $v[ 'brand_name' ] == '现金红包' ) {

                    $brand_info[ $k ][ 'is_have' ] = 1;

                }
            }
        }

        return $brand_info;
    }

    /**
     * 获取用户的红包状态
     *
     * @param $user_id
     *
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserRedStatus( $user_id )
    {

        $red_data = DB::table( 'fb_bonus' )
            ->where( [ 'user_id' => $user_id ] )
            ->first( [ 'is_copy' , 'red_envelopes_money' ]);
        if( !empty( $red_data ) ) {

            $red_data=get_object_vars($red_data);

            return $red_data;

        }
        else {
            $now = date( "Y-m-d H:i:s" , time() );

            $insertData = [
                'user_id' => $user_id ,
                'is_copy' => 1 ,
                'red_envelopes_status' => 1 ,
                'created_at' => $now
            ];
            DB::table( 'fb_bonus' )->insert( $insertData );
            return false;
        }
    }

    /**
     * 首页分类的tab选项卡
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productInfo( Request $request )
    {
        $category_id = $request->post( "category_id" , '' );

        $p = $request->post( "p" , '' );

        if( $category_id == "" ) {

            return $this->errorCode( '参数有误' , '' , 1000 );

        }
        else {

            $strategy_model = new Strategy();
            $strategy_all = $strategy_model->getIndexTabStrategyInfo( $category_id , $p );

            $strategy_info = $strategy_all[ 'strategy_info' ];
            $strategy_count = $strategy_all[ 'strategy_count' ];

            if( !empty( $strategy_info ) ) {

                $is_have_strategy = 1;
                $product_model = new Product();
                $product_info = $product_model->getIndexCategoryProduct( $category_id , $p , $is_have_strategy ,
                    $strategy_count );

            }
            else {

                $is_have_strategy = 0;
                $product_model = new Product();
                $product_info = $product_model->getIndexCategoryProduct( $category_id , $p , $is_have_strategy , $strategy_count );
            }


            if( !empty( $product_info ) ) {

                if( !empty( $product_info ) && !empty( $strategy_info ) ) {
                    $strategy_info[ 'is_product' ] = 2;
                    array_push( $product_info , $strategy_info );
                }
            }
            else {
                $product_info = [];
            }

            if( !empty( $product_info ) ) {
                return $this->successCode( $product_info , '成功' , 1 );
            }
            else {
                return $this->errorCode( '没有更多数据了' , '' , 1000 );
            }
        }
    }

    /**
     * 现金红包
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashRedEnvelope( Request $request )
    {
        $session = $request->post( 'session' );

        if( empty( $session ) ) {
            return $this->errorCode( '参数有误' , '' , 1000 );
        }

        $user_model = new User();

        //获取用户的$open_id
        $open_id = $this->getOpenId( $session );

        $user_id = $user_model->getUserId( $open_id );

        //        $user_id = 6;
        if( $user_id != false && !empty( $user_id ) ) {
            $this->getUserRedStatus( $user_id );
        }


        $img = Config( 'api.imgDomain' );
        $data = Config( 'api.own_config' );
        $data[ 'head_img' ] = $img . $data[ 'index_img' ];
        $data[ 'vipServerTag' ] = $img . $data[ 'vipServerTag' ];
        $data[ 'moneyBg' ] = $img . $data[ 'moneyBg' ];
        $data[ 'moneyBgCopy' ] = $img . $data[ 'moneyBgCopy' ];
        $data[ 'getMoney' ] = $img . $data[ 'getMoney' ];
        $data[ 'wechat_num' ] = $data[ 'wechat_num' ];
        $data[ 'wechat' ] = $data[ 'wechat' ];
        $data[ 'wechat_img' ] = $img . $data[ 'wechat_img' ];
        $data[ 'wechat_cha_img' ] = $img . $data[ 'wechat_cha_img' ];
        $data[ 'wechat_qian_img' ] = $img . $data[ 'wechat_qian_img' ];
        $data[ 'wechat_hou_img' ] = $img . $data[ 'wechat_hou_img' ];


        return $this->successCode( $data , '' , 1 );

    }

    /**
     * 首页现金红包一键复制
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userCopy( Request $request )
    {

        $session = $request->post( 'session' );

        //        获取用户的user_id
        $open_id = $this->getOpenId( $session );

        $user_model = new User();

        $user_id = $user_model->getUserId( $open_id );

        if( $user_id ) {

            DB::table( 'fb_bonus' )
                ->where( 'user_id' , $user_id )
                ->update( [ 'is_copy' => 0 ] );

            return $this->successCode( '' , '' , 1 );

        }
        else {

            return $this->errorCode( '' , '' , 1000 );
        }

    }

}
