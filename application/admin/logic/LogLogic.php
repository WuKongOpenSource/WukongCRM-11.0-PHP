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
        'work_task'       => '任务',
        'work'            => '项目',
        'oa_event'        => '日程',
        'crm_activity'        => '跟进记录',
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
        'customer'    => '客户管理',
        'work_task'    => '其他设置'
    ];
    public $systemAction=[
        'admin_oalog_rule'=>'日志',
        'admin_group'=>'角色',
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
        'crm_config'      => '客户管理系统设置',
        'crm_number_sequence'      => '业绩目标',
        'admin_structure'      => '部门',
        'admin_config'      => '应用管理',
        'work_task'    => '其他设置'
    ];
    /**
     * 日志记录中的行为所对应的中文名称
     *
     * @var string[]
     */
    private $action = [
        'save'   => '添加数据',
        'update' => '编辑数据',
        'delete' => '删除数据',
        'excel' => '导入数据',
        'excelexport' => '导出数据',
        'lock' => '锁定',
        'islock' => '解锁',
        'status' => '更改成交状态',
        'receive' => '领取',
        'transfer' => '转移',
        'teamSave' => '添加团队成员',
        'distribute' => '分配',
        'up' => '上架',
        'down' => '下架',
        'recover' => '归档恢复',
        'archiveData' => '归档',
        'copy' => '复制',
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
        $res= db('admin_login_record')
            ->alias('login')
            ->join('__ADMIN_USER__ user','user.id=login.create_user_id','LEFT')
            ->where(function ($query) use ($param) {
                if (!empty($param['startTime'])) $query->where('login.create_time', '>=', strtotime($param['startTime']));
                if (!empty($param['endTime'])) $query->where('login.create_time', '<=', strtotime($param['endTime']));
                if (!empty($param['userIds'])) $query->whereIn('login.create_user_id', $param['userIds']);
            })
            ->field('login.*,user.realname as username')
            ->page($param['page'],$param['limit'])
            ->order('login.id', 'desc')
            ->select();
        foreach ($res as $k =>$v){
            $res[$k]['create_time']=!empty($v['create_time'])?date('Y-m-d H:i:s',$v['create_time']):null;
            $res[$k]['type']=$this->loginType[$v['type']];
        }
        $total= db('admin_login_record')
            ->alias('login')
            ->join('__ADMIN_USER__ user','user.id=login.create_user_id','LEFT')
            ->where(function ($query) use ($param) {
                if (!empty($param['startTime'])) $query->where('login.create_time', '>=', strtotime($param['startTime']));
                if (!empty($param['endTime'])) $query->where('login.create_time', '<=', strtotime($param['endTime']));
                if (!empty($param['userIds'])) $query->whereIn('login.create_user_id', $param['userIds']);
            })->count();
        $data=[];
        $data['list']=$res;
        $data['dataCount']=$total;
        return $data;
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
        $data = db('admin_system_log')
            ->alias('system')
            ->join('__ADMIN_USER__ user','user.id=system.user_id','LEFT')
            ->where(function ($query) use ($param) {
                if (!empty($param['startTime'])) $query->where('system.create_time', '>=', strtotime($param['startTime']));
                if (!empty($param['endTime']))   $query->where('system.create_time', '<=', strtotime($param['endTime']));
                if (!empty($param['modules']))   $query->whereIn('system.module_name', $param['modules']);
                if (!empty($param['userIds']))   $query->whereIn('system.user_id', $param['userIds']);
            })
            ->page($param['page'], $param['limit'])
            ->field('system.log_id,system.target_name,system.create_time,system.client_ip,system.module_name,system.content,system.target_name,system.action_name,system.controller_name,user.realname')
            ->order('system.log_id', 'desc')->select();
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
        $data = db('admin_operation_log')
            ->alias('operation')
            ->join('__ADMIN_USER__ user','user.id=operation.user_id','LEFT')
            ->where(function ($query) use ($param) {
                if (!empty($param['startTime'])) $query->where('operation.create_time', '>=', strtotime($param['startTime']));
                if (!empty($param['endTime']))   $query->where('operation.create_time', '<=', strtotime($param['endTime']));
                if (!empty($param['modules']))   $query->whereIn('operation.module', arrayToString($param['modules']));
                if (!empty($param['userIds']))   $query->whereIn('operation.user_id', $param['userIds']);
            })
            ->field('operation.*,user.realname')
            ->page($param['page'], $param['limit'])
            ->order('operation.log_id', 'desc')->select();
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
        return db('admin_operation_log')
            ->alias('operation')
            ->join('__ADMIN_USER__ user','user.id=operation.user_id','LEFT')
            ->where(function ($query) use ($param) {
                if (!empty($param['startTime'])) $query->where('operation.create_time', '>=', strtotime($param['startTime']));
                if (!empty($param['endTime']))   $query->where('operation.create_time', '<=', strtotime($param['endTime']));
                if (!empty($param['modules']))   $query->whereIn('operation.module', arrayToString($param['modules']));
                if (!empty($param['userIds']))   $query->whereIn('operation.user_id', $param['userIds']);
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
                'source_name' => $value['target_name'],
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'ip'          => $value['client_ip'],
                'module'      => $this->recordModules[$value['module']],
                'action'      => in_array('crm',explode('_',$value['module']))==1?
                    '客户管理':(in_array('oa',explode('_',$value['module']))==1?'办公管理':'项目管理'),
                'content'     => $value['content'],
                'user_name'     => $value['realname'],
                'action_name'     =>  $this->action[$value['action_name']],
            ];
        }

        return $result;
    }

    /**
     * 组装系统操作数据操作日志数据
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
                'source_name' => $value['target_name'],
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'ip'          => $value['client_ip'],
                'action'      => $this->systemModules[$value['module_name']],
                'content'     => $value['content'],
                'user_name'     => $value['realname'],
                'action_name'     => $this->action[$value['action_name']],
                'module'     => '后台管理',
                'action_down'     => $this->recordModules[$value['controller_name']]?:'',
            ];
        }
        
        return $result;
    }
    
    /**
     * 导出方法
     * @param $param
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/8 0008 16:37
     */
    public function downExcel($param){
        $excelModel = new \app\admin\model\Excel();
        $file_name='sysLogs';
        $field_list=[];
        $type='';
        $action=$param['action'];
        unset($param['action']);
        unset($param['page']);
        unset($param['limit']);
        $param['startTime']= !empty($param['startTime'])?$param['startTime'].' 00:00:00':'';
        $param['endTime']= !empty($param['endTime'])?$param['endTime'].' 23:59:59':'';
        switch ($action){
            case 'getSystemLogs':
                $type='系统日志';
                $field_list = [
                    '0' => ['name' => '用户', 'field' => 'user_name'],
                    '1' => ['name' => '时间', 'field' => 'create_time'],
                    '2' => ['name' => 'ip地址', 'field' => 'ip'],
                    '3' => ['name' => '模块', 'field' => 'module'],
                    '4' => ['name' => '子模块', 'field' => 'action'],
                    '5' => ['name' => '行为', 'field' => 'action_name'],
                    '6' => ['name' => '对象', 'field' => 'source_name'],
                    '7' => ['name' => '操作详情', 'field' => 'content'],
                ];
                $modules = $this->systemModules;
                if(!empty($param['subModelLabels'])){
                    $param['modules']=$param['subModelLabels'];
                }
                $list = db('admin_system_log')
                    ->alias('system')
                    ->join('__ADMIN_USER__ user','user.id=system.user_id','LEFT')
                    ->where(function ($query) use ($param) {
                        if (!empty($param['startTime'])) $query->where('system.create_time', '>=', strtotime($param['startTime']));
                        if (!empty($param['endTime']))   $query->where('system.create_time', '<=', strtotime($param['endTime']));
                        if (!empty($param['modules']))   $query->whereIn('system.module_name', $param['modules']);
                        if (!empty($param['userIds']))   $query->whereIn('system.user_id', $param['userIds']);
                    })
                    ->field('system.log_id,system.target_name,system.create_time,system.client_ip,system.module_name,system.content,system.target_name,system.action_name,system.controller_name,user.realname')
                    ->order('system.log_id', 'desc')->select();
                 $data=$this->setSystemData($list);
                break;
            case 'getRecordLogs':
                $type='系统日志';
                $field_list = [
                    '0' => ['name' => '用户', 'field' => 'user_name'],
                    '1' => ['name' => '时间', 'field' => 'create_time'],
                    '2' => ['name' => 'ip地址', 'field' => 'ip'],
                    '3' => ['name' => '模块', 'field' => 'action'],
                    '4' => ['name' => '子模块', 'field' => 'module'],
                    '5' => ['name' => '行为', 'field' => 'action_name'],
                    '6' => ['name' => '对象', 'field' => 'source_name'],
                    '7' => ['name' => '操作详情', 'field' => 'content'],
                ];
                if(!empty($param['subModelLabels'])){
                    $param['modules']=$param['subModelLabels'];
                }else{
                    switch ($param['model']){
                        case 'crm': //客户管理
                            $param['modules']=array(
                                'crm_leads'  ,
                                'crm_customer',
                                'crm_pool'     ,
                                'crm_contacts'  ,
                                'crm_product'    ,
                                'crm_business'    ,
                                'crm_contract'     ,
                                'crm_receivables'  ,
                                'crm_visit'        ,
                                'crm_invoice'      ,
                                'crm_activity'
                            );
                            break;
                        case 'oa' : //办公管理
                            $param['modules']=array(
                                'oa_log'  ,
                                'oa_event',
                            );
                            break;
                        case 'work' ://项目管理
                            $param['modules']=array(
                                'work_task'  ,
                                'work',
                            );
                            break;
                        default :
                            $param['modules']=array(
                                'crm_leads'  ,
                                'crm_customer',
                                'crm_pool'     ,
                                'crm_contacts'  ,
                                'crm_product'    ,
                                'crm_business'    ,
                                'crm_contract'     ,
                                'crm_receivables'  ,
                                'crm_visit'        ,
                                'crm_invoice'      ,
                                'crm_activity',
                                'oa_log'  ,
                                'oa_event',
                                'work_task'  ,
                                'work',
                            );
                            break;
                    }
                }
    
                $list = db('admin_operation_log')
                    ->alias('operation')
                    ->join('__ADMIN_USER__ user','user.id=operation.user_id','LEFT')
                    ->where(function ($query) use ($param) {
                        if (!empty($param['startTime'])) $query->where('operation.create_time', '>=', strtotime($param['startTime']));
                        if (!empty($param['endTime']))   $query->where('operation.create_time', '<=', strtotime($param['endTime']));
                        if (!empty($param['modules']))   $query->whereIn('operation.module', arrayToString($param['modules']));
                        if (!empty($param['userIds']))   $query->whereIn('operation.user_id', $param['userIds']);
                    })
                    ->field('operation.*,user.realname')
                    ->order('operation.log_id', 'desc')->select();
                $data=$this->setRecordData($list);
                break;
            case 'getLoginRecord':
                $type='登陆日志';
                $field_list = [
                    '0' => ['name' => '用户', 'field' => 'username'],
                    '1' => ['name' => '时间', 'field' => 'create_time'],
                    '2' => ['name' => 'ip地址', 'field' => 'ip'],
                    '3' => ['name' => '登陆地点', 'field' => 'address'],
                    '4' => ['name' => '设备类型', 'field' => 'remark'],
                    '5' => ['name' => '终端内核', 'field' => 'browser'],
                    '6' => ['name' => '平台', 'field' => 'os'],
                    '7' => ['name' => '成功', 'field' => 'type'],
                ];
                $res  = db('admin_login_record')
                    ->alias('login')
                    ->join('__ADMIN_USER__ user','user.id=login.create_user_id','LEFT')
                    ->where(function ($query) use ($param) {
                        if (!empty($param['startTime'])) $query->where('login.create_time', '>=', strtotime($param['startTime']));
                        if (!empty($param['endTime'])) $query->where('login.create_time', '<=', strtotime($param['endTime']));
                        if (!empty($param['userIds'])) $query->whereIn('login.create_user_id', $param['userIds']);
                })
                    ->field('login.*,user.realname as username')
                    ->order('login.id', 'desc')
                    ->select();
                foreach ($res as $k =>$v){
                    $res[$k]['create_time']=!empty($v['create_time'])?date('Y-m-d H:i:s',$v['create_time']):null;
                    $res[$k]['type']=$this->loginType[$v['type']];
                }
                $data=$res;
                break;
        }
        return $excelModel->biExportExcel($file_name, $field_list, $type, $data);
    }
}