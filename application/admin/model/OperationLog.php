<?php
/**
 * 数据操作日志
 *
 * @author qifna
 * @date 2020-12-29
 */

namespace app\admin\model;

use think\Model;

class OperationLog extends Model
{
    protected $name = 'admin_operation_log';

    protected $pk = 'log_id';

    /**
     * 关联后台员工表姓名
     *
     * @return \think\model\relation\HasOne
     */
    public function toAdminUser()
    {
        return $this->hasOne('User', 'id', 'user_id')->bind(['source_name' => 'realname']);
    }
}