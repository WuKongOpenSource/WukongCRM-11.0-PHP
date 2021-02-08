<?php
/**
 * 字段授权控制器
 *
 * @author qifan
 * @date 2020-12-02
 */

namespace app\admin\controller;

use app\admin\logic\FieldGrantLogic;
use think\Hook;
use think\Request;

class FieldGrant extends ApiCommon
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
            'allow'=>['index', 'update']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 字段授权列表
     *
     * @param FieldGrantLogic $fieldGrantLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(FieldGrantLogic $fieldGrantLogic)
    {
        $data = $fieldGrantLogic->index($this->param);

        return resultArray(['data' => $data]);
    }

    /**
     * 更新授权信息
     *
     * @param FieldGrantLogic $fieldGrantLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update(FieldGrantLogic $fieldGrantLogic)
    {
        if (empty($this->param['grant_id'])) return resultArray(['error' => '缺少授权ID！']);
        if (empty($this->param['content']))  return resultArray(['error' => '缺少授权数据！']);

        $status = $fieldGrantLogic->update($this->param['grant_id'], $this->param['content']);

        if ($status === false) {
            return resultArray(['error' => '更新授权信息失败！']);
        }

        return resultArray(['data' => '更新授权信息成功！']);
    }
}