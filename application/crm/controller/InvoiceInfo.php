<?php
/**
 * 发票开户行控制器
 *
 * @author qifan
 * @date 2020-12-19
 */

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\model\InvoiceInfoLogic;
use think\Hook;
use think\Request;

class InvoiceInfo extends ApiCommon
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
            'allow' => ['index', 'read', 'save', 'update', 'delete']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        } else {
            $param = Request::instance()->param();
            $this->param = $param;
        }
    }

    /**
     * 列表
     *
     * @param InvoiceInfoLogic $invoiceInfoLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(InvoiceInfoLogic $invoiceInfoLogic)
    {
        $data = $invoiceInfoLogic->index($this->param);

        return resultArray(['data' => $data]);
    }

    /**
     * 详情
     *
     * @param InvoiceInfoLogic $invoiceInfoLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read(InvoiceInfoLogic $invoiceInfoLogic)
    {
        if (empty($this->param['info_id'])) return resultArray(['error' => '参数错误']);

        $data = $invoiceInfoLogic->read($this->param['info_id']);

        return resultArray(['data' => $data]);
    }

    /**
     * 创建
     *
     * @param InvoiceInfoLogic $invoiceInfoLogic
     * @return \think\response\Json
     */
    public function save(InvoiceInfoLogic $invoiceInfoLogic)
    {
        if (empty($this->param['customer_id'])) return resultArray(['error' => '请选择客户！']);

        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];

        if (!$invoiceInfoLogic->save($param)) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 编辑
     *
     * @param InvoiceInfoLogic $invoiceInfoLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update(InvoiceInfoLogic $invoiceInfoLogic)
    {
        if (empty($this->param['info_id'])) return resultArray(['error' => '参数错误！']);

        if (!$invoiceInfoLogic->update($this->param)) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 删除
     *
     * @param InvoiceInfoLogic $invoiceInfoLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete(InvoiceInfoLogic $invoiceInfoLogic)
    {
        if (empty($this->param['info_id'])) return resultArray(['error' => '参数错误！']);

        if (!$invoiceInfoLogic->delete($this->param['info_id'])) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }
}