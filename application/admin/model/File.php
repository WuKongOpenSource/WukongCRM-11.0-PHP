<?php
// +----------------------------------------------------------------------
// | Description: 附件
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;
use app\crm\model\Activity;
use app\crm\model\Invoice;
use think\Db;
use think\Request;

class File extends Common 
{

    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_file';
	protected $module_arr = [
	    'other',
        'crm_leads',
        'crm_customer',
        'crm_contacts',
        'crm_business',
        'crm_product',
        'crm_contract',
        'oa_log',
        'oa_examine',
        'oa_examine_travel',
        'work_task',
        'admin_record',
        'oa_travel',
        'hrm_pact',
        'hrm_file',
        'crm_invoice',
        'crm_activity',
        'crm_visit',
        'crm_receivables'
    ];
	
	/**
	 * [createData 添加附件]
	 * @author Michael_xu
	 * @param     $files 附件数组 
	 * @param     $param [module : 模块, module_id : 模块ID]
	 * @param     $x 裁剪图的长 ,$y 裁剪图的宽
	 * @return    [array]                         
	 */
	public function createData($files, $param = [], $x = '150', $y = '150')
	{	
        if (empty($files)) {
			$this->error = '请选择上传文件';
			return false;
        }
        $resData = [];
        $get_filesize_byte = get_upload_max_filesize_byte();
        foreach ($files as $k=>$v) {
        	$info = '';
            $upload_name = $v['obj']->getInfo()['name'];
            if ($upload_name == 'blob') {
                $upload_name = 'logo.png';
                $new_info = [];
                $new_info = $v['obj']->getInfo();
                $new_info['name'] = $upload_name;
                $v['obj']->setUploadInfo($new_info);
            }
			$info = $v['obj']->validate(['size'=>$get_filesize_byte,'ext'=>'jpg,jpeg,png,gif,zip,rar,doc,docx,xls,xlsx,ppt,pptx,txt,pdf,csv'])->move(FILE_PATH . 'public' . DS . 'uploads'); //验证规则
			// getimagesize($file["tmp_name"]
			if (!$info) {
				$this->error = $v['obj']->getError();
				return false;
				//$resData[$k] = ['key' => $k,'name' => $fileInfo['name'],'status' => 0,'error' => $v['obj']->getError()];
				break;
			}
			$fileInfo = $info->getInfo(); //附件数据
			$rSuccess = false;
			$ext = '';
			$saveName = '';
			$thumbSaveName = '';
			if ($info) {
				//如果是图片类型，生成缩略图
	            $ext = $info->getExtension();	           
	            $saveName = $info->getSaveName();
	            $fileName = $info->getFilename();
	            if (in_array($ext, ['jpg','png','jpeg']) && $fileInfo['size'] < 8388608) {
	                // $image = \think\Image::open($v['obj']);
	                $image = \think\Image::open(UPLOAD_PATH . str_replace(DS, '/', $saveName));
	                $thumbSaveName = str_replace(DS, DS.'thumb_', $saveName);
	                $image->thumb($x, $y,\think\Image::THUMB_FILLED)->save(FILE_PATH . 'public'. DS .'uploads'. DS .$thumbSaveName); //THUMB_SCALING 或 THUMB_FILLED
	            } 
	            if ($ext == 'gif') {
	            	$thumbSaveName = $saveName;
	            } 
	            //附件信息存储
	            $saveData = [];
	            $saveData['name'] = $fileInfo['name'];
	            $saveData['size'] = $fileInfo['size'];
	            $saveData['create_user_id'] = $param['create_user_id'];
	            $saveData['create_time'] = time();
	            $saveData['file_path'] = UPLOAD_PATH . str_replace(DS, '/', $saveName);
	            $saveData['file_path_thumb'] = $thumbSaveName ? UPLOAD_PATH . str_replace(DS, '/', $thumbSaveName) : '';
	            $saveData['save_name'] = str_replace(DS, '/', $saveName);
	            $saveData['types'] = $v['types'] ? : 'file';
	            if ($k > 0) {
					$this->data($saveData)->allowField(true)->isUpdate(false)->save();
	            } else {
					$this->data($saveData)->allowField(true)->save();
	            }
	            
	            $file_id = $this->file_id;
	            if ($file_id) {
	            	$rSuccess = true;
	            	//如果是关系表，则保存关系表数据
		            if (in_array($param['module'],$this->module_arr) && $param['module_id']) {
		            	switch ($param['module']) {
		            		case 'crm_leads' : $r = db('crm_leads_file'); $r_name = 'leads_id'; break;
		            		case 'crm_customer' : $r = db('crm_customer_file'); $r_name = 'customer_id'; break;
		            		case 'crm_contacts' : $r = db('crm_contacts_file'); $r_name = 'contacts_id'; break;
		            		case 'crm_business' : $r = db('crm_business_file'); $r_name = 'business_id'; break;
		            		case 'crm_product' : $r = db('crm_product_file'); $r_name = 'product_id'; break;
		            		case 'crm_contract' : $r = db('crm_contract_file'); $r_name = 'contract_id'; break;
		            		case 'oa_log' : $r = db('oa_log_file'); $r_name = 'log_id'; break;
		            		case 'oa_examine' : $r = db('oa_examine_file'); $r_name = 'examine_id'; break;
		            		case 'work_task' : $r = db('work_task_file'); $r_name = 'task_id'; break;
		            		case 'admin_record' : $r = db('admin_record_file'); $r_name = 'record_id'; break;
		            		case 'oa_travel' : $r = db('oa_travel_file'); $r_name = 'travel_id'; break;
							case 'hrm_pact' : $r = db('hrm_pact_file'); $r_name = 'pact_id'; break;
							case 'hrm_file' : $r = db('hrm_user_file'); $r_name = 'user_id'; break;
                            case 'crm_invoice' : $r = db('crm_invoice_file'); $r_name = 'invoice_id'; break;
                            case 'crm_activity': $r = db('crm_activity_file'); $r_name = 'activity_id'; break;
                            case 'crm_visit': $r = db('crm_visit_file'); $r_name = 'visit_id'; break;
                            case 'crm_receivables': $r = db('crm_receivables_file'); $r_name = 'receivables_id'; break;
		            		default : break;
		            	}
		            	$rData = [];
		            	$rData[$r_name] = intval ($param['module_id']);
		            	$rData['file_id'] = $file_id;
		            	$rRes = $r->insert($rData);
		            	if (!$rRes) {
		            		$rSuccess = false;
							//删除文件
			            	@unlink($saveData['file_path']);
			            	@unlink($saveData['file_path_thumb']);		            		
		            	}
		            }
	            }
	            if ($rSuccess !== false) {
	            	$path =  (UPLOAD_PATH.$saveName);
	            	# 应前端要求，将url字段改成跟path一样的内容，格式改为一位数组
                    $resData = ['key' => $k, 'name' => $fileInfo['name'], 'status' => 1, 'path' => getFullPath($path), 'save_name' => str_replace(DS, '/', $saveName), 'url' => getFullPath($path), 'size' => format_bytes($fileInfo['size']), 'file_id' => $file_id];
	            } else {
	            	$resData[$k] = ['key' => $k,'name' => $fileInfo['name'],'status' => 0,'error' => '上传出错'];
	            }
			} else {
				$resData[$k] = ['key' => $k,'name' => $fileInfo['name'],'status' => 0,'error' => $v['obj']->getError()];
			}
        }
        return $resData;		
	}

