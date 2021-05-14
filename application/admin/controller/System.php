<?php
// +----------------------------------------------------------------------
// | Description: 系统配置
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;

class System extends ApiCommon
{
    //用于判断权限
    public function _initialize()
    {
        $action = [
            'permission'=>['index'],
            'allow'=>['']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }            
    }        

    //信息列表
    public function index()
    {   
        $systemModel = model('System');
        $data = $systemModel->getDataList();
        return resultArray(['data' => $data]);
    }
	
    //编辑保存
	public function save()
	{
        $param = $this->param;
        $userInfo=$this->userInfo;
        $field_name='';
        $dataInfo=[];
        if (isset($param['logo'])) {
            $system_id=2;
            $field_name='企业logo';
            $logo = !empty($param['logo']) ? './public/uploads/'.$param['logo'] : '';
            $dataInfo =  db('admin_system')->where('id', $system_id)->column('name,value');
            db('admin_system')->where('name', 'logo')->update(['value' => $logo]);
        }elseif (isset($param['name'])) {
            $system_id=1;
            $field_name='企业名称';
            $dataInfo =  db('admin_system')->where('id', $system_id)->column('name,value');
            db('admin_system')->where('name', 'name')->update(['value' => $param['name']]);
        }
        # 修改记录
        SystemActionLog($userInfo['id'],'admin_system','company',1,'update','企业基本信息设置','','','编辑了：'.$field_name);
        return resultArray(['data' => '操作成功！']);
	}
}
 