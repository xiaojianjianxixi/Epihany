<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

Route::rule('/apiLogin', 'api/info/login', 'POST');
Route::rule('/getStaffNameByUserId', 'api/info/getStaffNameByUserId');
Route::rule('/index/Getfans','index/Getfans/index');

Route::rule('/index/getcode','index/Base/getcode');
Route::rule('/index/openid','index/Base/openid'); //获取微信openid
Route::rule('/index/Base/responseMsg','index/Base/responseMsg');
Route::rule('/index/Base/getcode','index/Base/getcode');//获取微信access_token
Route::rule('/index/isBind','index/Base/isBind');//检查是否登录 
Route::rule('/index/checkLicense','index/Base/checkLicense');//检查license的合法性
Route::rule('/index/weixin','index/Base/weixin');       //连接微信公众号
Route::rule('index/pushmessage','index/Base/pushmessage'); //推送模板消息

Route::rule('/index/Getfans/getfans','index/Getfans/getfans');//页面跳转中转
Route::rule('/index/login','index/Login/index'); //绑定微信页面
Route::rule('/index/logindo','index/Login/logindo');
Route::rule('/index/out_do','index/Login/out_do'); //解除绑定页面
Route::rule('/index/out','index/Login/out');
Route::rule('/index/face','index/Face/index'); //添加访客页面
Route::rule('/index/facepush','index/Face/facepush');//人脸处理页面

Route::rule('/index/attentionlist','index/Attentionlist/index'); //显示我关注的人页面
Route::rule('/index/Visitorarrival','index/Visitorarrival/index');//获取上一次访客推送时间,间隔不超过n秒
Route::rule('/index/attentionlist/search_page','index/Attentionlist/search_page');//我关注的人搜索页面
Route::rule('/index/templatedetails','index/Attentionlist/templatedetails');//到访通知详情页面


Route::rule('index/details','index/Attentionlist/details');//关注者的到访记录详情页
Route::rule('/index/edit_time','index/Attentionlist/edit_time');//修改关注者的到访时间页面渲染
Route::rule('/index/edit_time_do','index/Attentionlist/edit_time_do');//修改到访时间

Route::rule('/index/edit_face','index/Attentionlist/edit_face');//人脸库管理页面显示
Route::rule('/index/edit_face_del','index/Attentionlist/edit_face_del');//人脸库删除
Route::rule('/index/test','index/Test/index');
Route::rule('/index/getfanse_s','index/Test/getfanse_s');

Route::rule('/index/edit_face_add','index/Attentionlist/edit_face_add');//添加多张人脸
Route::rule('/index/getstafflist','index/Getstafflist/index');//获取关注这列表页面显示
Route::rule('/index/attention','index/Getstafflist/attention');//用户关注员工
Route::rule('/index/batch_concern','index/Getstafflist/batch_concern');//批量关注
Route::rule('/index/cancel_concern','index/Getstafflist/cancel_concern');//取消关注
Route::rule('/index/getvisitors','index/Getvisitors/index');
Route::rule('/index/visitor_concerns','index/Getvisitors/visitor_concerns');
Route::rule('/index/batch_concern_visitor','index/Getvisitors/batch_concern_visitor');
Route::rule('/index/details_two','index/Attentionlist/details_two');
/*
*post: user_count,user_password,salt
*return: api_key,api_secret,license
*/

Route::rule('index/updatenew','api/RequestGuest/updatenew');
Route::rule('RequestGuest/UpdateStatus','api/RequestGuest/UpdateStatus');
Route::rule('RequestGuest/guest','api/RequestGuest/guest');
Route::rule('getSwapInfo','api/info/getSwapInfo');//获取用户分配api key 和license和api secret
Route::rule('addPushedStaff','api/info/addPushedStaff');//client staff员工资料添加
Route::rule('addPushedStaffImg','api/info/addPushedStaffImg');//clientstaff 员工证件照添加
Route::rule('deletePushedStaff','api/info/deletePushedStaff');
Route::rule('updatePushedStaff','api/info/updatePushedStaff');
Route::rule('getNewGuestArray','api/info/getNewGuestArray');
Route::rule('SetNewGuestArray','api/info/SetNewGuestArray');

Route::rule('index/syncstaff','api/Syncstaff/syncstaff');//阿里云本地数据同步接口

Route::rule('index/cancelwechat','api/CancelWechat/cancelwechat');//本地取消关注阿里云同步接口
Route::rule('index/batchCancel_update','api/CancelWechat/batchCancel_update');//本地批量取消关注阿里云同步接口
Route::rule('index/deletestaff','api/StaffInfo/deletestaff');//本地同步阿里云staff接口
Route::rule('index/updatestaff','api/StaffInfo/updatestaff');//本地编辑员工信息同步阿里云
Route::rule('index/update_photo','api/StaffInfo/update_photo');//本地更新照片阿里云同步staff,client_staff
Route::rule('index/staffbatchdelete','api/StaffInfo/staffbatchdelete');//批量删除
Route::rule('index/pwdupdate','api/PwdInfo/pwdupdate');//修改密码同步
Route::rule('index/checkit','api/CheckLicense/checkit');//CheckLicense
Route::rule('index/select_license','api/CheckLicense/select_license');//select_license
Route::rule('index/deletemanager','api/Deletemanager/deletemanager');//删除访客同步
Route::rule('index/editmanager','api/Deletemanager/editmanager');//更新访客信息同步

Route::rule('index/insert','api/Addrecognition/insert');//插入识别表

Route::rule('index/recognitionApi','index/Attentionlist/recognitionApi');//显示同行者图片API
Route::rule('index/Bindphone','api/Bindphone/bindphone');//绑定手机到阿里云
Route::rule('index/Updatephone','api/Bindphone/updatephone');//绑定手机到阿里云


Route::rule('index/Choose','index/Login/choose');//选择登录界面
Route::rule('index/staffLogin','index/Login/staffLogin');//选择登录界面
Route::rule('index/staffLogin_do','index/Login/staffLogin_do');//选择登录界面提交
Route::rule('index/Verificationcode','index/Verificationcode/staffLogin_do');//选择登录界面提交


Route::rule('index/generationcode','index/Verificationcode/generationcode');//生成验证码
Route::rule('index/synchronousappid','api/info/synchronousappid');//同步appid

Route::rule('index/staffLogin_doing_more','index/Login/staffLogin_doing_more');//手机号选择公司登录
Route::rule('index/Menu_development','index/Test/Menu_development');//本地测试开发

Route::rule('index/yunchuangone','api/Clientapi/yunchuangone');//云创自动登录接口
Route::rule('index/yunchuangtwo','api/Clientapi/yunchuangtwo');//云创自动登录接口
Route::rule('index/yunchuangthree','api/Clientapi/yunchuangthree');//云创自动登录接口

//定义路由全局变量
define('wechatPath','http://wechat.techpami.com');
define('logname','tklogs');//定义日志存储文件夹
