<?php

namespace app\admin\model\prize;

use think\Model;

class Prize extends Model
{
    // 表名
    protected $name = 'prize';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
        'create_time_text',
        'update_time_text',
        'start_time_text',
        'end_time_text',
        'user_ids',
        'year_title'
    ];


    public function getYearTitleAttr($value, $data)
    {
        $value = Year::where('id',$data['y_id'])->value('title');
        return $value;
    }

    public function getUserIdsAttr($value, $data)
    {
        $value = Join::where('p_id',$data['id'])->column('userId');
        return implode(',',$value);
    }

    public function getStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['start_time']) ? $data['start_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['end_time']) ? $data['end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
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

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }
    protected function setStartTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }
    protected function setEndTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function getPrizeListByYear($yid,$lv=0)
    {
        $where['y_id'] = $yid;
        if($lv!=0){
            $where['lv'] = $lv;
        }
        $prizeList = $this->where($where)->select();

        return $prizeList;
    }
}
