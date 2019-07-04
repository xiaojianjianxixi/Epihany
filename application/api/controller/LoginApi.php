<?php
/**
 * 微信交互类
 */
namespace app\api\controller;
use app\home\logic\UsersLogic;
use app\home\logic\CartLogic;
use think\Request;
class LoginApi extends ApiBase{
    public $config;
    public $oauth;
    public $class_obj;

    public function __construct(){
      
    }
    public function thirdlogin(){
    	$logic = new UsersLogic();
    	if(empty($_REQUEST['unionid'])||empty($_REQUEST['oauth'])){
    		$msg['ReturnCode'] = 123;
			$msg['ReturnMsg'] = '第三方登陆失败';
			exit(json_encode($msg));
    	}
    	//获取用户信息
        $con[$_REQUEST['oauth'].'_unionid']=$_REQUEST['unionid'];
        $user=M('Users')->where($con)->find();
        if(!$user){
            /*先登陆后绑定*/
            $username = I('username');
	        $password = I('password');
	        $username = trim($username);
	        $password = trim($password);
	        if(empty($username)&&empty($password)){
	        	$msg['ReturnCode'] = 124;
				$msg['ReturnMsg'] = '账号未绑定';
				exit(json_encode($msg));
	        }
	        $logic = new UsersLogic();
	        $res = $logic->app_login($username, $password);
	        if ($res['status'] == 1) {
	        	$user=$res['result'];
	        	/*绑定*/
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
        }elseif($user['is_lock'] == 1){
           $result = array('status'=>-3,'msg'=>'账号异常已被锁定！！！');
        }else{
            //查询用户信息之后, 查询用户的登记昵称
            $levelId = $user['level'];
            $levelName = M("user_level")->where("level_id", $levelId)->getField("level_name");
            $user['level_name'] = $levelName;            
            $user['token'] = md5(time().mt_rand(1,999999999));
            M('users')->where("user_id", $user['user_id'])->save(array('token'=>$user['token'],'last_login'=>time()));
            $result=array(
            	'pay_points'=>$user['pay_points'],
            	'mobile'=>$user['mobile'],
            	'nickname'=>$user['nickname'],
            	'level'=>$user['level'],
            	'level_name'=>$user['level_name'],
            	'token'=>$user['token'],
            );
			$msg['ReturnCode'] = 100;
			$msg['ReturnMsg'] = '登陆成功';
			$msg['Result']=$result;
			exit(json_encode($msg));
        }
    }
}