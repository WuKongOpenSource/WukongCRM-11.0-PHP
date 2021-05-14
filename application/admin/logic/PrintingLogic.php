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
     * @param array $param : int $page 页码; int $limit 每页记录条数; string $type 打印模板类型
     * @author fanqi
     * @date 2021-03-26
     * @return array
     */
    public function index($param)
    {
        $page  = !empty($param['page'])  ? $param['page']             : 1;
        $limit = !empty($param['limit']) ? $param['limit']            : 500;
        $where = !empty($param['type'])  ? ['type' => $param['type']] : [];

        $result = [];
        $type   = [5 => '商机', 6 => '合同', 7 => '回款'];
        $field  = ['id', 'name', 'type', 'user_name', 'create_time', 'update_time'];
        $count  = Db::name('admin_printing')->where($where)->count();
        $data   = Db::name('admin_printing')
            ->field($field)
            ->where($where)
            ->order('id', 'desc')
            ->limit(($page - 1) * $limit, $limit)
            ->select();

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
            'content'     => json_encode(['data' => $param['content']]),
//            'content'     => htmlspecialchars($param['content']),
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

        $contentArray = json_decode($content, true);

        return ['id' => $id, 'content' => $contentArray['data']];
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
        if (!empty($param['content'])) $param['content'] = json_encode(['data' => $param['content']]);

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
     * @param $type 5商机；6合同；7回款
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFields($type)
    {
        $result = [];

        switch ($type) {
            case 5:
                $result['business'] = $this->getBusinessFields();
                $result['customer'] = $this->getCustomerFields(5);
                $result['product']  = $this->getProductFields(5);

                break;
            case 6:
                $result['contract'] = $this->getContractFields(6);
                $result['customer'] = $this->getCustomerFields(6);
                $result['contacts'] = $this->getContactsFields();
                $result['product']  = $this->getProductFields(6);

                break;
            case 7:
                $result['receivables'] = $this->getReceivablesFields(7);
                $result['contract']    = $this->getContractFields(7);

                break;
            default:
                $result[] = [];
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
    private function getCustomerFields($type)
    {
        $result = [];

        $customerList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_customer')->select();

        # 处理自定义字段
        foreach ($customerList AS $key => $value) {
            if (in_array($value['field'], ['next_time'])) continue;
            if (in_array($type, [5, 6]) && in_array($value['field'], ['deal_status'])) continue;

            $result[] = [
                'name'  => $value['name'],
                'field' => $value['field']
            ];
        }

        # 处理固定字段
        if (in_array($type, [5, 6])) {
            $result[] = ['name' => '详细地址', 'field' => 'address'];
            $result[] = ['name' => '区域', 'field' => 'detail_address'];
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
    private function getProductFields($type)
    {
        $result = [];

        $productList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_product')->select();

        # 处理自定义字段
        foreach ($productList AS $key => $value) {
            if (in_array($value['field'], ['status'])) continue;
            if (in_array($type, [5, 6]) && in_array($value['field'], ['description'])) continue;

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
        $result[] = ['name' => '产品总金额', 'field' => 'total_price'];

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
    private function getContractFields($type)
    {
        $result = [];

        $contractList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_contract')->select();

        # 处理自定义字段
        foreach ($contractList AS $key => $value) {
            if (in_array($type, [6, 7]) && in_array($value['field'], ['customer_id'])) continue;
            if ($type == 7 && in_array($value['field'], ['business_id'])) continue;

            $result[] = [
                'name'  => $value['name'],
                'field' => $value['field']
            ];
        }

        if (!in_array($type, [7])) {
            $result[] = ['name' => '负责人', 'field' => 'create_user_id'];
            $result[] = ['name' => '创建人', 'field' => 'owner_user_id'];
            $result[] = ['name' => '创建日期', 'field' => 'create_time'];
            $result[] = ['name' => '更新日期', 'field' => 'update_time'];
            $result[] = ['name' => '已收款金额', 'field' => 'done_money'];
            $result[] = ['name' => '未收款金额', 'field' => 'uncollected_money'];
        }

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
    private function getReceivablesFields($type)
    {
        $result = [];

        $receivablesList = Db::name('admin_field')->field(['name', 'field'])->where('types', 'crm_receivables')->select();

        # 处理自定义字段
        foreach ($receivablesList AS $key => $value) {
            if (in_array($value['field'], ['contract_id'])) continue;
            if (in_array($type, [7]) && in_array($value['field'], ['contract_id'])) continue;

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