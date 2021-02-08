<?php
// +----------------------------------------------------------------------
// | Description: 基础框架路由配置文件
// +----------------------------------------------------------------------
// | Author: Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------

return [
    // 定义资源路由
    '__rest__' => [
        // 'crm/customer'           =>'crm/customer',
    ],

    // 【仪表盘】销售简报
    'crm/index/index' => ['crm/index/index', ['method' => 'POST']],
    'crm/index/indexList' => ['crm/index/indexList', ['method' => 'POST']],
    'crm/index/getRecordList' => ['crm/index/getRecordList', ['method' => 'POST']],

    // 【客户】列表
    'crm/customer/index' => ['crm/customer/index', ['method' => 'POST']],
    // 【客户】创建
    'crm/customer/save' => ['crm/customer/save', ['method' => 'POST']],
    // 【客户】编辑
    'crm/customer/update' => ['crm/customer/update', ['method' => 'POST']],
    // 【客户】详情
    'crm/customer/read' => ['crm/customer/read', ['method' => 'POST']],
    // 【客户】转移
    'crm/customer/transfer' => ['crm/customer/transfer', ['method' => 'POST']],
    // 【客户】放入公海
    'crm/customer/putInPool' => ['crm/customer/putInPool', ['method' => 'POST']],
    // 【客户】锁定
    'crm/customer/lock' => ['crm/customer/lock', ['method' => 'POST']],
    // 【客户】导出
    'crm/customer/excelExport' => ['crm/customer/excelExport', ['method' => 'POST']],
    // 【客户】导入模板下载
    'crm/customer/excelDownload' => ['crm/customer/excelDownload', ['method' => 'GET']],
    // 【客户】导入
    'crm/customer/excelImport' => ['crm/customer/excelImport', ['method' => 'POST']],
    // 【客户】删除
    'crm/customer/delete' => ['crm/customer/delete', ['method' => 'POST']],
    // 【客户】领取
    'crm/customer/receive' => ['crm/customer/receive', ['method' => 'POST']],
    // 【客户】分配
    'crm/customer/distribute' => ['crm/customer/distribute', ['method' => 'POST']],
    // 【客户】公海
    'crm/customer/pool' => ['crm/customer/pool', ['method' => 'POST']],
    // 【客户】公海领取
//    'crm/customer/receive' => ['crm/customer/receive', ['method' => 'POST']],
    // 【客户】设置关注
    'crm/customer/star' => ['crm/customer/star', ['method' => 'POST']],
    // 【客户】附近
    'crm/customer/nearby' => ['crm/customer/nearby', ['method' => 'POST']],
    // 【客户】系统信息
    'crm/customer/system' => ['crm/customer/system', ['method' => 'POST']],
    // 【客户】菜单数量
    'crm/customer/count' => ['crm/customer/count', ['method' => 'POST']],
    // 【客户】公海权限
    'crm/customer/poolAuthority' => ['crm/customer/poolAuthority', ['method' => 'POST']],

    // 【客户】回访
    'crm/visit/index' => ['crm/visit/index', ['method' => 'POST']],
    // 【客户】创建
    'crm/visit/save' => ['crm/visit/save', ['method' => 'POST']],
    // 【客户】编辑
    'crm/visit/update' => ['crm/visit/update', ['method' => 'POST']],
    // 【客户】详情
    'crm/visit/read' => ['crm/visit/read', ['method' => 'POST']],
    // 【客户】删除
    'crm/visit/delete' => ['crm/visit/delete', ['method' => 'POST']],
    // 【客户】当前账户信息
    'crm/visit/visitUser' => ['crm/visit/visitUser', ['method' => 'POST']],
    // 【回访】系统信息
    'crm/visit/system' => ['crm/visit/system', ['method' => 'POST']],
    // 【回访】菜单数量
    'crm/visit/count' => ['crm/visit/count', ['method' => 'POST']],

    // 【线索】列表
    'crm/leads/index' => ['crm/leads/index', ['method' => 'POST']],
    // 【线索】创建
    'crm/leads/save' => ['crm/leads/save', ['method' => 'POST']],
    // 【线索】编辑
    'crm/leads/update' => ['crm/leads/update', ['method' => 'POST']],
    // 【线索】详情
    'crm/leads/read' => ['crm/leads/read', ['method' => 'POST']],
    // 【线索】转移
    'crm/leads/transfer' => ['crm/leads/transfer', ['method' => 'POST']],
    // 【线索】转化
    'crm/leads/transform' => ['crm/leads/transform', ['method' => 'POST']],
    // 【线索】导出
    'crm/leads/excelExport' => ['crm/leads/excelExport', ['method' => 'POST']],
    // 【线索】导入模板下载
    'crm/leads/excelDownload' => ['crm/leads/excelDownload', ['method' => 'GET']],
    // 【线索】导入
    'crm/leads/excelImport' => ['crm/leads/excelImport', ['method' => 'POST']],
    // 【线索】删除
    'crm/leads/delete' => ['crm/leads/delete', ['method' => 'POST']],
    // 【线索】设置关注
    'crm/leads/star' => ['crm/leads/star', ['method' => 'POST']],
    // 【线索】系统信息
    'crm/leads/system' => ['crm/leads/system', ['method' => 'POST']],
    // 【线索】菜单数量
    'crm/leads/count' => ['crm/leads/count', ['method' => 'POST']],

    // 【联系人】列表
    'crm/contacts/index' => ['crm/contacts/index', ['method' => 'POST']],
    // 【联系人】创建
    'crm/contacts/save' => ['crm/contacts/save', ['method' => 'POST']],
    // 【联系人】编辑
    'crm/contacts/update' => ['crm/contacts/update', ['method' => 'POST']],
    // 【联系人】详情
    'crm/contacts/read' => ['crm/contacts/read', ['method' => 'POST']],
    // 【联系人】转移
    'crm/contacts/transfer' => ['crm/contacts/transfer', ['method' => 'POST']],
    // 【联系人】删除
    'crm/contacts/delete' => ['crm/contacts/delete', ['method' => 'POST']],
    // 【联系人】导出
    'crm/contacts/excelExport' => ['crm/contacts/excelExport', ['method' => 'POST']],
    // 【联系人】导入模板下载
    'crm/contacts/excelDownload' => ['crm/contacts/excelDownload', ['method' => 'GET']],
    // 【联系人】导入
    'crm/contacts/excelImport' => ['crm/contacts/excelImport', ['method' => 'POST']],
    // 【联系人】设置关注
    'crm/contacts/star' => ['crm/contacts/star', ['method' => 'POST']],
    // 【联系人】设置首要联系人
    'crm/contacts/setPrimary' => ['crm/contacts/setPrimary', ['method' => 'POST']],
    // 【联系人】获取联系人列表
    'crm/contacts/getContactsList' => ['crm/contacts/getContactsList', ['method' => 'POST']],
    // 【联系人】系统信息
    'crm/contacts/system' => ['crm/contacts/system', ['method' => 'POST']],
    // 【联系人】菜单数量
    'crm/contacts/count' => ['crm/contacts/count', ['method' => 'POST']],

    // 【商机】列表
    'crm/business/index' => ['crm/business/index', ['method' => 'POST']],
    // 【商机】创建
    'crm/business/save' => ['crm/business/save', ['method' => 'POST']],
    // 【商机】编辑
    'crm/business/update' => ['crm/business/update', ['method' => 'POST']],
    // 【商机】详情
    'crm/business/read' => ['crm/business/read', ['method' => 'POST']],
    // 【商机】状态组
    'crm/business/statusList' => ['crm/business/statusList', ['method' => 'POST']],
    // 【商机】转移
    'crm/business/transfer' => ['crm/business/transfer', ['method' => 'POST']],
    // 【商机】相关产品
    'crm/business/product' => ['crm/business/product', ['method' => 'POST']],
    // 【商机】商机状态推进
    'crm/business/advance' => ['crm/business/advance', ['method' => 'POST']],
    // 【商机】删除
    'crm/business/delete' => ['crm/business/delete', ['method' => 'POST']],
    // 【商机】导出
    'crm/business/excelExport' => ['crm/business/excelExport', ['method' => 'POST']],
    // 【商机】设置关注
    'crm/business/star' => ['crm/business/star', ['method' => 'POST']],
    // 【商机】系统设置
    'crm/business/system' => ['crm/business/system', ['method' => 'POST']],
    // 【商机】菜单数量
    'crm/business/count' => ['crm/business/count', ['method' => 'POST']],
    // 【商机】菜单数量
    'crm/business/setPrimary' => ['crm/business/setPrimary', ['method' => 'POST']],

    // 【合同】列表
    'crm/contract/index' => ['crm/contract/index', ['method' => 'POST']],
    // 【合同】创建
    'crm/contract/save' => ['crm/contract/save', ['method' => 'POST']],
    // 【合同】编辑
    'crm/contract/update' => ['crm/contract/update', ['method' => 'POST']],
    // 【合同】详情
    'crm/contract/read' => ['crm/contract/read', ['method' => 'POST']],
    // 【合同】转移
    'crm/contract/transfer' => ['crm/contract/transfer', ['method' => 'POST']],
    // 【合同】关联产品
    'crm/contract/product' => ['crm/contract/product', ['method' => 'POST']],
    // 【合同】审核
    'crm/contract/check' => ['crm/contract/check', ['method' => 'POST']],
    // 【合同】撤销审核
    'crm/contract/revokeCheck' => ['crm/contract/revokeCheck', ['method' => 'POST']],
    // 【合同】删除
    'crm/contract/delete' => ['crm/contract/delete', ['method' => 'POST']],
    // 【合同】导出
    'crm/contract/excelExport' => ['crm/contract/excelExport', ['method' => 'POST']],
    // 【合同】作废
    'crm/contract/cancel' => ['crm/contract/cancel', ['method' => 'POST']],
    // 【合同】系统信息
    'crm/contract/system' => ['crm/contract/system', ['method' => 'POST']],
    // 【合同】菜单数量
    'crm/contract/count' => ['crm/contract/count', ['method' => 'POST']],
    // 【合同】复制合同
    'crm/contract/copy' => ['crm/contract/copy', ['method' => 'POST']],

    // 【产品】列表
    'crm/product/index' => ['crm/product/index', ['method' => 'POST']],
    // 【产品】创建
    'crm/product/save' => ['crm/product/save', ['method' => 'POST']],
    // 【产品】编辑
    'crm/product/update' => ['crm/product/update', ['method' => 'POST']],
    // 【产品】详情
    'crm/product/read' => ['crm/product/read', ['method' => 'POST']],
    // 【产品】上架/下架
    'crm/product/status' => ['crm/product/status', ['method' => 'POST']],
    // 【产品】导出
    'crm/product/excelExport' => ['crm/product/excelExport', ['method' => 'POST']],
    // 【产品】导入模板下载
    'crm/product/excelDownload' => ['crm/product/excelDownload', ['method' => 'GET']],
    // 【产品】导入
    'crm/product/excelImport' => ['crm/product/excelImport', ['method' => 'POST']],
    // 【产品】删除
    'crm/product/delete' => ['crm/product/delete', ['method' => 'POST']],
    // 【产品】系统信息
    'crm/product/system' => ['crm/product/system', ['method' => 'POST']],
    // 【产品】菜单数量
    'crm/product/count' => ['crm/product/count', ['method' => 'POST']],
    // 【产品】转移
    'crm/product/transfer' => ['crm/product/transfer', ['method' => 'POST']],

    // 【回款】列表
    'crm/receivables/index' => ['crm/receivables/index', ['method' => 'POST']],
    // 【回款】创建
    'crm/receivables/save' => ['crm/receivables/save', ['method' => 'POST']],
    // 【回款】编辑
    'crm/receivables/update' => ['crm/receivables/update', ['method' => 'POST']],
    // 【回款】详情
    'crm/receivables/read' => ['crm/receivables/read', ['method' => 'POST']],
    // 【回款】删除
    'crm/receivables/delete' => ['crm/receivables/delete', ['method' => 'POST']],
    // 【回款】审核
    'crm/receivables/check' => ['crm/receivables/check', ['method' => 'POST']],
    // 【回款】撤销审核
    'crm/receivables/revokeCheck' => ['crm/receivables/revokeCheck', ['method' => 'POST']],
    // 【回款】转移
    'crm/receivables/transfer' => ['crm/receivables/transfer', ['method' => 'POST']],
    // 【回款】系统信息
    'crm/receivables/system' => ['crm/receivables/system', ['method' => 'POST']],
    // 【回款】菜单数量
    'crm/receivables/count' => ['crm/receivables/count', ['method' => 'POST']],

    // 【回款计划】列表
    'crm/receivables_plan/index' => ['crm/receivables_plan/index', ['method' => 'POST']],
    // 【回款计划】创建
    'crm/receivables_plan/save' => ['crm/receivables_plan/save', ['method' => 'POST']],
    // 【回款计划】编辑
    'crm/receivables_plan/update' => ['crm/receivables_plan/update', ['method' => 'POST']],
    // 【回款计划】删除
    'crm/receivables_plan/delete' => ['crm/receivables_plan/delete', ['method' => 'POST']],

    // 【发票】列表
    'crm/invoice/index' => ['crm/invoice/index', ['method' => 'POST']],
    // 【发票】创建
    'crm/invoice/save' => ['crm/invoice/save', ['method' => 'POST']],
    // 【发票】详情
    'crm/invoice/read' => ['crm/invoice/read', ['method' => 'POST']],
    // 【发票】编辑
    'crm/invoice/update' => ['crm/invoice/update', ['method' => 'POST']],
    // 【发票】删除
    'crm/invoice/delete' => ['crm/invoice/delete', ['method' => 'POST']],
    // 【发票】变更负责人
    'crm/invoice/transfer' => ['crm/invoice/transfer', ['method' => 'POST']],
    // 【发票】设置开票
    'crm/invoice/setInvoice' => ['crm/invoice/setInvoice', ['method' => 'POST']],
    // 【发票】审核
    'crm/invoice/check' => ['crm/invoice/check', ['method' => 'POST']],
    // 【发票】撤回审核
    'crm/invoice/revokeCheck' => ['crm/invoice/revokeCheck', ['method' => 'POST']],
    // 【发票】菜单数量
    'crm/invoice/count' => ['crm/invoice/count', ['method' => 'POST']],
    // 【发票】重置开票信息
    'crm/invoice/resetInvoiceStatus' => ['crm/invoice/resetInvoiceStatus', ['method' => 'POST']],

    // 【发票-开户行】列表
    'crm/invoiceInfo/index' => ['crm/invoiceInfo/index', ['method' => 'POST']],
    // 【发票-开户行】详情
    'crm/invoiceInfo/read' => ['crm/invoiceInfo/read', ['method' => 'POST']],
    // 【发票-开户行】创建
    'crm/invoiceInfo/save' => ['crm/invoiceInfo/save', ['method' => 'POST']],
    // 【发票-开户行】编辑
    'crm/invoiceInfo/update' => ['crm/invoiceInfo/update', ['method' => 'POST']],
    // 【发票-开户行】删除
    'crm/invoiceInfo/delete' => ['crm/invoiceInfo/delete', ['method' => 'POST']],


    // 【相关团队】列表
    'crm/setting/team' => ['crm/setting/team', ['method' => 'POST']],
    // 【相关团队】创建
    'crm/setting/teamSave' => ['crm/setting/teamSave', ['method' => 'POST']],
    // 【相关团队】退出
    'crm/setting/quitTeam' => ['crm/setting/quitTeam', ['method' => 'POST']],
    // 【客户保护规则】保存
    'crm/setting/config' => ['crm/setting/config', ['method' => 'POST']],
    // 【客户保护规则】详情
    'crm/setting/configData' => ['crm/setting/configData', ['method' => 'POST']],
    // 【合同到期提醒】
    'crm/setting/contractDay' => ['crm/setting/contractDay', ['method' => 'POST']],
    // 【设置回访提醒】
    'crm/setting/setVisitDay' => ['crm/setting/setVisitDay', ['method' => 'POST']],
    // 【获取回访提醒】
    'crm/setting/getVisitDay' => ['crm/setting/getVisitDay', ['method' => 'POST']],
    // 【设置自动编号】
    'crm/setting/setNumber' => ['crm/setting/setNumber', ['method' => 'POST']],


    // 【商机状态组】列表
    'crm/business_status/type' => ['crm/business_status/type', ['method' => 'POST']],
    // 【商机状态组】创建
    'crm/business_status/save' => ['crm/business_status/save', ['method' => 'POST']],
    // 【商机状态组】编辑
    'crm/business_status/update' => ['crm/business_status/update', ['method' => 'POST']],
    // 【商机状态组】详情
    'crm/business_status/read' => ['crm/business_status/read', ['method' => 'POST']],
    // 【商机状态组】停用
    'crm/business_status/enables' => ['crm/business_status/enables', ['method' => 'POST']],
    // 【商机状态组】删除
    'crm/business_status/delete' => ['crm/business_status/delete', ['method' => 'POST']],

    // 【产品分类】列表
    'crm/product_category/index' => ['crm/product_category/index', ['method' => 'POST']],
    // 【产品分类】创建
    'crm/product_category/save' => ['crm/product_category/save', ['method' => 'POST']],
    // 【产品分类】编辑
    'crm/product_category/update' => ['crm/product_category/update', ['method' => 'POST']],
    // 【产品分类】删除
    'crm/product_category/delete' => ['crm/product_category/delete', ['method' => 'POST']],

    // 【业绩目标】
    'crm/achievement/save' => ['crm/achievement/save', ['method' => 'POST']],
    'crm/achievement/update' => ['crm/achievement/update', ['method' => 'POST']],
    'crm/achievement/index' => ['crm/achievement/index', ['method' => 'POST']],
    'crm/achievement/delete' => ['crm/achievement/delete', ['method' => 'POST']],
    'crm/achievement/indexForuser' => ['crm/achievement/indexForuser', ['method' => 'POST']],

    // 【工作台】业绩指标
    'crm/index/achievementData' => ['crm/index/achievementData', ['method' => 'POST']],
    // 【工作台】销售漏斗
    'crm/index/funnel' => ['crm/index/funnel', ['method' => 'POST']],
    // 【工作台】销售趋势
    'crm/index/saletrend' => ['crm/index/saletrend', ['method' => 'POST']],
    // 【工作台】查重
    'crm/index/search' => ['crm/index/search', ['method' => 'POST']],
    // 【工作台】查重（新）
    'crm/index/queryRepeat' => ['crm/index/queryRepeat', ['method' => 'POST']],
    // 【工作台】遗忘提醒
    'crm/index/forgottenCustomerCount' => ['crm/index/forgottenCustomerCount', ['method' => 'POST']],
    // 【工作台】遗忘提醒列表
    'crm/index/forgottenCustomerPageList' => ['crm/index/forgottenCustomerPageList', ['method' => 'POST']],
    // 【工作台】排行榜
    'crm/index/ranking' => ['crm/index/ranking', ['method' => 'POST']],
    // 【工作台】数据汇总
    'crm/index/queryDataInfo' => ['crm/index/queryDataInfo', ['method' => 'POST']],
    // 【获取自动编号开启状态】
    'crm/index/autoNumberStatus' => ['crm/index/autoNumberStatus', ['method' => 'POST']],

    // 【获取仪表盘布局】
    'crm/index/dashboard' => ['crm/index/dashboard', ['method' => 'POST']],
    // 【修改仪表盘布局】
    'crm/index/updateDashboard' => ['crm/index/updateDashboard', ['method' => 'POST']],


    // 【待办事项】今日需联系
    'crm/message/todayLeads'            => ['crm/message/todayLeads', ['method' => 'POST']],
    'crm/message/todayCustomer'         => ['crm/message/todayCustomer', ['method' => 'POST']],
    'crm/message/todayBusiness'         => ['crm/message/todayBusiness', ['method' => 'POST']],
    'crm/message/num'                   => ['crm/message/num', ['method' => 'POST']],
    'crm/message/followLeads'           => ['crm/message/followLeads', ['method' => 'POST']],
    'crm/message/followCustomer'        => ['crm/message/followCustomer', ['method' => 'POST']],
    'crm/message/checkContract'         => ['crm/message/checkContract', ['method' => 'POST']],
    'crm/message/checkReceivables'      => ['crm/message/checkReceivables', ['method' => 'POST']],
    'crm/message/remindReceivablesPlan' => ['crm/message/remindReceivablesPlan', ['method' => 'POST']],
    'crm/message/endContract'           => ['crm/message/endContract', ['method' => 'POST']],
    'crm/message/remindCustomer'        => ['crm/message/remindCustomer', ['method' => 'POST']],
    'crm/message/checkInvoice'          => ['crm/message/checkInvoice', ['method' => 'POST']],
    'crm/message/visitContract'         => ['crm/message/visitContract', ['method' => 'POST']],
    'crm/message/allDeal'               => ['crm/message/allDeal', ['method' => 'POST']],

    // 【客户】标记跟进
    'crm/customer/setFollow' => ['crm/customer/setFollow', ['method' => 'POST']],
    // 【线索】标记跟进
    'crm/leads/setFollow' => ['crm/leads/setFollow', ['method' => 'POST']],

    // 【跟进记录类型设置】列表
    'crm/setting/recordList' => ['crm/setting/recordList', ['method' => 'POST']],
    // 【跟进记录类型设置】记录类型编辑
    'crm/setting/recordEdit' => ['crm/setting/recordEdit', ['method' => 'POST']],
    // 【联系人】联系人商机关联/取消关联
    'crm/contacts/relation' => ['crm/contacts/relation', ['method' => 'POST']],


    //【编号】列表
    'crm/setting/numberSequenceList' => ['crm/setting/numberSequenceList', ['method' => 'POST']],
    //【编号】添加
    'crm/setting/numberSequenceAdd' => ['crm/setting/numberSequenceAdd', ['method' => 'POST']],
    //【编号】修改
    'crm/setting/numberSequenceUpdate' => ['crm/setting/numberSequenceUpdate', ['method' => 'POST']],
    //【编号】删除
    'crm/setting/numberSequenceDel' => ['crm/setting/numberSequenceDel', ['method' => 'POST']],

    // 【公海】数据统计 导出
    'crm/customer/poolExcelExport' => ['crm/customer/poolExcelExport', ['method' => 'POST']],

    // 【CRM设置】拥有、锁定客户数限制列表
    'crm/setting/customerConfigList' => ['crm/setting/customerConfigList', ['method' => 'POST']],
    // 【CRM设置】拥有、锁定客户数限制创建
    'crm/setting/customerConfigSave' => ['crm/setting/customerConfigSave', ['method' => 'POST']],
    // 【CRM设置】拥有、锁定客户数限制编辑
    'crm/setting/customerConfigUpdate' => ['crm/setting/customerConfigUpdate', ['method' => 'POST']],
    // 【CRM设置】拥有、锁定客户数限制删除
    'crm/setting/customerConfigDel' => ['crm/setting/customerConfigDel', ['method' => 'POST']],

    // 【客户成交】
    'crm/customer/deal_status' => ['crm/customer/deal_status', ['method' => 'POST']],
    // 【待进入客户池】
    'crm/message/remindCustomer' => ['crm/message/remindCustomer', ['method' => 'POST']],

    // 【活动】列表
    'crm/activity/index' => ['crm/activity/index', ['method' => 'POST']],
    // 【活动】创建跟进记录
    'crm/activity/save' => ['crm/activity/save', ['method' => 'POST']],
    // 【活动】跟进记录详情
    'crm/activity/read' => ['crm/activity/read', ['method' => 'POST']],
    // 【活动】编辑跟进记录
    'crm/activity/update' => ['crm/activity/update', ['method' => 'POST']],
    // 【活动】删除跟进记录
    'crm/activity/delete' => ['crm/activity/delete', ['method' => 'POST']],
    // 【活动】获取常用语
    'crm/activity/getPhrase' => ['crm/activity/getPhrase', ['method' => 'POST']],
    // 【活动】设置常用语
    'crm/activity/setPhrase' => ['crm/activity/setPhrase', ['method' => 'POST']],
    // 【活动】获取跟进记录权限
    'crm/activity/getRecordAuth' => ['crm/activity/getRecordAuth', ['method' => 'POST']],
    // 【活动】获取跟进记录列表
    'crm/index/activityList' => ['crm/index/activityList', ['method' => 'POST']],

    // 【打印】获取打印数据
    'crm/printing/printingData' => ['crm/printing/printingData', ['method' => 'POST']],
    // 【打印】获取打印模板
    'crm/printing/template' => ['crm/printing/template', ['method' => 'POST']],
    // 【打印】创建打印记录
    'crm/printing/setRecord' => ['crm/printing/setRecord', ['method' => 'POST']],
    // 【打印】获取打印记录
    'crm/printing/getRecord' => ['crm/printing/getRecord', ['method' => 'POST']],

    // 【通用】快捷编辑
    'crm/common/quickEdit' => ['crm/common/quickEdit', ['method' => 'POST']],

    // MISS路由
    '__miss__' => 'admin/base/miss',
];
