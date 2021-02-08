<?php
/**
 * 系统日志模型
 *
 * @author qifan
 * @date 2020-11-30
 */

namespace app\admin\model;

use think\Model;

class SystemLog extends Model
{
    protected $name = 'admin_system_log';

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