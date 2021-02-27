<?php
/**
 * 字段授权逻辑类
 *
 * @author qifan
 * @date 2020-12-01
 */

namespace app\admin\logic;

use think\Db;

class FieldGrantLogic
{
    /**
     * 字段授权列表
     *
     * @param $param
     * @return array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($param)
    {
        $where = function ($query) use ($param) {
            $query->where('module', $param['module']);
            $query->where('column', $param['column']);
            $query->where('role_id', $param['role_id']);
        };

        $count = Db::name('admin_field_grant')->where($where)->count();

        # 如果该角色下没有字段授权数据则自动添加
        if ($count == 0 && Db::name('admin_group')->where('id', $param['role_id'])->find()) {
            $this->createCrmFieldGrant($param['role_id']);
        }

        $data = Db::name('admin_field_grant')->field(['grant_id', 'content'])->where($where)->find();

        if (!empty($data['content'])) $data['content'] = unserialize($data['content']);

        return !empty($data) ? $data : [];
    }

    /**
     * 更新授权字段
     *
     * @param $grantId
     * @param $content
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update($grantId, $content)
    {
        return Db::name('admin_field_grant')->where('grant_id', $grantId)->update(['content' => serialize(array_values($content))]);
    }

    /**
     * 添加字段授权信息
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createCrmFieldGrant($roleId)
    {
        # 添加线索字段授权数据
        $this->createLeadsFieldGrant($roleId);
        # 添加客户字段授权数据
        $this->createCustomerFieldGrant($roleId);
        # 添加联系人字段授权数据
        $this->createContactsFieldGrant($roleId);
        # 添加商机字段授权数据
        $this->createBusinessFieldGrant($roleId);
        # 添加合同字段授权数据
        $this->createContractfieldGrant($roleId);
        # 添加回款字段授权数据
        $this->createReceivablesFieldGrant($roleId);
        # 添加产品字段授权信息
        $this->createProductFieldGrant($roleId);
        # 添加回访字段授权信息
        $this->createVisitFieldGrant($roleId);
    }

    /**
     * 删除授权字段数据
     *
     * @param $roleId
     * @param string $module
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function deleteCrmFieldGrant($roleId)
    {
        Db::name('admin_field_grant')->where('module', 'crm')->where('role_id', $roleId)->delete();
    }

    /**
     * 拷贝字段授权数据
     *
     * @param $copyId
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function copyCrmFieldGrant($copyId, $roleId)
    {
        $data = [];

        $list = Db::name('admin_field_grant')->where('module', 'crm')->where('role_id', $copyId)->select();

        foreach ($list AS $key => $value) {
            $data[] = [
                'role_id'     => $roleId,
                'module'      => $value['module'],
                'column'      => $value['column'],
                'content'     => $value['content'],
                'create_time' => time(),
                'update_time' => time()
            ];
        }

        if (!empty($data)) Db::name('admin_field_grant')->insertAll($data);
    }

    /**
     * 同步更新自定义字段的授权信息
     *
     * @param $types
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function fieldGrantDiyHandle($types)
    {
        $typesArray = explode('_', $types);

        # 只处理客户管理角色下的字段授权
        if ($typesArray[0] != 'crm') return false;

        # 查询自定义字段表
        $fieldBaseData = [];
        $fieldList = Db::name('admin_field')->field(['name', 'field'])->where('types', $types)->select();
        foreach ($fieldList AS $key => $value) {
            $fieldBaseData[$value['field']] = $value;
        }

        # 查询字段授权表
        $grantList = Db::name('admin_field_grant')->field(['grant_id', 'content'])->where('column', $typesArray[1])->select();

        # 处理授权字段的数据更新
        foreach ($grantList AS $key => $value) {
            $content   = unserialize($value['content']);
            $fieldData = $fieldBaseData;

            foreach ($content AS $k => $v) {
                # 只处理自定义字段
                if ($v['is_diy'] == 0) continue;

                if (empty($fieldData[$v['field']])) {
                    # 【处理删除：】没有在$fieldData找到，说明自定义字段被删除，则进行同步删除。
                    unset($content[(int)$k]);
                } else {

                    # 【处理更新：】如果在$fieldData找到，则进行同步更新。
                    $content[$k]['name'] = $fieldData[$v['field']]['name'];
                    $content[$k]['field'] = $fieldData[$v['field']]['field'];

                    # 删除$fieldData的数据，方便统计新增的自定义字段。
                    unset($fieldData[(string)$v['field']]);
                }

            }

            # 【处理新增】如果$fieldData还有数据，说明是新增的，则进行同步新增。
            if (!empty($fieldData)) {
                foreach ($fieldData AS $k => $v) {
                    $content[] = [
                        'name'            => $v['name'],
                        'field'           => $v['field'],
                        'read'            => 1,
                        'read_operation'  => 1,
                        'write'           => 1,
                        'write_operation' => 1,
                        'is_diy'          => 1
                    ];
                }
            }

            # todo 暂时将数据库操作写在循环中！！！
            Db::name('admin_field_grant')->where('grant_id', $value['grant_id'])->update(['content' => serialize(array_values($content))]);
        }

        return true;
    }

    /**
     * 添加线索字段授权数据
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createLeadsFieldGrant($roleId)
    {
        $content = [];

        $leadsList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_leads')->select();

        # 处理自定义字段
        foreach ($leadsList AS $key => $value) {
            $content[] = [
                'name'            => $value['name'],
                'field'           => $value['field'],
                'read'            => 1,
                'read_operation'  => in_array($value['field'], ['name', 'next_time']) ? 0 : 1,
                'write'           => 1,
                'write_operation' => $value['field'] == 'next_time' ? 0 : 1,
                'is_diy'          => 1
            ];
        }

        # 处理固定字段
        $content[] = ['name' => '负责人', 'field' => 'owner_user_id', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '最后跟进记录', 'field' => 'record', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建人', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建时间', 'field' => 'create_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '更新时间', 'field' => 'update_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];

        Db::name('admin_field_grant')->insert([
            'role_id'     => $roleId,
            'module'      => 'crm',
            'column'      => 'leads',
            'content'     => serialize($content),
            'create_time' => time(),
            'update_time' => time()
        ]);
    }

    /**
     * 添加客户字段授权数据
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createCustomerFieldGrant($roleId)
    {
        $content = [];

        $customerList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_customer')->select();

        # 处理自定义字段
        foreach ($customerList AS $key => $value) {
            $content[] = [
                'name'            => $value['name'],
                'field'           => $value['field'],
                'read'            => 1,
                'read_operation'  => in_array($value['field'], ['name', 'deal_status']) ? 0 : 1,
                'write'           => 1,
                'write_operation' => $value['field'] == 'deal_status' ? 0 : 1,
            ];
        }

        # 处理固定字段
        $content[] = ['name' => '合同编号', 'field' => 'contract', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '负责人', 'field' => 'owner_user_id', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '最后跟进记录', 'field' => 'record', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '创建人', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '创建时间', 'field' => 'create_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '更新时间', 'field' => 'update_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '最后跟进时间', 'field' => 'record_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '负责人获取客户时间', 'field' => 'deal_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '成交状态', 'field' => 'deal_status', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '锁定状态', 'field' => 'is_lock', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];
        $content[] = ['name' => '距进入公海天数', 'field' => 'pool_day', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0];

        Db::name('admin_field_grant')->insert([
            'role_id'     => $roleId,
            'module'      => 'crm',
            'column'      => 'customer',
            'content'     => serialize($content),
            'create_time' => time(),
            'update_time' => time()
        ]);
    }

    /**
     * 添加联系人字段授权数据
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createContactsFieldGrant($roleId)
    {
        $content = [];

        $contactsList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_contacts')->select();

        # 处理自定义字段
        foreach ($contactsList AS $key => $value) {
            $content[] = [
                'name'            => $value['name'],
                'field'           => $value['field'],
                'read'            => 1,
                'read_operation'  => in_array($value['field'], ['name', 'next_time']) ? 0 : 1,
                'write'           => 1,
                'write_operation' => $value['field'] == 'next_time' ? 0 : 1,
                'is_diy'          => 1
            ];
        }

        # 处理固定字段
        $content[] = ['name' => '创建人', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '负责人', 'field' => 'owner_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建时间', 'field' => 'create_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '更新时间', 'field' => 'update_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];

        Db::name('admin_field_grant')->insert([
            'role_id'     => $roleId,
            'module'      => 'crm',
            'column'      => 'contacts',
            'content'     => serialize($content),
            'create_time' => time(),
            'update_time' => time()
        ]);
    }

    /**
     * 添加商机字段授权数据
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createBusinessFieldGrant($roleId)
    {
        $content = [];

        $BusinessList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_business')->select();

        # 处理自定义字段
        foreach ($BusinessList AS $key => $value) {
            $content[] = [
                'name'            => $value['name'],
                'field'           => $value['field'],
                'read'            => 1,
                'read_operation'  => in_array($value['field'], ['customer_id', 'type_id', 'status_id']) == '' ? 0 : 1,
                'write'           => 1,
                'write_operation' => in_array($value['field'], ['customer_id', 'type_id', 'status_id']) ? 0 : 1,
                'is_diy'          => 1
            ];
        }

        # 处理固定字段
        $content[] = ['name' => '整单折扣', 'field' => 'discount_rate', 'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0];
        $content[] = ['name' => '创建人', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '负责人', 'field' => 'owner_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建时间', 'field' => 'create_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '更新时间', 'field' => 'update_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];

        Db::name('admin_field_grant')->insert([
            'role_id'     => $roleId,
            'module'      => 'crm',
            'column'      => 'business',
            'content'     => serialize($content),
            'create_time' => time(),
            'update_time' => time()
        ]);
    }

    /**
     * 添加合同字段授权数据
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createContractfieldGrant($roleId)
    {
        $content = [];

        $contractList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_contract')->select();

        # 处理自定义字段
        foreach ($contractList AS $key => $value) {
            $content[] = [
                'name'            => $value['name'],
                'field'           => $value['field'],
                'read'            => 1,
                'read_operation'  => $value['field'] == 'num' ? 0 : 1,
                'write'           => 1,
                'write_operation' => 1,
                'is_diy'          => 1
            ];
        }

        # 处理固定字段
        $content[] = ['name' => '创建人', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '负责人', 'field' => 'owner_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建时间', 'field' => 'create_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '更新时间', 'field' => 'update_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '最后跟进记录', 'field' => 'record', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '最后跟进记录', 'field' => 'record_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '已收款金额', 'field' => 'received', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '未收款金额', 'field' => 'uncollected', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '审核状态', 'field' => 'check_status', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];

        Db::name('admin_field_grant')->insert([
            'role_id'     => $roleId,
            'module'      => 'crm',
            'column'      => 'contract',
            'content'     => serialize($content),
            'create_time' => time(),
            'update_time' => time()
        ]);
    }

    /**
     * 添加回款字段授权数据
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createReceivablesFieldGrant($roleId)
    {
        $content = [];

        $receivablesList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_receivables')->select();

        # 处理自定义字段
        foreach ($receivablesList AS $key => $value) {
            $content[] = [
                'name'            => $value['name'],
                'field'           => $value['field'],
                'read'            => 1,
                'read_operation'  => 1,
                'write'           => 1,
                'write_operation' => 1,
                'is_diy'          => 1
            ];
        }

        # 处理固定字段
        $content[] = ['name' => '合同金额', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建人', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '负责人', 'field' => 'owner_user_id', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建时间', 'field' => 'create_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '更新时间', 'field' => 'update_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '审核状态', 'field' => 'check_status', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];

        Db::name('admin_field_grant')->insert([
            'role_id'     => $roleId,
            'module'      => 'crm',
            'column'      => 'receivables',
            'content'     => serialize($content),
            'create_time' => time(),
            'update_time' => time()
        ]);
    }

    /**
     * 添加产品字段授权信息
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createProductFieldGrant($roleId)
    {
        $content = [];

        $productList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_product')->select();

        # 处理自定义字段
        foreach ($productList AS $key => $value) {
            $readOperation  = 1;
            $writeOperation = 1;

            if (in_array($value['field'], ['name', 'category_id', 'unit', 'price', 'status'])) $readOperation = 0;
            if (in_array($value['field'], ['status'])) $writeOperation = 0;

            $content[] = [
                'name'            => $value['name'],
                'field'           => $value['field'],
                'read'            => 1,
                'read_operation'  => $readOperation,
                'write'           => 1,
                'write_operation' => $writeOperation,
                'is_diy'          => 1
            ];
        }

        # 处理固定字段
        $content[] = ['name' => '负责人', 'field' => 'owner_user_id', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建人', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建时间', 'field' => 'create_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '更新时间', 'field' => 'update_time', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];

        Db::name('admin_field_grant')->insert([
            'role_id'     => $roleId,
            'module'      => 'crm',
            'column'      => 'product',
            'content'     => serialize($content),
            'create_time' => time(),
            'update_time' => time()
        ]);
    }

    /**
     * 添加回访字段授权信息
     *
     * @param $roleId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createVisitFieldGrant($roleId)
    {
        $content = [];

        $visitList = Db::name('admin_field')->field(['name', 'field'])->where(['type' => 'crm_visit', 'operating' => 0])->select();

        # 处理自定义字段
        foreach ($visitList AS $key => $value) {
            $content[] = [
                'name'            => $value['name'],
                'field'           => $value['field'],
                'read'            => 1,
                'read_operation'  => 1,
                'write'           => 1,
                'write_operation' => 1,
                'is_diy'          => 1
            ];
        }

        # 处理固定字段
        $content[] = ['name' => '回访编号', 'field' => 'number', 'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0];
        $content[] = ['name' => '回访形式', 'field' => 'shape', 'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0];
        $content[] = ['name' => '客户满意度', 'field' => 'satisfaction', 'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0];
        $content[] = ['name' => '回访时间', 'field' => 'visit_time', 'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0];
        $content[] = ['name' => '客户名称', 'field' => 'customer_id', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '联系人', 'field' => 'contacts_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '合同编号', 'field' => 'contract_id', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '客户反馈', 'field' => 'feedback', 'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0];
        $content[] = ['name' => '回访人', 'field' => 'owner_user_id', 'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0];
        $content[] = ['name' => '创建时间', 'field' => 'create_time', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '编辑时间', 'field' => 'update_time', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];
        $content[] = ['name' => '创建人', 'field' => 'create_user_id', 'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0];


        Db::name('admin_field_grant')->insert([
            'role_id'     => $roleId,
            'module'      => 'crm',
            'column'      => 'visit',
            'content'     => serialize($content),
            'create_time' => time(),
            'update_time' => time()
        ]);
    }
}