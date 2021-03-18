<?php
/**
 * 项目逻辑类
 *
 * @author qifan
 * @date 2020-12-16
 */

namespace app\work\logic;

use app\work\traits\WorkAuthTrait;
use think\Db;

class WorkLogic
{
    use WorkAuthTrait;
    
    public function index($param)
    {
        # 排序
        $orderField = 'w.work_id';
        $orderSort = 'asc';
        if (!empty($param['sort_type']) && $param['sort_type'] == 1) {
            $orderField = 'w.work_id';
            $orderSort = 'asc';
        }
        if (!empty($param['sort_type']) && $param['sort_type'] == 2) {
            $orderField = 'w.work_id';
            $orderSort = 'desc';
        }
        if (!empty($param['sort_type']) && $param['sort_type'] == 3) {
            $orderField = 'w.update_time';
            $orderSort = 'desc';
        }
        if (!empty($param['sort_type']) && $param['sort_type'] == 4) {
            $orderField = 'o.order';
            $orderSort = 'asc';
        }

        # 搜索
        $searchWhere = '';
        $dateWhere = [];
        $userWhere = [];
        $ownerUserId = !empty($param['owner_user_id']) ? $param['owner_user_id'] : '';
        $search = !empty($param['search']) ? $param['search'] : '';
        $type = !empty($param['type']) ? $param['type'] : 0;
        $startTime = !empty($param['start_time']) ? $param['start_time'] : '';
        $endTime = !empty($param['end_time']) ? $param['end_time'] : '';
        unset($param['search']);
        unset($param['type']);
        unset($param['start_time']);
        unset($param['end_time']);
        unset($param['owner_user_id']);
        
        switch ($type) {
            case 1 :
                # 今天
                $dateWhere['w.update_time'][] = ['egt', strtotime(date('Y-m-d 00:00:00'))];
                $dateWhere['w.update_time'][] = ['elt', strtotime(date('Y-m-d 23:59:59'))];
                break;
            case 2 :
                # 上周
                $dateWhere['w.update_time'][] = ['egt', strtotime('last week monday')];
                $dateWhere['w.update_time'][] = ['elt', strtotime(date('Y-m-d 23:59:59', strtotime('last week sunday')))];
                break;
            case 3 :
                # 上月
                $dateWhere['w.update_time'][] = ['egt', strtotime(date('Y-m-01 00:00:00', strtotime('last month')))];
                $dateWhere['w.update_time'][] = ['elt', strtotime(date('Y-m-d 23:59:59', strtotime('Last day of last month')))];
                break;
            case 4 :
                # 去年
                $dateWhere['w.update_time'][] = ['egt', strtotime(date('Y-01-01 00:00:00', strtotime('last year')))];
                $dateWhere['w.update_time'][] = ['elt', strtotime(date('Y-12-31 23:59:59', strtotime('last year')))];
        }
        
        # 时间区间
        if (!empty($startTime)) $dateWhere['w.update_time'] = ['egt', strtotime($startTime . '00:00:00')];
        if (!empty($endTime)) $dateWhere['w.update_time'] = ['elt', strtotime($endTime . '23:59:59')];
        
        # 搜索内容
        if ($search) $searchWhere = '(w.name like "%' . $search . '%") OR (w.description like "%' . $search . '%")';
        
        # 成员
        if (!empty($ownerUserId)) {
            $userIds = Db::name('work_user')->whereIn('user_id', $ownerUserId)->column('work_id');
            $userWhere['w.work_id'] = ['in', $userIds];
        }
        
        $userModel = new \app\admin\model\User();
        $perUserIds = $userModel->getUserByPer('work', 'work', 'index');
        $authUser = array_unique(array_merge([$param['user_id']], $perUserIds));

        if ($param['sort_type'] == 4 && db('work_order')->where('user_id', $param['user_id'])->count() > 0) {
            # 选择了按手动拖动排序，并且手动排过序。
            $data = Db::name('work')->alias('w')
                    ->field('w.*')
                    ->join('__WORK_ORDER__ o', 'o.work_id = w.work_id', 'left')
                    ->where(function ($query) {
                        $query->where('status', 1);
                        $query->where('ishidden', 0);
                    })
                    ->where(function ($query) use ($param, $authUser) {
                        $query->whereOr(['create_user_id' => ['in', $authUser]]);
                        $query->whereOr('is_open', 1);
                        $query->whereOr(function ($query) use ($param) {
                            $query->where('is_open', 0);
                            $query->where('owner_user_id', 'like', '%' . $param['user_id'] . '%');
                        });
                    })
                    ->where('o.user_id', $param['user_id'])
                    ->where($searchWhere)
                    ->where($dateWhere)
                    ->where($userWhere)
                    ->order($orderField, $orderSort)->select();
        } else {
            # 未手动排过序，如果选择了手动排序选项
            if ($param['sort_type'] == 4) {
                $orderField = 'w.work_id';
                $orderSort = 'asc';
            }

            $data = Db::name('work')->alias('w')
                    ->field('w.*')
                    ->where(function ($query) {
                        $query->where('status', 1);
                        $query->where('ishidden', 0);
                    })
                    ->where(function ($query) use ($param, $authUser) {
                        $query->whereOr(['create_user_id' => ['in', $authUser]]);
                        $query->whereOr('is_open', 1);
                        $query->whereOr(function ($query) use ($param) {
                            $query->where('is_open', 0);
                            $query->where('owner_user_id', 'like', '%' . $param['user_id'] . '%');
                        });
                    })
                    ->where($searchWhere)
                    ->where($dateWhere)
                    ->where($userWhere)
                    ->order($orderField, $orderSort)->select();
        }

        foreach ($data as $key => $value) {
            $data[$key]['authList']['project'] = $this->getRuleList($value['work_id'], $param['user_id'], $value['group_id']);
        }
        
        return $data;
    }

    /**
     * 手动设置项目顺序
     *
     * @param $workIds 项目ID数组
     * @param $userId 当前用户ID
     * @author fanqi
     * @date 2021-03-11
     * @return bool
     */
    public function setWorkOrder($workIds, $userId)
    {
        $data = [];

        foreach ($workIds AS $key => $value) {
            $data[] = [
                'work_id' => $value,
                'user_id' => $userId,
                'order'   => $key + 1
            ];
        }

        if (!empty($data)) {
            if (db('work_order')->where('user_id', $userId)->delete() === false) return false;
            if (db('work_order')->insertAll($data) === false) return false;
        }

        return true;
    }
}