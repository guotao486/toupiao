<?php

namespace app\admin\controller\sign;

use app\common\controller\Backend;
use app\admin\model\DingUser;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class User extends Backend
{
    
    /**
     * Signuser模型对象
     * @var \app\admin\model\Signuser
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Signuser;

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
            foreach($list as &$v){
                $v['type_title'] = config('sign.type')[$v['type']];
                $v['a_title'] = \app\admin\model\Activity::where('id',$v['a_id'])->column('name');
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
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
                try {
                    $oldTypeId = $row->type;
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        if($oldTypeId != $params['type']){
                            $type = config('sign.typeSql');
                            $res = DingUser::where('userId',$row->ding_userid)
                                ->inc($type[$params['type']],1)
                                ->dec($type[$oldTypeId],1)
                                ->update();
                        }
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
        $this->view->assign('typeList',config('sign.type'));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function typeList()
    {
        return json(config('sign.type'));
    }

    /*
     * 导入表格到数据库
     */
    public function import()
    {
        $file = $this->request->file('file');

        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }

        $filePath = ROOT_PATH . 'public' . DS . 'uploads';
        $info = $file->move($filePath);

        if(!$info){
            $this->error('上传失败');
        }

        $filename = $filePath.DS .$info->getSaveName();
        if (!is_file($filename)) {
            $this->error(__('No results were found'));
        }

        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filename)) {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filename)) {
                $PHPReader = new \PHPExcel_Reader_CSV();
                if (!$PHPReader->canRead($filename)) {
                    $this->error(__('Unknown data format'));
                }
            }
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];//表字段 注释=>字段名
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }

        $PHPExcel = $PHPReader->load($filename); //加载文件
        $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
        $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
        $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
        $maxColumnNumber = \PHPExcel_Cell::columnIndexFromString($allColumn);
        for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
            for ($currentColumn = 0; $currentColumn < $maxColumnNumber; $currentColumn++) {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                $fields[] = $val;
            }
        }
        $insert = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $values = [];
            for ($currentColumn = 0; $currentColumn < $maxColumnNumber; $currentColumn++) {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                $values[] = is_null($val) ? '' : $val;
            }
            $row = [];
            $temp = array_combine($fields, $values);
            foreach ($temp as $k => $v) {
                if (isset($fieldArr[$k]) && $k !== '') {
                    $row[$fieldArr[$k]] = $v;
                }
            }
            if ($row) {
                $insert[] = $row;
            }
        }
        if (!$insert) {
            $this->error(__('No rows were updated'));
        }
        try {
//            $type = config('sign.typeSql');
//            foreach($insert as &$v){
//                if($v['a_id'] == '13'){
//                    $v['a_id'] = '26';
//                }
//                if($v['a_id'] == '14'){
//                    $v['a_id'] = '27';
//                }
//
//                DingUser::where('userId',$v['ding_userid'])
//                    ->inc('score',$v['score'])
//                    ->inc($type[$v['type']],1)
//                    ->update();
//            }
            $this->model->saveAll($insert);


        } catch (\think\exception\PDOException $exception) {
            $this->error($exception->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }
}
