<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-商机分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use app\bi\traits\SortTrait;
use think\Db;
use think\Hook;
use think\Request;

class Business extends ApiCommon
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
            'permission'=>[''],
            'allow'=>[
                'funnel',
                'businesstrend',
                'trendlist',
                'win',
                'winlist'
            ]
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'business' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
    } 
  
    /**
     * 销售漏斗
     * @author Michael_xu
     * @param 
     * @return
     */
    public function funnel()
    {
        if (empty($this->param['type_id'])) return resultArray(['error' => '请选择商机组！']);

        $businessModel = new \app\crm\model\Business();

        $param = $this->param;

        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');

        $data = $businessModel->getFunnel($param);
        foreach ($data['list'] AS $key => $value) {
            if (empty($value['money']) && empty($value['count'])) unset($data['list'][(int)$key]);
        }
        if (empty($data['total']['money_count']) && empty($data['total']['count'])) return resultArray(['data' => ['list' => []]]);

        $data['list'] = array_values($data['list']);

        # 排序
        if (!empty($data['list'])) $data['list'] = $this->sortCommon($data['list'], $sortField, $sortValue);

        return resultArray(['data' => $data]);
    }  

    /**
     * 新增商机数与金额趋势分析
     * @return 
     */
    public function businessTrend()
    {
        $businessModel = new \app\crm\model\Business();
        $userModel     = new \app\admin\model\User();
        $adminModel    = new \app\admin\model\Admin();
        $param         = $this->param;

        $perUserIds = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];

        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time  = getTimeArray($param['start_time'], $param['end_time']);
        $where = [
            'owner_user_id' => !empty($userIds) ? implode(',',$userIds) : '9999999999'
        ];
        $sql = [];

        foreach ($time['list'] as $val) {
            $whereArr = $where;
            $whereArr['type'] = $val['type'];
            $whereArr['start_time'] = $val['start_time'];
            $whereArr['end_time'] = $val['end_time'];
            $sql[] = $businessModel->getTrendql($whereArr);
        }

        $sql = implode(' UNION ALL ', $sql);
        $list = queryCache($sql);
        return resultArray(['data' => $list]);
    }

    /**
     * 新增商机数与金额趋势分析 列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function trendList()
    {
        $businessModel = new \app\bi\model\Business();
        $crmBusinessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        unset($param['types']);

        # 日期条件
        if (!empty($param['type'])) {
            $param['start_time'] = strtotime($param['type'] . '-01 00:00:00');
            $endMonth = strtotime(date('Y-m-d', $param['start_time']) . " +1 month -1 day");
            $param['end_time'] = strtotime(date('Y-m-d 23:59:59', $endMonth));
            unset($param['type']);
        } else {
            if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
            if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        }

        # 排序参数
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';

        $dataList = $businessModel->getDataList($param);
        foreach ($dataList['list'] as $k => $v) {
            $business_info = $crmBusinessModel->getDataById($v['business_id']);
            $dataList['list'][$k]['business_name'] = $business_info['name'];
            $dataList['list'][$k]['create_time'] = date('Y-m-d',strtotime($business_info['create_time']));
            $dataList['list'][$k]['customer_id'] = $v['customer_id'];
            $customer = db('crm_customer')->field('name')->where('customer_id',$v['customer_id'])->find();
            $dataList['list'][$k]['customer_name'] = $customer['name'];
            $create_user_id_info = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
            $dataList['list'][$k]['create_user_name'] = $create_user_id_info['realname'];
            $owner_user_id_info = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
            $dataList['list'][$k]['owner_user_name'] = $owner_user_id_info['realname'];
            $dataList['list'][$k]['business_stage'] = db('crm_business_status')->where('status_id',$v['status_id'])->value('name');//销售阶段
            $dataList['list'][$k]['business_type'] = db('crm_business_type')->where('type_id',$v['type_id'])->value('name');//商机状态组
        }

        # 排序
        if (!empty($dataList)) $dataList = $this->sortCommon($dataList, $sortField, $sortValue);
        
        return resultArray(['data' => $dataList]);
    }

    /**
     * 赢单机会转化率趋势分析
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function win()
    {
        $businessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereArr['userIds'];

        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $time = getTimeArray($param['start_time'], $param['end_time']);
        $sql = db('crm_business')->alias('business')->field([
                    "FROM_UNIXTIME(business.create_time, '{$time['time_format']}')" => 'type',
                    'COUNT(business.business_id)' => 'business_num',
                    'SUM(
                            CASE WHEN
                                `check_status` = 2
                            THEN 1 ELSE 0 END
                        )' => 'business_end'
                ])->join('__CRM_CONTRACT__ contract', 'contract.business_id = business.business_id', 'left')
                ->where([
                    'business.owner_user_id' => ['IN', $userIds],
                    'business.create_time' => ['BETWEEN', $time['between']]
                ])
                ->group('type')
                ->fetchSql()
                ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');
        foreach ($time['list'] as $key =>$val) {
            $val['business_num'] = (int) $res[$val['type']]['business_num'];
            $val['business_end'] = (int) $res[$val['type']]['business_end'];
            if($res[$val['type']]['business_num']== 0 || $res[$val['type']]['business_end'] == 0){
                $val['proportion'] = 0;
            }else{
                $val['proportion'] = round(($res[$val['type']]['business_end']/$res[$val['type']]['business_num']),4)*100;
            }
            $time['list'][$key] = $val;
        }
        return resultArray(['data' => $time['list']]);
    }

    /**
     * 商机转化率分析 列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function winList()
    {
        $businessModel = new \app\bi\model\Business();
        $crmBusinessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;

        # 日期条件
        if (!empty($param['date'])) {
            $param['start_time'] = strtotime($param['date'] . '-01 00:00:00');
            $endMonth = strtotime(date('Y-m-d', $param['start_time']) . " +1 month -1 day");
            $param['end_time'] = strtotime(date('Y-m-d 23:59:59', $endMonth)) ;
            unset($param['type']);
        }

        # 排序参数
        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';

        # 赢单条件
        $param['is_end'] = 1;

        $dataList = $businessModel->getDataList($param);
        foreach ($dataList as $k => $v) {
            $business_info = $crmBusinessModel->getDataById($v['business_id']);
            $dataList[$k]['business_name'] = $business_info['name'];
            $dataList[$k]['create_time'] = date('Y-m-d',strtotime($business_info['create_time']));
            $dataList[$k]['customer_id'] = $v['customer_id'];
            $customer = db('crm_customer')->field('name')->where('customer_id',$v['customer_id'])->find();
            $dataList[$k]['customer_name'] = $customer['name'];
            $create_user_id_info = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
            $dataList[$k]['create_user_name'] = $create_user_id_info['realname'];
            $owner_user_id_info = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
            $dataList[$k]['owner_user_name'] = $owner_user_id_info['realname'];
            $dataList[$k]['business_stage'] = db('crm_business_status')->where('status_id',$v['status_id'])->value('name');//销售阶段
            $dataList[$k]['business_type'] = db('crm_business_type')->where('type_id',$v['type_id'])->value('name');//商机状态组
        }

        # 排序
        if (!empty($dataList)) $dataList = $this->sortCommon($dataList, $sortField, $sortValue);

        return resultArray(['data' => $dataList]);
    }
}
