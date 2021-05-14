<?php
/**
 * 初始化控制器
 *
 * @author qifan
 * @date 2020-01-05
 */

namespace app\admin\controller;

use app\admin\logic\InitializeLogic;
use think\Hook;
use think\Request;

class Initialize extends ApiCommon
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
            'permission' => [],
            'allow' => ['verification']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 列表
     *
     * @return \think\response\Json
     */
    public function index()
    {
        $data = [
            ['type' => 1, 'name' => '全部应用'],
            ['type' => 2, 'name' => '客户管理'],
            ['type' => 3, 'name' => '任务/审批'],
            ['type' => 4, 'name' => '日志'],
            ['type' => 5, 'name' => '项目管理'],
            ['type' => 6, 'name' => '日历'],
        ];

        return resultArray(['data' => $data]);
    }

    /**
     * 初始化数据
     *
     * @param InitializeLogic $initializeLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update(InitializeLogic $initializeLogic)
    {
        $userInfo = $this->userInfo;

        if (empty($this->param['type']) || !is_array($this->param['type'])) return resultArray(['error' => '模块类型错误！']);

        if (!empty($this->param['password']) && !$initializeLogic->verification($this->userInfo['id'], $this->param['password'])) {
            return resultArray(['error' => '密码错误！']);
        }

        $initializeLogic->update($this->param['type']);

        # 系统操作日志
        SystemActionLog($userInfo['id'], 'admin_user','work_task', 1, 'update', '重置数据' , '', '','重置了数据');

        return resultArray(['data' => $initializeLogic->log]);
    }

    /**
     * 验证密码
     *
     * @param InitializeLogic $initializeLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function verification(InitializeLogic $initializeLogic)
    {
        if (empty($this->param['password'])) return resultArray(['error' => '参数错误！']);

        if (!$initializeLogic->verification($this->userInfo['id'], $this->param['password'])) return resultArray(['error' => '密码错误！']);

        return resultArray(['data' => '密码正确！']);
    }
}