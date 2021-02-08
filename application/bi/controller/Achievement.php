<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-业绩目标
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Db;
use think\Hook;
use think\Request;
use app\bi\logic\ExcelLogic;

class Achievement extends ApiCommon
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
            'allow' => ['statistics', 'excelexport']
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'achievement', 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
    }

    /**
     * 业绩目标完成情况
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function statistics($param = '')
    {
        if($param['excel_type']!=1){
            $param = $this->param;
        }
//        $achievementModel = new \app\crm\model\Achievement();
//        $list = $achievementModel->getList($param) ? : [];
        $list = $this->getAchievementStatistics($param) ?: [];
        //导出使用
        if (!empty($param['excel_type'])) {
            $list = $this->excelStatistics($param) ?: [];
            return $list;
        }

        return resultArray(['data' => $list]);
    }

    /**
     * 业绩目标完成情况列表
     *
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getAchievementStatistics($param)
    {
        # 结果数据
        $result = [];

        # 参数
        $status      = !empty($param['status'])       ? $param['status']       : 1; # 类型：1合同目标；2回款目标；
        $year        = !empty($param['year'])         ? $param['year']         : 0; # 年份
        $structureId = !empty($param['structure_id']) ? $param['structure_id'] : 0; # 部门
        $userId      = !empty($param['user_id'])      ? $param['user_id']      : 0; # 员工
        $type        = !empty($param['type'])         ? $param['type']         : 1; # 类型：1部门；2员工

        # 设置业绩目标条件
        $achievementWhere['year'] = $year;
        $achievementWhere['status'] = $status;
        $achievementWhere['type'] = !empty($type) && $type == 1 ? 2 : 3;
        if (!empty($userId)) $achievementWhere['obj_id'] = $userId;
        if (!empty($structureId)) $achievementWhere['obj_id'] = $structureId;

        # 查询业绩目标数据
        $achievementList = Db::name('crm_achievement')->where($achievementWhere)->select();

        if (empty($achievementList)) return [];

        # 部门
        if ($type == 1) {
            foreach ($achievementList as $key => $value) {
                # 组装结果数据
                $result[$value['obj_id']] = [
                    'name' => $value['name'],
                    'list' => [
                        '01' => ['achievement' => (int)$value['january'], 'money' => 0, 'rate' => 0, 'month' => '一月'],
                        '02' => ['achievement' => (int)$value['february'], 'money' => 0, 'rate' => 0, 'month' => '二月'],
                        '03' => ['achievement' => (int)$value['march'], 'money' => 0, 'rate' => 0, 'month' => '三月'],
                        '04' => ['achievement' => (int)$value['april'], 'money' => 0, 'rate' => 0, 'month' => '四月'],
                        '05' => ['achievement' => (int)$value['may'], 'money' => 0, 'rate' => 0, 'month' => '五月'],
                        '06' => ['achievement' => (int)$value['june'], 'money' => 0, 'rate' => 0, 'month' => '六月'],
                        '07' => ['achievement' => (int)$value['july'], 'money' => 0, 'rate' => 0, 'month' => '七月'],
                        '08' => ['achievement' => (int)$value['august'], 'money' => 0, 'rate' => 0, 'month' => '八月'],
                        '09' => ['achievement' => (int)$value['september'], 'money' => 0, 'rate' => 0, 'month' => '九月'],
                        '10' => ['achievement' => (int)$value['october'], 'money' => 0, 'rate' => 0, 'month' => '十月'],
                        '11' => ['achievement' => (int)$value['november'], 'money' => 0, 'rate' => 0, 'month' => '十一月'],
                        '12' => ['achievement' => (int)$value['december'], 'money' => 0, 'rate' => 0, 'month' => '十二月']
                    ]
                ];

                # 获取部门下的员工ID
                $userIds = Db::name('admin_user')->where('structure_id', $value['obj_id'])->column('id');

                # 业绩完成字段
                $finishField = ["DATE_FORMAT(FROM_UNIXTIME(`create_time`,'%Y-%m-%d'),'%m') AS time", 'sum(money) AS money'];

                # 业绩完成条件
                $finishWhere['check_status'] = 2;
                $finishWhere['owner_user_id'] = ['in', $userIds];

                # 合同
                if ($status == 1) {
                    $finishArray = Db::name('crm_contract')->field($finishField)->where($finishWhere)->group('time')->select();
                }

                # 回款
                if ($status == 2) {
                    $finishArray = Db::name('crm_receivables')->field($finishField)->where($finishWhere)->group('time')->select();
                }

                # 计算完成情况
                foreach ($finishArray as $k => $v) {
                    if (!empty($result[$value['obj_id']]['list'][$v['time']])) {
                        $achievement = $result[$value['obj_id']]['list'][$v['time']]['achievement'];

                        $result[$value['obj_id']]['list'][$v['time']]['money'] = (int)$v['money'];
                        $result[$value['obj_id']]['list'][$v['time']]['rate'] = (int)(($v['money'] / $achievement) * 100);

                    }
                }

                $result[$value['obj_id']]['list'] = array_values($result[$value['obj_id']]['list']);
            }
        }

        # 员工
        if ($type == 2) {
            $userData = [];
            $userList = db('admin_user')->field(['id', 'realname'])->select();
            foreach ($userList AS $key => $value) {
                $userData[$value['id']] = $value['realname'];
            }

            foreach ($achievementList as $key => $value) {
                # 组装结果数据
                $result[$value['obj_id']] = [
                    'name' => !empty($value['name']) ? $value['name'] : $userData[$value['obj_id']],
                    'list' => [
                        '01' => ['achievement' => (int)$value['january'], 'money' => 0, 'rate' => 0, 'month' => '一月'],
                        '02' => ['achievement' => (int)$value['february'], 'money' => 0, 'rate' => 0, 'month' => '二月'],
                        '03' => ['achievement' => (int)$value['march'], 'money' => 0, 'rate' => 0, 'month' => '三月'],
                        '04' => ['achievement' => (int)$value['april'], 'money' => 0, 'rate' => 0, 'month' => '四月'],
                        '05' => ['achievement' => (int)$value['may'], 'money' => 0, 'rate' => 0, 'month' => '五月'],
                        '06' => ['achievement' => (int)$value['june'], 'money' => 0, 'rate' => 0, 'month' => '六月'],
                        '07' => ['achievement' => (int)$value['july'], 'money' => 0, 'rate' => 0, 'month' => '七月'],
                        '08' => ['achievement' => (int)$value['august'], 'money' => 0, 'rate' => 0, 'month' => '八月'],
                        '09' => ['achievement' => (int)$value['september'], 'money' => 0, 'rate' => 0, 'month' => '九月'],
                        '10' => ['achievement' => (int)$value['october'], 'money' => 0, 'rate' => 0, 'month' => '十月'],
                        '11' => ['achievement' => (int)$value['november'], 'money' => 0, 'rate' => 0, 'month' => '十一月'],
                        '12' => ['achievement' => (int)$value['december'], 'money' => 0, 'rate' => 0, 'month' => '十二月']
                    ]
                ];

                # 业绩完成字段
                $finishField = ["DATE_FORMAT(FROM_UNIXTIME(`create_time`,'%Y-%m-%d'),'%m') AS time", 'sum(money) AS money'];

                # 业绩完成条件
                $finishWhere = ['check_status' => 2, 'owner_user_id' => $value['obj_id']];

                # 合同
                if ($status == 1) {
                    $finishArray = Db::name('crm_contract')->field($finishField)->where($finishWhere)->group('time')->select();
                }

                # 回款
                if ($status == 2) {
                    $finishArray = Db::name('crm_receivables')->field($finishField)->where($finishWhere)->group('time')->select();
                }

                # 计算完成情况
                foreach ($finishArray as $k => $v) {
                    if (!empty($result[$value['obj_id']]['list'][$v['time']])) {
                        $achievement = $result[$value['obj_id']]['list'][$v['time']]['achievement'];

                        $result[$value['obj_id']]['list'][$v['time']]['money'] = (int)$v['money'];
                        $result[$value['obj_id']]['list'][$v['time']]['rate'] = (int)(($v['money'] / $achievement) * 100);
                    }
                }

                $result[$value['obj_id']]['list'] = array_values($result[$value['obj_id']]['list']);
            }
        }

        return array_values($result);
    }

    public function excelStatistics($param)
    {
        # 结果数据
        $result = [];

        # 参数
        $status = !empty($param['status']) ? $param['status'] : 1; # 类型：1合同目标；2回款目标；
        $year = !empty($param['year']) ? $param['year'] : 0; # 年份
        $structureId = !empty($param['structure_id']) ? $param['structure_id'] : 0; # 部门
        $userId = !empty($param['user_id']) ? $param['user_id'] : 0; # 员工
        $type = !empty($param['type']) ? $param['type'] : 1; # 类型：1部门；2员工

        # 设置业绩目标条件
        $achievementWhere['year'] = $year;
        $achievementWhere['status'] = $status;
        $achievementWhere['type'] = !empty($type) && $type == 1 ? 2 : 3;
        if (!empty($userId)) $achievementWhere['obj_id'] = $userId;
        if (!empty($structureId)) $achievementWhere['obj_id'] = $structureId;

        # 查询业绩目标数据
        $achievementList = Db::name('crm_achievement')->where($achievementWhere)->select();

        if (empty($achievementList)) return [];

        # 部门
        if ($type == 1) {
            foreach ($achievementList as $key => $value) {
                # 组装结果数据
                $result[] = [
                    ['name' => $value['name'], 'achievement' => (int)$value['january'], 'money' => 0, 'rate' => 0, 'month' => '一月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['february'], 'money' => 0, 'rate' => 0, 'month' => '二月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['march'], 'money' => 0, 'rate' => 0, 'month' => '三月'],

                    ['name' => '', 'achievement' => 0, 'money' => 0, 'rate' => 0, 'month' => '第一季度'],

                    ['name' => $value['name'], 'achievement' => (int)$value['april'], 'money' => 0, 'rate' => 0, 'month' => '四月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['may'], 'money' => 0, 'rate' => 0, 'month' => '五月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['june'], 'money' => 0, 'rate' => 0, 'month' => '六月'],

                    ['name' => $value['name'], 'achievement' => (int)$value['april'], 'money' => 0, 'rate' => 0, 'month' => '第二季度'],

                    ['name' => $value['name'], 'achievement' => (int)$value['july'], 'money' => 0, 'rate' => 0, 'month' => '七月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['august'], 'money' => 0, 'rate' => 0, 'month' => '八月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['september'], 'money' => 0, 'rate' => 0, 'month' => '九月'],

                    ['name' => $value['name'], 'achievement' => 0, 'money' => 0, 'rate' => 0, 'month' => '第三季度'],

                    ['name' => $value['name'], 'achievement' => (int)$value['october'], 'money' => 0, 'rate' => 0, 'month' => '十月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['november'], 'money' => 0, 'rate' => 0, 'month' => '十一月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['december'], 'money' => 0, 'rate' => 0, 'month' => '十二月'],

                    ['name' => $value['name'], 'achievement' => 0, 'money' => 0, 'rate' => 0, 'month' => '第四季度'],
                    ['name' => $value['name'], 'achievement' => (int)$value['yeartarget'], 'money' => 0, 'rate' => 0, 'month' => '全年'],
                ];

                # 获取部门下的员工ID
                $userIds = Db::name('admin_user')->where('structure_id', $value['obj_id'])->column('id');

                # 业绩完成字段
                $finishField = ["DATE_FORMAT(FROM_UNIXTIME(`create_time`,'%Y-%m-%d'),'%m') AS time", 'sum(money) AS money'];

                # 业绩完成条件
                $finishWhere['check_status'] = 2;
                $finishWhere['owner_user_id'] = ['in', $userIds];

                # 合同
                if ($status == 1) {
                    $finishArray = Db::name('crm_contract')->field($finishField)->where($finishWhere)->group('time')->select();
                }

                # 回款
                if ($status == 2) {
                    $finishArray = Db::name('crm_receivables')->field($finishField)->where($finishWhere)->group('time')->select();
                }

                # 计算完成情况
                foreach ($finishArray as $k => $v) {
                    if (!empty($result[$v['time']])) {
                        $achievement = $result[$v['time']]['achievement'];
                        $result[$v['time']]['money'] = (int)$v['money'];
                        $result[$v['time']]['rate'] = (int)(($v['money'] / $achievement) * 100);
                    }

                }
                foreach ($result as &$val){
                    $val[3]['money']=$val[0]['money']+$val[1]['money']+$val[2]['money'];
                    $val[3]['rate']=$val[0]['rate']+$val[1]['rate']+$val[2]['rate'];
                    $val[7]['money']=$val[4]['money']+$val[5]['money']+$val[6]['money'];
                    $val[7]['rate']=$val[4]['rate']+$val[5]['rate']+$val[6]['rate'];
                    $val[11]['money']=$val[7]['money']+$val[9]['money']+$val[10]['money'];
                    $val[11]['rate']=$val[8]['rate']+$val[9]['rate']+$val[10]['rate'];
                    $val[15]['money']=$val[12]['money']+$val[13]['money']+$val[14]['money'];
                    $val[15]['rate']=$val[12]['rate']+$val[13]['rate']+$val[14]['rate'];
                    $val[15]['money']=$val[12]['money']+$val[13]['money']+$val[14]['money'];
                    $val[15]['rate']=$val[12]['rate']+$val[13]['rate']+$val[14]['rate'];
                    $val[16]['money']=$val[3]['money']+$val[7]['money']+$val[11]['money']+$val[15]['money'];
                    $val[16]['rate']=$val[3]['rate']+$val[7]['rate']+$val[11]['rate']+$val[15]['rate'];
                }

                $result = array_values($result);
            }
        }

        # 员工
        if ($type == 2) {
            foreach ($achievementList AS $key => $value) {
                # 组装结果数据


                $result[] = [
                    ['name' => $value['name'], 'achievement' => (int)$value['january'], 'money' => 0, 'rate' => 0, 'month' => '一月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['february'], 'money' => 0, 'rate' => 0, 'month' => '二月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['march'], 'money' => 0, 'rate' => 0, 'month' => '三月'],

                    ['name' => '', 'achievement' => 0, 'money' => 0, 'rate' => 0, 'month' => '第一季度'],

                    ['name' => $value['name'], 'achievement' => (int)$value['april'], 'money' => 0, 'rate' => 0, 'month' => '四月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['may'], 'money' => 0, 'rate' => 0, 'month' => '五月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['june'], 'money' => 0, 'rate' => 0, 'month' => '六月'],

                    ['name' => $value['name'], 'achievement' => (int)$value['april'], 'money' => 0, 'rate' => 0, 'month' => '第二季度'],

                    ['name' => $value['name'], 'achievement' => (int)$value['july'], 'money' => 0, 'rate' => 0, 'month' => '七月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['august'], 'money' => 0, 'rate' => 0, 'month' => '八月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['september'], 'money' => 0, 'rate' => 0, 'month' => '九月'],

                    ['name' => $value['name'], 'achievement' => 0, 'money' => 0, 'rate' => 0, 'month' => '第三季度'],

                    ['name' => $value['name'], 'achievement' => (int)$value['october'], 'money' => 0, 'rate' => 0, 'month' => '十月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['november'], 'money' => 0, 'rate' => 0, 'month' => '十一月'],
                    ['name' => $value['name'], 'achievement' => (int)$value['december'], 'money' => 0, 'rate' => 0, 'month' => '十二月'],

                    ['name' => $value['name'], 'achievement' => 0, 'money' => 0, 'rate' => 0, 'month' => '第四季度'],
                    ['name' => $value['name'], 'achievement' => (int)$value['yeartarget'], 'money' => 0, 'rate' => 0, 'month' => '全年'],
                ];



                # 业绩完成字段
                $finishField = ["DATE_FORMAT(FROM_UNIXTIME(`create_time`,'%Y-%m-%d'),'%m') AS time", 'sum(money) AS money'];

                # 业绩完成条件
                $finishWhere = ['check_status' => 2, 'owner_user_id' => $value['obj_id']];

                # 合同
                if ($status == 1) {
                    $finishArray = Db::name('crm_contract')->field($finishField)->where($finishWhere)->group('time')->select();
                }

                # 回款
                if ($status == 2) {
                    $finishArray = Db::name('crm_receivables')->field($finishField)->where($finishWhere)->group('time')->select();
                }

                # 计算完成情况
                foreach ($finishArray AS $k => $v) {
                    if (!empty($result[$v['time']])) {
                        $achievement = $result[$v['time']]['achievement'];

                        $result[$v['time']]['money'] = (int)$v['money'];
                        $result[$v['time']]['rate']  = (int)(($v['money'] / $achievement) * 100);
                    }
                }

                foreach ($result as &$val){
                    $val[3]['money']=$val[0]['money']+$val[1]['money']+$val[2]['money'];
                    $val[3]['rate']=$val[0]['rate']+$val[1]['rate']+$val[2]['rate'];
                    $val[7]['money']=$val[4]['money']+$val[5]['money']+$val[6]['money'];
                    $val[7]['rate']=$val[4]['rate']+$val[5]['rate']+$val[6]['rate'];
                    $val[11]['money']=$val[7]['money']+$val[9]['money']+$val[10]['money'];
                    $val[11]['rate']=$val[8]['rate']+$val[9]['rate']+$val[10]['rate'];
                    $val[15]['money']=$val[12]['money']+$val[13]['money']+$val[14]['money'];
                    $val[15]['rate']=$val[12]['rate']+$val[13]['rate']+$val[14]['rate'];
                    $val[15]['money']=$val[12]['money']+$val[13]['money']+$val[14]['money'];
                    $val[15]['rate']=$val[12]['rate']+$val[13]['rate']+$val[14]['rate'];
                    $val[16]['money']=$val[3]['money']+$val[7]['money']+$val[11]['money']+$val[15]['money'];
                    $val[16]['rate']=$val[3]['rate']+$val[7]['rate']+$val[11]['rate']+$val[15]['rate'];
                }
                $result = array_values($result);
            }
        }

        return array_values($result);
    }

    /**
     * 导出
     * @param $type
     * @param $types
     */
    public function excelExport()
    {
        $param = $this->param;
        $list = $this->statistics($param);
        if(empty($list)){
            return resultArray(['data'=>'数据不存在']);
        }
        $excelLogic = new ExcelLogic();
        $data = $excelLogic->achienementExcel($param, $list);
        return $data;
    }
}
