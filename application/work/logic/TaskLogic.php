<?php
/**
 * 项目任务逻辑类
 *
 * @author qifna
 * @date 2020-12-18
 */

namespace app\work\logic;

use think\Db;

class TaskLogic
{
    public function getSearchData($param)
    {
        # 排序
        $orderField = 'create_time';
        $orderSort  = 'desc';
        if (!empty($param['sort_type']) && $param['sort_type'] == 1) { # 最近创建
            $orderField = 'create_time';
            $orderSort  = 'desc';
        }
        if (!empty($param['sort_type']) && $param['sort_type'] == 2) { # 最近截止
            $orderField = 'stop_time';
            $orderSort  = 'desc';
        }
        if (!empty($param['sort_type']) && $param['sort_type'] == 3) { # 最近更新
            $orderField = 'update_time';
            $orderSort  = 'desc';
        }
        if (!empty($param['sort_type']) && $param['sort_type'] == 4) { # 最高优先级
            $orderField = 'priority';
            $orderSort  = 'desc';
        }

        # 搜索
        $searchWhere = '';
        $dateWhere   = [];
        $userWhere   = [];
        $workWhere   = [];
        $labelWhere  = '';
        $ownerUserId = !empty($param['owner_user_id']) ? $param['owner_user_id'] : '';
        $search      = !empty($param['search'])        ? $param['search']        : '';
        $type        = !empty($param['type'])          ? $param['type']          : 0;
        $startTime   = !empty($param['start_time'])    ? $param['start_time']    : '';
        $endTime     = !empty($param['end_time'])      ? $param['end_time']      : '';
        $workIds     = !empty($param['work_id'])       ? $param['work_id']       : '';
        $labelIds    = !empty($param['label_id'])      ? $param['label_id']      : '';
        unset($param['search']);
        unset($param['type']);
        unset($param['start_time']);
        unset($param['end_time']);
        unset($param['owner_user_id']);

        switch ($type) {
            case 1 :
                # 今天
                $dateWhere['update_time'][] = ['egt', strtotime(date('Y-m-d 00:00:00'))];
                $dateWhere['update_time'][] = ['elt', strtotime(date('Y-m-d 23:59:59'))];
                break;
            case 2 :
                # 上周
                $dateWhere['update_time'][] = ['egt', strtotime('last week monday')];
                $dateWhere['update_time'][] = ['elt', strtotime(date('Y-m-d 23:59:59', strtotime('last week sunday')))];
                break;
            case 3 :
                # 上月
                $dateWhere['update_time'][] = ['egt', strtotime(date('Y-m-01 00:00:00', strtotime('last month')))];
                $dateWhere['update_time'][] = ['elt', strtotime(date('Y-m-d 23:59:59', strtotime('Last day of last month')))];
                break;
            case 4 :
                # 去年
                $dateWhere['update_time'][] = ['egt', strtotime(date('Y-01-01 00:00:00', strtotime('last year')))];
                $dateWhere['update_time'][] = ['elt', strtotime(date('Y-12-31 23:59:59', strtotime('last year')))];
        }

        # 时间区间
        if (!empty($startTime)) $dateWhere['update_time'] = ['egt', strtotime($startTime . '00:00:00')];
        if (!empty($endTime))   $dateWhere['update_time'] = ['elt', strtotime($endTime . '23:59:59')];

        # 搜索内容
        if ($search) $searchWhere = '(name like "%' . $search . '%") OR (description like "%' . $search . '%")';

        # 成员
        if (!empty($ownerUserId)) {
            $taskArray = [];
            $userArray = explode(',', $ownerUserId);
            foreach ($userArray AS $key => $value) {
                $tmp = Db::name('task')->where('work_id', '<>', 0)->whereLike('owner_user_id', '%,' . $value . ',%')->column('task_id');
                $taskArray = array_merge($tmp, $taskArray);
            }

            if (!empty($taskArray)) $userWhere['task_id'] = ['in', $taskArray];
        }

        # 项目
        if (!empty($workIds)) $workWhere['work_id'] = ['in', $workIds];

        # 标签
        if (!empty($labelIds) && is_array($labelIds)) {
            foreach ($labelIds AS $key => $value) {
                $labelWhere .= '(lable_id like "%,'.$value.',%") OR ';
            }
            if (!empty($labelWhere)) $labelWhere = '(' . rtrim($labelWhere, 'OR ') . ')';
        }

        $data = Db::name('task')
                ->where('work_id', '<>', 0)
                ->where($searchWhere)
                ->where($dateWhere)
                ->where($userWhere)
                ->where($workWhere)
                ->where($labelWhere)
                ->order($orderField, $orderSort)
                ->select();

        foreach ($data AS $key => $value) {
            $data[$key]['filecount']     = Db::name('work_task_file')->where('task_id', $value['task_id'])->count();
            $data[$key]['relationCount'] = $this->getRelationCount($value['task_id']);
            $data[$key]['lableList']     = Db::name('work_task_lable')->field(['lable_id', 'name', 'color'])->whereIn('lable_id', trim($value['lable_id'], ','))->select();
            $data[$key]['subdonecount']  = Db::name('task')->where(['pid' => $value['task_id'], 'status' => 5])->count();
            $data[$key]['subcount']      = Db::name('task')->where(['pid' => $value['task_id'], 'status' => ['neq', 5]])->count();
            $data[$key]['start_time']    = !empty($value['start_time']) ? date('Y-m-d', $value['start_time']) : null;
            $data[$key]['stop_time']     = !empty($value['stop_time']) ? date('Y-m-d', $value['stop_time']) : null;
            $data[$key]['create_time']   = !empty($value['create_time']) ? date('Y-m-d H:i:s', $value['create_time']) : null;
            $data[$key]['update_time']   = !empty($value['update_time']) ? date('Y-m-d H:i:s', $value['update_time']) : null;
        }

        return $data;
    }

    private function getRelationCount($task_id)
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
}