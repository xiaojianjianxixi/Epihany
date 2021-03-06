<?php


namespace app\home\logic;
use think\Model;
use app\home\logic\CartLogic;
use think\Db;
/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class GoodsLogic extends Model
{

    /**
     * @param $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode 0  返回数组形式  1 直接返回result
     * @return array|mixed 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品列表页帅选品牌
     */
    public function get_filter_brand($goods_id_arr, $filter_param, $action, $mode = 0)
    {
        if (!empty($filter_param['brand_id']))
            return array();

        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';
        $list_brand = M('brand')->where("id in(select brand_id from " .C('database.prefix') . "goods where brand_id > 0 and goods_id in(:goods_id_str))")->bind(['goods_id_str'=>$goods_id_str])->limit('30')->select();  //where("goods_id in($goods_id_str)")->select();

        foreach ($list_brand as $k => $v) {
            // 帅选参数
            $filter_param['brand_id'] = $v['id'];
            $list_brand[$k]['href'] = urldecode(U("Goods/$action", $filter_param, ''));
        }
        if ($mode == 1) return $list_brand;
        return array('status' => 1, 'msg' => '', 'result' => $list_brand);
    }
    /*猜你喜欢*/
	public function getvisit_list($user_id,$page,$pageSize){
		/*浏览记录*/
    	$visit=M('GoodsVisit')->where('user_id='.$user_id)->field('goods_id,cat_id')->order('visittime desc')->select();
		if(empty($visit)){
			$con=array();
			$con['is_on_sale']=1;
			$no_list=M('Goods')->where($con)->order("sales_sum desc")->limit(($page-1)*$pageSize,$pageSize)->select();
			return $no_list;
		}
		foreach($visit as $k=>$v){
			$cat[]=$v['cat_id'];
			$good[]=$v['goods_id'];
		}
		$cat=array_unique($cat);
		$con['is_on_sale']=1;
		$con['cat_id']=array('in',$cat);
		$con['goods_id']=array('not in',$good);
		$count=M('Goods')->where($con)->count();
		if($count<($pageSize*$page)){
			/*当前分类不足 查询同级分类*/
			$where['id']=array('in',$cat);
			$where['is_show']=1;
			$cat_list=M('GoodsCategory')->where($where)->field('id,parent_id_path,level')->select();
			foreach($cat_list as $k=>$v){
				$tmp=array();
				$con=array();
				$con['is_show']=1;
				$tmp=explode('_',$v['parent_id_path']);
				if($v['level']==3){
					$con['parent_id']=$tmp[2];
					$parent_cat=M('GoodsCategory')->where($con)->field('id')->select();
				}elseif($v['level']==2){
					$con['parent_id']=$tmp[1];
					$parent_cat=M('GoodsCategory')->where($con)->field('id')->select();
				}
				/*$parent_cat同级分类*/
				foreach($parent_cat as $kk=>$vv){
					$cat[]=$vv['id'];
				}
			}
			$cat=array_unique($cat);
			$con2['is_on_sale']=1;
			$con2['cat_id']=array('in',$cat);
			$con2['goods_id']=array('not in',$good);
			$count=M('Goods')->where($con2)->count();
			if($count<($pageSize*$page)){
				foreach($cat_list as $ko=>$vo){
					$tmp=explode('_',$vo['parent_id_path']);
					if($vo['level']==3){
						$con['is_show']=1;
						$con['parent_id']=$tmp[1];
						$parent_cat=M('GoodsCategory')->where($con)->field('id')->select();
						foreach($parent_cat as $kp=>$vp){
							$cat[]=$vp['id'];
							$cat_tmp=M('GoodsCategory')->where('is_show=1 and parent_id='.$vp['id'])->field('id')->select();
							foreach($cat_tmp as $kt=>$vt){
								$cat[]=$vt['id'];
							}
						}
						$cat[]=$tmp[1]*1;
					}elseif($vo['level']==2){
						$cat[]=$tmp[1]*1;
					}
				}
				$cat=array_unique($cat);
				$con=array();
				$con['is_on_sale']=1;
				$con['cat_id']=array('in',$cat);
				$con['goods_id']=array('not in',$good);
				$count=M('Goods')->where($con)->count();
				$list=M('Goods')->where($con)->order("field(cat_id,".implode(',',$cat).")")->limit(($page-1)*$pageSize,$pageSize)->select();
				if(count($list)<$pageSize){
					$limit=$pageSize-count($list);
					$cha=$pageSize*$page-$count-$pageSize;
					if($cha>0){
						$strart=$pageSize*$page-$count-$pageSize;
					}else{
						$strart=0;
					}
					$con=array();
					$con['is_on_sale']=1;
					$con['cat_id']=array('not in',$cat);
					$con['goods_id']=array('not in',$good);
					$no_list=M('Goods')->where($con)->order("sales_sum desc")->limit($strart,$limit)->select();
				}
				//$count=M('Goods')->where($con)->count();
			}else{
				$list=M('Goods')->where($con2)->order("field(cat_id,".implode(',',$cat).")")->limit(($page-1)*$pageSize,$pageSize)->select();
			}
		}else{
			$list=M('Goods')->where($con)->order("field(cat_id,".implode(',',$cat).")")->limit(($page-1)*$pageSize,$pageSize)->select();
		}
		if(!empty($no_list)){
			foreach($no_list as $k=>$v){
				$list[]=$v;
			}
		}
		return $list;
	}

    /**
     * @param $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode  0  返回数组形式  1 直接返回result
     * @return array 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品列表页帅选规格
     */
    public function get_filter_spec($goods_id_arr, $filter_param, $action, $mode = 0)
    {
        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';
        $spec_key = DB::query("select group_concat(`key` separator  '_') as `key` from __PREFIX__spec_goods_price where goods_id in($goods_id_str)");  //where("goods_id in($goods_id_str)")->select();
        $spec_key = explode('_', $spec_key[0]['key']);
        $spec_key = array_unique($spec_key);
        $spec_key = array_filter($spec_key);

        if (empty($spec_key)) {
            if ($mode == 1) return array();
            return array('status' => 1, 'msg' => '', 'result' => array());
        }
        $spec = M('spec')->where(array('search_index'=>1))->getField('id,name');
        $spec_item = M('spec_item')->where(array('spec_id'=>array('in',array_keys($spec))))->getField('id,spec_id,item');

        $list_spec = array();
        $old_spec = $filter_param['spec'];
        foreach ($spec_key as $k => $v) {
            if (strpos($old_spec, $spec_item[$v]['spec_id'] . '_') === 0 || strpos($old_spec, '@' . $spec_item[$v]['spec_id'] . '_') || $spec_item[$v]['spec_id'] == '')
                continue;
            $list_spec[$spec_item[$v]['spec_id']]['spec_id'] = $spec_item[$v]['spec_id'];
            $list_spec[$spec_item[$v]['spec_id']]['name'] = $spec[$spec_item[$v]['spec_id']];
            //$list_spec[$spec_item[$v]['spec_id']]['item'][$v] = $spec_item[$v]['item'];

            // 帅选参数
            if (!empty($old_spec))
                $filter_param['spec'] = $old_spec . '@' . $spec_item[$v]['spec_id'] . '_' . $v;
            else
                $filter_param['spec'] = $spec_item[$v]['spec_id'] . '_' . $v;
            $list_spec[$spec_item[$v]['spec_id']]['item'][] = array('key' => $spec_item[$v]['spec_id'], 'val' => $v, 'item' => $spec_item[$v]['item'], 'href' => urldecode(U("Goods/$action", $filter_param, '')));
        }

        if ($mode == 1) return $list_spec;
        return array('status' => 1, 'msg' => '', 'result' => $list_spec);
    }

    /**
     * @param array $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode 0  返回数组形式  1 直接返回result
     * @return array
     * 获取商品列表页帅选属性
     */
    public function get_filter_attr($goods_id_arr = array(), $filter_param, $action, $mode = 0)
    {
        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';
        $goods_attr = M('goods_attr')->where(['goods_id'=>['in',$goods_id_str],'attr_value'=>['<>','']])->select();
        // $goods_attr = M('goods_attr')->where("attr_value != ''")->select();
        $goods_attribute = M('goods_attribute')->where("attr_index = 1")->getField('attr_id,attr_name,attr_index');
        if (empty($goods_attr)) {
            if ($mode == 1) return array();
            return array('status' => 1, 'msg' => '', 'result' => array());
        }
        $list_attr = $attr_value_arr = array();
        $old_attr = $filter_param['attr'];
        foreach ($goods_attr as $k => $v) {
            // 存在的帅选不再显示
            if (strpos($old_attr, $v['attr_id'] . '_') === 0 || strpos($old_attr, '@' . $v['attr_id'] . '_'))
                continue;
            if ($goods_attribute[$v['attr_id']]['attr_index'] == 0)
                continue;
            $v['attr_value'] = trim($v['attr_value']);
            // 如果同一个属性id 的属性值存储过了 就不再存贮
            if (in_array($v['attr_id'] . '_' . $v['attr_value'], (array)$attr_value_arr[$v['attr_id']]))
                continue;
            $attr_value_arr[$v['attr_id']][] = $v['attr_id'] . '_' . $v['attr_value'];

            $list_attr[$v['attr_id']]['attr_id'] = $v['attr_id'];
            $list_attr[$v['attr_id']]['attr_name'] = $goods_attribute[$v['attr_id']]['attr_name'];

            // 帅选参数
            if (!empty($old_attr))
                $filter_param['attr'] = $old_attr . '@' . $v['attr_id'] . '_' . $v['attr_value'];
            else
                $filter_param['attr'] = $v['attr_id'] . '_' . $v['attr_value'];

            $list_attr[$v['attr_id']]['attr_value'][] = array('key' => $v['attr_id'], 'val' => $v['attr_value'], 'attr_value' => $v['attr_value'], 'href' => urldecode(U("Goods/$action", $filter_param, '')));
            //unset($filter_param['attr_id_'.$v['attr_id']]);
        }
        if ($mode == 1) return $list_attr;
        return array('status' => 1, 'msg' => '', 'result' => $list_attr);
    }

    /**
     * $spec_item_id 参数类似于以下
     * Array
     * 5 => Array( // 网络
     * [0] => 11  // 4G
     * [1] => 12 // 3G
     * )
     * 6 => Array( // 内存
     * [0] => 14 // 8G
     * ) 其查询类似于  where 网络 in (4G,3G) and 内存 in(G)
     * @param type $spec_item_id 前台列表搜索页面 提交过来的  规格id 和规格项id
     * @param $mode 0  返回数组形式  1 直接返回result
     * @return array
     */
    public function get_spec_item_goods_id($spec_item_id, $mode = 0)
    {
        /** 最后组装的 sql语句
         * select * from (
         * select a.*,concat('_',`key`,'_') as key2 from `tp_spec_goods_price` as a
         * ) b
         * where (key2 like '%_7_%' or key2  like '%_8_%') and (key2  like '%_18_%')and (key2  like '%_24_%')
         */
        $where = " where ( 1 = 1 )";
        foreach ($spec_item_id as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $like[] = " key2 like '%\_{$v2}\_%' ";
            }
            $where .= " and (" . implode('or', $like) . ")";
            $like = array();
        }

        $sql = "select * from (
                  select *,concat('_',`key`,'_') as key2 from __PREFIX__spec_goods_price as a
              ) b  $where";
        $result = DB::query($sql);
        $goods_id_arr = get_arr_column($result, 'goods_id');  // 只获取商品id 那一列        
        if ($mode == 1) return array_unique($goods_id_arr);
        return array('status' => 1, 'msg' => '', 'result' => array_unique($goods_id_arr));
    }

    /**
     * @param $attr_id
     * @param int $mode
     * @return array
     * 提交过了的值类似于
     * Array
     * (
     * [59] => Array 外观样式
     * [0] => 翻盖
     * [1] => 滑盖
     * [75] => Array 天线位置:
     * [0] => 内置
     * )
     * 根据提交过了的商品属性 找出对应的商品id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function get_attr_goods_id($attr_id, $mode = 0)
    {
        // select * from `tp_goods_attr` where attr_id = 185 and (attr_value in('白色','黑色'))
        foreach ($attr_id as $key => $val) {
            $sql = "select goods_id from __PREFIX__goods_attr  where attr_id = $key and attr_value in ('" . implode("','", $val) . "')";
            $result = DB::query($sql);
            $tmp_attr[] = get_arr_column($result, 'goods_id');  // 只获取商品id 那一列
        }

        $goods_id_attr = $tmp_attr[0];
        foreach ($tmp_attr as $key => $val) {
            $goods_id_attr = array_intersect($goods_id_attr, $val);
        }
        if ($mode == 1) return $goods_id_attr;
        return array('status' => 1, 'msg' => '', 'result' => $goods_id_attr);
    }

    /**
     * 获取某个商品的评论统计
     * 全部评论数  好评数 中评数  差评数
     */
    public function commentStatistics($goods_id)
    {
    	$commentWhere = ['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>0];
        $c1 = M('Comment')->where("is_show = 1 and  goods_id = :goods_id and parent_id = 0 and  ceil((deliver_rank + goods_rank + service_rank) / 3) in(4,5)")->bind(['goods_id'=>$goods_id])->count(); // 好评
        $c2 = M('Comment')->where("is_show = 1 and  goods_id = :goods_id and parent_id = 0 and  ceil((deliver_rank + goods_rank + service_rank) / 3) in(3)")->bind(['goods_id'=>$goods_id])->count(); // 中评
        $c3 = M('Comment')->where("is_show = 1 and  goods_id = :goods_id and parent_id = 0 and  ceil((deliver_rank + goods_rank + service_rank) / 3) in(1,2)")->bind(['goods_id'=>$goods_id])->count(); // 差评
        $c4 = M('Comment')->where($commentWhere)->where("img !='' and img NOT LIKE 'N;%'")->count(); // 晒图

        $c0 = $c1 + $c2 + $c3; // 所有评论
        if($c0 <= 0){
            $rate1 = 100;
            $rate2 = 0;
            $rate3 = 0;
        }else{
            $rate1 = ceil($c1 / $c0 * 100); // 好评率
            $rate2 = ceil($c2 / $c0 * 100); // 中评率
            $rate3 = ceil($c3 / $c0 * 100); // 差评率
        }

        return array('c0' => $c0, 'c1' => $c1, 'c2' => $c2, 'c3' => $c3,'c4' => $c4, 'rate1' => $rate1, 'rate2' => $rate2, 'rate3' => $rate3);
    }

    /**
     * 商品收藏
     * @param type $user_id 用户id
     * @param type $goods_id 商品id
     * @return type
     */
    public function collect_goods($user_id, $goods_id)
    {
        if (!is_numeric($user_id) || $user_id <= 0) return array('status' => -1, 'msg' => '必须登录后才能收藏', 'result' => array());
        //$count = M('Goods')->where("goods_id = $goods_id")->count();
        //if($count == 0)  return array('status'=>-2,'msg'=>'收藏的商品不存在','result'=>array());
        $count = M('GoodsCollect')->where("user_id",$user_id)->where("goods_id", $goods_id)->count();
        if ($count > 0) return array('status' => -3, 'msg' => '商品已收藏', 'result' => array());
        M('GoodsCollect')->add(array('goods_id' => $goods_id, 'user_id' => $user_id, 'add_time' => time()));
        return array('status' => 1, 'msg' => '收藏成功!请到个人中心查看', 'result' => array());
    }

    /**
     * 获取商品规格
     */
    public function get_spec($goods_id)
    {
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = M('SpecGoodsPrice')->where("goods_id", $goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        $filter_spec = array();
        if ($keys) {
            $specImage = M('SpecImage')->where(['goods_id'=>$goods_id,'src'=>['<>','']])->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_', ',', $keys);
            $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY b.id";
            $filter_spec2 = \think\Db::query($sql);
            foreach ($filter_spec2 as $key => $val) {
                $filter_spec[$val['name']][] = array(
                    'item_id' => $val['id'],
                    'item' => $val['item'],
                    'src' => $specImage[$val['id']],
                );
            }
        }
        return $filter_spec;
    }


    /**
     * 获取相关分类
     */
    public function get_siblings_cate($cat_id)
    {
        if (empty($cat_id))
            return array();
        $cate_info = M('goods_category')->where("id", $cat_id)->find();
        $siblings_cate = M('goods_category')->where(['id'=>['<>',$cat_id],'parent_id'=>$cate_info['parent_id']])->select();
        return empty($siblings_cate) ? array() : $siblings_cate;
    }

    /**
     * 看了又看
     */
    public function get_look_see($goods)
    {
    	$list=M('goods')->where(['goods_id'=>['<>',$goods['goods_id']],'cat_id'=>['=',$goods['cat_id']],'is_on_sale'=>1])->order('sales_sum desc')->limit(12)->select();
        $list_2=$list_3=$list_no=array();
        if(count($list)<12){
        	$limit=12-count($list);
        	$cate_info=M('goods_category')->where('id='.$goods['cat_id'])->find();
        	$tmp=explode('_',$cate_info['parent_id_path']);
    		if($tmp[2]>0&&$tmp[2]!=$goods['cat_id']){
    			/*同级(三级)*/
    			$second_cat=M('goods_category')->where('parent_id='.$tmp[2])->field('id')->select();
    			foreach($second_cat as $k=>$v){
    				if($v['id']!=$goods['cat_id']){
    					$second[]=$v['id'];
    				}
    			}
    			$list_3=M('goods')->where(['goods_id'=>['<>',$goods['goods_id']],'cat_id'=>['in',$second],'is_on_sale'=>1])->order('sales_sum desc')->limit($limit)->select();
    			if(count($list_3)<$limit){
    				$limit=$limit-count($list_3);
    				/*同级(二级)*/
	    			$one_cat=M('goods_category')->where('parent_id='.$tmp[1])->field('id')->select();
	    			foreach($one_cat as $k=>$v){
	    				$one[]=$v['id'];
	    			}
	    			$list_2=M('goods')->where(['goods_id'=>['<>',$goods['goods_id']],'cat_id'=>['in',$one],'is_on_sale'=>1])->order('sales_sum desc')->limit($limit)->select();
	    		}
    		}elseif($tmp[1]!=$goods['cat_id']){
    			/*同级(二级)*/
    			$one_cat=M('goods_category')->where('parent_id='.$tmp[1])->field('id')->select();
    			foreach($one_cat as $k=>$v){
    				if($v['id']!=$goods['cat_id']){
    					$one[]=$v['id'];
    				}
    			}
    			$list_2=M('goods')->where(['goods_id'=>['<>',$goods['goods_id']],'cat_id'=>['in',$one],'is_on_sale'=>1])->order('sales_sum desc')->limit($limit)->select();
    		}
	    }
	    $count=count($list)+count($list_2)+count($list_3);
      	if($count<12){
      		$limit=12-$count;
      		$list_no=M('goods')->where(['goods_id'=>['<>',$goods['goods_id']],'cat_id'=>['<>',$goods['cat_id']],'is_on_sale'=>1])->order('sales_sum desc')->limit($limit)->select();
      	}
		$list = array_merge($list, $list_3,$list_2,$list_no); 
        return $list;
    }


    /**
     * * 帅选的价格期间
     * @param $goods_id_arr 帅选的分类id
     * @param $filter_param
     * @param $action
     * @param int $c 分几段 默认分5 段
     * @return array
     */
    function get_filter_price($goods_id_arr, $filter_param, $action, $c = 5)
    {

        if (!empty($filter_param['price']))
            return array();

        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';
        $priceList = M('goods')->where("goods_id", "in", $goods_id_str)->getField('shop_price', true);  //where("goods_id in($goods_id_str)")->select();
        rsort($priceList);
        $max_price = (int)$priceList[0];

        $psize = ceil($max_price / $c); // 每一段累积的价钱
        $parr = array();
        for ($i = 0; $i < $c; $i++) {
            $start = $i * $psize;
            $end = $start + $psize;

            // 如果没有这个价格范围的商品则不列出来
            $in = false;
            foreach ($priceList as $k => $v) {
                if ($v > $start && $v < $end)
                    $in = true;
            }
            if ($in == false)
                continue;

            $filter_param['price'] = "{$start}-{$end}";
            if ($i == 0)
                $parr[] = array('value' => "{$end}元以下", 'href' => urldecode(U("Goods/$action", $filter_param, '')));
            elseif ($i == ($c - 1))
                $parr[] = array('value' => "{$end}元以上", 'href' => urldecode(U("Goods/$action", $filter_param, '')));
            else
                $parr[] = array('value' => "{$start}-{$end}元", 'href' => urldecode(U("Goods/$action", $filter_param, '')));
        }
        return $parr;
    }

    /**
     * 帅选条件菜单
     */
    function get_filter_menu($filter_param, $action)
    {
        $menu_list = array();
        // 品牌
        if (!empty($filter_param['brand_id'])) {
            $brand_list = M('brand')->getField('id,name');
            $brand_id = explode('_', $filter_param['brand_id']);
            $brand['text'] = "品牌:";
            foreach ($brand_id as $k => $v) {
                $brand['text'] .= $brand_list[$v] . ',';
            }
            $brand['text'] = substr($brand['text'], 0, -1);
            $tmp = $filter_param;
            unset($tmp['brand_id']); // 当前的参数不再带入
            $brand['href'] = urldecode(U("Goods/$action", $tmp, ''));
            $menu_list[] = $brand;
        }
        // 规格
        if (!empty($filter_param['spec'])) {
            $spec = M('spec')->getField('id,name');
            $spec_item = M('spec_item')->getField('id,item');
            $spec_group = explode('@', $filter_param['spec']);
            foreach ($spec_group as $k => $v) {
                $spec_group2 = explode('_', $v);
                $spec_menu['text'] = $spec[$spec_group2[0]] . ':';
                array_shift($spec_group2); // 弹出第一个规格名称
                foreach ($spec_group2 as $k2 => $v2) {
                    $spec_menu['text'] .= $spec_item[$v2] . ',';
                }
                $spec_menu['text'] = substr($spec_menu['text'], 0, -1);

                $tmp = $spec_group;
                $tmp2 = $filter_param;
                unset($tmp[$k]);
                $tmp2['spec'] = implode('@', $tmp); // 当前的参数不再带入
                $spec_menu['href'] = urldecode(U("Goods/$action", $tmp2, ''));
                $menu_list[] = $spec_menu;
            }
        }
        // 属性
        if (!empty($filter_param['attr'])) {
            $goods_attribute = M('goods_attribute')->getField('attr_id,attr_name');
            $attr_group = explode('@', $filter_param['attr']);
            foreach ($attr_group as $k => $v) {
                $attr_group2 = explode('_', $v);
                $attr_menu['text'] = $goods_attribute[$attr_group2[0]] . ':';
                array_shift($attr_group2); // 弹出第一个规格名称
                foreach ($attr_group2 as $k2 => $v2) {
                    $attr_menu['text'] .= $v2 . ',';
                }
                $attr_menu['text'] = substr($attr_menu['text'], 0, -1);

                $tmp = $attr_group;
                $tmp2 = $filter_param;
                unset($tmp[$k]);
                $tmp2['attr'] = implode('@', $tmp); // 当前的参数不再带入
                $attr_menu['href'] = urldecode(U("Goods/$action", $tmp2, ''));
                $menu_list[] = $attr_menu;
            }
        }
        // 价格
        if (!empty($filter_param['price'])) {
            $price_menu['text'] = "价格:" . $filter_param['price'];
            unset($filter_param['price']);
            $price_menu['href'] = urldecode(U("Goods/$action", $filter_param, ''));
            $menu_list[] = $price_menu;
        }

        return $menu_list;
    }

    /**
     * 传入当前分类 如果当前是 2级 找一级
     * 如果当前是 3级 找2 级 和 一级
     * @param  $goodsCate
     */
    function get_goods_cate(&$goodsCate)
    {
        if (empty($goodsCate)) return array();
        $cateAll = get_goods_category_tree();
        if ($goodsCate['level'] == 1) {
            $cateArr = $cateAll[$goodsCate['id']]['tmenu'];
            $goodsCate['parent_name'] = $goodsCate['name'];
            $goodsCate['select_id'] = 0;
        } elseif ($goodsCate['level'] == 2) {
            $cateArr = $cateAll[$goodsCate['parent_id']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$goodsCate['parent_id']]['name'];//顶级分类名称
            $goodsCate['open_id'] = $goodsCate['id'];//默认展开分类
            $goodsCate['select_id'] = 0;
        } else {
            $parent = M('GoodsCategory')->where("id", $goodsCate['parent_id'])->order('`sort_order` desc')->find();//父类
            $cateArr = $cateAll[$parent['parent_id']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$parent['parent_id']]['name'];//顶级分类名称
            $goodsCate['open_id'] = $parent['id'];
            $goodsCate['select_id'] = $goodsCate['id'];//默认选中分类
        }
        return $cateArr;
    }

    /**
     * @param  $brand_id 帅选品牌id
     * @param  $price 帅选价格
     * @return array|mixed
     */
    function getGoodsIdByBrandPrice($brand_id, $price)
    {
        if (empty($brand_id) && empty($price))
            return array();

        $where = " 1 = 1 ";
        $bind = array();
        if ($brand_id) // 品牌查询
        {
            $brand_id_arr = explode('_', $brand_id);
            $where .= " and brand_id in(:brand_id_arr)";
            $bind['brand_id_arr'] = implode(',', $brand_id_arr);
        }
        if ($price)// 价格查询
        {
            $price = explode('-', $price);
            $where .= " and shop_price >= :shop_price1 and  shop_price <= :shop_price2 ";
            $bind['shop_price1'] = $price[0];
            $bind['shop_price2'] = $price[1];
        }
        $arr = M('goods')->where($where)->bind($bind)->getField('goods_id', true);
        return $arr ? $arr : array();
    }

    /**
     * @return array|\type
     * 根据规格 查找 商品id
     * @param $spec 规格
     */
    function getGoodsIdBySpec($spec)
    {
        if (empty($spec))
            return array();

        $spec_group = explode('@', $spec);
        $where = " where 1=1 ";
        foreach ($spec_group as $k => $v) {
            $spec_group2 = explode('_', $v);
            array_shift($spec_group2);
            $like = array();
            foreach ($spec_group2 as $k2 => $v2) {
                $like[] = " key2  like '%\_$v2\_%' ";
            }
            $where .= " and (" . implode('or', $like) . ") ";
        }
        $sql = "select * from (
                  select *,concat('_',`key`,'_') as key2 from __PREFIX__spec_goods_price as a
              ) b  $where";
        //$Model = new \think\Model();
        $result = \think\Db::query($sql);
        $arr = get_arr_column($result, 'goods_id');  // 只获取商品id 那一列        
        return ($arr ? $arr : array_unique($arr));
    }

    /**
     * @param $attr 属性
     * @return array|mixed
     * 根据属性 查找 商品id
     * 59_直板_翻盖
     * 80_BT4.0_BT4.1
     */
    function getGoodsIdByAttr($attr)
    {
        if (empty($attr))
            return array();

        $attr_group = explode('@', $attr);
        $attr_id = $attr_value = array();
        foreach ($attr_group as $k => $v) {
            $attr_group2 = explode('_', $v);
            $attr_id[] = array_shift($attr_group2);
            $attr_value = array_merge($attr_value, $attr_group2);
        }
        $where = "attr_id in(:attr_id) and attr_value in(:attr_value)";
        $bind['attr_id'] =  implode(',', $attr_id);
        $bind['attr_value'] = implode("','", $attr_value);
        $c = count($attr_id) - 1;
        if ($c > 0)
            $arr = M('goods_attr')->where($where)->bind($bind)->group('goods_id')->having("count(goods_id) > $c")->getField("goods_id", true);  //select * from   `tp_goods_attr` where attr_id in(59,80) and attr_value in('直板','翻盖','蓝牙4.0') group by goods_id having count(goods_id) > 1
        else
            $arr = M('goods_attr')->where($where)->bind($bind)->getField("goods_id", true); // 如果只有一个条件不再进行分组查询

        return ($arr ? $arr : array_unique($arr));
    }

    /**
     * 获取地址
     * @return array
     */
    function getRegionList()
    {
        $parent_region = M('region')->field('id,name')->where(array('level'=>1))->cache(true)->select();
        $ip_location = array();
        $city_location = array();
        foreach($parent_region as $key=>$val){
            $c = M('region')->field('id,name')->where(array('parent_id'=>$parent_region[$key]['id']))->order('id asc')->cache(true)->select();
            $ip_location[$parent_region[$key]['name']] = array('id'=>$parent_region[$key]['id'],'root'=>0,'djd'=>1,'c'=>$c[0]['id']);
            $city_location[$parent_region[$key]['id']] = $c;
        }
        $res = array(
            'ip_location'=>$ip_location,
            'city_location'=>$city_location
        );
        return $res;
    }

    /**
     * 寻找Region_id的父级id
     * @param $cid
     * @return array
     */
    function getParentRegionList($cid){
        //$pids = '';
        $pids = array();
        $parent_id =  M('region')->where(array('id'=>$cid))->getField('parent_id');
        if($parent_id != 0){
            //$pids .= $parent_id;
            array_push($pids,$parent_id);
            $npids = $this->getParentRegionList($parent_id);
            if(!empty($npids)){
                //$pids .= ','.$npids;
                $pids = array_merge($pids,$npids);
            }

        }
        return $pids;
    }

    /**
     * 商品物流配送和运费
     * @param $goods_id
     * @param $region_id
     * @return array
     */
    function getGoodsDispatching($goods_id,$region_id)
    {
        $return_data = array('status'=>1,'msg'=>'');
        $goods = M('goods')->where(array('goods_id'=>$goods_id))->find();
        //检查商品是否包邮
        if($goods['is_free_shipping']){
            $return_data['msg'] = '有货';
            $return_data['result'] = array(array('shipping_name'=>'包邮','freight'=>0));
            return $return_data;
        }
        $cart_logic = new CartLogic();
        //商品没有配置物流，使用物流配置里的默认物流
        if(empty($goods['shipping_area_ids'])){
            $plugin_goods_shipping = M('plugin')->where(array('type'=>'shipping'))->select();
            $goods_shipping = array();
            foreach($plugin_goods_shipping as $k=>$v){
                $goods_shipping[$k]['freight'] = $cart_logic->cart_freight2($goods_shipping[$k]['shipping_code'],0,0,0,$goods['weight']);//默认全国
                $goods_shipping[$k]['shipping_name'] = $plugin_goods_shipping[$k]['name'];
            }
            $return_data['msg'] = '有货';
            $return_data['result'] = $goods_shipping;
            return $return_data;
        }
        //查找地区$region_id的所有父类，与地区地址表进行匹配
        $goods_shipping_area_id_array = explode(',',$goods['shipping_area_ids']);
        $parent_region_id = $this->getParentRegionList($region_id);
        array_push($parent_region_id,(string)$region_id);//把region_id和它全部父级存起来
        $find_shipping_area_id = M('area_region')->where(array('region_id'=>array('in',$parent_region_id)))->group('shipping_area_id')->getField('shipping_area_id',true);
        $shipping_area_id_array =array();
        foreach($find_shipping_area_id as $key=>$val){
            if(in_array($find_shipping_area_id[$key],$goods_shipping_area_id_array)){
                array_push($shipping_area_id_array,$find_shipping_area_id[$key]);
            }
        }
        //没有匹配到，就使用商品配置的物流id去物流地址表去查找
        if(count($shipping_area_id_array) == 0){
            $goods_shipping = M('shipping_area')->where(array('shipping_area_id'=>array('in',$goods_shipping_area_id_array),'is_default'=>1))->select();
            //查询到就返回物流信息和运费，没有返回无货
            if(!empty($goods_shipping)){
                foreach($goods_shipping as $k=>$v){
                    $goods_shipping[$k]['freight'] = $cart_logic->cart_freight2($goods_shipping[$k]['shipping_code'],0,0,0,$goods['weight']);
                    $goods_shipping[$k]['shipping_name'] = M('plugin')->where(array('type'=>'shipping','code'=>$goods_shipping[$k]['shipping_code']))->getField('name');
                }
                $return_data['msg'] = '有货';
                $return_data['result'] = $goods_shipping;
                return $return_data;
            }else{
                $return_data['status'] = -1;
                $return_data['msg'] = '无货';
                return $return_data;
            }
        }
        //匹配到就返回物流信息和运费
        $goods_shipping = M('')
            ->table(C('DB_PREFIX').'area_region ar')
            ->join('__SHIPPING_AREA__ sa','sa.shipping_area_id = ar.shipping_area_id','INNER')
            ->where(array('ar.shipping_area_id'=>array('in',$shipping_area_id_array)))
            ->group('sa.shipping_code')
            ->select();
        //没匹配到就返回无货
        if(empty($goods_shipping)){
            $return_data['status'] = -1;
            $return_data['msg'] = '无货';
            return $return_data;
        }
        foreach($goods_shipping as $k=>$v){
            $goods_shipping[$k]['freight'] = $cart_logic->cart_freight2($goods_shipping[$k]['shipping_code'],0,0,$goods_shipping[$k]['region_id'],$goods['weight']);
            $goods_shipping[$k]['shipping_name'] = M('plugin')->where(array('type'=>'shipping','code'=>$goods_shipping[$k]['shipping_code']))->getField('name');
        }
        $return_data = array(
            'status'=>1,
            'msg'=>'可发货',
            'result'=>$goods_shipping
        );
        return $return_data;
    }

    /**
    *网站自营,入驻商家,货到付款,仅看有货,促销商品
    * @return $sel 筛选条件
    * @return $cat_id 分类ID
    * @return $arrid 符合条件的ID
    */
    function get_filter_selected($sel ,$cat_id = 1){
        $where = " 1 = 1 ";
        $Goods = M('goods')->where("cat_id" ,"in" ,implode(',', $cat_id));
        //查看全部
        if($sel == 'selall'){
            $where .= '';
        }
        //促销商品
        if($sel == 'prom_type'){
            $where .= ' and prom_type = 3';
        }
        //看有货
        if($sel == 'store_count'){
            $where .= ' and store_count > 0';
        }
        //看包邮
        if($sel == 'free_post'){
            $where .= ' and is_free_shipping=1';
        }
        //看全部
        if($sel == 'all'){
            $arrid = $Goods->getField('goods_id', true);
        }else{
            $arrid = $Goods->where($where)->getField('goods_id', true);
        }
        return $arrid;
    }
    /**
     * 用户浏览记录
     * @author lxl
     * @time  17-4-20
     */
    public function add_visit_log($user_id,$goods){
        $record = M('goods_visit')->where(array('user_id'=>$user_id,'goods_id'=>$goods['goods_id']))->find();
        if($record){
            M('goods_visit')->where(array('user_id'=>$user_id,'goods_id'=>$goods['goods_id']))->save(array('visittime'=>time()));
        }else{
            $visit = array('user_id'=>$user_id,'goods_id'=>$goods['goods_id'],'visittime'=>time(),'cat_id'=>$goods['cat_id'],'extend_cat_id'=>$goods['extend_cat_id']);
            M('goods_visit')->add($visit);
        }
    }
}

 