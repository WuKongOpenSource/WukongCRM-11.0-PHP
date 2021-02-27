<?php

namespace app\oa\logic;


use app\admin\model\Common;
use app\oa\model\Log;
use think\Db;
use app\admin\model\Comment as CommentModel;

class LogLogic extends Common
{
    protected $monthName = [
        '1' => '一',
        '2' => '二',
        '3' => '三',
        '4' => '四',
        '5' => '五',
        '6' => '六',
    ];

    //时间
    public function pastTime()
    {
        $dataTime['start_time'] = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $dataTime['end_time'] = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        return $dataTime;
    }

    /**
     * [getDataList 日志list]
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     * @author Michael_xu
     */
    public function getDataList($request)
    {
        $userModel = new \app\admin\model\User();
        $commonModel = new \app\admin\model\Comment();
        $recordModel = new \app\admin\model\Record();
        $user_id = $request['read_user_id'];
        $by = $request['by'] ?: '';

        $map = [];
        $search = $request['search'];
        if (isset($request['search']) && $request['search']) {
            //普通筛选
            $searchMap = function ($query) use ($search) {
                $query->where('log.content', array('like', '%' . $search . '%'))
                    ->whereOr('log.tomorrow', array('like', '%' . $search . '%'))
                    ->whereOr('log.question', array('like', '%' . $search . '%'));
            };
        }
        if ($request['category_id']) {
            $map['log.category_id'] = $request['category_id'];
        }
        if ($request['create_user_id']) {
            $map['log.create_user_id'] = $request['create_user_id'];
        }
        if ($request['type']) {
            $timeAry = ByDateTime($request['type']);
            $between_time = [$timeAry[0], $timeAry[1]];
            $map['log.create_time'] = ['between', $between_time];
        } else {
            //自定义时间
            $start_time = $request['start_time'] ? strtotime($request['start_time'].' 00:00:00') : strtotime(date('Y-m-01', time()));
            $end_time = $request['end_time'] ? strtotime($request['end_time'].' 23:59:59') : strtotime(date('Y-m-01', time()) . ' +1 month -1 day');
            $map['log.create_time'] = ['between', [$start_time, $end_time]];
        }
        $requestData = $this->requestData();
        //获取权限范围内的员工
        //获取权限范围内的员工
        $auth_user_ids = getSubUserId();
        $dataWhere['user_id'] = $user_id;
        $dataWhere['structure_id'] = $request['structure_id'];
        $dataWhere['auth_user_ids'] = $auth_user_ids;
        $logMap = '';
        if ($request['send_user_id'] != '') {
            $map['log.create_user_id'] = ['in', trim(arrayToString($request['send_user_id']), ',')];
        }
        switch ($by) {
            case 'me' :
                $map['log.create_user_id'] = $user_id;
                break;
            case 'other':
                $logMap = function ($query) use ($dataWhere) {
                    $query->where('log.send_user_ids', array('like', '%,' . $dataWhere['user_id'] . ',%'))
                        ->whereOr('log.send_structure_ids', array('like', '%,' . $dataWhere['structure_id'] . ',%'));
                };
                break;
            default :
                $logMap = function ($query) use ($dataWhere) {
                    $query->where('log.create_user_id', array('in', implode(',', $dataWhere['auth_user_ids'])))
                        ->whereOr('log.send_user_ids', array('like', '%,' . $dataWhere['user_id'] . ',%'))
                        ->whereOr('log.send_structure_ids', array('like', '%,' . $dataWhere['structure_id'] . ',%'));
                };
                break;
        }
        $list = Db::name('OaLog')
            ->where($map)
            ->where($logMap)
            ->where($searchMap)
            ->alias('log')
            ->join('__ADMIN_USER__ user', 'user.id = log.create_user_id', 'LEFT')
            ->field('log.*,user.realname')
            ->order('log.update_time desc')
            ->select();
        foreach ($list as $k => $v) {
            $param['type_id'] = $v['log_id'];
            $param['type'] = 'oa_log';


            $list[$k]['replyList'] = $commonModel->read($param);

            $list[$k]['replyList'] = arrayToString(array_column($list[$k]['replyList'], 'content'));
            $list[$k]['replyList'] = str_replace(',', ' ', $list[$k]['replyList']);
            $list[$k]['send_user_name'] = arrayToString(array_column($userModel->getListByStr($v['send_user_ids']), 'realname'));
            $list[$k]['send_user_name'] = str_replace(",", " ", $list[$k]['send_user_name']);
            if ($v['category_id'] == 1) {
                $list[$k]['category_name'] = '日报';
            } elseif ($v['category_id'] == 2) {
                $list[$k]['category_name'] = '周报';
            } else {
                $list[$k]['category_name'] = '月报';
            }
            $list[$k]['create_time'] = date('Y-m-d', $v['create_time']);
            //相关业务
            $relationArr = $recordModel->getListByRelationId('log', $v['log_id']);
            $list[$k]['relation'] = arrayToString(array_column($relationArr['businessList'], 'name')) . ' ' .
                arrayToString(array_column($relationArr['contactsList'], 'name')) . ' ' .
                arrayToString(array_column($relationArr['contractList'], 'name')) . ' ' .
                arrayToString(array_column($relationArr['customerList'], 'name'));
            $list[$k]['relation'] = str_replace(',', ' ', $list[$k]['relation']);
        }
        return $list;
    }

