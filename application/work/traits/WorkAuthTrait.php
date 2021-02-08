<?php
/**
 * 项目管理权限
 *
 * @author qifan
 * @date 2020-12-17
 */
namespace app\work\traits;

use think\Db;

trait WorkAuthTrait
{
    /**
     * 获取权限列表
     *
     * @param $workId
     * @param int $userId
     * @param int $groupId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRuleList($workId, $userId = 0, $groupId = 0)
    {
        $result = [];

        $isCreate = 0;
        if (!empty($userId)) $isCreate = Db::name('work')->where('create_user_id', $userId)->value('create_user_id');

        # 查询角色ID
        if (!empty($userId) && empty($groupId)) {
            $groupId = Db::name('work_user')->where(['work_id' => $workId, 'user_id' => $userId])->value('group_id');
        }

        # 查询角色下的权限
        $roleRules = Db::name('admin_group')->where('id', $groupId)->value('rules');
        $roleRules = !empty($roleRules) ? explode(',', $roleRules) : [];

        # 查询项目权限数据
        $adminRules = Db::name('admin_rule')->field(['id', 'name'])->where(['types' => 3, 'level' => 4, 'status' => 0])->select();

        foreach ($adminRules AS $key => $value) {
            # 如果是管理
            if (!empty($groupId) && $groupId == 1) {
                $result[$value['name']] = true;
                continue;
            }
            # 权限是否存在于角色权限中
            if (in_array($value['id'], $roleRules)) {
                $result[$value['name']] = true;
                continue;
            }
            # 创建人
            if (!empty($isCreate)) {
                $result[$value['name']] = true;
                continue;
            }

            $result[$value['name']] = false;
        }

        return $result;
    }

    /**
     * 验证项目操作权限
     *
     * @param $action
     * @param $workId
     * @param int $userId
     * @return false|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkWorkOperationAuth($action, $workId, $userId)
    {
        $groupId = Db::name('work')->where(['work_id' => $workId, 'is_open' => 1])->value('group_id');

        if (empty($userId) && empty($groupId)) return false;

        $result = $this->getRuleList($workId, $userId, !empty($groupId) ? $groupId : 0);

        return isset($result[$action]) ? $result[$action] : false;
    }
}