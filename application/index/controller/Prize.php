<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/22
 * Time: 9:14
 */

namespace app\index\controller;

use app\admin\model\DingUser;
use app\admin\model\Prize\Prize as PrizeModel;
use app\admin\model\Prize\Year;
use app\admin\model\Prize\Join;
use app\admin\model\Prize\Votes;
use app\common\controller\Frontend;
use think\Db;
use dd\Auth;
use think\Response;
use think\Validate;

class Prize extends Frontend
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function index()
    {
        $yid = $this->request->param('id');
        $year = $this->request->param('year');
        $userId = $this->request->param('userId');
        //年度详情
        $yearModel = new Year();
        $yearInfo = $yearModel->where('id',$yid)->find();
        if(!$yearInfo){
            $this->error('参数year错误');
        }

        //奖项列表
        $prizeModel = new PrizeModel();
        $prizeList = $prizeModel->getPrizeListByYear($yid,$yearInfo['lv']);
        if(!count($prizeList)){
            $this->error('该年度无评选内容');
        }

        //参选人
        $votes = new Votes();
        $joinModel = new Join();
        $list = collection($prizeList)->toArray();//结果集转数组
        foreach($list as &$v){
            $v['userList'] = $joinModel->getJoinUserInfoByYearPidUserId($yid,$v['id'],$userId);

            $voteCount = 0;
            foreach($v['userList'] as &$item){
                $voteCount +=$item['voteCount'];

                /*
                 * 判断单个参选人是否还能投票
                 * 重复投票 yes no
                 * no：不能重复，并已投票，则不能再次投票
                 * yes：能重复，并已投票，还能再次投票
                 */
                if($v['is_repeat'] == 0 && $item['voteCount'] !=0){
                    $item['isAction'] = false;
                }else{
                    $item['isAction'] = true;
                }
            }

            //若票数用尽，则不能投票
            if($v['ballot_num']<=$voteCount){
                $v['isAction'] = false;//允许投票数小于用户已投票次数
            }else{
                $v['isAction'] = true;//允许投票数大于用户已投票次数，可以继续投票
            }

        }

        if($this->request->isMobile()){
            $ddjs = "http://g.alicdn.com/dingding/open-develop/1.6.9/dingtalk.js";
        }else{
            $ddjs = "https://g.alicdn.com/dingding/dingtalk-pc-api/2.7.0/index.js";
        }

        //参与人数
        $count = 0;
        $count = Votes::where('y_id',$yid)->group('userId')->count();
        $this->view->assign('count',$count);
        $this->view->assign('userId',$userId);
        $this->view->assign('ddjs',$ddjs);
        $this->view->assign('_config',Auth::getConfig());
        $this->view->assign('list',$list);
        $this->view->assign('info',$yearInfo);
        return $this->view->fetch('prize');
    }

    public function ajaxVote()
    {

        if(!$this->request->isAjax()){
            $this->error('非法请求');
        }

        $id = $this->request->post('id');//选项id
        $toUserId = $this->request->post('toUserId');//参选人id
        $userId = $this->request->post('userId');//投票人id
        $year = $this->request->post('year');
        $yid = $this->request->post('yid');

        if(empty($id)){
            $this->error('缺少参数id');
        }
        if(empty($yid)){
            $this->error('缺少参数yid');
        }
        if(empty($toUserId)){
            $this->error('缺少参数toUserId');
        }
        if(empty($userId)){
            $this->error('缺少参数userId');
        }
        if(empty($year)){
            $this->error('缺少参数year');
        }


        $prize = new PrizeModel();
        $votes = new Votes();
        $join = new Join();
        $dingUser = new DingUser();
        //奖项详情
        $prizeInfo = $prize->where('id',$id)->find();
        if(!$prizeInfo){
            $this->error('奖项为空');
        }
        //是否已投票
        $where['p_id'] = $id;
        $where['userId'] = $userId;
        $where['year'] = $year;
        $where['y_id'] = $yid;
        $votesCount = $votes->where($where)->count();

        $return['isBallot'] = 0;
        //判断投票次数是否用尽
        if($votesCount>=$prizeInfo['ballot_num']){
            $this->error('投票次数已用尽');
        }

        //是否允许重复投票给一个人 0 不允许 1 允许
        $where['toUserId'] = $toUserId;
        $toUserJoin = $votes->where($where)->count();
        if(empty($prizeInfo['is_repeat']) && !empty($toUserJoin)){
            $this->error('不允许重复投票');
        }

        //数据封装
        $data['userId'] = $userId;
        $data['name'] = $dingUser->getNameByUserid($userId);
        $data['toUserId'] = $toUserId;
        $data['toName'] = $dingUser->getNameByUserid($toUserId);
        $data['year'] = $year;
        $data['y_id'] = $yid;
        $data['p_id'] = $id;

        try{
            $res = $votes->allowField(true)->save($data);
            if($res){
                unset($where['toUserId']);
                $where['userId'] = $toUserId;
                $join->where($where)->setInc('ballot');
            }
        }catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }

        /**
         * 返回数据
         */
        //是否有票数
        if(($votesCount+1)>=$prizeInfo['ballot_num']){
            $return['isBallot'] = 0;
        }else{
            $return['isBallot'] = 1;
        }
        //已参与人
        $return['voteCount'] =$votes->where('year',$year)->group('userId')->count();

        $this->success('投票成功！','',$return);
    }


    public function checkVotes()
    {
        $userId = $this->request->get('userId');
        $year = $this->request->get('year');
    }
}

