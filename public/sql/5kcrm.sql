/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50726
 Source Host           : localhost:3306
 Source Schema         : 72crm-php

 Target Server Type    : MySQL
 Target Server Version : 50726
 File Encoding         : 65001

 Date: 10/05/2021 10:37:01
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for 5kcrm_admin_access
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_access`;
CREATE TABLE `5kcrm_admin_access`  (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_access
-- ----------------------------
INSERT INTO `5kcrm_admin_access` VALUES (1, 1);

-- ----------------------------
-- Table structure for 5kcrm_admin_action_log
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_action_log`;
CREATE TABLE `5kcrm_admin_action_log`  (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '操作人ID',
  `module_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块',
  `controller_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '控制器',
  `action_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '方法',
  `action_id` int(10) NOT NULL COMMENT '操作ID',
  `target_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '被操作对象的名称',
  `action_delete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1为删除操作',
  `content` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '操作内容',
  `create_time` int(11) NOT NULL COMMENT '操作时间',
  `join_user_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '抄送人IDs',
  `structure_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '抄送部门IDs',
  `client_ip` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '操作记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_action_log
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_action_record
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_action_record`;
CREATE TABLE `5kcrm_admin_action_record`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `types` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型',
  `action_id` int(11) NOT NULL COMMENT '操作ID',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '字段操作记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_action_record
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_comment
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_comment`;
CREATE TABLE `5kcrm_admin_comment`  (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '评论表',
  `user_id` int(11) NOT NULL COMMENT '评论人ID',
  `content` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '内容(答案)',
  `reply_content` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '回复内容（问题）',
  `create_time` int(11) NOT NULL COMMENT '新建时间',
  `isreply` tinyint(2) NULL DEFAULT 0 COMMENT '是否是回复 1 是 0 否',
  `reply_user_id` int(11) NOT NULL DEFAULT 0,
  `reply_id` int(11) NULL DEFAULT 0 COMMENT '回复对象ID',
  `status` tinyint(2) NULL DEFAULT 1 COMMENT '状态 ',
  `type_id` int(11) NULL DEFAULT 0 COMMENT '评论项目任务ID 或评论其他模块ID',
  `favour` int(7) NULL DEFAULT 0 COMMENT '赞',
  `type` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '分类 ',
  `reply_fid` int(11) NOT NULL DEFAULT 0 COMMENT '回复最上级ID',
  PRIMARY KEY (`comment_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '任务评论表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_comment
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_config
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_config`;
CREATE TABLE `5kcrm_admin_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名字',
  `status` tinyint(2) NOT NULL COMMENT '状态',
  `module` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模型',
  `controller` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '控制器',
  `type` tinyint(2) NOT NULL COMMENT '类型：1已发布，2未发布，3增值',
  `pid` tinyint(4) NOT NULL COMMENT '父级ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_config
-- ----------------------------
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

-- ----------------------------
-- Table structure for 5kcrm_admin_examine_flow
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_examine_flow`;
CREATE TABLE `5kcrm_admin_examine_flow`  (
  `flow_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '审批流名称',
  `config` tinyint(4) NOT NULL COMMENT '1固定审批0授权审批',
  `types` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '关联对象',
  `types_id` tinyint(4) NOT NULL DEFAULT 0 COMMENT '对象ID（如审批类型ID）',
  `structure_ids` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '部门ID（0为全部）',
  `user_ids` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '员工ID',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '流程说明',
  `update_user_id` int(11) NOT NULL COMMENT '修改人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态 1启用，0禁用',
  `is_deleted` tinyint(4) NOT NULL DEFAULT 0 COMMENT '状态 1删除',
  `delete_time` int(11) NOT NULL DEFAULT 0 COMMENT '删除时间',
  `delete_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '删除人ID',
  PRIMARY KEY (`flow_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '审批流程表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_examine_flow
-- ----------------------------
INSERT INTO `5kcrm_admin_examine_flow` VALUES (1, '普通审批流程', 0, 'oa_examine', 1, '', '', '', 1, 1548835446, 1548835446, 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (2, '请假审批流程', 0, 'oa_examine', 2, '', '', '', 1, 1548835717, 1548835717, 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (3, '出差审批流程', 0, 'oa_examine', 3, '', '', '', 1, 1549959653, 1549959653, 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (4, '加班审批流程', 0, 'oa_examine', 4, '', '', '', 1, 1549959653, 1549959653, 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (5, '差旅审批流程', 0, 'oa_examine', 5, '', '', '', 1, 1549959653, 1549959653, 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (6, '借款审批流程', 0, 'oa_examine', 6, '', '', '', 1, 1549959653, 1549959653, 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (7, '合同审批流程', 0, 'crm_contract', 0, '', '', '', 1, 1549959653, 1549959653, 0, 1, 1620610745, 1);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (8, '回款审批流程', 0, 'crm_receivables', 0, '', '', '', 1, 1549959653, 1549959653, 0, 1, 1620610748, 1);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (9, '发票', 0, 'crm_invoice', 0, '', '', '', 1, 1612756642, 1612756642, 0, 1, 1620610741, 1);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (10, '发票审批流程', 0, 'crm_invoice', 0, '', '', '', 1, 1620610740, 1620610740, 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (11, '合同审批流程', 0, 'crm_contract', 0, '', '', '', 1, 1620610745, 1620610745, 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_examine_flow` VALUES (12, '回款审批流程', 0, 'crm_receivables', 0, '', '', '', 1, 1620610748, 1620610748, 1, 0, 0, 0);

-- ----------------------------
-- Table structure for 5kcrm_admin_examine_record
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_examine_record`;
CREATE TABLE `5kcrm_admin_examine_record`  (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '类型',
  `types_id` int(11) NOT NULL DEFAULT 0 COMMENT '类型ID',
  `flow_id` int(11) NOT NULL DEFAULT 0 COMMENT '审批流程ID',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '审批排序ID',
  `check_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '审批人ID',
  `check_time` int(11) NOT NULL COMMENT '审批时间',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1审核通过0审核失败2撤销',
  `content` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '审核意见',
  `is_end` tinyint(1) NOT NULL DEFAULT 0 COMMENT '审批失效（1标记为无效）',
  PRIMARY KEY (`record_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '审批记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_examine_record
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_examine_step
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_examine_step`;
CREATE TABLE `5kcrm_admin_examine_step`  (
  `step_id` int(11) NOT NULL AUTO_INCREMENT,
  `flow_id` int(11) NOT NULL COMMENT '审批流程ID',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1负责人主管，2指定用户（任意一人），3指定用户（多人会签），4上一级审批人主管',
  `user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '审批人ID (使用逗号隔开) ,1,2,',
  `order_id` tinyint(4) NOT NULL DEFAULT 1 COMMENT '排序ID',
  `relation` tinyint(1) NOT NULL DEFAULT 1 COMMENT '审批流程关系（1并2或）',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`step_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '审批步骤表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_examine_step
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_field
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_field`;
CREATE TABLE `5kcrm_admin_field`  (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '分类',
  `types_id` int(11) NOT NULL DEFAULT 0 COMMENT '分类ID（审批等）',
  `field` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '字段名',
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '标识名',
  `form_type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '字段类型',
  `default_value` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '默认值',
  `max_length` int(4) NOT NULL DEFAULT 0 COMMENT ' 字数上限',
  `is_unique` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否唯一（1是，0否）',
  `is_null` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否必填（1是，0否）',
  `input_tips` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '输入提示',
  `setting` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '设置',
  `order_id` int(4) NOT NULL DEFAULT 0 COMMENT '排序ID',
  `operating` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0改删，1改，2删，3无',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `type` int(2) NOT NULL DEFAULT 0 COMMENT '薪资管理 1固定 2增加 3减少',
  `relevant` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '相关字段名',
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否隐藏：1隐藏；0不隐藏',
  PRIMARY KEY (`field_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 242 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '自定义字段表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_field
-- ----------------------------
INSERT INTO `5kcrm_admin_field` VALUES (1, '', 0, 'create_user_id', '创建人', 'user', '', 0, 0, 0, '', '', 99, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (2, '', 0, 'update_time', '更新时间', 'datetime', '', 0, 0, 0, '', '', 100, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (3, '', 0, 'create_time', '创建时间', 'datetime', '', 0, 0, 0, '', '', 101, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (4, '', 0, 'owner_user_id', '负责人', 'user', '', 0, 0, 0, '', '', 102, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (5, 'crm_leads', 0, 'name', '线索名称', 'text', '', 0, 1, 1, '', '', 1, 1, 1553788800, 1620610808, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (6, 'crm_leads', 0, 'source', '线索来源', 'select', '', 0, 0, 0, '', '促销活动\n搜索引擎\n广告\n转介绍\n线上注册\n线上询价\n预约上门\n陌拜\n招商资源\n公司资源\n展会资源\n个人资源\n电话咨询\n邮件咨询', 2, 1, 1553788800, 1620610808, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (7, 'crm_leads', 0, 'telephone', '电话', 'text', '', 0, 0, 0, '', '', 3, 1, 1553788800, 1620610808, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (8, 'crm_leads', 0, 'mobile', '手机', 'mobile', '', 0, 1, 0, '', '', 4, 1, 1553788800, 1620610808, 7, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (9, 'crm_leads', 0, 'industry', '客户行业', 'select', '', 0, 0, 0, '', 'IT/通信/电子/互联网\n金融业\n房地产\n商业服务\n贸易\n生产\n运输/物流\n服务业\n文化传媒\n政府\n其他', 5, 1, 1553788800, 1620610808, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (10, 'crm_leads', 0, 'level', '客户级别', 'select', '', 0, 0, 0, '', 'A（重点客户）\nB（普通客户）\nC（非优先客户）', 6, 1, 1553788800, 1620610808, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (11, 'crm_leads', 0, 'detail_address', '地址', 'text', '', 0, 0, 0, '', '', 7, 1, 1553788800, 1620610808, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (12, 'crm_leads', 0, 'next_time', '下次联系时间', 'datetime', '', 0, 0, 0, '', '', 8, 1, 1553788800, 1620610808, 13, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (13, 'crm_leads', 0, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 9, 1, 1553788800, 1620610808, 2, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (14, 'crm_customer', 0, 'name', '客户名称', 'text', '', 0, 1, 1, '', '', 1, 1, 1553788800, 1620290850, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (15, 'crm_customer', 0, 'level', '客户级别', 'select', '', 0, 0, 0, '', 'A（重点客户）\nB（普通客户）\nC（非优先客户）', 2, 1, 1553788800, 1620290850, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (16, 'crm_customer', 0, 'industry', '客户行业', 'select', '', 0, 0, 0, '', 'IT/通信/电子/互联网\n金融业\n房地产\n商业服务\n贸易\n生产\n运输/物流\n服务业\n文化传媒\n政府\n其他', 3, 1, 1553788800, 1620290851, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (17, 'crm_customer', 0, 'source', '客户来源', 'select', '', 0, 0, 0, '', '促销活动\n搜索引擎\n广告\n转介绍\n线上注册\n线上询价\n预约上门\n陌拜\n招商资源\n公司资源\n展会资源\n个人资源\n电话咨询\n邮件咨询', 4, 1, 1553788800, 1620290851, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (18, 'crm_customer', 0, 'deal_status', '成交状态', 'select', '未成交', 0, 0, 1, '', '未成交\n已成交', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (19, 'crm_customer', 0, 'telephone', '电话', 'text', '', 0, 0, 0, '', '', 5, 1, 1553788800, 1620290851, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (20, 'crm_customer', 0, 'website', '网址', 'text', '', 0, 0, 0, '', '', 6, 1, 1553788800, 1620290851, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (21, 'crm_customer', 0, 'next_time', '下次联系时间', 'datetime', '', 0, 0, 0, '', '', 7, 1, 1553788800, 1620290851, 13, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (22, 'crm_customer', 0, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 8, 1, 1553788800, 1620290851, 2, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (23, 'crm_contacts', 0, 'name', '姓名', 'text', '', 0, 1, 1, '', '', 1, 1, 1553788800, 1620610868, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (24, 'crm_contacts', 0, 'customer_id', '客户名称', 'customer', '', 0, 0, 1, '', '', 2, 3, 1553788800, 1620610868, 15, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (25, 'crm_contacts', 0, 'mobile', '手机', 'mobile', '', 0, 0, 0, '', '', 3, 1, 1553788800, 1620610868, 7, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (26, 'crm_contacts', 0, 'telephone', '电话', 'text', '', 0, 0, 0, '', '', 4, 1, 1553788800, 1620610868, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (27, 'crm_contacts', 0, 'email', '电子邮箱', 'email', '', 0, 0, 0, '', '', 5, 1, 1553788800, 1620610868, 14, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (28, 'crm_contacts', 0, 'decision', '是否关键决策人', 'select', '', 0, 0, 0, '', '是\n否', 6, 1, 1553788800, 1620610868, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (29, 'crm_contacts', 0, 'post', '职务', 'text', '', 0, 0, 0, '', '', 7, 1, 1553788800, 1620610869, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (30, 'crm_contacts', 0, 'sex', '性别', 'select', '', 0, 0, 0, '', '男\n女', 8, 1, 1553788800, 1620610869, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (31, 'crm_contacts', 0, 'detail_address', '地址', 'text', '', 0, 0, 0, '', '', 9, 1, 1553788800, 1620610869, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (32, 'crm_contacts', 0, 'next_time', '下次联系时间', 'datetime', '', 0, 0, 0, '', '', 10, 1, 1553788800, 1620610869, 13, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (33, 'crm_contacts', 0, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 11, 1, 1553788800, 1620610869, 2, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (34, 'crm_business', 0, 'name', '商机名称', 'text', '', 0, 0, 1, '', '', 1, 1, 1553788800, 1620610954, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (35, 'crm_business', 0, 'customer_id', '客户名称', 'customer', '', 0, 0, 1, '', '', 2, 3, 1553788800, 1620610954, 15, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (36, 'crm_business', 0, 'type_id', '商机状态组', 'business_type', '', 0, 0, 1, '', '', 3, 3, 1553788800, 1620610954, 0, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (37, 'crm_business', 0, 'status_id', '商机阶段', 'business_status', '', 0, 0, 1, '', '', 4, 3, 1553788800, 1620610954, 0, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (38, 'crm_business', 0, 'money', '商机金额', 'floatnumber', '', 0, 0, 0, '元', '', 5, 3, 1553788800, 1620610954, 6, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (39, 'crm_business', 0, 'deal_date', '预计成交日期', 'date', '', 0, 0, 1, '', '', 6, 3, 1553788800, 1620610954, 4, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (40, 'crm_business', 0, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 7, 1, 1553788800, 1620610954, 2, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (41, 'crm_contract', 0, 'num', '合同编号', 'text', '', 0, 1, 1, '', '', 1, 1, 1553788800, 1620611006, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (42, 'crm_contract', 0, 'name', '合同名称', 'text', '', 0, 0, 1, '', '', 2, 1, 1553788800, 1620611006, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (43, 'crm_contract', 0, 'customer_id', '客户名称', 'customer', '', 0, 0, 1, '', '', 3, 3, 1553788800, 1620611006, 15, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (44, 'crm_contract', 0, 'business_id', '商机名称', 'business', '', 0, 0, 0, '', '', 4, 3, 1553788800, 1620611006, 16, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (45, 'crm_contract', 0, 'order_date', '下单时间', 'date', '', 0, 0, 0, '', '', 5, 1, 1553788800, 1620611006, 4, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (46, 'crm_contract', 0, 'money', '合同金额', 'floatnumber', '', 0, 0, 1, '元', '', 6, 1, 1553788800, 1620611006, 6, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (47, 'crm_contract', 0, 'start_time', '合同开始时间', 'date', '', 0, 0, 0, '', '', 7, 3, 1553788800, 1620611007, 4, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (48, 'crm_contract', 0, 'end_time', '合同到期时间', 'date', '', 0, 0, 0, '', '', 8, 3, 1553788800, 1620611007, 4, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (49, 'crm_contract', 0, 'contacts_id', '客户签约人', 'contacts', '', 0, 0, 0, '', '', 9, 3, 1553788800, 1620611007, 17, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (50, 'crm_contract', 0, 'order_user_id', '公司签约人', 'user', '', 0, 0, 0, '', '', 10, 3, 1553788800, 1620611007, 10, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (51, 'crm_contract', 0, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 11, 1, 1553788800, 1620611007, 2, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (52, 'crm_receivables', 0, 'number', '回款编号', 'text', '', 0, 1, 1, '', '', 1, 3, 1553788800, 1620611056, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (53, 'crm_receivables', 0, 'customer_id', '客户名称', 'customer', '', 0, 0, 1, '', '', 2, 3, 1553788800, 1620611056, 15, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (54, 'crm_receivables', 0, 'contract_id', '合同编号', 'contract', '', 0, 0, 1, '', '', 3, 3, 1553788800, 1620611056, 20, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (55, 'crm_receivables', 0, 'return_time', '回款日期', 'date', '', 0, 0, 1, '', '', 4, 3, 1553788800, 1620611056, 4, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (56, 'crm_receivables', 0, 'return_type', '回款方式', 'select', '', 0, 0, 1, '', '支票\n现金\n邮政汇款\n电汇\n网上转账\n支付宝\n微信支付\n其他', 5, 3, 1553788800, 1620611056, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (57, 'crm_receivables', 0, 'money', '回款金额', 'floatnumber', '', 0, 0, 1, '元', '', 6, 3, 1553788800, 1620611056, 6, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (58, 'crm_receivables', 0, 'plan_id', '期数', 'receivables_plan', '', 0, 0, 0, '', '', 7, 3, 1553788800, 1620611056, 21, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (59, 'crm_receivables', 0, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 8, 1, 1553788800, 1620611056, 2, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (60, 'crm_product', 0, 'name', '产品名称', 'text', '', 0, 0, 1, '', '', 1, 1, 1553788800, 1620610911, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (61, 'crm_product', 0, 'category_id', '产品类别', 'category', '', 0, 0, 1, '', '', 2, 3, 1553788800, 1620610911, 19, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (62, 'crm_product', 0, 'num', '产品编码', 'text', '', 0, 0, 1, '', '', 3, 3, 1553788800, 1620610911, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (63, 'crm_product', 0, 'status', '是否上架', 'select', '上架', 0, 0, 1, '', '上架\n下架', 4, 3, 1553788800, 1620610911, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (64, 'crm_product', 0, 'unit', '单位', 'select', '', 0, 0, 1, '', '个\n块\n只\n把\n枚\n瓶\n盒\n台\n吨\n千克\n米\n箱', 5, 1, 1553788800, 1620610911, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (65, 'crm_product', 0, 'price', '标准价格', 'floatnumber', '', 0, 0, 1, '元', '', 6, 1, 1553788800, 1620610911, 6, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (66, 'crm_product', 0, 'description', '产品描述', 'text', '', 0, 0, 1, '', '', 7, 1, 1553788800, 1620610911, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (67, 'oa_examine', 1, 'content', '审批内容', 'text', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (68, 'oa_examine', 1, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (69, 'oa_examine', 2, 'type_id', '请假类型', 'select', '', 0, 0, 1, '', '年假\n事假\n病假\n产假\n调休\n婚假\n丧假\n其他', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (70, 'oa_examine', 2, 'content', '审批内容', 'text', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (71, 'oa_examine', 2, 'start_time', '开始时间', 'datetime', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (72, 'oa_examine', 2, 'end_time', '结束时间', 'datetime', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (73, 'oa_examine', 2, 'duration', '时长(天)', 'floatnumber', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (74, 'oa_examine', 2, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (75, 'oa_examine', 3, 'content', '出差事由', 'text', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (76, 'oa_examine', 3, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (77, 'oa_examine', 3, 'cause', '行程明细', 'business_cause', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (78, 'oa_examine', 3, 'duration', '出差总天数', 'floatnumber', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (79, 'oa_examine', 4, 'content', '加班原因', 'text', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (80, 'oa_examine', 4, 'start_time', '开始时间', 'datetime', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (81, 'oa_examine', 4, 'end_time', '结束时间', 'datetime', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (82, 'oa_examine', 4, 'duration', '加班总天数', 'floatnumber', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (83, 'oa_examine', 4, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (84, 'oa_examine', 5, 'content', '差旅事由', 'text', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (85, 'oa_examine', 5, 'cause', '费用明细', 'examine_cause', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (86, 'oa_examine', 5, 'money', '报销总金额', 'floatnumber', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (87, 'oa_examine', 5, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (88, 'oa_examine', 6, 'content', '借款事由', 'text', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (89, 'oa_examine', 6, 'money', '借款金额（元）', 'floatnumber', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (90, 'oa_examine', 6, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (91, 'crm_receivables_plan', 0, 'customer_id', '客户名称', 'customer', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (92, 'crm_receivables_plan', 0, 'contract_id', '合同编号', 'contract', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (93, 'crm_receivables_plan', 0, 'money', '计划回款金额', 'floatnumber', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (94, 'crm_receivables_plan', 0, 'return_date', '计划回款日期', 'date', '', 0, 0, 1, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (95, 'crm_receivables_plan', 0, 'return_type', '计划回款方式', 'select', '', 0, 0, 1, '', '支票\n现金\n邮政汇款\n电汇\n网上转账\n支付宝\n微信支付\n其他\n在线支付\n线下支付\n预存款\n返利\n预存款+返利', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (96, 'crm_receivables_plan', 0, 'remind', '提前几日提醒', 'number', '', 0, 0, 0, '', '', 0, 3, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (97, 'crm_receivables_plan', 0, 'remark', '备注', 'textarea', '', 0, 0, 0, '', '', 0, 1, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (98, 'crm_receivables_plan', 0, 'file', '附件', 'file', '', 0, 0, 0, '', '', 0, 1, 1553788800, 1553788800, 0, NULL, 0);
INSERT INTO `5kcrm_admin_field` VALUES (99, 'crm_customer', 0, 'mobile', '手机', 'mobile', '', 0, 1, 0, '', '', 9, 1, 1553788800, 1620290851, 7, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (100, 'crm_customer', 0, 'email', '邮箱', 'email', '', 0, 0, 0, '', '', 10, 1, 1553788800, 1620290851, 14, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (101, 'crm_visit', 0, 'number', '回访编号', 'text', '', 0, 0, 1, '', NULL, 1, 1, 1553788800, 1620611098, 1, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (102, 'crm_visit', 0, 'visit_time', '回访时间', 'date', '', 0, 0, 1, '', '', 2, 1, 1553788800, 1620611098, 4, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (103, 'crm_visit', 0, 'owner_user_id', '回访人', 'single_user', '', 0, 0, 1, '', '', 8, 3, 1553788800, 1620611099, 28, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (104, 'crm_visit', 0, 'shape', '回访形式', 'select', '', 0, 0, 0, '', '见面拜访\r\n电话\r\n短信\r\n邮件\r\n微信', 3, 1, 1553788800, 1620611098, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (105, 'crm_visit', 0, 'customer_id', '客户名称', 'customer', '', 0, 0, 1, '', '', 4, 1, 1553788800, 1620611099, 15, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (106, 'crm_visit', 0, 'contacts_id', '联系人', 'contacts', '', 0, 0, 0, '', '', 5, 3, 1553788800, 1620611099, 17, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (107, 'crm_visit', 0, 'contract_id', '合同编号', 'contract', '', 0, 0, 1, '', '', 6, 1, 1553788800, 1620611099, 20, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (108, 'crm_visit', 0, 'satisfaction', '客户满意度', 'select', '', 0, 0, 0, '', '很满意\r\n满意\r\n一般\r\n不满意\r\n很不满意', 7, 1, 1553788800, 1620611099, 3, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (109, 'crm_visit', 0, 'feedback', '客户反馈', 'textarea', '', 0, 0, 0, '', '', 9, 1, 1553788800, 1620611099, 2, '', 0);
INSERT INTO `5kcrm_admin_field` VALUES (110, 'crm_leads', 0, 'email', '电子邮箱', 'email', '', 0, 0, 0, '', NULL, 10, 1, 1616464748, 1620610808, 14, '', 0);

-- ----------------------------
-- Table structure for 5kcrm_admin_field_grant
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_field_grant`;
CREATE TABLE `5kcrm_admin_field_grant`  (
  `grant_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int(10) NOT NULL COMMENT '角色ID',
  `module` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块：crm、oa、bi等',
  `column` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '栏目：leads、customer、contacts等',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '授权内容',
  `update_time` int(10) NOT NULL COMMENT '修改日期',
  `create_time` int(10) NOT NULL COMMENT '创建日期',
  PRIMARY KEY (`grant_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '角色权限管理-字段授权' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_field_grant
-- ----------------------------
INSERT INTO `5kcrm_admin_field_grant` VALUES (1, 10, 'crm', 'leads', 'a:16:{i:0;a:7:{s:5:\"field\";s:4:\"name\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"线索名称\";}i:1;a:7:{s:5:\"field\";s:5:\"email\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"邮箱\";}i:2;a:7:{s:5:\"field\";s:6:\"source\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"线索来源\";}i:3;a:7:{s:5:\"field\";s:6:\"mobile\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"手机\";}i:4;a:7:{s:5:\"field\";s:9:\"telephone\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"电话\";}i:5;a:7:{s:5:\"field\";s:14:\"detail_address\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"地址\";}i:6;a:7:{s:5:\"field\";s:8:\"industry\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户行业\";}i:7;a:7:{s:5:\"field\";s:5:\"level\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户级别\";}i:8;a:7:{s:5:\"field\";s:9:\"next_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"下次联系时间\";}i:9;a:7:{s:5:\"field\";s:6:\"remark\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"备注\";}i:10;a:7:{s:5:\"field\";s:13:\"owner_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"负责人\";}i:11;a:7:{s:5:\"field\";s:11:\"last_record\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进记录\";}i:12;a:7:{s:5:\"field\";s:14:\"create_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"创建人\";}i:13;a:7:{s:5:\"field\";s:11:\"create_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"创建时间\";}i:14;a:7:{s:5:\"field\";s:11:\"update_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"跟新时间\";}i:15;a:7:{s:5:\"field\";s:9:\"last_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进时间\";}}', 1617340207, 1617340207);
INSERT INTO `5kcrm_admin_field_grant` VALUES (2, 10, 'crm', 'customer', 'a:20:{i:0;a:7:{s:5:\"field\";s:4:\"name\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户名称\";}i:1;a:7:{s:5:\"field\";s:6:\"source\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户来源\";}i:2;a:7:{s:5:\"field\";s:6:\"mobile\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"手机\";}i:3;a:7:{s:5:\"field\";s:9:\"telephone\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"电话\";}i:4;a:7:{s:5:\"field\";s:7:\"website\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"网址\";}i:5;a:7:{s:5:\"field\";s:8:\"industry\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户行业\";}i:6;a:7:{s:5:\"field\";s:5:\"level\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户级别\";}i:7;a:7:{s:5:\"field\";s:9:\"next_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"下次联系时间\";}i:8;a:7:{s:5:\"field\";s:6:\"remark\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"备注\";}i:9;a:7:{s:5:\"field\";s:5:\"email\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"邮箱\";}i:10;a:7:{s:5:\"field\";s:13:\"owner_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"负责人\";}i:11;a:7:{s:5:\"field\";s:11:\"last_record\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进记录\";}i:12;a:7:{s:5:\"field\";s:14:\"create_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"创建人\";}i:13;a:7:{s:5:\"field\";s:11:\"create_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"创建时间\";}i:14;a:7:{s:5:\"field\";s:11:\"update_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"更新时间\";}i:15;a:7:{s:5:\"field\";s:9:\"last_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进时间\";}i:16;a:7:{s:5:\"field\";s:11:\"obtain_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:27:\"负责人获取客户时间\";}i:17;a:7:{s:5:\"field\";s:11:\"deal_status\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"成交状态\";}i:18;a:7:{s:5:\"field\";s:7:\"is_lock\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"锁定状态\";}i:19;a:7:{s:5:\"field\";s:8:\"pool_day\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:21:\"距进入公海天数\";}}', 1617340207, 1617340207);
INSERT INTO `5kcrm_admin_field_grant` VALUES (3, 10, 'crm', 'contacts', 'a:17:{i:0;a:7:{s:5:\"field\";s:4:\"name\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"姓名\";}i:1;a:7:{s:5:\"field\";s:11:\"customer_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户名称\";}i:2;a:7:{s:5:\"field\";s:6:\"mobile\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"手机\";}i:3;a:7:{s:5:\"field\";s:9:\"telephone\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"电话\";}i:4;a:7:{s:5:\"field\";s:5:\"email\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"邮箱\";}i:5;a:7:{s:5:\"field\";s:4:\"post\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"职务\";}i:6;a:7:{s:5:\"field\";s:8:\"decision\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:21:\"是否关键决策人\";}i:7;a:7:{s:5:\"field\";s:14:\"detail_address\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"地址\";}i:8;a:7:{s:5:\"field\";s:9:\"next_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"下次联系时间\";}i:9;a:7:{s:5:\"field\";s:6:\"remark\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"备注\";}i:10;a:7:{s:5:\"field\";s:3:\"sex\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"性别\";}i:11;a:7:{s:5:\"field\";s:13:\"owner_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"负责人\";}i:12;a:7:{s:5:\"field\";s:14:\"create_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"创建人\";}i:13;a:7:{s:5:\"field\";s:11:\"create_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"创建时间\";}i:14;a:7:{s:5:\"field\";s:11:\"update_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"更新时间\";}i:15;a:7:{s:5:\"field\";s:9:\"last_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进时间\";}i:16;a:7:{s:5:\"field\";s:11:\"last_record\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进记录\";}}', 1617340207, 1617340207);
INSERT INTO `5kcrm_admin_field_grant` VALUES (4, 10, 'crm', 'business', 'a:13:{i:0;a:7:{s:5:\"field\";s:4:\"name\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"商机名称\";}i:1;a:7:{s:5:\"field\";s:11:\"customer_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户名称\";}i:2;a:7:{s:5:\"field\";s:5:\"money\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"商机金额\";}i:3;a:7:{s:5:\"field\";s:9:\"deal_date\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"预计成交日期\";}i:4;a:7:{s:5:\"field\";s:6:\"remark\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"备注\";}i:5;a:7:{s:5:\"field\";s:9:\"status_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"商机阶段\";}i:6;a:7:{s:5:\"field\";s:7:\"type_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:15:\"商机状态组\";}i:7;a:7:{s:5:\"field\";s:13:\"owner_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"负责人\";}i:8;a:7:{s:5:\"field\";s:14:\"create_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"创建人\";}i:9;a:7:{s:5:\"field\";s:11:\"create_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"创建时间\";}i:10;a:7:{s:5:\"field\";s:11:\"update_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"更新时间\";}i:11;a:7:{s:5:\"field\";s:9:\"last_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进时间\";}i:12;a:7:{s:5:\"field\";s:11:\"last_record\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进记录\";}}', 1617340207, 1617340207);
INSERT INTO `5kcrm_admin_field_grant` VALUES (5, 10, 'crm', 'contract', 'a:20:{i:0;a:7:{s:5:\"field\";s:4:\"name\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"合同名称\";}i:1;a:7:{s:5:\"field\";s:3:\"num\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"合同编号\";}i:2;a:7:{s:5:\"field\";s:11:\"customer_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户名称\";}i:3;a:7:{s:5:\"field\";s:11:\"business_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"商机名称\";}i:4;a:7:{s:5:\"field\";s:5:\"money\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"合同金额\";}i:5;a:7:{s:5:\"field\";s:10:\"order_date\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"下单时间\";}i:6;a:7:{s:5:\"field\";s:10:\"start_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"合同开始时间\";}i:7;a:7:{s:5:\"field\";s:8:\"end_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"合同结束时间\";}i:8;a:7:{s:5:\"field\";s:11:\"contacts_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:15:\"客户签约人\";}i:9;a:7:{s:5:\"field\";s:13:\"order_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:15:\"公司签约人\";}i:10;a:7:{s:5:\"field\";s:6:\"remark\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"备注\";}i:11;a:7:{s:5:\"field\";s:13:\"owner_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"负责人\";}i:12;a:7:{s:5:\"field\";s:14:\"create_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"创建人\";}i:13;a:7:{s:5:\"field\";s:11:\"create_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"创建时间\";}i:14;a:7:{s:5:\"field\";s:11:\"update_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"更新时间\";}i:15;a:7:{s:5:\"field\";s:9:\"last_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进时间\";}i:16;a:7:{s:5:\"field\";s:11:\"last_record\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:18:\"最后跟进记录\";}i:17;a:7:{s:5:\"field\";s:10:\"done_money\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:15:\"已收款金额\";}i:18;a:7:{s:5:\"field\";s:8:\"un_money\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:15:\"未收款金额\";}i:19;a:7:{s:5:\"field\";s:12:\"check_status\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"审核状态\";}}', 1617340207, 1617340207);
INSERT INTO `5kcrm_admin_field_grant` VALUES (6, 10, 'crm', 'receivables', 'a:14:{i:0;a:7:{s:5:\"field\";s:6:\"number\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"回款编号\";}i:1;a:7:{s:5:\"field\";s:11:\"customer_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户名称\";}i:2;a:7:{s:5:\"field\";s:11:\"contract_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"合同编号\";}i:3;a:7:{s:5:\"field\";s:7:\"plan_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"期数\";}i:4;a:7:{s:5:\"field\";s:11:\"return_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"回款日期\";}i:5;a:7:{s:5:\"field\";s:5:\"money\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"回款金额\";}i:6;a:7:{s:5:\"field\";s:11:\"return_type\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"回款方式\";}i:7;a:7:{s:5:\"field\";s:6:\"remark\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"备注\";}i:8;a:7:{s:5:\"field\";s:14:\"contract_money\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"合同金额\";}i:9;a:7:{s:5:\"field\";s:13:\"owner_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"负责人\";}i:10;a:7:{s:5:\"field\";s:14:\"create_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"创建人\";}i:11;a:7:{s:5:\"field\";s:11:\"create_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"创建时间\";}i:12;a:7:{s:5:\"field\";s:11:\"update_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"更新时间\";}i:13;a:7:{s:5:\"field\";s:12:\"check_status\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"审核状态\";}}', 1617340207, 1617340207);
INSERT INTO `5kcrm_admin_field_grant` VALUES (7, 10, 'crm', 'product', 'a:11:{i:0;a:7:{s:5:\"field\";s:4:\"name\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"产品名称\";}i:1;a:7:{s:5:\"field\";s:11:\"category_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"产品类型\";}i:2;a:7:{s:5:\"field\";s:4:\"unit\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"产品单位\";}i:3;a:7:{s:5:\"field\";s:3:\"num\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"产品编码\";}i:4;a:7:{s:5:\"field\";s:5:\"price\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:6:\"价格\";}i:5;a:7:{s:5:\"field\";s:11:\"description\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"产品描述\";}i:6;a:7:{s:5:\"field\";s:6:\"status\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:15:\"是否上下架\";}i:7;a:7:{s:5:\"field\";s:13:\"owner_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"负责人\";}i:8;a:7:{s:5:\"field\";s:14:\"create_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"创建人\";}i:9;a:7:{s:5:\"field\";s:11:\"create_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"创建时间\";}i:10;a:7:{s:5:\"field\";s:11:\"update_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"更新时间\";}}', 1617340207, 1617340207);
INSERT INTO `5kcrm_admin_field_grant` VALUES (8, 10, 'crm', 'visit', 'a:12:{i:0;a:7:{s:5:\"field\";s:6:\"number\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"回访编号\";}i:1;a:7:{s:5:\"field\";s:10:\"visit_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"回访时间\";}i:2;a:7:{s:5:\"field\";s:13:\"owner_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"回访人\";}i:3;a:7:{s:5:\"field\";s:5:\"shape\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"回访形式\";}i:4;a:7:{s:5:\"field\";s:11:\"customer_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户名称\";}i:5;a:7:{s:5:\"field\";s:11:\"contacts_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"联系人\";}i:6;a:7:{s:5:\"field\";s:11:\"contract_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:0;s:5:\"write\";i:1;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"合同编号\";}i:7;a:7:{s:5:\"field\";s:12:\"satisfaction\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:15:\"客户满意度\";}i:8;a:7:{s:5:\"field\";s:8:\"feedback\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:1;s:15:\"write_operation\";i:1;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"客户反馈\";}i:9;a:7:{s:5:\"field\";s:14:\"create_user_id\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:9:\"创建人\";}i:10;a:7:{s:5:\"field\";s:11:\"create_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"创建时间\";}i:11;a:7:{s:5:\"field\";s:11:\"update_time\";s:4:\"read\";i:1;s:14:\"read_operation\";i:1;s:5:\"write\";i:0;s:15:\"write_operation\";i:0;s:6:\"is_diy\";i:0;s:4:\"name\";s:12:\"更新时间\";}}', 1617340207, 1617340207);

-- ----------------------------
-- Table structure for 5kcrm_admin_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_file`;
CREATE TABLE `5kcrm_admin_file`  (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型（file、img）',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '附件名称',
  `save_name` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '保存路径名称',
  `size` int(10) NOT NULL COMMENT '附件大小（字节）',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `file_path` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '文件路径',
  `file_path_thumb` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '文件路径(图片缩略图)',
  PRIMARY KEY (`file_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '附件表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_group
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_group`;
CREATE TABLE `5kcrm_admin_group`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` tinyint(4) NOT NULL COMMENT '分类：0客户自定义角色,1系统默认管理角色,2客户管理角色,3人力资源管理角色,4财务管理角色,5项目管理角色,6办公管理角色',
  `title` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名称',
  `rules` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '规则',
  `remark` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(3) NULL DEFAULT 1 COMMENT '1启用0禁用',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1本人，2本人及下属，3本部门，4本部门及下属部门，5全部 ',
  `types` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1超级管理员2系统设置管理员3部门与员工管理员4审批流管理员5工作台管理员6客户管理员7项目管理员8公告管理员',
  `system` tinyint(4) NOT NULL DEFAULT 0 COMMENT '系统角色',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '角色表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_group
-- ----------------------------
INSERT INTO `5kcrm_admin_group` VALUES (1, 1, '超级管理员角色', '', '超级管理员角色', 1, 1, 1, 0);
INSERT INTO `5kcrm_admin_group` VALUES (2, 1, '系统设置管理员', ',105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127,128,129,130,159,138,139,208,209,220,151,221,222,', '系统设置管理员', 1, 0, 2, 0);
INSERT INTO `5kcrm_admin_group` VALUES (3, 1, '部门与员工管理员', ',112,113,114,115,116,117,118,119,105,', '部门与员工管理员', 1, 1, 3, 0);
INSERT INTO `5kcrm_admin_group` VALUES (4, 1, '审批流管理员', ',124,125,105,', '审批流管理员', 1, 1, 4, 0);
INSERT INTO `5kcrm_admin_group` VALUES (5, 1, '工作台管理员', ',122,123,105,', '工作台管理员', 1, 1, 5, 0);
INSERT INTO `5kcrm_admin_group` VALUES (6, 1, '客户管理员', ',126,127,128,129,130,105,', '客户管理员', 1, 1, 6, 0);
INSERT INTO `5kcrm_admin_group` VALUES (7, 1, '公告管理员', '', '公告管理员', 1, 1, 8, 0);
INSERT INTO `5kcrm_admin_group` VALUES (10, 2, '销售经理角色', ',1,2,3,4,5,6,7,8,9,73,74,160,10,11,12,13,14,15,16,17,18,19,20,21,104,161,164,22,23,24,25,26,27,28,81,82,162,34,35,36,37,38,39,40,41,146,163,210,42,43,44,45,46,47,48,49,147,148,211,50,51,52,53,54,55,153,212,213,56,57,58,59,60,61,83,84,149,214,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,224,225,215,216,217,218,219,62,63,64,65,66,69,70,71,72,75,76,77,78,79,80,', '', 1, 2, 0, 0);
INSERT INTO `5kcrm_admin_group` VALUES (11, 1, '项目管理员', ',141,142,143,', '项目管理员', 1, 1, 7, 0);
INSERT INTO `5kcrm_admin_group` VALUES (12, 5, '编辑', ',0,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,201,202,203,204,205,206,207,223', '成员初始加入时默认享有的权限：默认只有新建任务，查看任务权限', 1, 0, 7, 1);
INSERT INTO `5kcrm_admin_group` VALUES (13, 5, '只读', '', '项目只读角色', 1, 0, 0, 0);
INSERT INTO `5kcrm_admin_group` VALUES (14, 6, '办公管理员', '', '', 1, 1, 0, 0);

-- ----------------------------
-- Table structure for 5kcrm_admin_import_record
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_import_record`;
CREATE TABLE `5kcrm_admin_import_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '导入模块',
  `total` int(10) NOT NULL DEFAULT 0 COMMENT '总数',
  `done` int(10) NOT NULL DEFAULT 0 COMMENT '已导入数',
  `cover` int(10) NOT NULL DEFAULT 0 COMMENT '覆盖数',
  `error` int(10) NOT NULL DEFAULT 0 COMMENT '错误数',
  `error_data_file_path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '错误数据文件路径',
  `create_time` int(10) NOT NULL DEFAULT 0,
  `user_id` int(10) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '导入数据记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_import_record
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_login_record
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_login_record`;
CREATE TABLE `5kcrm_admin_login_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '登录成功与否：0成功；1密码错误；2账号禁用',
  `create_user_id` int(10) NOT NULL DEFAULT 0 COMMENT '员工ID',
  `create_time` int(10) NOT NULL DEFAULT 0 COMMENT '登录时间',
  `ip` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '登录IP，IPv6是46 凑整64位',
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '登录地址',
  `browser` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '浏览器',
  `os` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '操作系统',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '说明 - 暂时记录user-agent',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_login_record
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_menu
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_menu`;
CREATE TABLE `5kcrm_admin_menu`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `pid` int(11) NULL DEFAULT 0 COMMENT '上级菜单ID',
  `title` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  `url` varchar(127) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '链接地址',
  `icon` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '图标',
  `menu_type` tinyint(4) NOT NULL COMMENT '菜单类型',
  `sort` tinyint(4) NULL DEFAULT 0 COMMENT '排序（同级有效）',
  `status` tinyint(4) NULL DEFAULT 1 COMMENT '状态',
  `rule_id` int(11) NOT NULL COMMENT '权限id',
  `module` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '后台菜单表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_menu
-- ----------------------------
INSERT INTO `5kcrm_admin_menu` VALUES (1, 0, 'CRM模块', '', '', 0, 0, 1, 1, 'crm');
INSERT INTO `5kcrm_admin_menu` VALUES (2, 1, '线索', '', '', 0, 0, 1, 2, 'leads');
INSERT INTO `5kcrm_admin_menu` VALUES (3, 1, '客户', '', '', 0, 0, 1, 10, 'customer');
INSERT INTO `5kcrm_admin_menu` VALUES (4, 1, '联系人', '', '', 0, 0, 1, 22, 'contacts');
INSERT INTO `5kcrm_admin_menu` VALUES (5, 1, '公海', '', '', 0, 0, 1, 29, 'pool');
INSERT INTO `5kcrm_admin_menu` VALUES (6, 1, '商机', '', '', 0, 0, 1, 34, 'business');
INSERT INTO `5kcrm_admin_menu` VALUES (7, 1, '合同', '', '', 0, 0, 1, 42, 'contract');
INSERT INTO `5kcrm_admin_menu` VALUES (8, 1, '回款', '', '', 0, 0, 1, 50, 'receivables');
INSERT INTO `5kcrm_admin_menu` VALUES (9, 1, '产品', '', '', 0, 0, 1, 56, 'product');

-- ----------------------------
-- Table structure for 5kcrm_admin_message
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_message`;
CREATE TABLE `5kcrm_admin_message`  (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(2) NOT NULL DEFAULT 0 COMMENT '消息类型，用于前端拼接消息',
  `to_user_id` int(10) NOT NULL COMMENT '接收人ID',
  `from_user_id` int(10) NOT NULL COMMENT '发送人ID',
  `content` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '发送内容',
  `send_time` int(11) NOT NULL COMMENT '发送时间',
  `read_time` int(11) NOT NULL COMMENT '阅读时间',
  `module_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块',
  `controller_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '控制器',
  `action_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '方法',
  `action_id` int(11) NOT NULL COMMENT '操作ID',
  `advance_time` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '提前时间',
  `is_delete` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态1未删除2已删除',
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`message_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '站内信' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_message
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_oa_schedule
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_oa_schedule`;
CREATE TABLE `5kcrm_admin_oa_schedule`  (
  `schedule_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '日程类型',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '修改时间',
  `color` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型颜色',
  `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '类型 1系统类型2 自定义类型',
  `is_select` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '显示状态：1显示，0不显示',
  PRIMARY KEY (`schedule_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日程自定义类型' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_oa_schedule
-- ----------------------------
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (1, '分配的任务', 0, NULL, '1', 1, 0);
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (2, '需联系的客户', 0, NULL, '2', 1, 0);
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (3, '即将到期的合同', 0, NULL, '3', 1, 0);
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (4, '计划回款', 0, NULL, '4', 1, 0);
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (5, '需联系的线索', 0, NULL, '5', 1, 0);
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (6, '需联系的商机', 0, NULL, '6', 1, 0);
INSERT INTO `5kcrm_admin_oa_schedule` VALUES (7, '预计成交的商机', 0, NULL, '7', 1, 0);

-- ----------------------------
-- Table structure for 5kcrm_admin_oa_schedule_relation
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_oa_schedule_relation`;
CREATE TABLE `5kcrm_admin_oa_schedule_relation`  (
  `schedule_relation_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL COMMENT '日程类型状态 0隐藏1 显示',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '修改时间',
  `schedule_id` int(10) NOT NULL DEFAULT 2 COMMENT '类型id',
  `user_id` int(10) NOT NULL COMMENT '负责人',
  PRIMARY KEY (`schedule_relation_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日程自定义类型显示状态' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_oa_schedule_relation
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_oalog_rule
-- ----------------------------
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
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日报规则表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_oalog_rule
-- ----------------------------
INSERT INTO `5kcrm_admin_oalog_rule` VALUES (1, 4, NULL, NULL, NULL, NULL, 1, 'a:3:{i:0;s:27:\"每一天都是崭新的！\";i:1;s:63:\"蓝天是宁静的，空气是清新的，阳光是明媚的！\";i:2;s:93:\"以下内容为系统默认欢迎语，在日志随机展示，可自定义更改欢迎语。\";}');
INSERT INTO `5kcrm_admin_oalog_rule` VALUES (2, 1, '3,4,10', '1,2,3,4,5,7,6', '08:00', '21:00', 1, NULL);
INSERT INTO `5kcrm_admin_oalog_rule` VALUES (3, 2, '3,4', NULL, '1', '3', 1, NULL);
INSERT INTO `5kcrm_admin_oalog_rule` VALUES (4, 3, '3', NULL, '3', '8', 1, NULL);

-- ----------------------------
-- Table structure for 5kcrm_admin_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_operation_log`;
CREATE TABLE `5kcrm_admin_operation_log`  (
  `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '员工ID',
  `client_ip` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '客户的IP',
  `module` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块',
  `action_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作ID',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '内容',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '时间',
  `action_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '操作',
  `target_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '被操作对象',
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '数据操作日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_operation_log
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_printing
-- ----------------------------
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
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '打印模板表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_printing
-- ----------------------------
INSERT INTO `5kcrm_admin_printing` VALUES (1, 5, '李四', '合同条款打印模板', 6, '{\"data\":\"<p style=\\\"text-align: center; line-height: 1; margin-bottom: 15px;\\\"><span style=\\\"font-size: 36px; font-family: simsun, serif;\\\">***\\u6709\\u9650\\u516c\\u53f8<\\/span><\\/p>\\n<p style=\\\"text-align: center; line-height: 1; margin-bottom: 15px;\\\"><span style=\\\"font-size: 36px; font-family: simsun, serif;\\\">\\u9500\\u552e\\u5408\\u540c<\\/span><\\/p>\\n<p style=\\\"text-align: right;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif; color: #525151;\\\">\\u5408\\u540c\\u7f16\\u53f7\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"contract.num\\\">{\\u5408\\u540c\\u7f16\\u53f7}<\\/span><\\/span><\\/p>\\n<p>\\u7532\\u65b9\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--customer\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"customer.name\\\">{\\u5ba2\\u6237\\u540d\\u79f0}<\\/span><\\/p>\\n<p>\\u4e59\\u65b9\\uff1a<span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">\\u90d1\\u5dde\\u5361\\u5361\\u7f57\\u7279\\u8f6f\\u4ef6\\u79d1\\u6280\\u6709\\u9650\\u516c\\u53f8<\\/span><\\/p>\\n<p>&nbsp;<\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">\\u7532\\u4e59\\u53cc\\u65b9\\u672c\\u7740\\u76f8\\u4e92\\u4fe1\\u4efb\\uff0c\\u771f\\u8bda\\u5408\\u4f5c\\u7684\\u539f\\u5219\\uff0c\\u7ecf\\u53cc\\u65b9\\u53cb\\u597d\\u534f\\u5546\\uff0c\\u5c31\\u4e59\\u65b9\\u4e3a\\u7532\\u65b9\\u63d0\\u4f9b\\u7279\\u5b9a\\u670d\\u52a1\\u8fbe\\u6210\\u4e00\\u81f4\\u610f\\u89c1\\uff0c\\u7279\\u7b7e\\u8ba2\\u672c\\u5408\\u540c\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\"><strong>\\u4e00\\u3001\\u670d\\u52a1\\u5185\\u5bb9<\\/strong><\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">1\\u3001\\u4e59\\u65b9\\u540c\\u610f\\u5411\\u7532\\u65b9\\u63d0\\u4f9b\\u7684\\u7279\\u5b9a\\u670d\\u52a1\\u3002\\u670d\\u52a1\\u7684\\u5185\\u5bb9\\u7684\\u6807\\u51c6\\u89c1\\u9644\\u4ef6A\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">2\\u3001\\u5982\\u679c\\u4e59\\u65b9\\u5728\\u5de5\\u4f5c\\u4e2d\\u56e0\\u81ea\\u8eab\\u8fc7\\u9519\\u800c\\u53d1\\u751f\\u4efb\\u4f55\\u9519\\u8bef\\u6216\\u9057\\u6f0f\\uff0c\\u4e59\\u65b9\\u5e94\\u65e0\\u6761\\u4ef6\\u66f4\\u6b63\\uff0c\\u800c\\u4e0d\\u53e6\\u5916\\u6536\\u8d39\\uff0c\\u5e76\\u5bf9\\u56e0\\u6b64\\u800c\\u5bf9\\u7532\\u65b9\\u9020\\u6210\\u7684\\u635f\\u5931\\u627f\\u62c5\\u8d54\\u507f\\u8d23\\u4efb\\uff0c\\u8d54\\u507f\\u4ee5\\u9644\\u4ef6A\\u6240\\u8f7d\\u660e\\u7684\\u8be5\\u9879\\u670d\\u52a1\\u5185\\u5bb9\\u5bf9\\u5e94\\u4e4b\\u670d\\u52a1\\u8d39\\u4e3a\\u9650\\u3002\\u82e5\\u56e0\\u7532\\u65b9\\u539f\\u56e0\\u9020\\u6210\\u5de5\\u4f5c\\u7684\\u5ef6\\u8bef\\uff0c\\u5c06\\u7531\\u7532\\u65b9\\u627f\\u62c5\\u76f8\\u5e94\\u7684\\u635f\\u5931\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">3\\u3001\\u4e59\\u65b9\\u7684\\u670d\\u52a1\\u627f\\u8bfa\\uff1a<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">&nbsp; &nbsp; 1\\uff09\\u4e59\\u65b9\\u63a5\\u5230\\u7532\\u65b9\\u901a\\u8fc7\\u7535\\u8bdd\\u3001\\u4fe1\\u51fd\\u4f20\\u771f\\u3001\\u7535\\u5b50\\u90ae\\u4ef6\\u3001\\u7f51\\u4e0a\\u63d0\\u4ea4\\u7b49\\u65b9\\u5f0f\\u63d0\\u51fa\\u5173\\u4e8e\\u9644\\u4ef6A\\u6240\\u5217\\u670d\\u52a1\\u7684\\u8bf7\\u6c42\\u540e\\uff0c\\u5728\\u4e24\\u4e2a\\u6709\\u6548\\u5de5\\u4f5c\\u65e5\\u5185\\u7ed9\\u4e88\\u54cd\\u5e94\\u5e76\\u63d0\\u4f9b\\u670d\\u52a1\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">&nbsp; &nbsp; 2\\uff09\\u4e59\\u65b9\\u63d0\\u4f9b\\u7ed9\\u7532\\u65b9\\u7684\\u670d\\u52a1\\uff0c\\u5fc5\\u987b\\u6309\\u7167\\u5408\\u540c\\u9644\\u4ef6A\\u89c4\\u5b9a\\u7684\\u6807\\u51c6\\u8fdb\\u884c\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">4\\u3001\\u4ea7\\u54c1\\u660e\\u7ec6\\uff1a<\\/span><\\/p>\\n<table style=\\\"border-collapse: collapse; width: 100%;\\\" border=\\\"1\\\" data-wk-table-tag=\\\"table\\\">\\n<tbody>\\n<tr data-wk-table-tr-tag=\\\"header\\\">\\n<td data-wk-table-td-tag=\\\"name\\\">\\u4ea7\\u54c1\\u540d\\u79f0<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u4ea7\\u54c1\\u7c7b\\u522b<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u5355\\u4f4d<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6807\\u51c6\\u4ef7\\u683c<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u552e\\u4ef7<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6570\\u91cf<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6298\\u6263<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u5408\\u8ba1<\\/td>\\n<\\/tr>\\n<tr data-wk-table-tr-tag=\\\"value\\\">\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.name\\\">{\\u4ea7\\u54c1\\u540d\\u79f0}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.category_id\\\">{\\u4ea7\\u54c1\\u7c7b\\u522b}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.unit\\\">{\\u5355\\u4f4d}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.price\\\">{\\u6807\\u51c6\\u4ef7\\u683c}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.sales_price\\\">{\\u552e\\u4ef7}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.count\\\">{\\u6570\\u91cf}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.discount\\\">{\\u6298\\u6263}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.subtotal\\\">{\\u5408\\u8ba1}<\\/span><\\/td>\\n<\\/tr>\\n<\\/tbody>\\n<\\/table>\\n<p>\\u6574\\u5355\\u6298\\u6263\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"contract.discount_rate\\\">{\\u6574\\u5355\\u6298\\u6263}<\\/span>&nbsp;&nbsp;&nbsp; \\u4ea7\\u54c1\\u603b\\u91d1\\u989d\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"contract.total_price\\\">{\\u4ea7\\u54c1\\u603b\\u91d1\\u989d}<\\/span><\\/p>\\n<p><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\"><strong>\\u4e8c\\u3001\\u670d\\u52a1\\u8d39\\u7684\\u652f\\u4ed8<\\/strong><\\/span><\\/p>\\n<p><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">1\\u3001\\u670d\\u52a1\\u8d39\\u603b\\u91d1\\u989d\\u4e3a<span style=\\\"text-decoration: underline;\\\">&nbsp;&nbsp;&nbsp; <span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"contract.money\\\">{\\u5408\\u540c\\u91d1\\u989d}<\\/span> &nbsp;&nbsp; <\\/span><\\/span><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">\\u5143\\u4eba\\u6c11\\u5e01(\\u4eba\\u6c11\\u5e01\\u5927\\u5199\\uff1a<span style=\\\"text-decoration: underline;\\\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<\\/span> \\u5143\\u6574)\\u3002<span style=\\\"text-decoration: underline;\\\"><br \\/><\\/span><\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">2\\u3001\\u672c\\u8d39\\u7528\\u7ed3\\u6784\\u4ec5\\u9650\\u65bc\\u9644\\u4ef6A\\u4e2d\\u5217\\u660e\\u7684\\u5de5\\u4f5c\\u3002\\u5982\\u679c\\u7532\\u65b9\\u8981\\u6c42\\u6269\\u5927\\u9879\\u76ee\\u8303\\u56f4\\uff0c\\u6216\\u56e0\\u7532\\u65b9\\u6539\\u53d8\\u5df2\\u7ecf\\u8bae\\u5b9a\\u7684\\u9879\\u76ee\\u5185\\u5bb9\\u5bfc\\u81f4\\u4e59\\u65b9\\u9700\\u91cd\\u590d\\u8fdb\\u884c\\u9879\\u76ee\\u6b65\\u9aa4\\uff0c\\u4e59\\u65b9\\u5c06\\u9700\\u8981\\u91cd\\u65b0\\u8bc4\\u4f30\\u4e0a\\u8ff0\\u8d39\\u7528\\u7ed3\\u6784\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">3\\u3001\\u7532\\u4e59\\u53cc\\u65b9\\u4e00\\u81f4\\u540c\\u610f\\u9879\\u76ee\\u670d\\u52a1\\u8d39\\u6309\\u4e00\\u6b21\\u6027\\u4ee5\\u4eba\\u6c11\\u5e01\\u5f62\\u5f0f\\u652f\\u4ed8\\u3002\\u670d\\u52a1\\u5b8c\\u6210\\u540e\\uff0c\\u7532\\u65b9\\u5c06\\u5728\\u9a8c\\u6536\\u786e\\u8ba4\\u670d\\u52a1\\u5b8c\\u6210\\u5408\\u683c\\uff0c\\u5e76\\u4e14\\u4e59\\u65b9\\u53d1\\u51fa\\u8be5\\u9636\\u6bb5\\u5de5\\u4f5c\\u7684\\u8d39\\u7528\\u8d26\\u5355\\u53ca\\u6b63\\u5f0f\\u6709\\u6548\\u7684\\u7a0e\\u52a1\\u53d1\\u7968\\u540e3\\u4e2a\\u5de5\\u4f5c\\u65e5\\u5185\\uff0c\\u5411\\u4e59\\u65b9\\u652f\\u4ed8\\u7ea6\\u5b9a\\u7684\\u8d39\\u7528\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">4\\u3001\\u6709\\u5173\\u53d1\\u7968\\u65b9\\u9762\\u7684\\u4efb\\u4f55\\u95ee\\u9898\\uff0c\\u7532\\u65b9\\u5e94\\u5728\\u6536\\u5230\\u53d1\\u7968\\u540e\\u53ca\\u65f6\\u4e66\\u9762\\u901a\\u77e5\\u4e59\\u65b9\\uff0c\\u4fbf\\u4e59\\u65b9\\u53ca\\u65f6\\u4f5c\\u51fa\\u89e3\\u91ca\\u6216\\u89e3\\u51b3\\u95ee\\u9898\\uff0c\\u4ee5\\u4f7f\\u7532\\u65b9\\u80fd\\u6309\\u65f6\\u4ed8\\u6b3e\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">5\\u3001\\u4e59\\u65b9\\u5c06\\u81ea\\u884c\\u627f\\u62c5\\u9879\\u76ee\\u5b9e\\u65bd\\u8303\\u56f4\\u5185\\u5408\\u7406\\u7684\\u5dee\\u65c5\\u8d39\\u7528\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">6\\u3001\\u4e59\\u65b9\\u540c\\u610f\\u514d\\u9664\\u9879\\u76ee\\u6742\\u8d39\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">7\\u3001\\u672c\\u534f\\u8bae\\u6709\\u6548\\u671f\\u4e3a\\uff1a<span style=\\\"text-decoration: underline;\\\"> &nbsp;&nbsp; <span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"contract.start_time\\\">{\\u5408\\u540c\\u5f00\\u59cb\\u65f6\\u95f4}<\\/span><\\/span><span style=\\\"text-decoration: underline;\\\"> &nbsp; &nbsp; <\\/span>&nbsp; <\\/span>\\u8d77 <span style=\\\"text-decoration: underline;\\\"> &nbsp;&nbsp; <\\/span><span style=\\\"text-decoration: underline;\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"contract.end_time\\\">{\\u5408\\u540c\\u5230\\u671f\\u65f6\\u95f4}<\\/span> &nbsp; &nbsp;&nbsp;<\\/span> \\u6b62<\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\"><strong>\\u4e09\\u3001\\u670d\\u52a1\\u7684\\u53d8\\u66f4<\\/strong><\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">\\u7532\\u65b9\\u53ef\\u4ee5\\u63d0\\u524d\\u4e2a\\u5de5\\u4f5c\\u65e5\\u4ee5\\u4e66\\u9762\\u5f62\\u5f0f\\u8981\\u6c42\\u53d8\\u66f4\\u6216\\u589e\\u52a0\\u6240\\u63d0\\u4f9b\\u7684\\u670d\\u52a1\\u3002\\u8be5\\u7b49\\u53d8\\u66f4\\u6700\\u7ec8\\u5e94\\u7531\\u53cc\\u65b9\\u4e92\\u76f8\\u5546\\u5b9a\\u8ba4\\u53ef\\uff0c\\u5176\\u4e2d\\u5305\\u62ec\\u4e0e\\u8be5\\u7b49\\u53d8\\u66f4\\u6709\\u5173\\u7684\\u4efb\\u4f55\\u8d39\\u7528\\u8c03\\u6574\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\"><strong>\\u56db\\u3001\\u4e89\\u8bae\\u5904\\u7406<\\/strong><\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">\\u7532\\u4e59\\u53cc\\u65b9\\u5982\\u5bf9\\u534f\\u8bae\\u6761\\u6b3e\\u89c4\\u5b9a\\u7684\\u7406\\u89e3\\u6709\\u5f02\\u8bae\\uff0c\\u6216\\u8005\\u5bf9\\u4e0e\\u534f\\u8bae\\u6709\\u5173\\u7684\\u4e8b\\u9879\\u53d1\\u751f\\u4e89\\u8bae\\uff0c\\u53cc\\u65b9\\u5e94\\u672c\\u7740\\u53cb\\u597d\\u5408\\u4f5c\\u7684\\u7cbe\\u795e\\u8fdb\\u884c\\u534f\\u5546\\u3002\\u534f\\u5546\\u4e0d\\u80fd\\u89e3\\u51b3\\u7684\\uff0c\\u4efb\\u4f55\\u4e00\\u65b9\\u53ef\\u5411\\u4ef2\\u88c1\\u59d4\\u5458\\u4f1a\\u63d0\\u8d77\\u4ef2\\u88c1\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\"><strong>\\u4e94\\u3001\\u5176\\u4ed6<\\/strong><\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">1\\u3001\\u672c\\u5408\\u540c\\u4e2d\\u6240\\u7528\\u7684\\u6807\\u9898\\u4ec5\\u4e3a\\u65b9\\u4fbf\\u800c\\u8bbe\\uff0c\\u800c\\u4e0d\\u5f71\\u54cd\\u5bf9\\u672c\\u5408\\u540c\\u7684\\u89e3\\u91ca\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">2\\u3001\\u9644\\u4ef6A\\u662f\\u672c\\u5408\\u540c\\u4e0d\\u53ef\\u5206\\u5272\\u7684\\u7ec4\\u6210\\u90e8\\u5206\\uff0c\\u4e0e\\u672c\\u5408\\u540c\\u5177\\u6709\\u540c\\u7b49\\u6cd5\\u5f8b\\u6548\\u529b\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">3\\u3001\\u672c\\u5408\\u540c\\u672a\\u5c3d\\u4e8b\\u5b9c\\uff0c\\u7531\\u7532\\u4e59\\u53cc\\u65b9\\u534f\\u5546\\u540e\\u4ea7\\u751f\\u4e66\\u9762\\u6587\\u4ef6\\uff0c\\u4f5c\\u4e3a\\u672c\\u5408\\u540c\\u7684\\u8865\\u5145\\u6761\\u6b3e\\uff0c\\u5177\\u5907\\u4e0e\\u672c\\u5408\\u540c\\u540c\\u7b49\\u6cd5\\u5f8b\\u6548\\u529b\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">4\\u3001\\u5bf9\\u672c\\u5408\\u540c\\u5185\\u5bb9\\u7684\\u4efb\\u4f55\\u4fee\\u6539\\u548c\\u53d8\\u66f4\\u9700\\u8981\\uff0c\\u7528\\u4e66\\u9762\\u5f62\\u5f0f\\uff0c\\u5e76\\u7ecf\\u53cc\\u65b9\\u786e\\u8ba4\\u540e\\u751f\\u6548\\u3002<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">(\\u4ee5\\u4e0b\\u65e0\\u6b63\\u6587)<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\">&nbsp;<\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">\\u7532\\u65b9\\uff08\\u7b7e\\u7ae0\\uff09&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; \\u4e59\\u65b9\\uff08\\u7b7e\\u7ae0\\uff09<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">\\u4ee3\\u8868\\u7b7e\\u5b57\\uff1a&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\\u4ee3\\u8868\\u7b7e\\u5b57\\uff1a<\\/span><\\/p>\\n<p style=\\\"line-height: 1.75;\\\"><span style=\\\"font-size: 14px; font-family: simsun, serif;\\\">\\u65e5\\u671f\\uff1a&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\\u65e5\\u671f\\uff1a<\\/span><\\/p>\"}', 1617868741, 1617868741);
INSERT INTO `5kcrm_admin_printing` VALUES (2, 5, '李四', '合同订单打印模板', 6, '{\"data\":\"<p style=\\\"text-align: center;\\\"><span style=\\\"font-size: 36px; font-family: simsun, serif;\\\">\\u5408\\u540c\\u8ba2\\u5355<\\/span><\\/p>\\n<p style=\\\"text-align: right;\\\">\\u5408\\u540c\\u7f16\\u53f7\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.num\\\">{\\u5408\\u540c\\u7f16\\u53f7}<\\/span><\\/p>\\n<table style=\\\"border-collapse: collapse; width: 100%; height: 95px;\\\" border=\\\"1\\\">\\n<tbody>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u5408\\u540c\\u540d\\u79f0\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.name\\\">{\\u5408\\u540c\\u540d\\u79f0}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u5ba2\\u6237\\u540d\\u79f0\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--customer\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"customer.name\\\">{\\u5ba2\\u6237\\u540d\\u79f0}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u5408\\u540c\\u603b\\u91d1\\u989d\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.money\\\">{\\u5408\\u540c\\u91d1\\u989d}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u8d1f\\u8d23\\u4eba\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.owner_user_id\\\">{\\u8d1f\\u8d23\\u4eba}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u76f8\\u5173\\u5546\\u673a\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.business_id\\\">{\\u5546\\u673a\\u540d\\u79f0}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u7b7e\\u8ba2\\u65f6\\u95f4\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.order_date\\\">{\\u4e0b\\u5355\\u65f6\\u95f4}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u5f00\\u59cb\\u65f6\\u95f4\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.start_time\\\">{\\u5408\\u540c\\u5f00\\u59cb\\u65f6\\u95f4}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u7ed3\\u675f\\u65f6\\u95f4\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.end_time\\\">{\\u5408\\u540c\\u5230\\u671f\\u65f6\\u95f4}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u5907\\u6ce8\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--contract\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.remark\\\">{\\u5907\\u6ce8}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">&nbsp;<\\/td>\\n<\\/tr>\\n<\\/tbody>\\n<\\/table>\\n<p>&nbsp;<\\/p>\\n<p>&nbsp;<\\/p>\\n<p>\\u4ea7\\u54c1\\u660e\\u7ec6\\uff1a<\\/p>\\n<table style=\\\"border-collapse: collapse; width: 100%;\\\" border=\\\"1\\\" data-wk-table-tag=\\\"table\\\">\\n<tbody>\\n<tr data-wk-table-tr-tag=\\\"header\\\">\\n<td data-wk-table-td-tag=\\\"name\\\">\\u4ea7\\u54c1\\u540d\\u79f0<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u4ea7\\u54c1\\u7c7b\\u522b<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u5355\\u4f4d<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6807\\u51c6\\u4ef7\\u683c<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u552e\\u4ef7<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6570\\u91cf<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6298\\u6263<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u5408\\u8ba1<\\/td>\\n<\\/tr>\\n<tr data-wk-table-tr-tag=\\\"value\\\">\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.name\\\">{\\u4ea7\\u54c1\\u540d\\u79f0}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.category_id\\\">{\\u4ea7\\u54c1\\u7c7b\\u522b}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.unit\\\">{\\u5355\\u4f4d}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.price\\\">{\\u6807\\u51c6\\u4ef7\\u683c}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.sales_price\\\">{\\u552e\\u4ef7}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.count\\\">{\\u6570\\u91cf}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.discount\\\">{\\u6298\\u6263}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"true\\\" data-wk-table-value-tag=\\\"product.subtotal\\\">{\\u5408\\u8ba1}<\\/span><\\/td>\\n<\\/tr>\\n<\\/tbody>\\n<\\/table>\\n<p style=\\\"text-align: right;\\\">\\u4ea7\\u54c1\\u603b\\u91d1\\u989d\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"contract.total_price\\\">{\\u4ea7\\u54c1\\u603b\\u91d1\\u989d}<\\/span><\\/p>\\n<p>&nbsp;<\\/p>\\n<p>&nbsp;<\\/p>\\n<p>&nbsp;<\\/p>\\n<p>&nbsp;<\\/p>\\n<p>&nbsp;<\\/p>\\n<p>&nbsp;<\\/p>\\n<p>&nbsp;<\\/p>\"}', 1617869167, 1617869167);
INSERT INTO `5kcrm_admin_printing` VALUES (3, 5, '李四', '商机打印模板', 5, '{\"data\":\"<p style=\\\"text-align: center; line-height: 1; margin-bottom: 15px;\\\"><span style=\\\"font-size: 36px; font-family: simsun, serif;\\\">***\\u6709\\u9650\\u516c\\u53f8<\\/span><\\/p>\\n<p style=\\\"text-align: center; line-height: 1; margin-bottom: 15px;\\\"><span style=\\\"font-size: 36px; font-family: simsun, serif;\\\">\\u5546\\u673a<\\/span><\\/p>\\n<table style=\\\"border-collapse: collapse; width: 100%;\\\" border=\\\"1\\\">\\n<tbody>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%;\\\">\\u5546\\u673a\\u540d\\u79f0\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--business\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"business.name\\\">{\\u5546\\u673a\\u540d\\u79f0}<\\/span><\\/td>\\n<td style=\\\"width: 50%;\\\">\\u5ba2\\u6237\\u540d\\u79f0\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--customer\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"customer.name\\\">{\\u5ba2\\u6237\\u540d\\u79f0}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%;\\\">\\u5546\\u673a\\u72b6\\u6001\\u7ec4\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--business\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"business.type_id\\\">{\\u5546\\u673a\\u72b6\\u6001\\u7ec4}<\\/span><\\/td>\\n<td style=\\\"width: 50%;\\\">\\u5546\\u673a\\u9636\\u6bb5\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--business\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"business.status_id\\\">{\\u5546\\u673a\\u9636\\u6bb5}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%;\\\">\\u9884\\u8ba1\\u6210\\u4ea4\\u65f6\\u95f4\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--business\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"business.deal_date\\\">{\\u9884\\u8ba1\\u6210\\u4ea4\\u65e5\\u671f}<\\/span><\\/td>\\n<td style=\\\"width: 50%;\\\">\\u5546\\u673a\\u91d1\\u989d\\uff08\\u5143\\uff09\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--business\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"business.money\\\">{\\u5546\\u673a\\u91d1\\u989d}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%;\\\">\\u8d1f\\u8d23\\u4eba\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--business\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"business.owner_user_id\\\">{\\u8d1f\\u8d23\\u4eba}<\\/span><\\/td>\\n<td style=\\\"width: 50%;\\\">&nbsp;<\\/td>\\n<\\/tr>\\n<\\/tbody>\\n<\\/table>\\n<p>&nbsp;<\\/p>\\n<p>&nbsp;<\\/p>\\n<p>\\u4ea7\\u54c1\\u660e\\u7ec6\\uff1a<\\/p>\\n<table style=\\\"border-collapse: collapse; width: 100%;\\\" border=\\\"1\\\" data-wk-table-tag=\\\"table\\\">\\n<tbody>\\n<tr data-wk-table-tr-tag=\\\"header\\\">\\n<td data-wk-table-td-tag=\\\"name\\\">\\u4ea7\\u54c1\\u540d\\u79f0<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u4ea7\\u54c1\\u7c7b\\u522b<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u5355\\u4f4d<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6807\\u51c6\\u4ef7\\u683c<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u552e\\u4ef7<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6570\\u91cf<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u6298\\u6263<\\/td>\\n<td data-wk-table-td-tag=\\\"name\\\">\\u5408\\u8ba1<\\/td>\\n<\\/tr>\\n<tr data-wk-table-tr-tag=\\\"value\\\">\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.name\\\">{\\u4ea7\\u54c1\\u540d\\u79f0}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.category_id\\\">{\\u4ea7\\u54c1\\u7c7b\\u522b}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.unit\\\">{\\u5355\\u4f4d}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.price\\\">{\\u6807\\u51c6\\u4ef7\\u683c}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.sales_price\\\">{\\u552e\\u4ef7}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.count\\\">{\\u6570\\u91cf}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.discount\\\">{\\u6298\\u6263}<\\/span><\\/td>\\n<td data-wk-table-td-tag=\\\"value\\\"><span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-table-value-tag=\\\"product.subtotal\\\">{\\u5408\\u8ba1}<\\/span><\\/td>\\n<\\/tr>\\n<\\/tbody>\\n<\\/table>\\n<p>\\u6574\\u70b9\\u6298\\u6263\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"business.discount_rate\\\">{\\u6574\\u5355\\u6298\\u6263}<\\/span> &nbsp;&nbsp;&nbsp;&nbsp; \\u4ea7\\u54c1\\u603b\\u91d1\\u989d\\uff08\\u5143\\uff09\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--product\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"business.total_price\\\">{\\u4ea7\\u54c1\\u603b\\u91d1\\u989d}<\\/span><\\/p>\\n<p>&nbsp;<\\/p>\"}', 1617869397, 1617869397);
INSERT INTO `5kcrm_admin_printing` VALUES (4, 5, '李四', '回款打印模板', 7, '{\"data\":\"<p style=\\\"text-align: center; line-height: 1; margin-bottom: 15px;\\\"><span style=\\\"font-size: 36px; font-family: simsun, serif;\\\">***\\u6709\\u9650\\u516c\\u53f8<\\/span><\\/p>\\n<p style=\\\"text-align: center; line-height: 1; margin-bottom: 15px;\\\"><span style=\\\"font-size: 36px; font-family: simsun, serif;\\\">\\u56de\\u6b3e\\u5355<\\/span><\\/p>\\n<table style=\\\"border-collapse: collapse; width: 100%; height: 95px;\\\" border=\\\"1\\\">\\n<tbody>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u5ba2\\u6237\\u540d\\u79f0\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--receivables\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"receivables.customer_id\\\">{\\u5ba2\\u6237\\u540d\\u79f0}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u56de\\u6b3e\\u7f16\\u53f7\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--receivables\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"receivables.number\\\">{\\u56de\\u6b3e\\u7f16\\u53f7}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u56de\\u6b3e\\u65e5\\u671f\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--receivables\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"receivables.return_time\\\">{\\u56de\\u6b3e\\u65e5\\u671f}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u56de\\u6b3e\\u65b9\\u5f0f\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--receivables\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"receivables.return_type\\\">{\\u56de\\u6b3e\\u65b9\\u5f0f}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u56de\\u6b3e\\u671f\\u6570\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--receivables\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"receivables.plan_id\\\">{\\u671f\\u6570}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u56de\\u6b3e\\u91d1\\u989d\\uff08\\u5143\\uff09\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--receivables\\\" contenteditable=\\\"false\\\" data-wk-tag=\\\"receivables.money\\\">{\\u56de\\u6b3e\\u91d1\\u989d}<\\/span><\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\">\\u8d1f\\u8d23\\u4eba\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--receivables\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"receivables.owner_user_id\\\">{\\u8d1f\\u8d23\\u4eba}<\\/span><\\/td>\\n<td style=\\\"width: 50%; height: 19px;\\\">&nbsp;<\\/td>\\n<\\/tr>\\n<tr style=\\\"height: 25px;\\\">\\n<td style=\\\"width: 50%; height: 19px;\\\" colspan=\\\"2\\\">\\u5907\\u6ce8\\uff1a<span class=\\\"wk-print-tag-wukong wk-tiny-color--receivables\\\" contenteditable=\\\"true\\\" data-wk-tag=\\\"receivables.remark\\\">{\\u5907\\u6ce8}<\\/span><\\/td>\\n<\\/tr>\\n<\\/tbody>\\n<\\/table>\\n<p>&nbsp;<\\/p>\"}', 1617869632, 1617869632);

-- ----------------------------
-- Table structure for 5kcrm_admin_printing_data
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_printing_data`;
CREATE TABLE `5kcrm_admin_printing_data`  (
  `data_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '文件key',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '文件内容',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `type` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型：word、pdf',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`data_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '打印文件表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_printing_data
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_rule
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_rule`;
CREATE TABLE `5kcrm_admin_rule`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `types` tinyint(2) NOT NULL DEFAULT 0 COMMENT '0系统设置1工作台2客户管理3项目管理4人力资源5财务管理6商业智能(客戶)7商业智能(办公)',
  `title` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '定义',
  `level` tinyint(5) NOT NULL DEFAULT 0 COMMENT '级别。1模块,2控制器,3操作',
  `pid` int(11) NULL DEFAULT 0 COMMENT '父id，默认0',
  `status` tinyint(3) NULL DEFAULT 1 COMMENT '状态，1启用，0禁用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 226 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '权限规则表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_rule
-- ----------------------------
INSERT INTO `5kcrm_admin_rule` VALUES (1, 2, '全部', 'crm', 1, 0, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (2, 2, '线索管理', 'leads', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (3, 2, '新建', 'save', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (4, 2, '编辑', 'update', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (5, 2, '查看列表', 'index', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (6, 2, '查看详情', 'read', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (7, 2, '导入', 'excelImport', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (8, 2, '导出', 'excelExport', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (9, 2, '刪除', 'delete', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (10, 2, '客户管理', 'customer', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (11, 2, '新建', 'save', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (12, 2, '编辑', 'update', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (13, 2, '查看列表', 'index', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (14, 2, '查看详情', 'read', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (15, 2, '导入', 'excelImport', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (16, 2, '导出', 'excelExport', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (17, 2, '刪除', 'delete', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (18, 2, '转移', 'transfer', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (19, 2, '放入公海', 'putInPool', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (20, 2, '锁定/解锁', 'lock', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (21, 2, '编辑团队成员', 'teamSave', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (22, 2, '联系人管理', 'contacts', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (23, 2, '新建', 'save', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (24, 2, '编辑', 'update', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (25, 2, '查看列表', 'index', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (26, 2, '查看详情', 'read', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (27, 2, '刪除', 'delete', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (28, 2, '转移', 'transfer', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (34, 2, '商机管理', 'business', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (35, 2, '新建', 'save', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (36, 2, '编辑', 'update', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (37, 2, '查看列表', 'index', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (38, 2, '查看详情', 'read', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (39, 2, '刪除', 'delete', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (40, 2, '转移', 'transfer', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (41, 2, '编辑团队成员', 'teamSave', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (42, 2, '合同管理', 'contract', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (43, 2, '新建', 'save', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (44, 2, '编辑', 'update', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (45, 2, '查看列表', 'index', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (46, 2, '查看详情', 'read', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (47, 2, '刪除', 'delete', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (48, 2, '转移', 'transfer', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (49, 2, '编辑团队成员', 'teamSave', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (50, 2, '回款管理', 'receivables', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (51, 2, '新建', 'save', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (52, 2, '编辑', 'update', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (53, 2, '查看列表', 'index', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (54, 2, '查看详情', 'read', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (55, 2, '刪除', 'delete', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (56, 2, '产品管理', 'product', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (57, 2, '新建', 'save', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (58, 2, '编辑', 'update', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (59, 2, '查看列表', 'index', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (60, 2, '查看详情', 'read', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (61, 2, '上架/下架', 'status', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (62, 6, '商业智能', 'bi', 1, 0, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (63, 6, '员工客户分析', 'customer', 2, 62, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (64, 6, '查看', 'read', 3, 63, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (65, 6, '销售漏斗分析', 'business', 2, 62, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (66, 6, '查看', 'read', 3, 65, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (67, 6, '回款统计', 'receivables', 2, 62, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (68, 6, '查看', 'read', 3, 67, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (69, 6, '产品分析', 'product', 2, 62, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (70, 6, '查看', 'read', 3, 69, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (71, 6, '业绩目标完成情况', 'achievement', 2, 62, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (72, 6, '查看', 'read', 3, 71, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (73, 2, '转移', 'transfer', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (74, 2, '转化', 'transform', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (75, 6, '员工业绩分析', 'contract', 2, 62, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (76, 6, '查看', 'read', 3, 75, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (77, 6, '客户画像分析', 'portrait', 2, 62, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (78, 6, '查看', 'read', 3, 77, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (79, 6, '排行榜', 'ranking', 2, 62, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (80, 6, '查看', 'read', 3, 79, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (81, 2, '导入', 'excelImport', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (82, 2, '导出', 'excelExport', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (83, 2, '导入', 'excelImport', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (84, 2, '导出', 'excelExport', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (86, 3, '项目管理', 'work', 1, 0, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (87, 3, '项目', 'work', 2, 86, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (88, 3, '任务', 'task', 2, 86, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (89, 3, '项目设置', 'update', 3, 87, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (90, 3, '任务列表', 'taskClass', 2, 86, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (91, 3, '新建任务列表', 'save', 3, 90, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (92, 3, '编辑任务列表', 'update', 3, 90, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (93, 3, '删除任务列表', 'delete', 3, 90, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (94, 3, '创建', 'save', 3, 88, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (95, 7, '办公分析', 'oa', 2, 140, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (96, 7, '查看', 'read', 3, 95, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (104, 2, '成交状态', 'deal_status', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (105, 0, '全部', 'admin', 1, 0, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (106, 0, '企业首页', 'system', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (107, 0, '查看', 'index', 3, 106, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (108, 0, '编辑', 'save', 3, 106, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (109, 0, '应用管理', 'configset', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (110, 0, '查看', 'index', 3, 109, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (111, 0, '停用/启用', 'update', 3, 109, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (112, 0, '员工与部门管理', 'users', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (113, 0, '部门/员工查看', 'index', 3, 112, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (114, 0, '员工新建', 'save', 3, 112, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (115, 0, '员工禁用/激活', 'enables', 3, 112, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (116, 0, '员工操作', 'update', 3, 112, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (117, 0, '部门新建', 'structures_save', 3, 112, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (118, 0, '部门编辑', 'structures_update', 3, 112, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (119, 0, '部门删除', 'structures_delete', 3, 112, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (120, 0, '角色权限管理', 'groups', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (121, 0, '角色权限设置', 'update', 3, 120, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (122, 0, '工作台设置', 'oa', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (123, 0, '办公审批管理', 'examine', 3, 122, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (124, 0, '审批流程管理', 'examine_flow', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (125, 0, '审批流程管理', 'index', 3, 124, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (126, 0, '客户管理设置', 'crm', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (127, 0, '自定义字段设置', 'field', 3, 126, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (128, 0, '客户公海规则', 'pool', 3, 126, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (129, 0, '业务参数设置', 'setting', 3, 126, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (130, 0, '业绩目标设置', 'achievement', 3, 126, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (131, 1, '全部', 'oa', 1, 0, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (132, 1, '通讯录', 'addresslist', 2, 131, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (133, 1, '查看列表', 'index', 3, 132, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (134, 1, '公告', 'announcement', 2, 131, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (135, 1, '新建', 'save', 3, 134, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (136, 1, '编辑', 'update', 3, 134, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (137, 1, '删除', 'delete', 3, 134, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (138, 0, '项目管理设置', 'work', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (139, 0, '项目管理', 'work', 3, 138, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (140, 7, '商业智能', 'bi', 1, 0, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (141, 9, '全部', 'work', 1, 0, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (142, 9, '项目管理', 'work', 2, 141, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (143, 9, '项目创建', 'save', 3, 142, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (145, 2, '查看列表', 'index', 3, 144, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (146, 2, '导出', 'excelExport', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (147, 2, '导出', 'excelExport', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (148, 2, '合同作废', 'cancel', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (149, 2, '删除', 'delete', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (151, 0, '登录日志', 'loginLog', 3, 220, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (153, 2, '转移', 'transfer', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (154, 0, '其他设置', 'other_rule', 2, 105, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (155, 0, '日志欢迎语', 'welcome', 3, 154, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (156, 0, '设置欢迎语', 'setWelcome', 3, 154, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (157, 0, '日志规则', 'workLogRule', 3, 154, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (158, 0, '设置日志规则', 'setWorkLogRule', 3, 154, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (159, 0, '自定义打印模板', 'printing', 3, 126, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (160, 2, '关注', 'star', 3, 2, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (161, 2, '关注', 'star', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (162, 2, '关注', 'star', 3, 22, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (163, 2, '关注', 'star', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (164, 2, '附近客户', 'nearby', 3, 10, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (165, 2, '发票管理', 'invoice', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (166, 2, '列表', 'index', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (167, 2, '创建', 'save', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (168, 2, '详情', 'read', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (169, 2, '编辑', 'update', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (170, 2, '删除', 'delete', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (171, 2, '转移', 'transfer', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (172, 2, '开票', 'setInvoice', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (173, 2, '重置开票状态', 'resetInvoiceStatus', 3, 165, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (174, 2, '跟进记录', 'activity', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (175, 2, '列表', 'index', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (176, 2, '详情', 'read', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (177, 2, '创建', 'save', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (178, 2, '编辑', 'update', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (179, 2, '删除', 'delete', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (180, 3, '项目设置', 'setWork', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (181, 3, '项目导出', 'excelExport', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (182, 3, '新建任务列表', 'saveTaskClass', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (183, 3, '编辑任务列表', 'updateTaskClass', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (184, 3, '移动任务列表', 'updateClassOrder', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (185, 3, '删除任务列表', 'deleteTaskClass', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (186, 3, '新建任务', 'saveTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (187, 3, '完成任务', 'setTaskStatus', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (188, 3, '编辑任务标题', 'setTaskTitle', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (189, 3, '编辑任务描述', 'setTaskDescription', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (190, 3, '分配任务', 'setTaskMainUser', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (191, 3, '设置任务时间', 'setTaskTime', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (192, 3, '设置任务标签', 'setTaskLabel', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (193, 3, '添加任务参与人', 'setTaskOwnerUser', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (194, 3, '设置任务优先级', 'setTaskPriority', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (195, 3, '移动任务', 'setTaskOrder', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (196, 3, '归档任务', 'archiveTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (197, 3, '删除任务', 'deleteTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (198, 3, '彻底删除任务', 'cleanTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (199, 3, '任务添加附件', 'uploadTaskFile', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (200, 3, '任务删除附件', 'deleteTaskFile', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (201, 3, '项目导入', 'excelImport', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (202, 3, '新建子任务', 'addChildTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (203, 3, '编辑子任务', 'updateChildTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (204, 3, '删除子任务', 'deleteChildTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (205, 3, '恢复任务', 'restoreTask', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (206, 3, '关联业务', 'saveTaskRelation', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (207, 3, '完成子任务', 'setChildTaskStatus', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (208, 0, '初始化', 'initialize', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (209, 0, '初始化数据', 'update', 3, 208, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (210, 2, '打印', 'print', 3, 34, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (211, 2, '打印', 'print', 3, 42, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (212, 2, '打印', 'print', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (213, 2, '导出', 'excelexport', 3, 50, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (214, 2, '转移', 'transfer', 3, 56, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (215, 2, '回访管理', 'visit', 2, 1, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (216, 2, '新建', 'save', 3, 215, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (217, 2, '编辑', 'update', 3, 215, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (218, 2, '查看列表', 'index', 3, 215, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (219, 2, '查看详情', 'read', 3, 215, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (220, 0, '系统日志', 'adminLog', 2, 105, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (221, 0, '数据操作日志', 'actionRecord', 3, 220, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (222, 0, '系统操作日志', 'systemLog', 3, 220, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (223, 3, '管理参与人权限', 'manageTaskOwnerUser', 4, 86, 0);
INSERT INTO `5kcrm_admin_rule` VALUES (224, 2, '导入', 'excelImport', 3, 174, 1);
INSERT INTO `5kcrm_admin_rule` VALUES (225, 2, '导出', 'excelExport', 3, 174, 1);

-- ----------------------------
-- Table structure for 5kcrm_admin_scene
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_scene`;
CREATE TABLE `5kcrm_admin_scene`  (
  `scene_id` int(10) NOT NULL AUTO_INCREMENT,
  `types` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '分类',
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '场景名称',
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `order_id` int(10) NOT NULL DEFAULT 1 COMMENT '排序ID',
  `data` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '属性值',
  `is_hide` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1隐藏',
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1系统0自定义',
  `bydata` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '系统参数',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`scene_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 34 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '场景' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_scene
-- ----------------------------
INSERT INTO `5kcrm_admin_scene` VALUES (1, 'crm_customer', '我负责的客户', 0, 0, '', 0, 1, 'me', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (2, 'crm_customer', '我参与的客户', 0, 0, '', 0, 1, 'mePart', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (3, 'crm_customer', '下属负责的客户', 0, 0, '', 0, 1, 'sub', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (4, 'crm_customer', '全部客户', 0, 0, '', 0, 1, 'all', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (5, 'crm_leads', '我负责的线索', 0, 0, '', 0, 1, 'me', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (6, 'crm_leads', '下属的线索', 0, 0, '', 0, 1, 'sub', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (7, 'crm_leads', '全部线索', 0, 0, '', 0, 1, 'all', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (8, 'crm_contacts', '我负责的联系人', 0, 0, '', 0, 1, 'me', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (9, 'crm_contacts', '下属负责的联系人', 0, 0, '', 0, 1, 'sub', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (10, 'crm_contacts', '全部联系人', 0, 0, '', 0, 1, 'all', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (11, 'crm_business', '我负责的商机', 0, 0, '', 0, 1, 'me', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (12, 'crm_business', '我参与的商机', 0, 0, '', 0, 1, 'mePart', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (13, 'crm_business', '下属负责的商机', 0, 0, '', 0, 1, 'sub', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (14, 'crm_business', '全部商机', 0, 0, '', 0, 1, 'all', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (15, 'crm_contract', '我负责的合同', 0, 0, '', 0, 1, 'me', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (16, 'crm_contract', '我参与的合同', 0, 0, '', 0, 1, 'mePart', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (17, 'crm_contract', '下属负责的合同', 0, 0, '', 0, 1, 'sub', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (18, 'crm_contract', '全部合同', 0, 0, '', 0, 1, 'all', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (19, 'crm_receivables', '我负责的回款', 0, 0, '', 0, 1, 'me', 1546272000, 1551515457);
INSERT INTO `5kcrm_admin_scene` VALUES (20, 'crm_receivables', '下属负责的回款', 0, 1, '', 0, 1, 'sub', 1546272000, 1551515457);
INSERT INTO `5kcrm_admin_scene` VALUES (21, 'crm_receivables', '全部回款', 0, 2, '', 0, 1, 'all', 1546272000, 1551515457);
INSERT INTO `5kcrm_admin_scene` VALUES (22, 'crm_product', '全部产品', 0, 0, '', 0, 1, 'all', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (23, 'crm_leads', '已转化线索', 0, 0, '', 0, 1, 'is_transform', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (24, 'crm_customer_pool', '今日进入公海的客户', 0, 0, '', 0, 1, 'pool', 1566748800, 1566748800);
INSERT INTO `5kcrm_admin_scene` VALUES (25, 'crm_business', '赢单商机', 0, 0, NULL, 0, 1, 'win_business', 1607072044, 1607072044);
INSERT INTO `5kcrm_admin_scene` VALUES (26, 'crm_business', '输单商机', 0, 0, NULL, 0, 1, 'fail_business', 1607072044, 1607072044);
INSERT INTO `5kcrm_admin_scene` VALUES (27, 'crm_leads', '我关注的线索', 0, 0, NULL, 0, 1, 'star', 1607158834, 1607158834);
INSERT INTO `5kcrm_admin_scene` VALUES (28, 'crm_customer', '我关注的客户', 0, 0, NULL, 0, 1, 'star', 1607158834, 1607158834);
INSERT INTO `5kcrm_admin_scene` VALUES (29, 'crm_contacts', '我关注的联系人', 0, 0, NULL, 0, 1, 'star', 1607158834, 1607158834);
INSERT INTO `5kcrm_admin_scene` VALUES (30, 'crm_business', '我关注的商机', 0, 0, NULL, 0, 1, 'star', 1607158834, 1607158834);
INSERT INTO `5kcrm_admin_scene` VALUES (31, 'crm_visit', '全部回访', 0, 0, '', 0, 1, 'all', 1546272000, 1546272000);
INSERT INTO `5kcrm_admin_scene` VALUES (32, 'crm_visit', '我负责的回访', 0, 0, '', 0, 1, 'me', 1546272000, 1551515457);
INSERT INTO `5kcrm_admin_scene` VALUES (33, 'crm_visit', '下属负责的回访', 0, 1, '', 0, 1, 'sub', 1546272000, 1551515457);

-- ----------------------------
-- Table structure for 5kcrm_admin_scene_default
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_scene_default`;
CREATE TABLE `5kcrm_admin_scene_default`  (
  `default_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型',
  `user_id` int(11) NOT NULL COMMENT '人员ID',
  `scene_id` int(11) NOT NULL COMMENT '场景ID',
  UNIQUE INDEX `default_id`(`default_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '场景默认关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_scene_default
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_sort
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_sort`;
CREATE TABLE `5kcrm_admin_sort`  (
  `sort_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `value` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '排序内容',
  PRIMARY KEY (`sort_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '顶部导航栏' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_sort
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_structure
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_structure`;
CREATE TABLE `5kcrm_admin_structure`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `pid` int(11) NULL DEFAULT 0,
  `owner_user_id` int(5) UNSIGNED NULL DEFAULT 0 COMMENT '当前部门负责人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '部门表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_structure
-- ----------------------------
INSERT INTO `5kcrm_admin_structure` VALUES (1, '办公室', 0, 0);

-- ----------------------------
-- Table structure for 5kcrm_admin_system
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_system`;
CREATE TABLE `5kcrm_admin_system`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_admin_system
-- ----------------------------
INSERT INTO `5kcrm_admin_system` VALUES (1, 'name', '悟空CRM', '网站名称');
INSERT INTO `5kcrm_admin_system` VALUES (2, 'logo', '', '企业logo');

-- ----------------------------
-- Table structure for 5kcrm_admin_system_log
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_system_log`;
CREATE TABLE `5kcrm_admin_system_log`  (
  `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `client_ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户IP',
  `module_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块名',
  `controller_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '控制器',
  `action_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '方法',
  `action_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作ID',
  `target_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '被操作对象的名称',
  `action_delete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1为删除操作',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '内容',
  `create_time` int(10) NOT NULL COMMENT '时间',
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '系统操作日志表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_system_log
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_upgrade_record
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_upgrade_record`;
CREATE TABLE `5kcrm_admin_upgrade_record`  (
  `version` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '版本号',
  UNIQUE INDEX `version`(`version`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '升级记录，用于防止重复执行升级SQL。' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_upgrade_record
-- ----------------------------
INSERT INTO `5kcrm_admin_upgrade_record` VALUES (1103);

-- ----------------------------
-- Table structure for 5kcrm_admin_user
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_user`;
CREATE TABLE `5kcrm_admin_user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '管理后台账号',
  `password` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '管理后台密码',
  `salt` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '安全符',
  `img` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `thumb_img` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '头像缩略图',
  `create_time` int(11) NOT NULL,
  `realname` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `num` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '员工编号',
  `email` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `mobile` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号码',
  `sex` varchar(5) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '男、女',
  `structure_id` int(11) NOT NULL DEFAULT 0 COMMENT '部门',
  `post` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '岗位',
  `status` tinyint(3) NOT NULL DEFAULT 2 COMMENT '状态,0禁用,1启用,2未激活',
  `parent_id` int(10) NOT NULL DEFAULT 0 COMMENT '直属上级ID',
  `authkey` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '验证信息',
  `authkey_time` int(11) NOT NULL DEFAULT 0 COMMENT '验证失效时间',
  `type` tinyint(2) NOT NULL COMMENT '1系统用户 0非系统用户',
  `is_read_notice` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户是否已读升级公告：1已读；0未读',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;


-- ----------------------------
-- Table structure for 5kcrm_admin_user_field
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_user_field`;
CREATE TABLE `5kcrm_admin_user_field`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `types` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '分类',
  `datas` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '属性值',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '自定义字段展示排序关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_user_field
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_admin_user_threeparty
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_admin_user_threeparty`;
CREATE TABLE `5kcrm_admin_user_threeparty`  (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `key` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '关联模块',
  `value` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '关联内容',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '关联第三方' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_admin_user_threeparty
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_achievement
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_achievement`;
CREATE TABLE `5kcrm_crm_achievement`  (
  `achievement_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名字',
  `obj_id` int(11) NOT NULL DEFAULT 0 COMMENT '对象ID',
  `type` tinyint(2) NOT NULL DEFAULT 0 COMMENT '1公司2部门3员工',
  `year` int(8) NOT NULL COMMENT '年',
  `january` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '一月',
  `february` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '二月',
  `march` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '三月',
  `april` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '四月',
  `may` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '五月',
  `june` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '六月',
  `july` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '七月',
  `august` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '八月',
  `september` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '九月',
  `october` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '十月',
  `november` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '十一月',
  `december` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '十二月',
  `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '1销售（目标）2回款（目标）',
  `yeartarget` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '年目标',
  PRIMARY KEY (`achievement_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_achievement
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_activity
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_activity`;
CREATE TABLE `5kcrm_crm_activity`  (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联类型',
  `activity_type_id` int(11) NOT NULL COMMENT '类型ID',
  `type` int(1) NULL DEFAULT 1 COMMENT '活动类型 1 跟进记录 2 创建记录 3 商机阶段变更 4 外勤签到',
  `status` int(2) NULL DEFAULT 1 COMMENT '0 删除 1 未删除',
  `lng` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '经度',
  `lat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '纬度',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '签到地址',
  `customer_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联客户',
  `contract_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联合同',
  `leads_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联线索',
  `activity_type` int(1) NOT NULL COMMENT '活动类型 1 线索 2 客户 3 联系人 4 产品 5 商机 6 合同 7回款 8日志 9审批 10日程 11任务 12 发邮件',
  `content` varchar(1024) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '跟进内容',
  `category` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '跟进类型',
  `next_time` int(11) NULL DEFAULT 0 COMMENT '下次联系时间',
  `business_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '商机ID',
  `contacts_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '联系人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  UNIQUE INDEX `activity_id`(`activity_id`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '跟进记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_activity
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_activity_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_activity_file`;
CREATE TABLE `5kcrm_crm_activity_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL COMMENT '活动ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '跟进记录附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_activity_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_business
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_business`;
CREATE TABLE `5kcrm_crm_business`  (
  `business_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户名称',
  `type_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商机状态组',
  `status_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商机阶段',
  `status_time` int(11) NOT NULL DEFAULT 0 COMMENT '阶段推进时间',
  `is_end` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1赢单2输单3无效',
  `next_time` int(11) NOT NULL DEFAULT 0 COMMENT '下次联系时间',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商机名称',
  `money` decimal(18, 2) NULL DEFAULT 0.00 COMMENT '商机金额',
  `total_price` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '产品总金额',
  `deal_date` date NULL DEFAULT NULL COMMENT '预计成交日期',
  `discount_rate` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '整单折扣',
  `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `ro_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '读写权限',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `is_dealt` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否已经处理（待办事项）：1已处理；0未处理；',
  `expire_remind` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否提醒合同到期：1提醒；0提醒',
  `last_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '最后跟进时间',
  `last_record` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '最后跟进记录',
  `contacts_id` int(10) NULL DEFAULT NULL,
  PRIMARY KEY (`business_id`) USING BTREE,
  INDEX `bi_analysis`(`create_time`, `is_end`, `owner_user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '商机表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_business
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_business_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_business_file`;
CREATE TABLE `5kcrm_crm_business_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL COMMENT '商机ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '商机附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_business_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_business_log
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_business_log`;
CREATE TABLE `5kcrm_crm_business_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL COMMENT '商机id',
  `status_id` int(11) NOT NULL COMMENT '状态id',
  `is_end` tinyint(4) NOT NULL COMMENT '1赢单2输单3无效',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '商机推进日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_business_log
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_business_product
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_business_product`;
CREATE TABLE `5kcrm_crm_business_product`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL COMMENT '商机ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `price` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '产品单价',
  `sales_price` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '销售价格',
  `num` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '数量',
  `discount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '折扣',
  `subtotal` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '小计（折扣后价格）',
  `unit` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '单位',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '商机产品关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_business_product
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_business_status
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_business_status`;
CREATE TABLE `5kcrm_crm_business_status`  (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL COMMENT '商机状态类别ID',
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '标识',
  `rate` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '赢单率',
  `order_id` tinyint(4) NOT NULL DEFAULT 0 COMMENT '排序',
  PRIMARY KEY (`status_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '商机状态' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_business_status
-- ----------------------------
INSERT INTO `5kcrm_crm_business_status` VALUES (1, 0, '赢单', '100', 99);
INSERT INTO `5kcrm_crm_business_status` VALUES (2, 0, '输单', '0', 100);
INSERT INTO `5kcrm_crm_business_status` VALUES (3, 0, '无效', '0', 101);
INSERT INTO `5kcrm_crm_business_status` VALUES (4, 1, '验证客户', '20', 1);
INSERT INTO `5kcrm_crm_business_status` VALUES (5, 1, '需求分析', '15', 2);
INSERT INTO `5kcrm_crm_business_status` VALUES (6, 1, '方案/报价', '30', 3);
INSERT INTO `5kcrm_crm_business_status` VALUES (7, 1, '谈判审核', '30', 4);

-- ----------------------------
-- Table structure for 5kcrm_crm_business_type
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_business_type`;
CREATE TABLE `5kcrm_crm_business_type`  (
  `type_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '标识',
  `structure_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '部门ID',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1启用0禁用',
  `is_display` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '软删除：1显示0不显示',
  PRIMARY KEY (`type_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '商机状态组类别' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_business_type
-- ----------------------------
INSERT INTO `5kcrm_crm_business_type` VALUES (1, '系统默认', '', 1, 1540973371, 1540973371, 1, 1);

-- ----------------------------
-- Table structure for 5kcrm_crm_config
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_config`;
CREATE TABLE `5kcrm_crm_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '标识',
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '值',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = 'CRM管理相关配置' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_config
-- ----------------------------
INSERT INTO `5kcrm_crm_config` VALUES (1, 'follow_day', '7', '距跟进天数');
INSERT INTO `5kcrm_crm_config` VALUES (2, 'deal_day', '30', '距成交天数');
INSERT INTO `5kcrm_crm_config` VALUES (3, 'config', '0', '1启用规则');
INSERT INTO `5kcrm_crm_config` VALUES (4, 'contract_day', '30', '合同到期提醒天数');
INSERT INTO `5kcrm_crm_config` VALUES (5, 'record_type', '[\"\\u6253\\u7535\\u8bdd\",\"\\u53d1\\u90ae\\u4ef6\",\"\\u53d1\\u77ed\\u4fe1\",\"\\u89c1\\u9762\\u62dc\\u8bbf\",\"\\u6d3b\\u52a8\"]', '跟进记录类型');
INSERT INTO `5kcrm_crm_config` VALUES (6, 'contract_config', '1', '1开启');
INSERT INTO `5kcrm_crm_config` VALUES (9, 'activity_phrase', 'a:4:{i:0;s:18:\"电话无人接听\";i:1;s:15:\"客户无意向\";i:2;s:42:\"客户意向度适中，后续继续跟进\";i:3;s:42:\"客户意向度较强，成交几率较大\";}', '跟进记录常用语');
INSERT INTO `5kcrm_crm_config` VALUES (10, 'visit_config', '1', '是否开启回访提醒：1开启；0不开启');
INSERT INTO `5kcrm_crm_config` VALUES (11, 'visit_day', '10', '客户回访提醒天数');

-- ----------------------------
-- Table structure for 5kcrm_crm_contacts
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_contacts`;
CREATE TABLE `5kcrm_crm_contacts`  (
  `contacts_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户名称',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '姓名',
  `mobile` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '手机',
  `telephone` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电子邮箱',
  `decision` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '是否关键决策人',
  `post` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '职务',
  `sex` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '性别',
  `detail_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '地址',
  `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  `ro_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '读写权限',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人ID',
  `next_time` int(11) NULL DEFAULT NULL COMMENT '下次联系时间',
  `primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是首要联系人：1是；0不是',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `last_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '最后跟进时间',
  `last_record` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '最后跟进记录',
  PRIMARY KEY (`contacts_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '联系人表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_contacts
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_contacts_business
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_contacts_business`;
CREATE TABLE `5kcrm_crm_contacts_business`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `contacts_id` int(10) NOT NULL,
  `business_id` int(10) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_contacts_business
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_contacts_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_contacts_file`;
CREATE TABLE `5kcrm_crm_contacts_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `contacts_id` int(11) NOT NULL COMMENT '联系人ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '联系人附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_contacts_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_contract
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_contract`;
CREATE TABLE `5kcrm_crm_contract`  (
  `contract_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户名称',
  `business_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商机名称',
  `contacts_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户签约人',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '合同名称',
  `num` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '合同编号',
  `order_date` date NULL DEFAULT NULL COMMENT '下单时间',
  `money` decimal(18, 2) NULL DEFAULT 0.00 COMMENT '合同金额',
  `total_price` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '产品总金额',
  `discount_rate` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '整单折扣',
  `check_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0待审核、1审核中、2审核通过、3审核未通过、4撤销、5草稿(未提交)',
  `flow_id` int(11) NOT NULL DEFAULT 0 COMMENT '审核流程ID',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '审核步骤排序ID',
  `check_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '审批人IDs',
  `flow_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '流程审批人ID',
  `start_time` date NULL DEFAULT NULL COMMENT '合同开始时间',
  `end_time` date NULL DEFAULT NULL COMMENT '合同到期时间',
  `order_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '公司签约人',
  `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `ro_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '读写权限',
  `next_time` int(11) NOT NULL DEFAULT 0 COMMENT '下次联系时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `is_visit` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已回访：1已回访；2未回访',
  `expire_remind` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否提醒合同到期：1提醒；0提醒',
  `last_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '最后跟进时间',
  `last_record` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '最后跟进记录',
  PRIMARY KEY (`contract_id`) USING BTREE,
  INDEX `bi_analysis`(`check_status`, `customer_id`, `order_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '合同表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_contract
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_contract_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_contract_file`;
CREATE TABLE `5kcrm_crm_contract_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT '合同ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '合同附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_contract_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_contract_product
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_contract_product`;
CREATE TABLE `5kcrm_crm_contract_product`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT '合同ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `price` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '产品单价',
  `sales_price` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '销售价格',
  `num` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '数量',
  `discount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '折扣',
  `subtotal` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '小计（折扣后价格）',
  `unit` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '单位',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '合同产品关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_contract_product
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_customer
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer`;
CREATE TABLE `5kcrm_crm_customer`  (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户名称',
  `is_lock` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1锁定',
  `deal_status` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '未成交' COMMENT '成交状态',
  `deal_time` int(11) NOT NULL COMMENT '领取，分配，创建时间',
  `level` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户级别',
  `industry` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户行业',
  `source` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户来源',
  `telephone` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `mobile` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '手机',
  `website` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '网址',
  `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人ID',
  `ro_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '读写权限',
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '省市区',
  `location` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '定位信息',
  `detail_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '详细地址',
  `lng` double(14, 11) NULL DEFAULT NULL COMMENT '地理位置经度',
  `lat` double(14, 11) NULL DEFAULT NULL COMMENT '地理位置维度',
  `next_time` int(11) NULL DEFAULT NULL COMMENT '下次联系时间',
  `follow` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '跟进',
  `obtain_time` int(10) NOT NULL DEFAULT 0 COMMENT '负责人获取客户时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `is_dealt` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否已经处理（待办事项）：1已处理；0未处理；',
  `is_allocation` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是分配给我的客户：1是；0不是',
  `last_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '最后跟进时间',
  `last_record` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '最后跟进记录',
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '邮箱',
  `before_owner_user_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '前负责人',
  `into_pool_time` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '进入公海时间',
  `pool_remain` tinyint(1) NOT NULL DEFAULT 0 COMMENT '代办事项待进入公海：1已处理，0未处理',
  PRIMARY KEY (`customer_id`) USING BTREE,
  INDEX `bi_analysis`(`create_time`, `owner_user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '客户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_customer
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_customer_config
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer_config`;
CREATE TABLE `5kcrm_crm_customer_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '员工',
  `structure_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '部门',
  `types` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1拥有客户上限2锁定客户上限',
  `value` int(10) NOT NULL COMMENT '数值',
  `is_deal` tinyint(4) NOT NULL COMMENT '1成交客户',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '客户配置表（锁定、拥有）' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_customer_config
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_customer_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer_file`;
CREATE TABLE `5kcrm_crm_customer_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '客户ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '客户附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_customer_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_customer_pool
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer_pool`;
CREATE TABLE `5kcrm_crm_customer_pool`  (
  `pool_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pool_name` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名称',
  `admin_user_ids` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '管理员IDS',
  `user_ids` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '成员IDS',
  `department_ids` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '部门IDS',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1启用，0停用',
  `before_owner_conf` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '前负责人领取规则：1限制，0不限制',
  `before_owner_day` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '前负责人领取规则限制天数',
  `receive_conf` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取频率规则：1限制，0不限制',
  `receive_count` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取频率个数',
  `remind_conf` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '提醒规则：1开启，0不开启',
  `remain_day` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '提前几天提醒',
  `recycle_conf` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动回收规则：1自动回收，0不自动回收',
  `create_user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建人',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`pool_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公海表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_customer_pool
-- ----------------------------
INSERT INTO `5kcrm_crm_customer_pool` VALUES (1, '系统默认公海', ',1,', ',1,', '', 1, 0, 0, 0, 0, 0, 0, 1, 1, 1620610119);

-- ----------------------------
-- Table structure for 5kcrm_crm_customer_pool_field_setting
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer_pool_field_setting`;
CREATE TABLE `5kcrm_crm_customer_pool_field_setting`  (
  `setting_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pool_id` int(10) UNSIGNED NOT NULL COMMENT '公海ID',
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '字段中文名',
  `field_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '字段英文名',
  `form_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '字段类型，与5kcrm_admin_field表的form_type类型一致',
  `is_hidden` tinyint(1) UNSIGNED NOT NULL COMMENT '是否隐藏：1隐藏，0不隐藏',
  PRIMARY KEY (`setting_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 21 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公海字段表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_customer_pool_field_setting
-- ----------------------------
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (1, 1, '客户名称', 'name', 'text', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (2, 1, '客户级别', 'level', 'select', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (3, 1, '客户行业', 'industry', 'select', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (4, 1, '客户来源', 'source', 'select', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (5, 1, '成交状态', 'deal_status', 'select', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (6, 1, '电话', 'telephone', 'text', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (7, 1, '网址', 'website', 'text', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (8, 1, '下次联系时间', 'next_time', 'datetime', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (9, 1, '备注', 'remark', 'textarea', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (10, 1, '手机', 'mobile', 'mobile', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (11, 1, '邮箱', 'email', 'email', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (12, 1, '省、市、区/县', 'address', 'customer_address', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (13, 1, '详细地址', 'detail_address', 'text', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (14, 1, '最后跟进记录', 'last_record', 'text', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (15, 1, '最后跟进时间', 'last_time', 'datetime', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (16, 1, '前负责人', 'before_owner_user_id', 'user', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (17, 1, '进入公海时间', 'into_pool_time', 'datetime', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (18, 1, '创建时间', 'create_time', 'datetime', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (19, 1, '更新时间', 'update_time', 'datetime', 0);
INSERT INTO `5kcrm_crm_customer_pool_field_setting` VALUES (20, 1, '创建人', 'create_user_id', 'user', 0);

-- ----------------------------
-- Table structure for 5kcrm_crm_customer_pool_field_style
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer_pool_field_style`;
CREATE TABLE `5kcrm_crm_customer_pool_field_style`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `pool_id` int(10) UNSIGNED NOT NULL COMMENT '公海ID',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '字段内容',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公海字段样式表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_customer_pool_field_style
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_customer_pool_record
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer_pool_record`;
CREATE TABLE `5kcrm_crm_customer_pool_record`  (
  `record_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) UNSIGNED NOT NULL COMMENT '客户ID',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '员工ID',
  `pool_id` int(10) UNSIGNED NOT NULL COMMENT '公海ID',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型：1领取公海客户；2将客户放入公海',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`record_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公海操作记录（领取公海客户、将客户放入公海）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_customer_pool_record
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_customer_pool_relation
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer_pool_relation`;
CREATE TABLE `5kcrm_crm_customer_pool_relation`  (
  `relation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pool_id` int(10) UNSIGNED NOT NULL COMMENT '公海ID',
  `customer_id` int(10) UNSIGNED NOT NULL COMMENT '客户ID',
  PRIMARY KEY (`relation_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公海与客户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_customer_pool_relation
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_customer_pool_rule
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_customer_pool_rule`;
CREATE TABLE `5kcrm_crm_customer_pool_rule`  (
  `rule_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pool_id` int(10) UNSIGNED NOT NULL COMMENT '公海ID',
  `type` tinyint(1) UNSIGNED NOT NULL COMMENT '收回规则类型：1跟进记录；2商机；3成交状态',
  `deal_handle` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '选择不进入公海客户（成交客户）：1已选，0未选',
  `business_handle` tinyint(1) UNSIGNED NOT NULL COMMENT '选择不进入公海客户（有商机客户）：1已选，0不选',
  `level_conf` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '客户级别设置：1全部，2根据客户级别设置',
  `level` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '客户级别的设置数据',
  `limit_day` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '公海规则限制天数',
  PRIMARY KEY (`rule_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公海规则表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_customer_pool_rule
-- ----------------------------
INSERT INTO `5kcrm_crm_customer_pool_rule` VALUES (1, 1, 1, 0, 0, 1, '[{\"level\":\"\\u6240\\u6709\\u5ba2\\u6237\",\"limit_day\":30}]', 0);

-- ----------------------------
-- Table structure for 5kcrm_crm_dashboard
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_dashboard`;
CREATE TABLE `5kcrm_crm_dashboard`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dashboard` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `user_id` int(4) NOT NULL COMMENT '创建人 、修改人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '仪表盘样式' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_dashboard
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_dealt_relation
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_dealt_relation`;
CREATE TABLE `5kcrm_crm_dealt_relation`  (
  `dealt_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `types` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型：crm_contract；crm_invoice；crm_receivables',
  `types_id` int(10) UNSIGNED NOT NULL COMMENT '类型ID',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  PRIMARY KEY (`dealt_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '待办事项关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_dealt_relation
-- ----------------------------
INSERT INTO `5kcrm_crm_dealt_relation` VALUES (2, 'crm_contract', 1, 2);
INSERT INTO `5kcrm_crm_dealt_relation` VALUES (4, 'crm_receivables', 1, 2);
INSERT INTO `5kcrm_crm_dealt_relation` VALUES (5, 'crm_receivables', 2, 3);
INSERT INTO `5kcrm_crm_dealt_relation` VALUES (6, 'crm_receivables', 3, 3);
INSERT INTO `5kcrm_crm_dealt_relation` VALUES (7, 'crm_contract', 2, 3);
INSERT INTO `5kcrm_crm_dealt_relation` VALUES (8, 'crm_receivables', 4, 3);

-- ----------------------------
-- Table structure for 5kcrm_crm_invoice
-- ----------------------------
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_invoice
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_invoice_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_invoice_file`;
CREATE TABLE `5kcrm_crm_invoice_file`  (
  `r_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) UNSIGNED NOT NULL COMMENT '发票ID',
  `file_id` int(10) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '发票附件关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_invoice_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_invoice_info
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_invoice_info`;
CREATE TABLE `5kcrm_crm_invoice_info`  (
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '发票开户行信息' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_invoice_info
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_leads
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_leads`;
CREATE TABLE `5kcrm_crm_leads`  (
  `leads_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL DEFAULT 0 COMMENT '线索转化为客户ID',
  `is_transform` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1已转化',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '线索名称',
  `source` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '线索来源',
  `telephone` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `mobile` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '手机',
  `industry` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户行业',
  `level` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户级别',
  `detail_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '地址',
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电子邮箱',
  `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `next_time` int(11) NULL DEFAULT NULL COMMENT '下次联系时间',
  `follow` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '跟进',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `is_dealt` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否已经处理（待办事项）：1已处理；0未处理；',
  `is_allocation` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是分配给我的线索：1是；0不是',
  `last_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '最后跟进时间',
  `last_record` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '最后跟进记录',
  PRIMARY KEY (`leads_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '线索表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_leads
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_leads_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_leads_file`;
CREATE TABLE `5kcrm_crm_leads_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `leads_id` int(11) NOT NULL COMMENT '线索ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '线索附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_leads_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_number_sequence
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_number_sequence`;
CREATE TABLE `5kcrm_crm_number_sequence`  (
  `number_sequence_id` int(10) NOT NULL AUTO_INCREMENT,
  `sort` int(2) NOT NULL COMMENT '编号顺序',
  `type` int(2) NOT NULL COMMENT '编号类型 1文本 2日期 3数字',
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '文本内容或日期格式或起始编号',
  `increase_number` int(2) NULL DEFAULT NULL COMMENT '递增数',
  `reset` int(10) NULL DEFAULT 0 COMMENT '重置编号 0 从不，1 天，2 月， 3 年，',
  `last_number` int(10) NULL DEFAULT NULL COMMENT '上次生成的编号',
  `last_date` int(11) NULL DEFAULT NULL COMMENT '上次生成的时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `create_user_id` bigint(20) NULL DEFAULT NULL COMMENT '创建人id',
  `company_id` bigint(20) NULL DEFAULT NULL COMMENT '公司id',
  `status` int(2) NOT NULL DEFAULT 0 COMMENT '默认开启使用自动编号 1不使用',
  `number_type` int(11) NULL DEFAULT NULL COMMENT '编号规则类型',
  PRIMARY KEY (`number_sequence_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '编号规则' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_number_sequence
-- ----------------------------
INSERT INTO `5kcrm_crm_number_sequence` VALUES (1, 1, 1, 'HT', NULL, NULL, NULL, NULL, 1620610121, 1, NULL, 0, 1);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (2, 2, 2, 'yyyyMMdd', NULL, NULL, NULL, NULL, 1620610121, 1, NULL, 0, 1);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (3, 3, 3, '1', 1, 1, 1, 1620610121, 1620610121, 1, NULL, 0, 1);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (4, 1, 1, 'HK', NULL, NULL, NULL, NULL, 1620610121, 1, NULL, 0, 2);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (5, 2, 2, 'yyyyMMdd', NULL, NULL, NULL, NULL, 1620610121, 1, NULL, 0, 2);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (6, 3, 3, '1', 1, 1, 1, 1620610121, 1620610121, 1, NULL, 0, 2);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (7, 1, 1, 'HF', NULL, NULL, NULL, NULL, 1620610121, 1, NULL, 0, 3);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (8, 2, 2, 'yyyyMMdd', NULL, NULL, NULL, NULL, 1620610121, 1, NULL, 0, 3);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (9, 3, 3, '1', 1, 1, 1, 1620610121, 1620610121, 1, NULL, 0, 3);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (10, 1, 2, 'yyyyMMdd', NULL, NULL, NULL, NULL, 1620610121, 1, NULL, 0, 4);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (11, 2, 1, 'FP', NULL, NULL, NULL, NULL, 1620610121, 1, NULL, 0, 4);
INSERT INTO `5kcrm_crm_number_sequence` VALUES (12, 3, 3, '1', 1, 1, 1, 1620610121, 1620610121, 1, NULL, 0, 4);

-- ----------------------------
-- Table structure for 5kcrm_crm_printing_record
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_printing_record`;
CREATE TABLE `5kcrm_crm_printing_record`  (
  `printing_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `type` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块：1商机、2合同；3回款',
  `action_id` int(10) UNSIGNED NOT NULL COMMENT '操作ID',
  `template_id` int(10) UNSIGNED NOT NULL COMMENT '模板ID',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '打印内容',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL COMMENT '编辑时间',
  PRIMARY KEY (`printing_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '打印记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_printing_record
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_product
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_product`;
CREATE TABLE `5kcrm_crm_product`  (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品名称',
  `num` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品编码',
  `unit` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单位',
  `price` decimal(18, 2) NULL DEFAULT 0.00 COMMENT '标准价格',
  `status` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '上架' COMMENT '是否上架',
  `category_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品类别',
  `category_str` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '产品分类id(层级)',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品描述',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `delete_user_id` int(10) NOT NULL DEFAULT 0 COMMENT '删除人',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  `cover_images` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '角色组ID',
  `details_images` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '封面图片',
  PRIMARY KEY (`product_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_product
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_product_category
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_product_category`;
CREATE TABLE `5kcrm_crm_product_category`  (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `pid` int(11) NULL DEFAULT 0,
  PRIMARY KEY (`category_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品分类表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_product_category
-- ----------------------------
INSERT INTO `5kcrm_crm_product_category` VALUES (1, '默认', 0);

-- ----------------------------
-- Table structure for 5kcrm_crm_product_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_product_file`;
CREATE TABLE `5kcrm_crm_product_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_product_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_receivables
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_receivables`;
CREATE TABLE `5kcrm_crm_receivables`  (
  `receivables_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '期数',
  `number` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '回款编号',
  `customer_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户名称',
  `contract_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '合同编号',
  `return_time` date NULL DEFAULT NULL COMMENT '回款日期',
  `return_type` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '回款方式',
  `money` decimal(18, 2) NULL DEFAULT 0.00 COMMENT '回款金额',
  `check_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0待审核、1审核中、2审核通过、3审核未通过、4撤销、5草稿(未提交)',
  `flow_id` int(11) NOT NULL DEFAULT 0 COMMENT '审核流程ID',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '审核步骤排序ID',
  `check_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '审批人IDs',
  `flow_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '流程审批人ID',
  `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`receivables_id`) USING BTREE,
  INDEX `bi_analysis`(`check_status`, `return_time`, `owner_user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '回款表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_receivables
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_receivables_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_receivables_file`;
CREATE TABLE `5kcrm_crm_receivables_file`  (
  `r_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receivables_id` int(10) UNSIGNED NOT NULL,
  `file_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '回款附件表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_receivables_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_receivables_plan
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_receivables_plan`;
CREATE TABLE `5kcrm_crm_receivables_plan`  (
  `plan_id` int(11) NOT NULL AUTO_INCREMENT,
  `num` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '期数',
  `receivables_id` int(11) NOT NULL DEFAULT 0 COMMENT '回款ID',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1完成',
  `contract_id` int(11) NOT NULL COMMENT '合同ID',
  `customer_id` int(11) NOT NULL COMMENT '客户ID',
  `money` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '计划回款金额',
  `return_date` date NULL DEFAULT NULL COMMENT '计划回款日期',
  `return_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '计划回款方式',
  `remind` tinyint(4) NOT NULL DEFAULT 0 COMMENT '提前几天提醒',
  `remind_date` date NULL DEFAULT NULL COMMENT '提醒日期',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `file` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '附件',
  `is_dealt` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已经处理（待办事项）：1已处理；0未处理；',
  PRIMARY KEY (`plan_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '回款计划表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_receivables_plan
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_star
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_star`;
CREATE TABLE `5kcrm_crm_star`  (
  `star_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '员工ID',
  `target_id` int(10) NOT NULL COMMENT '目标ID：客户、商机、线索、联系人',
  `type` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型：crm_leads线索；crm_customer客户；crm_contacts联系人；crm_business商机;',
  PRIMARY KEY (`star_id`) USING BTREE,
  UNIQUE INDEX `user_target_type`(`user_id`, `target_id`, `type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '我关注的' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_star
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_top
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_top`;
CREATE TABLE `5kcrm_crm_top`  (
  `top_id` int(10) NOT NULL AUTO_INCREMENT,
  `module_id` int(10) NOT NULL COMMENT '相关模块ID',
  `set_top` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1置顶',
  `top_time` int(10) NOT NULL COMMENT '置顶时间',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `module` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'business' COMMENT '置顶模块',
  PRIMARY KEY (`top_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '置顶表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_top
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_visit
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_visit`;
CREATE TABLE `5kcrm_crm_visit`  (
  `visit_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '回访id',
  `owner_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '回访人',
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_crm_visit
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_crm_visit_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_crm_visit_file`;
CREATE TABLE `5kcrm_crm_visit_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL COMMENT '回访ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '回访客户附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_crm_visit_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_hrm_user_det
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_hrm_user_det`;
CREATE TABLE `5kcrm_hrm_user_det`  (
  `userdet_id` int(9) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '员工id',
  `join_time` int(11) NOT NULL DEFAULT 0 COMMENT '入职时间',
  `type` tinyint(2) NOT NULL DEFAULT 0 COMMENT '工作性质：1全职 2兼职 3实习',
  `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '用户类型：1非系统用户 2系统用户 3待离职 4离职 ',
  `userstatus` tinyint(2) NOT NULL DEFAULT 1 COMMENT '员工状态：1试用 2正式',
  `mobile` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `sex` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '性别',
  `age` int(3) NOT NULL DEFAULT 0 COMMENT '年龄',
  `job_num` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '工号',
  `idtype` tinyint(2) NOT NULL DEFAULT 0 COMMENT '证件类型',
  `idnum` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '证件号码',
  `birth_time` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '出生日期',
  `nation` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '民族',
  `internship` tinyint(2) NOT NULL DEFAULT 0 COMMENT '试用期（月）',
  `done_time` int(11) NOT NULL DEFAULT 0 COMMENT '转正时间',
  `parroll_id` int(11) NOT NULL DEFAULT 0 COMMENT '工资信息表ID',
  `create_time` int(11) NOT NULL COMMENT '添加时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '编辑时间',
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `political` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '政治面貌',
  `location` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '户籍地址',
  `leave_time` int(11) NOT NULL DEFAULT 0 COMMENT '离职时间',
  PRIMARY KEY (`userdet_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '员工档案表' ROW_FORMAT = DYNAMIC;


-- ----------------------------
-- Table structure for 5kcrm_oa_announcement
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_announcement`;
CREATE TABLE `5kcrm_oa_announcement`  (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '标题',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `end_time` int(11) NOT NULL COMMENT '结束时间',
  `structure_ids` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '通知部门',
  `owner_user_ids` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '通知人',
  `read_user_ids` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '阅读人',
  `is_read` tinyint(2) NOT NULL DEFAULT 0 COMMENT '1表示已读 0表示未读',
  PRIMARY KEY (`announcement_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公告表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_announcement
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_event
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_event`;
CREATE TABLE `5kcrm_oa_event`  (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '日程标题',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `end_time` int(11) NOT NULL COMMENT '结束时间',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `owner_user_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '参与人',
  `schedule_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT 'admin_oa_schedule表的主键ID',
  PRIMARY KEY (`event_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日程表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_event
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_event_notice
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_event_notice`;
CREATE TABLE `5kcrm_oa_event_notice`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL COMMENT '日程ID',
  `noticetype` tinyint(4) UNSIGNED NULL DEFAULT NULL COMMENT '1分 2时 3天',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `stop_time` int(11) NOT NULL COMMENT '介绍时间',
  `number` tinyint(4) NOT NULL DEFAULT 0 COMMENT '根据noticetype来决定提前多久提醒',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日程提醒设置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_oa_event_notice
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_event_relation
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_event_relation`;
CREATE TABLE `5kcrm_oa_event_relation`  (
  `eventrelation_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日程关联业务表',
  `event_id` int(11) NOT NULL COMMENT '日程ID',
  `customer_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NULL DEFAULT 0 COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`eventrelation_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日程关联业务表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_oa_event_relation
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_examine
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_examine`;
CREATE TABLE `5kcrm_oa_examine`  (
  `examine_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL DEFAULT 1 COMMENT '审批类型',
  `content` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '内容',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `money` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '差旅、报销总金额',
  `start_time` int(11) NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_time` int(11) NOT NULL DEFAULT 0 COMMENT '结束时间',
  `duration` decimal(10, 1) NOT NULL DEFAULT 0.0 COMMENT '时长',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `check_user_id` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '审批人ID',
  `flow_user_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '流程审批人ID',
  `check_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '状态（0待审、1审批中、2通过、3失败、4撤销）',
  `flow_id` int(11) NOT NULL DEFAULT 0 COMMENT '审批流程ID',
  `order_id` int(10) NOT NULL DEFAULT 0 COMMENT '审批流程排序ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `last_user_id` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '上一审批人',
  PRIMARY KEY (`examine_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '审批表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_examine
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_examine_category
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_examine_category`;
CREATE TABLE `5kcrm_oa_examine_category`  (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名称',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1启用，0禁用',
  `is_sys` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1为系统类型，不能删除',
  `user_ids` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '可见范围（员工）',
  `structure_ids` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '可见范围（部门）',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1已删除',
  `delete_time` int(11) NOT NULL DEFAULT 0 COMMENT '删除时间',
  `delete_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '删除人ID',
  `flow_id` int(11) NOT NULL DEFAULT 0 COMMENT '审批流ID',
  `icon` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型图标和颜色',
  PRIMARY KEY (`category_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '审批类型表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_examine_category
-- ----------------------------
INSERT INTO `5kcrm_oa_examine_category` VALUES (1, '普通审批', '普通审批', 1, 1, 1, '', '', 1612576450, 1612576450, 0, 0, 0, 1, 'wk wk-leave,#00CAAB');
INSERT INTO `5kcrm_oa_examine_category` VALUES (2, '请假审批', '请假审批', 1, 1, 1, '', '', 1612518097, 1612518097, 0, 0, 0, 2, 'wk wk-l-record,#3ABCFB');
INSERT INTO `5kcrm_oa_examine_category` VALUES (3, '出差审批', '出差审批', 1, 1, 1, '', '', 1548911542, 1548911542, 0, 0, 0, 3, 'wk wk-trip,#3ABCFB');
INSERT INTO `5kcrm_oa_examine_category` VALUES (4, '加班审批', '加班审批', 1, 1, 1, '', '', 1548911542, 1548911542, 0, 0, 0, 4, 'wk wk-overtime,#FAAD14');
INSERT INTO `5kcrm_oa_examine_category` VALUES (5, '差旅报销', '差旅报销', 1, 1, 1, '', '', 1548911542, 1548911542, 0, 0, 0, 5, 'wk wk-reimbursement,#3ABCFB');
INSERT INTO `5kcrm_oa_examine_category` VALUES (6, '借款申请', '借款申请', 1, 1, 1, '', '', 1548911542, 1548911542, 0, 0, 0, 6, 'wk wk-go-out,#FF6033');

-- ----------------------------
-- Table structure for 5kcrm_oa_examine_data
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_examine_data`;
CREATE TABLE `5kcrm_oa_examine_data`  (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `field` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '字段名',
  `value` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`data_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '审批数据扩展表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_examine_data
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_examine_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_examine_file`;
CREATE TABLE `5kcrm_oa_examine_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '审批附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_examine_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_examine_order
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_examine_order`;
CREATE TABLE `5kcrm_oa_examine_order`  (
  `order_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `examine_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`order_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '办公审批流排序表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_oa_examine_order
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_examine_relation
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_examine_relation`;
CREATE TABLE `5kcrm_oa_examine_relation`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '审批关联业务表',
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `customer_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NOT NULL DEFAULT 1 COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '审批关联业务表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_oa_examine_relation
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_examine_travel
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_examine_travel`;
CREATE TABLE `5kcrm_oa_examine_travel`  (
  `travel_id` int(11) NOT NULL AUTO_INCREMENT,
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `start_address` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '出发地',
  `start_time` int(11) NOT NULL COMMENT '出发时间',
  `end_address` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '目的地',
  `end_time` int(11) NOT NULL COMMENT '到达时间',
  `traffic` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '交通费',
  `stay` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '住宿费',
  `diet` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '餐饮费',
  `other` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '其他费用',
  `money` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '金额',
  `vehicle` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '交通工具',
  `trip` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '单程往返（单程、往返）',
  `duration` decimal(10, 1) NOT NULL DEFAULT 0.0 COMMENT '时长',
  `description` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`travel_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '差旅行程表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_examine_travel
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_examine_travel_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_examine_travel_file`;
CREATE TABLE `5kcrm_oa_examine_travel_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `travel_id` int(11) NOT NULL COMMENT '差旅id',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '差旅附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_examine_travel_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_log
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_log`;
CREATE TABLE `5kcrm_oa_log`  (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` tinyint(2) NOT NULL DEFAULT 1 COMMENT '日志类型（1日报，2周报，3月报）',
  `title` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '日志标题',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '日志内容',
  `tomorrow` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '明日工作内容',
  `question` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '遇到问题',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `send_user_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '通知人',
  `send_structure_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '通知部门',
  `read_user_ids` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '已读ids',
  `is_relation` tinyint(2) NOT NULL DEFAULT 1 COMMENT '0不关联1关联',
  `save_customer` int(10) NOT NULL DEFAULT 0 COMMENT '每日新增客户数量',
  `save_business` int(10) NOT NULL DEFAULT 0 COMMENT '每日新增商机',
  `save_contract` int(10) NOT NULL DEFAULT 0 COMMENT '每日新增合同',
  `save_receivables` decimal(18, 2) NOT NULL DEFAULT 0.00 COMMENT '每日新增回款',
  `save_activity` int(10) NOT NULL DEFAULT 0 COMMENT '新增跟进记录',
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '工作日志表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_log
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_log_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_log_file`;
CREATE TABLE `5kcrm_oa_log_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `log_id` int(11) NOT NULL COMMENT '日志ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日志附件关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_oa_log_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_oa_log_relation
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_oa_log_relation`;
CREATE TABLE `5kcrm_oa_log_relation`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志关联业务表',
  `log_id` int(11) NOT NULL COMMENT '日志ID',
  `customer_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NOT NULL COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日志关联业务表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_oa_log_relation
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_task
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_task`;
CREATE TABLE `5kcrm_task`  (
  `task_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '任务表',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '任务名称',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `main_user_id` int(11) NOT NULL COMMENT '负责人ID',
  `owner_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '团队成员ID',
  `structure_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '部门IDs',
  `create_time` int(11) NOT NULL COMMENT '新建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `status` tinyint(2) NOT NULL DEFAULT 1 COMMENT '完成状态 1正在进行,2延期,5结束',
  `class_id` int(5) NOT NULL DEFAULT 0 COMMENT '分类 要做 在做 待定',
  `lable_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '标签 ,号拼接',
  `description` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '描述',
  `pid` int(11) NULL DEFAULT 0 COMMENT '上级ID',
  `start_time` int(11) NOT NULL DEFAULT 0 COMMENT '开始时间',
  `stop_time` int(11) NOT NULL DEFAULT 0 COMMENT '结束时间',
  `priority` tinyint(2) NOT NULL DEFAULT 0 COMMENT '优先级 从大到小',
  `work_id` int(11) NULL DEFAULT 0 COMMENT '项目ID',
  `is_top` tinyint(2) NULL DEFAULT 0 COMMENT '工作台展示 0收件箱 1，2，3',
  `is_open` tinyint(2) NULL DEFAULT 1 COMMENT '是否公开',
  `order_id` tinyint(4) NOT NULL DEFAULT 0 COMMENT '排序ID',
  `top_order_id` tinyint(4) NOT NULL DEFAULT 0 COMMENT '我的任务排序ID',
  `is_archive` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1归档',
  `archive_time` int(11) NOT NULL DEFAULT 0 COMMENT '归档时间',
  `ishidden` tinyint(2) NULL DEFAULT 0 COMMENT '是否删除',
  `hidden_time` int(11) NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`task_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '任务表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_task
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_task_relation
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_task_relation`;
CREATE TABLE `5kcrm_task_relation`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '任务关联业务表',
  `task_id` int(11) NOT NULL COMMENT '任务ID',
  `customer_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NOT NULL DEFAULT 1 COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '任务关联业务表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_task_relation
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work`;
CREATE TABLE `5kcrm_work`  (
  `work_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '项目名字',
  `status` tinyint(2) NULL DEFAULT 0 COMMENT '状态 1启用 0归档',
  `create_time` int(11) NOT NULL,
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `description` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '描述',
  `color` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '颜色',
  `is_open` tinyint(2) NULL DEFAULT 0 COMMENT '是否所有人可见 1可见',
  `owner_user_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '项目成员',
  `ishidden` tinyint(2) NULL DEFAULT 0 COMMENT '是否删除',
  `archive_time` int(11) NOT NULL DEFAULT 0 COMMENT '归档时间',
  `group_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT '角色组ID',
  `cover_url` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '封面图片',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `is_system_cover` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否是系统封面：1是；0不是',
  `is_follow` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关注：1关注',
  PRIMARY KEY (`work_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '项目表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_work
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work_lable_order
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work_lable_order`;
CREATE TABLE `5kcrm_work_lable_order`  (
  `order_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lable_id` int(10) UNSIGNED NOT NULL COMMENT '标签ID',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `order` int(10) UNSIGNED NOT NULL COMMENT '排序',
  PRIMARY KEY (`order_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '标签排序表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_work_lable_order
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work_order
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work_order`;
CREATE TABLE `5kcrm_work_order`  (
  `order_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`order_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '项目排序表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_work_order
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work_relation
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work_relation`;
CREATE TABLE `5kcrm_work_relation`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志关联业务表',
  `work_id` int(11) NOT NULL COMMENT '项目ID',
  `customer_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NOT NULL DEFAULT 1 COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '项目关联业务表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_work_relation
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work_task_class
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work_task_class`;
CREATE TABLE `5kcrm_work_task_class`  (
  `class_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '任务分类表',
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '分类名',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `status` tinyint(2) NULL DEFAULT 0 COMMENT '状态1正常',
  `work_id` int(11) NOT NULL COMMENT '项目ID',
  `order_id` tinyint(4) NOT NULL DEFAULT 0 COMMENT '排序',
  PRIMARY KEY (`class_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '任务分类表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_work_task_class
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work_task_file
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work_task_file`;
CREATE TABLE `5kcrm_work_task_file`  (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL COMMENT '文件ID',
  `task_id` int(11) NOT NULL COMMENT '任务ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_work_task_file
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work_task_lable
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work_task_lable`;
CREATE TABLE `5kcrm_work_task_lable`  (
  `lable_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '标签名',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `status` tinyint(2) NULL DEFAULT 0 COMMENT '状态',
  `color` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '颜色',
  PRIMARY KEY (`lable_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '任务标签表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_work_task_lable
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work_task_log
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work_task_log`;
CREATE TABLE `5kcrm_work_task_log`  (
  `log_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '项目日志表',
  `user_id` int(11) NOT NULL COMMENT '操作人ID',
  `content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '内容',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `status` tinyint(2) NULL DEFAULT 0 COMMENT '状态 4删除',
  `task_id` int(11) NULL DEFAULT 0 COMMENT '任务ID',
  `work_id` int(11) NULL DEFAULT 0 COMMENT '项目ID',
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '任务日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of 5kcrm_work_task_log
-- ----------------------------

-- ----------------------------
-- Table structure for 5kcrm_work_user
-- ----------------------------
DROP TABLE IF EXISTS `5kcrm_work_user`;
CREATE TABLE `5kcrm_work_user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_id` int(11) NOT NULL COMMENT '项目ID',
  `user_id` int(11) NOT NULL COMMENT '成员ID',
  `types` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1管理员，0初始权限',
  `group_id` int(11) NOT NULL COMMENT '角色ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '项目成员表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of 5kcrm_work_user
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