    /**
     * 随机获取日志欢迎语
     * @return array|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function LogWelcomeSpeech()
    {

        $logWelcome = db('admin_oalog_rule')->where('type', 4)->find();
        $data = unserialize($logWelcome['mark']);
        $key = array_rand($data);
        return $data[$key];
    }

    /**
     * 日报完成情况
     * @param $param
     */
    public function completeStats($param)
    {
        # 是否开启了日、周、月报
        $logStatus = Db::name('admin_oalog_rule')->where('type', $param['type'])->value('status');
        //每日完成数量
        $users = getSubUserId(false, 0, $param['user_id']);
        $logCount = Db::name('admin_oalog_rule')->where(['userIds' => ['like', '%' . $param['user_id'] . '%'], 'type' => $param['type']])->find();

        if ($logCount['userIds'] == '') {
            $logCount = count($users) ?: 1;
        } else {
            $logCount = count(array_intersect(stringToArray($logCount['userIds']), $users)) ?: 0;
        }
        //日志统计时间
        $type = db('admin_oalog_rule')->where('type', $param['type'])->find();
        if ($param['type'] == 1) {
            $start_time = strtotime(date('Y-m-d', time()) . ' ' . $type['start_time'] . ':00');
            $end_time = strtotime(date('Y-m-d', time()) . ' ' . $type['end_time'] . ':00');
            $between_time = [$start_time, $end_time];
            $timeCount = array('between', $between_time);

        } elseif ($param['type'] == 2) {
            //本周
            $start_time = strtotime(date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - $type['start_time']) * 24 * 3600)));
            $end_time = strtotime(date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - $type['end_time']) * 24 * 3600)) . '23:59:59');
            $between_time = [$start_time, $end_time];
            $timeCount = array('between', $between_time);
        } elseif ($param['type'] == 3) {
            //本月
            $start_time = strtotime(date('Y-m-d', strtotime(date('Y-m', time()) . '-' . date($type['start_time'], time()) . ' 00:00:00')));
            $end_time = strtotime(date('Y-m-d', strtotime(date('Y-m', time()) . '-' . date($type['end_time'], time()) . ' 23:59:59')));
            $between_time = [$start_time, $end_time];
            $timeCount = array('between', $between_time);
        }

        $endCount = Db::name('OaLog')->where(['create_time' => $timeCount, 'create_user_id' => ['in', arrayToString($users)]])->count();
        $data = [];
        $data['logCount'] = $logCount ?: 0;

        $data['endCount'] = $endCount ?: 0;
        # 判断是否显示日志统计按钮（日报、周报、月报）
        $data['status'] = !empty($logStatus) ? 1 : 0;
        return $data;
    }

    /**
     * 月报完成情况
     * @param $param
     */
    public function logBulletin($param)
    {
        //本月完成日志篇数
        $timeAry = getTimeByType('month');
        $between_time = [$timeAry[0], $timeAry[1]];
        $mothCount = array('between', $between_time);
        //日志统计时间
        $type = db('admin_oalog_rule')->where('type', 1)->find();
        $start_time = strtotime(date('Y-m-d', time()) . ' ' . $type['start_time'] . ':00');
        $end_time = strtotime(date('Y-m-d', time()) . ' ' . $type['end_time'] . ':00');
        $between_time = [$start_time, $end_time];
        $timeCount = array('between', $between_time);
        $mothEndCount = Db::name('OaLog')->where(['create_time' => $mothCount, 'create_user_id' => $param['user_id']])->count();
        $start_log = Db::name('OaLog')->where(['create_time' => $timeCount, 'create_user_id' => $param['user_id']])->count();
        $data = [];
        $data['logCount'] = 0;
        $data['startLog'] = $start_log ?: 0;
        $data['mothEndCount'] = $mothEndCount;
        return $data;
    }

    /**
     * 每日销售简报
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function oneBulletin($param)
    {
        $user_id = $param['user_id'];
        $start_time = $this->pastTime();
        $between_time = [$start_time['start_time'], $start_time['end_time']];
        $map['owner_user_id'] = $user_id;
        $map['create_time'] = array('between', $between_time);
        $customerNum = Db::name('CrmCustomer')
            ->where($map)
            ->count();
        $businessNum = Db::name('CrmBusiness')
            ->where($map)
            ->count();
        $contractNum = Db::name('CrmContract')
            ->where($map)
            ->count();
        $receivablesMoneyNum = Db::name('CrmReceivables')
            ->where($map)
            ->sum('money');
        unset($map['owner_user_id']);
        $map['create_user_id'] = $user_id;
        $recordNum = db('crm_activity')
            ->where($map)
            ->where(['type' => 1, 'activity_type' => ['<', 7],'status'=>1])
            ->count();
        $data = [];
        $data['data']['customerNum'] = $customerNum;
        $data['data']['businessNum'] = $businessNum;
        $data['data']['contractNum'] = $contractNum;
        $data['data']['receivablesMoneyNum'] = $receivablesMoneyNum;
        $data['data']['recordNum'] = $recordNum;
        return $data;
    }

    /**
     * 今日新增客户
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function Bulletin($param)
    {
        $user_id = $param['user_id'];
        $search = $param['search'];
        $start_time = $this->pastTime();
        $log_id = $param['log_id'] ?: 0;
        if (empty($log_id)) {
            $between_time = [$start_time['start_time'], $start_time['end_time']];
            $map1['customer.owner_user_id'] = $user_id;
            $map2['business.owner_user_id'] = $user_id;
            $map3['contract.owner_user_id'] = $user_id;
            $map4['receivables.owner_user_id'] = $user_id;

        } else {
            $item = Db::name('OaLog')->where('log_id', $log_id)->find();
            $between_time = [strtotime(date('Y-m-d 00:00:00', $item['create_time'])), strtotime(date('Y-m-d 23:59:59', $item['create_time']))];
            $map1['customer.owner_user_id'] = $item['create_user_id'];
            $map2['business.owner_user_id'] = $item['create_user_id'];
            $map3['contract.owner_user_id'] = $item['create_user_id'];
            $map4['receivables.owner_user_id'] = $item['create_user_id'];
        }
        if ($search) {
            $map['name'] = array('like', '%' . $search . '%');
        }
        $customerModel=new \app\crm\model\Customer();
        $customerMap =$customerModel->getWhereByCustomer();
        $type = $param['log_type'];
        switch ($type) {
            case '1':
                if ($search) $map['customer.name'] = array('like', '%' . $search . '%');
                $map['customer.create_time'] = array('between', $between_time);
                $activityData = Db::name('CrmCustomer')
                    ->alias('customer')
                    ->join('__ADMIN_USER__ user', 'user.id = customer.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where($map1)
                    ->where($customerMap)
                    ->order('customer.customer_id desc')
                    ->field('customer.customer_id,customer.name,customer.deal_status,customer.create_time,user.realname as owner_user_name,customer.last_time')
                    ->select();
                $dataCount = Db::name('CrmCustomer')
                    ->alias('customer')
                    ->join('__ADMIN_USER__ user', 'user.id = customer.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where($map1)
                    ->where($customerMap)
                    ->count();
                break;
            case '2':
                $map['business.name'] = array('like', '%' . $search . '%');
                $map['business.create_time'] = array('between', $between_time);
                $activityData = Db::name('CrmBusiness')
                    ->alias('business')
                    ->join('__CRM_BUSINESS_STATUS__ status', 'status.status_id=business.status_id')
                    ->join('__ADMIN_USER__ user', 'user.id = business.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where($map2)
                    ->order('business.business_id desc')
                    ->field('business.business_id,business.name,status.name as status_name,business.create_time,user.realname as owner_user_name,business.last_time')
                    ->select();
                $dataCount = Db::name('CrmBusiness')
                    ->alias('business')
                    ->join('__CRM_BUSINESS_STATUS__ status', 'status.status_id=business.status_id')
                    ->where($map)
                    ->where($map2)
                    ->count();

                break;
            case '3':
                $map['contract.name'] = array('like', '%' . $search . '%');
                $map['contract.create_time'] = array('between', $between_time);
                $activityData = Db::name('CrmContract')
                    ->alias('contract')
                    ->join('__ADMIN_USER__ u', 'u.id = contract.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where($map3)
                    ->order('contract.contract_id desc')
                    ->field('contract.contract_id,contract.name,contract.create_time,contract.check_status,u.realname as order_user_name')
                    ->select();
                $dataCount = Db::name('CrmContract')
                    ->alias('contract')
                    ->join('__ADMIN_USER__ u', 'u.id = contract.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where($map3)
                    ->count();
                break;
            case '4':
                $map['receivables.number'] = array('like', '%' . $search . '%');
                $map['receivables.create_time'] = array('between', $between_time);
                $activityData = Db::name('CrmReceivables')
                    ->alias('receivables')
                    ->join('__ADMIN_USER__ user', 'user.id = receivables.owner_user_id', 'LEFT')
                    ->field('receivables.receivables_id,receivables.number,receivables.return_time,user.realname as owner_user_name')
                    ->where($map)
                    ->where($map4)
                    ->order('receivables.receivables_id desc')
                    ->select();
                $dataCount = Db::name('CrmReceivables')
                    ->alias('receivables')
                    ->join('__ADMIN_USER__ user', 'user.id = receivables.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where($map4)
                    ->count();
                break;
        }
        foreach ($activityData as $k => $v) {
            $activityData[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : null;
            $activityData[$k]['last_time'] = $v['last_time'] ? date('Y-m-d H:i:s', $v['last_time']) : null;
        }
        $data = [];
        $data['list'] = $activityData;
        $data['dataCount'] = $dataCount ?: 0;
        return $data;
    }

    /**
     * 查看以往日志
     * @param $param
     * @return array
     */
    public function lastLog($param)
    {
        $user_id = $param('user_id');
        $activityData = db('oa_log')
            ->where(['send_user_ids' => ['like', '%' . $user_id . '%']])
            ->page($param['page'], $param['limit'])
            ->order('log_id desc')
            ->select();
        $dataCount = db('oa_log')->where(['send_user_ids' => ['like', '%' . $user_id . '%']])->count();
        $data = [];
        $data['page']['list'] = $activityData;
        $data['page']['dataCount'] = $dataCount ?: 0;
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
        return $data;
    }

    /**
     * 跟进记录
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function activity($param)
    {
        if ($param['search']) {
            $type['t.content'] = array('like', '%' . $param['search'] . '%');
        }
        if ($param['crmType'] == 0) {
            $type['t.activity_type'] = ['in', [1, 2, 3, 5, 6]];
        } else {
            $type['t.crmType'] = $param['activity_type'];
        }
        if ($param['type']) {
            $timeAry = getTimeByType($param['type']);
            $between_time = [$timeAry[0], $timeAry[1]];
            $type['t.create_time'] = array('between', $between_time);
        }
        if ($param['queryType'] == 0) {
            $type['t.type'] = ['in', [1, 4]];
        } else {
            $type['t.type'] = $param['queryType'];
        }
        if ($param['subUser'] == 'mycreate') {
            $type['t.create_user_id'] = $param['user_id'];
            //下属创建
        } elseif ($param['subUser'] == 'branchcreate') {
            $subList = getSubUserId(false, 0, $param['user_id']);
            $subStr = $subList ? implode(',', $subList) : '-1';
            $type['t.create_user_id'] = array('in', $subStr);
        } else {
            $userIds = getSubUserId(true, 1, $param['user_id']);
            $subStr = $userIds ? implode(',', $userIds) : '-1';
            $type['t.create_user_id'] = array('in', $subStr);
        }

        $list = db('crm_activity')
            ->alias('t')
            ->join('__ADMIN_USER__ user', 'user.id = t.create_user_id', 'LEFT')
            ->field('t.content,t.next_time as update_time,category,t.activity_type,t.type,t.activity_id,t.activity_type_id,user.realname as create_user_name,user.thumb_img')
            ->where($type)
            ->page($param['page'], $param['limit'])
            ->select();
        $dataCount = db('crm_activity')
            ->alias('t')
            ->join('__ADMIN_USER__ user', 'user.id = t.create_user_id', 'LEFT')
            ->field('t.content,t.next_time,category,t.activity_type,user.realname ,user.thumb_img ')
            ->where($type)
            ->count();
        foreach ($list as $k => $v) {
            // 业务名称（客户、线索、合同...）
            if ($v['type'] == 1 && $v['activity_type'] == 2) {
                $list[$k]['activity_type_name'] = Db::name('crm_customer')->where('customer_id', $v['activity_type_id'])->value('name');
            }
        }
        $data = [];
        $data['page']['list'] = $list;
        $data['page']['dataCount'] = $dataCount ?: 0;
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

        return $data;
    }

    /**
     * 已完成日志
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function completeLog($param)
    {
        $userModel = new \app\admin\model\User();
        $type = db('admin_oalog_rule')->where('type', 1)->find();
        $start_time = strtotime(date('Y-m-d', time()) . ' ' . $type['start_time'] . ':00');
        $end_time = strtotime(date('Y-m-d', time()) . ' ' . $type['end_time'] . ':00');
        $between_time = [$start_time, $end_time];
        $users = getSubUserId(false, 0, $param['user_id']);
        $between['create_time'] = array('between', $between_time);
        $list = db('oa_log')
            ->where($between)
            ->order('create_user_id', 'desc')
            ->column('create_user_id');

        if ($type['userIds'] == '') {

            $where['id'] = array('in', implode(',', array_intersect($users, $list)));
        } else {
            $users_diff = array_intersect($users, stringToArray($type['userIds']));//如果设置写日志人 显示要写下属id
            $where['id'] = array('in', implode(',', array_intersect($users_diff, $list)));
        }

//        if($type['userIds']==''){
//            $users=Db::name('AdminUser')->where('status',1)->column('id');
//            $where['log.create_user_id'] = array('in', implode(',',$users));
//        }else{
//            $where['log.create_user_id'] = array('in', implode(',', array_intersect(stringToArray($type['userIds']), $list)));
//        }
        $where['user.realname'] = array('like', '%' . $param['search'] . '%');
        $where['log.create_time'] = array('between', $between_time);
        $item = db('oa_log')
            ->alias('log')
            ->join('__ADMIN_USER__ user', 'user.id = log.create_user_id', 'LEFT')
            ->field('user.realname as user_name,log.content,log.create_time,log.log_id')
            ->where($where)
            ->order('log.log_id', 'desc')
            ->select();
        foreach ($item as $k => $v) {
            $item[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        $data = [];
        $data['list'] = $item;
        return $data;
    }


    /**
     * 未完成日志
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function inCompleteLog($param)
    {
        //下属员工未完成
        $type = db('admin_oalog_rule')->where('type', 1)->find();
        $start_time = strtotime(date('Y-m-d', time()) . ' ' . $type['start_time'] . ':00');
        $end_time = strtotime(date('Y-m-d', time()) . ' ' . $type['end_time'] . ':00');
        $between_time = [$start_time, $end_time];
        $users = getSubUserId(false, 0, $param['user_id']);
        $between['create_time'] = array('between', $between_time);

        $list = db('oa_log')
            ->where($between)
            ->order('create_user_id', 'desc')
            ->column('create_user_id');
        if ($type['userIds'] == '') {
            $diff = array_intersect($users, $list);// 下属已写
            $where['id'] = array('in', implode(',', array_diff_assoc($users, $diff)));
        } else {
            $users_diff = array_intersect($users, stringToArray($type['userIds']));//如果设置写日志人 显示要写下属
            $diff = array_intersect($users_diff, $list);// 下属已写
            $where['id'] = array('in', implode(',', array_diff_assoc($users_diff, $diff)));
        }
        $where['realname'] = array('like', '%' . $param['search'] . '%');
        $item = db('admin_user')
            ->field('realname as user_name')
            ->where($where)
            ->order('id', 'desc')
            ->select();
        $data = [];
        foreach ($item as $k => $v) {
            $item[$k]['content'] = '';
            $item[$k]['create_time'] = '';
        }
        $data['list'] = $item;
        return $data;
    }

    /**
     * 日志导出
     * @param $param
     */
    public function excelExport($param)
    {
        $data = $this->getDataList($param);
        $excelModel = new \app\admin\model\Excel();
        $file_name = 'log';
        $title = '日志列表';
        $field_list = [
            '0' => ['name' => '日志类型', 'field' => 'category_name'],
            '1' => ['name' => '创建日期', 'field' => 'create_time'],
            '2' => ['name' => '创建人', 'field' => 'realname'],
            '3' => ['name' => '发送给', 'field' => 'send_user_name'],
            '4' => ['name' => '今日工作内容', 'field' => 'content'],
            '5' => ['name' => '明日工作内容', 'field' => 'tomorrow'],
            '6' => ['name' => '遇到问题', 'field' => 'question'],
            '7' => ['name' => '关联业务', 'field' => 'relation'],
            '8' => ['name' => '回复', 'field' => 'replyList'],
        ];
        return $excelModel->taskExportCsv($file_name, $field_list, $title, $data);
    }

    /**
     * 回复列表
     * @param $param
     */
    public function CommentList($param)
    {
        $commonModel = new CommentModel();
        $param['type_id'] = $param['log_id'];
        $param['type'] = 'oa_log';
        $replyList = $commonModel->read($param);
        $data = [];
        $data['list'] = $replyList;
        return $data;
    }

    /**
     * 销售简报跟进数量统计
     *
     * @param $param 参数
     */
    public function activityCount($param)
    {
        $user_id = $param['user_id'];
        $item = Db::name('OaLog')->where('log_id', $param['log_id'])->find();
        $start_time = $this->pastTime();
        if (empty($param['log_id'])) {
            $between_time = [$start_time['start_time'], $start_time['end_time']];
            $map['create_time'] = array('between', $between_time);
            $map['create_user_id'] =$user_id;
        } else {
            $start_time = strtotime(date("Y-m-d", $item['create_time']));
            $end_time = strtotime(date("Y-m-d H:i:s", $item['create_time']));
            $between_time = [$start_time, $end_time];
            $map['create_time'] = array('between', $between_time);
            $map['create_user_id'] =  $item['create_user_id'];
        }
        $map['status']=1;
        $typesList = ['1', '2', '3', '5', '6'];
            foreach ($typesList as $k => $v) {
                $activityData = db('crm_activity')->where($map)->where(['type' => 1, 'activity_type' => $v])->count();
                if($v==1){
                    $arr[$k]['types'] ='crm_leads';
                    $arr[$k]['activity_type'] =1;
                }elseif ($v==2){
                    $arr[$k]['types'] ='crm_customer';
                    $arr[$k]['activity_type'] =2;
                }elseif ($v==3){
                    $arr[$k]['types'] ='crm_contacts';
                    $arr[$k]['activity_type'] =3;
                }elseif ($v==5){
                    $arr[$k]['types'] ='crm_business';
                    $arr[$k]['activity_type'] =5;
                }elseif ($v==6){
                    $arr[$k]['types'] ='crm_contract';
                    $arr[$k]['activity_type'] =6;
                }
                $arr[$k]['dataCount'] = $activityData;
            }
            $data = $arr;
        return $data;
    }

    /**
     *
     * @param $param
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function activityList($param)
    {
        $start_time = $this->pastTime();
        if (empty($param['log_id'])) {
            $between_time = [$start_time['start_time'], $start_time['end_time']];
            $where_activity['t.create_time'] = array('between', $between_time);
            $where_activity['t.create_user_id'] = $param['user_id'];
        } else {
            $item = Db::name('OaLog')->where('log_id', $param['log_id'])->find();
            $start_time = strtotime(date("Y-m-d", $item['create_time']));
            $end_time = strtotime(date("Y-m-d H:i:s", $item['create_time']));
            $between_time = [$start_time, $end_time];
            $where_activity['t.create_time'] = array('between', $between_time);
            $where_activity['t.create_user_id'] = $item['create_user_id'];
        }
        # 跟进记录类型
        $where_activity['t.activity_type'] = $param['activity_type'];
        $where_activity['t.status'] = 1;
        $where_activity['t.type'] = 1;
        $list = db('crm_activity')
            ->alias('t')
            ->join('__ADMIN_USER__ user', 'user.id = t.create_user_id', 'LEFT')
            ->where($where_activity)
            ->field('t.content,t.next_time,t.update_time,t.category,t.activity_type,t.type,t.activity_id,t.activity_type_id,user.realname as create_user_name,user.thumb_img')
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
            $list[$k]['dataInfo'] = $dataInfo ?: [];

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

    /**
     * 日志列表销售简办数据
     * @param $param
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function queryLogBulletin($param)
    {
        $user_id = $param['user_id'];
        $search = $param['search'];
        $log_id = $param['log_id'];
        $item = Db::name('OaLog')->where('log_id', $log_id)->find();
        $between_time = [$item['start_time'], $item['end_time']];
        if ($search) {
            $map['name'] = array('like', '%' . $search . '%');
        }
        $type = $param['type'];
        switch ($type) {
            case '1':
                $map['name'] = array('like', '%' . $search . '%');
                $map['customer.create_time'] = array('between', $between_time);
                $activityData = Db::name('CrmCustomer')
                    ->alias('customer')
                    ->join('__ADMIN_USER__ user', 'user.id = customer.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where('customer.create_user_id', $user_id)
                    ->order('customer.customer_id desc')
                    ->field('customer.name,customer.deal_status,customer.create_time,user.realname as owner_user_name,customer.last_time as activity_time')
                    ->select();
                $dataCount = Db::name('CrmCustomer')
                    ->alias('customer')
                    ->join('__ADMIN_USER__ user', 'user.id = customer.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where('customer.create_user_id', $user_id)
                    ->count();
                break;
            case '2':
                $map['name'] = array('like', '%' . $search . '%');
                $map['business.create_time'] = array('between', $between_time);
                $activityData = Db::name('CrmBusiness')
                    ->alias('business')
                    ->join('__CRM_BUSINESS_STATUS__ status', 'status.status_id=business.status_id')
                    ->where($map)
                    ->where('business.create_user_id', $user_id)
                    ->order('business.business_id desc')
                    ->field('business.name,status.name,business.create_time,user.realname as owner_user_name,business.last_time as activity_time')
                    ->select();
                $dataCount = Db::name('CrmBusiness')
                    ->alias('business')
                    ->join('__CRM_BUSINESS_STATUS__ status', 'status.status_id=business.status_id')
                    ->where($map)
                    ->where('business.create_user_id', $user_id)
                    ->count();

                break;
            case '3':
                $map['name'] = array('like', '%' . $search . '%');
                $map['contract.create_time'] = array('between', $between_time);
                $activityData = Db::name('CrmContract')
                    ->alias('contract')
                    ->join('__ADMIN_USER__ u', 'u.id = contract.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where('contract.create_user_id', $user_id)
                    ->order('contract.contract_id desc')
                    ->field('contract.name,contract.create_time,contract.check_status,u.realname as order_user_name')
                    ->select();

                $dataCount = Db::name('CrmContract')
                    ->alias('contract')
                    ->join('__ADMIN_USER__ u', 'u.id = contract.owner_user_id', 'LEFT')
                    ->where($map)
                    ->where('contract.create_user_id', $user_id)
                    ->count();
                break;
            case '4':
                $map['number'] = array('like', '%' . $search . '%');
                $map['receivables.create_time'] = array('between', $between_time);
                $activityData = Db::name('CrmReceivables')
                    ->alias('receivables')
                    ->join('__ADMIN_USER__ user', 'user.id = receivables.owner_user_id', 'LEFT')
                    ->field('receivables.number,receivables.create_time,user.realname as owner_user_name')
                    ->where($map)
                    ->where('create_user_id', $user_id)
                    ->order('receivables.receivables_id desc')
                    ->column('receivables_id');

                $dataCount = Db::name('CrmReceivables')
                    ->alias('receivables')
                    ->join('__ADMIN_USER__ user', 'user.id = receivables.owner_user_id', 'LEFT')
                    ->field('receivables.number,receivables.create_time,user.realname as owner_user_name')
                    ->where($map)
                    ->where('create_user_id', $user_id)
                    ->count();
                break;
        }
        $data = [];
        $data['list'] = $activityData;
        $data['dataCount'] = $dataCount ?: 0;
        return $data;
    }

    /**
     * 日志详情
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function queryLog($param)
    {
        $fileModel = new \app\admin\model\File();
        $recordModel = new \app\admin\model\Record();
        $commonModel = new \app\admin\model\Comment();
        $item = Db::name('OaLog')->where('log_id', $param['log_id'])->find();
        $fileList = [];
        $imgList = [];
        $where = [];
        $where['module'] = 'oa_log';
        $where['module_id'] = $item['log_id'];
        $newFileList = [];
        $newFileList = $fileModel->getDataList($where);
        foreach ($newFileList['list'] as $val) {
            if ($val['types'] == 'file') {
                $fileList[] = $val;
            } else {
                $imgList[] = $val;
            }
        }
        $param['type_id'] = $item['log_id'];
        $param['type'] = 'oa_log';
        $item['replyList'] = $commonModel->read($param);
        $item['fileList'] = $fileList ?: [];
        $item['imgList'] = $imgList ?: [];
        //相关业务
        $relationArr = $recordModel->getListByRelationId('log', $item['log_id']);
        $item['businessList'] = $relationArr['businessList'];
        $item['contactsList'] = $relationArr['contactsList'];
        $item['contractList'] = $relationArr['contractList'];
        $item['customerList'] = $relationArr['customerList'];
        $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        if ($item['is_relation'] == 1) {
            $item['bulletin']['customerNum'] = $item['save_customer'];
            $item['bulletin']['businessNum'] = $item['save_business'];
            $item['bulletin']['contractNum'] = $item['save_contract'];
            $item['bulletin']['receivablesMoneyNum'] = $item['save_receivables'];
            $item['bulletin']['recordNum'] = $item['save_activity'];
        } else {
            $item['bulletin'] = 0;
        }
        $data = [];
        $data['list'] = $item;
        return $data;
    }
}