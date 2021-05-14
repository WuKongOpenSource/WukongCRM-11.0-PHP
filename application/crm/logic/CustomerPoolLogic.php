<?php
/**
 * 客户公海逻辑类
 *
 * @author fanqi
 * @since 2021-04-13
 */

namespace app\crm\logic;

use app\admin\model\ActionRecord;
use app\admin\model\Common;
use app\admin\model\Field;
use app\admin\model\File;
use app\admin\model\Record;
use app\crm\model\CustomerConfig;
use PDOStatement;
use think\Collection;
use think\Db;
use think\Model;

class CustomerPoolLogic extends Common
{
    /**
     * 公海列表
     *
     * @param $param
     * @author fanqi
     * @since 2021-04-14
     * @return array
     */
    public function getPoolList($param)
    {
        $result = ['dataCount' => 0, 'list' => []];

        $fieldModel = new Field();

        $poolId = $param['pool_id'];
        $orderField = $param['order_field'];
        $orderType = $param['order_type'];
      
        # 基础条件
        $where['relation.pool_id'] = $poolId;

        # 普通搜索
        $searchMap = [];
        if ($param['search'] == '0' || !empty($param['search'])) {
            $search = $param['search'];
            $searchMap = function($query) use ($search) {
                $query->where('customer.name',array('like','%'.$search.'%'))
                    ->whereOr('customer.mobile',array('like','%'.$search.'%'))
                    ->whereOr('customer.telephone',array('like','%'.$search.'%'));
            };
        }
      
        # 处理排序参数
        if (!empty($orderField)) {
            if ($orderField == 'create_user_id_name') $orderField = 'create_user_id';
            if ($orderField == 'before_owner_user_name') $orderField = 'before_owner_user_id';
        }
        # 公海条件
        if ($param['is_excel'] == 1 && !empty($param['customer_id'])) {
            $authMap['customer.customer_id'] = ['in', trim(arrayToString($param['customer_id']),',')];
        }
        # 排序
        if (!empty($orderField) && !empty($orderType)) {
            $order = $fieldModel->getOrderByFormtype('crm_customer','customer', $orderField, $orderType);
        } else {
            $order = 'customer.update_time desc';
        }
        # 删除参数
        unset($param['pool_id']);
        unset($param['search']);
        unset($param['order_field']);
        unset($param['order_type']);
        unset($param['is_excel']);
        unset($param['customer_id']);
        # 格式化参数
        $request = $this->fmtRequest( $param );
        $requestMap = !empty($request['map']) ? $request['map'] : [];
        
        # 高级搜索
        $map = where_arr($requestMap, 'crm', 'customer', 'index');
        # 公海字段
        $customerFieldString = $this->getPoolQueryField($poolId);
        # 公海数据
        $customerPoolCount = db('crm_customer_pool_relation')->alias('relation')
            ->join('__CRM_CUSTOMER__ customer', 'customer.customer_id = relation.customer_id', 'LEFT')
            ->where($where)->where($searchMap)->where($map)->where($authMap)->count();
        if (empty($customerPoolCount)) return $result;
      
        $customerPoolList = db('crm_customer_pool_relation')->alias('relation')->field($customerFieldString)
            ->join('__CRM_CUSTOMER__ customer', 'customer.customer_id = relation.customer_id', 'LEFT')
            ->limit($request['offset'], $request['length'])->where($where)->where($searchMap)->where($map)->where($authMap)->orderRaw($order)->select();

        # 员工列表
        $userData = $this->getUserList();

        # 部门列表
        $structureData = $this->getStructureList();

        # 特殊字段
        $userField = $fieldModel->getFieldByFormType('crm_customer', 'user'); # 人员类型
        $structureField = $fieldModel->getFieldByFormType('crm_customer', 'structure'); # 部门类型
        $datetimeField = $fieldModel->getFieldByFormType('crm_customer', 'datetime'); # 日期时间类型

        # 整理公海数据
        foreach ($customerPoolList AS $key => $value) {
            $customerPoolList[$key]['create_user_name'] = !empty($userData[$value['create_user_id']]) ? $userData[$value['create_user_id']] : '';
            $customerPoolList[$key]['before_owner_user_name'] = !empty($userData[$value['before_owner_user_id']]) ? $userData[$value['before_owner_user_id']] : '';

            $customerPoolList[$key]['next_time'] = !empty($value['next_time']) ? date('Y-m-d H:i:s', $value['next_time']) : null;
            $customerPoolList[$key]['update_time'] = !empty($value['update_time']) ? date('Y-m-d H:i:s', $value['update_time']) : null;
            $customerPoolList[$key]['create_time'] = !empty($value['create_time']) ? date('Y-m-d H:i:s', $value['create_time']) : null;
            $customerPoolList[$key]['last_time'] = !empty($value['last_time']) ? date('Y-m-d H:i:s', $value['last_time']) : null;
            $customerPoolList[$key]['into_pool_time'] = !empty($value['into_pool_time']) ? date('Y-m-d H:i:s', $value['into_pool_time']) : null;

            # 处理日期时间类型的自定义字段
            foreach ($datetimeField AS $k => $v) {
                if (isset($datetimeField[$key][$v])) {
                    $datetimeField[$key][$v] = !empty($value[$v]) ? date('Y-m-d H:i:s', $value[$v]) : null;
                }
            }
            # 处理人员类型的自定义字段
            foreach ($userField AS $k => $v) {
                if (isset($datetimeField[$key][$v]) && !empty($value[$v])) {
                    $datetimeField[$key][$v] = $this->fieldTransformToText($value[$v], $userData);
                }
            }
            # 处理部门类型的自定义字段
            foreach ($structureField AS $k => $v) {
                if (isset($datetimeField[$key][$v]) && !empty($value[$v])) {
                    $datetimeField[$key][$v] = $this->fieldTransformToText($value[$v], $structureData);
                }
            }
        }

        $result['dataCount'] = $customerPoolCount;
        $result['list'] = $customerPoolList;

        return $result;
    }

