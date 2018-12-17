<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Base extends Model
{
    /**
     * 图片兼容
     *
     * @param string $imageUrl
     *
     * @return string
     */
    public function imageCompatible( $imageUrl )
    {
        if( empty( $imageUrl ) ) {

            return $imageUrl;

        }
        else {

            $nums = substr( $imageUrl , 0 , 1 );


            $num = substr_count( $imageUrl , 'http' );


            if( $num ) {

                $newImageUrl = $imageUrl;

            }
            else {

                $domain = config( 'api.imgDomain' );

                if( $nums == '/' ) {

                    //                $domain = config( 'app.image_domain' );
                    $newImageUrl = $domain . $imageUrl;

                }
                else {

                    $newImageUrl = $domain . '/' . $imageUrl;
                }


            }
            return $newImageUrl;
        }
    }

    /**
     * 获取偏移量
     *
     * @param string $p
     *
     * @return float|int
     */
    public function getLimit( $p = "" )
    {
        //每页要显示总条数
        $p_num = Config( 'api.p_num' );

        //商品每页要查的条数
        $product_num = $p_num - 1;

        if( empty( $p ) ) {
            $limit = 0;
        }
        elseif( $p % $p_num != 0 ) {
            $limit = ( ( ( ( $p + $p_num ) - ( $p % $p_num ) ) / $p_num ) ) * $product_num;
            if( $limit <= $p ) {
                $limit = ( ( ( ( $p + $p_num ) - ( $p % $p_num ) ) / $p_num ) + 1 ) * $product_num;
            }
        }
        else {

            $limit = ( ( $p + $p_num ) / $p_num - 1 ) * $product_num;
        }

        return $limit;
    }

    /**
     * 无攻略展示10条商品的limit
     *
     * @param string $p
     * @param string $strategy_count
     *
     * @return float|int|string
     */
    public function getTabLimit( $p = "" , $strategy_count = "" )
    {
        //每页要显示总条数
        $p_num = Config( 'api.p_num' );

        if( empty( $p ) ) {
            $limit = 0;
        }
        elseif( $p <= 9 ) {

            if( !empty( $strategy_count ) ) {
                $limit = $p - ( $p_num % 9 );
            }else{
                $limit = $p;
            }
        }
        elseif( $p % 9 == 0 ) {
            if( $p / 9 <= $strategy_count ) {
                $limit = $p - ( $p % 9 );
            }
            else {
                $num = $p / 9 - $strategy_count;
                $limit = $p - ( $p % 9 ) - $num;
            }
        }
        else {
            if( $p / 9 <= $strategy_count ) {
                echo 1;
                $limit = $p - ( $p % 9 );
            }
            else {
                $num = $p % 9 - $strategy_count;

                $limit = $p - ( $p % 9 ) + $num;
            }
        }

        return $limit;
    }

    /**
     * @param $object
     *
     * @return array
     * 将对象转换成数组
     */
    public function getArrayByObject( $object )
    {

        if( !$object || !is_array( $object ) ) return [];

        foreach( $object as $k => $v ) {

            $data[ $k ] = (array) $v;
        }

        return $data;
    }

    /**
     * 获取折扣
     *
     * @param $shop_price
     * @param $market_price
     *
     * @return mixed
     */
    function getDiscountNumber( $shop_price , $market_price )
    {

        $str = ( intval( $shop_price ) / intval( $market_price ) ) * 10;
        $discount = substr( $str , 0 , strpos( $str , '.' ) + 2 );
        if( $discount > 8 ) {
            $dis[ 'is_discount' ] = 0;
            $dis[ 'discount_num' ] = $discount;
        }
        else {
            $dis[ 'is_discount' ] = 1;
            $dis[ 'discount_num' ] = $discount;
        }

        $res = substr( $dis[ 'discount_num' ] , strpos( '.' , $dis[ 'discount_num' ] ) + 2 );

        if( $res == 0 ) {
            $res2 = substr( $dis[ 'discount_num' ] , 0 , strpos( '.' , $dis[ 'discount_num' ] ) + 1 );
            $dis[ 'discount_num' ] = $res2;
        }

        return $dis;
    }


    /**
     *是否为免税店商品
     *
     * @param $goods_id
     *
     * @return mixed
     */
    public function isDuty( $goods_id )
    {
        $res = DB::table( 'fb_goods as a' )
            ->join( 'fb_website as b' , 'a.orignal_website' , '=' , 'b.website_abbreviation' )
            ->leftJoin( 'fb_shop as c' , 'a.shop_id' , '=' , 'c.id' )
            ->where( [ 'a.id' => $goods_id ] )
            ->select( 'pay_way' , 'website_name' , 'website_thumbnail' , 'shop_name' , 'shop_thumbnail' )
            ->first();


        if( $res->pay_way == 5 ) {

            $data[ 'website_name' ] = $res->shop_name;
            $data[ 'website_thumbnail' ] = $res->shop_thumbnail;

        }
        else {

            $data[ 'website_name' ] = $res->website_name;
            $data[ 'website_thumbnail' ] = $res->website_thumbnail;

        }

        return $data;
    }

    /**
     * 获取最终的划线价格
     *
     * @param $price
     *
     * @return string
     */
    public function getFinalPrice( $price )
    {
        if( strlen( intval(  ceil($price) ) ) >= 5 ) {
            return $price;
        }
        else {
            $price = '¥' . intval( ceil($price) );
            return $price;
        }
    }

    /**
     * 获取参考价格（划线价）
     *
     * @param $product_id
     * @param $original_product_id
     *
     * @return Model|null|object|static
     */
    public function getSubjectPrice( $product_id , $original_product_id )
    {
        /** 参考价 */
        $subject_price = Db::table( "fb_product as a" )
            ->join( "fb_goods as b" , 'a.id' , '=' , 'b.product_id' )
            ->where( [ 'product_id' => $product_id ] )
            ->where( [ 'original_goods_id' => $original_product_id ] )
            ->select( 'a.id' , 'market_price' , 'product_name' , 'product_image' , 'good_specs' )
            ->first();

        return $subject_price;
    }

    /**
     * 获取商品规格
     *
     * @param string $good_spec
     *
     * @return mixed|string
     */
    public function getAttributeValue( $good_spec )
    {
        if( !empty( $good_spec ) ) {
            return implode( ',' , json_decode( $good_spec , true ) );
        }
        else {
            return '';
        }
    }

    /**
     * 汇率计算
     *
     * @param $currency_genre
     * @param $price
     *
     * @return int
     */
    public function currencyCalculation( $currency_genre , $price )
    {
        if( $currency_genre != 'CNY' ) {
            $currency_genre = DB::table( 'fb_currency' )
                ->where( [ 'currency_unit' => $currency_genre ] )
                ->select( 'currency_rate' )
                ->first();

            return $currency_genre->currency_rate / 100 * $price;

        }
        else {
            return $price;
        }
    }


    /**
     * 获取随机的微信群图片
     * @return string
     */
    public function getWeChatGroupImage()
    {
        $weChatGroup = Config::get( 'api.weChatGroup' );
        shuffle( $weChatGroup );
        $count = count( $weChatGroup );
        $weChatGroupImage = Config::get( "api.imgDomain" ) . $weChatGroup[ rand( 0 , $count - 1 ) ];

        return $weChatGroupImage;
    }

}
