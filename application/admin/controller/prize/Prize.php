<?php

namespace app\admin\controller\prize;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Prize extends Backend
{
    
    /**
     * Prize模型对象
     * @var \app\admin\model\Prize
     */
    protected $model = null;
    protected $join = null;
    protected $year = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\prize\Prize;
        $this->join = new \app\admin\model\prize\Join;
        $this->year = new \app\admin\model\prize\Year;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


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
                $yearInfo = $this->year->get($params['y_id']);
                $params['year'] = $yearInfo->year;
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }

                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false) {
                        $joinRes = $this->join->setPrizeJoin($params['user_ids'],$this->model->id,$params['year'],$params['y_id']);
                        if($joinRes){
                            $this->success();
                        }else{
                            $this->error('参选人添加失败');
                        }
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
            if ($params) {
                $yearInfo = $this->year->get($params['y_id']);
                $params['year'] = $yearInfo->year;
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->join->savePrizeJoin($params['user_ids'],$row->id,$params['year'],$params['y_id']);
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
        }
        //转数组才能得到追加字段
        $row = $row->toArray();
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