    /**
     * 公海详情
     *
     * @param array $param pool_id 公海ID，customer_id 客户ID
     * @author fanqi
     * @since 2021-04-14
     * @return array|bool|PDOStatement|string|Model|null
     */
    public function getPoolData($param)
    {
        # 更新用户自定义字段样式
        $this->updateFieldStyle($param);

        # 公海字段
        $fields = $this->getPoolQueryField($param['pool_id']);

        $data = db('crm_customer')->alias('customer')->field($fields)->where('customer_id', $param['customer_id'])->find();

        $fieldModel = new Field();

        # 员工列表
        $userData = $this->getUserList();

        # 部门列表
        $structureData = $this->getStructureList();

        # 特殊字段
        $userField = $fieldModel->getFieldByFormType('crm_customer', 'user'); # 人员类型
        $structureField = $fieldModel->getFieldByFormType('crm_customer', 'structure'); # 部门类型
        $datetimeField = $fieldModel->getFieldByFormType('crm_customer', 'datetime'); # 日期时间类型

        # 整理公海数据
        $data['create_user_name'] = !empty($userData[$data['create_user_id']]) ? $userData[$data['create_user_id']] : '';
        $data['before_owner_user_name'] = !empty($userData[$data['before_owner_user_id']]) ? $userData[$data['before_owner_user_id']] : '';

        $data['next_time'] = !empty($data['next_time']) ? date('Y-m-d H:i:s', $data['next_time']) : null;
        $data['update_time'] = !empty($data['update_time']) ? date('Y-m-d H:i:s', $data['update_time']) : null;
        $data['create_time'] = !empty($data['create_time']) ? date('Y-m-d H:i:s', $data['create_time']) : null;
        $data['last_time'] = !empty($data['last_time']) ? date('Y-m-d H:i:s', $data['last_time']) : null;
        $data['into_pool_time'] = !empty($data['into_pool_time']) ? date('Y-m-d H:i:s', $data['into_pool_time']) : null;

        # 处理日期时间类型的自定义字段
        foreach ($datetimeField AS $k => $v) {
            if (isset($data[$v])) {
                $data[$v] = !empty($data[$v]) ? date('Y-m-d H:i:s', $data[$v]) : null;
            }
        }
        # 处理人员类型的自定义字段
        foreach ($userField AS $k => $v) {
            if (isset($data[$v]) && !empty($data[$v])) {
                $data[$v] = $this->fieldTransformToText($data[$v], $userData);
            }
        }
        # 处理部门类型的自定义字段
        foreach ($structureField AS $k => $v) {
            if (isset($data[$v]) && !empty($data[$v])) {
                $data[$v] = $this->fieldTransformToText($data[$v], $structureData);
            }
        }

        return $data;
    }

