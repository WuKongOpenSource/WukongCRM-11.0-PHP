<?php
/**
 * 项目管理逻辑类
 *
 * @author qifan
 * @date 2020-12-17
 */

namespace app\admin\logic;

use app\admin\controller\ApiCommon;
use think\Db;

class WorkLogic
{
    /**
     * 规则列表
     *
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRules()
    {
        return Db::name('admin_rule')->field(['id', 'title', 'name'])->where(['types' => 3, 'level' => 4, 'status' => 0])->select();
    }

    /**
     * 获取角色
     *
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRoles()
    {
        $data = Db::name('admin_group')->field(['id', 'title', 'rules', 'remark', 'system'])->where(['pid' => 5, 'types' => 7, 'status' => 1])->select();

        foreach ($data AS $key => $value) {
            $data[$key]['rules'] = explode(',', trim($value['rules'], ','));
        }

        return $data;
    }

    /**
     * 创建角色
     *
     * @param $param
     * @return int|string
     */
    public function saveRole($param)
    {
        # 设置参数
        $param['pid']    = 5;
        $param['status'] = 1;
        $param['type']   = 0;
        $param['types']  = 7;
        $param['system'] = 0;
        $data=Db::name('admin_group')->insertGetId($param);
        return $data;
    }

    /**
     * 权限角色详情
     *
     * @param $id
     * @return array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function readRole($id)
    {
        $data = Db::name('admin_group')->field(['id', 'title', 'rules', 'remark'])->where('id', $id)->find();

        $data['rules'] = trim($data['rules'], ',');

        return $data;
    }

    /**
     * 编辑权限角色
     *
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateRole($param)
    {
        $res=Db::name('admin_group')->where('id',$param['id'])->find();
        if(!$res){
            return false;
        }else{
            $data=Db::name('admin_group')->update($param);
            # 添加系统操作日志
            $user=new ApiCommon();
            $userInfo=$user->userInfo;
            SystemActionLog($userInfo['id'], 'admin_group','project', $param['id'], 'update',$res['title'] , '', '','编辑了项目管理权限：'.$res['title']);
            return $data;
        }
       
    }

    /**
     * 删除权限角色
     *
     * @param $id
     * @return array|bool[]
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function deleteRole($id)
    {
        $system = Db::name('admin_group')->where('id', $id)->find();

        if (!empty($system['system'])) return ['status' => false, 'error' => '不允许删除系统默认角色！'];

        if (!Db::name('admin_group')->where('id', $id)->delete()) return ['status' => false, 'error' => '操作失败！'];

        # 将项目权限变更为只读权限
        $readOnlyId = db('admin_group')->where(['title' => '只读', 'types' => 7, 'system' => 1])->value('id');
        if (!empty($readOnlyId)) {
            db('work')->where('group_id', $id)->update(['group_id' => $readOnlyId]);      # 处理公开项目的权限
            db('work_user')->where('group_id', $id)->update(['group_id' => $readOnlyId]); # 处理私有项目的权限
        }
        # 添加系统操作日志
        $user=new ApiCommon();
        $userInfo=$user->userInfo;
        SystemActionLog($userInfo['id'], 'admin_group','project', $id, 'update',$system['title'] , '', '','删除了项目管理权限：'.$system['title']);
        return ['status' => true];
    }
}