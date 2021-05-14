<?php

namespace app\oa\logic;

use app\admin\model\Common;
use app\oa\model\Examine;
use think\Db;
use think\Validate;

class ExamineLogic extends Common
{
    private $statusArr = ['0' => '待审核', '1' => '审核中', '2' => '审核通过', '3' => '已拒绝', '4' => '已撤回'];
    
    /**
     * 导入数据查询
     * @param $request
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/2/27 0027 17:34
     */
    public function getDataList($request)
    {
        $userModel = new \app\admin\model\User();
        $fileModel = new \app\admin\model\File();
        $recordModel = new \app\admin\model\Record();
        
        $examine_by = $request['examine_by']; //1待我审批 2我已审批 all 全部
        $user_id = $request['user_id'];
        $bi = $request['bi_types'];
        $check_status = $request['check_status']; //0 待审批 2 审批通过 4 审批拒绝 all 全部
        unset($request['by']);
        unset($request['bi_types']);
        unset($request['user_id']);
        unset($request['check_status']);
        unset($request['examine_by']);
        $request = $this->fmtRequest($request);
        $map = $request['map'] ?: [];
        if (isset($map['search']) && $map['search']) {
            //普通筛选
            $map['examine.content'] = ['like', '%' . $map['search'] . '%'];
        } else {
            $map = where_arr($map, 'oa', 'examine', 'index'); //高级筛选
        }
        unset($map['search']);
        //审批类型
        $map['examine.category_id'] = $map['examine.category_id'] ?: array('gt', 0);
        
        $map_str = '';
        $logmap = '';
        switch ($examine_by) {
            case 'all' :
                //如果超管则能看到全部
                if (!isSuperAdministrators($user_id)) {
                    $map_str = "(( check_user_id LIKE '%," . $user_id . ",%' OR check_user_id = " . $user_id . " ) OR ( flow_user_id LIKE '%," . $user_id . ",%'  OR `flow_user_id` = " . $user_id . " ) )";
                }
                break;
            case '1' :
                $map['check_user_id'] = [['like', '%,' . $user_id . ',%']];
                break; //待审
            case '2' :
                $map_str = "(( check_user_id LIKE '%," . $user_id . ",%' OR check_user_id = " . $user_id . " )
                 OR ( flow_user_id LIKE '%," . $user_id . ",%'  OR `flow_user_id` = " . $user_id . " ) )";
//                $map['flow_user_id'] = [['like', '%,' . $user_id . ',%'], ['eq', $user_id], 'or'];
                break; //已审
            default:
                $map['examine.create_user_id'] = $user_id;
                break;
        }
        $order = 'examine.create_time desc,examine.update_time desc';
        //发起时间
        if ($map['examine.between_time'][0] && $map['examine.between_time'][1]) {
            $start_time = $map['examine.between_time'][0];
            $end_time = $map['examine.between_time'][1];
            $map['examine.create_time'] = array('between', array($start_time, $end_time));
        }
        unset($map['examine.between_time']);
        
