<?php
/**
 * 活动控制器
 *
 * @author qifan
 * @date 2020-12-09
 */
namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\logic\ActivityLogic;
use think\Hook;
use think\Request;

class Activity extends ApiCommon
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
            'allow'=>['index', 'save', 'read', 'update', 'delete', 'getphrase', 'setphrase', 'getrecordauth','excelimport','excelexport','exceldownload']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 活动列表
     *
     * @param ActivityLogic $activityLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(ActivityLogic $activityLogic)
    {
        $param = $this->param;
        $param['user_id'] = $this->userInfo['id'];

        $data = $activityLogic->index($param);

        return resultArray(['data' => $data]);
    }

    /**
     * 创建活动【跟进记录】
     *
     * @param ActivityLogic $activityLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function save(ActivityLogic $activityLogic)
    {
        if (!checkPerByAction('crm', 'activity', 'save')) {
            return resultArray(['error' => '你没有创建跟进记录的权限！']);
        }
        if (empty($this->param['activity_type']))    return resultArray(['error' => '缺少模块类型！']);
        if (empty($this->param['activity_type_id'])) return resultArray(['error' => '缺少活动类型ID！']);
        if (empty($this->param['content']))          return resultArray(['error' => '请填写跟进内容！']);
        if (!empty($this->param['next_time']) && strtotime($this->param['next_time']) < time()) {
            return resultArray(['error' => '下次联系时间不能在当前时间之前！']);
        }

        $param = $this->param;
        $param['user_id'] = $this->userInfo['id'];

        if (!$activityLogic->save($param)) return resultArray(['error' => '操作失败！']);
        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 活动详情
     *
     * @param ActivityLogic $activityLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read(ActivityLogic $activityLogic)
    {
        if (!checkPerByAction('crm', 'activity', 'read')) {
            return resultArray(['error' => '你没有查看跟进记录的权限！']);
        }
        if (empty($this->param['activity_id'])) return resultArray(['error' => '请选择跟进记录！']);

        $data = $activityLogic->read($this->param['activity_id']);

        return resultArray(['data' => $data]);
    }

    /**
     * 编辑活动【跟进记录】
     *
     * @param ActivityLogic $activityLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update(ActivityLogic $activityLogic)
    {
        if (!checkPerByAction('crm', 'activity', 'update')) {
            return resultArray(['error' => '你没有编辑跟进记录的权限！']);
        }
        if (empty($this->param['activity_id']))      return resultArray(['error' => '请选择跟进记录！']);
        if (empty($this->param['activity_type']))    return resultArray(['error' => '缺少活动类型！']);
        if (empty($this->param['activity_type_id'])) return resultArray(['error' => '缺少活动类型ID！']);
        if (empty($this->param['content']))          return resultArray(['error' => '请填写跟进内容！']);

        $param  = $this->param;
        $userId = $this->userInfo['id'];

        if (!$activityLogic->update($param)) return resultArray(['error' => '操作失败！']);

        $data = $activityLogic->getFollowData($param['activity_id'], $userId);

        return resultArray(['data' => $data]);
    }

    /**
     * 删除活动【跟进记录】
     *
     * @param ActivityLogic $activityLogic
     * @return \think\response\Json
     */
    public function delete(ActivityLogic $activityLogic)
    {
        if (!checkPerByAction('crm', 'activity', 'delete')) {
            return resultArray(['error' => '你没有删除跟进记录的权限！']);
        }
        if (empty($this->param['activity_id'])) return resultArray(['error' => '请选择跟进记录！']);

        if (!$activityLogic->delete($this->param['activity_id'])) return resultArray(['error' => '操作失败！']);
        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 获取常用语
     *
     * @param ActivityLogic $activityLogic
     * @return \think\response\Json
     */
    public function getPhrase(ActivityLogic $activityLogic)
    {
        $data = $activityLogic->getPhrase();

        return resultArray(['data' => $data]);
    }

    /**
     * 设置常用语
     *
     * @param ActivityLogic $activityLogic
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setPhrase(ActivityLogic $activityLogic)
    {
        if (empty($this->param['phrase']))     return resultArray(['error' => '缺少常用语数据！']);
        if (!is_array($this->param['phrase'])) return resultArray(['error' => '参数格式错误！']);

        if (!$activityLogic->setPhrase($this->param['phrase'])) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 跟进记录权限
     *
     * @return \think\response\Json
     */
    public function getRecordAuth()
    {
        $data = [
            'index'  => checkPerByAction('crm', 'activity', 'index'),
            'read'   => checkPerByAction('crm', 'activity', 'read'),
            'save'   => checkPerByAction('crm', 'activity', 'save'),
            'update' => checkPerByAction('crm', 'activity', 'update'),
            'delete' => checkPerByAction('crm', 'activity', 'delete'),
        ];

        return resultArray(['data' => $data]);
    }
    /**
     * 导入模板下载
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/10 0010 16:01
     */
    public function excelDownload($save_path = ''){
        $param = $this->param;
        $excelModel = new \app\admin\model\Excel();
        $field_list=$this->importData($param);
        $types='crm_activity';
        $excelModel->importDown($field_list,$types,$save_path);
    }
    
    /**
     * 导入导出模板标题
     * @param $param
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/13 0013 11:15
     */
    public function importData($param){
        switch ($param['label']){
            case 1 :
                $field = [
                    '2' => [
                        'name' => '所属线索',
                        'field' => 'activity_type_id',
                        'types' => 'log',
                        'form_type' => 'datetime',
                        'is_null' => 1,
                    ]
                ];
                break;
            case 3:
                $field = [
                    '2' => [
                        'name' => '所属联系人',
                        'field' => 'activity_type_id',
                        'types' => 'log',
                        'form_type' => 'datetime',
                        'is_null' => 1,
                    ],
                ];
                break;
            case 5:
                $field = [
                    '2' => [
                        'name' => '所属商机',
                        'field' => 'activity_type_id',
                        'types' => 'log',
                        'form_type' => 'text',
                        'is_null' => 1,
                    ]
                ];
                break;
            case 6:
                $field = [
                    '2' => [
                        'name' => '所属合同',
                        'field' => 'activity_type_id',
                        'types' => 'log',
                        'form_type' => 'text',
                        'is_null' => 1,
                    ],
                ];
                break;
            case 2:
                $field_list = [
                    '0' => [
                        'name' => '跟进内容',
                        'field' => 'content',
                        'types' => 'log',
                        'form_type' => 'text',
                        'is_null' => 1,
                    ],
                    '1' => [
                        'name' => '创建人',
                        'field' => 'create_user_id',
                        'types' => 'log',
                        'form_type' => 'user',
                        'is_null' => 1,
                    ],
                    '2' => [
                        'name' => '所属客户',
                        'field' => 'activity_type_id',
                        'types' => 'log',
                        'form_type' => 'text',
                        'is_null' => 1,
                    ],
                    '3' => [
                        'name' => '跟进时间-例:2020-2-1',
                        'field' => 'next_time',
                        'types' => 'log',
                        'form_type' => 'datetime',
                    ],
                    '4' => [
                        'name' => '跟进方式',
                        'field' => 'category',
                        'types' => 'log',
                        'form_type' => 'text',
                    ],
                    '5' => [
                        'name' => '相关联系人',
                        'field' => 'contacts_ids',
                        'types' => 'log',
                        'form_type' => 'text',
                    ],
                    '6' => [
                        'name' => '相关商机',
                        'field' => 'business_ids',
                        'types' => 'log',
                        'form_type' => 'text',
                    ]
                ];
                break;
        }
        $fields = [
            '0' => [
                'name' => '跟进内容',
                'field' => 'content',
                'types' => 'log',
                'form_type' => 'text',
                'is_null' => 1,
            ],
            '1' => [
                'name' => '创建人',
                'field' => 'create_user_id',
                'types' => 'log',
                'form_type' => 'user',
                'is_null' => 1,
            ],
            '2' => [
                'name' => '所属111',
                'field' => 'activity_type_id',
                'types' => 'log',
                'form_type' => 'text',
                'is_null' => 1,
            ],
            '3' => [
                'name' => '跟进时间-例:2020-2-1',
                'field' => 'next_time',
                'types' => 'log',
                'form_type' => 'datetime',
            ],
            '4' => [
                'name' => '跟进方式',
                'field' => 'category',
                'types' => 'log',
                'form_type' => 'text',
            ],
        ];
        // 导入的字段列表
        if(!empty($param['down'])){
            $field_list = [
                '0' => ['name' => '所属客户', 'field' => 'activity_type_name'],
                '1' => ['name' => '跟进内容', 'field' => 'content'],
                '2' => ['name' => '创建人', 'field' => 'create_user_name'],
                '3' => ['name' => '跟进时间', 'field' => 'create_time'],
                '4' => ['name' => '跟进方式','field' => 'category'],
                '5' => ['name' => '下次联系时间', 'field' => 'next_time'],
                '6' => ['name' => '相关联系人', 'field' => 'contacts_ids'],
                '7' => ['name' => '相关商机', 'field' => 'business_ids'],
               
            ];
        }else{
            if(empty($field_list)){
                $field_list=array_merge($fields,$field);
            }
        }
        return $field_list;
    }
    /**
     * 导入数据
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/10 0010 16:27
     */
    public function excelImport(){
        $param = $this->param;
        $field_list=$this->importData($param['label']);
        $excelModel = new \app\admin\model\Excel();
        $file = request()->file('file');
        switch ($param['label']){
            case 1 :
                $param['types']='crm_leads';
                $param['activity_type']=1;
                break;
            case 3:
                $param['types']='crm_contacts';
                $param['activity_type']=3;
                break;
            case 5:
                $param['types']='crm_business';
                $param['activity_type']=5;
                break;
            case 6:
                $param['types']='crm_contract';
                $param['activity_type']=6;
                break;
            case 2:
                $param['types']='crm_customer';
                $param['activity_type']=2;
                break;
        }
        $res = $excelModel->ActivityImport($file,$field_list, $param,$this);
        if (!$res) {
            return resultArray(['error' => $excelModel->getError()]);
        }
        return resultArray(['data' => $excelModel->getError()]);
    }
    
    /**
     * 导出跟进记录
     * action 列表分辨是否导出
     * label 导出类型 合同 客户 联系人
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/13 0013 11:32
     */
    public function excelExport(){
       
        $activityLogic=new ActivityLogic();
        $indexLogic=new \app\crm\logic\IndexLogic();
        $param = $this->param;
        $param['action']='crm_activity';
        $list=$indexLogic->activityList($param);
//        $param['down']=1;
        $field_list=$this->importData($param);
        $data=$activityLogic->excelExport($field_list,$list);
        return $data;
    }
}