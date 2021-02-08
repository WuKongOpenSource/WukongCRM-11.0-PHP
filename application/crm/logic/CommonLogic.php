<?php
/**
 * crm模块下的通用功能逻辑类
 *
 * @author qifan
 * @date 2020-12-11
 */

namespace app\crm\logic;

use app\admin\controller\ApiCommon;
use app\admin\model\User;
use app\crm\model\Customer;
use think\Db;

class CommonLogic
{
    public $error = '操作失败！';

    /**
     * 快捷编辑【线索、客户、联系人、商机、合同、回款、回访、产品】
     *
     * @param $param
     * @return false|int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function quickEdit($param)
    {
        /**
         * $param['types']     表名
         * $param['action_id'] 主键ID
         * $param['field']     字段
         * $param['name']      字段中文名，用作提示
         * $param['value]      字段值
         */

        $actionId = $param['action_id'];
        $types    = $param['types'];
        unset($param['action_id']);
        unset($param['types']);

        # 模型
        $model = db($types);

        # 主键
        $primaryKey = '';
        if ($types == 'crm_leads')       $primaryKey = 'leads_id';
        if ($types == 'crm_customer')    $primaryKey = 'customer_id';
        if ($types == 'crm_contacts')    $primaryKey = 'contacts_id';
        if ($types == 'crm_business')    $primaryKey = 'business_id';
        if ($types == 'crm_contract')    $primaryKey = 'contract_id';
        if ($types == 'crm_receivables') $primaryKey = 'receivables_id';
        if ($types == 'crm_visit')       $primaryKey = 'visit_id';
        if ($types == 'crm_product')     $primaryKey = 'product_id';

        $apiCommon = new ApiCommon();
        $userModel = new User();

        # 客户模块快捷编辑权限验证
        if ($types == 'crm_customer') {
            $dataInfo  = $model->field(['ro_user_id', 'rw_user_id', 'owner_user_id'])->where($primaryKey, $actionId)->find();
            $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'update');
            $rwPre = $userModel->rwPre($apiCommon->userInfo['id'], $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
            $wherePool = (new Customer())->getWhereByPool();
            $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $param['action_id']])->where($wherePool)->find();
            if ($resPool || (!in_array($dataInfo['owner_user_id'],$auth_user_ids) && !$rwPre)) {
                $this->error = '无权操作！';
                return false;
            }
        }

        # 商机模块快捷编辑权限验证
        if ($types == 'crm_business') {
            $dataInfo  = $model->field(['ro_user_id', 'rw_user_id', 'owner_user_id'])->where($primaryKey, $actionId)->find();
            $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'update');
            $rwPre = $userModel->rwPre($apiCommon->userInfo['id'], $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
            if (!in_array($dataInfo['owner_user_id'],$auth_user_ids) && !$rwPre) {
                $this->error = '无权操作！';
                return false;
            }
        }

        # 合同模块快捷编辑权限验证
        if ($types == 'crm_contract') {
            $dataInfo  = $model->field(['ro_user_id', 'rw_user_id', 'owner_user_id'])->where($primaryKey, $actionId)->find();
            $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'update');
            $rwPre = $userModel->rwPre($apiCommon->userInfo['id'], $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
            if (!in_array($dataInfo['owner_user_id'], $auth_user_ids) && !$rwPre) {
                $this->error = '无权操作！';
                return false;
            }
        }

        foreach ($param AS $key => $value) {
            # 查询自定义字段信息
            $fieldInfo = Db::name('admin_field')->field(['max_length', 'is_unique', 'is_null', 'name'])
                ->where('types', $types)->where('field', $key)->find();

            # 字符长度
            if (!empty($fieldInfo['max_length']) && strlen($value) > $fieldInfo['max_length']) {
                $this->error = $fieldInfo['name'] . ' 字符长度不能超过 ' . $fieldInfo['max_length'] . ' 个字符！';
                return false;
            }

            # 必填
            if (!empty($fieldInfo['is_null']) && empty($value)) {
                $this->error = $fieldInfo['name'] . ' 是必填信息，不能为空！';
                return false;
            }

            # 唯一
            if (!empty($fieldInfo['is_unique']) && $model->where([$primaryKey => ['neq', $actionId]])->where($key, $value)->value($primaryKey)) {
                $this->error = $fieldInfo['name'] . ' 内容重复！';
                return false;
            }
        }

        # 编辑参数
        $data = [];
        if (!empty($param['list'])) {
            foreach ($param['list'] AS $key => $value) {
                foreach ($value AS $k => $v) {
                    # 处理下次联系时间格式
                    if ($k == 'next_time') {
                        $data[$k] = !empty($v) ? strtotime($v) : '';
                    } else {
                        $data[$k] = $v;
                    }
                    # 处理产品类别
                    if ($types == 'crm_product' && $k == 'category_id') {
                        $categorys = explode(',', $v);
                        $data[$k]  = $categorys[count($categorys) - 1];
                    }
                }
            }
            $data[$primaryKey] = $actionId;
        }

        return $model->update($data);
    }
}