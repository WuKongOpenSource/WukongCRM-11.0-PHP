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
     * 审批导出
     * @param $param
     * @return mixed
     */
    public function excelExport($param)
    {
        $excelModel = new \app\admin\model\Excel();
        $field_list1 = [
            array('name' => '审批类型', 'field' => 'category_name', 'form_type' => 'text'),
            array('name' => '创建时间', 'field' => 'create_time', 'form_type' => ''),
            array('name' => '创建人', 'field' => 'create_user_id', 'form_type' => 'user'),
            array('name' => '状态', 'field' => 'check_status_info','form_type' => ''),
            array('name' => '当前审批人', 'field' => 'check_user_id','form_type' => 'userStr'),
            // array('name' => '下一审批人', 'field' => 'last_user_id','form_type' => 'user'),
            // array('name' => '关联业务', 'field' => 'relation','form_type' => ''),
        ];
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldConfig('oa_examine', $param['user_id'], $param['category_id']);
        $file_name = 'oa_examine';
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        $model = model('Examine');
        $field_list = array_merge_recursive($field_list1, $field_list);

        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function ($page, $limit) use ($model, $param, $field_list) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = $model->getDataList($param);
            // $newData['list'] = $model->exportHandle($data['page']['list'], $field_list, 'oa_examine');
            $newData['list'] = $data['page']['list'];
            return $newData;
        });
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
            $where['a.check_status'] = ['in', [2,3]];
            $whereOr = function ($query) use ( $user_id) {
                $query->where('a.check_user_id',['like', '%' . $user_id . '%'])->whereOr('a.flow_user_id', ['like', '%' . $user_id . '%']);
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

}