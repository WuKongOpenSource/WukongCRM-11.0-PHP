<?php
/**
 * 业绩目标逻辑类
 *
 * @author qifan
 * @date 2020-12-29
 */

namespace app\crm\logic;


use think\Db;

class AchievementLogic
{
    /**
     * 获取部门业绩列表
     *
     * @param $param
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepartmentList($param)
    {
        # 获取部门及全部子部门ID
        $departmentIds = $this->getDepartmentIds($param['structure_id']);

        $departmentWhere['year']   = $param['year'];
        $departmentWhere['status'] = $param['type'];
        $departmentWhere['type']   = 2;
        $departmentWhere['obj_id'] = ['in', $departmentIds];

        # 获取部门数据
        $departments = db('admin_structure')->field(['id', 'name'])->select();

        # 处理部门数据
        $departmentData = [];
        foreach ($departments AS $key => $value) {
            $departmentData[$value['id']] = $value['name'];
        }

        # 获取部门业绩数据
        $achievements = Db::name('crm_achievement')->where($departmentWhere)->select();

        # 处理业绩数据
        foreach ($achievements AS $key => $value) {
            if (!empty($departmentData[$value['obj_id']])) $achievements[$key]['name'] = $departmentData[$value['obj_id']];
        }

        return $achievements;
    }

    /**
     * 获取员工业绩列表
     *
     * @param $param
     * @return array|bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getEmployeeList($param)
    {
        $userWhere['year']   = $param['year'];
        $userWhere['status'] = $param['type'];
        $userWhere['type']   = 3;

        if (!empty($param['user_id'])) {
            $userWhere['obj_id'] = $param['user_id'];
        } else {
            # 获取部门及全部子部门ID
            $departmentIds = $this->getDepartmentIds($param['structure_id']);
            # 获取部门下的员工
            $userIds = Db::name('admin_user')->whereIn('structure_id', $departmentIds)->column('id');
            if (empty($userIds)) return [];
            # 设置员工条件
            $departmentWhere['obj_id'] = ['in', $userIds];
        }

        # 获取员工数据
        $users = db('admin_user')->field(['id', 'realname'])->select();

        # 处理员工数据
        $userData = [];
        foreach ($users AS $key => $value) {
            $userData[$value['id']] = $value['realname'];
        }

        # 获取业绩数据
        $achievements = Db::name('crm_achievement')->where($userWhere)->select();

        # 处理业绩数据
        foreach ($achievements AS $key => $value) {
            if (!empty($userData[$value['obj_id']])) $achievements[$key]['name'] = $userData[$value['obj_id']];
        }


        return $achievements;
    }

    /**
     * 获取部门及全部子部门ID
     *
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepartmentIds($id)
    {
        $result[] = $id;

        # 父级ID数组
        $parentIds[] = $id;

        # 查询部门数据
        $list = Db::name('admin_structure')->select();

        foreach ($list AS $key => $value) {
            if (!in_array($value['pid'], $parentIds)) continue;

            $parentIds[] = $value['id'];
            $result[]    = $value['id'];
        }

        return $result;
    }
}