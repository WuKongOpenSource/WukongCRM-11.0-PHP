<?php
// +----------------------------------------------------------------------
// | Description: 日程
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\oa\model;

use app\admin\controller\ApiCommon;
use think\Db;
use app\admin\model\Common;
use app\admin\model\Message;
use think\Request;
use think\Validate;
use think\helper\Time;

class Event extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    protected $name = 'oa_event';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $autoWriteTimestamp = true;

    //类型转换
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $type = [
        'start_time' => 'timestamp',
        'end_time' => 'timestamp',
    ];
    
    /**
     * [getDataList 日程list]
     * @param     [by]                       $by [查询时间段类型]
     * @return    [array]                    [description]
     * @author Michael_xu
     */
    public function getDataList($param)
    {
        $userModel = new \app\admin\model\User();
        $recordModel = new \app\admin\model\Record();
        $user_id = $param['user_id'];
        
        //默认本账户or 自定义用户id
        if ($param['star_time'] && $param['end_time']) {
            $start_time = $param['star_time'];
            $end_time = $param['end_time'];
        } else {
            $start_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $end_time = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        }
        
        $where = '( ( start_time BETWEEN ' . $start_time . ' AND ' . $end_time . ' ) AND ( create_user_id = ' . $user_id . ' or owner_user_ids like "%,' . $user_id . ',%" ) ) 
        OR ( ( end_time BETWEEN ' . $start_time . ' AND ' . $end_time . ' ) AND  ( create_user_id = ' . $user_id . ' or owner_user_ids like "%,' . $user_id . ',%" ) ) 
        OR ( start_time < ' . $start_time . ' AND end_time > ' . $end_time . ' AND ( create_user_id = ' . $user_id . ' or owner_user_ids like "%,' . $user_id . ',%" ) )';
        $event_date = Db::name('OaEvent')->where($where)->select();
        foreach ($event_date as $k=>$v) {
            $event_date[$k]['create_user_info'] = $userModel->getUserById($v['create_user_id']);
            $event_date[$k]['ownerList'] = $userModel->getDataByStr($v['owner_user_ids']) ? : [];
            $list = db('admin_oa_schedule')->where('schedule_id',$v['schedule_id'])->find();
            $event_date[$k]['color']= $list['color'] ;
            $relationArr= [];
            $relationArr = $recordModel->getListByRelationId('event', $v['event_id']);
            $event_date[$k]['businessList'] = $relationArr['businessList'];
            $event_date[$k]['contactsList'] = $relationArr['contactsList'];
            $event_date[$k]['contractList'] = $relationArr['contractList'];
            $event_date[$k]['customerList'] = $relationArr['customerList'];
        
            $event_date[$k]['remindtype'] = (int)$v['remindtype'];
            $noticeInfo = Db::name('OaEventNotice')->where(['event_id' => $v['event_id']])->find();
            $is_repeat = 0;
            if ($noticeInfo) {
                $is_repeat = 1;
            }
            $event_date[$k]['is_repeat'] = $is_repeat;
            $event_date[$k]['stop_time'] = $noticeInfo ? $noticeInfo['stop_time'] : '';
            $event_date[$k]['noticetype'] = $noticeInfo ? $noticeInfo['noticetype'] : '';
            if ($noticeInfo['noticetype'] == '2') {
                $event_date[$k]['repeat'] = $noticeInfo['repeated'] ? explode('|||',$noticeInfo['repeated']) : [];
            } else {
                $event_date[$k]['repeat'] =  '';
            }
            //权限
            $is_update = 0;
            $is_delete = 0;
            if ($user_id == $v['create_user_id']) {
                $is_update = 1;
                $is_delete = 1;
            }
            $permission['is_update'] = $is_update;
            $permission['is_delete'] = $is_delete;
            $event_date[$k]['permission']	= $permission;
            $event_date[$k]['start_time'] = !empty($v['start_time']) ? $v['start_time'] * 1000 : null;
            $event_date[$k]['end_time'] = !empty($v['end_time']) ? $v['end_time'] * 1000 : null;
    
            $event_date[$k]['type_id'] = $v['schedule_id'];
        }
        return $event_date ? : [];
    }
    
    /**
     * 系统自定义类型数据（任务）
     *
     * @param $param
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eventTask($param)
    {
        $user_id = $param['user_id'];
        
        // 默认本账户or 自定义用户id
        if ($param['start_time'] && $param['end_time']) {
            $start_time = $param['start_time'];
            $end_time = $param['end_time'];
        } else {
            $start_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $end_time = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        }
        $between_time =
            '((stop_time BETWEEN ' . $start_time . ' AND ' . $end_time . ' ) AND ( create_user_id = ' . $user_id . ' or owner_user_id like "%,' . $user_id . ',%")) 
          OR ((stop_time > ' . $start_time . ' AND stop_time <' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id like "%,' . $user_id . ',%"))
          OR ((stop_time > ' . $start_time . ' AND stop_time >' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id like "%,' . $user_id . ',%"))';
        
        //分配的任务 负责人或参与人是当前用户
        $event_date = db('task')->where(['stop_time' => ['>', 0], 'ishidden' => ['=', 0]])->where($between_time)
            ->field('task_id,name,start_time,stop_time')->select();
        foreach ($event_date as $k1 => $v) {
            $event_date[$k1]['color'] = 1;
            $event_date[$k1]['type_id'] = 1;
            $event_date[$k1]['type'] = 2;
            $event_date[$k1]['start_time'] = !empty($v['start_time']) ? $v['start_time'] * 1000 : null;
            $event_date[$k1]['stop_time'] = !empty($v['stop_time']) ? $v['stop_time'] * 1000 : null;
        }
        return $event_date;
    }
    
    /**
     * 系统自定义类型数据（客户）
     *
     * @param $param
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eventCrm($param)
    {
        $userModel = new \app\admin\model\User();
        $recordModel = new \app\admin\model\Record();
        $user_id = $param['user_id'];
        
        
        //默认本账户or 自定义用户id
        if ($param['start_time'] && $param['end_time']) {
            $start_time = $param['start_time'];
            $end_time = $param['end_time'];
            if (strlen($start_time) == 13) $start_time = $start_time / 1000;
            if (strlen($end_time) == 13) $end_time = $end_time / 1000;
        } else {
            $start_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $end_time = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        }
        $between_time = '';
        //需要联系的客户 next_time crm_customer
        $between_time = '((next_time BETWEEN ' . $start_time . ' AND ' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id = ' . $user_id . '))
        OR ((next_time > ' . $start_time . ' AND next_time <' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id =' . $user_id . '))
        OR ((next_time > ' . $start_time . ' AND next_time > ' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id = ' . $user_id . '))';
        $event_date = db('crm_customer')->where(['next_time' => ['>', 0], 'owner_user_id' => ['<>', 0]])->where($between_time)->where('')
            ->field('next_time as start_time,customer_id,name ')->group('start_time')->select();
        $item = [];
        foreach ($event_date as $k2 => $v) {
            $event_date[$k2]['color'] = 2;
            $event_date[$k2]['type_id'] = 2;
            $event_date[$k2]['type'] = 'customer';
            $event_date[$k2]['start_time'] = !empty($v['start_time']) ? $v['start_time'] * 1000 : null;
            $event_date[$k2]['stop_time'] = !empty($v['start_time']) ? $v['start_time'] * 1000 : null;
            $event_date[$k2]['stop_time'] = !empty($v['start_time']) ? $v['start_time'] * 1000 : null;
            $item[] = $v['start_time'] ? date('Y-m-d', $v['start_time']) : '';
        }
        $list_data['customer'] = $item ? array_values(array_unique($item)) : [];
        //即将到期的合同 end_time
        $starts_time = date('Y-m-d', $start_time);
        $ends_time = date('Y-m-d', $end_time);
        $searchMap = function ($query) use ($starts_time, $ends_time) {
            $query->where('end_time', array('between', [$starts_time, $ends_time]))
                ->whereOr('end_time', array('gt', $ends_time));
        };
        //需要联系的客户 next_time crm_customer
        $between['create_user_id|owner_user_id'] = ['eq', $user_id];
        $event_date = db('crm_contract')->where('end_time', '>', 0)->where($searchMap)->where($between)
            ->field('end_time as start_time,contract_id,name ')
            ->group('start_time')
            ->select();
        unset($item);
        foreach ($event_date as $k3 => $v) {
            $event_date[$k3]['color'] = 3;
            $event_date[$k3]['type_id'] = 3;
            $event_date[$k3]['type'] = 'contract';
            $event_date[$k3]['start_time'] = !empty($v['start_time']) ? strtotime($v['start_time']) * 1000 : null;
            $event_date[$k3]['stop_time'] = !empty($v['start_time']) ? strtotime($v['start_time']) * 1000 : null;
            $item[] = $v['start_time'] ? $v['start_time'] : '';
        }
        $list_data['contract'] = $item ? array_values(array_unique($item)) : [];
        //计划回款 return_date
        $between_receivables = function ($query) use ($starts_time, $ends_time) {
            $query->where('r.return_date', array('between', [$starts_time, $ends_time]))
                ->whereOr('r.return_date', array('gt', $ends_time));
        };
        $receivables['r.create_user_id|r.owner_user_id'] = ['eq', $user_id];
        $event_date = db('crm_receivables_plan')
            ->alias('r')
            ->where(['r.status' => 0, 'r.return_date' => ['>', 0]])
            ->where($between_receivables)
            ->where($receivables)
            ->field('r.return_date as start_time')
            ->group('start_time')
            ->select();
        unset($item);
        foreach ($event_date as $k4 => $v) {
            $event_date[$k4]['color'] = 4;
            $event_date[$k4]['type_id'] = 4;
            $event_date[$k4]['type'] = 'receivables_plan';
            $event_date[$k4]['start_time'] = !empty($v['start_time']) ? strtotime($v['start_time']) * 1000 : null;
            $event_date[$k4]['stop_time'] = !empty($v['start_time']) ? strtotime($v['start_time']) * 1000 : null;
            $item[] = $v['start_time'] ? $v['start_time'] : '';
        }
        
        $list_data['receivables'] = $item ? array_values(array_unique($item)) : [];
        //需联系的线索 next_time
        
        $between_time = '(( next_time BETWEEN ' . $start_time . ' AND ' . $end_time . ')AND ( create_user_id = ' . $user_id . ' or owner_user_id = ' . $user_id . '  ))
    
        OR ((next_time > ' . $start_time . ' AND next_time <' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id = ' . $user_id . ' ))
        OR ((next_time > ' . $start_time . ' AND next_time > ' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id = ' . $user_id . ' ))';
        $event_date = db('crm_leads')
            ->where($between_time)
            ->where(['next_time' => ['>', 0], 'is_transform' => 0])
            ->field('next_time as start_time,leads_id,name')
            ->group('next_time')
            ->select();
        unset($item);
        foreach ($event_date as $k5 => $v) {
            $event_date[$k5]['color'] = 5;
            $event_date[$k5]['type_id'] = 5;
            $event_date[$k5]['type'] = 'leads';
            $event_date[$k5]['start_time'] = $v['start_time'] * 1000;
            $event_date[$k5]['stop_time'] = !empty($v['start_time']) ? $v['start_time'] * 1000 : null;
            $item[] = $v['start_time'] ? date('Y-m-d', $v['start_time']) : '';
        }
        
        $list_data['leads'] = $item ? array_values(array_unique($item)) : [];
        //需联系的商机 next_time
        $between_time =
            '((next_time BETWEEN ' . $start_time . ' AND ' . $end_time . ' ) AND ( create_user_id = ' . $user_id . ' or owner_user_id = ' . $user_id . '  )) 
          OR ((next_time > ' . $start_time . ' AND next_time <' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id = ' . $user_id . ' ))
          OR ((next_time > ' . $start_time . ' AND next_time >' . $end_time . ') AND ( create_user_id = ' . $user_id . ' or owner_user_id = ' . $user_id . ' ))';
        
        $event_date = db('crm_business')->where($between_time)->where('next_time', '>', 0)->field('next_time as start_time,business_id,name ')->select();
        unset($item);
        foreach ($event_date as $k6 => $v) {
            $event_date[$k6]['color'] = 6;
            $event_date[$k6]['type_id'] = 6;
            $event_date[$k6]['type'] = 'business';
            $event_date[$k6]['start_time'] = !empty($v['start_time']) ? strtotime($v['start_time']) * 1000 : null;
            $event_date[$k6]['stop_time'] = !empty($v['start_time']) ? strtotime($v['start_time']) * 1000 : null;
            $item[] = $v['start_time'] ? date('Y-m-d', $v['start_time']) : '';
            
        }
        $list_data['businessNext'] = $item ? array_values(array_unique($item)) : [];
        
        //需要联系的客户 next_time crm_customer
        $return_time = date('Y-m-d', $start_time);
        $return_end = date('Y-m-d', $end_time);
        $searchMap = function ($query) use ($return_time, $return_end) {
            $query->where('deal_date', array('between', [$return_time, $return_end]))
                ->whereOr('deal_date', array('gt', $return_end));
        };
        $between['create_user_id|owner_user_id'] = ['eq', $user_id];
        $event_date = db('crm_business')
            ->where('deal_date', '>', 0)
            ->where($between)
            ->where($searchMap)
            ->field('deal_date as start_time,business_id,name')
            ->group('start_time')
            ->select();
        
        unset($item);
        foreach ($event_date as $k7 => $v) {
            $event_date[$k7]['color'] = 7;
            $event_date[$k7]['type_id'] = 7;
            $event_date[$k7]['type'] = 'business';
            $event_date[$k7]['start_time'] = !empty($v['start_time']) ? strtotime($v['start_time']) * 1000 : null;
            $event_date[$k7]['stop_time'] = !empty($v['start_time']) ? strtotime($v['start_time']) * 1000 : null;
            $item[] = $v['start_time'] ? $v['start_time'] : '';
        }
        $list_data['business'] = $item ? array_values(array_unique($item)) : [];
        return $list_data ?: [];
    }
    
    /**
     * 日历上显示
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function listStatus($param)
    {
        $userId = $param['user_id'];
        
        if ($param['start_time'] && $param['end_time']) {
            $start_time = $param['start_time'];
            $end_time = $param['end_time'];
        } else {
            $start_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $end_time = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        }
        
        $list = Db::name('oa_event')->where(['end_time' => ['>', 0], 'start_time' => ['>', 0]])
            ->where(function ($query) use ($userId) {
                $query->whereOr('create_user_id', $userId);
                $query->whereOr(['owner_user_ids' => ['in', $userId]]);
            })->select();
        foreach ($list as $k => $v) {
            $list[$k]['start_time'] = $v['start_time'] ? date('Y-m-d', $v['start_time']) : null;
            $list[$k]['end_time'] = $v['end_time'] ? date('Y-m-d', $v['end_time']) : null;
        }
        return $list;
    }
    
    /**
     * 类型列表
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function schedule($param)
    {
        $item = db('admin_oa_schedule_relation')->where('user_id', $param['user_id'])->select();
        if ($item) {
            $list = db('admin_oa_schedule')->select();
            foreach ($list as $k => $v) {
                foreach ($item as $val) {
                    if ($val['schedule_id'] == $v['schedule_id'] && $val['type'] == 0) {
                        $list[$k]['is_select'] = 0;
                    }
                    if ($val['schedule_id'] == $v['schedule_id'] && $val['type'] == 1) {
                        $list[$k]['is_select'] = 1;
                    }
                }
                $list[$k]['type_id'] = $v['schedule_id'];
                $list[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : null;
            }
        } else {
            $list = db('admin_oa_schedule')->select();
        }
        $data['list'] = $list;
        return $data;
    }
    
    /**
     * 修改展示类型
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveSchedule($param)
    {
        $item = db('admin_oa_schedule_relation')->where('user_id', $param['user_id'])->select();
        $items = db('admin_oa_schedule')->select();
        $item_schedule_id = array_column($items, 'schedule_id');
        $schedule_id = array_diff($item_schedule_id, $param['schedule_id']);
        foreach ($param['schedule_id'] as $v) {
            if ($item) {
                $res = db('admin_oa_schedule_relation')->where(['user_id' => $param['user_id'], 'schedule_id' => $v])->find();
                if ($res) {
                    if ($res['type'] == 1) {
                        db('admin_oa_schedule_relation')->where(['user_id' => $param['user_id']])
                            ->whereIn('schedule_id', implode(',', $schedule_id))
                            ->update(['type' => 0]);
                    } else {
                        db('admin_oa_schedule_relation')->where(['user_id' => $param['user_id']])
                            ->whereIn('schedule_id', arrayToString($param['schedule_id']))
                            ->update(['type' => 1]);
                    }
                    
                } else {
                    db('admin_oa_schedule_relation')->where('user_id', $param['user_id'])->insert(['schedule_id' => $v, 'user_id' => $param['user_id'], 'type' => 1, 'create_time' => time()]);
                }
                
            } else {
                db('admin_oa_schedule_relation')->where('user_id', $param['user_id'])->insert(['schedule_id' => $v, 'user_id' => $param['user_id'], 'type' => 1, 'create_time' => time()]);
            }
        }
        $data = [];
        return $data;
    }
    
    /**
     * 创建日程信息
     *
     * @param array $param
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createData($param)
    {
        $todayTime = Time::today();
        # 设置日程参数
        $param['create_user_id'] = $param['user_id'];
        $param['owner_user_ids'] = arrayToString($param['owner_user_ids']);
        $param['start_time'] = !empty($param['start_time']) ? strtotime($param['start_time']) : $todayTime[0];
        $param['end_time'] = !empty($param['end_time']) ? strtotime($param['end_time']) : $todayTime[1];
        $param['create_time'] = time();
        $param['update_time'] = time();
        unset($param['user_id']);
        
        # 关联数据
        $relation = [];
        if (!empty($param['customer_ids'])) $relation['customer_ids'] = arrayToString($param['customer_ids']);
        if (!empty($param['contacts_ids'])) $relation['contacts_ids'] = arrayToString($param['contacts_ids']);
        if (!empty($param['business_ids'])) $relation['business_ids'] = arrayToString($param['business_ids']);
        if (!empty($param['contract_ids'])) $relation['contract_ids'] = arrayToString($param['contract_ids']);
        
        # 提醒数据
        $notice = $param['notice'];
        
        # 删除多余字段
        unset($param['customer_ids']);
        unset($param['contacts_ids']);
        unset($param['business_ids']);
        unset($param['contract_ids']);
        unset($param['notice']);
        
        if ($this->allowField(true)->save($param)) {
            $eventId = $this->event_id;
            
            # 提醒
            if (!empty($notice)) {
                $noticeData = [];
                foreach ($notice as $key => $value) {
                    $startTime = $param['start_time'];
                    if ($value['type'] == 1) $startTime = $param['start_time'] - ($value['value'] * 60);
                    if ($value['type'] == 2) $startTime = $param['start_time'] - ($value['value'] * 60 * 60);
                    if ($value['type'] == 3) $startTime = $param['start_time'] - ($value['value'] * 60 * 60 * 24);
                    $noticeData[] = [
                        'event_id' => $eventId,
                        'noticetype' => $value['type'],
                        'number' => $value['value'],
                        'start_time' => $startTime,
                        'stop_time' => $param['end_time']
                    ];
                }
                if (!empty($noticeData)) Db::name('oa_event_notice')->insertAll($noticeData);
            }
            
            # 关联数据
            if (!empty($relation)) {
                $relation['event_id'] = $eventId;
                $relation['status'] = 1;
                $relation['create_time'] = time();
                
                Db::name('oa_event_relation')->insert($relation);
            }
            
//            actionLog($eventId, $param['owner_user_ids'], '', '创建了日程');
            
            $data['event_id'] = $eventId;
            // 站内信
            $item = Db::name('oa_event_notice')->where('event_id', $eventId)->select();
            $messageModel = new Message();
            foreach ($item as $value) {
                if ($value['noticetype'] == '1') { //分
                    $dd = $param['start_time'] - ($value['number'] * 60);
                } else if ($value['noticetype'] == '2') { //时
                    $dd = $param['start_time'] - ($value['number'] * 60 * 60);
                } else if ($value['noticetype'] == '3') {//天
                    $dd = $param['start_time'] - ($value['number'] * 60 * 60 * 24);
                }
                $messageModel->send(
                    Message::EVENT_MESSAGE,
                    [
                        'title' => $param['title'],
                        'action_id' => $value['id'],
                        'advance_time' => $dd ?: 0
                    ],
                    stringToArray($param['owner_user_ids'])
                );
                
            }
            RecordActionLog($param['create_user_id'], 'oa_event', 'save',$param['title'], '','','添加了日程：'.$param['title']);
            return $data;
        } else {
            $this->error = '添加失败';
            return false;
        }
        
        
    }
    
    /**
     * 即将到期合同列表
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eventContract($param)
    {
        $user_id = $param['user_id'];
        $time = $this->eventDataTime($param);
        $return_time = `'` . date('Y-m-d', $time[0]) . `'`;
        if ($param['type'] == 1) {
            $between_time['contract.end_time'] = $return_time;
            $between_time['contract.create_user_id|contract.owner_user_id'] = ['eq', $user_id];
            $event_date = db('crm_contract')->alias('contract')
                ->join('__CRM_CUSTOMER__ customer', 'customer.customer_id = contract.customer_id', 'LEFT')
                ->join('__ADMIN_USER__ u', 'u.id = contract.owner_user_id', 'LEFT')
                ->where('contract.end_time', '>', 0)->where($between_time)
                ->field('contract.name as contract_name,contract.customer_id,contract.num,contract.contract_id,customer.name,contract.money,contract.start_time,contract.end_time,u.realname as owner_user_name')
                ->select();
            $count = db('crm_contract')->alias('contract')
                ->join('__ADMIN_USER__ user', 'user.id = contract.create_user_id', 'LEFT')
                ->join('__ADMIN_USER__ u', 'u.id = contract.owner_user_id', 'LEFT')
                ->where('contract.end_time', '>', 0)->where($between_time)->count();
            foreach ($event_date as $k => $v) {
                $event_date[$k]['start_time'] = $v['start_time'] ? $v['start_time'] : null;
                $event_date[$k]['end_time'] = $v['end_time'] ? $v['end_time'] : null;
            }
        } elseif ($param['type'] == 2) {
            $between_time['r.return_date'] = ['=', $return_time];
            $between_time['r.create_user_id|r.owner_user_id'] = ['eq', $user_id];
            $event_date = db('crm_receivables_plan')
                ->alias('r')
                ->join('__CRM_CUSTOMER__ customer', 'customer.customer_id = r.customer_id', 'LEFT')
                ->join('__CRM_CONTRACT__ contract', 'contract.contract_id = r.contract_id', 'LEFT')
                ->where(['r.status' => 0, 'r.return_date' => ['>', 0]])
                ->where($between_time)
                ->field('r.contract_id,r.customer_id,r.return_date,r.num as plan_num,r.return_type,customer.name as name,contract.num as contract_num')
                ->select();
            $count = db('crm_receivables_plan')
                ->alias('r')
                ->join('__CRM_CUSTOMER__ customer', 'customer.customer_id = r.customer_id', 'LEFT')
                ->join('__CRM_CONTRACT__ contract', 'contract.contract_id = r.contract_id', 'LEFT')
                ->where($between_time)
                ->where(['r.status' => 0, 'r.return_date' => ['>', 0]])->count();
            foreach ($event_date as $k => $v) {
                $event_date[$k]['return_date'] = $v['return_date'] ? $v['return_date'] : null;
            }
        }
        
        $data = [];
        $data['list'] = $event_date;
        $data['countData'] = $count;
        return $data;
    }
    
    /**
     * 续联系客户
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eventCustomer($param)
    {
        $user_id = $param['user_id'];
        $time = $this->eventDataTime($param);
        $return_time = `'` . strtotime(date('Y-m-d', $time[0])) . `'`;
        $return_end = `'` . strtotime(date('Y-m-d 23:59:59', $time[1])) . `'`;
        //需要联系的客户 next_time crm_customer
        $between_time['customer.next_time'] = ['between', [$return_time, $return_end]];
        $between_time['customer.create_user_id|customer.owner_user_id'] = ['eq', $user_id];
        $event_date = db('crm_customer')
            ->alias('customer')
            ->join('__ADMIN_USER__ user', 'user.id = customer.owner_user_id', 'LEFT')
            ->where('customer.next_time', '>', 0)->where($between_time)
            ->field('customer.customer_id,customer.next_time,customer.name,user.realname as owner_user_name,customer.create_time,customer.last_time')
            ->select();
        $count = db('crm_customer')
            ->alias('customer')
            ->join('__ADMIN_USER__ user', 'user.id = customer.owner_user_id', 'LEFT')
            ->where('customer.next_time', '>', 0)->where($between_time)->where($wherePool)->count();
        foreach ($event_date as $k => $v) {
            $event_date[$k]['next_time'] = $v['next_time'] ? date('Y-m-d H:i:s', $v['next_time']) : null;
            $event_date[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : null;
            $event_date[$k]['last_time'] = $v['last_time'] ? date('Y-m-d H:i:s', $v['last_time']) : null;
        }
        $data = [];
        $data['list'] = $event_date;
        $data['countData'] = $count;
        return $data;
    }
    
    /**
     * 续联系客户
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eventLeads($param)
    {
        $user_id = $param['user_id'];
        $time = $this->eventDataTime($param);
        $return_time = `'` . strtotime(date('Y-m-d', $time[0])) . `'`;
        $return_end = `'` . strtotime(date('Y-m-d 23:59:59', $time[1])) . `'`;
        $between_time['leads.next_time'] = ['between', [$return_time, $return_end]];
        $between_time['leads.create_user_id|leads.owner_user_id'] = ['eq', $user_id];
        $event_date = db('crm_leads')
            ->alias('leads')
            ->join('__ADMIN_USER__ user', 'user.id = leads.owner_user_id', 'LEFT')
            ->where($between_time)
            ->where(['leads.next_time' => ['>', 0], 'leads.is_transform' => 0])
            ->field('leads.leads_id,leads.next_time,leads.leads_id,leads.name,user.realname as owner_user_name,leads.create_time,leads.last_time')
            ->select();
        $count = db('crm_leads')
            ->alias('leads')
            ->join('__ADMIN_USER__ user', 'user.id = leads.owner_user_id', 'LEFT')
            ->where($between_time)
            ->where(['leads.next_time' => ['>', 0], 'leads.is_transform' => 0])->count();
        foreach ($event_date as $k => $v) {
            $event_date[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : null;
            $event_date[$k]['last_time'] = $v['last_time'] ? date('Y-m-d H:i:s', $v['last_time']) : null;
            $event_date[$k]['next_time'] = $v['next_time'] ? date('Y-m-d H:i:s', $v['next_time']) : null;
        }
        $data = [];
        $data['list'] = $event_date;
        $data['countData'] = $count;
        return $data;
    }
    
    /**
     * 计划回款
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eventBusiness($param)
    {
        $user_id = $param['user_id'];
        $time = $this->eventDataTime($param);
        $return_time = `'` . strtotime(date('Y-m-d', $time[0])) . `'`;
        $return_end = `'` . strtotime(date('Y-m-d 23:59:59', $time[1])) . `'`;
        //需要联系的客户 next_time crm_customer
        $between_time['business.next_time'] = ['between', [$return_time, $return_end]];
        $between_time['business.create_user_id|business.owner_user_id'] = ['eq', $user_id];
        
        $event_date = db('crm_business')
            ->alias('business')
            ->join('__ADMIN_USER__ user', 'user.id = business.owner_user_id', 'LEFT')
            ->where($between_time)
            ->where('business.next_time', '>', 0)
            ->field('business.business_id,business.next_time,business.business_id,business.name,business.last_time,business.create_time,user.realname as owner_user_name')
            ->select();
        $count = db('crm_business')
            ->alias('business')
            ->join('__ADMIN_USER__ user', 'user.id = business.owner_user_id', 'LEFT')
            ->where($between_time)
            ->where('business.next_time', '>', 0)->count();
        foreach ($event_date as $k => $v) {
            $event_date[$k]['next_time'] = $v['next_time'] ? date('Y-m-d H:i:s', $v['next_time']) : null;
            $event_date[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : null;
            $event_date[$k]['last_time'] = $v['last_time'] ? date('Y-m-d H:i:s', $v['last_time']) : null;
        }
        $data = [];
        $data['list'] = $event_date;
        $data['countData'] = $count;
        return $data;
    }
    
    /**
     * 预计成交商机
     * @param $param
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eventDealBusiness($param)
    {
        $user_id = $param['user_id'];
        $time = $this->eventDataTime($param);
        $return_time = `'` . date('Y-m-d', $time[0]) . `'`;
        $return_end = `'` . date('Y-m-d', $time[1]) . `'`;
        $between_time['business.deal_date'] = ['between', [$return_time, $return_end]];
        $between_time['business.create_user_id|business.owner_user_id'] = ['eq', $user_id];
        $event_date = db('crm_business')
            ->alias('business')
            ->join('__ADMIN_USER__ user', 'user.id = business.owner_user_id', 'LEFT')
            ->where('business.deal_date', '>', 0)
            ->where($between_time)
            ->field('business.business_id,business.deal_date,business.business_id,business.name,business.last_time,business.create_time,user.realname as owner_user_name')
            ->select();
        $count = db('crm_business')
            ->alias('business')
            ->join('__ADMIN_USER__ user', 'user.id = business.owner_user_id', 'LEFT')
            ->where('business.deal_date', '>', 0)->where($between_time)->count();
        foreach ($event_date as $k => $v) {
            $event_date[$k]['deal_date'] = $v['deal_date'] ?: null;
            $event_date[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : null;
            $event_date[$k]['last_time'] = $v['last_time'] ? date('Y-m-d H:i:s', $v['last_time']) : null;
        }
        $data = [];
        $data['list'] = $event_date;
        $data['countData'] = $count;
        return $data;
    }
    
    /**
     * 公共时间处理
     * @param $param
     */
    public function eventDataTime($param)
    {
        $userModel = new \app\admin\model\User();
        $recordModel = new \app\admin\model\Record();
        
        
        //默认本账户or 自定义用户id
        if ($param['start_time'] && $param['end_time']) {
            $start_time = $param['start_time'];
            $end_time = $param['end_time'];
            if (strlen($start_time) == 13) $start_time = $start_time / 1000;
            if (strlen($end_time) == 13) $end_time = $end_time / 1000;
        } else {
            $start_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $end_time = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        }
        $data = [];
        $data = [$start_time, $end_time];
        return $data;
    }
    
    /**
     * 编辑日程信息
     *
     * @param $param
     * @param string $event_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateDataById($param, $event_id = '')
    {
        $dataInfo = $this->getDataById($event_id, $param);
        $user_id=$param['user_id'];
        if (!$dataInfo) {
            $this->error = '数据不存在或已删除';
            return false;
        }
        if ($dataInfo['create_user_id'] != $param['user_id']) {
            $this->error = '没有编辑权限';
            return false;
        }
        $todayTime = Time::today();
        # 设置日程参数
        $param['create_user_id'] = $param['user_id'];
        $param['owner_user_ids'] = arrayToString($param['owner_user_ids']);
        $param['start_time'] = !empty($param['start_time']) ? strtotime($param['start_time']) : $todayTime[0];
        $param['end_time'] = !empty($param['end_time']) ? strtotime($param['end_time']) : $todayTime[1];
        $param['update_time'] = time();
        unset($param['user_id']);
        
        # 关联数据
        $relation = [];
        if (!empty($param['customer_ids'])) $relation['customer_ids'] = arrayToString($param['customer_ids']);
        if (!empty($param['contacts_ids'])) $relation['contacts_ids'] = arrayToString($param['contacts_ids']);
        if (!empty($param['business_ids'])) $relation['business_ids'] = arrayToString($param['business_ids']);
        if (!empty($param['contract_ids'])) $relation['contract_ids'] = arrayToString($param['contract_ids']);
        
        
        # 提醒数据
        $notice = $param['notice'];
        
        # 删除多余字段
        unset($param['customer_ids']);
        unset($param['contacts_ids']);
        unset($param['business_ids']);
        unset($param['contract_ids']);
        unset($param['notice']);
        
        if ($this->allowField(true)->save($param, ['event_id' => $event_id])) {
            $eventId = $this->event_id;
//            actionLog($event_id, $param['owner_user_ids'], '', '修改了日程');
            $list = db('oa_event_notice')->where('event_id', $eventId)->select();
            if ($list) {
                foreach ($list as $k => $v) {
                    Db::name('admin_message')->where(['type' => 9, 'action_id' => $v['id']])->delete();
                }
            }
            $item = Db::name('OaEventNotice')->where(['event_id' => $eventId])->delete();
            # 提醒
            if (!empty($notice)) {
                $noticeData = [];
                $startTime = $param['start_time'];
                foreach ($notice as $key => $value) {
                    if ($value['type'] == 1) $startTime = $param['start_time'] - ($value['number'] * 60);
                    if ($value['type'] == 2) $startTime = $param['start_time'] - ($value['number'] * 60 * 60);
                    if ($value['type'] == 3) $startTime = $param['start_time'] - ($value['number'] * 60 * 60 * 24);
                    
                    $noticeData[] = [
                        'event_id' => $eventId,
                        'noticetype' => $value['type'],
                        'number' => $value['value'],
                        'start_time' => $startTime,
                        'stop_time' => $param['end_time']
                    ];
                }
                if (!empty($noticeData)) {
                    $item = Db::name('oa_event_notice')->insertAll($noticeData);
                    if ($item) {
                       
                        $item = Db::name('oa_event_notice')->where('event_id', $eventId)->select();
                        foreach ($item as $val) {
                            if ($value['noticetype'] == '1') { //分
                                $dd = strtotime($param['start_time']) - ($val['number'] * 60);
                            } else if ($val['noticetype'] == '2') { //时
                                $dd = strtotime($param['start_time']) - ($val['number'] * 60 * 60);
                            } else if ($val['noticetype'] == '3') {//天
                                $dd = strtotime($param['start_time']) - ($val['number'] * 60 * 60 * 24);
                            }
                            // 站内信
                            (new Message())->send(
                                Message::EVENT_MESSAGE,
                                [
                                    'title' => $param['title'],
                                    'action_id' => $val['id'],
                                    'advance_time' => $dd ?: 0
                                ],
                                stringToArray($param['owner_user_ids'])
                            );
            
                        }
                    }
                }
            }
            
           
            $data['event_id'] = $event_id;
            if (Db::name('OaEventRelation')->where(['event_id' => $event_id])->find()) {
                Db::name('OaEventRelation')->where(['event_id' => $event_id])->update($relation);
            } else {
                if (!empty($relation)) {
                    $relation['event_id'] = $eventId;
                    $relation['status'] = 1;
                    $relation['create_time'] = time();
                    Db::name('OaEventRelation')->where(['event_id' => $event_id])->insert($relation);
                }
            }
            RecordActionLog($user_id, 'oa_event','update', $param['title'], '','','修改了日程：'.$param['title']);
            return $data;
        } else {
            $this->error = '编辑失败';
            return false;
        }
    }
    
    /**
     * 日程数据
     *
     * @param string $eventId
     * @return Common|array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDataById($eventId)
    {
        # 日程数据
        $eventData = Db::name('oa_event')->where('event_id', $eventId)->find();
        
        # 颜色类型
        $eventData['color'] = Db::name('admin_oa_schedule')->where('schedule_id', $eventData['schedule_id'])->value('color');
        
        # 创建人信息
        $eventData['create_user_name'] = Db::name('admin_user')->where('id', $eventData['create_user_id'])->value('realname');
        
        # 参与人信息
        $eventData['owner_user_info'] = Db::name('admin_user')->field(['id', 'realname'])->whereIn('id', trim($eventData['owner_user_ids'], ','))->select();
        
        # 处理日程的日期数据
        $eventData['start_time'] = !empty($eventData['start_time']) ? date('Y-m-d H:i:s', $eventData['start_time']) : '';
        $eventData['end_time'] = !empty($eventData['end_time']) ? date('Y-m-d H:i:s', $eventData['end_time']) : '';
        $eventData['create_time'] = !empty($eventData['create_time']) ? date('Y-m-d H:i:s', $eventData['create_time']) : '';
        $eventData['update_time'] = !empty($eventData['update_time']) ? date('Y-m-d H:i:s', $eventData['update_time']) : '';
        
        # 日程提醒数据
        $noticeData = Db::name('oa_event_notice')->where('event_id', $eventId)->select();
        
        # 整理日程提醒数据
        $eventData['notice'] = [];
        foreach ($noticeData as $key => $value) {
            $eventData['notice'][] = [
                'type' => $value['noticetype'],
                'value' => $value['number']
            ];
        }
        
        # 关联客户数据
        $relationData = Db::name('oa_event_relation')->where('event_id', $eventId)->find();
        
        # 关联的客户数据
        $eventData['customer'] = [];
        if (!empty($relationData['customer_ids'])) {
            $eventData['customer'] = Db::name('crm_customer')->field(['customer_id', 'name'])->whereIn('customer_id', trim($relationData['customer_ids'], ','))->select();
        }
        # 关联的联系人数据
        $eventData['contacts'] = [];
        if (!empty($relationData['contacts_ids'])) {
            $eventData['contacts'] = Db::name('crm_contacts')->field(['contacts_id', 'name'])->whereIn('contacts_id', trim($relationData['contacts_ids'], ','))->select();
        }
        # 关联的商机数据
        $eventData['business'] = [];
        if (!empty($relationData['business_ids'])) {
            $eventData['business'] = Db::name('crm_business')->field(['business_id', 'name'])->whereIn('business_id', trim($relationData['business_ids'], ','))->select();
        }
        # 关联的合同数据
        $eventData['contract'] = [];
        if (!empty($relationData['contract_ids'])) {
            $eventData['contract'] = Db::name('crm_contract')->field(['contract_id', 'name'])->whereIn('contract_id', trim($relationData['contract_ids'], ','))->select();
        }
        
        return $eventData;
    }
    
    //根据ID 删除日程
    public function delDataById($param)
    {
        $dataInfo = $this->get($param['event_id']);
        if (!$dataInfo) {
            $this->error = '数据不存在或已删除';
            return false;
        }
        
        if ($dataInfo['create_user_id'] != $param['user_id']) {
            $this->error = '没有编辑权限';
            return false;
        }
        
        $map['event_id'] = $param['event_id'];
        $map['create_user_id'] = $param['user_id'];
        $flag = $this->where($map)->delete();
        if ($flag) {
            Db::name('OaEventNotice')->where(['event_id' => $param['event_id']])->delete();
            Db::name('OaEventRelation')->where(['event_id' => $param['event_id']])->delete();
            return true;
        } else {
            $this->error = '删除失败';
            return false;
        }
    }
}