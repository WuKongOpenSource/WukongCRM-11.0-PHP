<?php
/**
 * crm模块下的通用功能控制器
 *
 * @author qifan
 * @date 2020-12-11
 */

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\logic\CommonLogic;
use think\Hook;
use think\Request;

class Common extends ApiCommon
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
            'allow'      => ['quickedit']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 快捷编辑
     *
     * @param CommonLogic $commonLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function quickEdit(CommonLogic $commonLogic)
    {
        if (empty($this->param['types']))     return resultArray(['error' => '缺少模块类型！']);
        if (empty($this->param['action_id'])) return resultArray(['error' => '缺少数据ID！']);

        if ($commonLogic->quickEdit($this->param) === false) return resultArray(['error' => $commonLogic->error]);

        return resultArray(['data' => '操作成功！']);
    }
}