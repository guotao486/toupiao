<?php

namespace app\admin\model;

use think\Model;

class File extends Model
{

    // 表名
    protected $name = 'file';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;


    /*
     * 根据ids查询附件列表
     * @param ids
     * return array
     */
    public function fileList($ids = null)
    {
        if(empty($ids)){
            return false;
        }
        $list = $this->where('id','in',$ids)
            ->order('id desc')
            ->select();

        return collection($list)->toArray();
    }

    /*
     * 根据id返回附件详情
     * @param id
     * return array
     */
    public function file($id = 0)
    {
        if(empty($id)){
            return false;
        }

        $info = $this->where('id',$id)->find();

        return $info;
    }

    public function fileType($type = 'image')
    {

    }
}
