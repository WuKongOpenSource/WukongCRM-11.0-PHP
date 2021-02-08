<?php
/**
 * 日志逻辑类
 *
 * @author qifan
 * @date 2020-11-30
 */

namespace app\admin\logic;

use app\admin\model\LoginRecord;
use app\admin\model\OperationLog;
use app\admin\model\SystemLog;

class LogLogic
{
    /**
     * 数据操作日志中的模块对应的中文名称
     *
     * @var string[]
     */
    public $recordModules = [
        'crm_leads'       => '线索',
        'crm_customer'    => '客户',
        'crm_pool'        => '客户公海',
        'crm_contacts'    => '联系人',
        'crm_product'     => '产品',
        'crm_business'    => '商机',
        'crm_contract'    => '合同',
        'crm_receivables' => '回款',
        'crm_visit'       => '回访',
        'crm_invoice'     => '回款',
        'oa_log'          => '办公日志',
        'oa_examine'      => '办公审批',
        'work_task'       => '任务',
        'work'            => '项目',
        'label'           => '标签',
        'calendar'        => '日历'
    ];

    public $systemModules = [
        'company'     => '企业首页',
        'application' => '应用管理',
        'structures'  => '部门管理',
        'employee'    => '员工管理',
        'role'        => '角色管理',
        'approval'    => '审批流程管理',
        'workbench'   => '工作台',
        'project'     => '项目管理',
        'customer'    => '客户管理'
    ];

    /**
     * 日志记录中的行为所对应的中文名称
     *
     * @var string[]
     */
    private $action = [
        'index'  => '查看数据',
        'save'   => '添加数据',
        'update' => '编辑数据',
        'delete' => '删除数据'
    ];
    private $loginType = [
        '成功', '密码错误', '账号禁用'
    ];
    /**
     * 登录日志
     *
     * @param $param
     * @return array
     * @throws \think\exception\DbException
     */
    public function getLoginRecord($param)
    {
        $loginRecordModel = new LoginRecord();

        $limit = !empty($param['limit']) ? $param['limit'] : 15;

        $data  = $loginRecordModel->where(function ($query) use ($param) {
            if (!empty($param['startTime'])) $query->where('create_time', '>=', strtotime($param['startTime']));
            if (!empty($param['endTime'])) $query->where('create_time', '<=', strtotime($param['endTime']));
            if (!empty($param['userIds'])) $query->whereIn('create_user_id', $param['userIds']);
        })->order('id', 'desc')->paginate($limit)->each(function ($value) {
            $value['username']  = $value->create_user_info['realname'];
            $value['type'] = $this->loginType[$value['type']];
        })->toArray();

        return ['list' => $data['data'], 'count' => $data['total']];
    }



    /**
     * 获取系统操作日志列表
     *
     * @param $param 查询条件、分页参数
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSystemLogs($param)
    {
        $data = SystemLog::with(['toAdminUser'])
            ->where(function ($query) use ($param) {
                if (!empty($param['startTime'])) $query->where('create_time', '>=', strtotime($param['startTime']));
                if (!empty($param['endTime']))   $query->where('create_time', '<=', strtotime($param['endTime']));
                if (!empty($param['modules']))   $query->whereIn('modules', $param['modules']);
                if (!empty($param['userIds']))   $query->whereIn('user_id', $param['userIds']);
            })->limit(($param['page'] - 1) * $param['limit'])->order('log_id', 'desc')->select();

        return $this->setSystemData($data);
    }

    /**
     * 获取系统操作日志总数
     *
     * @param $param 查询条件、分页参数
     * @return int|string|null
     */
    public function getSystemLogCount($param)
    {
        return SystemLog::where(function ($query) use ($param) {
            if (!empty($param['startTime'])) $query->where('create_time', '>=', strtotime($param['startTime']));
            if (!empty($param['endTime'])) $query->where('create_time', '<=', strtotime($param['endTime']));
            if (!empty($param['modules'])) $query->whereIn('controller_name', $param['modules']);
            if (!empty($param['userIds'])) $query->whereIn('user_id', $param['userIds']);
        })->count();
    }

    /**
     * 获取数据操作日志列表
     *
     * @param $param 查询条件、分页参数
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecordLogs($param)
    {
        $data = OperationLog::with(['toAdminUser'])
            ->where(function ($query) use ($param) {
                if (!empty($param['startTime'])) $query->where('create_time', '>=', strtotime($param['startTime']));
                if (!empty($param['endTime']))   $query->where('create_time', '<=', strtotime($param['endTime']));
                if (!empty($param['modules']))   $query->whereIn('module', $param['modules']);
                if (!empty($param['userIds']))   $query->whereIn('user_id', $param['userIds']);
            })
            ->limit(($param['page'] - 1) * $param['limit'])->order('log_id', 'desc')->select();

        return $this->setRecordData($data);
    }

    /**
     * 获取数据操作日志总数
     *
     * @param $param 查询条件、分页参数
     * @return int|string|null
     */
    public function getRecordLogCount($param)
    {
        return OperationLog::where(function ($query) use ($param) {
            if (!empty($param['startTime'])) $query->where('create_time', '>=', strtotime($param['startTime']));
            if (!empty($param['endTime'])) $query->where('create_time', '<=', strtotime($param['endTime']));
            if (!empty($param['modules'])) $query->whereIn('module', $param['module']);
            if (!empty($param['userIds'])) $query->whereIn('user_id', $param['userIds']);
        })->count();
    }

    /**
     * 组装数据操作日志数据
     *
     * @param $data
     * @return mixed
     */
    private function setRecordData($data)
    {
        $result = [];

        foreach ($data AS $key => $value) {
            $result[] = [
                'log_id'      => $value['log_id'],
                'source_name' => $value['source_name'],
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'ip'          => $value['client_ip'],
                'module'      => $this->recordModules[$value['module']],
                'content'     => $value['content']
            ];
        }

        return $result;
    }

    /**
     * 组装数据操作日志数据
     *
     * @param $data
     * @return mixed
     */
    private function setSystemData($data)
    {
        $result = [];

        foreach ($data AS $key => $value) {
            $result[] = [
                'log_id'      => $value['log_id'],
                'source_name' => $value['source_name'],
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'ip'          => $value['client_ip'],
                'module'      => $this->systemModules[$value['module']],
                'content'     => $value['content']
            ];
        }

        return $result;
    }
}