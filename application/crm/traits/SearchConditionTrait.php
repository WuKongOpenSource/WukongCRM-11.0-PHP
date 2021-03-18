<?php
/**
 * 客户模块查询条件
 *
 * @author fanqi
 * @date 2021-03-09
 */

namespace app\crm\traits;

trait SearchConditionTrait
{
    /**
     * 联系人tab列表查询条件（权限）
     *
     * @param $userId 当前登录ID
     * @author fanqi
     * @date 2021-03-09
     * @return \Closure
     */
    public function getContactsSearchWhere($userId)
    {
        $userModel = new \app\admin\model\User();

        $authUserIds = $userModel->getUserByPer('crm', 'contacts', 'index');

        $authMapData['auth_user_ids'] = $authUserIds;
        $authMapData['user_id']       = $userId;

        return $this->getSearchAuthWhere($authMapData);
    }

    /**
     * 商机tab列表查询条件（权限）
     *
     * @param $userId 当前登录ID
     * @author fanqi
     * @date 2021-03-09
     * @return \Closure
     */
    public function getBusinessSearchWhere($userId)
    {
        $userModel = new \app\admin\model\User();

        $authUserIds = $userModel->getUserByPer('crm', 'business', 'index');

        $authMapData['auth_user_ids'] = $authUserIds;
        $authMapData['user_id']       = $userId;

        return $this->getSearchAuthWhere($authMapData);
    }

    /**
     * 合同tab列表查询条件（权限）
     *
     * @param $userId 当前登录ID
     * @author fanqi
     * @date 2021-03-09
     * @return \Closure
     */
    public function getContractSearchWhere($userId)
    {
        $userModel = new \app\admin\model\User();

        $authUserIds = $userModel->getUserByPer('crm', 'contract', 'index');

        $authMapData['auth_user_ids'] = $authUserIds;
        $authMapData['user_id']       = $userId;

        return $this->getSearchAuthWhere($authMapData);
    }

    /**
     * 回访tab列表查询条件（权限）
     *
     * @param $userId
     * @author fanqi
     * @date 2021-03-09
     * @return \Closure
     */
    public function getVisitSearchWhere($userId)
    {
        $userModel = new \app\admin\model\User();

        $authUserIds = $userModel->getUserByPer('crm', 'visit', 'index');

        $authMapData['auth_user_ids'] = $authUserIds;
        $authMapData['user_id']       = $userId;

        return $this->getSearchAuthWhere($authMapData);
    }

    /**
     * 回款tab列表查询条件（权限）
     *
     * @author fanqi
     * @date 2021-03-10
     * @return array[]
     */
    public function getReceivablesSearchWhere()
    {
        $userModel = new \app\admin\model\User();

        return $userModel->getUserByPer('crm', 'receivables', 'index');
    }

    /**
     * 发票tab列表查询条件
     *
     * @author fanqi
     * @date 2021-03-11
     * @return array|false|string
     */
    public function getInvoiceSearchWhere()
    {
        $userModel = new \app\admin\model\User();

        return $userModel->getUserByPer('crm', 'invoice', 'index');
    }

    /**
     * 产品tab列表查询条件
     *
     * @author fanqi
     * @date 2021-03-11
     * @return array|false|string
     */
    public function getProductSearchWhere()
    {
        $userModel = new \app\admin\model\User();

        return $userModel->getUserByPer('crm', 'product', 'index');
    }

    /**
     * 查询权限条件
     *
     * @param $authMapData 权限范围内的ID
     * @return \Closure
     */
    private function getSearchAuthWhere($authMapData)
    {
        return function($query) use ($authMapData) {
            $query->where(['owner_user_id' => ['in', $authMapData['auth_user_ids']]])
                ->whereOr(function ($query) use ($authMapData) {
                    $query->where('FIND_IN_SET("'.$authMapData['user_id'].'", ro_user_id)')->where(['owner_user_id' => ['neq', '']]);
                })
                ->whereOr(function ($query) use ($authMapData) {
                    $query->where('FIND_IN_SET("'.$authMapData['user_id'].'", rw_user_id)')->where(['owner_user_id' => ['neq', '']]);
                });
        };
    }
}