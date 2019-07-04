<?php

namespace app\api\controller;
use app\home\logic\UsersLogic;
use think\Controller;
use think\Session;
use think\Db;

class ApiBase extends Controller {
    public $session_id;
    public $weixin_config;
    public $cateTrre = array();
    
    /*
     * 初始化操作
     */
    public function _initialize() {
    	$action=ACTION_NAME;
    	$no_sign=array(
    		'add_comment',
    		'edit_return_goods',
    		'userinfo',
    		'getArea',
    		'getPaymentList',
    	);
    	if(!in_array($action,$no_sign)){
    		//签名
	        $param=$_REQUEST;
	        $client_sign = $param['sign'];
			unset($param['sign']);
	        $str='';
	        if($param){
	        	ksort($param);
	        	foreach($param as $key=>$val){
	        		$val=urldecode($val);
				    $str .= $key . '=' . $val . '&';
				}
	        }
			$str .= C('token');
			$server_sign = md5($str);
			if($server_sign !== $client_sign){
				$msg['ReturnCode'] = 101;
				$msg['ReturnMsg'] = '签名参数不正确'.$str;
				exit(json_encode($msg));
			}
    	}
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */   
    public function public_assign()
    {
        
       $tpshop_config = array();
       $tp_config = M('config')->cache(true,TPSHOP_CACHE_TIME)->select();       
       foreach($tp_config as $k => $v)
       {
       	  if($v['name'] == 'hot_keywords'){
       	  	 $tpshop_config['hot_keywords'] = explode('|', $v['value']);
       	  }       	  
          $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }                        
       
       $goods_category_tree = get_goods_category_tree();
       $this->cateTrre = $goods_category_tree;
       $this->assign('goods_category_tree', $goods_category_tree);                     
       $brand_list = M('brand')->cache(true,TPSHOP_CACHE_TIME)->field('id,cat_id,logo,is_hot')->where("cat_id>0")->select();              
       $this->assign('brand_list', $brand_list);
       $this->assign('tpshop_config', $tpshop_config);
    }      

    // 网页授权登录获取 OpendId
    public function GetOpenid()
    {
        if($_SESSION['openid'])
            return $_SESSION['openid'];
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            //$baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $baseUrl = urlencode($this->get_url());
            $url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
            Header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
            exit();
        } else {
            //上面获取到code后这里跳转回来
            $code = $_GET['code'];
            $data = $this->getOpenidFromMp($code);//获取网页授权access_token和用户openid
            $data2 = $this->GetUserInfo($data['access_token'],$data['openid']);//获取微信用户信息
            $data['nickname'] = empty($data2['nickname']) ? '微信用户' : trim($data2['nickname']);
            $data['sex'] = $data2['sex'];
            $data['head_pic'] = $data2['headimgurl']; 
            $data['subscribe'] = $data2['subscribe'];                         
            $_SESSION['openid'] = $data['openid'];
            $data['oauth'] = 'weixin';
            if(isset($data2['unionid'])){
            	$data['unionid'] = $data2['unionid'];
            }
            return $data;
        }
    }

