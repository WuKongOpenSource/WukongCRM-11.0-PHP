<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-员工业绩分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use app\bi\traits\SortTrait;
use app\crm\model\Contract as ContractModel;
use app\crm\model\Receivables as ReceivablesModel;
use think\Db;
use think\Hook;
use think\Request;
use app\bi\logic\ExcelLogic;
class Contract extends ApiCommon
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
                'analysis',
                'summary',
                'invoice',
                'excelexport'
            ]
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'contract', 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
    }

    /**
     * 合同数量分析/金额分析/回款金额分析
     *
     * @param string $param
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function analysis($param='')
    {
        $userModel  = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        if ($param['excel_type'] != 1) {
            $param = $this->param;
        }
        $perUserIds = $userModel->getUserByPer('bi', 'contract', 'read'); // 权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds);       // 统计条件
        $userIds    = $whereArr['userIds'];

        $year       = !empty($param['year']) ? $param['year'] : date('Y');
        $start_time = strtotime(date(($year - 1) . '-01-01'));
        $end_time   = strtotime('+2 year', $start_time) - 1;
        $time       = getTimeArray($start_time, $end_time);

        if ($param['type'] == 'back' || $param['excel_type'] == 'back') {
            $model = new ReceivablesModel;
            $time_field = 'return_time';
        } else {
            $model = new ContractModel;
            $time_field = 'order_date';
        }
        if ($param['type'] == 'count' || $param['$excel_type'] == 'count') {
            $field['COUNT(*)'] = 'total';
        } else {
            $field['SUM(`money`)'] = 'total';
        }
        $between_time = [date('Y-m-d', $time['between'][0]), date('Y-m-d', $time['between'][1])];
        $field["SUBSTR(`{$time_field}`, 1, 7)"] = 'type';
        $sql = $model->field($field)
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'check_status' => 2,
                $time_field => ['BETWEEN', $between_time]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');

        $data = [];
        for ($i = 12; $i < 24; $i++) {
            $k  = $time['list'][$i]['type'];
            $k2 = $time['list'][$i - 1]['type'];
            $k3 = $time['list'][$i - 12]['type'];
            $item['month'] = ($i - 11) < 10 ? $param['year']. '0' . $i - 11 : $param['year'] . $i - 11;

            $item['thisMonth']       = $res[$k]  ? $res[$k]['total']  : 0; # 本月
            $item['lastMonthGrowth'] = $res[$k2] ? $res[$k2]['total'] : 0; # 上月
            $item['lastYearGrowth']  = $res[$k3] ? $res[$k3]['total'] : 0; # 上年本月

            # 环比
            $item['chain_ratio'] = $item['thisMonth'] && $item['lastMonthGrowth'] ? round(($item['thisMonth'] / $item['lastMonthGrowth'])  * 100, 4) : 0;
            # 同比
            $item['year_on_year'] = $item['thisMonth'] && $item['lastYearGrowth'] ? round(($item['thisMonth'] / $item['lastYearGrowth'])  * 100, 4) : 0;

            $data[] = $item;
        }
        //导出使用
        if (!empty($param['excel_type'])) {
            return $data;
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 合同汇总表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function summary($param='')
    {
        $userModel  = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        
        $perUserIds = $userModel->getUserByPer('bi', 'contract', 'read'); //权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereArr['userIds'];

        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }

        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        $year       = !empty($param['year']) ? $param['year'] : date('Y');
        $start_time = strtotime($year . '-01-01');
        $end_time   = strtotime('+1 year', $start_time) - 1;
        $time       = getTimeArray($start_time, $end_time);
        $ax         = 7;
        if ($time['time_format'] == '%Y-%m-%d') {
            $ax = 10;
        }
        $between_time = [date('Y-m-d', $time['between'][0]), date('Y-m-d', $time['between'][1])];
        $sql = ContractModel::field([
            'SUBSTR(`order_date`, 1, ' . $ax . ')' => 'type',
            'COUNT(*)' => 'count',
            'SUM(`money`)' => 'money'
        ])
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'check_status' => 2,
                'order_date' => ['BETWEEN', $between_time]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $contract_data = queryCache($sql);
        $contract_data = array_column($contract_data, null, 'type');
        $sql = ReceivablesModel::field([
            'SUBSTR(`return_time`, 1, ' . $ax . ')' => 'type',
            'SUM(`money`)' => 'money'
        ])
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'check_status' => 2,
                'return_time' => ['BETWEEN', $between_time]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $receivables_data = queryCache($sql);
        $receivables_data = array_column($receivables_data, null, 'type');

        $items = [];
        $count_zong = 0;
        $money_zong = 0;
        $back_zong = 0;
        foreach ($time['list'] as $val) {
            $item = ['type' => $val['type']];
            $count_zong += $item['count'] = $contract_data[$val['type']]['count']   ?: 0;
            $money_zong += $item['money'] = $contract_data[$val['type']]['money']   ?: 0;
            $back_zong  += $item['back']  = $receivables_data[$val['type']]['money'] ?: 0;
            $items[] = $item;
        }
        $data = [
            'list'        => $items,
            'count_zong'  => $count_zong,
            'money_zong'  => $money_zong,
            'back_zong'   => $back_zong,
            'w_back_zong' => $money_zong - $back_zong,
        ];

        if (!empty($data['list'])) $data['list'] = $this->sortCommon($data['list'], $sortField, $sortValue);
        //导出使用
        if (!empty($param['excel_type'])) return $data;
        return resultArray(['data' => $data]);
    }

    /**
     * 发票统计分析
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function invoice($param='')
    {
        $userModel  = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        $perUserIds = $userModel->getUserByPer('bi', 'contract', 'read'); //权限范围内userIds
        $whereArr   = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereArr['userIds'];
        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }

        $sortField = !empty($param['sort_field']) ? $param['sort_field'] : '';
        $sortValue = !empty($param['sort_value']) ? $param['sort_value'] : '';
        unset($param['sort_field']);
        unset($param['sort_value']);

        $time = getTimeArray();
        $ax = 7;
        if ($time['time_format'] == '%Y-%m-%d') {
            $ax = 10;
        }
        $between_time = [date('Y-m-d', $time['between'][0]), date('Y-m-d', $time['between'][1])];
        $sql = Db::name('crm_invoice')->field([
            'SUBSTR(`invoice_date`, 1, ' . $ax . ')' => 'type',
            'SUM(`invoice_money`)' => 'money'
        ])
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'check_status' => 2,
                'invoice_status' => 1,
                'invoice_date' => ['BETWEEN', $between_time]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $invoice_data = queryCache($sql);
        $invoice_data = array_column($invoice_data, null, 'type');

        $sql = ReceivablesModel::field([
            'SUBSTR(`return_time`, 1, ' . $ax . ')' => 'type',
            'SUM(`money`)' => 'money'
        ])
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'check_status' => 2,
                'return_time' => ['BETWEEN', $between_time]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $receivables_data = queryCache($sql);
        $receivables_data = array_column($receivables_data, null, 'type');

        $items = [];
        $invoiceCount = 0;
        $receivablesCount = 0;
        foreach ($time['list'] as $val) {
            $receivablesModel = !empty($receivables_data[$val['type']]['money']) ? $receivables_data[$val['type']]['money'] : 0;
            $invoiceModel     = !empty($invoice_data[$val['type']]['money']) ? $invoice_data[$val['type']]['money'] : 0;

            $items[] = [
                'type' => $val['type'],
                'receivables_money' => $receivablesModel,
                'invoice_money'     => $invoiceModel,
                'not_invoice'       => $receivablesModel - $invoiceModel > 0 ? $receivablesModel - $invoiceModel : 0,
                'not_receivables'   => $invoiceModel - $receivablesModel > 0 ? $invoiceModel - $receivablesModel : 0
            ];

            $invoiceCount     += $invoiceModel;
            $receivablesCount += $receivablesModel;

        }
        $data = [
            'list' => $items,
            'receivables_count' => $receivablesCount,
            'invoice_count' => $invoiceCount
        ];

        if (!empty($data['list'])) $data['list'] = $this->sortCommon($data['list'], $sortField, $sortValue);
        //导出使用
        if (!empty($param['excel_type'])) return $data;
        return resultArray(['data' => $data]);
    }

    /**
     * 导出
     * @param $type
     * @param $types
     */
    public function excelExport()
    {

        $param = $this->param;
        $excel_type = $param['excel_type'];
        $type=[];
        $type['excel_types']=$param['excel_types'];

        switch ($param['excel_types']) {
            case 'analysis':
                if ($param['type'] == 'count') {
                    $type['type'] = '合同数量分析';

                } elseif ($param['type'] == 'back') {
                    $type['type'] = '回款金额分析';
                } else {
                    $type['type'] = '金额分析';
                }
                $list = $this->analysis($param);
                break;
            case 'summary':
                $list = $this->summary($excel_type);
                $list=$list['list'];
                $type['type'] = '合同汇总表';
                break;
            case 'invoice':
                $list = $this->invoice($excel_type);
                $list=$list['list'];
                $type['type'] = '发票统计分析表';
                break;
        }
        if(empty($list)){
            return resultArray(['data'=>'数据不存在']);
        }
        $excelLogic = new ExcelLogic();
        $data = $excelLogic->contractExcel($type, $list);
        return $data;
    }

}
