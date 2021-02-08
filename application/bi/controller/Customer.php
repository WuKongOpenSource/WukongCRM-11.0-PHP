<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-客户分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use app\bi\logic\BiCustomerLogic;
use app\bi\model\Customer as CustomerModel;
use app\admin\model\User as UserModel;
use app\bi\traits\SortTrait;
use think\Hook;
use think\Request;
use think\Db;
use app\bi\logic\ExcelLogic;

class Customer extends ApiCommon
{
    use SortTrait;

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
                'statistics',
                'total',
                'recordtimes',
                'recordlist',
                'recordmode',
                'conversion',
                'conversioninfo',
                'pool',
                'poollist',
                'usercycle',
                'usercyclelist',
                'productcycle',
                'addresscycle',
                'addressanalyse',
                'portrait',
                'customersatisfaction',
                'productsatisfaction',
                'excelexport'
            ]
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
//        if (!checkPerByAction('bi', 'customer', 'read')) {
//            header('Content-Type:application/json; charset=utf-8');
//            exit(json_encode(['code' => 102, 'error' => '无权操作']));
//        }
    }

    /**
     * 员工客户分析
     * @param
     * @return
     * @author Michael_xu
     */
    public function statistics($param='')
    {
        $customerModel = new \app\crm\model\Customer();
        if($param['excel_type']!=1){
            $param = $this->param;
        }

        # 排序参数
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        if ($param['type']) {
            $timeArr = getTimeByType($param['type']);
            $param['start_time'] = $timeArr[0];
            $param['end_time'] = $timeArr[1];
        } else {
            if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
            if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        }

        $data = $customerModel->getStatistics($param);

        # 排序
        if (!empty($data['list'])) $data['list'] = $this->sortCommon($data['list'], $sortField, $sortValue);
        //导出使用
        if (!empty($param['excel_type'])) return $data;
        return resultArray(['data' => $data]);
    }

    /**
     * 员工客户总量分析
     * @param
     * @return
     * @author zhi
     */
    public function total()
    {
        $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $param = $this->param;

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereArr['userIds'];

        # 处理无员工的情况
        if (empty($userIds)) return resultArray(['data' => []]);

        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);
        $where = [
            'create_user_id' => implode(',', $userIds),
            'deal_status' => '已成交'
        ];
        $sql = [];

        foreach ($time['list'] as $val) {
            $whereArr = $where;
            $whereArr['type'] = $val['type'];
            $whereArr['start_time'] = $val['start_time'];
            $whereArr['end_time'] = $val['end_time'];
            $sql[] = $customerModel->getAddDealSql($whereArr);
        }

        $sql = implode(' UNION ALL ', $sql);

        $list = queryCache($sql);
        return resultArray(['data' => $list]);
    }

    /**
     * 员工客户跟进次数分析
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recordTimes()
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $param = $this->param;

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereArr['userIds'];
        if (empty($userIds)) return resultArray(['data' => []]);

        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);

        $recordWhere['type']           = 1;
        $recordWhere['activity_type']  = 2;
        $recordWhere['create_user_id'] = ['in', $userIds];
        $recordWhere['create_time']    = ['between', [$time['between'][0], $time['between'][1]]];
        $sql = db('crm_activity')->
                field([
                    "FROM_UNIXTIME(`create_time`, '".$time['time_format']."') AS `type`",
                    'COUNT(DISTINCT(`activity_type_id`)) AS `customerCount`',
                    'COUNT(*) AS `dataCount`'
                ])
                ->where($recordWhere)
                ->group('type')
                ->select();
        $res = array_column((array)$sql, null, 'type');

        foreach ($time['list'] as &$val) {
            $val['customerCount'] = (int)$res[$val['type']]['customerCount'];
            $val['dataCount'] = (int)$res[$val['type']]['dataCount'];
        }
        return resultArray(['data' => $time['list']]);
    }

    /**
     * 员工客户跟进次数分析 具体员工列表
     *
     * @param string $param
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recordList($param='')
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        if($param['excel_type']!=1){
            $param = $this->param;
        }

        # 排序参数
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereArr['userIds'];
        if (empty($userIds)) return resultArray(['data' => []]);

        # 员工列表
        $userData = [];
        $userList = db('admin_user')->field(['id', 'realname'])->whereIn('id', $userIds)->select();
        foreach ($userList AS $key => $value) {
            $userData[$value['id']] = $value['realname'];
        }

        # 跟进记录列表
        $recordData = [];
        $recordWhere['type']           = 1;
        $recordWhere['activity_type']  = 2;
        $recordWhere['create_user_id'] = ['in', $userIds];
        $recordWhere['create_time']    = ['between', [$whereArr['between_time'][0], $whereArr['between_time'][1]]];
        $recordList = db('crm_activity')->field(['count(*) as count', 'create_user_id', 'activity_type_id'])->where($recordWhere)
            ->group('create_user_id, activity_type_id')->select();

        # 跟进列表
        foreach ($recordList AS $key => $value) {
            if (empty($recordData[$value['create_user_id']]['realname'])) {
                $recordData[$value['create_user_id']]['realname']     = $userData[$value['create_user_id']];
                $recordData[$value['create_user_id']]['record_num']   = $value['count'];
                $recordData[$value['create_user_id']]['customer_num'] = 1;
            } else {
                $recordData[$value['create_user_id']]['record_num']   = $recordData[$value['create_user_id']]['record_num'] + $value['count'];
                $recordData[$value['create_user_id']]['customer_num'] = $recordData[$value['create_user_id']]['customer_num'] + 1;
            }

            if (!empty($userData[$value['create_user_id']])) unset($userData[(int)$value['create_user_id']]);
        }

        # 跟进客户总数
        $customerCount = db('crm_activity')->where($recordWhere)->group('activity_type_id')->count();

        # 没有跟进的员工设置默认值
        foreach ($userData AS $key => $value) {
            $recordData[$key]['realname']     = $value;
            $recordData[$key]['record_num']   = 0;
            $recordData[$key]['customer_num'] = 0;
        }

        $result = ['list' => array_values($recordData), 'total' => ['realname' => '总计', 'customer_num' => $customerCount]];

        # 排序
        if (!empty($result['list'])) $result['list'] = $this->sortCommon($result['list'], $sortField, $sortValue);
        //导出使用
        if (!empty($param['excel_type'])) return $recordData;
        return resultArray(['data' => $result]);
    }

    /**
     * 员工跟进方式分析
     *
     * @param string $param
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recordMode($param='')
    {
        $biCustomerModel = new \app\bi\model\Customer();
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        $userId = $this->userInfo['id'];

        # 处理无部门无员工的情况
        $groupType = !empty($param['group_type']) ? $param['group_type'] : 1; # 组类型：1部门；2员工
        if (empty($param['user_id']) && empty($param['structure_id'])) {
            if ($groupType == 1) $param['structure_id'] = Db::name('admin_user')->where('id', $userId)->value('structure_id');
            if ($groupType == 2) $param['user_id'] = $userId;
        }
        unset($param['group_type']);

        # 判断部门下是否有员工
        if (!empty($param['structure_id'])) {
            $userModel = new \app\admin\model\User();
            $userIds   = $userModel->getSubUserByStr($param['structure_id'], 2);
            if (empty($userIds)) return resultArray(['data' => []]);
        }

        # 排序参数
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');

        $whereArr = $biCustomerModel->getParamByWhere($param, 'record');

        //跟进类型
        $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
        if ($record_type) {
            $record_categorys = json_decode($record_type['value']);
        } else {
            $record_categorys = array('打电话', '发邮件', '发短信', '见面拜访', '活动');
        }

        $sql = db('crm_activity')
            ->field([
                'category',
                'COUNT(*)' => 'count'
            ])
            ->where([
                'create_time' => $whereArr['create_time'],
                'create_user_id' => $whereArr['create_user_id'],
                'type' => 1,
                'activity_type' => 2
            ])
            ->group('category')
            ->fetchSql()
            ->select();

        $list = queryCache($sql);
        $list = array_column($list, null, 'category');
        $sum = array_sum(array_column($list, 'count'));

        $res = [];
        $recordCount = 0; # 跟进类型总数
        foreach ($record_categorys as $val) {
            $item['category'] = $val;
            if ($sum) {
                $item['recordNum'] = (int)$list[$val]['count'];
                $item['proportion'] = round(($item['recordNum'] / $sum  * 100), 4);
            } else {
                $item['recordNum'] = $item['proportion'] = 0;
            }
            $res[] = $item;

            $recordCount += $item['recordNum'];
        }

        $result = [
            'list' => $res,
            'total' => [
                'category' => '合计',
                'recordNum' => $recordCount,
                'proportion' => !empty($recordCount) ? 100 : 0
            ]
        ];

        # 排序
        if (!empty($result['list'])) $result['list'] = $this->sortCommon($result['list'], $sortField, $sortValue);
        //导出使用
        if (!empty($param['excel_type'])) return $result['list'];
        return resultArray(['data' => $result]);
    }

    /**
     * 客户转化率分析
     *
     * @param
     * @return
     * @author zhi
     */
    public function conversion()
    {
        $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $param = $this->param;

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        if (empty($whereArr['userIds'])) resultArray(['data' => []]);
        $userIds  = $whereArr['userIds'];
        $user_ids = implode(',',$userIds);

        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);
        $sql = [];
        foreach ($time['list'] as $val) {
            $sql[] = $customerModel->getAddDealSql([
                'create_user_id' => $user_ids ? $user_ids : '9999999999',
                'type' => $val['type'],
                'start_time' => $val['start_time'],
                'end_time' => $val['end_time'],
                'deal_status' => '已成交',
            ]);
        }
        $sql = implode(' UNION ALL ', $sql);
        $list = queryCache($sql);
        foreach ($list as &$val) {
            $val['proportion'] = $val['customer_num'] ? number_format($val['deal_customer_num'] / $val['customer_num'] * 100, 2) : 0;
        }
        return resultArray(['data' => $list]);
    }

    /**
     * 客户转化率分析具体数据
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function conversionInfo()
    {
        $customerModel = new \app\bi\model\Customer();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userIds = [];
        $userId = $this->userInfo['id'];

        # 处理无部门无员工的情况
        $groupType = !empty($param['group_type']) ? $param['group_type'] : 1; # 组类型：1部门；2员工
        if (empty($param['user_id']) && empty($param['structure_id'])) {
            if ($groupType == 1) $param['structure_id'] = Db::name('admin_user')->where('id', $userId)->value('structure_id');
            if ($groupType == 2) $param['user_id'] = $userId;
        }
        unset($param['group_type']);

        # 如果存在员工参数，则不再使用部门参数
        if (!empty($param['user_id'])) {
            $userIds = [$param['user_id']];
        } elseif (!empty($param['structure_id'])) {
            $userIds = $userModel->getSubUserByStr($param['structure_id'], 2);
        }
        if (empty($userIds)) return resultArray(['data' => []]);

        # 排序参数
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        # 日期条件
        if (!empty($param['type'])) {
            $param['start_time'] = strtotime($param['type'] . '-01 00:00:00');
            $endMonth = strtotime(date('Y-m-d', $param['start_time']) . " +1 month -1 day");
            $param['end_time'] = strtotime(date('Y-m-d 23:59:59', $endMonth));
            unset($param['type']);
        }

        $whereArr = $customerModel->getParamByWhere($param);
        $whereArr['deal_status'] = '已成交';

        $list = $customerModel->getWhereByList($whereArr, $sortField, $sortValue);

        return resultArray(['data' => $list]);
    }

    /**
     * 公海客户分析
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pool()
    {
        $actionRecordModel = new \app\bi\model\ActionRecord();
        $userModel         = new \app\admin\model\User();
        $adminModel        = new \app\admin\model\Admin();
        $param             = $this->param;

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereArr['userIds'];

        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);
        $sql = $actionRecordModel
            ->field([
                "FROM_UNIXTIME(`create_time`, '{$time['time_format']}')" => 'type',
                'SUM(CASE WHEN `content` = "将客户放入公海" THEN 1 ELSE 0 END)' => 'put_in',
                'SUM(CASE WHEN `content` = "领取了客户" THEN 1 ELSE 0 END)' => 'receive'
            ])
            ->where([
                'user_id' => ['IN', !empty($userIds) ? $userIds : '9999999999'],
                'create_time' => ['BETWEEN', $time['between']],
                'content' => ['IN', ['将客户放入公海', '领取了客户']]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');

        foreach ($time['list'] as &$val) {
            $val['put_in'] = (int)$res[$val['type']]['put_in'];
            $val['receive'] = (int)$res[$val['type']]['receive'];
        }

        return resultArray(['data' => $time['list']]);
    }

    /**
     * 公海客户分析 具体列表
     *
     * @param string $param
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function poolList($param='')
    {
        $userModel = new \app\admin\model\User();
        $actionRecordModel = new \app\bi\model\ActionRecord();
        $adminModel = new \app\admin\model\Admin();
        if($param['excel_type']!=1){
            $param = $this->param;
        }

        # 排序参数
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');

        $perUserIds   = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr     = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds      = $whereArr['userIds'];
        $between_time = $whereArr['between_time'];

        $sql = CustomerModel::field([
            'COUNT(*)' => 'customer_num',
            'owner_user_id'
        ])
            ->where([
                'create_time' => ['BETWEEN', $between_time],
                'owner_user_id' => ['IN', !empty($userIds) ? $userIds : '9999999999']
            ])
            ->group('owner_user_id')
            ->fetchSql()
            ->select();
        $customer_num_list = queryCache($sql);
        $customer_num_list = array_column($customer_num_list, null, 'owner_user_id');

        $sql = $actionRecordModel
            ->field([
                'user_id',
                'SUM(CASE WHEN `content` = "将客户放入公海" THEN 1 ELSE 0 END)' => 'put_in',
                'SUM(CASE WHEN `content` = "领取了客户" THEN 1 ELSE 0 END)' => 'receive'
            ])
            ->group('user_id')
            ->where([
                'create_time' => ['BETWEEN', $between_time],
                'user_id' => ['IN', $userIds],
                'content' => ['IN', ['将客户放入公海', '领取了客户']],
                'types' => 'crm_customer',
            ])
            ->fetchSql()
            ->select();
        $action_record_list = queryCache($sql);
        $action_record_list = array_column($action_record_list, null, 'user_id');

        $res = [];
        $receiveCount = 0; # 领取公海客户总数
        $putInCount = 0; # 进入公海客户总数
        foreach ($userIds as $val) {
            $item['put_in'] = !empty($action_record_list[$val]['put_in']) ? (int)$action_record_list[$val]['put_in'] : 0;
            $item['receive'] = !empty($action_record_list[$val]['receive']) ? (int)$action_record_list[$val]['receive'] : 0;
            $item['customer_num'] = !empty($customer_num_list[$val]['customer_num']) ? (int)$customer_num_list[$val]['customer_num'] : 0;
            $user_info = $userModel->getUserById($val);
            $item['realname'] = $user_info['realname'];
            $item['username'] = $user_info['structure_name'];
            $res[] = $item;

            $receiveCount += $item['receive'];
            $putInCount += $item['put_in'];
        }

        $result = ['list' => $res, 'total' => ['realname' => '总计', 'receive' => $receiveCount, 'put_in' => $putInCount]];

        # 排序
        if (!empty($result['list'])) $result['list'] = $this->sortCommon($result['list'], $sortField, $sortValue);
        //导出使用
        if (!empty($param['excel_type'])) return $result['list'];
        return resultArray(['data' => $result]);
    }

    /**
     * 员工客户成交周期
     *
     * @param string $param
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userCycle($param='')
    {
        $userModel  = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        if($param['excel_type']!=1){
            $param = $this->param;
        }

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereData  = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereData['userIds'];

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);

        $prefix = config('database.prefix');

        $sql = CustomerModel::alias('a')
            ->field([
                "FROM_UNIXTIME(`a`.`create_time`, '{$time['time_format']}')" => 'type',
                'COUNT(*)' => 'customer_num',
                'SUM(
                    CASE WHEN ISNULL(`b`.`order_date`) THEN 0 ELSE (
                        UNIX_TIMESTAMP(`b`.`order_date`) - `a`.`create_time`
                    ) / 86400 END
                )' => 'cycle_sum'
            ])
            ->join(
                "(
                    SELECT 
                        `customer_id`, MIN(`order_date`) AS `order_date` 
                    FROM
                        `{$prefix}crm_contract` 
                    WHERE
                        `check_status` = 2 
                    GROUP BY
                        `customer_id`
                ) b",
                '`a`.`customer_id` = `b`.`customer_id`',
                'LEFT'
            )
            ->where([
                'a.deal_status' => '已成交',
                'a.create_time' => ['BETWEEN', $time['between']],
                'a.owner_user_id' => ['IN', !empty($userIds) ? $userIds : '9999999999']
            ])
            ->group('type')
            ->fetchsql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');

        foreach ($time['list'] as &$val) {
            $val['customer_num'] = (int)$res[$val['type']]['customer_num'];
            if ($res[$val['type']]['customer_num']) {
                $val['cycle'] = intval($res[$val['type']]['cycle_sum'] / $res[$val['type']]['customer_num']);
            } else {
                $val['cycle'] = 0;
            }
        }
        $datas = $time['list'];

        return resultArray(['data' => $datas]);
    }

    /**
     * 成交周期列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userCycleList()
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $param = $this->param;

        # 排序参数
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds   = $whereData['userIds'];

        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);

        $prefix = config('database.prefix');

        $sql = CustomerModel::alias('a')
            ->field([
                'a.owner_user_id',
                'COUNT(*)' => 'customer_num',
                'SUM(
                    CASE WHEN  ISNULL(b.order_date) THEN 0 ELSE (
                        UNIX_TIMESTAMP(b.order_date) - a.create_time
                    ) / 86400 END
                )' => 'cycle_sum'
            ])
            ->join(
                "(
                    SELECT
                        `customer_id`,
                        MIN(`order_date`) AS `order_date`
                    FROM
                        `{$prefix}crm_contract`
                    WHERE
                        `check_status` = 2
                    GROUP BY
                        `customer_id`
                ) b",
                'a.customer_id = b.customer_id',
                'LEFT'
            )
            ->where([
                'a.deal_status' => '已成交',
                'a.create_time' => ['BETWEEN', $time['between']],
                'a.owner_user_id' => ['IN', !empty($userIds) ? $userIds : '9999999999']
            ])
            ->group('a.owner_user_id')
            ->fetchSql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'owner_user_id');

        $user_data = [];
        $customerCount = 0; # 成交客户总数
        $cycleCount    = 0; # 成交周期总数
        foreach ($userIds as $value) {
            $item['customer_num'] = !empty($res[$value]['customer_num']) ? $res[$value]['customer_num'] : 0;
            $item['cycle'] = $res[$value]['customer_num'] ? intval($res[$value]['cycle_sum'] / $res[$value]['customer_num']) : 0;
            $item['realname'] = $userModel->getUserById($value)['realname'];

            $user_data[] = $item;

            $customerCount += $item['customer_num'];
            $cycleCount += $item['cycle'];
        }
        $datas['list'] = $user_data;

        $datas['total'] = [
            'realname'       => '总计',
            'customer_num' => $customerCount,
            'cycle'    => $cycleCount

        ];

        # 排序
        if (!empty($datas['users'])) $datas['users'] = $this->sortCommon($datas['users'], $sortField, $sortValue);
        //导出使用
        if (!empty($param['excel_type'])) return $datas['list'];
        return resultArray(['data' => $datas]);
    }

    /**
     * 产品成交周期
     * @param
     * @return
     * @author zhi
     */
    public function productCycle()
    {
        $biCustomerModel = new \app\bi\model\Customer();
        $productModel = new \app\bi\model\Product();
        $param = $this->param;
        $list = $productModel->getDealByProduct($param);
        $datas = array();
        $cycleCount    = 0;
        $customerCount = 0;
        foreach ($list as $key => $value) {
            $item = array();
            //周期
            $customer_ids = $productModel->getCycleByProduct($param, $value['product_id']);
            $whereArr = array();
            $whereArr['customer_id'] = array('in', $customer_ids);
            $cycle = $biCustomerModel->getWhereByCycle($whereArr);
            $item['product_name'] = $value['product_name'];
            $item['customer_num'] = !empty($value['num']) ? (int)$value['num'] : 0;
            $item['cycle'] = !empty($cycle) ? (int)$cycle : 0;
            $datas['list'][] = $item;

            $cycleCount += $item['cycle'];
            $customerCount += $item['customer_num'];
        }

        $datas['total'] = ['product_name' => '总计', 'cycle' => $cycleCount, 'customer_num' => $customerCount];

        return resultArray(['data' => $datas]);
    }

    /**
     * 地区成交周期
     * @param
     * @return
     * @author zhi
     */
    public function addressCycle()
    {
        $userModel = new \app\admin\model\User();
        $customerModel = new \app\crm\model\Customer();
        $biCustomerModel = new \app\bi\model\Customer();
        $address_arr = \app\crm\model\Customer::$address;
        $param = $this->param;
        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $time = getTimeArray();

        $prefix = config('database.prefix');
        $sql = CustomerModel::alias('a')
            ->field([
                'SUBSTR(`a`.`address`, 1, 2)' => 'addr',
                'COUNT(*)' => 'customer_num',
                'SUM(
                    CASE WHEN  ISNULL(b.order_date) THEN 0 ELSE (
                        UNIX_TIMESTAMP(b.order_date) - a.create_time
                    ) / 86400 END
                )' => 'cycle_sum'
            ])
            ->join(
                "(
                    SELECT 
                        `customer_id`, 
                        MIN(`order_date`) AS `order_date` 
                    FROM 
                        `{$prefix}crm_contract` 
                    WHERE 
                        `check_status` = 2 
                    GROUP BY 
                        `customer_id`
                ) b",
                'a.customer_id = b.customer_id',
                'LEFT'
            )
            ->where([
                'a.deal_status' => '已成交',
                'a.create_time' => ['BETWEEN', $time['between']],
                'a.owner_user_id' => ['IN', $userIds]
            ])
            ->group('addr')
            ->fetchSql()
            ->select();
        $list = queryCache($sql);
        $list = array_column($list, null, 'addr');
        $list['黑龙江'] = $list['黑龙'];
        $list['内蒙古'] = $list['内蒙'];
        $res = [];
        $cycleCount    = 0;
        $customerCount = 0;
        foreach ($address_arr as $val) {
            $item['address'] = $val;
            $item['customer_num'] = !empty($list[$val]['customer_num']) ? $list[$val]['customer_num'] : 0;
            $item['cycle'] = $list[$val]['customer_num'] ? intval($list[$val]['cycle_sum'] / $list[$val]['customer_num']) : 0;
            $res['list'][] = $item;

            $cycleCount    += $item['cycle'];
            $customerCount += $item['customer_num'];
        }

        $res['total'] = ['address' => '总计', 'cycle' => $cycleCount, 'customer_num' => $customerCount];
        
        return resultArray(['data' => $res]);
    }

    /**
     * 客户所在城市分析
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addressAnalyse()
    {
        $param = $this->param;
        // $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $address_arr = \app\crm\model\Customer::$address;
        $map_user_ids = [];
        # 如果存在员工参数，则不再使用部门参数
        if (!empty($param['user_id'])) {
            $map_user_ids = array($param['user_id']);
        } elseif (!empty($param['structure_id'])) {
            $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
        }
        if (empty($map_user_ids)) return resultArray(['data' => []]);

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds    = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);
        $sql = CustomerModel::alias('a')
            ->field([
                'SUBSTR(`address`, 1, 2)' => 'addr',
                'COUNT(*)' => 'allCustomer',
                'SUM(
                    CASE WHEN `deal_status` = "已成交" THEN  1 ELSE 0 END
                )' => 'dealCustomer',
            ])
            ->where([
                'create_time' => ['BETWEEN', $time['between']],
                'owner_user_id' => ['IN', $userIds]
            ])
            ->group('addr')
            ->fetchSql()
            ->select();
        $list = queryCache($sql);
        $list = array_column($list, null, 'addr');
        $list['黑龙江'] = $list['黑龙'];
        $list['内蒙古'] = $list['内蒙'];
        $data = [];
        foreach ($address_arr as $val) {
            $item['address'] = $val;
            $item['allCustomer'] = !empty($list[$val]['allCustomer']) ? (int)$list[$val]['allCustomer'] : 0;
            $item['dealCustomer'] = !empty($list[$val]['dealCustomer']) ? (int)$list[$val]['dealCustomer'] : 0;
            $data[] = $item;
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 客户行业/级别/来源分析
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function portrait()
    {
        $biCustomerModel = new \app\bi\model\Customer();
        $userModel       = new \app\admin\model\User();
        $adminModel      = new \app\admin\model\Admin();
        $param           = $this->param;

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];

        if (!in_array($param['type_analyse'], ['industry', 'source', 'level'])) {
            return resultArray(['error' => '参数错误']);
        }
        $poolWhere = $this->getWhereByPool();
        $poolId    = db('crm_customer')->alias('customer')->where($poolWhere)->column('customer_id');
        $whereArr = array();
        $whereArr['types'] = 'crm_customer';
        $whereArr['field'] = $param['type_analyse'];
        $setting = $biCustomerModel->getOptionByField($whereArr);
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);
        $sql = CustomerModel::field([
            "(
                    CASE WHEN 
                        `{$param['type_analyse']}` = '' 
                    THEN '(空)' 
                    ELSE {$param['type_analyse']} END
                )" => $param['type_analyse'],
            'COUNT(*)' => 'allCustomer',
            'SUM(
                    CASE WHEN `deal_status` = "已成交" THEN  1 ELSE 0 END
                )' => 'dealCustomer',
        ])
            ->where([
                'create_time' => ['BETWEEN', $time['between']],
                'owner_user_id' => ['IN', !empty($userIds) ? $userIds : '9999999999']
            ])
            ->whereNotIn('customer_id', $poolId)
            ->group($param['type_analyse'])
            ->fetchSql()
            ->select();
        $list = queryCache($sql);
        $list = array_column($list, null, $param['type_analyse']);
        $other_keys = array_diff(array_keys($list), $setting);
        $setting = array_merge($setting, $other_keys);

        $data = [];
        foreach ($setting as $val) {
            $item = [];

            $item[$param['type_analyse']] = $val;
            $item['allCustomer'] = !empty($list[$val]['allCustomer']) ? (int)$list[$val]['allCustomer'] : 0;
            $item['dealCustomer'] = !empty($list[$val]['dealCustomer']) ? (int)$list[$val]['dealCustomer'] : 0;

            $data[] = $item;
        }

        return resultArray(['data' => $data]);
    }

    /**
     * [客户公海条件]
     * @author Michael_xu
     * @param
     * @return
     */
    public function getWhereByPool()
    {
        $configModel = new \app\crm\model\ConfigData();
        $configInfo = $configModel->getData();
        $config = $configInfo['config'] ? : 0;
        $follow_day = $configInfo['follow_day'] ? : 0;
        $deal_day = $configInfo['deal_day'] ? : 0;
        $whereData = [];
        //启用
        if ($config == 1) {
            //默认公海条件(没有负责人或已经到期)
            $data['follow_time'] = time()-$follow_day*86400;
            $data['deal_time'] = time()-$deal_day*86400;
            $data['deal_status'] = '未成交';
            if ($follow_day < $deal_day) {
                $whereData = function($query) use ($data){
                    $query->where(['customer.owner_user_id'=>0])
                        ->whereOr(function ($query) use ($data) {
                            $query->where(function ($query) use ($data) {
                                $query->where(['customer.update_time' => array('elt',$data['follow_time'])])
                                    ->whereOr(['customer.deal_time' => array('elt',$data['deal_time'])]);
                            })
                                ->where(['customer.is_lock' => 0])
                                ->where(['customer.deal_status' => ['neq','已成交']]);
                        });
                };
            } else {
                $whereData = function($query) use ($data){
                    $query->where(['customer.owner_user_id'=>0])
                        ->whereOr(function ($query) use ($data) {
                            $query->where(function ($query) use ($data) {
                                $query->where(['customer.deal_time' => array('elt',$data['deal_time'])]);
                            })
                                ->where(['customer.is_lock' => 0])
                                ->where(['customer.deal_status' => ['neq','已成交']]);
                        });
                };
            }
        } else {
            $whereData['customer.owner_user_id'] = 0;
        }
        return $whereData ? : '';
    }

    /**
     * 员工客户满意度分析
     *
     * @param BiCustomerLogic $biCustomerLogic
     * @param string $param
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function customerSatisfaction(BiCustomerLogic $biCustomerLogic, $param='')
    {
        $param = $this->param;

        $param['start_time'] = !empty($param['start_time']) ? strtotime($param['start_time']) : '';
        $param['end_time']   = !empty($param['end_time'])   ? strtotime($param['end_time'])   : '';

        if (!empty($param['type'])) {
            # 日期工具类
            $timeArr = getTimeByType($param['type']);
            # 设置日期参数
            $param['start_time'] = $timeArr[0];
            $param['end_time']   = $timeArr[1];
        }

        $data = $biCustomerLogic->getCustomerSatisfaction($param);
        //导出使用
        if (!empty($param['excel_type'])) return $data;
        return resultArray(['data' => $data]);
    }

    /**
     * 产品满意度分析
     *
     * @param BiCustomerLogic $biCustomerLogic
     * @param string $param
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productSatisfaction(BiCustomerLogic $biCustomerLogic, $param='')
    {
        $param = $this->param;

        $param['start_time'] = !empty($param['start_time']) ? strtotime($param['start_time']) : '';
        $param['end_time']   = !empty($param['end_time'])   ? strtotime($param['end_time'])   : '';

        if (!empty($param['type'])) {
            # 日期工具类
            $timeArr = getTimeByType($param['type']);
            # 设置日期参数
            $param['start_time'] = $timeArr[0];
            $param['end_time'] = $timeArr[1];
        }

        $data = $biCustomerLogic->getProductSatisfaction($param);
        //导出使用
        if (!empty($param['excel_type'])) return $data;
        return resultArray(['data' => $data]);
    }

    /**
     * 导出
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excelExport()
    {
        $param = $this->param;
        $excel_type = $param['excel_type'];

        $type=[];
        $type['excel_types']=$param['excel_types'];

        switch ($param['excel_types']) {
            case 'statistics':

                $list = $this->statistics($param);

                $list=$list['list'];
                $type['type'] = '客户总量分析列表';
                break;
            case'recordList':
                $list = $this->recordList($param);
                $list=$list['list'];
                $type['type'] = '客户跟进次数分析';
                break;
            case 'recordMode':
                $list = $this->recordMode($param);
                $type['type'] = '客户跟进方式分析';
                break;
            case 'poolList':
                $list = $this->poolList($param);
                $type['type'] = '公海客户分析';
                break;
            case 'userCycle':
                $list = $this->userCycle($param);
                $list=$list['list'];
                $type['type'] = '员工客户成交周期分析';
                break;
            case 'customerSatisfaction':
                $list = $this->customerSatisfaction($param);
                $list=$list['list'];
                $type['type'] = '员工客户满意度分析';
                break;
            case 'productSatisfaction':
                $list = $this->productSatisfaction($param);
                $list=$list['list'];
                $type['type'] = '产品满意度分析';
                break;
        }

        if(empty($list)){
            return resultArray(['data'=>'数据不存在']);
        }
        $excelLogic = new ExcelLogic();
        $data = $excelLogic->biexcle($type, $list);
        return $data;
    }
}
