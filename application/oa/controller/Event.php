<?php
// +----------------------------------------------------------------------
// | Description: 日程
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Event extends ApiCommon
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
            'allow' => [
                'index', 'save', 'read',
                'update', 'delete', 'schedule',
                'eventtask', 'eventcrm', 'schedulesystem', 'saveschedule','liststatus',
                'eventcontract','eventcustomer','eventleads',
                'eventbusiness','eventdealbusiness'


            ]
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    //日程列表
    public function index()
    {
        $eventModel = model('Event');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $data = $eventModel->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 获取系统显示类型数据（任务）
     *
     * @return \think\response\Json
     */
    public function eventTask()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->eventTask($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 获取系统显示类型数据（客户）
     *
     * @return \think\response\Json
     */
    public function eventCrm()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->eventCrm($param);
        return resultArray(['data' => $data]);
    }
    /**
     * 获取系统显示类型数据（客户）
     *
     * @return \think\response\Json
     */
    public function eventContract()
    {

        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->eventContract($param);
        return resultArray(['data' => $data]);
    } /**
     * 获取系统显示类型数据（客户）
     *
     * @return \think\response\Json
     */
    public function eventCustomer()
    {

        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->eventCustomer($param);
        return resultArray(['data' => $data]);
    } /**
     * 获取系统显示类型数据（客户）
     *
     * @return \think\response\Json
     */
    public function eventLeads()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->eventLeads($param);
        return resultArray(['data' => $data]);
    } /**
     * 获取系统显示类型数据（客户）
     *
     * @return \think\response\Json
     */
    public function eventBusiness()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->eventBusiness($param);
        return resultArray(['data' => $data]);
    } /**
     * 获取系统显示类型数据（客户）
     *
     * @return \think\response\Json
     */
    public function eventDealBusiness()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->eventDealBusiness($param);
        return resultArray(['data' => $data]);
    }
    /**
     *日历上显示
     */
    public function listStatus()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $eventModel = model('Event');
        $data1 = $eventModel->eventCrm($param);
        $data2 = $eventModel->eventTask($param);
        $data3 = $eventModel->listStatus($param);
        $items=[];
//        foreach ($data1 as $k => $v) {
//            $item[$k]['stop_time'] = $v['stop_time'] ? date('Y-m-d ', ($v['stop_time']/1000)) : '';
//            $ites[$k]['stop_time'] = $v['start_time'] ? date('Y-m-d ', ($v['start_time']/1000)) : '';
//        }
        foreach ($data2 as $key => $val) {
            $data2[$key]['start_time'] = $val['start_time'] ? date('Y-m-d', ($val['start_time']/1000)) : '';
            $data2[$key]['stop_time'] = $val['stop_time'] ? date('Y-m-d', ($val['stop_time']/1000)) : '';
        }
        foreach ($data3 as $kk => $value) {
            $data3[$kk]['stop_time'] = $value['start_time'] ?  : '';
            $data3[$kk]['stop_time'] = $value['end_time'] ?  : '';
        }

        $data=array_merge($data1,$data2,$data3,$data2);
        $data = array_filter(array_column((array)$data, 'stop_time'));
        foreach ($data as $v){
            $items[]=$v;
        }
        $items=$items?array_values(array_unique($items)):[];
        return resultArray(['data' => $items]);
    }
    /**
     * 类型数据
     * @return mixed
     */
    public function schedule()
    {
        $param=$this->param;
        $userInfo=$this->userInfo;
        $param['user_id']=$param['user_id']?:$userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->schedule($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 修改显示类型
     * @return \think\response\Json
     */
    public function saveSchedule()
    {
        $param=$this->param;
        $userInfo=$this->userInfo;
        $param['user_id']=$param['user_id']?:$userInfo['id'];
        $eventModel = model('Event');
        $data = $eventModel->saveSchedule($param);
        return resultArray(['data' => '修改成功！']);
    }

    //添加日程
    public function save()
    {
        if (empty($this->param['title']))          return resultArray(['error' => '请填写日程内容！']);
        if (empty($this->param['schedule_id']))    return resultArray(['error' => '请选择日程类型！']);
        if (empty($this->param['owner_user_ids'])) return resultArray(['error' => '请选择参与人！']);

        $eventModel = model('Event');

        $param = $this->param;
        $param['user_id'] = $this->userInfo['id'];

        if (!$eventModel->createData($param)) return resultArray(['error' => $eventModel->getError()]);

        return resultArray(['data' => '添加成功']);
    }

    //日程详情
    public function read()
    {
        if (empty($this->param['event_id'])) return resultArray(['error' => '缺少日程ID！']);

        $eventModel = model('Event');

        $data = $eventModel->getDataById($this->param['event_id']);
        if(!$data['title']){
            return resultArray(['error' => '日程已删除']);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑日程
     *
     * @return \think\response\Json
     */
    public function update()
    {
        $eventModel = model('Event');
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['event_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $param['user_id'] = $userInfo['id'];

        $flag = $eventModel->getDataById($param['event_id'], $param);
        if ($flag['create_user_id'] != $userInfo['id']) {
            return resultArray(['error' => '没有修改权限']);
        }

        $res = $eventModel->updateDataById($param, $param['event_id']);
        if ($res) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $eventModel->getError()]);
        }
    }

    //删除日程
    public function delete()
    {
        $eventModel = model('Event');
        $param = $this->param;
        $userInfo=$this->userInfo;
        if (!$param['event_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];

        $flag = $eventModel->getDataById($param['event_id'], $param);
        if ($flag['create_user_id'] != $userInfo['id']) {
            return resultArray(['error' => '没有修改权限']);
        }
        $ret = $eventModel->delDataById($param);
        if (!$ret) {
            return resultArray(['error' => $eventModel->getError()]);
        }
        
        RecordActionLog($userInfo['id'], 'oa_event', 'delete', $flag['title'], '', '', '删除了日程：' . $flag['title']);
    
        return resultArray(['data' => '删除成功']);
    }
}
