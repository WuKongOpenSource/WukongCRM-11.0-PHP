<?php
/**
 * 初始化逻辑类
 *
 * @author qifan
 * @date 2020-01-05
 */

namespace app\admin\logic;

use app\admin\controller\UpdateSql;
use think\Db;
use think\Exception;

class InitializeLogic
{
    public $log = '操作成功！';

    # 值为false时，终止其他方法的操作
    private $status = true;

    /**
     * 重置数据
     *
     * @param $param
     * @return bool
     */
    public function update($param)
    {
        # 设置脚本执行时间和内存
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '256M');

        # 重置单个或多个模块
        foreach ($param AS $key => $value) {
            if ($value == 'crm'         && $this->status) $this->resetCustomerManagementData(); # 重置客户管理数据
            if ($value == 'taskExamine' && $this->status) $this->resetTaskExamineData();        # 重置任务/审批数据
            if ($value == 'log'         && $this->status) $this->resetDailyRecordData();        # 重置日志数据
            if ($value == 'project'     && $this->status) $this->resetProjectManagementData();  # 重置项目管理数据
            if ($value == 'calendar'    && $this->status) $this->resetCalendarData();           # 重置日历数据
        }

        return true;
    }

    /**
     * 验证密码
     *
     * @param $userId
     * @param $password
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function verification($userId, $password)
    {
        $userInfo = Db::name('admin_user')->field(['password', 'salt'])->where('id', $userId)->find();

        return user_md5($password, $userInfo['salt']) == $userInfo['password'];
    }

    /**
     * 重置客户管理数据
     */
    private function resetCustomerManagementData()
    {
        # 表前缀
        $prefix = config('database.prefix');

        # 文件数组
        $files = [];

        # 启动事务
        Db::startTrans();
        try {
            # ------ 重置产品数据START ------ #

            # 获取产品附件ID
            $productFileIds = Db::name('crm_product_file')->column('file_id');
            # 查询产品文件数据
            $productFileInfo = $this->getFileList($productFileIds);

            # 获取产品图和产品详情图附件ID
            $productDetailsIds  = [];
            $productDetailFiles = Db::name('crm_product')->field(['cover_images', 'details_images'])->select();
            foreach ($productDetailFiles AS $key => $value) {
                if (!empty($value['cover_images'])) $productDetailsIds = array_merge($productDetailsIds, explode(',', $value['cover_images']));
                if (!empty($value['details_images'])) $productDetailsIds = array_merge($productDetailsIds, explode(',', $value['details_images']));
            }

            # 合并附件ID数据
            $productFileIds = array_merge($productFileIds, $productDetailsIds);

            # 获取产品图和产品详情图文件数据
            $productDetailsFiles = $this->getFileList($productDetailsIds);

            # 合并附件数据
            $files = array_merge($files, $productDetailsFiles);
            $files = array_merge($files, $productFileInfo);

            # 删除产品分类表
            Db::name('crm_product_category')->where(['category_id' => ['neq', 1]])->delete();
            # 重置产品分类自增ID
            Db::query("ALTER TABLE ".$prefix."crm_product_category AUTO_INCREMENT = 1");

            # 清除产品附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_product_file");

            # 删除产品附件
            Db::name('admin_file')->whereIn('file_id', $productFileIds)->delete();

            # 清除产品表数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_product");

            # ------ 重置产品数据 END ------ #


            # ------ 重置回访数据 START ------ #

            # 获取回访附件ID
            $visitFileIds = Db::name('crm_visit_file')->column('file_id');
            # 查询回访文件数据
            $visitFileInfo = $this->getFileList($visitFileIds);

            # 合并附件数据
            $files = array_merge($files, $visitFileInfo);

            # 清除回访附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_visit_file");

            # 删除回访附件
            Db::name('admin_file')->whereIn('file_id', $visitFileIds)->delete();

            # 清除回访表数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_visit");

            # ------ 重置回访数据 END ------ #


            # ------ 重置发票数据 START ------ #

            # 获取回访附件ID
            $invoiceFileIds = Db::name('crm_invoice_file')->column('file_id');
            # 查询回访文件数据
            $invoiceFileInfo = $this->getFileList($invoiceFileIds);

            # 合并附件数据
            $files = array_merge($files, $invoiceFileInfo);

            # 清除发票附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_invoice_file");

            # 删除发票附件
            Db::name('admin_file')->whereIn('file_id', $invoiceFileIds)->delete();

            # 清除发票开户行信息数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_invoice_info");

            # 清除发票数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_invoice");

            # ------ 重置发票数据 END ------ #


            # ------ 重置回款数据 START ------ #

            # 获取回款附件ID
            $receivablesFileIds = Db::name('crm_receivables_file')->column('file_id');
            # 查询回访文件数据
            $receivablesFileInfo = $this->getFileList($receivablesFileIds);

            # 合并附件数据
            $files = array_merge($files, $receivablesFileInfo);

            # 清除回款附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_receivables_file");

            # 删除回款附件
            Db::name('admin_file')->whereIn('file_id', $receivablesFileIds)->delete();

            # 清除回款计划数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_receivables_plan");

            # 清除回款数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_receivables");

            # ------ 重置回款数据 END ------ #


            # ------ 重置合同数据 START ------ #

            # 获取合同附件ID
            $contractFileIds = Db::name('crm_contract_file')->column('file_id');
            # 查询合同文件数据
            $contractFileInfo = $this->getFileList($contractFileIds);

            # 合并附件数据
            $files = array_merge($files, $contractFileInfo);

            # 清除合同附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_contract_file");

            # 删除合同附件
            Db::name('admin_file')->whereIn('file_id', $contractFileIds)->delete();

            # 删除合同产品关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_contract_product");

            # 删除合同数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_contract");

            # ------ 重置合同数据 END ------ #


            # ------ 重置商机数据 START ------ #

            # 获取商机附件ID
            $businessFileIds = Db::name('crm_business_file')->column('file_id');
            # 查询商机文件数据
            $businessFileInfo = $this->getFileList($businessFileIds);

            # 合并附件数据
            $files = array_merge($files, $businessFileInfo);

            # 清除商机附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_business_file");

            # 删除商机附件
            Db::name('admin_file')->whereIn('file_id', $businessFileIds)->delete();

            # 清除商机日志数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_business_log");

            # 清除商机产品关系数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_business_product");

            # 删除商机状态组类别数据
            Db::name('crm_business_type')->where(['type_id' => ['neq', 1]])->delete();
            # 重置商机状态组类别自增ID
            Db::query("ALTER TABLE ".$prefix."crm_business_type AUTO_INCREMENT = 1");

            # 删除商机状态数据
            Db::name('crm_business_status')->where(['type_id' => ['gt', 1]])->delete();
            # 重置商机状态自增ID
            Db::query("ALTER TABLE ".$prefix."crm_business_status AUTO_INCREMENT = 1");

            # 删除商机数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_business");

            # ------ 重置商机数据 END ------ #


            # ------ 重置联系人数据 START ------ #

            # 获取联系人附件ID
            $contactsFileIds = Db::name('crm_contacts_file')->column('file_id');
            # 查询联系人文件数据
            $contactsFileInfo = $this->getFileList($contactsFileIds);

            # 合并附件数据
            $files = array_merge($files, $contactsFileInfo);

            # 清除联系人附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_contacts_file");

            # 删除联系人附件
            Db::name('admin_file')->whereIn('file_id', $contactsFileIds)->delete();

            # 清除联系人商机关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_contacts_business");

            # 清除联系人数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_contacts");

            # ------ 重置联系人数据 END ------ #


            # ------ 重置公海数据 START ------ #

            # 清除公海主数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer_pool");

            # 清除公海字段数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer_pool_field_setting");

            # 清除公海用户自定义字段样式数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer_pool_field_style");

            # 清除公海操作记录数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer_pool_record");

            # 清除公海关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer_pool_relation");

            # 清除公海规则数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer_pool_rule");

            # 添加公海默认数据
            UpdateSql::addPoolDefaultData();

            # ------ 重置公海数据 END ------ #


            # ------ 重置客户数据 START ------ #

            # 获取客户附件ID
            $customerFileIds = Db::name('crm_customer_file')->column('file_id');
            # 查询联系人文件数据
            $customerFileInfo = $this->getFileList($customerFileIds);

            # 合并附件数据
            $files = array_merge($files, $customerFileInfo);

            # 清除客户附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer_file");

            # 删除客户附件
            Db::name('admin_file')->whereIn('file_id', $customerFileIds)->delete();

            # 清除客户数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer");

            # ------ 重置客户数据 END ------ #


            # ------ 重置线索数据 START ------ #

            # 获取线索附件ID
            $leadsFileIds = Db::name('crm_leads_file')->column('file_id');
            # 查询线索文件数据
            $leadsFileInfo = $this->getFileList($leadsFileIds);

            # 合并附件数据
            $files = array_merge($files, $leadsFileInfo);

            # 清除线索附件关联数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_leads_file");

            # 删除线索附件
            Db::name('admin_file')->whereIn('file_id', $leadsFileIds)->delete();

            # 清除线索数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_leads");

            # ------ 重置线索数据 END ------ #

            # ------ 重置附件表自增ID START ------ #

            Db::query("ALTER TABLE ".$prefix."admin_file AUTO_INCREMENT = 1");

            # ------ 重置附件表自增ID END ------ #


            # ------ 清除活动记录中关于客户管理模块的数据 START ------ #

            # 获取活动附件ID
            $activityFileIds = Db::name('crm_activity_file')->column('file_id');

            # 查询活动附件数据
            $activityFileInfo = $this->getFileList($activityFileIds);

            # 合并附件数据
            $files = array_merge($files, $activityFileInfo);

            # 清除活动关联附件数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_activity_file");

            # 清除活动数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."crm_activity");

            # ------ 清除活动记录中关于客户管理模块的数据 END ------ #


            # ------ 清除我的关注数据 START ------ #
            Db::query("TRUNCATE TABLE ".$prefix."crm_star");
            # ------ 清除我的关注数据 END ------ #


            # ------ 清除数据操作日志数据 START ------ #
            Db::name('admin_operation_log')->where(['module' => ['like', 'crm_%']])->delete();
            # ------ 清除数据操作日志数据 END ------ #


            # ------ 清除客户配置表（锁定、拥有）数据 START ------ #
            Db::query("TRUNCATE TABLE ".$prefix."crm_customer_config");
            # ------ 清除客户配置表（锁定、拥有）数据 END ------ #
            

            # ------ 清除导入数据记录表 START ------ #
            Db::name('admin_import_record')->where(['type' => ['like', 'crm_%']])->delete();
            # ------ 清除导入数据记录表 END ------ #


            # ------ 清除字段操作记录表 START ------ #
            Db::name('admin_action_record')->where(['types' => ['like', 'crm_%']])->delete();
            # ------ 清除字段操作记录表 END ------ #


            # ------ 清除数据操作记录表 START ------ #
            Db::name('admin_action_log')->where('module_name', 'crm')->delete();
            # ------ 清除数据操作记录表 END ------ #

            # ------ 清除有关客户模块的审批记录 START ------ #
            Db::name('admin_examine_record')->where(['types' => ['like', 'crm_%']])->delete();
            # ------ 清除有关客户模块的审批记录 END ------ #


            # ------ 清除跟客户模块有关的管理数据表 START ------ #

            # 清除项目关联客户模块表并重置字段ID
            Db::query("TRUNCATE TABLE ".$prefix."work_relation");
            # 清除任务关联客户模块表并重置字段ID
            Db::query("TRUNCATE TABLE ".$prefix."task_relation");
            # 清除日志关联客户模块表并重置字段ID
            Db::query("TRUNCATE TABLE ".$prefix."oa_log_relation");
            # 清除审批关联客户模块表并重置字段ID
            Db::query("TRUNCATE TABLE ".$prefix."oa_examine_relation");
            # 清除日程关联客户模块表并重置字段ID
            Db::query("TRUNCATE TABLE ".$prefix."oa_event_relation");

            # ------ 清除跟客户模块有关的管理数据表 END ------ #


            # ------ 重置自动编号数据 START ------ #
            $time = time();
            Db::query("TRUNCATE TABLE ".$prefix."crm_number_sequence");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (1, 1, 1, 'HT', null, null, null, null, ".$time.", 1, null, 0, 1)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (2, 2, 2, 'yyyyMMdd', null, null, null, null, ".$time.", 1, null, 0, 1)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (3, 3, 3, 1, 1, 1, 1, ".$time.", ".$time.", 1, null, 0, 1)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (4, 1, 1, 'HK', null, null, null, null, ".$time.", 1, null, 0, 2)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (5, 2, 2, 'yyyyMMdd', null, null, null, null, ".$time.", 1, null, 0, 2)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (6, 3, 3, 1, 1, 1, 1, ".$time.", ".$time.", 1, null, 0, 2)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (7, 1, 1, 'HF', null, null, null, null, ".$time.", 1, null, 0, 3)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (8, 2, 2, 'yyyyMMdd', null, null, null, null, ".$time.", 1, null, 0, 3)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (9, 3, 3, 1, 1, 1, 1, ".$time.", ".$time.", 1, null, 0, 3)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (10, 1, 2, 'yyyyMMdd', null, null, null, null, ".$time.", 1, null, 0, 4)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (11, 2, 1, 'FP', null, null, null, null, ".$time.", 1, null, 0, 4)");
            Db::query("INSERT INTO `".$prefix."crm_number_sequence` VALUES (12, 3, 3, 1, 1, 1, 1, ".$time.", ".$time.", 1, null, 0, 4)");
            # ------ 重置自动编号数据 END ------ #


            # ------ 设置跟进记录常用语 START ------ #
            $phrase = ['电话无人接听', '客户无意向', '客户意向度适中，后续继续跟进', '客户意向度较强，成交几率较大'];
            $phraseId = db('crm_config')->where('name', 'activity_phrase')->value('id');
            if (!empty($phraseId)) {
                db('crm_config')->where('id', $phraseId)->update([
                    'value' => serialize($phrase)
                ]);
            } else {
                db('crm_config')->insert([
                    'name' => 'activity_phrase',
                    'value' => serialize($phrase),
                    'description' => '跟进记录常用语'
                ]);
            }
            # ------ 设置跟进记录常用语 END ------ #


            # ------ 清除打印相关数据 START ------ #
            Db::query("TRUNCATE TABLE ".$prefix."admin_printing_data");
            Db::query("TRUNCATE TABLE ".$prefix."crm_printing_record");
            # ------ 清除打印相关数据 END ------ #


            # ------ 删除审批记录 START ------ #
            Db::name('admin_examine_record')->whereLike('types', 'crm%')->delete();
            Db::query("ALTER TABLE ".$prefix."admin_examine_record AUTO_INCREMENT = 1");
            # ------ 删除审批记录 END ------ #


            # ------ 清除消息数据 START ------ #
            Db::name('admin_message')->where('module_name', 'crm')->delete();
            # ------ 清除消息数据 END ------ #


            # ------ 删除附件 START ------ #

            if (!empty($files)) {
                foreach ($files AS $key => $value) {
                    unlink($value);
                }
            }

            # ------ 删除附件 START ------ #

            # 提交事务
            Db::commit();

            return true;
        } catch (Exception $e) {
            # 回滚事务
            Db::rollback();

            # 将状态设置为false，终止其他方法的操作
            $this->status = false;

            $this->log = '重置客户管理模块时出错！';

            return false;
        }
    }

    /**
     * 重置任务审批数据
     */
    private function resetTaskExamineData()
    {
        # 表前缀
        $prefix = config('database.prefix');

        # 文件数组
        $files = [];

        # 启动事务
        Db::startTrans();
        try {
            # ------ 清除任务数据 START ------ #

//            # 获取任务ID
//            $taskIds = Db::name('task')->where(['work_id' => ['eq', 0]])->column('task_id');
//
//            # 获取任务下关联的附件ID
//            $taskFieldIds = Db::name('work_task_file')->whereIn('task_id', $taskIds)->column('file_id');
//
//            # 查询活动附件数据
//            $taskFileInfo = $this->getFileList($taskFieldIds);
//
//            # 合并附件数据
//            $files = array_merge($files, $taskFileInfo);
//
//            # 删除任务附件关联表
//            Db::name('work_task_file')->whereIn('task_id', $taskIds)->delete();
//            # 重置自增ID
//            Db::query("ALTER TABLE ".$prefix."work_task_file AUTO_INCREMENT = 1");
//
//            # 删除附件
//            Db::name('admin_file')->whereIn('file_id', $taskFieldIds)->delete();
//
//            # 清除任务关联的客户模块数据数据并重置自增ID
//            Db::query("TRUNCATE TABLE ".$prefix."task_relation");
//
//            # 删除任务log
//            Db::name('work_task_log')->whereIn('task_id', $taskIds)->delete();
//            # 重置任务log自增ID
//            Db::query("ALTER TABLE ".$prefix."work_task_log AUTO_INCREMENT = 1");
//
//            # 删除任务
//            Db::name('task')->where(['work_id' => ['eq', 0]])->delete();
//            # 重置任务自增ID
//            Db::query("ALTER TABLE ".$prefix."task AUTO_INCREMENT = 1");

            # ------ 清除任务数据 END ------ #


            # ------ 清除审批数据 START ------ #

            # 获取审批下关联的附件ID
            $examineFileIds = Db::name('oa_examine_file')->column('file_id');

            # 查询审批附件数据
            $examineFileInfo = $this->getFileList($examineFileIds);

            # 合并附件数据
            $files = array_merge($files, $examineFileInfo);

            # 清除审批关联的附件信息并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."oa_examine_file");

            # 获取差旅附件ID
            $travelFileId = Db::name('oa_examine_travel_file')->column('file_id');

            # 查询差旅附件数据
            $travelFileInfo = $this->getFileList($travelFileId);

            # 合并附件数据
            $files = array_merge($files, $travelFileInfo);

            # 清除差旅关联的附件信息并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."oa_examine_travel_file");

            # 删除差率和审批的附件
            Db::name('admin_file')->whereIn('file_id', $examineFileIds)->delete();
            Db::name('admin_file')->whereIn('file_id', $travelFileId)->delete();

            # 删除差旅数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."oa_examine_travel");

            # 删除审批关联的客户模块下的数据并重置ID
            Db::query("TRUNCATE TABLE ".$prefix."oa_examine_relation");

            # 删除审批数据并重置自增ID
            Db::query("TRUNCATE TABLE ".$prefix."oa_examine");

            # ------ 清除审批数据 END ------ #


            # ------ 清除活动中有关审批的数据 START ------ #

            # 获取有关审批的活动ID
            $activityIds = Db::name('crm_activity')->where('activity_type', 9)->column('activity_id');

            # 获取有关审批的附件ID
            $activityFileIds = Db::name('crm_activity_file')->whereIn('activity_id', $activityIds)->column('file_id');

            # 查询审批附件数据
            $activityFileInfo = $this->getFileList($activityFileIds);

            # 合并附件数据
            $files = array_merge($files, $activityFileInfo);

            # 删除有关审批的附件关联记录
            Db::name('crm_activity_file')->whereIn('activity_id', $activityIds)->delete();

            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."crm_activity_file AUTO_INCREMENT = 1");

            # 删除有关审批的活动记录
            Db::name('crm_activity')->where('activity_type', 9)->delete();

            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."crm_activity AUTO_INCREMENT = 1");

            # ------ 清除活动中有关审批的数据 END ------ #


            # ------ 清除有关审批的数据操作记录 START ------ #
            Db::name('admin_action_log')->where('module_name', 'oa')->where('controller_name', 'examine')->delete();
            Db::query("ALTER TABLE ".$prefix."admin_action_log AUTO_INCREMENT = 1");
            # ------ 清除活动中有关审批的数据 END ------ #

            # ------ 清除有关任务的数据操作记录 START ------ #
