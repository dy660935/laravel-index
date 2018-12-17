<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Base
{
    protected $table = "fb_product";


    /**
     * 根据搜索条件查商品
     *
     * @param $search_name
     *
     * @return array
     */
    public function sphinxProduct( $search_name , $p )
    {

        $limit = $this->getLimitNum( $p );

        $product_num = Config( 'api.p_num' );

        $sql = "SELECT p.id,p.product_name,p.price_flag,p.product_image,p.original_product_id,g.market_price,w.website_name as market_price_website_name FROM fb_product p LEFT JOIN fb_goods g ON g.product_id = p.id AND g.original_goods_id = p.original_product_id LEFT JOIN fb_website w ON w.id = p.orignal_website_id WHERE is_master=1 AND product_name LIKE '%" . $search_name . "%' ORDER BY product_weight DESC LIMIT $limit,$product_num";

        $product_info = DB::select( $sql );

        if( $product_info ) {

            $product_info = $this->getCommon( $product_info );

            return $product_info;

        } else {
            return [];
        }

    }


    public function getProductInfoByProductId( $productId )
    {

        $productIds = [];

        foreach( $productId as $k => $v ) {

            $productIds[] = $v[ 'id' ];
        }

        $product_ids = implode( ',' , $productIds );

        $sql = "SELECT p.id,p.product_name,p.price_flag,p.product_image,p.original_product_id,g.market_price,w.website_name as market_price_website_name FROM fb_product p LEFT JOIN fb_goods g ON g.product_id = p.id AND g.original_goods_id = p.original_product_id LEFT JOIN fb_website w ON w.id = p.orignal_website_id WHERE is_master=1 AND p.id in ($product_ids) ORDER BY product_weight DESC";

        $product_info = DB::select( $sql );

        if( $product_info ) {

            $product_info = $this->getCommon( $product_info );

            return $product_info;

        } else {

            return [];
        }
    }

    public function getProductInfoByBrandId( $brandId , $p )
    {
        $limit = $this->getLimitNum( $p );

        $product_num = Config( 'api.p_num' );

        $sql = "SELECT p.id,p.product_name,p.price_flag,p.product_image,p.original_product_id,g.market_price,w.website_name as market_price_website_name FROM fb_product p LEFT JOIN fb_goods g ON g.product_id = p.id AND g.original_goods_id = p.original_product_id LEFT JOIN fb_website w ON w.id = p.orignal_website_id WHERE is_master=1 AND brand_id in ($brandId) ORDER BY product_weight DESC LIMIT $limit,$product_num";

        $product_info = DB::select( $sql );

        if( $product_info ) {

            $product_info = $this->getCommon( $product_info );

            return $product_info;

        } else {

            return [];
        }
    }


    /**
     * 获取首页tab分页
     *
     * @param $category_id
     * @param $p
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function getIndexCategoryProduct( $category_id , $p = "" , $is_have_strategy = "" , $strategy_count = "" )
    {

        if( $is_have_strategy == 1 ) {
            $limit = $this->getLimit( $p );

            $product_num = Config( 'api.p_num' ) - 1;
        } else {
            $limit = $this->getTabLimit( $p , $strategy_count );

            $product_num = Config( 'api.p_num' );
        }


        if( $category_id == 0 ) {

            $where = 'product_status=1 and is_master=1 and price_flag in (2,3)';

        } else {

            $where = "product_status=1 and is_master=1 and root_category_id = $category_id and price_flag in (2,3)";

        }

        $sql = "SELECT p.id,p.product_name,p.product_image,p.price_flag,p.original_product_id,g.market_price,w.website_name FROM fb_product p LEFT JOIN fb_goods g ON g.product_id = p.id AND g.original_goods_id = p.original_product_id LEFT JOIN fb_website w on w.id=p.orignal_website_id WHERE $where ORDER BY product_weight DESC LIMIT $limit,$product_num";

        $product_info = DB::select( $sql );

        if( !empty( $product_info ) ) {

            $product_info = $this->getCommon( $product_info );

        } else {

            $product_info = [];

        }

        return $product_info;
    }

    private function getCommon( $product_info )
    {

        foreach( $product_info as $k => $v ) {


            $product_info[ $k ] = (array) $v;

            $product_id[] = $v->id;

            $img = $this->imageCompatible( $v->product_image );

            $product_info[ $k ][ 'product_image' ] = $img;

            $product_info[ $k ][ 'market_price' ] = intval( $v->market_price );

            $product_info[ $k ][ 'is_product' ] = 1;

            if( $v->price_flag == 0 || $v->price_flag == 1 ) {

                $product_info[ $k ][ 'price_type' ] = '';

            } elseif( $v->price_flag == 2 ) {

                $product_info[ $k ][ 'price_type' ] = '国内最低价';

            } else {

                $product_info[ $k ][ 'price_type' ] = '全球最低价';

            }

            //            $attribute_value= json_decode($v->good_specs,true);

            //            $product_info[ $k ][ 'attribute_value' ] = $attribute_value;
        }


        $good_model = new Goods();

        $goods_list = $good_model->getPriceByProductId( $product_id );

        $goods_list = $this->getArrayByObject( $goods_list );

        $product_info = $this->getPrice( $product_info , $goods_list );

        return $product_info;
    }

    /**
     * 处理数组获取价格
     */
    public function getPrice( $product_info , $goods_list )
    {

        foreach( $product_info as $p_k => $p_v ) {

            foreach( $goods_list as $g_k => $g_v ) {

                if( $p_v[ 'id' ] == $g_v[ 'product_id' ] ) {

                    $data = $this->getDiscountNumber( $g_v[ 'shop_price' ] , $p_v[ 'market_price' ] );

                    $product_info[ $p_k ][ 'is_discount' ] = $data[ 'is_discount' ];

                    $product_info[ $p_k ][ 'discount_num' ] = $data[ 'discount_num' ];

                    $product_info[ $p_k ][ 'shop_price_website_name' ] = $g_v[ 'shop_price_website_name' ];
                    $product_info[ $p_k ][ 'website_name' ] = $g_v[ 'shop_price_website_name' ];

                    $product_info[ $p_k ][ 'shop_price' ] = '¥' . intval( ceil( $g_v[ 'shop_price' ] ) );

                    if( $g_v[ 'shop_price' ] > $p_v[ 'market_price' ] || strlen( trim($p_v[ 'market_price' ] )) > 5 ) {

                        $product_info[ $p_k ][ 'market_price' ] = '';

                    } else {

                        $product_info[ $p_k ][ 'market_price' ] = '¥' . intval( ceil( $p_v[ 'market_price' ] ) );

                    }
                }
            }
        }

        return $product_info;

    }


    /**
     * 商品详情
     *
     * @param $product_id
     *
     * @return array
     */
    public function productInfoFind( $product_id )
    {
        //最低价
        $lower_message = DB::table( 'fb_product as a' )
            ->join( 'fb_goods as b' , 'a.id' , '=' , 'b.product_id' )
            ->join( 'fb_website as c' , 'b.orignal_website' , '=' , 'c.website_abbreviation' )
            ->join( 'fb_brand as d' , 'a.brand_id' , '=' , 'd.id' )
            ->where( [ 'product_id' => $product_id ] )
            ->where( [ 'is_best_price' => 1 ] )
            ->select( 'a.id' , 'price_flag' , 'shop_price' , 'brand_chinese_name' , 'orginal_brand_logo' , 'original_product_id' ,
                'original_goods_id' , 'website_name' , 'pay_way' )
            ->first();

        #价格标签
        if( $lower_message->price_flag == 0 || $lower_message->price_flag == 1 ) {
            $price_flag = "";
        } elseif( $lower_message->price_flag == 2 ) {
            $price_flag = "国内最低价";
        } else {
            $price_flag = "全球最低价";
        }
        /** 参考价 */
        $subject_price = $this->getSubjectPrice( $product_id , $lower_message->original_product_id );

        $website_message = $this->getWebsiteMessage( $product_id );

        //是否打折
        $discount_message = $this->getDiscountNumber( $lower_message->shop_price , $subject_price->market_price );

        /** 是否展示market_price */
        if( $lower_message->shop_price > $subject_price->market_price ) {

            $market_price = '';

        } else {
            $market_price = $this->getFinalPrice( $subject_price->market_price );
        }

        #获取微信群图片
        $weChatGroupImage = $this->getWeChatGroupImage();

        //拼接数据
        $product_info = [
            'product_id' => $lower_message->id ,
            'product_name' => $subject_price->product_name . $this->getAttributeValue( $subject_price->good_specs ) ,
            'product_image' => $this->imageCompatible( $subject_price->product_image ) ,
            'original_product_id' => $lower_message->original_product_id ,
            'shop_price' => '¥' . intval( ceil( $lower_message->shop_price ) ) ,
            'market_price' => $market_price ,
            'website_name' => $lower_message->website_name ,
            'attribute_value' => $this->getAttributeValue( $subject_price->good_specs ) ,
            'is_discount' => $discount_message[ 'is_discount' ] ,
            'discount_num' => $discount_message[ 'discount_num' ] ,
            'price_type' => $price_flag ,
            'price' => $website_message[ 'price' ] ,
            'other_price' => $website_message[ 'other_price' ] ,
            'price_count' => $website_message[ 'price_count' ] ,
            'price_number' => $website_message[ 'price_number' ] ,
            'brand_chinese_name' => $lower_message->brand_chinese_name ,
            //            'orginal_brand_logo' => $this->imageCompatible( $lower_message->orginal_brand_logo ) ,
            'orginal_brand_logo' => $weChatGroupImage ,
        ];

        return $product_info;

    }

    /**
     * 获取网站信息
     *
     * @param $product_id
     *
     * @return mixed
     */
    public function getWebsiteMessage( $product_id )
    {

        /** 全球最低价 */
        $lower_message = $this->getCommonPrice( $product_id );

        #价格标签
        $duty_price_type = "免税店最低价";
        $sea_price_type = "海淘最低价";
        $china_price_type = "国内最低价";

        /*
                price_flag == 1

                $lower_message 去掉全球最低价

                price_flag == 2

                $lower_message 替换为国内最低价，只处理other_price的情况

                price_flag == 3

                按旧有逻辑处理

                */

        if( $lower_message->price_flag == 0 || $lower_message->price_flag == 1 ) {

            $lower_price_type = "";

        } elseif( $lower_message->price_flag == 2 ) {

            $lower_price_type = "国内最低价";

        } else {

            $lower_price_type = "全球最低价";
        }

        #主价格
        $data[ 'price' ][] = $this->getWebsitePrice( $lower_message , $lower_price_type );

        //price_flag=3全球最低价需要查询免税店最低价和国内最低价和海淘最低价
        if( $lower_message->price_flag == 3 ) {
            #通过购买方式判断其他价格
            if( $lower_message->pay_way == 1 || $lower_message->pay_way == 2 ) {
                /** 免税店最低价 */
                $duty_where = [ 5 , 6 ];
                $duty_message = $this->getCommonPrice( $product_id , $duty_where );

                $data[ 'price' ][] = $this->getWebsitePrice( $duty_message , $duty_price_type );

                /** 海淘最低价 */
                $sea_where = [ 3 , 4 ];
                $sea_message = $this->getCommonPrice( $product_id , $sea_where );

                $data[ 'price' ][] = $this->getWebsitePrice( $sea_message , $sea_price_type );

            } elseif( $lower_message->pay_way == 3 || $lower_message->pay_way == 4 ) {

                /** 国内最低价 */
                $china_where = [ 1 , 2 ];
                $china_message = $this->getCommonPrice( $product_id , $china_where );
                $data[ 'price' ][] = $this->getWebsitePrice( $china_message , $china_price_type );

                /** 免税店最低价 */
                $duty_where = [ 5 , 6 ];
                $duty_message = $this->getCommonPrice( $product_id , $duty_where );


                $data[ 'price' ][] = $this->getWebsitePrice( $duty_message , $duty_price_type );

            } else {

                /** 国内最低价 */
                $china_where = [ 1 , 2 ];
                $china_message = $this->getCommonPrice( $product_id , $china_where );
                $data[ 'price' ][] = $this->getWebsitePrice( $china_message , $china_price_type );

                /** 海淘最低价 */
                $sea_where = [ 3 , 4 ];
                $sea_message = $this->getCommonPrice( $product_id , $sea_where );
                $data[ 'price' ][] = $this->getWebsitePrice( $sea_message , $sea_price_type );

            }
        }

        //国内最低价或全球最低价需要获取other_price
        if( $lower_message->price_flag == 2 || $lower_message->price_flag == 3 ) {
            $goods_id[] = $lower_message->goods_id;

            if( isset( $sea_message ) && !empty( $sea_message ) ) {
                $goods_id[] = $sea_message->goods_id;
            }
            if( isset( $china_message ) && !empty( $china_message ) ) {
                $goods_id[] = $china_message->goods_id;
            }
            if( isset( $duty_message ) && !empty( $duty_message ) ) {
                $goods_id[] = $duty_message->goods_id;
            }

            $goods_id = array_unique( $goods_id );


            foreach( $data[ 'price' ] as $k => $v ) {
                if( $v[ 'pay_way' ] != 5 ) {
                    $website_id[] = $v[ 'website_id' ];
                } else {
                    $website_id[] = '';
                }
            }


            $website_id = array_filter( $website_id );


            if( $website_id != "" ) {
                $other_message = $this->getOtherWebsitePrice( $product_id , $goods_id , $website_id );
            } else {
                $other_message = $this->getOtherWebsitePrice( $product_id , $goods_id );
            }

            //        dd( $other_message );
            if( !empty( $other_message ) ) {

                foreach( $other_message as $k => $v ) {
                    $data[ 'other_price' ][] = $this->getWebsitePrice( $v );
                }

            } else {

                $data[ 'other_price' ] = [];
            }

        } else {

            $data[ 'other_price' ] = [];
        }

        //计算有价格的条数
        $price_count = 0;
        $price_number = 0;
        foreach( $data[ 'price' ] as $k => $v ) {
            if( !empty( $v[ 'shop_price' ] ) ) {
                $price_count += 1;
                $price_number += 1;
            }
        }
        if( isset( $data[ 'other_price' ] ) ) {
            $count_all = $price_count + count( $data[ 'other_price' ] );
        } else {
            $count_all = $price_count;
        }

        $data[ 'price_count' ] = $count_all;
        $data[ 'price_number' ] = $price_number;

        return $data;

    }

    /**
     * 获取最低价
     *
     * @param $product_id
     * @param array $where
     *
     * @return Model|null|object|static
     */
    public function getCommonPrice( $product_id , $where = [] )
    {
        if( empty( $where ) ) {
            $price = DB::table( "fb_product as a" )
                ->join( "fb_goods as b" , 'a.id' , '=' , 'b.product_id' )
                ->join( 'fb_website as c' , 'b.orignal_website' , '=' , 'c.website_abbreviation' )
                ->where( [ 'product_id' => $product_id ] )
                ->where( [ 'is_best_price' => 1 ] )
                ->where( [ 'goods_status' => 1 ] )
                ->select( 'a.id' , 'price_flag' , 'b.id as goods_id' , 'c.id as website_id' , 'shop_price' , 'pay_way' ,
                    'original_goods_id' , 'website_thumbnail' , 'website_name' )
                ->first();
        } else {
            $price = Db::table( "fb_product as a" )
                ->join( "fb_goods as b" , 'a.id' , '=' , 'b.product_id' )
                ->join( 'fb_website as c' , 'b.orignal_website' , '=' , 'c.website_abbreviation' )
                ->where( [ 'product_id' => $product_id ] )
                ->whereIn( 'c.pay_way' , $where )
                ->where( [ 'goods_status' => 1 ] )
                ->select( 'a.id' , 'price_flag' , 'b.id as goods_id' , 'c.id as website_id' , 'shop_price' , 'pay_way' , 'original_goods_id' , 'website_thumbnail' , 'website_name' )
                ->orderBy( 'shop_price' , 'asc' )
                ->first();

        }

        return $price;
    }

    /**
     * 处理网站价格
     *
     * @param $message
     * @param string $price_type
     *
     * @return array
     */
    public function getWebsitePrice( $message , $price_type = "" )
    {
        if( empty( $message ) ) {
            $message = [
                'product_id' => '' ,
                'original_product_id' => '' ,
                'website_thumbnail' => '' ,
                'website_name' => '' ,
                'shop_price' => '' ,
                'pay_way' => '' ,
                'website_id' => '' ,
                'price_type' => $price_type
            ];
        } else {

            $is_shop = $this->isDuty( $message->goods_id );

            $message = [
                'product_id' => $message->id ,
                'original_product_id' => $message->original_goods_id ,
                'website_thumbnail' => $is_shop[ 'website_thumbnail' ] ,
                'website_name' => $is_shop[ 'website_name' ] ,
                'shop_price' => '¥' . intval( ceil( $message->shop_price ) ) ,
                'pay_way' => $message->pay_way ,
                'website_id' => $message->website_id ,
                'price_type' => $price_type
            ];
        }

        return $message;
    }

    /**
     * 获取其他网站价格信息
     *
     * @param $product_id
     * @param $goods_id
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOtherWebsitePrice( $product_id , $goods_id , $website_id = "" )
    {

        //                var_dump($product_id,$goods_id,$website_id);
        //                $website_id = array( 23 );
        //        DB::connection()->enableQueryLog();
        $price1 = Db::table( "fb_product as a" )
            ->join( "fb_goods as b" , 'a.id' , '=' , 'b.product_id' )
            ->join( 'fb_website as c' , 'b.orignal_website' , '=' , 'c.website_abbreviation' )
            ->where( [ 'product_id' => $product_id ] )
            ->where( 'goods_status' , '=' , 1 )
            ->where( 'c.pay_way' , '=' , 5 )
            ->whereNotIn( "c.id" , $website_id )
            ->whereNotIn( 'b.id' , $goods_id )
            ->select( 'a.id' , 'b.id as goods_id' , 'c.id as website_id' , 'shop_price' , 'pay_way' , 'original_goods_id'
                , 'website_thumbnail' , 'website_name' )
            ->orderBy( 'shop_price' , 'asc' )
            ->get();

        //        $queries = DB::getQueryLog();
        //        dd($queries);
        $goods_id = implode( ',' , $goods_id );
        $website_id = implode( ',' , $website_id );

        if( !empty( $website_id ) ) {
            $sql = " SELECT * FROM (SELECT a.id,b.id as goods_id,c.id as website_id,shop_price,pay_way,original_goods_id,
 website_thumbnail,website_name FROM fb_product AS a JOIN fb_goods AS b ON a.id = b.product_id JOIN fb_website AS c ON b.orignal_website = c.website_abbreviation WHERE product_id = $product_id AND goods_status = 1 AND c.pay_way != 5 AND c.id NOT IN ($website_id) AND b.id NOT IN ($goods_id) ORDER BY shop_price ASC) as r GROUP BY r.website_id";
            //        dd($sql);

        } else {
            $sql = " SELECT * FROM (SELECT a.id,b.id as goods_id,c.id as website_id,shop_price,pay_way,original_goods_id,
 website_thumbnail,website_name FROM fb_product AS a JOIN fb_goods AS b ON a.id = b.product_id JOIN fb_website AS c ON b.orignal_website = c.website_abbreviation WHERE product_id = $product_id AND goods_status = 1 AND c.pay_way != 5  AND b.id NOT IN ($goods_id) ORDER BY shop_price ASC) as r GROUP BY r.website_id";
            //        dd($sql);
        }

        $price2 = DB::select( $sql );


        if( $price1 ) {
            $price1 = $price1->toArray();
        } else {
            $price1 = [];
        }

        if( $price2 ) {
            $price2 = $price2;
        } else {
            $price2 = [];
        }

        if( !empty( $price2 ) && !empty( $price1 ) ) {
            $price = array_merge( $price1 , $price2 );
        } elseif( !empty( $price1 ) && empty( $price2 ) ) {
            $price = $price1;
        } elseif( empty( $price1 ) && !empty( $price2 ) ) {
            $price = $price2;
        } else {
            $price = [];
        }

        array_multisort( array_column( $price , 'shop_price' ) , SORT_ASC , $price );

        if( $price ) {
            return $price;
        } else {
            return [];
        }
    }


    /*
     * 获取商品的品牌id
     */
    public function getProductAll( $where = '' )
    {
        if( $where ) {

            $result = Product::where( [ 'product_status' => 1 , 'is_master' => 1 ] )
                ->where( $where )
                ->pluck( 'brand_id' )->toArray();

        } else {

            $result = Product::where( [ 'product_status' => 1 , 'is_master' => 1 ] )
                ->pluck( 'brand_id' )->toArray();
        }


        if( empty( $result ) ) {

            return [];

        } else {

            $result = array_unique( $result );

            //            $result = implode(',', $result);

            return $result;
        }
    }

    /**
     * 搜索limit
     *
     * @param string $p
     *
     * @return float|int
     */
    public function getLimitNum( $p = "" )
    {
        //每页要显示总条数
        $p_num = Config( 'api.p_num' );

        //商品每页要查的条数
        $product_num = $p_num;

        if( empty( $p ) ) {

            $limit = 0;

        } else {

            $p = intval( $p );

            $num = ceil( $p / $product_num );

            $num = intval( $num );

            $limit = $num * $product_num;

        }

        return $limit;
    }


}
