<?php
// +----------------------------------------------------------------------
// | Description: 任务
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use app\admin\model\Common;
use app\admin\model\Message;
use app\admin\model\User as UserModel;
use app\admin\model\Structure as StructureModel;
use app\admin\model\Comment as CommentModel;
use app\work\model\WorkLog as LogModel;
use app\work\model\WorkLable as lableModel;
use app\work\model\WorkClass as classModel;
use com\verify\HonrayVerify;
use think\Validate;
use think\Cache;

class Task extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
    protected $name = 'task';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $autoWriteTimestamp = true;
    protected $insert = [
        'status' => 1,
    ];

    /**
     * 项目下任务列表(看板视图)
     *
     * @param $request
     * @param $user_id
     * @return array|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDataList($request, $user_id)
    {
        //权限项目判断
        $workModel = model('Work');
        $userModel = new \app\admin\model\User();
        $work_id = $request['work_id'];
        $ret = $workModel->checkWork($work_id, $user_id);
        if (!$ret) {
            $this->error = $workModel->getError();
            return false;
        }
        $classModel = model('WorkClass');
        //删除还原的任务，归类至未分组列表下，此列表不可拖拽编辑
        if ($this->where(['class_id' => 0, 'ishidden' => 0, 'work_id' => $work_id])->find()) {
            $classArr = ['0' => ['name' => '未分组', 'class_id' => 0]];
        }
        $classList = $classModel->getDataList($work_id);
        if ($classArr && $classList['list']) {
            $newList = array_merge($classArr, $classList['list']);
        } elseif ($classArr) {
            $newList = $classArr;
        } else {
            $newList = $classList['list'];
        }

        if ($request['main_user_id']) {
            $map['main_user_id'] = ['in', $request['main_user_id']];
        }
        //截止时间
        if ($request['stop_time_type']) {
            if ($request['stop_time_type'] == '5') { //没有截至日期
                $map['stop_time'] = '0';
            } elseif ($request['stop_time_type'] == '6') { //延期的
                $map['stop_time'] = ['between', [1, time()]];
                $map['status'] = 1;
            } elseif ($request['stop_time_type'] == '7') { //今日更新
                $timeAry = getTimeByType('today');
                $map['update_time'] = ['between', [$timeAry[0], $timeAry[1]]];
            } else {
                switch ($request['stop_time_type']) {
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
                $map['stop_time'] = ['between', [$timeAry[0], $timeAry[1]]];
            }
        }
        if ($request['lable_id']) {
            $taskIds = [];
            $task_ids = [];
            foreach ($request['lable_id'] as $v) {
                $task_id = [];
                $lableWhere = [];
                $lableWhere['lable_id'] = ['like', '%,' . $v . ',%'];
                $lableWhere['work_id'] = $work_id;
                $lableWhere['status'] = ['in', ['1', '5']];
                $lableWhere['ishidden'] = 0;
                $lableWhere['pid'] = 0;
                $lableWhere['is_archive'] = 0;
                $task_id = $this->where($lableWhere)->column('task_id');
                if ($task_id && $task_ids) {
                    $task_ids = array_unique(array_filter(array_merge($task_ids, $task_id)));
                } elseif ($task_id) {
                    $task_ids = $task_id;
                }
            }
            $map['task_id'] = ['in', $task_ids];
        }
        $data = array();
        foreach ($newList as $key => $value) {
            $data[$key]['class_id'] = $value['class_id'] ?: -1;
            $data[$key]['class_name'] = $value['name'];

            $map['status'] = $map['status'] ?: ['in', ['1', '5']];
            $map['ishidden'] = 0;
            $map['work_id'] = $request['work_id'];
            $map['class_id'] = $value['class_id'];
            $map['pid'] = 0;
            $map['is_archive'] = 0;

            $taskList = [];
            $resTaskList = $this->getTaskList($map);
            $data[$key]['count'] = $resTaskList['count'];
            $data[$key]['list'] = $resTaskList['list'];
        }
        return $data;
    }

    /**
     * 项目下任务列表(负责人视图)
     *
     * @param $request
     * @param $user_id
     * @return array|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOwnerTaskList($request, $user_id)
    {
        //权限项目判断
        $workModel = model('Work');
        $userModel = new \app\admin\model\User();
        $work_id = $request['work_id'];
        $ret = $workModel->checkWork($work_id, $user_id);
        if (!$ret) {
            $this->error = $workModel->getError();
            return false;
        }

        $newList = db('task')->alias('task')->join('__ADMIN_USER__ user', 'user.id = task.main_user_id', 'LEFT')
                    ->field(['user.id', 'user.realname'])->where('work_id', $work_id)->group('task.main_user_id')->select();

        if ($request['main_user_id']) {
            $map['main_user_id'] = ['in', $request['main_user_id']];
        }
        //截止时间
        if ($request['stop_time_type']) {
            if ($request['stop_time_type'] == '5') { //没有截至日期
                $map['stop_time'] = '0';
            } elseif ($request['stop_time_type'] == '6') { //延期的
                $map['stop_time'] = ['between', [1, time()]];
                $map['status'] = 1;
            } elseif ($request['stop_time_type'] == '7') { //今日更新
                $timeAry = getTimeByType('today');
                $map['update_time'] = ['between', [$timeAry[0], $timeAry[1]]];
            } else {
                switch ($request['stop_time_type']) {
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
                $map['stop_time'] = ['between', [$timeAry[0], $timeAry[1]]];
            }
        }
        if ($request['lable_id']) {
            $taskIds = [];
            $task_ids = [];
            foreach ($request['lable_id'] as $v) {
                $task_id = [];
                $lableWhere = [];
                $lableWhere['lable_id'] = ['like', '%,' . $v . ',%'];
                $lableWhere['work_id'] = $work_id;
                $lableWhere['status'] = ['in', ['1', '5']];
                $lableWhere['ishidden'] = 0;
                $lableWhere['pid'] = 0;
                $lableWhere['is_archive'] = 0;
                $task_id = $this->where($lableWhere)->column('task_id');
                if ($task_id && $task_ids) {
                    $task_ids = array_unique(array_filter(array_merge($task_ids, $task_id)));
                } elseif ($task_id) {
                    $task_ids = $task_id;
                }
            }
            $map['task_id'] = ['in', $task_ids];
        }
        $data = array();
        foreach ($newList as $key => $value) {
            $data[$key]['class_id'] = $value['id'];
            $data[$key]['class_name'] = $value['realname'];

            $map['status'] = $map['status'] ?: ['in', ['1', '5']];
            $map['ishidden'] = 0;
            $map['work_id'] = $request['work_id'];
            $map['main_user_id'] = $value['id'];
            $map['pid'] = 0;
            $map['is_archive'] = 0;

            $taskList = [];
            $resTaskList = $this->getTaskList($map);
            $data[$key]['count'] = $resTaskList['count'];
            $data[$key]['list'] = $resTaskList['list'];
        }
        return $data;
    }

    /**
     * 根据任务ID 获取操作记录
     * @return
     * @author yykun
     */
    public function getTaskLogList($param)
    {
        $list = Db::name('WorkTaskLog')->alias('l')
            ->join('AdminUser u', 'u.id = l.user_id', 'LEFT')
            ->field('l.*,u.realname,u.thumb_img')
            ->where('l.task_id =' . $param['task_id'])
            ->order('l.log_id desc')
            ->select();
        foreach ($list as $key => $value) {
            $list[$key]['thumb_img']   = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
            $list[$key]['create_time'] = !empty($value['create_time']) ? date('Y-m-d H:i:s', $value['create_time']) : null;
        }
        return $list ?: [];
    }

    /**
     * 根据主键获取详情
     * @return
     * @author yykun
     */
    public function getDataById($id = '', $userInfo)
    {
        //读取参与人
        $userModel = new UserModel();
        $structModel = new StructureModel();
        $recordModel = new \app\admin\model\Record();
        $taskInfo = $this->where(['task_id' => $id])->find();
        if (!$taskInfo) {
            $this->error = '任务不存在或已删除';
            return false;
        }

        # 日期格式
        $taskInfo['start_time'] = !empty($taskInfo['start_time']) ? date('Y-m-d', $taskInfo['start_time']) : null;
        $taskInfo['stop_time'] = !empty($taskInfo['stop_time']) ? date('Y-m-d', $taskInfo['stop_time']) : null;
        $taskInfo['hidden_time'] = !empty($taskInfo['hidden_time']) ? date('Y-m-d H:i:s', $taskInfo['hidden_time']) : null;

        $userlist = $userModel->getDataByStr($taskInfo['owner_user_id']);
        $taskInfo['owner_list'] = $userlist ?: array();

        $workInfo = Db::name('Work')->where(['work_id' => $taskInfo['work_id']])->find();
        $taskInfo['work_name'] = $workInfo['name'] ?: '';

        //读取部门
        $structList = $structModel->getDataByStr($taskInfo['structure_ids']);
        $taskInfo['struct_list'] = $structList ?: array();

        //负责人
        $mainData = [];
        if ($taskInfo['main_user_id']) {
            $mainData = $userModel->getDataById($taskInfo['main_user_id']);
        }
        $taskInfo['main_user_name'] = !empty($mainData['realname']) ? $mainData['realname'] : '';
        $taskInfo['main_user_img'] = !empty($mainData['thumb_img']) ? $mainData['thumb_img'] : '';
        $taskInfo['main_user'] = [
            'id' => !empty($taskInfo['main_user_id']) ? $taskInfo['main_user_id'] : 0,
            'realname' => $taskInfo['main_user_name'],
            'img' => $taskInfo['main_user_img']
        ];
        $lablelist = [];
        if ($taskInfo['lable_id']) {
            $lableModel = new \app\work\model\WorkLable();
            $lablelist = $lableModel->getDataByStr($taskInfo['lable_id']);
        }
        $taskInfo['lable_list'] = $lablelist ?: array();

        $commonmodel = new \app\admin\model\Comment();
        $param['type_id'] = $taskInfo['task_id'];
        $param['type'] = 'task';
        $taskInfo['replyList'] = $commonmodel->read($param);
        $subTaskList = $this->alias('t')
            ->join('AdminUser u', 'u.id = t.main_user_id', 'LEFT')
            ->field('t.task_id,t.pid,t.name,t.main_user_id,t.stop_time,t.status,t.class_id,u.id as main_user_id,u.realname,u.thumb_img')
            ->where(' t.ishidden = 0 and ( t.status=1 or t.status=5 ) and t.pid =' . $id)
            ->select();
        $complete = 0;
        foreach ($subTaskList as $key => $value) {
            $subTaskList[$key]['thumb_img'] = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
            $subTaskList[$key]['stop_time'] = !empty($value['stop_time']) ? date('Y-m-d H:i:s', $value['stop_time']) : null;
            if ($value['status'] == 5) ++$complete;
        }
        # 子任务
        $taskInfo['subTaskList'] = $subTaskList;
        # 子任务完成总数
        $taskInfo['subTaskComplete'] = $complete;
        # 附件
        $taskInfo['fileList'] = $this->getTaskFile($id);
        //相关业务
        $relationArr = $recordModel->getListByRelationId('task', $id);
        $taskInfo['businessList'] = $relationArr['businessList'];
        $taskInfo['contactsList'] = $relationArr['contactsList'];
        $taskInfo['contractList'] = $relationArr['contractList'];
        $taskInfo['customerList'] = $relationArr['customerList'];
        if (!strripos($taskInfo['create_time'], '-')) {
            $taskInfo['create_time'] = date('Y-m-d H:i:s', $taskInfo['create_time']);
        }
        if (!strripos($taskInfo['update_time'], '-')) {
            $taskInfo['update_time'] = date('Y-m-d H:i:s', $taskInfo['update_time']);
        }
        $taskInfo['start_time'] = !empty($taskInfo['start_time']) ? $taskInfo['start_time'] : null;
        $taskInfo['stop_time'] = !empty($taskInfo['stop_time']) ? $taskInfo['stop_time'] : null;
        $taskInfo['archive_time'] = !empty($taskInfo['archive_time']) ? date('Y-m-d H:i:s', $taskInfo['archive_time']) : null;

        $createUserInfo = $userModel->getDataById($taskInfo['create_user_id']);
        $createUserInfo['thumb_img'] = $createUserInfo['thumb_img'] ? getFullPath($createUserInfo['thumb_img']) : '';
        $taskInfo['create_user_info'] = $createUserInfo;
        return $taskInfo;
    }

    /**
     * 获取任务附件列表
     *
     * @param $taskId
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getTaskFile($taskId)
    {
        # 查询文件IDS
        $fileIds = Db::name('work_task_file')->where('task_id', $taskId)->column('file_id');

        # 查询附件
        $list = Db::name('admin_file')->whereIn('file_id', $fileIds)->select();

        foreach ($list as $key => $value) {
            $list[$key]['size'] = format_bytes($value['size']); //字节转换
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['ext'] = getExtension($value['save_name']);
            $list[$key]['file_path'] = getFullPath($value['file_path']);
            $list[$key]['file_path_thumb'] = getFullPath($value['file_path_thumb']);
        }

        return $list;
    }

    /**
     * 创建任务
     * @return
     * @author yykun
     */
    public function createTask($param)
    {
        # 子任务
        $subtask = !empty($param['subtask']) ? $param['subtask'] : [];
        unset($param['subtask']);

        # 附件
        $files = !empty($param['files']) ? $param['files'] : '';
        unset($param['files']);

        $param['status'] = 1;
        $rdata = [];
        $rdata['customer_ids'] = !empty($param['customer_ids']) ? arrayToString($param['customer_ids']) : '';
        $rdata['contacts_ids'] = !empty($param['contacts_ids']) ? arrayToString($param['contacts_ids']) : '';
        $rdata['business_ids'] = !empty($param['business_ids']) ? arrayToString($param['business_ids']) : '';
        $rdata['contract_ids'] = !empty($param['contract_ids']) ? arrayToString($param['contract_ids']) : '';
        $arr = ['customer_ids', 'contacts_ids', 'business_ids', 'contract_ids'];
        foreach ($arr as $value) {
            unset($param[$value]);
        }
        $main_user_id = $param['main_user_id'] ?: $param['create_user_id'];

        $param['main_user_id'] = $main_user_id; //负责人
        $param['start_time']   = !empty($param['start_time']) ? strtotime($param['start_time']) : 0;
        $param['stop_time']    = !empty($param['stop_time'])  ? strtotime($param['stop_time'])  : 0;
        if (!empty($param['stop_time']) && $param['start_time'] > $param['stop_time']) {
            $this->error = '截止时间不能在开始时间之前';
            return false;
        }
        if ((!empty($param['start_time']) || !empty($param['stop_time'])) && $param['start_time'] == $param['stop_time']) {
            $param['stop_time'] = $param['start_time'] + 86399;
        }
        $this->data($param)->allowField(true)->save();
        $task_id = $this->task_id;
        if ($task_id) {
            $rdata['status'] = 1;
            $rdata['create_time'] = time();
            $rdata['task_id'] = $task_id;
            Db::name('TaskRelation')->insert($rdata);

            if (!$param['pid']) {
                $taskLog = new LogModel();
                $datalog['name'] = $param['name'];
                $datalog['user_id'] = $param['create_user_id'];
                $datalog['task_id'] = $task_id;
                $datalog['work_id'] = $param['work_id'] ?: '';
                $ret = $taskLog->newTaskLog($datalog);
                //操作日志
                actionLog($task_id, '', '', '新建了任务');
                //抄送站内信
//                (new Message())->send(
//                    Message::TASK_ALLOCATION,
//                    [
//                        'title' => $param['name'],
//                        'action_id' => $task_id
//                    ],
//                    $param['owner_user_id']
//                );
            }

            # 添加活动记录
            if (!empty($rdata['customer_ids']) || !empty($rdata['contacts_ids']) || !empty($rdata['business_ids']) || !empty($rdata['contract_ids'])) {
                Db::name('crm_activity')->insert([
                    'type' => 2,
                    'activity_type' => 11,
                    'activity_type_id' => $task_id,
                    'content' => $param['name'],
                    'create_user_id' => $param['create_user_id'],
                    'update_time' => time(),
                    'create_time' => time(),
                    'customer_ids' => !empty($rdata['customer_ids']) ? trim($rdata['customer_ids'], ',') : '',
                    'contacts_ids' => !empty($rdata['contacts_ids']) ? trim($rdata['contacts_ids'], ',') : '',
                    'business_ids' => !empty($rdata['business_ids']) ? trim($rdata['business_ids'], ',') : '',
                    'contract_ids' => !empty($rdata['contract_ids']) ? trim($rdata['contract_ids'], ',') : ''
                ]);
            }

            # 添加附件
            if (!empty($files)) {
                $fileData = [];
                foreach ($files as $key => $value) {
                    $fileData[] = ['file_id' => $value, 'task_id' => $task_id];
                }
                db('work_task_file')->insertAll($fileData);
            }

            # 添加子任务
            if (!empty($subtask)) {
                $subtaskData = [];
                foreach ($subtask as $key => $value) {
                    $stopTime    = !empty($value['stop_time'])     ? strtotime($value['stop_time']) : 0;
                    $ownerUserId = !empty($value['owner_user_id']) ? $value['owner_user_id']        : $param['create_user_id'];

                    $subtaskData[] = [
                        'name'           => $value['name'],
                        'create_user_id' => $param['create_user_id'],
                        'main_user_id'   => $ownerUserId,
                        'owner_user_id'  => $ownerUserId,
                        'create_time'    => time(),
                        'update_time'    => time(),
                        'pid'            => $task_id,
                        'start_time'     => 0,
                        'stop_time'      => $stopTime
                    ];
                }
                Db::name('task')->insertAll($subtaskData);
            }

            return $task_id;
        } else {
            $this->error = '添加失败';
            return false;
        }
    }

    /**
     * 编辑任务
     *
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateDetTask($param)
    {
        $LogModel = new LogModel();
        $userModel = new UserModel();
        $lableModel = new lableModel();
        $StructureModel = new StructureModel();
        $createUserId = $param['create_user_id'];
        $type = $param['type'] ?: '';
        if (!$param['task_id']) {
            $this->error = '参数错误！';
            return false;
        }
        //关联业务
        if (isset($param['customer_ids']) && !empty($param['customer_ids'])) $rdata['customer_ids'] = arrayToString($param['customer_ids']);
        if (isset($param['contacts_ids']) && !empty($param['contacts_ids'])) $rdata['contacts_ids'] = arrayToString($param['contacts_ids']);
        if (isset($param['business_ids']) && !empty($param['business_ids'])) $rdata['business_ids'] = arrayToString($param['business_ids']);
        if (isset($param['contract_ids']) && !empty($param['contract_ids'])) $rdata['contract_ids'] = arrayToString($param['contract_ids']);

        $rdata['task_id'] = $param['task_id'];
        $arr = ['customer_ids', 'contacts_ids', 'business_ids', 'contract_ids'];
        foreach ($arr as $value) {
            unset($param[$value]);
        }

        # 调整时间参数格式
        if (!empty($param['stop_time']))  $param['stop_time']  = strtotime($param['stop_time']);
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time']);

        if (empty($param['stop_time'])  && $param['stop_time'] === null)  unset($param['stop_time']);
        if (empty($param['start_time']) && $param['start_time'] === null) unset($param['start_time']);

        $data = array();
        $taskInfo = $this->get($param['task_id']);
        $taskInfo = json_decode(json_encode($taskInfo), true);
        $data['type'] = $param['type'];
        $data['before'] = $taskInfo[$param['type']] ? $taskInfo[$param['type']] : '空';

        switch ($type) {
            case 'name' :
                $data['after'] = $param['name'];
                break;
            case 'stop_time' :
                if ($param['stop_time']) {
                    $data['after'] = date("Y-m-d", $param['stop_time']);
                } else {
                    $data['after'] = '无';
                }
                break;
            case 'start_time' :
                if (!empty($param['start_time'])) {
                    $data['after'] = date("Y-m-d", $param['stop_time']);
                } else {
                    $data['after'] = '无';
                }
                break;
            case 'class_id'    :
                //类型修改
                $classModel = model('WorkClass');
                $taskInfo = $classModel->getDataById($param['class_id']);
                $data['after'] = $taskInfo['name'];
                break;
            case 'lable_id_add' :
                //标签添加
                $lable = $lableModel->getNameByIds($param['lable_id_add']);
                if ($taskInfo['lable_id'] && $param['lable_id_add']) {
                    $param['lable_id_add'] = array_unique(array_merge(stringToArray($taskInfo['lable_id']), $param['lable_id_add']));
                }
                $param['lable_id'] = arrayToString($param['lable_id_add']);
                $data['after'] = $lable ? implode(',', $lable) : '';
                unset($param['lable_id_add']);
                break;
            case 'lable_id_del' :
                //标签删除
                $lable = $lableModel->getNameByIds($param['lable_id_del']);
                if ($param['lable_id_del']) {
                    $lable_id = array_unique(array_diff(stringToArray($taskInfo['lable_id']), $param['lable_id_del']));
                    $param['lable_id'] = arrayToString($lable_id);
                } else {
                    $param['lable_id'] = $taskInfo['lable_id'];
                }
                $data['after'] = $lable ? implode(',', $lable) : '';
                unset($param['lable_id_del']);
                break;
            case 'structure_id_del' :
                //删除参与部门
                $structuredet = $StructureModel->getDataById($param['structure_id']);
                $param['structure_ids'] = str_replace(',' . $param['structure_id_del'] . ',', ',', $taskInfo['structure_ids']); //删除
                $data['after'] = $structuredet['name'];
                unset($param['structure_id_del']);
                break;
            case 'structure_id_add' :
                //添加参与部门
                $structuredet = $StructureModel->getDataById($param['owner_userid_add']);
                if ($taskInfo['structure_ids']) {
                    $param['structure_ids'] = $taskInfo['structure_ids'] . $param['structure_id_add'] . ','; //追加
                } else {
                    $param['structure_ids'] = ',' . $param['structure_id_add'] . ','; //首次添加
                }
                $data['after'] = $structuredet['name'];
                unset($param['structure_id_add']);
                break;
            case 'owner_userid_del' :
                //删除参与成员
                $userdet = $userModel->getDataById($param['owner_userid_del']);
                $param['owner_user_id'] = str_replace(',' . $param['owner_userid_del'] . ',', ',', $taskInfo['owner_user_id']); //删除
                $data['after'] = $userdet['realname'];
                unset($param['owner_userid_del']);
                break;
            case 'owner_userid_add' :
                //添加参与成员
                $userdet = $userModel->getDataById($param['owner_userid_add']);
                if ($taskInfo['owner_user_id']) {
                    $param['owner_user_id'] = $taskInfo['owner_user_id'] . $param['owner_userid_add'] . ','; //追加
                } else {
                    $param['owner_user_id'] = ',' . $param['owner_userid_add'] . ','; //首次添加
                }
                $data['after'] = $userdet['realname'];
                unset($param['owner_userid_add']);
                break;
            case 'main_user_id' :
                //设置负责人
                $userdet = $userModel->getDataById($param['main_user_id']);
                $data['after'] = '设定' . $userdet['realname'] . '为主要负责人！';
//                (new Message())->send(
//                    Message::TASK_ALLOCATION,
//                    [
//                        'title' => $taskInfo['name'],
//                        'action_id' => $param['task_id']
//                    ],
//                    $param['main_user_id']
//                );
                break;
        }

        $param['update_time'] = time();
        $data['work_id'] = $param['work_id'];
        $data['task_id'] = $param['task_id'];
        $data['user_id'] = $param['create_user_id'];
        unset($param['type']);
        unset($param['create_user_id']);
        $flag = $this->where(['task_id' => $param['task_id']])->update($param);

        if ($flag || count($rdata)) {
            if ($param['owner_user_id']) {
                $this->where(['task_id' => $param['task_id']])->setField('owner_user_id', $param['owner_user_id']);
            }
            if (!$param['pid']) {
                $LogModel = new LogModel();
                $taskInfo = $LogModel->taskLogAdd($data);
                actionLog($param['task_id'], $param['owner_user_id'], $param['structure_ids'], '修改了任务');
                $resRelation = Db::name('TaskRelation')->where(['task_id' => $param['task_id']])->find();
                if ($resRelation) {
                    Db::name('TaskRelation')->where(['task_id' => $param['task_id']])->update($rdata); //更新关联关系
                } else {
                    $rdata['create_time'] = time();
                    $rdata['status'] = 1;
                    Db::name('TaskRelation')->insert($rdata); //更新关联关系
                }
            }
            # 删除活动记录
            Db::name('crm_activity')->where(['activity_type' => 8, 'activity_type_id' => $param['task_id']])->delete();
            # 添加活动记录
            if (!empty($rdata['customer_ids']) || !empty($rdata['contacts_ids']) || !empty($rdata['business_ids']) || !empty($rdata['contract_ids'])) {
                Db::name('crm_activity')->insert([
                    'type'             => 2,
                    'activity_type'    => 11,
                    'activity_type_id' => $param['task_id'],
                    'content'          => $param['name'],
                    'create_user_id'   => $createUserId,
                    'update_time'      => time(),
                    'create_time'      => time(),
                    'customer_ids'     => !empty($rdata['customer_ids']) ? trim($rdata['customer_ids'], ',') : '',
                    'contacts_ids'     => !empty($rdata['contacts_ids']) ? trim($rdata['contacts_ids'], ',') : '',
                    'business_ids'     => !empty($rdata['business_ids']) ? trim($rdata['business_ids'], ',') : '',
                    'contract_ids'     => !empty($rdata['contract_ids']) ? trim($rdata['contract_ids'], ',') : ''
                ]);
            }
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }

    //根据IDs获取数组
    public function getDataByStr($idstr)
    {
        $idArr = stringToArray($idstr);

        if (!$idArr) {
            return [];
        }
        $list = db('work_task_lable')->where(['lable_id' => ['in', $idArr]])->select();
        return $list;
    }

    /**
     * 任务统计不同状态
     * @param
     * @return
     * @author yykun
     */
    public function getCount($status = 0)
    {
        $map = array();
        if ($status > 0) {
            $map['status'] = $status;
        }
        $count = $this->where($map)->count();
        return $count ?: 0;
    }

    /**
     * 获取某一月份任务列表
     * @param
     * @return
     * @author yykun
     */
    public function getDateList($param)
    {
        $start_time = $param['start_time'];
        $stop_time = $param['stop_time'];
        $user_id = $param['user_id'];
        // $date_list = dateList($start_time, $stop_time, 1);
        $where = [];
        $where['ishidden'] = 0;
        $where['is_archive'] = 0;
        $where['status'] = 1;
        $where['pid'] = 0;
        $str = ',' . $user_id . ',';
        $whereStr = ' ( create_user_id = ' . $user_id . ' or ( owner_user_id like "%' . $str . '%") or ( main_user_id = ' . $user_id . ' ) )';
        $whereDate = '( stop_time > 0 and stop_time between ' . $start_time . ' and ' . $stop_time . ' ) or ( update_time between ' . $start_time . ' and ' . $stop_time . ' )';
        $list = db('task')
            ->where($where)
            ->where($whereStr)
            ->where($whereDate)
            ->field('task_id,name,priority,start_time,stop_time,priority,update_time')
            ->select();
        return $list ?: [];
    }

    /**
     * 删除任务
     * @param
     * @return
     * @author yykun
     */
    public function delTaskById($param)
    {
        if (!$param['task_id']) {
            $this->error = '参数错误';
            return false;
        }
        $taskInfo = $this->get($param['task_id']);
        if (!$taskInfo) {
            $this->error = '数据不存在或已删除';
            return false;
        }
        $map['task_id'] = $param['task_id'];
        $temp['ishidden'] = 1;
        $temp['hidden_time'] = time();
        $flag = $this->where($map)->update($temp);
        if ($flag) {
            if (!$taskInfo['pid']) {
                actionLog($taskInfo['task_id'], $taskInfo['owner_user_id'], $taskInfo['structure_ids'], '删除了任务');
            }
            return true;
        } else {
            $this->error = '删除失败';
            return false;
        }
    }

    /**
     * 归档任务
     * @param
     * @return
     * @author yykun
     */
    public function archiveData($param)
    {
        $data['is_archive'] = 1;
        $data['archive_time'] = time();
        $flag = $this->where(['task_id' => $param['task_id']])->update($data);
        if ($flag) {
            //添加归档日志
            actionLog($param['task_id'], '', '', '归档了任务');
            return true;
        } else {
            $this->error = '归档失败';
            return false;
        }
    }

    /**
     * 归档任务恢复
     * @param
     * @return
     * @author yykun
     */
    public function recover($param)
    {
        $flag = $this->where(['task_id' => $param['task_id']])->setField('is_archive', 0);
        if ($flag) {
            //添加日志
            actionLog($param['task_id'], '', '', '恢复归档任务');
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }

    /**
     * 任务权限判断
     * @param
     * @return
     * @author Michael_xu
     */
    public function checkTask($task_id, $userInfo)
    {
        $userModel = new \app\admin\model\User();
        $taskInfo = $this->get($task_id);
        if (!$taskInfo) {
            $this->error = '该任务不存在或已删除';
            return false;
        }
        $user_id = $userInfo['id'];
        $structure_id = $userInfo['structure_id'];
        $adminTypes = adminGroupTypes($user_id);
        if (in_array(1, $adminTypes) || in_array(7, $adminTypes)) {
            return true;
        }
        if (($taskInfo['create_user_id'] == $user_id) || ($taskInfo['main_user_id'] == $user_id) || in_array($user_id, stringToArray($taskInfo['owner_user_id'])) || in_array($structure_id, stringToArray($taskInfo['structure_ids']))) {
            return true;
        }
        $workInfo = db('work')->where(['work_id' => $taskInfo['work_id']])->find();
        if ($taskInfo['is_open'] == 1) {
            return true;
        } else {
            //私有项目(只有项目成员可以查看)
            $workUser = db('work_user')->where(['work_id' => $taskInfo['work_id']])->column('user_id');
            if ($workUser && in_array($user_id, $workUser)) {
                return true;
            }
            return false;
        }
    }

    /**
     * 查看关联个数
     * @param
     * @return
     * @author yykun
     */
    public function getRelationCount($task_id)
    {
        $relationInfo = Db::name('TaskRelation')->where(['task_id' => $task_id])->find();
        $count = 0;
        if ($relationInfo) {
            $count1 = count(stringToArray($relationInfo['customer_ids']));
            $count2 = count(stringToArray($relationInfo['contacts_ids']));
            $count3 = count(stringToArray($relationInfo['business_ids']));
            $count4 = count(stringToArray($relationInfo['contract_ids']));
            $count = $count1 + $count2 + $count3 + $count4;
        }
        return $count;
    }

    /**
     * 任务列表
     *
     * @param $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTaskList($request)
    {
        $search = $request['search'];
        $whereStr = $request['whereStr'] ?: [];
        $lable_id = $request['lable_id'] ?: '';
        $taskSearch = !empty($request['taskSearch']) ? $request['taskSearch'] : '';
        $isArchive = !empty($request['is_archive']) ? $request['is_archive'] : 0;
        unset($request['search']);
        unset($request['whereStr']);
        unset($request['lable_id']);
        unset($request['taskSearch']);
        $request = $this->fmtRequest($request);
        $requestMap = $request['map'] ?: [];
        $userModel = new \app\admin\model\User();
        $lableModel = new \app\work\model\WorkLable();
        $map = $requestMap;
        $map['ishidden'] = $requestMap['ishidden'] ?: 0;
        if ($search) {
            //普通筛选
            $map['name'] = ['like', '%' . $search . '%'];
        }
        $map = where_arr($map, 'work', 'task', 'index');
        if ($lable_id) {
            $map['task.lable_id'] = array('like', '%' . $lable_id . '%');
        }
        $dataCount = db('task')->alias('task')->where($map)->where($whereStr)->where($taskSearch)->count();
        $taskList = [];
        if ($dataCount) {
            $taskList = db('task')
                ->alias('task')
                ->join('AdminUser u', 'u.id = task.main_user_id', 'LEFT')
                ->join('Work w', 'w.work_id = task.work_id', 'LEFT')
                ->field('task.task_id,task.name,task.main_user_id,task.is_top,task.work_id,task.lable_id,task.priority,task.stop_time,task.status,task.pid,task.create_time,task.owner_user_id,u.realname as main_user_name,u.thumb_img,w.name as work_name,color')
                ->where($map)
                ->where($whereStr)
                ->where($taskSearch)
                ->order('task.status asc,task.order_id asc')
                ->select();
            foreach ($taskList as $key => $value) {
                if ($value['pid'] > 0) {
                    $p_det = $this->field('task_id,name')->where(['task_id' => $value['pid']])->find();
                    $taskList[$key]['pname'] = $p_det['name'];
                } else {
                    $taskList[$key]['pname'] = '';
                }
                $taskList[$key]['thumb_img'] = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
                $subcount = $this->where(['ishidden' => 0, 'status' => 1, 'pid' => $value['task_id']])->count();
                $subdonecount = $this->where(['ishidden' => 0, 'status' => 5, 'pid' => $value['task_id']])->count();
                $taskList[$key]['subcount'] = $subcount; //子任务
                $taskList[$key]['subdonecount'] = $subdonecount; //已完成子任务
                $taskList[$key]['commentcount'] = Db::name('AdminComment')->where(['type' => 'task', 'type_id' => $value['task_id']])->count(); //评论
                $taskList[$key]['filecount'] = Db::name('WorkTaskFile')->where(['task_id' => $value['task_id']])->count();
                $lableList = [];
                if ($value['lable_id']) {
                    $lableList = $lableModel->getDataByStr($value['lable_id']);
                    $taskList[$key]['lableList'] = $lableList ?: array();
                }
                $taskList[$key]['lableList'] = $lableList ?: array();
                //参与人
                //负责人信息
                $taskList[$key]['main_user'] = $value['main_user_id'] ? $userModel->getDataById($value['main_user_id']) : NULL;
                $taskList[$key]['relationCount'] = $this->getRelationCount($value['task_id']);
                $is_end = 0;
                if (!empty($value['stop_time']) && (strtotime(date('Ymd')) + 86399 > $value['stop_time'])) $is_end = 1;
                $taskList[$key]['is_end'] = $is_end;
                $taskList[$key]['checked'] = ($value['status'] == '5') ? true : false;
                $taskList[$key]['stop_time'] = !empty($value['stop_time']) ? date('Y-m-d H:i:s', $value['stop_time']) : null;
                $taskList[$key]['create_time'] = !empty($value['create_time']) ? date('Y-m-d H:i:s', $value['create_time']) : null;
            }
        }
        # 归档任务
        if (!empty($isArchive)) {
            return $taskList;
        }
        $data = [];
        $data['count'] = $dataCount;
        $data['list'] = $taskList ?: [];
        return $data;
    }

    /**
     * 项目-控制台-任务列表（新）
     *
     * @param $request
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getProjectTaskList($request, $param)
    {
        # 排序
        $order = $this->getSplicingSortParam($param);
        # 搜索
        $whereStr = $request['whereStr'] ?: [];
        $taskSearch = !empty($request['taskSearch']) ? $request['taskSearch'] : '';
        unset($request['whereStr']);
        unset($request['taskSearch']);
        $request = $this->fmtRequest($request);
        $requestMap = $request['map'] ?: [];
        $userModel = new \app\admin\model\User();
        $lableModel = new \app\work\model\WorkLable();
        $map = $requestMap;
        $map['ishidden'] = $requestMap['ishidden'] ?: 0;
        $map = where_arr($map, 'work', 'task', 'index');
        # 成员
        if (!empty($param['owner_user_id']) && is_array($param['owner_user_id'])) {
            $whereStr = '';
            foreach ($param['owner_user_id'] as $key => $value) {
                $whereStr .= '(  task.owner_user_id like "%,' . $value . ',%") OR ';
            }
            if (!empty($whereStr)) $whereStr = '(' . rtrim($whereStr, 'OR ') . ')';
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
        $dataCount = db('task')->alias('task')->where($map)->where($whereStr)->where($taskSearch)->where($timeWhere)->where($labelWhere)->count();
        $taskList = [];
        if ($dataCount) {
            $taskList = db('task')
                ->alias('task')
                ->join('AdminUser u', 'u.id = task.main_user_id', 'LEFT')
                ->join('Work w', 'w.work_id = task.work_id', 'LEFT')
                ->field('task.task_id,task.name,task.main_user_id,task.is_top,task.work_id,task.lable_id,task.priority,task.stop_time,task.status,task.pid,task.create_time,task.owner_user_id,u.realname as main_user_name,u.thumb_img,w.name as work_name,color')
                ->where($map)
                ->where($whereStr)
                ->where($taskSearch)
                ->where($timeWhere)
                ->where($labelWhere)
                ->order($order)
                ->select();

            foreach ($taskList as $key => $value) {
                if ($value['pid'] > 0) {
                    $p_det = $this->field('task_id,name')->where(['task_id' => $value['pid']])->find();
                    $taskList[$key]['pname'] = $p_det['name'];
                } else {
                    $taskList[$key]['pname'] = '';
                }
                $taskList[$key]['thumb_img'] = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
                $subcount = $this->where(['ishidden' => 0, 'status' => 1, 'pid' => $value['task_id']])->count();
                $subdonecount = $this->where(['ishidden' => 0, 'status' => 5, 'pid' => $value['task_id']])->count();
                $taskList[$key]['subcount'] = $subcount; //子任务
                $taskList[$key]['subdonecount'] = $subdonecount; //已完成子任务
                $taskList[$key]['commentcount'] = Db::name('AdminComment')->where(['type' => 'task', 'type_id' => $value['task_id']])->count(); //评论
                $taskList[$key]['filecount'] = Db::name('WorkTaskFile')->where(['task_id' => $value['task_id']])->count();
                $lableList = [];
                if ($value['lable_id']) {
                    $lableList = $lableModel->getDataByStr($value['lable_id']);
                    $taskList[$key]['lableList'] = $lableList ?: array();
                }
                $taskList[$key]['lableList'] = $lableList ?: array();
                //参与人
                //负责人信息
                $taskList[$key]['main_user'] = $value['main_user_id'] ? $userModel->getDataById($value['main_user_id']) : NULL;
                $taskList[$key]['relationCount'] = $this->getRelationCount($value['task_id']);
                $is_end = 0;
                if (!empty($value['stop_time']) && (strtotime(date('Ymd')) + 86399 > $value['stop_time'])) $is_end = 1;
                $taskList[$key]['is_end'] = $is_end;
                $taskList[$key]['checked'] = ($value['status'] == '5') ? true : false;
            }
        }
        $data = [];
        $data['count'] = $dataCount;
        $data['list'] = $taskList ?: [];
        return $data;
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

    /**
     * 拼接排序参数
     *
     * @param $param
     * @return string
     */
    private function getSplicingSortParam($param)
    {
        $result = '';

        # 排序字段映射
        $sortFieldArray = [1 => 'top_order_id', 2 => 'create_time', 3 => 'stop_time', 4 => 'update_time', 5 => 'priority'];

        # 已完成任务默认排在最后
        $completedTask = $param['completed_task'];

        # 排序方式：top_order_id按手动拖拽；create_time按最近创建；stop_time按最近截止；update_time按最近更新；priority按最高优先级；
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : 'task_id';

        # 默认是升序
        $sortValue = 'asc';

        # 除按手动拖拽以外，全部是降序
        if (in_array($sortField, [2, 3, 4, 5])) $sortValue = 'desc';

        if (!empty($completedTask) && ($completedTask != 'false' || $completedTask != false)) $result = 'task.status asc, ';

        $result .= 'task.' . $sortFieldArray[$sortField] . ' ' . $sortValue;

        return $result;
    }
}