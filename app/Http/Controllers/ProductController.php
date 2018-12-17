<?php

namespace App\Http\Controllers;

use App\Models\Goods;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends CommonController
{
    /**
     * 商品详情
     * @param Request $request
     *
     * @return array
     */
    public function productDetail( Request $request )
    {
        $product_id = $request->post( 'product_id' );

        if( empty( $product_id ) ) {

            return $this->errorCode( '参数有误' , '' , 1000 );

        }
        else {

            $product_model = new Product();

            $product_info = $product_model->productInfoFind( $product_id );

            return $this->successCode( $product_info , '' , 200 );
        }
    }

    /**
     * 商品落地页
     * @param Request $request
     *
     * @return array
     */
    public function productDetailsPage( Request $request )
    {

        $product_id = $request->post( "product_id" );

        $original_product_id = $request->post( "original_product_id" );

        if( empty( $product_id ) || empty( $original_product_id ) ) {
            return $this->errorCode( '参数有误' , '' , 1000 );

        }

        $goods_model = new Goods();

        $product_info = $goods_model->getProductDetailsPage( $product_id , $original_product_id );

        return $this->successCode( $product_info , '' , 200 );
    }
}
