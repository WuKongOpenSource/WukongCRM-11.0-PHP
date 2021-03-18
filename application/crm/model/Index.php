<?php
// +----------------------------------------------------------------------
// | Description: CRM工作台
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;
use think\helper\Time;

class Index extends Common
{
    /**
     * 销售简报
     * @param
     * @return
     * @author Michael_xu
     */
    public function getSalesData($param)
    {
        $where = array();
        $start_time = $param['start_time'];
        $where['create_time'] = Time::today();
    }
    
    public function getQueryRepeat($type, $content)
    {
        $result = [];
        $customerList = [];
        $poolList = [];
        $leadsList = [];
        
        # 客户列表
        $customerList = $this->getCustomerList($type, $content);
        # 公海列表
        if (count($customerList) < 10) $poolList = $this->getPoolList($type, $content);
        # 线索列表
        if (count($customerList) + count($poolList) < 10) $leadsList = $this->getLeadsList($type, $content);
        # 处理客户列表数据
        foreach ($customerList as $key => $value) {
            $ownerUserName = !empty($value['owner_user_id']) ? db('admin_user')->where('id', $value['owner_user_id'])->value('realname') : '';
            if (!empty($ownerUserName)) {
                $result[] = [
                    'id' => $value['customer_id'],
                    'name' => $value['name'],
                    'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                    'mobile' => !empty($value['mobile']) ? $value['mobile'] : '',
                    'telephone' => !empty($value['telephone']) ? $value['telephone'] : '',
                    'last_time' => !empty($value['deal_time']) ? date('Y-m-d H:i:s', $value['deal_time']) : '',
                    'owner_user_name' => $ownerUserName,
                    'module' => '客户模块',
                    'type' => 2
                ];
            }
        }
        # 处理公海列表数据
        foreach ($poolList as $key => $value) {
            $ownerUserName = !empty($value['owner_user_id']) ? db('admin_user')->where('id', $value['owner_user_id'])->value('realname') : '';
            
            $result[] = [
                'id' => $value['customer_id'],
                'name' => $value['name'],
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'mobile' => !empty($value['mobile']) ? $value['mobile'] : '',
                'telephone' => !empty($value['telephone']) ? $value['telephone'] : '',
                'last_time' => !empty($value['deal_time']) ? date('Y-m-d H:i:s', $value['deal_time']) : '',
                'owner_user_name' => $ownerUserName,
                'module' => '公海模块',
                'type' => 9,
                // guogaobo 公海数据权限
                'poolAuthList' => [
                    'receive' => true,
                    'excelexport' => true,
                    'poolId' => $value['customer_id'], // guogaobo 多公海使用 公海类型id
                    'index' => true,
                    'distribute' => true,
                    'delete' => true
                ]
            ];
        }
        # 处理线索模块数据
        foreach ($leadsList as $key => $value) {
            $ownerUserName = !empty($value['owner_user_id']) ? db('admin_user')->where('id', $value['owner_user_id'])->value('realname') : '';
            
            $result[] = [
                'id' => $value['leads_id'],
                'name' => $value['name'],
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'mobile' => !empty($value['mobile']) ? $value['mobile'] : '',
                'telephone' => !empty($value['telephone']) ? $value['telephone'] : '',
                'last_time' => '',
                'owner_user_name' => $ownerUserName,
                'module' => '线索模块',
                'type' => 1
            ];
        }
        return $result;
    }
    /**
     * 获取客户列表
     *
     * @param $type
     * @param $content
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getCustomerList($type, $content)
{
    # 默认条件
    $customerWhere = $this->getCustomerWhere();
    
    # 查询条件
    $searchWhere = $this->getSearchWhere($type, $content);
    
    # 查询字段
    $field = ['customer_id', 'name', 'create_time', 'owner_user_id', 'deal_time', 'telephone', 'mobile'];
    
    return db('crm_customer')->alias('customer')->field($field)->where($customerWhere)
        ->where($searchWhere)->limit(10)->order('update_time', 'desc')->select();
}

    /**
     * 获取公海客户列表
     *
     * @param $type
     * @param $content
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getPoolList($type, $content)
{
    # 公海条件
    $poolWhere = $this->getPoolWhere();
    
    # 查询条件
    $searchWhere = $this->getSearchWhere($type, $content);
    
    # 查询字段
    $field = ['customer_id', 'name', 'create_time', 'owner_user_id', 'deal_time', 'telephone', 'mobile'];
    
    return db('crm_customer')->alias('customer')->field($field)->where($poolWhere)
        ->where($searchWhere)->limit(10)->order('update_time', 'desc')->select();
}

    /**
     * 获取线索列表
     *
     * @param $type
     * @param $content
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getLeadsList($type, $content)
{
    # 查询条件
    $searchWhere = $this->getSearchWhere($type, $content);
    
    # 查询字段
    $field = ['leads_id', 'name', 'telephone', 'mobile', 'owner_user_id', 'create_time', 'is_transform'];
    
    return db('crm_leads')->field($field)->where($searchWhere)->where('is_transform', 0)->limit(10)
        ->order('update_time', 'desc')->select();
}

    /**
     * 获取查询条件
     *
     * @param $type
     * @param $content
     * @return array|\Closure
     */
    private function getSearchWhere($type, $content)
{
    $searchWhere = [];
    
    # 查询客户名称
    if ($type == 'name') {
        $searchWhere = function ($query) use ($content) {
            $query->where('name', 'like', '%' . $content . '%');
        };
    }
    
    # 查询手机或电话
    if ($type == 'phone') {
        $searchWhere = function ($query) use ($content) {
            $query->where(function ($query) use ($content) {
                $query->whereOr('telephone', $content);
                $query->whereOr('mobile', $content);
            });
        };
    }
    
    return $searchWhere;
}

