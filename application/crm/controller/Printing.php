<?php
/**
 * 模板打印控制器
 *
 * @author qifan
 * @date 2020-12-15
 */

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\logic\PrintingLogic;
use think\Hook;
use think\Request;

class Printing extends ApiCommon
{
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['printingdata', 'template', 'setrecord', 'getrecord']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 获取打印的数据
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function printingData(PrintingLogic $printingLogic)
    {
        $actionId   = $this->param['action_id'];
        $templateId = $this->param['template_id'];
        $type       = $this->param['type'];

        if (empty($actionId))   return resultArray(['error' => '请选择打印的数据！']);
        if (empty($templateId)) return resultArray(['error' => '请选择打印的模板！']);
        if (empty($type))       return resultArray(['error' => '请选择打印的类型！']);

        $data = $printingLogic->getPrintingData($type, $actionId, $templateId);

        return resultArray(['data' => $data]);
    }

    /**
     * 获取打印模板列表
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function template(PrintingLogic $printingLogic)
    {
        if (empty($this->param['type'])) return resultArray(['error' => '请选择打印的类型！']);

        $data = $printingLogic->getTemplateList($this->param['type']);

        return resultArray(['data' => $data]);
    }

    /**
     * 创建模板打印记录
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     */
    public function setRecord(PrintingLogic $printingLogic)
    {
        if (empty($this->param['type']))        return resultArray(['error' => '请选择模块！']);
        if (empty($this->param['action_id']))   return resultArray(['error' => '缺少数据ID！']);
        if (empty($this->param['template_id'])) return resultArray(['error' => '缺少模板ID！']);

        $userId = $this->userInfo['id'];

        if (!$printingLogic->setRecord($userId, $this->param)) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 获取打印记录
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecord(PrintingLogic $printingLogic)
    {
        if (empty($this->param['type'])) return resultArray(['error' => '请选择模块！']);

        $data = $printingLogic->getRecord($this->param, $this->userInfo['id']);

        return resultArray(['data' => $data]);
    }
}