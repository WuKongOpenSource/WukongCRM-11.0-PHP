<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-排行榜
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use app\bi\traits\SortTrait;
use think\Hook;
use think\Request;
use think\Db;
use app\bi\model\Customer as CustomerModel;
use app\bi\model\Contract as ContractModel;
use app\bi\logic\ExcelLogic;

class Ranking extends ApiCommon
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
                'contract',
                'receivables',
                'signing',
                'addcustomer',
                'addcontacts',
                'recordnun',
                'recordcustomer',
                'examine',
                'product',
                'excelexport'
            ]
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'ranking', 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
    }

    /**
     * 合同金额排行
     * @param
     * @return
     * @author Michael_xu
     */
    public function contract($param = '')
    {
        if($param['excel_type']!=1){
            $param = $this->param;
        }

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');

        $whereArr = $this->com($param, 'contract');
        $whereArr['check_status'] = 2;

        //导出使用
        if (!empty($param['excel_type'])) return $this->handel(
            new \app\bi\model\Contract,
            $whereArr,
            ['field' => 'SUM(`money`)', 'alias' => 'money', 'default' => '0.00'],
            $param['excel_type']
        );
        return $this->handel(
            new \app\bi\model\Contract,
            $whereArr,
            ['field' => 'SUM(`money`)', 'alias' => 'money', 'default' => '0.00']
        );
    }

    /**
     * 回款金额排序
     * @return
     */
    public function receivables($param = '')
    {
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $whereArr = $this->com($param, 'receivables');
        $whereArr['check_status'] = 2;

        //导出使用
        if (!empty($param['excel_type'])) return $this->handel(
            new \app\bi\model\Receivables,
            $whereArr,
            ['field' => 'SUM(`money`)', 'alias' => 'money', 'default' => '0.00'],
            $param['excel_type']
        );
        return $this->handel(
            new \app\bi\model\Receivables,
            $whereArr,
            ['field' => 'SUM(`money`)', 'alias' => 'money', 'default' => '0.00']
        );
    }

    /**
     * 签约合同排序
     * @return
     */
    public function signing($param = '')
    {
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $whereArr = $this->com($param, 'contract');
        $whereArr['check_status'] = 2;

        //导出使用
        if (!empty($param['excel_type'])) $this->handel(
            new ContractModel,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0],
            $param['excel_type']
        );
        return $this->handel(
            new ContractModel,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0]
        );
    }

    /**
     * 新增客户排序
     * @return
     */
    public function addCustomer($param = '')
    {
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $whereArr = $this->com($param, 'customer');

        $poolWhere = $this->getWhereByPool();
        $poolId    = db('crm_customer')->alias('customer')->where($poolWhere)->column('customer_id');
        if (!empty($poolId)) $whereArr['customer_id'] = ['notin', $poolId];

        //导出使用
        if (!empty($param['excel_type'])) return $this->handel(
            new \app\bi\model\Customer,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0],
            $param['excel_type']
        );
        return $this->handel(
            new \app\bi\model\Customer,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0]
        );
    }

    /**
     * 新增联系人排序
     * @return
     */
    public function addContacts()
    {
        $param = $this->param;
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $whereArr = $this->com($param, 'contacts');

        //导出使用
        if (!empty($param['excel_type'])) return $this->handel(
            new \app\bi\model\Contacts,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0],
            $param['excel_type']
        );
        return $this->handel(
            new \app\bi\model\Contacts,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0]
        );
    }

    /**
     * 跟进次数排行
     *
     * @param string $param
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recordNun($param = '')
    {
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $whereArr = $this->com($param, 'record');

        # 权限内的员工列表
        $userData = [];
        $userList = db('admin_user')->alias('user')
                    ->field(['user.id', 'user.realname AS user_name', 'structure.name AS structure_name'])
                    ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id')
                    ->whereIn('user.id', $whereArr['create_user_id'][1])->select();
        foreach ($userList AS $key => $value) {
            $userData[$value['id']]['user_name']      = $value['user_name'];
            $userData[$value['id']]['structure_name'] = $value['structure_name'];
        }

        # 跟进记录列表
        $data = [];
        $recordWhere['type']           = 1;
        $recordWhere['activity_type']  = 2;
        $recordWhere['create_user_id'] = ['in', $whereArr['create_user_id'][1]];
        $recordWhere['create_time']    = ['between', [$whereArr['create_time'][1][0], $whereArr['create_time'][1][1]]];
        $recordList = db('crm_activity')->field(['count(*) as count', 'create_user_id'])->where($recordWhere)
            ->group('create_user_id')->order('count', 'desc')->select();

        foreach ($recordList AS $key => $value) {
            $data[] = [
                'count'          => $value['count'],
                'user_name'      => $userData[$value['create_user_id']]['user_name'],
                'structure_name' => $userData[$value['create_user_id']]['structure_name']
            ];
        }

        //导出使用
        if (!empty($param['excel_type'])) return $data;

        return resultArray(['data' => $data]);
    }

    /**
     * 跟进客户数排行
     *
     * @param string $param
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recordCustomer($param = '')
    {
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $whereArr = $this->com($param, 'record');

        # 权限内的员工列表
        $userData = [];
        $userList = db('admin_user')->alias('user')
            ->field(['user.id', 'user.realname AS user_name', 'structure.name AS structure_name'])
            ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id')
            ->whereIn('user.id', $whereArr['create_user_id'][1])->select();
        foreach ($userList AS $key => $value) {
            $userData[$value['id']]['user_name']      = $value['user_name'];
            $userData[$value['id']]['structure_name'] = $value['structure_name'];
        }

        # 跟进记录列表
        $data = [];
        $recordWhere['type']           = 1;
        $recordWhere['activity_type']  = 2;
        $recordWhere['create_user_id'] = ['in', $whereArr['create_user_id'][1]];
        $recordWhere['create_time']    = ['between', [$whereArr['create_time'][1][0], $whereArr['create_time'][1][1]]];
        $recordList = db('crm_activity')->field(['create_user_id', 'activity_type_id'])->where($recordWhere)
            ->group('create_user_id, activity_type_id')->select();

        foreach ($recordList AS $key => $value) {
            if (empty($data[$value['create_user_id']]['user_name'])) {
                $data[$value['create_user_id']]['user_name']      = $userData[$value['create_user_id']]['user_name'];
                $data[$value['create_user_id']]['structure_name'] = $userData[$value['create_user_id']]['structure_name'];
                $data[$value['create_user_id']]['count']          = 1;
            } else {
                $data[$value['create_user_id']]['count'] = $data[$value['create_user_id']]['count'] + 1;
            }
        }

        $data = $this->sortCommon($data, 'count', 'desc');

        //导出使用
        if (!empty($param['excel_type'])) return $data;

        return resultArray(['data' => $data]);
    }

    /**
     * 出差次数排行
     * @return
     */
    public function examine($param = '')
    {
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $whereArr = $this->com($param, 'record');
        $whereArr['category_id'] = 3; // 审批类型，3出差
        $whereArr['check_status'] = 2;

        //导出使用
        if (!empty($param['excel_type'])) return $this->handel(
            new \app\bi\model\Examine,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0],
            $param['excel_type'],
            'create_user_id'
        );

        return $this->handel(
            new \app\bi\model\Examine,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0],
            '',
            'create_user_id'
        );
    }

    /**
     * 产品销量排行
     * @return
     */
    public function product($param = '')
    {
        $userModel = new \app\admin\model\User();
        $productModel = new \app\bi\model\Product();
        if($param['excel_type']!=1){
            $param = $this->param;
        }
        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');
        $list = $productModel->getSortByProduct($param);
        $list = array_column($list, null, 'owner_user_id');

        $whereArr = $this->com($param, 'contract');

        $data = [];
        foreach ($whereArr['owner_user_id'][1] as $val) {
            $user = $userModel->getUserById($val);
            $item = [];
            $item['num'] = !empty($list[$val]['num']) ? (int)$list[$val]['num'] : 0;
            $item['user_name'] = $user['realname'];
            $item['structure_name'] = $user['structure_name'];
            $data[] = $item;
        }

        # 排序
        if (!empty($data)) $data = $this->sortCommon($data, 'num', 'desc');
        //导出使用
        if (!empty($param['excel_type'])) return $data;

        return resultArray(['data' => $data]);
    }

    /**
     * 查询条件
     * @return
     */
    private function com($param, $type = '')
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $perUserIds = $userModel->getUserByPer('bi', 'ranking', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];
        $between_time = $whereData['between_time'];
        if ($type == 'contract') {
            $where_time = 'order_date';
        } elseif (in_array($type, ['record', 'customer', 'contacts'])) {
            $where_time = 'create_time';
        } elseif ($type == 'receivables') {
            $where_time = 'return_time';
        } else {
            $where_time = 'start_time';
        }
        //时间戳：新增客户排行
        if ($type == 'contract' || $type == 'receivables') {
            $whereArr[$where_time] = array('between', array(date('Y-m-d', $between_time[0]), date('Y-m-d', $between_time[1])));
        } else {
            $whereArr[$where_time] = array('between', array($between_time[0], $between_time[1]));
        }

        if (in_array($type, ['customer', 'contract', 'receivables', 'contacts'])) {
            $whereArr['owner_user_id'] = ['IN', $userIds];
        } else {
            $whereArr['create_user_id'] = ['IN', $userIds];
        }

        return $whereArr;
    }

    /**
     * 查询统计数据
     *
     * @param model $model
     * @param array $whereArr
     * @return void
     * @author Ymob
     * @datetime 2019-11-25 11:11:59
     */
    private function handel($model, $whereArr, $field, $excel_type = '', $user_field = 'owner_user_id')
    {
        $userModel = new \app\admin\model\User();
        $sql = $model->field([
            $user_field,
            $field['field'] => $field['alias']
        ])
            ->where($whereArr)
            ->group($user_field)
            ->fetchSql()
            ->select();

        $list = queryCache($sql);
        $list = array_column($list, null, $user_field);
        $data = [];

        foreach ($whereArr[$user_field][1] as $val) {
            $user = $userModel->getUserById($val);
            $item = [];
            $item[$field['alias']] = $list[$val][$field['alias']] ?: $field['default'];
            $item['user_name'] = $user['realname'];
            $item['structure_name'] = $user['structure_name'];
            $data[] = $item;
        }
        array_multisort($data, SORT_DESC, array_column($data, $field['alias']));
        if (!empty($excel_type)) return $data;

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
        $type = [];
        $type['excel_types'] = $param['excel_types'];
        switch ($param['excel_types']) {
            case 'contract':
                $list = $this->contract($param);

                foreach ($list as $key => $v) {
                    $list[$key]['id'] = $key + 1;
                }
                $type['type'] = '合同金额排行';
                break;
            case'receivables':
                $list = $this->receivables($param);
                $type['type'] = '回款金额排行';
                break;
            case 'signing':
                $list = $this->signing($param);
                $type['type'] = '签约合同排行';
                break;
            case 'product':
                $list = $this->product($param);
                $type['type'] = '产品销量排行';
                break;
            case 'addCustomer':
                $list = $this->addCustomer($param);
                $type['type'] = '新增客户数排行';
                break;
            case 'addContacts':
                $list = $this->addContacts($param);
                $type['type'] = '新增联系人数排行';
                break;
            case 'recordNun':
                $list = $this->recordNun($param);
                $type['type'] = '跟进次数排行';
                break;
            case 'recordCustomer':
                $list = $this->recordCustomer($param);
                $type['type'] = '跟进客户数排行';
                break;
            case 'examine':
                $list = $this->examine($param);
                $type['type'] = '出差次数排行';
                break;
        }
        if(empty($list)){
            return resultArray(['data'=>'数据不存在']);
        }
        $excelLogic = new ExcelLogic();
        $data = $excelLogic->rankingExcle($param, $list);
        return $data;
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
}