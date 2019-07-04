<?php

namespace app\api\controller;
use think\AjaxPage;
use think\Page;
use think\Db;
class Goods extends ApiBase {
    public function index(){
       // $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
        return $this->fetch();
    }

    /**
     * 分类列表显示
     */
    public function categoryList(){
    	$arr = $result = array();
		$cat_list = M('goods_category')->where("is_show = 1")->field('id,mobile_name,parent_id,level,image')->cache(true)->order('sort_order')->select();//所有分类
		foreach ($cat_list as $val){
			if($val['image']){
				$val['image']=C('HeadUrl').$val['image'];
			}
			if($val['level'] == 2){
				$arr[$val['parent_id']][] = $val;
			}
			if($val['level'] == 3){
				$crr[$val['parent_id']][] = $val;
			}
			if($val['level'] == 1){
				$tree[] = $val;
			}
		}
		foreach ($arr as $k=>$v){
			foreach ($v as $kk=>$vv){
				$arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
			}
		}
		foreach ($tree as $val){
			$val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
			$result[$val['id']] = $val;
		}
		$goods_category_tree=array();
		foreach($result as $k=>$v){
			$goods_category_tree[]=$v;
		}
		/*分类广告pid=51323*/
        $condition['pid']=51323;
        $condition['enabled']=1;
        $condition['ad_code']=array('neq','');
        $condition['start_time']=array('elt',time());
        $condition['end_time']=array('egt',time());
        $ad=M('ad')->where($condition)->select();
        //echo M('ad')->getLastSql();die;
        $ad=$this->getAdList($ad);
		$result1['category_tree']=$goods_category_tree;
		$result1['category_ad']=!empty($ad)?$ad[0]:array();
		$msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result1;
		exit(json_encode($msg));
    }

    /**
     * 商品列表页
     */
    public function goodsList(){
    	$goodsLogic = new \app\home\logic\GoodsLogic();
    	$type=I('type','all');
    	$keyword=I('keyword');
    	$sales=I('sales/d',0);//0没选择 1升序  2降序
    	$price=I('price/d',0);//0没选择 1升序  2降序
    	$cid=I('cid/d',0);
    	$page=I('Page/d',1);
    	$page=$page<=0?1:$page;
    	$pagesize=I('PageSize/d',10);
    	$goodsCate = M('GoodsCategory')->where("id", $cid)->find();// 当前分类
    	$where['is_on_sale']=1;
    	if(!empty($goodsCate)){
    		$where['cat_id']=$cid;
    	}
    	if($keyword){
    		$where['goods_name']=array('like','%'.$keyword.'%');
    	}
    	/*销量*/
    	if($sales>0){
    		if($sales==1){
    			$orderby['sales_sum']='sales_sum asc';
    		}else{
    			$orderby['sales_sum']='sales_sum desc';
    		}
    	}
    	/*价格*/
    	if($price>0){
    		if($price==1){
    			$orderby['shop_price']='shop_price asc';
    		}else{
    			$orderby['shop_price']='shop_price desc';
    		}
    	}
    	if($price==0&&$sales==0){
    		if($type=='new'){//新品
	    		/*按插入倒序*/
	    		$orderby['goods_id']='goods_id desc';
	    	}elseif($type=='all'){//综合
	    		/*按销量+点击量*/
	    		$orderby['sales_sum']='sales_sum desc';
	    		$orderby['click_count']='click_count desc';
	    	}
    	}
    	
    	$sortby=implode(',',$orderby);
    	$goodslist=M('Goods')->where($where)->order($sortby)->limit(($page-1)*$pagesize,$pagesize)->select();
    	$goodslist=$this->getGoodsList($goodslist);
    	if(!empty($goodslist)){
    		/*评价  好评率*/
	    	foreach($goodslist as $k=>$v){
	    		$commentStatistics=array();
	    		$commentStatistics = $goodsLogic->commentStatistics($v['gid']);// 获取某个商品的评论统计    
		        $goodslist[$k]['commentrate']=$commentStatistics['rate1'];
		        $goodslist[$k]['commentcount']=$commentStatistics['c0'];
	    	}
    	}
    	$count=M('Goods')->where($where)->count();
    	/*商品分类*/
    	$arr = $result = array();
		$cat_list = M('goods_category')->where("is_show = 1")->field('id,mobile_name,parent_id,level,image')->cache(true)->order('sort_order')->select();//所有分类
		foreach ($cat_list as $val){
			if($val['image']){
				$val['image']=C('HeadUrl').$val['image'];
			}
			if($val['level'] == 2){
				$arr[$val['parent_id']][] = $val;
			}
			if($val['level'] == 3){
				$crr[$val['parent_id']][] = $val;
			}
			if($val['level'] == 1){
				$tree[] = $val;
			}
		}
		foreach ($arr as $k=>$v){
			foreach ($v as $kk=>$vv){
				$arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
			}
		}
		foreach ($tree as $val){
			$val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
			$result[$val['id']] = $val;
		}
		$goods_category_tree=array();
		foreach($result as $k=>$v){
			$goods_category_tree[]=$v;
		}
    	$result=array(
    		'List'=>!empty($goodslist)?$goodslist:array(),
    		'count'=>$count,
    		'goods_category_tree'=>$goods_category_tree
    	);
    	$msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }

