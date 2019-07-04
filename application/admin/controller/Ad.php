<?php


namespace app\admin\controller;
use think\Page;

class Ad extends Base{
    public function ad(){       
        $act = I('get.act','add');
        $ad_id = I('get.ad_id/d');
        $ad_info = array();
        if($ad_id){
            $ad_info = D('ad')->where('ad_id',$ad_id)->find();
            $ad_info['start_time'] = date('Y-m-d',$ad_info['start_time']);
            $ad_info['end_time'] = date('Y-m-d',$ad_info['end_time']);            
        }
        if($act == 'add')          
           $ad_info['pid'] = $this->request->param('pid');
        $template_home = include(APP_PATH."/home/html.php");
        $template_mobile = include(APP_PATH."/mobile/html.php");
        $where['tpl']=array('in',array($template_home['template']['default_theme'],$template_mobile['template']['default_theme']));
        $position = M('AdPosition')->where($where)->select();
        //$position = D('ad_position')->where('1=1')->select();
        $this->assign('info',$ad_info);
        $this->assign('act',$act);
        $this->assign('position',$position);
        return $this->fetch();
    }
    
    public function adList(){
        
        delFile(RUNTIME_PATH.'html'); // 先清除缓存, 否则不好预览
        //获取模板信息
        $t = I('t', 'pc'); // pc or  mobile
        $m = ($t == 'pc') ? 'home' : 'mobile';
        $template_arr = include(APP_PATH."/$m/html.php");
        /*获取模板广告*/
        $ad_position_list = M('AdPosition')->where('tpl="'.$template_arr['template']['default_theme'].'"')->getField("position_id,position_name,is_open");
        if(!empty($ad_position_list)){
        	foreach($ad_position_list as $k=>$v){
	        	$pids[]=$v['position_id'];
	        }
	        $pids=implode(',',$pids);
        }else{
        	$pids=0;
        }
        $Ad =  M('ad');         
        $pid = I('pid',0);
        if($pid){
            $where['pid'] = $pid;
        	$this->assign('pid',I('pid'));
        }else{
        	$where['pid'] = array('in',$pids);
        }
        $keywords = I('keywords/s',false,'trim');
        if($keywords){
            $where['ad_name'] = array('like','%'.$keywords.'%');
        }
        $count = $Ad->where($where)->count();// 查询满足要求的总记录数
        $Page = $pager = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $Ad->where($where)->order('pid desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $list = array();
        if($res){
        	$media = array('图片','文字','flash');
        	foreach ($res as $val){
        		$val['media_type'] = $media[$val['media_type']];        		
        		$list[] = $val;
        	}
        }
                                     
                                
        $this->assign('ad_position_list',$ad_position_list);//广告位 
        $show = $Page->show();// 分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);        
        return $this->fetch();
    }
    
    public function position(){
        $act = I('get.act','add');
        $position_id = I('get.position_id/d');
        $info = array();
        if($position_id){
            $info = D('ad_position')->where('position_id',$position_id)->find();
        }
        $this->assign('info',$info);
        $this->assign('act',$act);
        $arr_pc = scandir("./template/pc/");
        foreach ($arr_pc as $key => $val) {
            if ($val == '.' || $val == '..')
                continue;
            $template_config[$val] = include "./template/pc/$val/config.php";
        }
        $arr_mobile = scandir("./template/mobile/");
        foreach ($arr_mobile as $key => $val) {
            if ($val == '.' || $val == '..')
                continue;
            $template_config[$val] = include "./template/mobile/$val/config.php";
        }
        $this->assign('template',$template_config);
        return $this->fetch();
    }
    
    public function positionList(){
        $Position =  M('ad_position');
        $count = $Position->where('1=1')->count();// 查询满足要求的总记录数
        $Page = $pager = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $Position->order('position_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('list',$list);// 赋值数据集                
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager); 
        return $this->fetch();
    }
    
    public function adHandle(){
    	$data = I('post.');
    	$data['start_time'] = strtotime($data['begin']);
    	$data['end_time'] = strtotime($data['end']);
    	
    	if($data['act'] == 'add'){
    		$r = D('ad')->add($data);
    	}
    	if($data['act'] == 'edit'){
    		$r = D('ad')->where('ad_id', $data['ad_id'])->save($data);
    	}
    	
    	if($data['act'] == 'del'){
            $r = D('ad')->where('ad_id', $data['del_id'])->delete();
    		if($r) exit(json_encode(1));
    	}
    	$referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Ad/adList');
        // 不管是添加还是修改广告 都清除一下缓存
        delFile(RUNTIME_PATH.'html'); // 先清除缓存, 否则不好预览
        
    	if($r){
    		$this->success("操作成功",U('Admin/Ad/adList'));
    	}else{
    		$this->error("操作失败",$referurl);
    	}
    }
    
    public function positionHandle(){
        $data = I('post.');
        if($data['act'] == 'add'){
            $r = M('ad_position')->add($data);
        }
        
        if($data['act'] == 'edit'){
        	$r = M('ad_position')->where('position_id',$data['position_id'])->save($data);
        }
        
        if($data['act'] == 'del'){
        	if(M('ad')->where('pid',$data['position_id'])->count()>0){
        		$this->error("此广告位下还有广告，请先清除",U('Admin/Ad/positionList'));
        	}else{
        		$r = M('ad_position')->where('position_id', $data['position_id'])->delete();
        		if($r) exit(json_encode(1));
        	}
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Ad/positionList');
        if($r){
        	$this->success("操作成功",$referurl);
        }else{
        	$this->error("操作失败",$referurl);
        }
    }
    
    public function changeAdField(){
        $field = $this->request->request('field');
    	$data[$field] = I('get.value');
    	$data['ad_id'] = I('get.ad_id');
    	M('ad')->save($data); // 根据条件保存修改的数据
    }
}