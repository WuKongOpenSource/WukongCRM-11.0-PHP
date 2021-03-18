<?php
// +----------------------------------------------------------------------
// | Description: 系统基础公共
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\controller\ApiCommon;
use app\admin\model\Common;
use think\Db;

class Admin extends Common
{
    /**
     * 统计筛选条件
     * @param  $merge 1 user,structure 合并查询，0 user_id 优先级高 $is_last 昨天、上周、、、
     * @param  $perUserIds 权限范围
     * @return
     * @author Michael_xu
     */
    public function getWhere($param, $merge = '', $perUserIds = [], $filter = true, $is_last = false)
    {
        $apiCommon = new ApiCommon();
        $userModel = new \app\admin\model\User();
        $user_id = $apiCommon->userInfo['id'];
        $structure_id = $apiCommon->userInfo['structure_id'];
        //员工IDS
        $user_ids = [];
        if ($param['user_id']) {
            $user_ids = array($param['user_id']);
        }
        if ($param['structure_id']) {
            $userModel->getSubUserByStr($param['structure_id'], 2);
        }        
        if ($param['dataType']) {
            switch ($param['dataType']) {
                case 1 : $user_ids = [$user_id];
                    break;
                case 2 : $user_ids = getSubUserId(true, 0, $apiCommon->userInfo['id']);
                    break;
                case 3 : $user_ids = $userModel->getSubUserByStr($structure_id, 1);
                    break;
                case 4 : $user_ids = $userModel->getSubUserByStr($structure_id, 2);
                    break;
                default : $user_ids = [$user_id];
                    break;                                             
            }
        } else {
            if ($merge == 1) {
                if ($param['structure_id']) {
                    $str_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
                }
                //合并
                if ($user_ids && $str_user_ids) {
                    $user_ids = array_unique(array_merge($user_ids, $str_user_ids));
                } elseif ($str_user_ids) {
                    $user_ids = $str_user_ids;
                }
            } else {
                if (!$user_ids) {
                    if ($param['structure_id']) {
                        $user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
                    }
                }
            }
        }
        if (!$user_ids) $user_ids = getSubUserId(true,0, $apiCommon->userInfo['id']);
        $perUserIds = $perUserIds ? : getSubUserId(); //权限范围内userIds
        $userIds = [];
        if ($user_ids) {
            $userIds = $perUserIds ? array_intersect($user_ids, $perUserIds) : $perUserIds; //数组交集
        }
        $where['userIds'] = array_map('intval', $userIds);
        if ($param['type']) {
            $between_time = getTimeByType($param['type'], $is_last);
        } else {
            //自定义时间
            if ($param['start_time']) {
                $between_time = array(strtotime($param['start_time']), strtotime($param['end_time']));
            }            
            //自定义时间
            if ($param['startTime']) {
                $between_time = array(strtotime($param['startTime']), strtotime($param['endTime']));
            }
        }
        $where['between_time'] = $between_time;
        return $where ?: [];
    }
}