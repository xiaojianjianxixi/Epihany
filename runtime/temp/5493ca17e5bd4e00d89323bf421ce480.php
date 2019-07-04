<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:37:"./template/mobile/56go/cart/cart.html";i:1523877337;s:41:"./template/mobile/56go/public/footer.html";i:1523877340;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
	<title>购物车-<?php echo $tpshop_config['shop_info_store_title']; ?></title>
	<link rel="stylesheet" type="text/css" href="__STATIC__/css/style.css">
	<script type="text/javascript" src="__STATIC__/js/jquery.min.js"></script>
	<script type="text/javascript" src="__STATIC__/js/flexible.js"></script>
	<script src="__PUBLIC__/js/layer/layer.js"></script>
</head>
<body>
<div class="main">		
    <form id="cart_form" name="formCart" action="<?php echo U('Mobile/Cart/ajaxCartList'); ?>" method="post">
        <?php echo token(); ?>
    </form>
    
</div>
<script type="text/javascript">
$(document).ready(function(){
    ajax_cart_list(); // ajax 请求获取购物车列表
});
// ajax 提交购物车
var before_request = 1; // 上一次请求是否已经有返回来, 有才可以进行下一次请求
function ajax_cart_list(){

	if(before_request == 0) // 上一次请求没回来 不进行下一次请求
	    return false;
	before_request = 0;
    $.ajax({
        type : "POST",
        url:"<?php echo U('Mobile/Cart/ajaxCartList'); ?>",//+tab,
        data : $('#cart_form').serialize(),// 你的formid
        success: function(data){
            $("#cart_form").html('');
            $("#cart_form").append(data);
			before_request = 1;
        }
    });
}

/**
 * 购买商品数量加加减减
 * 购买数量 , 购物车id , 库存数量
 */
function switch_num(num,cart_id,store_count)
{
    var num2 = parseInt($("input[name='goods_num["+cart_id+"]']").val());
    num2 += num;
    if(num2 < 1) num2 = 1; // 保证购买数量不能少于 1
    if(num2 > store_count)
    {   alert("库存只有 "+store_count+" 件, 你只能买 "+store_count+" 件");
        num2 = store_count; // 保证购买数量不能多余库存数量
    }

    $("input[name='goods_num["+cart_id+"]']").val(num2);

    ajax_cart_list(); // ajax 更新商品价格 和数量
}

// ajax 删除购物车的商品
function ajax_del_cart(ids)
{
    $.ajax({
        type : "POST",
        url:"<?php echo U('Mobile/Cart/ajaxDelCart'); ?>",
        data:{ids:ids},
        dataType:'json',
        success: function(data){
            if(data.status == 1)
        	{
            	ajax_cart_list(); //ajax 请求获取购物车列表	
        	}               
        }
    });
}

function del_cart_goods(goods_id)
{
	
	//询问框
	layer.confirm('确定要删除吗？', {
	  btn: ['确定','取消'] //按钮
	}, function(){
		layer.close(layer.index);
	    var chk_value = [];
    	chk_value.push(goods_id);
   		if(chk_value.length > 0){
   		 	ajax_del_cart(chk_value.join(','));
   		}
	});
	
}
// 批量删除购物车的商品
function del_cart_more()
{
    if(!confirm('确定要删除吗?'))
        return false;
    // 循环获取复选框选中的值
    var chk_value = [];
    $('input[name^="cart_select"]:checked').each(function(){
        var s_name = $(this).attr('name');
        var id = s_name.replace('cart_select[','').replace(']','');
        chk_value.push(id);
    });
    // ajax调用删除
    if(chk_value.length > 0)
        ajax_del_cart(chk_value.join(','));
}
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