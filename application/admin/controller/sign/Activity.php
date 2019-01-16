<?php

namespace app\admin\controller\sign;


use app\admin\model\Signuser;
use app\common\controller\Backend;
use think\Response;
use Endroid\QrCode\QrCode;
use app\admin\model\DingUser;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Activity extends Backend
{
    
    /**
     * Activity模型对象
     * @var \app\admin\model\Activity
     */
    protected $model = null;
    protected $file = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Activity;
        $this->file = new \app\admin\model\File;

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
                $lecture = DingUser::where('userId','in',$v['lecture'])->column('name');
                $v['lecture_name'] = implode(',',$lecture);

                $v['host_name'] = DingUser::where('userId',$v['host'])->column('name');
//                $v['host_name'] = implode(',',$lecture);
                if(empty($v['sponsor'])){
                    $v['sponsor_name'] = '无';
                }else{
                    $lecture = DingUser::where('userId','in',$v['sponsor'])->column('name');
                    $v['sponsor_name'] = implode(',',$lecture);
                }
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
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $params['content'] = preg_replace("/[\r\n|\r|\n]/","<br />",$params['content']);
                    $result = $this->model->allowField(true)->save($params);

                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($this->model->getError());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /*
     * 附件列表
     */
    public function fileList($ids = null)
    {
        try{

            $info = $this->model->where('id',$ids)->find();
            $fileList = null;
            $id = $info['upload_ids'];
            if(!empty(trim($info['upload_ids']))){
                $fileList = $this->file->fileList($info['upload_ids']);
            }

        } catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->view->assign('fileList',$fileList);
        $this->view->assign('id',$ids);
        return $this->view->fetch();
    }

    /*
     * 附件上传
     */
    public function upload()
    {
        $file = $this->request->file('file');

        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
        }

        $id = $this->request->get('id');
        if(empty($id)){
            $this->error('id为空');
        }
        //判断是否已经存在附件
//        $sha1 = $file->hash();

        $filePath = ROOT_PATH . 'public' . DS . 'uploads';
        $info = $file->move($filePath);

        if(!$info){
            $this->error('上传失败');
        }

        $fileSave['name'] = $info->getInfo('name');
        $fileSave['save_name'] = $info->getSaveName();
        $fileSave['path'] = DS.'uploads'.DS.$info->getSaveName();
        $fileSave['type'] = $info->getInfo('type');
        $fileRes = $this->file->save($fileSave);
        if($fileRes){
            $activity = $this->model->get($id);
            if(empty(trim($activity->upload_ids))){
                $activity->upload_ids = $this->file->id;
            }else{
                $activity->upload_ids .= ','.$this->file->id;
            }
            $activity->upload_num +=1;
            $activity->save();
        }
        $result = [
            'code' => 1,
            'msg'  => '上传成功',
            'data' => '',
            'url'  => null,
            'wait' => 3,
        ];

        return json($result);
    }

    /*
     * 附件删除
     */
    public function fileDel()
    {
        $aId = $this->request->get('a_id');
        $id = $this->request->get('id');

        try{
            $activity = $this->model->get($aId);
            $uploadIds = explode(',',$activity->upload_ids);
            $uploadIds = array_diff($uploadIds, [$id]);
            $activity->upload_ids = implode(',',$uploadIds);
            $activity->upload_num -=1;
            $activity->save();
        } catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('删除成功！');
    }

    /*
     * 生成二维码
     */
    public function signQrcode($ids = null)
        {
        $qrCode=new QrCode();
        $url = $this->request->domain().url('/index/sign/index',['id'=>$ids]);//加http://这样扫码可以直接跳转url
        $qrCode->setText($url)
            ->setSize(300)//大小
            ->setLabelFontPath(ROOT_PATH . 'public/assets/fonts/fzltxh.ttf')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel('钉钉扫描二维码开始签到')
            ->setLabelFontSize(16);

        return new Response($qrCode->get(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    /*
     * 导出表格
     */
    public function signExcel($ids)
    {

        try{
            $activityInfo = $this->model->where('id',$ids)->find();
            $filename = $activityInfo->name."-签到列表.xls";
            $contents = "<table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"0\" >
           <caption height='55'> <b>".$filename."</b> </caption>";

            $where['a_id'] = $ids;
            $where['sign_start'] = 1;
            $order = 'department desc';
            $userList = Signuser::where($where)->order($order)->select();
        }catch (\think\exception\PDOException $e) {
            $this->error($e->getMessage());
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }

        $contents .="<tr align=\"left\">";
        $contents .="<td>编号</td>";
        $contents .="<td>名称</td>";
        $contents .="<td>部门</td>";
        $contents .="<td>时间</td>";
        $contents .="<td>身份</td>";
        $contents .="<td>积分</td>";
        $contents .="</tr>";

        $type = config('sign.type');
        foreach($userList as $k=>$v){
            $contents .= "<tr align=\"left\">";
            $contents .="<td>";
            $contents .=$k+1;
            $contents .="</td>";

            $contents .="<td>";
            $contents .=$v->name;
            $contents .="</td>";

            $contents .="<td>";
            $contents .=$v->department;
            $contents .="</td>";

            $contents .="<td>";
            $contents .=$v->sign_time_text;
            $contents .="</td>";

            $contents .="<td>";
            $contents .=$type[$v->type];
            $contents .="</td>";

            $contents .="<td>";
            $contents .=$v->score;
            $contents .="</td>";

            $contents .="</tr>";
        }

        $contents .= "</table>";
        header ( 'Content-type: application/vnd.ms-execl' );
        header ( 'Content-Disposition: attachment; filename=' . $filename );
        echo $contents;
        die ();
    }
}
