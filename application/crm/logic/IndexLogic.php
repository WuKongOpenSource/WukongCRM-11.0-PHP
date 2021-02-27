<?php

namespace app\crm\logic;

use app\admin\controller\ApiCommon;
use app\admin\model\Common;
use think\Db;

class IndexLogic extends Common
{
    protected $monthName = [
        '01' => 'january',
        '02' => 'february',
        '03' => 'march',
        '04' => 'april',
        '05' => 'may',
        '06' => 'june',
        '07' => 'july',
        '08' => 'august',
        '09' => 'september',
        '10' => 'october',
        '11' => 'november',
        '12' => 'december',
    ];

    /**
     * @param $param
     * @return array
     */
    public function index($param)
    {
        $adminModel = new \app\admin\model\Admin();
        $userModel = new \app\admin\model\User();
        $customerModel = new \app\crm\model\Customer();
        $contactsModel = new \app\crm\model\Contacts();
        $businessModel = new \app\crm\model\Business();
        $contractModel = new \app\crm\model\Contract();
        $receivablesModel = new \app\crm\model\Receivables();
        $activityModel = new \app\crm\model\Activity();

        $whereArr = $adminModel->getWhere($param, 1, ''); //统计条件
        $lastArr = $adminModel->getWhere($param, 1, '', true); //统计条件
        $userIds = $whereArr['userIds'];
        $between_time = $whereArr['between_time'];
        $last_between_time = $lastArr['between_time'];

        $customerNum = 0; //新增客户
        $customerLastNum = 0; //上期对比
        $contactsNum = 0; //新增联系人
        $contactsLastNum = 0; ////上期对比
        $businessNum = 0; //新增商机
        $businessLastNum = 0; //上期对比d
        $contractNum = 0; //新增合同
        $contractLastNum = 0; //上期对比
        $recordNum = 0; //新增跟进记录
        $recordLastNum = 0; //上期对比
        $businessMoneyNum = 0; //新增商机金额
        $businessLastMoneyNum = 0; //上期对比
        $contractMoneyNum = 0; //新增合同金额
        $contractLastMoneyNum = 0; //上期对比
        $receivablesMoneyNum = 0; //新增回款金额
        $receivablesLastMoneyNum = 0; //上期对比

        $where = [];
        $where['owner_user_id']['value'] = $userIds;
        $where['create_time']['start'] = $between_time[0];
        $where['create_time']['end'] = $between_time[1];
        $where['getCount'] = 1;

        $customer_auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'index');
        $contacts_auth_user_ids = $userModel->getUserByPer('crm', 'contacts', 'index');
        $business_auth_user_ids = $userModel->getUserByPer('crm', 'business', 'index');
        $contract_auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'index');
        $receivables_auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'index');
        $record_auth_user_ids = $userModel->getUserByPer('crm', 'activity', 'index');
        $resCount = queryCache(
            $this->getCountSql([
                'start_time' => $between_time[0],
                'end_time' => $between_time[1],
                'customer_auth_user_ids' => array_intersect($userIds, $customer_auth_user_ids) ? :[-1],
                'contacts_auth_user_ids' => array_intersect($userIds, $contacts_auth_user_ids) ? :[-1],
                'business_auth_user_ids' => array_intersect($userIds, $business_auth_user_ids) ? :[-1],
                'contract_auth_user_ids' => array_intersect($userIds, $contract_auth_user_ids) ? :[-1],
                'receivables_auth_user_ids' => array_intersect($userIds, $receivables_auth_user_ids) ? :[-1],
                'record_auth_user_ids' => array_intersect($userIds, $record_auth_user_ids) ? :[-1],
            ])
        );

        $resLastCount = queryCache(
            $this->getCountSql([
                'start_time' => $last_between_time[0],
                'end_time' => $last_between_time[1],
                'customer_auth_user_ids' => array_intersect($userIds, $customer_auth_user_ids) ? : [-1],
                'contacts_auth_user_ids' => array_intersect($userIds, $contacts_auth_user_ids) ? : [-1],
                'business_auth_user_ids' => array_intersect($userIds, $business_auth_user_ids) ? : [-1],
                'contract_auth_user_ids' => array_intersect($userIds, $contract_auth_user_ids) ? : [-1],
                'receivables_auth_user_ids' => array_intersect($userIds, $receivables_auth_user_ids) ? : [-1],
                'record_auth_user_ids' => array_intersect($userIds, $record_auth_user_ids) ? : [-1],
            ])
        );

        $customerNum = (int)$resCount[0]['count1'] ?: 0;
        $contactsNum = (int)$resCount[1]['count1'] ?: 0;
        $businessNum = (int)$resCount[2]['count1'] ?: 0;
        $contractNum = (int)$resCount[3]['count1'] ?: 0;
        $businessMoneyNum = $resCount[2]['count2'] ?: 0;
        $contractMoneyNum = $resCount[3]['count2'] ?: 0;
        $receivablesMoneyNum = $resCount[4]['count1'] ?: 0;
        $recordNum = (int)$resCount[5]['count1'] ?: 0;

        $customerLastNum = (int)$resLastCount[0]['count1'] ?: 0;
        $contactsLastNum = (int)$resLastCount[1]['count1'] ?: 0;
        $businessLastNum = (int)$resLastCount[2]['count1'] ?: 0;
        $contractLastNum = (int)$resLastCount[3]['count1'] ?: 0;
        $businessLastMoneyNum = $resLastCount[2]['count2'] ?: 0;
        $contractLastMoneyNum = $resLastCount[3]['count2'] ?: 0;
        $receivablesLastMoneyNum = $resLastCount[4]['count'] ?: 0;
        $recordLastNum = (int)$resLastCount[5]['count'] ?: 0;

        $data = [];
        $data['data']['customerNum'] = $customerNum;
        $data['prev']['customerNum'] = $this->getProportion($customerNum, $customerLastNum);

        $data['data']['contactsNum'] = $contactsNum;
        $data['prev']['contactsNum'] = $this->getProportion($contactsNum, $contactsLastNum);

        $data['data']['businessNum'] = $businessNum;
        $data['prev']['businessNum'] = $this->getProportion($businessNum, $businessLastNum);

        $data['data']['contractNum'] = $contractNum;
        $data['prev']['contractNum'] = $this->getProportion($contractNum, $contractLastNum);

        $data['data']['recordNum'] = $recordNum;
        $data['prev']['recordNum'] = $this->getProportion($recordNum, $recordLastNum);

        $data['data']['businessMoneyNum'] = $businessMoneyNum;
        $data['prev']['businessMoneyNum'] = $this->getProportion($businessMoneyNum, $businessLastMoneyNum);

        $data['data']['contractMoneyNum'] = $contractMoneyNum;
        $data['prev']['contractMoneyNum'] = $this->getProportion($contractMoneyNum, $contractLastMoneyNum);

        $data['data']['receivablesMoneyNum'] = $receivablesMoneyNum;
        $data['prev']['receivablesMoneyNum'] = $this->getProportion($receivablesMoneyNum, $receivablesLastMoneyNum);
        return $data;
    }

    public function getCountSql($param)
    {
        $countSql = "SELECT
        count(1) count1,
        0 count2
        FROM 5kcrm_crm_customer
        WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
        and owner_user_id IN (" . implode(',', $param['customer_auth_user_ids']) . ")
        UNION ALL
        SELECT
        count(1) AS count1,
        0 count2
        FROM 5kcrm_crm_contacts
        WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
        and owner_user_id IN (" . implode(',', $param['contacts_auth_user_ids']) . ")
        UNION ALL
        SELECT
        count(1) AS count1,
        SUM( CASE WHEN is_end IN (1, 0) THEN money ELSE 0 END) AS count2
        FROM 5kcrm_crm_business
        WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
        and owner_user_id IN (" . implode(',', $param['business_auth_user_ids']) . ")
        UNION ALL
        SELECT
        count(1) AS count1,
        SUM( CASE WHEN check_status = 2 THEN money ELSE 0 END) AS count2
        FROM 5kcrm_crm_contract
        WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
        and owner_user_id IN (" . implode(',', $param['contract_auth_user_ids']) . ")
        UNION ALL
        SELECT
        sum(money) AS count,
        0 count2
        FROM 5kcrm_crm_receivables
        WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
        AND check_status = 2
        and owner_user_id IN (" . implode(',', $param['receivables_auth_user_ids']) . ")                     
        UNION ALL
        SELECT
        count(1) AS count,
        0 count2
        FROM 5kcrm_crm_activity
        WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
        AND type = 1
        AND status = 1
        AND activity_type IN (1, 2, 3, 5, 6)
        and create_user_id IN (" . implode(',', $param['record_auth_user_ids']) . ")";
        return $countSql;
    }

    ///计算涨幅
    public function getProportion($now, $last)
    {
        $res = 0;
        if ($last && $last != 0.00) {
            if ($now && $now != 0.00) {
                $res = round(($now / $last), 2);
            }
        } else {
            if ($now && $now != 0.00) {
                $res = 1;
            }
        }
        return $res;
    }


    /**
     * 遗忘数据统计
     * @return mixed
     */
    public function getDataList($param)
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $customerModel = new \app\crm\model\Customer();
        $dateTime = date('Y-m-d H:i:s');
        $whereArr = $adminModel->getWhere($param, 1, ''); //统计条件
        $userIds = $whereArr['userIds'];
        //权限控制
        $auth_customer_user_ids = $userModel->getUserByPer('crm', 'customer', 'index');
        $auth_customer_user_ids = $auth_customer_user_ids ? array_intersect($userIds, $auth_customer_user_ids) : []; //取交集
        $owner_user_ids = array('in', $auth_customer_user_ids);

        $customerParam = [];
        $customerParam['limit'] = $param['limit'];
        $customerParam['page'] = $param['page'];
        $customerParam['search'] = $param['search'];
        $customerParam['getCount'] = 1;
        $customerParam['owner_user_id'] = $owner_user_ids;

        $sevenDaysParam['otherMap'] = " ( IFNULL('last_time','create_time') < " . strtotime('-1 week') . ") ";
        $fifteenDaysParam['otherMap'] = " ( IFNULL('last_time','create_time') < " . strtotime('15 day') . ") ";
        $oneMonthParam['otherMap'] = " ( IFNULL('last_time','create_time') < " . strtotime('-30 day') . ") ";
        $threeMonthParam['otherMap'] = " ( IFNULL('last_time','create_time') < " . strtotime('-3 month') . ") ";
        $sixMonthParam['otherMap'] = " ( IFNULL('last_time','create_time') < " . strtotime('-6 month') . ") ";
        $unContactParam['otherMap'] = " ( last_time < next_time AND next_time < now()) ";

        $data['sevenDays'] = $customerModel->getDataList(array_merge($customerParam, $sevenDaysParam))['dataCount'] ?: 0;
        $data['fifteenDays'] = $customerModel->getDataList(array_merge($customerParam, $fifteenDaysParam))['dataCount'] ?: 0;
        $data['oneMonth'] = $customerModel->getDataList(array_merge($customerParam, $oneMonthParam))['dataCount'] ?: 0;
        $data['threeMonth'] = $customerModel->getDataList(array_merge($customerParam, $threeMonthParam))['dataCount'] ?: 0;
        $data['sixMonth'] = $customerModel->getDataList(array_merge($customerParam, $sixMonthParam))['dataCount'] ?: 0;
        $data['unContactCustomerCount'] = $customerModel->getDataList(array_merge($customerParam, $unContactParam))['dataCount'] ?: 0;

        return $data;
    }

    /**
     * 遗忘数据列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function forgottenCustomerPageList($param)
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $customerModel = new \app\crm\model\Customer();
        $whereArr = $adminModel->getWhere($param, 1, ''); //统计条件
        $userIds = $whereArr['userIds'];
        //权限控制
        $auth_customer_user_ids = $userModel->getUserByPer('crm', 'customer', 'index');
        $auth_customer_user_ids = $auth_customer_user_ids ? array_intersect($userIds, $auth_customer_user_ids) : []; //取交集
        $owner_user_ids = array('in', $auth_customer_user_ids);

        $sql_unContactCustomerList = db('crm_customer')
            ->where(['owner_user_id' => $owner_user_ids])
            ->where('last_time < next_time AND next_time < now()')
            ->fetchSql()
            ->select();

        $label = $param['label'];
        $day = $param['day'] ?: '';
        $customerParam = [];
        $customerParam['limit'] = $param['limit'];
        $customerParam['page'] = $param['page'];
        $customerParam['search'] = $param['search'];
        $customerParam['owner_user_id'] = $owner_user_ids;

        switch ($label) {
            case 2 :
                $customerParam['otherMap'] = " ( IFNULL('last_time','create_time') < " . strtotime('-1 week') . ") ";
                break;
            case 3 :
                $customerParam['otherMap'] = "  (  IFNULL('last_time','create_time') < " . strtotime('15 day') . ") ";
                break;
            case 4 :
                $customerParam['otherMap'] = "  ( IFNULL('last_time','create_time') < " . strtotime('-30 day') . ") ";
                break;
            case 5 :
                $customerParam['otherMap'] = "  ( IFNULL('last_time','create_time') < " . strtotime('-3 month') . ") ";
                break;
            case 6 :
                $customerParam['otherMap'] = "  ( IFNULL('last_time','create_time') < " . strtotime('-6 month') . ") ";
                break;
        }
        return $customerModel->getDataList($customerParam);
    }

    /**
     * 排行榜
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ranking($param)
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $user_id = $param['user_id'] ?: [-1];
        $status = $param['label'] ?: 1; //1合同目标 2回款目标 3合同数 4新增客户数 5新增联系人数 6新增跟进记录数

        $whereArr = $adminModel->getWhere($param, 1, ''); //统计条件
        $userIds = $whereArr['userIds'];
        $between_time = $whereArr['between_time'];

        $auth_user_ids = $userModel->getUserByPer('bi', 'ranking', 'read');
        $auth_user_ids = $auth_user_ids ? array_intersect($userIds, $auth_user_ids) : []; //取交集
        switch ($param['label']) {
            //合同金额
            case '1':
                //合同金额
                $sql = db('crm_contract')
                    ->alias('contract')
                    ->join('__ADMIN_USER__ user', 'contract.owner_user_id=user.id')
                    ->join('__ADMIN_STRUCTURE__ structure', 'user.structure_id=structure.id')
                    ->field([
                        'SUM(CASE WHEN check_status = 2 THEN money ELSE 0 END) as money',
                        'contract.owner_user_id as owner_user_id',
                        'user.realname as realname',
                        'user.id',
                        'user.thumb_img',
                        'structure.name'
                    ])
                    ->where([
                        'contract.owner_user_id' => ['in', $auth_user_ids],
                        'contract.create_time' => ['between', $between_time],
                    ])
                    ->group('contract.owner_user_id')
                    ->order('money desc,owner_user_id asc')
                    ->fetchSql()
                    ->select();
                $list = queryCache($sql, 200);
                break;
            //回款金额
            case '2':
                //回款金额
                $sql1 = db('crm_receivables')
                    ->alias('receivables')
                    ->join('__ADMIN_USER__ user', 'receivables.owner_user_id=user.id')
                    ->join('__ADMIN_STRUCTURE__ structure', 'user.structure_id=structure.id')
                    ->field([
                        'SUM(CASE WHEN receivables.check_status = 2 THEN receivables.money ELSE 0 END) as money',
                        'receivables.owner_user_id as owner_user_id',
                        'user.realname as realname',
                        'user.id',
                        'user.thumb_img',
                        'structure.name'
                    ])
                    ->where([
                        'receivables.owner_user_id' => ['in', $auth_user_ids],
                        'receivables.create_time' => ['between', $between_time],
                        'receivables.check_status' => 2
                    ])
                    ->group('receivables.owner_user_id')
                    ->order('money desc,owner_user_id asc')
                    ->fetchSql()
                    ->select();
                $list = queryCache($sql1, 200);
                break;
            //合同数
            case '3':
                //合同
                $where_contract['contract.check_status'] = 2; //审核通过
                $list = db('crm_contract')
                    ->alias('contract')
                    ->join('__ADMIN_USER__ user', 'contract.owner_user_id=user.id')
                    ->join('__ADMIN_STRUCTURE__ structure', 'user.structure_id=structure.id')
                    ->field([
                        'count(contract.contract_id) as count',
                        'user.realname as realname',
                        'user.id',
                        'user.thumb_img',
                        'structure.name',
                        'contract.owner_user_id as owner_user_id'])
                    ->where([
                        'contract.owner_user_id' => ['in', $auth_user_ids],
                        'contract.create_time' => ['between', $between_time],
                        'contract.check_status' => 2
                    ])
                    ->group('contract.owner_user_id')
                    ->order('count desc,owner_user_id asc')
                    ->select();
                break;
            //新增客户数
            case '4':
                //新增客户
                $list = db('crm_customer')
                    ->alias('customer')
                    ->join('__ADMIN_USER__ user', 'customer.create_user_id=user.id')
                    ->join('__ADMIN_STRUCTURE__ structure', 'user.structure_id=structure.id')
                    ->field([
                        'count(customer.customer_id) as count',
                        'user.realname as realname',
                        'user.id',
                        'user.thumb_img',
                        'structure.name',
                        'customer.owner_user_id as owner_user_id'])
                    ->where([
                        'customer.owner_user_id' => ['in', $auth_user_ids],
                        'customer.create_time' => ['between', $between_time],
                    ])
                    ->group('customer.owner_user_id')
                    ->order('count desc,owner_user_id asc')
                    ->select();
                break;
            //新增联系人
            case '5':
                //新增联系人
                $list = db('crm_contacts')
                    ->alias('contacts')
                    ->join('__ADMIN_USER__ user', 'contacts.owner_user_id=user.id')
                    ->join('__ADMIN_STRUCTURE__ structure', 'user.structure_id=structure.id')
                    ->field([
                        'count(contacts.contacts_id) as count',
                        'user.realname as realname',
                        'user.id',
                        'user.thumb_img',
                        'structure.name',
                        'contacts.contacts_id',
                        'contacts.owner_user_id as owner_user_id'])
                    ->where([
                        'contacts.owner_user_id' => ['in', $auth_user_ids],
                        'contacts.create_time' => ['between', $between_time],
                    ])
                    ->group('contacts.owner_user_id')
                    ->order('count desc,owner_user_id asc')
                    ->select();
                break;
            //新增跟进记录
            case '8':
                //新增跟进
                $list = db('crm_activity')
                    ->alias('activity')
                    ->join('__ADMIN_USER__ user', 'activity.create_user_id=user.id')
                    ->field([
                        'count(activity.activity_id) as count',
                        'user.realname as realname',
                        'user.id',
                        'user.thumb_img',
                        'activity.create_user_id as create_user_id'])
                    ->where([
                        'activity.create_user_id' => ['in', $auth_user_ids],
                        'activity.create_time' => ['between', $between_time],
                        'activity.activity_type' => ['in', [1, 2, 3, 5, 6]],
                        'activity.type' => 1,
                        'activity.status' => 1
                    ])
                    ->group('activity.create_user_id')
                    ->order('count desc,create_user_id asc')
                    ->select();
                break;
        }

        //业绩目标
        $between_time = getTimeByType($param['type']);
        $start_time = $between_time[0];
        $end_time = $between_time[1];
        $where_achievement = [];
        $where_achievement['status'] = $param['label'];
        //获取时间段包含年份
        $year = getYearByTime($start_time, $end_time);
        $where_achievement['year'] = array('in', $year);
        $achievement = '';
        //获取需要查询的月份
        $month = getmonthByTime($start_time, $end_time);
        //月份
        foreach ($month as $key => $val) {
            foreach ($val as $key1 => $val1) {
                $achievement = $this->monthName[$val1];
            }
        }
        if ($param['dataType'] == 1 || $param['dataType'] == 2) {
            $where_achievement['type'] = 3;
        }
        if ($param['dataType'] == 3 || $param['dataType'] == 4) {
            $where_achievement['type'] = 2;
        }
        $userInfo = new ApiCommon();
        $user_id = $userInfo->userInfo;
        $userName = db('admin_user')
            ->alias('user')
            ->join('__ADMIN_STRUCTURE__ structure', 'user.structure_id=structure.id')
            ->where('user.id', $user_id['id'])
            ->field('user.id,user.thumb_img,user.realname,structure.name')->find();
        $ranking = [];
        foreach ($list as $k => $v) {
            $where_achievement['obj_id'] = $v['owner_user_id'];
            $achievementMoney = db('crm_achievement')->where($where_achievement)->find();
            $v['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
            if ($achievementMoney[$achievement] == 0.00) {
                $v['rate'] = 0.00;
            } else {
                $v['rate'] = (int)$v['money'] / $achievementMoney[$achievement];
            }
            if ($userName['realname'] == $v['realname']) {
                $list['self']['sort'] = $k + 1;
                $list['self']['thumb_img'] = $userName['thumb_img'] ? getFullPath($userName['thumb_img']) : '';
                $list['self']['realname'] = $v['realname'];
                if(in_array($param['label'],['1','2'])){
                    $list['self']['rate'] = $v['rate'];$list['self']['money'] = $v['money'];
                }
                if(!in_array($param['label'],['1','2'])) $list['self']['count'] = $v['count'];
                $list['self']['owner_user_id'] = $v['owner_user_id'];
                $list['self']['structureName'] = $userName['name'];
                $list['self']['user_id'] = $userName['id'];
            } else {
                $money['self'] = [];
            }
            $ranking['ranking'][] = $v;
            $ranking['self'] = $list['self'];
        }

        $data = [];
        $data = $ranking ?: [];
        return $data;
    }

    /**
     * 数据汇总
     * @param $param
     */
    public function queryDataInfo($param)
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $whereArr = $adminModel->getWhere($param, 1, ''); //统计条件
        $user_id = $param['user_id'] ?: [-1];
        $userIds = $whereArr['userIds'];
        $between_time = $whereArr['between_time'];

        $customer_auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'index');
        $contacts_auth_user_ids = $userModel->getUserByPer('crm', 'contacts', 'index');
        $business_auth_user_ids = $userModel->getUserByPer('crm', 'business', 'index');
        $contract_auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'index');
        $receivables_auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'index');
        $record_auth_user_ids = $userModel->getUserByPer('crm', 'activity', 'index');

        $resDataArr = [];
        for ($i = 1; $i <= 5; $i++) {
            $resData = queryCache(
                $this->getQueryDataSql([
                    'type' => $i,
                    'start_time' => $between_time[0],
                    'end_time' => $between_time[1],
                    'customer_auth_user_ids' => array_intersect($userIds, $customer_auth_user_ids) ? : [-1],
                    'contacts_auth_user_ids' => array_intersect($userIds, $contacts_auth_user_ids) ? : [-1],
                    'business_auth_user_ids' => array_intersect($userIds, $business_auth_user_ids) ? : [-1],
                    'contract_auth_user_ids' => array_intersect($userIds, $contract_auth_user_ids) ? : [-1],
                    'receivables_auth_user_ids' => array_intersect($userIds, $receivables_auth_user_ids) ? : [-1],
                    'record_auth_user_ids' => array_intersect($userIds, $record_auth_user_ids) ? : [-1],
                ])
            );
            $resDataArr = array_merge($resDataArr, $resData[0]);
        }

        return $resDataArr;
    }

    /**
     * [数据汇总sql]
     * @return
     * @author Michael_xu
     */
    public function getQueryDataSql($param)
    {
        switch ($param['type']) {
            case 1 :
                $countSql = "SELECT
                count(1) allCustomer,
                COUNT(CASE WHEN deal_status = '已成交' THEN 1 ELSE NULL END) AS dealCustomer
                FROM 5kcrm_crm_customer
                WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
                AND owner_user_id IN (" . implode(',', $param['customer_auth_user_ids']) . ")";
                break;
            case 2 :
                $countSql = "SELECT
                count(1) activityNum,
                COUNT(CASE WHEN b.activity_type_id in (SELECT customer_id FROM 5kcrm_crm_customer
                WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
                AND owner_user_id IN (" . implode(',', $param['customer_auth_user_ids']) . ")
                ) THEN 1 ELSE NULL END ) as activityRealNum
                FROM 5kcrm_crm_activity AS b
                WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
                AND b.type = '1'
                AND b.activity_type = '2'                
                AND b.status = '1'                
                AND b.create_user_id IN (" . implode(',', $param['record_auth_user_ids']) . ")";
                break;
            case 3 :
                $countSql = "SELECT
                count(1) allBusiness,
                COUNT(CASE WHEN is_end = 1 THEN 1 ELSE NULL END) AS endBusiness,
                SUM( CASE WHEN is_end IN (1, 0) THEN money ELSE 0 END) AS businessMoney
                FROM 5kcrm_crm_business
                WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
                AND owner_user_id IN (" . implode(',', $param['business_auth_user_ids']) . ")";
                break;
            case 4 :
                $countSql = "SELECT
                count(1) allContract,
                SUM( CASE WHEN check_status = 2 THEN money ELSE 0 END) AS contractMoney
                FROM 5kcrm_crm_contract
                WHERE create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
                AND owner_user_id IN (" . implode(',', $param['business_auth_user_ids']) . ")";
                break;
            case 5 :
                $countSql = "SELECT
                SUM( CASE WHEN r.check_status = 2 THEN r.money ELSE 0 END) AS receivablesMoney,
                SUM(CASE WHEN p.money > 0 THEN p.money ELSE 0 END) AS planMoney
                FROM 5kcrm_crm_receivables as r
                LEFT JOIN 5kcrm_crm_receivables_plan AS p ON p.receivables_id = r.receivables_id                 
                WHERE r.create_time BETWEEN " . $param['start_time'] . " AND " . $param['end_time'] . "
                AND r.owner_user_id IN (" . implode(',', $param['business_auth_user_ids']) . ")";
                break;
        }
        return $countSql;
    }

    /**
     * 赢单输单查看
     */
    public function businessList($param)
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $whereArr = $adminModel->getWhere($param, 1, ''); //统计条件
        $user_id = $param['user_id'] ?: [-1];
        $userIds = $whereArr['userIds'];
        $between_time = $whereArr['between_time'];
        //权限控制
        $where_customer['create_time'] = array('between', $between_time);
        $auth_customer_user_ids = $userModel->getUserByPer('bi', 'ranking', 'read');
        $auth_customer_user_ids = $auth_customer_user_ids ? array_intersect($userIds, $auth_customer_user_ids) : []; //取交集
        $where_customer['owner_user_id'] = array('in', $auth_customer_user_ids);
        $businessList = db('crm_business')
            ->where([
                'owner_user_id' => $where_customer['owner_user_id'],
                'create_time' => $where_customer['create_time'],
                'status_id' => $param['status_id']
            ])
            ->select();
        $data = [];
        $data['businesslist'] = $businessList;
        return $data;
    }

    /**
     * 仪表盘布局列表
     */
    public function dashboard($param)
    {
        $data = [];
        $list = db('crm_dashboard')->where('user_id', $param['user_id'])->find();

        if ($list) {
            $data = unserialize($list['dashboard']);
            return $data ?: [];
        } else {
            $data['left'][0]['modelId'] = 1;
            $data['left'][0]['list'] = 1;
            $data['left'][0]['isHidden'] = 0;
            $data['left'][]['modelId'] = 5;
            $data['left'][1]['list'] = 1;
            $data['left'][1]['isHidden'] = 0;
            $data['left'][2]['modelId'] = 7;
            $data['left'][2]['list'] = 1;
            $data['left'][2]['isHidden'] = 0;

            $data['right'][0]['modelId'] = 2;
            $data['right'][0]['list'] = 2;
            $data['right'][0]['isHidden'] = 0;
            $data['right'][1]['modelId'] = 4;
            $data['right'][1]['list'] = 2;
            $data['right'][1]['isHidden'] = 0;
            $data['right'][2]['modelId'] = 6;
            $data['right'][2]['list'] = 2;
            $data['right'][2]['isHidden'] = 0;
            return $data;
        }

    }

    /**
     * 修改自定义仪表盘
     * @param $param
     */
    public function updateDashboard($param)
    {

        $data = db('crm_dashboard')->where('user_id', $param['user_id'])->find();
        if ($data) {
            $list = db('crm_dashboard')->where('user_id', $param['user_id'])->update(['dashboard' => serialize($param['dashboard'])]);
            return $list;
        } else {
            $list = db('crm_dashboard')->insert(['user_id' => $param['user_id'], 'dashboard' => serialize($param['dashboard'])]);
            return $list;
        }

    }

    /**
     * 跟进记录列表
     * @param $param
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function activityList($param)
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $whereArr = $adminModel->getWhere($param, 1, ''); //统计条件
        if ($param['dataType'] == 1) {
            $userIds = [];
            $userIds[] = $param['user_id'];
        } else {
            $userIds = $whereArr['userIds'];
        }

        //权限控制
        if (!empty($param['type'])) {
            $last_where_contract = getTimeByType($param['type']);
            $between_time = [$last_where_contract[0], $last_where_contract[1]];
            $where_activity['t.create_time'] = array('between', $between_time);
        } else {
            //自定义时间
            $start_time = $param['start_time'] ?: strtotime(date('Y-01-01', time()));
            $end_time = $param['end_time'] ? strtotime(date('Y-m-01', $param['end_time']) . ' +1 month -1 day') : strtotime(date('Y-m-01', time()) . ' +1 month -1 day');
            $where_activity['t.create_time'] = ['between', [$start_time, $end_time]];
        }
        $auth_customer_user_ids = $userModel->getUserByPer('crm', 'activity', 'index');
        $auth_customer_user_ids = $auth_customer_user_ids ? array_intersect($userIds, $auth_customer_user_ids) : []; //取交集
        $where_activity['t.create_user_id'] = array('in', $auth_customer_user_ids);
        # 跟进记录类型
        $where_activity['t.activity_type'] = $param['activity_type'];
        $where_activity['t.type'] = 1;
        $where_activity['t.status'] = 1;
        if ($param['label'] == 2) {
            if ($param['search']) {
                $type['t.content'] = array('like', '%' . $param['search'] . '%');
            }
            //跟进记录类型
            if ($param['crmType'] == 0) {
                $type['t.activity_type'] = ['in', [1, 2, 3, 5, 6]];
            } else {
                $type['t.activity_type'] = $param['crmType'];
            }
            if ($param['type']) {
                $timeAry = getTimeByType($param['type']);
                $between_time = [$timeAry[0], $timeAry[1]];
                $type['t.create_time'] = array('between', $between_time);
            } else {
                //自定义时间
                $start_time = $param['start_time'] ? strtotime($param['start_time'] . ' 00:00:00') : strtotime(date('Y-m-01', time()));
                $end_time = $param['end_time'] ? strtotime($param['end_time'] . ' 23:59:59') : strtotime(date('Y-m-01', time()) . ' +1 month -1 day');
                $type['t.create_time'] = ['between', [$start_time, $end_time]];
            }

            if ($param['queryType'] == 0) {
                $type['t.type'] = ['in', [1, 4]];
            } else {
                $type['t.type'] = $param['queryType'];
            }
            if ($param['user'] == '') {
                if ($param['subUser'] == '0') {
                    $type['t.create_user_id'] = $param['id'];
                    //下属创建
                } elseif ($param['subUser'] == '1') {
                    $subList = getSubUserId(false, 0, $param['id']);
                    $subStr = $subList ? implode(',', $subList) : '-1';
                    $type['t.create_user_id'] = array('in', $subStr);
                } elseif ($param['subUser'] == '') {
                    $userIds = getSubUserId(true, 0, $param['id']);
                    $subStr = $userIds ? implode(',', $userIds) : '-1';
                    $type['t.create_user_id'] = array('in', $subStr);
                }
            } else {
                $type['t.create_user_id'] = $param['user'];
            }
            $type['t.status'] = 1;
            $list = db('crm_activity')
                ->alias('t')
                ->join('__ADMIN_USER__ user', 'user.id = t.create_user_id', 'LEFT')
                ->field('t.content,t.next_time,t.category,t.activity_type,t.type,t.activity_id,t.activity_type_id,t.update_time,t.create_time,user.realname as create_user_name,user.thumb_img')
                ->where($type)
                ->page($param['page'], $param['limit'])
                ->order('t.create_time desc')
                ->select();
            $dataCount = db('crm_activity')
                ->alias('t')
                ->join('__ADMIN_USER__ user', 'user.id = t.create_user_id', 'LEFT')
                ->where($type)
                ->count();
            foreach ($list as $k => $v) {
                // 业务名称（客户、线索、合同...）
                if ($v['activity_type'] == 1) {
                    $activity_name = Db::name('crm_leads')->where('leads_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                }
                if ($v['activity_type'] == 2) {
                    $activity_name = Db::name('crm_customer')->where('customer_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                    $activity_business = Db::name('crm_business')->where('business_id', $v['activity_type_id'])->select();
                    $activity_contacts = Db::name('crm_contacts')->where('contacts_id', $v['activity_type_id'])->select();
                    $list[$k]['business_list'] = $activity_business ?: [];
                    $list[$k]['contacts_list'] = $activity_contacts ?: [];
                }

                if ($v['activity_type'] == 3) {
                    $activity_name = Db::name('crm_contacts')->where('contacts_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                }
                if ($v['activity_type'] == 5) {
                    $activity_name = Db::name('crm_business')->where('business_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                }
                if ($v['activity_type'] == 6) {
                    $activity_name = Db::name('crm_contract')->where('contract_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                }
                $list[$k]['create_time'] = !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : null;
                $list[$k]['update_time'] = !empty($v['update_time']) ? date('Y-m-d H:i:s', $v['update_time']) : null;
                $list[$k]['next_time'] = !empty($v['next_time']) ? date('Y-m-d H:i:s', $v['next_time']) : null;
                $fileModel = new \app\admin\model\File();
                $recordModel = new \app\admin\model\Record();
                $f_where = [];
                $f_where['module_id'] = $v['activity_id'];
                $relation_list = [];
                $f_where['module'] = 'crm_activity';
                $relation_list = $recordModel->getListByRelationId('activity', $v['activity_id']);
                $dataInfo = [];
                $newFileList = [];
                $imgList = [];
                $fileList = [];
                $newFileList = $fileModel->getDataList($f_where, 'all');
                if ($newFileList['list']) {
                    foreach ($newFileList['list'] as $val) {
                        if ($val['types'] == 'file') {
                            $fileList[] = $val;
                        } else {
                            $imgList[] = $val;
                        }
                    }
                }
                $list[$k]['fileList'] = $fileList ?: [];
                $list[$k]['imgList'] = $imgList ?: [];
                $dataInfo['customerList'] = $relation_list['customer_list'] ?: [];
                $dataInfo['contactsList'] = $relation_list['contacts_list'] ?: [];
                $dataInfo['businessList'] = $relation_list['business_list'] ?: [];
                $dataInfo['contractList'] = $relation_list['contract_list'] ?: [];
                $list[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
                $list[$k]['dataInfo'] = $dataInfo ?: [];

            }
        } else {
            $list = db('crm_activity')
                ->alias('t')
                ->join('__ADMIN_USER__ user', 'user.id = t.create_user_id', 'LEFT')
                ->where($where_activity)
                ->field('t.content,t.next_time,t.update_time,t.create_time,t.category,t.activity_type,t.type,t.activity_id,t.activity_type_id,user.realname as create_user_name,user.thumb_img')
                ->page($param['page'], $param['limit'])
                ->order('t.create_time desc')
                ->select();
            $dataCount = db('crm_activity')->alias('t')
                ->join('__ADMIN_USER__ user', 'user.id = t.create_user_id', 'LEFT')->where($where_activity)->count();
            foreach ($list as $k => $v) {
                // 业务名称（客户、线索、合同...）
                if ($param['activity_type'] == 1) {
                    $activity_name = Db::name('crm_leads')->where('leads_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                }
                if ($param['activity_type'] == 2) {
                    $activity_name = Db::name('crm_customer')->where('customer_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                    $activity_business = Db::name('crm_business')->where('business_id', $v['activity_type_id'])->select();
                    $activity_contacts = Db::name('crm_contacts')->where('contacts_id', $v['activity_type_id'])->select();
                    $list[$k]['business_list'] = $activity_business ?: [];
                    $list[$k]['contacts_list'] = $activity_contacts ?: [];
                }

                if ($param['activity_type'] == 3) {
                    $activity_name = Db::name('crm_contacts')->where('contacts_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                }
                if ($param['activity_type'] == 5) {
                    $activity_name = Db::name('crm_business')->where('business_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                }
                if ($param['activity_type'] == 6) {
                    $activity_name = Db::name('crm_contract')->where('contract_id', $v['activity_type_id'])->find();
                    $list[$k]['activity_type_name'] = $activity_name['name'];
                }
                $list[$k]['create_time'] = !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : null;
                $list[$k]['update_time'] = !empty($v['update_time']) ? date('Y-m-d H:i:s', $v['update_time']) : null;
                $list[$k]['next_time'] = !empty($v['next_time']) ? date('Y-m-d H:i:s', $v['next_time']) : null;
                $fileModel = new \app\admin\model\File();
                $recordModel = new \app\admin\model\Record();
                $f_where = [];
                $f_where['module_id'] = $v['activity_id'];
                $relation_list = [];
                $f_where['module'] = 'crm_activity';
                $relation_list = $recordModel->getListByRelationId('activity', $v['activity_id']);
                $dataInfo = [];
                $newFileList = [];
                $imgList = [];
                $fileList = [];
                $newFileList = $fileModel->getDataList($f_where, 'all');
                if ($newFileList['list']) {
                    foreach ($newFileList['list'] as $val) {
                        if ($val['types'] == 'file') {
                            $fileList[] = $val;
                        } else {
                            $imgList[] = $val;
                        }
                    }
                }
                $list[$k]['fileList'] = $fileList ?: [];
                $list[$k]['imgList'] = $imgList ?: [];
                $dataInfo['customerList'] = $relation_list['customer_list'] ?: [];
                $dataInfo['contactsList'] = $relation_list['contacts_list'] ?: [];
                $dataInfo['businessList'] = $relation_list['business_list'] ?: [];
                $dataInfo['contractList'] = $relation_list['contract_list'] ?: [];
                $list[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
                $list[$k]['dataInfo'] = $dataInfo ?: [];
            }
        }


        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ?: 0;
        if ($param['page'] != 1 && ($param['page'] * $param['limit']) >= $dataCount) {
            $data['firstPage'] = false;
            $data['lastPage'] = true;
        } else if ($param['page'] != 1 && (int)($param['page'] * $param['limit']) < $dataCount) {
            $data['firstPage'] = false;
            $data['lastPage'] = false;
        } else if ($param['page'] == 1) {
            $data['firstPage'] = true;
            $data['lastPage'] = false;
        }

        return $data ?: [];
    }
}