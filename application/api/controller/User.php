<?php

namespace app\api\controller;
use app\home\logic\UsersLogic;
use app\home\model\Message;
use think\Page;
use think\Request;
use think\Verify;
use think\db;

class User extends ApiBase
{
    /*
     * 用户中心首页
     */
    public function index()
    {
    	$user=$this->getUserByToken(I('token'));
    	$goods_collect_count=M('goods_collect')->alias('gc')
            ->join('goods g','g.goods_id=gc.goods_id','LEFT')
            ->where("gc.user_id=".$user['user_id'].' and g.goods_id >0')
            ->count();
        //$goods_collect_count = M('goods_collect')->where("user_id", $user['user_id'])->count(); // 我的商品收藏
        $wait_pay = M('order')->where("user_id=".$user['user_id']." and pay_status =0 and order_status = 1  and pay_code != 'cod'")->count(); //我的待付款 (改)
        $wait_receive = M('order')->where("user_id=".$user['user_id']." and order_status= 1 and shipping_status= 1")->count(); //我的待收货 (改)
        $comment = DB::query("select COUNT(1) as comment from __PREFIX__order_goods as og left join __PREFIX__order as o on o.order_id = og.order_id where o.order_status in(2,4) and o.user_id = ".$user['user_id']." and og.is_send = 1 and og.is_comment = 0 ");  //我的待评论订单
        $wait_comment = $comment[0][comment];
        $wait_return = M('ReturnGoods')->where("user_id=".$user['user_id']." and status <2 ")->count(); //退货退款 
        if($user['head_pic']){
        	$user_info['head_pic']=C('HeadUrl').$user['head_pic'];
        }else{
        	$user_info['head_pic']='';
        }
        $user_info['nickname']=$user['nickname'];
        $user_info['sex']=$user['sex'];
        $user_info['mobile']=$user['mobile'];
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
        $count= DB::name('user_message')
        ->alias('um')
        ->field('um.rec_id, um.message_id, m.message, m.send_time')
        ->join('__MESSAGE__ m','um.message_id = m.message_id','LEFT')
        ->where($user_system_message_where)
        ->count('um.rec_id');
        $result=array(
        	'userInfo'=>$user_info,
        	'waitPay'=>$wait_pay,
        	'waitReceive'=>$wait_receive,
        	'waitComment'=>$wait_comment,
        	'waitReturn'=>$wait_return,
        	'goodsCollect'=>$goods_collect_count,
        	'messageCount'=>$count
        );
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        setcookie('cn', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
        //$this->success("退出成功",U('Mobile/Index/index'));
        header("Location:" . U('Mobile/Index/index'));
        exit();
    }

    /*
     * 账户资金
     */
    public function account()
    {
        $user = session('user');
        //获取账户资金记录
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id, I('get.type'));
        $account_log = $data['result'];

        $this->assign('user', $user);
        $this->assign('account_log', $account_log);
        $this->assign('page', $data['show']);

        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_account_list');
            exit;
        }
        return $this->fetch();
    }

    /**
     * 优惠券
     */
    public function coupon()
    {
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
    	$user=$this->getUserByToken(I('token'));
    	$type=I('type/d',0);
    	$where['l.uid']=$user['user_id'];
        $where['l.order_id']=0;
        $where['c.use_end_time']=array('gt',time());
        if($type == 1){
            //已使用
            $where['l.order_id']=array('gt',0);
            $where['l.use_time']=array('gt',0);
        }
        if($type == 2){
            //已过期
            $where['c.use_end_time']=array('lt',time());
        }   
        //获取数量
        $count=Db::table('tp_coupon_list')
             ->alias('l')
             ->join('tp_coupon c', 'l.cid =  c.id')
             ->field('l.*,c.name,c.money,c.use_end_time,c.condition')
             ->where($where)
             ->count();
        $list=Db::table('tp_coupon_list')
             ->alias('l')
             ->join('tp_coupon c', 'l.cid =  c.id')
             ->field('l.*,c.name,c.money,c.use_end_time,c.condition')
             ->where($where)
             ->limit(($page-1)*$pagesize,$pagesize)
             ->select();
        foreach($list as $k=>$v){
        	unset($list[$k]['cid']);
        	unset($list[$k]['type']);
        	unset($list[$k]['uid']);
        	$list[$k]['send_time']=date('Y-m-d H:i:s',$v['send_time']);
        	$list[$k]['use_end_time']=date('Y-m-d H:i:s',$v['use_end_time']);
        }
        $result['List']=$list;
        $result['Count']=$count;
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }

    /**
     * 确定订单的使用优惠券
     * @author lxl
     * @time 2017
     */
    public function checkcoupon()
    {
        $cartLogic = new \app\home\logic\CartLogic();
        // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的
        $result = $cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品
        if(I('type') == ''){
            $where = " c2.uid = {$this->user_id} and ".time()." < c1.use_end_time and c1.condition <= {$result['total_price']['total_fee']} ";
        }
        if(I('type') == '1'){
           $where = " c2.uid = {$this->user_id} and c1.use_end_time < ".time()." or {$result['total_price']['total_fee']}  < c1.condition ";
        }

        $coupon_list = DB::name('coupon')
            ->alias('c1')
            ->field('c1.name,c1.money,c1.condition,c1.use_end_time, c2.*')
            ->join('coupon_list c2','c2.cid = c1.id and c1.type in(0,1,2,3) and order_id = 0','LEFT')
            ->where($where)
            ->select();
        $this->assign('coupon_list', $coupon_list); // 优惠券列表
        return $this->fetch();
    }

    /**
     * 登录
     */
    public function do_login()
    {
        $username = I('username');
        $password = I('password');
        $username = trim($username);
        $password = trim($password);
        $logic = new UsersLogic();
        $res = $logic->app_login($username, $password);
        if ($res['status'] == 1) {
            $user=$res['result'];
            $result=array(
            	'pay_points'=>$user['pay_points'],
            	'mobile'=>$user['mobile'],
            	'nickname'=>$user['nickname'],
            	'level'=>$user['level'],
            	'level_name'=>$user['level_name'],
            	'token'=>$user['token'],
            );
			$msg['ReturnCode'] = 100;//
			$msg['ReturnMsg'] = '登陆成功';
			$msg['Result']=$result;
			exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 102;//
			$msg['ReturnMsg'] = $res['msg'];
			exit(json_encode($msg));
        }
    }

    /**
     *  注册
     */
    public function reg()
    {
        $reg_sms_enable = tpCache('sms.regis_sms_enable');
        $reg_smtp_enable = tpCache('sms.regis_smtp_enable');
        if ($_REQUEST) {
            $logic = new UsersLogic();
            $username = I('post.username', '');
            $password = I('post.password', '');
            $password2 = I('post.password2', '');
            //是否开启注册验证码机制
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 1);
            if(check_mobile($username)){
                $check_code = $logic->check_validate_code($code, $username, 'phone', 0 , $scene);
                if($check_code['status'] != 1){
                	$msg['ReturnCode'] = 104;//
					$msg['ReturnMsg'] = $check_code['msg'];
					exit(json_encode($msg));
                }
            }
            //是否开启注册邮箱验证码机制
            if(check_email($username)){
                $check_code = $logic->check_validate_code($code, $username);
                if($check_code['status'] != 1){
                	$msg['ReturnCode'] = 104;//
					$msg['ReturnMsg'] = $check_code['msg'];
					exit(json_encode($msg));
                }
            }
            $data = $logic->reg($username, $password, $password2);
            if ($data['status'] != 1){
            	$msg['ReturnCode'] = 104;
				$msg['ReturnMsg'] = $data['msg'];
				exit(json_encode($msg));
            }
            /*执行登陆*/
            $res = $logic->app_login($username, $password);
	        if ($res['status'] == 1) {
	            $user=$res['result'];
	            /*绑定*/
	            if(!empty($_REQUEST['unionid'])&&!empty($_REQUEST['oauth'])){
		        	$data=array(
			    		'user_id'=>$user['user_id'],
			    		'unionid'=>$_REQUEST['unionid'],
			    		'oauth'=>$_REQUEST['oauth']
			    	);
		        	$data = $logic->thirdBind($data);
			        if($data['status'] != 1){
			        	$msg['ReturnCode'] = 122;
						$msg['ReturnMsg'] = $data['msg'];
						exit(json_encode($msg));
			        }
	            }
	            $result=array(
	            	'pay_points'=>$user['pay_points'],
	            	'mobile'=>$user['mobile'],
	            	'nickname'=>$user['nickname'],
	            	'level'=>$user['level'],
	            	'level_name'=>$user['level_name'],
	            	'token'=>$user['token'],
	            );
				$msg['ReturnCode'] = 100;//
				$msg['ReturnMsg'] = '注册成功';
				$msg['Result']=$result;
				exit(json_encode($msg));
	        }else{
	        	$msg['ReturnCode'] = 102;//
				$msg['ReturnMsg'] = $res['msg'];
				exit(json_encode($msg));
	        }
        }
    }

    /*
     * 订单列表
     */
    public function order_list()
    {
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
    	$user=$this->getUserByToken(I('token'));
        $where = ' user_id=' . $user['user_id'];
        //条件搜索
        if(I('get.type')){
            $where .= C(strtoupper(I('type')));
        }   
        $count = M('order')->where($where)->count();
        $order_str = "order_id DESC";
        $order_list = M('order')->order($order_str)->where($where)->limit(($page-1)*$pagesize,$pagesize)->select();
        //获取订单商品
        $model = new UsersLogic();
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }
        //统计订单商品数量
        foreach ($order_list as $key => $value) {
            $count_goods_num = '';
            foreach ($value['goods_list'] as $kk => $vv) {
                $count_goods_num += $vv['goods_num'];
            }
            $order_list[$key]['count_goods_num'] = $count_goods_num;
        }
        $order_list=$this->getorderlist($order_list);
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=array(
			'List'=>!empty($order_list)?$order_list:array(),
			'Count'=>$count
		);
		exit(json_encode($msg));
    }


    /*
     * 订单列表
     */
    public function ajax_order_list()
    {

    }

    /*
     * 订单详情
     */
    public function order_detail()
    {
    	$user=$this->getUserByToken(I('token'));
        $id = I('order_id/d');
        $map['order_id'] = $id;
        $map['user_id'] = $user['user_id'];
        $order_info = M('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
        if (!$order_info) {
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = '没有获取到订单信息';
			exit(json_encode($msg));
        }
        //获取订单商品
        $model = new UsersLogic();
        $data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];
        $tmp=array(
				'order_id'=>$order_info['order_id'],
				'order_sn'=>$order_info['order_sn'],
				'order_status'=>$order_info['order_status'],
				'shipping_status'=>$order_info['shipping_status'],
				'consignee'=>$order_info['consignee'],
				'address'=>$order_info['address'],
				'mobile'=>$order_info['mobile'],
				'province'=>M('Region')->where('id='.$order_info['province'])->getField('name'),
				'city'=>M('Region')->where('id='.$order_info['city'])->getField('name'),
				'district'=>M('Region')->where('id='.$order_info['district'])->getField('name'),
				'add_time'=>date('Y-m-d H:i:s',$order_info['add_time']),
				'shipping_name'=>$order_info['shipping_name'],
				'pay_name'=>$order_info['pay_name'],
				'user_note'=>$order_info['user_note'],
				'pay_status'=>$order_info['pay_status'],
				'total_amount'=>$order_info['total_amount'],
				'shipping_price'=>$order_info['shipping_price'],
				'coupon_price'=>$order_info['coupon_price'],
				'integral_money'=>$order_info['integral_money'],
				'order_amount'=>$order_info['order_amount'],
				'order_prom_amount'=>$order_info['order_prom_amount'],
				'order_status_code'=>$order_info['order_status_code'],
				'order_status_desc'=>$order_info['order_status_desc'],
				'pay_btn'=>$order_info['pay_btn'],
				'cancel_btn'=>$order_info['cancel_btn'],
				'receive_btn'=>$order_info['receive_btn'],
				'comment_btn'=>$order_info['comment_btn'],
				'shipping_btn'=>$order_info['shipping_btn'],
				'return_btn'=>$order_info['return_btn'],
				'shipping_name'=>$order_info['shipping_name'],
				'invoice_tax'=>$order_info['tax'],
				'invoice_title'=>$order_info['invoice_title']
			);
		if(!empty($order_info['goods_list'])){
			$goods_list=array();
			$shipping_sn='';
			foreach($order_info['goods_list'] as $kg=>$vg){
				if($vg['delivery_id']>0&&empty($shipping_sn)){
					$shipping_sn=M('delivery_doc')->where('id='.$vg['delivery_id'])->getField('invoice_no');
					$tmp['invoice_no']=$shipping_sn;
				}
				$thumb=goods_thum_images($vg['goods_id'],300,300);
		  		if($thumb){
		  			$thumb=C('HeadUrl').$thumb;
		  		}
				$goods_list[]=array(
					'rec_id'=>$vg['rec_id'],
					'goods_id'=>$vg['goods_id'],
					'goods_name'=>$vg['goods_name'],
					'goods_sn'=>$vg['goods_sn'],
					'goods_num'=>$vg['goods_num'],
					'member_goods_price'=>$vg['member_goods_price'],
					'spec_key_name'=>$vg['spec_key_name'],
					'spec_key'=>$vg['spec_key'],
					'thumb'=>$thumb
				);
			}
			$tmp['goods_list']=$goods_list;
		}else{
			$tmp['goods_list']=array();
		}
		$order_info=$tmp;
        //$invoice_no = M('DeliveryDoc')->where("order_id", $id)->getField('invoice_no', true);
        //$order_info['invoice_no'] = implode(' , ', $invoice_no);
        //获取订单操作记录
        //$order_action = M('order_action')->where(array('order_id' => $id))->select();
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$order_info;
		exit(json_encode($msg));
    }

    public function express()
    {
        $order_id = I('get.order_id/d', 195);
        $order_goods = M('order_goods')->where("order_id", $order_id)->select();
        $delivery = M('delivery_doc')->where("order_id", $order_id)->find();
        $this->assign('order_goods', $order_goods);
        $this->assign('delivery', $delivery);
        return $this->fetch();
    }

    /*
     * 取消订单
     */
    public function cancel_order()
    {
    	$user=$this->getUserByToken(I('token'));
        $id = I('order_id/d');
        //检查是否有积分，余额支付
        $logic = new UsersLogic();
        $data = $logic->cancel_order($user['user_id'], $id);
        if ($data['status'] < 0){
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = $data['msg'];
			exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = $data['msg'];
			exit(json_encode($msg));
        }
    }

    /*
     * 用户地址列表
     */
    public function address_list()
    {
    	$user=$this->getUserByToken(I('token'));
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
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=array(
			'List'=>$address_lists
		);
		exit(json_encode($msg));
    }

    /*
     * 添加地址
     */
    public function add_address()
    {
    	$user=$this->getUserByToken(I('token'));
    	$address_id=I('address_id/d',0);
    	$post=array(
    		'consignee'=>urldecode(I('consignee')),
    		'province'=>I('province'),
    		'city'=>I('city'),
    		'district'=>I('district'),
    		'address'=>urldecode(I('address')),
    		'mobile'=>I('mobile'),
    	);
        $logic = new UsersLogic();
        $data = $logic->add_address($user['user_id'],$address_id, $post);
        if ($data['status'] != 1){
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = $data['msg'];
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '操作成功';
        }
        exit(json_encode($msg));
    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        $id = I('id/d');
        $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $this->user_id))->find();
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, $id, I('post.'));
            if ($_POST['source'] == 'cart2') {
                header('Location:' . U('/Mobile/Cart/cart2', array('address_id' => $id)));
                exit;
            } else
                $this->success($data['msg'], U('/Mobile/User/address_list'));
            exit();
        }
        //获取省份
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $c = M('region')->where(array('parent_id' => $address['province'], 'level' => 2))->select();
        $d = M('region')->where(array('parent_id' => $address['city'], 'level' => 3))->select();
        if ($address['twon']) {
            $e = M('region')->where(array('parent_id' => $address['district'], 'level' => 4))->select();
            $this->assign('twon', $e);
        }
        $this->assign('province', $p);
        $this->assign('city', $c);
        $this->assign('district', $d);
        $this->assign('address', $address);
        return $this->fetch();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default()
    {
        $user=$this->getUserByToken(I('token'));
    	$address_id=I('address_id/d',0);
        M('user_address')->where(array('user_id' => $user['user_id']))->save(array('is_default' => 0));
        $res = M('user_address')->where(array('user_id' => $user['user_id'], 'address_id' => $address_id))->save(array('is_default' => 1));
        if ($res!==false){
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '操作成功';
        }else{
        	$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = '操作失败';
        }
        exit(json_encode($msg));
    }

    /*
     * 地址删除
     */
    public function del_address()
    {
    	$user=$this->getUserByToken(I('token'));
        $id = I('address_id/d');
        $address = M('user_address')->where("address_id", $id)->find();
        $row = M('user_address')->where(array('user_id' => $user['user_id'], 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address2 = M('user_address')->where("user_id", $user['user_id'])->find();
            $address2 && M('user_address')->where("address_id", $address2['address_id'])->save(array('is_default' => 1));
        }
        if (!$row){
        	$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = '操作失败';
        	exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '操作成功';
        	exit(json_encode($msg));
        	//$this->success("操作成功", U('User/address_list'));
        }
    }

    /*
     * 评论晒单
     */
    public function comment()
    {
    	$user=$this->getUserByToken(I('token'));
        $user_id = $user['user_id'];
        $status = I('status');
        $logic = new UsersLogic();
        $result = $logic->get_comment($user_id, $status); //获取评论列表
        $list=array();
        if(!empty($result['result'])){
        	foreach($result['result']  as $k=>$v){
	        	$thumb=goods_thum_images($v['goods_id'],300,300);
		  		if($thumb){
		  			$thumb=C('HeadUrl').$thumb;
		  		}
	        	$tmp=array(
	        		'rec_id'=>$v['rec_id'],
	        		'order_id'=>$v['order_id'],
	        		'goods_id'=>$v['goods_id'],
	        		'goods_name'=>$v['goods_name'],
	        		'goods_price'=>$v['goods_price'],
	        		'spec_key'=>$v['spec_key'],
	        		'spec_key_name'=>$v['spec_key_name'],
	        		'add_time'=>date('Y-m-d H:i:s',$v['add_time']),
	        		'is_comment'=>$v['is_comment'],
	        		'thumb'=>$thumb
	        	);
	        	$list[]=$tmp;
	        }
        }
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$list;
        exit(json_encode($msg));
    }

    /*
     *添加评论
     */
    public function add_comment()
    {
    	$user_info=$this->getUserByToken(I('token'));
        // 晒图片
        $files = request()->file('comment_img_file');
        if(!empty($files)){
        	$save_url = 'public/upload/comment/' . date('Y', time()) . '/' . date('m-d', time());
	        foreach ($files as $file) {
	            // 移动到框架应用根目录/public/uploads/ 目录下
	            $info = $file->rule('uniqid')->validate(['size' => 1024 * 1024 * 5, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
	            if ($info) {
	                // 成功上传后 获取上传信息
	                // 输出 jpg
	                $comment_img[] = '/'.$save_url . '/' . $info->getFilename();
	            } else {
	                // 上传失败获取错误信息
	                $msg['ReturnCode'] = 114;
					$msg['ReturnMsg'] = '上传图片失败';
			        exit(json_encode($msg));
	            }
	        }
        }
        if (!empty($comment_img)) {
            $add['img'] = serialize($comment_img);
        }
        $logic = new UsersLogic();
        $add['goods_id'] = I('goods_id/d');
        $add['email'] = $user_info['email'];
        $hide_username = I('hide_username');
        if (empty($hide_username)) {
            $add['username'] = $user_info['nickname'];
        }
        $add['order_id'] = I('order_id/d');
        $add['service_rank'] = I('goods_rank');
        $add['deliver_rank'] = I('goods_rank');
        $add['goods_rank'] = I('goods_rank');
        $add['is_show'] = 0;//默认不显示 需后台审核
        $add['content'] = I('content');
        $add['add_time'] = time();
        $add['ip_address'] = getIP();
        $add['user_id'] = $user_info['user_id'];
        //添加评论
        $row = $logic->add_comment($add);
        if ($row['status'] == 1) {
            $msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '评论成功';
	        exit(json_encode($msg));
        } else {
        	$msg['ReturnCode'] = 115;
			$msg['ReturnMsg'] = $row['msg'];
	        exit(json_encode($msg));
        }
    }

    /*
     * 个人信息
     */
    public function userinfo()
    {
    	$userLogic = new UsersLogic();
    	$user=$this->getUserByToken(I('token'));
        I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
        I('post.sex') ? $post['sex'] = I('post.sex') : $post['sex'] = 0;  // 性别
        $files = request()->file('head_pic_file');
        if(!empty($files)){
        	$save_url = 'public/upload/head_pic/' . date('Y', time()) . '/' . date('m-d', time());
	        if(count($files)>1){
	        	foreach ($files as $file) {
		            // 移动到框架应用根目录/public/uploads/ 目录下
		            $info = $file->rule('uniqid')->validate(['size' => 1024 * 1024 * 3, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
		            if ($info) {
		                // 成功上传后 获取上传信息
		                $return_imgs[] = '/'.$save_url . '/' . $info->getFilename();
		            } else {
		                // 上传失败获取错误信息
		                $msg['ReturnCode'] = 114;
						$msg['ReturnMsg'] = '上传图片失败';
				        exit(json_encode($msg));
		            }
		        }
	        }else{
	        	$info = $files->rule('uniqid')->validate(['size' => 1024 * 1024 * 5, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
	            if ($info) {
	                $head_img[] = '/'.$save_url . '/' . $info->getFilename();
	            } else {
	                // 上传失败获取错误信息
	                $msg['ReturnCode'] = 114;
					$msg['ReturnMsg'] = '上传图片失败';
			        exit(json_encode($msg));
	            }
	        }
	        
        }
        if(!empty($head_img)){
        	$post['head_pic']=$head_img[0];
        }
        if (!$userLogic->update_info($user['user_id'], $post)){
        	$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = '修改失败';
	        exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '修改成功';
	        exit(json_encode($msg));
        }
    }

    /*
     * 邮箱验证
     */
    public function email_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['email_validated'] == 0)
            $step = 2;
        //原邮箱验证是否通过
        if ($user_info['email_validated'] == 1 && session('email_step1') == 1)
            $step = 2;
        if ($user_info['email_validated'] == 1 && session('email_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $email = I('post.email');
            $code = I('post.code');
            $info = session('email_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['email'] == $email || $info['code'] == $code) {
                if ($user_info['email_validated'] == 0 || session('email_step1') == 1) {
                    session('email_code', null);
                    session('email_step1', null);
                    if (!$userLogic->update_email_mobile($email, $this->user_id))
                        $this->error('邮箱已存在');
                    $this->success('绑定成功', U('Home/User/index'));
                } else {
                    session('email_code', null);
                    session('email_step1', 1);
                    redirect(U('Home/User/email_validate', array('step' => 2)));
                }
                exit;
            }
            $this->error('验证码邮箱不匹配');
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    /*
    * 手机验证
    */
    public function mobile_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['mobile_validated'] == 0)
            $step = 2;
        //原手机验证是否通过
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') == 1)
            $step = 2;
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $mobile = I('post.mobile');
            $code = I('post.code');
            $info = session('mobile_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['email'] == $mobile || $info['code'] == $code) {
                if ($user_info['email_validated'] == 0 || session('email_step1') == 1) {
                    session('mobile_code', null);
                    session('mobile_step1', null);
                    if (!$userLogic->update_email_mobile($mobile, $this->user_id, 2))
                        $this->error('手机已存在');
                    $this->success('绑定成功', U('Home/User/index'));
                } else {
                    session('mobile_code', null);
                    session('email_step1', 1);
                    redirect(U('Home/User/mobile_validate', array('step' => 2)));
                }
                exit;
            }
            $this->error('验证码手机不匹配');
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    /**
     * 用户收藏列表
     */
    public function collect_list()
    {
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
    	$user=$this->getUserByToken(I('token'));
    	$sql = "SELECT c.collect_id,c.add_time,g.goods_id,g.store_count,g.goods_name,g.shop_price,g.original_img,g.is_on_sale FROM __PREFIX__goods_collect c ".
            "inner JOIN __PREFIX__goods g ON g.goods_id = c.goods_id ".
            "WHERE c.user_id = ".$user['user_id'];
        $result = Db::query($sql);
        $count = count($result);
        //获取我的收藏列表
        $sql = "SELECT c.collect_id,c.add_time,g.goods_id,g.store_count,g.goods_name,g.shop_price,g.original_img,g.is_on_sale FROM __PREFIX__goods_collect c ".
            "inner JOIN __PREFIX__goods g ON g.goods_id = c.goods_id ".
            "WHERE c.user_id = ".$user['user_id'].
            " ORDER BY c.add_time DESC LIMIT ".($page-1)*$pagesize.' , '.$pagesize;
        $result = Db::query($sql);
        $list=array();
        foreach($result as $k=>$v){
        	$thumb=goods_thum_images($v['goods_id']);
	  		if($thumb){
	  			$thumb=C('HeadUrl').$thumb;
	  		}
        	$tmp=array(
        		'goods_id'=>$v['goods_id'],
        		'goods_name'=>$v['goods_name'],
        		'shop_price'=>$v['shop_price'],
        		'thumb'=>$thumb
        	);
        	$list[]=$tmp;
        }
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=array(
			'List'=>$list,
			'Count'=>$count
		);
        exit(json_encode($msg));
    }

    /*
     *取消收藏
     */
    public function cancel_collect()
    {
        $collect_id = I('collect_id/d');
        $user_id = $this->user_id;
        if (M('goods_collect')->where(['collect_id' => $collect_id, 'user_id' => $user_id])->delete()) {
            $this->success("取消收藏成功", U('User/collect_list'));
        } else {
            $this->error("取消收藏失败", U('User/collect_list'));
        }
    }

    /**
     * 我的留言
     */
    public function message_list()
    {
        C('TOKEN_ON', true);
        if (IS_POST) {
            $this->verifyHandle('message');

            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $user = session('user');
            $data['user_name'] = $user['nickname'];
            $data['msg_time'] = time();
            if (M('feedback')->add($data)) {
                $this->success("留言成功", U('User/message_list'));
                exit;
            } else {
                $this->error('留言失败', U('User/message_list'));
                exit;
            }
        }
        $msg_type = array(0 => '留言', 1 => '投诉', 2 => '询问', 3 => '售后', 4 => '求购');
        $count = M('feedback')->where("user_id", $this->user_id)->count();
        $Page = new Page($count, 100);
        $Page->rollPage = 2;
        $message = M('feedback')->where("user_id", $this->user_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $showpage = $Page->show();
        header("Content-type:text/html;charset=utf-8");
        $this->assign('page', $showpage);
        $this->assign('message', $message);
        $this->assign('msg_type', $msg_type);
        return $this->fetch();
    }

    /**积分明细*/
    public function points()
    {
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
    	$user=$this->getUserByToken(I('token'));
        $where = 'pay_points != 0 AND user_id='.$user['user_id'];
        $count = M('account_log')->where($where)->count();
        $logs = M('account_log')->where($where)->order('change_time desc')->limit(($page-1)*$pagesize,$pagesize)->select();
    	foreach($logs as $k=>$v){
    		unset($logs[$k]['user_id']);
    		unset($logs[$k]['user_money']);
    		unset($logs[$k]['frozen_money']);
    		$logs[$k]['change_time']=date('Y.m.d',$v['change_time']);
    	}
    	$result['List']=$logs;
    	$result['Count']=$count;
    	$result['UserPoints']=$user['pay_points'];
    	$msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }

    /*
     * 密码修改
     */
    public function password()
    {
		$user=$this->getUserByToken(I('token'));
        $userLogic = new UsersLogic();
        /*短信验证码*/
        $username = $user['mobile'];
        $code = I('mobile_code', '');
        $scene = I('scene', 6);
        $check_code = $userLogic->check_validate_code($code, $username, 'phone', 0 , $scene);
        if($check_code['status'] != 1){
        	$msg['ReturnCode'] = 104;//
			$msg['ReturnMsg'] = $check_code['msg'];
			exit(json_encode($msg));
        }
        $data = $userLogic->password($user['user_id'], I('old_password'), I('new_password'), I('confirm_password')); // 获取用户信息
        if ($data['status'] == -1){
        	$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = $data['msg'];
			exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = $data['msg'];
			exit(json_encode($msg));
        }
    }

    function forget_pwd()
    {
        if ($this->user_id > 0) {
            $this->redirect("User/index");
//            header("Location: " . U('User/Index'));`
        }
        $username = I('username');
        if (IS_POST) {
            if (!empty($username)) {
                $this->verifyHandle('forget');
                $field = 'mobile';
                if (check_email($username)) {
                    $field = 'email';
                }
                $user = M('users')->where("email", $username)->whereOr('mobile', $username)->find();
                if ($user) {
                    session('find_password', array('user_id' => $user['user_id'], 'username' => $username,
                        'email' => $user['email'], 'mobile' => $user['mobile'], 'type' => $field));
                    header("Location: " . U('User/find_pwd'));
                    exit;
                } else {
                    $this->error("用户名不存在，请检查");
                }
            }
        }
        return $this->fetch();
    }

    function find_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('User/index'));
        }
        $user = session('find_password');
        if (empty($user)) {
            $this->error("请先验证用户名", U('User/forget_pwd'));
        }
        $this->assign('user', $user);
        return $this->fetch();
    }

	/*找回密码*/
    public function set_pwd()
    {
    	$logic = new UsersLogic();
        $password = I('post.password');
        $password2 = I('post.password2');
        $mobile=I('post.username');
        $code=I('post.mobile_code');
        $scene=I('post.scene/d',2);
        if ($password2 != $password) {
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = '两次密码不一致';
			exit(json_encode($msg));
            //$this->error('两次密码不一致', U('User/forget_pwd'));
        }
        /*验证短信(找回密码$scene=2)*/
        $check_code = $logic->check_validate_code($code, $mobile, 'phone', 0 , $scene);
        if($check_code['status'] != 1){
        	$msg['ReturnCode'] = 104;//
			$msg['ReturnMsg'] = $check_code['msg'];
			exit(json_encode($msg));
        }
        $user = M('users')->where("mobile", $mobile)->find();
        $res=M('users')->where("user_id", $user['user_id'])->save(array('password' => encrypt($password)));
        if($res!==false){
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '修改密码成功';
			exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = '修改密码失败';
			exit(json_encode($msg));
        }
    }
 
    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
            $this->error("验证码错误");
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }

    /**
     * 账户管理
     */
    public function accountManage()
    {
        return $this->fetch();
    }

    /**
     * 确定收货成功
     */
    public function order_confirm()
    {
    	$user=$this->getUserByToken(I('token'));
        $id = I('order_id/d', 0);
        $data = confirm_order($id, $user['user_id']);
        if ($data['status'] < 0){
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = $data['msg'];
			exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = $data['msg'];
			exit(json_encode($msg));
        }
    }

    /**
     * 申请退货
     */
    public function return_goods()
    {
    	$user=$this->getUserByToken(I('token'));
        $order_id = I('order_id/d', 0);
        $goods_id = I('goods_id/d', 0);
        $spec_key = I('spec_key');
        $info = M('order')->where(['order_id' => $order_id, 'user_id' => $user['user_id']])->find();
        if (empty($info)) {
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = '非法操作';
			exit(json_encode($msg));
        }
        $return_money_btn=1;
        $return_goods_btn=0;
        if($info['order_status']==1&&$info['pay_status']==1&&$info['shipping_status']==1){/*待收货*/
    		$return_goods_btn=1;
    	}
        $return_goods = M('return_goods')
            ->where(['order_id' => $order_id, 'goods_id' => $goods_id, 'spec_key' => $spec_key])
            ->find();
        if (!empty($return_goods)) {
        	$return_goods['addtime']=date('Y-m-d H:i:s',$return_goods['addtime']);
        	$return_goods['return_time']=date('Y-m-d H:i:s',$return_goods['return_time']);
	        if ($return_goods['imgs']){
	        	$return_goods['imgs'] = explode(',', $return_goods['imgs']);
	        	foreach($return_goods['imgs'] as $k=>$v){
	        		if($v){
	        			$return_goods['imgs'][$k]=C('HeadUrl').$v;
	        		}
	        	}
	        }else{
	        	$return_goods['imgs']=array();
	        }
	        $goods = M('order_goods')->where("spec_key='".$spec_key."' and order_id=".$order_id." and goods_id", $goods_id)->field('rec_id,order_id,goods_id,goods_name,goods_num,member_goods_price,spec_key,spec_key_name')->find();
	        $thumb=goods_thum_images($goods['goods_id'],300,300);
	  		if($thumb){
	  			$thumb=C('HeadUrl').$thumb;
	  		}
	  		$goods['thumb']=$thumb;
	  		
	  		$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '获取成功';
			$msg['Result'] = array(
				'goods'=>$goods,
				'return_info'=>$return_goods,
			);
			exit(json_encode($msg));
        }
        $region_id[] = tpCache('shop_info.province');        
        $region_id[] = tpCache('shop_info.city');        
        $region_id[] = tpCache('shop_info.district');
        $return_address = M('region')->where("id in (".implode(',', $region_id).")")->getField('id,name');
        $goods = M('order_goods')->where("spec_key='".$spec_key."' and order_id=".$order_id." and goods_id", $goods_id)->field('rec_id,order_id,goods_id,goods_name,goods_num,member_goods_price,spec_key,spec_key_name')->find();
        $thumb=goods_thum_images($goods['goods_id'],300,300);
  		if($thumb){
  			$thumb=C('HeadUrl').$thumb;
  		}
  		$goods['thumb']=$thumb;
        //查找退货收货地址
        $region['province']=$return_address[tpCache('shop_info.province')];
        $region['city']=$return_address[tpCache('shop_info.city')];
        $region['district']=$return_address[tpCache('shop_info.district')];
        $region['address']=tpCache('shop_info.address');
        $region['phone']=tpCache('shop_info.phone');
        $region['work_time']='(周一至周五)08:00~18:00';
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result'] = array(
			'goods'=>$goods,
			'region'=>$region,
			'return_money'=>$return_money_btn,
			'return_goods'=>$return_goods_btn
		);
		exit(json_encode($msg));
    }
    public function edit_return_goods(){
    	$user=$this->getUserByToken(I('token'));
    	$return_money=I('return_money/d',0);
        $order_id = I('order_id/d', 0);
        $goods_id = I('goods_id/d', 0);
        $spec_key = I('spec_key');
        $order_info = M('order')->where(['order_id' => $order_id, 'user_id' => $user['user_id']])->find();
        if (empty($order_info)) {
        	$msg['ReturnCode'] = 104;
			$msg['ReturnMsg'] = '非法操作';
			exit(json_encode($msg));
        }
        $return_goods = M('return_goods')
            ->where(['order_id' => $order_id, 'goods_id' => $goods_id, 'spec_key' => $spec_key])
            ->find();
        if(!empty($return_goods)){
        	$msg['ReturnCode'] = 116;
			$msg['ReturnMsg'] = '已提交过售后服务单';
			exit(json_encode($msg));
        }
    	// 晒图片
        $files = request()->file('return_imgs');
        if(!empty($files)){
        	$save_url = 'public/upload/return_goods/' . date('Y', time()) . '/' . date('m-d', time());
	        foreach ($files as $file) {
	            // 移动到框架应用根目录/public/uploads/ 目录下
	            $info = $file->rule('uniqid')->validate(['size' => 1024 * 1024 * 3, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
	            if ($info) {
	                // 成功上传后 获取上传信息
	                $return_imgs[] = '/'.$save_url . '/' . $info->getFilename();
	            } else {
	                // 上传失败获取错误信息
	                $msg['ReturnCode'] = 114;
					$msg['ReturnMsg'] = '上传图片失败';
			        exit(json_encode($msg));
	            }
	        }
        }
        if (!empty($return_imgs)) {
            $data['imgs'] = implode(',', $return_imgs);
        }
        $data['order_id'] = $order_id;
        $data['order_sn'] = $order_info['order_sn'];
        $data['goods_id'] = $goods_id;
        $data['addtime'] = time();
        $data['user_id'] = $user['user_id'];
        $data['type'] = I('type'); // 服务类型  0退货退款  2仅退款
        $data['reason'] = I('reason'); // 问题描述     
        $data['spec_key'] = I('spec_key'); // 商品规格
        $data['return_money']=$return_money;						       
        $res = M('return_goods')->add($data);
        if($res!==false){
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '申请成功,客服第一时间会帮您处理';
			$msg['Result']['return_id']=$res;
			exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = '提交失败，请稍后重试';
			exit(json_encode($msg));
        }
    }

    /**
     * 退换货列表
     */
    public function return_goods_list()
    {
    	$user=$this->getUserByToken(I('token'));
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
        //退换货商品信息
        $count = M('return_goods')->where("user_id", $user['user_id'])->count();
        $list = M('return_goods')->where("user_id", $user['user_id'])->order("id desc")->limit(($page-1)*$pagesize,$pagesize)->select();
        if(!empty($list)){
	        foreach($list as $k=>$v){
	        	$list[$k]['addtime']=date('Y-m-d H:i:s',$v['addtime']);
	        	if($v['imgs']){
	        		$tmp=explode(',',$v['imgs']);
	        		if(!empty($tmp)){
	        			foreach($tmp as $kt=>$vt){
	        				if($vt){
	        					$tmp[$kt]=C('HeadUrl').$vt;
	        				}
	        			}
	        			$list[$k]['imgs']=$tmp;
	        		}
	        	}else{
	        		$list[$k]['imgs']=array();
	        	}
	        	$con['order_id']=$v['order_id'];
	        	$con['goods_id']=$v['goods_id'];
	        	$con['spec_key']=$v['spec_key'];
	        	$goods_name=M('OrderGoods')->where($con)->getField('goods_name');
	        	$list[$k]['goods_name']=$goods_name;
	        	$thumb=goods_thum_images($v['goods_id'],300,300);
		  		if($thumb){
		  			$thumb=C('HeadUrl').$thumb;
		  		}
		  		$list[$k]['thumb']=$thumb;
	        }
        }
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=array(
			'List'=>$list,
			'Count'=>$count
		);
		exit(json_encode($msg));
    }

    /**
     *  退货详情
     */
    public function return_goods_info()
    {
    	$user=$this->getUserByToken(I('token'));
        $id = I('id/d', 0);
        $return_goods = M('return_goods')->where("id = $id")->find();
    	if(!empty($return_goods['imgs'])){
    		$tmp=explode(',',$return_goods['imgs']);
			if(!empty($tmp)){
				foreach($tmp as $kt=>$vt){
					if($vt){
						$tmp[$kt]=C('HeadUrl').$vt;
					}
				}
				$return_goods['imgs']=$tmp;
			}
    	}else{
    		$return_goods['imgs']=array();
    	}
    	$con['order_id']=$return_goods['order_id'];
    	$con['goods_id']=$return_goods['goods_id'];
    	$con['spec_key']=$return_goods['spec_key'];
    	$order_goods=M('OrderGoods')->where($con)->find();
    	$return_goods['goods_name']=$order_goods['goods_name'];
    	$return_goods['goods_num']=$order_goods['goods_num'];
    	$return_goods['price']=$order_goods['member_goods_price'];
    	$return_goods['spec_key_name']=$order_goods['spec_key_name'];
    	$thumb=goods_thum_images($return_goods['goods_id'],300,300);
  		if($thumb){
  			$thumb=C('HeadUrl').$thumb;
  		}
  		$return_goods['thumb']=$thumb;
  		$return_goods['addtime']=date('Y-m-d H:i:s',$return_goods['addtime']);
  		$return_goods['return_time']=date('Y-m-d H:i:s',$return_goods['return_time']);
  		$msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$return_goods;
		exit(json_encode($msg));
    }


    public function recharge()
    {
        $order_id = I('order_id/d');
        $paymentList = M('Plugin')->where("`type`='payment' and code!='cod' and status = 1 and  scene in(0,1)")->select();
        //微信浏览器
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and code='weixin'")->select();
        }
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2) {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }
        $bank_img = include APP_PATH . 'home/bank.php'; // 银行对应图片
        $payment = M('Plugin')->where("`type`='payment' and status = 1")->select();
        $this->assign('paymentList', $paymentList);
        $this->assign('bank_img', $bank_img);
        $this->assign('bankCodeList', $bankCodeList);

        if ($order_id > 0) {
            $order = M('recharge')->where("order_id", $order_id)->find();
            $this->assign('order', $order);
        }
        return $this->fetch();
    }

    /**
     * 申请提现记录
     */
    public function withdrawals()
    {

        C('TOKEN_ON', true);
        if (IS_POST) {
            $this->verifyHandle('withdrawals');
            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $data['create_time'] = time();
            $distribut_min = tpCache('basic.min'); // 最少提现额度
            if ($data['money'] < $distribut_min) {
                $this->error('每次最少提现额度' . $distribut_min);
                exit;
            }
            if ($data['money'] > $this->user['user_money']) {
                $this->error("你最多可提现{$this->user['user_money']}账户余额.");
                exit;
            }
            $withdrawal = M('withdrawals')->where(array('user_id' => $this->user_id, 'status' => 0))->sum('money');
            if ($this->user['user_money'] < ($withdrawal + $data['money'])) {
                $this->error('您有提现申请待处理，本次提现余额不足');
            }
            if (M('withdrawals')->add($data)) {
                $this->success("已提交申请");
                exit;
            } else {
                $this->error('提交失败,联系客服!');
                exit;
            }
        }

        $withdrawals_where['user_id'] = $this->user_id;
        $count = M('withdrawals')->where($withdrawals_where)->count();
        $pagesize = C('PAGESIZE');
        $page = new Page($count, $pagesize);
        $list = M('withdrawals')->where($withdrawals_where)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();

        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('list', $list); // 下线
        if (I('is_ajax')) {
            return $this->fetch('ajax_withdrawals_list');
            exit;
        }
        $order_count = M('order')->where("user_id", $this->user_id)->count(); // 我的订单数
        $goods_collect_count = M('goods_collect')->where("user_id", $this->user_id)->count(); // 我的商品收藏
        $comment_count = M('comment')->where("user_id", $this->user_id)->count();//  我的评论数
        $coupon_count = M('coupon_list')->where("uid", $this->user_id)->count(); // 我的优惠券数量
        $level_name = M('user_level')->where("level_id", $this->user['level'])->getField('level_name'); // 等级名称
        $this->assign('level_name', $level_name);
        $this->assign('order_count', $order_count);
        $this->assign('goods_collect_count', $goods_collect_count);
        $this->assign('comment_count', $comment_count);
        $this->assign('coupon_count', $coupon_count);
        $this->assign('user_money', $this->user['user_money']);    //用户余额
        return $this->fetch();
    }

    /**
     * 申请记录列表
     */
    public function withdrawals_list()
    {
        $withdrawals_where['user_id'] = $this->user_id;
        $count = M('withdrawals')->where($withdrawals_where)->count();
        $pagesize = C('PAGESIZE');
        $page = new Page($count, $pagesize);
        $list = M('withdrawals')->where($withdrawals_where)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();

        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('list', $list); // 下线
        if (I('is_ajax')) {
            return $this->fetch('ajax_withdrawals_list');
            exit;
        }
        return $this->fetch();
    }

    /**
     * 删除已取消的订单
     */
    public function order_del()
    {
        $user_id = $this->user_id;
        $order_id = I('get.order_id/d');
        $order = M('order')->where(array('order_id' => $order_id, 'user_id' => $user_id))->find();
        if (empty($order)) {
            return $this->error('订单不存在');
            exit;
        }
        $res = M('order')->where("order_id=$order_id and order_status=3")->delete();
        $result = M('order_goods')->where("order_id=$order_id")->delete();
        if ($res && $result) {
            return $this->success('成功', "mobile/User/order_list");
            exit;
        } else {
            return $this->error('删除失败');
            exit;
        }
    }

    /**
     * 我的关注
     * $author lxl
     * $time   2017/1
     */
    public function myfocus()
    {
        return $this->fetch();
    }

    /**
     * 待收货列表
     * $author lxl
     * $time   2017/1
     */
    public function wait_receive()
    {
        $where = ' user_id=' . $this->user_id;
        //条件搜索
        if (I('type') == 'WAITRECEIVE') {
            $where .= C(strtoupper(I('type')));
        }
        $count = M('order')->where($where)->count();
        $pagesize = C('PAGESIZE');
        $Page = new Page($count, $pagesize);
        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = M('order')->order($order_str)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //获取订单商品
        $model = new UsersLogic();
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }

        //统计订单商品数量
        foreach ($order_list as $key => $value) {
            $count_goods_num = '';
            foreach ($value['goods_list'] as $kk => $vv) {
                $count_goods_num += $vv['goods_num'];
            }
            $order_list[$key]['count_goods_num'] = $count_goods_num;
            //订单物流单号
            $invoice_no = M('DeliveryDoc')->where("order_id", $value['order_id'])->getField('invoice_no', true);
            $order_list[$key][invoice_no] = implode(' , ', $invoice_no);
        }
        $this->assign('page', $show);
        $this->assign('order_list', $order_list);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_wait_receive');
            exit;
        }
        return $this->fetch();
    }

    /**
     *  用户消息通知
     * @author dyr
     * @time 2016/09/01
     */
    public function message_notice()
    {
    	$page=I('Page/d',1);
    	$pagesize=I('PageSize/d',10);
    	$user_info=$this->getUserByToken(I('token'));
        $type = I('type',0);
        $user_logic = new UsersLogic();
        $message_model = new Message();
        //系统消息
        $message_model->updateUserMessageNotice();//插入系统消息
        $user_system_message_where = array(
                'um.user_id' => $user_info['user_id'],
                'um.category' => 0
        );
        $count= DB::name('user_message')
        ->alias('um')
        ->field('um.rec_id, um.message_id, m.message, m.send_time')
        ->join('__MESSAGE__ m','um.message_id = m.message_id','LEFT')
        ->where($user_system_message_where)
        ->count('um.rec_id');
        $user_system_message= DB::name('user_message')
        ->alias('um')
        ->field('um.rec_id, um.message_id, m.message, m.send_time,um.status')
        ->join('__MESSAGE__ m','um.message_id = m.message_id','LEFT')
        ->where($user_system_message_where)
        ->order('m.send_time desc')
        ->limit(($page-1)*$pagesize,$pagesize)
        ->select();
        foreach($user_system_message as $k=>$v){
        	$user_system_message[$k]['send_time']=date('Y.m.d   H:i:s',$v['send_time']);
        }
        $result=array(
        	'List'=>$user_system_message,
        	'Count'=>$count
        );
        $msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		$msg['Result']=$result;
		exit(json_encode($msg));
    }
	/*阅读消息*/
	public function read_message(){
		$user_info=$this->getUserByToken(I('token'));
		$rec_id=I('rec_id/d',0);
		$where['rec_id']=$rec_id;
		$where['user_id']=$user_info['user_id'];
		$update['status']=1;
		$res=M('user_message')->where($where)->save($update);
		if($res!==false){
			$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '操作成功';
		}else{
			$msg['ReturnCode'] = 106;
			$msg['ReturnMsg'] = '操作失败';
		}
		exit(json_encode($msg));
	}
    /**
     * ajax用户消息通知请求
     * @author dyr
     * @time 2016/09/01
     */
    public function ajax_message_notice()
    {
        $type = I('type', 0);
        $user_logic = new UsersLogic();
        $message_model = new Message();
        if ($type == 1) {
            //系统消息
            $user_sys_message = $message_model->getUserMessageNotice();
            $user_logic->setSysMessageForRead();
        } else if ($type == 2) {
            //活动消息：后续开发
            $user_sys_message = array();
        } else {
            //全部消息：后续完善
            $user_sys_message = $message_model->getUserMessageNotice();
        }
        $this->assign('messages', $user_sys_message);
        return $this->fetch('user/ajax_message_notice');

    }

    /**
     * 设置消息通知
     */
    public function set_notice(){
        //暂无数据
        return $this->fetch();
    }

}

