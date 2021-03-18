<?php
// +----------------------------------------------------------------------
// | Description: 商机
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;

class Business extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_business';

	/**
     * [getDataCount 商机count]
     * @author Michael_xu
     * @param
     * @return
     */		
	function getDataCount($whereArr)
    {
    	$where = [];
        $dataCount = $this->where($whereArr)->where($where)->count('business_id');
        $count = $dataCount ? : 0;
        return $count;		
    }

    /**
     * [getDataMoney 商机金额]
     * @author Michael_xu
     * @param
     * @return
     */		
	function getDataMoney($whereArr)
    {
    	$where = [];
        $money = $this->where($whereArr)->where($where)->sum('money');
        return $money;		
    }

    /**
     * 获取商机list
     *
     * @param $param
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function getDataList($param)
    {
        $page  = !empty($param['page'])  ? $param['page']  : 1;
        $limit = !empty($param['limit']) ? $param['limit'] : 15;
        unset($param['page']);
        unset($param['limit']);

    	$userModel  = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();

        $perUserIds = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
        $whereData  = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds    = $whereData['userIds'];
        if (!empty($whereData['between_time'][0])) unset($whereData['between_time'][1]);
        $between_time = $whereData['between_time'];
        $where['business.owner_user_id'] = array('in',$userIds);
        $where['business.create_time'] = ['between', [$param['start_time'],$param['end_time']]];
        if (!empty($param['is_end']) && $param['is_end'] == 1) $where['is_end'] = 1;

        $count = db('crm_business')->alias('business')
            ->join('__CRM_CONTRACT__ contract', 'contract.business_id = business.business_id', 'left')
            ->where($where)->group('business.business_id')->count();
        $sql = db('crm_business')->alias('business')
            ->field('business.business_id,business.customer_id,business.money,business.type_id,business.status_id,business.deal_date,business.create_user_id,business.owner_user_id,business.is_end')
            ->join('__CRM_CONTRACT__ contract', 'contract.business_id = business.business_id', 'left')
            ->where($where)
            ->limit(($page - 1) * $limit, $limit)
            ->order(['money' => 'DESC'])
            ->group('business.business_id')
            ->select();
        return ['dataCount' => $count, 'list' => $sql];
   }
}