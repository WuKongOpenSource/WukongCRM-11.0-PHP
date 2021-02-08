<?php
// +----------------------------------------------------------------------
// | Description: 日志
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\oa\model;

use think\Db;
use app\admin\model\Common;
use app\admin\model\Message;
use app\admin\model\User;
use think\Request;
use think\Validate;

class Log extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    protected $name = 'oa_log';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $autoWriteTimestamp = true;

    /**
     * [getDataList 日志list]
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     * @author Michael_xu
     */
    public function getDataList($request)
    {
        $userModel = new \app\admin\model\User();
        $structureModel = new \app\admin\model\Structure();
        $fileModel = new \app\admin\model\File();
        $commonModel = new \app\admin\model\Comment();
        $recordModel = new \app\admin\model\Record();

        $user_id = $request['read_user_id'];
        $by = $request['by'] ?: '';

        $map = [];
        $search = $request['search'];
        if (isset($request['search']) && $request['search']) {
            //普通筛选
            $searchMap = function ($query) use ($search) {
                $query->where('log.content', array('like', '%' . $search . '%'))
                    ->whereOr('log.tomorrow', array('like', '%' . $search . '%'))
                    ->whereOr('log.question', array('like', '%' . $search . '%'));
            };
        }
        if ($request['category_id']) {
            $map['log.category_id'] = $request['category_id'];
        }
        if ($request['type']) {
            $timeAry = ByDateTime($request['type']);
            $between_time = [$timeAry[0], $timeAry[1]];
            $map['log.create_time'] = ['between', $between_time];
        } else {
            //自定义时间
            $start_time = $request['start_time'] ? strtotime($request['start_time'].' 00:00:00') : strtotime(date('Y-m-01', time()));
            $end_time = $request['end_time'] ? strtotime($request['end_time'].' 23:59:59') : strtotime(date('Y-m-01', time()) . ' +1 month -1 day');
            $map['log.create_time'] = ['between', [$start_time, $end_time]];
        }
        $requestData = $this->requestData();
        //获取权限范围内的员工
        $auth_user_ids = getSubUserId();
        $dataWhere['user_id'] = $user_id;
        $dataWhere['structure_id'] = $request['structure_id'];
        $dataWhere['auth_user_ids'] = $auth_user_ids;
        $logMap = '';
        if ($request['send_user_id'] != '') {
            $map['log.create_user_id'] = ['in', trim(arrayToString($request['send_user_id']), ',')];
        }
        switch ($by) {
            case 'me' :
                $map['log.create_user_id'] = $user_id;
                break;
            case 'other':
                $logMap = function ($query) use ($dataWhere) {
                    $query->where('log.send_user_ids', array('like', '%,' . $dataWhere['user_id'] . ',%'))
                        ->whereOr('log.send_structure_ids', array('like', '%,' . $dataWhere['structure_id'] . ',%'));
                };
                break;
            default :
                $logMap = function ($query) use ($dataWhere) {
                    $query->where('log.create_user_id', array('in', implode(',', $dataWhere['auth_user_ids'])))
                        ->whereOr('log.send_user_ids', array('like', '%,' . $dataWhere['user_id'] . ',%'))
                        ->whereOr('log.send_structure_ids', array('like', '%,' . $dataWhere['structure_id'] . ',%'));
                };
                break;
        }
        $list = Db::name('OaLog')
            ->where($map)
            ->where($logMap)
            ->where($searchMap)
            ->alias('log')
            ->join('__ADMIN_USER__ user', 'user.id = log.create_user_id', 'LEFT')
            ->page($request['page'], $request['limit'])
            ->field('log.*,user.realname,user.thumb_img')
            ->order('log.update_time desc')
            ->select();
        $dataCount = $this->alias('log')->where($map)->where($logMap)->where($searchMap)->count();
        foreach ($list as $k => $v) {
            $list[$k]['create_user_info']['realname'] = $v['realname'] ?: '';
            $list[$k]['create_user_info']['id'] = $v['create_user_id'] ?: '';
            $list[$k]['create_user_info']['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
            //附件、图片
            $fileList = [];
            $imgList = [];
            $where = [];
            $where['module'] = 'oa_log';
            $where['module_id'] = $v['log_id'];
            $newFileList = [];
            $newFileList = $fileModel->getDataList($where);
            foreach ($newFileList['list'] as $val) {
                if ($val['types'] == 'file') {
                    $fileList[] = $val;
                } else {
                    $imgList[] = $val;
                }
            }
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $list[$k]['fileList'] = $fileList ?: [];
            $list[$k]['imgList'] = $imgList ?: [];
            $list[$k]['sendUserList'] = $userModel->getDataByStr($v['send_user_ids']) ?: [];
            $list[$k]['sendStructList'] = $structureModel->getDataByStr($v['send_structure_ids']) ?: [];
            $param['type_id'] = $v['log_id'];
            $param['type'] = 'oa_log';
            $list[$k]['replyList'] = $commonModel->read($param);
            //相关业务
            $relationArr = $recordModel->getListByRelationId('log', $v['log_id']);
            $list[$k]['businessList'] = $relationArr['businessList'];
            $list[$k]['contactsList'] = $relationArr['contactsList'];
            $list[$k]['contractList'] = $relationArr['contractList'];
            $list[$k]['customerList'] = $relationArr['customerList'];

            if ($v['is_relation'] == 1) {
                $list[$k]['bulletin']['customerNum'] = $v['save_customer'];
                $list[$k]['bulletin']['businessNum'] = $v['save_business'];
                $list[$k]['bulletin']['contractNum'] = $v['save_contract'];
                $list[$k]['bulletin']['receivablesMoneyNum'] = $v['save_receivables'];
                $list[$k]['bulletin']['recordNum'] = $v['save_activity'];
            } else {
                $list[$k]['bulletin'] = 0;
            }

            $is_update = 0;
            $is_delete = 0;
            //3天内的日志可删,可修改
            if (($v['create_user_id'] == $user_id) && date('Ymd', $v['create_time']) > date('Ymd', (strtotime(date('Ymd', time())) - 86400 * 3))) {
                $is_update = 1;
                $is_delete = 1;
            }
            if (in_array($v['create_user_id'], $auth_user_ids)) {
                $is_delete = 1;
            }
            $permission['is_update'] = $is_update;
            $permission['is_delete'] = $is_delete;
            $list[$k]['permission'] = $permission;
            //已读
            $read_user_ids = stringToArray($v['read_user_ids']);
            $is_read = 0;
            if (in_array($user_id, $read_user_ids)) {
                $is_read = 1;
            }
            $list[$k]['is_read'] = $is_read;
        }
        $data = [];
        $data['page']['list'] = $list;
        $data['page']['dataCount'] = $dataCount ?: 0;
        if ($request['page'] != 1 && (int)($request['page'] * $request['limit']) >= (int)$dataCount) {
            $data['page']['firstPage'] = false;
            $data['page']['lastPage'] = true;
        } else if ($request['page'] != 1 && (int)($request['page'] * $request['limit']) < (int)$dataCount) {
            $data['page']['firstPage'] = false;
            $data['page']['lastPage'] = false;
        } else if ($request['page'] == 1) {
            $data['page']['firstPage'] = true;
            $data['page']['lastPage'] = false;
        }

        return $data;
    }


    // 创建日志信息
    public function createData($param)
    {
        $userModel = new \app\admin\model\User();
        $recordModel = new \app\admin\model\Record();
        $fileArr = $param['file']; //接收表单附件
        unset($param['file']);
        $senduserArray = $param['send_user_ids'] ?: [];
        $param['send_user_ids'] = $param['send_user_ids'] ? arrayToString($param['send_user_ids']) : '';
        $param['send_structure_ids'] = $param['send_structure_ids'] ? arrayToString($param['send_structure_ids']) : '';
        $param['is_relation'] = $param['is_relation'] ?: 0;
        $rdata = [];
        //关联业务
        $rdata['customer_ids'] = $param['customer_ids'] ? arrayToString($param['customer_ids']) : '';
        $rdata['contacts_ids'] = $param['contacts_ids'] ? arrayToString($param['contacts_ids']) : '';
        $rdata['business_ids'] = $param['business_ids'] ? arrayToString($param['business_ids']) : '';
        $rdata['contract_ids'] = $param['contract_ids'] ? arrayToString($param['contract_ids']) : '';
        $arr = ['customer_ids', 'contacts_ids', 'business_ids', 'contract_ids'];
        foreach ($arr as $value) {
            unset($param[$value]);
        }

        if ($param['category_id'] == 1) {
            $param['title'] = date('Y-m-d') . '-日报';
        } else if ($param['category_id'] == 2) {
            $param['title'] = date('Y-m-d') . '-周报';
        } else if ($param['category_id'] == 3) {
            $param['title'] = date('Y-m-d') . '-月报';
        }

        if ($this->data($param)->allowField(true)->save()) {
            $log_id = $this->log_id;
            //操作记录
            actionLog($log_id, $param['send_user_ids'], $param['send_structure_ids'], '创建了日志');
            //处理附件关系
            if ($fileArr) {
                $fileModel = new \app\admin\model\File();
                $resData = $fileModel->createDataById($fileArr, 'oa_log', $log_id);
                if ($resData == false) {
                    $this->error = '附件上传失败';
                    return false;
                }
            }

            $temp = User::where(['structure_id' => ['in', $param['send_structure_ids']]])->column('id');
            (new Message())->send(
                Message::LOG_SEND,
                [
                    'title' => $param['title'],
                    'action_id' => $log_id
                ],
                array_merge($temp, $senduserArray)
            );
            //返回数据，前端动态追加使用
            $data = [];
            $data['log_id'] = $log_id;
            $data = $param;

            if (count($fileArr)) {
                $fileList = Db::name('AdminFile')->where('file_id in (' . implode(',', $fileArr) . ')')->select();
                foreach ($fileList as $k => $v) {
                    $fileList[$k]['file_path'] = $v['file_path'] ? getFullPath($v['file_path']) : '';
                }
            }
            $data['fileList'] = $fileList ?: array();
            //发送人
            $data['sendUserList'] = $param['send_user_ids'] ? $userModel->getListByStr($param['send_user_ids']) : [];
            //发送部门
            $data['sendStructureList'] = $param['send_structure_ids'] ? $userModel->getListByStr($param['send_structure_ids']) : [];
            $data['log_id'] = $log_id;

            $rdata['log_id'] = $log_id;
            $rdata['status'] = 1;
            $rdata['create_time'] = time();
            //关联业务
            Db::name('OaLogRelation')->insert($rdata);

            //相关业务
            $relationArr = $recordModel->getListByRelationId('log', $log_id);
            $data['businessList'] = $relationArr['businessList'];
            $data['contactsList'] = $relationArr['contactsList'];
            $data['contractList'] = $relationArr['contractList'];
            $data['customerList'] = $relationArr['customerList'];

            # 添加活动记录
            if (!empty($rdata['customer_ids']) || !empty($rdata['contacts_ids']) || !empty($rdata['business_ids']) || !empty($rdata['contract_ids'])) {
                Db::name('crm_activity')->insert([
                    'type' => 2,
                    'activity_type' => 8,
                    'activity_type_id' => $log_id,
                    'content' => !empty($param['title']) ? $param['title'] : '日志',
                    'create_user_id' => $param['create_user_id'],
                    'update_time' => time(),
                    'create_time' => time(),
                    'customer_ids' => !empty($rdata['customer_ids']) ? trim($rdata['customer_ids'], ',') : '',
                    'contacts_ids' => !empty($rdata['contacts_ids']) ? trim($rdata['contacts_ids'], ',') : '',
                    'business_ids' => !empty($rdata['business_ids']) ? trim($rdata['business_ids'], ',') : '',
                    'contract_ids' => !empty($rdata['contract_ids']) ? trim($rdata['contract_ids'], ',') : '',
                ]);
            }

            return $data;
        } else {
            $this->error = '添加失败';
            return false;
        }
    }

    /**
     * 编辑日志信息
     * @param
     * @return
     * @author Michael_xu
     */
    public function updateDataById($param, $log_id = '')
    {
        $dataInfo = $this->getDataById($log_id);
        if (!$dataInfo) {
            $this->error = '数据不存在或已删除';
            return false;
        }
        if ($dataInfo['create_time'] < time() - 3600 * 24 * 3) {
            $this->error = '超过时效，不可修改';
            return false;
        }
        //关联业务
        $rdata['customer_ids'] = $param['customer_ids'] ? arrayToString($param['customer_ids']) : '';
        $rdata['contacts_ids'] = $param['contacts_ids'] ? arrayToString($param['contacts_ids']) : '';
        $rdata['business_ids'] = $param['business_ids'] ? arrayToString($param['business_ids']) : '';
        $rdata['contract_ids'] = $param['contract_ids'] ? arrayToString($param['contract_ids']) : '';

        $arr = ['customer_ids', 'contacts_ids', 'business_ids', 'contract_ids'];
        foreach ($arr as $value) {
            unset($param[$value]);
        }
        //过滤不能修改的字段
        $unUpdateField = ['create_user_id', 'is_deleted', 'delete_time'];
        foreach ($unUpdateField as $v) {
            unset($param[$v]);
        }
        $fileArr = $param['file']; //接收表单附件
        unset($param['file']);
        $param['send_user_ids'] = $param['send_user_ids'] ? arrayToString($param['send_user_ids']) : '';
        $param['send_structure_ids'] = $param['send_structure_ids'] ? arrayToString($param['send_structure_ids']) : '';

        if ($this->allowField(true)->save($param, ['log_id' => $log_id])) {
            //操作日志
            Db::name('AdminActionLog')->where(['action_id' => $log_id])->update(['join_user_ids' => $this->send_user_ids, 'structure_ids' => $this->send_structure_ids]);
            actionLog($log_id, $this->send_user_ids, $this->send_structure_ids, '修改了日志');
            //处理附件关系
            if ($fileArr) {
                $fileModel = new \app\admin\model\File();
                $resData = $fileModel->createDataById($fileArr, 'oa_log', $log_id);
                if ($resData == false) {
                    $this->error = '附件上传失败';
                    return false;
                }
            }
            $data = [];
            $data['log_id'] = $log_id;
            Db::name('OaLogRelation')->where('log_id = ' . $log_id)->update($rdata);
            # 删除活动记录
            Db::name('crm_activity')->where(['activity_type' => 8, 'activity_type_id' => $log_id])->delete();
            # 添加活动记录
            if (!empty($rdata['customer_ids']) || !empty($rdata['contacts_ids']) || !empty($rdata['business_ids']) || !empty($rdata['contract_ids'])) {
                Db::name('crm_activity')->insert([
                    'type' => 2,
                    'activity_type' => 8,
                    'activity_type_id' => $log_id,
                    'content' => !empty($param['title']) ? $param['title'] : '日志',
                    'create_user_id' => $param['user_id'],
                    'update_time' => time(),
                    'create_time' => time(),
                    'customer_ids' => !empty($rdata['customer_ids']) ? trim($rdata['customer_ids'], ',') : '',
                    'contacts_ids' => !empty($rdata['contacts_ids']) ? trim($rdata['contacts_ids'], ',') : '',
                    'business_ids' => !empty($rdata['business_ids']) ? trim($rdata['business_ids'], ',') : '',
                    'contract_ids' => !empty($rdata['contract_ids']) ? trim($rdata['contract_ids'], ',') : ''
                ]);
            }
            return $data;
        } else {
            $this->error = '编辑失败';
            return false;
        }
    }

    /**
     * 日志数据
     * @param  $id 日志ID
     * @return
     */
    public function getDataById($id = '')
    {
        $fileModel = new \app\admin\model\File();
        $userModel = new \app\admin\model\User();
        $structureModel = new \app\admin\model\Structure();
        $commonModel = new \app\admin\model\Comment();

        $map['log.log_id'] = $id;
        $data_view = db('oa_log')
            ->where($map)
            ->alias('log')
            ->join('__ADMIN_USER__ user', 'user.id = log.create_user_id', 'LEFT');
        $dataInfo = $data_view->field('log.*,user.realname,user.thumb_img')->find();
        if (!$dataInfo) {
            $this->error = '暂无此数据';
            return false;
        }

        $relation = Db::name('OaLogRelation')->where('log_id =' . $id)->find();
        $BusinessModel = new \app\crm\model\Business(); //商机
        $dataInfo['businessList'] = $relation['business_ids'] ? $BusinessModel->getDataByStr($relation['business_ids']) : [];
        $ContactsModel = new \app\crm\model\Contacts();//联系人
        $dataInfo['contactsList'] = $relation['contacts_ids'] ? $ContactsModel->getDataByStr($relation['contacts_ids']) : [];
        $ContractModel = new \app\crm\model\Contract();//合同
        $dataInfo['contractList'] = $relation['contract_ids'] ? $ContractModel->getDataByStr($relation['contract_ids']) : [];
        $CustomerModel = new \app\crm\model\Customer();//客户
        $dataInfo['customerList'] = $relation['customer_ids'] ? $CustomerModel->getDataByStr($relation['customer_ids']) : [];

        $dataInfo['create_user_info']['realname'] = $dataInfo['realname'] ?: '';
        $dataInfo['create_user_info']['id'] = $dataInfo['create_user_id'] ?: '';
        $dataInfo['create_user_info']['thumb_img'] = $dataInfo['thumb_img'] ? getFullPath($dataInfo['thumb_img']) : '';
        //附件、图片
        $where['module'] = 'oa_log';
        $where['module_id'] = $id;
        $newFileList = $fileModel->getDataList($where);
        foreach ($newFileList['list'] as $val) {
            if ($val['types'] == 'file') {
                $fileList[] = $val;
            } else {
                $imgList[] = $val;
            }
        }
        $dataInfo['fileList'] = $fileList ?: [];
        $dataInfo['imgList'] = $imgList ?: [];
        $dataInfo['sendUserList'] = $userModel->getDataByStr($dataInfo['send_user_ids']) ?: [];
        $dataInfo['sendStructList'] = $structureModel->getDataByStr($dataInfo['send_structure_ids']) ?: [];
        $param['type_id'] = $id;
        $param['type'] = 'oa_log';
        $dataInfo['replyList'] = $commonModel->read($param);
        return $dataInfo;
    }

    /**
     * 日志删除
     *
     * @param string $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delDataById($param)
    {
        $map['log_id'] = $param['log_id'];
        $dataInfo = $this->get($map['log_id']);
        if (!$dataInfo) {
            $this->error = '数据不存在或已删除';
            return false;
        }
        $flag = Db::name('OaLog')->where($map)->delete();
        if ($flag) {
            $fileModel = new \app\admin\model\File();
            $commentModel = new \app\admin\model\Comment();
            //删除关联附件
            $fileModel->delRFileByModule('oa_log', $param['log_id']);
            //删除相关评论
            $commentModel->delDataById(['type' => 'oa_log', 'type_id' => $param['log_id']]);
            actionLog($param['log_id'], $dataInfo['send_user_ids'], $dataInfo['send_structure_ids'], '删除了日志');
            # 删除活动记录
            Db::name('crm_activity')->where(['activity_type' => 8, 'activity_type_id' => $param['log_id']])->delete();
            return true;
        } else {
            $this->error = '操作失败';
            return false;
        }
    }
}