<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:46:"./template/mobile/56go/goods/categoryList.html";i:1523930836;s:41:"./template/mobile/56go/public/footer.html";i:1523930801;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
	<title>全部分类-<?php echo $tpshop_config['shop_info_store_title']; ?></title>
	<link rel="stylesheet" type="text/css" href="__STATIC__/css/style.css">
	<script type="text/javascript" src="__STATIC__/js/jquery.js"></script>
	<script type="text/javascript" src="__STATIC__/js/TouchSlide.1.1.js"></script>
	<script type="text/javascript" src="__STATIC__/js/flexible.js"></script>
</head>
	<body>
		<style>
			body,html{background:#fff;height:100%;width:100%;}
		</style>
		<div class="search_box main" onclick="location.href='/index.php?m=Mobile&c=Goods&a=search_56go'">
			<input class="search_input" type="text" placeholder="请输入商品名称"/>
			<i class="search_ic"></i>
		</div>
		<div class="main">
			<div class="class_left">
				<ul>
					<?php $m = '0'; if(is_array($goods_category_tree) || $goods_category_tree instanceof \think\Collection): if( count($goods_category_tree)==0 ) : echo "" ;else: foreach($goods_category_tree as $k=>$vo): if($vo[level] == 1): ?>
               <li <?php if($m == 0): ?>class="select"<?php endif; ?> data-id="<?php echo $m++; ?>"><a href="javascript:;"><?php echo getSubstr($vo['mobile_name'],0,5); ?></a></li>
            <?php endif; endforeach; endif; else: echo "" ;endif; ?>
				</ul>
			</div>	
			<div class="class_right">
				<div class="class_banner">
					<?php $pid =51323;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1524020400 and end_time > 1524020400 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
if(!in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v0):       
    
    $v0[position] = $ad_position[$v0[pid]]; 
    if(I("get.edit_ad") && $v0[not_adv] == 0 )
    {
        $v0[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v0[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v0[ad_id]";        
        $v0[title] = $ad_position[$v0[pid]][position_name]."===".$v0[ad_name];
        $v0[target] = 0;
    }else{
    	$v0[style] = "background:$v0[bgcolor]"; // 广告背景图样式
        $v0[title] = $v0[ad_name];
    }	
    ?>
						<a href="<?php echo $v0['ad_link']; ?>">
							<img src="<?php echo $v0['ad_code']; ?>">
						</a>
					<?php endforeach; ?>
				</div>
				<?php $j = '0'; ?>
				<div class="catedetail">
				<?php if(is_array($goods_category_tree) || $goods_category_tree instanceof \think\Collection): if( count($goods_category_tree)==0 ) : echo "" ;else: foreach($goods_category_tree as $k=>$vo): ?>
					<dl style="<?php if($j == 0): ?>display:block;<?php else: ?>display:none;<?php endif; ?>" data-id="<?php echo $j++; ?>">
					<?php if(is_array($vo['tmenu']) || $vo['tmenu'] instanceof \think\Collection): if( count($vo['tmenu'])==0 ) : echo "" ;else: foreach($vo['tmenu'] as $k2=>$v2): ?>
		        <div class="class_list" >
							<h1 class="f24"><?php echo $v2['name']; ?></h1>
							<ul>
								<?php if(is_array($v2['sub_menu']) || $v2['sub_menu'] instanceof \think\Collection): if( count($v2['sub_menu'])==0 ) : echo "" ;else: foreach($v2['sub_menu'] as $key=>$v3): ?>
									<li>
										<a href="<?php echo U('Mobile/Goods/goodsList',array('id'=>$v3[id])); ?>">
											<img src="<?php echo $v3['image']; ?>" onerror='javascript:this.src="__STATIC__/images/default200x200.png";'>
											<p><?php echo getSubstr($v3['name'],0,4); ?></p>	
										</a>
									</li>
								<?php endforeach; endif; else: echo "" ;endif; ?>
							</ul>
						</div>
			    <?php endforeach; endif; else: echo "" ;endif; ?>
			    </dl>
				<?php endforeach; endif; else: echo "" ;endif; ?>
				</div>
			</div>
		</div>
	
		<script>
			$(function () {
		  //点击切换2 3级分类
			var array=new Array();
			$('.class_left li').each(function(){
				array.push($(this).position().top-0);
			});
		
			$('.class_left li').click(function() {
				var index=$(this).index();
				$('.class_left').delay(200).animate({scrollTop:array[index]},300);
				$(this).addClass('select').siblings().removeClass();
				$('.catedetail dl').eq(index).show().siblings().hide();
		    $('.class_right').scrollTop(0);
			});
		
		});
		</script>
			
		<div class="footer main">
			<ul>
				<li <?php if(ACTION_NAME == 'index' and CONTROLLER_NAME == 'Index'): ?>class="select"<?php endif; ?> >
					<a href="<?php echo U('Mobile/Index/index'); ?>">
						<span class="bot_ic_home"></span>
						<p>首页</p>
					</a>
				</li>
				<li <?php if(ACTION_NAME == 'categoryList' and CONTROLLER_NAME == 'Goods'): ?>class="select"<?php endif; ?> >
					<a href="<?php echo U('Mobile/Goods/categoryList'); ?>">
						<span class="bot_ic_class"></span>
						<p>分类</p>
					</a>
				</li>
				<li <?php if(ACTION_NAME == 'cart' and CONTROLLER_NAME == 'Cart'): ?>class="select"<?php endif; ?> >
					<a href="<?php echo U('Mobile/Cart/cart'); ?>">
						<span class="bot_ic_cart"></span>
						<p>购物车</p>
					</a>
				</li>
				<li <?php if(ACTION_NAME == 'index' and CONTROLLER_NAME == 'User'): ?>class="select"<?php endif; ?>>
					<a href="<?php echo U('Mobile/User/index'); ?>">
						<span class="bot_ic_user"></span>
						<p>我的</p>
					</a>
				</li>
			</ul>
		</div>
		<div class="footer_block"></div>
	</body>
</html>