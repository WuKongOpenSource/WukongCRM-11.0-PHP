<?php
// +----------------------------------------------------------------------
// | Description: 工作台及基础
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\logic\MessageLogic;
use think\Db;
use think\Hook;
use app\admin\model\Message as MessageModel;
use think\Request;

class Message extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     **/
    public function _initialize()
    {
        parent::_initialize();
        $action = [
            'permission' => [],
            'allow' => ['index', 'markedRead',  'messagelist', 'updatemessage','delete','readallmessage','clear','unreadcount'],
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 系统信息
     */
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $type = $param['type'] ?: 'all';

        if ($type != 'all' && !isset(MessageModel::$typeGroup[$type])) {
            return resultArray(['error' => '参数错误']);
        }

        $where = ['to_user_id' => $userInfo['id']];

        if ($type != 'all') {
            $where['type'] = ['IN', MessageModel::$typeGroup[$type]];
        }

        $order = [
            'read_time' => 'ASC',
            'send_time' => 'DESC',
        ];

        $page = $param['page'] ?: 1;
        $limit = $param['limit'] ?: 15;

        $data = MessageModel::where($where)
            ->order($order)
            ->paginate($limit)
            ->each(function ($val) {
                $val['relation_title'] = $val->relation_title;
                $val['from_user_id_info'] = $val->from_user_id_info;
            })
            ->toArray();

        return resultArray([
            'data' => [
                'list' => $data['data'],
                'dataCount' => $data['total']
            ]
        ]);
    }

    /**
     * 阅读系统通知，修改状态为已读
     */
    public function markedRead()
    {
        $userInfo = $this->userInfo;
        $param = $this->param;

        $where = [
            'to_user_id' => $userInfo['id'],
            'message_id' => ['IN', (array)$param['message_id']],
            'read_time' => 0,
        ];

        $res = MessageModel::where($where)->update(['read_time' => time()]);
        return resultArray(['data' => $res > 0]);
    }

    /**
     * 未读数
     */
//    public function unReadCount()
//    {
//        $data = [];
//        foreach (MessageModel::$typeGroup as $key => $val) {
//            $data[$key] = MessageModel::where(['type' => ['IN', $val]])->count();
//        }
//        $data['all'] = array_sum($data);
//        return resultArray(['data' => $data]);
//    }

    /**
     * 未读消息列表
     * @return \think\response\Json
     */
    public function messageList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $messageLogic = new MessageLogic();
        $data = $messageLogic->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     *更新消息类型 已读未读
     */
    public function updateMessage()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['id']=$userInfo['id'];
        $messageLogic = new MessageLogic();
        $data = $messageLogic->endMessage($param);
        if(!$data){
            return resultArray(['data' => "操作失败！"]);
        }
        return resultArray(['data' => "操作成功！"]);
    }

    /**
     * 删除消息
     *
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete()
    {
        if (empty($this->param['message_id'])) return resultArray(['error' => '参数错误！']);
        $userInfo = $this->userInfo;
        $param = $this->param;
        $data['message_id'] = $param['message_id'];
        $data['user_id'] = $userInfo['id'];
        $messageLogic = new MessageLogic();
        if (!$messageLogic->delete($data)) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 批量更新
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function readAllMessage(){
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];        
        $messageLogic = new MessageLogic();
        $data = $messageLogic->readAllMessage($param);
        return resultArray(['data' => "操作成功！"]);
    }

    /**
     * 批量删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function clear(){
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];        
        $messageLogic = new MessageLogic();
        $data = $messageLogic->clear($param);
        return resultArray(['data' => "操作成功！"]);
    }

    /**
     * 总数
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function unreadCount(){
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $messageLogic = new MessageLogic();
        $data = $messageLogic->unreadCount($param);
        return resultArray(['data' => $data]);
    }
}
