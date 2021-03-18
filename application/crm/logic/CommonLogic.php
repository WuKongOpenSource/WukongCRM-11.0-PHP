<?php
/**
 * crm模块下的通用功能逻辑类
 *
 * @author qifan
 * @date 2020-12-11
 */

namespace app\crm\logic;

use app\admin\controller\ApiCommon;
use app\admin\model\User;
use app\crm\model\Customer;
use think\Db;
use think\Validate;

class CommonLogic
{
    public $error = '操作失败！';
    
    /**
     * 快捷编辑【线索、客户、联系人、商机、合同、回款、回访、产品】
     *
     * @param $param
     * @return false|int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function quickEdit($param)
    {
        /**
         * $param['types']     表名
         * $param['action_id'] 主键ID
         * $param['field']     字段
         * $param['name']      字段中文名，用作提示
         * $param['value]      字段值
         */
        
        $actionId = $param['action_id'];
        $types = $param['types'];
        unset($param['action_id']);
        unset($param['types']);
        
        # 模型
        $model = db($types);
        
        # 主键
        $primaryKey = '';
       // author      guogaobo $item模块
        $info='';
        switch ($types) {
            case 'crm_leads' :
                $primaryKey = 'leads_id';
                $dataModel=new \app\crm\model\Leads();
                $info=$dataModel->getDataById($actionId);
                break;
            case 'crm_customer' :
                $primaryKey = 'customer_id';
                $info=db('crm_customer')->where('customer_id',$actionId)->find();
                break;
            case 'crm_contacts' :
                $primaryKey = 'contacts_id';
                $dataModel=new \app\crm\model\Contacts();
                $info=$dataModel->getDataById($actionId);
                break;
            case 'crm_business' :
                $primaryKey = 'business_id';
                $dataModel=new \app\crm\model\Business();
                $info=$dataModel->getDataById($actionId);
                break;
            case 'crm_contract' :
                $primaryKey = 'contract_id';
                $info=db('crm_contract')->where('customer_id',$actionId)->find();
                break;
            case 'crm_receivables' :
                $primaryKey = 'receivables_id';
                $info=db('crm_receivables')->where('customer_id',$actionId)->find();
                break;
            case 'crm_visit' :
                $primaryKey = 'visit_id';
                $dataModel=new \app\crm\logic\VisitLogic();
                $info=$dataModel->getDataById($actionId);
                break;
            case 'crm_product' :
                $primaryKey = 'product_id';
                $dataModel=new \app\crm\model\Product();
                $info=$dataModel->getDataById($actionId);
                break;
        }
        $apiCommon = new ApiCommon();
        $userModel = new User();


        if (in_array($types, ['crm_contract', 'crm_receivables'])) {
            $checkStatus = $model->where($primaryKey, $actionId)->value('check_status');
            if (!in_array($checkStatus, [4, 5, 6])) {
                $this->error = '只能编辑状态为撤销、草稿或作废的信息！';
                return false;
            }
        }
        # 产品修改验证
        if($types == 'crm_product'){
            foreach ($param['list'] as $val){
                $infoData=db('crm_product')->where(['name'=>$val['name'],'delete_user_id'=>0])->find();
                if(!empty($infoData)){
                    $fieldModel = new \app\admin\model\Field();
                    $validateArr = $fieldModel->validateField('crm_product'); //获取自定义字段验证规则
                    $validate = new Validate($validateArr['rule'], $validateArr['message']);
                    $result = $validate->check($val);
                    if (!$result) {
                        $this->error = $validate->getError();
                        return false;
                    }
                }
            }
        }

