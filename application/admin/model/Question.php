<?php

namespace app\admin\model;

use think\Model;

class Question extends Model
{
    // 表名
    protected $name = 'question';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
    ];

    public function optionCensus($s_id,$q_id,$options)
    {
        //获得选项数据
        $option = $this->table('tp_options')
            ->where('id','in',$options)
            ->field('id,title,num')
            ->orderRaw("field(id,{$options})")
            ->select();
        $option = collection($option)->toArray();//转成数组
        $option = array_column($option, null,'id');//以id作为key

        unset($option[1]);//去除文本选项
        //该问题结果
        $where = array(
            'survey_id'=>$s_id,
            'question_id'=>$q_id
        );
        $result = $this->table('tp_survey_result')
            ->where($where)
            ->field('option_text,option_id,question_type')
            ->select();
        $result = collection($result)->toArray();//转成数组

        $question = array();

        //根据类型统计数据，单选多选选中项++，文本不计数
        foreach($result as $v){
            switch($v['question_type']){
                case 1;
                    if(empty($v['option_id'])){//如非必填情况下可能为空
                        break;
                    }
                    $option[$v['option_id']]['num']++;
                    break;
                case 2;
                    if(empty($v['option_id'])){//如非必填情况下可能为空
                        break;
                    }
                    $ids = explode(',',$v['option_id']);
                    foreach($ids as $id){
                        $option[$id]['num']++;
                    }
                    break;
                case 3;
                    $question['text'][] = $v['option_text'];
                    break;
                case 4;
                    if(!empty($v['option_id'])){//如非必填情况下可能为空
                        $option[$v['option_id']]['num']++;
                    }
                    $question['text'][] = $v['option_text'];
                    break;
                case 5;
                    if(!empty($v['option_id'])){//如非必填情况下可能为空
                        $ids = explode(',',$v['option_id']);
                        foreach($ids as $id){

                            $option[$id]['num']++;
                        }
                    }
                    $question['text'][] = $v['option_text'];
                    break;
            }
        }

        $question['option'] = $option;
        $question['sum'] = count($result);

        return $question;
    }
    







}
