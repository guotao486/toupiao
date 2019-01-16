<?php

namespace app\admin\model;

use think\Model;

class Signuser extends Model
{
    // 表名
    protected $name = 'sign_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'sign_time';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'sign_time_text',
        'end_time_text'
    ];
    

    



    public function getSignTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sign_time']) ? $data['sign_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['end_time']) ? $data['end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setSignTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setEndTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


}
