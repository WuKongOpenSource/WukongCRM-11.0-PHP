<?php
// +----------------------------------------------------------------------
// | Description: 任务及基础
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\controller;

use app\oa\logic\TaskLogic as TasksLogic;
use app\work\logic\TaskLogic;
use app\work\traits\WorkAuthTrait;
use think\Request;
use think\Hook;
use app\admin\controller\ApiCommon;
use app\admin\model\Message;
use think\helper\Time;
use think\Db;

class Task extends ApiCommon
{
    use WorkAuthTrait;
    
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     **/
    public function _initialize()
    {
        $action = [
            'permission' => [''],
            'allow' => [
                'index', 'mytask', 'updatetop', 'updateorder', 'read', 'update', 'readloglist', 'updatepriority',
                'updateowner', 'delownerbyid', 'delstruceurebyid', 'updatestoptime', 'updatelable', 'updatename',
                'taskover', 'datelist', 'save', 'delmainuserid', 'rename', 'delete', 'archive', 'recover', 'archlist',
                'archivetask', 'setover', 'updateclassorder', 'excelimport', 'excelexport', 'taskusers', 'ownertasklist']
        
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        //权限判断
        $param = $this->param;
        if ($param['task_id']) {
            $userInfo = $this->userInfo;
            $taskModel = model('Task');
            if (!$taskModel->checkTask($param['task_id'], $userInfo)) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code' => 102, 'error' => '没有权限']));
            }
        }
    }
    
