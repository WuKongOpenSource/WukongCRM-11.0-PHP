<?php
/**
 * 打印设置逻辑类
 *
 * @author qifan
 * @date 2020-12-03
 */

namespace app\admin\logic;

use app\admin\controller\ApiCommon;
use think\Db;

class PrintingLogic
{
    /**
     * 打印模板列表
     *
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($page, $limit)
    {
        $result = [];
        $type   = [1 => '商机', 2 => '合同', 3 => '回款'];
        $field  = ['id', 'name', 'type', 'user_name', 'create_time', 'update_time'];
        $count  = Db::name('admin_printing')->count();
        $data   = Db::name('admin_printing')->field($field)->order('id', 'desc')->limit(($page - 1) * $limit, $limit)->select();

        foreach ($data AS $key => $value) {
            $result[] = [
                'id'          => $value['id'],
                'name'        => $value['name'],
                'type'        => $value['type'],
                'type_name'   => !empty($type[$value['type']]) ? $type[$value['type']] : '',
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'user_name'   => $value['user_name'],
                'update_time' => date('Y-m-d H:i:s', $value['update_time'])
            ];
        }

        return ['count' => $count, 'list' => $result];
    }

    /**
     * 创建打印模板
     *
     * @param $param
     * @return int|string
     */
    public function create($param)
    {
        $apiCommon = new ApiCommon();
        $userId   = $apiCommon->userInfo['id'];
        $userName = Db::name('admin_user')->where('id', $userId)->value('realname');

        $data = [
            'user_id'     => $userId,
            'user_name'   => $userName,
            'name'        => $param['name'],
            'type'        => $param['type'],
            'content'     => htmlspecialchars($param['content']),
            'create_time' => time(),
            'update_time' => time()
        ];

        return Db::name('admin_printing')->insert($data);
    }

    /**
     * 获取模板详情
     *
     * @param $id
     * @return array
     */
    public function read($id)
    {
        $content = Db::name('admin_printing')->where('id', $id)->value('content');

        return ['id' => $id, 'content' => htmlspecialchars_decode($content)];
    }

