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
    private $except = [
        'leads' => [
            'name', 'email', 'source', 'mobile', 'telephone', 'detail_address', 'industry', 'level', 'next_time',
            'remark', 'owner_user_id', 'last_record', 'create_user_id', 'create_time', 'update_time', 'last_time'
        ],
        'customer' => [
            'name', 'source', 'mobile', 'telephone', 'website', 'industry', 'level', 'next_time', 'remark', 'email',
            'owner_user_id', 'last_record', 'create_user_id', 'create_time', 'update_time', 'last_time', 'obtain_time',
            'deal_status', 'is_lock', 'pool_day'
        ],
        'contacts' => [
            'name', 'customer_id', 'mobile', 'telephone', 'email', 'post', 'decision', 'detail_address', 'next_time',
            'remark', 'sex', 'owner_user_id', 'create_user_id', 'create_time', 'update_time', 'last_time', 'last_record'
        ],
        'business' => [
            'name', 'customer_id', 'money', 'deal_date', 'remark', 'status_id', 'type_id', 'owner_user_id',
            'create_user_id', 'create_time', 'update_time', 'last_time', 'last_record'
        ],
        'contract' => [
            'name', 'num', 'customer_id', 'business_id', 'money', 'order_date', 'start_time', 'end_time', 'contacts_id',
            'order_user_id', 'remark', 'owner_user_id', 'create_user_id', 'create_time', 'update_time', 'last_time',
            'last_record', 'done_money', 'un_money', 'check_status'
        ],
        'receivables' => [
            'number', 'customer_id', 'contract_id', 'plan_id', 'return_time', 'money', 'return_type', 'remark',
            'owner_user_id', 'create_user_id', 'create_time', 'update_time', 'check_status'
        ],
        'product' => [
            'name', 'category_id', 'unit', 'num', 'price', 'description', 'status', 'owner_user_id', 'create_user_id',
            'create_time', 'update_time'
        ],
        'visit' => [
            'number', 'visit_time', 'owner_user_id', 'shape', 'customer_id', 'contacts_id', 'contract_id', 'satisfaction',
            'feedback', 'create_user_id', 'create_time', 'update_time'
        ]
    ];

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
        $this->createContractFieldGrant($roleId);
        # 添加回款字段授权数据
        $this->createReceivablesFieldGrant($roleId);
        # 添加产品字段授权信息
        $this->createProductFieldGrant($roleId);
        # 添加回访字段授权信息
        $this->createVisitFieldGrant($roleId);
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
            # 排除掉固定字段
            if (in_array($value['field'], $this->except[$typesArray[1]])) continue;

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
                    $content[$k]['name']  = $fieldData[$v['field']]['name'];
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

            Db::name('admin_field_grant')->where('grant_id', $value['grant_id'])->update(['content' => serialize(array_values($content))]);
        }

        return true;
    }

    /**
     * 处理线索字段授权数据
     *
     * @param int $roleId 角色ID
     * @author fanqi
     * @date 2021-03-22
     */
    private function createLeadsFieldGrant($roleId)
    {
        # 固定字段
        $content = [
            ['field' => 'name',           'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '线索名称'],
            ['field' => 'email',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '邮箱'],
            ['field' => 'source',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '线索来源'],
            ['field' => 'mobile',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '手机'],
            ['field' => 'telephone',      'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '电话'],
            ['field' => 'detail_address', 'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '地址'],
            ['field' => 'industry',       'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户行业'],
            ['field' => 'level',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户级别'],
            ['field' => 'next_time',      'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '下次联系时间'],
            ['field' => 'remark',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '备注'],
            ['field' => 'owner_user_id',  'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '负责人'],
            ['field' => 'last_record',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '最后跟进记录'],
            ['field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建人'],
            ['field' => 'create_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建时间'],
            ['field' => 'update_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '更新时间'],
            ['field' => 'last_time',      'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '最后跟进时间'],
        ];

        $leadsList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_leads')->select();

        # 自定义字段
        foreach ($leadsList AS $key => $value) {
            if (in_array($value['field'], $this->except['leads'])) continue;

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
     * 处理客户字段授权数据
     *
     * @param int $roleId 角色ID
     * @author fanqi
     * @date 2021-03-22
     */
    private function createCustomerFieldGrant($roleId)
    {
        # 固定字段
        $content = [
            ['field' => 'name',           'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户名称'],
            ['field' => 'source',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户来源'],
            ['field' => 'mobile',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '手机'],
            ['field' => 'telephone',      'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '电话'],
            ['field' => 'website',        'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '网址'],
            ['field' => 'industry',       'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户行业'],
            ['field' => 'level',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户级别'],
            ['field' => 'next_time',      'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '下次联系时间'],
            ['field' => 'remark',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '备注'],
            ['field' => 'email',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '邮箱'],
            ['field' => 'owner_user_id',  'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '负责人'],
            ['field' => 'last_record',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '最后跟进记录'],
            ['field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建人'],
            ['field' => 'create_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建时间'],
            ['field' => 'update_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '更新时间'],
            ['field' => 'last_time',      'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '最后跟进时间'],
            ['field' => 'obtain_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '负责人获取客户时间'],
            ['field' => 'deal_status',    'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '成交状态'],
            ['field' => 'is_lock',        'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '锁定状态'],
            ['field' => 'pool_day',       'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '距进入公海天数'],
        ];

        $customerList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_customer')->select();

        # 自定义字段
        foreach ($customerList AS $key => $value) {
            if (in_array($value['field'], $this->except['customer'])) continue;

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
     * 处理联系人字段授权数据
     *
     * @param int $roleId 角色ID
     * @author fanqi
     * @date 2021-03-22
     */
    private function createContactsFieldGrant($roleId)
    {
        # 固定字段
        $content = [
            ['field' => 'name',           'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '姓名'],
            ['field' => 'customer_id',    'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '客户名称'],
            ['field' => 'mobile',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '手机'],
            ['field' => 'telephone',      'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '电话'],
            ['field' => 'email',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '邮箱'],
            ['field' => 'post',           'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '职务'],
            ['field' => 'decision',       'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '是否关键决策人'],
            ['field' => 'detail_address', 'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '地址'],
            ['field' => 'next_time',      'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '下次联系时间'],
            ['field' => 'remark',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '备注'],
            ['field' => 'sex',            'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '性别'],
            ['field' => 'owner_user_id',  'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '负责人'],
            ['field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建人'],
            ['field' => 'create_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建时间'],
            ['field' => 'update_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '更新时间'],
            ['field' => 'last_time',      'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '最后跟进时间'],
        ];

        $contactsList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_contacts')->select();

        # 自定义字段
        foreach ($contactsList AS $key => $value) {
            if (in_array($value['field'], $this->except['contacts'])) continue;

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
     * 处理商机字段授权数据
     *
     * @param int $roleId 角色ID
     * @author fanqi
     * @date 2021-03-22
     */
    private function createBusinessFieldGrant($roleId)
    {
        # 固定字段
        $content = [
            ['field' => 'name',           'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '商机名称'],
            ['field' => 'customer_id',    'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '客户名称'],
            ['field' => 'money',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '商机金额'],
            ['field' => 'deal_date',      'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '预计成交日期'],
            ['field' => 'remark',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '备注'],
            ['field' => 'status_id',      'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '商机阶段'],
            ['field' => 'type_id',        'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '商机状态组'],
            ['field' => 'owner_user_id',  'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '负责人'],
            ['field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建人'],
            ['field' => 'create_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建时间'],
            ['field' => 'update_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '更新时间'],
            ['field' => 'last_time',      'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '最后跟进时间'],
        ];

        $BusinessList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_business')->select();

        # 自定义字段
        foreach ($BusinessList AS $key => $value) {
            if (in_array($value['field'], $this->except['business'])) continue;

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
     * 处理合同字段授权数据
     *
     * @param int $roleId 角色ID
     * @author fanqi
     * @date 2021-03-22
     */
    private function createContractFieldGrant($roleId)
    {
        # 固定字段
        $content = [
            ['field' => 'name',           'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '合同名称'],
            ['field' => 'num',            'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '合同编号'],
            ['field' => 'customer_id',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户名称'],
            ['field' => 'business_id',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '商机名称'],
            ['field' => 'money',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '合同金额'],
            ['field' => 'order_date',     'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '下单时间'],
            ['field' => 'start_time',     'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '合同开始时间'],
            ['field' => 'end_time',       'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '合同结束时间'],
            ['field' => 'contacts_id',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户签约人'],
            ['field' => 'order_user_id',  'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '公司签约人'],
            ['field' => 'remark',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '备注'],
            ['field' => 'owner_user_id',  'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '负责人'],
            ['field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建人'],
            ['field' => 'create_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建时间'],
            ['field' => 'update_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '更新时间'],
            ['field' => 'last_time',      'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '最后跟进时间'],
            ['field' => 'last_record',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '最后跟进记录'],
            ['field' => 'done_money',     'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '已收款金额'],
            ['field' => 'un_money',       'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '未收款金额'],
            ['field' => 'check_status',   'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '审核状态'],
        ];

        $contractList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_contract')->select();

        # 自定义字段
        foreach ($contractList AS $key => $value) {
            if (in_array($value['field'], $this->except['contract'])) continue;

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
     * 处理回款字段授权数据
     *
     * @param int $roleId 角色ID
     * @author fanqi
     * @date 2021-03-22
     */
    private function createReceivablesFieldGrant($roleId)
    {
        # 固定字段
        $content = [
            ['field' => 'number',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '回款编号'],
            ['field' => 'customer_id',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户名称'],
            ['field' => 'contract_id',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '合同编号'],
            ['field' => 'plan_id',        'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '期数'],
            ['field' => 'return_time',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '回款日期'],
            ['field' => 'money',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '回款金额'],
            ['field' => 'return_type',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '回款方式'],
            ['field' => 'remark',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '备注'],
            ['field' => 'contract_money', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '合同金额'],
            ['field' => 'owner_user_id',  'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '负责人'],
            ['field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建人'],
            ['field' => 'create_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建时间'],
            ['field' => 'update_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '更新时间'],
            ['field' => 'check_status',   'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '审核状态'],
        ];

        $receivablesList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_receivables')->select();

        # 自定义字段
        foreach ($receivablesList AS $key => $value) {
            if (in_array($value['field'], $this->except['receivables'])) continue;

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
     * 处理产品字段授权信息
     *
     * @param int $roleId 角色ID
     * @author fanqi
     * @date 2021-03-22
     */
    private function createProductFieldGrant($roleId)
    {
        # 固定字段
        $content = [
            ['field' => 'name',           'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '产品名称'],
            ['field' => 'category_id',    'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '产品类型'],
            ['field' => 'unit',           'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '产品单位'],
            ['field' => 'num',            'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '产品编码'],
            ['field' => 'price',          'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '价格'],
            ['field' => 'description',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '产品描述'],
            ['field' => 'status',         'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '是否上下架'],
            ['field' => 'owner_user_id',  'read' => 1, 'read_operation' => 0, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '负责人'],
            ['field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建人'],
            ['field' => 'create_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建时间'],
            ['field' => 'update_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '更新时间'],
        ];

        $productList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_product')->select();

        # 自定义字段
        foreach ($productList AS $key => $value) {
            if (in_array($value['field'], $this->except['product'])) continue;

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
     * 处理回访字段授权信息
     *
     * @param int $roleId
     * @author fanqi
     * @date 2021-03-22
     */
    private function createVisitFieldGrant($roleId)
    {
        $content = [
            ['field' => 'number',         'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '回访编号'],
            ['field' => 'visit_time',     'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '回访时间'],
            ['field' => 'owner_user_id',  'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '回访人'],
            ['field' => 'shape',          'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '回访形式'],
            ['field' => 'customer_id',    'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '客户名称'],
            ['field' => 'contacts_id',    'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '联系人'],
            ['field' => 'contract_id',    'read' => 1, 'read_operation' => 0, 'write' => 1, 'write_operation' => 0, 'is_diy' => 0, 'name' => '合同编号'],
            ['field' => 'satisfaction',   'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户满意度'],
            ['field' => 'feedback',       'read' => 1, 'read_operation' => 1, 'write' => 1, 'write_operation' => 1, 'is_diy' => 0, 'name' => '客户反馈'],
            ['field' => 'create_user_id', 'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建人'],
            ['field' => 'create_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '创建时间'],
            ['field' => 'update_time',    'read' => 1, 'read_operation' => 1, 'write' => 0, 'write_operation' => 0, 'is_diy' => 0, 'name' => '更新时间'],
        ];

        $visitList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_visit')->select();

        # 处理自定义字段
        foreach ($visitList AS $key => $value) {
            if (in_array($value['field'], $this->except['visit'])) continue;

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