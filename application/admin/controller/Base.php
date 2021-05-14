<?php
// +----------------------------------------------------------------------
// | Description: 基础类，无需验证权限。
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\admin\controller;

use com\verify\HonrayVerify;
use app\common\controller\Common;
use think\Cache;
use think\Request;
use think\Session;

class Base extends Common
{
    public function login()
    {
        $request = Request::instance();
        $paramArr = $request->param();        
        $userModel = model('User');
        $param = $this->param;
        $username = $param['username'];
        $password = $param['password'];
        $verifyCode = !empty($param['verifyCode']) ? $param['verifyCode']: '';
        $isRemember = !empty($param['isRemember']) ? $param['isRemember']: '';
        $data = $userModel->login($username, $password, $verifyCode, $isRemember, $type, $authKey, $paramArr);
        
        Session::set('user_id', $data['userInfo']['id']);
        if (!$data) {
            return resultArray(['error' => $userModel->getError()]);
        }

        # 数据库更新 todo 在线升级正常使用后删除
        $updateStatus = $this->executeUpdateSql();
        if (empty($updateStatus['status'])) return resultArray(['error' => $updateStatus['message']]);

        return resultArray(['data' => $data]);
    }

    /**
     * 更新SQL
     *
     * @author fanqi
     * @since 2021-05-08
     */
    public function executeUpdateSql()
    {
        # 表前缀
        $prefix = config('database.prefix');

        # 检查更新记录表是否存在
        if (!db()->query("SHOW TABLES LIKE '".$prefix."admin_upgrade_record'")) {
            db()->query("
                CREATE TABLE `".$prefix."admin_upgrade_record` (
                  `version` int(10) unsigned DEFAULT NULL COMMENT '版本号',
                  UNIQUE KEY `version` (`version`) USING BTREE
                ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COMMENT = 'SQL更新记录，用于防止重复执行更新。'
            ");
        }

        # 检查是否执行过11.0.3版本的更新
        if (!db('admin_upgrade_record')->where('version', 1103)->value('version')) {
            # 添加跟进记录导入导出权限数据
            UpdateSql::addFollowRuleData();

            # 添加公海默认数据
            $poolStatus = UpdateSql::addPoolDefaultData();
            if (!$poolStatus) return ['status' => false, 'message' => '添加公海默认配置失败，请在后台手动添加！'];

            # 添加此次升级标记
            db('admin_upgrade_record')->insert(['version' => 1103]);

            return ['status' => true, 'message' => '更新完成！'];
        }

        return ['status' => true, 'message' => '没有可用更新！'];
    }

    //退出登录
    public function logout()
    {
        $param = $this->param;
        $header = Request::instance()->header();
        $request = Request::instance();
        $paramArr = $request->param();
        $platform = $paramArr['platform'] ? '_'.$paramArr['platform'] : ''; //请求平台(mobile,ding)
        $cache = Cache::set('Auth_'.trim($header['authkey']).$platform,null);
        cookie(null, '72crm_');
        cookie(null, '5kcrm_');
        session('user_id','null');
        return resultArray(['data'=>'退出成功']);
    }

    //获取图片验证码
    public function getVerify()
    {
        $captcha = new HonrayVerify(config('captcha'));
        return $captcha->entry();
    }

	//网站信息
    public function index()
    {   
        $systemModel = model('System');
        $data = $systemModel->getDataList();
        return  resultArray(['data' => $data]);
    }    
	
    // miss 路由：处理没有匹配到的路由规则
    public function miss()
    {
        if (Request::instance()->isOptions()) {
            return ;
        } else {
            echo '悟空软件';
        }
    }
}
 