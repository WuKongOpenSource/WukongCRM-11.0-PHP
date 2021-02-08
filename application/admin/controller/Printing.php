<?php
/**
 * 打印设置控制器
 *
 * @author qifan
 * @date 2020-12-03
 */

namespace app\admin\controller;

use app\admin\logic\PrintingLogic;
use think\Hook;
use think\Request;

class Printing extends ApiCommon
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
            'permission'=>[''],
            'allow'=>['index', 'create', 'update', 'read', 'delete', 'field', 'copy']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 打印模板列表
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(PrintingLogic $printingLogic)
    {
        $page  = !empty($this->param['page'])  ? $this->param['page']  : 1;
        $limit = !empty($this->param['limit']) ? $this->param['limit'] : 15;

        $data = $printingLogic->index($page, $limit);

        return resultArray(['data' => $data]);
    }

    /**
     * 创建打印模板
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     */
    public function create(PrintingLogic $printingLogic)
    {
        $param = $this->param;

        if (empty($param['name']))    return resultArray(['error' => '缺少模板名称！']);
        if (empty($param['type']))    return resultArray(['error' => '缺少模板类型！']);
        if (empty($param['content'])) return resultArray(['error' => '缺少模板详情！']);

        if (!$printingLogic->create($param)) return resultArray(['error' => '添加失败！']);


        return resultArray(['data' => '添加成功！']);
    }

    /**
     * 获取模板详情
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     */
    public function read(PrintingLogic $printingLogic)
    {
        $id = $this->param['id'];

        if (empty($id)) return resultArray('缺少模板ID！');

        $data = $printingLogic->read($id);

        return resultArray(['data' => $data]);
    }

    /**
     * 更新模板数据
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update(PrintingLogic $printingLogic)
    {
        $param = $this->param;

        if (empty($param['id'])) return resultArray(['error' => '缺少模板ID！']);

        if (!$printingLogic->update($param)) return resultArray(['error' => '更新失败！']);

        return resultArray(['data' => '更新成功！']);
    }

    /**
     * 删除模板数据
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete(PrintingLogic $printingLogic)
    {
        $id = $this->param['id'];

        if (empty($id)) return resultArray(['error' => '缺少模板ID！']);

        if (!$printingLogic->delete($id)) return resultArray(['error' => '删除失败！']);

        return resultArray(['data' => '删除成功！']);
    }

    /**
     * 复制模板数据
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function copy(PrintingLogic $printingLogic)
    {
        $id = $this->param['id'];

        if (empty($id)) return resultArray(['error' => '缺少模板ID！']);

        if (!$printingLogic->copy($id)) return resultArray(['error' => '复制失败！']);

        return resultArray(['data' => '复制成功！']);
    }

    /**
     * 获取打印字段
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function field(PrintingLogic $printingLogic)
    {
        # 打印类型：1商机；2合同；3回款
        $type = !empty($this->param['type']) ? $this->param['type'] : 5;

        $data = $printingLogic->getFields($type);

        return resultArray(['data' => $data]);
    }
}