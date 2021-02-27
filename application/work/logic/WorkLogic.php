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
        $orderField = 'work_id';
        $orderSort = 'asc';
        if (!empty($param['sort_type']) && $param['sort_type'] == 1) {
            $orderField = 'work_id';
            $orderSort = 'asc';
        }
        if (!empty($param['sort_type']) && $param['sort_type'] == 2) {
            $orderField = 'work_id';
            $orderSort = 'desc';
        }
        if (!empty($param['sort_type']) && $param['sort_type'] == 3) {
            $orderField = 'update_time';
            $orderSort = 'desc';
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
        if (!empty($endTime)) $dateWhere['update_time'] = ['elt', strtotime($endTime . '23:59:59')];
        
        # 搜索内容
        if ($search) $searchWhere = '(name like "%' . $search . '%") OR (description like "%' . $search . '%")';
        
        # 成员
        if (!empty($ownerUserId)) {
            $userIds = Db::name('work_user')->whereIn('user_id', $ownerUserId)->column('work_id');
            $userWhere['work_id'] = ['in', $userIds];
        }
        
        $userModel = new \app\admin\model\User();
        $perUserIds = $userModel->getUserByPer('work', 'work', 'index');
        $authUser = array_unique(array_merge([$param['user_id']], $perUserIds));
        
        $data = Db::name('work')
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

        foreach ($data as $key => $value) {
            $data[$key]['authList']['project'] = $this->getRuleList($value['work_id'], $param['user_id'], $value['group_id']);
        }
        
        return $data;
    }
    
    /**
     * @param $param work 排序数组值 user_id用户
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/2/23 15:42
     */
//    public function workStart($param)
//    {
//        $item = Db::name('workStart')->where('user_id', $param['user_id'])->find();
//        $data = [];
//        $data['datas'] = $param['work'];
//        $data['user_id'] = $param['user_id'];
//        if (!$item) {
//            return Db::name('workStart')->insertGetId($data);
//        } else {
//            return Db::name('workStart')->where('user_id', $param['user_id'])->update([$data]);
//        }
//    }
    
    /**
     * 根据数组指定键名排序数组
     * @param $array array  被排序数组
     * @param $key_name string 数组键名
     * @param $sort   string  desc|asc  升序或者降序
     * @return array 返回排序后的数组
     */
//    function gw_sort($array, $param)
//    {
//        $whilr = array
//        (
//            [0] => ['work_id' => 5,
//                'name' => 222,
//                'status' => 1,
//                'create_time' => 1613628469,
//                'create_user_id' => 7,
//                'description' => 222,
//                'color' => '#53D397',
//                'is_open' => 1,
//                'owner_user_id' => ',1,2,3,4,5,7,',
//                'ishidden' => 0,
//                'archive_time' => 0,
//                'group_id' => 12,
//                'cover_url' => 'http://192.168.1.31/72crm-php/public/uploads/20210218/12ece02733c8684ce987f207062173b5.png',
//                'update_time' => 1613629916,
//                'is_follow' => 0,
//                'is_system_cover' => 0,],
//
//            [1] => [
//
//                'work_id' => 7,
//                'name' => '啊啊',
//                'status' => 1,
//                'create_time' => 1614059388,
//                'create_user_id' => 1,
//                'description' => '',
//                'color' => '#53D397',
//                'is_open' => 0,
//                'owner_user_id' => ',1,3,',
//                'ishidden' => 0,
//                'archive_time' => 0,
//                'group_id' => 12,
//                'cover_url' => 'https://file.72crm.com/static/pc/images/pm/project-cover-1.jpg',
//                'update_time' => 1614059926,
//                'is_follow' => 0,
//                'is_system_cover' => 1,
//            ]
//
//        );
//        $item = Db::name('workStart')->where('user_id', $param['user_id'])->find();
//        $key_name_array = array();//保存被排序数组键名
//        foreach ($whilr as $key => $val) {
//            foreach ($item as $v){
//                $key_name_array[] = array_merge(array_flip($val), $v);
//            }
//        }
//        $key_name_array = array_flip($key_name_array);//反转键名和值得到数组排序后的位置
//        $result = array();
//        foreach($array as $k=>$v){
//            foreach ($item as $vall){
//                $this_key_name_value = $v[$vall];//当前数组键名值依次是20,10,30
//                $save_position = $key_name_array[$this_key_name_value];//获取20,10,30排序后存储位置
//                $result[$save_position] = $v;//当前项存储到数组指定位置
//            }
//        }
//        ksort($result);
//
//        return $result;
//    }
}