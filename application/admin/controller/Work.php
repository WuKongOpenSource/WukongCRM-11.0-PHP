<?php
/**
 * 项目管理控制器
 *
 * @author qifan
 * @date 2020-12-17
 */

namespace app\admin\controller;

use app\admin\logic\WorkLogic;
use think\Hook;
use think\Request;

class Work extends ApiCommon
{
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
            'allow' => ['rules', 'roles', 'saverole', 'readrole', 'updaterole', 'deleterole']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 规则列表
     *
     * @param WorkLogic $workLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rules(WorkLogic $workLogic)
    {
        $data = $workLogic->getRules();

        $result = [
            'menu_id'   => 0,
            'menu_name' => "项目管理",
            'menu_type' => 1,
            'parent_id' => 0,
            'realm'     => 'work',
            'children'  => []
        ];

        $result['children'][] = ['id' => 0, 'title' => '项目', 'name' => 'work', 'children' => $data];

        return resultArray(['data' => $result]);
    }

    /**
     * 获取角色
     *
     * @param WorkLogic $workLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function roles(WorkLogic $workLogic)
    {
        $data = $workLogic->getRoles();

        return resultArray(['data' => $data]);
    }

    /**
     * 创建角色
     *
     * @param WorkLogic $workLogic
     * @return \think\response\Json
     */
    public function saveRole(WorkLogic $workLogic)
    {
        if (empty($this->param['title'])) return resultArray(['error' => '请填写权限名称！']);

        if (!$workLogic->saveRole($this->param)) return resultArray(['操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 权限角色详情
     *
     * @param WorkLogic $workLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function readRole(WorkLogic $workLogic)
    {
        if (empty($this->param['id'])) return resultArray(['error' => '请选择权限角色！']);

        $data = $workLogic->readRole($this->param['id']);

        return resultArray(['data' => $data]);
    }

    /**
     * 编辑权限角色
     *
     * @param WorkLogic $workLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateRole(WorkLogic $workLogic)
    {
        if (empty($this->param['id']))    return resultArray(['error' => '请选择要编辑的权限角色！']);
        if (empty($this->param['title'])) return resultArray(['error' => '请填写权限名称！']);

        if ($workLogic->updateRole($this->param) === false) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 删除权限角色
     *
     * @param WorkLogic $workLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function deleteRole(WorkLogic $workLogic)
    {
        if (empty($this->param['id'])) return resultArray(['error' => '请选择要删除的权限角色！']);

        $result = $workLogic->deleteRole($this->param['id']);

        if (empty($result['status'])) return resultArray(['error' => $result['error']]);

        return resultArray(['data' => '操作成功！']);
    }
}