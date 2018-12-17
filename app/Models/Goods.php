<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Goods extends Base
{
    protected $table = "fb_goods";

    #获取各个商品的最终价格
    public function getPriceByProductId( $productId )
    {
        if( !$productId || !is_array( $productId ) ) return [];
        $productId = implode( ',' , $productId );
        $sql = "select g.id,product_id,g.orignal_website_id,g.is_best_price,g.shop_price,g.shop_id,w.website_name as shop_price_website_name from fb_goods g left join fb_product p on p.id = g.product_id LEFT JOIN fb_website w on w.id=g.orignal_website_id where g.product_id in ({$productId}) and g.is_best_price = 1";
//                        dd($sql);
        $data = DB::select( $sql );


        $data = $this->getArrayByObject( $data );

        $data = $this->getShopWebsiteName( $data );

        return $data;

    }

    /**
     * 获取shop名字
     *
     * @param $data
     */

    public function getShopWebsiteName( $data )
    {

        $web_id = DB::table( 'fb_website' )->where( 'website_abbreviation' , 'jxk' )->select( 'id' )->first();

        $sql = "select shop_name,id from fb_shop ";

        $shop_data = DB::select( $sql );

        $shop_data = $this->getArrayByObject( $shop_data );

        foreach( $data as $k => $v ) {

            if( $v[ 'orignal_website_id' ] == $web_id->id ) {

                foreach( $shop_data as $s_k => $s_v ) {

                    if( $v[ 'shop_id' ] == $s_v[ 'id' ] ) {

                        $data[ $k ][ 'shop_price_website_name' ] = $s_v[ 'shop_name' ];
                    }
                }
            }
        }

        return $data;

    }


    //获取商品落地页
    public function getProductDetailsPage( $product_id , $original_product_id )
    {
        //当前商品的信息
        $goods_message = DB::table( 'fb_goods as a' )
            ->join( 'fb_product as b' , 'a.product_id' , '=' , 'b.id' )
            ->join( 'fb_website as c' , 'c.website_abbreviation' , '=' , 'a.orignal_website' )
            ->join( 'fb_brand as d' , 'b.brand_id' , '=' , 'd.id' )
            ->where( [ 'a.product_id' => $product_id ] )
            ->where( [ 'a.original_goods_id' => $original_product_id ] )
            ->select( 'a.id' , 'good_specs' , 'goods_name' , 'brand_chinese_name' , 'orginal_brand_logo' , 'price_updated_at' , 'shop_price' , 'pay_way' , 'is_local_tax_in' , 'original_product_id'
                , 'website_name' , 'website_thumbnail' , 'duty_free_price' )
            ->first();

        if( $goods_message->pay_way == 1 ) {
            $is_show = 0;
            $price_type = "";
        } else {
            $is_show = 1;
            //$price_type = '免税价';
            $price_type = '到手价';
        }

        $now = time();
        $price_updated_at = strtotime( $goods_message->price_updated_at );

        if( $now - $price_updated_at > 7 * 24 * 3600 ) {
            $price_updated_at = '7天前更新';
        } else {
            $m = date( "m" , $price_updated_at );
            $d = date( "d" , $price_updated_at );
            $price_updated_at = $m . '月' . $d . '日更新';
        }

        $subject_message = $this->getSubjectPrice( $product_id , $goods_message->original_product_id );

        $other_message = $this->getOtherMessage( $product_id , $original_product_id );

        //        dd( $other_message );

        $website_message = $this->isDuty( $goods_message->id );

        $discount_message = $this->getDiscountNumber( $goods_message->shop_price , $subject_message->market_price );

        /** 是否展示market_price */
        if($goods_message->shop_price > $subject_message->market_price){

            $market_price = '';

        }else{
            $market_price = $this->getFinalPrice( $subject_message->market_price );
        }

        #获取微信群图片
        $weChatGroupImage= $this->getWeChatGroupImage();

        $goods_info = [
            'goods_id' => $goods_message->id ,
            'price_updated_at' => $price_updated_at ,
            'product_name' => $goods_message->goods_name . $this->getAttributeValue( $goods_message->good_specs ) ,
            'product_image' => $this->imageCompatible( $subject_message->product_image ) ,
            'website_name' => $website_message[ 'website_name' ] ,
            'website_thumbnail' => $website_message[ 'website_thumbnail' ] ,
            'attribute_value' => $this->getAttributeValue( $goods_message->good_specs ) ,
            'is_show' => $is_show ,
            'price_flag' => $price_type ,
            'shop_price' => '¥' . intval( ceil( $goods_message->shop_price ) ) ,
            'original_product_id' => $goods_message->original_product_id ,
            'market_price' => $market_price ,
            'is_discount' => $discount_message[ 'is_discount' ] ,
            'discount_num' => $discount_message[ 'discount_num' ] ,
            //            'cross_border_tax' => $other_message[ 'cross_border_tax' ] ,
            'original_price' => $other_message[ 'original_price' ] ,
            //            'postage_price' => $other_message[ 'postage_price' ] ,
            'cross' => $other_message[ 'cross' ] ,
            'postage' => $other_message[ 'postage' ] ,
            'trans' => $other_message[ 'trans' ] ,
            'hand_price' => $other_message[ 'hand_price' ] ,
            'place_of_delivery' => $other_message[ 'place_of_delivery' ] ,
            'location' => $other_message[ 'location' ] ,
            'service' => $other_message[ 'service' ] ,
            'is_postage' => $other_message[ 'is_postage' ] ,
            'is_cross' => $other_message[ 'is_cross' ] ,
            'is_trans' => $other_message[ 'is_trans' ] ,
            'brand_chinese_name' => $goods_message->brand_chinese_name ,
//            'orginal_brand_logo' => $this->imageCompatible( $goods_message->orginal_brand_logo ) ,
            'orginal_brand_logo' => $this->getWeChatGroupImage() ,
        ];

        return $goods_info;

    }


    /** 获取落地页信息 */
    public function getOtherMessage( $product_id , $original_goods_id )
    {
        //查出所有的信息
        $priceEntry = Db::table( 'fb_goods as a' )
            ->join( 'fb_website as b' , 'a.orignal_website' , '=' , 'b.website_abbreviation' )
            ->join( 'fb_country as c' , 'b.website_country' , '=' , 'c.id' )
            ->where( 'product_id' , $product_id )
            ->where( 'original_goods_id' , $original_goods_id )
            ->select( 'country' ,'cross_border_tax', 'postage' , 'shop_price' , 'currency_genre' , 'original_price' , 'tax_free_zone'
                , 'delivery_from' ,
                'orignal_website' , 'original_price' , 'pay_way' , 'service' , 'is_postage'
                , 'postage_price' , 'is_cross_border_tax_in' , 'tax_free_zone' , 'is_import_fee_in' , 'import_fee' ,
                'is_local_tax_in' , 'local_tax_in_price' , 'tax_refund_price' , 'duty_free_price' )
            ->first();

        /** pay_way 1国内直邮 2海外直邮 3海淘直邮 4海淘转运  5免税店  6其他 */
        if( $priceEntry->pay_way == 1 ) {
            /** 国内直邮 */
            $otherMessage = $this->chinese( $priceEntry );

        } elseif( $priceEntry->pay_way == 2 ) {
            /** 海外直邮 */
            $otherMessage = $this->haiWai( $priceEntry );
        } elseif( $priceEntry->pay_way == 3 ) {
            /** 海淘直邮 */
            $otherMessage = $this->haiTaoZhi( $priceEntry );

        } elseif( $priceEntry->pay_way == 4 ) {
            /** 海淘转运 */
            $otherMessage = $this->haiTaoZhuan( $priceEntry );

        } else {
            /** 免税店 */
            $otherMessage = $this->mianShuiDian( $priceEntry );
        }


        return $otherMessage;
    }

    /**
     * 国内
     *
     * @param $priceEntry
     *
     * @return mixed
     */
    public function chinese( $priceEntry )
    {

        $price_text = $this->getPriceText( $priceEntry );

        //邮费
        $price_text[ 'is_postage' ] = 1;
        //关税
        $price_text[ 'cross' ][ 'cross_desc' ] = '';
        $price_text[ 'cross' ][ 'cross_border_tax' ] = '';
        $price_text[ 'is_cross' ] = 0;

        //转运费
        $price_text[ 'trans' ][ 'transfer' ] = '';
        $price_text[ 'trans' ][ 'transfer_price' ] = '';
        $price_text[ 'is_trans' ] = 0;

        //服务
        $server = [ [
            'service_des' => '7天无理由退货'
        ] ];
        $price_text[ 'service' ] = array_merge( $price_text[ 'service' ] , $server );

        if( $priceEntry->is_postage == 1 ) {
            $price_text[ 'original_price' ] = '';
            $price_text[ 'postage' ][ 'postage_price' ] = '';
            $price_text[ 'hand_price' ] = '';
        }

        return $price_text;

    }


    /**
     * 海外直邮
     *
     * @param $priceEntry
     *
     * @return mixed
     */
    public function haiWai( $priceEntry )
    {
        /** 海外直邮 */

        $price_text = $this->getPriceText( $priceEntry );

        //邮费
        $price_text[ 'is_postage' ] = 1;

        //关税
        $price_text[ 'is_cross' ] = 1;

        //转运费
        $price_text[ 'trans' ][ 'transfer' ] = '';
        $price_text[ 'trans' ][ 'transfer_price' ] = '';
        $price_text[ 'is_trans' ] = 0;

        //服务
        $server = [ [
            'service_des' => '假一罚十'

        ] , [
            'service_des' => '30天退货'
        ] ];
        $price_text[ 'service' ] = array_merge( $price_text[ 'service' ] , $server );

        if( $priceEntry->is_postage == 1 && $priceEntry->is_cross_border_tax_in == 1 ) {
            /** 包邮含税 */

            /** 税费描述 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '含税';
            /** 税费价格 */
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥0';

        } elseif( $priceEntry->is_postage == 2 && $priceEntry->is_cross_border_tax_in == 1 ) {
            /** 不包邮含税 */

            /** 税费描述 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '含税';
            /** 税费价格 */
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥0';

        } elseif( $priceEntry->is_postage == 1 && $priceEntry->is_cross_border_tax_in == 2 ) {
            /** 包邮不含税 */

            if( $priceEntry->cross_border_tax  == 0 ) {
                /** 税费描述 */
                $price_text[ 'cross' ][ 'cross_desc' ] = '不含税(海关抽检)';
            } else {
                /** 税费描述 */
                $price_text[ 'cross' ][ 'cross_desc' ] = '关税';
            }

            /** 税费价格 */
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥' . intval( ceil( $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->cross_border_tax ) ) );

        } else {
            /** 不包邮不含税 */

            /** 税费 */
            if( $priceEntry->cross_border_tax  == 0 ) {
                /** 税费描述 */
                $price_text[ 'cross' ][ 'cross_desc' ] = '不含税(海关抽检)';
            } else {
                /** 税费描述 */
                $price_text[ 'cross' ][ 'cross_desc' ] = '关税';
            }

            /** 税费价格 */
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥' . intval( ceil( $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->cross_border_tax ) ) );
        }

        return $price_text;
    }

    /**
     * 海淘直邮
     *
     * @param $priceEntry
     *
     * @return mixed
     */
    public function haiTaoZhi( $priceEntry )
    {
        /** 海淘直邮 */

        $price_text = $this->getPriceText( $priceEntry );

        //邮费
        $price_text[ 'is_postage' ] = 1;

        //关税
        $price_text[ 'is_cross' ] = 1;

        //转运费
        $price_text[ 'trans' ][ 'transfer' ] = '';
        $price_text[ 'trans' ][ 'transfer_price' ] = '';
        $price_text[ 'is_trans' ] = 0;

        if( $priceEntry->is_import_fee_in == 2 && $priceEntry->is_postage == 1 ) {
            /** 包邮不含税 */
            if( $priceEntry->import_fee == 0 ) {
                $price_text[ 'cross' ][ 'cross_desc' ] = '不含税(海关抽检)';
            } else {
                $price_text[ 'cross' ][ 'cross_desc' ] = '关税';
            }

        } elseif( $priceEntry->is_import_fee_in == 1 && $priceEntry->is_postage == 1 ) {

            /** 包邮含税 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '含税';
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥0';

            //服务
            $server = [ [
                'service_des' => '14天无理由退货'

            ] ];
            $price_text[ 'service' ] = array_merge( $price_text[ 'service' ] , $server );

        } elseif( $priceEntry->is_import_fee_in == 2 && $priceEntry->is_postage == 2 ) {
            /** 不包邮不含税 */

            if( $priceEntry->import_fee == 0 ) {
                $price_text[ 'cross' ][ 'cross_desc' ] = '不含税(海关抽检)';
            } else {
                $price_text[ 'cross' ][ 'cross_desc' ] = '关税';
            }

        } else {
            /** 不包邮含税 */

            $price_text[ 'cross' ][ 'cross_desc' ] = '含税';
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥0';
        }

        return $price_text;
    }

    /**
     * 海淘转运
     *
     * @param $priceEntry
     *
     * @return mixed
     */
    public function haiTaoZhuan( $priceEntry )
    {
        $price_text = $this->getPriceText( $priceEntry );

        //邮费
        $price_text[ 'is_postage' ] = 1;

        //关税
        $price_text[ 'is_cross' ] = 1;

        //转运
        $price_text[ 'is_trans' ] = 1;

        if( $priceEntry->is_import_fee_in == 2 && $priceEntry->is_postage == 1 ) {
            /** 不包税包邮 */

            $price_text[ 'cross' ][ 'cross_desc' ] = '不含税(海关抽检)';
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥0';

            //            /** 运费描述 */
            //            $price_text[ 'postage' ][ 'postage_desc' ] = $priceEntry->country . '境内' . ' 包邮';

            /** 转运费描述 */
            $price_text[ 'trans' ][ 'transfer' ] = $priceEntry->country . '转运-不包税';


        } elseif( $priceEntry->is_import_fee_in == 2 && $priceEntry->is_postage == 2 ) {

            /** 不含税不包邮 */

            //            /** 运费描述 */
            //            $price_text[ 'postage' ][ 'postage_desc' ] = '送达' . $priceEntry->country;

            /** 税费 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '不含税(海关抽检)';
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥0';

            /** 转运费描述 */
            $price_text[ 'trans' ][ 'transfer' ] = $priceEntry->country . '转运-不包税';


        } elseif( $priceEntry->is_import_fee_in == 1 && $priceEntry->is_postage == 2 ) {
            /** 包税不包邮转运 */

            /** 税费 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '包税转运';
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥0';

            //            /** 运费描述 */
            //            $price_text[ 'postage' ][ 'postage_desc' ] = '送达' . $priceEntry->country;

            /** 转运费描述 */
            $price_text[ 'trans' ][ 'transfer' ] = $priceEntry->country . '转运-包税';

        } else {
            /** 包税包邮转运 */

            /** 税费 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '包税转运';
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥0';

            //            /** 运费描述 */
            //            $price_text[ 'postage' ][ 'postage_desc' ] = $priceEntry->country . '境内' . ' 包邮';

            /** 转运费描述 */
            $price_text[ 'trans' ][ 'transfer' ] = $priceEntry->country . '转运-包税';

        }
        return $price_text;
    }

    /**
     * 免税店
     *
     * @param $priceEntry
     *
     * @return mixed
     */
    public function mianShuiDian( $priceEntry )
    {

        $price_text = $this->getPriceText( $priceEntry );

        //邮费
        $price_text[ 'postage' ][ 'postage_desc' ] = '';
        $price_text[ 'postage' ][ 'postage_price' ] = '';
        $price_text[ 'is_postage' ] = 0;

        //关税
        $price_text[ 'is_cross' ] = 1;

        //转运费
        $price_text[ 'trans' ][ 'transfer' ] = '';
        $price_text[ 'trans' ][ 'transfer_price' ] = '';
        $price_text[ 'is_trans' ] = 0;


        if( $priceEntry->is_local_tax_in == 1 ) {
            /** 含税 */

            //TODO判断是否是退税
            $sale_tax = intval( ceil( $this->currencyCalculation( $priceEntry->currency_genre , $priceEntry->local_tax_in_price ) - $this->currencyCalculation( $priceEntry->currency_genre , $priceEntry->tax_refund_price ) ) );

            /** 税费 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '可退税';
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '-' .'¥' .  $sale_tax;
            /** 原价 */
            $price_text[ 'original_price' ] = '¥' . $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->local_tax_in_price );

            /** 到手价 */
            $price_text[ 'hand_price' ] = '¥' . $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->tax_refund_price );


        } elseif( $priceEntry->is_local_tax_in == 2 && $priceEntry->duty_free_price > 0 ) {

            /** 不含税，免税 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '免税';

            $price_text[ 'cross' ][ 'cross_border_tax' ] = '';

            /** 原价 */
            $price_text[ 'original_price' ] = '';

            /** 到手价 */
            $price_text[ 'hand_price' ] = '';

        } elseif( $priceEntry->is_local_tax_in == 2 && $priceEntry->duty_free_price == 0 &&
            $priceEntry->tax_refund_price > 0 ) {
            /** 不含税，加税 */

            //TODO加税
            $add_sale_tax = intval( ceil( $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->local_tax_in_price ) - $this->currencyCalculation( $priceEntry->currency_genre , $priceEntry->tax_refund_price ) ) );

            /** 税费 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '消费税';
            $price_text[ 'cross' ][ 'cross_border_tax' ] = '¥' . $add_sale_tax;

            /** 原价 */
            $price_text[ 'original_price' ] = '¥' . $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->tax_refund_price );

            /** 到手价 */
            $price_text[ 'hand_price' ] = '¥' . $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->local_tax_in_price );


        } elseif( $priceEntry->is_local_tax_in == 2 && $priceEntry->duty_free_price == 0 &&
            $priceEntry->tax_refund_price == 0 ) {
            /** 会员价 */
            /** 税费 */
            $price_text[ 'cross' ][ 'cross_desc' ] = '免税';

            $price_text[ 'cross' ][ 'cross_border_tax' ] = '';

            /** 原价 */
            $price_text[ 'original_price' ] = '';

            /** 到手价 */
            $price_text[ 'hand_price' ] = '';
        }

        /** 所在地 */
        $price_text[ 'location' ] = $priceEntry->delivery_from . ' ' . $priceEntry->tax_free_zone;
        /** 发货地 */
        $price_text[ 'place_of_delivery' ] = "";

        return $price_text;
    }

    /**
     * 获取价格模板
     *
     * @param $priceEntry
     *
     * @return mixed
     */
    public function getPriceText( $priceEntry )
    {
        /** 原价 */
        $price_text[ 'original_price' ] = '¥' . intval( ceil( $this->currencyCalculation( $priceEntry->currency_genre ,
                $priceEntry->original_price ) ) );
        /** 税费 */
        $price_text[ 'cross' ] = [
            'cross_desc' => '关税' ,
            'cross_border_tax' => '¥' . intval( ceil( $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->import_fee ) ) )
        ];

        /** 邮费 */
        $price_text[ 'postage' ] = [
            'postage_desc' => $priceEntry->postage ,
            'postage_price' => '¥' . intval( ceil( $this->currencyCalculation( $priceEntry->currency_genre ,
                    $priceEntry->postage_price ) ) )
        ];

        /** 转运费 */
        $price_text[ 'trans' ] = [
            'transfer' => '美国转运-不包税' ,
            'transfer_price' => '¥' . intval( ceil( $this->transferPrice( $priceEntry ) ) )
        ];

        /** 到手价 */
        $price_text[ 'hand_price' ] = '¥' . intval( ceil( $priceEntry->shop_price ) );

        /** 发货地 */
        $price_text[ 'place_of_delivery' ] = $priceEntry->delivery_from . ' ' . $priceEntry->tax_free_zone;

        /** 所在地 */
        $price_text[ 'location' ] = '';

        /** 服务 */
        $price_text[ 'service' ] = [ [
            'service_des' => '正品保证' ,

        ] ];

        return $price_text;
    }

    /**
     * 转运费
     *
     * @param $priceEntry
     *
     * @return int
     */
    public function transferPrice( $priceEntry )
    {
        if( $priceEntry->pay_way == 4 ) {
            $original_price = $this->currencyCalculation( $priceEntry->currency_genre , $priceEntry->original_price );
            /** 转运费 原价的10%*/
            $transport = intval( $original_price * 0.095 );

        } else {
            $transport = "";
        }

        return $transport;

    }

    /**
     * 获取最终的shop_price
     *
     * @param $other_message
     *
     * @return string
     */
    public function getFinallyShopPrice( $goods_message , $other_message )
    {
        if( !empty( $other_message[ 'trans' ][ 'transfer_price' ] ) ) {
            $new_transfer_price = $this->getNewPrice( $other_message[ 'trans' ][ 'transfer_price' ] );

            $new_shop_price = '¥' . intval( ceil( $goods_message->shop_price ) + $new_transfer_price );
        } else {

            $new_shop_price = '¥' . intval( ceil( $goods_message->shop_price ) );
        }

        return $new_shop_price;
    }

    /**
     * 获取去除¥的价格
     *
     * @param $price
     *
     * @return string
     */
    public function getNewPrice( $price )
    {
        $new_price = mb_substr( $price , 1 );

        return $new_price;
    }

}
