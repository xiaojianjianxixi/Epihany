<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:39:"./template/mobile/56go/index/index.html";i:1535191243;}*/ ?>
<!doctype html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
	<title>厦门佰翔花园酒店特产便利店</title>
	<link rel="stylesheet" href="__PUBLIC__/css/style.css">
	<script type="text/javascript" src="__PUBLIC__/js/jquery.min.js"></script>
	<script type="text/javascript" src="__PUBLIC__/js/add.js"></script>
	<script type="text/javascript" src="__PUBLIC__/js/vue.min.js"></script>
	<script src="__PUBLIC__/js/layer/layer-min.js"></script><!-- 弹窗js 参考文档 http://layer.layui.com/-->
</head>
<style>
		.footer_1 {
			padding-top: 2px;
			width: 100%;
			height: 2.9125rem;
			background: #fff;
			border-top: 1px solid #f0f0f0;
			position: fixed;
			bottom: 0;
			z-index: 3;
		}
	
		.footer_1 ul li {
			width: 33.3333%;
			height: 1.3125rem;
			display: inline-block;
			float: left;
		   }
		.footer_1 ul li p {
			text-align: center;
			font-size: .28125rem;
		}
	
		.footer_1 ul li.select p {
			color: #EA4E24;
		}
	
		.footer_1_block {
			height: 1.40625rem;
		}
	
		.bot_ic_home,
		.bot_ic_class,
		.bot_ic_cart,
		.bot_ic_user {
			width: .625rem;
			height: .625rem;
			display: block;
			margin: 0 auto;
			margin-top: .125rem;
			margin-bottom: .03125rem;
		}
	
		.bot_ic_home {
			background: url(/template/mobile/56go/static/images/bottom_ic_home.png) no-repeat;
			background-size: 100%;
		}
	
		.bot_ic_class {
			background: url(/template/mobile/56go/static/images/bottom_ic_class.png) no-repeat;
			background-size: 100%;
		}
	
		.bot_ic_cart {
			background: url(/template/mobile/56go/static/images/bottom_ic_cart.png) no-repeat;
			background-size: 100%;
		}
	
		.bot_ic_user {
			background: url(/template/mobile/56go/static/images/bottom_ic_user.png) no-repeat;
			background-size: 100%;
		}
	
		.footer_1 ul li.select .bot_ic_home {
			background: url(/template/mobile/56go/static/images/bottom_ic_home_down.png) no-repeat;
			background-size: 100%;
		}
	
		.footer_1 ul li.select .bot_ic_class {
			background: url(/template/mobile/56go/static/images/bottom_ic_class_down.png) no-repeat;
			background-size: 100%;
		}
	
		.footer_1 ul li.select .bot_ic_cart {
			background: url(/template/mobile/56go/static/images/bottom_ic_cart_down.png) no-repeat;
			background-size: 100%;
		}
	
		.footer_1 ul li.select .bot_ic_user {
			background: url(/template/mobile/56go/static/images/bottom_ic_user_down.png) no-repeat;
			background-size: 100%;
		}
</style>
<style>
		.up, .up1 {
		width: 100%;
		height: 50%;
		background: #000;
		opacity: 0.5;
		}
		.footer_s {
		display: block;
		position: fixed;
		width: 100%;
		z-index: 3;
		bottom: 3.0125rem;
		color: #f03c03;
		background: #fff;
		line-height: 2rem;
		font-size: 12px;
		border-top: 1px solid #e2e2e2;
		}
	
	.left-menu {
		width: 25%;
		float: left;
		background-color: #eee;
		position: relative;
		overflow-y: scroll;
		height: 100%;
	}
	.bot_ic_home, .bot_ic_class, .bot_ic_cart, .bot_ic_user {
		width: 1.425rem;
		height: 1.525rem;
		display: block;
		margin: 0 auto;
		margin-top: .125rem;
		margin-bottom: .03125rem;
	}
	
	
	.main_s{
		display: flex;
	}
	.main_s .con{
		width:75%;
		overflow-y: auto;
		background: #fff;
	}
	.main_s .con .right-con{
		width:100%;
		
	}
	.red-point{
          position: relative;
	}

	.red-point::before{
		content: " ";
		border: 5px solid red;/*设置红色*/
		border-radius:5px;/*设置圆角*/
		position: absolute;
		z-index: 1000;
		right: 0%;
		margin-right: -5px;
		margin-top: -5px;
	}


	</style>
