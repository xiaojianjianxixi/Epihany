<?php

namespace app\home\model;

use think\model;
use think\Db;

/**
 * @package Home\Model
 */
class Message extends Model
{
    protected $tableName = 'message';
    protected $_validate = array();

    /**
     * 获取用户的系统消息
     * @return array
     */
    public function getUserMessageNotice()
    {
        $this->checkPublicMessage();
        $user_info = session('user');
        $user_system_message_no_read_where = array(
                'um.user_id' => $user_info['user_id'],
                'um.status' => 0,
                'um.category' => 0
        );
        $user_system_message_no_read = DB::name('user_message')
            ->alias('um')
            ->field('um.rec_id, um.message_id, m.message, m.send_time')
            ->join('__MESSAGE__ m','um.message_id = m.message_id','LEFT')
            ->where($user_system_message_no_read_where)
            ->order('m.send_time desc')->select();
        return $user_system_message;
    }
    /**
     * 更新用户的系统消息
     * @return array
     */
    public function updateUserMessageNotice()
    {
        $this->checkPublicMessage();
        return true;
    }

    /**
     * 查询系统全体消息，如有将其插入用户信息表
     * @author dyr
     * @time 2016/09/01
     */
    public function checkPublicMessage()
    {
        $user_info = session('user');

        $user_message = DB::name('user_message')->where(array('user_id' => $user_info['user_id'], 'category' => 0))->select();
        $message_where = array(
            'category' => 0,
            'type' => 1,
            'send_time' => array('gt', $user_info['reg_time']),
        );
        if (!empty($user_message)) {
            $user_id_array = get_arr_column($user_message, 'message_id');
            $message_where['message_id'] = array('NOT IN', $user_id_array);
        }
        $user_system_public_no_read = DB::name('message')->field('message_id')->where($message_where)->select();
        foreach($user_system_public_no_read as $key){
            DB::name('user_message')->add(['user_id'=>$user_info['user_id'],'message_id'=>$key['message_id'],'category'=>0,'status'=>0]);
        }
    }
}