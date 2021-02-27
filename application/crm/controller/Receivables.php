<?php
// +----------------------------------------------------------------------
// | Description: 回款
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\admin\model\Message;
use app\admin\model\User;
use app\crm\model\NumberSequence;
use app\crm\traits\AutoNumberTrait;
use think\Hook;
use think\Request;
use think\Db;

class Receivables extends ApiCommon
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
            'permission' => [''],
            'allow' => ['check', 'revokecheck', 'system', 'count']
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }
    
    /**
     * 回款列表
     * @return
     * @author Michael_xu
     */
    public function index()
    {
        $receivablesModel = model('Receivables');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $receivablesModel->getDataList($param);
        return resultArray(['data' => $data]);
    }
    
    /**
     * 导出
     * @param
     * @return
     * @author guogaobo
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        if ($param['receivables_id']) {
            $param['receivables_id'] = ['condition' => 'in', 'value' => $param['receivables_id'], 'form_type' => 'text', 'name' => ''];
            $param['is_excel'] = 1;
        }
        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldConfig('crm_receivables', $userInfo['id']);
        // 文件名
        $file_name = '5kcrm_receivables_' . date('Ymd');
        
        $model = model('Receivables');
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        unset($param['page']);
        unset($param['export_queue_index']);
        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function ($page, $limit) use ($model, $param, $field_list) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = $model->getDataList($param);
            $data['list'] = $model->exportHandle($data['list'], $field_list, 'Receivables');
            return $data;
        });
    }
    
    /**
     * 添加回款
     *
     */
    public function save()
    {
        $receivablesModel = model('Receivables');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $examineStepModel = new \app\admin\model\ExamineStep();
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];
        $examineStatus = $param['examineStatus']; // 审批是否停用
        unset($param['examineStatus']);
        
        # 自动设置回款编号
        $numberInfo = [];
        if (empty($param['number'])) {
            $numberInfo = $this->getAutoNumbers(2);
            if (empty($numberInfo['number'])) return resultArray(['error' => '请填写回款编号！']);
            $param['number'] = $numberInfo['number'];
        }
        if ($param['is_draft'] || (!empty($param['check_status']) && $param['check_status'] == 5)) {
            //保存为草稿
            $param['check_status'] = 5; //草稿(未提交)
            $param['check_user_id'] = $param['check_user_id'] ? ',' . $param['check_user_id'] . ',' : '';
        }
        if (($examineStatus != false && $examineStatus != 'false') || $examineStatus == 1) {
            //审核判断（是否有符合条件的审批流）
            $examineFlowModel = new \app\admin\model\ExamineFlow();
            if (!$examineFlowModel->checkExamine($param['owner_user_id'], 'crm_receivables')) {
                return resultArray(['error' => '暂无审批人，无法创建']);
            }
            //添加审批相关信息
            $examineFlowData = $examineFlowModel->getFlowByTypes($param['owner_user_id'], 'crm_receivables');
            if (!$examineFlowData) {
                return resultArray(['error' => '无可用审批流，请联系管理员']);
            }
            $param['flow_id'] = $examineFlowData['flow_id'];
            //获取审批人信息
            if ($examineFlowData['config'] == 1) {
                //固定审批流
                $nextStepData = $examineStepModel->nextStepUser($userInfo['id'], $examineFlowData['flow_id'], 'crm_receivables', 0, 0, 0);
                $next_user_ids = arrayToString($nextStepData['next_user_ids']) ?: '';
                $check_user_id = $next_user_ids ?: [];
                $param['order_id'] = 1;
            } else {
                $check_user_id = $param['check_user_id'] ? ',' . $param['check_user_id'] . ',' : '';
            }
            if (!$check_user_id) {
                return resultArray(['error' => '无可用审批人，请联系管理员']);
            }
            $param['check_user_id'] = is_array($check_user_id) ? ',' . implode(',', $check_user_id) . ',' : $check_user_id;
        } else {
            # 审批流停用，将状态改为审核通过
            $param['check_status'] = 2;
        }
        
        $res = $receivablesModel->createData($param);
        if ($res) {
            //回款计划关联
            if ($param['plan_id']) {
                db('crm_receivables_plan')->where(['plan_id' => $param['plan_id']])->update(['receivables_id' => $res['receivables_id']]);
            }
            # 更新crm_number_sequence表中的last_date、create_time字段
            if (!empty($numberInfo['data'])) (new NumberSequence())->batchUpdate($numberInfo['data']);
            
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $receivablesModel->getError()]);
        }
    }
    
    /**
     * 回款详情
     * @param
     * @return
     * @author Michael_xu
     */
    public function read()
    {
        $receivablesModel = model('Receivables');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $data = $receivablesModel->getDataById($param['id']);
        
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'read');
        if (!in_array($data['owner_user_id'], $auth_user_ids)) {
            $authData['dataAuth'] = 0;
            return resultArray(['data' => $authData]);
        }
        if (!$data) {
            return resultArray(['error' => $receivablesModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }
    
    /**
     * 编辑回款
     * @param
     * @return
     * @author Michael_xu
     */
    public function update()
    {
        $receivablesModel = model('Receivables');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $examineStatus = $param['examineStatus']; // 审批流是否停用
        unset($param['examineStatus']);
        //判断权限
        $dataInfo = $receivablesModel->getDataById($param['id']);
        $auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'update');
        if (!in_array($dataInfo['owner_user_id'], $auth_user_ids)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
        
        # 自动设置回款编号
        $numberInfo = [];
        if (empty($param['number'])) {
            $numberInfo = $this->getAutoNumbers(2);
            if (empty($numberInfo['number'])) return resultArray(['error' => '请填写回款编号！']);
            $param['number'] = $numberInfo['number'];
        }
        
        //已进行审批，不能编辑
        if (!in_array($dataInfo['check_status'], ['3', '4', '5', '6'])) {
            return resultArray(['error' => '当前状态为审批中或已审批通过，不可编辑']);
        }
        
        if ($param['is_draft'] || (!empty($param['check_status']) && $param['check_status'] == 5)) {
            //保存为草稿
            $param['check_status'] = 5; //草稿(未提交)
            $param['check_user_id'] = $param['check_user_id'] ? ',' . $param['check_user_id'] . ',' : '';
        } else {
            if (($examineStatus != false && $examineStatus != 'false') || $examineStatus == 1) {
                if ($param['is_draft']) {
                    //保存为草稿
                    $param['check_status'] = 5;
                    $param['check_user_id'] = $param['check_user_id'] ? ',' . $param['check_user_id'] . ',' : '';
                } else {
                    //将回款审批状态至为待审核，提交后重新进行审批
                    //审核判断（是否有符合条件的审批流）
                    $examineFlowModel = new \app\admin\model\ExamineFlow();
                    $examineStepModel = new \app\admin\model\ExamineStep();
                    if (!$examineFlowModel->checkExamine($param['user_id'], 'crm_receivables')) {
                        return resultArray(['error' => '暂无审批人，无法创建']);
                    }
                    //添加审批相关信息
                    $examineFlowData = $examineFlowModel->getFlowByTypes($param['user_id'], 'crm_receivables');
                    if (!$examineFlowData) {
                        return resultArray(['error' => '无可用审批流，请联系管理员']);
                    }
                    $param['flow_id'] = $examineFlowData['flow_id'];
                    //获取审批人信息
                    if ($examineFlowData['config'] == 1) {
                        //固定审批流
                        $nextStepData = $examineStepModel->nextStepUser($dataInfo['owner_user_id'], $examineFlowData['flow_id'], 'crm_receivables', 0, 0, 0);
                        $next_user_ids = arrayToString($nextStepData['next_user_ids']) ?: '';
                        $check_user_id = $next_user_ids ?: [];
                        $param['order_id'] = 1;
                    } else {
                        $check_user_id = $param['check_user_id'] ? ',' . $param['check_user_id'] . ',' : '';
                    }
                    if (!$check_user_id) {
                        return resultArray(['error' => '无可用审批人，请联系管理员']);
                    }
                    $param['check_user_id'] = is_array($check_user_id) ? ',' . implode(',', $check_user_id) . ',' : $check_user_id;
                    $param['check_status'] = 0;
                    $param['flow_user_id'] = '';
                }
            }
        }
        
        $res = $receivablesModel->updateDataById($param, $param['id']);
        if ($res) {
            //将审批记录至为无效
            $examineRecordModel = new \app\admin\model\ExamineRecord();
            $examineRecordModel->setEnd(['types' => 'crm_receivables', 'types_id' => $param['id']]);
            # 更新crm_number_sequence表中的last_date、create_time字段
            if (!empty($numberInfo['data'])) (new NumberSequence())->batchUpdate($numberInfo['data']);
            
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $receivablesModel->getError()]);
        }
    }
    
    /**
     * 删除回款
     * @param
     * @return
     * @author Michael_xu
     */
    public function delete()
    {
        $actionRecordModel = new \app\admin\model\ActionRecord();
        $fileModel = new \app\admin\model\File();
        $recordModel = new \app\admin\model\Record();
        $receivablesModel = model('Receivables');
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!is_array($param['id'])) {
            $receivables_id = [$param['id']];
        } else {
            $receivables_id = $param['id'];
        }
        $delIds = [];
        $errorMessage = [];
        
        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'delete');
        $adminTypes = adminGroupTypes($userInfo['id']);
        foreach ($receivables_id as $k => $v) {
            $isDel = true;
            //数据详情
            $data = $receivablesModel->getDataById($v);
            if (!$data) {
                $isDel = false;
                $errorMessage[] = 'id为' . $v . '的回款删除失败,错误原因：' . $receivablesModel->getError();
                continue;
            }
            if (!in_array($data['owner_user_id'], $auth_user_ids)) {
                $isDel = false;
                $errorMessage[] = '名称为' . $data['number'] . '的回款删除失败,错误原因：无权操作';
                continue;
            }
            if (!in_array($data['check_status'], [4, 5]) && !in_array(1, $adminTypes)) {
                $isDel = false;
                $errorMessage[] = '名称为' . $data['number'] . '的回款删除失败,错误原因：请先撤销审核';
                continue;
            }
            if ($isDel) {
                if (db('crm_receivables_plan')->where('receivables_id', $v)->value('plan_id')) {
                    $isDel = false;
                    $errorMessage[] = '名称为' . $data['number'] . '的回款删除失败,错误原因：回款已关联回款计划，不能删除！';
                    continue;
                }
            }
            if ($isDel) {
                $delIds[] = $v;
            }
        }
        if ($delIds) {
            $data = $receivablesModel->delDatas($delIds);
            if (!$data) {
                return resultArray(['error' => $receivablesModel->getError()]);
            }
            //删除跟进记录
            $recordModel->delDataByTypes(7,$delIds);
            # 删除附件
            $fileModel->delRFileByModule('crm_receivables', $delIds);
            //删除关联操作记录
            $actionRecordModel->delDataById(['types' => 'crm_receivables', 'action_id' => $delIds]);
            actionLog($delIds, '', '', '');
        }
        
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '删除成功']);
        }
    }
    
    /**
     * 回款审核
     * @param
     * @return
     * @author Michael_xu
     */
    public function check()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $receivablesModel = model('Receivables');
        $examineStepModel = new \app\admin\model\ExamineStep();
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $examineFlowModel = new \app\admin\model\ExamineFlow();
        
        $receivablesData = [];
        $receivablesData['update_time'] = time();
        $receivablesData['check_status'] = 1; //0待审核，1审核通中，2审核通过，3审核未通过
        //权限判断
        if (!$examineStepModel->checkExamine($user_id, 'crm_receivables', $param['id'])) {
            return resultArray(['error' => $examineStepModel->getError()]);
        };
        //审批主体详情
        $dataInfo = $receivablesModel->getDataById($param['id']);
        $flowInfo = $examineFlowModel->getDataById($dataInfo['flow_id']);
        $is_end = 0; // 1审批结束
        
        $status = $param['status'] ? 1 : 0; //1通过，0驳回
        $checkData = [];
        $checkData['check_user_id'] = $user_id;
        $checkData['types'] = 'crm_receivables';
        $checkData['types_id'] = $param['id'];
        $checkData['check_time'] = time();
        $checkData['content'] = $param['content'];
        $checkData['flow_id'] = $dataInfo['flow_id'];
        $checkData['order_id'] = $dataInfo['order_id'] ?: 1;
        $checkData['status'] = $status;
        
        if ($status == 1) {
            if ($flowInfo['config'] == 1) {
                //固定流程
                //获取下一审批信息
                $nextStepData = $examineStepModel->nextStepUser($dataInfo['owner_user_id'], $dataInfo['flow_id'], 'crm_receivables', $param['id'], $dataInfo['order_id'], $user_id);
                $next_user_ids = $nextStepData['next_user_ids'] ?: [];
                $receivablesData['order_id'] = $nextStepData['order_id'] ?: '';
                if (!$next_user_ids) {
                    $is_end = 1;
                    //审批结束
                    $checkData['check_status'] = !empty($status) ? 2 : 3;
                    $receivablesData['check_user_id'] = '';
                } else {
                    //修改主体相关审批信息
                    $receivablesData['check_user_id'] = arrayToString($next_user_ids);
                }
            } else {
                //自选流程
                $is_end = $param['is_end'] ? 1 : '';
                $check_user_id = $param['check_user_id'] ?: '';
                if ($is_end !== 1 && empty($check_user_id)) {
                    return resultArray(['error' => '请选择下一审批人']);
                }
                $receivablesData['check_user_id'] = arrayToString($param['check_user_id']);
            }
            if ($is_end == 1) {
                $checkData['check_status'] = !empty($status) ? 2 : 3;
                $receivablesData['check_user_id'] = '';
                $receivablesData['check_status'] = 2;
            }
        } else {
            //审批驳回
            $is_end = 1;
            $receivablesData['check_status'] = 3;
            //将审批记录至为无效
            // $examineRecordModel->setEnd(['types' => 'crm_receivables','types_id' => $param['id']]);                       
        }
        //已审批人ID
        $receivablesData['flow_user_id'] = stringToArray($dataInfo['flow_user_id']) ? arrayToString(array_merge(stringToArray($dataInfo['flow_user_id']), [$user_id])) : arrayToString([$user_id]);
        $resReceivables = db('crm_receivables')->where(['receivables_id' => $param['id']])->update($receivablesData);
        if ($resReceivables) {
            if ($status) {
                // 审批通过，通知下一审批人
                (new Message())->send(
                    Message::RECEIVABLES_TO_DO,
                    [
                        'from_user' => User::where(['id' => $dataInfo['owner_user_id']])->value('realname'),
                        'title' => $dataInfo['number'],
                        'action_id' => $param['id']
                    ],
                    stringToArray($receivablesData['check_user_id'])
                );
            } else {
                // 驳回通知负责人
                (new Message())->send(
                    Message::RECEIVABLES_REJECT,
                    [
                        'title' => $dataInfo['number'],
                        'action_id' => $param['id']
                    ],
                    $dataInfo['owner_user_id']
                );
            }
            
            //审批记录
            $resRecord = $examineRecordModel->createData($checkData);
            
            if ($is_end == 1 && !empty($status)) {
                //发送站内信 通过
                (new Message())->send(
                    Message::RECEIVABLES_PASS,
                    [
                        'title' => $dataInfo['number'],
                        'action_id' => $param['id']
                    ],
                    $dataInfo['owner_user_id']
                );
            }
            return resultArray(['data' => '审批成功']);
        } else {
            return resultArray(['error' => '审批失败，请重试！']);
        }
    }
    
    /**
     * 回款撤销审核
     * @param
     * @return
     * @author Michael_xu
     */
    public function revokeCheck()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $receivablesModel = model('Receivables');
        $examineStepModel = new \app\admin\model\ExamineStep();
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $customerModel = model('Customer');
        $userModel = new \app\admin\model\User();
        
        $receivablesData = [];
        $receivablesData['update_time'] = time();
        $receivablesData['check_status'] = 0; //0待审核，1审核通中，2审核通过，3审核未通过
        //审批主体详情
        $dataInfo = $receivablesModel->getDataById($param['id']);
        //权限判断(创建人或负责人或管理员)
        if ($dataInfo['check_status'] == 2) {
            return resultArray(['error' => '已审批结束,不能撤销']);
        }
        if ($dataInfo['check_status'] == 4) {
            return resultArray(['error' => '无需撤销']);
        }
        $admin_user_ids = $userModel->getAdminId();
        if ($dataInfo['owner_user_id'] !== $user_id && !in_array($user_id, $admin_user_ids)) {
            return resultArray(['error' => '没有权限']);
        }
        
        $is_end = 0; // 1审批结束
        $status = 2; //1通过，0驳回, 2撤销
        $checkData = [];
        $checkData['check_user_id'] = $user_id;
        $checkData['types'] = 'crm_receivables';
        $checkData['types_id'] = $param['id'];
        $checkData['check_time'] = time();
        $checkData['content'] = $param['content'];
        $checkData['flow_id'] = $dataInfo['flow_id'];
        $checkData['order_id'] = $dataInfo['order_id'];
        $checkData['status'] = $status;
        
        $receivablesData['check_status'] = 4;
        $receivablesData['check_user_id'] = '';
        $receivablesData['flow_user_id'] = '';
        $resReceivables = db('crm_receivables')->where(['receivables_id' => $param['id']])->update($receivablesData);
        if ($resReceivables) {
            //将审批记录至为无效
            // $examineRecordModel->setEnd(['types' => 'crm_receivables','types_id' => $param['id']]);
            //审批记录
            $resRecord = $examineRecordModel->createData($checkData);
            return resultArray(['data' => '撤销成功']);
        } else {
            return resultArray(['error' => '撤销失败，请重试！']);
        }
    }
    
    /**
     * 转移
     *
     * @return \think\response\Json
     */
    public function transfer()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $receivablesModel = model('Receivables');
        
        $userModel = new \app\admin\model\User();
        $authIds = $userModel->getUserByPer();
        
        if (empty($param['owner_user_id'])) return resultArray(['error' => '变更负责人不能为空']);
        if (empty($param['receivables_id']) || !is_array($param['receivables_id'])) return resultArray(['error' => '请选择需要转移的回款']);
        
        $owner_user_info = $userModel->getUserById($param['owner_user_id']);
        
        $data = [
            'owner_user_id' => $param['owner_user_id'],
            'update_time' => time(),
        ];
        
        $errorMessage = [];
        foreach ($param['receivables_id'] as $receivables_id) {
            $receivables_info = $receivablesModel->getDataById($receivables_id);
            if (!$receivables_info) {
                $errorMessage[] = 'id:为《' . $receivables_id . '》的回款转移失败，错误原因：数据不存在；';
                continue;
            }
            
            # 转移至当前负责人的直接跳过
            if ($param['owner_user_id'] == $receivables_info['owner_user_id']) continue;
            
            if (!in_array($receivables_info['owner_user_id'], $authIds)) {
                $errorMessage[] = $receivables_info['number'] . '"转移失败，错误原因：无权限；';
                continue;
            }
            
            if (in_array($receivables_info['check_status'], ['0', '1'])) {
                $errorMessage[] = $receivables_info['number'] . '转移失败，错误原因：待审或审批中，无法转移；';
                continue;
            }
            
            $res = $receivablesModel->where(['receivables_id' => $receivables_id])->update($data);
            if (!$res) {
                $errorMessage[] = $receivables_info['number'] . '"转移失败，错误原因：数据出错；';
                continue;
            }
            updateActionLog($userInfo['id'], 'crm_receivables', $receivables_id, '', '', '将回款转移给：' . $owner_user_info['realname']);
        }
        if (!$errorMessage) {
            return resultArray(['data' => '转移成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }
    
    /**
     * 系统信息
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function system()
    {
        if (empty($this->param['id'])) return resultArray(['error' => '参数错误！']);
        
        $receivablesModel = new \app\crm\model\Receivables();
        
        $data = $receivablesModel->getSystemInfo($this->param['id']);
        
        return resultArray(['data' => $data]);
    }
    
    /**
     * table标签栏数量
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function count()
    {
        if (empty($this->param['receivables_id'])) return resultArray(['error' => '参数错误！']);
        
        # 附件
        $fileCount = Db::name('crm_receivables_file')->alias('receivables')->join('__ADMIN_FILE__ file', 'file.file_id = receivables.file_id', 'LEFT')->where('receivables_id', $this->param['receivables_id'])->count();
        
        return resultArray(['data' => ['fileCount' => $fileCount]]);
    }
}
