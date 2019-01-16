<?php

namespace app\admin\model\prize;

use app\admin\model\DingUser;
use think\Model;

class Join extends Model
{
    // 表名
    protected $name = 'prize_join';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
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

    //存入参选人信息
    public function setPrizeJoin($userId,$pid,$year,$yid)
    {
        //参选人
        $userIds = explode(',',$userId);
        return $this->addPrizeJoin($userIds,$pid,$year,$yid);
    }

    //添加
    protected function addPrizeJoin($userIds,$pid,$year,$yid)
    {
        $dingUser = new \app\admin\model\DingUser();

        //要添加的参选人数据
        $data = [];
        foreach($userIds as $k=>$id){
            $data[$k]['userId'] = $id;
            $data[$k]['name'] = $dingUser->getNameByUserid($id);
            $data[$k]['p_id'] = $pid;
            $data[$k]['ballot'] = 0;
            $data[$k]['year'] = $year;
            $data[$k]['y_id'] = $yid;
        }
        $result = $this->allowField(true)->isUpdate(false)->saveAll($data);

        //检查成功
        if(count($result) == count($userIds)){
            return true;
        }else{
            return false;
        }
    }
    //删除
    public function delPrizeJoin($userIds,$pid,$year,$yid)
    {
        //参选人
        $dingUser = new \app\admin\model\DingUser();
        $where['p_id'] = $pid;
        $where['userId'] = ['in',$userIds];
        $where['year'] = $year;
        $where['y_id'] = $yid;
        $result = $this->where($where)->delete();

        //检查成功
        if($result == count($userIds)){
            return true;
        }else{
            return false;
        }
    }
    //修改参选人
    public function savePrizeJoin($userId,$pid,$year,$yid)
    {
        //参选人
        $userIds = explode(',',$userId);
        //获得原有参选人id
        $userJoinList = $this->where('p_id',$pid)->column('userId');

        //利用差集检查有没有新的参选人或减少了参选人

        $addUser = array_diff($userIds,$userJoinList);
        $delUser = array_diff($userJoinList,$userIds);

        $addRes = true;
        $delRes = true;
        if(!empty($addUser)){
            $addRes = $this->addPrizeJoin($addUser,$pid,$year,$yid);
        }

        if(!empty($delUser)){
            $delRes = $this->delPrizeJoin($delUser,$pid,$year,$yid);
        }

        if($addRes && $delRes){
            return true;
        }
        return false;
    }

    //根据年份奖项id获得参选人并取得投票情况
    public function getJoinUserInfoByYearPidUserId($yid,$pid,$userId)
    {
        $where['y_id'] = $yid;
        $where['p_id'] = $pid;
        $list = $this->where($where)->select();

        if(count($list)){
            $votes = new Votes();
            foreach($list as &$v){
                $v['user'] = DingUser::get($v['userId'])->toArray();

                //是否被当前用户投票

                $where['toUserId'] = $v['user']['userId'];
                $where['userId'] = $userId;
                $v['voteCount'] = $votes->where($where)->count();
            }
        }
        return $list;
    }

    public function getJoinUserInfoByYearPid($yid,$pid)
    {
        $where['y_id'] = $yid;
        $where['p_id'] = $pid;
        $list = $this->where($where)->order('ballot desc')->select();

        if(count($list)){
            foreach($list as &$v){
                $v['user'] = DingUser::get($v['userId'])->toArray();
            }
        }
        return $list;
    }





}
