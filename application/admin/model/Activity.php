<?php

namespace app\admin\model;

use think\Model;

class Activity extends Model
{

    // 表名
    protected $name = 'sign_activity';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
        'create_time_text',
        'update_time_text',
        'sign_start_time_text',
        'sign_end_time_text',
        'delete_time_text'
    ];



    public function setContentAttr($value)
    {
        return preg_replace("/[\r\n|\r|\n]/","<br>",$value);
    }

    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getSignStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sign_start_time']) ? $data['sign_start_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getSignEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sign_end_time']) ? $data['sign_end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
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


    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setSignStartTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setSignEndTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setDeleteTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


    public static function getField($id = null,$field = null)
    {
        if(empty($field)){
            return self::get($id);
        }else{
            return self::where('id',$id)->field($field)->find();
        }
    }

}
