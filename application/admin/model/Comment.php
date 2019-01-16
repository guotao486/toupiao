<?php

namespace app\admin\model;

use think\Model;

class Comment extends Model
{
    // 表名
    protected $name = 'activity_comment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'create_time_text',
        'delete_time_text',
        'userName',
        'toUserName',
        'create_time_text2'
    ];



    public function getUserNameAttr($value, $data)
    {
        $value = $data['userId'];
        return DingUser::dingIdToName($value)[0];
    }

    public function getToUserNameAttr($value, $data)
    {
        $value = $data['toUserId'];
        return DingUser::dingIdToName($value)[0];
    }

    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    public function getCreateTimeText2Attr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d", $value) : $value;
    }


    public function getDeleteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['delete_time']) ? $data['delete_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setDeleteTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

}
