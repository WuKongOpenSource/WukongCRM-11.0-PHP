<?php
// +----------------------------------------------------------------------
// | Description: 任务评论及基础
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\controller;

use think\Db;
use think\Request;
use think\Hook;
use app\admin\controller\ApiCommon;

class Taskcomment extends ApiCommon
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
            'permission'=>[''],
            'allow'=>['index','save','delete']         
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 添加评论
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save()
    {
        $param  = $this->param;
        $userId = $this->userInfo['id'];

        if (empty($param['content']))    return resultArray(['error' => '缺少回复内容！']);
        if (empty($param['type']))       return resultArray(['error' => '缺少回复类型：任务/日志']);
        if (empty($param['type_id']))    return resultArray(['error' => '缺少回复数据ID']);

        $commentModel = new \app\admin\model\Comment();

		# 评论人ID
        $param['user_id'] = $userId;
		# 1：任务；2：日志
        $param['type'] = !empty($param['type']) && $param['type'] == 2 ? 'oa_log' : 'task';
        # 任务或日志的ID，取决于$param['type']的值
        $param['type_id'] = $param['type_id'];

        $flag = $commentModel->createData($param);

        if ($flag) {
            # 用户信息
            $userInfo = Db::name('admin_user')->field(['realname', 'thumb_img'])->where('id', $flag['user_id'])->find();
            $flag['userInfo'] = [
                'user_id'   => $flag['user_id'],
                'realname'  => $userInfo['realname'],
                'thumb_img' => getFullPath($userInfo['thumb_img'])
            ];

            # 时间格式
            if (!empty($flag['create_time'])) $flag['create_time'] = date('Y-m-d H:i:s', $flag['create_time']);

            return resultArray(['data' => $flag]);
        } else {
            return resultArray(['error' => $commentModel->getError()]);
        }
    }

    /**
     * 删除评论
     * @author yykun
     * @return
     */ 
    public function delete()
    {
        $param = $this->param;
        $commentModel = new \app\admin\model\Comment();
        if (!$param['comment_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $commentModel->delDataById($param);
        if ( $flag ) {
            return resultArray(['data'=>'删除成功']);
        } else {
            return resultArray(['error'=>$commentModel->getError()]);
        }
    }
}
 