    /**
     * @param array $param
     * @return array
     */
    public function deletePoolCustomer($param)
    {
        if (empty($param['user_id'])) return ['缺少用户ID！'];

        # 消息数据
        $message = [];

        $customerId = $param['id'];

        # 查询客户列表数据
        $customerData = $this->getCustomerList($customerId);

        # 验证是否是公海数据
        foreach ($customerId AS $key => $value) {
            if (empty($customerData[$value])) {
                $message[] = '删除 《' . $customerData[$value]['name'] . '》 失败，原因：公海客户不存在！';

                unset($customerId[(int)$key]);
            }

            if (!empty($customerData[$value]['owner_user_id'])) {
                $message[] = '删除 《' . $customerData[$value]['name'] . '》 失败，原因：不是公海客户！';

                unset($customerId[(int)$key]);
            }
        }

        # 验证是否还有需要删除的客户
        if (empty($customerId)) return $message;

        # 查询与客户有关的数据
        $customerContactsData = db('crm_contacts')->whereIn('customer_id', $customerId)->column('customer_id');
        $customerBusinessData = db('crm_business')->whereIn('customer_id', $customerId)->column('customer_id');
        $customerContractData = db('crm_contract')->whereIn('customer_id', $customerId)->column('customer_id');

        # 验证客户下是否存在联系人、商机、合同
        foreach ($customerId AS $key => $value) {
            if (in_array($value, $customerContactsData)) {
                $message[] = '删除 《' . $customerData[$value]['name'] . '》 失败，原因：客户下存在联系人！';

                unset($customerId[(int)$key]);
            }
            if (in_array($value, $customerBusinessData)) {
                $message[] = '删除 《' . $customerData[$value]['name'] . '》 失败，原因：客户下存在商机！';

                unset($customerId[(int)$key]);
            }
            if (in_array($value, $customerContractData)) {
                $message[] = '删除 《' . $customerData[$value]['name'] . '》 失败，原因：客户下存在合同！';

                unset($customerId[(int)$key]);
            }
        }

        # 删除客户
        if (!empty($customerId)) {
            if (db('crm_customer')->whereIn('customer_id', $customerId)->delete()) {
                # 删除公海关联数据
                db('crm_customer_pool_relation')->whereIn('customer_id', $customerId)->delete();
                # 删除跟进记录
                (new Record())->delDataByTypes(2, $customerId);
                # 删除关联附件
                (new File())->delRFileByModule('crm_customer', $customerId);
                # 删除关联操作记录
                (new ActionRecord())->delDataById(['types' => 'crm_customer', 'action_id' => $customerId]);
                # 记录到数据操作日志
                $ip = request()->ip();
                $addOperationLogData = [];
                foreach ($customerId AS $key => $value) {
                    $addOperationLogData[] = [
                        'user_id'     => $param['user_id'],
                        'client_ip'   => $ip,
                        'module'      => 'crm_customer',
                        'action_id'   => $value,
                        'content'     => '删除了客户：' . $customerData[$value]['name'],
                        'create_time' => time(),
                        'action_name' => 'delete',
                        'target_name' => $customerData[$value]['name']
                    ];
                }
                db('admin_operation_log')->insertAll($addOperationLogData);
            } else {
                $message[] = '删除客户失败，请刷新后重试！';
            }
        }

        return $message;
    }

    /**
     * 获取公海池列表
     *
     * @param array $param 查询参数：user_id 用户id，structure_id 部门id
     * @author fanqi
     * @since 2021-04-21
     * @return bool|PDOStatement|string|Collection
     */
    public function getPondList($param)
    {
        return db('crm_customer_pool')->field(['pool_id', 'pool_name'])
            ->where('status', 1)
            ->where(function ($query) use ($param) {
                $query->where('admin_user_ids', 'like', '%,' . $param['user_id'] . ',%');
                $query->whereOr('user_ids', 'like', '%,' . $param['user_id'] . ',%');
                $query->whereOr('department_ids', '%,' . $param['structure_id'] . ',%');
            })->select();
    }

    /**
     * 获取公海字段
     *
     * @param array $param pool_id 公海ID，action 操作类型，action_id 数据id
     * @author fanqi
     * @since 2021-04-13
     * @return array
     */
    public function getFieldList($param)
    {
        $data = [];

        # 自定义字段
        $list = db('crm_customer_pool_field_setting')->where('pool_id', $param['pool_id'])->where('is_hidden', 0)->select();

        # 处理公海字段
        foreach ($list AS $key => $value) {
            $list[$key]['field'] = $value['field_name'];

            # 处理别名
            switch ($value['field_name']) {
                case 'create_user_id' :
                    $list[$key]['fieldName'] = 'create_user_name';
                    break;
                case 'before_owner_user_id' :
                    $list[$key]['fieldName'] = 'before_owner_user_name';
                    break;
                default :
                    $list[$key]['fieldName'] = $value['field_name'];
            }
            if (in_array($value['form_type'], ['user', 'structure']) && !in_array($value['field_name'], ['create_user_id', 'owner_user_id', 'before_owner_user_id'])) {
                $list[$key]['fieldName'] = $value['field_name'] . '_name';
            }

            if (in_array($value['field_name'], ['last_record', 'create_user_id', 'create_time', 'update_time', 'last_time', 'deal_status'])) {
                $list[$key]['system'] = 1;
            } else {
                $list[$key]['system'] = 0;
            }

            $data[$list[$key]['field']] = [
                'field'     => $list[$key]['field'],
                'fieldName' => $list[$key]['fieldName'],
                'name'      => $list[$key]['name'],
                'width'     => '',
                'is_hidden' => 1,
                'system'    => $list[$key]['system'],
                'form_type' => $list[$key]['form_type']
            ];
        }

        # 公海字段样式
        $list = $this->setFieldStyle($param, $data);

        return array_values((array)$list);
    }