        //审核状态 0 待审批 2 审批通过 4 审批拒绝 all 全部
        if (isset($check_status)) {
            if ($check_status == 'all') {
                $map['examine.check_status'] = ['egt', 0];
                if (isSuperAdministrators($user_id)) {
                    unset($map['examine.create_user_id']);
                }
            } elseif ($check_status == 4) {
                $map['examine.check_status'] = ['eq', 3];
            } elseif ($check_status == 0) {
                $map['examine.check_status'] = ['<=', 1];
            } else {
                $map['examine.check_status'] = $check_status;
            }
        } else {
            if ($examine_by == 'all') {
                $map['examine.check_status'] = ['egt', 0];
            } elseif ($examine_by == 1) {
                $map['examine.check_status'] = ['elt', 1];
            } elseif ($examine_by == 2) {
                $map['examine.check_status'] = ['egt', 2];
            }
        }
        $join = [
            ['__ADMIN_USER__ user', 'user.id = examine.create_user_id', 'LEFT'],
            ['__OA_EXAMINE_CATEGORY__ examine_category', 'examine_category.category_id = examine.category_id', 'LEFT'],
        ];
        $list_view = db('oa_examine')
            ->alias('examine')
            ->where($map_str)
            ->where($map)
            ->join($join);
        $res = [];
        $list = $list_view
            ->page($request['page'], $request['limit'])
            ->field('examine.*,user.realname,user.thumb_img,examine_category.title as category_name,examine_category.category_id as examine_config,examine_category.icon as examineIcon')
            ->order($order)
            ->select();
        foreach ($list as $k => $v) {
            $causeCount = 0;
            $causeTitle = '';
            $duration = $v['duration'] ?: '0.0';
            $money = $v['money'] ?: '0.00';
            
            $list[$k]['causeTitle'] = $causeTitle;
            $list[$k]['causeCount'] = $causeCount ?: 0;
            $item = db('oa_examine_travel')->where(['examine_id' => $v['examine_id']])->select();
            if ($item) {
                foreach ($item as $key => $value) {
                    if($v['check_status']==4){
                        $usernames = '';
                    }else{
                        $usernames = db('admin_user')->whereIn('id', stringToArray($v['check_user_id']))->column('realname');
                    }
                 
                    //关联业务
                    $relationArr = [];
                    $relationArr = $recordModel->getListByRelationId('examine', $v['examine_id']);
                    $item[$key]['relation'] = arrayToString(array_column($relationArr['businessList'], 'name')) . ' ' .
                        arrayToString(array_column($relationArr['contactsList'], 'name')) . ' ' .
                        arrayToString(array_column($relationArr['contractList'], 'name')) . ' ' .
                        arrayToString(array_column($relationArr['customerList'], 'name'));
                    $res[] = [
                        'category_name' => $v['category_name'],
                        'create_time' => !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : null,
                        'realname' => $v['realname'],
                        'check_status_info' => $this->statusArr[(int)$v['check_status']],
                        'examine_name' => implode($usernames, '，'),
                        'content' => $v['content'],
                        'remark' => $v['remark'],
                        'duration' => $v['duration'],
                        'vehicle' => $value['vehicle'],
                        'trip' => $value['trip'],
                        'money' => $value['money'],
                        'traffic' => $value['traffic'],
                        'stay' => $value['stay'],
                        'diet' => $value['diet'],
                        'other' => $value['other'],
                        'start_address' => $value['start_address'],
                        'end_address' => $value['end_address'],
                        'start_time' => !empty($value['start_time']) ? date('Y-m-d H:i:s', $value['start_time']) : null,
                        'end_time' => !empty($value['end_time']) ? date('Y-m-d H:i:s', $value['end_time']) : null,
                        'description' => $value['description'],
                        'replyList' => str_replace(',', ' ', $item[$key]['relation']),
                    ];
                }
            } else {
                $list[$k]['create_time'] = !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : null;
                $list[$k]['start_time'] = !empty($v['start_time']) ? date('Y-m-d H:i:s', $v['start_time']) : null;
                $list[$k]['end_time'] = !empty($v['end_time']) ? date('Y-m-d H:i:s', $v['end_time']) : null;
                if($v['check_status']==4){
                    $usernames = '';
                }else{
                    $usernames = db('admin_user')->whereIn('id', stringToArray($v['check_user_id']))->column('realname');
                }
                //关联业务
                $relationArr = [];
                $relationArr = $recordModel->getListByRelationId('examine', $v['examine_id']);
                $list[$k]['relation'] = arrayToString(array_column($relationArr['businessList'], 'name')) . ' ' .
                    arrayToString(array_column($relationArr['contactsList'], 'name')) . ' ' .
                    arrayToString(array_column($relationArr['contractList'], 'name')) . ' ' .
                    arrayToString(array_column($relationArr['customerList'], 'name'));
                $res[] = [
                    'category_name' => $v['category_name'],
                    'create_time' => !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : null,
                    'realname' => $v['realname'],
                    'check_status_info' => $this->statusArr[(int)$v['check_status']],
                    'examine_name' => implode($usernames, '，'),
                    'content' => $v['content'],
                    'remark' => $v['remark'],
                    'duration' => $v['duration'],
                    'vehicle' => '',
                    'money' => $v['money'],
                    'traffic' => '',
                    'stay' => '',
                    'diet' => '',
                    'other' => '',
                    'start_address' => '',
                    'end_address' => '',
                    'start_time' => !empty($v['start_time']) ? date('Y-m-d H:i:s', $v['start_time']) : null,
                    'end_time' => !empty($v['end_time']) ? date('Y-m-d H:i:s', $v['end_time']) : null,
                    'description' => '',
                    'replyList' => str_replace(',', ' ', $item[$key]['relation']),
                ];
            }
        }
        return $res;
    }
    
    /**
     * 审批导出
     * @param $param
     * @return mixed
     */
    public function excelExport($param)
    {
        $excelModel = new \app\admin\model\Excel();
        $data = $this->getDataList($param);
        $list = [];
        switch ($param['category_id']) {
            case '1' :
                $field_list = [
                    '0' => ['name' => '审批类型', 'field' => 'category_name'],
                    '1' => ['name' => '创建时间', 'field' => 'create_time'],
                    '2' => ['name' => '创建人', 'field' => 'realname'],
                    '3' => ['name' => '状态', 'field' => 'check_status_info'],
                    '4' => ['name' => '当前审批人', 'field' => 'examine_name'],
                    '5' => ['name' => '备注', 'field' => 'description'],
                    '6' => ['name' => '关联业务', 'field' => 'replyList'],
                ];
                break;
            case '2' :
                $field_list = [
                    '0' => ['name' => '审批类型', 'field' => 'category_name'],
                    '1' => ['name' => '创建时间', 'field' => 'create_time'],
                    '2' => ['name' => '创建人', 'field' => 'realname'],
                    '3' => ['name' => '状态', 'field' => 'check_status_info'],
                    '4' => ['name' => '当前审批人', 'field' => 'examine_name'],
                    '5' => ['name' => '审批内容', 'field' => 'content'],
                    '6' => ['name' => '开始时间', 'field' => 'start_time'],
                    '7' => ['name' => '结束时间', 'field' => 'end_time'],
                    '8' => ['name' => '时长', 'field' => 'duration'],
                    '9' => ['name' => '备注', 'field' => 'description'],
                    '10' => ['name' => '关联业务', 'field' => 'replyList'],
                ];
                break;
            case '3' :
                $field_list = [
                    '0' => ['name' => '审批类型', 'field' => 'category_name'],
                    '1' => ['name' => '创建时间', 'field' => 'create_time'],
                    '2' => ['name' => '创建人', 'field' => 'realname'],
                    '3' => ['name' => '状态', 'field' => 'check_status_info'],
                    '4' => ['name' => '当前审批人', 'field' => 'examine_name'],
                    '5' => ['name' => '出差事由', 'field' => 'content'],
                    '6' => ['name' => '备注', 'field' => 'remark'],
                    '7' => ['name' => '出差总天数', 'field' => 'duration'],
                    '8' => ['name' => '交通工具', 'field' => 'vehicle'],
                    '9' => ['name' => '单程往返', 'field' => 'trip'],
                    '10' => ['name' => '出发城市', 'field' => 'start_address'],
                    '11' => ['name' => '目的城市', 'field' => 'end_address'],
                    '12' => ['name' => '开始时间', 'field' => 'start_time'],
                    '13' => ['name' => '结束时间', 'field' => 'end_time'],
                    '14' => ['name' => '出差备注', 'field' => 'description'],
                    '15' => ['name' => '时长', 'field' => 'duration'],
                    '16' => ['name' => '关联业务', 'field' => 'replyList'],
                ];
                break;
            case '4' :
                $field_list = [
                    '0' => ['name' => '审批类型', 'field' => 'category_name'],
                    '1' => ['name' => '创建时间', 'field' => 'create_time'],
                    '2' => ['name' => '创建人', 'field' => 'realname',],
                    '3' => ['name' => '状态', 'field' => 'check_status_info'],
                    '4' => ['name' => '当前审批人', 'field' => 'examine_name'],
                    '5' => ['name' => '加班原因', 'field' => 'content'],
                    '6' => ['name' => '开始时间', 'field' => 'start_time'],
                    '7' => ['name' => '结束时间', 'field' => 'end_time'],
                    '8' => ['name' => '加班总天数', 'field' => 'duration'],
                    '9' => ['name' => '备注', 'field' => 'description'],
                    '10' => ['name' => '关联业务', 'field' => 'replyList'],
                ];
                break;
            case '5':
                $field_list = [
                    '0' => ['name' => '审批类型', 'field' => 'category_name'],
                    '1' => ['name' => '创建时间', 'field' => 'create_time'],
                    '2' => ['name' => '创建人', 'field' => 'realname'],
                    '3' => ['name' => '状态', 'field' => 'check_status_info'],
                    '4' => ['name' => '当前审批人', 'field' => 'examine_name'],
                    '5' => ['name' => '差旅内容', 'field' => 'content'],
                    '6' => ['name' => '报销总金额', 'field' => 'money'],
                    '7' => ['name' => '备注', 'field' => 'remark'],
                    '8' => ['name' => '出发城市', 'field' => 'start_address'],
                    '9' => ['name' => '目的城市', 'field' => 'end_address'],
                    '10' => ['name' => '开始时间', 'field' => 'start_time'],
                    '11' => ['name' => '结束时间', 'field' => 'end_time'],
                    '12' => ['name' => '交通费', 'field' => 'traffic'],
                    '13' => ['name' => '住宿费', 'field' => 'stay'],
                    '14' => ['name' => '餐饮费', 'field' => 'diet'],
                    '15' => ['name' => '其他费用', 'field' => 'other'],
                    '16' => ['name' => '合计', 'field' => 'money'],
                    '17' => ['name' => '费用明细描述', 'field' => 'description'],
                    '18' => ['name' => '关联业务', 'field' => 'relation'],
                ];
                break;
            case '6' :
                $field_list = [
                    '0' => ['name' => '审批类型', 'field' => 'category_name'],
                    '1' => ['name' => '创建时间', 'field' => 'create_time'],
                    '2' => ['name' => '创建人', 'field' => 'realname'],
                    '3' => ['name' => '状态', 'field' => 'check_status_info'],
                    '4' => ['name' => '当前审批人', 'field' => 'examine_name'],
                    '5' => ['name' => '借款事由', 'field' => 'content'],
                    '6' => ['name' => '开始时间', 'field' => 'start_time'],
                    '7' => ['name' => '结束时间', 'field' => 'end_time'],
                    '8' => ['name' => '借款金额', 'field' => 'money'],
                    '9' => ['name' => '备注', 'field' => 'description'],
                    '10' => ['name' => '关联业务', 'field' => 'replyList'],
                ];
                break;
            default :
                $field_list = [
                    '0' => ['name' => '审批类型', 'field' => 'category_name'],
                    '1' => ['name' => '创建时间', 'field' => 'create_time'],
                    '2' => ['name' => '创建人', 'field' => 'realname'],
                    '3' => ['name' => '状态', 'field' => 'check_status_info'],
                    '4' => ['name' => '当前审批人', 'field' => 'examine_name'],
                    '5' => ['name' => '备注', 'field' => 'description'],
                    '6' => ['name' => '关联业务', 'field' => 'replyList'],
                    
                ];
        }
        $file_name = 'oa_examine';
        return $excelModel->dataExportCsv($file_name, $field_list, $data);
    }
    
    /**
     * 审批数据
     * @param $param
     */
    public function myExamine($param)
    {
        $param['status'] = $param['status'] == 'all' ? 3 : $param['status'];
        $auth_user_ids = getSubUserId(true, 0, $param['user_id']);
        $user_id = $param['user_id'];
        $where = [];
        $whereOr = [];
        if ($param['status'] == 0) {
            $where['a.check_status'] = ['elt', 1];
            $whereOr = function ($query) use ($user_id) {
                $query->where('a.check_user_id', ['like', '%' . $user_id . '%'])->whereOr('a.flow_user_id', ['like', '%' . $user_id . '%']);
            };
        } elseif ($param['status'] == 1) {
            $where['a.check_status'] = ['in', [2, 3]];
            $whereOr = function ($query) use ($user_id) {
                $query->where('a.check_user_id', ['like', '%' . $user_id . '%'])->whereOr('a.flow_user_id', ['like', '%' . $user_id . '%']);
            };
        } elseif ($param['status'] == 3) {
            $where['a.check_status'] = ['lt', 5];
            $whereOr = function ($query) use ($user_id) {
                $query->where('a.check_user_id', ['like', '%' . $user_id . '%'])->whereOr('a.flow_user_id', ['like', '%' . $user_id . '%']);
            };
        }
        $userModel = new \app\admin\model\User();
        switch ($param['type']) {
            case '1':
                //合同
                $list = db('crm_contract')
                    ->alias('a')
                    ->join('__CRM_CUSTOMER__ customer', 'a.customer_id = customer.customer_id', 'LEFT')
                    ->join('__ADMIN_USER__ user', 'user.id = a.create_user_id', 'LEFT')
                    ->join('__ADMIN_EXAMINE_FLOW__ examine_flow', 'examine_flow.flow_id = a.flow_id', 'LEFT')
                    ->where($where)
                    ->where($whereOr)
                    ->field(
                        'a.contract_id as catagory_id,a.name,a.create_time,a.check_status,a.create_user_id,a.check_user_id,a.flow_user_id,
                        customer.name as customer_name,
                       a.customer_id,user.realname,examine_flow.name as examine_name'
                    )
                    ->page($param['page'], $param['limit'])
                    ->order('a.create_time desc')
                    ->select();
                foreach ($list as $k => $v) {
                    $list[$k]['customer_id_info']['customer_id'] = $v['customer_id'];
                    $list[$k]['customer_id_info']['name'] = $v['customer_name'];
                    $list[$k]['create_user_info'] = $userModel->getUserById($v['create_user_id']);
                    
                }
                $dataCount = db('crm_contract')
                    ->alias('a')
                    ->join('__CRM_CUSTOMER__ customer', 'a.customer_id = customer.customer_id', 'LEFT')
                    ->join('__ADMIN_USER__ user', 'user.id = a.create_user_id', 'LEFT')
                    ->where($where)
                    ->where($whereOr)
                    ->count();
                break;
            case '2':
                //回款
                $list = db('crm_receivables')
                    ->alias('a')
                    ->join('__ADMIN_USER__ user', 'user.id = a.create_user_id', 'LEFT')
                    ->join('__ADMIN_EXAMINE_FLOW__ examine_flow', 'examine_flow.flow_id = a.flow_id', 'LEFT')
                    ->join('__CRM_CONTRACT__ contract', 'a.contract_id = contract.contract_id', 'LEFT')
                    ->where($where)
                    ->where($whereOr)
                    ->field(
                        'a.receivables_id as catagory_id ,a.number as name,a.create_time,a.check_status,a.check_user_id,a.flow_user_id,
                       contract.name as contract_name,a.create_user_id,a.contract_id,user.realname,examine_flow.name as examine_name'
                    )
                    ->page($param['page'], $param['limit'])
                    ->order('a.create_time desc')
                    ->select();
                $dataCount = db('crm_receivables')
                    ->alias('a')
                    ->join('__ADMIN_USER__ user', 'user.id = a.create_user_id', 'LEFT')
                    ->join('__CRM_CONTRACT__ contract', 'a.contract_id = contract.contract_id', 'LEFT')
                    ->where($where)
                    ->where($whereOr)
                    ->count();
                foreach ($list as $k => $v) {
                    $list[$k]['create_user_info'] = $userModel->getUserById($v['create_user_id']);
                    $list[$k]['contract_id_info']['contract_id'] = $v['contract_id'];
                    $list[$k]['contract_id_info']['name'] = $v['contract_name'];
                    
                }
                break;
            case '3':
                $list = db('crm_invoice')
                    ->alias('a')
                    ->join('__ADMIN_USER__ user', 'user.id = a.create_user_id', 'LEFT')
                    ->join('__ADMIN_EXAMINE_FLOW__ examine_flow', 'examine_flow.flow_id = a.flow_id', 'LEFT')
                    ->where($where)
                    ->where($whereOr)
                    ->field(
                        'a.invoice_id as catagory_id ,a.invoice_apple_number as name,a.create_time,a.check_status,a.create_user_id,a.check_user_id,a.flow_user_id,user.realname,examine_flow.name as examine_name'
                    )
                    ->page($param['page'], $param['limit'])
                    ->order('a.create_time desc')
                    ->select();
                foreach ($list as $k => $v) {
                    $list[$k]['create_user_info'] = $userModel->getUserById($v['create_user_id']);
                    
                }
                $dataCount = db('crm_invoice')
                    ->alias('a')
                    ->join('__ADMIN_USER__ user', 'user.id = a.create_user_id', 'LEFT')
                    ->where($where)
                    ->where($whereOr)
                    ->count();
                break;
            
        }
        
        foreach ($list as $key => $v) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $v['create_time']) ?: '';
        }
        
        $data = [];
        $data['page']['list'] = $list;
        $data['page']['dataCount'] = $dataCount ?: 0;
        if ($param['page'] != 1 && ($param['page'] * $param['limit']) >= $dataCount) {
            $data['page']['firstPage'] = false;
            $data['page']['lastPage'] = true;
        } else if ($param['page'] != 1 && (int)($param['page'] * $param['limit']) < $dataCount) {
            $data['page']['firstPage'] = false;
            $data['page']['lastPage'] = false;
        } else if ($param['page'] == 1) {
            $data['page']['firstPage'] = true;
            $data['page']['lastPage'] = false;
        }
        return $data;
    }
    
    /**
     * @param $workIds
     * @param $userId
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/16 0016 14:00
     */
    public function setWorkOrder($examineIds, $userId)
    {
        $data = [];
        
        foreach ($examineIds AS $key => $value) {
            $data[] = [
                'work_id' => $value,
                'user_id' => $userId,
                'order'   => $key + 1
            ];
        }
        if (!empty($data)) {
            if (db('oa_examine_order')->where('user_id', $userId)->delete() === false) return false;
            if (db('oa_examine_order')->insertAll($data) === false) return false;
        }
        
        return true;
    }
    
}