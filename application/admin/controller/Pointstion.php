<?php


namespace app\admin\controller;

use think\AjaxPage;
use think\Page;
use app\admin\logic\GoodsLogic;

class Pointstion extends Base
{

    public function index()
    {
        return $this->fetch();
    }

    /**
     * 商品活动列表
     */
    public function points_goods_list()
    {
        $level = M('user_level')->select();
        if ($level) {
            foreach ($level as $v) {
                $lv[$v['level_id']] = $v['level_name'];
            }
        }
        $count = M('points_goods')->where('is_close=0')->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $res = M('points_goods')->where('is_close=0')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if ($res) {
            foreach ($res as $val) {
                if (!empty($val['group']) && !empty($lv)) {
                    $val['group'] = explode(',', $val['group']);
                    foreach ($val['group'] as $v) {
                        $val['group_name'] .= $lv[$v] . ',';
                    }
                }
                $points_list[] = $val;
            }
        }
        $this->assign('pager',$Page);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('points_list', $points_list);
        return $this->fetch();
    }

    public function points_goods_info()
    {
        $level = M('user_level')->select();
        $this->assign('level', $level);
        $points_id = I('id');
        $info['start_time'] = date('Y-m-d');
        $info['end_time'] = date('Y-m-d', time() + 3600 * 60 * 24);
        if ($points_id > 0) {
            $info = M('points_goods')->where("id=$points_id")->find();
            $info['start_time'] = date('Y-m-d', $info['start_time']);
            $info['end_time'] = date('Y-m-d', $info['end_time']);
            $points_goods = M('goods')->where("points_id=$points_id")->select();
            $this->assign('points_goods', $points_goods);
        }
        $this->assign('info', $info);
        $this->assign('min_date', date('Y-m-d'));
        $this->initEditor();
        return $this->fetch();
    }

    public function points_goods_save()
    {
        $points_id = I('id');
        $data = I('post.');
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        if($data['group']){
        	$data['group'] = implode(',', $data['group']);
        }
        if ($points_id) {
            M('points_goods')->where("id=$points_id")->save($data);
            $last_id = $points_id;
            adminLog("管理员修改了积分活动 " . I('name'));
        } else {
            $last_id = M('points_goods')->add($data);
            adminLog("管理员添加了积分活动 " . I('name'));
        }

        if (is_array($data['goods_id'])) {
            $goods_id = implode(',', $data['goods_id']);
            if ($points_id > 0) {
                M("goods")->where("points_id=$points_id")->save(array('points_id' => 0));
            }
            /*更新商品积分活动id*/
            M("goods")->where("goods_id in($goods_id)")->save(array('points_id' => $last_id));
            /*更新商品兑换积分*/
            M("goods")->where("goods_id in($goods_id) and exchange_integral !=".$data['points'])->save(array('exchange_integral'=>$data['points']));
        }
        $this->success('编辑积分活动成功', U('Pointstion/points_goods_list'));
    }

    public function points_goods_del()
    {
        $points_id = I('id');
        $info=M('PointsGoods')->where('id='.$points_id)->find();
        /*更新商品积分*/
        M("goods")->where("points_id=".$points_id.' and exchange_integral='.$info['points'])->save(array('exchange_integral' => 0));
        /*更新商品活动id*/
        M("goods")->where("points_id=$points_id")->save(array('points_id' => 0));
        M('points_goods')->where("id=$points_id")->delete();
        $this->success('删除活动成功', U('Pointstion/points_goods_list'));
    }
    public function get_goods()
    {
        $points_id = I('id');
        $count = M('goods')->where("points_id=$points_id")->count();
        $Page = new Page($count, 10);
        $goodsList = M('goods')->where("points_id=$points_id")->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('goodsList', $goodsList);
        return $this->fetch();
    }

    public function search_goods()
    {
        $GoodsLogic = new GoodsLogic;
        $brandList = $GoodsLogic->getSortBrands();
        $this->assign('brandList', $brandList);
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList', $categoryList);
        $goods_id = I('goods_id');
        $where = ' is_on_sale = 1 and points_id = 0 and store_count>0 ';//搜索条件
        if (!empty($goods_id)) {
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro') && $where = "$where and " . I('intro') . " = 1";
        if (I('cat_id')) {
            $this->assign('cat_id', I('cat_id'));
            $grandson_ids = getCatGrandson(I('cat_id'));
            $where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
        }
        if (I('brand_id')) {
            $this->assign('brand_id', I('brand_id'));
            $where = "$where and brand_id = " . I('brand_id');
        }
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
        }
        $count = M('goods')->where($where)->count();
        $Page = new Page($count, 10);
        $goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $this->assign('pager', $Page);//赋值分页输出
        $tpl = I('get.tpl', 'search_goods');
        return $this->fetch($tpl);
    }

  
    private function initEditor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUp', array('savepath' => 'promotion')));
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp', array('savepath' => 'promotion')));
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp', array('savepath' => 'promotion')));
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage', array('savepath' => 'promotion')));
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager', array('savepath' => 'promotion')));
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp', array('savepath' => 'promotion')));
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie', array('savepath' => 'promotion')));
        $this->assign("URL_Home", "");
    }


}