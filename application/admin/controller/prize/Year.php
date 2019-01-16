<?php

namespace app\admin\controller\prize;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Year extends Backend
{
    
    /**
     * Year模型对象
     * @var \app\admin\model\prize\Year
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\prize\Year;

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

                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
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
        return $this->view->fetch();
    }

    public function detail($ids = null)
    {
        $yearInfo = $this->model->get($ids);
        if (!$yearInfo)
            $this->error(__('No Results were found'));

        //
        $prizeModel = new \app\admin\model\prize\Prize();
        $prizeList = $prizeModel->getPrizeListByYear($ids,$yearInfo['lv']);
        if(!count($prizeList)){
            $this->error('该年度无评选内容');
        }

        //参选人
        $votesModel = new \app\admin\model\prize\Votes();
        $joinModel = new \app\admin\model\prize\Join();
        $list = collection($prizeList)->toArray();//结果集转数组
        foreach($list as &$v){
            $v['userList'] = $joinModel->getJoinUserInfoByYearPid($ids,$v['id']);
            $v['voteCount']= $votesModel
                ->where(
                    [
                        'y_id'=>$ids,
                        'p_id'=>$v['id']
                    ]
                )->group('userId')
                ->count();
        }
        $num = $votesModel->where('y_id',$ids)->group('userId')->count();
        $this->view->assign('info',$yearInfo);
        $this->view->assign('prize',$list);
        $this->view->assign('num',$num);
        $this->view->assign('id',$ids);
        return $this->view->fetch();
    }

    public function excel()
    {

        $id = $this->request->get('id');

        $yearInfo = $this->model->get($id);
        if (!$yearInfo)
            $this->error(__('No Results were found'));

        $filename = $yearInfo->title.".xls";

        $contents = "<table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"0\" >
           <caption height='55'> <b>".$filename."</b> </caption>
          ";

        $prizeModel = new \app\admin\model\prize\Prize();
        $prizeList = $prizeModel->getPrizeListByYear($id,$yearInfo['lv']);
        if(!count($prizeList)){
            $this->error('该年度无评选内容');
        }

        //参选人
        $votesModel = new \app\admin\model\prize\Votes();
        $joinModel = new \app\admin\model\prize\Join();
        $list = collection($prizeList)->toArray();//结果集转数组
        $num = $votesModel->where('y_id',$id)->group('userId')->count();

        //选项结果统计
        foreach($list as $k=>$v){
            $v['userList'] = $joinModel->getJoinUserInfoByYearPid($id,$v['id']);
            $v['voteCount']= $votesModel
                ->where(
                    [
                        'y_id'=>$id,
                        'p_id'=>$v['id']
                    ]
                )->group('userId')
                ->count();

            /*
             * 表格内容
             */
            //奖项
            $str = $v['ballot_num']=='1'?'单选':'最多选'.$v['ballot_num'].'项';
            $contents .= "<tr align=\"left\">";
            $contents .="<td colspan='2'>";
            $contents .=($k+1).'.'.$v['title'].'('.$str.')';
            $contents .="</td>";
            $contents .= "</tr>";

            //参选人
            foreach($v['userList'] as $u){
                $contents .= "<tr align=\"left\">";
                $contents .="<td >";
                $contents .=$u['name'];
                $contents .="</td>";
                $contents .="<td >";
                $contents .=$u['ballot'];
                $contents .="</td>";
                $contents .= "</tr>";
            }
            $contents .= "<tr align=\"left\">";
            $contents .= "</tr>";
            $contents .="<td >";
            $contents .='参与人数';
            $contents .="</td>";
            $contents .="<td >";
            $contents .=$v['voteCount'];
            $contents .="</td>";
            $contents .= "<tr></tr>";
        }

        $contents .="<tr align=\"left\">";
        $contents .="<td>总参与人数</td>";
        $contents .="<td>{$num}</td>";
        $contents .="</tr>";
        $contents .= "</table>";
        header ( 'Content-type: application/vnd.ms-execl' );
        header ( 'Content-Disposition: attachment; filename=' . $filename );
        echo $contents;
        die ();
    }
}
