<?php
// +----------------------------------------------------------------------
// | Description: 工作日志
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use app\oa\logic\LogLogic;
use app\crm\logic\IndexLogic;
use think\Hook;
use think\Request;
use app\admin\model\Message;
use app\admin\model\Comment as CommentModel;
use think\Db;

class Log extends ApiCommon
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
            'permission' => [''],
            'allow' => ['index', 'save', 'read', 'update', 'delete', 'commentsave',
                'commentdel', 'setread', 'excelexport', 'newbulletin', 'overlog', 'activity', 'incompletelog',
                'completelog',
                'completestats',
                'logbulletin',
                'logwelcomespeech',
                'commentlist',
                'activitycount',
                'activitylist',
                'querylog',
                'onebulletin'
            ]
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }

        $param = $this->param;
        $userInfo = $this->userInfo;
        $checkAction = ['update', 'delete'];
        if (in_array($a, $checkAction) && $param['log_id']) {
            $det = Db::name('OaLog')->where('log_id = ' . $param['log_id'])->find();
            $auth_user_ids = getSubUserId();
            if (($det['create_user_id'] != $userInfo['id']) && in_array($v['create_user_id'], $auth_user_ids)) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code' => 102, 'error' => '无权操作']));
            }
        }
    }

    /**commentSave
     * 日志列表
     * @return
     * @author Michael_xu
     */
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['read_user_id'] = $userInfo['id'];
        $param['structure_id'] = $userInfo['structure_id'];
        $data = model('Log')->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 日志回复列表
     * @return \think\response\Json
     */
    public function commentList()
    {
        $param = $this->param;
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->CommentList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 欢迎语
     * @return \think\response\Json
     */
    public function logWelcomeSpeech()
    {

        $TaskLogic = new LogLogic();
        $data = $TaskLogic->LogWelcomeSpeech();
        return resultArray(['data' => $data]);
    }

    /**
     * 日报完成情况
     * @return \think\response\Json
     */
    public function completeStats()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $param['read_user_id'] = $userInfo['id'];
        $param['structure_id'] = $userInfo['structure_id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->completeStats($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 月完成情况
     * @return \think\response\Json
     */
    public function logBulletin()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $param['read_user_id'] = $userInfo['id'];
        $param['structure_id'] = $userInfo['structure_id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->logBulletin($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 任务列表导出
     * @return \think\response\Json|void
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['read_user_id'] = $userInfo['id'];
        $param['structure_id'] = $userInfo['structure_id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->excelExport($param);
        return $data;
    }

    /**
     * 跟进记录
     * @return \think\response\Json
     */
    public function activity()
    {

        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->activity($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 已完成日志员工
     * @return \think\response\Json
     */
    public function completeLog()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->completeLog($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 未完成日志员工
     * @return \think\response\Json
     */
    public function inCompleteLog()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->inCompleteLog($param);
        return resultArray(['data' => $data]);
    }
    /**
     * 未完成日志员工
     * @return \think\response\Json
     */
    public function oneBulletin()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->oneBulletin($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 标记已读
     * @return
     * @author Michael_xu
     */
    public function setread()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        if (!$param['log_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $where = [];
        $where['log_id'] = $param['log_id'];
        $resData = Db::name('OaLog')->where($where)->find();
        $read_user_ids = stringToArray($resData['read_user_ids']) ? array_merge(stringToArray($resData['read_user_ids']), array($user_id)) : array($user_id);
        $res = Db::name('OaLog')->where(['log_id' => $param['log_id']])->update(['read_user_ids' => arrayToString($read_user_ids)]);
        return resultArray(['data' => '操作成功']);
    }

    /**
     * 添加日志
     * @param
     * @return
     * @author Michael_xu
     */
    public function save()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $logModel = model('Log');
        $param['create_user_id'] = $userInfo['id'];
        $param['user_id']=$userInfo['id'];
        $param['create_user_name'] = $userInfo['realname'];
        $indexLogic = new LogLogic();
        $save = $indexLogic->oneBulletin($param);
        $param['save_customer'] = $save['data']['customerNum'];
        $param['save_business'] = $save['data']['businessNum'];
        $param['save_contract'] = $save['data']['contractNum'];
        $param['save_receivables'] = $save['data']['receivablesMoneyNum'];
        $param['save_activity'] = $save['data']['recordNum'];
        $res = $logModel->createData($param);
        if ($res) {
            $res['realname'] = $userInfo['realname'];
            $res['thumb_img'] = $userInfo['thumb_img'] ? getFullPath($userInfo['thumb_img']) : '';
            $data[] = $res;
            return resultArray(['data' => $data]);
        } else {
            return resultArray(['error' => $logModel->getError()]);
        }
    }

    /**
     * 日志详情
     * @param
     * @return
     * @author Michael_xu
     */
    public function read()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $logModel = model('Log');
        $data = $logModel->getDataById($param['id']);
        //权限判断
        $auth_user_ids = getSubUserId();
        if (!in_array($userInfo['id'], $auth_user_ids) && $data['create_user_id'] !== $userInfo['id'] && !in_array($userInfo['id'], stringToArray($data['send_user_ids']))) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
        if (!$data) {
            return resultArray(['error' => $logModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑日志
     * @param
     * @return
     * @author Michael_xu
     */
    public function update()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $log_id = $param['log_id'];
        $param['user_id']=$userInfo['id'];
        $logModel = model('Log');
        if(!empty($param['is_relation'])){
            $indexLogic = new LogLogic();
            $save = $indexLogic->oneBulletin($param);
            $param['save_customer'] = $save['data']['customerNum'];
            $param['save_business'] = $save['data']['businessNum'];
            $param['save_contract'] = $save['data']['contractNum'];
            $param['save_receivables'] = $save['data']['receivablesMoneyNum'];
            $param['save_activity'] = $save['data']['recordNum'];
        }
        if ($log_id) {
            $dataInfo = db('oa_log')->where(['log_id' => $log_id])->find();
            //权限判断
            if ($dataInfo['create_user_id'] !== $userInfo['id']) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code' => 102, 'error' => '无权操作']));
            }
            $res = $logModel->updateDataById($param, $log_id);
            if ($res) {
                return resultArray(['data' => '编辑成功']);
            } else {
                return resultArray(['error' => $logModel->getError()]);
            }
        } else {
            return resultArray(['error' => '参数错误']);
        }
    }

    /**
     * 删除日志
     * @param
     * @return
     * @author Michael_xu
     */
    public function delete()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $log_id = $param['log_id'];
        if ($log_id) {
            $dataInfo = db('oa_log')->where(['log_id' => $log_id])->find();
            $adminTypes = adminGroupTypes($userInfo['id']);
            //3天内的日志可删
            if (date('Ymd', $dataInfo['create_time']) < date('Ymd', (strtotime(date('Ymd', time())) - 86400 * 3)) && !in_array(1, $adminTypes)) {
                return resultArray(['error' => '已超3天，不能删除']);
            }
            //权限判断
            if ($dataInfo['create_user_id'] !== $userInfo['id'] && !in_array(1, $adminTypes)) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code' => 102, 'error' => '无权操作']));
            }
            $res = model('Log')->delDataById($param);
            if (!$res) {
                return resultArray(['error' => model('Log')->getError()]);
            }
            return resultArray(['data' => '删除成功']);
        } else {
            return resultArray(['error' => '参数错误']);
        }
    }

    /**
     * 日志评论添加
     * @param
     * @return
     * @author
     */
    public function commentSave()
    {
        $param = $this->param;
        $logmodel = model('Log');
        $commentmodel = new CommentModel();
        if ($param['log_id'] && $param['content']) {
            $userInfo = $this->userInfo;
            $param['user_id'] = $userInfo['id'];
            $param['type'] = 'oa_log';
            $param['type_id'] = $param['log_id'];
            $flag = $commentmodel->createData($param);
            $flag['create_time']=date('Y-m-d H:i:s',$flag['create_time']);
            if ($flag) {
                $logInfo = $logmodel->getDataById($param['log_id']);
                (new Message())->send(
                    Message::LOG_REPLAY,
                    [
                        'title' => $logInfo['title'],
                        'action_id' => $param['log_id']
                    ],
                    $logInfo['create_user_id']
                );
//				actionLog($param['log_id'],$logInfo['send_user_ids'],$logInfo['send_structure_ids'],'评论了日志');
                return resultArray(['data' => $flag]);
            } else {
                return resultArray(['error' => $commentmodel->getError()]);
            }
        } else {
            return resultArray(['error' => '参数错误']);
        }
    }

    /**
     * 日志评论删除 comment_id删除单个
     * @param
     * @return
     * @author
     */
    public function commentDel()
    {
        $param = $this->param;
        $logmodel = model('Log');
        if ($param['comment_id'] && $param['log_id']) {
            $det = Db::name('AdminComment')->where('comment_id = ' . $param['comment_id'])->find();
            $userInfo = $this->userInfo;
            if ($det) {
                if ($det['user_id'] != $userInfo['id']) {
                    return resultArray(['error' => '没有删除权限']);
                }
            } else {
                return resultArray(['error' => '不存在或已删除']);
            }
            $model = new CommentModel();
            $temp['type'] = 2;
            $temp['type_id'] = $param['log_id'];
            $temp['comment_id'] = $param['comment_id'];
            $ret = $model->delDataById($param);
            if ($ret) {
                $logInfo = $logmodel->getDataById($param['log_id']);
                //actionLog($param['log_id'],$logInfo['send_user_ids'],$logInfo['send_structure_ids'],'删除了日志评论');
                return resultArray(['data' => '删除成功']);
            } else {
                return resultArray(['error' => $model->getError()]);
            }
        } else {
            return resultArray(['error' => '参数错误']);
        }
    }

    /**
     * 今日新增
     * @return \think\response\Json
     */
    public function newBulletin()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->Bulletin($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 查看以往日志
     * @return \think\response\Json
     */
    public function overLog()
    {
        $param = $this->param;
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->lastLog($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 销售简报跟进数量统计
     * @return \think\response\Json
     */
    public function activityCount()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->activityCount($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 销售简报跟进详情
     * @return \think\response\Json
     */
    public function activityList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->activityList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 日志详情
     * @return \think\response\Json
     */
    public function queryLog(){
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $TaskLogic = new LogLogic();
        $data = $TaskLogic->queryLog($param);
        return resultArray(['data' => $data]);
    }
}