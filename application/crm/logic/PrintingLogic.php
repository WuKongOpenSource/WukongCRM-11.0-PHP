<?php
/**
 * 打印逻辑类
 *
 * @author qifan
 * @date 2020-12-15
 */

namespace app\crm\logic;

use think\Db;

class PrintingLogic
{
    /**
     * 获取打印数据
     *
     * @param $type
     * @param $actionId
     * @param $templateId
     * @return array|string|string[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPrintingData($type, $actionId, $templateId)
    {
        $result  = [];

        $content = htmlspecialchars_decode(Db::name('admin_printing')->where('id', $templateId)->value('content'));
        $content = str_replace('\n', '', $content);
        $content = str_replace('\\', '', $content);

        # 商机模板
        if ($type == 1) $result = $this->getBusinessData($actionId, $content);

        # 合同模板
        if ($type == 2) $result = $this->getContractData($actionId, $content);

        # 回款模板
        if ($type == 3) $result = $this->getReceivablesData($actionId, $content);

        return $result;
    }

    /**
     * 获取打印模板列表
     *
     * @param $type
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTemplateList($type)
    {
        return Db::name('admin_printing')->field(['id AS template_id', 'name', 'type'])->where('type', $type)->select();
    }

    /**
     * 创建模板打印记录
     *
     * @param $userId
     * @param $param
     * @return int|string
     */
    public function setRecord($userId, $param)
    {
        $data = [
            'user_id'     => $userId,
            'type'        => $param['type'],
            'action_id'   => $param['action_id'],
            'template_id' => $param['template_id'],
            'create_time' => time(),
            'update_time' => time()
        ];

        return Db::name('crm_printing_record')->insert($data);
    }

