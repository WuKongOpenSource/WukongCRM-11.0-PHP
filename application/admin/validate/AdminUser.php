<?php

namespace app\admin\validate;
use think\Validate;
/**
* 设置模型
*/
class AdminUser extends Validate{

	protected $rule = array(
		'realname'      	=> 'require',
		'username'      	=> 'require|unique:admin_user|regex:^1[3456789][0-9]{9}?$',
		'structure_id'      	=> 'require',
		'password'      	=> 'require|regex:^(?![0-9]*$)[a-zA-Z0-9]{6,20}$',
	);
	protected $message = array(
		'realname.require'    	=> '姓名必须填写',
		'username.require'    	=> '手机号码必须填写',
		'username.unique'    	=> '手机号码已存在',
		'username.regex'    	=> '手机号码格式错误',
		'password.require'    	=> '密码必须填写',
		'password.regex'    	=> '密码由6-20位字母、数字组成',
		'structure_id.require'    	=> '请选择所属部门',
	);
}