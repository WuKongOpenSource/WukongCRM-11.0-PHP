<?php

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\model\NumberSequence;
use app\crm\traits\AutoNumberTrait;
use app\crm\logic\VisitLogic;
use think\Hook;
use think\Request;
use app\admin\model\User;
use think\Db;

class Visit extends ApiCommon
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
            'allow' => ['count']
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }
    public function visitUser(){
        $userInfo = $this->userInfo;
        $userModel=new User();
        $userInfo= $userModel->getUserById($userInfo['id']);
        return resultArray(['data' => $userInfo]);
    }
    /**
     * 回访列表
     */
    public function index()
    {
        $Visit = new VisitLogic;
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id']?:$userInfo['id'];
        $data = $Visit->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 创建回访单
     */
    public function save()
    {
        $Visit = new VisitLogic;
        $param = $this->param;
        $userInfo = $this->userInfo;
        # 设置回复编号
        $numberInfo = [];
        if (empty($param['number'])) {
            $numberInfo = $this->getAutoNumbers(3);
            if (empty($numberInfo['number'])) return resultArray(['error' => '请填写回访编号！']);
            $param['number'] = $numberInfo['number'];
        }
        $param['owner_user_id'] = $param['owner_user_id'] ? : $userInfo['id'];
        $param['create_user_id'] = $userInfo['id'];
        $param['create_time'] = time();
        $param['update_time'] = time();
        $res = $Visit->createData($param);
        if ($res) {
            # 更新crm_number_sequence表中的last_date、create_time字段
            if (!empty($numberInfo['data'])) (new NumberSequence())->batchUpdate($numberInfo['data']);
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $Visit->getError()]);
        }
    }

    /**
     * 回访单详情
     */
    public function read()
    {

        $visit = new VisitLogic;
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $visit->getDataById($param['id']);
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'visit', 'read');
        //读权限
        $roPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'read');
        $rwPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'update');
        if (!in_array($data['owner_user_id'], $auth_user_ids) && !$rwPre && !$roPre) {
            $authData['dataAuth'] = (int)0;
            return resultArray(['data' => $authData]);
        }
        if (!$data) {
            return resultArray(['error' => $visit->getError()]);
        }
        return resultArray(['data' => $data]);

    }

    /**
     * 编辑回访单
     */
    public function update()
    {
        $Visit = new VisitLogic;
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $param['owner_user_id'] ? : $userInfo['id'];
        # 设置回访编号
        $numberInfo = [];
        if (empty($param['number'])) {
            $numberInfo = $this->getAutoNumbers(3);
            if (empty($numberInfo['number'])) return resultArray(['error' => '请填写回访编号！']);
            $param['number'] = $numberInfo['number'];
        }
        //判断权限
        $data = $Visit->getDataById($param['id']);
        $auth_user_ids = $userModel->getUserByPer('crm', 'visit', 'update');
        $param['update_time'] = time();
        if ($Visit->updateDataById($param, $param['id'])) {
            # 更新crm_number_sequence表中的last_date、create_time字段
            if (!empty($numberInfo['data'])) (new NumberSequence())->batchUpdate($numberInfo['data']);
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $Visit->getError()]);
        }

    }

    /**
     * 删除回访单
     */
    public function delete()
    {
        $Visit = new VisitLogic;
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!is_array($param['id'])) {
            $visit_id[] = $param['id'];
        } else {
            $visit_id = $param['id'];
        }
        $data = $Visit->del($visit_id);
        if ($data) {
            return resultArray(['error' => $data]);
        } else {
            return resultArray(['data' => '删除成功']);
        }
    }

    /**
     * 系统信息
     *
     */
    public function system(VisitLogic $visitLogic)
    {
        if (empty($this->param['id'])) return resultArray(['error' => '参数错误！']);

        $data = $visitLogic->getSystemInfo($this->param['id']);

        return resultArray(['data' => $data]);
    }

    /**
     * table标签栏数量
     */
    public function count()
    {
        if (empty($this->param['visit_id'])) return resultArray(['error' => '参数错误！']);
        # 附件
        $fileCount = Db::name('crm_visit_file')->alias('visit')->join('__ADMIN_FILE__ file', 'file.file_id = visit.file_id', 'LEFT')->where('visit_id', $this->param['visit_id'])->count();

        return resultArray(['data' => ['fielCount' => $fileCount]]);
    }
}