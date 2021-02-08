<?php
/**
 * 发票表
 *
 * @author qifan
 * @data 2020-12-07
 */

namespace app\crm\model;

use app\admin\model\Common;

class Invoice extends Common
{
    protected $name = 'crm_invoice';
    protected $pk   = 'invoice_id';

    /**
     * 关联用户模型
     *
     * @return \think\model\relation\HasOne
     */
    public function toCustomer()
    {
        return $this->hasOne('Customer', 'customer_id', 'customer_id')->bind([
            'customer_name' => 'name'
        ]);
    }

    /**
     * 关联合同模型
     *
     * @return \think\model\relation\HasOne
     */
    public function toContract()
    {
        return $this->hasOne('Contract', 'contract_id', 'contract_id')->bind([
            'contract_number' => 'num',
            'contract_money'  => 'money'
        ]);
    }

    /**
     * 关联用户模型
     *
     * @return \think\model\relation\HasOne
     */
    public function toAdminUser()
    {
        return $this->hasOne('AdminUser', 'id', 'owner_user_id')->bind([
           'owner_user_name' => 'realname'
        ]);
    }
}