    /**
     * 高级筛选字段列表
     *
     * @author fanqi
     * @since 2021-04-14
     * @return array
     */
    public function getAdvancedFilterFieldList()
    {
        # 基础字段
        $base = [
            ['field' => 'address', 'name' => '省、市、区/县', 'form_type' => 'address', 'setting' => ''],
            ['field' => 'detail_address', 'name' => '详细地址', 'form_type' => 'text', 'setting' => ''],
            ['field' => 'last_record', 'name' => '最后跟进记录', 'form_type' => 'text', 'setting' => ''],
            ['field' => 'last_time', 'name' => '最后跟进时间', 'form_type' => 'datetime', 'setting' => ''],
            ['field' => 'create_time', 'name' => '创建时间', 'form_type' => 'datetime', 'setting' => ''],
            ['field' => 'update_time', 'name' => '更新时间', 'form_type' => 'datetime', 'setting' => ''],
            ['field' => 'create_user_id', 'name' => '创建人', 'form_type' => 'user', 'setting' => ''],
            ['field' => 'before_owner_user_id', 'name' => '前负责人', 'form_type' => 'user', 'setting' => ''],
            ['field' => 'into_pool_time', 'name' => '进入公海时间', 'form_type' => 'datetime', 'setting' => ''],
        ];
        # 自定义字段
        $list = db('admin_field')->field(['field', 'name', 'form_type', 'setting'])->where('types', 'crm_customer')->select();
        $list = array_merge($list, $base);

        # 整理数据
        foreach ($list AS $key => $value) {
            if (!empty($value['setting'])) $list[$key]['setting'] = explode(chr(10), $value['setting']);
        }

        return $list;
    }