    /**
     * 项目下任务列表
     * @return
     * @author yykun
     */
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $taskModel = model('Task');
        if (!$param['work_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $list = $taskModel->getDataList($param, $userInfo['id']);
        return resultArray(['data' => $list]);
    }
    
    public function ownerTaskList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $taskModel = model('Task');
        if (!$param['work_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $list = $taskModel->getOwnerTaskList($param, $userInfo['id']);
        return resultArray(['data' => $list]);
    }
    
    /**
     * 任务列表导出
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('excelExport', $param['work_id'], $userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        $TaskLogic = new TasksLogic();
        $data = $TaskLogic->excelExport($param);
        return $data;
    }
    
    /**
     * 导入模板下载
     * @param string $save_path 本地保存路径     用于错误数据导出，在 Admin\Model\Excel::batchImportData()调用
     * @return
     * @author Michael_xu
     */
    public function excelDownload($save_path = '')
    {
        $excelModel = new \app\admin\model\Excel();
        $field_list = [
            '0' => [
                'name' => '任务名称',
                'field' => 'name',
                'types' => 'task',
                'form_type' => 'text',
                'default_value' => '',
                'is_unique' => 1,
                'is_null' => 1,
                'input_tips' => '',
                'setting' => array(),
                'is_hidden' => 0,
                'writeStatus' => 1,
                'value' => '',
            ],
            '1' => [
                'name' => '任务描述',
                'field' => 'description',
                'types' => 'task',
                'form_type' => 'textarea',
            ],
            '2' => [
                'name' => '开始时间',
                'field' => 'start_time',
                'types' => 'task',
                'form_type' => 'datetime',
            ],
            '3' => [
                'name' => '结束时间',
                'field' => 'stop_time',
                'types' => 'task',
                'form_type' => 'datetime',
            ],
            '4' => [
                'name' => '创建人',
                'field' => 'create_user_id',
                'types' => 'task',
                'form_type' => 'user',
            ],
            '5' => [
                'name' => '参与人',
                'field' => 'owner_user_id',
                'types' => 'task',
                'form_type' => 'user',
            ],
            '6' => [
                'name' => '所在任务列表',
                'field' => 'class_id',
                'types' => 'task',
                'form_type' => 'text',
                'is_unique' => 1,
                'is_null' => 1,
            ],
        ];
        // 导入的字段列表
        $excelModel->excelImportDownload($field_list, 'work_task', $save_path);
    }
    
    /**
     * 客户数据导入
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excelImport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('excelImport', $param['work_id'], $userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        $field_list = [
            '0' => [
                'name' => '任务名称',
                'field' => 'name',
                'types' => 'task',
                'form_type' => 'text',
                'default_value' => '',
                'is_unique' => 1,
                'is_null' => 1,
                'input_tips' => '',
                'setting' => array(),
                'is_hidden' => 0,
                'writeStatus' => 1,
                'value' => '',
            ],
            '1' => [
                'name' => '任务描述',
                'field' => 'description',
                'types' => 'task',
                'form_type' => 'textarea',
            ],
            '2' => [
                'name' => '开始时间',
                'field' => 'start_time',
                'types' => 'task',
                'form_type' => 'datetime',
            ],
            '3' => [
                'name' => '结束时间',
                'field' => 'stop_time',
                'types' => 'task',
                'form_type' => 'datetime',
            ],
            '4' => [
                'name' => '创建人',
                'field' => 'create_user_id',
                'types' => 'task',
                'form_type' => 'user',
            ],
            '5' => [
                'name' => '参与人',
                'field' => 'owner_user_id',
                'types' => 'task',
                'form_type' => 'user',
            ],
            '6' => [
                'name' => '所在任务列表',
                'field' => 'class_id',
                'types' => 'task',
                'form_type' => 'text',
                'is_unique' => 1,
                'is_null' => 1,
            ],
        ];
        $excelModel = new \app\admin\model\Excel();
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $param['owner_user_id'] ?: 0;
        $file = request()->file('file');
        $param['types'] = 'task';
        // $res = $excelModel->importExcel($file, $param, $this);
        $res = $excelModel->batchTaskImportData($file,$field_list, $param, $this);
        if (!$res) {
            return resultArray(['error' => $excelModel->getError()]);
        }
        return resultArray(['data' => $excelModel->getError()]);
    }
    
    
    /**
     * 任务搜索
     *
     * @param TaskLogic $taskLogic
     * @return \think\response\Json
     */
    public function search(TaskLogic $taskLogic)
    {
        $data = $taskLogic->getSearchData($this->param);
        
        return resultArray(['data' => $data]);
    }
    
    /**
     * 我的任务
     * @return
     * @author yykun
     */
    public function myTask()
    {
        $taskModel = model('Task');
        $userId = $this->userInfo['id'];
        
        $data = [];
        $data[0]['title'] = '收件箱';
        $data[1]['title'] = '今天要做';
        $data[2]['title'] = '下一步要做';
        $data[3]['title'] = '以后要做';
        for ($k = 0; $k < 4; $k++) {
            $where = [];
            $where['ishidden'] = 0;
            $where['is_top'] = $k;
            $where['pid'] = 0;
            $where['whereStr'] = ' ( task.create_user_id =' . $userId . ' or (  task.owner_user_id like "%,' . $userId . ',%") or ( task.main_user_id = ' . $userId . ' ) )';
            if (!empty($this->param['search'])) $where['taskSearch'] = '(task.name like "%' . $this->param['search'] . '%" OR task.description like "%' . $this->param['search'] . '%")';
            $resData = $taskModel->getProjectTaskList($where, $this->param);
            $data[$k]['is_top'] = $k;
            $data[$k]['list'] = $resData['list'] ?: [];
            $data[$k]['count'] = $resData['count'] ?: 0;
        }
        return resultArray(['data' => $data]);
    }
    
    /**
     * 我的任务 拖拽改变分类
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateTop()
    {
        $param = $this->param;
        $tolist = $param['tolist'];
        $fromlist = $param['fromlist'];
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('setTaskOrder', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        if ($param['to_top_id'] || $param['to_top_id'] == 0) {
            if ($tolist) {
                foreach ($tolist as $k1 => $v1) {
                    $toData = [];
                    $toData['is_top'] = $param['to_top_id'];
                    $toData['top_order_id'] = $k1 + 1;
                    Db::name('Task')->where(['task_id' => $v1])->update($toData);
                }
            }
        }
        if ($param['from_top_id'] || $param['from_top_id'] == 0) {
            if ($fromlist) {
                foreach ($fromlist as $k2 => $v2) {
                    $fromData = [];
                    $fromData['is_top'] = $param['from_top_id'];
                    $fromData['top_order_id'] = $k2 + 1;
                    Db::name('Task')->where(['task_id' => $v2])->update($fromData);
                }
            }
        } else {
            return resultArray(['error' => '参数错误']);
        }
        return resultArray(['data' => true]);
    }
    
    /**
     * 项目 拖拽改变分类并排序
     * @return
     * @author yykun
     */
    public function updateOrder()
    {
        $param = $this->param;
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('setTaskOrder', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        if ($param['tolist']) {
            $tolist = $param['tolist'];
            foreach ($tolist as $k1 => $v1) {
                $toData = [];
                $toData['class_id'] = $param['toid'];
                $toData['order_id'] = $k1 + 1;
                Db::name('Task')->where(['task_id' => $v1])->update($toData);
            }
        }
        if ($param['fromlist']) {
            $fromlist = $param['fromlist'];
            foreach ($fromlist as $k2 => $v2) {
                $fromData = [];
                $fromData['class_id'] = $param['fromid'];
                $fromData['order_id'] = $k2 + 1;
                Db::name('Task')->where(['task_id' => $v2])->update($fromData);
            }
        }
        return resultArray(['data' => true]);
    }
    
    /**
     * 项目下 拖拽整个分类排序
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateClassOrder()
    {
        $param = $this->param;
        $classlist = $param['class_ids'];
        if (!$param['work_id'] || !$param['class_ids']) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('updateClassOrder', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        foreach ($classlist as $k => $v) {
            $temp = [];
            $temp['order_id'] = $k + 1;
            Db::name('WorkTaskClass')->where(['work_id' => $param['work_id'], 'class_id' => $v])->update($temp);
        }
        
        return resultArray(['data' => '操作成功！']);
    }
    
    /**
     * 任务详情
     * @return
     * @author yykun
     */
    public function read()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $taskModel = model('Task');
        $taskData = $taskModel->getDataById($param['task_id'], $userInfo);
        
        # 获取任务的项目信息
        $workInfo = Db::name('work')->field(['work_id', 'group_id', 'is_open'])->where('work_id', $taskData['work_id'])->find();
        # 是否是公开项目
        $userId = $userInfo['id'];
        $groupId = !empty($workInfo['is_open']) ? $workInfo['group_id'] : 0;
        # 获取项目下的权限
        $taskData['auth'] = !empty($taskData['work_id']) ? $this->getRuleList($workInfo['work_id'], $userId, $groupId) : [];
        
        if ($taskData) {
            return resultArray(['data' => $taskData]);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 任务编辑
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        
        # 权限判断
        $action = 'updateChildTask'; # 修改子任务
        if (!empty($param['customer_ids']) || !empty($param['customer_ids']) || !empty($param['customer_ids']) || !empty($param['customer_ids'])) {
            $action = 'saveTaskRelation'; # 关联业务
        } elseif (!empty($param['description'])) {
            $action = 'setTaskDescription'; # 任务描述
        }
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth($action, $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $ary = array('owner_userid_del', 'owner_userid_add', 'stop_time', 'lable_id_add', 'lable_id_del', 'name', 'structure_id_del', 'structure_id_add');
        if ((in_array($param['type'], $ary))) {
            return resultArray(['error' => '参数错误']);
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 任务操作记录
     * @return
     * @author yykun
     */
    public function readLoglist()
    {
        $param = $this->param;
        $taskModel = model('Task');
        if (!$param['task_id']) return resultArray(['error' => '参数错误']);
        $list = $taskModel->getTaskLogList($param);
        return resultArray(['data' => $list]);
    }
    
    /**
     * 优先级设置
     * @return
     * @author yykun
     */
    public function updatePriority()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        if (!isset($param['priority_id']) || !$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('setTaskPriority', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $flag = Db::name('Task')->where(['task_id' => $param['task_id']])->setField('priority', $param['priority_id']);
        if ($flag) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => '操作失败']);
        }
    }
    
    /**
     * 参与人/参与部门编辑
     * @return
     * @author yykun
     */
    public function updateOwner()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $task_id = $param['task_id'] ?: '';
        $param['create_user_id'] = $userInfo['id'];
        $taskInfo = db('task')->where(['task_id' => $param['task_id']])->find();
        if (!$taskInfo) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('setTaskOwnerUser', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $data = [];
        //部门编辑
        $structure_ids = '';
        if ($param['structure_ids']) {
            $structure_ids = arrayToString($param['structure_ids']);
        }
        $owner_user_id = '';
        $sendUserArr = [];
        if ($param['owner_userids']) {
            $owner_user_id = arrayToString($param['owner_userids']);
            foreach ($param['owner_userids'] as $k => $v) {
                if (!in_array($v, stringToArray($taskInfo['owner_user_id']))) {
                    $sendUserArr[] = $v;
                }
            }
            // $content = $userInfo['realname'].'邀请您参与《'.$taskInfo['name'].'》项目，请及时查看';
            // if ($sendUserArr) sendMessage($sendUserArr,$content,1);
            actionLog($param['task_id'], $param['owner_user_id'], $param['structure_ids'], '修改了参与人');
        }
        $data['structure_ids'] = $structure_ids;
        $data['owner_user_id'] = $owner_user_id;
        $resUpdate = db('task')->where(['task_id' => $param['task_id']])->update($data);
        if ($resUpdate) {
            //站内信
            if ($sendUserArr) {
                (new Message())->send(
                    Message::TASK_INVITE,
                    [
                        'title' => $taskInfo['name'],
                        'action_id' => $taskInfo['task_id']
                    ],
                    $sendUserArr
                );
            }
            return resultArray(['data' => '修改成功']);
        }
        return resultArray(['error' => '修改失败或数据无变化']);
    }
    
    /**
     * 单独删除参与人
     * @return
     * @author yykun
     */
    public function delOwnerById()
    {
        $taskModel = model('Task');
        $userInfo = $this->userInfo;
        $param = $this->param;
        $param['create_user_id'] = $userInfo['id'];
        $ary = array('owner_userid_del', 'owner_userid_add');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('setTaskOwnerUser', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $ret = $taskModel->updateDetTask($param);
        if ($ret) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 单独删除参与部门
     * @return
     * @author yykun
     */
    public function delStruceureById()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $ary = array('structure_id_del', 'structure_id_add');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error' => '参数错误']);
        }
        $res = $taskModel->updateDetTask($param);
        if ($res) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 设置任务截止时间
     * @return
     * @author yykun
     */
    public function updateStoptime()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
//        if (!$param['stop_time']) {
//            return resultArray(['error'=>'参数错误']);
//        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('setTaskTime', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 修改任务标签
     * @return
     * @author yykun
     */
    public function updateLable()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $ary = array('lable_id_add', 'lable_id_del');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('setTaskLabel', $param['work_id'], $userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        if (isset($param['lable_id_add']) && !is_array($param['lable_id_add'])) {
            $label_id_arr[] = $param['lable_id_add'];
            $param['lable_id_add'] = $label_id_arr;
        }
        if (isset($param['lable_id_del']) && !is_array($param['lable_id_del'])) {
            $label_id_arr[] = $param['lable_id_del'];
            $param['lable_id_del'] = $label_id_arr;
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 修改任务名称
     * @return
     * @author yykun
     */
    public function updateName()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        if ($param['type'] !== 'name') {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('setTaskTitle', $param['work_id'], $userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $res = $taskModel->updateDetTask($param);
        if ($res) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 任务标记结束
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function taskOver()
    {
        $taskModel = model('Task');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        if (!$param['task_id'] || !$param['status']) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        $pid = Db::name('task')->where('task_id', $param['task_id'])->value('pid');
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth(empty($pid) ? 'setTaskStatus' : 'setChildTaskStatus', $param['work_id'], $userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $taskInfo = Db::name('Task')->where(['task_id' => $param['task_id']])->find();
        if ($param['status'] == '5') {
            $flag = Db::name('Task')->where(['task_id' => $param['task_id']])->setField('status', 5);
            if ($flag && !$taskInfo['pid']) {
                $temp['user_id'] = $userInfo['id'];
                $temp['content'] = '任务标记结束';
                $temp['create_time'] = time();
                $temp['task_id'] = $param['task_id'];
                Db::name('WorkTaskLog')->insert($temp);
                actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '任务标记结束');
                //抄送站内信
                $sendUserArr = [];
                $sendUserArr[] = $taskInfo['create_user_id'];
                if ($taskInfo['main_user_id']) {
                    $sendUserArr[] = $taskInfo['main_user_id'];
                }
                if ($taskInfo['owner_user_id']) {
                    $sendUserArr = $sendUserArr ? array_merge($sendUserArr, stringToArray($taskInfo['owner_user_id'])) : stringToArray($taskInfo['owner_user_id']);
                }
                if ($sendUserArr) {
                    (new Message())->send(
                        Message::TASK_OVER,
                        [
                            'title' => $taskInfo['name'],
                            'action_id' => $param['task_id']
                        ],
                        $sendUserArr
                    );
                }
            }
        } else {
            $flag = Db::name('Task')->where('task_id =' . $param['task_id'])->setField('status', 1);
            if ($flag && !$taskInfo['pid']) {
                $temp['user_id'] = $userInfo['id'];
                $temp['content'] = '任务标记开始';
                $temp['create_time'] = time();
                $temp['task_id'] = $param['task_id'];
                Db::name('WorkTaskLog')->insert($temp);
                actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '任务标记开始');
            }
        }
        if ($flag) {
            return resultArray(['data' => true]);
        } else {
            return resultArray(['error' => '标记失败']);
        }
    }
    
    /**
     * 日历任务展示/月份
     * @return
     * @author yykun
     */
    public function dateList()
    {
        $param = $this->param;
        $taskModel = model('Task');
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $taskModel->getDateList($param);
        return resultArray(['data' => $data]);
    }
    
    /**
     * 添加任务
     * @return
     * @author Michael_xu
     */
    public function save()
    {
        $param = $this->param;
        $taskModel = model('Task');
        $workModel = model('Work');
        if (!$param['name']) {
            return resultArray(['error' => '参数错误']);
        }
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['create_user_name'] = $userInfo['realname'];
        # 任务权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth(empty($param['pid']) ? 'addChildTask' : 'saveTask', $param['work_id'], $userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
//        if ($param['work_id'] && !$workModel->isCheck('work','task','save',$param['work_id'],$userInfo['id'])) {
//            header('Content-Type:application/json; charset=utf-8');
//            exit(json_encode(['code'=>102,'error'=>'无权操作']));
//        }
        $res = $taskModel->createTask($param);
        if ($res) {
            return resultArray(['data' => $res]);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 删除主负责人
     * @return
     * @author yykun
     */
    public function delMainUserId()
    {
        $param = $this->param;
        $workModel = model('Task');
        if ($param['task_id']) {
            $userInfo = $this->userInfo;
            $param['create_user_id'] = $userInfo['id'];
            $taskInfo = Db::name('Task')->where(['task_id' => $param['task_id']])->find();
            $data = [];
            $data['main_user_id'] = '';
            $data['status'] = 1;
            $flag = Db::name('Task')->where(['task_id' => $param['task_id']])->update($data);
            if ($flag && !$taskInfo['pid']) {
                actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '删除负责人');
                return resultArray(['data' => '操作成功']);
            }
            return resultArray(['error' => '操作失败']);
        } else {
            return resultArray(['error' => '参数错误']);
        }
    }
    
    /**
     * 重命名任务
     * @return
     * @author yykun
     */
    public function rename()
    {
        $param = $this->param;
        $workModel = model('Work');
        if (!$param['rename'] || !$param['work_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $flag = $workModel->rename($param);
        if ($flag) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $workModel->getError()]);
        }
    }
    
    /**
     * 删除任务
     * @return
     * @author yykun
     */
    public function delete()
    {
        $param = $this->param;
        $taskModel = model('Task');
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        $pid = Db::name('task')->where('task_id', $param['task_id'])->value('pid');
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth(empty($pid) ? 'deleteTask' : 'deleteChildTask', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $flag = $taskModel->delTaskById($param);
        if ($flag) {
            return resultArray(['data' => '删除成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 归档任务
     * @return
     * @author yykun
     */
    public function archive()
    {
        $param = $this->param;
        $taskModel = model('Task');
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('archiveTask', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $flag = $taskModel->archiveData($param);
        if ($flag) {
            $temp['user_id'] = $userInfo['id'];
            $temp['content'] = '归档任务';
            $temp['create_time'] = time();
            $temp['task_id'] = $param['task_id'];
            Db::name('WorkTaskLog')->insert($temp);
            return resultArray(['data' => '归档成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 恢复归档任务
     * @return
     * @author yykun
     */
    public function recover()
    {
        $param = $this->param;
        $taskModel = model('Task');
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        
        # 权限判断
        if (!empty($param['work_id']) && !$this->checkWorkOperationAuth('archiveTask', $param['work_id'], $this->userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作！']));
        }
        
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $flag = $taskModel->recover($param);
        if ($flag) {
            $temp['user_id'] = $userInfo['id'];
            $temp['content'] = '恢复归档任务';
            $temp['create_time'] = time();
            $temp['task_id'] = $param['task_id'];
            Db::name('WorkTaskLog')->insert($temp);
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 归档任务列表
     * @return
     * @author yykun
     */
    public function archList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $taskModel = model('Task');
        if (!$param['work_id']) return resultArray(['error' => '参数错误']);
        $request = [];
        $request['work_id'] = $param['work_id'];
        $request['is_archive'] = 1;
        $list = $taskModel->getTaskList($request);
        return resultArray(['data' => $list]);
    }
    
    /**
     * 归档某一类已完成任务
     * @return
     * @author yykun
     */
    public function archiveTask()
    {
        $param = $this->param;
        if (!$param['class_id']) return resultArray(['error' => '参数错误']);
        $data = array();
        $data['is_archive'] = 1;
        $data['archive_time'] = time();
        $res = db('task')->where(['class_id' => $param['class_id'], 'status' => '5'])->update($data);
        if ($res) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => '暂无已完成任务，归档失败！']);
        }
    }
    
    /**
     * 任务成员列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function taskUsers()
    {
        $userId = $this->userInfo['id'];
        
        # 查询条件
        $where['create_user_id'] = $userId;
        $where['main_user_id'] = $userId;
        $where['owner_user_id'] = ['like', '%,' . $userId . ',%'];
        
        # 查询数据
        $data = Db::name('task')->field(['create_user_id', 'main_user_id', 'owner_user_id'])->whereOr($where)->select();
        
        # 整理数据
        $userIds = [];
        foreach ($data as $key => $value) {
            if (!empty($value['create_user_id'])) $userIds[] = $value['create_user_id'];
            if (!empty($value['main_user_id'])) $userIds[] = $value['main_user_id'];
            
            $ownerUserIds = explode(',', $value['owner_user_id']);
            foreach ($ownerUserIds as $k => $v) {
                if (!empty($v)) $userIds[] = $v;
            }
        }
        $userIds = array_unique($userIds);
        
        # 查询参与人
        $userList = Db::name('admin_user')->field(['id', 'realname'])->whereIn('id', $userIds)->select();
        
        return resultArray(['data' => $userList]);
    }
}