//            Db::name('admin_action_log')->where('module_name', 'oa')->where('controller_name', 'task')->delete();
//            Db::query("ALTER TABLE ".$prefix."admin_action_log AUTO_INCREMENT = 1");
            # ------ 清除有关任务的数据操作记录 END ------ #


            # ------ 清除审批日志 START ------ #
            Db::name('admin_examine_record')->where('types', 'oa_examine')->delete();
            Db::query("ALTER TABLE ".$prefix."admin_examine_record AUTO_INCREMENT = 1");
            # ------ 清除审批日志 END ------ #


            # ------ 清除任务和审批操作记录 START ------ #
//            Db::name('admin_operation_log')->where('module', 'oa_task')->delete();
            Db::name('admin_operation_log')->where('module', 'oa_examine')->delete();
            Db::query("ALTER TABLE ".$prefix."admin_operation_log AUTO_INCREMENT = 1");
            # ------ 清除任务操作记录 END ------ #


            # ------ 清除消息数据 START ------ #
            Db::name('admin_message')->where('module_name', 'oa')->where('controller_name', 'examine')->delete();
            # ------ 清除消息数据 END ------ #


            # ------ 重置附件表自增ID START ------ #
            Db::query("ALTER TABLE ".$prefix."admin_file AUTO_INCREMENT = 1");
            # ------ 重置附件表自增ID END ------ #


            # ------ 删除附件 START ------ #
            if (!empty($files)) {
                foreach ($files AS $key => $value) {
                    unlink($value);
                }
            }
            # ------ 删除附件 START ------ #


            # 提交事务
            Db::commit();

            return true;
        } catch (Exception $e) {
            # 回滚事务
            Db::rollback();

            # 将状态设置为false，终止其他方法的操作
            $this->status = false;

            $this->log = '重置任务/审批模块时出错！';

            return false;
        }
    }

    /**
     * 重置日志数据
     */
    private function resetDailyRecordData()
    {
        # 表前缀
        $prefix = config('database.prefix');

        # 文件数组
        $files = [];

        # 启动事务
        Db::startTrans();
        try {
            # ------ 清除日志数据 START ------ #

            # 获取日志附件ID
            $logFileIds = Db::name('oa_log_file')->column('file_id');

            # 查询活动附件数据
            $logFileInfo = $this->getFileList($logFileIds);

            # 合并附件数据
            $files = array_merge($files, $logFileInfo);

            # 清除日志附件关联数据
            Db::query("TRUNCATE TABLE ".$prefix."oa_log_file");

            # 清除日志客户管理关联数据
            Db::query("TRUNCATE TABLE ".$prefix."oa_log_relation");

            # 清楚日志数据
            Db::query("TRUNCATE TABLE ".$prefix."oa_log");

            # ------ 清除日志数据 START ------ #


            # ------ 清除活动中有关审批的数据 START ------ #

            # 获取有关日志的活动ID
            $activityIds = Db::name('crm_activity')->where('activity_type', 8)->column('activity_id');

            # 获取有关日志的附件ID
            $activityFileIds = Db::name('crm_activity_file')->whereIn('activity_id', $activityIds)->column('file_id');

            # 查询日志附件数据
            $activityFileInfo = $this->getFileList($activityFileIds);

            # 合并附件数据
            $files = array_merge($files, $activityFileInfo);

            # 删除有关日志的附件关联记录
            Db::name('crm_activity_file')->whereIn('activity_id', $activityIds)->delete();

            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."crm_activity_file AUTO_INCREMENT = 1");

            # 删除有关日志的活动记录
            Db::name('crm_activity')->where('activity_type', 8)->delete();

            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."crm_activity AUTO_INCREMENT = 1");

            # ------ 清除活动中有关审批的数据 END ------ #


            # ------ 清除日志操作记录表 START ------ #
            Db::name('admin_action_log')->where('module_name', 'oa')->where('controller_name', 'log')->delete();
            Db::query("ALTER TABLE ".$prefix."admin_action_log AUTO_INCREMENT = 1");
            # ------ 清除日志操作记录表 END ------ #


            # ------ 清除日志操作记录 START ------ #
            Db::name('admin_operation_log')->where('module', 'oa_log')->delete();
            Db::query("ALTER TABLE ".$prefix."admin_operation_log AUTO_INCREMENT = 1");
            # ------ 清除日志操作记录 END ------ #

            # ------ 删除消息数据 START ------ #
            Db::name('admin_comment')->where('type', 'oa_log')->delete();
            Db::name('admin_message')->where('module_name', 'oa')->where('controller_name', 'log')->delete();
            # ------ 删除消息数据 END ------ #


            # ------ 重置附件表自增ID START ------ #
            Db::query("ALTER TABLE ".$prefix."admin_file AUTO_INCREMENT = 1");
            # ------ 重置附件表自增ID END ------ #


            # ------ 删除附件 START ------ #
            if (!empty($files)) {
                foreach ($files AS $key => $value) {
                    unlink($value);
                }
            }
            # ------ 删除附件 START ------ #


            # 提交事务
            Db::commit();

            return true;
        } catch (Exception $e) {
            # 回滚事务
            Db::rollback();

            # 将状态设置为false，终止其他方法的操作
            $this->status = false;

            $this->log = '重置日志模块时出错！';

            return false;
        }
    }

    /**
     * 重置项目管理数据
     */
    private function resetProjectManagementData()
    {
        # 表前缀
        $prefix = config('database.prefix');

        # 文件数组
        $files = [];

        # 启动事务
        Db::startTrans();
        try {
            # ------ 清除项目管理数据 START ------ #

            # 获取项目ID
//            $workIds = Db::name('work')->column('work_id');

            # 获取任务ID
//            $taskIds = Db::name('task')->whereIn('work_id', $workIds)->column('task_id');
            $taskIds = Db::name('task')->column('task_id');

            # 获取关联附件ID
            $workTaskFileIds = Db::name('work_task_file')->whereIn('task_id', $taskIds)->column('file_id');

            # 查询项目附件数据
            $workTaskFileInfo = $this->getFileList($workTaskFileIds);

            # 合并附件数据
            $files = array_merge($files, $workTaskFileInfo);

            # 清除项目中的任务附件关联数据
            Db::name('work_task_file')->whereIn('task_id', $taskIds)->delete();
            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."work_task_file AUTO_INCREMENT = 1");

            # 清除标签数据
            Db::query("TRUNCATE TABLE ".$prefix."work_task_lable");

            # 清除任务日志
            Db::name('work_task_log')->whereIn('task_id', $taskIds)->delete();
            # 重置任务日志自增ID
            Db::query("ALTER TABLE ".$prefix."work_task_log AUTO_INCREMENT = 1");

            # 清除项目成员
            Db::query("TRUNCATE TABLE ".$prefix."work_user");

            # 清除任务分类
            Db::query("TRUNCATE TABLE ".$prefix."work_task_class");

            # 清除任务与客户模块的管理数据
            Db::query("TRUNCATE TABLE ".$prefix."work_relation");

            # 清除任务数据
            Db::query("TRUNCATE TABLE ".$prefix."task");
//            Db::name('task')->where('work_id', '<>', 0)->delete();
//            # 重置自增ID
//            Db::query("ALTER TABLE ".$prefix."task AUTO_INCREMENT = 1");

            # 清除项目排序数据
            Db::query("TRUNCATE TABLE ".$prefix."work_order");

            # 清除标签排序数据
            Db::query("TRUNCATE TABLE ".$prefix."work_lable_order");

            # 清除项目数据
            Db::query("TRUNCATE TABLE ".$prefix."work");

            # ------ 清除项目管理数据 END ------ #


            # ------ 清除活动中有关任务的数据 START ------ #

            # 获取有关任务的活动ID
            $activityIds = Db::name('crm_activity')->where('activity_type', 11)->whereIn('activity_type_id', $taskIds)->column('activity_id');

            # 获取有关任务的附件ID
            $activityFileIds = Db::name('crm_activity_file')->whereIn('activity_id', $activityIds)->column('file_id');

            # 查询任务附件数据
            $activityFileInfo = $this->getFileList($activityFileIds);

            # 合并附件数据
            $files = array_merge($files, $activityFileInfo);

            # 删除有关任务的附件关联记录
            Db::name('crm_activity_file')->whereIn('activity_id', $activityIds)->delete();

            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."crm_activity_file AUTO_INCREMENT = 1");

            # 删除有关任务的活动记录
            Db::name('crm_activity')->where('activity_type', 11)->whereIn('activity_type_id', $taskIds)->delete();

            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."crm_activity AUTO_INCREMENT = 1");

            # ------ 清除活动中有关任务的数据 END ------ #


            # ------ 清除有关项目的数据操作记录 START ------ #
            Db::name('admin_action_log')->where('module_name', 'work')->delete();
            Db::query("ALTER TABLE ".$prefix."admin_action_log AUTO_INCREMENT = 1");
            # ------ 清除有关项目的数据操作记录 END ------ #

            # ------ 清除项目操作记录 START ------ #
            Db::name('admin_operation_log')->where('module', 'work_task')->delete();
            Db::query("ALTER TABLE ".$prefix."admin_operation_log AUTO_INCREMENT = 1");
            # ------ 清除项目操作记录 END ------ #


            # ------ 清除评论和消息数据 START ------#
            Db::name('admin_comment')->where('type', 'task')->delete();
            Db::name('admin_message')->where('module_name', 'work')->delete();
            Db::name('admin_message')->where('module_name', 'oa')->where('controller_name', 'task')->delete();
            # ------ 清除评论和消息数据 END ------ #


            # 清除任务关联客户模块表并重置字段ID
            Db::query("TRUNCATE TABLE ".$prefix."task_relation");


            # ------ 重置附件表自增ID START ------ #
            Db::query("ALTER TABLE ".$prefix."admin_file AUTO_INCREMENT = 1");
            # ------ 重置附件表自增ID END ------ #


            # ------ 删除附件 START ------ #
            if (!empty($files)) {
                foreach ($files AS $key => $value) {
                    unlink($value);
                }
            }
            # ------ 删除附件 START ------ #


            # 提交事务
            Db::commit();

            return true;
        } catch (Exception $e) {
            # 回滚事务
            Db::rollback();

            # 将状态设置为false，终止其他方法的操作
            $this->status = false;

            $this->log = '重置项目管理模块时出错！';

            return false;
        }
    }

    /**
     * 重置日历数据
     */
    private function resetCalendarData()
    {
        # 表前缀
        $prefix = config('database.prefix');

        # 文件数组
        $files = [];

        # 启动事务
        Db::startTrans();
        try {
            # ------ 清除日历数据 START ------ #

            # 获取日历ID
            $eventIds = Db::name('oa_event')->column('event_id');

            # 删除日历关联的客户数据
            Db::query("TRUNCATE TABLE ".$prefix."oa_event_relation");

            # 删除日历的数据操作记录
            Db::name('admin_action_log')->where('module_name', 'oa')->where('controller_name', 'event')->delete();
            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."admin_action_log AUTO_INCREMENT = 1");

            # 删除日历的字段操作记录
            Db::name('admin_action_record')->where('types', 'oa_event')->delete();
            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."admin_action_record AUTO_INCREMENT = 1");

            # 删除日历提醒
            Db::query("TRUNCATE TABLE ".$prefix."oa_event_notice");

            # 删除日历数据
            Db::query("TRUNCATE TABLE ".$prefix."oa_event");

            # ------ 清除日历数据 END ------ #


            # ------ 删除活动数据 START ------ #

            # 获取活动ID
            $activityIds = Db::name('crm_activity')->where('activity_type', 10)->whereIn('activity_type_id', $eventIds)->column('activity_id');

            # 获取有关任务的附件ID
            $activityFileIds = Db::name('crm_activity_file')->whereIn('activity_id', $activityIds)->column('file_id');

            # 查询任务附件数据
            $activityFileInfo = $this->getFileList($activityFileIds);

            # 合并附件数据
            $files = array_merge($files, $activityFileInfo);

            # 删除有关任务的附件关联记录
            Db::name('crm_activity_file')->whereIn('activity_id', $activityIds)->delete();

            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."crm_activity_file AUTO_INCREMENT = 1");

            # 删除有关任务的活动记录
            Db::name('crm_activity')->where('activity_type', 10)->whereIn('activity_type_id', $eventIds)->delete();

            # 重置自增ID
            Db::query("ALTER TABLE ".$prefix."crm_activity AUTO_INCREMENT = 1");

            # ------ 删除活动数据 END ------ #

            # ------ 删除消息数据 START ------ #
            Db::name('admin_message')->where('module_name', 'oa')->where('controller_name', 'event')->delete();
            # ------ 删除消息数据 END ------ #

            # ------ 重置附件表自增ID START ------ #
            Db::query("ALTER TABLE ".$prefix."admin_file AUTO_INCREMENT = 1");
            # ------ 重置附件表自增ID END ------ #


            # ------ 删除附件 START ------ #
            if (!empty($files)) {
                foreach ($files AS $key => $value) {
                    unlink($value);
                }
            }
            # ------ 删除附件 START ------ #


            # 提交事务
            Db::commit();

            return true;
        } catch (Exception $e) {
            # 回滚事务
            Db::rollback();

            # 将状态设置为false，终止其他方法的操作
            $this->status = false;

            $this->log = '重置日历模块时出错！';

            return false;
        }
    }

    /**
     * 获取文件列表
     *
     * @param $fileIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getFileList($fileIds)
    {
        $result = [];

        $list = Db::name('admin_file')->field([
            'file_path',
            'file_path_thumb'
        ])->whereIn('file_id', $fileIds)->select();

        foreach ($list AS $key => $value) {
            if (!empty($value['file_path']))       $result[] = 'public/uploads/' . $value['file_path'];
            if (!empty($value['file_path_thumb'])) $result[] = 'public/uploads/' . $value['file_path_thumb'];
        }

        return $result;
    }
}