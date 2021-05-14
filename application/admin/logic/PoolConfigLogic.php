<?php

namespace app\admin\logic;

use PDOStatement;
use think\Collection;
use think\Db;

class PoolConfigLogic
{
    public $error = '操作失败！';

    /**
     * 公海配置列表
     *
     * @param array $param page 页码，limit 每页条数
     * @author fanqi
     * @since 2021-03-30
     * @return array
     */
    public function getPoolList($param)
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $limit = !empty($param['limit']) ? $param['limit'] : 15;

        $count = db('crm_customer_pool')->count();

        # 公海列表
        $list = db('crm_customer_pool')->field([
            'pool_id', 'pool_name', 'admin_user_ids', 'user_ids', 'department_ids', 'status'
        ])->limit(($page - 1) * $limit, $limit)->select();

        # 统计公海下的客户数量
        $customerData = [];
        $customerList = db('crm_customer_pool_relation')->field(['pool_id', 'count(customer_id) AS customer_count'])->group('pool_id')->select();
        foreach ($customerList AS $key => $value) {
            $customerData[$value['pool_id']] = $value['customer_count'];
        }

        foreach ($list AS $key => $value) {
            # 公海管理员
            $adminUserIds = trim($value['admin_user_ids'], ',');
            $adminUserNames = db('admin_user')->whereIn('id', $adminUserIds)->column('realname');

            # 公海成员
            $userIds = trim($value['user_ids'], ',');
            $userNames = db('admin_user')->whereIn('id', $userIds)->column('realname');

            # 部门
            $structureIds = trim($value['department_ids'], ',');
            $structureNames = db('admin_structure')->whereIn('id', $structureIds)->column('name');

            # 公海成员
            $poolMembers = array_merge($structureNames, $userNames);

            $list[$key]['admin_user_names'] = implode(',', $adminUserNames);
            $list[$key]['user_names'] = implode(',', array_unique($poolMembers));

            $list[$key]['customer_count'] = !empty($customerData[$value['pool_id']]) ? $customerData[$value['pool_id']] : 0;
        }

