<?php

namespace app\admin\model;

use think\Model;
use dd\dingAction;
class DingUser extends Model
{

    // 表名
    protected $name = 'ding_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function saveUser($userId)
    {
        $userInfo = dingAction::getUser($userId);
        if($userInfo->errcode!=0){
            return $userInfo;
        }
        $params['userId'] = $userId;
        if(!empty($userInfo->extattr)&&!empty($userInfo->extattr->自然名)){
            $params['nick'] = $userInfo->extattr->自然名;
        }
        $params['name'] = $userInfo->name;
        $params['avatar'] = $userInfo->avatar;
        $params['mobile'] = $userInfo->mobile;
        $params['departmentId'] = $userInfo->department[0];
        $dept = dingAction::getDept($userInfo->department[0]);
        if($dept->errcode!=0){
            return $dept;
        }
        $params['department'] =$dept->name;
        return $params;
    }

    public function add($params)
    {
        return $this->allowField(true)->save($params);
    }

    public static function dingIdToName( $ids = null,$type = '=')
    {
        return DingUser::where('userId',$type,$ids)->column('name');
    }

    public function getNameByUserid($id)
    {
        return $this->where('userId',$id)->value('name');
    }





}
