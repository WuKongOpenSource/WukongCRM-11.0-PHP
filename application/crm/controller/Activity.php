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
            'allow'=>['index', 'save', 'read', 'update', 'delete', 'getphrase', 'setphrase', 'getrecordauth']
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
}