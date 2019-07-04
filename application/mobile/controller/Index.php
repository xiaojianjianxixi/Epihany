<?php

namespace app\mobile\controller;
use app\home\logic\UsersLogic;
use app\home\model\Message;
use Think\Db;
class Index extends MobileBase {

    public function index(){

        $cateList = M('goods_category')->where("is_show=1")->select();

        $typeList = M('goods_category')->where("is_show=1 and level=1")->select();

        foreach ($cateList as $key => $value){

                $value['menu'] =  M('goods_category')->where("is_show=1 and level<>1 and parent_id=".$value['id']."")->select();
                $list = $value['menu'];

                if($list!==""){
                    foreach ($value['menu'] as  $k=>$v) {
                        $list[$k]['goods'] = M('goods')->where(array('cat_id'=>$v['id'],'is_on_sale'=>'1'))->order("sort desc")->select();
                    }

                    $cateList[$key]['menu'] = $list;
                }
        }

        for ($i=0; $i < count($cateList); $i++) {
            if($cateList[$i]['level']!==intval(1)){
                unset($cateList[$i]);
            }
        }

        $where['session_id'] = $this->session_id; // 默认按照 session_id 查询
 
        // 如果这个用户已经等了则按照用户id查询
        if($this->user_id){
            unset($where);
            $where['user_id'] = $this->user_id;
        }
        $cartList = M('Cart')->where($where)->getField("id,goods_num,selected,prom_type,prom_id,goods_id");
        if($cartList)
        {
            $count = count($cartList);
        }

        $this->assign('cateList',$cateList);
        $this->assign('count',$count);
        $this->assign('typeList',$typeList);
        return $this->fetch();
    }


    /**
     * 分类列表显示
     */
    public function categoryList(){
        return $this->fetch();
    }
    /*首页猜你喜欢*/
	public function ajaxlikelist(){
		$user_id = cookie('user_id');
		$page=I('page/d',1);
    	$pageSize=I('pagesize/d',10);
    	$limit=I('limit/d',0);
    	if($limit>0){
    		$pageSize=$limit;
    		$page=1;
    	}
    	if($user){
    		$this->cartLogic = new \app\home\logic\GoodsLogic();
    		$list=$this->cartLogic->getvisit_list($user_id,$page,$pageSize);
    	}else{
    		$list=M('Goods')->where('is_on_sale=1')->order("sales_sum desc")->limit(($page-1)*$pageSize,$pageSize)->select();
    	}
    	$this->assign('likelist',$list);
    	if($limit>0){
    		return $this->fetch('ajax_like_limit');
    	}else{
    		return $this->fetch('ajax_like_list');
    	}
	}
    /**
     * 模板列表
     */
    public function mobanlist(){
        $arr = glob("D:/wamp/www/svn_tpshop/mobile--html/*.html");
        foreach($arr as $key => $val)
        {
            $html = end(explode('/', $val));
            echo "<a href='http://www.php.com/svn_tpshop/mobile--html/{$html}' target='_blank'>{$html}</a> <br/>";            
        }        
    }
    
    /**
     * 商品列表页
     */
    public function goodsList(){
        $id = I('get.id/d',0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists',$lists);
        return $this->fetch();
    }
    
    public function ajaxGetMore(){
    	$p = I('p/d',1);
    	$favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
    	$this->assign('favourite_goods',$favourite_goods);
    	return $this->fetch();
    }
}