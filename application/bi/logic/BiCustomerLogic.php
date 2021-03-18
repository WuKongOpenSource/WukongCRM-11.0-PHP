<?php
/**
 * 员工客户分析逻辑类
 *
 * @author qifan
 * @date 2020-12-24
 */

namespace app\bi\logic;

use app\bi\traits\SortTrait;
use think\Db;

class BiCustomerLogic
{
    use SortTrait;

    /**
     * 员工客户满意度分析
     *
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCustomerSatisfaction($param)
    {
        $result = [];

        $userModel = new \app\admin\model\User();
        $adminModel      = new \app\admin\model\Admin();
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); # 权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];
        if (empty($userIds)) {
            # 普通员工没有查看权限，返回固定数据（根据员工筛选）
            $result[] = [
                'realname' => db('admin_user')->where('id', $param['user_id'])->value('realname'),
                'visitContractNum' => 0,
                '很满意' => 0,
                '满意' => 0,
                '一般' => 0,
                '不满意' => 0,
                '很不满意' => 0,
            ];
            return $result;
        }
        # 员工信息
        $userList = db('admin_user')->field(['id', 'realname'])->whereIn('id', $userIds)->select();
        foreach ($userList AS $key => $value) {
            $result[$value['id']] = [
                'realname' => $value['realname'],
                'visitContractNum' => 0,
                '很满意' => 0,
                '满意' => 0,
                '一般' => 0,
                '不满意' => 0,
                '很不满意' => 0,
            ];
        }

        $visitField = ['owner_user_id', 'satisfaction', 'count(`satisfaction`) AS satisfactionCount', 'count(`contract_id`) AS contractCount'];
        $where['owner_user_id'] = ['in', $userIds];
        $where['create_time']   = ['between', [$param['start_time'], $param['end_time']]];
        $where['deleted_state'] = 0;
        $visitList = db('crm_visit')->field($visitField)->where($where)->group('owner_user_id, satisfaction')->select();
        foreach ($visitList AS $key => $value) {
            if (!empty($value['satisfaction']) && trim($value['satisfaction']) == '很满意') {
                $result[$value['owner_user_id']]['很满意'] += $value['satisfactionCount'];
            }
            if (!empty($value['satisfaction']) && trim($value['satisfaction']) == '满意') {
                $result[$value['owner_user_id']]['满意'] += $value['satisfactionCount'];
            }
            if (!empty($value['satisfaction']) && trim($value['satisfaction']) == '一般') {
                $result[$value['owner_user_id']]['一般'] += $value['satisfactionCount'];
            }
            if (!empty($value['satisfaction']) && trim($value['satisfaction']) == '不满意') {
                $result[$value['owner_user_id']]['不满意'] += $value['satisfactionCount'];
            }
            if (!empty($value['satisfaction']) && trim($value['satisfaction']) == '很不满意') {
                $result[$value['owner_user_id']]['很不满意'] += $value['satisfactionCount'];
            }
            $result[$value['owner_user_id']]['visitContractNum'] += $value['contractCount'];
        }

        $result = $this->sortCommon($result, 'visitContractNum', 'desc');

        return array_values($result);
    }

    /**
     * 产品满意度分析
     *
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getProductSatisfaction($param)
    {
        $productData = [];

        $userModel = new \app\admin\model\User();

        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); # 权限范围内userIds
        $userIds    = !empty($param['user_id']) ? array_intersect([$param['user_id']], $perUserIds) : $perUserIds; # 数组交集

        # 产品列表（上架中）
        $productList = db('crm_product')->field(['product_id', 'name'])->where( 'delete_user_id',0)->select();
        foreach ($productList AS $key => $value) {
            $productData[$value['product_id']] = [
                'productName' => $value['name'],
                'visitNum' => 0,
                '很满意' => 0,
                '满意' => 0,
                '一般' => 0,
                '不满意' => 0,
                '很不满意' => 0,
            ];
        }

        # 普通员工没有查询权限，返回固定数据（根据员工筛选）
        if (empty($userIds)) array_values($productData);

        # 回访条件
        $where['visit.owner_user_id'] = ['in', $userIds];
        $where['visit.create_time']   = ['between', [$param['start_time'], $param['end_time']]];
        $where['visit.deleted_state'] = 0;

        # 回访数据
        $visitList = db('crm_visit')->alias('visit')->field(['visit.contract_id', 'visit.satisfaction'])
                    ->join('__CRM_CONTRACT__ contract', 'contract.contract_id = visit.contract_id')
                    ->where($where)->select();

        # 整理数据
        foreach ($visitList AS $key => $value) {
            if (empty($value['satisfaction'])) continue;

            $productIds = db('crm_contract_product')->where('contract_id', $value['contract_id'])->column('product_id');
            foreach ($productIds AS $k => $v) {
                if ($productData[$v]) {
                    if (trim($value['satisfaction']) == '很满意') $productData[$v]['很满意']++;
                    if (trim($value['satisfaction']) == '满意') $productData[$v]['满意']++;
                    if (trim($value['satisfaction']) == '一般') $productData[$v]['一般']++;
                    if (trim($value['satisfaction']) == '不满意') $productData[$v]['不满意']++;
                    if (trim($value['satisfaction']) == '很不满意') $productData[$v]['很不满意']++;

                    $productData[$v]['visitNum']++;
                }
            }
        }

        $productData = $this->sortCommon($productData, 'visitNum', 'desc');

        return array_values($productData);
    }
}