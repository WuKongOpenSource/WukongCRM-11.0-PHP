<?php

namespace app\oa\logic;

use app\work\logic\WorkLogic;
use app\work\model\Task as TaskModel;
use app\work\model\Work as WorkModel;
use app\work\model\WorkClass as classModel;
use think\Db;

class TaskLogic
{
    public function getDataList($param)
    {
        $userModel = new \app\admin\model\User();
        $taskModel = new TaskModel();
        $recordModel = new \app\admin\model\Record();
        $str = ',' . $param['user_id'] . ',';
        
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
        if ($status) {
            $where['t.status'] = $status;
        } else {
            $where['t.status'] = [['=', 1], ['=', 5], 'OR'];
        }
        
        if ($param['main_user_id']) {
            $where['t.main_user_id'] = $param['main_user_id'];
        }
        //项目id
        $priority = ($param['priority'] || $param['priority'] == '0') ? $param['priority'] : ['in', [0, 1, 2, 3]];
        $where['t.priority'] = $priority;
        
        if ($param['work_id'] != 0) {
            $where['t.work_id'] = $param['work_id'];
            $taskList = db('task')
                ->alias('t')
                ->join('AdminUser u', 'u.id = t.main_user_id', 'LEFT')
                ->field('t.task_id,t.name as task_name,t.main_user_id,t.description,t.priority,t.stop_time,t.create_time,t.owner_user_id,t.start_time,t.create_user_id,u.realname as main_user_name,t.class_id')
                ->where($where)
                ->order('t.task_id desc')
                ->select();
        } else {
            if ($param['is_top'] == 5) {
                $where = [];
                $where['ishidden'] = 0;
                $where['pid'] = 0;
                $where['whereStr'] = ' ( task.create_user_id =' . $param['user_id'] . ' or (  task.owner_user_id like "%,' . $param['user_id'] . ',%") or ( task.main_user_id = ' . $param['user_id'] . ' ) )';
                if (!empty($this->param['search'])) {
                    $where['taskSearch'] = ' and (task.name like "%' . $this->param['search'] . '%" OR task.description like "%' . $this->param['search'] . '%")';
                }
                # 截止日期
                $timeWhere = $this->getTimeParam($param['time_type']);
                # 标签
                $labelWhere = '';
                if (!empty($param['label_id']) && is_array($param['label_id'])) {
                    foreach ($param['label_id'] as $key => $value) {
                        $labelWhere .= '(  task.lable_id like "%,' . $value . ',%") OR ';
                    }
                    if (!empty($labelWhere)) $labelWhere = '(' . rtrim($labelWhere, 'OR ') . ')';
                }
                $where = $this->where($param);
                $taskList = db('task')
                    ->alias('t')
                    ->join('AdminUser u', 'u.id = t.main_user_id', 'LEFT')
                    ->field('t.task_id,t.name as task_name,t.main_user_id,t.description,t.priority,t.stop_time,t.create_time,t.owner_user_id,t.start_time,t.create_user_id,u.realname as main_user_name,t.is_top')
                    ->where($where)
                    ->where($timeWhere)
                    ->where($labelWhere)
                    ->order('t.task_id desc')
                    ->select();
                
            } else {
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
                    $map['t.pid'] = 0;
                    $map['t.ishidden'] = 0;
                    $taskList = Db::name('Task')
                        ->alias('t')
                        ->join('AdminUser u', 'u.id = t.main_user_id', 'LEFT')
                        ->field(
                            't.task_id,t.name as task_name,t.main_user_id,t.description,t.priority,t.stop_time,t.create_time,t.owner_user_id,t.start_time,t.create_user_id,u.realname as main_user_name'
                        )
                        ->where(function ($query) use ($type) {
                            $query->where($type);
                        })
                        ->where($where)
                        ->where($map)
                        ->order('t.task_id desc')
                        ->select();
                } else {
                    $map['t.pid'] = 0;
                    // $map['t.work_id'] = 0;
                    if ($type != 0) {
                        switch ($type) {
                            case '1' :
                                $type = 't.main_user_id =' . $param['user_id'] . '';
                                break; //我负责的
                            case '3' :
                                $type = 't.owner_user_id like "%,' . $param['user_id'] . ',%"';
                                break; //我参与的
                        }
                    } else {
                        $adminIds = $userModel->getAdminId();
                        if (in_array($param['user_id'], $adminIds)) {
                            $type = 't.is_open = 1';
                        } else {
                            $type = 't.is_open = 1 AND (t.main_user_id =' . $param['user_id'] . ' OR t.owner_user_id like "%,' . $param['user_id'] . ',%")';
                        }
                    }
                    $where['t.ishidden'] = 0;
                    $taskList = Db::name('Task')->alias('t')
                        ->join('AdminUser u', 'u.id = t.main_user_id', 'LEFT')
                        ->join('Work w', 'w.work_id = t.work_id', 'LEFT')
                        ->field('t.task_id,t.name as task_name,t.main_user_id,t.description,t.create_user_id,t.is_top,t.work_id,t.lable_id,t.priority,t.update_time,t.start_time,t.stop_time,t.status,t.pid,t.create_time,t.owner_user_id,u.realname as main_user_name,u.thumb_img,w.name as work_name')
                        ->where($where)
                        ->where($type)
                        ->where($map)
                        ->order('t.task_id desc')
                        ->select();
                }
            }
        }
        foreach ($taskList as $key => $value) {
            $taskList[$key]['work'] = '';
            if ($param['work_id'] != 0) {
                $work = db('work_task_class')->where('class_id', $value['class_id'])->find();
                $taskList[$key]['work'] = $work['name'];
            }
            if ($param['is_top'] != 0) {
                switch ($value['is_top']) {
                    case 0:
                        $taskList[$key]['top'] = '收件箱';
                        break;
                    case 1:
                        $taskList[$key]['top'] = '今天要做';
                        break;
                    case 2:
                        $taskList[$key]['top'] = '下一步要做';
                        break;
                    case 3:
                        $taskList[$key]['top'] = '以后要做';
                        break;
                }
            }
            //创建时间
            $taskList[$key]['create_time'] = $value['create_time'] ? date('Y-m-d H:i:s', $value['create_time']) : '';
            //开始时间
            $taskList[$key]['start_time'] = $value['start_time'] ? date('Y-m-d H:i:s', $value['start_time']) : '';
            //结束时间
            $taskList[$key]['stop_time'] = $value['stop_time'] ? date('Y-m-d H:i:s', $value['stop_time']) : '';
            //优先级
            switch ($value['priority']) {
                case 0:
                    $taskList[$key]['priority'] = '无';
                    break;
                case 1:
                    $taskList[$key]['priority'] = '低';
                    break;
                case 2:
                    $taskList[$key]['priority'] = '中';
                    break;
                case 3:
                    $taskList[$key]['priority'] = '高';
                    break;
            }
            $is_end = 0;
            $create_user = db('admin_user')->where('id', $value['create_user_id'])->find();
            $taskList[$key]['create_user_name'] = $create_user['realname'];
            $relationArr = $recordModel->getListByRelationId('task', $value['task_id']);
            $lableArr = $recordModel->getListByLableId('task', $value['task_id']);
            $taskList[$key]['owner_user_name'] = arrayToString(array_column($userModel->getListByStr($value['owner_user_id']), 'realname'));
            $taskList[$key]['work_name'] = arrayToString(array_column($lableArr['lable'], 'name')) . ' ';
            $taskList[$key]['relation'] = arrayToString(array_column($relationArr['businessList'], 'name')) . ' ' .
                arrayToString(array_column($relationArr['contactsList'], 'name')) . ' ' .
                arrayToString(array_column($relationArr['contractList'], 'name')) . ' ' .
                arrayToString(array_column($relationArr['customerList'], 'name'));
        }
        return $taskList;
    }
    
    /**
     * 任务导出
     * @param $param
     */
    public function excelExport($param)
    {
        
        $data = $this->getDataList($param);
        $excelModel = new \app\admin\model\Excel();
        if ($param['work_id'] != 0) {
            $file_name = 'work_task';
            $title = '项目任务信息';
            $field_list = [
                '0' => ['name' => '任务名称', 'field' => 'task_name'],
                '1' => ['name' => '任务描述', 'field' => 'description'],
                '2' => ['name' => '负责人', 'field' => 'main_user_name'],
                '3' => ['name' => '开始时间', 'field' => 'start_time'],
                '4' => ['name' => '结束时间', 'field' => 'stop_time'],
                '5' => ['name' => '标签', 'field' => 'work_name'],
                '6' => ['name' => '参与人', 'field' => 'owner_user_name'],
                '7' => ['name' => '优先级', 'field' => 'priority'],
                '8' => ['name' => '创建人', 'field' => 'create_user_name'],
                '9' => ['name' => '创建时间', 'field' => 'create_time'],
                '10' => ['name' => '所在任务列表', 'field' => 'work'],
                '11' => ['name' => '关联业务', 'field' => 'relation'],
            ];
        } elseif ($param['is_top'] != 0) {
            $file_name = 'oaTask';
            $title = '项目任务信息';
            $field_list = [
                '0' => ['name' => '任务名称', 'field' => 'task_name'],
                '1' => ['name' => '任务描述', 'field' => 'description'],
                '2' => ['name' => '负责人', 'field' => 'main_user_name'],
                '3' => ['name' => '开始时间', 'field' => 'start_time'],
                '4' => ['name' => '结束时间', 'field' => 'stop_time'],
                '5' => ['name' => '标签', 'field' => 'work_name'],
                '6' => ['name' => '参与人', 'field' => 'owner_user_name'],
                '7' => ['name' => '优先级', 'field' => 'priority'],
                '8' => ['name' => '创建人', 'field' => 'create_user_name'],
                '9' => ['name' => '创建时间', 'field' => 'create_time'],
                '10' => ['name' => '所在工作台', 'field' => 'top'],
                '11' => ['name' => '关联业务', 'field' => 'relation'],
            ];
        } else {
            $file_name = 'oa_task';
            $title = '办公任务信息';
            $field_list = [
                '0' => ['name' => '任务名称', 'field' => 'task_name'],
                '1' => ['name' => '任务描述', 'field' => 'description'],
                '2' => ['name' => '负责人', 'field' => 'main_user_name'],
                '3' => ['name' => '开始时间', 'field' => 'start_time'],
                '4' => ['name' => '结束时间', 'field' => 'stop_time'],
                '5' => ['name' => '标签', 'field' => 'work_name'],
                '6' => ['name' => '参与人', 'field' => 'owner_user_name'],
                '7' => ['name' => '优先级', 'field' => 'priority'],
                '8' => ['name' => '创建人', 'field' => 'create_user_name'],
                '9' => ['name' => '创建时间', 'field' => 'create_time'],
                '10' => ['name' => '关联业务', 'field' => 'relation'],
            ];
        }
        return $excelModel->taskExportCsv($file_name, $field_list, $title, $data);
    }
    
    public function where($param)
    {
        $taskModel = new TaskModel();
        $mep = [];
        //权限项目判断
        $workModel = new WorkModel();
        $userModel = new \app\admin\model\User();
        $work_id = $param['work_id'] ?: '';
        
        if ($param['main_user_id']) {
            $map['t.main_user_id'] = ['in', $param['main_user_id']];
        }
        
        //截止时间
        if ($param['stop_time_type']) {
            if ($param['stop_time_type'] == '5') { //没有截至日期
                $map['t.stop_time'] = '0';
            } elseif ($param['stop_time_type'] == '6') { //延期的
                $map['t.stop_time'] = ['between', [1, time()]];
                $map['t.status'] = 1;
            } elseif ($param['stop_time_type'] == '7') { //今日更新
                $timeAry = getTimeByType('today');
                $map['t.update_time'] = ['between', [$timeAry[0], $timeAry[1]]];
            } else {
                switch ($param['stop_time_type']) {
                    case '1': //今天到期
                        $timeAry = getTimeByType('today');
                        break;
                    case '2': //明天到期
                        $temp = getTimeByType('today');
                        $timeAry[0] = $temp[1];
                        $timeAry[1] = $temp[1] + 3600 * 24;
                        break;
                    case '3': //一周内到期
                        $timeAry = getTimeByType('week');
                        break;
                    case '4': //一月内到期
                        $timeAry = getTimeByType('month');
                        break;
                    default:
                        break;
                }
                $map['t.stop_time'] = ['between', [$timeAry[0], $timeAry[1]]];
            }
        }
        
        if ($param['lable_id']) {
            $taskIds = [];
            $task_ids = [];
            foreach ($param['lable_id'] as $v) {
                $task_id = [];
                $lableWhere = [];
                $lableWhere['lable_id'] = ['like', '%,' . $v . ',%'];
                $lableWhere['work_id'] = $work_id;
                $lableWhere['status'] = ['in', ['1', '5']];
                $lableWhere['ishidden'] = 0;
                $lableWhere['pid'] = 0;
                $lableWhere['is_archive'] = 0;
                
                $task_id = $taskModel->where($lableWhere)->column('task_id');
                if ($task_id && $task_ids) {
                    $task_ids = array_unique(array_filter(array_merge($task_ids, $task_id)));
                } elseif ($task_id) {
                    $task_ids = $task_id;
                }
            }
            $map['t.task_id'] = ['in', $task_ids];
        } else {
            
            $map['t.task_id'] = $work_id;
        }
        return $map;
    }
    
    /**
     * 获取截止日期参数
     * @param $type
     * @return array|string
     */
    private function getTimeParam($type)
    {
        $result = [];
        
        # 今天
        if ($type == 1) {
            $result = '(task.stop_time > 0 AND task.stop_time <= ' . strtotime(date('Y-m-d 23:59:59')) . ')';
        }
        
        # 明天
        if ($type == 2) {
            $tomorrow = date("Y-m-d 23:59:59", strtotime("+1 day"));
            $result = '(task.stop_time > 0 AND task.stop_time <= ' . strtotime($tomorrow) . ')';
        }
        
        # 本周
        if ($type == 3) {
            $week = mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y"));
            $result = '(task.stop_time > 0 AND task.stop_time <= ' . $week . ')';
        }
        
        # 本月
        if ($type == 4) {
            $month = mktime(23, 59, 59, date("m"), date("t"), date("Y"));
            $result = '(task.stop_time > 0 AND task.stop_time <= ' . $month . ')';
        }
        
        # 未设置截止日期
        if ($type == 5) {
            $result = $result = '(task.stop_time = 0)';;
        }
        
        # 已延期
        if ($type == 6) {
            $result = '(task.status = 2 OR task.stop_time >= ' . time() . ')';
        }
        
        # 今日更新
        if ($type == 7) {
            $result = '(task.update_time >= ' . strtotime(date('Y-m-d 00:00:00')) . ' AND task.update_time <= ' . strtotime(date('Y-m-d 23:59:59')) . ')';
        }
        
        return $result;
    }
    
}