<?php
// +----------------------------------------------------------------------
// | Description: 系统设置
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\controller\ApiCommon;
use app\admin\logic\PoolConfigLogic;
use think\Hook;
use think\Request;

class Setting extends ApiCommon
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
            'allow'=>['pool','setpool','readpool','changepool','deletepool','transferpool','customerlevel','poolfield']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        $userInfo = $this->userInfo;
        //权限判断
        $unAction = [''];
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(2,$adminTypes) && !in_array(1,$adminTypes) && !in_array($a, $unAction)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }           
    }

    /**
     * 公海配置列表
     *
     * @param PoolConfigLogic $poolConfigLogic
     * @author fanqi
     * @since 2021-03-30
     * @return \think\response\Json
     */
    public function pool(PoolConfigLogic $poolConfigLogic)
    {
        $data = $poolConfigLogic->getPoolList($this->param);

        return resultArray(['data' => $data]);
    }

    /**
     * 设置公海规则
     *
     * @param
     * @param PoolConfigLogic $poolConfigLogic 公海逻辑类
     * @author fanqi
     * @since 2021-03-29
     * @return \think\response\Json
     */
    public function setPool(PoolConfigLogic $poolConfigLogic)
    {
        $userInfo = $this->userInfo;
        $param = $this->param;
        $param['user_id'] = $userInfo['id'];

        if ($poolConfigLogic->setPoolConfig($param) === false) return resultArray(['error' => $poolConfigLogic->error]);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 公海配置详情
     *
     * @param PoolConfigLogic $poolConfigLogic
     * @author fanqi
     * @since 2021-03-30
     * @return \think\response\Json
     */
    public function readPool(PoolConfigLogic $poolConfigLogic)
    {
        $poolId = $this->param['pool_id'];

        $data = $poolConfigLogic->readPool($poolId);

        if ($data === false) return resultArray(['error' => $poolConfigLogic->error]);

        return resultArray(['data' => $data]);
    }

    /**
     * 变更公海配置状态
     *
     * @param PoolConfigLogic $poolConfigLogic
     * @author fanqi
     * @since 2021-03-30
     * @return \think\response\Json
     */
    public function changePool(PoolConfigLogic $poolConfigLogic)
    {
        if ($poolConfigLogic->changePoolStatus($this->param) === false) return resultArray(['error' => $poolConfigLogic->error]);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 删除公海配置
     *
     * @param PoolConfigLogic $poolConfigLogic
     * @author fanqi
     * @since 2021-03-30
     * @return \think\response\Json
     */
    public function deletePool(PoolConfigLogic $poolConfigLogic)
    {
        $poolId = $this->param['pool_id'];

        if ($poolConfigLogic->deletePool($poolId) === false) return resultArray(['error' => $poolConfigLogic->error]);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 公海客户转移
     *
     * @param PoolConfigLogic $poolConfigLogic
     * @author fanqi
     * @since 2021-03-30
     * @return \think\response\Json
     */
    public function transferPool(PoolConfigLogic $poolConfigLogic)
    {
        $param = $this->param;

        if ($poolConfigLogic->transferPool($param) === false) {
            return resultArray(['error' => $poolConfigLogic->error]);
        }

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 客户级别列表
     *
     * @param PoolConfigLogic $poolConfigLogic
     * @author fanqi
     * @since 2021-04-22
     * @return \think\response\Json
     */
    public function customerLevel(PoolConfigLogic $poolConfigLogic)
    {
        $data = $poolConfigLogic->getCustomerLevel();

        return resultArray(['data' => $data]);
    }

    /**
     * 公海字段
     *
     * @param PoolConfigLogic $poolConfigLogic
     * @author fanqi
     * @since 2021-04-29
     * @return \think\response\Json
     */
    public function poolField(PoolConfigLogic $poolConfigLogic)
    {
        $data = $poolConfigLogic->getPoolFieldList($this->param);

        return resultArray(['data' => $data]);
    }
}