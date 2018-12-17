<?php

namespace App\Http\Controllers;

use App\Libs\sphinx\SphinxClient;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{
    /**
     * 提示错误的方法
     * @param string $msg
     * @param array $data
     * @param int $status
     * @return array
     */
    public function errorCode($msg='fail',$data=[],$status=1)
    {
        $arr=[
            'status_code'=>$status,
            'message'=>$msg,
            'data'=>$data
        ];

        return response()->json($arr);

    }


    /**
     * 提示成功的方法
     * @param array $data
     * @param string $msg
     * @param int $status
     * @return array
     */
    public function successCode($data=[],$msg='',$status=1000)
    {
        $arr= [
            'status_code'=>$status,
            'message'=>$msg,
            'data'=>$data
        ];
        return response()->json($arr);
    }

    /**
     * @param $object
     * @return array
     * 将对象转换成数组
     */
    public function getArrayByObject($object){

        if(!$object || !is_array($object)) return [];

        foreach($object as $k=>$v) {

            $data[$k] = (array)$v;
        }

        return $data;
    }

    /**
     * 搜索主页
     */
    public function searchIndex()
    {
        //热门搜索品牌

        $sql = "SELECT id,brand_chinese_name FROM fb_brand WHERE brand_status = 1 ORDER BY  brand_weight desc LIMIT 10 ";

        $hot_search_brand = DB::select($sql);

        $hot_search_brand=$this->getArrayByObject($hot_search_brand);

        return $this -> successCode($hot_search_brand,'success',1);

    }

    /**
     * 搜索结果页接口
     */
    public function searchResult(Request $request){

        $search_name = $request->post('search_name');

        $p = $request->post( "p" , '' );

        if(empty($search_name)){

            return $this -> errorCode('请输入关键字','',1000);

        }else{

            return $this -> getSearchInfo($search_name ,$p);

        }
    }


    public function getSearchInfo($search_name ,$p){

        $where = 1;

        if(Config('api.sphinx.enable')) {

            $brand_info = $this->_sphinxBrand($search_name);


        } else {

            $where .= " and brand_chinese_name like '%$search_name%' or brand_english_name like '%$search_name%'";

            $sql=" select id from fb_brand where ".$where;

            $brand_info = DB::select($sql);

            $brand_info=$this->getArrayByObject($brand_info);

        }

        $product_model = new Product();

        if(empty($brand_info)) {

            if(Config('api.sphinx.enable')) {

                $limit=$this->getLimitNum($p);

                $product_data = $this->_sphinxProduct($search_name,$limit);

                if($product_data){

                    $product_info=$product_model->getProductInfoByProductId($product_data);

                }else{

                    $product_info=[];
                }

            } else {

                $product_info=$product_model->sphinxProduct($search_name ,$p);

            }

        } else {

            $str = "";

            foreach ($brand_info as $k => $v) {

                $str .= $v['id'] . ',';
            }

            $str = trim($str, ',');

            $product_info=$product_model->getProductInfoByBrandId($str,$p);

        }


        if(empty($product_info)){

            return $this -> errorCode('暂无数据',[],1000);

        }else{

            return $this ->successCode($product_info,'成功',1);

        }
    }

    private function _sphinxBrand($keyword) {
        $cl = new SphinxClient();
        //$cl->SetServer ( '127.0.0.1', 9312);
        $cl->SetServer( Config('api.sphinx.host'), 9312);
        $cl->SetConnectTimeout( 3 );
        $cl->SetArrayResult( true );
        $cl->SetFilter('brand_status',[1]);
        $cl->SetMatchMode( SPH_MATCH_ALL);
        //$cl->SetSortMode(SPH_SORT_EXTENDED,' shop_price asc, @id desc ');
        //$cl->SetLimits(0,10);
        //$cl->setLimits(0,300);
        $res = $cl->Query( $keyword, env('SPHINX_INDEX_BRAND') );
        $brandIdAry = [];
        if(isset($res['matches']) && $res['matches']) {
            foreach($res['matches'] as $k => $v) {
                $brandIdAry[] = ['id' => $v['id']];
            }
        }

        return $brandIdAry;
    }


    public function getLimitNum( $p = "" )
    {
        //每页要显示总条数
        $p_num = Config( 'api.p_num' );

        //商品每页要查的条数
        $product_num = $p_num;

        if( empty( $p ) ) {

            $limit = 0;

        }else{

            $p=intval($p);

            $num= ceil($p/$product_num);

            $num=intval($num);

            $limit = $num * $product_num;

        }

        return $limit;
    }


    private function _sphinxProduct($keyword,$limit) {
        $p_num = Config( 'api.p_num' );
        $cl = new SphinxClient ();
        //$cl->SetServer ( '127.0.0.1', 9312);
        $cl->SetServer ( Config('api.sphinx.host'), 9312);
        $cl->SetConnectTimeout ( 3 );
        $cl->SetArrayResult ( true );
        $cl->SetFilter('product_status',[1]);
        $cl->SetFilter('is_deleted',[1]);
        $cl->SetMatchMode ( SPH_MATCH_ALL);
        $cl->SetSortMode(SPH_SORT_EXTENDED,' product_weight desc, @id desc ');
        //$cl->SetSortMode(SPH_SORT_ATTR_DESC,' product_weight desc, @id desc ');
        $cl->SetLimits($limit,$p_num);
        $res = $cl->Query ( $keyword, env('SPHINX_INDEX_PRODCUT') );
//        dd($res);
        $productIdAry = [];
        if(isset($res['matches']) && $res['matches']) {
            foreach($res['matches'] as $k => $v) {
                $productIdAry[] = ['id' => $v['id'],'product_name'=>$v['attrs']['product_name']];
            }
        }
        return $productIdAry;
    }


    /**
     * 搜索申请收录接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function applyRecord(Request $request){

        $search_name = $request->post('search_name');

        if(empty($search_name)){

            return $this ->errorCode('参数有误','',1000);

        }else{

            $res = DB::table('fb_apply_record')

                ->where(['record_name' => $search_name])

                ->first();

            if($res){

                return $this ->successCode('','申请成功',1);

            }else{

                $result = DB::table('fb_apply_record')->insert([

                        'record_name' => $search_name,

                        'created_at' => date("Y-m-d H:i:s",time())

                    ]);

                if($result){

                    return $this ->successCode('','申请成功',1);

                }else{

                    return $this ->errorCode('申请成功','',1000);

                }
            }
        }
    }

    /**
     * 获取用户的open_id
     * @param $session_id
     * @return bool|string
     */
    public function getOpenId($session_id)
    {
        session_id($session_id);

        session_start();

        $session_value = $_SESSION['session'];

        $session_key_value = $_SESSION['session_key'];

        $count = strlen($session_key_value);

        $open_id = substr($session_value, $count);

        return $open_id;
    }








}
