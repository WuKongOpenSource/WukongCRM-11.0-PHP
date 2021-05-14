<?php
namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use app\crm\logic\MarketLogic;

class Market extends ApiCommon{
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
            'allow'=>['index','save','read','update','marketlist']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }
    
    /**
     * 市场活动列表
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/26 0026 17:15
     */
    public function index(){
        $marketLogic=new MarketLogic;
        $param=$this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $marketLogic->getDataList($param);
        return resultArray(['data' => $data]);
    }
    
    /**
     * 关联对象列表
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/26 0026 17:14
     */
    public function marketList(){
        $marketLogic=new MarketLogic;
        $data = $marketLogic->marketList();
        return resultArray(['data' => $data]);
    }
    public function save(){
        $marketLogic=new MarketLogic;
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        # 检查活动图片
        if (!empty($param['cover_images']) && count(explode(',', $param['cover_images'])) > 9) {
            return resultArray(['error' => '最多只能上次9张产品图片！']);
        }
    
        # 检查活动详情图片
        if (!empty($param['details_images']) && count(explode(',', $param['details_images'])) > 9) {
            return resultArray(['error' => '最多只能上次9张产品详情图片！']);
        }
    
        if ($marketLogic->createData($param)) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => '添加失败']);
        }
    }
    public function update(){
        $marketLogic=new MarketLogic;
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
    
        # 检查产品图片
        if (!empty($param['cover_images']) && count(explode(',', $param['cover_images'])) > 9) {
            return resultArray(['error' => '最多只能上次9张产品图片！']);
        }
    
        # 检查产品详情图片
        if (!empty($param['details_images']) && count(explode(',', $param['details_images'])) > 9) {
            return resultArray(['error' => '最多只能上次9张产品详情图片！']);
        }
    
        if ($marketLogic->updateDataById($param, $param['id'])) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => '编辑失败']);
        }
    }
    public function read()
    {
        $marketLogic=new MarketLogic;
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $marketLogic->getDataById($param['id'], $userInfo['id']);
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'Market', 'read');
        if (!in_array($data['owner_user_id'], $auth_user_ids)) {
            //无权限
            $authData['dataAuth'] = (int)0;
            return resultArray(['data' => $authData]);
        }
        if (!$data) {
            return resultArray(['error' => $marketLogic->getError()]);
        }
        return resultArray(['data' => $data]);
    }
    public function delete(){
        $marketLogic=new MarketLogic;
        $userInfo = $this->userInfo;
        $id_list = (array) $this->param['id'];
        $id_list['user_id']=$userInfo['id'];
        $id_list = array_map('intval', $id_list);
        $data=$marketLogic->delete($id_list);
        if($data){
            return resultArray(['data' => '删除成功']);
        }else{
            return resultArray(['error' => '删除失败']);
        }
    }
    public function enables(){
        $marketModel = model('Market');
        $param = $this->param;
        $userInfo=$this->userInfo;
        $id = [$param['flow_id']];
        $data = $marketModel->enableDatas($id, $param['status']);
        # 系统操作日志
        if (!$data) {
            return resultArray(['error' => $marketModel->getError()]);
        }
        if($param['status']==0){
            $content='禁用了：';
        }else{
            $content='启用了：';
        }
        $dataInfo=db('admin_examine_flow')->where('flow_id',$param['flow_id'])->find();
        SystemActionLog($userInfo['id'], 'admin_examine','approval', $param['flow_id'], 'update', $dataInfo['name'], '', '',$content.$dataInfo['name']);
        return resultArray(['data' => '操作成功']);
    }
}
