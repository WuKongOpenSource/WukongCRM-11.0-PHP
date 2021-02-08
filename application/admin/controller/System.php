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

        if (isset($param['logo'])) {
            $logo = !empty($param['logo']) ? './public/uploads/'.$param['logo'] : '';
            db('admin_system')->where('name', 'logo')->update(['value' => $logo]);
        }

        if (isset($param['name'])) {
            db('admin_system')->where('name', 'name')->update(['value' => $param['name']]);
        }

        return resultArray(['data' => '操作成功！']);
	}
}
 