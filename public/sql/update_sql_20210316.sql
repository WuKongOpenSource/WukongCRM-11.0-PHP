ALTER TABLE `5kcrm_crm_leads` ADD COLUMN `is_dealt` tinyint(1) NOT NULL DEFAULT 1  COMMENT '是否已经处理（待办事项）：1已处理；0未处理；';
ALTER TABLE `5kcrm_crm_customer` ADD COLUMN `is_dealt` tinyint(1) NOT NULL DEFAULT 1  COMMENT '是否已经处理（待办事项）：1已处理；0未处理；';
ALTER TABLE `5kcrm_crm_business` ADD COLUMN `is_dealt` tinyint(1) NOT NULL DEFAULT 1  COMMENT '是否已经处理（待办事项）：1已处理；0未处理；';
ALTER TABLE `5kcrm_crm_receivables_plan` ADD COLUMN `is_dealt` tinyint(1) NOT NULL DEFAULT 0  COMMENT '是否已经处理（待办事项）：1已处理；0未处理；';

DROP TABLE IF EXISTS `5kcrm_crm_dealt_relation`;
CREATE TABLE `5kcrm_crm_dealt_relation`  (
  `dealt_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `types` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型：crm_contract；crm_invoice；crm_receivables',
  `types_id` int(10) UNSIGNED NOT NULL COMMENT '类型ID',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  PRIMARY KEY (`dealt_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '待办事项关联表' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `5kcrm_work_order`;
CREATE TABLE `5kcrm_work_order`  (
  `order_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`order_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '项目排序表' ROW_FORMAT = Dynamic;

UPDATE `5kcrm_admin_field` SET `form_type`='email' WHERE `types` = 'crm_customer' AND `field` = 'email';

ALTER TABLE `5kcrm_crm_leads` ADD COLUMN `is_allocation` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是分配给我的线索：1是；0不是';
ALTER TABLE `5kcrm_crm_customer` ADD COLUMN `is_allocation` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是分配给我的客户：1是；0不是';

ALTER TABLE `5kcrm_admin_user` ADD COLUMN `is_read_notice` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户是否已读升级公告：1已读；0未读';

ALTER TABLE `5kcrm_crm_activity` MODIFY COLUMN `content` varchar(1024) DEFAULT NULL COMMENT '跟进内容';
