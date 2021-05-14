<?php
/**
 * 日志控制器
 *
 * @author qifan
 * @date 2020-11-30
 */

namespace app\admin\controller;

use app\admin\logic\LogLogic;
use think\Hook;
use think\Request;

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
            'allow' => ['dataRecord', 'systemRecord', 'loginRecord','excelImport']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 数据操作日志
     *
     * @param LogLogic $logLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dataRecord(LogLogic $logLogic)
    {
        $this->param['startTime']= !empty($this->param['startTime'])?$this->param['startTime'].' 00:00:00':'';
        $this->param['endTime']= !empty($this->param['endTime'])?$this->param['endTime'].' 23:59:59':'';
        if(!empty($this->param['subModelLabels'])){
            $this->param['modules']=$this->param['subModelLabels'];
        }else{
            switch ($this->param['model']){
                case 'crm': //客户管理
                    $this->param['modules']=array(
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
                    $this->param['modules']=array(
                        'oa_log'  ,
                        'oa_event',
                    );
                    break;
                case 'work' ://项目管理
                    $this->param['modules']=array(
                        'work_task'  ,
                        'work',
                    );
                    break;
                default :
                    $this->param['modules']=array(
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
        
        $data['list']    = $logLogic->getRecordLogs($this->param);
        $data['count']   = $logLogic->getRecordLogCount($this->param);
        $data['modules'] = $logLogic->recordModules;

        return resultArray(['data' => $data]);
    }

    /**
     * 系统操作日志
     *
     * @param LogLogic $logLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function systemRecord(LogLogic $logLogic)
    {
        $this->param['startTime']= !empty($this->param['startTime'])?$this->param['startTime'].' 00:00:00':'';
        $this->param['endTime']= !empty($this->param['endTime'])?$this->param['endTime'].' 23:59:59':'';
        if(!empty($this->param['subModelLabels'])){
            $this->param['modules']=$this->param['subModelLabels'];
        }
        $data['list']    = $logLogic->getSystemLogs($this->param);
        $data['count']   = $logLogic->getSystemLogCount($this->param);
        $data['modules'] = $logLogic->systemModules;

        return resultArray(['data' => $data]);
    }

    /**
     * 登录日志
     *
     * @param LogLogic $logLogic
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function loginRecord(LogLogic $logLogic)
    {
        $this->param['startTime']= !empty($this->param['startTime'])?$this->param['startTime'].' 00:00:00':'';
        $this->param['endTime']= !empty($this->param['endTime'])?$this->param['endTime'].' 23:59:59':'';
        $data = $logLogic->getLoginRecord($this->param);

        return resultArray(['data' => $data]);
    }
    
    /**
     * 日志导出
     *
     * @param LogLogic $logLogic
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/8 0008 16:36
     */
    public function excelImport()
    {
        $logLogic =new LogLogic;
        $data = $logLogic->downExcel($this->param);
        return $data;
    }
}