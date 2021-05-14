<?php
/**
 * 更新sql（包含安装）
 *
 * @author fanqi
 * @since 2021-05-08
 */
namespace app\admin\controller;

use think\Db;
use think\Exception;

class UpdateSql
{
    /**
     * 添加公海默认数据
     *
     * @author fanqi
     * @since 2021-05-08
     * @return bool
     */
    static public function addPoolDefaultData()
    {
        # 员工ID
        $userIds = db('admin_user')->column('id');

        # 公海主数据
        $poolData = [
            'pool_name'         => '系统默认公海',
            'admin_user_ids'    => ',1,',
            'user_ids'          => ','.implode(',', $userIds).',',
            'department_ids'    => '',
            'status'            => 1,
            'before_owner_conf' => 0,
            'before_owner_day'  => 0,
            'receive_conf'      => 0,
            'receive_count'     => 0,
            'remind_conf'       => 0,
            'remain_day'        => 0,
            'recycle_conf'      => 1,
            'create_user_id'    => 1,
            'create_time'       => time()
        ];

        # 公海规则数据
        $poolRuleData = [
            'pool_id'         => 0,
            'type'            => 1,
            'deal_handle'     => 0,
            'business_handle' => 0,
            'level_conf'      => 1,
            'level'           => json_encode([['level' => '所有客户', 'limit_day' => 30]]),
            'limit_day'       => 0
        ];

        # 公海字段数据
        $poolFieldData = [];
        $fields = db('admin_field')->field(['field', 'name', 'form_type', 'is_hidden'])->where(['types' => 'crm_customer'])->select();
        foreach ($fields AS $key => $value) {
            $poolFieldData[] = [
                'field_name' => $value['field'],
                'name'       => $value['name'],
                'form_type'  => $value['form_type'],
                'is_hidden'  => $value['is_hidden']
            ];
        }
        $poolFieldData[] = ['field_name' => 'address', 'name' => '省、市、区/县', 'form_type' => 'customer_address', 'is_hidden' => 0];
        $poolFieldData[] = ['field_name' => 'detail_address', 'name' => '详细地址', 'form_type' => 'text', 'is_hidden' => 0];
        $poolFieldData[] = ['field_name' => 'last_record', 'name' => '最后跟进记录', 'form_type' => 'text', 'is_hidden' => 0];
        $poolFieldData[] = ['field_name' => 'last_time', 'name' => '最后跟进时间', 'form_type' => 'datetime', 'is_hidden' => 0];
        $poolFieldData[] = ['field_name' => 'before_owner_user_id', 'name' => '前负责人', 'form_type' => 'user', 'is_hidden' => 0];
        $poolFieldData[] = ['field_name' => 'into_pool_time', 'name' => '进入公海时间', 'form_type' => 'datetime', 'is_hidden' => 0];
        $poolFieldData[] = ['field_name' => 'create_time', 'name' => '创建时间', 'form_type' => 'datetime', 'is_hidden' => 0];
        $poolFieldData[] = ['field_name' => 'update_time', 'name' => '更新时间', 'form_type' => 'datetime', 'is_hidden' => 0];
        $poolFieldData[] = ['field_name' => 'create_user_id', 'name' => '创建人', 'form_type' => 'user', 'is_hidden' => 0];

        Db::startTrans();
        try {
            # 添加公海主数据
            $poolId = Db::name('crm_customer_pool')->insert($poolData, false, true);

            # 添加公海规则数据
            $poolRuleData['pool_id'] = $poolId;
            Db::name('crm_customer_pool_rule')->insert($poolRuleData);

            # 添加公海字段数据
            array_walk($poolFieldData, function (&$val) use ($poolId) {
                $val['pool_id'] = $poolId;
            });
            Db::name('crm_customer_pool_field_setting')->insertAll($poolFieldData);

            Db::commit();

            return true;
        } catch (Exception $e) {
            Db::rollback();

            return false;
        }
    }

    /**
     * 添加跟进记录的导入导出权限数据
     *
     * @author fanqi
     * @since 2021-05-08
     */
    static public function addFollowRuleData()
    {
        # 删除旧版的跟进记录权限规则数据
        db('admin_rule')->where(['types' => 2, 'title' => '跟进记录管理', 'name' => 'record', 'level' => 2, 'pid' => 1])->delete();

        # 新版跟进记录权限规则增加导入导出
        $activityPid = db('admin_rule')->where(['types' => 2, 'title' => '跟进记录', 'name' => 'activity', 'level' => 2])->value('id');
        if (!db('admin_rule')->where(['types' => 2, 'pid' => $activityPid, 'name' => 'excelImport'])->value('id')) {
            db('admin_rule')->insert(['types' => 2, 'title' => '导入', 'name' => 'excelImport', 'level' => 3, 'pid' => $activityPid, 'status' => 1]);
        }
        if (!db('admin_rule')->where(['types' => 2, 'pid' => $activityPid, 'name' => 'excelExport'])->value('id')) {
            db('admin_rule')->insert(['types' => 2, 'title' => '导出', 'name' => 'excelExport', 'level' => 3, 'pid' => $activityPid, 'status' => 1]);
        }
    }
}