<body>

	<div class="header">
		<div class="content-wrapper">
			<div class="avatar">
				<img width="64" height="64" src="__PUBLIC__/images/xmhybld.jpg">
			</div>
			<div class="content">
				<div class="title">
					<span class="brand"></span>
					<span class="name">厦门佰翔花园酒店特产便利店</span>
				</div>
				<div class="description">
					10分钟送达
				</div>
			</div>
		</div>
		<div class="bulletin-wrapper">
			<span class="bulletin-title"></span>
			<span class="bulletin-text">厦门佰翔花园酒店特产便利店24小时为您提供方便。</span>
			<i class="icon-keyboard_arrow_right"></i>
		</div>
		<div class="background">
			<img width="100%" height="100%" src="__PUBLIC__/images/seller_avatar_256px.jpg">
		</div>
		<div class="detail fade-transition" style="display: none;">
			<div class="detail-wrapper clearfix">
				<div class="detail-main">
					<h1 class="name">粥品香坊（回龙观）</h1>
					<div class="star-wrapper">
						<div class="star star-48">
							<span class="star-item on"></span>
							<span class="star-item on"></span>
							<span class="star-item on"></span>
							<span class="star-item on"></span>
							<span class="star-item off"></span>
						</div>
					</div>
					<div class="title">
						<div class="line"></div>
						<div class="text">优惠信息</div>
						<div class="line"></div>
					</div>
					<ul class="supports">
						<li class="support-item">
							<span class="icon decrease"></span>
							<span class="text">在线支付满28减5</span>
						</li>
						<li class="support-item">
							<span class="icon discount"></span>
							<span class="text">VC无限橙果汁全场8折</span>
						</li>
						<li class="support-item">
							<span class="icon special"></span>
							<span class="text">单人精彩套餐</span>
						</li>
						<li class="support-item">
							<span class="icon invoice"></span>
							<span class="text">该商家支持发票,请下单写好发票抬头</span>
						</li>
						<li class="support-item">
							<span class="icon guarantee"></span>
							<span class="text">已加入“外卖保”计划,食品安全保障</span>
						</li>
					</ul>
					<div class="title">
						<div class="line"></div>
						<div class="text">商家公告</div>
						<div class="line"></div>
					</div>
					<div class="bulletin">
						<p class="content">厦门航空港花园便利店24小时为您提供方便</p>
					</div>
				</div>
			</div>
			<div class="detail-close">
				<i class="icon-close"></i>
			</div>
		</div>
	</div>
	<div class="main_s">
		<div class="left-menu" id="left">
			<ul>
				<?php if(is_array($typeList) || $typeList instanceof \think\Collection): if( count($typeList)==0 ) : echo "" ;else: foreach($typeList as $key=>$vo): ?>
				<li>
					<span><?php echo $vo['name']; ?></span>
				</li>
				<?php endforeach; endif; else: echo "" ;endif; ?>
			</ul>
		</div>
		<div class="con">
			<?php if(is_array($cateList) || $cateList instanceof \think\Collection): if( count($cateList)==0 ) : echo "" ;else: foreach($cateList as $key=>$item): ?>

				<div class="right-con con-active" style="display: none;">
					<?php if(is_array($item['menu']) || $item['menu'] instanceof \think\Collection): if( count($item['menu'])==0 ) : echo "" ;else: foreach($item['menu'] as $key=>$v): ?>
							<h4>&nbsp&nbsp&nbsp<?php echo $v['name']; ?></h4>
						<ul>
							<?php if(is_array($v['goods']) || $v['goods'] instanceof \think\Collection): if( count($v['goods'])==0 ) : echo "" ;else: foreach($v['goods'] as $key=>$product): ?>
								<li class="good-item">
									<div class="menu-img">
										<img class="shop_icon" src="<?php echo $product['goods_image_url']; ?>" width="55" height="55">
									</div>
									<div class="menu-txt">
										<h4 data-icon="00"><?php echo $product['goods_name']; ?></h4>
										<p class="list1"><?php echo $product['mobile_name']; ?></p>
										<p class="list2">
											<b>￥</b>
											<b><?php echo $product['shop_price']; ?></b><?php if($product['unit'] != null): ?><span>/<?php echo $product['unit']; ?></span><?php endif; ?>
										</p>
										<div class="btn">
											<button class="minus">
												<strong></strong>
											</button>
											<i class="good-num">0</i>
											
											<button class="add">
												<strong></strong>
											</button>
											<i class="price">0</i>
										</div>
										&nbsp;&nbsp;&nbsp;<span  class="tastename-choose" style="font-size:12px;"><?php echo $product['tastename']; ?></span>
									</div>
									<input type="hidden"  class="good-id" data-id="<?php echo $product['goods_id']; ?>" value="<?php echo $product['goods_id']; ?>"></input>
									<input type="hidden"  class="oldthisnum"  value=""></input>
									<input type="hidden"  class="oldthisprice"  value=""></input>
								</li>
							<?php endforeach; endif; else: echo "" ;endif; ?>
						</ul>
					<?php endforeach; endif; else: echo "" ;endif; ?>
				</div>
			<?php endforeach; endif; else: echo "" ;endif; ?> 
		</div>


		<div class="up1"></div>
		<div class="shopcart-list fold-transition" style="">
			<div class="list-header">
				<h1 class="title">购物车</h1>
				<span class="empty">清空</span>
			</div>
			<div class="list-content">
				<ul></ul>
			</div>
		</div>
		<div class="footer_s">
			<div class="left">已选：
				<span id="cartN">
					<span id="totalcountshow">0</span>份　总计：￥
					<span id="totalpriceshow">0.00</span>
				</span>元
			</div>
			<div class="right">
				<a id="btnselect" class="xhlbtn  disable" href="javascript:void(0)">加入购物车</a>
			</div>
		</div>
	</div>
	<div class="subFly">
		<div class="up"></div>
		<div class="down">
			<a class="close" href="javascript:">
				<img src="__PUBLIC__/images/close.png" alt="">
			</a>
			<dl class="subName">
				<dt>
					<img class="imgPhoto" style="width: 80%;" src="__PUBLIC__/images/pic.png" alt="">
				</dt>
				<dd>
					<p data-icon=""></p>
					<p>
						<span>¥ </span>
						<span class="pce" style="font-size: 16px;font-weight: bold"></span>
					</p>
					<!-- <p hidden>
						<span>已选：“</span>
						<span class="choseValue"></span>
						<span>”</span>
					</p> -->
				</dd>
			</dl>
			<dl hidden class="subChose">
				<dt>选项</dt>
				<dd hidden class="m-active"></dd>
			</dl>
			<dl class="subCount">
				<dt>购买数量</dt>
				<dd>
					<div class="btn">
						<button class="ms" style="display: inline-block;">
							<strong></strong>
						</button>
						<i style="display: inline-block;">1</i>
						<button class="ad">
							<strong></strong>
						</button>
						<i class="price">25</i>
						<input type="hidden" class="goods_id_s" value="">
					</div>
				</dd>
			</dl>
			<dl>
				<dt style="color: #666">商品信息</dt>
				<br>
				<text>香港进货  每瓶1.5L</text>
			</dl>
			<div class="foot_s">
				<span>确&nbsp&nbsp定</span>
			</div>
		</div>

	</div>

	<div class="footer_1 main">
		<ul >
			<li <?php if(ACTION_NAME == 'index' and CONTROLLER_NAME == 'Index'): ?>class="select"<?php endif; ?> >
				<a href="<?php echo U('Mobile/Index/index'); ?>">
					<span class="bot_ic_home"></span>
					<p>首页</p>
				</a>
			</li>
			<!-- <li <?php if(ACTION_NAME == 'categoryList' and CONTROLLER_NAME == 'Goods'): ?>class="select"<?php endif; ?> >
				<a href="<?php echo U('Mobile/Goods/categoryList'); ?>">
					<span class="bot_ic_class"></span>
					<p>分类</p>
				</a>
			</li> -->
			<li <?php if(ACTION_NAME == 'cart' and CONTROLLER_NAME == 'Cart'): ?>class="select"<?php endif; ?> >
				<div style="height:1px"></div>
				<a href="<?php echo U('Mobile/Cart/cart'); ?>">
					<?php if($count != null): ?>
					<span class="bot_ic_cart red-point"></span>
					<?php else: ?>
					<span class="bot_ic_cart"></span>
					<?php endif; ?>	
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
	<div class="footer_1_block"></div>

