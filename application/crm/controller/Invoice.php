<?php
/**
 * 发票控制器
 *
 * @author qifan
 * @date 2020-12-07
 */

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\admin\model\Message;
use app\admin\model\User;
use app\crm\logic\InvoiceLogic;
use app\crm\model\NumberSequence;
use app\crm\traits\AutoNumberTrait;
use think\Db;
use think\Hook;
use think\Request;

class Invoice extends ApiCommon
{
    use AutoNumberTrait;

    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     **/
    public function _initialize()
    {
        $action = [
            'permission' => [],
            'allow'      => ['check', 'revokecheck', 'count', 'read']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 列表
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index(InvoiceLogic $invoiceLogic)
    {
        $param = $this->param;
        $param['user_id'] = $this->userInfo['id'];

        $data = $invoiceLogic->index($param, true);

        return resultArray(['data' => $data]);
    }

    /**
     * 创建
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save(InvoiceLogic $invoiceLogic)
    {
        if (empty($this->param['customer_id']))          return resultArray(['error' => '请选择客户！']);
        if (empty($this->param['contract_id']))          return resultArray(['error' => '请选择合同！']);
        if (empty($this->param['invoice_money']))        return resultArray(['error' => '请填写开票金额！']);
        if (empty($this->param['invoice_type']))         return resultArray(['error' => '请选择开票类型！']);
        if (empty($this->param['title_type']))           return resultArray(['error' => '请选择抬头类型！']);
        if (empty($this->param['examineStatus']))        return resultArray(['error' => '缺少审批状态！']);

        $param         = $this->param;
        $userId        = $this->userInfo['id'];
        # 审批是否停用
        $examineStatus = $param['examineStatus'];
        # 删除无用参数
        unset($param['examineStatus']);
        unset($param['customer_name']);
        unset($param['contract_money']);
        unset($param['contract_number']);
        # 设置创建人负责人ID
        $param['create_user_id'] = $userId;
        $param['owner_user_id']  = $userId;
        # 创建更新日期
        $param['create_time']    = time();
        $param['update_time']    = time();

        # 自动设置发票编号
        $numberInfo = [];
        if (empty($param['invoice_apple_number'])) {
            $numberInfo = $this->getAutoNumbers(4);
            if (empty($numberInfo['number'])) return resultArray(['error' => '请填写发票编号！']);
            $param['invoice_apple_number'] = $numberInfo['number'];
        }
        # 检查发票编号是否重复
        if ($invoiceLogic->getInvoiceId(['invoice_apple_number' => $param['invoice_apple_number']])) {
            return resultArray(['error' => '发票编号重复！']);
        }

        if (($examineStatus != false && $examineStatus != 'false') || $examineStatus == 1) {
            $examineStepModel = new \app\admin\model\ExamineStep();
            # 审核判断（是否有符合条件的审批流）
            $examineFlowModel = new \app\admin\model\ExamineFlow();
            if (!$examineFlowModel->checkExamine($userId, 'crm_invoice')) {
                return resultArray(['error' => '暂无审批人，无法创建']);
            }
            # 添加审批相关信息
            $examineFlowData = $examineFlowModel->getFlowByTypes($userId, 'crm_invoice');
            if (!$examineFlowData) {
                return resultArray(['error' => '无可用审批流，请联系管理员']);
            }
            $param['flow_id'] = $examineFlowData['flow_id'];
            # 获取审批人信息
            if ($examineFlowData['config'] == 1) {
                # 固定审批流
                $nextStepData = $examineStepModel->nextStepUser($userId, $examineFlowData['flow_id'], 'crm_invoice', 0, 0, 0);
                $next_user_ids = arrayToString($nextStepData['next_user_ids']) ? : '';
                $check_user_id = $next_user_ids ? : [];
                $param['order_id'] = 1;
            } else {
                # 授权审批流
                $check_user_id = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
            }
            if (!$check_user_id) {
                return resultArray(['error' => '无可用审批人，请联系管理员']);
            }
            $param['check_user_id'] = is_array($check_user_id) ? ','.implode(',',$check_user_id).',' : $check_user_id;
        } else {
            # 审批流停用，将状态改为审核通过
            $param['check_status'] = 2;
        }

        if (!$invoice_id = $invoiceLogic->save($param)) {
            return resultArray(['error' => '创建失败！']);
        }
        $send_user_id = stringToArray($param['check_user_id']);
        (new Message())->send(
            Message::INVOICE_TO_DO,
            [
                'title'     => $param['invoice_apple_number'],
                'action_id' => $invoice_id
            ],
            $send_user_id
        );
        # 更新crm_number_sequence表中的last_date、create_time字段
        if (!empty($numberInfo['data'])) (new NumberSequence())->batchUpdate($numberInfo['data']);
        updateActionLog($param['create_user_id'], 'crm_invoice', $invoice_id, '', '', '创建了发票');

        # 创建待办事项的关联数据
        $checkUserIds = db('crm_invoice')->where('invoice_id', $invoice_id)->value('check_user_id');
        $checkUserIdArray = stringToArray($checkUserIds);
        $dealtData = [];
        foreach ($checkUserIdArray AS $kk => $vv) {
            $dealtData[] = [
                'types'    => 'crm_invoice',
                'types_id' => $invoice_id,
                'user_id'  => $vv
            ];
        }
        if (!empty($dealtData)) db('crm_dealt_relation')->insertAll($dealtData);

        return resultArray(['data' => '创建成功！']);
    }

    /**
     * 详情
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read(InvoiceLogic $invoiceLogic)
    {
        $invoiceId = $this->param['id'];
        $isUpdate  = !empty($this->param['is_update']) ? $this->param['is_update'] : 0;
        $userInfo  = $this->userInfo;

        $data = $invoiceLogic->read($invoiceId, $isUpdate);
        $readStatus = false;

        # 角色权限
        $authArray = db('admin_access')->alias('access')
            ->join('__ADMIN_GROUP__ group', 'group.id = access.group_id', 'left')
            ->where('access.user_id', $userInfo['id'])->column('group.rules');

        # 详情权限ID
        $invoiceAuthId     = db('admin_rule')->where(['types' => 2, 'name' => 'invoice', 'level' => 2])->value('id');
        $invoiceReadAuthId = db('admin_rule')->where(['types' => 2, 'name' => 'read', 'level' => 3, 'pid' => $invoiceAuthId])->value('id');

        foreach ($authArray AS $key => $value) {
            if (!empty($value) && in_array($invoiceReadAuthId, stringToArray($value))) $readStatus = true;
        }

        if (!isSuperAdministrators($userInfo['id']) && $readStatus === false) {
            $authData['dataAuth'] = (int)0;
            return resultArray(['data' => $authData]);
        }

        return resultArray(['data' => $data]);
    }

    /**
     * 编辑
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update(InvoiceLogic $invoiceLogic)
    {
        $param = $this->param;
        if (empty($param['invoice_id']))           return resultArray(['error' => '缺少发票ID！']);
        // if (empty($param['customer_id']))          return resultArray(['error' => '请选择客户！']);
        // if (empty($param['contract_id']))          return resultArray(['error' => '请选择合同！']);
        // if (empty($param['invoice_money']))        return resultArray(['error' => '请填写开票金额！']);
        if (empty($param['invoice_type']))         return resultArray(['error' => '请选择开票类型！']);
        if (empty($param['title_type']))           return resultArray(['error' => '请选择抬头类型！']);
        if (empty($param['examineStatus']))        return resultArray(['error' => '缺少审批状态！']);
        $userId = $this->userInfo['id']; 

        # 审批是否停用
        $examineStatus = $param['examineStatus'];
        # 删除无用参数
        unset($param['examineStatus']);
        unset($param['customer_name']);
        unset($param['contract_money']);
        unset($param['contract_number']);
        # 设置负责人ID
        $param['update_time'] = time();

        # 已进行审批，不能编辑
        // $dataInfo = $invoiceLogic->read($param['invoice_id'], '');
        // if (!$dataInfo) {
        //     $this->error = '数据不存在或已删除';
        //     return false;
        // }            
     
        $checkStatus = $invoiceLogic->getExamineStatus($param['invoice_id']);
        if (!in_array($checkStatus, ['3', '4', '5', '6'])) return resultArray(['error' => '当前状态为审批中或已审批通过，不可编辑']);

        # 自动设置发票编号
        $numberInfo = [];
        if (empty($param['invoice_apple_number'])) {
            $numberInfo = $this->getAutoNumbers(4);
            if (empty($numberInfo['number'])) return resultArray(['error' => '请填写发票编号！']);
            $param['invoice_apple_number'] = $numberInfo['number'];
        }

        # 检查发票编号是否重复
        $invoiceWhere['invoice_apple_number'] = $param['invoice_apple_number'];
        $invoiceWhere['invoice_id'] = ['neq', $this->param['invoice_id']];
        if ($invoiceLogic->getInvoiceId($invoiceWhere)) return resultArray(['error' => '发票编号重复！']);

        if ($param['is_draft'] || (!empty($param['check_status']) && $param['check_status'] == 5)) {
            //保存为草稿
            $param['check_status'] = 5; //草稿(未提交)
            $param['check_user_id'] = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
        } else {
            # 将合同审批状态至为待审核，提交后重新进行审批
            if (($examineStatus != false && $examineStatus != 'false') || $examineStatus == 1) {
                # 审核判断（是否有符合条件的审批流）
                $examineFlowModel = new \app\admin\model\ExamineFlow();
                $examineStepModel = new \app\admin\model\ExamineStep();
                if (!$examineFlowModel->checkExamine($userId, 'crm_invoice')) {
                    return resultArray(['error' => '暂无审批人，无法创建']);
                }
                # 添加审批相关信息
                $examineFlowData = $examineFlowModel->getFlowByTypes($userId, 'crm_invoice');
                if (!$examineFlowData) {
                    return resultArray(['error' => '无可用审批流，请联系管理员']);
                }
                $param['flow_id'] = $examineFlowData['flow_id'];
                # 获取审批人信息
                if ($examineFlowData['config'] == 1) {
                    # 固定审批流
                    $nextStepData = $examineStepModel->nextStepUser($userId, $examineFlowData['flow_id'], 'crm_invoice', 0, 0, 0);
                    $next_user_ids = arrayToString($nextStepData['next_user_ids']) ? : '';
                    $check_user_id = $next_user_ids ? : [];
                    $param['order_id'] = 1;
                } else {
                    # 授权审批流
                    $check_user_id = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
                }
                if ($param['is_draft']) {
                    //保存为草稿
                    $param['check_status'] = 5;
                    $param['check_user_id'] = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
                } else {
                    if (!$check_user_id) {
                        return resultArray(['error' => '无可用审批人，请联系管理员']);
                    }
                    $param['check_user_id'] = is_array($check_user_id) ? ','.implode(',',$check_user_id).',' : $check_user_id;
                    $param['check_status'] = 0;
                }
                $param['flow_user_id'] = '';
            }
        }

        if (!$invoiceLogic->update($param)) {
            return resultArray(['error' => '编辑失败！']);
        }

        //将审批记录至为无效
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $examineRecordModel->setEnd(['types' => 'crm_invoice','types_id' => $param['invoice_id']]);

        # 更新crm_number_sequence表中的last_date、create_time字段
        if (!empty($numberInfo['data'])) (new NumberSequence())->batchUpdate($numberInfo['data']);
        //修改记录
        // updateActionLog($param['user_id'], 'crm_invoice', $param['invoice_id'], $dataInfo, $param);

        # 删除待办事项的关联数据
        db('crm_dealt_relation')->where(['types' => ['eq', 'crm_invoice'], 'types_id' => ['eq', $param['invoice_id']]])->delete();
        # 创建待办事项的关联数据
        $checkUserIds = db('crm_invoice')->where('invoice_id', $param['invoice_id'])->value('check_user_id');
        $checkUserIdArray = stringToArray($checkUserIds);
        $dealtData = [];
        foreach ($checkUserIdArray AS $kk => $vv) {
            $dealtData[] = [
                'types'    => 'crm_invoice',
                'types_id' => $param['invoice_id'],
                'user_id'  => $vv
            ];
        }
        if (!empty($dealtData)) db('crm_dealt_relation')->insertAll($dealtData);

        return resultArray(['data' => '编辑成功！']);
    }

    /**
     * 删除
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete(InvoiceLogic $invoiceLogic)
    {
        $actionRecordModel = new \app\admin\model\ActionRecord();
        $fileModel = new \app\admin\model\File();
        $idArray  = $this->param['id'];
        $userinfo = $this->userInfo['id'];

        if (!is_array($idArray)) return resultArray(['error' => '发票ID类型错误！']);

        $idString = implode(',', $idArray);
        $status   = true;

        if (!isSuperAdministrators($userinfo['id'])) {
            $list = $invoiceLogic->getExamineStatus($idString, true);
            foreach ($list AS $key => $value) {
                if (!in_array($value['check_status'],  [4, 5])) {
                    $status = false;
                    break;
                }
            }
        }

        if (!$status) return resultArray(['error' => '不能删除审批中或审批结束的发票信息！']);

        if (!$invoiceLogic->delete($idArray)) return resultArray(['error' => '删除失败！']);

        # 删除附件
        $fileModel->delRFileByModule('crm_invoice', $idArray);
        //删除关联操作记录
        $actionRecordModel->delDataById(['types'=>'crm_invoice','action_id'=>$idArray]);

        return resultArray(['data' => '删除成功！']);
    }

    /**
     * 转移（变更负责人）
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     */
    public function transfer(InvoiceLogic $invoiceLogic)
    {
        $ownerUserId = $this->param['owner_user_id'];
        $invoiceIds  = $this->param['invoice_id'];

        if (empty($ownerUserId))    return resultArray(['error' => '请选择负责人！']);
        if (empty($invoiceIds))     return resultArray(['error' => '请选择发票！']);
        if (!is_array($invoiceIds)) return resultArray(['error' => '发票ID类型错误！']);

        if ($invoiceLogic->transfer($invoiceIds, $ownerUserId) === false) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 设置开票
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     */
    public function setInvoice(InvoiceLogic $invoiceLogic)
    {
        if (empty($this->param['invoice_id']))        return resultArray(['error' => '参数错误！']);
//        if (empty($this->param['invoice_number']))    return resultArray(['error' => '请填写发票号码！']);
//        if (empty($this->param['logistics_number']))  return resultArray(['error' => '请填写物流单号！']);
//        if (empty($this->param['real_invoice_date'])) return resultArray(['error' => '请选择开票日期！']);

        $this->param['real_invoice_date'] = !empty($this->param['real_invoice_date']) ? $this->param['real_invoice_date'] : date('Y-m-d');

        # 开票状态
        $this->param['invoice_status'] = 1;

        # 设置开票信息
        if (!$invoiceLogic->setInvoice($this->param)) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 审核
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check(InvoiceLogic $invoiceLogic)
    {
        $param              = $this->param;
        $user_id            = $this->userInfo['id'];

        $examineStepModel   = new \app\admin\model\ExamineStep();
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $examineFlowModel   = new \app\admin\model\ExamineFlow();

        $invoiceData = [];
        $invoiceData['invoice_id']   = $param['id'];
        $invoiceData['update_time']  = time();
        $invoiceData['check_status'] = 1;
        # 权限判断
        if (!$examineStepModel->checkExamine($user_id, 'crm_invoice', $param['id'])) {
            return resultArray(['error' => $examineStepModel->getError()]);
        }

        # 审批主体详情
        $dataInfo = $invoiceLogic->getExamineInfo($param['id']);
        $flowInfo = $examineFlowModel->getDataById($dataInfo['flow_id']);

        # 1审批结束
        $is_end = 0;

        # 1通过，0驳回
        $status = !empty($param['status']) && $param['status'] == 1 ? 1 : 0;

        # 审批记录
        $checkData                  = [];
        $checkData['check_user_id'] = $user_id;
        $checkData['types']         = 'crm_invoice';
        $checkData['types_id']      = $param['id'];
        $checkData['check_time']    = time();
        $checkData['content']       = $param['content'];
        $checkData['flow_id']       = $dataInfo['flow_id'];
        $checkData['order_id']      = $dataInfo['order_id'] ? : 1;
        $checkData['status']        = $status;

        if ($status == 1) {
            if ($flowInfo['config'] == 1) {
                # 固定流程
                # 获取下一审批信息
                $nextStepData = $examineStepModel->nextStepUser($dataInfo['owner_user_id'], $dataInfo['flow_id'], 'crm_invoice', $param['id'], $dataInfo['order_id'], $user_id);
                $next_user_ids = $nextStepData['next_user_ids'] ? : [];
                $invoiceData['order_id'] = $nextStepData['order_id'] ? : '';
                if (!$next_user_ids) {
                    $is_end = 1;
                    # 审批结束
                    $checkData['check_status']    = !empty($status) ? 2 : 3;
                    $invoiceData['check_user_id'] = '';
                } else {
                    # 修改主体相关审批信息
                    $invoiceData['check_user_id'] = arrayToString($next_user_ids);
                }
            } else {
                # 自选流程
                $is_end        = $param['is_end'] ? 1 : '';
                $check_user_id = $param['check_user_id'] ? : '';
                if ($is_end !== 1 && empty($check_user_id)) {
                    return resultArray(['error' => '请选择下一审批人']);
                }
                $invoiceData['check_user_id'] = arrayToString($param['check_user_id']);
            }
            if ($is_end == 1) {
                $checkData['check_status']    = !empty($status) ? 2 : 3;
                $invoiceData['check_user_id'] = '';
                $invoiceData['check_status']  = 2;
            }
        } else {
            # 审批驳回
            $is_end = 1;
            $invoiceData['check_status'] = 3;
        }
        # 已审批人ID
        $invoiceData['flow_user_id'] = stringToArray($dataInfo['flow_user_id']) ? arrayToString(array_merge(stringToArray($dataInfo['flow_user_id']),[$user_id])) : arrayToString([$user_id]);
        $resContract = $invoiceLogic->setExamineInfo($invoiceData);
        if ($resContract) {
            # 审批记录
            $examineRecordModel->createData($checkData);

            # 发送站内信
            if ($is_end == 1 && !empty($status)) {
                # 审批流程结束，将审批通过消息告知负责人
                (new Message())->send(
                    Message::INVOICE_PASS,
                    [
                        'title'     => $dataInfo['invoice_apple_number'],
                        'action_id' => $param['id']
                    ],
                    stringToArray($dataInfo['owner_user_id'])
                );
            } else {
                if (!empty($status)) {
                    # 审批流程未结束，将待审批提醒发送给下一级负责人
                    (new Message())->send(
                        Message::INVOICE_TO_DO,
                        [
                            'from_user' => User::where(['id' => $dataInfo['owner_user_id']])->value('realname'),
                            'title'     => $dataInfo['invoice_apple_number'],
                            'action_id' => $param['id']
                        ],
                        stringToArray($invoiceData['check_user_id'])
                    );
                } else {
                    # 将审批被驳回的消息告知负责人
                    (new Message())->send(
                        Message::INVOICE_REJECT,
                        [
                            'title'     => $dataInfo['invoice_apple_number'],
                            'action_id' => $param['id']
                        ],
                        stringToArray($dataInfo['owner_user_id'])
                    );
                }
            }

            return resultArray(['data' => '审批成功']);
        } else {
            return resultArray(['error' => '审批失败，请重试！']);
        }
    }

    /**
     * 撤销审核
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function revokeCheck(InvoiceLogic $invoiceLogic)
    {
        $invoiceId = $this->param['id'];
        $content   = $this->param['content'];
        $realname  = $this->userInfo['realname'];
        $userInfo  = $this->userInfo;
        $user_id   = $userInfo['id'];

        if (empty($invoiceId)) return resultArray(['error' => '请选择要撤回审核的发票！']);

        $examineInfo = $invoiceLogic->getExamineInfo($invoiceId);

        if ($examineInfo['check_status'] == 2) {
            return resultArray(['error' => '已审批结束,不能撤销']);
        }
        if ($examineInfo['check_status'] == 4) {
            return resultArray(['error' => '无需撤销']);
        }
        $userModel = new \app\admin\model\User();
        $admin_user_ids = $userModel->getAdminId();
        if ($examineInfo['owner_user_id'] !== $user_id && !in_array($user_id, $admin_user_ids)) {
            return resultArray(['error' => '没有权限']);
        }

        # 修改发票审核状态
        if (!$invoiceLogic->update(['invoice_id' => $invoiceId, 'check_status' => 4, 'check_user_id' => '', 'flow_user_id' => ''])) {
            return resultArray(['error' => '操作失败！']);
        }

        # 添加撤销审核的记录
        $invoiceLogic->createExamineRecord($invoiceId, $examineInfo, $realname, $content, $user_id);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * table栏数量统计
     *
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function count()
    {
        if (empty($this->param['invoice_id'])) return resultArray(['error' => '参数错误！']);

        # 附件
        $fileCount = Db::name('crm_invoice_file')->alias('invoice')->join('__ADMIN_FILE__ file', 'file.file_id = invoice.file_id', 'LEFT')->where('invoice_id', $this->param['invoice_id'])->count();

        return resultArray(['data' => ['fileCount' => $fileCount]]);
    }

    /**
     * 重置开票信息
     *
     * @param InvoiceLogic $invoiceLogic
     * @return \think\response\Json
     */
    public function resetInvoiceStatus(InvoiceLogic $invoiceLogic)
    {
        if (empty($this->param['invoice_id'])) resultArray(['error' => '参数错误！']);

        $this->param['real_invoice_date'] = !empty($this->param['real_invoice_date']) ? $this->param['real_invoice_date'] : date('Y-m-d');

        # 开票状态
        $this->param['invoice_status'] = 1;

        if (!$invoiceLogic->setInvoice($this->param)) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }
}