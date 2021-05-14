<?php
/**
 * 客户公海
 *
 * @author fanqi
 * @since 2021-04-13
 */

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\logic\CustomerPoolLogic;
use think\Hook;
use think\Request;
use think\response\Json;

class CustomerPool extends ApiCommon
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
            'permission' => [],
            'allow' => [
                'index',
                'read',
                'pondlist',
                'field',
                'advanced',
                'authority',
                'receive',
                'distribute',
                'delete',
                'fieldconfig',
                'setfieldwidth',
                'setfieldconfig',
                'exceldownload',
                'import',
                'export'
            ]
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 公海列表
     *
     * @author fanqi
     * @since 2021-04-14
     * @return Json
     */
    public function index()
    {
        if (empty($this->param['pool_id'])) return resultArray(['error' => '缺少公海ID']);

        $data = (new CustomerPoolLogic())->getPoolList($this->param);

        return resultArray(['data' => $data]);
    }

    /**
     * 详情
     *
     * @author fanqi
     * @since 2021-04-14
     * @return Json
     */
    public function read()
    {
        if (empty($this->param['pool_id']) || empty($this->param['customer_id'])) return resultArray(['error' => '参数错误！']);

        $userInfo = $this->userInfo;
        $param = $this->param;
        $param['user_id'] = $userInfo['id'];

        $data = (new CustomerPoolLogic())->getPoolData($param);

        return resultArray(['data' => $data]);
    }

    /**
     * 删除公海客户
     *
     * @author fanqi
     * @since 2021-04-15
     * @return Json
     */
    public function delete()
    {
        if (empty($this->param['id'])) return resultArray(['error' => '请选择要删除的客户！']);

        $this->param['user_id'] = $this->userInfo['id'];

        $result = (new CustomerPoolLogic())->deletePoolCustomer($this->param);

        if (!empty($result)) return resultArray(['error' => $result]);

        return resultArray(['data' => '删除成功！']);
    }

    /**
     * 公海池列表
     *
     * @author fanqi
     * @since 2021-04-13
     * @return Json
     */
    public function pondList()
    {
        $data = (new CustomerPoolLogic())->getPondList(['user_id' => $this->userInfo['id'], 'structure_id' => $this->userInfo['structure_id']]);

        return resultArray(['data' => $data]);
    }

    /**
     * 公海字段
     *
     * @author fanqi
     * @since 2021-04-13
     * @return Json
     */
    public function field()
    {
        if (empty($this->param['pool_id'])) return resultArray(['error' => '缺少公海ID！']);

        $userInfo = $this->userInfo;
        $param = $this->param;
        $param['user_id'] = $userInfo['id'];

        $data = (new CustomerPoolLogic())->getFieldList($param);

        return resultArray(['data' => $data]);
    }

    /**
     * 高级筛选字段列表
     *
     * @author fanqi
     * @since 2021-04-14
     * @return Json
     */
    public function advanced()
    {
        if (empty($this->param['types'])) return resultArray(['error' => '缺少模块类型！']);

        $data = (new CustomerPoolLogic())->getAdvancedFilterFieldList($this->param);

        return resultArray(['data' => $data]);
    }

    /**
     * 领取公海池客户
     *
     * @author fanqi
     * @since 2021-04-15
     * @return Json
     */
    public function receive()
    {
        if (empty($this->param['customer_id'])) return resultArray(['error' => '请选择要领取的公海客户！']);

        $param = $this->param;
        $param['user_id'] = $this->userInfo['id'];

        $result = (new CustomerPoolLogic())->receiveCustomers($param);

        if (!empty($result)) return resultArray(['error' => $result]);

        return resultArray(['data' => '领取成功！']);
    }

    /**
     * 分配公海客户
     *
     * @author fanqi
     * @since 2021-04-15
     * @return Json
     */
    public function distribute()
    {
        if (empty($this->param['customer_id'])) return resultArray(['error' => '请选择要分配的公海客户！']);
        if (empty($this->param['user_id'])) return resultArray(['error' => '请选择要分配的员工！']);

        $result = (new CustomerPoolLogic())->distributeCustomer($this->param);

        if (!empty($result)) return resultArray(['error' => $result]);

        return resultArray(['data' => '分配成功！']);
    }

    // 公海客户导入模板下载
    public function excelDownload($save_path='')
    {
        $excelModel = new \app\admin\model\Excel();
        $param=$this->param;
        $field_list=$this->fieldsData($param);
        $excelModel->excelImportDownload($field_list, 'crm_customer', $save_path);
    }

    // 导入
    public function import()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $excelModel = new \app\admin\model\Excel();
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];
        $param['deal_time'] = time();
        $param['types'] = 'crm_customer';
        $file = request()->file('file');
        // $res = $excelModel->importExcel($file, $param, $this);
        $res = $excelModel->batchImportData($file, $param, $this);
        RecordActionLog($userInfo['id'],'crm_customer','excel','导入公海客户','','','导入公海客户');
        return resultArray(['data' => $excelModel->getError()]);
    }

    // 导出
    public function export()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $action_name='导出全部';
        if ($param['customer_id']) {
            $action_name='导出选中';
        }
        $param['is_excel'] = 1;
        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list=$this->fieldsData($param);
        $field=[
            1=>[
            'field'=>'before_owner_user_name',
            'types'=>'crm_customer',
            'name'=>'前负责人',
            ],
            2=>[
                'field'=>'into_pool_time',
                'types'=>'crm_customer',
                'name'=>'进入公海时间',
            ]
        ];
        $field_list=array_merge($field_list,$field);
        // 文件名
        $file_name = '5kcrm_customer_' . date('Ymd');
        $model = model('Customer');
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        unset($param['page']);
        unset($param['export_queue_index']);
      
        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function ($page, $limit) use ($model, $param, $field_list) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = (new CustomerPoolLogic())->getPoolList($param);
            $data['list'] = $model->exportHandle($data['list'], $field_list, 'customer');
            return $data;
        });
        RecordActionLog($userInfo['id'],'crm_customer','excelexport',$action_name,'','','导出客户');
        return resultArray(['error' => 'error']);
    }

    /**
     * 公海权限
     *
     * @author fanqi
     * @since 2021-04-14
     * @return Json
     */
    public function authority()
    {
        $param = $this->param;
        $param['user_id'] = $this->userInfo['id'];
        $param['structure_id'] = $this->userInfo['structure_id'];

        $data = (new CustomerPoolLogic())->getAuthorityData($param);

        return resultArray(['data' => $data]);
    }

    /**
     * 公海字段样式
     *
     * @author fanqi
     * @since 2021-04-22
     * @return Json
     */
    public function fieldConfig()
    {
        if (empty($this->param['pool_id'])) return resultArray(['error' => '缺少公海ID！']);

        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];

        $data = (new CustomerPoolLogic())->getFieldConfigIndex($param);

        return resultArray(['data' => $data]);
    }

    /**
     * 设置公海字段宽度
     *
     * @author fanqi
     * @since 2021-04-22
     * @return Json
     */
    public function setFieldWidth()
    {
        if (empty($this->param['pool_id'])) return resultArray(['error' => '缺少公海ID！']);
        if (empty($this->param['field'])) return resultArray(['error' => '缺少字段名称！']);
        if (empty($this->param['width'])) return resultArray(['error' => '缺少宽度值！']);

        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];

        (new CustomerPoolLogic())->setFieldWidth($param);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 设置公海字段样式
     *
     * @author fanqi
     * @since 2021-04-22
     * @return Json
     */
    public function setFieldConfig()
    {
        if (empty($this->param['pool_id'])) return resultArray(['缺少公海ID！']);
        if (empty($this->param['value']) && empty($this->param['hide_value'])) return resultArray(['error' => '字段参数错误！']);

        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];

        (new CustomerPoolLogic())->setFieldConfig($param);

        return resultArray(['data' => '操作成功！']);
    }
    public function fieldsData($param){
        $pool_list=db('crm_customer_pool_field_setting')->where(['pool_id'=>$param['pool_id'],'is_hidden'=>0])->select();
        $fieldModel = new \app\admin\model\Field();
        $fieldParam['types'] = 'crm_customer';
        $fieldParam['action'] = 'excel';
        $merge_list = $fieldModel->field($fieldParam);
        $field_list=array_intersect($merge_list,$pool_list);
        return $field_list;
        
    }
}