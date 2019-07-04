<?php
$home_config = [
    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------
	//默认错误跳转对应的模板文件
	'dispatch_error_tmpl' => 'public:dispatch_jump',
	//默认成功跳转对应的模板文件
	'dispatch_success_tmpl' => 'public:dispatch_jump', 
	//签名token
	'token'=>'fbb4404011ad5b7d02e630d7b8772eb5',
	'HeadUrl'=>'http://testshop.medp.cn',
];

$html_config = include_once 'html.php';
return array_merge($home_config,$html_config);
?>