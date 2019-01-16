<?php

namespace app\admin\controller\survey;

use app\common\controller\Backend;
use think\Request;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Survey extends Backend
{
    
    /**
     * Survey模型对象
     * @var \app\admin\model\Survey
     */
    protected $model = null;
    protected $typeList = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Survey;
        $this->typeList = config('survey.typeList');
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function detail($ids = null)
    {
        $question = new \app\admin\model\Question;
        $questionList = $question->where('survey_id',$ids)->select();
        if(empty($questionList)){
            $this->error('该问卷没有设置问题');
        }
        $questionList = collection($questionList)->toArray();//转数组

        $census = array();
        $sum = 0;//总人数

        //选项结果统计
        foreach($questionList as $v){
            $questionCensus = $question->optionCensus($v['survey_id'],$v['id'],$v['options_ids']);
            $questionCensus['title'] = $v['title'];
            $questionCensus['required'] = $v['required'];
            $census[] = $questionCensus;
            $sum = $questionCensus['sum'];
        }

        $this->assign('id',$ids);
        $this->assign('sum',$sum);
        $this->assign('census',$census);
        return $this->view->fetch();
    }

    public function excel(Request $request)
    {

        $id = $request->get('id');

        $surveyInfo = $this->model->where('id',$id)->find();
        $filename = $surveyInfo->title.".xls";

        $contents = "<table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"0\" >
           <caption height='55'> <b>".$filename."</b> </caption>
          ";

        $question = new \app\admin\model\Question;
        $questionList = $question->where('survey_id',$id)->select();
        $questionList = collection($questionList)->toArray();//转数组

        $sum = 0;//总人数

        //选项结果统计
        foreach($questionList as $k=>$v){
            $questionCensus = $question->optionCensus($v['survey_id'],$v['id'],$v['options_ids']);
            $questionCensus['required'] = $v['required'];

            //表格内容
            $contents .= "<tr align=\"left\">";
            $contents .="<td colspan='2'>";
            $contents .= $k+1 .'.' . $v['title'] ."(". $this->typeList[$v['type']].'&nbsp;';
            $contents .= ($v['required']==1)? '必填': '非必填';
            $contents .=")";

            $contents .="</td>";
            $contents .= "</tr>";

            if(!empty($questionCensus['option'])){
                unset($o);
                foreach($questionCensus['option'] as $o){
                    $contents .= "<tr align=\"left\">";
                    $contents .="<td>{$o['title']}</td>";
                    $contents .="<td>{$o['num']}</td>";
                    $contents .= "</tr>";
                }
            }

            if(!empty($questionCensus['text'])){
                unset($k3);
                unset($o);
                $contents .= "<tr align=\"left\">";
                $contents .="<td colspan=\"2\">文本内容：</td>";
                $contents .= "</tr>";
                foreach($questionCensus['text'] as $k3 => $o){
                    $contents .= "<tr align=\"left\">";
                    $contents .="<td colspan=\"2\">";
                    $contents .=$k3+1;
                    $contents .= "." .$o."</td>";
                    $contents .= "</tr>";
                }
            }

            $contents .= "<tr></tr>";
            $sum = $questionCensus['sum'];
        }

        $contents .="<tr align=\"left\">";
        $contents .="<td>总参与人数</td>";
        $contents .="<td>{$sum}</td>";
        $contents .="</tr>";
        $contents .= "</table>";
        header ( 'Content-type: application/vnd.ms-execl' );
        header ( 'Content-Disposition: attachment; filename=' . $filename );
        echo $contents;
        die ();
    }
}