    /**
     * 更新模板数据
     *
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update($param)
    {
        if (!empty($param['content'])) $param['content'] = htmlspecialchars($param['content']);

        return Db::name('admin_printing')->update($param);
    }

    /**
     * 删除模板数据
     *
     * @param $id
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($id)
    {
        return Db::name('admin_printing')->where('id', $id)->delete();
    }

    /**
     * 复制模板数据
     *
     * @param $id
     * @return false|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function copy($id)
    {
        $apiCommon = new ApiCommon();
        $info = Db::name('admin_printing')->where('id', $id)->find();

        if (!empty($info['id'])) {
            $userId   = $apiCommon->userInfo['id'];
            $userName = Db::name('admin_user')->where('id', $userId)->value('realname');

            $data = [
                'user_id'     => $userId,
                'user_name'   => $userName,
                'name'        => strlen($info['name']) > 25 ? $info['name'] : $info['name'] . rand(111, 999),
                'type'        => $info['type'],
                'content'     => $info['content'],
                'update_time' => time(),
                'create_time' => time()
            ];

            return Db::name('admin_printing')->insert($data);
        }

        return false;
    }

    /**
     * 获取打印模板需要的字段
     *
     * @param $type 1商机；2合同；3回款
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFields($type)
    {
        $result = [];

        switch ($type) {
            case 1:
                $result['business'] = $this->getBusinessFields();
                $result['customer'] = $this->getCustomerFields();
                $result['product']  = $this->getProductFields();

                break;
            case 2:
                $result['contract'] = $this->getContractFields();
                $result['customer'] = $this->getCustomerFields();
                $result['contacts'] = $this->getContactsFields();
                $result['product']  = $this->getProductFields();

                break;
            case 3:
                $result['receivables'] = $this->getReceivablesFields();
                $result['contract']    = $this->getContractFields();

                break;
            default:
                $result['business']    = $this->getBusinessFields();
                $result['customer']    = $this->getCustomerFields();
                $result['product']     = $this->getProductFields();
                $result['contract']    = $this->getContractFields();
                $result['contacts']    = $this->getContactsFields();
                $result['receivables'] = $this->getReceivablesFields();
        }

        return $result;
    }

    /**
     * 获取商机字段
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getBusinessFields()
    {
        $result = [];

        $businessList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_business')->select();

        # 处理自定义字段
        foreach ($businessList AS $key => $value) {
            if ($value['field'] == 'customer_id') continue;

            $result[] = [
                'name'  => $value['name'],
                'field' => $value['field']
            ];
        }

        # 处理固定字段
        $result[] = ['name' => '负责人', 'field' => 'owner_user_id'];
        $result[] = ['name' => '创建人', 'field' => 'create_user_id'];
        $result[] = ['name' => '创建日期', 'field' => 'create_time'];
        $result[] = ['name' => '更新日期', 'field' => 'update_time'];

        return $result;
    }

    /**
     * 获取客户字段
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getCustomerFields()
    {
        $result = [];

        $customerList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_customer')->select();

        # 处理自定义字段
        foreach ($customerList AS $key => $value) {
            if (in_array($value['field'], ['next_time', 'remark'])) continue;

            $result[] = [
                'name'  => $value['name'],
                'field' => $value['field']
            ];
        }

        return $result;
    }

    /**
     * 获取产品字段
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getProductFields()
    {
        $result = [];

        $productList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_product')->select();

        # 处理自定义字段
        foreach ($productList AS $key => $value) {
            if ($value['field'] == 'status') continue;

            $result[] = [
                'name'  => $value['name'],
                'field' => $value['field']
            ];
        }

        # 处理固定字段
        $result[] = ['name' => '售价', 'field' => 'sales_price'];
        $result[] = ['name' => '数量', 'field' => 'count'];
        $result[] = ['name' => '折扣', 'field' => 'discount'];
        $result[] = ['name' => '整单折扣', 'field' => 'discount_rate'];
        $result[] = ['name' => '合计', 'field' => 'subtotal'];

        return $result;
    }

    /**
     * 获取合同字段
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getContractFields()
    {
        $result = [];

        $contractList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_contract')->select();

        # 处理自定义字段
        foreach ($contractList AS $key => $value) {
            $result[] = [
                'name'  => $value['name'],
                'field' => $value['field']
            ];
        }

        # 处理固定字段
        $result[] = ['name' => '负责人', 'field' => 'owner_user_id'];
        $result[] = ['name' => '创建人', 'field' => 'create_user_id'];
        $result[] = ['name' => '创建日期', 'field' => 'create_time'];
        $result[] = ['name' => '更新日期', 'field' => 'update_time'];
        $result[] = ['name' => '已收款金额', 'field' => 'received'];
        $result[] = ['name' => '未收款金额', 'field' => 'uncollected'];

        return $result;
    }

    /**
     * 获取联系人字段
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getContactsFields()
    {
        $result = [];

        $contactsList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_contacts')->select();

        # 处理自定义字段
        foreach ($contactsList AS $key => $value) {
            if ($value['field'] == 'next_time') continue;

            $result[] = [
                'name'  => $value['name'],
                'field' => $value['field']
            ];
        }

        return $result;
    }

    /**
     * 获取回款字段
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getReceivablesFields()
    {
        $result = [];

        $receivablesList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_receivables')->select();

        # 处理自定义字段
        foreach ($receivablesList AS $key => $value) {
            $result[] = [
                'name'  => $value['name'],
                'field' => $value['field']
            ];
        }

        # 处理固定字段
        $result[] = ['name' => '负责人', 'field' => 'owner_user_id'];
        $result[] = ['name' => '创建人', 'field' => 'create_user_id'];
        $result[] = ['name' => '创建日期', 'field' => 'create_time'];
        $result[] = ['name' => '更新日期', 'field' => 'update_time'];

        return $result;
    }
}