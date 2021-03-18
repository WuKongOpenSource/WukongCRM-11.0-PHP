<?php
/**
 * 发票逻辑类
 *
 * @author qifan
 * @date 2020-12-07
 */

namespace app\crm\logic;

use app\admin\controller\ApiCommon;
use app\admin\model\Common;
use app\crm\model\Invoice;
use think\Db;

class InvoiceLogic
{
    private $invoiceType = ['增值税专用发票', '增值税普通发票', '国税通用机打发票', '地税通用机打发票', '收据'];

    /**
     * 列表
     *
     * @param $param
     * @param false $search
     * @return array
     * @throws \think\exception\DbException
     */
    public function index($param, $search = false)
    {
        $field = [
            'invoice_id',
            'invoice_apple_number',
            'invoice_money',
            'invoice_date',
            'real_invoice_date',
            'invoice_type',
            'invoice_number',
            'logistics_number',
            'check_status',
            'invoice_status',
            'customer_id',
            'contract_id',
            'owner_user_id',
            'flow_id'
        ];

        $limit = $param['limit'];
        $getCount = $param['getCount'];
        $userId   = $param['user_id'];
        $invoiceIdArray = $param['invoiceIdArray']; // 待办事项提醒参数
        $dealt = $param['dealt'];

        unset($param['getCount']);
        unset($param['limit']);
        unset($param['page']);
        unset($param['user_id']);
        unset($param['invoiceIdArray']);
        unset($param['dealt']);

        $where = [];
        if ($search) {
            # 处理基本参数

            $scene_id = $param['scene_id'];
            unset($param['scene_id']);

            $common = new Common();

            # 高级搜索
            $request    = $common->fmtRequest($param);
            $requestMap = !empty($request['map']) ? $request['map'] : [];
            unset($requestMap['search']);

            # 场景
            $sceneMap = [];
            if (!empty($scene_id) && $scene_id == 1) {
                # 我负责的
                $sceneMap['owner_user_id'] = $userId;
            }
            if (!empty($scene_id) && $scene_id == 2) {
                # 我下属负责的
                $subordinate = getSubUserId(false, 0, $userId);
                $sceneMap['owner_user_id'] = !empty($subordinate) ? ['in', $subordinate] : 0;
            }

            # 合并高级搜索和场景的查询条件
            $map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
            $map = where_arr($map, 'crm', 'invoice', 'index');

            # 替换掉字段前缀，不修改公共函数
            foreach ($map AS $key => $value) {
                $k = str_replace('invoice.', '', $key);

                $where[$k] = $value;
            }

            # 重置查询条件
            if ($where) $param = $where;
        }

        # 待办事项查询参数
        $dealtWhere = [];
        if (!empty($invoiceIdArray)) $dealtWhere['invoice_id'] = ['in', $invoiceIdArray];

        # 权限，不是待办事项，则加上列表权限
        $auth = [];
        if (empty($dealt)) {
            $userModel = new \app\admin\model\User();
            $authUserIds = $userModel->getUserByPer('crm', 'invoice', 'index');
            $auth['owner_user_id'] = ['in', $authUserIds];
        }

        # 查询数据
        $list = Invoice::with(['toCustomer', 'toContract', 'toAdminUser'])->field($field)->where($auth)
            ->where($param)->where($dealtWhere)->limit($limit)->order('update_time', 'desc')->paginate($limit)->toArray();

        # 处理发票类型
//        foreach ($list['data'] AS $key => $value) {
//            $list['data'][$key]['invoice_type'] = $this->invoiceType[$value['invoice_type']];
//        }

        return ['list' => $list['data'], 'dataCount' => $list['total']];
    }

    /**
     * 创建
     *
     * @param $param
     * @return Invoice|int|string
     */
    public function save($param)
    {
        return db('crm_invoice')->insert($param, false, true);
    }

