<?php
/**
 * 日志控制器
 *
 * @author qifan
 * @date 2020-11-30
 */

namespace app\admin\controller;

use app\admin\logic\LogLogic;
use think\Hook;
use think\Request;

class Log extends ApiCommon
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
            'allow' => ['dataRecord', 'systemRecord', 'loginRecord']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 数据操作日志
     *
     * @param LogLogic $logLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dataRecord(LogLogic $logLogic)
    {
        $data['list']    = $logLogic->getRecordLogs($this->param);
        $data['count']   = $logLogic->getRecordLogCount($this->param);
        $data['modules'] = $logLogic->recordModules;

        return resultArray(['data' => $data]);
    }

    /**
     * 系统操作日志
     *
     * @param LogLogic $logLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function systemRecord(LogLogic $logLogic)
    {
        $data['list']    = $logLogic->getSystemLogs($this->param);
        $data['count']   = $logLogic->getSystemLogCount($this->param);
        $data['modules'] = $logLogic->systemModules;

        return resultArray(['data' => $data]);
    }

    /**
     * 登录日志
     *
     * @param LogLogic $logLogic
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function loginRecord(LogLogic $logLogic)
    {
        $data = $logLogic->getLoginRecord($this->param);

        return resultArray(['data' => $data]);
    }
}