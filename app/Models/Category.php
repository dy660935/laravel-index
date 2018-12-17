<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table='fb_category';

    public function getIndexCategoryInfo($category_name="")
    {
        $category_info = Category::where(['category_status' => 1, 'category_level' =>1])->take(10)->get(['id','category_name'])->toArray();

        if($category_name){

            array_unshift($category_info, $category_name);

        }

        return $category_info;
    }

    /**
     * 通过分类的id获取品牌的id
     * @param $id
     * @return array|bool
     */
    public function getBrandInByCategoryId($id){

        $brand_ids=Category::where('id',$id)->value('brand_ids');

        if($brand_ids){

            $brand_ids=explode(',',$brand_ids);

        }else{

            $brand_ids=[];

        }

        return $brand_ids;
    }

}
