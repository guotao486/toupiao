<?php

namespace app\admin\model\prize;

use think\Model;

class Votes extends Model
{
    // 表名
    protected $name = 'prize_votes';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'create_time_text',
        'prize_title',
        'year_title'
    ];


    public function getYearTitleAttr($value, $data)
    {
        $value = Year::where('id',$data['y_id'])->value('title');
        return $value;
    }
    public function getPrizeTitleAttr($value, $data)
    {
        $value = Prize::where('id',$data['p_id'])->value('title');
        return $value;
    }
    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


}
