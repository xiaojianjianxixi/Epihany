<?php



namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
class Base extends Controller {

    /**
     * 析构函数
     */
    function __construct() 
    {
        Session::start();
        header("Cache-control: private");  
        parent::__construct();
        //用户中心面包屑导航
        $navigate_admin = navigate_admin();
        $this->assign('navigate_admin',$navigate_admin);
        tpversion();        
   }    
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify')) || in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
        	//return;
        }else{
        	if(session('admin_id') > 0 ){
        		$this->check_priv();//检查管理员菜单操作权限
        	}else{
        		$this->error('请先登陆',U('Admin/Admin/login'),1);
        	}
        }
        //更新积分活动
        $this->update_points_goods();
        $this->public_assign();
    }
    /*
     * 更新积分活动信息 lqw 2017年6月12日14:52:14
     * */
    public function update_points_goods(){
		//过期的未关闭的积分活动
    	$list=M('PointsGoods')->where('end_time<'.time().' and is_close=0')->select();
    	if(!empty($list)){
    		foreach($list as $k=>$v){
    			$update=array(
    				'is_close'=>1
    			);
    			M('PointsGoods')->where('id='.$v['id'])->save($update);
    			//查询商品
    			$goods_list=M('Goods')->where('points_id='.$v['id'])->field('goods_id,points_id,exchange_integral')->select();
    			if(!empty($goods_list)){
    				foreach($goods_list as $key=>$val){
    					$update_goods=array(
    						'points_id'=>0
    					);
    					if($val['exchange_integral']==$v['points']){
    						$update_goods['exchange_integral']=0;
    					}
    					M('Goods')->where('goods_id='.$val['goods_id'])->save($update_goods);
    				}
    			}
    		}
    	}
    }
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
       $tpshop_config = array();
       $tp_config = M('config')->cache(true)->select();
       foreach($tp_config as $k => $v)
       {
          $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $this->assign('tpshop_config', $tpshop_config);       
    }
    
    public function check_priv()
    {
    	$ctl = CONTROLLER_NAME;
    	$act = ACTION_NAME;
        $act_list = session('act_list');
		//无需验证的操作
		$uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task');
    	if($ctl == 'Index' || $act_list == 'all'){
    		//后台首页控制器无需验证,超级管理员无需验证
    		return true;
    	}elseif(strpos($act,'ajax') || in_array($act,$uneed_check)){
    		//所有ajax请求不需要验证权限
    		return true;
    	}else{
    		$right = M('system_menu')->where("id", "in", $act_list)->cache(true)->getField('right',true);
    		foreach ($right as $val){
    			$role_right .= $val.',';
    		}
    		$role_right = explode(',', $role_right);
    		//检查是否拥有此操作权限
    		if(!in_array($ctl.'Controller@'.$act, $role_right)){
    			$this->error('您没有操作权限,请联系超级管理员分配权限',U('Admin/Index/welcome'));
    		}
    	}
    }
    
    public function ajaxReturn($data,$type = 'json'){                        
            exit(json_encode($data));
    }    
}