    /**
     * 商品列表页 ajax 翻页请求 搜索
     */
    public function ajaxGoodsList() {
        $where ='';

        $cat_id  = I("id/d",0); // 所选择的商品分类id
        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id);
            $where .= " WHERE cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }

        $result = DB::query("select count(1) as count from __PREFIX__goods $where ");
        $count = $result[0]['count'];
        $page = new AjaxPage($count,10);

        $order = " order by goods_id desc"; // 排序
        $limit = " limit ".$page->firstRow.','.$page->listRows;
        $list = DB::query("select *  from __PREFIX__goods $where $order $limit");

        $this->assign('lists',$list);
        $html = $this->fetch('ajaxGoodsList'); //return $this->fetch('ajax_goods_list');
       exit($html);
    }

    /**
     * 商品详情页
     */
    public function goodsInfo(){
        $goodsLogic = new \app\home\logic\GoodsLogic();
        $goods_id = I("gid/d");
        $goods = M('Goods')->where("goods_id", $goods_id)->field('extend_cat_id,goods_id,cat_id,goods_sn,goods_name,store_count,market_price,shop_price,exchange_integral,prom_type,prom_id,goods_content,click_count,sales_sum,goods_remark')->find();
		$goods['thumb']=C('HeadUrl').goods_thum_images($goods_id,300,300);
		if(!$goods['goods_remark']){
			$goods['goods_remark']=tpCache('shop_info.store_name');
		}
		$goods['goods_content']=htmlspecialchars_decode($goods['goods_content']);
    	$goods['goods_content'] = str_replace('src="/public', 'src="'.C('HeadUrl').'/public', $goods['goods_content']);
        if(empty($goods)){
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = '此商品不存在或者已下架';
			exit(json_encode($msg));
        }
        M('Goods')->where("goods_id", $goods_id)->save(array('click_count'=>$goods['click_count']+1 )); //统计点击数
        $goods_images_list = M('GoodsImages')->where("goods_id", $goods_id)->field('image_url')->select(); // 商品 图册
        foreach($goods_images_list as $k=>$v){
			if($v['image_url']){
				$goods_images_list[$k]['image_url']=C('HeadUrl').$v['image_url'];
			}
        }
        if($goods_images_list){
        	$goods['imglist']=$goods_images_list;
        }else{
        	$goods['imglist']=array();
        }
        
		/*属性选项*/
		$filter_spec = $goodsLogic->get_spec($goods_id); 
		$new_spec=array(); 
		if($filter_spec){
			foreach($filter_spec as $k=>$v){
				$tmp=array();
				if($v){
					foreach($v as $ko=>$vo){
						if($vo['src']){
							$vo['src']=C('HeadUrl').$vo['src'];
						}
						$tmp[]=$vo;
					}
				}
				$new_spec[]=array(
					'name'=>$k,
					'child'=>$tmp
				);
			}
		}
		$goods['filter_spec']=$new_spec;
        $spec_goods_price  = M('spec_goods_price')->where("goods_id", $goods_id)->getField("key,price,store_count"); // 规格 对应 价格 库存表
        $tmp=array();
        foreach($spec_goods_price as $k=>$v){
        	$tmp[]=$v;
        }
        $goods['spec_goods_price']=$tmp;
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计    
        $commentrate=$commentStatistics['rate1'];
        $commentcount=$commentStatistics['c0'];
        //商品促销
        if($goods['prom_type'] == 1)
        {
        	$prom_goods = M('prom_goods')->where(['id'=>$goods['prom_id'],'is_close'=>0])->find();
            $goods['flash_sale'] = get_goods_promotion($goods['goods_id']);                        
            $flash_sale = M('flash_sale')->where("id", $goods['prom_id'])->find();
            $goods['discount'] = round($goods['flash_sale']['price']/$goods['shop_price'],2)*10;
        }else{
        	$goods['discount'] = round($goods['shop_price']/$goods['market_price'],2)*10;
        }
        if(I('token')){
    		$user=$this->getUserByTokenNO(I('token'));
    		if(!empty($user)){
    			//当前用户收藏
		        $collect = M('goods_collect')->where(array("goods_id"=>$goods_id ,"user_id"=>$user['user_id']))->count();
		        $collect = $collect>0?1:0;
    		}
    		/*浏览记录*/
    		if ($user['user_id']) {
	            $goodsLogic->add_visit_log($user['user_id'], $goods);
	            unset($goods['extend_cat_id']);
	        }
    	}
    	/*商品评论*/
        $count = M('Comment')->where(['goods_id'=>$goods_id,'is_show'=>1])->count();
        $list = M('Comment')
				->alias('c')
				->join('__USERS__ u','u.user_id = c.user_id','LEFT')
				->where(['goods_id'=>$goods_id,'is_show'=>1])
				->order("add_time desc")
				->limit($page_count)
				->field('c.comment_id,c.username,c.content,c.add_time,c.goods_rank,c.add_time,c.img,u.head_pic')
				->select();
        foreach($list as $k => $v){
        	if(!$v['goods_rank']){
        		$list[$k]['goods_rank'] = 0;
        	}
        	if($v['img']){
	        	$tmp=unserialize($v['img']);
	        	if(!empty($tmp)){
	        		foreach($tmp as $key=>$val){
	        			if($val){
	        				$tmp[$key]=C('HeadUrl').$val;
	        			}
	        		}
	        	}else{
	        		$tmp=array();
	        	}
	            $list[$k]['img'] = $tmp; // 晒单图片       
        	}else{
        		$list[$k]['img'] = array();
        	}
        	/*头像*/
            if($v['head_pic']){
            	$list[$k]['head_pic']=C('HeadUrl').$v['head_pic'];
            }
            $list[$k]['add_time']=date('Y-m-d',$v['add_time']);     
        }
        //dump($goods);die;
        $result=array(
        	'Goods' => $goods,
        	'CommentRate' => $commentrate,
        	'CommentCount' => $commentcount,
        	'Collect' => $collect>0?1:0,
        	'CommentList'=>$list
        );
        $msg['ReturnCode'] = 100;//
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }

    /**
     * 商品详情页
     */
    public function detail(){
        //  form表单提交
        C('TOKEN_ON',true);
        $goods_id = I("get.id/d");
        $goods = M('Goods')->where("goods_id", $goods_id)->find();
        $this->assign('goods',$goods);
        return $this->fetch();
    }

    /*
     * 商品评论
     */
    public function comment(){
        $goods_id = I("goods_id/d",0);
        $this->assign('goods_id',$goods_id);
        return $this->fetch();
    }

    /*
     * ajax获取商品评论
     */
    public function ajaxComment(){   
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
    	$goods_id=I('gid/d');     
        /*商品评论*/
        $count = M('Comment')->where(['goods_id'=>$goods_id,'is_show'=>1])->count();
        $list = M('Comment')
				->alias('c')
				->join('__USERS__ u','u.user_id = c.user_id','LEFT')
				->where(['goods_id'=>$goods_id,'is_show'=>1])
				->order("add_time desc")
				->limit(($page-1)*$pagesize,$pagesize)
				->field('c.comment_id,c.username,c.content,c.add_time,c.goods_rank,c.add_time,c.img,u.head_pic')
				->select();
        foreach($list as $k => $v){
        	if($v['img']){
	        	$tmp=unserialize($v['img']);
	        	if(!empty($tmp)){
	        		foreach($tmp as $key=>$val){
	        			if($val){
	        				$tmp[$key]=C('HeadUrl').$val;
	        			}
	        		}
	        	}else{
	        		$tmp=array();
	        	}
	            $list[$k]['img'] = $tmp; // 晒单图片       
        	}else{
        		$list[$k]['img'] = array();
        	}
        	/*头像*/
            if($v['head_pic']){
            	$list[$k]['head_pic']=C('HeadUrl').$v['head_pic'];
            }
            $list[$k]['add_time']=date('Y-m-d',$v['add_time']);     
        }
        $result=array(
        	'List'=>$list,
        	'Count'=>$count
        );
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }
    
    /*
     * 获取商品规格
     */
    public function goodsAttr(){
        $goods_id = I("get.goods_id/d",0);
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id", $goods_id)->select(); // 查询商品属性表
        $this->assign('goods_attr_list',$goods_attr_list);
        $this->assign('goods_attribute',$goods_attribute);
        return $this->fetch();
    }

    /**
     * 积分商城
     */
    public function integralMall()
    {
        $point_rate = tpCache('shopping.point_rate');
        $goods_where = array(
            'is_on_sale' => 1,  //是否上架
        );
        $goods_where['exchange_integral'] =  array('gt',0);
        $goods_list = M('goods')->where($goods_where)->limit(4)->order('sales_sum desc')->select();
        //$goods_list=$this->getGoodsList($goods_list);
        $list=array();
        foreach($goods_list as $key=>$val){
	  		$thumb=goods_thum_images($val['goods_id'],300,300);
	  		if($thumb){
	  			$thumb=C('HeadUrl').$thumb;
	  		}
	  		$points_money=round($val['exchange_integral']/$point_rate,2);
	  		if($points_money>$val['shop_price']){
	  			$money=0;
	  			$points=ceil($val['shop_price']*$point_rate);
	  		}else{
	  			$cha=$val['shop_price']-$points_money;
	  			$money=$cha;
	  			$points=$val['exchange_integral'];
	  		}
	  		$list[]=array(
	  			'gid'=>$val['goods_id'],
	  			'goods_name'=>$val['goods_name'],
	  			'market_price'=>$val['market_price'],
	  			'shop_price'=>$val['shop_price'],
	  			'thumb'=>$thumb,
	  			'points'=>$points>0?$points:0,
	  			'money'=>$money
	  		);
	  	}
	  	/*优惠券*/
	  	$where=array();
	  	$where['type']=5;
	  	$where['send_start_time']=array('elt',time());
        $where['send_end_time']=array('egt',time());
        $coupon_list=M('Coupon')->where($where)->field('id,name,money,condition,points')->limit(4)->order('add_time desc')->select();
        /*广告 51321积分商城*/
        $condition['pid']=51321;
        $condition['enabled']=1;
        $condition['ad_code']=array('neq','');
        $condition['start_time']=array('elt',time());
        $condition['end_time']=array('egt',time());
        $ad=M('ad')->where($condition)->select();
        $ad=$this->getAdList($ad);
        $result=array(
        	'GoodsList'=>$list,
        	'CouponList'=>$coupon_list,
        	'ad'=>$ad
        );
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }
    /**
     * 积分商品列表
     */
    public function integralList()
    {
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
        $point_rate = tpCache('shopping.point_rate');
        $goods_where = array(
            'is_on_sale' => 1,  //是否上架
        );
        $goods_where['exchange_integral'] =  array('gt',0);
        $goods_list = M('goods')->where($goods_where)->limit(($page-1)*$pagesize,$pagesize)->order('goods_id desc')->select();
        $list=array();
        foreach($goods_list as $key=>$val){
	  		$thumb=goods_thum_images($val['goods_id'],300,300);
	  		if($thumb){
	  			$thumb=C('HeadUrl').$thumb;
	  		}
	  		$points_money=round($val['exchange_integral']/$point_rate,2);
	  		if($points_money>$val['shop_price']){
	  			$money=0;
	  			$points=ceil($val['shop_price']*$point_rate);
	  		}else{
	  			$cha=$val['shop_price']-$points_money;
	  			$money=$cha;
	  			$points=$val['exchange_integral'];
	  		}
	  		$list[]=array(
	  			'gid'=>$val['goods_id'],
	  			'goods_name'=>$val['goods_name'],
	  			'market_price'=>$val['market_price'],
	  			'shop_price'=>$val['shop_price'],
	  			'thumb'=>$thumb,
	  			'points'=>$points,
	  			'money'=>$money
	  		);
	  	}
	  	$count=M('goods')->where($goods_where)->count();
	  	$result=array(
	  		'goodsmoreList'=>$list,
	  		'Count'=>$count
	  	);
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }
    /**
     * 优惠券列表
     */
    public function couponList()
    {
    	/*优惠券*/
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
	  	$where['type']=5;
	  	$where['send_start_time']=array('elt',time());
        $where['send_end_time']=array('egt',time());
        $coupon_list=M('Coupon')->where($where)->field('id,name,money,condition,points')->limit(($page-1)*$pagesize,$pagesize)->order('add_time desc')->select();
        $count=M('Coupon')->where($where)->count();
        $result=array(
        	'couponmoreList'=>$coupon_list,
        	'Count'=>$count,
        );
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }

     /**
     * 商品搜索列表页
     */
    public function search(){
    	$filter_param = array(); // 帅选数组
    	$id = I('get.id/d',0); // 当前分类id
    	$brand_id = I('brand_id/d',0);    	    	
    	$sort = I('sort','goods_id'); // 排序
    	$sort_asc = I('sort_asc','asc'); // 排序
    	$price = I('price',''); // 价钱
    	$start_price = trim(I('start_price','0')); // 输入框价钱
    	$end_price = trim(I('end_price','0')); // 输入框价钱
    	if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱   	 
    	$filter_param['id'] = $id; //加入帅选条件中
    	$brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中    	    	
    	$price  && ($filter_param['price'] = $price); //加入帅选条件中
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入帅选条件中
        $qtype = I('qtype','');
        $where  = array('is_on_sale' => 1);
        if($qtype){
        	$filter_param['qtype'] = $qtype;
        	$where[$qtype] = 1;
        }
        if($q) $where['goods_name'] = array('like','%'.$q.'%');
        
    	$goodsLogic = new \app\home\logic\GoodsLogic(); // 前台商品操作逻辑类
    	$filter_goods_id = M('goods')->where($where)->cache(true)->getField("goods_id",true);

    	// 过滤帅选的结果集里面找商品
    	if($brand_id || $price)// 品牌或者价格
    	{
    		$goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
    		$filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个帅选条件的结果 的交集
    	}

        //筛选网站自营,入驻商家,货到付款,仅看有货,促销商品
        $sel = I('sel');
        if($sel)
        {
            $goods_id_4 = $goodsLogic->get_filter_selected($sel);
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_4);
        }

    	$filter_menu  = $goodsLogic->get_filter_menu($filter_param,'search'); // 获取显示的帅选菜单
    	$filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'search'); // 帅选的价格期间
    	$filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'search',1); // 获取指定分类下的帅选品牌    	 
    	
    	$count = count($filter_goods_id);
    	$page = new Page($count,12);
    	if($count > 0)
    	{
    		$goods_list = M('goods')->where("goods_id", "in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
    		$filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
    		if($filter_goods_id2)
    			$goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
    	}
    	$goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
    	$this->assign('goods_list',$goods_list);
    	$this->assign('goods_category',$goods_category);
    	$this->assign('goods_images',$goods_images);  // 相册图片
    	$this->assign('filter_menu',$filter_menu);  // 帅选菜单     
    	$this->assign('filter_brand',$filter_brand);// 列表页帅选属性 - 商品品牌
    	$this->assign('filter_price',$filter_price);// 帅选的价格期间    	
    	$this->assign('filter_param',$filter_param); // 帅选条件    	
    	$this->assign('page',$page);// 赋值分页输出
    	$this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');
    	C('TOKEN_ON',false);
        if(input('is_ajax'))
            return $this->fetch('ajaxGoodsList');
        else
            return $this->fetch();
    }

    /**
     * 商品搜索列表页
     */
    public function ajaxSearch()
    {
        return $this->fetch();
    }

    /**
     * 品牌街
     */
    public function brandstreet()
    {
        $getnum = 9;   //取出数量
        $goods=M('goods')->where(array('is_recommend'=>1,'is_on_sale'=>1))->page(1,$getnum)->cache(true,TPSHOP_CACHE_TIME)->select(); //推荐商品
        for($i=0;$i<($getnum/3);$i++){
            //3条记录为一组
            $recommend_goods[] = array_slice($goods,$i*3,3);
        }
        $where = array(
            'is_hot' => 1,  //1为推荐品牌
        );
        $count = M('brand')->where($where)->count(); // 查询满足要求的总记录数
        $Page = new Page($count,20);
        $show = $Page->show();// 分页显示输出
        $brand_list = M('brand')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('sort desc')->select();
        $this->assign('Page',$show);  //赋值分页输出
        $this->assign('recommend_goods',$recommend_goods);  //品牌列表
        $this->assign('brand_list',$brand_list);            //推荐商品
        if(I('is_ajax')){
            return $this->fetch('ajaxBrandstreet');
        }
        return $this->fetch();
    }
	/*积分兑换优惠券*/
	public function exchange_coupon(){
		$user=$this->getUserByToken(I('token'));
		$user_id=$user['user_id'];
		$coupon_id=I('coupon_id/d',0);
		$coupon=M('coupon')->where('id='.$coupon_id.' and type=5')->find();
		if(empty($coupon)){
			$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = '无效优惠券';
			exit(json_encode($msg));
		}
		if($coupon['createnum']>0){
			$remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
			if($remain<=0){
				$msg['ReturnCode'] = 104;
				$msg['ReturnMsg'] = '优惠券数量不足';
				exit(json_encode($msg));
			}
		}
		$pay_points=M('Users')->where('user_id='.$user_id)->getField('pay_points');
		if($pay_points<$coupon['points']){
			$msg['ReturnCode'] = 118;
			$msg['ReturnMsg'] = '您的积分不足';
			exit(json_encode($msg));
		}
		/*扣除会员积分并插入记录*/
		accountLog($user_id, 0, ($coupon['points'] * -1), "兑换优惠券");
		/*插入优惠券记录*/
		$insert_coupon=array(
			'cid'      => $coupon['id'],
			'type'     => 5,
			'uid'      => $user_id,
			'send_time'=> time()
		);
		M('coupon_list')->add($insert_coupon);
		/*更新红包信息*/
		$update_coupon['send_num']=$coupon['send_num']-1;
		$update_coupon['id']=$coupon['id'];
		DB::name('coupon')->update($update_coupon);
		$msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '兑换成功';
		exit(json_encode($msg));    	
    }
    /**
     * 用户收藏某一件商品
     * @param type $goods_id
     */
    public function collect_goods()
    {
    	$goodsLogic = new \app\home\logic\GoodsLogic();
    	$user=$this->getUserByToken(I('token'));
        $goods_id = I('goods_id/d');
        $result = $goodsLogic->collect_goods($user['user_id'],$goods_id);
        if($result['status']<0){
        	$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = $result['msg'];
			exit(json_encode($msg));    
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = $result['msg'];
			exit(json_encode($msg));    
        }
    }
    /*
     *取消收藏
     */
    public function cancel_collect()
    {
        $user=$this->getUserByToken(I('token'));
        $goods_id = I('goods_id/d');
        if (M('goods_collect')->where(['goods_id' => $goods_id, 'user_id' => $user['user_id']])->delete()) {
            $msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '取消收藏成功';
			exit(json_encode($msg)); 
        } else {
            $msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = '取消收藏失败';
			exit(json_encode($msg)); 
        }
    }
    /**
     * 商品物流配送和运费
     */
    public function dispatching()
    {       
    	$goodsLogic = new \app\home\logic\GoodsLogic(); 
        $goods_id = I('goods_id/d');
        $region_id = I('region_id/d');
        $dispatching_data = $goodsLogic->getGoodsDispatching($goods_id,$region_id);
        if($dispatching_data['status']==1){
        	foreach($dispatching_data['result'] as $k=>$v){
	        	unset($dispatching_data['result'][$k]['shipping_area_id']);
	        	unset($dispatching_data['result'][$k]['shipping_code']);
	        	unset($dispatching_data['result'][$k]['config']);
	        	unset($dispatching_data['result'][$k]['update_time']);
	        	unset($dispatching_data['result'][$k]['is_default']);
	        	unset($dispatching_data['result'][$k]['shipping_area_name']);
	        }
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = $dispatching_data['msg'];
			$msg['Result'] = $dispatching_data['result'];
			exit(json_encode($msg)); 
        }else{
        	$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = $dispatching_data['msg'];
			exit(json_encode($msg)); 
        }
    }

    /**
     * 看相似
     * $author lxl
     * $time 2017-1
     */
//    public function similar(){
//        $good_id = I('id/d','');
//        $now_good = M('goods')->where("goods_id=$good_id")->find();   //当前商品
//        $where_id_list = M('goods')->field('cat_id,spec_type')->where("goods_id=$good_id")->find(); //相似条件，分类id,类型id
//        $count = M('goods')->where($where_id_list) ->count();    //符合条件的总数
//        $pagesize = C('PAGESIZE');  //每页显示数
//        $Page = new Page($count,$pagesize);
//        $goods_list = M('goods')->where($where_id_list) ->limit($Page->firstRow.','.$Page->listRows)->select();  //获取相似商品
//        $this->assign('goods_list',$goods_list);
//        $this->assign('now_good',$now_good);
//        if(I('is_ajax')){
//            return $this->fetch('ajax_similar');
//        }
//        return $this->fetch();
//    }
}