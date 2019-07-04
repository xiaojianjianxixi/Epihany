<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:43:"./template/mobile/56go/goods/goodsList.html";i:1523930836;s:41:"./template/mobile/56go/public/footer.html";i:1523930801;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
	<title>商品列表-<?php echo $tpshop_config['shop_info_store_title']; ?></title>
	<link rel="stylesheet" type="text/css" href="__STATIC__/css/style.css">
	<script type="text/javascript" src="__STATIC__/js/jquery.js"></script>
	<script type="text/javascript" src="__STATIC__/js/flexible.js"></script>
</head>
	<body>
		<div class="main">		
			<div class="good_tab five">
				<form action="<?php echo U('Goods/goodsList'); ?>" id="search_form" method="post">
					<input type="hidden" name="id" value="<?php echo $cat_id; ?>" />
					<input type="hidden" name="listtype" value="<?php echo $listtype; ?>" />
					<input type="hidden" name="keyword" value="<?php echo $keyword; ?>" />
					<input type="hidden" name="" />
					<ul>
						<li>
							<a href="javascript:void(0);" onclick="select_type('type')">
								<span class="f24"><?php echo (isset($typename) && ($typename !== '')?$typename:"综 合"); ?></span>
								<i class="arrow_down"></i>
								<input type="hidden" name="type" value="<?php echo (isset($type) && ($type !== '')?$type:'all'); ?>" />
							<a href="#">						
						</li>
						<li>
							<a href="javascript:void(0);" onclick="select_type('salessort')">
								<span class="f24">销量</span>
								<?php switch($salessort): case "1": ?><i class="ic_arrow_down"></i><?php break; case "2": ?><i class="ic_arrow_up"></i><?php break; default: ?><i class="ic_arrow_normal"></i>
								<?php endswitch; ?>
								<input type="hidden" name="salessort" value="<?php echo $salessort; ?>" />
							</a>
						</li>
						<li>
							<a href="javascript:void(0);" onclick="select_type('pricesort')">
								<span class="f24">价格</span>
								<?php switch($pricesort): case "1": ?><i class="ic_arrow_down"></i><?php break; case "2": ?><i class="ic_arrow_up"></i><?php break; default: ?><i class="ic_arrow_normal"></i>
								<?php endswitch; ?>
								<input type="hidden" name="pricesort" value="<?php echo $pricesort; ?>" />
							</a>
						</li>
						<li>
							<a href="javascript:void(0);" onclick="select_type('category')">
								<span class="f24">筛选</span>
								<!--<i class="ic_arrow_down"></i>-->
							</a>
						</li>
						<li>
							<a href="javascript:void(0);" onclick="changelist()">
								<i class="type_style"></i>
							</a>
						</li>
					</ul>
				</form>
			</div>	
			
			<!--弹窗-->
			<div class="com_popup select_type" id="type" style="display:none;">
				<div class="com_popup_box">				
					<ul>
						<li onclick="select_option('综 合','all','type')"><a href="javascript:void(0);">综 合</a></li>
						<li onclick="select_option('新 品','new','type')"><a href="javascript:void(0);">新 品</a></li>
						<!--<li><a href="#">评 价</a></li>-->
					</ul>
				</div>
				<div class="mask"></div>
			</div>
			<!--弹窗-->
			<!--侧面弹窗-->
			<div class="side_popup select_type" id="category"  style="display: none;">
				<div class="side_popup_box">				
					<div class="side_popup_head">
						<div class="left back">
							<i class="back"></i>
						</div>
						<p class="f28">筛 选</p>
						<!--<div class="right">
							<a href="#" class="f26">确认</a>
						</div>-->
					</div>
					<!--<div class="side_popup_block"></div>
					<div class="side_popup_tab">
						<ul>
							<li class="select"><a href="#">显示全部</a></li>
							<li><a href="#">仅看包邮</a></li>
							<li><a href="#">促销商品</a></li>
							<li><a href="#">仅看有货</a></li>
						</ul>
					</div>-->
					<div class="side_popup_block"></div>
					<style>
						.yili{width:80%;}
						.erji{background:#f9f9f9;padding-left:.3125rem;clear:both;display: none;}
						.sanji{background:#f5f5f5;padding-left:.3125rem;clear:both;display: none;}
						.side_popup_item li{
							display:inline-block;
							width:100%;
							box-sizing:border-box;
							border-bottom: 1px solid #f0f0f0;
						    min-height:1.375rem;
						    line-height:0rem;
						    position: relative;
						}

					</style>
					<ul class="side_popup_item" style="overflow-y: scroll;height: 100%;">
						<?php if(is_array($goods_category_tree) || $goods_category_tree instanceof \think\Collection): if( count($goods_category_tree)==0 ) : echo "" ;else: foreach($goods_category_tree as $key=>$v): ?>
							<li data-id="<?php echo $v['id']; ?>"  >
								<div class="left yili"><?php echo $v['mobile_name']; ?></div>
								<?php if(!empty($v['tmenu'])): ?>
									<i class="arrow_down2"></i>
									<ul class="erji" id="child<?php echo $v['id']; ?>">
										<?php if(is_array($v['tmenu']) || $v['tmenu'] instanceof \think\Collection): if( count($v['tmenu'])==0 ) : echo "" ;else: foreach($v['tmenu'] as $key=>$vv): ?>
											<li data-id="<?php echo $vv['id']; ?>" >
												<div class="left yili"><?php echo $vv['mobile_name']; ?></div>
												<?php if(!empty($vv['sub_menu'])): ?>
													<i class="arrow_down2"></i>
													<ul class="sanji" id="child<?php echo $vv['id']; ?>">
														<?php if(is_array($vv['sub_menu']) || $vv['sub_menu'] instanceof \think\Collection): if( count($vv['sub_menu'])==0 ) : echo "" ;else: foreach($vv['sub_menu'] as $key=>$vvv): ?>
															<li data-id="<?php echo $vvv['id']; ?>"  >
																<div class="left yili"><?php echo $vvv['mobile_name']; ?></div>
															</li>
														<?php endforeach; endif; else: echo "" ;endif; ?>
													</ul>
												<?php endif; ?>
											</li>
										<?php endforeach; endif; else: echo "" ;endif; ?>
									</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; endif; else: echo "" ;endif; ?>
					</ul>
				</div>
				<div class="mask"></div>
			</div>
			<script type="text/javascript">
				var data=$('#search_form').serialize();
				var	url="/index.php?m=Mobile&c=Goods&a=ajaxGoodsList&"+data;
				//var url="/index.php?m=Mobile&c=Goods&a=ajaxGoodsList&id=<?php echo $cat_id; ?>";
				var  page = 0;
				var  flag=true;
				var  load_flag=true;
				/*** ajax 提交表单 查询订单列表结果*/  
				function ajax_goods_list()
				{	 	
				 	if(!flag){
				 		/*已经到底了*/
				 		return false;
				 	}
				 	if(!load_flag){
				 		return false;
				 	}
				 	load_flag=false;
					page += 1; 
					$.ajax({
						type : "GET",
						url:url+"&p="+page,//+tab,
						success: function(data)
						{
							if(data != ''){
								if(page>1){
									$("#goods_list").append(data);
								}else{
									$('#load').hide();
									$("#goods_list").empty().append(data);
								}
							}else{
								if(page==1){
									var html='<div class="empty"><img src="__STATIC__/images/img_search_empty.png"><p class="f26">暂无商品</p><a href="/" class="o_color f22 btn">去逛逛</a></div>';
									$("#goods_list").empty().append(html);
								}else{
									$('#load').show();
								}
								flag=false;
							}
							load_flag=true;
						}
					}); 
				}
				function getlist(){
					$('.select_type').hide();
					data=$('#search_form').serialize();
					url="/index.php?m=Mobile&c=Goods&a=ajaxGoodsList&"+data;
					page=0;
					flag=true;
					ajax_goods_list();
				}
				ajax_goods_list();
				$('.yili,.arrow_down2,arrow_up2').click(function(){
					var cid=$(this).parent('li').attr('data-id');
					var html=$(this).siblings('ul').html();
					if(html){
						var show_flag=$(this).siblings('ul').css('display');
						var thisclass=$(this).attr('class');
						if(show_flag=='none'){
							if(thisclass!='arrow_down2'&&thisclass!='arrow_up2'){
								$(this).siblings('i').attr('class','arrow_up2');
							}else{
								$(this).attr('class','arrow_up2');
							}
							$('#child'+cid).show();
						}else{
							if(thisclass!='arrow_down2'&&thisclass!='arrow_up2'){
								$(this).siblings('i').attr('class','arrow_down2');
							}else{
								$(this).attr('class','arrow_down2');
							}
							$('#child'+cid).hide();
						}
					}else{
						$('input[name=id]').val(cid);
						$('input[name=salessort]').val(0);
						$('input[name=salessort').siblings('i').attr('class','ic_arrow_normal');
						$('input[name=pricesort]').val(0);
						$('input[name=pricesort').siblings('i').attr('class','ic_arrow_normal');
						$('input[name=type]').val('all');
						$('input[name=type]').siblings('span').html('综 合');
						getlist();
					}
				});
			</script>
			<!--侧面弹窗 end-->
			<?php if($listtype == 'column'): ?>
				<div id="list" class="list_style1">
			<?php else: ?>
				<div id="list" class="list_style2">
			<?php endif; ?>
				<ul id="goods_list">
					
				</ul>
			</div>
			<div class="tip" id="load" style="margin-bottom: 0;display:none;">
				<p style="text-align: center;">已全部加在完毕</p>
			</div>
		</div>
		<script type="text/javascript">
			function changelist(){
				var listclass=$('#list').attr('class');
				if(listclass=='list_style1'){
					$('#list').attr('class','list_style2');
					$('input[name=listtype]').val('row');
				}else{
					$('#list').attr('class','list_style1');
					$('input[name=listtype]').val('column');
				}
			}
			function select_type(type){
				$('.select_type').hide();
				if(type=='salessort'){
					$('input[name=pricesort]').val('0');
					$('input[name=pricesort]').siblings('i').removeClass('ic_arrow_down');
					$('input[name=pricesort]').siblings('i').removeClass('ic_arrow_up');
					var sort=$('input[name='+type+']').val();
					//0没选择 1升序  2降序
					if(!sort||sort==2){
						$('input[name='+type+']').val('1');
						$('input[name='+type+']').siblings('i').attr('class','ic_arrow_down');
					}else{
						$('input[name='+type+']').val('2');
						$('input[name='+type+']').siblings('i').attr('class','ic_arrow_up');
					}
					getlist();
					return false;
				}
				if(type=='pricesort'){
					$('input[name=salessort]').val('0');
					$('input[name=salessort]').siblings('i').removeClass('ic_arrow_down');
					$('input[name=salessort]').siblings('i').removeClass('ic_arrow_up');
					var sort=$('input[name='+type+']').val();
					//0没选择 1升序  2降序
					if(!sort||sort==2){
						$('input[name='+type+']').val('1');
						$('input[name='+type+']').siblings('i').attr('class','ic_arrow_down');
					}else{
						$('input[name='+type+']').val('2');
						$('input[name='+type+']').siblings('i').attr('class','ic_arrow_up');
					}
					getlist();
					return false;
				}
				$('#'+type).show();
			}
			function select_option(item,value,type){
				$('input[name='+type+']').val(value);
				$('input[name='+type+']').siblings('span').html(item);
				$('.select_type').hide();
				getlist();
			}
			$('.mask').bind("click",function(){
				$('.select_type').hide();
			});
			
			$(window).scroll(function () {
		 		var listheight = $("#ajax_return").outerHeight(); 
		 		listheight=listheight;
			    if ($(document).scrollTop() + $(window).height() >= listheight) {
					ajax_goods_list();
			    }
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

