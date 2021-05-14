<?php
// +----------------------------------------------------------------------
// | Description: 审批
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\oa\model;

use think\Db;
use app\admin\model\Common;
use app\admin\model\Message;
use think\Request;
use think\Validate;
use app\admin\model\Field;

class Examine extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    protected $name = 'oa_examine';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $autoWriteTimestamp = true;
    private $statusArr = ['待审核', '审核中', '审核通过', '已拒绝', '已撤回'];
    
    /**
     * [getDataList 审批list]
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return
     * @author Michael_xu
     */
    public function getDataList($request)
    {
        $userModel = new \app\admin\model\User();
        $fileModel = new \app\admin\model\File();
        $recordModel = new \app\admin\model\Record();
        
        $examine_by = $request['examine_by']; //1待我审批 2我已审批 all 全部
        $user_id = $request['user_id'];
        $bi = $request['bi_types'];
        $check_status = $request['check_status']; //0 待审批 2 审批通过 4 审批拒绝 all 全部
        unset($request['by']);
        unset($request['bi_types']);
        unset($request['user_id']);
        unset($request['check_status']);
        unset($request['examine_by']);
        $request = $this->fmtRequest($request);
        $map = $request['map'] ?: [];
        if (isset($map['search']) && $map['search']) {
            //普通筛选
            $map['examine.content'] = ['like', '%' . $map['search'] . '%'];
        } else {
            $map = where_arr($map, 'oa', 'examine', 'index'); //高级筛选
        }
        unset($map['search']);
        //审批类型
        $map['examine.category_id'] = $map['examine.category_id'] ?: array('gt', 0);
        
        $map_str = '';
        $logmap = '';
        switch ($examine_by) {
            case 'all' :
                //如果超管则能看到全部
                if (!isSuperAdministrators($user_id)) {
                    $map_str = "(( check_user_id LIKE '%," . $user_id . ",%' OR check_user_id = " . $user_id . " ) OR ( flow_user_id LIKE '%," . $user_id . ",%'  OR `flow_user_id` = " . $user_id . " ) )";
                }
                $map['examine.create_user_id'] = ['<>',$user_id];
                break;
            case '1' :
                $map['check_user_id'] = [['like', '%,' . $user_id . ',%']];
                break; //待审
            case '2' :
                $map_str = "(( check_user_id LIKE '%," . $user_id . ",%' OR check_user_id = " . $user_id . " )
                 OR ( flow_user_id LIKE '%," . $user_id . ",%'  OR `flow_user_id` = " . $user_id . " ) )";
//                $map['flow_user_id'] = [['like', '%,' . $user_id . ',%'], ['eq', $user_id], 'or'];
                break; //已审
            default:
                $map['examine.create_user_id'] = $user_id;
                break;
        }
        $order = 'examine.create_time desc,examine.update_time desc';
        //发起时间
        if ($map['examine.between_time'][0] && $map['examine.between_time'][1]) {
            $start_time = $map['examine.between_time'][0];
            $end_time = $map['examine.between_time'][1];
            $map['examine.create_time'] = array('between', array($start_time, $end_time));
        }
        unset($map['examine.between_time']);
        
        //审核状态 0 待审批 2 审批通过 4 审批拒绝 all 全部
        if (isset($check_status)) {
            if ($check_status == 'all') {
                $map['examine.check_status'] = ['egt', 0];
                if (isSuperAdministrators($user_id)) {
                    unset($map['examine.create_user_id']);
                }
            } elseif ($check_status == 4) {
                $map['examine.check_status'] = ['eq', 3];
            } elseif ($check_status == 0) {
                $map['examine.check_status'] = ['<=', 1];
            } else {
                $map['examine.check_status'] = $check_status;
            }
        }else{
            if ($examine_by == 'all') {
                $map['examine.check_status'] = ['egt', 0];
            } elseif ($examine_by == 1) {
                $map['examine.check_status'] = ['elt', 1];
            } elseif($examine_by == 2) {
                $map['examine.check_status'] = ['egt', 2];
            }
        }
        $join = [
            ['__ADMIN_USER__ user', 'user.id = examine.create_user_id', 'LEFT'],
            ['__OA_EXAMINE_CATEGORY__ examine_category', 'examine_category.category_id = examine.category_id', 'LEFT'],
        ];
        $list_view = db('oa_examine')
            ->alias('examine')
            ->where($map_str)
            ->where($map)
            ->join($join);
        
        $list = $list_view
            ->page($request['page'], $request['limit'])
            ->field('examine.*,user.realname,user.thumb_img,examine_category.title as category_name,examine_category.category_id as examine_config,examine_category.icon as examineIcon')
            ->order($order)
            ->select();
        $dataCount = $this->alias('examine')
            ->where($map_str)
            ->where($map)
            ->join($join)
            ->count('examine_id');
        $admin_user_ids = $userModel->getAdminId();
        
        foreach ($list as $k => $v) {
            $list[$k]['create_user_info'] = $userModel->getUserById($v['create_user_id']);
            $causeCount = 0;
            $causeTitle = '';
            $duration = $v['duration'] ?: '0.0';
            $money = $v['money'] ?: '0.00';
            if (in_array($v['category_id'], ['3', '5'])) {
                $causeCount = db('oa_examine_travel')->where(['examine_id' => $v['examine_id']])->count() ?: 0;
                if ($v['category_id'] == 3) $causeTitle = $causeCount . '个行程,共' . $duration . '天';
                if ($v['category_id'] == 5) $causeTitle = $causeCount . '个报销事项,共' . $money . '元';
                
                //附件
                $fileList = [];
                $imgList = [];
                $where = [];
                $where['module'] = 'oa_examine_travel';
                $where['module_id'] = $v['travel_id'];
                $newFileList = [];
                $newFileList = $fileModel->getDataList($where);
                foreach ($newFileList['list'] as $val) {
                    if ($val['types'] == 'file') {
                        $fileList[] = $val;
                    } else {
                        $imgList[] = $val;
                    }
                }
                $list[$k]['fileList'] = $fileList ?: [];
                $list[$k]['imgList'] = $imgList ?: [];
                
            }
            $list[$k]['causeTitle'] = $causeTitle;
            $list[$k]['causeCount'] = $causeCount ?: 0;
            
            //关联业务
            $relationArr = [];
            $relationArr = $recordModel->getListByRelationId('examine', $v['examine_id']);
            $list[$k]['businessList'] = $relationArr['businessList'];
            $list[$k]['contactsList'] = $relationArr['contactsList'];
            $list[$k]['contractList'] = $relationArr['contractList'];
            $list[$k]['customerList'] = $relationArr['customerList'];
            
            //附件
            $fileList = [];
            $imgList = [];
            $where = [];
            $where['module'] = 'oa_examine';
            $where['module_id'] = $v['examine_id'];
            $newFileList = [];
            $newFileList = $fileModel->getDataList($where);
            foreach ($newFileList['list'] as $val) {
                if ($val['types'] == 'file') {
                    $fileList[] = $val;
                } else {
                    $imgList[] = $val;
                }
            }
            $list[$k]['fileList'] = $fileList ?: [];
            $list[$k]['imgList'] = $imgList ?: [];
            
            //创建人或管理员有撤销权限
            $permission = [];
            $is_recheck = 0;
            $is_update = 0;
            $is_delete = 0;
            $is_check = 0;
            //创建人或负责人或管理员有撤销权限
            if ($v['create_user_id'] == $user_id || in_array($user_id, $admin_user_ids)) {
                if (!in_array($v['check_status'], ['2', '3', '4'])) {
                    $is_recheck = 1;
                }
            }
            
            //创建人（失败、撤销状态时可编辑）
            if ($v['create_user_id'] == $user_id && in_array($v['check_status'], ['3', '4'])) {
                $is_update = 1;
                $is_delete = 1;
                $is_check = 0;
                $is_end = 0;
            }
            //添加审批相关信息
            $examineFlowModel = new \app\admin\model\ExamineFlow();
            $examineFlowData = $examineFlowModel->getFlowByTypes($user_id, 'oa_examine', $v['category_id']);
            //获取审批人信息
            if ($examineFlowData['config'] == 1) {
                //固定审批流
                $examineStepModel = new \app\admin\model\ExamineStep();
//                $nextStepData = $examineStepModel->nextStepUser($user_id, $examineFlowData['flow_id'], 'oa_examine', 0, 0, 0);
                $is_check = in_array($user_id, stringToArray($v['check_user_id'])) && in_array($v['check_status'], [0, 1]) ? 1 : 0;
                $is_end = 1;
            } else {
                $is_end = 0;
                if ($v['check_user_id'] == (',' . $user_id . ',') && in_array($v['check_status'], [0, 1])) {
                    $is_check = 1;
                } else {
                    $is_check = 0;
                }
            }
            if($v['check_status']==4){
                $usernames = db('admin_user')->whereIn('id', stringToArray($user_id))->column('realname');
            }else{
                $usernames = db('admin_user')->whereIn('id', stringToArray($v['check_user_id']))->column('realname');
            }
            
            $list[$k]['examine_name'] = implode($usernames, '，');
            
            $permission['is_check'] = $is_check;
            $permission['is_delete'] = $is_delete;
            $permission['is_recheck'] = $is_recheck;
            $permission['is_update'] = $is_update;
            $list[$k]['permission'] = $permission;
            $list[$k]['config'] = $is_end;
            $list[$k]['check_status_info'] = $this->statusArr[(int)$v['check_status']];
            $list[$k]['create_time'] = !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : null;
            $list[$k]['update_time'] = !empty($v['update_time']) ? date('Y-m-d H:i:s', $v['update_time']) : null;
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
    
    /**
     * 创建审批信息
     * @param
     * @return
     * @author Michael_xu
     */
    public function createData($param)
    {
        $fieldModel = new \app\admin\model\Field();
        $userModel = new \app\admin\model\User();
        $examineCategoryModel = new \app\oa\model\ExamineCategory();
        $examineDataModel = new \app\oa\model\ExamineData();
        if (!$param['category_id']) {
            $this->error = '参数错误';
            return false;
        }
        // 自动验证
        $validateArr = $fieldModel->validateField($this->name, $param['category_id']); //获取自定义字段验证规则
        $validate = new Validate($validateArr['rule'], $validateArr['message']);
        $result = $validate->check($param);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        
        $categoryInfo = $examineCategoryModel->getDataById($param['category_id']);
        
        $fileArr = $param['file_id']; //接收表单附件
        unset($param['file_id']);
        $param['start_time'] = $param['start_time'] ? strtotime($param['start_time']) : 0;
        $param['end_time'] = $param['end_time'] ? strtotime($param['end_time']) : 0;
        if ($this->data($param)->allowField(true)->save()) {
            //处理自定义字段数据
            $resData = $examineDataModel->createData($param, $this->examine_id);
            if ($resData) {
                //处理附件关系
                if ($fileArr) {
                    $fileModel = new \app\admin\model\File();
                    $resData = $fileModel->createDataById($fileArr, 'oa_examine', $this->examine_id);
                    if ($resData == false) {
                        $this->error = '附件上传失败';
                        return false;
                    }
                }
                //相关业务
                $rdata = [];
                $rdata['customer_ids'] = $param['oaExamineRelation']['customer_ids'] ? arrayToString($param['oaExamineRelation']['customer_ids']) : '';
                $rdata['contacts_ids'] = $param['oaExamineRelation']['contacts_ids'] ? arrayToString($param['oaExamineRelation']['contacts_ids']) : '';
                $rdata['business_ids'] = $param['oaExamineRelation']['business_ids'] ? arrayToString($param['oaExamineRelation']['business_ids']) : '';
                $rdata['contract_ids'] = $param['oaExamineRelation']['contract_ids'] ? arrayToString($param['oaExamineRelation']['contract_ids']) : '';
                $rdata['examine_id'] = $this->examine_id;
                $rdata['status'] = 1;
                $rdata['create_time'] = time();
                Db::name('OaExamineRelation')->insert($rdata);
                
                //处理差旅相关
                $resTravel = true;
                if (in_array($param['category_id'], ['3', '5']) && $param['cause']) {
                    $resTravel = $this->createTravelById($param['cause']['list'], $this->examine_id);
                }
                if (!$resTravel) {
                    $this->error = '相关事项保存失败，请重试';
                    return false;
                }
                //站内信
                $send_user_id = stringToArray($param['check_user_id']);
                (new Message())->send(
                    Message::EXAMINE_TO_DO,
                    [
                        'title' => $categoryInfo['title'],
                        'action_id' => $this->examine_id
                    ],
                    $send_user_id
                );
                
                
                $data = [];
                $data['examine_id'] = $this->examine_id;
                
                # 添加活动记录
                if (!empty($rdata['customer_ids']) || !empty($rdata['contacts_ids']) || !empty($rdata['business_ids']) || !empty($rdata['contract_ids'])) {
                    Db::name('crm_activity')->insert([
                        'type' => 2,
                        'activity_type' => 9,
                        'activity_type_id' => $data['examine_id'],
                        'content' => '审批',
                        'create_user_id' => $param['create_user_id'],
                        'update_time' => time(),
                        'create_time' => time(),
                        'customer_ids' => !empty($rdata['customer_ids']) ? $rdata['customer_ids'] : '',
                        'contacts_ids' => !empty($rdata['contacts_ids']) ? $rdata['contacts_ids'] : '',
                        'business_ids' => !empty($rdata['business_ids']) ? $rdata['business_ids'] : '',
                        'contract_ids' => !empty($rdata['contract_ids']) ? $rdata['contract_ids'] : '',
                    ]);
                }
                
                return $data;
            } else {
                $this->error = $examineDataModel->getError();
                return false;
            }
        } else {
            $this->error = '添加失败';
            return false;
        }
    }
    
    /**
     * 编辑审批信息
     *
     * @param $param
     * @param string $examine_id
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateDataById($param, $examine_id = '')
    {
        $examine_id = intval($examine_id);
        $userModel = new \app\admin\model\User();
        $examineCategoryModel = new \app\oa\model\ExamineCategory();
        $examineDataModel = new \app\oa\model\ExamineData();
        $create_user_id = $param['create_user_id'];
        unset($param['id']);
        $dataInfo = db('oa_examine')->where(['examine_id' => $examine_id])->find();
        if (!$dataInfo) {
            $this->error = '数据不存在或已删除';
            return false;
        }
        
        //过滤不能修改的字段
        $unUpdateField = ['create_user_id', 'is_deleted', 'delete_time'];
        foreach ($unUpdateField as $v) {
            unset($param[$v]);
        }
        $categoryInfo = $examineCategoryModel->getDataById($dataInfo['category_id']);
        
        //验证
        $fieldModel = new \app\admin\model\Field();
        $validateArr = $fieldModel->validateField($this->name, $dataInfo['category_id']); //获取自定义字段验证规则
        $validate = new Validate($validateArr['rule'], $validateArr['message']);
        $result = $validate->check($param);
        if (!$result) {
            $this->error = $validate->getError();
            return false;
        }
        
        $fileArr = $param['file_id']; //接收表单附件
        unset($param['file_id']);
        $param['start_time'] = $param['start_time'] ? strtotime($param['start_time']) : 0;
        $param['end_time'] = $param['end_time'] ? strtotime($param['end_time']) : 0;
        if ($this->allowField(true)->save($param, ['examine_id' => $examine_id])) {
            //处理自定义字段数据
            $resData = $examineDataModel->createData($param, $examine_id);
            if ($resData) {
                //处理附件关系
                if ($fileArr) {
                    $fileModel = new \app\admin\model\File();
                    $resData = $fileModel->createDataById($fileArr, 'oa_examine', $examine_id);
                    if ($resData == false) {
                        $this->error = '附件上传失败';
                        return false;
                    }
                }
                
                //站内信
                $send_user_id = stringToArray($param['check_user_id']);
                if ($send_user_id) {
                    (new Message())->send(
                        Message::EXAMINE_TO_DO,
                        [
                            'title' => $categoryInfo['title'],
                            'action_id' => $examine_id
                        ],
                        $send_user_id
                    );
                }

                //相关业务
                Db::name('OaExamineRelation')->where('examine_id', $examine_id)->delete(); // 先删除在添加
                $rdata = [];
                $rdata['examine_id']   = $examine_id;
                $rdata['status']       = 1;
                $rdata['create_time']  = time();
                $rdata['customer_ids'] = $param['oaExamineRelation']['customer_ids'] ? arrayToString($param['oaExamineRelation']['customer_ids']) : '';
                $rdata['contacts_ids'] = $param['oaExamineRelation']['contacts_ids'] ? arrayToString($param['oaExamineRelation']['contacts_ids']) : '';
                $rdata['business_ids'] = $param['oaExamineRelation']['business_ids'] ? arrayToString($param['oaExamineRelation']['business_ids']) : '';
                $rdata['contract_ids'] = $param['oaExamineRelation']['contract_ids'] ? arrayToString($param['oaExamineRelation']['contract_ids']) : '';
                Db::name('OaExamineRelation')->insert($rdata);
                
                //处理差旅相关
                $resTravel = true;
                if (in_array($dataInfo['category_id'], ['3', '5']) && $param['cause']) {
                    $resTravel = $this->updateTravelById($param['cause']['list'], $examine_id);
                }
                if (!$resTravel) {
                    $this->error = '相关事项保存失败，请重试';
                    return false;
                }
                // 站内信
                $send_user_id = stringToArray($param['check_user_id']);
                if ($send_user_id) {
                    (new Message())->send(
                        Message::EXAMINE_TO_DO,
                        [
                            'title' => $categoryInfo['title'],
                            'action_id' => $examine_id
                        ],
                        $send_user_id
                    );
                }
                
                $data = [];
                $data['examine_id'] = $examine_id;
                
                # 删除活动记录
                Db::name('crm_activity')->where(['activity_type' => 9, 'activity_type_id' => $examine_id])->delete();
                # 添加活动记录
                if (!empty($rdata['customer_ids']) || !empty($rdata['contacts_ids']) || !empty($rdata['business_ids']) || !empty($rdata['contract_ids'])) {
                    Db::name('crm_activity')->insert([
                        'type' => 2,
                        'activity_type' => 9,
                        'activity_type_id' => $examine_id,
                        'content' => '审批',
                        'create_user_id' => $create_user_id,
                        'update_time' => time(),
                        'create_time' => time(),
                        'customer_ids' => !empty($rdata['customer_ids']) ? $rdata['customer_ids'] : '',
                        'contacts_ids' => !empty($rdata['contacts_ids']) ? $rdata['contacts_ids'] : '',
                        'business_ids' => !empty($rdata['business_ids']) ? $rdata['business_ids'] : '',
                        'contract_ids' => !empty($rdata['contract_ids']) ? $rdata['contract_ids'] : '',
                    ]);
                }
                
                return $data;
            } else {
                $this->error = $examineDataModel->getError();
                return false;
            }
        } else {
            $this->error = '编辑失败';
            return false;
        }
    }
    
    /**
     * 审批数据
     * @param  $id 审批ID
     * @return
     */
    public function getDataById($id = '')
    {
        $examineData = new \app\oa\model\ExamineData();
        $fieldModel = new \app\admin\model\Field();
        $fileModel = new \app\admin\model\File();
        $userModel = new \app\admin\model\User();
        $recordModel = new \app\admin\model\Record();
        $map['examine.examine_id'] = $id;
        $data_view = db('oa_examine')
            ->where($map)
            ->alias('examine')
            ->join('__OA_EXAMINE_CATEGORY__ examine_category', 'examine_category.category_id = examine.category_id', 'LEFT');
        
        $dataInfo = $data_view
            ->field('examine.*,examine_category.title as category_name,examine_category.icon as examineIcon')
            ->find();
        if (!$dataInfo) {
            $this->error = '暂无此数据';
            return false;
        }
        //自定义字段信息
        $examineDataInfo = $examineData->getDataById($id);
        $dataInfo = $examineDataInfo ? array_merge($dataInfo, $examineDataInfo) : $dataInfo;
        $dataInfo['start_time'] = $dataInfo['start_time'] ? date('Y-m-d H:i:s', $dataInfo['start_time']) : null;
        $dataInfo['end_time'] = $dataInfo['end_time'] ? date('Y-m-d H:i:s', $dataInfo['end_time']) : null;;
        $dataInfo['create_time'] = $dataInfo['create_time'] ? date('Y-m-d H:i:s', $dataInfo['create_time']) : null;;
        $realname=$userModel->getUserById($dataInfo['create_user_id']);
        $dataInfo['value'] =  !empty($realname)?$realname['realname']: '';
        //表格数据处理
        // $fieldList = $fieldModel->getFieldByFormType('oa_examine', 'form');
        // foreach ($fieldList as $k=>$v) {
        // 	$dataInfo[$v] = $fieldModel->getFormValueByField($v, $dataInfo[$v]);
        // }
        
        //关联业务
        $relationArr = [];
        $relationArr = $recordModel->getListByRelationId('examine', $id);
        $dataInfo['businessList'] = $relationArr['businessList'];
        $dataInfo['contactsList'] = $relationArr['contactsList'];
        $dataInfo['contractList'] = $relationArr['contractList'];
        $dataInfo['customerList'] = $relationArr['customerList'];
        
        $travelList = [];
        if (in_array($dataInfo['category_id'], ['3', '5'])) {
            //行程、费用明细
            $whereTravel = [];
            $whereTravel['examine_id'] = $dataInfo['examine_id'];
            $travelList = db('oa_examine_travel')->where($whereTravel)->select() ?: [];
            foreach ($travelList as $k => $v) {
                //附件
                $fileList = [];
                $imgList = [];
                $where = [];
                $where['module'] = 'oa_examine_travel';
                $where['module_id'] = $v['travel_id'];
                $newFileList = [];
                $newFileList = $fileModel->getDataList($where, 'all');
                if ($newFileList['list']) {
                    foreach ($newFileList['list'] as $val) {
                        if ($val['types'] == 'file') {
                            $fileList[] = $val;
                        } else {
                            $imgList[] = $val;
                        }
                    }
                }
              
                $travelList[$k]['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
                $travelList[$k]['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
                $travelList[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $travelList[$k]['fileList'] = $fileList ?: [];
                $travelList[$k]['imgList'] = $imgList ?: [];
                
            }
        }
        $dataInfo['travelList'] = $travelList;
        
        //附件
        $fileList = [];
        $imgList = [];
        $where = [];
        $where['module'] = 'oa_examine';
        $where['module_id'] = $id;
        $newFileList = [];
        $newFileList = $fileModel->getDataList($where, 'all');
        foreach ($newFileList['list'] as $val) {
            if ($val['types'] == 'file') {
                $fileList[] = $val;
            } else {
                $imgList[] = $val;
            }
        }
        $dataInfo['fileList'] = $fileList ?: [];
        $dataInfo['imgList'] = $imgList ?: [];
        $dataInfo['create_user_info'] = $realname;
        $dataInfo['examine_id'] = $id;
        return $dataInfo;
    }
    
    /**
     * 审批差旅数据保存
     * @param examine_id 审批ID
     * @return
     */
    public function createTravelById($data = [], $examine_id)
    {
        if (!$examine_id) {
            $this->error = '参数错误';
            return false;
        }
        $successRes = true;
        foreach ($data as $k => $v) {
            $newData = [];
            $fileArr = [];
            unset($v['files']);
            $newData = $v;
            $newData['examine_id'] = $examine_id;
            
            $fileArr = $v['file_id']; //接收表单附件
            unset($newData['file_id']);
            unset($newData['fileList']);
            unset($newData['imgList']);
            $newData['start_time'] = $newData['start_time'] ? strtotime($newData['start_time']) : 0;
            $newData['end_time'] = $newData['end_time'] ? strtotime($newData['end_time']) : 0;
            if ($travel_id = db('oa_examine_travel')->insertGetId($newData)) {
                //处理附件关系
                if ($fileArr) {
                    $fileModel = new \app\admin\model\File();
                    $resData = $fileModel->createDataById($fileArr, 'oa_examine_travel', $travel_id);
                    if ($resData == false) {
                        $successRes = false;
                        return false;
                    }
                }
            } else {
                $successRes = false;
                return false;
            }
        }
        if (!$successRes) {
            $this->error = '审批事项创建失败';
            return false;
        }
        return true;
    }
    
    /**
     * 审批差旅数据编辑
     * @param examine_id 审批ID
     * @return
     */
    public function updateTravelById($data = [], $examine_id)
    {
        if (!$examine_id) {
            $this->error = '参数错误';
            return false;
        }
        $oldTravelIds = db('oa_examine_travel')->where(['examine_id' => $examine_id])->column('travel_id');
        $oldTravelFileIds = db('oa_examine_travel_file')->where(['travel_id' => ['in', $oldTravelIds]])->column('r_id');
        
        $successRes = true;
        foreach ($data as $k => $v) {
            $newData = [];
            $fileArr = [];
            unset($v['files']);
            $newData = $v;
            $newData['examine_id'] = $examine_id;
            $fileArr = $v['file_id']; //接收表单附件
            unset($newData['file_id']);
            unset($newData['fileList']);
            unset($newData['imgList']);
            unset($newData['travel_id']);
            $newData['start_time'] = $newData['start_time'] ? strtotime($newData['start_time']) : 0;
            $newData['end_time'] = $newData['end_time'] ? strtotime($newData['end_time']) : 0;
            if ($travel_id = db('oa_examine_travel')->insertGetId($newData)) {
                //处理附件关系
                if ($fileArr) {
                    $fileModel = new \app\admin\model\File();
                    $resData = $fileModel->createDataById($fileArr, 'oa_examine_travel', $travel_id);
                    if ($resData == false) {
                        $successRes = false;
                        return false;
                    }
                }
            } else {
                $successRes = false;
                return false;
            }
        }
        if (!$successRes) {
            $this->error = '审批事项创建失败';
            return false;
        }
        //删除旧数据
        if ($oldTravelIds) db('oa_examine_travel')->where(['travel_id' => ['in', $oldTravelIds]])->delete();
        if ($oldTravelFileIds) db('oa_examine_travel_file')->where(['r_id' => ['in', $oldTravelFileIds]])->delete();
        return true;
    }
}