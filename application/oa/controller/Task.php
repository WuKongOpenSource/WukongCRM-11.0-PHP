<?php
// +----------------------------------------------------------------------
// | Description: 任务及基础
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\oa\logic\TaskLogic;
use think\Request;
use think\Session;
use think\Hook;
use app\admin\controller\ApiCommon;
use think\helper\Time;
use think\Db;
use app\work\model\Task as TaskModel;

class Task extends ApiCommon
{
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
            'allow' => ['index', 'mytask', 'subtasklist', 'updatetop', 'updateorder', 'read', 'update', 'readloglist', 'updatepriority', 'updateowner', 'updatestructure', 'updateownerid', 'delownerbyid', 'delstruceurebyid', 'updatestoptime', 'updatelable', 'updatename', 'taskover', 'datelist', 'save', 'delmainuserid', 'rename', 'delete', 'archive', 'recover', 'archlist', 'archivetask', 'setover', 'worklist', 'delrelation', 'excelexport']
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        $param = $this->param;
        if ($param['task_id']) {
            $userInfo = $this->userInfo;
            $taskModel = new TaskModel();
            $ret = $taskModel->checkTask($param['task_id'], $userInfo);
            if (!$ret) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code' => 102, 'error' => '没有权限']));
            }
        }
    }
    
    //判断任务(需创建人和负责人才能编辑删除)
    public function checkSub($task_id)
    {
        $userInfo = $this->userInfo;
        $taskInfo = Db::name('Task')->where('task_id = ' . $task_id)->find();
        $main_user_ids = stringToArray($taskInfo['main_user_id']);
        if ($taskInfo['create_user_id'] == $userInfo['id'] || in_array($userInfo['id'], $main_user_ids)) {
            return true;
        } else {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '没有权限']));
        }
    }
    
    /**
     * 查看下属创建的任务
     * @param   //负责和参与
     * @return
     * @author
     */
    public function subTaskList()
    {
    
    }
    
    /**
     * 查看所有的项目
     * @param
     * @return
     * @author
     */
    public function workList()
    {
        $count = Db::name('Work')->where(['status' => 1])->count();
        $workList = Db::name('Work')->where(['status' => 1])->field('work_id,name')->select();
        $data['list'] = $workList;
        $data['count'] = $count;
        return resultArray(['data' => $data]);
    }
    
    /**
     * 查看某个项目下任务列表
     * @param
     * @return
     * @author
     */
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $taskModel = new \app\work\model\Task();
        if (!$param['work_id']) return resultArray(['error' => '参数错误']);
        $list = $taskModel->getDataList($param, $userInfo['id']);
        return resultArray(['data' => $list]);
    }
    
    /**
     * 查看我的任务
     * @param
     * @return
     * @author
     */
    public function myTask()
    {
        $userModel = new \app\admin\model\User();
        $lableModel = new \app\work\model\WorkLable();
        $taskModel = new \app\work\model\Task();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $str = ',' . $userInfo['id'] . ',';
        
        //自定义时间
        $map['t.stop_time'] = $param['dueDate'] ? strtotime($param['dueDate'] . ' +1 month -1 day') : ['>=', 0];
        $search = $param['search'];
        if ($search) {
            $where['t.name'] = array('like', '%' . $search . '%');
        }
        $type = '';
        if (isset($param['type']) && $param['type']) {
            $type = $param['type'];
        }
        //状态
        $status = $param['status'] ?: '';
        if ($status==1) {
            $where['t.status'] = $status;
        } elseif($status==6) {
            $where['t.status'] = 5;
        }else{
            $where['t.status'] = [['=', 1], ['=', 5], 'OR'];
        }
        if ($param['main_user_id']) {
            $where['t.main_user_id'] = $param['main_user_id'];
        }
        //项目id
        if ($param['work_id']) $where['t.work_id'] = $param['work_id'];
        $priority = ($param['priority'] || $param['priority'] == '0') ? $param['priority'] : ['in', [0, 1, 2, 3]];
        $where['t.priority'] = $priority;
        ///下属任务
        if ($param['mold'] == 1) {
            $subList = getSubUserId(false, 0);
            $subStr = $subList ? implode(',', $subList) : '-1';
            $subArr = [];
            foreach ($subList as $k => $v) {
                $subArr[] = $v;
                $subArr[] = '|';
            }
            $subValue = $subList ? arrayToString($subArr) : '';
            $where['t.ishidden'] = 0;
            $where['t.pid'] = 0;
            if ($type != 0) {
                switch ($type) {
                    case '1' :
                        $type = 't.main_user_id in (' . $subStr . ')';
                        break; //下属负责的
                    case '3' :
                        //使用正则查询
                        // SELECT * FROM 5kcrm_task WHERE owner_user_id REGEXP '(,1,|,2,|,3,)';
                        $type = $subValue ? 't.owner_user_id REGEXP "(' . $subValue . ')"' : '';
                        break; //下属参与的
                }
            } else {
                if (!$subValue) {
                    $type = 't.is_open = 1 AND (t.main_user_id in (' . $subStr . ') or t.create_user_id in (' . $subStr . '))';
                } else {
                    $type .= 't.is_open = 1 AND (t.main_user_id in (' . $subStr . ') or t.create_user_id in (' . $subStr . ') or t.owner_user_id REGEXP "(' . $subValue . ')")';
                }
            }
            // $where['t.work_id'] = 0;
            $taskList = Db::name('Task')
                ->alias('t')
                ->where($where)
                ->where(function ($query) use ($type) {
                    $query->where($type);
                })
                ->where($map)
                ->field('t.task_id,t.create_user_id,t.main_user_id,t.owner_user_id,t.status,t.priority,t.pid,t.start_time,t.stop_time,t.work_id,t.order_id,t.create_time,t.lable_id,t.name')
                ->page($param['page'], $param['limit'])
                ->order('t.task_id desc')
                ->select();
            $dataCount = db('task')
                ->alias('t')
                ->where($where)
                ->where($map)
                ->where(function ($query) use ($type) {
                    $query->where($type);
                })
                ->count();
            $completeCount = db('task')->alias('t')
                ->where($map)
                ->where(function ($query) use ($type) {
                    $query->where($type);
                })
                ->where($map)->where(['t.status' => 5, 't.ishidden' => 0, 'priority' => $priority])->count();
            foreach ($taskList as $k => $v) {
                $temp = $v ?: [];
                if ($v['pid']) {
                    $pname = db('task')->where('task_id =' . $v['pid'])->value('name');
                    $taskList[$k]['pname'] = $pname ?: '';
                }
                $subcount = db('task')->where(['status' => 1, 'pid' => $v['task_id']])->count();
                $subdonecount = db('task')->where(['status' => 5, 'pid' => $v['task_id']])->count();
                $taskList[$k]['subcount'] = $subcount; //子任务
                $taskList[$k]['subdonecount'] = $subdonecount; //已完成子任
                $taskList[$k]['commentcount'] = db('admin_comment')->where(['type' => 'task', 'type_id' => $v['task_id']])->count();
                $taskList[$k]['filecount'] = Db::name('WorkTaskFile')->where(['task_id' => $v['task_id']])->count();
                $taskList[$k]['lableList'] = $v['lable_id'] ? $lableModel->getDataByStr($v['lable_id']) : [];
                $taskList[$k]['main_user'] = $v['main_user_id'] ? $userModel->getUserById($v['main_user_id']) : array();
                $taskList[$k]['relationCount'] = $taskModel->getRelationCount($v['task_id']);
                
                $taskList[$k]['create_time'] = date('Y-m-d', $v['create_time']) ?: '';
                $taskList[$k]['update_time'] = date('Y-m-d', $v['update_time']) ?: '';
                $taskList[$k]['start_time'] = $v['start_time'] == 0 ? null : date('Y-m-d', $v['start_time']);;
                $taskList[$k]['stop_time'] = $v['stop_time'] == 0 ? null : date('Y-m-d', $v['stop_time']);
                $is_end = 0;
                if (!empty($v['stop_time']) && (strtotime(date('Ymd')) + 86400 > $v['stop_time'])) $is_end = 1;
                $taskList[$k]['is_end'] = $is_end;
            }
            
        } else {
            $map['t.pid'] = 0;
            // $map['t.work_id'] = 0;
            if ($type != 0) {
                switch ($type) {
                    case '1' :
                        $type = 't.main_user_id =' . $userInfo['id'] . '';
                        break; //我负责的
                    case '3' :
                        $type = 't.owner_user_id like "%,' . $userInfo['id'] . ',%"';
                        break; //我参与的
                }
            } else {
                $adminIds = $userModel->getAdminId();
                if (in_array($userInfo['id'], $adminIds)) {
                    $type = 't.is_open = 1';
                } else {
                    $type = 't.is_open = 1 AND (t.main_user_id =' . $userInfo['id'] . ' OR t.owner_user_id like "%,' . $userInfo['id'] . ',%")';
                }
            }
            $where['t.ishidden'] = 0;
            $taskList = Db::name('Task')->alias('t')
                ->join('AdminUser u', 'u.id = t.main_user_id', 'LEFT')
                ->join('Work w', 'w.work_id = t.work_id', 'LEFT')
                ->field('t.task_id,t.name as task_name,t.main_user_id,t.is_top,t.work_id,t.lable_id,t.priority,t.update_time,t.start_time,t.stop_time,t.status,t.pid,t.create_time,t.owner_user_id,u.realname as main_user_name,u.thumb_img,w.name as work_name')
                ->where($where)
                ->where($type)
                ->where($map)
                ->page($param['page'], $param['limit'])
                ->order('t.task_id desc')
                ->select();
            $dataCount = db('task')->alias('t')->where($where)->where($type)->where($map)->count();
            $completeCount = db('task')->alias('t')
                ->where($type)
                ->where($map)->where(['t.status' => 5, 't.ishidden' => 0, 'priority' => $priority])->count();
            foreach ($taskList as $key => $value) {
                $pname = '';
                if ($value['pid']) {
                    $pname = Db::name('Task')->where('task_id =' . $value['pid'])->value('name');
                }
                $taskList[$key]['pname'] = $pname ?: '';
                $taskList[$key]['thumb_img'] = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
                $taskList[$key]['subcount'] = Db::name('Task')->where(['ishidden' => 0, 'status' => 1, 'pid' => $value['task_id']])->count(); //子任务
                $taskList[$key]['subdonecount'] = Db::name('Task')->where(['ishidden' => 0, 'status' => 5, 'pid' => $value['task_id']])->count(); //已完成子任务
                $taskList[$key]['commentcount'] = Db::name('AdminComment')->where(['type' => 'task', 'type_id' => $value['task_id']])->count();
                $taskList[$key]['filecount'] = Db::name('WorkTaskFile')->where('task_id =' . $value['task_id'])->count();
                $taskList[$key]['lableList'] = $value['lable_id'] ? $lableModel->getDataByStr($value['lable_id']) : [];
                
                $taskList[$key]['create_time'] = date('Y-m-d', $value['create_time']) ?: '';
                $taskList[$key]['update_time'] = date('Y-m-d', $value['update_time']) ?: '';
                $taskList[$key]['start_time'] = $value['start_time'] == 0 ? null : date('Y-m-d', $value['start_time']);;
                $taskList[$key]['stop_time'] = $value['stop_time'] == 0 ? null : date('Y-m-d', $value['stop_time']);
                //负责人信息
                $taskList[$key]['main_user'] = $value['main_user_id'] ? $userModel->getDataById($value['main_user_id']) : array();
                $taskList[$key]['relationCount'] = $taskModel->getRelationCount($value['task_id']);
                $is_end = 0;
                if (!empty($value['stop_time']) && (strtotime(date('Ymd')) + 86399 > $value['stop_time'])) $is_end = 1;
                $taskList[$key]['is_end'] = $is_end;
            }
        }
        
        $data = [];
        $data['page']['list'] = $taskList ?: [];
        $data['page']['dataCount'] = $dataCount ?: 0;
        $data['page']['completeCount'] = $completeCount ?: 0;
        if ($param['page'] != 1 && ($param['page'] * $param['limit']) >= $dataCount) {
            $data['page']['firstPage'] = false;
            $data['page']['lastPage'] = true;
        } else if ($param['page'] != 1 && (int)($param['page'] * $param['limit']) < $dataCount) {
            $data['page']['firstPage'] = false;
            $data['page']['lastPage'] = false;
        } else if ($param['page'] == 1) {
            $data['page']['firstPage'] = true;
            $data['page']['lastPage'] = false;
        }
        return resultArray(['data' => $data]);
    }
    
    /**
     * 任务列表导出
     * @return \think\response\Json|void
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new TaskLogic();
        $data = $TaskLogic->excelExport($param);
        return $data;
    }
    
    /**
     * 获取任务详情
     * @param
     * @return
     * @author
     */
    public function read()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $taskmodel = new \app\work\model\Task();
        $data = $taskmodel->getDataById($param['task_id'], $userInfo);
        if ($data) {
            return resultArray(['data' => $data]);
        } else {
            return resultArray(['error' => $taskmodel->getError()]);
        }
    }
    
    /**
     * 任务编辑保存
     * @param
     * @return
     * @author
     */
    public function update()
    {
        $taskModel = new \app\work\model\Task();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $ary = array('owner_userid_del', 'owner_userid_add', 'stop_time', 'lable_id_add', 'lable_id_del', 'name', 'structure_id_del', 'structure_id_add');
        if ((in_array($param['type'], $ary))) {
            return resultArray(['error' => '参数错误']);
        }
        if (isset($param['main_user_id'])) {
            //判断编辑权限
            $this->checkSub($param['task_id']);
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 解除关联关系
     * @param
     * @return
     * @author
     */
    public function delrelation()
    {
        $param = $this->param;
        if (!$param['task_id'] || !$param['type'] || !$param['id']) {
            return resultArray(['error' => '参数错误']);
        }
        $taskInfo = Db::name('Task')->where(['task_id' => $param['task_id']])->find();
        $det = Db::name('TaskRelation')->where(['task_id' => $param['task_id']])->find();
        $activityUpdate = [];
        if ($param['type'] == '1') {
            $newstr = str_replace(',' . $param['id'] . ',', ',', $det['customer_ids']);
            $newdata['customer_ids'] = $newstr;
            # 删除活动关联
            $customerIds = db('crm_activity')->where(['activity_type' => 11, 'activity_type_id' => $param['task_id']])->value('customer_ids');
            $activityUpdate['customer_ids'] = str_replace(',' . $param['id'] . ',', ',', $customerIds);
            if ($activityUpdate['customer_ids'] == ',') $activityUpdate['customer_ids'] = '';
        } elseif ($param['type'] == '2') {
            $newstr = str_replace(',' . $param['id'] . ',', ',', $det['contacts_ids']);
            $newdata['contacts_ids'] = $newstr;
            # 删除活动关联
            $contactsIds = db('crm_activity')->where(['activity_type' => 11, 'activity_type_id' => $param['task_id']])->value('contacts_ids');
            $activityUpdate['contacts_ids'] = str_replace(',' . $param['id'] . ',', ',', $contactsIds);
            if ($activityUpdate['contacts_ids'] == ',') $activityUpdate['contacts_ids'] = '';
        } elseif ($param['type'] == '3') {
            $newstr = str_replace(',' . $param['id'] . ',', ',', $det['business_ids']);
            $newdata['business_ids'] = $newstr;
            # 删除活动关联
            $businessIds = db('crm_activity')->where(['activity_type' => 11, 'activity_type_id' => $param['task_id']])->value('business_ids');
            $activityUpdate['business_ids'] = str_replace(',' . $param['id'] . ',', ',', $businessIds);
            if ($activityUpdate['business_ids'] == ',') $activityUpdate['business_ids'] = '';
        } elseif ($param['type'] == '4') {
            $newstr = str_replace(',' . $param['id'] . ',', ',', $det['contract_ids']);
            $newdata['contract_ids'] = $newstr;
            # 删除活动关联
            $contractIds = db('crm_activity')->where(['activity_type' => 11, 'activity_type_id' => $param['task_id']])->value('contract_ids');
            $activityUpdate['contract_ids'] = str_replace(',' . $param['id'] . ',', ',', $contractIds);
            if ($activityUpdate['contract_ids'] == ',') $activityUpdate['contract_ids'] = '';
        }
        $flag = Db::name('TaskRelation')->where(['task_id' => $param['task_id']])->update($newdata);
        # 取消活动关联
        db('crm_activity')->where(['activity_type' => 11, 'activity_type_id' => $param['task_id']])->update($activityUpdate);
        if ($flag) {
            if (!$taskInfo['pid']) {
                actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '编辑关联关系');
            }
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => '操作失败']);
        }
    }
    
    /**
     * 获取任务操作记录
     * @param
     * @return
     * @author
     */
    public function readLoglist()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task();
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $list = $taskModel->getTaskLogList($param) ?: [];
        return resultArray(['data' => $list]);
    }
    
    /**
     * 优先级设置
     * @param
     * @return
     * @author
     */
    public function updatePriority()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        
        if (!isset($param['priority_id']) || !$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        if (db('task')->where(['task_id' => $param['task_id']])->setField('priority', $param['priority_id'])) {
            $taskInfo = db('task')->where(['task_id' => $param['task_id']])->find();
            if (!$taskInfo['pid']) {
                actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '修改优先级');
            }
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => '操作失败']);
        }
    }
    
    /**
     * 参与人/参与部门编辑
     * @param
     * @return
     * @author
     */
    public function updateOwner()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $task_id = $param['task_id'] ?: '';
        $param['create_user_id'] = $userInfo['id'];
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $data = [];
        //部门编辑
        $structure_ids = '';
        if ($param['structure_ids']) {
            $structure_ids = arrayToString($param['structure_ids']);
        }
        $owner_user_id = '';
        if ($param['owner_userids']) {
            $owner_user_id = arrayToString($param['owner_userids']);
            actionLog($param['task_id'], $param['owner_user_id'], $param['structure_ids'], '修改了参与人');
        }
        $data['structure_ids'] = $structure_ids;
        $data['owner_user_id'] = $owner_user_id;
        $resUpdate = db('task')->where(['task_id' => $param['task_id']])->update($data);
        if ($resUpdate) {
            return resultArray(['data' => '修改成功']);
        }
        return resultArray(['error' => '修改失败']);
    }
    
    /**
     * 单独删除参与人
     * @param
     * @return
     * @author
     */
    public function delOwnerById()
    {
        $taskModel = new \app\work\model\Task();
        $userInfo = $this->userInfo;
        $param = $this->param;
        $param['create_user_id'] = $userInfo['id'];
        $ary = array('owner_userid_del', 'owner_userid_add');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error' => '参数错误']);
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 单独删除参与部门
     * @param
     * @return
     * @author
     */
    public function delStruceureById()
    {
        $taskModel = new \app\work\model\Task();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $ary = array('structure_id_del', 'structure_id_add');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error' => '参数错误']);
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 设置任务截止时间
     * @param
     * @return
     * @author
     */
    public function updateStoptime()
    {
        $taskModel = new \app\work\model\Task();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        if (!isset($param['stop_time'])) {
            return resultArray(['error' => '参数错误']);
        }
        $rett = $this->checkSub($param['task_id']); //判断编辑权限
        if (!$rett) {
            return resultArray(['error' => '没有权限']);
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 添加删除标签
     * @param
     * @return
     * @author
     */
    public function updateLable()
    {
        $taskModel = new \app\work\model\Task();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $ary = array('lable_id_add', 'lable_id_del');
        if (!in_array($param['type'], $ary)) {
            return resultArray(['error' => '参数错误']);
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
     * 任务标题描述更新
     * @param
     * @return
     * @author
     */
    public function updateName()
    {
        $taskModel = new \app\work\model\Task();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        if ($param['type'] !== 'name') {
            return resultArray(['error' => '参数错误']);
        }
        if ($taskModel->updateDetTask($param)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 任务标记结束
     * @param
     * @return
     * @author
     */
    public function taskOver()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $task_id = $param['task_id'];
        $param['create_user_id'] = $userInfo['id'];
        if (!$task_id || !$param['type']) {
            return resultArray(['error' => '参数错误']);
        }
        $taskInfo = Db::name('task')->where(['task_id' => $task_id])->find();
        if ($param['type'] == '1') {
            $res = Db::name('Task')->where(['task_id' => $task_id])->setField('status', 5);
            if ($res && !$taskInfo['pid']) {
                $temp['user_id'] = $userInfo['id'];
                $temp['content'] = '任务标记结束';
                $temp['create_time'] = time();
                $temp['task_id'] = $task_id;
                Db::name('WorkTaskLog')->insert($temp);
                actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '任务标记结束');
            }
        } else {
            $res = Db::name('Task')->where(['task_id' => $task_id])->setField('status', 1);
            if ($res && !$taskInfo['pid']) {
                $temp['user_id'] = $userInfo['id'];
                $temp['content'] = '任务标记开始';
                $temp['create_time'] = time();
                $temp['task_id'] = $task_id;
                Db::name('WorkTaskLog')->insert($temp);
                actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '任务标记开始');
            }
        }
        return resultArray(['data' => '操作成功']);
    }
    
    /**
     * 日历任务展示/月份
     * @param
     * @return
     * @author
     */
    public function dateList()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task();
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $ret = $taskModel->getDateList($param);
        if ($ret) {
            return resultArray(['data' => $ret]);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 添加任务
     * @param
     * @return
     * @author
     */
    public function save()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task();
        if (!$param['name']) {
            return resultArray(['error' => '请填写任务名称']);
        }
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $data = $taskModel->createTask($param);
        if ($data) {
            return resultArray(['data' => $data]);
        } else {
            return resultArray(['error' => $taskModel->getError()]);
        }
    }
    
    /**
     * 删除主负责人
     * @param
     * @return
     * @author
     */
    public function delMainUserId()
    {
        $param = $this->param;
        $workModel = new \app\work\model\Task();
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        //判断编辑权限
        $this->checkSub($param['task_id']);
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $taskInfo = Db::name('Task')->where(['task_id' => $param['task_id']])->find();
        $res = Db::name('Task')->where(['task_id' => $param['task_id']])->setField('main_user_id', '');
        if (!$res) {
            return resultArray(['error' => '操作失败']);
        }
        if (!$taskInfo['pid']) {
            actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '删除负责人');
        }
        return resultArray(['data' => '操作成功']);
    }
    
    /**
     * 重命名任务
     * @param
     * @return
     * @author
     */
    public function rename()
    {
        $param = $this->param;
        $workModel = new \app\work\model\Task();
        if (!$param['rename'] || !$param['work_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $res = $workModel->rename($param);
        if ($res) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $workModel->getError()]);
        }
    }
    
    /**
     * 删除任务
     * @param
     * @return
     * @author
     */
    public function delete()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task();
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        //判断编辑权限
        $this->checkSub($param['task_id']);
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $res = $taskModel->delTaskById($param);
        if ($res) {
            return resultArray(['data' => '删除成功']);
        } else {
            return resultArray(['error' => $workModel->getError()]);
        }
    }
    
    /**
     * 归档任务 改变状态
     * @param
     * @return
     * @author
     */
    public function archive()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task();
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $res = $taskModel->archiveData($param);
        if ($res) {
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
     * @param
     * @return
     * @author
     */
    public function recover()
    {
        $param = $this->param;
        $taskModel = new \app\work\model\Task();
        if (!$param['task_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $flag = $taskModel->recover($param);
        if ($flag) {
            $temp['user_id'] = $userInfo['id'];
            $temp['content'] = '归档任务';
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
     * @param
     * @return
     * @author
     */
    public function archList()
    {
        $param = $this->param;
        if (!$param['work_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $list = Db::name('Task')->where(['status' => 3, 'work_id' => $param['work_id']])->field('task_id,name,create_time,archive_time,stop_time')->select();
        return resultArray(['data' => $list]);
    }
}