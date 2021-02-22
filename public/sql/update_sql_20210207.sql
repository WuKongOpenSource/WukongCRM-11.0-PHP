INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES (151, 0, '登录日志', 'loginRecord', 2, 105, 1),(152, 0, '查看', 'index', 3, 151, 1);
INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES (153, 2, '转移', 'transfer', 3, 50, 1);

CREATE TABLE `5kcrm_admin_system_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `client_ip` varchar(20) NOT NULL COMMENT '用户IP',
  `module_name` varchar(20) NOT NULL COMMENT '模块名',
  `controller_name` varchar(20) NOT NULL COMMENT '控制器',
  `action_name` varchar(20) NOT NULL COMMENT '方法',
  `action_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作ID',
  `target_name` varchar(50) NOT NULL COMMENT '被操作对象的名称',
  `action_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1为删除操作',
  `content` text NOT NULL COMMENT '内容',
  `create_time` int(10) NOT NULL COMMENT '时间',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='系统操作日志表';

ALTER TABLE `5kcrm_admin_action_log` ADD `target_name` VARCHAR(50) NULL DEFAULT NULL COMMENT '被操作对象的名称' AFTER `action_id`;
ALTER TABLE `5kcrm_admin_record`
CHANGE COLUMN `record_id` `activity_id`  int(11) NOT NULL AUTO_INCREMENT FIRST ;

ALTER TABLE `5kcrm_admin_record`
DROP INDEX `record_id` ,
ADD UNIQUE INDEX `activity_id` (`activity_id`) USING BTREE ;

ALTER TABLE `5kcrm_admin_record`
ADD COLUMN `type`  int(1) NULL DEFAULT 1 COMMENT '活动类型 1 跟进记录 2 创建记录 3 商机阶段变更 4 外勤签到' AFTER `activity_id`;

ALTER TABLE `5kcrm_admin_record`
MODIFY COLUMN `types`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '关联类型' AFTER `activity_id`;

ALTER TABLE `5kcrm_admin_record`
CHANGE COLUMN `types_id` `activity_type_id`  int(11) NOT NULL COMMENT '类型ID' AFTER `types`;

ALTER TABLE `5kcrm_admin_record`
ADD COLUMN `status`  int(2) NULL DEFAULT 1 COMMENT '0 删除 1 未删除' AFTER `type`;

ALTER TABLE `5kcrm_admin_record`
ADD COLUMN `lng`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '经度' AFTER `status`,
ADD COLUMN `lat`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '纬度' AFTER `lng`,
ADD COLUMN `address`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '签到地址' AFTER `lat`,
ADD COLUMN `customer_ids`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联客户' AFTER `address`,
ADD COLUMN `contract_ids`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联合同' AFTER `customer_ids`,
ADD COLUMN `leads_ids`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联线索' AFTER `contract_ids`;

ALTER TABLE `5kcrm_admin_record`
ADD COLUMN `activity_type`  int(1) NOT NULL COMMENT '活动类型 1 线索 2 客户 3 联系人 4 产品 5 商机 6 合同 7回款 8日志 9审批 10日程 11任务 12 发邮件' AFTER `leads_ids`;

ALTER TABLE `5kcrm_admin_record`
MODIFY COLUMN `next_time`  int(11) NULL DEFAULT 0 COMMENT '下次联系时间' AFTER `category`,
MODIFY COLUMN `business_ids`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '商机ID' AFTER `next_time`,
MODIFY COLUMN `contacts_ids`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '联系人ID' AFTER `business_ids`;

ALTER TABLE `5kcrm_admin_record`
ADD INDEX `create_time` (`create_time`) USING BTREE ;

rename table 5kcrm_admin_record to 5kcrm_crm_activity;

ALTER TABLE `5kcrm_admin_record_file`
CHANGE COLUMN `record_id` `activity_id`  int(11) NOT NULL COMMENT '活动ID' AFTER `r_id`;

rename table 5kcrm_admin_record_file to 5kcrm_crm_activity_file;

UPDATE `5kcrm_crm_activity` SET `activity_type`='1' WHERE (`types`='crm_leads');
UPDATE `5kcrm_crm_activity` SET `activity_type`='2' WHERE (`types`='crm_customer');
UPDATE `5kcrm_crm_activity` SET `activity_type`='3' WHERE (`types`='crm_contacts');
UPDATE `5kcrm_crm_activity` SET `activity_type`='4' WHERE (`types`='crm_product');
UPDATE `5kcrm_crm_activity` SET `activity_type`='5' WHERE (`types`='crm_business');
UPDATE `5kcrm_crm_activity` SET `activity_type`='6' WHERE (`types`='crm_contract');
UPDATE `5kcrm_crm_activity` SET `activity_type`='7' WHERE (`types`='crm_receivables');

CREATE TABLE `5kcrm_admin_field_grant` (
  `grant_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) NOT NULL COMMENT '角色ID',
  `module` varchar(32) NOT NULL COMMENT '模块：crm、oa、bi等',
  `column` varchar(32) NOT NULL COMMENT '栏目：leads、customer、contacts等',
  `content` text NOT NULL COMMENT '授权内容',
  `update_time` int(10) NOT NULL COMMENT '修改日期',
  `create_time` int(10) NOT NULL COMMENT '创建日期',
  PRIMARY KEY (`grant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='角色权限管理-字段授权';

INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`)  VALUES ('crm_business', '赢单商机', 0, 0, NULL, 0, 1, 'win_business', 1607072044, 1607072044);
INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`)  VALUES ('crm_business', '输单商机', 0, 0, NULL, 0, 1, 'fail_business', 1607072044, 1607072044);

CREATE TABLE `5kcrm_crm_star` (
  `star_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '员工ID',
  `target_id` int(10) NOT NULL COMMENT '目标ID：客户、商机、线索、联系人',
  `type` varchar(30) NOT NULL COMMENT '类型：crm_leads线索；crm_customer客户；crm_contacts联系人；crm_business商机;',
  PRIMARY KEY (`star_id`) USING BTREE,
  UNIQUE INDEX `user_target_type`(`user_id`, `target_id`, `type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '我关注的' ROW_FORMAT = Dynamic;

INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`) VALUES ('crm_leads', '我关注的线索', 0, 0, NULL, 0, 1, 'star', 1607158834, 1607158834);
INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`) VALUES ('crm_customer', '我关注的客户', 0, 0, NULL, 0, 1, 'star', 1607158834, 1607158834);
INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`) VALUES ('crm_contacts', '我关注的联系人', 0, 0, NULL, 0, 1, 'star', 1607158834, 1607158834);
INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`) VALUES ('crm_business', '我关注的商机', 0, 0, NULL, 0, 1, 'star', 1607158834, 1607158834);

DROP TABLE IF EXISTS `5kcrm_crm_invoice`;
CREATE TABLE `5kcrm_crm_invoice`  (
  `invoice_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_apple_number` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '发票编号',
  `customer_id` int(10) NOT NULL COMMENT '客户ID',
  `contract_id` int(10) NOT NULL COMMENT '合同ID',
  `invoice_money` float(10, 2) NOT NULL COMMENT '开票金额',
  `invoice_date` date NULL DEFAULT NULL COMMENT '开票日期',
  `invoice_type` tinyint(1) NOT NULL COMMENT '开票类型：1增值税专用发票；2增值税普通发票；3国税通用机打发票；4地税通用机打发票；5收据；',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '备注',
  `title_type` tinyint(1) NOT NULL COMMENT '抬头类型：1企业；2个人',
  `invoice_title` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '开票抬头',
  `tax_number` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '纳税人识别号',
  `deposit_account` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '开户账号',
  `deposit_address` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '开票地址',
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '电话',
  `contacts_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '联系人',
  `contacts_mobile` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '联系方式',
  `contacts_address` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '联系地址',
  `real_invoice_date` date NULL DEFAULT NULL COMMENT '实际开票日期',
  `invoice_number` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '发票号码',
  `logistics_number` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '物流单号',
  `create_user_id` int(10) UNSIGNED NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `check_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待审核；1审核中；2审核通过；3审核未通过；4撤回',
  `flow_id` int(10) NULL DEFAULT 0 COMMENT '审核流程ID',
  `order_id` int(10) NULL DEFAULT 0 COMMENT '审核步骤排序ID',
  `check_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '审批人IDs',
  `flow_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '流程审批人ID',
  `invoice_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发票状态：0未开票；1已开票',
  `update_time` int(10) NOT NULL COMMENT '修改日期',
  `create_time` int(10) NOT NULL COMMENT '创建日期',
  `deposit_bank` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '开户行',
  PRIMARY KEY (`invoice_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `5kcrm_crm_invoice_file`;
CREATE TABLE `5kcrm_crm_invoice_file`  (
  `r_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) UNSIGNED NOT NULL COMMENT '发票ID',
  `file_id` int(10) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '发票附件关联表' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `5kcrm_crm_invoice_info`;