        # 客户模块快捷编辑权限验证
        if ($types == 'crm_customer') {
            $dataInfo = $model->field(['ro_user_id', 'rw_user_id', 'owner_user_id'])->where($primaryKey, $actionId)->find();
            $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'update');
            $rwPre = $userModel->rwPre($apiCommon->userInfo['id'], $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
            $wherePool = (new Customer())->getWhereByPool();
            $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $param['action_id']])->where($wherePool)->find();
            if ($resPool || (!in_array($dataInfo['owner_user_id'], $auth_user_ids) && !$rwPre)) {
                $this->error = '无权操作！';
                return false;
            }
        }
        
        # 商机模块快捷编辑权限验证
        if ($types == 'crm_business') {
            $dataInfo = $model->field(['ro_user_id', 'rw_user_id', 'owner_user_id'])->where($primaryKey, $actionId)->find();
            $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'update');
            $rwPre = $userModel->rwPre($apiCommon->userInfo['id'], $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
            if (!in_array($dataInfo['owner_user_id'], $auth_user_ids) && !$rwPre) {
                $this->error = '无权操作！';
                return false;
            }
        }
        
        # 合同模块快捷编辑权限验证
        if ($types == 'crm_contract') {
            $dataInfo = $model->field(['ro_user_id', 'rw_user_id', 'owner_user_id'])->where($primaryKey, $actionId)->find();
            $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'update');
            $rwPre = $userModel->rwPre($apiCommon->userInfo['id'], $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');
            if (!in_array($dataInfo['owner_user_id'], $auth_user_ids) && !$rwPre) {
                $this->error = '无权操作！';
                return false;
            }
        }

        $fieldModel = new \app\admin\model\Field();
        # 日期时间类型
        $datetimeField = $fieldModel->getFieldByFormType($types, 'datetime');
        # 附件类型
        $fileField = $fieldModel->getFieldByFormType($types, 'file');
        # 多选类型
        $checkboxField = $fieldModel->getFieldByFormType($types, 'checkbox');
        # 人员类型
        $userField = $fieldModel->getFieldByFormType($types, 'user');
        # 部门类型
        $structureField = $fieldModel->getFieldByFormType($types, 'structure');

        foreach ($param['list'] as $key => $value) {
            foreach ($value as $k => $v) {
                # 查询自定义字段信息
                $fieldInfo = Db::name('admin_field')->field(['max_length', 'is_unique', 'is_null', 'name'])
                    ->where('types', $types)->where('field', $k)->find();

                # 字符长度
                if (!empty($fieldInfo['max_length']) && strlen($v) > $fieldInfo['max_length']) {
                    $this->error = $fieldInfo['name'] . ' 字符长度不能超过 ' . $fieldInfo['max_length'] . ' 个字符！';
                    return false;
                }

                # 必填
                if (!empty($fieldInfo['is_null']) && empty($v)) {
                    $this->error = $fieldInfo['name'] . ' 是必填信息，不能为空！';
                    return false;
                }

                # 唯一
                if (!empty($fieldInfo['is_unique']) && $model->where([$primaryKey => ['neq', $actionId]])->where($k, $v)->value($primaryKey)) {
                    $this->error = $fieldInfo['name'] . ' 内容重复！';
                    return false;
                }
            }
        }
        
        # 编辑参数
        $data = [];
        if (!empty($param['list'])) {
            foreach ($param['list'] as $key => $value) {
                foreach ($value as $k => $v) {
                    if ($k == 'next_time' || in_array($k, $datetimeField)) {
                        # 处理下次联系时间格式、datetime类型数据
                        $data[$k] = !empty($v) ? strtotime($v) : '';
                    } elseif ($types == 'crm_product' && $k == 'category_id') {
                        # 处理产品类别
                        $categorys = explode(',', $v);
                        $data[$k] = $categorys[count($categorys) - 1];
                    } elseif (in_array($k, $fileField) || in_array($k, $checkboxField) || in_array($k, $userField) || in_array($k, $structureField)) {
                        # 处理附件、多选、人员、部门类型数据
                        $data[$k] = !empty($v) ? arrayToString($v) : '';
                    } elseif ($types == 'crm_visit' && $k == 'contract_id') {
                        # 处理回访提交过来的合同编号
                        if (!empty($v[0]['contract_id'])) $data[$k] = $v[0]['contract_id'];
                    } else {
                        $data[$k] = $v;
                    }
                    $item[$k]=$v;
                }
            }
            $data[$primaryKey]   = $actionId;
            $data['update_time'] = time();
        }
        $res = $model->update($data);
        unset($data[$primaryKey]);
        unset($data['update_time']);
        //详细信息修改新增操作记录
        if ($res) {
            //修改记录
            $user_id = $apiCommon->userInfo;
            updateActionLog($user_id['id'], $types, $actionId, $info, $data);
        }
        return $res;
    }
}