	/**
	 * 修改上传文件名
	 * @param  [type] $save_name [description]
	 * @param  [type] $name      [description]
	 * @return [type]            [description]
	 */
	public function updateNameBySaveName($save_name,$name)
	{
		$flag = $this->where(['save_name' => $save_name])->setField('name',$name);
		if ( $flag ) {
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}

	/**
	 * [delFileById 删除附件]
	 * @author Michael_xu
	 * @param     $save_name 附件保存名称 
	 * @param     $param [module : 模块, module_id : 模块ID]
	 * @return    [array]                         
	 */	
	public function delFileBySaveName($save_name, $param = [])
	{
		if (!$save_name) {
			$this->error = '请选择需要删除的附件';
			return false;    
		}
		$fileInfo = $this->where(['save_name' => trim($save_name)])->find();
		if (!$fileInfo) {
			$this->error = '附件不存在或已删除';
			return false;
		}
		$file_id = $fileInfo['file_id'];
		$res = db('admin_file')->where(['file_id' => $file_id])->delete();
		if ($res) {
			@unlink($fileInfo['file_path']);//删除文件
			if ($fileInfo['file_path_thumb']) @unlink($fileInfo['file_path_thumb']);
			//处理附表信息
			if (in_array($param['module'], $this->module_arr)) {
				switch ($param['module']) {
					case 'crm_leads' : $r = db('crm_leads_file'); break;
					case 'crm_customer' : $r = db('crm_customer_file'); break;
            		case 'crm_contacts' : $r = db('crm_contacts_file'); break;
            		case 'crm_business' : $r = db('crm_business_file'); break;
            		case 'crm_product' : $r = db('crm_product_file'); break;
            		case 'crm_contract' : $r = db('crm_contract_file'); break;
            		case 'oa_log' : $r = db('oa_log_file'); break;
            		case 'oa_examine' : $r = db('oa_examine_file'); break;
            		case 'work_task' : $r = db('work_task_file'); break;
            		case 'admin_record' : $r = db('admin_record_file'); break;
            		case 'oa_travel' : $r = db('oa_travel_file'); break;
					case 'hrm_pact' : $r = db('hrm_pact_file'); break;
					case 'hrm_file' : $r = db('hrm_user_file'); break;
                    case 'crm_invoice' : $r = db('crm_invoice_file'); break;
                    case 'crm_activity': $r = db('crm_activity_file'); break;
                    case 'crm_visit': $r = db('crm_visit_file'); break;
                    case 'crm_receivables': $r = db('crm_receivables_file'); break;
            		default : break;
				}
				$resDel = $r->where(['file_id'=>$file_id])->delete();
			}
			return true;
		} else {
			$this->error = '删除失败';
			return false;
		}
	}

	/**
	 * 根据主键获取详情
	 * @author Michael_xu
	 * @param  array   $param  [description]
	 */ 
	public function getDataBySaveName($save_name = '')
	{
		if (!$save_name) {
			$this->error = '参数错误';
			return false;
		}
		$data = $this->where(['save_name' => trim($save_name)])->find();
		$data['full_path'] = getFullPath($data['file_path']);
		$data['full_path_thumb'] = getFullPath($data['file_path_thumb']);
		if (!$data) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		return $data;		
	}

	/**
	 * 根据ID获取列表
	 * @author Michael_xu
	 * @param  string   module  [类型]
	 * @param  string   by = all 全部（不分页）
	 * @param  int   module_id  [类型ID]
	 */	 
	public function getDataList($request, $by = '')
	{   
		if (!in_array($request['module'], $this->module_arr) || !$request['module_id']) {
			$this->error = '参数错误';
			return false;
		}
		$module_ids = $request['module_id'];
		if (!is_array($request['module_id'])) {
			$module_ids = array($request['module_id']);
		}
		$moduleName = $request['module'];

		switch ($request['module']) {
			case 'crm_leads' : $r = db('crm_leads_file'); $module = db('crm_leads'); break;
			case 'crm_customer' : $r = db('crm_customer_file'); $module = db('crm_customer'); break;
			case 'crm_contacts' : $r = db('crm_contacts_file'); $module = db('crm_contacts'); break;
			case 'crm_business' : $r = db('crm_business_file'); $module = db('crm_business'); break;
			case 'crm_product' : $r = db('crm_product_file'); $module = db('crm_product'); break;
			case 'crm_contract' : $r = db('crm_contract_file'); $module = db('crm_contract'); break;
			case 'oa_log' : $r = db('oa_log_file'); $module = db('oa_log'); break;
			case 'oa_examine' : $r = db('oa_examine_file'); $module = db('oa_examine'); break;
			case 'oa_examine_travel' : $r = db('oa_examine_travel_file'); $module = db('oa_examine_travel'); break;
			case 'work_task' : $r = db('work_task_file'); $module = db('task'); break;
			case 'admin_record' : $r = db('admin_record_file'); $module = db('admin_record'); break;
			case 'oa_travel' : $r = db('oa_travel_file'); $module = db('oa_travel'); break;
			case 'hrm_pact' : $r = db('hrm_pact_file'); $module = db('hrm_pact'); break;
			case 'hrm_file' : $r = db('hrm_user_file'); $module = db('admin_user'); break;
            case 'crm_invoice' : $r = db('crm_invoice_file'); $module = db('crm_invoice'); break;
            case 'crm_activity' : $r = db('crm_activity_file'); $module = db('crm_activity'); break;
            case 'crm_visit' : $r = db('crm_visit_file'); $module = db('crm_visit'); break;
            case 'crm_receivables' : $r = db('crm_receivables_file'); $module = db('crm_receivables'); break;
			default : break;
		}
		if ($r) {
			$fileIds = $r->where([$module->getPk() => ['in',$module_ids]])->column('file_id');
			$request['file_id'] = ['in', $fileIds];			
		}
		unset($request['module']);
		unset($request['module_id']);
		unset($request['by']);

        # 查询活动附件，用于判断是否可以删除附件（活动添加的附件只能在活动中删除）
        $activityFileIds = [];
        $activityType = ['crm_leads' => 1, 'crm_customer' => 2, 'crm_contacts' => 3, 'crm_business' => 5, 'crm_contract' => 6];
        if (!empty($activityType[$moduleName])) {
            $activityFileIds = db('crm_activity')->alias('activity')
                ->join('__CRM_ACTIVITY_FILE__ file', 'file.activity_id = activity.activity_id', 'LEFT')
                ->where(['type' => 1, 'activity_type' => $activityType[$moduleName], 'activity_type_id' => ['in', $module_ids]])
                ->column('file.file_id');
            $activityFileIds = array_filter($activityFileIds);
        }

		$userModel = new \app\admin\model\User();
		$request = $this->fmtRequest( $request );
        $map = $request['map'];
        $order = 'create_time desc';
        $dataCount = $this->where($map)->count('file_id');
        if ($by == 'all') {
        	$list = Db::name('AdminFile')->where($map)->order($order)->select();
        } else {
        	$list = Db::name('AdminFile')->where($map)->page($request['page'], $request['limit'])->order($order)->select();	
        }
        foreach ($list as $k=>$v) {
            $createUserInfo = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['size'] = format_bytes($v['size']); //字节转换
        	$list[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
        	$list[$k]['create_user_id_info'] = $createUserInfo;
        	$list[$k]['createName'] = !empty($createUserInfo['realname']) ? $createUserInfo['realname'] : '';
        	$list[$k]['ext'] = getExtension($v['save_name']);
        	$list[$k]['file_path'] = getFullPath($v['file_path']);
        	$list[$k]['file_path_thumb'] = getFullPath($v['file_path_thumb']);
        	$list[$k]['readOnly'] = !empty($activityType[$moduleName]) && in_array($v['file_id'], $activityFileIds) ? 1 : 0; # 活动中上次的附件，只能在活动中删除
        }
        $data = [];
        $data['list'] = $list ? : [];
        $data['dataCount'] = $dataCount ? : 0;
        return $data;		
	}
	
	/**
	 * 根据表、字段更新上传图片
	 * @author Michael_xu
	 * @param  $file 附件信息
	 * @param  $module 模块（判断权限）,一般是控制器，并且和表名一致
	 * @param  $module_id 模块ID（判断权限）
	 * @param  $file 字段
	 * @param  $thumb_field 缩略图字段
	 * @param  $x 裁剪宽度
	 * @param  $y 裁剪高度
	 */	
	public function updateByField($file, $module, $module_id, $field, $thumb_field = '', $x = '150', $y = '150')
	{
		if (empty($module) || empty($module_id) || empty($field)) {
			$this->error = '参数错误';
			return false;
		}
		
		$info = $file->move(FILE_PATH . 'public' . DS . 'uploads'); //验证规则
		$fileInfo = $info->getInfo(); //附件数据
		$saveName = '';
		$thumbSaveName = '';
		if ($info) {
			//如果是图片类型，生成缩略图
            $ext = $info->getExtension();	           
            $saveName = $info->getSaveName();
            $fileName = $info->getFilename();
            $thumbSaveName = str_replace(DS, DS.'thumb_', $saveName);
			//附件信息存储
            $saveData = [];

            if ($thumb_field) {
            	// $image = \think\Image::open($file);
            	$image = \think\Image::open(UPLOAD_PATH . str_replace(DS, '/', $saveName));
            	$thumbSaveName = str_replace(DS, DS.'thumb_', $saveName);
            	$image->thumb($x, $y,\think\Image::THUMB_FILLED)->save(FILE_PATH . 'public'. DS .'uploads'. DS .$thumbSaveName); //THUMB_SCALING 或 THUMB_FILLED
            	$saveData[$thumb_field] = $thumbSaveName ? UPLOAD_PATH . str_replace(DS, '/', $thumbSaveName) : '';
            }
            $saveData[$field] = UPLOAD_PATH . str_replace(DS, '/', $saveName);  
            switch ($module) {
            	case 'crm_customer' : $moduleModel = new \app\crm\model\Customer(); break;
				case 'crm_contacts' : $moduleModel = new \app\crm\model\Contacts(); break;
				case 'crm_business' : $moduleModel = new \app\crm\model\Business(); break;
				case 'crm_product' : $moduleModel = new \app\crm\model\Product(); break;
				case 'crm_contract' : $moduleModel = new \app\crm\model\Contract(); break;		
				case 'User' : $moduleModel = new \app\admin\model\User(); break;
				case 'admin_system': $moduleModel = new \app\admin\model\System(); break;
                case 'crm_invoice' : $moduleModel = new Invoice(); break;
                case 'crm_activity' : $moduleModel = new Activity(); break;
                case 'crm_receivables' : $moduleModel = new \app\crm\model\Receivables(); break;
                case 'crm_visit' : $moduleModel = new \app\crm\model\Visit(); break;
            }

            $resFile = $moduleModel->allowField([$field,$thumb_field])->save($saveData, [$moduleModel->getPk() => $module_id]);
            if (!$resFile) {
				$this->error = '上传失败';
            	return false;            	
            }
            return true;
        }		
	}

	/**
	 * 根据ID保存，处理逻辑关系
	 * @author Michael_xu
	 * @param  $ids 附件ID数组
	 * @param  $module 表名
	 * @param  $module_id 模块ID
	 */	
	public function createDataById($ids, $module, $module_id)
	{
		if (!$ids || !in_array($module, $this->module_arr) || !$module_id) {
			$this->error = '参数错误';
			return false;
		}

		switch (trim($module)) {
			case 'crm_customer' : $rDb = db('crm_customer_file'); $r_name = 'customer_id'; break;
			case 'crm_contacts' : $rDb = db('crm_contacts_file'); $r_name = 'contacts_id'; break;
			case 'crm_business' : $rDb = db('crm_business_file'); $r_name = 'business_id'; break;
			case 'crm_product' : $rDb = db('crm_product_file'); $r_name = 'product_id'; break;
			case 'crm_contract' : $rDb = db('crm_contract_file'); $r_name = 'contract_id'; break;
			case 'oa_log' : $rDb = db('oa_log_file'); $r_name = 'log_id'; break;
			case 'oa_examine' : $rDb = db('oa_examine_file'); $r_name = 'examine_id'; break;
			case 'oa_examine_travel' : $rDb = db('oa_examine_travel_file'); $r_name = 'travel_id'; break;
			case 'admin_record' : $rDb = db('admin_record_file'); $r_name = 'record_id'; break;
			case 'oa_travel' : $rDb = db('oa_travel_file'); $r_name = 'travel_id'; break;
            case 'crm_invoice' : $rDb = db('crm_invoice_file'); $r_name = 'invoice_id'; break;
            case 'crm_activity' : $rDb = db('crm_activity_file'); $r_name = 'activity_id'; break;
            case 'crm_visit' : $rDb = db('crm_visit_file'); $r_name = 'activity_id'; break;
            case 'crm_receivables' : $rDb = db('crm_receivables_file'); $r_name = 'receivables_id'; break;
		}

		$res_success = true;
		$data = [];
		$data[$r_name] = intval ($module_id);
		foreach ($ids as $v) {
			$data['file_id'] = intval($v);
			if (!$rDb->insert($data)) {
				$res_success = false;
			}
		}
		if ($res_success == false) {
			$this->error = '附件上传失败';
		}
		return true;
	}


	/**
	 * 删除关系表附件
	 * @author Michael_xu
	 * @param  $module 模块
	 * @param  $module_id 模块ID
	 */	
	public function delRFileByModule($module, $module_id)
	{
		if (in_array($module,$this->module_arr) && $module_id) {
        	switch ($module) {
        		case 'crm_leads' : 
        			$r = db('crm_leads_file'); 
        			$r_name = 'leads_id'; 
        			break;
        		case 'crm_customer' : 
        			$r = db('crm_customer_file'); 
        			$r_name = 'customer_id'; 
        			break;
        		case 'crm_contacts' : 
        			$r = db('crm_contacts_file'); 
        			$r_name = 'contacts_id'; 
        			break;
        		case 'crm_business' : 
        			$r = db('crm_business_file'); 
        			$r_name = 'business_id'; 
        			break;
        		case 'crm_product' : 
        			$r = db('crm_product_file'); 
        			$r_name = 'product_id'; 
        			break;
        		case 'crm_contract' : 
        			$r = db('crm_contract_file'); 
        			$r_name = 'contract_id'; 
        			break;
        		case 'oa_log' : 
        			$r = db('oa_log_file'); 
        			$r_name = 'log_id'; 
        			break;
        		case 'oa_examine' : 
        			$r = db('oa_examine_file'); 
        			$r_name = 'examine_id'; 
        			break;
        		case 'work_task' : 
        			$r = db('work_task_file'); 
        			$r_name = 'task_id'; 
        			break;
        		case 'admin_record' : 
        			$r = db('admin_record_file'); 
        			$r_name = 'record_id'; 
        			break;
        		case 'oa_travel' : 
        			$r = db('oa_travel_file'); 
        			$r_name = 'travel_id'; 
        			break;
				case 'hrm_pact' : 
					$r = db('hrm_pact_file'); 
					$r_name = 'pact_id'; 
					break;
				case 'hrm_file' : 
					$r = db('hrm_user_file'); 
					$r_name = 'user_id';
					break;
                case 'crm_invoice' :
                    $r = db('crm_invoice_file');
                    $r_name = 'invoice_id'; break;
                case 'crm_visit' :
                    $r = db('crm_visit_file');
                    $r_name = 'visit_id';
                    break;
                case 'crm_activity' :
                    $r = db('crm_activity_file');
                    $r_name = 'activity_id';
                    break;
                case 'crm_receivables' :
                    $r = db('crm_receivables_file');
                    $r_name = 'receivables_id';
        		default : break;
        	}
        	$rWhere = [];
        	if(!is_array($module_id)){
        		$module_id = [intval ($module_id)];
        	}
        	$rWhere[$r_name] = ['in',$module_id];
        	$rFileIds = $r->where($rWhere)->column('file_id');
        	$rRes = $r->where($rWhere)->delete();
        	if ($rRes && $rFileIds) {
        		$fileList = db('admin_file')->where(['file_id' => ['in',$rFileIds]])->field('file_path,file_path_thumb')->select();
        		foreach ($fileList as $v) {
					//删除文件
	            	@unlink($v['file_path']);
	            	@unlink($v['file_path_thumb']);
        		}		            		
        	}
        }		
	}

    /**
     * 全部删除(活动、产品)
     *
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
	public function deleteAll($param)
    {
        if (!empty($param['module']) && !empty($param['module_id'])) {
            $module     = db('crm_activity_file');
            $primaryKey = 'activity_id';

            switch ($param['module']) {
                case 'crm_activity' : $module = db('crm_activity_file'); $primaryKey = 'activity_id'; break;
                case 'crm_product' : $module = db('crm_product_file'); $primaryKey = 'product_id'; break;
            }

            $fileIds = $module->where($primaryKey, $param['module_id'])->column('file_id');

            # 删除关联数据
            $module->where($primaryKey, $param['module_id'])->delete();

            # 删除附件
            db('admin_file')->whereIn('file_id', $fileIds)->delete();
        }

        if (!empty($param['file_id'])) {
            db('admin_file')->whereIn('file_id', $param['file_id'])->delete();
        }

        return true;
    }
}