<?php
// +----------------------------------------------------------------------
// | Description: 工作台及基础
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;

class Index extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     */
    public function _initialize()
    {
        parent::_initialize();
        $action = [
            'permission' => [],
            'allow' => ['fields', 'fieldrecord', 'authlist','sort','updatesort',
            'importnum','importinfo','importlist','readnotice'],
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 获取字段属性，用于筛选或其他操作
     * @param
     * @return
     */
    public function fields()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $fieldModel = model('Field');
        $field_arr = $fieldModel->getField($param);

        # 转form_type类型，用于场景筛选和创建自定义场景
        foreach ($field_arr AS $key => $value) {
            if ($value['field'] == 'address')      $field_arr[$key]['form_type'] = 'map_address';
            if ($value['field'] == 'deal_status')  $field_arr[$key]['form_type'] = 'deal_status';
            if ($value['field'] == 'check_status') $field_arr[$key]['form_type'] = 'check_status';
            if ($param['types'] == 'crm_visit' && $value['field'] == 'owner_user_id') $field_arr[$key]['name'] = '回访人';
        }

        return resultArray(['data' => $field_arr]);
    }

    /**
     * 获取字段修改记录
     * @param
     * @return
     */
    public function fieldRecord()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $actionRecordModel = model('ActionRecord');
        $data = $actionRecordModel->getDataList($param);
        if (!$data) {
            return resultArray(['data' => '暂无数据']);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 权限数据返回
     * @param
     * @return
     */
    public function authList()
    {
        $userInfo = $this->userInfo;
        $userModel = model('User');
        $dataList = $userModel->getMenuAndRule($userInfo['id']);
        return resultArray(['data' => $dataList['authList']]);
    }

    /**
     * todo
     * 顶部菜单栏展示
     * @return mixed
     */
    //todo admin_sort 表数据未完善
    public function sort(){
        $param = $this->param;
        $userModel = model('User');
        $userInfo = $this->userInfo;
        $param['user_id']= $param['user_id']?:$userInfo['id'];
        $dataList = $userModel->sortList($param);
        return resultArray(['data' => $dataList]);
    }

    /**
     *顶部菜单栏编辑
     */
    public function updateSort(){
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userModel = model('User');
        $param['value']=$param;
        $param['user_id']= $param['user_id']?:$userInfo['id'];
        $dataList = $userModel->updateSort($param);
        if (!$dataList) {
            return resultArray(['data' => '编辑失败']);
        }
        return resultArray(['data' => '编辑成功']);
    }
    /**
     * 导入中
     * @return \think\response\Json
     */
    public function importNum(){
        $excelModel = model('Excel');
        $data = $excelModel->importNum();
        return resultArray(['data'=>$data]);

    }

    /**
     * 导入成功返回值
     * @return \think\response\Json
     */
    public function importInfo(){
        $excelModel = model('Excel');
        $data = $excelModel->importInfo();
        return resultArray(['data'=>$data]);
    }

    /**
     * 导入历史
     * @return \think\response\Json
     */
    public function importList(){
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $excelModel = model('Excel');
        $data = $excelModel->importList($param);
        return resultArray(['data'=>$data]);
    }

    /**
     * 升级公告
     * @author fanqi
     * @date 2021-03-15
     * @return \think\response\Json
     */
    public function readNotice()
    {
        $userInfo = $this->userInfo;

        if (!empty($userInfo['id'])) db('admin_user')->where('id', $userInfo['id'])->update(['is_read_notice' => 1]);

        return resultArray(['data' => '']);
    }
}
