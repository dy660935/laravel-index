<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Base
{
    protected $table='fb_brand';

    public function getHotBrand($brand_id=''){

        if ($brand_id){

            $result=Brand::where(['is_hot'=>1,'brand_status'=>1,'is_deteled'=>1])
                ->whereIn('id', $brand_id)
                ->orderBy('brand_weight','asc')
                ->get(['id','brand_chinese_name','orginal_brand_logo'])
                ->toArray();

        }else{

            $result=Brand::where(['is_hot'=>1,'brand_status'=>1,'is_deteled'=>1])
                ->orderBy('brand_weight','asc')
                ->get(['id','brand_chinese_name','orginal_brand_logo'])
                ->toArray();

        }


        foreach ($result as $k =>$v){

            $result[$k]['orginal_brand_logo']=$this->imageCompatible($v['orginal_brand_logo']);

        }

        if($result){

            return $result;

        }else{

            return [];

        }
    }

    /**
     * @param $brand_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *
     */

    public function getBrand($brand_id){

        if ($brand_id){

            $result=Brand::where(['brand_status'=>1,'is_deteled'=>1])
                ->whereIn('id', $brand_id)
                ->orderBy('brand_weight','asc')
                ->get(['id','brand_chinese_name','orginal_brand_logo'])
                ->toArray();

        }else{

            $result=[];

        }

        if($result){

            foreach ($result as $k =>$v){

                $result[$k]['orginal_brand_logo']=$this->imageCompatible($v['orginal_brand_logo']);

            }

            return $result;

        }else{

            return [];
        }
    }

}
