<?php

namespace app\api\controller;
use app\home\model\Message;
use app\home\logic\UsersLogic;
use Think\Db;
class Index extends ApiBase {

    public function index(){
    	if(I('token')){
    		$user=$this->getUserByTokenNO(I('token'));
    	}
    	$page=I('page/d',1);
    	$pageSize=I('pagesize/d',10);
    	if($user){
    		$this->cartLogic = new \app\home\logic\GoodsLogic();
    		$list=$this->cartLogic->getvisit_list($user['user_id'],$page,$pageSize);
    	}else{
    		$list=M('Goods')->where('is_on_sale=1')->order("sales_sum desc")->limit(($page-1)*$pageSize,$pageSize)->select();
    	}
		$like_list=$this->getGoodsList($list);
        /*推荐51319  首页轮播51318  新品51320  中间51322*/
        $condition['pid']=51318;
        $condition['enabled']=1;
        $condition['ad_code']=array('neq','');
        $condition['start_time']=array('elt',time());
        $condition['end_time']=array('egt',time());
        $top_ad=M('ad')->where($condition)->select();
        $top_ad=$this->getAdList($top_ad);
        $condition['pid']=51322;
        $middle_ad=M('ad')->where($condition)->limit(2)->select();
        $middle_ad=$this->getAdList($middle_ad);
        $condition['pid']=51319;
        $recommend_ad=M('ad')->where($condition)->limit(3)->select();
        $recommend_ad=$this->getAdList($recommend_ad);
        $condition['pid']=51320;
        $new_ad=M('ad')->where($condition)->limit(4)->select();
        $new_ad=$this->getAdList($new_ad);
        /*统计消息数量*/
       	if($user['user_id']>0){
       		$user_logic = new UsersLogic();
	        $message_model = new Message();
	        //系统消息
	        $message_model->updateUserMessageNotice();//插入系统消息
	        //系统消息
	        $user_system_message_where = array(
	                'um.user_id' => $user['user_id'],
	                'um.category' => 0,
	                'um.status'=>0
	        );
	        $message_count= DB::name('user_message')
	        ->alias('um')
	        ->field('um.rec_id, um.message_id, m.message, m.send_time')
	        ->join('__MESSAGE__ m','um.message_id = m.message_id','LEFT')
	        ->where($user_system_message_where)
	        ->count('um.rec_id');
       	}
        $result=array(
        	'LikeList'=>!empty($like_list)?$like_list:array(),
        	'Top'=>!empty($top_ad)?$top_ad:array(),
        	'Middle'=>!empty($middle_ad)?$middle_ad:array(),
        	'Recommend'=>!empty($recommend_ad)?$recommend_ad:array(),
        	'New'=>!empty($new_ad)?$new_ad:array(),
        	'Messagecount'=>$message_count>0?$message_count:0
        );
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }
    /*猜你喜欢分页*/
    public function ajaxlikelist(){
    	if(I('token')){
    		$user=$this->getUserByTokenNO(I('token'));
    	}
    	$page=I('Page/d',1);
    	$pageSize=I('PageSize/d',10);
    	if($user){
    		$this->cartLogic = new \app\home\logic\GoodsLogic();
    		$list=$this->cartLogic->getvisit_list($user['user_id'],$page,$pageSize);
    	}else{
    		$list=M('Goods')->where('is_on_sale=1')->order("sales_sum desc")->limit(($page-1)*$pageSize,$pageSize)->select();
    	}
		$like_list=$this->getGoodsList($list);
		$result=array(
        	'LikeList'=>!empty($like_list)?$like_list:array(),
        );
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }
    /**
     * 分类列表显示
     */
    public function categoryList(){
        return $this->fetch();
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