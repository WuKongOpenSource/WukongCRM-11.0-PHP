<?php
/**
 * 活动模型
 *
 * @author qifan
 * @date 2020-12-09
 */

namespace app\crm\model;

use app\admin\model\Common;

class Activity extends Common
{
    protected $name               = 'crm_activity';
    protected $createTime         = 'create_time';
    protected $updateTime         = 'update_time';
    protected $autoWriteTimestamp = true;
}