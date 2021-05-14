<?php
// +----------------------------------------------------------------------
// | Description: Api基础类，验证权限
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Cache;
use think\Request;
use think\Db;
use app\common\adapter\AuthAdapter;
use app\common\controller\Common;


class ApiCommon extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        /*获取头部信息*/ 
        $header = Request::instance()->header();
        $request = Request::instance();
        
        $authKey = trim($header['authkey']);
        $sessionId = trim($header['sessionid']);
        $paramArr = $request->param();
        $platform = $paramArr['platform'] ? '_'.$paramArr['platform'] : ''; //请求平台(mobile,ding)
        $cache = Cache::get('Auth_'.$authKey.$platform);
//        $cache = cache('Auth_'.$authKey.$platform);
//        dump($request->action());die;
        // 校验sessionid和authKey
        if (empty($sessionId) || empty($authKey) || empty($cache)) {
            header('Content-Type:application/json; charset=utf-8');
            $dataTime=date('H:i',time());
            exit(json_encode(['code' => 302, 'data' => ['extra' => 1, 'extraTime' => $dataTime], 'msg' => '请先登录!']));
        }
        //登录有效时间
        $cacheConfig = config('cache');
        $loginExpire = !empty($cacheConfig['expire']) ? $cacheConfig['expire'] : 86400 * 30;

        // 检查账号有效性
        $userInfo = $cache['userInfo'];
        $map['id'] = $userInfo['id'];
        $map['status'] = array('in',['1','2']);
        $userData = Db::name('admin_user')->where($map)->find();
        if (!$userData) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>103, 'data' => [], 'msg'=>'账号已被删除或禁用']));   
        }
        session('user_id', $userInfo['id']);
        // 更新缓存
        Cache::set('Auth_'.$authKey, $cache, $loginExpire);
    }
}