    /**
     * 详情
     *
     * @param $invoiceId
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($invoiceId, $isUpdate)
    {
        $apiCommon = new ApiCommon();

        $userId     = $apiCommon->userInfo['id'];
        $result     = [];
        $dataObject = Invoice::with(['toCustomer', 'toContract'])->where('invoice_id', $invoiceId)->find();

        if (empty($dataObject)) return $result;

        $dataArray = $dataObject->toArray();

        if (!empty($isUpdate)) return $dataArray;

        # 主键ID
        $result['invoice_id'] = $dataArray['invoice_id'];

        # 是否显示撤回按钮
        $result['isShowRecall'] = 0;
        if ($userId == $dataArray['owner_user_id'] && $dataArray['check_status'] == 0) $result['isShowRecall'] = 1;

        $result['customer_name']     = $dataArray['customer_name'];     # 客户名称
        $result['invoice_money']     = $dataArray['invoice_money'];     # 开票金额
        $result['invoice_number']    = $dataArray['invoice_number'];    # 发票号码
        $result['real_invoice_date'] = $dataArray['real_invoice_date']; # 开票日期
        $result['flow_id']           = $dataArray['flow_id'];           # 审核ID

        # 基本信息
        $result['essential'] = [
            'invoice_apple_number' => $dataArray['invoice_apple_number'],
            'customer_name'        => $dataArray['customer_name'],
            'contract_number'      => $dataArray['contract_number'],
            'contract_money'       => $dataArray['contract_money'],
            'invoice_money'        => $dataArray['invoice_money'],
            'invoice_date'         => $dataArray['invoice_date'],
            'invoice_type'         => $dataArray['invoice_type'],
            'remark'               => $dataArray['remark'],
            'create_user_name'     => db('admin_user')->where('id', $dataArray['create_user_id'])->value('realname'),
            'owner_user_name'      => db('admin_user')->where('id', $dataArray['owner_user_id'])->value('realname'),
            'create_time'          => $dataArray['create_time'],
            'update_time'          => $dataArray['update_time'],
            'invoice_number'       => $dataArray['invoice_number'],
            'real_invoice_date'    => $dataArray['real_invoice_date'],
            'customer_id'          => $dataArray['customer_id'],
            'contract_id'          => $dataArray['contract_id']
        ];

        # 发票信息
        $result['invoice'] = [
            'title_type'      => $dataArray['title_type'],
            'deposit_bank'    => $dataArray['deposit_bank'],
            'invoice_title'   => $dataArray['invoice_title'],
            'tax_number'      => $dataArray['tax_number'],
            'deposit_account' => $dataArray['deposit_account'],
            'deposit_address' => $dataArray['deposit_address'],
            'phone'           => $dataArray['phone']
        ];

        # 邮寄信息
        $result['posting']   = [
            'contacts_name'    => $dataArray['contacts_name'],
            'contacts_mobile'  => $dataArray['contacts_mobile'],
            'contacts_address' => $dataArray['contacts_address']
        ];

        return $result;
    }

    /**
     * 编辑
     *
     * @param $param
     * @return Invoice
     */
    public function update($param)
    {
        return Invoice::update($param);
    }

    /**
     * 删除
     *
     * @param $where
     * @return int
     */
    public function delete($where)
    {
        return Invoice::destroy($where);
    }

    /**
     * 获取审批状态
     *
     * @param $invoiceId
     * @param false $isDelete
     * @return bool|int|mixed|\PDOStatement|string|\think\Collection|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getExamineStatus($invoiceId, $isDelete = false)
    {
        # 删除
        if ($isDelete) {
            return Invoice::field(['check_status'])->whereIn('invoice_id', $invoiceId)->select();
        }

        # 编辑
        return Invoice::where('invoice_id', $invoiceId)->value('check_status');
    }

    /**
     * 转移（变更负责人）
     *
     * @param $invoiceIds
     * @param $ownerUserId
     * @return Invoice
     */
    public function transfer($invoiceIds, $ownerUserId)
    {
        return Invoice::whereIn('invoice_id', $invoiceIds)->update(['owner_user_id' => $ownerUserId]);
    }

    /**
     * 设置开票
     * 
     * @param $param
     * @return Invoice
     */
    public function setInvoice($param)
    {
        return Invoice::update($param);
    }

    /**
     * 获取发票审核信息
     *
     * @param $invoiceId
     * @return array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getExamineInfo($invoiceId)
    {
        $field = ['check_status', 'flow_id', 'order_id', 'check_user_id', 'flow_user_id', 'invoice_apple_number', 'owner_user_id', 'create_user_id'];

        return Invoice::field($field)->where('invoice_id', $invoiceId)->find();
    }

    /**
     * 设置审批信息
     *
     * @param $data
     * @return Invoice
     */
    public function setExamineInfo($data)
    {
        return Invoice::update($data);
    }

    /**
     * 添加撤销审核记录
     *
     * @param $invoiceId
     * @param $examineInfo
     * @param $realname
     * @param $content
     * @param $userId
     */
    public function createExamineRecord($invoiceId, $examineInfo, $realname, $content, $userId)
    {
        $data = [
            'types'         => 'crm_invoice',
            'types_id'      => $invoiceId,
            'flow_id'       => $examineInfo['flow_id'],
            'order_id'      => $examineInfo['order_id'],
            'check_user_id' => $userId,
            'check_time'    => time(),
            'status'        => 2,
            'content'       => !empty($content) ? $content : $realname . ' 撤销了审核',
        ];

        Db::name('admin_examine_record')->insert($data);
    }

    /**
     * 检查发票编号是否重复
     *
     * @param $where
     * @return int|mixed|string|null
     */
    public function getInvoiceId($where)
    {
        return Db::name('crm_invoice')->where($where)->value('invoice_id');
    }
}