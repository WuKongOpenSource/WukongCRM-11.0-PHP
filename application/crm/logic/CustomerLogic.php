<?php
/**
 * 客户逻辑类
 *
 * @author qifan
 * @date 2020-01-18
 */

namespace app\crm\logic;

use think\Db;

class CustomerLogic
{
    /**
     * 获取员工角色ID
     *
     * @param $userId
     * @return array|false|string
     */
    public function getEmployeeGroups($userId)
    {
        return Db::name('admin_access')->where('user_id', $userId)->column('group_id');
    }

    /**
     * 获取员工角色下的规则ID
     *
     * @param $groupIds
     * @return array|false|string
     */
    public function getEmployeeRules($groupIds)
    {
        return Db::name('admin_group')->whereIn('id', $groupIds)->column('rules');
    }

    /**
     * 获取公海管理规则数据
     *
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPoolRules()
    {
        # 公海管理查询条件
        $poolRuleWhere['types'] = 2;
        $poolRuleWhere['title'] = '公海管理';
        $poolRuleWhere['name']  = 'customer';
        $poolRuleWhere['level'] = 2;

        # 查询公海管理ID
        $poolRuleId = Db::name('admin_rule')->where($poolRuleWhere)->value('id');

        return Db::name('admin_rule')->field(['id', 'name'])->where('pid', $poolRuleId)->select();
    }
}