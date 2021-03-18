<?php
// +----------------------------------------------------------------------
// | Description: 客户
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\logic\CustomerLogic;
use app\crm\traits\SearchConditionTrait;
use app\crm\traits\StarTrait;
use think\Hook;
use think\Request;
use think\Db;

class Customer extends ApiCommon
{
    use StarTrait, SearchConditionTrait;
    
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     **/
    public function _initialize()
    {
        $action = [
            'permission' => ['exceldownload', 'setfollow', 'delete'],
            'allow' => ['read', 'system', 'count', 'poolauthority']
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        } else {
            $param = Request::instance()->param();
            $this->param = $param;
        }
    }
    
    /**
     * 客户列表
     * @return
     * @author Michael_xu
     */
    public function index()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $customerModel->getDataList($param);
        return resultArray(['data' => $data]);
    }
    
    /**
     * 客户公海(没有负责人或已经到期)
     * @return
     * @author Michael_xu
     */
    public function pool()
    {
        $param = $this->param;
        $param['action'] = 'pool';
        unset($param['poolId']); # todo uniApp传来的参数，临时删除掉 fanqi。
        $data = model('Customer')->getDataList($param);
        return resultArray(['data' => $data]);
    }
    
    /**
     * 添加客户
     * @param
     * @return
     * @author Michael_xu
     */
    public function save()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];
        if ($res = $customerModel->createData($param)) {
            return resultArray(['data' => $res]);
        } else {
            return resultArray(['error' => $customerModel->getError()]);
        }
    }
    
    /**
     * 客户详情
     * @param
     * @return
     * @author Michael_xu
     */
    public function read()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $customerModel->getDataById($param['id'], $userInfo['id']);
        if (!$data) {
            return resultArray(['error' => $customerModel->getError()]);
        }
        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'read');
        //读权限
        $roPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'read');
        $rwPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'update');
        //判断是否客户池数据
        $wherePool = $customerModel->getWhereByPool();
        $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $param['id']])->where($wherePool)->find();
        if (!$resPool && !in_array($data['owner_user_id'], $auth_user_ids) && !$roPre && !$rwPre) {
            $authData['dataAuth'] = (int)0;
            return resultArray(['data' => $authData]);
        }
        return resultArray(['data' => $data]);
    }
    
    /**
     * 编辑客户
     * @param
     * @return
     * @author Michael_xu
     */
    public function update()
    {
        $customerModel = model('Customer');
        $param = $this->param;
        $userInfo = $this->userInfo;
        //数据详情
        $data = $customerModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $customerModel->getError()]);
        }
        
        $param['user_id'] = $userInfo['id'];
        if ($customerModel->updateDataById($param, $param['id'])) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $customerModel->getError()]);
        }
    }
    
    /**
     * 删除客户
     * @param
     * @return
     * @author Michael_xu
     */
    public function delete()
    {
        $param = $this->param;
        // 是否客户池
        if ($param['isSeas'] == 1) {
            $permission = checkPerByAction('crm', 'customer', 'poolDelete');
        } else {
            $permission = checkPerByAction('crm', 'customer', 'delete');
        }
        if ($permission == false) {
            return resultArray(['error' => '无权操作']);
        }
        $customerModel = model('Customer');
        $userModel = new \app\admin\model\User();
        $recordModel = new \app\admin\model\Record();
        $fileModel = new \app\admin\model\File();
        $actionRecordModel = new \app\admin\model\ActionRecord();
        if (!is_array($param['id'])) {
            $customer_id[] = $param['id'];
        } else {
            $customer_id = $param['id'];
        }
        $delIds = [];
        $errorMessage = [];
        
        //数据权限判断
        $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'delete');
        //判断是否客户池数据(客户池数据只有管理员可以删)
        $adminId = $userModel->getAdminId();
        $wherePool = $customerModel->getWhereByPool();
        foreach ($customer_id as $k => $v) {
            $isDel = true;
            //数据详情
            $data = db('crm_customer')->where(['customer_id' => $v])->find();
            if (!$data) {
                $isDel = false;
                $errorMessage[] = 'id为' . $v . '的客户删除失败,错误原因：' . $customerModel->getError();
            }
            $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $v])->where($wherePool)->find();
            if (!$resPool && !in_array($data['owner_user_id'], $auth_user_ids) && $isDel) {
                $isDel = false;
                $errorMessage[] = '名称为' . $data['name'] . '的客户删除失败,错误原因：无权操作';
            }
            // 公海 (原逻辑，公海仅允许管理员删除，修改为授权，不再限制)
            // if ($resPool && !in_array($data['owner_user_id'],$adminId)) {
            //     $isDel = false;
            //     $errorMessage[] = '名称为'.$data['name'].'的客户删除失败,错误原因：无权操作';
            // }
            //有商机、合同、联系人则不能删除 
            if ($isDel) {
                $resBusiness = db('crm_business')->where(['customer_id' => $v])->find();
                if ($resBusiness) {
                    $isDel = false;
                    $errorMessage[] = '名称为' . $data['name'] . '的客户删除失败,错误原因：客户下存在商机，不能删除';
                }
            }
            if ($isDel) {
                $resContacts = db('crm_contacts')->where(['customer_id' => $v])->find();
                if ($resContacts) {
                    $isDel = false;
                    $errorMessage[] = '名称为' . $data['name'] . '的客户删除失败,错误原因：客户下存在联系人，不能删除';
                }
            }
            if ($isDel) {
                $resContract = db('crm_contract')->where(['customer_id' => $v])->find();
                if ($resContract) {
                    $isDel = false;
                    $errorMessage[] = '名称为' . $data['name'] . '的客户删除失败,错误原因：客户下存在合同，不能删除';
                }
            }
            if ($isDel) {
                $delIds[] = $v;
            }
        }
        if ($delIds) {
            $delRes = $customerModel->delDatas($delIds);
            if (!$delRes) {
                return resultArray(['error' => $customerModel->getError()]);
            }
            //删除跟进记录
            $recordModel->delDataByTypes(2, $delIds);
            //删除关联附件
            $fileModel->delRFileByModule('crm_customer', $delIds);
            //删除关联操作记录
            $actionRecordModel->delDataById(['types' => 'crm_customer', 'action_id' => $delIds]);
            actionLog($delIds, '', '', '');
        }
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '删除成功']);
        }
    }
    
    /**
     * 客户转移
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @param types business,contract 相关模块
     * @param type 权限 1只读2读写
     * @return
     * @author Michael_xu
     */
    public function transfer()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $businessModel = model('Business');
        $contractModel = model('Contract');
        $contactsModel = model('Contacts');
        $settingModel = model('Setting');
        $customerConfigModel = model('CustomerConfig');
        $userModel = new \app\admin\model\User();
        
        if (!$param['owner_user_id']) {
            return resultArray(['error' => '变更负责人不能为空']);
        }
        if (!$param['customer_id'] || !is_array($param['customer_id'])) {
            return resultArray(['error' => '请选择需要转移的客户']);
        }
        $is_remove = ($param['is_remove'] == 2) ? 2 : 1;
        $type = $param['type'] == 2 ?: 1;
        $types = $param['types'] ?: [];
        
        $data = [];
        $data['owner_user_id'] = $param['owner_user_id'];
        $data['update_time'] = time();
        $data['follow'] = '待跟进';
        # 获取客户的时间
        $data['obtain_time'] = time();
        
        $ownerUserName = $userModel->getUserNameById($param['owner_user_id']);
        $errorMessage = [];
        foreach ($param['customer_id'] as $customer_id) {
            $customerInfo = db('crm_customer')->where(['customer_id' => $customer_id])->find();
            if (!$customerInfo) {
                $errorMessage[] = '名称:为《' . $customerInfo['name'] . '》的客户转移失败，错误原因：数据不存在；';
                continue;
            }
            $resCustomer = true;
            //权限判断
            if (!$customerModel->checkData($customer_id)) {
                $errorMessage[] = $customerInfo['name'] . '转移失败，错误原因：无权限；';
                continue;
            }
            //拥有客户数上限检测
            if (!$customerConfigModel->checkData($param['owner_user_id'], 1)) {
                $errorMessage[] = $customerInfo['name'] . '转移失败，错误原因：' . $customerConfigModel->getError();
                continue;
            }
            
            //团队成员
            $teamData = [];
            $teamData['type'] = $type; //权限 1只读2读写
            $teamData['user_id'] = [$customerInfo['owner_user_id']]; //协作人
            $teamData['types'] = 'crm_customer'; //类型
            $teamData['types_id'] = $customer_id; //类型ID
            $teamData['is_del'] = ($is_remove == 1) ? 1 : '';
            $res = $settingModel->createTeamData($teamData);

            # 处理分配标识，待办事项专用
            $data['is_allocation'] = 1;
            
            $resCustomer = db('crm_customer')->where(['customer_id' => $customer_id])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = $customerInfo['name'] . '转移失败，错误原因：数据出错；';
                continue;
            } else {
                # 处理转移时，负责人出现在只读和读写成员列表中
                $customerArray = [];
                $teamCustomer = db('crm_customer')->field(['owner_user_id', 'ro_user_id', 'rw_user_id'])->where('customer_id', $customer_id)->find();
                if (!empty($teamCustomer['ro_user_id'])) {
                    $customerRo = arrayToString(array_diff(stringToArray($teamCustomer['ro_user_id']), [$teamCustomer['owner_user_id']]));
                    $customerArray['ro_user_id'] = $customerRo;
                }
                if (!empty($teamCustomer['rw_user_id'])) {
                    $customerRo = arrayToString(array_diff(stringToArray($teamCustomer['rw_user_id']), [$teamCustomer['owner_user_id']]));
                    $customerArray['rw_user_id'] = $customerRo;
                }
                db('crm_customer')->where('customer_id', $customer_id)->update($customerArray);
            }
            
            if (in_array('crm_contacts', $types)) {
                $contactsIds = [];
                $contactsIds = db('crm_contacts')->where(['customer_id' => $customer_id])->column('contacts_id');
                if ($contactsIds) {
                    $resContacts = $contactsModel->transferDataById($contactsIds, $param['owner_user_id'], $type, $is_remove);
                    if ($resContacts !== true) {
                        $errorMessage[] = $resContacts;
                        continue;
                    }
                }
            }
            
            //商机、合同转移
            if (in_array('crm_business', $types)) {
                $businessIds = [];
                $businessIds = db('crm_business')->where(['customer_id' => $customer_id])->column('business_id');
                if ($businessIds) {
                    $resBusiness = $businessModel->transferDataById($businessIds, $param['owner_user_id'], $type, $is_remove);
                    if ($resBusiness !== true) {
                        $errorMessage = $errorMessage ? array_merge($errorMessage, $resBusiness) : $resBusiness;
                        continue;
                    }
                }
            }
            
            if (in_array('crm_contract', $types)) {
                $contractIds = [];
                $contractIds = db('crm_contract')->where(['customer_id' => $customer_id])->column('contract_id');
                if ($contractIds) {
                    $resContract = $contractModel->transferDataById($contractIds, $param['owner_user_id'], $type, $is_remove);
                    if ($resContract !== true) {
                        $errorMessage = $errorMessage ? array_merge($errorMessage, $resContract) : $resContract;
                        continue;
                    }
                }
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $customer_id, '', '', '将客户转移给：' . $ownerUserName);
        }
        if (!$errorMessage) {
            return resultArray(['data' => '转移成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }
    
    /**
     * 客户放入公海(负责人置为0)
     * @param
     * @return
     * @author Michael_xu
     */
    public function putInPool()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $settingModel = new \app\crm\model\Setting();
        if (!$param['customer_id'] || !is_array($param['customer_id'])) {
            return resultArray(['error' => '请选择需要放入公海的客户']);
        }
        $data = [];
        $data['owner_user_id'] = 0;
        $data['is_lock'] = 0;
        $data['update_time'] = time();
        $errorMessage = [];
        foreach ($param['customer_id'] as $customer_id) {
            $customerInfo = [];
            $customerInfo = db('crm_customer')->where(['customer_id' => $customer_id])->find();
            if (!$customerInfo) {
                $errorMessage[] = '名称:为《' . $customerInfo['name'] . '》的客户放入公海失败，错误原因：数据不存在；';
                continue;
            }
            //权限判断
            if (!$customerModel->checkData($customer_id)) {
                $errorMessage[] = '"' . $customerInfo['name'] . '"放入公海失败，错误原因：无权限';
                continue;
            }
            //将团队成员全部清除
            $data['ro_user_id'] = '';
            $data['rw_user_id'] = '';
            $resCustomer = db('crm_customer')->where(['customer_id' => $customer_id])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = '"' . $customerInfo['name'] . '"放入公海失败，错误原因：数据出错；';
                continue;
            }
            //联系人负责人清除
            db('crm_contacts')->where(['customer_id' => $customer_id])->update(['owner_user_id' => 0]);
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $customer_id, '', '', '将客户放入公海');
        }
        if (!$errorMessage) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }
    
    /**
     * 客户锁定，解锁
     * @param is_lock 1锁定，2解锁
     * @return
     * @author Michael_xu
     */
    public function lock()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $customerConfigModel = model('CustomerConfig');
        $is_lock = ((int)$param['is_lock'] == 2) ? (int)$param['is_lock'] : 1;
        $lock_name = ($is_lock == 2) ? '解锁' : '锁定';
        if (!$param['customer_id'] || !is_array($param['customer_id'])) {
            return resultArray(['error' => '请选择需要' . $lock_name . '的客户']);
        }
        $data = [];
        $data['is_lock'] = ($is_lock == 1) ? $is_lock : 0;
        $data['update_time'] = time();
        $errorMessage = [];
        foreach ($param['customer_id'] as $customer_id) {
            $customerInfo = [];
            $customerInfo = $customerModel->getDataById($customer_id);
            if (!$customerInfo) {
                $errorMessage[] = '名称:为《' . $customerInfo['name'] . '》的客户' . $lock_name . '失败，错误原因：数据不存在；';
                continue;
            }
            //权限判断
            if (!$customerModel->checkData($customer_id)) {
                $errorMessage[] = $customerInfo['name'] . $lock_name . '失败，错误原因：无权限';
                continue;
            }
            //锁定上限检测
            if ($is_lock == 1 && !$customerConfigModel->checkData($customerInfo['owner_user_id'], 2)) {
                $errorMessage[] = $customerInfo['name'] . $lock_name . '失败，错误原因：' . $customerConfigModel->getError();
                continue;
            }
            //已成交客户，锁定，提示无需锁定
            // if ($customerInfo['deal_status'] == '已成交' && $is_lock == 1) {
            //     $errorMessage[] = $customerInfo['name'].$lock_name.'失败，错误原因：已成交状态，无需锁定';
            //     continue;                 
            // }         
            $resCustomer = db('crm_customer')->where(['customer_id' => $customer_id])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = $customerInfo['name'] . $lock_name . '失败，错误原因：数据出错；';
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $customer_id, '', '', '将客户' . $lock_name);
        }
        if (!$errorMessage) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }
    
    /**
     * 客户领取
     * @param
     * @return
     * @author Michael_xu
     */
    public function receive()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $customerConfigModel = model('CustomerConfig');
        
        $customer_ids = $param['customer_id'] ?: $userInfo['id'];
        if (!$customer_ids || !is_array($customer_ids)) {
            return resultArray(['error' => '请选择需要领取的客户']);
        }
        $errorMessage = [];
        $wherePool = $customerModel->getWhereByPool();
        foreach ($customer_ids as $k => $v) {
            $dataName = db('crm_customer')->where(['customer_id' => $v])->value('name');
            //判断是否是客户池数据
            $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $v])->where($wherePool)->find();
            if (!$resPool) {
                $errorMessage[] = '客户《' . $dataName . '》领取失败，错误原因：非公海数据无权操作；';
                continue;
            }
            //拥有客户数上限检测
            if (!$customerConfigModel->checkData($userInfo['id'], 1)) {
                $errorMessage[] = '客户《' . $dataName . '》领取失败，错误原因：' . $customerConfigModel->getError();
                continue;
            }
            $data = [];
            $data['owner_user_id'] = $userInfo['id'];
            $data['update_time'] = time();
            $data['deal_time'] = time();
            $data['follow'] = '待跟进';
            //将团队成员全部清除
            $data['ro_user_id'] = '';
            $data['rw_user_id'] = '';
            # 获取客户的时间
            $data['obtain_time'] = time();
            $resCustomer = db('crm_customer')->where(['customer_id' => $v])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = '客户《' . $dataName . '》领取失败，错误原因：数据出错；';
                continue;
            }
            //联系人领取
            db('crm_contacts')->where(['customer_id' => $v])->update(['owner_user_id' => $userInfo['id']]);
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $v, '', '', '领取了客户');
        }
        if (!$errorMessage) {
            return resultArray(['data' => '领取成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }
    
    /**
     * 客户分配
     * @param
     * @return
     * @author Michael_xu
     */
    public function distribute()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $customerModel = model('Customer');
        $userModel = new \app\admin\model\User();
        $customerConfigModel = model('CustomerConfig');
        
        $customer_ids = $param['customer_id'];
        $owner_user_id = $param['owner_user_id'];
        if (!$customer_ids || !is_array($customer_ids)) {
            return resultArray(['error' => '请选择需要分配的客户']);
        }
        if (!$owner_user_id) {
            return resultArray(['error' => '请选择分配人']);
        }
        $ownerUserName = $userModel->getUserNameById($owner_user_id);
        
        $errorMessage = [];
        $wherePool = $customerModel->getWhereByPool();
        foreach ($customer_ids as $k => $v) {
            $dataName = db('crm_customer')->where(['customer_id' => $v])->value('name');
            //判断是否是客户池数据
            $resPool = db('crm_customer')->alias('customer')->where(['customer_id' => $v])->where($wherePool)->find();
            if (!$resPool) {
                $errorMessage[] = '客户《' . $dataName . '》分配失败，错误原因：非公海数据无权操作；';
                continue;
            }
            //拥有客户数上限检测
            if (!$customerConfigModel->checkData($owner_user_id, 1)) {
                $errorMessage[] = '客户《' . $dataName . '》分配失败，错误原因：' . $customerConfigModel->getError();
                continue;
            }
            $data = [];
            $data['owner_user_id'] = $owner_user_id;
            $data['update_time'] = time();
            $data['deal_time'] = time();
            $data['follow'] = '待跟进';
            //将团队成员全部清除
            $data['ro_user_id'] = '';
            $data['rw_user_id'] = '';
            # 处理分配标识，待办事项专用
            $data['is_allocation'] = 1;
            $resCustomer = db('crm_customer')->where(['customer_id' => $v])->update($data);
            if (!$resCustomer) {
                $errorMessage[] = '客户《' . $dataName . '》分配失败，错误原因：数据出错；';
            }
            db('crm_contacts')->where(['customer_id' => $v])->update(['owner_user_id' => $owner_user_id]);
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $v, '', '', '将客户分配给：' . $ownerUserName);
            //站内信
            $send_user_id[] = $owner_user_id;
            $sendContent = $userInfo['realname'] . '将客户《' . $dataName . '》,分配给您';
            if ($send_user_id) {
                sendMessage($send_user_id, $sendContent, $v, 1);
            }
        }
        if (!$errorMessage) {
            return resultArray(['data' => '分配成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }
    
    /**
     * 客户导出
     * @param
     * @return
     * @author Michael_xu
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        if ($param['customer_id']) {
            $param['customer_id'] = ['condition' => 'in', 'value' => $param['customer_id'], 'form_type' => 'text', 'name' => ''];
        }
        $param['is_excel'] = 1;
        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldConfig('crm_customer', $userInfo['id']);
        // 文件名
        $file_name = '5kcrm_customer_' . date('Ymd');
        
        $model = model('Customer');
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        unset($param['page']);
        unset($param['export_queue_index']);
        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function ($page, $limit) use ($model, $param, $field_list) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = $model->getDataList($param);
            $data['list'] = $model->exportHandle($data['list'], $field_list, 'customer');
            return $data;
        });
    }
    
    /**
     * 客户导入模板下载
     * @param string $save_path 本地保存路径     用于错误数据导出，在 Admin\Model\Excel::batchImportData()调用
     * @return
     * @author Michael_xu
     */
    public function excelDownload($save_path = '')
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $excelModel = new \app\admin\model\Excel();
        
        // 导入的字段列表
        $fieldModel = new \app\admin\model\Field();
        $fieldParam['types'] = 'crm_customer';
        $fieldParam['action'] = 'excel';
        $field_list = $fieldModel->field($fieldParam);
        $excelModel->excelImportDownload($field_list, 'crm_customer', $save_path);
    }
    
    
    /**
     * 客户数据导入
     * @param
     * @return
     * @author Michael_xu
     */
    public function excelImport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $excelModel = new \app\admin\model\Excel();
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $param['owner_user_id'] ?: 0;
        $param['deal_time'] = time();
        $param['deal_status'] = '未成交';
        $param['types'] = 'crm_customer';
        $file = request()->file('file');
        // $res = $excelModel->importExcel($file, $param, $this);
        $res = $excelModel->batchImportData($file, $param, $this);
        return resultArray(['data' => $excelModel->getError()]);
    }
    
    /**
     * 客户标记为已跟进
     * @param
     * @return
     * @author Michael_xu
     */
    public function setFollow()
    {
        $param = $this->param;
        $customerIds = $param['id'] ?: [];
        if (!$customerIds || !is_array($customerIds)) {
            return resultArray(['error' => '参数错误']);
        }
        $data['follow'] = '已跟进';
        $data['update_time'] = time();
        $res = db('crm_customer')->where(['customer_id' => ['in', $customerIds]])->update($data);
        if (!$res) {
            return resultArray(['error' => '操作失败，请重试']);
        }
        return resultArray(['data' => '跟进成功']);
    }
    
    /**
     * 置顶 / 取消置顶
     * @return [type] [description]
     */
    public function top()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_role_id'] = $userInfo['id'];
        $param['top_time'] = time();
        
        $top_id = Db::name('crm_top')->where(['module' => ['eq', $param['module']], 'create_role_id' => ['eq', $userInfo['id']], 'module_id' => ['eq', $param['module_id']]])->column('top_id');
        if ($top_id) {
            if ($res = Db::name('crm_top')->where('top_id', $top_id[0])->update($param)) {
                return resultArray(['data' => $res]);
            } else {
                return resultArray(['error' => Db::name('crm_top')->getError()]);
            }
        } else {
            if ($res = Db::name('crm_top')->data($param)->insert()) {
                return resultArray(['data' => $res]);
            } else {
                return resultArray(['error' => $customerModel->getError()]);
            }
        }
    }
    
    /**
     * 客户公海导出
     * @param
     * @return
     * @author Michael_xu
     */
    public function poolExcelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        if ($param['customer_id']) {
            $param['customer_id'] = ['condition' => 'in', 'value' => $param['customer_id'], 'form_type' => 'text', 'name' => ''];
        }
        $param['is_excel'] = 1;
        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldConfig('crm_customer', $userInfo['id']);
        $field_list = array_filter($field_list, function ($val) {
            return $val['field'] != 'owner_user_id';
        });
        // 文件名
        $file_name = '5kcrm_customer_pool_' . date('Ymd');
        $param['action'] = 'pool';

        $model = model('Customer');
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        unset($param['page']);
        unset($param['export_queue_index']);
        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function ($page, $limit) use ($model, $param) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = $model->getDataList($param);
            $data['list'] = $model->exportHandle($data['list'],  $field_list,'crm_customer');
            return $data;
        });
    }
    
    /**
     * 客户成交状态
     * @param status 1已成交,2未成交
     * @return
     * @author Michael_xu
     */
    public function deal_status()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $statusArr = ['1' => '已成交', '2' => '未成交'];
        $statusList = ['1', '2'];
        if (!$param['customer_id'] || !in_array($param['status'], $statusList)) {
            return resultArray(['error' => '参数错误']);
        }
        $customerModel = model('Customer');
        $customerConfigModel = model('CustomerConfig');
        $userModel = new \app\admin\model\User();
        $customer_ids = $param['customer_id'];
        if (!is_array($customer_ids) || !$customer_ids) {
            $customer_ids[] = $customer_ids;
        }
        $data = [];
        $data['update_time'] = time();
        $data['deal_time'] = time();
        $data['deal_status'] = $statusArr[$param['status']];
        $errorMessage = [];
        foreach ($customer_ids as $customer_id) {
            $dataInfo = [];
            $dataInfo = db('crm_customer')->where(['customer_id' => $customer_id])->field('owner_user_id,deal_status,name')->find();
            //权限判断
            if (!$customerModel->checkData($customer_id, 1)) {
                $errorMessage[] = '名称:为《' . $dataInfo['name'] . '》的客户更改失败，错误原因：' . $customerModel->getError();
                continue;
            }
            $owner_user_id = $dataInfo['owner_user_id'];;
            if (!$owner_user_id) {
                $errorMessage[] = '名称:为《' . $dataInfo['name'] . '》的客户更改失败，错误原因：公海数据无权操作';
                continue;
            }
            //拥有客户数上限检测
            if ($statusArr[$param['status']] == '未成交' && $dataInfo['deal_status'] == '已成交') {
                if (!$customerConfigModel->checkData($owner_user_id, 1, 1)) {
                    $errorMessage[] = '名称:为《' . $dataInfo['name'] . '》的客户更改失败，错误原因：' . $customerConfigModel->getError();
                    continue;
                }
            }
            if ($statusArr[$param['status']] == '已成交') {
                $data['is_lock'] = 0;
            }
            $res = db('crm_customer')->where(['customer_id' => $customer_id])->update($data);
            if (!$res) {
                $errorMessage[] = '名称:为《' . $dataInfo['name'] . '》的客户更改失败，错误原因：操作失败，请重试！';
                continue;
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_customer', $customer_id, ['deal_status' => $dataInfo['deal_status']], ['deal_status' => $data['deal_status']]);
        }
        if (!$errorMessage) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }
    
    /**
     * 设置关注
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function star()
    {
        $userId = $this->userInfo['id'];
        $targetId = $this->param['target_id'];
        $type = $this->param['type'];
        
        if (empty($userId) || empty($targetId) || empty($type)) return resultArray(['error' => '缺少必要参数！']);
        
        if (!$this->setStar($type, $userId, $targetId)) {
            return resultArray(['error' => '设置关注失败！']);
        }
        
        return resultArray(['data' => '设置关注成功！']);
    }
    
    /**
     * 附近客户
     *
     * @return \think\response\Json
     */
    public function nearby()
    {
        if (empty($this->param['lng'])) return resultArray(['error' => '缺少经度参数！']);
        if (empty($this->param['lat'])) return resultArray(['error' => '缺少纬度参数！']);
        if (empty($this->param['distance'])) return resultArray(['error' => '请选择距离！']);
        
        $customerModel = model('Customer');
        
        $data = $customerModel->getNearbyList($this->param);
        
        return resultArray(['data' => $data]);
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
        
        $customerModel = new \app\crm\model\Customer();
        
        $data = $customerModel->getSystemInfo($this->param['id']);
        
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
        if (empty($this->param['customer_id'])) return resultArray(['error' => '参数错误！']);

        $userInfo = $this->userInfo;
        
        $customerId = $this->param['customer_id'];
        
        # 联系人
        $contactsAuth = $this->getContactsSearchWhere($userInfo['id']);
        $contactsCount = Db::name('crm_contacts')->where('customer_id', $customerId)->where($contactsAuth)->count();

        # 团队成员
        $customer = Db::name('crm_customer')->field(['owner_user_id', 'ro_user_id', 'rw_user_id'])->where('customer_id', $customerId)->find();
        $customer['ro_user_id'] = explode(',', trim($customer['ro_user_id'], ','));
        $customer['rw_user_id'] = explode(',', trim($customer['rw_user_id'], ','));
        $customer['owner_user_id'] = [$customer['owner_user_id']];
        $teamCount = array_filter(array_unique(array_merge($customer['ro_user_id'], $customer['rw_user_id'], $customer['owner_user_id'])));

        # 商机
        $businessAuth = $this->getBusinessSearchWhere($userInfo['id']);
        $businessCount = Db::name('crm_business')->where('customer_id', $customerId)->where($businessAuth)->count();

        # 合同
        $contractAuth = $this->getContractSearchWhere($userInfo['id']);
        $contractCount = Db::name('crm_contract')->where('customer_id', $customerId)->where($contractAuth)->count();

        # 回款
        $receivablesAuth = $this->getReceivablesSearchWhere();
        $receivablesCount = Db::name('crm_receivables')->where('customer_id', $customerId)->whereIn('owner_user_id', $receivablesAuth)->count();

        # 回访
        $visitAuth = $this->getVisitSearchWhere($userInfo['id']);
        $visitCount = Db::name('crm_visit')->where(['customer_id' => $customerId, 'deleted_state' => 0])->where($visitAuth)->count();

        # 发票
        $invoiceAuth = $this->getInvoiceSearchWhere();
        $invoiceCount = Db::name('crm_invoice')->where('customer_id', $customerId)->whereIn('owner_user_id', $invoiceAuth)->count();

        # 附件
        $fileCount = Db::name('crm_customer_file')->alias('customer')->join('__ADMIN_FILE__ file', 'file.file_id = customer.file_id')->where('customer_id', $customerId)->count();
        
        $data = [
            'businessCount'    => $businessCount,
            'contactCount'     => $contactsCount,
            'contractCount'    => $contractCount,
            'fileCount'        => $fileCount,
            'invoiceCount'     => $invoiceCount,
            'memberCount'      => count($teamCount),
            'receivablesCount' => $receivablesCount,
            'returnVisitCount' => $visitCount
        ];
        
        return resultArray(['data' => $data]);
    }
    
    /**
     * 公海权限
     *
     * @param CustomerLogic $customerLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function poolAuthority(CustomerLogic $customerLogic)
    {
        $authority = [
            'delete' => false, # 删除
            'distribute' => false, # 分配
            'excelexport' => false, # 导出
            'index' => false, # 列表
            'receive' => false, # 领取
        ];
        
        $userId = $this->userInfo['id'];
        
        if (empty($userId)) return resultArray(['data' => $authority]);
        
        # 员工角色数据
        $groupIds = $customerLogic->getEmployeeGroups($userId);
        # 员工角色下的规则数据
        $ruleIds = $customerLogic->getEmployeeRules($groupIds);
        # 公海规则数据
        $poolRules = $customerLogic->getPoolRules();
        
        # 整理员工规则数据
        $rules = [];
        $ruleIds = implode(',', $ruleIds);
        $rules = array_filter(array_unique(explode(',', $ruleIds)));
        # 整理公海规则数据
        $deleteId = $distributeId = $exportId = $indexId = $receiveId = 0;
        foreach ($poolRules as $key => $value) {
            if ($value['name'] == 'pool') $indexId = $value['id'];
            if ($value['name'] == 'distribute') $distributeId = $value['id'];
            if ($value['name'] == 'receive') $receiveId = $value['id'];
            if ($value['name'] == 'poolExcelExport') $exportId = $value['id'];
            if ($value['name'] == 'poolDelete') $deleteId = $value['id'];
        }
        
        # 权限判断
        $authority['delete'] = $userId == 1 || in_array(1, $groupIds) || in_array($deleteId, $rules) ? true : false;
        $authority['distribute'] = $userId == 1 || in_array(1, $groupIds) || in_array($distributeId, $rules) ? true : false;
        $authority['excelexport'] = $userId == 1 || in_array(1, $groupIds) || in_array($exportId, $rules) ? true : false;
        $authority['index'] = $userId == 1 || in_array(1, $groupIds) || in_array($indexId, $rules) ? true : false;
        $authority['receive'] = $userId == 1 || in_array(1, $groupIds) || in_array($receiveId, $rules) ? true : false;
        
        return resultArray(['data' => $authority]);
    }
}