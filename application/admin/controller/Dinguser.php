<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use Endroid\QrCode\QrCode;
use think\Response;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Dinguser extends Backend
{
    
    /**
     * DingUser模型对象
     * @var \app\admin\model\DingUser
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\DingUser;
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
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
                ->where('status',1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
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
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    if(empty($params['userId'])){
                        $this->error('钉钉userId为空');
                    }
                    $params = $this->model->saveUser($params['userId']);
                    if(!empty($params->errcode)){
                        $this->error($params->errmsg);
                    }
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
        }
        dump(\dd\Auth::getConfig());
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
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    if(!empty($params['avatar'])&&empty($row->avatar)){
                        $params['avatar']='http://www.bkjg.com/toupiao/public'.$params['avatar'];
                    }else{
                        $params['avatar']=$row->avatar;
                    }
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
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    public function dingQrCode()
    {
        $qrCode=new QrCode();
        $url = 'http://www.xxiangfang.com/ddoa/sign/jifen.php';//加http://这样扫码可以直接跳转url
        $qrCode->setText($url)
            ->setSize(300)//大小
            ->setLabelFontPath(ROOT_PATH . 'public/assets/fonts/fzltxh.ttf')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel('钉钉扫描二维码授权')
            ->setLabelFontSize(16);

        return new Response($qrCode->get(), 200, ['Content-Type' => $qrCode->getContentType()]);
//        header('Content-Type: '.$qrCode->getContentType());
//        $qrCode->render();
//        exit;
    }

    /*
     * 更新钉钉数据
     */
    public function update($ids = NULL)
    {
        $count = count($ids);
        if(empty($ids)){
            $this->error('钉钉userId为空');
        }
        try {
            $saveNum = 0;
            foreach($ids as $id){
                $params = $this->model->saveUser($id);

                if(!empty($params->errcode)){
                    $this->model->where('userId',$id)->setField('status',0);

                    exception('ID:{$id},'.$params->errmsg.'，已移除该用户');
                }

                $result = $this->model->allowField(true)->where('userId',$params['userId'])->update($params);
                if ($result !== false) {
                    $saveNum++;
                } else {
                    exception($this->model->getError());
                }
            }

        } catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
        if($count == $saveNum){
            $this->success('更新成功！');
        }
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

        $filePath = ROOT_PATH . 'public' . DS . 'uploads'.DS . 'dinguser';
        $info = $file->rule('md5')->move($filePath);

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
        $fieldArr = [];
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
            $this->model->saveAll($insert);
        } catch (\think\exception\PDOException $exception) {
            $this->error($exception->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }
}
