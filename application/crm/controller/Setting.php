<?php
// +----------------------------------------------------------------------
// | Description: 客户模块设置
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\admin\model\Message;
use think\Hook;
use think\Request;
use think\Db;

class Setting extends ApiCommon
{
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
            'allow' => [
                'config', 'configdata', 'team', 'teamsave', 'contractday', 'recordlist', 'recordedit', 'customerconfiglist',
                'customerconfigsave', 'customerconfigupdate', 'customerconfigdelete', 'numberSequenceAdd', 'quitteam',
                'setvisitday', 'getvisitday', 'setnumber', 'customerconfigdel', 'numbersequencelist',
            ]
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 客户相关配置
     *
     * @return \think\response\Json
     */
    public function config()
    {
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'pool')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
        $configModel = model('ConfigData');
        $param = $this->param;
        if ((int)$param['follow_day'] > (int)$param['deal_day']) {
            return resultArray(['error' => '成交设置时长不能大于跟进设置时长']);
        }
        $res = $configModel->createData($param);
        if ($res) {
            return resultArray(['data' => '设置成功']);
        } else {
            return resultArray(['error' => $configModel->getError()]);
        }
    }

    /**
     * 客户相关配置(详情)
     *
     * @return \think\response\Json
     */
    public function configData()
    {
        $configModel = model('ConfigData');
        $data = $configModel->getData();
        return resultArray(['data' => $data]);
    }

    /**
     * 相关团队列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function team()
    {
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        if (!$param['types'] || !$param['types_id']) {
            return resultArray(['error' => '参数错误']);
        }
        switch ($param['types']) {
            case 'crm_leads'    :
                $dataModel = new \app\crm\model\Leads();
                break;
            case 'crm_customer' :
                $dataModel = new \app\crm\model\Customer();
                break;
            case 'crm_contacts' :
                $dataModel = new \app\crm\model\Contacts();
                break;
            case 'crm_business' :
                $dataModel = new \app\crm\model\Business();
                break;
            case 'crm_contract' :
                $dataModel = new \app\crm\model\Contract();
                break;
        }
        $resData = $dataModel->getDataById($param['types_id']);
        $ro_user_ids = $resData['ro_user_id'] ? array_filter(explode(',', $resData['ro_user_id'])) : []; //只读权限
        $rw_user_ids = $resData['rw_user_id'] ? array_filter(explode(',', $resData['rw_user_id'])) : []; //读写权限

        $ro_user_arr = [];
        $rw_user_arr = [];
        $owner_user_arr = ['1' => ['user_id' => $resData['owner_user_id'], 'type' => 0, 'group_name' => '负责人', 'authority' => '负责人权限']]; //负责人

        //转换为二维数组
        foreach ($ro_user_ids as $k => $v) {
            $ro_user_arr[$k]['user_id'] = $v;
            $ro_user_arr[$k]['type'] = 1;
            $ro_user_arr[$k]['group_name'] = '普通成员';
            $ro_user_arr[$k]['authority'] = '只读';
        }

        foreach ($rw_user_ids as $k => $v) {
            $rw_user_arr[$k]['user_id'] = $v;
            $rw_user_arr[$k]['type'] = 2;
            $rw_user_arr[$k]['group_name'] = '普通成员';
            $rw_user_arr[$k]['authority'] = '读写';
        }

        $user_list = array_merge($owner_user_arr, $rw_user_arr, $ro_user_arr);
        $new_user_list = [];
        foreach ($user_list as $k => $v) {
            if ($v['user_id']) {
                $userInfo = [];
                $userInfo = $userModel->getUserById($v['user_id']) ?: [];
                $userInfo['group_name'] = $v['group_name'];
                $userInfo['authority'] = $v['authority'];
                $userInfo['type'] = $v['type'];
                $new_user_list[] = $userInfo;
            }
        }
        return resultArray(['data' => $new_user_list]);
    }

    /**
     * 退出团队
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function quitTeam()
    {
        if (empty($this->param['types']) || empty($this->param['types_id'])) return resultArray(['error' => '参数错误']);
        if (!in_array($this->param['types'], ['crm_customer', 'crm_contacts', 'crm_business', 'crm_contract'])) {
            return resultArray(['error' => '参数错误']);
        }

        $userId = $this->userInfo['id'];

        $primaryKey = ['crm_customer' => 'customer_id', 'crm_contacts' => 'contacts_id', 'crm_business' => 'business_id', 'crm_contract' => 'contract_id'];

        $data = Db::name($this->param['types'])->field([$primaryKey[$this->param['types']], 'ro_user_id', 'rw_user_id', 'owner_user_id,name'])
            ->where($primaryKey[$this->param['types']], $this->param['types_id'])->find();

        if ($data['owner_user_id'] == $userId) return resultArray(['error' => '负责人不能退出团队！']);


        $data[$primaryKey[$this->param['types']]] = $this->param['types_id'];
        $data['ro_user_id'] = str_replace(',' . $userId, '', $data['ro_user_id']);
        $data['rw_user_id'] = str_replace(',' . $userId, '', $data['rw_user_id']);

        if (!Db::name($this->param['types'])->update($data)) {
            return resultArray(['error' => '操作失败！']);
        }
        $type = '';
        switch ($this->param['types']) {
            case 'crm_customer':
                $type = Message::CUSTOMER_PASS;
                break;
            case 'crm_business':
                $type = Message::BUSINESS_PASS;
                break;
            case 'crm_contract':
                $type = Message::CONTRACT_END;
                break;

        }
        //站内信
        $send_user_id = stringToArray($userId);
        if ($send_user_id && empty($param['check_status'])) {
            (new Message())->send(
                $type,
                [
                    'title' => $data['name'],
                    'action_id' => $this->param['types_id']
                ],
                $send_user_id
            );
        }
        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 相关团队创建
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function teamSave()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $settingModel = model('Setting');
        $userModel = new \app\admin\model\User();
        $types_id = $param['types_id'];
        if (!$param['types'] || !$types_id) {
            return resultArray(['error' => '参数错误']);
        }
        if (!$param['user_id']) {
            return resultArray(['error' => '请先选择协作人']);
        }
        $errorMessage = [];
        foreach ($types_id as $k => $v) {
            $error = false;
            $typesName = '';
            //权限判断
            switch ($param['types']) {
                case 'crm_customer' :
                    $typesName = '客户';
                    $customerModel = new \app\crm\model\Customer();
                    $dataInfo = db('crm_customer')->where(['customer_id' => $v])->find();
                    //判断权限
                    $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'teamSave');
                    //判断是否客户池数据
                    $wherePool = $customerModel->getWhereByPool();
                    $resPool = db('crm_customer')->alias('customer')->where(['customer.customer_id' => $v])->where($wherePool)->find();
                    if (!$resPool && !in_array($dataInfo['owner_user_id'], $auth_user_ids)) {
                        $error = true;
                        $errorMessage = "客户'" . $dataInfo['name'] . "'操作失败，错误原因：无权操作";
                    }
                    if (in_array($dataInfo['owner_user_id'], $param['user_id'])) {
                        $error = true;
                        $errorMessage = "客户'" . $dataInfo['name'] . "'操作失败，错误原因：不能对负责人进行添加或移出操作！";
                    }
                    $message_type = Message::TEAM_CUSTOMER;
                    continue;
                case 'crm_business' :
                    $typesName = '商机';
                    $businessModel = new \app\crm\model\Business();
                    $dataInfo = db('crm_business')->where(['business_id' => $v])->find();
                    //判断权限
                    $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'teamSave');
                    if (!in_array($dataInfo['owner_user_id'], $auth_user_ids)) {
                        $error = true;
                        $errorMessage = "商机'" . $dataInfo['name'] . "'操作失败，错误原因：无权操作";
                    }
                    if (in_array($dataInfo['owner_user_id'], $param['user_id'])) {
                        $error = true;
                        $errorMessage = "商机'" . $dataInfo['name'] . "'操作失败，错误原因：不能对负责人进行添加或移出操作！";
                    }
                    $message_type = Message::TEAM_BUSINESS;
                    continue;
                case 'crm_contract' :
                    $typesName = '合同';
                    $contractModel = new \app\crm\model\Contract();
                    $dataInfo = db('crm_contract')->where(['contract_id' => $v])->find();
                    //判断权限
                    $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'teamSave');
                    if (!in_array($dataInfo['owner_user_id'], $auth_user_ids)) {
                        $error = true;
                        $errorMessage = "合同'" . $dataInfo['name'] . "'操作失败，错误原因：无权操作";
                    }
                    if (in_array($dataInfo['owner_user_id'], $param['user_id'])) {
                        $error = true;
                        $errorMessage = "合同'" . $dataInfo['name'] . "'操作失败，错误原因：不能对负责人进行添加或移出操作！";
                    }
                    $message_type = Message::TEAM_CONTRACT;
                    continue;
            }
            if ($error !== true) {
                $param['type_id'] = $v;
                $param['type'] = $param['type'] ?: 1;
                $param['is_del'] = $param['is_del'] ?: 3;
                $param['owner_user_id'] = $userInfo['id'];
                if (empty($param['is_del'])) {
                    $res = $settingModel->createTeamData($param);
                    if (!$res) {
                        $errorMessage = $typesName . $dataInfo['name'] . "'操作失败，错误原因：修改失败";
                    } else {
                        (new Message())->send(
                            $message_type,
                            [
                                'title' => $dataInfo['name'],
                                'action_id' => $v
                            ],
                            $param['user_id']
                        );
                        $username=db('admin_user')->where('id',['in',$param['user_id']])->column('realname');
                        RecordActionLog($userInfo['id'], 'crm_customer', 'teamSave',$dataInfo['name'], '','','给' . $typesName. $dataInfo['name'].'添加了团队成员 ：'.implode(',',$username));
                    }
                } else {
                    $res = $settingModel->createTeamData($param);
                    if (!$res) {
                        $errorMessage = $typesName . $dataInfo['name'] . "'操作失败，错误原因：修改失败";
                    }else{
                        (new Message())->send(
                            Message::TEAM_END,
                            [
                                'title' => $dataInfo['name'],
                                'action_id' => $v
                            ],
                            $param['user_id']
                        );
                    }
                    $username=db('admin_user')->where('id',['in',$param['user_id']])->column('realname');
                    RecordActionLog($userInfo['id'], 'crm_customer', 'teamSave',$dataInfo['name'], '','','移除了' . $typesName. $dataInfo['name'].'团队成员 ：'.implode(',',$username));
                }

            }
        }
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '保存成功']);
        }
    }

    /**
     * 合同到期提醒天数
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function contractDay()
    {
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
        $param = $this->param;
        $userInfo=$this->userInfo;
        $data = [];
        $contract_day = $param['contract_day'] ? intval($param['contract_day']) : 0;
        $contract_config = $param['contract_config'] ? intval($param['contract_config']) : 0;
        $res = db('crm_config')->where(['name' => 'contract_config'])->update(['value' => $contract_config]);
        if ($contract_day && $contract_config == 1) $res = db('crm_config')->where(['name' => 'contract_day'])->update(['value' => $contract_day]);
        # 系统操作日志
        SystemActionLog($userInfo['id'], 'crm_config','customer', 1, 'update','' , '', '','编辑了合同到期提醒设置');
        return resultArray(['data' => '设置成功']);
    }

    /**
     * 记录类型编辑
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function recordEdit()
    {
        $param = $this->param;
        $userInfo=$this->userInfo;
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
        if ($param['value']) {
            $array = json_encode($param['value']);
            $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
            if ($record_type) {
                $res = db('crm_config')->where(['name' => 'record_type'])->update(['value' => $array]);
                $id=$record_type['id'];
            } else {
                $data = array();
                $data['name'] = 'record_type';
                $data['value'] = $array;
                $data['description'] = '跟进记录类型';
                $res = db('crm_config')->insertGetId($data);
                $id=$res;
                $record_type['description']='跟进记录类型';
            }
            if ($res) {
                SystemActionLog($userInfo['id'], 'crm_config','customer', $id,  'update',$record_type['description'] , '', '','编辑了跟进记录类型：'.$record_type['description']);
                return resultArray(['data' => '设置成功']);
            } else {
                return resultArray(['error' => '设置失败，请重试！']);
            }
        } else {
            $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
            $record_type['value'] = json_decode($record_type['value']);
            return resultArray(['data' => $record_type]);
        }
    }

    /**
     * 跟进记录 记录方式展示
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recordList()
    {
        $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
        if ($record_type) {
            $arr = json_decode($record_type['value']);
            return resultArray(['data' => $arr]);
        } else {
            return resultArray(['data' => array()]);
        }
    }

    /**
     * 拥有、锁定客户数限制列表
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function customerConfigList()
    {
        $param = $this->param;
        $param['types'] = $param['types'] ?: 1;
        $customerConfigModel = new \app\crm\model\CustomerConfig();
        $data = $customerConfigModel->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 拥有、锁定客户数限制 创建/编辑 todo 创建和编辑走一个接口，前端非要这么搞
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function customerConfigSave()
    {
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }

        $customerConfigModel = new \app\crm\model\CustomerConfig();
        if (!$customerConfigModel->createData($this->param)) {
            return resultArray(['error' => $customerConfigModel->getError()]);
        }

        return resultArray(['data' => empty($param['id']) ? '创建成功！' : '编辑成功！']);
    }

    /**
     * 拥有、锁定客户数限制 编辑 todo 编辑不走这个接口，前端非要走创建接口
     *
     * @return \think\response\Json
     */
    public function customerConfigUpdate()
    {
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }

        $param = $this->param;
        $customerConfigModel = new \app\crm\model\CustomerConfig();
        $res = $customerConfigModel->updateDataById($param, $param['id']);
        if (!$res) {
            return resultArray(['error' => $customerConfigModel->getError()]);
        }
        return resultArray(['data' => '编辑成功']);
    }

    /**
     * 拥有、锁定客户数限制 删除
     *
     * @return \think\response\Json
     */
    public function customerConfigDel()
    {
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
        $param = $this->param;
        $customerConfigModel = new \app\crm\model\CustomerConfig();
        $res = $customerConfigModel->delDataById($param['id']);
        if (!$res) {
            return resultArray(['error' => $customerConfigModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }

    /**
     * 编号列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function numberSequenceList()
    {
        $param = $this->param;
        $numberSequenceModel = new \app\crm\model\NumberSequence();
        $data = $numberSequenceModel->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 编号修改
     *
     * @return \think\response\Json
     */
    public function numberSequenceUpdate()
    {
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
        $param = $this->param;
        $numberSequenceModel = new \app\crm\model\NumberSequence();
        $res = $numberSequenceModel->numberSequenceUpdate($param, $param['id']);
        if (!$res) {
            return resultArray(['error' => $numberSequenceModel->getError()]);
        }
        return resultArray(['data' => '编辑成功']);
    }

    /**
     * 编号删除
     *
     * @return \think\response\Json
     */
    public function numberSequenceDel()
    {
        //权限判断
        if (!checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
        $param = $this->param;
        $numberSequenceModel = new \app\crm\model\NumberSequence();
        $res = $numberSequenceModel->delDataById($param['id']);
        if (!$res) {
            return resultArray(['error' => $numberSequenceModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }

    /**
     * 设置回访提醒
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setVisitDay()
    {
        $status = !empty($this->param['status']) ? $this->param['status'] : 0;
        $day    = !empty($this->param['day'])    ? $this->param['day']    : 0;

        $settingModel = new \app\crm\model\Setting();

        if (!$settingModel->setVisitDay($status, $day)) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }
    
    /**
     * 获取回访提醒
     *
     * @return \think\response\Json
     */
    public function getVisitDay()
    {
        $settingModel = new \app\crm\model\Setting();
        
        $data = $settingModel->getVisitDay();
        
        return resultArray(['data' => $data]);
    }
    /**
     * 设置自动编号
     *
     * @return \think\response\Json
     */
    public function setNumber()
    {
        if (empty($this->param) || !is_array($this->param)) return resultArray(['error' => '参数错误！']);

        $settingModel = new \app\crm\model\Setting();

        if ($settingModel->setNumber($this->param) === false) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }
}