<?php
// +----------------------------------------------------------------------
// | Description: 基础框架路由配置文件
// +----------------------------------------------------------------------
// | Author: Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------

return [
    // 定义资源路由
    '__rest__'=>[
        // 'oa/log'		   =>'oa/log',
    ],
	// 【工作台】工作圈
	'oa/index/index' => ['oa/index/index', ['method' => 'POST']],
	//日程
	'oa/index/eventList' => ['oa/index/eventList', ['method' => 'POST']],
	//日程详情
	'oa/index/event' => ['oa/index/event', ['method' => 'POST']],
	//任务列表
	'oa/index/taskList' => ['oa/index/taskList', ['method' => 'POST']],
	//所有项目列表
	'oa/task/workList' => ['oa/task/workList', ['method' => 'POST']],
	//我下属的任务
	'oa/task/subTaskList' => ['oa/task/subTaskList', ['method' => 'POST']],
	//我的任务
	'oa/task/myTask' => ['oa/task/myTask', ['method' => 'POST']],
	//任务详情
	'oa/task/read' => ['oa/task/read', ['method' => 'POST']],
    //任务导出
    'oa/task/excelExport' => ['oa/task/excelExport', ['method' => 'POST']],
	//【标签】编辑
	'oa/tasklable/update' => ['oa/tasklable/update', ['method' => 'POST']],		
	//【标签】添加
	'oa/tasklable/save' => ['oa/tasklable/save', ['method' => 'POST']],		
	//【标签】删除
	'oa/tasklable/delete' => ['oa/tasklable/delete', ['method' => 'POST']],	
	//【标签】列表展示
	'oa/tasklable/index' => ['oa/tasklable/index', ['method' => 'POST']],	
	
	'oa/tasklable/groupList' => ['oa/tasklable/groupList', ['method' => 'POST']],	
	//【标签】标签获取任务列表
	'oa/tasklable/getWokList' => ['oa/tasklable/getWokList', ['method' => 'POST']],	
	
	//【任务】详情
	'oa/task/read'  => ['oa/task/read', ['method' => 'POST']],
	//【任务】删除
	'oa/task/delete'  => ['oa/task/delete', ['method' => 'POST']],
	//【任务】编辑
	'oa/task/update' => ['oa/task/update', ['method' => 'POST']],	
	//【任务】保存
	'oa/task/save' => ['oa/task/save', ['method' => 'POST']],	
	//【任务】编辑任务名
	'oa/task/updateName' => ['oa/task/updateName', ['method' => 'POST']],	
	//【任务】编辑标签
	'oa/task/updateLable' => ['oa/task/updateLable', ['method' => 'POST']],	
	//【任务】设置截止时间 
	'oa/task/updateStoptime' => ['oa/task/updateStoptime', ['method' => 'POST']],	
	//【任务】编辑参与人
	'oa/task/updateOwner' => ['oa/task/updateOwner', ['method' => 'POST']],	
	//【任务】单独删除参与部门
	'oa/task/delStruceureById' => ['oa/task/delStruceureById', ['method' => 'POST']],	
	//【任务】单独删除参与人
	'oa/task/delOwnerById' => ['oa/task/delOwnerById', ['method' => 'POST']],
	//【任务】编辑优先级
	'oa/task/updatePriority' => ['oa/task/updatePriority', ['method' => 'POST']],	
	//【任务】结束
	'oa/task/taskOver' => ['oa/task/taskOver', ['method' => 'POST']],	
	//【任务】操作记录
	'oa/task/readLoglist' => ['oa/task/readLoglist', ['method' => 'POST']],	
	//【任务】解除关联关系
	'oa/task/delrelation' => ['oa/task/delrelation', ['method' => 'POST']],	
	//任务评论添加
	'oa/taskcomment/save' => ['oa/taskcomment/save', ['method' => 'POST']],	
	'oa/taskcomment/delete' => ['oa/taskcomment/delete', ['method' => 'POST']],	
	
	//通讯录(废弃)
	//'oa/addresslist/index' => ['oa/addresslist/index', ['method' => 'POST']],

	// 【日程】列表
	'oa/event/index' => ['oa/event/index', ['method' => 'POST']],
	// 【日程】添加
	'oa/event/save' => ['oa/event/save', ['method' => 'POST']],
	// 【日程】编辑
	'oa/event/update' => ['oa/event/update', ['method' => 'POST']],	
	// 【日程】详情
	'oa/event/read' => ['oa/event/read', ['method' => 'POST']],		
	// 【日程】删除
	'oa/event/delete' => ['oa/event/delete', ['method' => 'POST']],
    // 【日程】自定义类型列表
    'oa/event/schedule' => ['oa/event/schedule', ['method' => 'POST']],
    // 【日程】系统类型展示数据
//    'oa/event/eventList' => ['oa/event/eventList', ['method' => 'POST']],
    //【日程】系统类型展示数据（任务/审批）
    'oa/event/eventTask' => ['oa/event/eventTask', ['method' => 'POST']],
    //【日程】系统类型展示数据（客户管理）
    'oa/event/eventCrm' => ['oa/event/eventCrm', ['method' => 'POST']],
    // 【日程】系统类型展示
    'oa/event/saveSchedule' => ['oa/event/saveSchedule', ['method' => 'POST']],
    // 【日程】日历上展示
    'oa/event/listStatus' => ['oa/event/listStatus', ['method' => 'POST']],
    // 【日程】日历上系统类型列表展示 需联系合同 、计划回款
    'oa/event/eventContract' => ['oa/event/eventContract', ['method' => 'POST']],
    // 【日程】日历上系统类型列表展示 需联系客户
    'oa/event/eventCustomer' => ['oa/event/eventCustomer', ['method' => 'POST']],
    // 【日程】日历上系统类型列表展示 需联系线索
    'oa/event/eventLeads' => ['oa/event/eventLeads', ['method' => 'POST']],
    // 【日程】日历上系统类型列表展示 需联系商机
    'oa/event/eventBusiness' => ['oa/event/eventBusiness', ['method' => 'POST']],
    // 【日程】日历上系统类型列表展示 预计成交商机
    'oa/event/eventDealBusiness' => ['oa/event/eventDealBusiness', ['method' => 'POST']],

    // 【公告】列表
	'oa/announcement/index' => ['oa/announcement/index', ['method' => 'POST']],
	// 【公告】添加
	'oa/announcement/save' => ['oa/announcement/save', ['method' => 'POST']],
	// 【公告】编辑
	'oa/announcement/update' => ['oa/announcement/update', ['method' => 'POST']],	
	// 【公告】详情
	'oa/announcement/read' => ['oa/announcement/read', ['method' => 'POST']],		
	// 【公告】详情
	'oa/announcement/delete' => ['oa/announcement/delete', ['method' => 'POST']],		
	
	// 【日志】添加
	'oa/log/save' => ['oa/log/save', ['method' => 'POST']],		
	// 【日志】编辑
	'oa/log/update' => ['oa/log/update', ['method' => 'POST']],		
	// 【日志】删除
	'oa/log/delete' => ['oa/log/delete', ['method' => 'POST']],		
	// 【日志】列表
	'oa/log/index' => ['oa/log/index', ['method' => 'POST']],		
	// 【日志】详情
	'oa/log/read' => ['oa/log/read', ['method' => 'POST']],	
	// 【日志】添加评论
	'oa/log/commentSave'=>['oa/log/commentSave', ['method' => 'POST']],
	// 【日志】删除评论
	'oa/log/commentDel'=>['oa/log/commentDel', ['method' => 'POST']],
	//标记已读
	'oa/log/setread' => ['oa/log/setread', ['method' => 'POST']],
    //日志导出
    'oa/log/excelExport' => ['oa/log/excelExport', ['method' => 'POST']],
    //今日新增客户
    'oa/log/newBulletin' => ['oa/log/newBulletin', ['method' => 'POST']],
    //跟进记录
    'oa/log/activity' => ['oa/log/activity', ['method' => 'POST']],
    //未完成日志
    'oa/log/inCompleteLog' => ['oa/log/inCompleteLog', ['method' => 'POST']],
    //已完成日志
    'oa/log/completeLog' => ['oa/log/completeLog', ['method' => 'POST']],
    //日报完成
    'oa/log/completeStats' => ['oa/log/completeStats', ['method' => 'POST']],
    //月报完成
    'oa/log/logBulletin' => ['oa/log/logBulletin', ['method' => 'POST']],
    //欢迎语
    'oa/log/logWelcomeSpeech' => ['oa/log/logWelcomeSpeech', ['method' => 'POST']],
    //日志回复列表
    'oa/log/commentList' => ['oa/log/commentList', ['method' => 'POST']],
    //日志销售简报跟进数量统计
    'oa/log/activityCount' => ['oa/log/activityCount', ['method' => 'POST']],
    //日志销售简报跟进详情
    'oa/log/activityList' => ['oa/log/activityList', ['method' => 'POST']],
    //日志详情
    'oa/log/queryLog' => ['oa/log/queryLog', ['method' => 'POST']],
    //日志销售简报
    'oa/log/oneBulletin' => ['oa/log/oneBulletin', ['method' => 'POST']],
    
    // 【审批】类型列表
	'oa/examine/category'=>['oa/examine/category', ['method' => 'POST']],
	// 【审批】类型列表（添加）
	'oa/examine/categoryList'=>['oa/examine/categoryList', ['method' => 'POST']],	
	// 【审批】类型创建
	'oa/examine/categorySave'=>['oa/examine/categorySave', ['method' => 'POST']],
	// 【审批】类型编辑
	'oa/examine/categoryUpdate'=>['oa/examine/categoryUpdate', ['method' => 'POST']],
	// 【审批】类型删除
	'oa/examine/categoryDelete'=>['oa/examine/categoryDelete', ['method' => 'POST']],	
	// 【审批】类型状态
	'oa/examine/categoryEnables'=>['oa/examine/categoryEnables', ['method' => 'POST']],	
	// 【审批】列表
	'oa/examine/index'=>['oa/examine/index', ['method' => 'POST']],	
	// 【审批】创建
	'oa/examine/save'=>['oa/examine/save', ['method' => 'POST']],	
	// 【审批】编辑
	'oa/examine/update'=>['oa/examine/update', ['method' => 'POST']],	
	// 【审批】详情
	'oa/examine/read'=>['oa/examine/read', ['method' => 'POST']],	
	// 【审批】删除
	'oa/examine/delete'=>['oa/examine/delete', ['method' => 'POST']],				
	// 【审批】审核
	'oa/examine/check'=>['oa/examine/check', ['method' => 'POST']],	
	// 【审批】撤销审核
	'oa/examine/revokeCheck'=>['oa/examine/revokeCheck', ['method' => 'POST']],
    // 【审批】导出
    'oa/examine/excelExport'=>['oa/examine/excelExport', ['method' => 'POST']],
    // 【审批】我的审批
    'oa/examine/myExamine'=>['oa/examine/myExamine', ['method' => 'POST']],
    // 【审批】审批排序
    'oa/examine/examineSort'=>['oa/examine/examineSort', ['method' => 'POST']],
    // 【审批】通讯录列表
    'oa/addresslist/queryList'=>['oa/addresslist/queryList', ['method' => 'POST']],
    // 【审批】关注通讯录列表
    'oa/addresslist/starList'=>['oa/addresslist/starList', ['method' => 'POST']],
    // 【审批】关注
    'oa/addresslist/userStar'=>['oa/addresslist/userStar', ['method' => 'POST']],

    // 【代办事项】办公
	'oa/message/num'=>['oa/message/num', ['method' => 'POST']],		
	
	// MISS路由
	'__miss__'  => 'admin/base/miss',
];