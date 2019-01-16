<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/22
 * Time: 9:14
 */

namespace app\index\controller;

use app\admin\model\Activity;
use app\admin\model\Comment;
use app\admin\model\DingUser;
use app\admin\model\Signuser;
use app\common\controller\Frontend;
use think\Db;
use dd\Auth;
use Endroid\QrCode\QrCode;
use think\Response;
use think\Validate;

class Sign extends Frontend
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function index()
    {
        $id = $this->request->param('id');
        if(empty($id)){
            $this->error('缺少参数id');
        }
        try{
            $info = Activity::getField($id,'id,time,name');
        }catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }

        if(empty($info)){
            $this->error('参数id没有记录');
        }

        $this->assign('id',$id);
        $this->assign('info',$info);
        return $this->view->fetch();
    }

    public function signQrcode()
    {
        $id = $this->request->param('id');
        $qrCode=new QrCode();
        $url = $this->request->domain().url('/index/sign/detail',['id'=>$id]);//加http://这样扫码可以直接跳转url
        $qrCode->setText($url)
            ->setSize(300)//大小
            ->setLabelFontPath(ROOT_PATH . 'public/assets/fonts/fzltxh.ttf')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel('长按二维码即可扫描')
            ->setLabelFontSize(16);

        return new Response($qrCode->get(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    public function detail($id = null)
    {
        if($this->request->isMobile()){
            $ddjs = "http://g.alicdn.com/dingding/open-develop/1.6.9/dingtalk.js";
        }else{
            $ddjs = "https://g.alicdn.com/dingding/dingtalk-pc-api/2.7.0/index.js";
        }

        try{
            //详情
            $info = Activity::getField($id,'id,time,name,content,address,users,lecture,host');
            $lecture = DingUser::dingIdToName($info['lecture'],'in');
            $info['lecture_name'] = implode(',',$lecture);
            $info['host_name'] = DingUser::dingIdToName($info['host'],'=')[0];

            //已签到人员
            $list = Signuser::where('a_id',$id)->order('sign_time desc')->limit(3)->select();
        }catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->view->assign('ddjs',$ddjs);
        $this->view->assign('info',$info);
        $this->view->assign('list',$list);
        $this->view->assign('_config',Auth::getConfig());
        return $this->view->fetch();
    }

    public function info($id = null)
    {
        if($this->request->isMobile()){
            $ddjs = "http://g.alicdn.com/dingding/open-develop/1.6.9/dingtalk.js";
        }else{
            $ddjs = "https://g.alicdn.com/dingding/dingtalk-pc-api/2.7.0/index.js";
        }

        try{
            //详情
            $info = Activity::getField($id,'id,time,name,content,address,users,lecture,host');
            $lecture = DingUser::dingIdToName($info['lecture'],'in');
            $info['lecture_name'] = implode(',',$lecture);
            $info['host_name'] = DingUser::dingIdToName($info['host'],'=')[0];
            //已签到人员
            $list = Signuser::where('a_id',$id)->order('sign_time desc')->limit(3)->select();
        }catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->view->assign('ddjs',$ddjs);
        $this->view->assign('info',$info);
        $this->view->assign('list',$list);
        $this->view->assign('_config',Auth::getConfig());
        return $this->view->fetch();
    }
    /*
     * 签到
     * 检查参数
     * 检查用户
     * 检查活动是否存在
     * 检查是否已签到
     * 检查活动状态
     * 检查活动时间
     */
    public function signOk()
    {
//        $userId = '15244680548796652';
        $userId = $this->request->post('userId');
        $id = $this->request->post('id');
//        $id = 27;
        if(empty($userId)){
            $this->error('参数userId为空');
        }
        if(empty($id)){
            $this->error('参数Id为空');
        }

        //得到用户数据
        $ding_user = new DingUser();
        $userInfo = $ding_user->where('userId',$userId)->find();
        if(!$userInfo){
            //不存在则通过钉钉api获取用户数据并存数据
            $params = $ding_user->saveUser($userId);
            if(!empty($params->errcode)){
                $this->error($params->errmsg);
            }
            $ding_user->save($params);
        }

        //获得分享数据
        $activity = Activity::get($id);
        if(!$activity){
            $this->error('该分享不存在');
        }

        //检查是否已经签到
        $sign_user = Signuser::where(['a_id'=>$id,'ding_userid'=>$userId,'sign_start'=>1])->find();
        if($sign_user){
            $this->success('签到成功');
        }

        //检查分享状态和时间
        if($activity->status == 0){
            $this->error('签到已停止');
        }
        $time = time();
        if($activity->sign_start_time > $time){
            $this->error('签到时间未开始');
        }
        if($activity->sign_end_time < $time){
            $this->error('签到时间已结束');
        }

        $data['name'] = $userInfo->name;
        $data['department'] = $userInfo->department;
        $data['a_id'] = $id;
        $data['ding_userid'] = $userInfo->userId;
        $data['sign_start'] = 1;
        $data['sign_time'] = $time;
        //参加身份与积分,若同为主持主讲，以主讲优先
        if(strpos($activity->lecture,$userId) !== false){
            //主讲
            $data['type'] = 3;
            $data['score'] = $activity->lecture_score;
            $score = $data['score'];
            $scoreType = 'shareNum';
        }elseif(strpos($activity->host,$userId) !== false){
            //主持
            $data['type'] = 2;
            $data['score'] = $activity->host_score;
            $score = $data['score'];
            $scoreType = 'shareHostNum';
        }else{
            //参加
            $data['type'] = 1;
            $data['score'] = $activity->score;
            $score = $data['score'];
            $scoreType = 'shareToNum';
        }
        try{
            $res = Signuser::create($data);
            if($res->id){
                if(!$ding_user->where('userId',$userInfo->userId)
                    ->inc('score',$score)
                    ->inc($scoreType,1)
                    ->update()){
                    $this->error('签到成功，数据更新失败');
                }
            }else{
                $this->error('签到失败');
            }
        }catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('签到成功');
    }

    public function signUserList($id = null)
    {
        try{
            $list = Signuser::where('a_id',$id)->order('sign_time desc')->select();
        }catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->view->assign('list',$list);
        return $this->view->fetch('result');
    }

    /*
     * 积分页面
     * 个人信息，积分，参加的活动
     */
    public function jifen()
    {
        if($this->request->isMobile()){
            $ddjs = "http://g.alicdn.com/dingding/open-develop/1.6.9/dingtalk.js";
        }else{
            $ddjs = "https://g.alicdn.com/dingding/dingtalk-pc-api/2.7.0/index.js";
        }
        $this->view->assign('ddjs',$ddjs);
        $this->view->assign('_config',Auth::getConfig());
        if($this->request->isAjax()){
            $userId = $this->request->get('userId');
//            $userId ='0749271726881265';

            $user = DingUser::get($userId);
            $data['user']=$user->toArray();

            if(empty($data['user'])){
                $this->error('用户数据为空');
            }

            //1 参加 2主持 3主讲
            $data['list'] = Activity::where('status',1)->order('listorder desc,create_time desc')->select();
//            $data['list'] = collection($data['list'])->toArray();
            $time = time();
            foreach($data['list'] as &$v){
                if(empty($v['sign_start_time'])){
                    $v['start_text'] = '';
                }elseif($v['sign_start_time'] > $time){
                    $v['start_text'] = '未开始';
                }
            }
            $data['count'] = count($data['list']);
            $data['list1'] = $this->scoreActivityList($userId,1);
            $data['list2'] = $this->scoreActivityList($userId,2);
            $data['list3'] = $this->scoreActivityList($userId,3);
            $this->success('返回成功',null,$data);
        }
        return $this->view->fetch();
    }

    public function scoreActivityList($userId,$type =0)
    {
        //1 参加 2主持 3主讲
        if(!empty($type)){
            $where['type'] = $type;
        }
        $where['ding_userid'] = $userId;
        $ids = Signuser::where($where)->column('a_id');
        $list = Activity::where('id','in',$ids)->column('id,name,time');
        return $list;
    }

    public function comment($id = null)
    {
        if(empty($id)){
            $this->error('id参数为空');
        }
        $userId = $this->request->param('userId');
//        $userId = '19071262561178731';

        //主讲人信息
        $activity = Activity::get($id);
        if(empty($activity)){
            $this->error('该活动不存在');
        }
        $lecture = DingUser::where('userId','in',$activity->lecture)->column('userId,name,department');

        //主讲人评论信息
        foreach($lecture as &$v) {
            $where['a_id'] = $id;
            $where['toUserId'] = $v['userId'];
            $v['comment'] = Comment::where($where)->select();

            //当前用户是否评论
            $v['isComment'] = false;
            if($v['comment']){
                foreach($v['comment'] as $key=>$c){
                    if(!$v['isComment']){
                        $v['isComment'] = $c->userId == $userId?true:false;
                    }
                    $v['comment'][$key]['userName'] = DingUser::dingIdToName($c['userId'],'=')[0];
                }
            }
        }

        if ($this->request->isMobile()) {
            $ddjs = "http://g.alicdn.com/dingding/open-develop/1.6.9/dingtalk.js";
        } else {
            $ddjs = "https://g.alicdn.com/dingding/dingtalk-pc-api/2.7.0/index.js";
        }

        $this->view->assign('ddjs', $ddjs);
        $this->view->assign('_config', Auth::getConfig());
        $this->view->assign('lecture',$lecture);
        $this->view->assign('title',$activity->name);
        return $this->view->fetch();
    }

    //ajax提交评价
    public function commentAjax()
    {
        if($this->request->isAjax())
        {
            $data['a_id'] = $this->request->post('id/d','');
            $data['content'] = $this->request->post('content','','htmlspecialchars,trim');
            $data['score'] = $this->request->post('score/d',0);
            $data['toUserId'] = $this->request->post('toUserId','');
            $data['userId'] = $this->request->post('userId','');

            $validate = new Validate(
                [
                'a_id'  => 'require',
                'content' => 'require',
                'toUserId' => 'require',
                'userId' => 'require'
                ],
                [
                'a_id.require' => '缺少参数id',
                'content.require' => '尚未评价！',
                'toUserId.require'        => '缺少被评价用户id',
                'userId.require'        => '缺少用户id',
                ]
            );

            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            //
            $activity = Activity::get($data['a_id']);
            if(time()<$activity->sign_end_time){
                $this->error('分享尚未结束');
            }
            try{
                $result = Comment::create($data);
                $userName = DingUser::dingIdToName($data['userId'],'=')[0];
            } catch (\think\exception\PDOException $e) {
                $this->error($e->getMessage());
            } catch (\think\Exception $e) {
                $this->error($e->getMessage());
            }

            if ($result->id) {
                $resData['userId'] = $result->userId;
                $resData['userName'] = $userName;
                $resData['content'] = $result->content;
                $resData['create_time_text2'] = $result->create_time_text2;
                $resData['score'] = $result->score;
                $this->success('评价成功','',$resData);
            } else {
                $this->error(Comment::getError());
            }

        }else{
            $this->error('不是AJAX请求');
        }
    }

    //附件列表
    public function fileList($id = null)
    {
         try{
             $info = Activity::where('id',$id)->find();
             $fileList = null;

             if(!empty(trim($info['upload_ids']))){
                 $file = new \app\admin\model\File();
                 $fileList = $file->fileList($info['upload_ids']);
             }
         } catch (\think\exception\PDOException $e) {
             $this->error($e->getMessage());
         } catch (\think\Exception $e) {
             $this->error($e->getMessage());
         }

         $this->view->assign('list',$fileList);
         return $this->view->fetch();
    }


    /*
     * redis demo
     * 下单
     */
    public function setRedis(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);

//        $redis->del('list');
        for($i=1;$i<=120;$i++){
            $id = mt_rand(0000,9999).'_'.microtime(true);
            if($redis->llen('list') < 100){
                $redis->lpush('list',$id);
            }else{
                echo '已结束';
            }
            sleep(1);
        }
        var_dump($redis->lRange('list',0,-1));
    }

    //入库
    public function getRedisSetSql(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);

        while($redis->llen('list') != 0){
            if($redis->llen('list') != 0){
                $data['order_id'] = $redis->rPop('list');
                $data['status'] = 0;
                $res = Db::table('tp_redis_demo')->insert($data);
                if(!$res){
                   $redis->lPush('list',$data['order_id']);
                }
            }
            sleep(1);
        }
    }

    //处理
    public function saveSql(){
        while(1 ==1){
            $info = Db::table('tp_redis_demo')->where('status',0)->find();
            //上锁，防止其他程序访问该条数据
            Db::table('tp_redis_demo')->where('id',$info['id'])->setField('status',2);

            //相关逻辑

            //结束
            Db::table('tp_redis_demo')->where('id',$info['id'])->setField('status',1);
            sleep(1);
        }
    }
    //
    public function demo(){
        echo '111';
    }
}