    /**
     * 获取当前的url 地址
     * @return type
     */
    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }    
    
    /**
     *
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     *
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
    	//1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
    	//2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
        $url = $this->__CreateOauthUrlForOpenid($code);       
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);         
        curl_close($ch);
        return $data;
    }
    
    
        /**
     *
     * 通过access_token openid 从工作平台获取UserInfo      
     * @return openid
     */
    public function GetUserInfo($access_token,$openid)
    {         
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token,$openid);
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);            
        curl_close($ch);
        //获取用户是否关注了微信公众号， 再来判断是否提示用户 关注
        if(!isset($data['unionid'])){
        	$access_token2 = $this->get_access_token();//获取基础支持的access_token
        	$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token2&openid=$openid";
        	$subscribe_info = httpRequest($url,'GET');
        	$subscribe_info = json_decode($subscribe_info,true);
        	$data['subscribe'] = $subscribe_info['subscribe'];
        }                
        return $data;
    }
    
    
    public function get_access_token(){
        //判断是否过了缓存期
        $expire_time = $this->weixin_config['web_expires'];
        if($expire_time > time()){
           return $this->weixin_config['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->weixin_config[appid]}&secret={$this->weixin_config[appsecret]}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        $web_expires = time() + 7140; // 提前60秒过期
        M('wx_user')->where(array('id'=>$this->weixin_config['id']))->save(array('web_access_token'=>$return['access_token'],'web_expires'=>$web_expires));
        return $return['access_token'];
    }    

    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
//        $urlObj["scope"] = "snsapi_base";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }

    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["secret"] = $this->weixin_config['appsecret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }

    /**
     *
     * 构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址     
     * @return 请求的url
     */
    private function __CreateOauthUrlForUserinfo($access_token,$openid)
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';        
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?".$bizString;                    
    }    
    
    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    public function ajaxReturn($data){
        exit(json_encode($data));
    }
    public function check_sms_app(){
    	$mobiel=I('mobile');
    	$scene=I('scene/d',1);
    	$code=I('mobile_code');
    	$con=array('mobile'=>$mobiel ,'scene'=>$scene, 'status'=>1);
        $data = M('sms_log')->where($con)->order('id DESC')->find();
        if(is_array($data) && $data['code'] == $code){
        	$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '验证码正确';
			exit(json_encode($msg));
        }else{
        	$msg['ReturnCode'] = 119;//
			$msg['ReturnMsg'] = '验证码错误';
			exit(json_encode($msg));
        } 
    }
    /**
     * 前端发送短信方法: APP/WAP/PC 共用发送方法
     */
    public function send_validate_code(){
        $this->send_scene = C('SEND_SCENE');
        $type = I('type','mobile');
        $scene = I('scene');    //发送短信验证码使用场景
        $mobile = I('mobile');
        $sender = I('send');
        $mobile = !empty($mobile) ?  $mobile : $sender ;
        //注册
        if($scene == 1){
            $con['mobile']=$mobile;
            $mobileinfo=M('users')->where($con)->find();
            if(!empty($mobileinfo)){
            	$return_arr = array('ReturnCode'=>103,'ReturnMsg'=>'手机号已被注册');
            	exit(json_encode($return_arr));
            }
        }
        if($type == 'email'){
            //发送邮件验证码
            $logic = new UsersLogic();
            $res = $logic->send_email_code($sender);
            exit(json_encode($res));
        }else{
            //发送短信验证码
            $res = checkEnableSendSms($scene);
            if($res['status'] != 1){
                $return_arr = array('ReturnCode'=>104,'ReturnMsg'=>'参数错误');
            	exit(json_encode($return_arr));
            }
            //判断是否存在验证码
            $data = M('sms_log')->where(array('mobile'=>$mobile,'session_id'=>$session_id, 'status'=>1))->order('id DESC')->find();
            //获取时间配置
            $sms_time_out = tpCache('sms.sms_time_out');
            $sms_time_out = $sms_time_out ? $sms_time_out : 120;
            //120秒以内不可重复发送
            if($data && (time() - $data['add_time']) < $sms_time_out){
                $return_arr = array('ReturnCode'=>105,'ReturnMsg'=>'操作过于频繁');
            	exit(json_encode($return_arr));
            }
            //随机一个验证码
            $code = rand(1000, 9999);
            $user = session('user');
            $params['code'] =$code;
            //发送短信
            $resp = sendSms($scene , $mobile , $params);
            if($resp['status'] == 1){
                //发送成功, 修改发送状态位成功
                M('sms_log')->where(array('mobile'=>$mobile,'code'=>$code,'session_id'=>$session_id , 'status' => 0))->save(array('status' => 1));
                //$return_arr = array('status'=>1,'msg'=>'发送成功,请注意查收');
                $return_arr = array('ReturnCode'=>100,'ReturnMsg'=>'发送成功,请注意查收');
            }else{
                $return_arr = array('ReturnCode'=>106,'ReturnMsg'=>'发送失败'.$resp['msg']);
            }
            exit(json_encode($return_arr));
        }
    }
    /*
   	* 获取商品列表
    * */
    public function getGoodsList($data,$width=300,$height=300){
	  	if(empty($data)){return false;}
	  	$list=array();
	  	foreach($data as $key=>$val){
	  		$thumb=goods_thum_images($val['goods_id'],$width,$height);
	  		if($thumb){
	  			$thumb=C('HeadUrl').$thumb;
	  		}
	  		$list[]=array(
	  			'gid'=>$val['goods_id'],
	  			'goods_name'=>$val['goods_name'],
	  			'market_price'=>$val['market_price'],
	  			'shop_price'=>$val['shop_price'],
	  			'thumb'=>$thumb
	  		);
	  	}
	  	return $list;
	}
	
	/*
   	* 用户信息
    * */
    public function getUserByToken($token=''){
	  	if(empty($token)){
	  		$msg['ReturnCode'] = 107;//
			$msg['ReturnMsg'] = '用户未登录';
			$msg['Result']=$result;
			exit(json_encode($msg));
	  	}
	  	$con['token']=$token;
	  	$user=M('Users')->where($con)->find();
	  	if(empty($user)){
	  		$msg['ReturnCode'] = 107;//
			$msg['ReturnMsg'] = '用户未登录';
			$msg['Result']=$result;
			exit(json_encode($msg));
	  	}
	  	return $user;
	}
	/*
   	* 用户信息(不报错)
    * */
    public function getUserByTokenNO($token=''){
	  	if(empty($token)){return false;}
	  	$con['token']=$token;
	  	$user=M('Users')->where($con)->find();
	  	if(empty($user)){
	  		return false;
	  	}
	  	return $user;
	}
	/*组装App广告*/
	public function getAdList($data){
		if(empty($data)){return false;}
	  	$list=array();
	  	foreach($data as $key=>$val){
	  		if($val['ad_code']){
	  			$thumb=C('HeadUrl').$val['ad_code'];
	  		}
	  		$list[]=array(
	  			'aid'=>$val['ad_id'],
	  			'ad_link'=>$val['ad_link'],
	  			'thumb'=>$thumb,
	  			'bgcolor'=>$val['bgcolor']
	  		);
	  	}
	  	return $list;
	}
	/*
	 * 组装订单列表信息
	 * */
	public function getorderlist($data,$width=300,$height=300){
		if(empty($data)){
			return false;
		}
		$order_list=array();
		foreach($data as $k=>$v){
			$tmp=array(
				'order_id'=>$v['order_id'],
				'order_sn'=>$v['order_sn'],
				'order_status'=>$v['order_status'],
				'shipping_status'=>$v['shipping_status'],
				'pay_status'=>$v['pay_status'],
				'total_amount'=>$v['total_amount'],
				'order_status_code'=>$v['order_status_code'],
				'order_status_desc'=>$v['order_status_desc'],
				'pay_btn'=>$v['pay_btn'],
				'cancel_btn'=>$v['cancel_btn'],
				'receive_btn'=>$v['receive_btn'],
				'comment_btn'=>$v['comment_btn'],
				'shipping_btn'=>$v['shipping_btn'],
				'return_btn'=>$v['return_btn'],
				'count_goods_num'=>$v['count_goods_num'],
				'shipping_name'=>$v['shipping_name']
			);
			if(!empty($v['goods_list'])){
				$goods_list=array();
				$shipping_sn='';
				foreach($v['goods_list'] as $kg=>$vg){
					if($vg['delivery_id']>0&&empty($shipping_sn)){
						$shipping_sn=M('delivery_doc')->where('id='.$vg['delivery_id'])->getField('invoice_no');
						$tmp['invoice_no']=$shipping_sn;
					}
					$thumb=goods_thum_images($vg['goods_id'],$width,$height);
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
						'spec_key'=>$vg['spec_key'],
						'spec_key_name'=>$vg['spec_key_name'],
						'thumb'=>$thumb
					);
				}
				$tmp['goods_list']=$goods_list;
			}else{
				$tmp['goods_list']=array();
			}
			$order_list[]=$tmp;
		}
		return $order_list;
	}
	/*获取地区列表
	 * 
	 *   */
	public function getArea(){
		/*组装地区数组，分类好对应的子地区  */
		//获取省份
		$resultData = array();
		$List = M("Region")->where(array('parent_id'=>0,'level'=>1))->select();
		if(!empty($List)){
			foreach($List as $pro=>$province){
				$CityList = array();
				$resultData[$pro]['ProvinceSysNo'] = $province['id'];
				$resultData[$pro]['ProvinceName'] = $province['name'];
				$CityList = M("Region")->where(array('parent_id'=>$province['id'],'level'=>2))->select();
				$resultData[$pro]['City'] = array();
				if(!empty($CityList)){
					foreach($CityList as $cty=>$city){
						$districtList = array();
						$resultData[$pro]['City'][$cty]["CitySysNo"] = $city['id'];
						$resultData[$pro]['City'][$cty]["CityName"] = $city['name'];
						$districtList = M("Region")->where(array('parent_id'=>$city['id'],'level'=>3))->select();
						$resultData[$pro]['City'][$cty]["District"] = array();
						if(!empty($districtList)){
							foreach($districtList as $dist=>$district){
								$resultData[$pro]['City'][$cty]["District"][$dist]['DistrictSysNo'] = $district['id'];
								$resultData[$pro]['City'][$cty]["District"][$dist]['DistrictName'] = $district['name'];
							}
						}
					}
				}
			}
		}
		$msg['Result'] = $resultData;
		$msg['ReturnCode'] = 100;
		$msg['ReturnMsg'] = '获取成功';
		exit(json_encode($msg));
	}
	/*获取热门搜索*/
	public function getkeyword(){
		$inc_type =  'basic';
		$config = tpCache($inc_type);
		if(!empty($config['hot_keywords'])){
			$hot_keyword=explode('|',$config['hot_keywords']);
			if($hot_keyword){
				$keyword=array();
				foreach($hot_keyword as $k=>$v){
					$keyword[]=$v;
				}
			}
		}
		if(!empty($keyword)){
			$msg['Result'] = $keyword;
			$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '获取成功';
			exit(json_encode($msg));
		}else{
			$msg['ReturnCode'] = 121;
			$msg['ReturnMsg'] = '暂无数据';
			exit(json_encode($msg));
		}
	}
}