    /**
     * 领取公海客户
     *
     * @param array $param user_id 领取人ID，customer_id 要领取的客户ID
     * @author fanqi
     * @since 2021-04-15
     * @return array
     */
    public function receiveCustomers($param)
    {
        if (empty($param['user_id'])) return ['缺少员工ID'];

        # 查询参数
        $userId     = $param['user_id'];
        $customerId = $param['customer_id'];

        # 消息数据
        $message = [];

        # 查询客户列表数据
        $customerData = $this->getCustomerList($customerId);

        # 剔除非公海客户
        foreach ($customerId AS $key => $value) {
            if (!empty($customerData[$value]['owner_user_id'])) {
                $message[] = '客户《' . $customerData[$value]['name'] . '》领取失败，失败原因：不是公海客户！';

                unset($customerId[(int)$key]);
                continue;
            }
        }

        # 检查是否还有要领取的客户
        if (empty($customerId)) return $message;

        # 获取超出持有客户数的数量
        $customerConfigModel = new CustomerConfig();
        $exceedCount = $customerConfigModel->checkData($userId, 1, 0, count($customerId));

        # 记录添加失败的客户
        $failCustomer = [];
        if (!is_bool($exceedCount) && !empty($exceedCount) && $exceedCount > 0) {
            $failCustomer = array_slice($customerId, count($customerId) - $exceedCount);
            foreach ($failCustomer AS $key => $value) {
                $message[] = '客户《' . $customerData[$value]['name'] . '》领取失败，失败原因：持有客户数达到上限！';
            }
        }

        # 可以领取的客户ID，取差集
        $addCustomerId = count($customerId) == 1 ? $customerId : array_diff($customerId, $failCustomer);

        # 检查是否还有要领取的客户
        if (empty($addCustomerId)) return $message;

        # 公海配置
        $poolConfig = db('crm_customer_pool')->field(['before_owner_conf', 'before_owner_day', 'receive_conf', 'receive_count'])->where('pool_id', $param['pool_id'])->find();

        # 前负责人N天内不能领取客户
        if (!empty($poolConfig['before_owner_conf'])) {
            foreach ($addCustomerId AS $key => $value) {
                # 是前负责人，检查前负责人是否能够领取。
                if ($userId == $customerData[$value]['before_owner_user_id']) {
                    $restrictDay = $customerData[$value]['into_pool_time'] + 86400 * $poolConfig['before_owner_day'];
                    if (time() < $restrictDay) {
                        $message[] = '客户《' . $customerData[$value]['name'] . '》领取失败，失败原因：进入公海后，'.$poolConfig['before_owner_day'].'天内不能领取！';

                        unset($addCustomerId[(int)$key]);
                    }
                }
            }
        }

        # 检查是否还有要领取的客户
        if (empty($addCustomerId)) return $message;

        # 检查每天领取的个数限制
        $countWhere['type'] = 1;
        $countWhere['pool_id'] = $param['pool_id'];
        $countWhere['user_id'] = $userId;
        $countWhere['create_time'] = ['between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))]];
        $receiveCount = db('crm_customer_pool_record')->where($countWhere)->count();
        if (!empty($poolConfig['receive_conf']) && $receiveCount + count($addCustomerId) > $poolConfig['receive_count']) {
            $overQuantity = ($receiveCount + count($addCustomerId)) - $poolConfig['receive_count'];
            $message[] = '领取客户失败，失败原因：超出当日可领取数量，超出'.$overQuantity.'个！';
            return $message;
        }

        # 整理客户更新数据
        $addCustomerData = [
            'owner_user_id'        => $userId,
            'before_owner_user_id' => 0,
            'into_pool_time'       => 0,
            'obtain_time'          => time()
        ];

        # 整理字段操作记录和数据日志的数据
        $ip = request()->ip();
        $addActionRecordData = [];
        $addOperationLogData = [];
        $addReceiveData      = [];
        foreach ($addCustomerId AS $key => $value) {
            $addActionRecordData[] = [
                'user_id'     => $userId,
                'types'       => 'crm_customer',
                'action_id'   => $value,
                'content'     => '领取了客户',
                'create_time' => time()
            ];
            $addOperationLogData[] = [
                'user_id'     => $userId,
                'client_ip'   => $ip,
                'module'      => 'crm_customer',
                'action_id'   => $value,
                'content'     => '领取了客户',
                'create_time' => time(),
                'action_name' => 'update',
                'target_name' => $customerData[$value]['name']
            ];
            $addReceiveData[] = [
                'customer_id' => $value,
                'user_id'     => $userId,
                'pool_id'     => $param['pool_id'],
                'type'        => 1,
                'create_time' => time()
            ];
        }

        Db::startTrans();
        try {
            # 领取客户
            Db::name('crm_customer')->whereIn('customer_id', $addCustomerId)->update($addCustomerData);

            # 设置客户的联系人数据
            Db::name('crm_contacts')->whereIn('customer_id', $addCustomerId)->update(['owner_user_id' => $userId]);

            # 删除公海与客户关联数据
            Db::name('crm_customer_pool_relation')->whereIn('customer_id', $addCustomerId)->delete();

            # 字段操作日志
            Db::name('admin_action_record')->insertAll($addActionRecordData);

            # 数据操作日志
            Db::name('admin_operation_log')->insertAll($addOperationLogData);

            # 记录领取的客户
            Db::name('crm_customer_pool_record')->insertAll($addReceiveData);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            $message = ['领取失败，刷新后重试！'];
        }

        return $message;
    }

    /**
     * 分配客户
     *
     * @param array $param user_id 员工ID，customer_id 客户ID
     * @author fanqi
     * @since 2021-04-15
     * @return array
     */
    public function distributeCustomer($param)
    {
        # 查询参数
        $userId     = $param['user_id'];
        $customerId = $param['customer_id'];
        $username   = db('admin_user')->where('id', $userId)->value('realname');

        # 消息数据
        $message = [];

        # 查询客户列表数据
        $customerData = $this->getCustomerList($customerId);

        # 剔除非公海客户
        foreach ($customerId AS $key => $value) {
            if (!empty($customerData[$value]['owner_user_id'])) {
                $message[] = '将客户《' . $customerData[$value]['name'] . '》分配给员工' . $username . '失败，失败原因：不是公海客户！';

                unset($customerId[(int)$key]);
                continue;
            }
        }

        # 检查是否还有要领取的客户
        if (empty($customerId)) return $message;

        # 获取超出持有客户数的数量
        $customerConfigModel = new CustomerConfig();
        $exceedCount = $customerConfigModel->checkData($userId, 1, 0, count($customerId));

        # 记录添加失败的客户
        $failCustomer = [];
        if (!is_bool($exceedCount) && !empty($exceedCount) && $exceedCount > 0) {
            $failCustomer = array_slice($customerId, count($customerId) - $exceedCount);
            foreach ($failCustomer AS $key => $value) {
                $message[] = '将客户《' . $customerData[$value]['name'] . '》分配给员工' . $username . '失败，失败原因：持有客户数达到上限！';
            }
        }

        # 可以领取的客户ID，取差集
        $addCustomerId = count($customerId) == 1 ? $customerId : array_diff($customerId, $failCustomer);

        # 检查是否还有要领取的客户
        if (empty($addCustomerId)) return $message;

        # 整理客户更新数据
        $addCustomerData = [
            'owner_user_id'        => $userId,
            'before_owner_user_id' => 0,
            'into_pool_time'       => 0,
            'obtain_time'          => time(),
            'is_dealt'             => 0,
            'is_allocation'        => 1,
            'follow'               => '待跟进'
        ];

        # 整理字段操作记录和数据日志的数据
        $ip = request()->ip();
        $addActionRecordData = [];
        $addOperationLogData = [];
        $addReceiveData = [];
        foreach ($addCustomerId AS $key => $value) {
            $addActionRecordData[] = [
                'user_id'     => $userId,
                'types'       => 'crm_customer',
                'action_id'   => $value,
                'content'     => '将客户 ' . $customerData[$value]['name'] . ' 分配给员工 ' . $username,
                'create_time' => time()
            ];
            $addOperationLogData[] = [
                'user_id'     => $userId,
                'client_ip'   => $ip,
                'module'      => 'crm_customer',
                'action_id'   => $value,
                'content'     => '将客户 ' . $customerData[$value]['name'] . ' 分配给员工 ' . $username,
                'create_time' => time(),
                'action_name' => 'update',
                'target_name' => $customerData[$value]['name']
            ];
            $addReceiveData[] = [
                'customer_id' => $value,
                'user_id'     => $userId,
                'pool_id'     => $param['pool_id'],
                'type'        => 3,
                'create_time' => time()
            ];
        }

        Db::startTrans();
        try {
            # 领取客户
            Db::name('crm_customer')->whereIn('customer_id', $addCustomerId)->update($addCustomerData);

            # 设置客户的联系人数据
            Db::name('crm_contacts')->whereIn('customer_id', $addCustomerId)->update(['owner_user_id' => $userId]);

            # 删除公海与客户关联数据
            Db::name('crm_customer_pool_relation')->whereIn('customer_id', $addCustomerId)->delete();

            # 字段操作日志
            Db::name('admin_action_record')->insertAll($addActionRecordData);

            # 数据操作日志
            Db::name('admin_operation_log')->insertAll($addOperationLogData);

            # 记录领取的客户
            Db::name('crm_customer_pool_record')->insertAll($addReceiveData);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            $message = ['分配失败，刷新后重试！'];
        }

        return $message;
    }

    /**
     * 公海权限
     *
     * @param $param
     * @author fanqi
     * @since 2021-04-14
     * @return array
     */
    public function getAuthorityData($param)
    {
        # 权限
        $authority = [
            'index'       => false, # 列表
            'receive'     => false, # 领取
            'distribute'  => false, # 分配
            'excelexport' => false, # 导出
            'excelimport' => false, # 导入
            'delete'      => false, # 删除
        ];

        if (empty($param['pool_id']) || empty($param['user_id']) || empty($param['structure_id'])) return $authority;

        $poolId      = $param['pool_id'];
        $userId      = $param['user_id'];
        $structureId = $param['structure_id'];

        # 是否是超级管理员
        $userLevel = isSuperAdministrators($param['user_id']);

        # 公海成员数据
        $data = db('crm_customer_pool')->field(['admin_user_ids', 'user_ids', 'department_ids'])->where('pool_id', $poolId)->find();

        # 管理员、成员、部门
        $adminUserIds  = !empty($data['admin_user_ids']) ? explode(',', trim($data['admin_user_ids'], ',')) : [];
        $userIds       = !empty($data['user_ids'])       ? explode(',', trim($data['user_ids'], ','))       : [];
        $structureIds  = !empty($data['department_ids']) ? explode(',', trim($data['department_ids'], ',')) : [];

        # 权限判断
        $authority['index']       = ($userLevel || in_array($userId, $adminUserIds)) || (in_array($userId, $userIds) || in_array($structureId, $structureIds));
        $authority['receive']     = ($userLevel || in_array($userId, $adminUserIds)) || (in_array($userId, $userIds) || in_array($structureId, $structureIds));
        $authority['distribute']  = $userLevel || in_array($userId, $adminUserIds);
        $authority['excelexport'] = $userLevel || in_array($userId, $adminUserIds);
        $authority['excelimport'] = $userLevel || in_array($userId, $adminUserIds);
        $authority['delete']      = $userLevel || in_array($userId, $adminUserIds);

        return $authority;
    }

    /**
     * 获取用户公海字段样式
     *
     * @param array $param pool 公海ID，user_is 用户ID
     * @author fanqi
     * @since 2021-04-22
     * @return array[]
     */
    public function getFieldConfigIndex($param)
    {
        $showList = [];
        $hideList = [];

        # 公海字段-用户配置数据
        $data = db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->value('content');
        $data = !empty($data) ? json_decode($data, true) :[];

        if (!empty($data)) {
            foreach ($data AS $key => $value) {
                if (!empty($value['is_hidden'])) {
                    $hideList[] = $value;
                } else {
                    $showList[] = $value;
                }
            }
        } else {
            # 公海字段-后台配置数据
            $poolField = db('crm_customer_pool_field_setting')->where('pool_id', $param['pool_id'])->select();
            foreach ($poolField AS $key => $value) {
                if (empty($value['is_hidden'])) {
                    $showList[] = [
                        'field' => $value['field_name'],
                        'name' => $value['name'],
                        'form_type' => $value['form_type'],
                        'is_hidden' => $value['is_hidden'],
                        'width' => ''
                    ];
                }
            }
        }

        return ['value_list' => $showList, 'hide_list' => $hideList];
    }

    /**
     * 设置公海字段列宽
     *
     * @param array $param pool_id 公海ID，field 字段名，width 宽度
     * @author fanqi
     * @since 2021-04-22
     */
    public function setFieldWidth($param)
    {
        $data = db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->value('content');
        $data = !empty($data) ? json_decode($data, true) :[];

        if (!empty($data)) {
            foreach ($data AS $key => $value) {
                if ($param['field'] == $value['field']) {
                    $data[$key]['width'] = $param['width'];
                }
            }
            db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->update([
                'content' => json_encode($data),
                'update_time' => time()
            ]);
        } else {
            $result = [];
            $poolField = db('crm_customer_pool_field_setting')->where('pool_id', $param['pool_id'])->select();
            foreach ($poolField AS $key => $value) {
                if ($param['field'] == $value['field_name']) {
                    $value['width'] = $param['width'];
                }
                $result[] = [
                    'field' => $value['field_name'],
                    'name' => $value['name'],
                    'width' => $value['width'],
                    'is_hidden' => $value['hidden']
                ];
            }
            if (!empty($result)) {
                db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->insert([
                    'user_id' => $param['user_id'],
                    'pool_id' => $param['pool_id'],
                    'content' => json_encode($result),
                    'create_time' => time(),
                    'update_time' => time()
                ]);
            }
        }
    }

    /**
     * 设置公海字段样式
     *
     * @param array $param pool_id 公海ID，value 要显示的字段数组，hide_value 要隐藏的字段数组
     * @author fanqi
     * @since 2021-04-22
     */
    public function setFieldConfig($param)
    {
        $data     = [];
        $showList = $param['value'];
        $hideList = $param['hide_value'];

        foreach ($showList AS $key => $value) {
            $data[] = [
                'field' => $value['field'],
                'name' => $value['name'],
                'is_hidden' => 0,
                'width' => $value['width']
            ];
        }
        foreach ($hideList AS $key => $value) {
            $data[] = [
                'field' => $value['field'],
                'name' => $value['name'],
                'is_hidden' => 1,
                'width' => $value['width']
            ];
        }

        if (db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->value('id')) {
            db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->update([
                'content' => json_encode($data),
                'update_time' => time()
            ]);
        } else {
            db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->insert([
                'user_id' => $param['user_id'],
                'pool_id' => $param['pool_id'],
                'content' => json_encode($data),
                'create_time' => time(),
                'update_time' => time()
            ]);
        }
    }

    /**
     * 数据的ID字符串转文字
     *
     * @param string $source 源ids
     * @param array $target 目标数组
     * @author fanqi
     * @since 2021-04-14
     * @return string
     */
    private function fieldTransformToText($source, $target)
    {
        $result = [];

        $array = explode(',', trim($source, ','));

        foreach ($array AS $kk => $vv) {
            if (!empty($target[$vv])) $result[] = $target[$vv];
        }

        return implode(',', $result);
    }

    /**
     * 查询表字段
     *
     * @param int $poolId 公海ID
     * @author fanqi
     * @since 2021-04-14
     * @return string
     */
    private function getPoolQueryField($poolId)
    {
        # 自定义字段
        $customerFields = db('crm_customer_pool_field_setting')->where('pool_id', $poolId)->column('field_name');
        $customerFields[] = 'customer_id';

        # 自定增加表别名
        $result = array_reduce($customerFields, function ($result, $value) {
            return $result . 'customer.' . $value . ',';
        });

        return trim($result, ',');
    }

    /**
     * 获取员工列表
     *
     * @author fanqi
     * @since 2021-04-14
     * @return array
     */
    private function getUserList()
    {
        $result = [];

        $list = db('admin_user')->field(['id', 'realname'])->select();

        foreach ($list AS $key => $value) {
            $result[$value['id']] = $value['realname'];
        }

        return $result;
    }

    /**
     * 获取部门列表
     *
     * @author fanqi
     * @since 2021-04-14
     * @return array
     */
    private function getStructureList()
    {
        $result = [];

        $list = db('admin_structure')->field(['id', 'name'])->select();

        foreach ($list AS $key => $value) {
            $result[$value['id']] = $value['name'];
        }

        return $result;
    }

    /**
     * 获取客户列表
     *
     * @param array $customerId 客户ID
     * @author fanqi
     * @since 2021-04-15
     * @return array
     */
    private function getCustomerList($customerId)
    {
        $result = [];

        # 获取客户数据
        $customerList = db('crm_customer')->field(['customer_id', 'owner_user_id', 'name', 'into_pool_time', 'before_owner_user_id'])->whereIn('customer_id', $customerId)->select();

        # 整理客户数据
        foreach ($customerList AS $key => $value) {
            $result[$value['customer_id']] = $value;
        }

        return $result;
    }

    /**
     * 设置公海字段样式
     *
     * @param array $param user_id 用户id，pool_id 公海id
     * @param array $data 公海字段数据
     * @return array
     */
    private function setFieldStyle($param, $data)
    {
        $result = [];

        # 更新字段样式
        $this->updateFieldStyle($param);

        # 查询自定义字段样式列表
        $list = db('crm_customer_pool_field_style')->where(['user_id' => $param['user_id'], 'pool_id' => $param['pool_id']])->value('content');

        # 如果用户没有自定义数据返回原数据
        if (empty($list)) return $data;

        $list = json_decode($list, true);

        foreach ($list AS $key => $value) {
            if (!empty($value['is_hidden']) || empty($data[$value['field']])) continue;

            $result[] = [
                'field'     => $value['field'],
                'fieldName' => $data[$value['field']]['fieldName'],
                'name'      => $value['name'],
                'width'     => $value['width'],
                'is_hidden' => 0,
                'system'    => $data[$value['field']]['system'],
                'form_type' => $data[$value['field']]['form_type'],
            ];
        }

        return $result;
    }

    /**
     * 更新用户设置的字段样式
     *
     * @param $param
     * @author fanqi
     * @since 2021-05-06
     */
    private function updateFieldStyle($param)
    {
        if (!empty($param['user_id']) && !empty($param['pool_id'])) {
            # 公海字段-用户配置数据
            $fieldStyleContent = db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->value('content');

            if (!empty($fieldStyleContent)) {
                $fieldStyleList = json_decode($fieldStyleContent, true);

                # 公海字段-后台配置数据
                $poolField = db('crm_customer_pool_field_setting')->where('pool_id', $param['pool_id'])->select();
                $poolData = [];
                foreach ($poolField AS $key => $value) {
                    $poolData[$value['field_name']] = $value;
                }

                # 去掉隐藏的字段 + 去掉已经存在的字段 = 剩下的就是新增(隐藏后又开启)的字段
                foreach ($fieldStyleList AS $key => $value) {
                    # 去掉隐藏的字段
                    if (empty($poolData[$value['field']]) || (!empty($poolData[$value['field']]) && !empty($poolData[$value['field']]['is_hidden']))) {
                        unset($fieldStyleList[$key]);
                        unset($poolData[$value['field']]);
                        continue;
                    }

                    # 后台可能更新了字段名称
                    $fieldStyleList[$key]['name'] = $poolData[$value['field']]['name'];

                    # 去掉已经存在的字段
                    unset($poolData[$value['field']]);
                }

                # 新增（隐藏后又开启）字段
                if (!empty($poolData)) {
                    foreach ($poolData AS $key => $value) {
                        if (empty($value['is_hidden'])) {
                            $fieldStyleList[] = [
                                'field' => $value['field_name'],
                                'name' => $value['name'],
                                'is_hidden' => 0,
                                'width' => ''
                            ];
                        }
                    }
                }

                # 公海字段-更新用户配置
                db('crm_customer_pool_field_style')->where(['pool_id' => $param['pool_id'], 'user_id' => $param['user_id']])->update([
                    'content' => json_encode(array_values($fieldStyleList))
                ]);
            }
        }
    }
}