CREATE TABLE `5kcrm_crm_invoice_info` (
  `info_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) NOT NULL COMMENT '客户ID',
  `title_type` tinyint(1) NULL DEFAULT NULL COMMENT '抬头类型：1企业；2个人',
  `invoice_title` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '开票抬头',
  `tax_number` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '纳税人识别号',
  `deposit_bank` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '开户行',
  `deposit_account` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '开户账号',
  `deposit_address` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '开票地址',
  `phone` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '编辑时间',
  PRIMARY KEY (`info_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '发票开户行信息' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `5kcrm_crm_printing_record`;
CREATE TABLE `5kcrm_crm_printing_record` (
  `printing_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `type` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块：1商机、2合同；3回款',
  `action_id` int(10) UNSIGNED NOT NULL COMMENT '操作ID',
  `template_id` int(10) UNSIGNED NOT NULL COMMENT '模板ID',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL COMMENT '编辑时间',
  PRIMARY KEY (`printing_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '打印记录' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `5kcrm_admin_printing`;
CREATE TABLE `5kcrm_admin_printing`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '预留字段：用户ID',
  `user_name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '显示字段：用户名称',
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '打印模板名称',
  `type` tinyint(1) NOT NULL COMMENT '打印类型：1商机；2合同；3回款',
  `content` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '打印内容',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '打印模板表' ROW_FORMAT = Dynamic;

ALTER TABLE `5kcrm_work` ADD COLUMN `group_id` tinyint(1) NOT NULL DEFAULT '0' COMMENT '角色组ID' AFTER `archive_time`;
ALTER TABLE `5kcrm_work` ADD COLUMN `cover_url` varchar(100) NOT NULL COMMENT '封面图片' AFTER `group_id`;
ALTER TABLE `5kcrm_work` ADD COLUMN `update_time` int(10) NOT NULL COMMENT '更新时间' AFTER `cover_url`;
ALTER TABLE `5kcrm_work` ADD COLUMN `is_system_cover` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '是否是系统封面：1是；0不是';
ALTER TABLE `5kcrm_crm_product` ADD COLUMN `cover_images` varchar(100)  COMMENT '角色组ID' AFTER `delete_time`;
ALTER TABLE `5kcrm_crm_product` ADD COLUMN `details_images` varchar(100) COMMENT '封面图片' AFTER `cover_images`;
ALTER TABLE `5kcrm_crm_contacts` ADD COLUMN `primary` tinyint(1) NOT NULL DEFAULT 0  COMMENT '是否是首要联系人：1是；0不是' AFTER `next_time`;
ALTER TABLE `5kcrm_crm_contacts` ADD COLUMN `last_time` int(10) unsigned DEFAULT NULL COMMENT '最后跟进时间';
ALTER TABLE `5kcrm_crm_contacts` ADD COLUMN `last_record` varchar(512) DEFAULT NULL COMMENT '最后跟进记录';
ALTER TABLE `5kcrm_crm_customer` ADD COLUMN `obtain_time` int(10) NOT NULL DEFAULT 0  COMMENT '负责人获取客户时间' AFTER `follow`;
ALTER TABLE `5kcrm_crm_customer` ADD COLUMN `last_time` int(10) unsigned DEFAULT NULL COMMENT '最后跟进时间';
ALTER TABLE `5kcrm_crm_customer` ADD COLUMN `last_record` varchar(512) DEFAULT NULL COMMENT '最后跟进记录';
ALTER TABLE `5kcrm_crm_customer` ADD COLUMN `email` varchar(64) DEFAULT NULL COMMENT '邮箱';
ALTER TABLE `5kcrm_crm_contract` ADD COLUMN `is_visit` tinyint(1) NOT NULL DEFAULT 0  COMMENT '是否已回访：1已回访；2未回访';
ALTER TABLE `5kcrm_crm_contract` ADD COLUMN `expire_remind` tinyint(1) NOT NULL DEFAULT 0  COMMENT '是否提醒合同到期：1提醒；0提醒';
ALTER TABLE `5kcrm_crm_contract` ADD COLUMN `last_time` int(10) unsigned DEFAULT NULL COMMENT '最后跟进时间';
ALTER TABLE `5kcrm_crm_contract` ADD COLUMN `last_record` varchar(512) DEFAULT NULL COMMENT '最后跟进记录';
ALTER TABLE `5kcrm_crm_business` ADD COLUMN `expire_remind` tinyint(1) NOT NULL DEFAULT 0  COMMENT '是否提醒合同到期：1提醒；0提醒';
ALTER TABLE `5kcrm_crm_business` ADD COLUMN `last_time` int(10) unsigned DEFAULT NULL COMMENT '最后跟进时间';
ALTER TABLE `5kcrm_crm_business` ADD COLUMN `last_record` varchar(512) DEFAULT NULL COMMENT '最后跟进记录';
ALTER TABLE `5kcrm_oa_event` ADD COLUMN `schedule_id` int(10) unsigned DEFAULT NULL COMMENT 'admin_oa_schedule表的主键ID';
ALTER TABLE `5kcrm_oa_event` drop COLUMN `type`;
ALTER TABLE `5kcrm_oa_event` drop COLUMN `remindtype`;
ALTER TABLE `5kcrm_oa_event` drop COLUMN `remark`;
ALTER TABLE `5kcrm_oa_event` drop COLUMN `color`;
ALTER TABLE `5kcrm_oa_event_notice` drop COLUMN `repeated`;
ALTER TABLE `5kcrm_oa_event_notice` MODIFY COLUMN `noticetype` tinyint(4) unsigned DEFAULT NULL COMMENT '1分 2时 3天';
ALTER TABLE `5kcrm_oa_event_notice` ADD COLUMN `number` tinyint(4) NOT NULL DEFAULT 0  COMMENT '根据noticetype来决定提前多久提醒';
ALTER TABLE `5kcrm_admin_field` ADD COLUMN `is_hidden` tinyint(1) NOT NULL DEFAULT 0  COMMENT '是否隐藏：1隐藏；0不隐藏';
INSERT INTO `5kcrm_admin_field` VALUES (NULL, 'crm_customer', 0, 'email', '邮箱', 'text', '', 0, 0, 0, '', '', 9, 1, 1553788800, 1611144298, 2, '', 0);
ALTER TABLE `5kcrm_crm_leads` ADD COLUMN `last_time` int(10) unsigned DEFAULT NULL COMMENT '最后跟进时间';
ALTER TABLE `5kcrm_crm_leads` ADD COLUMN `last_record` varchar(512) DEFAULT NULL COMMENT '最后跟进记录';
ALTER TABLE `5kcrm_admin_config` MODIFY COLUMN `controller` varchar(50) DEFAULT NULL COMMENT '控制器';
TRUNCATE TABLE `5kcrm_admin_config`;
INSERT INTO `5kcrm_admin_config` VALUES (1, '任务审批', 1, 'taskExamine', '', 1, 0);
INSERT INTO `5kcrm_admin_config` VALUES (2, '客户管理', 1, 'crm', '', 1, 0);
INSERT INTO `5kcrm_admin_config` VALUES (3, '项目管理', 1, 'work', '', 1, 0);
INSERT INTO `5kcrm_admin_config` VALUES (4, '人力资源管理', 1, 'hrm', '', 2, 0);
INSERT INTO `5kcrm_admin_config` VALUES (5, '进销存管理', 1, 'jxc', '', 2, 0);
INSERT INTO `5kcrm_admin_config` VALUES (6, '呼叫中心功能', 1, 'call', '', 3, 0);
INSERT INTO `5kcrm_admin_config` VALUES (7, '日志', 1, 'log', '', 1, 0);
INSERT INTO `5kcrm_admin_config` VALUES (8, '通讯录', 1, 'book', '', 1, 0);
INSERT INTO `5kcrm_admin_config` VALUES (9, '日历', 1, 'calendar', '', 1, 0);
INSERT INTO `5kcrm_admin_config` VALUES (10, '邮箱', 1, 'email', '', 2, 0);
INSERT INTO `5kcrm_admin_config` VALUES (11, '知识库', 1, 'knowledge', '', 2, 0);

DROP TABLE IF EXISTS `5kcrm_admin_field_grant`;
CREATE TABLE `5kcrm_admin_field_grant` (
  `grant_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int(10) NOT NULL COMMENT '角色ID',
  `module` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块：crm、oa、bi等',
  `column` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '栏目：leads、customer、contacts等',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '授权内容',
  `update_time` int(10) NOT NULL COMMENT '修改日期',
  `create_time` int(10) NOT NULL COMMENT '创建日期',
  PRIMARY KEY (`grant_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '角色权限管理-字段授权' ROW_FORMAT = Dynamic;

INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`) VALUES ('crm_visit', '全部回访', '0', '0', '', '0', '1', 'all', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`) VALUES ('crm_visit', '我负责的回访', '0', '0', '', '0', '1', 'me', '1546272000', '1551515457');
INSERT INTO `5kcrm_admin_scene` (`types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`) VALUES ('crm_visit', '下属负责的回访', '0', '1', '', '0', '1', 'sub', '1546272000', '1551515457');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ('crm_visit', '0', 'number', '回访编号', 'text', '', '0', '0', '1', '', NULL, '1', '1', '1553788800', '1553788800', '0', NULL, '0');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ('crm_visit', '0', 'visit_time', '回访时间', 'date', '', '0', '0', '1', '', '', '2', '1', '1553788800', '1553788800', '0', '', '0');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ('crm_visit', '0', 'visit_user_id', '回访人', 'user', '', '0', '0', '1', '', '', '3', '1', '1553788800', '1553788800', '0', '', '0');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ('crm_visit', '0', 'shape', '回访形式', 'select', '', '0', '0', '0', '', '见面拜访\r\n电话\r\n短信\r\n邮件\r\n微信', '4', '1', '1553788800', '1553788800', '0', NULL, '0');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ( 'crm_visit', '0', 'customer_id', '客户名称', 'customer', '', '0', '0', '1', '', '', '5', '1', '1553788800', '1553788800', '0', '', '0');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ( 'crm_visit', '0', 'contacts_id', '联系人', 'contacts', '', '0', '0', '0', '', '', '6', '1', '1553788800', '1553788800', '0', '', '0');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ( 'crm_visit', '0', 'contract_id', '合同编号', 'contract', '', '0', '0', '1', '', '', '7', '1', '1553788800', '1553788800', '0', '', '0');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ('crm_visit', '0', 'satisfaction', '客户满意度', 'select', '', '0', '0', '0', '', '很满意\r\n满意\r\n一般不满意\r\n很不满意', '8', '1', '1553788800', '1553788800', '0', NULL, '0');
INSERT INTO `5kcrm_admin_field` ( `types`, `types_id`, `field`, `name`, `form_type`, `default_value`, `max_length`, `is_unique`, `is_null`, `input_tips`, `setting`, `order_id`, `operating`, `create_time`, `update_time`, `type`, `relevant`, `is_hidden`) VALUES ( 'crm_visit', '0', 'feedback', '客户反馈', 'textarea', '', '0', '0', '0', '', '', '9', '1', '1553788800', '1553788800', '0', '', '0');

DROP TABLE IF EXISTS `5kcrm_crm_number_sequence`;
CREATE TABLE `5kcrm_crm_number_sequence` (
  `number_sequence_id` int(10) NOT NULL AUTO_INCREMENT,
  `sort` int(2) NOT NULL COMMENT '编号顺序',
  `type` int(2) NOT NULL COMMENT '编号类型 1文本 2日期 3数字',
  `value` varchar(255) NOT NULL COMMENT '文本内容或日期格式或起始编号',
  `increase_number` int(2) DEFAULT NULL COMMENT '递增数',
  `reset` int(10) DEFAULT '0' COMMENT '重置编号 0 从不，1 天，2 月， 3 年，',
  `last_number` int(10) DEFAULT NULL COMMENT '上次生成的编号',
  `last_date` int(11) DEFAULT NULL COMMENT '上次生成的时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `create_user_id` bigint(20) DEFAULT NULL COMMENT '创建人id',
  `company_id` bigint(20) DEFAULT NULL COMMENT '公司id',
  `status` int(2) NOT NULL DEFAULT '0' COMMENT '默认开启使用自动编号 1不使用',
  `number_type` int(11) DEFAULT NULL COMMENT '编号规则类型',
  PRIMARY KEY (`number_sequence_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='编号规则';

INSERT INTO `5kcrm_crm_number_sequence` VALUES ('1', '0', '1', 'HT', null, null, null, null, '1607356800', '1', null, '0', '1');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('2', '1', '2', 'yyyyMMdd', null, null, null, null, '1607356800', '1', null, '0', '1');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('3', '2', '3', '1', '1', '1', '43', '1612578239', '1607356800', '1', null, '0', '1');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('4', '1', '1', 'HK', null, null, null, null, '1611627355', '7', null, '0', '2');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('5', '1', '2', 'yyyyMMdd', null, null, null, null, '1611627355', '7', null, '0', '2');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('6', '1', '3', '1', '1', '4', '21', '1612578487', '1611627355', '7', null, '0', '2');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('7', '1', '1', 'HF', null, null, null, null, '1611627355', '7', null, '0', '3');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('8', '1', '2', 'yyyyMMdd', null, null, null, null, '1611627355', '7', null, '0', '3');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('9', '1', '3', '1', '1', '4', '13', '1612519628', '1611627355', '7', null, '0', '3');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('10', '1', '2', 'yyyyMMdd', null, null, null, null, '1612505697', '8', null, '0', '4');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('11', '1', '1', 'FP', null, null, null, null, '1612505750', '8', null, '0', '4');
INSERT INTO `5kcrm_crm_number_sequence` VALUES ('12', '2', '3', '1', '1', '4', '4', '1612581183', '1612505750', '8', null, '0', '4');

DROP TABLE IF EXISTS `5kcrm_crm_visit`;
CREATE TABLE `5kcrm_crm_visit` (
  `visit_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '回访id',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人',
  `visit_user_id` int(11) NOT NULL COMMENT '回访人',
  `create_user_id` int(11) NOT NULL COMMENT '创建人id',
  `customer_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户名称',
  `contract_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '合同编号',
  `contacts_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系人',
  `number` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '回访编号',
  `shape` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '回访形式',
  `status` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '处理状态',
  `satisfaction` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户满意度',
  `feedback` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '客户反馈',
  `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_state` int(2) NOT NULL DEFAULT 0 COMMENT '删除状态0 正常1回收站',
  `ro_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '读写权限',
  `visit_time` date NULL DEFAULT NULL COMMENT '回访时间',
  `num` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '合同编号',
  PRIMARY KEY (`visit_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `5kcrm_crm_visit_file`;
CREATE TABLE `5kcrm_crm_visit_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL COMMENT '回访ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='回访客户附件关系表';

DROP TABLE IF EXISTS `5kcrm_admin_oa_schedule`;
CREATE TABLE `5kcrm_admin_oa_schedule` (
  `schedule_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(500) NOT NULL COMMENT '日程类型',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `color` varchar(50) NOT NULL COMMENT '类型颜色',
  `type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '类型 1系统类型2 自定义类型',
  `is_select` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '显示状态：1显示，0不显示',
  PRIMARY KEY (`schedule_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='日程自定义类型';

INSERT INTO `5kcrm_admin_oa_schedule` VALUES (1, '分配的任务', '0', null, '1', '1', '1');
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (2, '需联系的客户', '0', null, '2', '1', '1');
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (3, '即将到期的合同', '0', null, '3', '1', '1');
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (4, '计划回款', '0', null, '4', '1', '1');
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (5, '需联系的线索', '0', null, '5', '1', '1');
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (6, '需联系的商机', '0', null, '6', '1', '1');
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (7, '预计成交的商机', '0', null, '7', '1', '1');

DROP TABLE IF EXISTS `5kcrm_admin_oa_schedule_relation`;
CREATE TABLE `5kcrm_admin_oa_schedule_relation` (
  `schedule_relation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL COMMENT '日程类型状态 0隐藏1 显示',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `schedule_id` int(10) NOT NULL DEFAULT '2' COMMENT '类型id',
  `user_id` int(10) NOT NULL COMMENT '负责人',
  PRIMARY KEY (`schedule_relation_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='日程自定义类型显示状态';

ALTER TABLE `5kcrm_oa_log` ADD COLUMN `is_relation` tinyint(2) NOT NULL DEFAULT 1 COMMENT '0不关联1关联';
ALTER TABLE `5kcrm_oa_log` ADD COLUMN `save_customer` int(10) NOT NULL DEFAULT 0 COMMENT '每日新增客户数量';
ALTER TABLE `5kcrm_oa_log` ADD COLUMN `save_business` int(10) NOT NULL DEFAULT 0 COMMENT '每日新增商机';
ALTER TABLE `5kcrm_oa_log` ADD COLUMN `save_contract` int(10) NOT NULL DEFAULT 0 COMMENT '每日新增合同';
ALTER TABLE `5kcrm_oa_log` ADD COLUMN `save_receivables` int(10) NOT NULL DEFAULT 0 COMMENT '每日新增回款';
ALTER TABLE `5kcrm_oa_log` ADD COLUMN `save_activity` int(10) NOT NULL DEFAULT 0 COMMENT '新增跟进记录';

DROP TABLE IF EXISTS `5kcrm_crm_dashboard`;
CREATE TABLE `5kcrm_crm_dashboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dashboard` text NOT NULL,
  `user_id` int(4) NOT NULL COMMENT '创建人 、修改人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='仪表盘样式';

DROP TABLE IF EXISTS `5kcrm_admin_sort`;
CREATE TABLE `5kcrm_admin_sort` (
  `sort_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `value` text NOT NULL COMMENT '排序内容',
  PRIMARY KEY (`sort_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='顶部导航栏';

ALTER TABLE `5kcrm_admin_message` ADD COLUMN `advance_time` varchar(255) DEFAULT NULL COMMENT '提前时间';
ALTER TABLE `5kcrm_admin_message` ADD COLUMN `is_delete` tinyint(4) not null default 1 comment '状态1未删除2已删除';
ALTER TABLE `5kcrm_oa_examine` ADD COLUMN `last_user_id` varchar(200) not null default '0' comment '上一审批人';
ALTER TABLE `5kcrm_oa_announcement` ADD COLUMN `is_read` tinyint(2) not null default 0  comment '1表示已读 0表示未读';
ALTER TABLE `5kcrm_oa_examine_category` ADD COLUMN `icon` varchar(255) NOT NULL  COMMENT '类型图标和颜色';

UPDATE `5kcrm_oa_examine_category` SET `category_id`='1', `title`='普通审批', `remark`='普通审批', `create_user_id`='1', `status`='1', `is_sys`='1', `user_ids`='', `structure_ids`='', `create_time`='1612576450', `update_time`='1612576450', `is_deleted`='0', `delete_time`='0', `delete_user_id`='0', `flow_id`='65' ,`icon`='wk wk-leave,#00CAAB' WHERE (`category_id`='1');
UPDATE `5kcrm_oa_examine_category` SET `category_id`='2', `title`='请假审批', `remark`='请假审批', `create_user_id`='1', `status`='1', `is_sys`='1', `user_ids`='', `structure_ids`='', `create_time`='1612518097', `update_time`='1612518097', `is_deleted`='0', `delete_time`='0', `delete_user_id`='0', `flow_id`='63' ,`icon`='wk wk-l-record,#3ABCFB' WHERE (`category_id`='2');
UPDATE `5kcrm_oa_examine_category` SET `category_id`='3', `title`='出差审批', `remark`='出差审批', `create_user_id`='1', `status`='1', `is_sys`='1', `user_ids`='', `structure_ids`='', `create_time`='1548911542', `update_time`='1548911542', `is_deleted`='0', `delete_time`='0', `delete_user_id`='0', `flow_id`='1' ,`icon`='wk wk-trip,#3ABCFB' WHERE (`category_id`='3');
UPDATE `5kcrm_oa_examine_category` SET `category_id`='4', `title`='加班审批', `remark`='加班审批', `create_user_id`='1', `status`='1', `is_sys`='1', `user_ids`='', `structure_ids`='', `create_time`='1548911542', `update_time`='1548911542', `is_deleted`='0', `delete_time`='0', `delete_user_id`='0', `flow_id`='1' ,`icon`='wk wk-overtime,#FAAD14' WHERE (`category_id`='4');
UPDATE `5kcrm_oa_examine_category` SET `category_id`='5', `title`='差旅报销', `remark`='差旅报销', `create_user_id`='1', `status`='1', `is_sys`='1', `user_ids`='', `structure_ids`='', `create_time`='1548911542', `update_time`='1548911542', `is_deleted`='0', `delete_time`='0', `delete_user_id`='0', `flow_id`='1' ,`icon`='wk wk-reimbursement,#3ABCFB' WHERE (`category_id`='5');
UPDATE `5kcrm_oa_examine_category` SET `category_id`='6', `title`='借款申请', `remark`='借款申请', `create_user_id`='1', `status`='1', `is_sys`='1', `user_ids`='', `structure_ids`='', `create_time`='1548911542', `update_time`='1548911542', `is_deleted`='0', `delete_time`='0', `delete_user_id`='0', `flow_id`='1' ,`icon`='wk wk-go-out,#FF6033' WHERE (`category_id`='6');

INSERT INTO `5kcrm_admin_rule` VALUES ('154', '0', '其他设置', 'other_rule', '2', '105', '0');
INSERT INTO `5kcrm_admin_rule` VALUES ('155', '0', '日志欢迎语', 'welcome', '3', '154', '0');
INSERT INTO `5kcrm_admin_rule` VALUES ('156', '0', '设置欢迎语', 'setWelcome', '3', '154', '0');
INSERT INTO `5kcrm_admin_rule` VALUES ('157', '0', '日志规则', 'workLogRule', '3', '154', '0');
INSERT INTO `5kcrm_admin_rule` VALUES ('158', '0', '设置日志规则', 'setWorkLogRule', '3', '154', '0');
INSERT INTO `5kcrm_admin_rule` VALUES ('159', '0', '自定义打印模板', 'printing', '3', '126', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('160', '2', '关注', 'star', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('161', '2', '关注', 'star', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('162', '2', '关注', 'star', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('163', '2', '关注', 'star', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('164', '2', '附近客户', 'nearby', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('165', '2', '发票管理', 'invoice', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('166', '2', '列表', 'index', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('167', '2', '创建', 'save', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('168', '2', '详情', 'read', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('169', '2', '编辑', 'update', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('170', '2', '删除', 'delete', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('171', '2', '转移', 'transfer', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('172', '2', '开票', 'setInvoice', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('173', '2', '重置开票状态', 'resetInvoiceStatus', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('174', '2', '跟进记录', 'activity', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('175', '2', '列表', 'index', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('176', '2', '详情', 'read', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('177', '2', '创建', 'save', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('178', '2', '编辑', 'update', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('179', '2', '删除', 'delete', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('180', '3', '项目设置', 'setWork', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('181', '3', '项目导出', 'excelExport', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('182', '3', '新建任务列表', 'saveTaskClass', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('183', '3', '编辑任务列表', 'updateTaskClass', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('184', '3', '移动任务列表', 'updateClassOrder', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('185', '3', '删除任务列表', 'deleteTaskClass', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('186', '3', '新建任务', 'saveTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('187', '3', '完成任务', 'setTaskStatus', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('188', '3', '编辑任务标题', 'setTaskTitle', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('189', '3', '编辑任务描述', 'setTaskDescription', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('190', '3', '分配任务', 'setTaskMainUser', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('191', '3', '设置任务时间', 'setTaskTime', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('192', '3', '设置任务标签', 'setTaskLabel', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('193', '3', '添加任务参与人', 'setTaskOwnerUser', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('194', '3', '设置任务优先级', 'setTaskPriority', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('195', '3', '移动任务', 'setTaskOrder', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('196', '3', '归档任务', 'archiveTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('197', '3', '删除任务', 'deleteTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('198', '3', '彻底删除任务', 'cleanTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('199', '3', '任务添加附件', 'uploadTaskFile', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('200', '3', '任务删除附件', 'deleteTaskFile', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('201', '3', '项目导入', 'excelImport', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('202', '3', '新建子任务', 'addChildTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('203', '3', '编辑子任务', 'updateChildTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('204', '3', '删除子任务', 'deleteChildTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('205', '3', '恢复任务', 'restoreTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('206', '3', '关联业务', 'saveTaskRelation', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('207', '3', '完成子任务', 'setChildTaskStatus', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('208', '0', '初始化', 'initialize', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('209', '0', '初始化数据', 'update', 3, 208, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('210', '2', '打印', 'print', 3, 34, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('211', '2', '打印', 'print', 3, 42, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('212', '2', '打印', 'print', 3, 50, 0);
INSERT INTO `5kcrm_admin_rule` VALUES ('213', '2', '导出', 'excelexport', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('214', '2', '转移', 'transfer', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES ('215', '2', '回访管理', 'visit', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('216', '2', '新建', 'save', '3', '215', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('217', '2', '编辑', 'update', '3', '215', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('218', '2', '查看列表', 'index', '3', '215', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('219', '2', '查看详情', 'read', '3', '215', '1');

DROP TABLE IF EXISTS `5kcrm_admin_oalog_rule`;
CREATE TABLE `5kcrm_admin_oalog_rule`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) UNSIGNED NOT NULL COMMENT '类型：1日报；2周报；3月报；4欢迎语',
  `userIds` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户ID串',
  `effective_day` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '需要统计的日志，针对日报',
  `start_time` char(5) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '日报提交的开始时间',
  `end_time` char(5) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '日报提交的结束时间',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：1启用；2禁用',
  `mark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '类型为欢迎语使用的字段',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日报规则表' ROW_FORMAT = Dynamic;

ALTER TABLE `5kcrm_crm_business_type` ADD COLUMN `is_display` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '软删除：1显示0不显示';

DROP TABLE IF EXISTS `5kcrm_admin_operation_log`;
CREATE TABLE `5kcrm_admin_operation_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '员工ID',
  `client_ip` varchar(30) NOT NULL DEFAULT '' COMMENT '客户的IP',
  `module` varchar(20) NOT NULL COMMENT '模块',
  `action_id` int(10) unsigned NOT NULL COMMENT '操作ID',
  `content` text NOT NULL COMMENT '内容',
  `create_time` int(10) unsigned NOT NULL COMMENT '时间',
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='数据操作日志';

ALTER TABLE `5kcrm_admin_message` ADD COLUMN `title`  varchar(255) NULL AFTER `is_delete`;

ALTER TABLE `5kcrm_admin_action_log` ADD COLUMN `client_ip`  varchar(30) NULL AFTER `structure_ids`;

ALTER TABLE `5kcrm_crm_business` ADD COLUMN `contacts_id`  int(10) NULL AFTER `last_record`;

ALTER TABLE `5kcrm_admin_import_record` ADD COLUMN `user_id`  int(10) NULL AFTER `create_time`;

INSERT INTO `5kcrm_admin_examine_flow` (`name`, `config`, `types`, `update_user_id`, `create_time`, `update_time`) VALUES ('发票', '0', 'crm_invoice', '1', '1612756642', '1612756642');

DROP TABLE IF EXISTS `5kcrm_crm_receivables_file`;
CREATE TABLE `5kcrm_crm_receivables_file` (
  `r_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `receivables_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

INSERT INTO `5kcrm_crm_config` (`name`, `value`, `description`) VALUES ('activity_phrase', 'a:5:{i:0;s:18:\\\"电话无人接听\\\";i:1;s:15:\\\"客户无意向\\\";i:2;s:42:\\\"客户意向度适中，后续继续跟进\\\";i:3;s:42:\\\"客户意向度较强，成交几率较大\\\";i:4;s:3:\\\"312\\\";}', '跟进记录常用语');
INSERT INTO `5kcrm_crm_config` (`name`, `value`, `description`) VALUES ('visit_config', '1', '是否开启回访提醒：1开启；0不开启');
INSERT INTO `5kcrm_crm_config` (`name`, `value`, `description`) VALUES ('visit_day', '10', '客户回访提醒天数');