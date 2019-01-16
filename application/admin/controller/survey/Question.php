<?php

namespace app\admin\controller\survey;

use app\common\controller\Backend;
use app\admin\model\Survey;
use app\admin\model\Result;

/**
 * 
 *
 * @icon fa fa-question
 */
class Question extends Backend
{
    
    /**
     * Question模型对象
     * @var \app\admin\model\Question
     */
    protected $model = null;
    protected $multiFields="required";
    protected $typeList = array(
        '1'=>'单选',
        '2'=>'多选',
        '3'=>'文本',
        '4'=>'单选+文本',
        '5'=>'多选+文本'
    );

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Question;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach($list as &$vo){
                $vo['type_text'] = $this->typeList[$vo['type']];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $options = explode(',',$params['options_ids']);
                switch($params['type']){
                    case '1':
                        if(in_array('1',$options)){
                            $this->error('非文本类型禁止选择文本内容');
                        }
                        if(count($options)<2){
                            $this->error('单选类型至少选择2个选项');
                        }
                        break;
                    case '2':
                        if(in_array('1',$options)){
                            $this->error('非文本类型禁止选择文本内容');
                        }
                        if(count($options)<3){
                            $this->error('多选类型至少选择3个选项');
                        }
                        break;
                    case '3';
                        if($params['options_ids']!=='1'){
                            $this->error('文本类型只能选择文本内容');
                        }
                        break;
                    case '4':
                        if(!in_array('1',$options)){
                            $this->error('包含文本类型需要选择文本内容');
                        }
                        if(count($options)<3){
                            $this->error('单选类型至少选择2个选项');
                        }
                        break;
                    case '5':
                        if(!in_array('1',$options)){
                            $this->error('包含文本类型需要选择文本内容');
                        }
                        if(count($options)<4){
                            $this->error('多选类型至少选择2个选项');
                        }
                        break;
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $params['survey_title'] = Survey::where('id',$params['survey_id'])->value('title');
                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }else{
            $surveyList = Survey::order('create_time desc,id desc')->select();
            $this->assign('typeList',$this->typeList);
            $this->assign('surveyList',$surveyList);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $options = explode(',',$params['options_ids']);
            switch($params['type']){//选项验证
                case '1':
                    if(in_array('1',$options)){
                        $this->error('非文本类型禁止选择文本内容');
                    }
                    if(count($options)<2){
                        $this->error('单选类型至少选择2个选项');
                    }
                    break;
                case '2':
                    if(in_array('1',$options)){
                        $this->error('非文本类型禁止选择文本内容');
                    }
                    if(count($options)<3){
                        $this->error('多选类型至少选择3个选项');
                    }
                    break;
                case '3';
                    if($params['options_ids']!=='1'){
                        $this->error('文本类型只能选择文本内容');
                    }
                    break;
                case '4':
                    if(!in_array('1',$options)){
                        $this->error('包含文本类型需要选择文本内容');
                    }
                    if(count($options)<3){
                        $this->error('单选类型至少选择2个选项');
                    }
                    break;
                case '5':
                    if(!in_array('1',$options)){
                        $this->error('包含文本类型需要选择文本内容');
                    }
                    if(count($options)<4){
                        $this->error('多选类型至少选择2个选项');
                    }
                    break;
            }
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $params['survey_title'] = Survey::where('id',$params['survey_id'])->value('title');
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }else{

            $surveyList = Survey::order('create_time desc,id desc')->select();
            $this->assign('typeList',$this->typeList);
            $this->assign('surveyList',$surveyList);
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 详情
     */

    public function detail($ids = null)
    {
        $info = $this->model->get(['id' => $ids]);

        //选项结果统计
        $census = $this->model->optionCensus($info['survey_id'],$info['id'],$info['options_ids']);

        $this->assign('census',$census);
        return $this->view->fetch();
    }
}
