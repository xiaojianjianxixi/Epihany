<?php

namespace app\api\controller;
use think\Db;
class Cart extends ApiBase {
    
    public $cartLogic; // 购物车逻辑操作类    
    public $user_id = 0;
    public $user = array();        
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();          
        $this->cartLogic = new \app\home\logic\CartLogic();
        /*if(session('?user'))
        {
        	$user = session('user');
                $user = M('users')->where("user_id", $user['user_id'])->find();
                session('user',$user);  //覆盖session 中的 user               			                
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('user',$user); //存储用户信息
                // 给用户计算会员价 登录前后不一样
                if($user){
                    $user[discount] = (empty($user[discount])) ? 1 : $user[discount];
                    DB::execute("update `__PREFIX__cart` set member_goods_price = goods_price * {$user[discount]} where (user_id ={$user[user_id]} or session_id = '{$this->session_id}') and prom_type = 0");
                }                 
         }*/            
    }
    
    public function cart(){
        //获取热卖商品
        $hot_goods = M('Goods')->where('is_hot=1 and is_on_sale=1')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();
        $this->assign('hot_goods',$hot_goods);
        return $this->fetch('cart');
    }
    /**
     * 将商品加入购物车
     */
    function addCart()
    {
    	$user=$this->getUserByToken(I('token'));
        $goods_id = I("gid/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $goods_spec = I("goods_spec"); // 商品规格                
        if($goods_spec){
        	$goods_spec=explode('_',$goods_spec);
        }else{
        	$goods_spec=array();
        }
        $unique_id = I("token"); // 唯一id  类似于 pc 端的session id
        $user_id = $user['user_id']; // 用户id        
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$unique_id,$user_id); // 将商品加入购物车
        if($result['status']<=0){
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = $result['msg'];
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = $result['msg'];
        }
        exit(json_encode($msg)); 
    }
    /**
     * 将商品加入购物车(立即购买)
     */
    function addCartBuy()
    {
    	$user=$this->getUserByToken(I('token'));
    	/*清空购物车*/
    	M('CartBuy')->where('user_id='.$user['user_id'])->delete();
        $goods_id = I("gid/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $goods_spec = I("goods_spec"); // 商品规格                
        if($goods_spec){
        	$goods_spec=explode('_',$goods_spec);
        }else{
        	$goods_spec=array();
        }
        $unique_id = I("token"); // 唯一id  类似于 pc 端的session id
        $user_id = $user['user_id']; // 用户id        
        $result = $this->cartLogic->addCartBuy($goods_id, $goods_num, $goods_spec,$unique_id,$user_id); // 将商品加入购物车
        if($result['status']<=0){
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = $result['msg'];
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = $result['msg'];
        }
        exit(json_encode($msg)); 
    }
    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $goods_id = I("goods_id/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $goods_spec = I("goods_spec/a",array()); // 商品规格
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id); // 将商品加入购物车
        exit(json_encode($result));
    }

    /*
     * 请求获取购物车列表
     */
    public function cartList()
    {
    	$user=$this->getUserByToken(I('token'));
        $result = $this->cartLogic->cartList($user, I('token'),1);
        if($result['status']<=0){
        	$msg['ReturnCode'] = 108;
			$msg['ReturnMsg'] = $result['msg'];
			exit(json_encode($msg));
        }else{
        	$cartList=$result['result']['cartList'];
        	$cart=array();
        	if(!empty($cartList)){
        		foreach($cartList as $k=>$v){
	        		$thumb=goods_thum_images($v['goods_id']);
			  		if($thumb){
			  			$thumb=C('HeadUrl').$thumb;
			  		}
	        		$tmp=array(
	        			'cid'              => $v['id'],
	        			'goods_id'         => $v['goods_id'],
	        			'goods_name'       => $v['goods_name'],
	        			'market_price'     => $v['market_price'],
	        			'goods_price'      => $v['goods_price'],
	        			'member_goods_price' => $v['member_goods_price'],
	        			'goods_num'        => $v['goods_num'],
	        			'spec_key'         => $v['spec_key'],
	        			'spec_key_name'    => $v['spec_key_name'],
	        			'goods_fee'        => $v['goods_fee'],
	        			'store_count'      => $v['store_count'],
	        			'thumb'            => $thumb,
	        			'selected'         => $v['selected']
	        		);
	        		$cart[]=$tmp;
	        	}
        	}
        	$res=array(
        		'CartList'=>$cart,
        		'TotalPrice'=>$result['result']['total_price']
        	);
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '获取成功';
			$msg['Result'] = $res;
        	exit(json_encode($msg)); 
        }
    }
    /*购物车修改*/
    public function changecart(){
    	$user=$this->getUserByToken(I('token'));
    	$cart_id=I('cid/d',0);
    	$goods_num=I('goods_num');
    	$goods_select=I('selected');
    	$goods_del=I('del');
    	$select_all=I('selected_all');
    	$con['id']=$cart_id;
    	$con['user_id']=$user['user_id'];
    	$cart_info=M('Cart')->where($con)->find();
    	if(empty($cart_info)&&empty($select_all)){
    		$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = '无效参数';
        	exit(json_encode($msg)); 
    	}else{
    		if(!empty($select_all)){
    			$update['selected']=$select_all==1?1:0;
    			$where['user_id']=$user['user_id'];
    			$res=M('Cart')->where($where)->save($update);
    		}
    		if(!empty($goods_num)){
    			$update['goods_num']=$goods_num<=0?1:$goods_num;
    		}
    		if(!empty($goods_del)){
				$where['id']=$cart_id;
				$res=M('Cart')->where($where)->delete();
			}
    		if(empty($goods_del)){
    			if($goods_select>0){
    				$update['selected']=$goods_select>1?0:1;
    			}
    			if(!empty($update)){
    				$where['id']=$cart_id;
	    			$res=M('Cart')->where($where)->save($update);
    			}
    		}
    		$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '操作成功';
        	/*exit(json_encode($msg)); */
    	}
    	$result = $this->cartLogic->cartList($user, I('token'),1);
        if($result['status']<=0){
        	$msg['ReturnCode'] = 108;
			$msg['ReturnMsg'] = $result['msg'];
			exit(json_encode($msg));
        }else{
        	$cartList=$result['result']['cartList'];
        	$cart=array();
        	if(!empty($cartList)){
        		foreach($cartList as $k=>$v){
	        		$thumb=goods_thum_images($v['goods_id']);
			  		if($thumb){
			  			$thumb=C('HeadUrl').$thumb;
			  		}
	        		$tmp=array(
	        			'cid'              => $v['id'],
	        			'goods_id'         => $v['goods_id'],
	        			'goods_name'       => $v['goods_name'],
	        			'market_price'     => $v['market_price'],
	        			'goods_price'      => $v['goods_price'],
	        			'member_goods_price' => $v['member_goods_price'],
	        			'goods_num'        => $v['goods_num'],
	        			'spec_key'         => $v['spec_key'],
	        			'spec_key_name'    => $v['spec_key_name'],
	        			'goods_fee'        => $v['goods_fee'],
	        			'store_count'      => $v['store_count'],
	        			'thumb'            => $thumb,
	        			'selected'         => $v['selected']
	        		);
	        		$cart[]=$tmp;
	        	}
        	}
        	$res=array(
        		'CartList'=>$cart,
        		'TotalPrice'=>$result['result']['total_price']
        	);
			$msg['Result'] = $res;
        	exit(json_encode($msg)); 
        }
    } 

    /**
     * 购物车第二步确定页面
     */
    public function cart2()
    {
    	$user=$this->getUserByToken(I('token'));
        $address = M('user_address')->where(['user_id'=>$user['user_id'],'is_default'=>1])->find();
        $type=I('type','cart');
        if($type=='buy'){
        	//立即购买
			$count = M('CartBuy')->where(['user_id' => $user['user_id'] , 'selected' => 1])->count();
        	if($count<=0){
	        	$msg['ReturnCode'] = 109;
				$msg['ReturnMsg'] = '您的购物车没有选中商品';
	        	exit(json_encode($msg)); 
	        }
        	$result = $this->cartLogic->cartListbuy($user,I('token'),1,1); // 获取购物车商品
        }else{
        	/*购物车购买*/
        	if($this->cartLogic->cart_count($user['user_id'],1) == 0 ){
	        	$msg['ReturnCode'] = 109;
				$msg['ReturnMsg'] = '您的购物车没有选中商品';
	        	exit(json_encode($msg)); 
	        }
	        $result = $this->cartLogic->cartList($user,I('token'),1,1); // 获取购物车商品
        }
        $cartList=array();
		if(!empty($result['cartList'])){
			foreach($result['cartList'] as $k=>$v){
				$thumb=goods_thum_images($v['goods_id']);
		  		if($thumb){
		  			$thumb=C('HeadUrl').$thumb;
		  		}
        		$tmp=array(
        			'cid'              => $v['id'],
        			'goods_id'         => $v['goods_id'],
        			'goods_name'       => $v['goods_name'],
        			'market_price'     => $v['market_price'],
        			'goods_price'      => $v['goods_price'],
        			'member_goods_price' => $v['member_goods_price'],
        			'goods_num'        => $v['goods_num'],
        			'spec_key'         => $v['spec_key'],
        			'spec_key_name'    => $v['spec_key_name'],
        			'goods_fee'        => $v['goods_fee'],
        			'store_count'      => $v['store_count'],
        			'thumb'            => $thumb,
        		);
        		if($v['selected']==1){
        			$cartList[]=$tmp;
        		}
			}
		}
		$total_price=$result['total_price'];
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->field('code,name,desc,type,icon')->cache(true,TPSHOP_CACHE_TIME)->select();// 物流公司
        if(!empty($shippingList)){
	        	foreach($shippingList as $k=>$v){
				if($v['icon']){
					$shippingList[$k]['icon']=C('HeadUrl').'/plugins/shipping/'.$v['code'].'/'.$v['icon'];
				}	
	        }
        }
        $sql = "select c1.name,c1.money,c1.condition, c2.id from __PREFIX__coupon as c1 inner join __PREFIX__coupon_list as c2  on c2.cid = c1.id and c1.type in(0,1,2,3,5) and order_id = 0  where c2.uid = {$user['user_id']} and ".time()." < c1.use_end_time and c1.condition <= {$result['total_price']['total_fee']}";
        $couponList = DB::query($sql);
        /*我的地址*/
        $address_lists = get_user_address_list($user['user_id']);
        $region_list = get_region_list();
        foreach($address_lists as $k=>$v){
        	unset($address_lists[$k]['user_id']);
        	unset($address_lists[$k]['is_pickup']);
        	unset($address_lists[$k]['twon']);
        	$address_lists[$k]['province_name']=$region_list[$v['province']]['name'];
        	$address_lists[$k]['city_name']=$region_list[$v['city']]['name'];
        	$address_lists[$k]['district_name']=$region_list[$v['district']]['name'];
        }
        if(empty($address)){
        	$address=$address_lists[0];
        }else{
        	unset($address['user_id']);
        	unset($address['is_pickup']);
        	unset($address['twon']);
        	$address['province_name']=$region_list[$v['province']]['name'];
        	$address['city_name']=$region_list[$v['city']]['name'];
        	$address['district_name']=$region_list[$v['district']]['name'];
        }
        $res['pay_points']=$user['pay_points'];
        $res['addressList']=$address_lists;
        $res['address']=$address;
        $res['couponList']=$couponList;
        $res['shippingList']=$shippingList;
        $res['totalPrice']=$total_price;
        $res['cartList']=$cartList;
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result'] = $res;
    	exit(json_encode($msg)); 
    }

    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
        $user=$this->getUserByToken(I('token'));
        $address_id = I("address_id/d"); //  收货地址id
        $shipping_code =  I("shipping_code"); //  物流编号        
        $invoice_type = I('invoice_type',0);//发票类型  0为个人   1为企业   
        $invoice_tax = I('invoice_tax');//税号
        $invoice_title = I('invoice_title'); // 发票
        $couponTypeSelect =  I("couponTypeSelect"); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
        $coupon_id =  I("coupon_id/d"); //  优惠券id
        $couponCode =  I("couponCode"); //  优惠券代码
        $pay_points =  I("pay_points/d",0); //  使用积分
        $user_money =  I("user_money/f",0); //  使用余额 暂时没有余额 lqw 2017年7月19日10:27:15
        $user_note = trim(I('user_note'));   //买家留言
        $user_money = $user_money ? $user_money : 0;
		$type=I('type','cart');
        if($type=='buy'){
        	//立即购买
			$count = M('CartBuy')->where(['user_id' => $user['user_id'] , 'selected' => 1])->count();
        	if($count<=0){
	        	$msg['ReturnCode'] = 109;
				$msg['ReturnMsg'] = '您的购物车没有选中商品';
	        	exit(json_encode($msg)); 
	        }
	        $order_goods = M('CartBuy')->where(['user_id'=>$user['user_id'],'selected'=>1])->select();
        }else{
        	/*购物车购买*/
        	if($this->cartLogic->cart_count($user['user_id'],1) == 0 ){
	        	$msg['ReturnCode'] = 109;
				$msg['ReturnMsg'] = '您的购物车没有选中商品';
	        	exit(json_encode($msg)); 
	        }
	        $order_goods = M('cart')->where(['user_id'=>$user['user_id'],'selected'=>1])->select();
        }
        if(!$address_id){
        	$msg['ReturnCode'] = 110;
			$msg['ReturnMsg'] = '请先填写收货人信息';
        	exit(json_encode($msg));
        }
        if(!$shipping_code){
        	$msg['ReturnCode'] = 111;
			$msg['ReturnMsg'] = '请选择物流信息';
        	exit(json_encode($msg));
        }
		$address = M('UserAddress')->where("address_id", $address_id)->find();
        $result = calculate_price($user['user_id'],$order_goods,$shipping_code,0,$address[province],$address[city],$address[district],$pay_points,$user_money,$coupon_id,$couponCode);
		if($result['status'] < 0){
			$msg['ReturnCode'] = 112;
			$msg['ReturnMsg'] = $result['msg'];
        	exit(json_encode($msg));
		}	
		// 订单满额优惠活动		                
        $order_prom = get_order_promotion($result['result']['order_amount']);
        $result['result']['order_amount'] = $order_prom['order_amount'] ;
        $result['result']['order_prom_id'] = $order_prom['order_prom_id'] ;
        $result['result']['order_prom_amount'] = $order_prom['order_prom_amount'] ;
			
        $car_price = array(
            'postFee'      => $result['result']['shipping_price'], // 物流费
            'couponFee'    => $result['result']['coupon_price'], // 优惠券            
            'balance'      => $result['result']['user_money'], // 使用用户余额
            'pointsFee'    => $result['result']['integral_money'], // 积分支付
            'payables'     => $result['result']['order_amount'], // 应付金额
            'goodsFee'     => $result['result']['goods_price'],// 商品价格
            'order_prom_id' => $result['result']['order_prom_id'], // 订单优惠活动id
            'order_prom_amount' => $result['result']['order_prom_amount'], // 订单优惠活动优惠了多少钱            
        );
       
        // 提交订单        
        if($_REQUEST['act'] == 'submit_order')
        {  
            if(empty($coupon_id) && !empty($couponCode)){
                $coupon_id = M('CouponList')->where("code", $couponCode)->getField('id');
            }
            if($type=='buy'){
            	$result = $this->cartLogic->addOrderBuy($user['user_id'],$address_id,$shipping_code,$invoice_title,$coupon_id,$car_price,$user_note,$invoice_tax,$invoice_type); // 添加订单
            }else{
            	$result = $this->cartLogic->addOrder($user['user_id'],$address_id,$shipping_code,$invoice_title,$coupon_id,$car_price,$user_note,$invoice_tax,$invoice_type); // 添加订单
            }
            if($result['status']<0){
            	/*下单失败*/
            	$msg['ReturnCode'] = 113;
				$msg['ReturnMsg'] = $result['msg'];
            }else{
            	/*下单成功*/
            	$msg['ReturnCode'] = 100;
				$msg['ReturnMsg'] = $result['msg'];
				$msg['Result']['orderId']=$result['result'];
            }
            exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '计算成功';
			$msg['Result']=$car_price;
        	exit(json_encode($msg));
        }
    }	
    /*
     * 订单支付页面
     */
    public function cart4(){

        $order_id = I('order_id/d');
        $order = M('Order')->where("order_id", $order_id)->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/User/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }

        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            //微信浏览器
            if($order['order_prom_type'] == 4){
                //预售订单
                $payment_where['code'] = 'weixin';
            }else{
                $payment_where['code'] = array('in',array('weixin','cod'));
            }
        }else{
            if($order['order_prom_type'] == 4){
                //预售订单
                $payment_where['code'] = array('neq','cod');
            }
            $payment_where['scene'] = array('in',array('0','1'));
        }
        $paymentList = M('Plugin')->where($payment_where)->select();
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
            //判断当前浏览器显示支付方式
            if(($key == 'weixin' && !is_weixin()) || ($key == 'alipayMobile' && is_weixin())){
                unset($paymentList[$key]);
            }
        }

        $bank_img = include APP_PATH.'home/bank.php'; // 银行对应图片
        $payment = M('Plugin')->where("`type`='payment' and status = 1")->select();
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('order',$order);
        $this->assign('bankCodeList',$bankCodeList);
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));
        return $this->fetch();
    }
    /*获取支付方式*/
	public function getPaymentList(){
		$user=$this->getUserByToken(I('token'));
        $order_id = I('order_id/d');
        $con['order_id']=$order_id;
        $con['user_id']=$user['user_id'];
        $order = M('Order')->where("order_id", $order_id)->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $msg['ReturnCode'] = 120;
			$msg['ReturnMsg'] = '订单已完成支付';
        	exit(json_encode($msg));
        }
		if($order['order_prom_type'] == 4){
            //预售订单
            $payment_where['code'] = array('neq','cod');
        }
        $payment_where['status'] = 1;
        $payment_where['scene'] = array('in',array('0','1'));
        $paymentList = M('Plugin')->where($payment_where)->field('code,name')->select();
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '计算成功';
		$msg['Result']=array(
			'Money'=>$order['order_amount'],
			'OrderSn'=>$order['order_sn'],
			'List'=>$paymentList
		);
    	exit(json_encode($msg));
    }

    /*
    * ajax 请求获取购物车列表
    */
    public function ajaxCartList()
    {
        $post_goods_num = I("goods_num/a"); // goods_num 购物车商品数量
        $post_cart_select = I("cart_select/a"); // 购物车选中状态
        $where['session_id'] = $this->session_id; // 默认按照 session_id 查询
        // 如果这个用户已经等了则按照用户id查询
        if($this->user_id){
            unset($where);
            $where['user_id'] = $this->user_id;
        }
        $cartList = M('Cart')->where($where)->getField("id,goods_num,selected,prom_type,prom_id");

        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {                
                $data['goods_num'] = $val < 1 ? 1 : $val;
                if($cartList[$key]['prom_type'] == 1) //限时抢购 不能超过购买数量
                {
                    $flash_sale = M('flash_sale')->where("id", $cartList[$key]['prom_id'])->find();
                    $data['goods_num'] = $data['goods_num'] > $flash_sale['buy_limit'] ? $flash_sale['buy_limit'] : $data['goods_num'];
                }
                
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ;
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected']))
                    M('Cart')->where("id", $key)->save($data);
            }
            $this->assign('select_all', input('post.select_all')); // 全选框
        }

        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1);        
        if(empty($result['total_price']))
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0, 'atotal_fee' =>0, 'acut_fee' =>0, 'anum' => 0);
        
        $this->assign('cartList', $result['cartList']); // 购物车的商品                
        $this->assign('total_price', $result['total_price']); // 总计
        return $this->fetch('ajax_cart_list');
    }

    /*
 * ajax 获取用户收货地址 用于购物车确认订单页面
 */
    public function ajaxAddress(){

        $regionList = M('Region')->getField('id,name');

        $address_list = M('UserAddress')->where("user_id", $this->user_id)->select();
        $c = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;

        $this->assign('regionList', $regionList);
        $this->assign('address_list', $address_list);
        return $this->fetch('ajax_address');
    }

    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {
        $ids = I("ids"); // 商品 ids
        $result = M("Cart")->where("id","in",$ids)->delete(); // 删除id为5的用户数据
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态
        exit(json_encode($return_arr));
    }

}
