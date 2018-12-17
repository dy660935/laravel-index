<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Strategy extends Base
{
    protected $table = "fb_strategy";
    /**
     * 获取首页轮播图信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSlider ()
    {

        $strategy_info = Strategy::where( 'strategy_status' , 1 )->orderby( 'created_at' , 'desc' )->take(5)->get(['id','strategy_slider_image','created_at','is_weChat_add','strategy_describe'])->toArray();

        foreach ( $strategy_info as $k=>$v ) {

            $img=$this->imageCompatible($v['strategy_slider_image']);

            $strategy_info[ $k ][ 'strategy_image' ] = $img;

        }

        return $strategy_info;
    }

    /**
     * 获取首页的热门攻略信息
     * @return array
     */
    public function getIndexStrategy ()
    {
        $sql = "SELECT
            a.id,
            strategy_title,
            strategy_image,
            a.created_at,
            author_head_portrait,
            author_name,
            strategy_clicks,
	        is_weChat_add
            FROM fb_strategy AS a
            JOIN fb_author AS b ON a.author_id = b.id
            WHERE  strategy_status = 1
            ORDER BY strategy_weight desc
            LIMIT 1";

        $strategy = DB::select( $sql );
        $strategy_info = [];
        foreach ( $strategy as $k => $v ) {
            $strategy[$k] = (array)$v;
            $time = strtotime( $v->created_at );
            $strategy[ $k ][ 'created_at' ] = date( 'Y-m-d' , $time );
            $image=$this->imageCompatible($v->strategy_image);
            $author_image=$this->imageCompatible($v->author_head_portrait);
            $strategy[$k]['strategy_image'] = $image;
            $strategy[$k]['author_head_portrait'] = $author_image;
            $strategy_info = $strategy[ $k ];
        }

        return $strategy_info;
    }

    /**
     * 首页分页tab
     *
     * @param $category_id
     * @param $p
     *
     * @return array
     */
    public function getIndexTabStrategyInfo ( $category_id , $p )
    {
        //var_dump($p);die;
        if ( empty( $p ) ) {
            $limit = 0;
        }
        elseif ( $p % 10 != 0 ) {
            $limit = ( ( ( ( $p + 10 ) - ( $p % 10 ) ) / 10 ) ) * 1;
            if ( $limit <= $p ) {
                $limit = ( ( ( ( $p + 10 ) - ( $p % 10 ) ) / 10 ) + 1 ) * 1;
            }
        }
        else {

            $limit = ( ( $p + 10 ) / 10 - 1 ) * 1;
        }

        if ( $category_id == 0 ) {

            $where = "strategy_status = 1";
        }
        else {
            $where = "strategy_status = 1 and c.category_id = $category_id";
        }

        $sql = "SELECT
            a.id,
            strategy_title,
            strategy_image,
            a.created_at,
            c.category_id,
            author_head_portrait,
            author_name,
            strategy_clicks,
	        is_weChat_add
            FROM fb_strategy AS a
            JOIN fb_author AS b ON a.author_id = b.id
            left join fb_category_strategy_mapping as c on a.id = c.strategy_id
            WHERE  $where
            ORDER BY strategy_weight desc
            LIMIT $limit ,1";

        $strategy = DB::select( $sql );
        $strategy_info = [];

        $sql2 = "select COUNT(a.id) as strategy_count from fb_strategy as a left join fb_category_strategy_mapping as c on a.id = c
.strategy_id where $where";
        $strategy_count = DB::select( $sql2 );

        foreach( $strategy as $k => $v ) {
            $strategy[ $k ] = (array) $v;
            $time = strtotime( $v->created_at );
            $strategy[$k]['created_at'] = date('Y-m-d' , $time );
            $image=$this->imageCompatible($v->strategy_image);
            $author_image=$this->imageCompatible($v->author_head_portrait);
            $strategy[$k]['strategy_image'] = $image;
            $strategy[$k]['author_head_portrait'] = $author_image;
            $strategy_info = $strategy[ $k ];
        }

        $strategy_all[ 'strategy_info' ] = $strategy_info;
        if( $strategy_count ) {
            $strategy_count = $this->getArrayByObject( $strategy_count );

            $strategy_all[ 'strategy_count' ] = $strategy_count[ 0 ][ 'strategy_count' ];

        }


        return $strategy_all;
    }


    /**
     * 攻略单条
     *
     * @param $strategy_id
     *
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function strategyInfoFind( $strategy_id )
    {
        //修改总的点击量
        $this->where( 'id' , $strategy_id )->increment('strategy_clicks');

        //今天凌晨时间戳
        $today = strtotime(date( 'Y-m-d' ));

        $now = time();

        if( $now - $today > 86400 ){

            if($this->find($strategy_id)){

                $this->where('id',$strategy_id)->update(['strategy_daily_clicks'=>1]);

            }else{

                return false;
            }

        }else{

            $this->where('id',$strategy_id)->increment('strategy_daily_clicks');
        }

        $strategy_info=DB::table('fb_strategy as a')
            ->join('fb_author as b','a.author_id','=','b.id')
            ->where(['a.id' => $strategy_id ])
            ->first(['strategy_title','strategy_wechat_url','strategy_slider_image as strategy_image','strategy_describe','author_name','author_head_portrait','a.created_at','strategy_clicks','is_weChat_add','strategy_abstract','strategy_wechat_url']);

        if($strategy_info){

            $strategy_info=get_object_vars($strategy_info);

            $strategy_info['author_head_portrait']=$this->imageCompatible($strategy_info['author_head_portrait']);

            $strategy_info['strategy_image']=$this->imageCompatible($strategy_info['strategy_image']);

            return $strategy_info;

        }else{

            return false;
        }

    }


    public function getStrategyIndex ( $strategy_type ,$limit )
    {
        $p_num = Config( 'api.p_num' );

        if ( $strategy_type == 0 ) {

            $where = ['strategy_status'=>1];

            $order = 'strategy_clicks';

            $by = 'desc';

        } else {

            $where = ['strategy_status'=>1];

            $order = 'a.created_at';

            $by = 'desc';
        }

        $strategy_info = DB::table('fb_strategy as a')
            ->join( 'fb_author as b' , 'a.author_id','=','b.id')
            ->where($where)
            ->orderBy($order,$by)
            ->select(['a.id','strategy_title','strategy_slider_image as strategy_image','strategy_abstract','strategy_clicks','is_weChat_add','author_name','author_head_portrait'])
            ->offset($limit)
            ->limit($p_num)
            ->get()
            ->toArray();

        if($strategy_info){

            $strategy_info=$this->getArrayByObject($strategy_info);

            foreach ( $strategy_info as $k => $v ) {

                $strategy_info[$k]['author_head_portrait']=$this->imageCompatible($v['author_head_portrait']);

                $strategy_info[$k]['strategy_image']=$this->imageCompatible($v['strategy_image']);

                $strategy_info[$k]['strategy_abstract'] = mb_substr( $v[ 'strategy_abstract' ] , 0 , 50 );

            }

            return $strategy_info;

        }else{

            return false;
        }

    }
    
}