    /**
     * 获取打印记录
     *
     * @param $param
     * @param $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecord($param, $userId)
    {
        $result = [];

        $limit = !empty($param['limit']) ? $param['limit'] : 15;
        $page  = !empty($param['page'])  ? $param['page']  : 1;

        $where['type']    = $param['type'];
        $where['user_id'] = $userId;

        $count = Db::name('crm_printing_record')->where($where)->count();
        $data  = Db::name('crm_printing_record')->where($where)->limit(($page - 1) * $limit, $limit)->select();

        foreach ($data AS $key => $value) {
            $templateName = Db::name('admin_printing')->where('id', $value['template_id'])->value('name');

            $result[] = [
                'type'          => $value['type'],
                'action_id'     => $value['action_id'],
                'template_id'   => $value['template_id'],
                'template_name' => $templateName,
                'create_time'   => date('Y-m-d H:i:s', $value['create_time'])
            ];
        }

        return ['list' => $result, 'count' => $count];
    }

    /**
     * 获取商机打印数据
     *
     * @param $id
     * @param $content
     * @return string|string[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getBusinessData($id, $content)
    {
        # 查询商机数据
        $businessData = Db::name('crm_business')->where('business_id', $id)->find();
        # 查询商机状态组
        $businessType = Db::name('crm_business_type')->where('type_id', $businessData['type_id'])->value('name');
        # 查询商机阶段
        $businessStatus = Db::name('crm_business_status')->where('status_id', $businessData['status_id'])->value('name');
        # 查询客户数据
        $customerData = Db::name('crm_customer')->where('customer_id', $businessData['customer_id'])->find();
        # 查询产品数据
        $businessProduct = Db::name('crm_business_product')->field(['product_id', 'price', 'sales_price', 'num', 'discount', 'subtotal'])->where('business_id', $businessData['business_id'])->select();
        $productIdArray = [];
        $productInfo    = [];
        foreach ($businessProduct AS $key => $value) {
            $productIdArray[] = $value['product_id'];
            $productInfo[$value['product_id']] = $value;
        }
        $productList = Db::name('crm_product')->whereIn('product_id', $productIdArray)->select();
        # 创建人
        $createUserName = Db::name('admin_user')->where('id', $businessData['create_user_id'])->value('realname');
        # 负责人
        $ownerUserName  = Db::name('admin_user')->where('id', $businessData['owner_user_id'])->value('realname');

        # 商机模板数据替换
        $content = str_replace('{商机名称}', $businessData['name'], $content);
        $content = str_replace('{商机状态组}', $businessType, $content);
        $content = str_replace('{商机阶段}', $businessStatus, $content);
        $content = str_replace('{商机金额}', $businessData['money'], $content);
        $content = str_replace('{预计成交日期}', $businessData['deal_date'], $content);
        $content = str_replace('{备注}', $businessData['remark'], $content);
        $content = str_replace('{负责人}', $createUserName, $content);
        $content = str_replace('{创建人}', $ownerUserName, $content);
        $content = str_replace('{创建日期}', date('Y-m-d H:i:s', $businessData['create_time']), $content);
        $content = str_replace('{更新日期}', date('Y-m-d H:i:s', $businessData['update_time']), $content);

        # 客户模板数据替换
        $content = str_replace('{客户名称}', $customerData['name'], $content);
        $content = str_replace('{客户级别}', $customerData['level'], $content);
        $content = str_replace('{客户行业}', $customerData['industry'], $content);
        $content = str_replace('{客户来源}', $businessStatus, $content);
        $content = str_replace('{成交状态}', $customerData['deal_status'], $content);
        $content = str_replace('{电话}', $customerData['telephone'], $content);
        $content = str_replace('{网址}', $customerData['website'], $content);
        $content = str_replace('{手机}', $customerData['mobile'], $content);

        # 产品模板数据替换
        preg_match_all('/(data-wk-table-tr-tag="value")>(.*)<\/tr>/mU', $content, $productHtml);
        if (!empty($productHtml[2])) {
            # 循环匹配到的HTML数据
            foreach ($productHtml[2] AS $key => $value) {
                # 循环产品数据
                $oldHtml     = $productHtml[0][$key]; # 保留旧HTML数据，用于str_str_replace函数查找替换
                $replaceHtml = '';
                foreach ($productList AS $k => $v) {
                    if ($k == 0) {
                        $replaceHtml .= 'data-wk-table-tr-tag="value">';
                    } else {
                        $replaceHtml .= '<tr data-wk-table-tr-tag="value">';
                    }
                    $value = str_replace('{产品名称}', $v['name'], $value);
                    $value = str_replace('{产品编码}', $v['num'], $value);
                    $value = str_replace('{单位}', $v['unit'], $value);
                    $value = str_replace('{标准价格}', $v['price'], $value);
                    $value = str_replace('{产品描述}', $v['description'], $value);
                    $value = str_replace('{售价}', $productInfo[$v['product_id']]['sales_price'], $value);
                    $value = str_replace('{数量}', $productInfo[$v['product_id']]['num'], $value);
                    $value = str_replace('{折扣}', $productInfo[$v['product_id']]['discount'], $value);
                    $value = str_replace('{整单折扣}', $businessData['discount_rate'], $value);
                    $value = str_replace('{合计}', $productInfo[$v['product_id']]['subtotal'], $value);

                    if (strstr($value, '{产品类别}')) {
                        $categoryNam = Db::name('crm_product_category')->where('category_id', $v['category_id'])->value('name');
                        $value = str_replace('{产品类别}', $categoryNam, $value);
                    }

                    $replaceHtml .= $value . '</tr>';
                }

                $content = str_replace($oldHtml, $replaceHtml, $content);
            }
        }

        # 替换整单折扣
        $content = str_replace('{整单折扣}', $businessData['discount_rate'], $content);

        return $content;
    }

    /**
     * 获取合同打印数据
     *
     * @param $id
     * @param $content
     * @return string|string[]|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getContractData($id, $content)
    {
        # 查询合同数据
        $contractData = Db::name('crm_contract')->where('contract_id', $id)->find();
        # 查询商机数据
        $businessData = Db::name('crm_business')->where('business_id', $contractData['business_id'])->find();
        # 查询客户数据
        $customerData = Db::name('crm_customer')->where('customer_id', $contractData['customer_id'])->find();
        # 查询联系人数据
        $contactsData = Db::name('crm_contacts')->where('contacts_id', $contractData['contacts_id'])->find();
        # 查询产品数据
        $businessProduct = Db::name('crm_business_product')->field(['product_id', 'price', 'sales_price', 'num', 'discount', 'subtotal'])->where('business_id', $contractData['business_id'])->select();
        $productIdArray = [];
        $productInfo    = [];
        foreach ($businessProduct AS $key => $value) {
            $productIdArray[] = $value['product_id'];
            $productInfo[$value['product_id']] = $value;
        }
        $productList = Db::name('crm_product')->whereIn('product_id', $productIdArray)->select();
        # 回款金额
        $receivablesModel = new \app\crm\model\Receivables();
        $moneyInfo        = $receivablesModel->getMoneyByContractId($contractData['contract_id']);
        $doneMoney        = $moneyInfo['doneMoney'] ? : 0.00;
        # 合同签约人
        $signerString = '';
        $signerArray = Db::name('admin_user')->field('realname')->whereIn('id', $contractData['order_user_id'])->select();
        foreach ($signerArray AS $key => $value) $signerString .= $value['realname'] . '、';
        # 创建人
        $createUserName = Db::name('admin_user')->where('id', $contractData['create_user_id'])->value('realname');
        # 负责人
        $ownerUserName  = Db::name('admin_user')->where('id', $contractData['owner_user_id'])->value('realname');

        # 客户模板数据替换
        $content = str_replace('{客户名称}', $customerData['name'], $content);
        $content = str_replace('{客户级别}', $customerData['level'], $content);
        $content = str_replace('{客户行业}', $customerData['industry'], $content);
        $content = str_replace('{客户来源}', $customerData['source'], $content);
        $content = str_replace('{成交状态}', $customerData['deal_status'], $content);
        $content = str_replace('{网址}', $customerData['website'], $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--customer" contenteditable="([true|false]*?)" data-wk-tag="customer.mobile">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--customer" contenteditable="true" data-wk-tag="customer.mobile">'.$customerData['mobile'].'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--customer" contenteditable="([true|false]*?)" data-wk-tag="customer.telephone">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--customer" contenteditable="true" data-wk-tag="customer.telephone">'.$customerData['telephone'].'</span>', $content);

        # 合同模板数据替换
        $content = str_replace('{合同编号}', $contractData['num'], $content);
        $content = str_replace('{合同名称}', $contractData['name'], $content);
        $content = str_replace('{客户名称}', $customerData['name'], $content);
        $content = str_replace('{商机名称}', $businessData['name'], $content);
        $content = str_replace('{下单时间}', $contractData['order_date'], $content);
        $content = str_replace('{合同金额}', $contractData['money'], $content);
        $content = str_replace('{合同开始时间}', $contractData['start_time'], $content);
        $content = str_replace('{合同到期时间}', $contractData['end_time'], $content);
        $content = str_replace('{客户签约人}', $contactsData['name'], $content);
        $content = str_replace('{公司签约人}', trim($signerString, '、'), $content);
        $content = str_replace('{负责人}', $createUserName, $content);
        $content = str_replace('{创建人}', $ownerUserName, $content);
        $content = str_replace('{创建日期}', date('Y-m-d H:i:s', $contractData['create_time']), $content);
        $content = str_replace('{更新日期}', date('Y-m-d H:i:s', $contractData['update_time']), $content);
        $content = str_replace('{已收款金额}', $doneMoney, $content);
        $content = str_replace('{未收款金额}', $contractData['money'] - $doneMoney, $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="([true|false]*?)" data-wk-tag="contract.remark">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="true" data-wk-tag="contract.remark">'.$contractData['remark'].'</span>', $content);

        # 联系人模板替换
        $content = str_replace('{姓名}', $contactsData['name'], $content);
        $content = str_replace('{客户名称}', $customerData['name'], $content);
        $content = str_replace('{电子邮箱}', $contactsData['email'], $content);
        $content = str_replace('{是否关键决策人}', $contactsData['decision'], $content);
        $content = str_replace('{职务}', $contactsData['post'], $content);
        $content = str_replace('{性别}', $contactsData['sex'], $content);
        $content = str_replace('{地址}', $contactsData['detail_address'], $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contacts" contenteditable="([true|false]*?)" data-wk-tag="contacts.mobile">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contacts" contenteditable="true" data-wk-tag="contacts.mobile">'.$contactsData['mobile'].'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contacts" contenteditable="([true|false]*?)" data-wk-tag="contacts.telephone">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contacts" contenteditable="true" data-wk-tag="contacts.telephone">'.$contactsData['telephone'].'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contacts" contenteditable="([true|false]*?)" data-wk-tag="contacts.remark">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contacts" contenteditable="true" data-wk-tag="contacts.remark">'.$contactsData['remark'].'</span>', $content);

        # 替换产品模板
        preg_match_all('/(data-wk-table-tr-tag="value")>(.*)<\/tr>/mU', $content, $productHtml);
        if (!empty($productHtml[2])) {
            # 循环匹配到的HTML数据
            foreach ($productHtml[2] AS $key => $value) {
                # 循环产品数据
                $oldHtml     = $productHtml[0][$key]; # 保留旧HTML数据，用于str_str_replace函数查找替换
                $replaceHtml = '';
                foreach ($productList AS $k => $v) {
                    if ($k == 0) {
                        $replaceHtml .= 'data-wk-table-tr-tag="value">';
                    } else {
                        $replaceHtml .= '<tr data-wk-table-tr-tag="value">';
                    }
                    $value = str_replace('{产品名称}', $v['name'], $value);
                    $value = str_replace('{产品编码}', $v['num'], $value);
                    $value = str_replace('{单位}', $v['unit'], $value);
                    $value = str_replace('{标准价格}', $v['price'], $value);
                    $value = str_replace('{产品描述}', $v['description'], $value);
                    $value = str_replace('{售价}', $productInfo[$v['product_id']]['sales_price'], $value);
                    $value = str_replace('{数量}', $productInfo[$v['product_id']]['num'], $value);
                    $value = str_replace('{折扣}', $productInfo[$v['product_id']]['discount'], $value);
                    $value = str_replace('{整单折扣}', $businessData['discount_rate'], $value);
                    $value = str_replace('{合计}', $productInfo[$v['product_id']]['subtotal'], $value);

                    if (strstr($value, '{产品类别}')) {
                        $categoryNam = Db::name('crm_product_category')->where('category_id', $v['category_id'])->value('name');
                        $value = str_replace('{产品类别}', $categoryNam, $value);
                    }

                    $replaceHtml .= $value . '</tr>';
                }

                $content = str_replace($oldHtml, $replaceHtml, $content);
            }
        }

        # 替换整单折扣
        $content = str_replace('{整单折扣}', $businessData['discount_rate'], $content);

        return $content;
    }

    /**
     * 获取回款打印数据
     *
     * @param $id
     * @param $content
     * @return string|string[]|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getReceivablesData($id, $content)
    {
        # 查询回款数据
        $receivablesData = Db::name('crm_receivables')->where('receivables_id', $id)->find();
        # 查询合同数据
        $contractData = Db::name('crm_contract')->where('contract_id', $receivablesData['contract_id'])->find();
        # 查询客户数据
        $customerName = Db::name('crm_customer')->where('customer_id', $receivablesData['customer_id'])->value('name');
        # 查询商机数据
        $businessName = Db::name('crm_business')->where('business_id', $contractData['business_id'])->find();
        # 查询联系人数据
        $contactsName = Db::name('crm_contacts')->where('contacts_id', $contractData['contacts_id'])->value('name');
        # 合同签约人
        $signerString = '';
        $signerArray = Db::name('admin_user')->field('realname')->whereIn('id', $contractData['order_user_id'])->select();
        foreach ($signerArray AS $key => $value) $signerString .= $value['realname'] . '、';
        # 合同创建人、负责人
        $contractCreate = Db::name('admin_user')->where('id', $contractData['create_user_id'])->value('realname');
        $contractOwner  = Db::name('admin_user')->where('id', $contractData['owner_user_id'])->value('realname');
        # 回款创建人、负责人
        $receivablesCreate = Db::name('admin_user')->where('id', $contractData['create_user_id'])->value('realname');
        $receivablesOwner  = Db::name('admin_user')->where('id', $contractData['owner_user_id'])->value('realname');
        # 回款金额
        $receivablesModel = new \app\crm\model\Receivables();
        $moneyInfo        = $receivablesModel->getMoneyByContractId($contractData['contract_id']);
        $doneMoney        = $moneyInfo['doneMoney'] ? : 0.00;

        # 合同模板数据替换
        $content = str_replace('{合同编号}', $contractData['num'], $content);
        $content = str_replace('{合同名称}', $contractData['name'], $content);
        $content = str_replace('{客户名称}', $customerName, $content);
        $content = str_replace('{商机名称}', $businessName, $content);
        $content = str_replace('{下单时间}', $contractData['order_date'], $content);
        $content = str_replace('{合同金额}', $contractData['money'], $content);
        $content = str_replace('{合同开始时间}', $contractData['start_time'], $content);
        $content = str_replace('{合同到期时间}', $contractData['end_time'], $content);
        $content = str_replace('{客户签约人}', $contactsName, $content);
        $content = str_replace('{公司签约人}', trim($signerString, '、'), $content);
        $content = str_replace('{已收款金额}', $doneMoney, $content);
        $content = str_replace('{未收款金额}', $contractData['money'] - $doneMoney, $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="([true|false]*?)" data-wk-tag="contract.create_user_id">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="true" data-wk-tag="contract.create_user_id">'.$contractCreate.'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="([true|false]*?)" data-wk-tag="contract.owner_user_id">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="true" data-wk-tag="contract.owner_user_id">'.$contractOwner.'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="([true|false]*?)" data-wk-tag="contract.create_time">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="true" data-wk-tag="contract.create_time">'.date('Y-m-d H:i:s', $contractData['create_time']).'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="([true|false]*?)" data-wk-tag="contract.update_time">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="true" data-wk-tag="contract.update_time">'.date('Y-m-d H:i:s', $contractData['update_time']).'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="([true|false]*?)" data-wk-tag="contract.remark">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="true" data-wk-tag="contract.remark">'.$contractData['remark'].'</span>', $content);

        # 回款模板数据替换
        $content = str_replace('{回款编号}', $receivablesData['number'], $content);
        $content = str_replace('{客户名称}', $customerName, $content);
        $content = str_replace('{合同编号}', $contractData['num'], $content);
        $content = str_replace('{回款日期}', $receivablesData['return_time'], $content);
        $content = str_replace('{回款方式}', $receivablesData['return_type'], $content);
        $content = str_replace('{回款金额}', $receivablesData['money'], $content);
        $content = str_replace('{期数}', '0期', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="([true|false]*?)" data-wk-tag="receivables.create_user_id">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="true" data-wk-tag="receivables.create_user_id">'.$receivablesCreate.'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="([true|false]*?)" data-wk-tag="receivables.owner_user_id">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="true" data-wk-tag="receivables.owner_user_id">'.$receivablesOwner.'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="([true|false]*?)" data-wk-tag="receivables.create_time">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="true" data-wk-tag="receivables.create_time">'.date('Y-m-d H:i:s', $receivablesData['create_time']).'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="([true|false]*?)" data-wk-tag="receivables.update_time">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="true" data-wk-tag="receivables.update_time">'.date('Y-m-d H:i:s', $receivablesData['update_time']).'</span>', $content);
        $content = preg_replace('/<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="([true|false]*?)" data-wk-tag="receivables.remark">(.*?)<\/span>/si', '<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="true" data-wk-tag="receivables.remark">'.$receivablesData['remark'].'</span>', $content);

        return $content;
    }
}