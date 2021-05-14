<?php
namespace app\crm\model;

use app\crm\controller\Common;

class Market extends Common {
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    protected $name = 'crm_market';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
