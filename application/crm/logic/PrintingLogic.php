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
     * @param int $type 打印模板类型
     * @param int $actionId 操作ID（商机数据ID、合同数据ID、回款数据ID）
     * @param int $templateId 模板ID
     * @param int $recordId 记录ID
     * @author fanqi
     * @since 2021-03-29
     * @return array|string|string[]
     */
    public function getPrintingData($type, $actionId, $templateId, $recordId)
    {
        $result  = [];

        $content = Db::name('admin_printing')->where('id', $templateId)->value('content');
        $content = json_decode($content, true);
        $content = $content['data'];
        $content = str_replace('\n', '', $content);
        $content = str_replace('\\', '', $content);

        # 商机模板
        if ($type == 5) $result = $this->getBusinessData($actionId, $content);

        # 合同模板
        if ($type == 6) $result = $this->getContractData($actionId, $content);

        # 回款模板
        if ($type == 7) $result = $this->getReceivablesData($actionId, $content);

        # 打印记录
        if ($type == 20) $result = $this->getHistoryData($recordId);

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
            'content'     => json_encode(['data' => $param['recordContent']]),
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

        $where['action_id'] = $param['typeId'];
        $where['type']      = $param['crmType'];

        $count = Db::name('crm_printing_record')->where($where)->count();
        $data  = Db::name('crm_printing_record')->where($where)->limit(($page - 1) * $limit, $limit)->select();

        foreach ($data AS $key => $value) {
            $templateName = Db::name('admin_printing')->where('id', $value['template_id'])->value('name');

            $result[] = [
                'record_id'     => $value['printing_id'],
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
        # 清除无用数据
        $content = preg_replace("/[\t\n\r]+/", "", $content);
        
        # 查询商机数据
        $businessData = Db::name('crm_business')->where('business_id', $id)->find();
        # 查询商机状态组
        $businessType = Db::name('crm_business_type')->where('type_id', $businessData['type_id'])->value('name');
        # 查询商机阶段
        $businessStatus = Db::name('crm_business_status')->where('status_id', $businessData['status_id'])->value('name');
        # 查询客户数据
        $customerData = Db::name('crm_customer')->where('customer_id', $businessData['customer_id'])->find();
        # 查询产品数据
        $businessProduct = Db::name('crm_business_product')->where('business_id', $businessData['business_id'])->select();
        $productIdArray = [];
        foreach ($businessProduct AS $key => $value) {
            $productIdArray[] = $value['product_id'];
        }
        $productList = Db::name('crm_product')->whereIn('product_id', $productIdArray)->select();
        $productInfo = [];
        foreach ($productList AS $key => $value) {
            $productInfo[$value['product_id']] = $value;
        }
        # 产品类别数据
        $productCategoryList = db('crm_product_category')->select();
        $productCategoryData = [];
        foreach ($productCategoryList AS $key => $value) {
            $productCategoryData[$value['category_id']] = $value['name'];
        }
        # 创建人
        $createUserName = Db::name('admin_user')->where('id', $businessData['create_user_id'])->value('realname');
        # 负责人
        $ownerUserName  = Db::name('admin_user')->where('id', $businessData['owner_user_id'])->value('realname');
        # 产品自定义字段
        $productFields = db('admin_field')->field(['field', 'name', 'form_type'])->where('types', 'crm_product')->select();

        # 产品数据替换
        preg_match_all('/<tr data-wk-table-tr-tag="value">(.*)<\/tr>/mU', $content, $productHtml);
        if (!empty($productHtml[1])) {
            foreach ($productHtml[1] AS $k => $v) {
                if (empty($productHtml[0][$k])) continue;

                $oldHtml     = $productHtml[0][$k]; # 保留旧HTML数据，用于str_str_replace函数查找替换
                $replaceHtml = ''; # 要替换的HTML数据
                foreach ($businessProduct AS $key => $value) {
                    $replaceHtml .= '<tr data-wk-table-tr-tag="value">';
                    $detail = str_replace('{产品名称}', $productInfo[$value['product_id']]['name'], $productHtml[1][$k]);
                    $detail = str_replace('{产品编码}', $productInfo[$value['product_id']]['num'], $detail);
                    $detail = str_replace('{单位}', $value['unit'], $detail);
                    $detail = str_replace('{标准价格}', $value['price'], $detail);
                    $detail = str_replace('{产品描述}', $productInfo[$value['product_id']]['description'], $detail);
                    $detail = str_replace('{售价}', $value['sales_price'], $detail);
                    $detail = str_replace('{数量}', (int)$value['num'], $detail);
                    $detail = str_replace('{折扣}', (int)$value['discount'].'%', $detail);
                    $detail = str_replace('{合计}', $value['subtotal'], $detail);
                    foreach ($productFields AS $key1 => $value1) {
                        switch ($value1['form_type']) {
                            case 'user' :
                                $productUsers = db('admin_user')->whereIn('id', trim($productInfo[$value['product_id']][$value1['field']], ','))->column('realname');
                                $detail = str_replace('{'.$value1['name'].'}', implode(',', $productUsers), $detail);
                                break;
                            case 'structure' :
                                $productStructure = db('admin_structure')->whereIn('id', trim($productInfo[$value['product_id']][$value1['field']], ','))->column('name');
                                $detail = str_replace('{'.$value1['name'].'}', implode(',', $productStructure), $detail);
                                break;
                            case 'file' :
                                $productFiles = db('admin_file')->whereIn('file_id', trim($productInfo[$value['product_id']][$value1['field']], ','))->column('name');
                                $detail = str_replace('{'.$value1['name'].'}', implode(',', $productFiles), $detail);
                                break;
                            case 'datetime' :
                                $productDatetime = !empty($productInfo[$value['product_id']][$value1['field']]) ? date('Y-m-d H:i:s', $productInfo[$value['product_id']][$value1['field']]) : '';
                                $detail = str_replace('{'.$value1['name'].'}', $productDatetime, $detail);
                                break;
                            case 'category' :
                                $categoryId = $productInfo[$value['product_id']]['category_id'];
                                $detail = str_replace('{'.$value1['name'].'}', !empty($productCategoryData[$categoryId]) ? $productCategoryData[$categoryId] : '', $detail);
                                break;
                            default :
                                $detail = str_replace('{'.$value1['name'].'}', trim($productInfo[$value['product_id']][$value1['field']], ','), $detail);
                        }
                    }

                    if (strstr($detail, '{产品类别}')) {
                        $categoryNam = Db::name('crm_product_category')->where('category_id', $productInfo[$value['product_id']]['category_id'])->value('name');
                        $detail = str_replace('{产品类别}', $categoryNam, $detail);
                    }

                    $replaceHtml .= $detail . '</tr>';
                }

                $content = str_replace($oldHtml, $replaceHtml, $content);
            }
        }

        # 替换商机数据
        $content = str_replace('{商机状态组}', $businessType, $content);
        $content = str_replace('{商机阶段}', $businessStatus, $content);
        $content = str_replace('{负责人}', $createUserName, $content);
        $content = str_replace('{创建人}', $ownerUserName, $content);
        $content = str_replace('{创建日期}', date('Y-m-d H:i:s', $businessData['create_time']), $content);
        $content = str_replace('{更新日期}', date('Y-m-d H:i:s', $businessData['update_time']), $content);
        $businessFields = db('admin_field')->field(['field', 'form_type', 'name'])->where('types', 'crm_business')->select();
        foreach ($businessFields AS $key => $value) {
            preg_match_all('/<span class="wk-print-tag-wukong wk-tiny-color--business" contenteditable="([true|false]*)" data-wk-tag="business.'.$value['field'].'">(.*)<\/span>/mU', $content, $businessHtml);
            if (!empty($businessHtml[0])) {
                $businessSourceData = $businessHtml[0];
                switch ($value['form_type']) {
                    case 'user' :
                        $businessUsers = db('admin_user')->whereIn('id', trim($businessData[$value['field']], ','))->column('realname');
                        $businessTargetData = str_replace('{'.$value['name'].'}', implode(',', $businessUsers), $businessHtml[0]);
                        break;
                    case 'structure' :
                        $businessStructure = db('admin_structure')->whereIn('id', trim($businessData[$value['field']], ','))->column('name');
                        $businessTargetData = str_replace('{'.$value['name'].'}', implode(',', $businessStructure), $businessHtml[0]);
                        break;
                    case 'file' :
                        $businessFiles = db('admin_file')->whereIn('file_id', trim($businessData[$value['field']], ','))->column('name');
                        $businessTargetData = str_replace('{'.$value['name'].'}', implode(',', $businessFiles), $businessHtml[0]);
                        break;
                    case 'datetime' :
                        $businessDatetime = !empty($businessData[$value['field']]) ? date('Y-m-d H:i:s', $businessData[$value['field']]) : '';
                        $businessTargetData = str_replace('{'.$value['name'].'}', $businessDatetime, $businessHtml[0]);
                        break;
                    default :
                        $businessTargetData = str_replace('{'.$value['name'].'}', trim($businessData[$value['field']], ','), $businessHtml[0]);
                }
                $content = str_replace($businessSourceData, $businessTargetData, $content);
            }
        }

        # 替换客户数据
        $content = str_replace('{详细地址}', $customerData['detail_address'], $content);
        $content = str_replace('{区域}', $customerData['address'], $content);
        $customerFields = db('admin_field')->field(['field', 'form_type', 'name'])->where('types', 'crm_customer')->select();
        foreach ($customerFields AS $key => $value) {
            preg_match_all('/<span class="wk-print-tag-wukong wk-tiny-color--customer" contenteditable="([true|false]*)" data-wk-tag="customer.'.$value['field'].'">(.*)<\/span>/mU', $content, $customerHtml);
            if (!empty($customerHtml[0])) {
                $customerSourceData = $customerHtml[0];
                switch ($value['form_type']) {
                    case 'user' :
                        $customerUsers = db('admin_user')->whereIn('id', trim($customerData[$value['field']], ','))->column('realname');
                        $customerTargetData = str_replace('{'.$value['name'].'}', implode(',', $customerUsers), $customerHtml[0]);
                        break;
                    case 'structure' :
                        $customerStructure = db('admin_structure')->whereIn('id', trim($customerData[$value['field']], ','))->column('name');
                        $customerTargetData = str_replace('{'.$value['name'].'}', implode(',', $customerStructure), $customerHtml[0]);
                        break;
                    case 'file' :
                        $customerFiles = db('admin_file')->whereIn('file_id', trim($customerData[$value['field']], ','))->column('name');
                        $customerTargetData = str_replace('{'.$value['name'].'}', implode(',', $customerFiles), $customerHtml[0]);
                        break;
                    case 'datetime' :
                        $customerDatetime = !empty($customerData[$value['field']]) ? date('Y-m-d H:i:s', $customerData[$value['field']]) : '';
                        $customerTargetData = str_replace('{'.$value['name'].'}', $customerDatetime, $customerHtml[0]);
                        break;
                    default :
                        $customerTargetData = str_replace('{'.$value['name'].'}', trim($customerData[$value['field']], ','), $customerHtml[0]);
                }
                $content = str_replace($customerSourceData, $customerTargetData, $content);
            }
        }

        # 替换整单折扣
        $content = str_replace('{整单折扣}', (int)$businessData['discount_rate'].'%', $content);
        $content = str_replace('{产品总金额}', $businessData['money'], $content);

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
        # 清除无用数据
        $content = preg_replace("/[\t\n\r]+/", "", $content);

        # 查询合同数据
        $contractData = Db::name('crm_contract')->where('contract_id', $id)->find();
        # 查询商机数据
        $businessData = Db::name('crm_business')->where('business_id', $contractData['business_id'])->find();
        # 查询客户数据
        $customerData = Db::name('crm_customer')->where('customer_id', $contractData['customer_id'])->find();
        # 查询联系人数据
        $contactsData = Db::name('crm_contacts')->where('contacts_id', $contractData['contacts_id'])->find();
        # 查询产品数据
        $businessProduct = Db::name('crm_business_product')->field(['product_id', 'price', 'sales_price', 'num', 'discount', 'subtotal', 'unit'])->where('business_id', $contractData['business_id'])->select();
        $productIdArray = [];
        foreach ($businessProduct AS $key => $value) {
            $productIdArray[] = $value['product_id'];
        }
        $productList = Db::name('crm_product')->whereIn('product_id', $productIdArray)->select();
        $productInfo = [];
        foreach ($productList AS $key => $value) {
            $productInfo[$value['product_id']] = $value;
        }
        # 回款金额
        $receivablesModel = new \app\crm\model\Receivables();
        $moneyInfo        = $receivablesModel->getMoneyByContractId($contractData['contract_id']);
        $doneMoney        = !empty($moneyInfo['doneMoney']) ? $moneyInfo['doneMoney'] : 0.00;
        $unMoney          = $moneyInfo['contractMoney'] - $doneMoney > 0 ? $moneyInfo['contractMoney'] - $doneMoney : 0.00;
        # 合同签约人
        $signerString = '';
        $signerArray = Db::name('admin_user')->field('realname')->whereIn('id', $contractData['order_user_id'])->select();
        foreach ($signerArray AS $key => $value) $signerString .= $value['realname'] . '、';
        # 创建人
        $createUserName = Db::name('admin_user')->where('id', $contractData['create_user_id'])->value('realname');
        # 负责人
        $ownerUserName  = Db::name('admin_user')->where('id', $contractData['owner_user_id'])->value('realname');
        # 合同附件
        $fileList = Db::name('crm_contract_file')->alias('contract')->join('__ADMIN_FILE__ file', 'file.file_id = contract.file_id', 'left')
            ->where('contract.contract_id', $id)->column('file.file_path');
        $fileInfo = [];
        foreach ($fileList AS $key => $value) {
            $fileInfo[] = getFullPath($value['file_path']);
        }
        # 产品类别数据
        $productCategoryList = db('crm_product_category')->select();
        $productCategoryData = [];
        foreach ($productCategoryList AS $key => $value) {
            $productCategoryData[$value['category_id']] = $value['name'];
        }
        # 产品自定义字段
        $productFields = db('admin_field')->field(['field', 'name', 'form_type'])->where('types', 'crm_product')->select();

        # 产品模板数据替换
        preg_match_all('/<tr data-wk-table-tr-tag="value">(.*)<\/tr>/mU', $content, $productHtml);
        if (!empty($productHtml[1])) {
            foreach ($productHtml[1] AS $k => $v) {
                $oldHtml     = $productHtml[0][$k]; # 保留旧HTML数据，用于str_str_replace函数查找替换
                $replaceHtml = ''; # 要替换的HTML数据
                foreach ($businessProduct AS $key => $value) {
                    $replaceHtml .= '<tr data-wk-table-tr-tag="value">';
                    $detail = str_replace('{产品名称}', $productInfo[$value['product_id']]['name'], $productHtml[1][$k]);
                    $detail = str_replace('{产品编码}', $productInfo[$value['product_id']]['num'], $detail);
                    $detail = str_replace('{单位}', $value['unit'], $detail);
                    $detail = str_replace('{标准价格}', $value['price'], $detail);
                    $detail = str_replace('{产品描述}', $productInfo[$value['product_id']]['description'], $detail);
                    $detail = str_replace('{售价}', $value['sales_price'], $detail);
                    $detail = str_replace('{数量}', (int)$value['num'], $detail);
                    $detail = str_replace('{折扣}', (int)$value['discount'].'%', $detail);
                    $detail = str_replace('{合计}', $value['subtotal'], $detail);
                    foreach ($productFields AS $key1 => $value1) {
                        switch ($value1['form_type']) {
                            case 'user' :
                                $productUsers = db('admin_user')->whereIn('id', trim($productInfo[$value['product_id']][$value1['field']], ','))->column('realname');
                                $detail = str_replace('{'.$value1['name'].'}', implode(',', $productUsers), $detail);
                                break;
                            case 'structure' :
                                $productStructure = db('admin_structure')->whereIn('id', trim($productInfo[$value['product_id']][$value1['field']], ','))->column('name');
                                $detail = str_replace('{'.$value1['name'].'}', implode(',', $productStructure), $detail);
                                break;
                            case 'file' :
                                $productFiles = db('admin_file')->whereIn('file_id', trim($productInfo[$value['product_id']][$value1['field']], ','))->column('name');
                                $detail = str_replace('{'.$value1['name'].'}', implode(',', $productFiles), $detail);
                                break;
                            case 'datetime' :
                                $productDatetime = !empty($productInfo[$value['product_id']][$value1['field']]) ? date('Y-m-d H:i:s', $productInfo[$value['product_id']][$value1['field']]) : '';
                                $detail = str_replace('{'.$value1['name'].'}', $productDatetime, $detail);
                                break;
                            case 'category' :
                                $categoryId = $productInfo[$value['product_id']]['category_id'];
                                $detail = str_replace('{'.$value1['name'].'}', !empty($productCategoryData[$categoryId]) ? $productCategoryData[$categoryId] : '', $detail);
                                break;
                            default :
                                $detail = str_replace('{'.$value1['name'].'}', trim($productInfo[$value['product_id']][$value1['field']], ','), $detail);
                        }
                    }

                    if (strstr($detail, '{产品类别}')) {
                        $categoryNam = Db::name('crm_product_category')->where('category_id', $productInfo[$value['product_id']]['category_id'])->value('name');
                        $detail = str_replace('{产品类别}', $categoryNam, $detail);
                    }

                    $replaceHtml .= $detail . '</tr>';
                }
                $content = str_replace($oldHtml, $replaceHtml, $content);
            }
        }

        # 替换客户数据
        $content = str_replace('{详细地址}', $customerData['detail_address'], $content);
        $content = str_replace('{区域}', $customerData['address'], $content);
        $customerFields = db('admin_field')->field(['field', 'form_type', 'name'])->where('types', 'crm_customer')->select();
        foreach ($customerFields AS $key => $value) {
            preg_match_all('/<span class="wk-print-tag-wukong wk-tiny-color--customer" contenteditable="([true|false]*)" data-wk-tag="customer.'.$value['field'].'">(.*)<\/span>/mU', $content, $customerHtml);
            if (!empty($customerHtml[0])) {
                $customerSourceData = $customerHtml[0];
                switch ($value['form_type']) {
                    case 'user' :
                        $customerUsers = db('admin_user')->whereIn('id', trim($customerData[$value['field']], ','))->column('realname');
                        $customerTargetData = str_replace('{'.$value['name'].'}', implode(',', $customerUsers), $customerHtml[0]);
                        break;
                    case 'structure' :
                        $customerStructure = db('admin_structure')->whereIn('id', trim($customerData[$value['field']], ','))->column('name');
                        $customerTargetData = str_replace('{'.$value['name'].'}', implode(',', $customerStructure), $customerHtml[0]);
                        break;
                    case 'file' :
                        $customerFiles = db('admin_file')->whereIn('file_id', trim($customerData[$value['field']], ','))->column('name');
                        $customerTargetData = str_replace('{'.$value['name'].'}', implode(',', $customerFiles), $customerHtml[0]);
                        break;
                    case 'datetime' :
                        $customerDatetime = !empty($customerData[$value['field']]) ? date('Y-m-d H:i:s', $customerData[$value['field']]) : '';
                        $customerTargetData = str_replace('{'.$value['name'].'}', $customerDatetime, $customerHtml[0]);
                        break;
                    default :
                        $customerTargetData = str_replace('{'.$value['name'].'}', trim($customerData[$value['field']], ','), $customerHtml[0]);
                }
                $content = str_replace($customerSourceData, $customerTargetData, $content);
            }
        }

        # 替换合同数据
        $content = str_replace('{商机名称}', $businessData['name'], $content);
        $content = str_replace('{合同附件}', implode(' | ', $fileInfo), $content);
        $content = str_replace('{客户签约人}', $contactsData['name'], $content);
        $content = str_replace('{公司签约人}', trim($signerString, '、'), $content);
        $content = str_replace('{负责人}', $createUserName, $content);
        $content = str_replace('{创建人}', $ownerUserName, $content);
        $content = str_replace('{创建日期}', date('Y-m-d H:i:s', $contractData['create_time']), $content);
        $content = str_replace('{更新日期}', date('Y-m-d H:i:s', $contractData['update_time']), $content);
        $content = str_replace('{已收款金额}', $doneMoney, $content);
        $content = str_replace('{未收款金额}', $unMoney, $content);
        $contractFields = db('admin_field')->field(['field', 'form_type', 'name'])->where('types', 'crm_contract')->select();
        foreach ($contractFields AS $key => $value) {
            preg_match_all('/<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="([true|false]*)" data-wk-tag="contract.'.$value['field'].'">(.*)<\/span>/mU', $content, $contractHtml);
            if (!empty($contractHtml[0])) {
                $contractSourceData = $contractHtml[0];
                switch ($value['form_type']) {
                    case 'user' :
                        $contractUsers = db('admin_user')->whereIn('id', trim($contractData[$value['field']], ','))->column('realname');
                        $contractTargetData = str_replace('{'.$value['name'].'}', implode(',', $contractUsers), $contractHtml[0]);
                        break;
                    case 'structure' :
                        $contractStructure = db('admin_structure')->whereIn('id', trim($contractData[$value['field']], ','))->column('name');
                        $contractTargetData = str_replace('{'.$value['name'].'}', implode(',', $contractStructure), $contractHtml[0]);
                        break;
                    case 'file' :
                        $contractFiles = db('admin_file')->whereIn('file_id', trim($contractData[$value['field']], ','))->column('name');
                        $contractTargetData = str_replace('{'.$value['name'].'}', implode(',', $contractFiles), $contractHtml[0]);
                        break;
                    case 'datetime' :
                        $contractDatetime = !empty($contractData[$value['field']]) ? date('Y-m-d H:i:s', $contractData[$value['field']]) : '';
                        $contractTargetData = str_replace('{'.$value['name'].'}', $contractDatetime, $contractHtml[0]);
                        break;
                    default :
                        $contractTargetData = str_replace('{'.$value['name'].'}', trim($contractData[$value['field']], ','), $contractHtml[0]);
                }
                $content = str_replace($contractSourceData, $contractTargetData, $content);
            }
        }

        # 替换联系人数据
        $content = str_replace('{客户名称}', $customerData['name'], $content);
        $contactsFields = db('admin_field')->field(['field', 'form_type', 'name'])->where('types', 'crm_contacts')->select();
        foreach ($contactsFields AS $key => $value) {
            preg_match_all('/<span class="wk-print-tag-wukong wk-tiny-color--contacts" contenteditable="([true|false]*)" data-wk-tag="contacts.'.$value['field'].'">(.*)<\/span>/mU', $content, $contactsHtml);
            if (!empty($contactsHtml[0])) {
                $contactsSourceData = $contactsHtml[0];
                switch ($value['form_type']) {
                    case 'user' :
                        $contactsUsers = db('admin_user')->whereIn('id', trim($contactsData[$value['field']], ','))->column('realname');
                        $contactsTargetData = str_replace('{'.$value['name'].'}', implode(',', $contactsUsers), $contactsHtml[0]);
                        break;
                    case 'structure' :
                        $contactsStructure = db('admin_structure')->whereIn('id', trim($contactsData[$value['field']], ','))->column('name');
                        $contactsTargetData = str_replace('{'.$value['name'].'}', implode(',', $contactsStructure), $contactsHtml[0]);
                        break;
                    case 'file' :
                        $contactsFiles = db('admin_file')->whereIn('file_id', trim($contactsData[$value['field']], ','))->column('name');
                        $contactsTargetData = str_replace('{'.$value['name'].'}', implode(',', $contactsFiles), $contactsHtml[0]);
                        break;
                    case 'datetime' :
                        $contactsDatetime = !empty($contactsData[$value['field']]) ? date('Y-m-d H:i:s', $contactsData[$value['field']]) : '';
                        $contactsTargetData = str_replace('{'.$value['name'].'}', $contactsDatetime, $contactsHtml[0]);
                        break;
                    default :
                        $contactsTargetData = str_replace('{'.$value['name'].'}', trim($contactsData[$value['field']], ','), $contactsHtml[0]);
                }
            }
            $content = str_replace($contactsSourceData, $contactsTargetData, $content);
        }

        # 替换整单折扣
        $content = str_replace('{整单折扣}', $businessData['discount_rate'], $content);
        $content = str_replace('{产品总金额}', $businessData['money'], $content);

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
        $businessName = Db::name('crm_business')->where('business_id', $contractData['business_id'])->value('name');
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
        $receivablesCreate = Db::name('admin_user')->where('id', $receivablesData['create_user_id'])->value('realname');
        $receivablesOwner  = Db::name('admin_user')->where('id', $receivablesData['owner_user_id'])->value('realname');
        # 回款金额
        $receivablesModel = new \app\crm\model\Receivables();
        $moneyInfo        = $receivablesModel->getMoneyByContractId($contractData['contract_id']);
        $doneMoney        = $moneyInfo['doneMoney'] ? : 0.00;
        # 合同附件
        $fileList = Db::name('crm_contract_file')->alias('contract')->join('__ADMIN_FILE__ file', 'file.file_id = contract.file_id', 'left')
            ->where('contract.contract_id', $id)->column('file.file_path');
        $fileInfo = [];
        foreach ($fileList AS $key => $value) {
            $fileInfo[] = getFullPath($value['file_path']);
        }
        # 期数
        $plan = '';
        if (!empty($receivablesData['plan_id'])) {
            $plan = db('crm_receivables_plan')->where('plan_id', $receivablesData['plan_id'])->value('num');
        }

        # 替换合同数据
        $content = str_replace('{商机名称}', $businessName, $content);
        $content = str_replace('{客户签约人}', $contactsName, $content);
        $content = str_replace('{公司签约人}', trim($signerString, '、'), $content);
        $contractFields = db('admin_field')->field(['field', 'form_type', 'name'])->where('types', 'crm_contract')->select();
        foreach ($contractFields AS $key => $value) {
            preg_match_all('/<span class="wk-print-tag-wukong wk-tiny-color--contract" contenteditable="([true|false]*)" data-wk-tag="contract.'.$value['field'].'">(.*)<\/span>/mU', $content, $contractHtml);
            if (!empty($contractHtml[0])) {
                $contractSourceData = $contractHtml[0];
                switch ($value['form_type']) {
                    case 'user' :
                        $contractUsers = db('admin_user')->whereIn('id', trim($contractData[$value['field']], ','))->column('realname');
                        $contractTargetData = str_replace('{'.$value['name'].'}', implode(',', $contractUsers), $contractHtml[0]);
                        break;
                    case 'structure' :
                        $contractStructure = db('admin_structure')->whereIn('id', trim($contractData[$value['field']], ','))->column('name');
                        $contractTargetData = str_replace('{'.$value['name'].'}', implode(',', $contractStructure), $contractHtml[0]);
                        break;
                    case 'file' :
                        $contractFiles = db('admin_file')->whereIn('file_id', trim($contractData[$value['field']], ','))->column('name');
                        $contractTargetData = str_replace('{'.$value['name'].'}', implode(',', $contractFiles), $contractHtml[0]);
                        break;
                    case 'datetime' :
                        $contractDatetime = !empty($contractData[$value['field']]) ? date('Y-m-d H:i:s', $contractData[$value['field']]) : '';
                        $contractTargetData = str_replace('{'.$value['name'].'}', $contractDatetime, $contractHtml[0]);
                        break;
                    default :
                        $contractTargetData = str_replace('{'.$value['name'].'}', trim($contractData[$value['field']], ','), $contractHtml[0]);
                }
                $content = str_replace($contractSourceData, $contractTargetData, $content);
            }
        }

        # 替换回款数据
        $content = str_replace('{客户名称}', $customerName, $content);
        $content = str_replace('{合同编号}', $contractData['num'], $content);
        $content = str_replace('{创建人}', $receivablesCreate, $content);
        $content = str_replace('{负责人}', $receivablesOwner, $content);
        $content = str_replace('{创建日期}', date('Y-m-d H:i:s', $receivablesData['create_time']), $content);
        $content = str_replace('{更新日期}', date('Y-m-d H:i:s', $receivablesData['update_time']), $content);
        $receivablesFields = db('admin_field')->field(['field', 'form_type', 'name'])->where('types', 'crm_receivables')->select();
        foreach ($receivablesFields AS $key => $value) {
            preg_match_all('/<span class="wk-print-tag-wukong wk-tiny-color--receivables" contenteditable="([true|false]*)" data-wk-tag="receivables.'.$value['field'].'">(.*)<\/span>/mU', $content, $receivablesHtml);
            if (!empty($receivablesHtml[0])) {
                $receivablesSourceData = $receivablesHtml[0];
                switch ($value['form_type']) {
                    case 'user' :
                        $receivablesUsers = db('admin_user')->whereIn('id', trim($receivablesData[$value['field']], ','))->column('realname');
                        $receivablesTargetData = str_replace('{'.$value['name'].'}', implode(',', $receivablesUsers), $receivablesHtml[0]);
                        break;
                    case 'structure' :
                        $receivablesStructure = db('admin_structure')->whereIn('id', trim($receivablesData[$value['field']], ','))->column('name');
                        $receivablesTargetData = str_replace('{'.$value['name'].'}', implode(',', $receivablesStructure), $receivablesHtml[0]);
                        break;
                    case 'file' :
                        $receivablesFiles = db('admin_file')->whereIn('file_id', trim($receivablesData[$value['field']], ','))->column('name');
                        $receivablesTargetData = str_replace('{'.$value['name'].'}', implode(',', $receivablesFiles), $receivablesHtml[0]);
                        break;
                    case 'datetime' :
                        $receivablesDatetime = !empty($receivablesData[$value['field']]) ? date('Y-m-d H:i:s', $receivablesData[$value['field']]) : '';
                        $receivablesTargetData = str_replace('{'.$value['name'].'}', $receivablesDatetime, $receivablesHtml[0]);
                        break;
                    case 'receivables_plan' :
                        $receivablesTargetData = str_replace('{'.$value['name'].'}', $plan, $receivablesHtml[0]);
                        break;
                    default :
                        $receivablesTargetData = str_replace('{'.$value['name'].'}', trim($receivablesData[$value['field']], ','), $receivablesHtml[0]);
                }
                $content = str_replace($receivablesSourceData, $receivablesTargetData, $content);
            }
        }

        return $content;
    }

    /**
     * 保存预览数据
     *
     * @param $param user_id 用户id；type 类型（work，pdf）；content 打印内容；
     * @author fanqi
     * @date 2021-03-25
     * @return string
     */
    public function preview($param)
    {
        $userId  = $param['user_id'];
        $type    = $param['type'];
        $content = $param['content'];
        $key     = md5(md5($content).$type);

        $dataId = db('admin_printing_data')->where('key', $key)->value('data_id');

        if (empty($dataId)) {
            db('admin_printing_data')->insert([
                'key'         => $key,
                'content'     => json_encode(['data' => $content]),
                'user_id'     => $userId,
                'type'        => $type,
                'create_time' => time()
            ]);
        }

        return $key;
    }

    /**
     * 获取打印记录数据
     *
     * @param int $printingId 记录ID
     * @author fanqi
     * @since 2021-03-29
     * @return array
     */
    private function getHistoryData($printingId)
    {
        $data = db('crm_printing_record')->field(['type', 'content', 'template_id'])->where('printing_id', $printingId)->find();

        $contentArray = !empty($data['content']) ? json_decode($data['content'], true) : ['data' => ''];

        $result = [
            'type' => (int)$data['type'],
            'template_id' => (int)$data['template_id'],
            'content' => $contentArray['data']
        ];

        return $result;
    }
}