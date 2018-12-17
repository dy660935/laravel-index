<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends CommonController
{
    /**
     * 分类首页接口
     */
    public function categoryIndex()
    {
//        $productModel = new Product();

//        $res = $productModel->getProductAll();

        $categoryModel = new Category();

        $category_name = Config('api.category_index');

        $categoryInfo=$categoryModel->getIndexCategoryInfo($category_name);

        $brandModel = new Brand();

//        if($res){
//
//            $hotBrand=$brandModel->getHotBrand($res);
//
//        }else{

            $hotBrand=$brandModel->getHotBrand();

//        }

        $json_data = [
            'category_info' => $categoryInfo,
            'hot_brand_info' => $hotBrand
        ];


        if($json_data){

            return $this->successCode($json_data,'查询成功',1);

        }else{

            return $this->errorCode('','暂时没有哦','1000');
        }

    }

    /**
     * 分类下品牌接口
     */
    public function categoryBrand(){

        $category_id = request()->post('id');

//        $productModel = new Product();

        $brandModel = new Brand();

        $categoryModel = new Category();

        if($category_id==0){

//            $res = $productModel ->getProductAll();

            $brand_category_info=$brandModel->getHotBrand();

        }else{

            $brand_id=$categoryModel->getBrandInByCategoryId($category_id);

            $brand_category_info=$brandModel->getBrand($brand_id);

        }

        return $this->successCode($brand_category_info,'查询成功',1);
    }
}