<script type="text/javascript">
    $(function(){
    	var window_h=$(window).height();
             var header_h =$('.header').height();
             var footer_s =$('.footer_s').height();
             var footer_1 =$('.footer_1').height();
      
             $('.main_s').css('height',window_h-header_h-footer_s-footer_1)
    })
</script>
<script>
//添加购物车异步提交
	$(function(){
		$("#btnselect").click(function(){

			var goods = []
			$('.good-item').each(function () {
				var num = $(this).find('.good-num').text()
				var id = $(this).find('.good-id').val()
				if(num == "" || num == "0") {
					return
				} else {
					goods.push({
						goods_id: id,
						goods_num: num
					})
				}
			})

	       	$.post("<?php echo U('mobile/Cart/ajaxAddCart'); ?>",{goods:goods},function(data){
            data = $.parseJSON(data);

            // $('#desc_span').show();
            // $('#desc').hide();
            if(data.status == 1){
				layer.alert(data.msg, {icon: 1});
				setTimeout(function () {
				window.location.reload();
				}, 1500);
            }else{
				layer.alert(data.msg, {icon: 2});  // alert('修改失败');
				setTimeout(function () {
				window.location.reload();
				}, 1500);
            }
        })
		})


	})

  	function getSelectArr(){
        var arr = [];
        $(".goods_num").each(function(){
            if(this.txt !== 0){
              arr.push(this.getAttribute('data-id'));
            }
        });
        return arr;
  	}
</script>

</body>
</html>