<?php

namespace app\admin\logic;

use think\Db;

class MessageLogic
{
    private function label($label)
    {
        $where = '';
        switch ($label) {
            
            case '1':  //任务
                $where = array('in', [1, 2, 3,27]);//
                break;
            case '2':  //日志
                $where = array('in', [4, 5]);//27项目导入
                break;
            case '3':  //办公审批
                $where = array('in', [6, 7, 8]);
                break;
            case '4':  //公告
                $where = 9;
                break;
            case '5' :  //日程
                $where = 10;
                break;
            case '6' :  //客户管理
                $where = array('in', [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 28, 29, 30]);
                break;
            case '4' :
                break;
            default:
                $where = array('in', [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30]);//17181920
        }
        return $where;
    }
   
    public function getDataList($param)
    {
        $userId = $param['user_id'];
        unset($param['user_id']);
        //types 1表示已读 0表示未读
        if (isset($param['is_read'])) {
            $where['m.read_time'] = 0;
        }
        $where['m.to_user_id'] = $userId;
        $where['m.is_delete'] = ['eq', 1];
        $order = [
            'm.send_time' => 'DESC',
        ];
        $where['m.type'] = $this->label($param['label']);
        if ($param['label'] == 4) {
            $where['m.type'] = 9;
            $list = db('admin_message')
                ->alias('m')
                ->join('__ADMIN_USER__ user', 'user.id=m.from_user_id', 'LEFT')
                ->where($where)
                ->field('m.*,user.realname as user_name')
                ->page($param['page'], $param['limit'])
                ->order($order)
                ->select();
            $dataCount = db('admin_message')
                ->alias('m')
                ->join('__ADMIN_USER__ user', 'user.id=m.from_user_id', 'LEFT')
                ->where($where)->count();
        } else {
            $list = db('admin_message')
                ->alias('m')
                ->join('__ADMIN_USER__ user', 'user.id=m.from_user_id', 'LEFT')
                ->where($where)
                ->field('m.*,user.realname as user_name')
                ->page($param['page'], $param['limit'])
                ->order($order)
                ->select();
            $dataCount = db('admin_message')
                ->alias('m')
                ->join('__ADMIN_USER__ user', 'user.id=m.from_user_id', 'LEFT')
                ->where($where)->count();
        }
        //1表示已读 0表示未读
        foreach ($list as $k => $v) {
            if ($v['read_time'] == 0) {
                $list[$k]['is_read'] = 0;
            } else {
                $list[$k]['is_read'] = 1;
            }
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['send_time']);
            if ($v['type'] == 4) {
                $content = db('admin_comment')
                    ->where(
                        ['status' => 1,
                            'type_id' => $v['action_id'],
                            'type' => ['like', '%' . $v['controller_name' . '%']],
                            'user_id' => $v['from_user_id']
                        ])
                    ->select();
                $list[$k]['content'] = $content[$k]['content'];
            } elseif (in_array($v['type'], [7,12, 15,25])) {
                $content = db('admin_examine_record')->where(['types_id' => $v['action_id'], 'types' => ['like', '%' . $v['controller_name'] . '%'], 'check_user_id' => $v['from_user_id']])->field('content')->find();
                if ($content['content']) {
                    $list[$k]['content'] = $content['content'];
                }
            }
            if ($v['type'] == 10 && $v['advance_time'] < time()) {
                $item = db('oa_event_notice')->where('id', $v['action_id'])->find();
                if ($item) {
                    $type['value'] = $item['number'];
                    $type['type'] = $item['noticetype'];
                    $list[$k]['content'] = $type;
                    $list[$k]['action_id'] = $item['event_id'];
                }
            } elseif($v['type'] == 10 && $v['advance_time'] > time()) {
                unset($list[$k]);
            }
            $time=time();
            if (in_array($v['type'], ['17', '18', '19', '20', '27'])) {
                $error_file_path = db('admin_import_record')->where('id', $v['action_id'])->find();
                $week = strtotime("+7 day", $error_file_path['create_time']);
                if ($time > (int)$week && $error_file_path['error_data_file_path'] != '') {
                    $list[$k]['valid'] = 0;
                } else {
                    $list[$k]['valid'] = 1;
                }
                $list[$k]['error_file_path'] = $error_file_path['error_data_file_path'];
            }
        }
        $data = [];
        $data['page']['list'] = $list;
        $data['page']['dataCount'] = $dataCount ?: 0;
        if ($param['page'] != 1 && ($param['page'] * $param['limit']) >= $dataCount) {
            $data['page']['firstPage'] = false;
            $data['page']['lastPage'] = true;
        } else if ($param['page'] != 1 && (int)($param['page'] * $param['limit']) < $dataCount) {
            $data['page']['firstPage'] = false;
            $data['page']['lastPage'] = false;
        } else if ($param['page'] == 1) {
            $data['page']['firstPage'] = true;
            $data['page']['lastPage'] = false;
        }
        return $data;
    }
    
    /**
     * 修改状态变为已读
     * @param $param
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function endMessage($param)
    {
        $where = [
            'to_user_id' => $param['id'],
            'message_id' => ['IN', (array)$param['message_id']],
            'read_time' => 0,
        ];
        $list = db('admin_message')
            ->where($where)
            ->update(['read_time' => time()]);
        $data = [];
        $data['list'] = $list;
        return $data;
    }
    
    /**
     * 删除
     *
     * @param $messageId
     * @return array|int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($param)
    {
        $res = db('admin_message')->where(['message_id' => $param['message_id']])->find();
        if ($res['to_user_id'] != $param['user_id']) {
            return resultArray(['error' => '没有权限！']);
        }
        return db('admin_message')->where(['message_id' => $param['message_id']])->update(['is_delete' => 2]);
    }
    
    /**
     * 批量更新
     * @param $param
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function readAllMessage($param)
    {
        
        $where = [
            'to_user_id' => $param['user_id'],
            'read_time' => 0
        ];
        if ($param['label'] == 4) {
            $list = db('admin_message')
                ->where('type', 9)
                ->where($where)
                ->update(['read_time' => time()]);
        } else {
            $where['type'] = $this->label($param['label']);
            $list = db('admin_message')
                ->where($where)
                ->update(['read_time' => time()]);
        }
        $data = [];
        $data['list'] = $list;
        return $data;
    }
    
    /**
     * 批量删除已读
     * @param $param
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function clear($param)
    {
        $where = [];
        $where = [
            'to_user_id' => $param['user_id'],
            'is_delete' => 1,
            'read_time' => ['neq', 0],
        ];
        $where['type'] = $this->label($param['label']);
        $list = db('admin_message')
            ->where($where)
            ->update(['is_delete' => 2]);
        $data = [];
        $data['list'] = $list;
        return $data;
    }
    
    public function unreadCount($param)
    {
        $userId = $param['user_id'];
        //types 1表示已读 0表示未读
        $where['read_time'] = ['=', 0];
        $label = '';
        $where['to_user_id'] = ['eq', $userId];
        $where['is_delete'] = ['eq', 1];
        
        $where['type'] = $this->label('');
        $allCount = db('admin_message')->where($where)->count();
        $where['type'] = $this->label(1);
        $taskCount = db('admin_message')->where($where)->count();
        $where['type'] = $this->label(2);
        $logCount = db('admin_message')->where($where)->count();
        $where['type'] = $this->label(3);
        $jxcCount = db('admin_message')->where($where)->count();
        $where['type'] = 9;
        $announceCount = db('admin_message')->where($where)->count();
        $where['type'] = $this->label(5);
        $eventCount = db('admin_message')->where($where)->where(['advance_time'=>['<', time()],'advance_time'=>['<>',0]])->count();
        $where['type'] = $this->label(6);
        $crmCount = db('admin_message')->where($where)->count();
        
        $data = [];
        $data['allCount'] = $allCount ?: 0;
        $data['taskCount'] = $taskCount ?: 0;
        $data['logCount'] = $logCount ?: 0;
        $data['examineCount'] = $jxcCount ?: 0;
        $data['announceCount'] = $announceCount ?: 0;
        $data['eventCount'] = $eventCount ?: 0;
        $data['crmCount'] = $crmCount ?: 0;
        return $data;
    }
}