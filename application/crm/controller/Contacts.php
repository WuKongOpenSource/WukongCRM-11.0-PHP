<?php
// +----------------------------------------------------------------------
// | Description: 联系人
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\traits\SearchConditionTrait;
use app\crm\traits\StarTrait;
use think\Hook;
use think\Request;
use think\Db;

class Contacts extends ApiCommon
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
            'permission'=>['exceldownload'],
            'allow'=>['index','relation','setprimary','getcontactslist','system','count']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 联系人列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $contactsModel = model('Contacts');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $contactsModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加联系人
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function save()
    {
        $contactsModel = model('Contacts');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];
        
        if ($data = $contactsModel->createData($param)) {
            # 商机管理联系人
            $business_id = $param['business_id'] ? $param['business_id'] : 0;
            if (!empty($business_id)) {
                $data['business_id'] = $business_id;
                if ($res = Db::name('crm_contacts_business')->data($data)->insert()) {
                    return resultArray(['data' => '添加成功']);
                } else {
                    return resultArray(['error' => Db::name('crm_contacts_business')->getError()]);
                }
            }
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $contactsModel->getError()]);
        }
    }

    /**
     * 联系人详情
     * @author Michael_xu
     * @param  
     * @return 
     */
    public function read()
    {
        $contactsModel = model('Contacts');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $data = $contactsModel->getDataById($param['id'], $this->userInfo['id']);
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'contacts', 'read');
        if (!in_array($data['owner_user_id'],$auth_user_ids)) {
            $authData['dataAuth'] = (int)0;
            return resultArray(['data' => $authData]);
        }         
        if (!$data) {
            return resultArray(['error' => $contactsModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑联系人
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function update()
    {    
        $contactsModel = model('Contacts');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];

        // //判断权限
        // $data = $contactsModel->getDataById($param['id']);
        // $auth_user_ids = $userModel->getUserByPer('crm', 'contacts', 'update');
        // if (!in_array($data['owner_user_id'],$auth_user_ids)) {
        //     header('Content-Type:application/json; charset=utf-8');
        //     exit(json_encode(['code'=>102,'error'=>'无权操作']));
        // }        
        if ($contactsModel->updateDataById($param, $param['id'])) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $contactsModel->getError()]);
        }      
    }

    /**
     * 删除联系人
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function delete()
    {
        $param = $this->param;    
        $contactsModel = model('Contacts');
        $recordModel = new \app\admin\model\Record();
        $fileModel = new \app\admin\model\File();
        $actionRecordModel = new \app\admin\model\ActionRecord();
        if (!is_array($param['id'])) {
            $contacts_id[] = $param['id'];
        } else {
            $contacts_id = $param['id'];
        }
        $delIds = [];
        $errorMessage = [];

        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'contacts', 'delete');
        foreach ($contacts_id as $k=>$v) {
            $isDel = true;
            //数据详情
            $data = $contactsModel->getDataById($v);
            if (!$data) {
                $isDel = false;
                $errorMessage[] = 'id为'.$v.'的联系人删除失败,错误原因：'.$contactsModel->getError();
            }
            if (!in_array($data['owner_user_id'],$auth_user_ids)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的联系人删除失败,错误原因：无权操作';
            }
            if ($isDel) {
                $delIds[] = $v;
            }           
        }
        if ($delIds) {
            $data = $contactsModel->delDatas($delIds);
            if (!$data) {
                return resultArray(['error' => $contactsModel->getError()]);
            }
            //删除跟进记录
            $recordModel->delDataByTypes(3,$delIds);
            //删除关联附件
            $fileModel->delRFileByModule('crm_contacts',$delIds);
            //删除关联操作记录
            $actionRecordModel->delDataById(['types'=>'crm_contacts','action_id'=>$delIds]);
            $userInfo = $this->userInfo;
            foreach ($contacts_id as $k => $v) {
                $data = $contactsModel->getDataById($v);
                RecordActionLog($userInfo['id'], 'crm_contacts', 'delete', $data['name'], '', '', '删除了联系人：' . $data['name']);
            }
        }        
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '删除成功']);
        }         
    }      

    /**
     * 联系人转移
     * @author Michael_xu
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @param type 权限 1只读2读写
     * @return
     */ 
    public function transfer()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $contactsModel = model('Contacts');
        $settingModel = model('Setting');
        $userModel = new \app\admin\model\User();
        $authIds = $userModel->getUserByPer(); //权限范围的user_id

        if (!$param['owner_user_id']) {
            return resultArray(['error' => '变更负责人不能为空']);
        }
        $owner_user_info = $userModel->getUserById($param['owner_user_id']);

        if (!$param['contacts_id'] || !is_array($param['contacts_id'])) {
            return resultArray(['error' => '请选择需要转移的联系人']); 
        }
        
        $is_remove = $param['is_remove'] == 2 ? : 1;
        $type = $param['type'] == 2 ? : 1;
        
        $data = [];
        $data['owner_user_id'] = $param['owner_user_id'];
        $data['update_time'] = time();
        $errorMessage = [];
        foreach ($param['contacts_id'] as $contacts_id) {
            $contactsInfo = $contactsModel->getDataById($contacts_id);
            // 转移至当前负责人的直接跳过
            if ($param['owner_user_id'] == $contactsInfo['owner_user_id']) continue;
            
            if (!$contactsInfo) {
                $errorMessage[] = '名称:为《'.$contactsInfo['name'].'》的联系人转移失败，错误原因：数据不存在；';
                continue;
            }
            //权限判断
            if (!in_array($contactsInfo['owner_user_id'],$authIds)) {
                $errorMessage[] = $contactsInfo['name'].'"转移失败，错误原因：无权限；';
                continue;
            }
            $resContacts = db('crm_contacts')->where(['contacts_id' => $contacts_id])->update($data);
            if (!$resContacts) {
                $errorMessage[] = $contactsInfo['name'].'"转移失败，错误原因：数据出错；';
                continue;
            }
			updateActionLog($userInfo['id'], 'crm_contacts', $contacts_id, '', '', '将联系人转移给：' . $owner_user_info['realname']);
            RecordActionLog($userInfo['id'], 'crm_contacts', 'transfer',$contactsInfo['name'], '','','将联系人：'.$contactsInfo['name'].'转移给：' . $owner_user_info['realname']);
        }
        
        if (!$errorMessage) {
            return resultArray(['data' => '转移成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }

    /**
     * 联系人导入模板
     * @author Michael_xu
     * @param string $save_path 本地保存路径     用于错误数据导出，在 Admin\Model\Excel::importExcel()调用
     * @return
     */ 
    public function excelDownload($save_path = '') 
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $excelModel = new \app\admin\model\Excel();
    
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $fieldParam['types'] = 'crm_contacts';
        $fieldParam['action'] = 'excel';
        $field_list = $fieldModel->field($fieldParam);
        $res = $excelModel->excelImportDownload($field_list, 'crm_contacts', $save_path);
        # 下次升级
//        $param = $this->param;
//        $userInfo = $this->userInfo;
//        $excelModel = new \app\admin\model\Excel();
//
//        // 导出的字段列表
//        $fieldModel = new \app\admin\model\Field();
//        $fieldParam['types'] = 'crm_contacts';
//        $fieldParam['action'] = 'excel';
//        $field_list = $fieldModel->field($fieldParam);
//        $array=[];
//        $field=[1=>[
//            'field'=>'owner_user_id',
//            'types'=>'crm_contacts',
//            'name'=>'负责人',
//            'form_type'=>'user',
//            'default_value'=>'',
//            'is_unique' => 1,
//            'is_null' => 1,
//            'input_tips' =>'',
//            'setting' => Array(),
//            'is_hidden'=>0,
//            'writeStatus' => 1,
//            'value' => '']
//        ];
//        $first_array = array_splice($field_list, 0, 2);
//        $array = array_merge($first_array, $field, $field_list);
//        $res = $excelModel->excelImportDownload($array, 'crm_contacts', $save_path);
    }  

    /**
     * 联系人导出
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $action_name='导出全部';
        if ($param['contacts_id']) {
           $param['contacts_id'] = ['condition' => 'in','value' => $param['contacts_id'],'form_type' => 'text','name' => ''];
           $param['is_excel'] = 1;
            $action_name='导出选中';
        }        

        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldConfig('crm_contacts', $userInfo['id']);
        // 文件名
        $file_name = '5kcrm_contacts_'.date('Ymd');

        $model = model('Contacts');
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        unset($param['page']);
        unset($param['export_queue_index']);
        RecordActionLog($userInfo['id'],'crm_contracts','excelexport',$action_name,'','','导出联系人');
        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function($page, $limit) use ($model, $param, $field_list) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = $model->getDataList($param);
            $data['list'] = $model->exportHandle($data['list'], $field_list, 'contacts');
            return $data;
        });
    } 

    /**
     * 联系人数据导入
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelImport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $excelModel = new \app\admin\model\Excel();
        $param['types'] = 'crm_contacts';
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $param['owner_user_id'] ? : $userInfo['id'];
        $file = request()->file('file');
        // $res = $excelModel->importExcel($file, $param, $this);
        $res = $excelModel->batchImportData($file, $param, $this);
//        if (!$res) {
//            return resultArray(['error'=>$excelModel->getError()]);
//        }
        RecordActionLog($userInfo['id'],'crm_contacts','excel','导入联系人','','','导入联系人');
        return resultArray(['data' => $excelModel->getError()]);
    }  

    /**
     * 联系人  关联/取消关联  商机
     * @return [type] [description]
     */
    public function relation()
    {
        $param = $this->param;
        if (!$param['contacts_id'] || !$param['contacts_id']) {
            return resultArray(['error' => '参数错误!']);
        }
        $res = 1;
        if ($param['is_relation'] == 1) {//关联
            $data = [];
            if (is_array($param['contacts_id'])) {//商机关联联系人
                foreach ($param['contacts_id'] as $key => $value) {
                    $data['contacts_id'] = $value;
                    $data['business_id'] = $param['business_id'];
                    $ret = Db::name('crm_contacts_business')->where(['contacts_id' => $value,'business_id' => $param['business_id']])->find();
                    if (!$ret) {
                        if (!Db::name('crm_contacts_business')->insert($data)) {
                            $res = 0;
                        }
                    }
                }
            } else {//联系人关联商机
                foreach ($param['business_id'] as $key => $value) {
                    $data['business_id'] = $value;
                    $data['contacts_id'] = $param['contacts_id'];
                    $ret = Db::name('crm_contacts_business')->where(['contacts_id' => $param['contacts_id'],'business_id' => $value])->find();
                    if (!$ret) {
                        if (!Db::name('crm_contacts_business')->insert($data)) {
                            $res = 0;
                        }
                    }
                }
            }
        } else {//取消关联
            $where = array();
            if (is_array($param['contacts_id'])) {
                $where['contacts_id'] = array('in',$param['contacts_id']);
                $where['business_id'] = array('eq',$param['business_id']);
            } else {
                $where['business_id'] = array('in',$param['business_id']);
                $where['contacts_id'] = array('eq',$param['contacts_id']);
            }
            Db::name('crm_contacts_business')->where($where)->delete();
            # 商机首要联系人处理
            if (is_array($param['contacts_id'])) {
                foreach ($param['contacts_id'] AS $key => $value) {
                    $contactsId = Db::name('crm_business')->where('business_id', $param['business_id'])->value('contacts_id');
                    if ($contactsId == $value) {
                        Db::name('crm_business')->where('business_id', $param['business_id'])->update(['contacts_id' => 0]);
                    }
                }
            }
        }
        if ($res == 1) {
            return resultArray(['data' => '操作成功!']);
        } else {
            return resultArray(['error' => '操作失败，请重试!']);
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
        $userId   = $this->userInfo['id'];
        $targetId = $this->param['target_id'];
        $type     = $this->param['type'];

        if (empty($userId) || empty($targetId) || empty($type)) return resultArray(['error' => '缺少必要参数！']);

        if (!$this->setStar($type, $userId, $targetId)) {
            return resultArray(['error' => '设置关注失败！']);
        }

        return resultArray(['data' => '设置关注成功！']);
    }

    /**
     * 设置首要联系人
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setPrimary()
    {
        $customerId = $this->param['customer_id'];
        $contactsId = $this->param['contacts_id'];

        if (empty($customerId)) return resultArray(['error' => '缺少客户ID！']);
        if (empty($customerId)) return resultArray(['error' => '缺少联系人ID！']);

        $contactsModel = new \app\crm\model\Contacts();

        $contactsModel->setPrimary($customerId, $contactsId);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 获取联系人
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getContactsList()
    {
        if (empty($this->param['customer_id'])) return resultArray(['error' => '缺少客户ID！']);

        $contactsModel = new \app\crm\model\Contacts();

        $data = $contactsModel->getContactsList($this->param['customer_id']);

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

        $contactsModel = new \app\crm\model\Contacts();

        $data = $contactsModel->getSystemInfo($this->param['id']);

        return resultArray(['data' => $data]);
    }

    /**
     * table标签栏数量
     *
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function count()
    {
        if (empty($this->param['contacts_id'])) return resultArray(['error' => '参数错误！']);

        $contactsId = $this->param['contacts_id'];

        $userInfo = $this->userInfo;

        # 查询联系人和商机的关联表
        $businessIds = Db::name('crm_contacts_business')->where('contacts_id', $contactsId)->column('business_id');

        # 商机权限条件
        $businessAuth = $this->getBusinessSearchWhere($userInfo['id']);

        # 商机
        $businessCount = Db::name('crm_business')->whereIn('business_id', $businessIds)->where($businessAuth)->count();

        # 附件
        $fileCount = Db::name('crm_contacts_file')->alias('contacts')->join('__ADMIN_FILE__ file', 'file.file_id = contacts.file_id', 'LEFT')->where('contacts_id', $contactsId)->count();

        $data = [
            'businessCount' => $businessCount,
            'fileCount'     => $fileCount
        ];

        return resultArray(['data' => $data]);
    }
}
