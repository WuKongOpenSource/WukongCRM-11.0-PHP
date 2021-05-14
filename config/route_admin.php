<?php
// +----------------------------------------------------------------------
// | Description: 基础框架路由配置文件
// +----------------------------------------------------------------------
// | Author: Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------

return [
    // 定义资源路由
    '__rest__'=>[
        // 'admin/structures'	   =>'admin/structures',
    ],
    'admin/install/index' => ['admin/install/index', ['method' => 'GET']],
    'admin/install/step1' => ['admin/install/step1', ['method' => 'GET']],
    'admin/install/step2' => ['admin/install/step2', ['method' => 'GET']],
    'admin/install/step3' => ['admin/install/step3', ['method' => 'GET']],
 	'admin/install/step4' => ['admin/install/step4', ['method' => 'POST|AJAX']],
    'admin/install/step5' => ['admin/install/step5', ['method' => 'GET']],
    'admin/install/step6' => ['admin/install/step6', ['method' => 'GET']],
 	'admin/install/progress' => ['admin/install/progress', ['method' => 'POST']],

    // 升级公告
    'admin/adminUser/readNotice' => ['admin/index/readNotice', ['method' => 'POST']],

    //子部门列表
    'admin/structures/subIndex' => ['admin/structures/subIndex', ['method' => 'POST']],
    'admin/structures/getSubUserByStructrue' => ['admin/structures/getSubUserByStructrue', ['method' => 'POST']],
	//权限数据返回
    'admin/index/authList' => ['admin/index/authList', ['method' => 'POST']],
    'admin/system/index' => ['admin/system/index', ['method' => 'POST']],
    'admin/system/save' => ['admin/system/save', ['method' => 'POST']],
    'admin/users/ceshi' => ['admin/users/ceshi', ['method' => 'POST']],
	// 【基础】登录
	'admin/base/login' => ['admin/base/login', ['method' => 'POST']],
	// 【基础】短信验证码登录
	'admin/base/smslogin' => ['admin/base/smslogin', ['method' => 'POST']],	
	// 【基础】记住登录
	'admin/base/relogin'	=> ['admin/base/relogin', ['method' => 'POST']],
	// 【基础】退出登录
	'admin/base/logout' => ['admin/base/logout', ['method' => 'POST']],
	// 【基础】获取验证码
	'admin/base/getVerify' => ['admin/base/getVerify', ['method' => 'GET']],
	// 保存系统配置
	'admin/systemConfigs' => ['admin/systemConfigs/save', ['method' => 'POST']],

	// 【角色】列表
	'admin/groups/index' => ['admin/groups/index', ['method' => 'POST']],
	// 【角色】分类列表
	'admin/groups/typeList' => ['admin/groups/typeList', ['method' => 'POST']],	
	// 【角色】添加
	'admin/groups/save' => ['admin/groups/save', ['method' => 'POST']],	
	// 【角色】编辑
	'admin/groups/update' => ['admin/groups/update', ['method' => 'POST']],	
	// 【角色】批量删除
	'admin/groups/delete' => ['admin/groups/delete', ['method' => 'POST']],
	// 【角色】批量启用/禁用
	'admin/groups/enables' => ['admin/groups/enables', ['method' => 'POST']],
	// 【角色】复制
	'admin/groups/copy' => ['admin/groups/copy', ['method' => 'POST']],
	// 【角色】获取权限规则
	'admin/rules/index' => ['admin/rules/index', ['method' => 'POST']],		
	// 【角色】新建权限规则
	'admin/rules/save' => ['admin/rules/save', ['method' => 'POST']],	
	// 【角色】编辑权限规则
	'admin/rules/update' => ['admin/rules/update', ['method' => 'POST']],	
	'admin/rules/getListBytypes' => ['admin/rules/getListBytypes', ['method' => 'POST']],		
	'admin/rules/getSublist' => ['admin/rules/getSublist', ['method' => 'POST']],
    // 【角色】字段授权
    'admin/fieldGrant/index' => ['admin/fieldGrant/index', ['method' => 'POST']],
    // 【角色】设置字段授权
    'admin/fieldGrant/update' => ['admin/fieldGrant/update', ['method' => 'POST']],

    //应用配置
	'admin/config_set/index' => ['admin/config_set/index', ['method' => 'POST']],		
	'admin/config_set/update' => ['admin/config_set/update', ['method' => 'POST']],

	// 【用户】列表
	'admin/users/index' => ['admin/users/index', ['method' => 'POST']],	
	'admin/users/read' => ['admin/users/read', ['method' => 'POST']],	
	// 【用户】创建
	'admin/users/save' => ['admin/users/save', ['method' => 'POST']],		
	// 【用户】编辑
	'admin/users/update' => ['admin/users/update', ['method' => 'POST']],
	// 【用户】状态
	'admin/users/enables' => ['admin/users/enables', ['method' => 'POST']],
	// 【用户】权限范围内的用户数组
	'admin/users/getUserList' => ['admin/users/getUserList', ['method' => 'POST']],	
	// 【用户】修改头像
	'admin/users/updateImg' => ['admin/users/updateImg', ['method' => 'POST']],	
	// 【用户】修改密码
	'admin/users/resetPassword' => ['admin/users/resetPassword', ['method' => 'POST']],	
	// 【角色】员工角色关系
	'admin/users/groups' => ['admin/users/groups', ['method' => 'POST']],	
	// 【角色】员工角色关系（删除）
	'admin/users/groupsDel' => ['admin/users/groupsDel', ['method' => 'POST']],
	// 【角色】部门员工混合数据
	'admin/users/structureUserList' => ['admin/users/structureUserList', ['method' => 'POST']],
	// 【角色】根据部门ID获取员工列表
	'admin/users/userListByStructId' => ['admin/users/userListByStructId', ['method' => 'POST']],
    // 【角色】复制角色员工
    'admin/users/copyRole' => ['admin/users/copyRole', ['method' => 'POST']],
	//人资员工导入
	'admin/users/tobeusers' => ['admin/users/tobeusers', ['method' => 'POST']],	
	//根据ID批量设置密码
	'admin/users/updatePwd' => ['admin/users/updatePwd', ['method' => 'POST']],	
	// 【员工】导入模板下载
	'admin/users/excelDownload' => ['admin/users/excelDownload', ['method' => 'GET']],	
	// 【员工】导入
	'admin/users/import' => ['admin/users/import', ['method' => 'POST']],
	// 【员工】批量设置直属上级
	'admin/users/setParent' => ['admin/users/setParent', ['method' => 'POST']],
    // 【员工】获取下属(全部层级)
    'admin/users/subordinate' => ['admin/users/subordinate', ['method' => 'POST']],
    // 【员工】重设部门
    'admin/users/setUserDept' => ['admin/users/setUserDept', ['method' => 'POST']],
    // 【员工】员工数量
    'admin/users/countNumOfUser' => ['admin/users/countNumOfUser', ['method' => 'POST']],

	// 【部门】列表
	'admin/structures/index' => ['admin/structures/index', ['method' => 'POST']],
	// 【部门】人资组织列表
	'admin/structures/indexForHrm' => ['admin/structures/indexForHrm', ['method' => 'POST']],
	// 【部门】添加
	'admin/structures/save' => ['admin/structures/save', ['method' => 'POST']],	
	// 【部门】编辑
	'admin/structures/update' => ['admin/structures/update', ['method' => 'POST']],	
	// 【部门】删除
	'admin/structures/delete' => ['admin/structures/delete', ['method' => 'POST']],
	// 【部门】批量启用/禁用
	'admin/structures/enables' => ['admin/structures/enables', ['method' => 'POST']],
	// 【部门】列表list
	'admin/structures/listDialog' => ['admin/structures/listDialog', ['method' => 'POST']],	

	// 【场景】列表
	'admin/scene/index' => ['admin/scene/index', ['method' => 'POST']],
	// 【场景】创建
	'admin/scene/save' => ['admin/scene/save', ['method' => 'POST']],	
	// 【场景】编辑
	'admin/scene/update' => ['admin/scene/update', ['method' => 'POST']],
	// 【场景】详情
	'admin/scene/read' => ['admin/scene/read', ['method' => 'POST']],	
	// 【场景】删除
	'admin/scene/delete' => ['admin/scene/delete', ['method' => 'POST']],
	// 【场景】排序
	'admin/scene/sort' => ['admin/scene/sort', ['method' => 'POST']],	
	// 【场景】默认	
	'admin/scene/defaults' => ['admin/scene/defaults', ['method' => 'POST']],		

	// 【其他】字段数据	
	'admin/index/fields' => ['admin/index/fields', ['method' => 'POST']],
	// 【其他】修改记录	
	'admin/index/fieldRecord' => ['admin/index/fieldRecord', ['method' => 'POST']],	

	// 【附件】上传	
	'admin/file/save' => ['admin/file/save', ['method' => 'POST']],	
	// 【附件】删除	
	'admin/file/delete' => ['admin/file/delete', ['method' => 'POST']],	
	// 【附件】查看
	'admin/file/read' => ['admin/file/read', ['method' => 'POST']],
	// 【附件】列表
	'admin/file/index' => ['admin/file/index', ['method' => 'POST']],		
	// 【附件】重命名
	'admin/file/update' => ['admin/file/update', ['method' => 'POST']],		
	// 【附件】下载
	'admin/file/download' => ['admin/file/download', ['method' => 'POST']],
    // 【附件】下载
    'admin/file/downloadImage' => ['admin/file/downloadImage', ['method' => 'POST']],
    // 【附件】全部删除(活动、产品)
    'admin/file/deleteAll' => ['admin/file/deleteAll', ['method' => 'POST']],

    // 【自定义字段】列表
	'admin/field/index' => ['admin/field/index', ['method' => 'POST']],	
	// 【自定义字段】数据
	'admin/field/read' => ['admin/field/read', ['method' => 'POST']],	
	// 【自定义字段】编辑
	'admin/field/update' => ['admin/field/update', ['method' => 'POST']],	
	// 【自定义字段】数据返回
	'admin/field/getField' => ['admin/field/getField', ['method' => 'POST']],
	// 【自定义字段】数据验重
	'admin/field/validates' => ['admin/field/validates', ['method' => 'POST']],
	// 【自定义字段】列表排序config
	'admin/field/config' => ['admin/field/config', ['method' => 'POST']],
	// 【自定义字段】列表宽度设置
	'admin/field/columnWidth' => ['admin/field/columnWidth', ['method' => 'POST']],		
	// 【自定义字段】列表排序数据
	'admin/field/configIndex' => ['admin/field/configIndex', ['method' => 'POST']],	
	// 【自定义字段】自定义验重字段
	'admin/field/uniqueField' => ['admin/field/uniqueField', ['method' => 'POST']],

    // 【自定义打印模板】字段
    'admin/printing/field' => ['admin/printing/field', ['method' => 'POST']],
    // 【自定义打印模板】列表
    'admin/printing/index' => ['admin/printing/index', ['method' => 'POST']],
    // 【自定义打印模板】创建
    'admin/printing/create' => ['admin/printing/create', ['method' => 'POST']],
    // 【自定义打印模板】读取
    'admin/printing/read' => ['admin/printing/read', ['method' => 'POST']],
    // 【自定义打印模板】修改
    'admin/printing/update' => ['admin/printing/update', ['method' => 'POST']],
    // 【自定义打印模板】删除
    'admin/printing/delete' => ['admin/printing/delete', ['method' => 'POST']],
    // 【自定义打印模板】复制
    'admin/printing/copy' => ['admin/printing/copy', ['method' => 'POST']],

    // 【站内信】列表
	'admin/message/index' => ['admin/message/index', ['method' => 'POST']],
	// 【站内信】未读数
	'admin/message/unreadCount' => ['admin/message/unreadCount', ['method' => 'POST']],
	// 【站内信】标记已读
	'admin/message/markedRead' => ['admin/message/markedRead', ['method' => 'POST']],

	// 【跟进记录】列表
	'admin/record/index' => ['admin/record/index', ['method' => 'POST']],
	// 【跟进记录】创建
	'admin/record/save' => ['admin/record/save', ['method' => 'POST']],	
	// 【跟进记录】删除
	'admin/record/delete' => ['admin/record/delete', ['method' => 'POST']],	

	// 【审批流程】列表
	'admin/examine_flow/index' => ['admin/examine_flow/index', ['method' => 'POST']],	
	// 【审批流程】创建
	'admin/examine_flow/save' => ['admin/examine_flow/save', ['method' => 'POST']],	
	// 【审批流程】编辑
	'admin/examine_flow/update' => ['admin/examine_flow/update', ['method' => 'POST']],
	// 【审批流程】状态
	'admin/examine_flow/enables' => ['admin/examine_flow/enables', ['method' => 'POST']],	
	// 【审批流程】状态
	'admin/examine_flow/delete' => ['admin/examine_flow/delete', ['method' => 'POST']],	
	// 【审批流程】审批步骤（固定）
	'admin/examine_flow/stepList' => ['admin/examine_flow/stepList', ['method' => 'POST']],	
	// 【审批流程】自选审批人列表
	'admin/examine_flow/userList' => ['admin/examine_flow/userList', ['method' => 'POST']],	
	// 【审批流程】审批记录
	'admin/examine_flow/recordList' => ['admin/examine_flow/recordList', ['method' => 'POST']],
    // 【审批流程】审批详情
    'admin/examine_flow/read' => ['admin/examine_flow/read', ['method' => 'POST']],

    // 【员工部门】员工账号编辑
	'admin/users/usernameEdit' => ['admin/users/usernameEdit', ['method' => 'POST']],					
	
	// 【员工】员工登录记录
	'admin/users/loginRecord' => ['admin/users/loginRecord', ['method' => 'POST']],
	
	// 【系统通知】列表
	'admin/index/message' => ['admin/index/message', ['method' => 'POST']],
	// 【系统通知】消息通知类型 废弃
	//'admin/index/messageTypeList' => ['admin/index/messageTypeList', ['method' => 'POST']],
	// 【系统通知】阅读消息 废弃
	//'admin/index/readMessage' => ['admin/index/readMessage', ['method' => 'POST']],

    // 【系统通知】消息列表
    'admin/message/messageList' => ['admin/message/messageList', ['method' => 'POST']],
    // 【系统通知】阅读消息
    'admin/message/updateMessage' => ['admin/message/updateMessage', ['method' => 'POST']],

    // 【系统通知】删除
    'admin/message/delete' => ['admin/message/delete', ['method' => 'POST']],
    // 【系统通知】批量更新
    'admin/message/readAllMessage' => ['admin/message/readAllMessage', ['method' => 'POST']],
    // 【系统通知】批量删除
    'admin/message/clear' => ['admin/message/clear', ['method' => 'POST']],


    // 【系统日志】数据操作日志
    'admin/log/dataRecord' => ['admin/log/dataRecord', ['method' => 'POST']],
    // 【系统日志】系统操作日志
    'admin/log/systemRecord' => ['admin/log/systemRecord', ['method' => 'POST']],
    // 【系统日志】登录日志
    'admin/log/loginRecord' => ['admin/log/loginRecord', ['method' => 'POST']],
    // 【系统日志】导出
    'admin/log/excelImport' => ['admin/log/excelImport', ['method' => 'POST']],

    // 【日志设置】欢迎语列表
    'admin/dailyRule/welcome' => ['admin/dailyRule/welcome', ['method' => 'POST']],
    // 【日志设置】设置欢迎语
    'admin/dailyRule/setWelcome' => ['admin/dailyRule/setWelcome', ['method' => 'POST']],
    // 【日志设置】日志规则
    'admin/dailyRule/workLogRule' => ['admin/dailyRule/workLogRule', ['method' => 'POST']],
    // 【日志设置】设置日志规则
    'admin/dailyRule/setWorkLogRule' => ['admin/dailyRule/setWorkLogRule', ['method' => 'POST']],

    //自定义日程 类型列表
    'admin/dailyRule/scheduleList' => ['admin/dailyRule/scheduleList', ['method' => 'POST']],
    //设置自定义日程类型列表
    'admin/dailyRule/setSchedule' => ['admin/dailyRule/setSchedule', ['method' => 'POST']],
    //添加自定义日程 类型列表
    'admin/dailyRule/addSchedule' => ['admin/dailyRule/addSchedule', ['method' => 'POST']],
    //删除自定义日程 类型列表
    'admin/dailyRule/delSchedule' => ['admin/dailyRule/delSchedule', ['method' => 'POST']],


    // 【项目管理】规则列表
    'admin/work/rules' => ['admin/work/rules', ['method' => 'POST']],
    // 【项目管理】角色列表
    'admin/work/roles' => ['admin/work/roles', ['method' => 'POST']],
    // 【项目管理】创建角色
    'admin/work/saveRole' => ['admin/work/saveRole', ['method' => 'POST']],
    // 【项目管理】角色详情
    'admin/work/readRole' => ['admin/work/readRole', ['method' => 'POST']],
    // 【项目管理】修改角色
    'admin/work/updateRole' => ['admin/work/updateRole', ['method' => 'POST']],
    // 【项目管理】删除角色
    'admin/work/deleteRole' => ['admin/work/deleteRole', ['method' => 'POST']],

    // 【通讯录】列表
    'admin/users/queryList' => ['admin/users/queryList', ['method' => 'POST']],
    // 【通讯录】加关注
    'admin/users/userStar' => ['admin/users/userStar', ['method' => 'POST']],
    // 【通讯录】关注列表
    'admin/users/starList' => ['admin/users/starList', ['method' => 'POST']],

    //顶部菜单排序展示
    'admin/index/sort' => ['admin/index/sort', ['method' => 'POST']],
    //顶部菜单排序
    'admin/index/updatesort' => ['admin/index/updatesort', ['method' => 'POST']],

    // 【初始化】列表
    'admin/initialize/index' => ['admin/initialize/index', ['method' => 'POST']],
    // 【初始化】初始化数据
    'admin/initialize/update' => ['admin/initialize/update', ['method' => 'POST']],
    // 【初始化】验证
    'admin/initialize/verification' => ['admin/initialize/verification', ['method' => 'POST']],

    //【导入】导入中
    'admin/index/importNum' => ['admin/index/importNum', ['method' => 'POST']],
    //【导入】导入结束
    'admin/index/importInfo' => ['admin/index/importInfo', ['method' => 'POST']],
    //【导入】导入历史列表
    'admin/index/importList' => ['admin/index/importList', ['method' => 'POST']],

    //【设置】公海配置列表
    'admin/setting/pool' => ['admin/setting/pool', ['method' => 'POST']],
    //【设置】添加公海配置
    'admin/setting/setPool' => ['admin/setting/setPool', ['method' => 'POST']],
    //【设置】公海配置详情
    'admin/setting/readPool' => ['admin/setting/readPool', ['method' => 'POST']],
    //【设置】变更公海配置状态
    'admin/setting/changePool' => ['admin/setting/changePool', ['method' => 'POST']],
    //【设置】删除公海配置
    'admin/setting/deletePool' => ['admin/setting/deletePool', ['method' => 'POST']],
    //【设置】转移公海客户
    'admin/setting/transferPool' => ['admin/setting/transferPool', ['method' => 'POST']],
    //【设置】客户级别
    'admin/setting/customerLevel' => ['admin/setting/customerLevel', ['method' => 'POST']],
    //【设置】公海字段
    'admin/setting/poolField' => ['admin/setting/poolField', ['method' => 'POST']],

	// MISS路由
	'__miss__'  => 'admin/base/miss',
];