        return ['count' => $count, 'list' => !empty($list) ? $list : []];
    }

    /**
     * 设置多公海配置
     *
     * @param $param
     * @author fanqi
     * @since 2021-03-30
     * @return bool
     */
    public function setPoolConfig($param)
    {
        if (empty($param['pool_name'])) {
            $this->error = '请填写公海名称！';
            return false;
        }

        if (!empty($param['pool_name']) && mb_strlen($param['pool_name']) > 100) {
            $this->error = '公海名称最多只能输入100个字符！';
            return false;
        }

        if (empty($param['admin_user_ids'])) {
            $this->error = '请选择公海管理员！';
            return false;
        }

        if (empty($param['user_ids']) && empty($param['department_ids'])) {
            $this->error = '请选择公海成员！';
            return false;
        }

        if (!empty($param['recycle_conf']) && empty($param['rule'])) {
            $this->error = '请设置回收规则！';
            return false;
        }

        $repeatWhere['pool_name'] = $param['pool_name'];
        if (!empty($param['pool_id'])) $repeatWhere['pool_id'] = ['neq', $param['pool_id']];
        if (db('crm_customer_pool')->where($repeatWhere)->value('pool_id')) {
            $this->error = '公海名称重复';
            return false;
        }

        $poolData = [
            'pool_name' => $param['pool_name'],
            'admin_user_ids' => ',' . $param['admin_user_ids'] . ',',
            'user_ids' => ',' . $param['user_ids'] . ',',
            'department_ids' => !empty($param['department_ids']) ? ',' . $param['department_ids'] . ',' : '',
            'status' => 1,
            'before_owner_conf' => $param['before_owner_conf'],
            'before_owner_day'  => $param['before_owner_day'],
            'receive_conf' => $param['receive_conf'],
            'receive_count' => $param['receive_count'],
            'remind_conf' => $param['remind_conf'],
            'remain_day' => $param['remain_day'],
            'recycle_conf' => $param['recycle_conf'],
            'create_user_id' => $param['user_id'],
            'create_time' => time()
        ];

        Db::startTrans();
        try {
            if (!empty($param['pool_id'])) {
                # 编辑
                $poolId = $param['pool_id'];
                Db::name('crm_customer_pool')->where('pool_id', $poolId)->update($poolData);
            } else {
                # 创建
                $poolId = Db::name('crm_customer_pool')->insert($poolData, false, true);
            }

            # 公海字段
            $fieldData = $this->getPoolField($param['field'], $poolId);
            if (!empty($fieldData)) {
                Db::name('crm_customer_pool_field_setting')->where('pool_id', $poolId)->delete();
                Db::name('crm_customer_pool_field_setting')->insertAll($fieldData);
            }

            # 公海规则
            $ruleData = $this->getPoolRule($param['rule'], $poolId);
            if (!empty($ruleData)) {
                Db::name('crm_customer_pool_rule')->where('pool_id', $poolId)->delete();
                Db::name('crm_customer_pool_rule')->insertAll($ruleData);
            }

            Db::commit();

            return true;
        } catch (\Exception $e) {
            Db::rollback();

            $this->error = '创建公海失败！';
            return false;
        }
    }

    /**
     * 公海配置详情
     *
     * @param int $poolId 公海ID
     * @author fanqi
     * @since 2021-03-30
     * @return array|bool
     */
    public function readPool($poolId)
    {
        $data = db('crm_customer_pool')->where('pool_id', $poolId)->find();

        if (empty($data['pool_id'])) {
            $this->error = '没有查询到数据！';
            return false;
        }

        # 时间格式
        $data['create_time'] = date('Y-m-d H:i:s', $data['create_time']);

        # 公海管理员
        $adminUserIds = trim($data['admin_user_ids'], ',');
        $data['admin_user_ids'] = $adminUserIds;
        $data['admin_user_info'] = db('admin_user')->field(['id', 'realname', 'thumb_img'])->whereIn('id', $adminUserIds)->select();
        foreach ($data['admin_user_info'] AS $key => $value) {
            $data['admin_user_info'][$key]['thumb_img'] = getFullPath($value['thumb_img']);
        }

        # 公海成员
        $userIds = trim($data['user_ids'], ',');
        $data['user_ids'] = $userIds;
        $data['user_info'] = db('admin_user')->field(['id', 'realname', 'thumb_img'])->whereIn('id', $userIds)->select();
        foreach ($data['user_info'] AS $key => $value) {
            $data['user_info'][$key]['thumb_img'] = getFullPath($value['thumb_img']);
        }

        # 公海部门
        $departmentIds = trim($data['department_ids'], ',');
        $data['department_ids'] = $departmentIds;
        $data['department_info'] = db('admin_structure')->field(['id', 'name'])->whereIn('id', $departmentIds)->select();

        # 公海字段
        $data['field'] = db('crm_customer_pool_field_setting')->where('pool_id', $data['pool_id'])->select();

        # 公海规则
        $data['rule'] = db('crm_customer_pool_rule')->where('pool_id', $data['pool_id'])->select();
        foreach ($data['rule'] AS $key => $value) {
            if (!empty($value['level'])) {
                $data['rule'][$key]['level'] = json_decode($value['level'], true);
                $data['rule'][$key]['level_setting'] = json_decode($value['level'], true);
            }
        }

        # 客户数量
        $data['customer_count'] = db('crm_customer_pool_relation')->where('pool_id', $poolId)->count();

        return $data;
    }

    /**
     * 变更公海配置状态
     *
     * @param array $param pool_id 公海ID, status 状态（1启用、0停用）
     * @author fanqi
     * @since 2021-03-30
     * @return false|int|string
     */
    public function changePoolStatus($param)
    {
        $poolId = $param['pool_id'];
        $status = $param['status'];

        if ($status == 0 && db('crm_customer_pool_relation')->where('pool_id', $poolId)->count() > 0) {
            $this->error = '公海内有客户，不能停用！';
            return false;
        }

        if ($status == 0 && db('crm_customer_pool')->where(['pool_id' => ['neq', $poolId], 'status' => 1])->count() < 1) {
            $this->error = '至少要开启一个公海！';
            return false;
        }

        return db('crm_customer_pool')->where('pool_id', $poolId)->update(['status' => $status]);
    }

    /**
     * 删除公海配置
     *
     * @param int $poolId 公海ID
     * @author fanqi
     * @since 2021-03-30
     * @return bool
     */
    public function deletePool($poolId)
    {
        if (db('crm_customer_pool_relation')->where('pool_id', $poolId)->count() > 0) {
            $this->error = '公海内有客户，不能删除！';
            return false;
        }

        if (db('crm_customer_pool')->where(['pool_id' => ['neq', $poolId], 'status' => 1])->count() < 1) {
            $this->error = '至少要保留一个开启的公海！';
            return false;
        }

        Db::startTrans();
        try {
            # 删除公海规则数据
            Db::name('crm_customer_pool_rule')->where('pool_id', $poolId)->delete();

            # 删除公海字段数据
            Db::name('crm_customer_pool_field_setting')->where('pool_id', $poolId)->delete();

            # 删除用户保存的公海字段数据
            Db::name('crm_customer_pool_field_style')->where('pool_id', $poolId)->delete();

            # 删除公海数据
            Db::name('crm_customer_pool')->where('pool_id', $poolId)->delete();

            Db::commit();

            return true;
        } catch (\Exception $e) {
            Db::rollback();

            $this->error = '删除公海配置失败！';
            return false;
        }
    }

    /**
     * 转移公海客户
     *
     * @param array $param source_pool_id 源公海ID，target_pool_id 目标公海ID
     * @author fanqi
     * @since 2021-03-30
     * @return bool
     */
    public function transferPool($param)
    {
        if (empty($param['source_pool_id']) || empty($param['target_pool_id'])) {
            $this->error = '缺少源ID或目标ID';
            return false;
        }

        # 源
        $sourceCustomerIds = Db::name('crm_customer_pool_relation')->where('pool_id', $param['source_pool_id'])->column('customer_id');
        # 目标
        $targetCustomerIds = Db::name('crm_customer_pool_relation')->where('pool_id', $param['target_pool_id'])->column('customer_id');
        # 差异
        $diffCustomerIds = array_diff($sourceCustomerIds, $targetCustomerIds);

        $data = [];
        foreach ($diffCustomerIds AS $key => $value) {
            $data[] = [
                'customer_id' => $value,
                'pool_id' => $param['target_pool_id']
            ];
        }

        Db::startTrans();
        try {
            Db::name('crm_customer_pool_relation')->where('pool_id', $param['source_pool_id'])->delete();

            if (!empty($data)) Db::name('crm_customer_pool_relation')->insertAll($data);

            Db::commit();

            return true;
        } catch (\Exception $e) {
            Db::rollback();

            $this->error = '转移失败！';
            return false;
        }
    }

    /**
     * 获取客户级别列表
     *
     * @author fanqi
     * @since 2021-04-22
     * @return array
     */
    public function getCustomerLevel()
    {
        $setting = db('admin_field')->where(['types' => 'crm_customer', 'field' => 'level'])->value('setting');

        $data = explode(chr(10), $setting);

        return !empty($data) ? $data : [];
    }

    /**
     * 获取公海字段列表
     *
     * @param array $param pool_id 公海ID
     * @author fanqi
     * @since 2021-04-29
     * @return bool|PDOStatement|string|Collection
     */
    public function getPoolFieldList($param)
    {
        if (!empty($param['pool_id'])) {
            return db('crm_customer_pool_field_setting')->field(['field_name AS field', 'name', 'form_type', 'is_hidden'])->where('pool_id', $param['pool_id'])->select();
        } else {
            $data = db('admin_field')->field(['field', 'name', 'form_type', 'is_hidden'])->where(['types' => 'crm_customer'])->select();

            $address = [
                'field'     => 'address',
                'name'      => '省、市、区/县',
                'form_type' => 'customer_address',
                'is_hidden' => 0
            ];
            $detailAddress = [
                'field'     => 'detail_address',
                'name'      => '详细地址',
                'form_type' => 'text',
                'is_hidden' => 0
            ];
            $lastRecord = [
                'field'     => 'last_record',
                'name'      => '最后跟进记录',
                'form_type' => 'text',
                'is_hidden' => 0
            ];
            $lastTime = [
                'field'     => 'last_time',
                'name'      => '最后跟进时间',
                'form_type' => 'datetime',
                'is_hidden' => 0
            ];
            $beforeOwnerUser = [
                'field'     => 'before_owner_user_id',
                'name'      => '前负责人',
                'form_type' => 'user',
                'is_hidden' => 0
            ];
            $intoPoolTime = [
                'field'     => 'into_pool_time',
                'name'      => '进入公海时间',
                'form_type' => 'datetime',
                'is_hidden' => 0
            ];
            $createTime = [
                'field'     => 'create_time',
                'name'      => '创建时间',
                'form_type' => 'datetime',
                'is_hidden' => 0
            ];
            $updateTime = [
                'field'     => 'update_time',
                'name'      => '更新时间',
                'form_type' => 'datetime',
                'is_hidden' => 0
            ];
            $createUser = [
                'field'     => 'create_user_id',
                'name'      => '创建人',
                'form_type' => 'user',
                'is_hidden' => 0
            ];
            array_push($data, $address, $detailAddress, $lastRecord, $lastTime, $createTime, $updateTime, $createUser, $beforeOwnerUser, $intoPoolTime);

            return $data;
        }
    }

    /**
     * 处理公海规则数据
     *
     * @param array $rules 规则数据
     * @param int $poolId 公海ID
     * @author fanqi
     * @since 2021-03-30
     * @return array
     */
    private function getPoolRule($rules, $poolId)
    {
        $result = [];

        foreach ($rules AS $key => $value) {
            $result[] = [
                'pool_id' => $poolId,
                'type' => $value['type'],
                'deal_handle' => $value['deal_handle'],
                'business_handle' => $value['business_handle'],
                'level_conf' => $value['level_conf'],
                'level' => json_encode($value['level']),
                'limit_day' => !empty($value['limit_day']) ? $value['limit_day'] : 0
            ];
        }

        return $result;
    }

    /**
     * 处理公海字段数据
     *
     * @param array $fields 字段列表
     * @param int $poolId 公海ID
     * @author fanqi
     * @since 2021-03-30
     * @return array
     */
    private function getPoolField($fields, $poolId)
    {
        $result = [];

        foreach ($fields AS $key => $value) {
            $result[] = [
                'pool_id' => $poolId,
                'name' => $value['name'],
                'field_name' => $value['field'],
                'form_type' => $value['form_type'],
                'is_hidden' => $value['is_hidden']
            ];
        }

        return $result;
    }
}