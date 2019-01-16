<?php

namespace app\index\controller;

use app\admin\model\DingUser;
use app\common\controller\Frontend;
use think\Db;
use think\Request;
use \dd\Auth;

class Survey extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    protected $typeList = null;
    protected $checkMin = 2;

    public function _initialize()
    {
        parent::_initialize();
        $this->typeList = config('survey.typeList');
    }

    public function index(){

        $list = db::name('survey')->where(['status'=>1])->order('listorder desc,id desc')->select();

        foreach($list as &$v){
            //0 关闭 1未开始 2进行中 3已结束
            switch ($v['status']){
                case 0:
                    break;
                case 1:
                    //未开始
                    if($v['start_time']>time()){
                        $v['status'] = 1;
                    }
                    //未结束
                    if($v['start_time']<=time() && $v['end_time'] >time()){
                        $v['status'] = 2;
                    }
                    //已结束
                    if($v['end_time'] <=time()){
                        $v['status'] = 3;
                    }
                    break;
            }
            $v['result_num'] = db::name('survey_result')->where(['survey_id'=>$v['id']])->group('user_ding')->count();
        }
        unset($v);

        //评优
        $list2 = db::name('prize_year')->where('status',1)->order('year desc,listorder desc')->select();
        foreach($list2 as &$v){

            if($v['start_time']>time()){
                $v['status'] = 1;
            }
            //未结束
            if($v['start_time']<=time() && $v['end_time'] >time()){
                $v['status'] = 2;
            }
            //已结束
            if($v['end_time'] <=time()){
                $v['status'] = 3;
            }
            $v['result_num'] = db::name('prize_votes')->where('y_id',$v['id'])->group('userId')->count();
        }
        if($this->request->isMobile()){
            $ddjs = "http://g.alicdn.com/dingding/open-develop/1.6.9/dingtalk.js";
        }else{
            $ddjs = "https://g.alicdn.com/dingding/dingtalk-pc-api/2.7.0/index.js";
        }
        $this->assign('ddjs',$ddjs);
        $this->assign('_config',Auth::getConfig());//钉钉配置
        $this->assign('list',$list);
        $this->assign('list2',$list2);
        return $this->view->fetch('indexlist');
    }

    //根据问卷类型判断当前用户是否有权限参加
    public function checkSurvey()
    {
        $id = $this->request->param('id');
        $userId = $this->request->param('userId');
//        $userId = '15289612166944815';
        if(empty($id)){
            $this->error('缺少参数id');
        }
        if(empty($userId)){
            $this->error('缺少参数userId');
        }
        $info = db::name('survey')->where('id',$id)->field('id,title,remark,start_time,end_time,status,type')->find();

        if($info['status'] == 1 && $info['start_time'] >time()){
            $this->error('该问卷尚未开始！');
        }
        if($info['status'] == 1 && $info['end_time'] <= time()){
            $this->error('该问卷已经结束！');
        }
        if($info['status'] == 0){
            $this->error('该问卷已关闭！');
        }

        $userInfo = DingUser::get($userId);
        if($userInfo->userId){
            switch ($info['type'])
            {
                case '0':
                    $this->success();
                    break;
                case '1':
                    if($userInfo->manage < 3&&$userInfo->manage>0){
                        $this->success();
                    }else{
                        $this->error('无权限访问');
                    }
                    break;
                case '2':
                    break;
                case '3':
                    break;
            }
        }else{
            $this->error('非法用户');
        }

    }
    public function survey(Request $request)
    {
        if($request->isMobile()){
            $ddjs = "http://g.alicdn.com/dingding/open-develop/1.6.9/dingtalk.js";
        }else{
            $ddjs = "https://g.alicdn.com/dingding/dingtalk-pc-api/2.7.0/index.js";
        }

        $id = $request->param('id');

        $this->assign('_config',Auth::getConfig());//钉钉配置

        $info = db::name('survey')->where('id',$id)->field('id,title,remark,start_time,end_time,status,type,isModel,image')->find();
        //题目数据
        $info['question'] = db::name('question')->where('survey_id',$id)->field('id,title,type,options_ids,required,image,describe')->order('listorder asc,id')->select();

        foreach($info['question'] as &$v){
            $order = &$old_order;
            $order = $v['options_ids'];
            $v['options'] = db::name('options')->where('id','in',$v['options_ids'])->where('id','neq','1')->field('id,title')->orderRaw("field(id,{$v['options_ids']})")->select();
            $v['type_text'] = $this->typeList[$v['type']];
        }
        $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
        $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);

        //已答题人数
        $result_num = db::name('survey_result')->where(['survey_id'=>$id])->group('user_ding')->count();
        $this->assign('result_num',$result_num);
        $this->assign('info',$info);
        $this->assign('ddjs',$ddjs);
        switch ($info['isModel']){
            case '0':
                return $this->view->fetch('index');
                break;
            case '1':
                return $this->view->fetch('indeximage');
                break;
        }

    }

    public function add(Request $request)
    {
        $id = $request->post('id');//问卷id
        $row = $request->post('row/a');//问卷结果
        $user_name = $request->post('name');
        $user_ding = $request->post('ding');

        //钉钉用户信息验证
        if(empty($user_name)||empty($user_ding)){
            if($request->isMobile()){
                return json(['code'=>400,'msg'=>'请从钉钉打开或刷新后重试！']);
            }else{
                return json(['code'=>400,'msg'=>'PC用户请从钉钉工作台打开微应用']);
            }
        }

        //禁止重复答题
        $isResult = db::name('survey_result')->where(['survey_id'=>$id,'user_ding'=>$user_ding])->find();
        if($isResult){
            return json(['code'=>400,'msg'=>'您已经参加过该问卷！']);
        }

        $questions = db::name('question')->where('survey_id',$id)->field('id,required,type')->order('id')->select();//问卷所有问题id和类型
//        if(count($row) != count($questions)){//结果条数是否与问题条数一致，不一致表示漏题了
//            $return['code'] = 400;
//            $return['msg'] ='您还未参加问卷！';
//            return json($return);
//        }
        $data = [];
        foreach($questions as $k=>$v){//封装结果数据
            $tmp = array(
                'user_name'   => $user_name,
                'user_avatar' => 'user_avatar',
                'user_ding'   => $user_ding,

                'question_type'=>$v['type'],
                'survey_id'   => $id,
                'question_id' => $v['id'],
                'create_time' => time(),
                'update_time' => time()
            );
            //对每个类型的问题分开验证结果
            $k++;
            switch($v['type']){
                case '1':
                    if($v['required'] == 1){//必填
                        if(empty($row[$v['id']]['option'])){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为必填题,请选择选项！';
                            return json($return);
                        }
                    }else{
                        $row[$v['id']]['option'][0] = empty($row[$v['id']]['option']) ? '':$row[$v['id']]['option'][0];
                    }
                    $tmp['option_id'] = $row[$v['id']]['option'][0];
                    $tmp['option_text'] = '';
                    break;
                case '2':
                    if($v['required'] == 1){//必填
                        if(empty($row[$v['id']]['option'])){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为必填题,请选择选项！';
                            return json($return);
                        }
                        if(count($row[$v['id']]['option']) < $this->checkMin){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为多选题，选项不能少于'. $this->checkMid .'个！';
                            return json($return);
                        }
                        $tmp['option_id'] = implode(",", $row[$v['id']]['option']);
                    }else{
                        $tmp['option_id'] = empty($row[$v['id']]['option']) ? '':implode(",", $row[$v['id']]['option']);
                    }
                    $tmp['option_text'] = '';
                    break;
                case '3':
                    if($v['required'] == 1){//必填
                        if(empty(trim($row[$v['id']]['title']))){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为必填题,请输入文本！';
                            return json($return);
                        }
                    }else{
                        $row[$v['id']]['title'] = empty($row[$v['id']]['title']) ? '':$row[$v['id']]['title'];
                    }
                    $tmp['option_id'] = '';
                    $tmp['option_text'] = $row[$v['id']]['title'];
                    break;
                case '4':
                    if($v['required'] == 1){//必填
                        if(empty($row[$v['id']]['option'])){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为必填题,请选择选项！';
                            return json($return);
                        }
                        if(empty(trim($row[$v['id']]['title']))){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为必填题,请输入文本！';
                            return json($return);
                        }
                    }else{
                        $row[$v['id']]['option'][0] = empty($row[$v['id']]['option']) ? '':$row[$v['id']]['option'][0];
                        $row[$v['id']]['title'] = empty($row[$v['id']]['title']) ? '':$row[$v['id']]['title'];
                    }
                    $tmp['option_id'] = $row[$v['id']]['option'][0];
                    $tmp['option_text'] = $row[$v['id']]['title'];
                    break;
                case '5':
                    if($v['required'] == 1){//必填
                        if(empty($row[$v['id']]['option'])){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为必填题,请选择选项！';
                            return json($return);
                        }
                        if(count($row[$v['id']]['option']) < $this->checkMin){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为多选题，选项不能少于'. $this->checkMid .'个！';
                            return json($return);
                        }
                        if(empty(trim($row[$v['id']]['title']))){
                            $return['code'] = 400;
                            $return['msg'] ='第'. $k .'题为必填题,请输入文本！';
                            return json($return);
                        }
                        $tmp['option_id'] = implode(",", $row[$v['id']]['option']);
                    }else{
                        $tmp['option_id'] = empty($row[$v['id']]['option']) ? '':implode(",", $row[$v['id']]['option']);
                        $row[$v['id']]['title'] = empty($row[$v['id']]['title']) ? '':$row[$v['id']]['title'];
                    }
                    $tmp['option_text'] = $row[$v['id']]['title'];
                    break;
            }

            $data[] = $tmp;
        }

        $res = Db::name('survey_result')->insertAll($data);
        if($res != count($questions)){
            $return['code'] = 400;
            $return['msg'] ='提交错误，请重新提交或联系管理员';
        }else{
            $return['code'] = 200;
            $return['msg'] ='提交成功';
        }
        return json($return);
    }

    //
}