    /**
     * [客户公海条件]
     * @param
     * @return
     * @author Michael_xu
     */
    private function getPoolWhere()
{
    $configModel = new \app\crm\model\ConfigData();
    $configInfo = $configModel->getData();
    $config = $configInfo['config'] ?: 0;
    $follow_day = $configInfo['follow_day'] ?: 0;
    $deal_day = $configInfo['deal_day'] ?: 0;
    $whereData = [];
    //启用
    if ($config == 1) {
        //默认公海条件(没有负责人或已经到期)
        $data['follow_time'] = time() - $follow_day * 86400;
        $data['deal_time'] = time() - $deal_day * 86400;
        $data['deal_status'] = '未成交';
        if ($follow_day < $deal_day) {
            $whereData = function ($query) use ($data) {
                $query->where(['customer.owner_user_id' => 0])
                    ->whereOr(function ($query) use ($data) {
                        $query->where(function ($query) use ($data) {
                            $query->where(['customer.update_time' => array('elt', $data['follow_time'])])
                                ->whereOr(['customer.deal_time' => array('elt', $data['deal_time'])]);
                        })
                            ->where(['customer.is_lock' => 0])
                            ->where(['customer.deal_status' => ['neq', '已成交']]);
                    });
            };
        } else {
            $whereData = function ($query) use ($data) {
                $query->where(['customer.owner_user_id' => 0])
                    ->whereOr(function ($query) use ($data) {
                        $query->where(function ($query) use ($data) {
                            $query->where(['customer.deal_time' => array('elt', $data['deal_time'])]);
                        })
                            ->where(['customer.is_lock' => 0])
                            ->where(['customer.deal_status' => ['neq', '已成交']]);
                    });
            };
        }
    } else {
        $whereData['customer.owner_user_id'] = 0;
    }
    return $whereData ?: [];
}

    /**
     * [客户默认条件]
     * @param
     * @return
     * @author Michael_xu
     */
    private function getCustomerWhere()
{
    $configModel = new \app\crm\model\ConfigData();
    $configInfo = $configModel->getData();
    $config = $configInfo['config'] ?: 0;
    $follow_day = $configInfo['follow_day'] ?: 0;
    $deal_day = $configInfo['deal_day'] ?: 0;
    //默认条件(没有到期或已锁定)
    $data['follow_time'] = time() - $follow_day * 86400;
    $data['deal_time'] = time() - $deal_day * 86400;
    if ($config == 1) {
        if ($follow_day < $deal_day) {
            $whereData = function ($query) use ($data) {
                $query->where(function ($query) use ($data) {
                    $query->where(['customer.update_time' => array('gt', $data['follow_time']), 'customer.deal_time' => array('gt', $data['deal_time'])]);
                })
                    ->whereOr(['customer.deal_status' => '已成交'])
                    ->whereOr(['customer.is_lock' => 1]);
            };
        } else {
            $whereData = function ($query) use ($data) {
                $query->where(function ($query) use ($data) {
                    $query->where(['customer.deal_time' => array('gt', $data['deal_time'])]);
                })
                    ->whereOr(['customer.deal_status' => '已成交'])
                    ->whereOr(['customer.is_lock' => 1]);
            };
        }
    }
    return $whereData ?: [];
}
}