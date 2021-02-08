<?php
// +----------------------------------------------------------------------
// | Description: 审批流程
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;
use think\Db;

class ExamineFlow extends ApiCommon
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
            'permission'=>[],
            'allow'=>['index','save','update','read','delete','enables','steplist','userlist','recordlist']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        //权限判断
        $unAction = ['steplist','userlist','recordlist'];
        if (!in_array($a, $unAction) && !checkPerByAction('admin', 'examine_flow', 'index')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
    } 

    /**
     * 审批流程列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $examineFlowModel = model('ExamineFlow');
        $param = $this->param;
        //过滤审批类型中关联的审批流
        $param['types'] = ['neq','oa_examine'];
        $data = $examineFlowModel->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 添加审批流程
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function save()
    {
        if (empty($this->param['types'])) return resultArray(['error' => '请选择关联对象！']);

        $examineFlowModel = model('ExamineFlow');
        $examineStepModel = model('ExamineStep');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['update_user_id'] = $userInfo['id'];

        # 验证名称是否重复
        $repeatWhere['name'] = $param['name'];
        $repeatWhere['is_deleted'] = 0;
        $repeatWhere['types'] = ['neq', 'oa_examine'];
        if (db('admin_examine_flow')->where($repeatWhere)->value('flow_id')) return resultArray(['error' => '审批流名称重复！']);

        //处理
        $param['user_ids'] = arrayToString($param['user_ids']);
        $param['structure_ids'] = arrayToString($param['structure_ids']);
        $res = $examineFlowModel->createData($param);
        $param['config'] = $param['config'] ? 1 : 0;
        if ($res) {
            $config = $param['config'];
            if ((int)$config == 1) {
                //固定审批流
                $resStep = $examineStepModel->createStepData($param['step'], $res['flow_id']);
                if ($resStep) {
                    return resultArray(['data' => '添加成功']);
                } else {
                    db('admin_examine_flow')->where(['flow_id' => $res['flow_id']])->delete();
                    return resultArray(['error' => $examineStepModel->getError()]);
                }               
            }
            return resultArray(['data' => '添加成功']);
        } else {
        	return resultArray(['error' => $examineFlowModel->getError()]);
        }
    }

    /**
     * 编辑审批流程
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function update()
    {
        if (empty($this->param['types'])) return resultArray(['error' => '请选择关联对象！']);

        $examineFlowModel = model('ExamineFlow');
        $examineStepModel = model('ExamineStep');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['update_user_id'] = $userInfo['id'];
        $param['create_time'] = time();
        $param['update_time'] = time();
        $flowId = $param['flow_id'];
        unset($param['flow_id']);

        # 验证名称是否重复
        $repeatWhere['name'] = $param['name'];
        $repeatWhere['is_deleted'] = 0;
        $repeatWhere['types'] = ['neq', 'oa_examine'];
        $repeatWhere['flow_id'] = ['neq', $flowId];
        if (db('admin_examine_flow')->where($repeatWhere)->value('flow_id')) return resultArray(['error' => '审批流名称重复！']);

        //处理
        $param['user_ids'] = arrayToString($param['user_ids']);
        $param['structure_ids'] = arrayToString($param['structure_ids']);
        $res = $examineFlowModel->createData($param);
        $param['config'] = $param['config'] ? 1 : 0;
        if ($res) {
            //将当前审批流标记为已删除，重新创建审批流(目的：保留审批流程记录)
            $upData = [];
            $upData['is_deleted'] = 1;
            $upData['delete_time'] = time();
            $upData['delete_user_id'] = $userInfo['id'];
            $upData['status'] = 0;
            db('admin_examine_flow')->where(['flow_id' => $flowId])->update($upData);

            $config = $param['config'];
            if ((int)$config == 1) {
                //固定审批流
                $resStep = $examineStepModel->createStepData($param['step'], $res['flow_id']);
                if ($resStep) {
                    return resultArray(['data' => '添加成功']);
                } else {
                    db('admin_examine_flow')->where(['flow_id' => $res['flow_id']])->delete();
                    return resultArray(['error' => $examineStepModel->getError()]);
                }
            }
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $examineFlowModel->getError()]);
        }


//        $newData = db('admin_examine_flow')->where(['flow_id' => $param['flow_id']])->find();
//        $newData['user_ids'] = arrayToString($param['user_ids']);
//        $param['structure_ids'] = arrayToString($param['structure_ids']);
//        $param['update_user_id'] = $userInfo['id'];
//        $param['create_time'] = time();
//        $param['update_time'] = time();
//        $param['status'] = 1;
//        $resUpdate = $examineFlowModel->updateDataById($param, $param['flow_id']);
//
//        if ($resUpdate) {
//            if ($param['config'] == 1) {
//                $resStep = $examineStepModel->createStepData($param['step'], $resUpdate['flow_id']);
//                if (!$resStep) {
//                    return resultArray(['error' => $examineStepModel->getError()]);
//                }
//            }
//
//            $upData = [];
//            $upData['is_deleted'] = 1;
//            $upData['delete_time'] = time();
//            $upData['delete_user_id'] = $userInfo['id'];
//            $upData['status'] = 0;
//            $resFlow = db('admin_examine_flow')->where(['flow_id' => $param['flow_id']])->update($upData);
//            if (!$resFlow) {
//                return resultArray(['error' => '编辑失败1']);
//            }
//            return resultArray(['data' => '编辑成功']);
//        } else {
//            return resultArray(['error' => '编辑失败2']);
//        }
    }

    /**
     * 审批流程详情
     * @author Michael_xu
     * @param 
     * @return
     */
    public function read()
    {
        $examineFlowModel = model('ExamineFlow');
        $param = $this->param;        
        $res = $examineFlowModel->getDataById($param['id']);
        if (!$res) {
            return resultArray(['error' => $examineFlowModel->getError()]);
        }
        return resultArray(['data' => $res]); 
    }    

    /**
     * 删除审批流程（逻辑删）
     * @author Michael_xu
     * @param 
     * @return
     */
    public function delete()
    {
        $examineFlowModel = model('ExamineFlow');
        $param = $this->param;       
        $data = $examineFlowModel->signDelById($param['flow_id']);
        if (!$data) {
            return resultArray(['error' => $examineFlowModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }

    /**
     * 审批流程状态
     * @author Michael_xu
     * @param ids array
     * @param status 1启用，0禁用
     * @return
     */    
    public function enables()
    {
        $examineFlowModel = model('ExamineFlow');
        $param = $this->param;        
        $id = [$param['flow_id']];
        $data = $examineFlowModel->enableDatas($id, $param['status']);  
        if (!$data) {
            return resultArray(['error' => $examineFlowModel->getError()]);
        } 
        return resultArray(['data' => '操作成功']);         
    }

    /**
     * 完整审批步骤（固定审批流）
     * @author Michael_xu
     * @param  flow_id 审批流ID
     * @param  user_id 审批对象创建人ID
     * @return
     */
    public function stepList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $examineStepModel = model('ExamineStep');
        $examineFlowModel = model('ExamineFlow');

        $check_user_id = $userInfo['id'];
        $flow_id = $param['flow_id'];
        $types = $param['types'];
        $types_id = $param['types_id'];
        $typesArr = ['crm_contract', 'crm_receivables', 'crm_invoice', 'oa_examine'];
        if (!$types || !in_array($types, $typesArr)) {
            return resultArray(['error' => '参数错误']);
        }
        
        if ($flow_id) {
            $examineFlowData = $examineFlowModel->getDataById($param['flow_id']);
            if (!$examineFlowData) {
                return resultArray(['error' => '参数错误']);
            }
            $typesInfo = $examineStepModel->getDataByTypes($types, $types_id);
            $user_id = $typesInfo['dataInfo']['owner_user_id'];
            if ($types == 'oa_examine') {
                $user_id = $typesInfo['dataInfo']['create_user_id'];
            }
            if (!$user_id) {
                return resultArray(['error' => '参数错误']);    
            }      
        } else {
            $user_id = $check_user_id;
            // 获取符合条件的审批流
            $examineFlowData = $examineFlowModel->getFlowByTypes($user_id, $types, $types_id);
            if (!$examineFlowData) {
                # 合同、回款、发票模块审批流停用
                if (in_array($types, ['crm_contract', 'crm_receivables', 'crm_invoice'])) {
                    return resultArray(['data' => ['examineStatus' => false]]);
                }

                return resultArray(['error' => '无可用审批流，请联系管理员']);
            } 
            $flow_id = $examineFlowData['flow_id'];         
        }
        if ($types == 'oa_examine') {
            $category_id = db('oa_examine')->where(['examine_id' => $types_id])->value('category_id');
        }
        $list=[];
        //自选还是流程(1固定,0自选)
        if ($examineFlowData['config'] == 1) {
            //获取审批流程
            $stepInfo = $examineStepModel->getStepList($flow_id, $user_id, $types, $types_id, $check_user_id, $param['action'], $category_id);
            $stepList = $stepInfo['steplist'];
            $list=$stepInfo['steplist'][0]['user_id_info'];
//            foreach ($stepInfo['steplist'] as $k=>$v){
//                $data['user_id_info'][]=$v['user_id_info'];
//            }
        } else {
            $stepInfo = $examineStepModel->getPerStepList($types, $types_id, $user_id, $check_user_id, $param['action']);
            $stepList = $stepInfo['steplist'];
            $list=$stepInfo['steplist'][0]['user_id_info'];
//            foreach ($stepInfo['steplist'] as $k=>$v){
//                $data['user_id_info'][]=$stepInfo['steplist']['user_id_info'];
//            }
        }
        $data = [];
        $data['config'] = (int) $examineFlowData['config']; //1固定,0自选
        $data['stepList'] = $stepList ? : []; 
        $data['examine_user'] = $list ? : [];
        $data['is_check'] = $stepInfo['is_check'] ? : 0;
        $data['is_recheck'] = $stepInfo['is_recheck'] ? : 0;
        $data['examineStatus'] = true;
        return resultArray(['data' => $data]);
    }

    /**
     * 自选审批人列表（授权审批类型）
     * @author Michael_xu
     * @param  types 类型
     * @return
     */
    public function userList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $types = $param['types'];
        $examineStepModel = model('ExamineStep');
        $userModel = model('User');
        // $examine_user_ids = $examineStepModel->getUserByPer($types);
        //暂定返回全部
        $examine_user_ids = getSubUserId(true, 1);
        $where = [];
        $where['user.id'] = array('in',$examine_user_ids);
        $where['status'] = ['gt',0];
        $where['pageType'] = 'all';
        $userList = $userModel->getDataList($where);
        return resultArray(['data' => $userList['list']]);
    } 

    /**
     * 审批记录
     * @author Michael_xu
     * @param  types 类型
     * @return
     */
    public function recordList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $examineRecordModel = model('ExamineRecord');
        $list = $examineRecordModel->getDataList($param) ? : [];
        return resultArray(['data' => $list]);
    }        
}