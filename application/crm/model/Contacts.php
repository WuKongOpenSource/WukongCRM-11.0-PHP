<?php
// +----------------------------------------------------------------------
// | Description: 联系人
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Contacts extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_contacts';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	/**
     * [getDataList 联系人list]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */		
	public function getDataList($request)
    {  	
    	$userModel = new \app\admin\model\User();
    	$structureModel = new \app\admin\model\Structure();
    	$fieldModel = new \app\admin\model\Field();
    	$customerModel = new \app\crm\model\Customer();
    	$search = $request['search'];
    	$user_id = $request['user_id'];
    	$scene_id = (int)$request['scene_id'];
    	$is_excel = $request['is_excel']; //导出
    	$business_id = $request['business_id'];
		$order_field = $request['order_field'];
    	$order_type = $request['order_type'];
    	$pageType = $request['pageType']; 
    	$getCount = $request['getCount'];
		//需要过滤的参数
    	$unsetRequest = ['scene_id','search','user_id','is_excel','action','order_field','order_type','is_remind','getCount','type','otherMap','business_id','check_status'];
    	foreach ($unsetRequest as $v) {
    		unset($request[$v]);
    	}

        $request = $this->fmtRequest( $request );
        $requestMap = $request['map'] ? : [];

		$sceneModel = new \app\admin\model\Scene();
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'contacts') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('crm_contacts', $user_id) ? : [];
		}
		$searchMap = [];
		if ($search) {
			//普通筛选
			$searchMap = function($query) use ($search){
			        $query->where('contacts.name',array('like','%'.$search.'%'))
			        	->whereOr('contacts.mobile',array('like','%'.$search.'%'))
			        	->whereOr('contacts.telephone',array('like','%'.$search.'%'));
			};			
			// $sceneMap['name'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '联系人姓名'];
		}
		//优先级：普通筛选>高级筛选>场景
		$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		//高级筛选
		$map = where_arr($map, 'crm', 'contacts', 'index');		
		//权限
		$a = 'index';
		if ($is_excel) $a = 'excelExport';		
		$authMap = [];
		$auth_user_ids = $userModel->getUserByPer('crm', 'contacts', $a);
		if (isset($map['contacts.owner_user_id']) && $map['contacts.owner_user_id'][0] != 'like') {
			if (!is_array($map['contacts.owner_user_id'][1])) {
				$map['contacts.owner_user_id'][1] = [$map['contacts.owner_user_id'][1]];
			}
			if ($map['contacts.owner_user_id'][0] == 'neq') {
				$auth_user_ids = array_diff($auth_user_ids, $map['contacts.owner_user_id'][1]) ? : [];	//取差集	
			} else {
				$auth_user_ids = array_intersect($map['contacts.owner_user_id'][1], $auth_user_ids) ? : [];	//取交集	
			}
	        unset($map['contacts.owner_user_id']);
	    }		    
	    $auth_user_ids = array_merge(array_unique(array_filter($auth_user_ids))) ? : ['-1'];
	    //负责人、相关团队
	    $authMap['contacts.owner_user_id'] = ['in',$auth_user_ids];		
		//联系人商机
		if ($business_id) {
			$contacts_id = Db::name('crm_contacts_business')->where(['business_id' => $business_id])->column('contacts_id');
			if ($contacts_id) {
		    	$map['contacts.contacts_id'] = array('in',$contacts_id);
		    }else{
		    	$map['contacts.contacts_id'] = array('eq',-1);
		    }
		}	    
		//列表展示字段
		$indexField = $fieldModel->getIndexField('crm_contacts', $user_id, 1) ? : array('name');
		$userField = $fieldModel->getFieldByFormType('crm_contacts', 'user'); //人员类型
		$structureField = $fieldModel->getFieldByFormType('crm_contacts', 'structure');  //部门类型			

		//排序
		if ($order_type && $order_field) {
			$order = $fieldModel->getOrderByFormtype('crm_contacts','contacts',$order_field,$order_type);
		} else {
			$order = 'contacts.update_time desc';
		}
		$readAuthIds = $userModel->getUserByPer('crm', 'contacts', 'read');
        $updateAuthIds = $userModel->getUserByPer('crm', 'contacts', 'update');
        $deleteAuthIds = $userModel->getUserByPer('crm', 'contacts', 'delete');	
       	$customerWhere = [];
        if ($pageType == !'all') {
			//非客户池条件
        	$customerWhere = $customerModel->getWhereByCustomer();
        }
		$dataCount = db('crm_contacts')
        			->alias('contacts')
        			->join('__CRM_CUSTOMER__ customer','contacts.customer_id = customer.customer_id','LEFT')
        			->where($map)
        			->where($searchMap)
        			->where($authMap)
        			->where($customerWhere)
        			->count('contacts_id'); 
		if ($getCount == 1) {
			$data['dataCount'] = $dataCount ? : 0;
	        return $data;
        }               
		$list = db('crm_contacts')
				->alias('contacts')
				->join('__CRM_CUSTOMER__ customer','contacts.customer_id = customer.customer_id','LEFT')
				->where($map)
				->where($searchMap)
				->where($authMap)
				->where($customerWhere)
        		->limit($request['offset'], $request['length'])
        		->field('contacts.*,customer.name as customer_name')
        		->field(implode(',',$indexField).',customer.name as customer_name')
        		->orderRaw($order)
        		->select();
        
        foreach ($list as $k=>$v) {
        	$list[$k]['create_user_id_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
        	$list[$k]['customer_id_info']['customer_id'] = $v['customer_id'] ? : '';
        	$list[$k]['customer_id_info']['name'] = $v['customer_name'] ? : '';
			foreach ($userField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $userModel->getListByStr($v[$val]) : [];
        	}
			foreach ($structureField as $key => $val) {
        		$list[$k][$val.'_info'] = isset($v[$val]) ? $structureModel->getDataByStr($v[$val]) : [];
        	} 

			//权限
			$permission = [];
			$is_read = 0;
			$is_update = 0;
			$is_delete = 0;
			if (in_array($v['owner_user_id'],$readAuthIds)) $is_read = 1;
			if (in_array($v['owner_user_id'],$updateAuthIds)) $is_update = 1;
			if (in_array($v['owner_user_id'],$deleteAuthIds)) $is_delete = 1;	        
	        $permission['is_read'] = $is_read;
	        $permission['is_update'] = $is_update;
	        $permission['is_delete'] = $is_delete;
	        $list[$k]['permission']	= $permission;

            # 下次联系时间
            $list[$k]['next_time'] = !empty($v['next_time']) ? date('Y-m-d H:i:s', $v['next_time']) : null;

            # 关注
            $starWhere = ['user_id' => $user_id, 'target_id' => $v['contacts_id'], 'type' => 'crm_contacts'];
            $star = Db::name('crm_star')->where($starWhere)->value('star_id');
            $list[$k]['star'] = !empty($star) ? 1 : 0;
            # 日期
            $list[$k]['create_time'] = !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : null;
            $list[$k]['update_time'] = !empty($v['update_time']) ? date('Y-m-d H:i:s', $v['update_time']) : null;
            $list[$k]['last_time']   = !empty($v['last_time'])   ? date('Y-m-d H:i:s', $v['last_time'])   : null;
            # 创建人
            $list[$k]['create_user_name'] = !empty($list[$k]['create_user_id_info']['realname']) ? $list[$k]['create_user_id_info']['realname'] : '';
            # 负责人
            $list[$k]['owner_user_name'] = !empty($list[$k]['owner_user_id_info']['realname']) ? $list[$k]['owner_user_id_info']['realname'] : '';
        }
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;

        return $data;
    }

	/**
	 * 创建联系人主表信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{
	    $businessId = $param['business_id'];
	    unset($param['business_id']);

		$fieldModel = new \app\admin\model\Field();
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);
		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}
		# 处理客户首要联系人
        $primaryStatus = Db::name('crm_contacts')->where('customer_id', $param['customer_id'])->value('contacts_id');
        if (!empty($param['primary']) && $param['primary'] == 1 && !empty($primaryStatus)) {
            # 设置首要联系人，去除其他首要联系人状态
            Db::name('crm_contacts')->where('customer_id', $param['customer_id'])->update(['primary' => 0]);
        }
		if (!empty($param['customer_id']) && empty($primaryStatus)) {
		    # 为客户添加第一个联系人默认设置成首要联系人
            $param['primary'] = 1;
        }

        # 处理下次联系时间
        if (!empty($param['next_time'])) $param['next_time'] = strtotime($param['next_time']);

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_contacts');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}
		if ($this->data($param)->allowField(true)->isUpdate(false)->save()) {
			updateActionLog($param['create_user_id'], 'crm_contacts', $this->contacts_id, '', '', '创建了联系人');			
			$data = [];
			$data['contacts_id'] = $this->contacts_id;

            # 添加活动记录
            Db::name('crm_activity')->insert([
                'type'             => 2,
                'activity_type'    => 3,
                'activity_type_id' => $data['contacts_id'],
                'content'          => $param['name'],
                'create_user_id'   => $param['create_user_id'],
                'update_time'      => time(),
                'create_time'      => time(),
                'customer_ids'      => $param['customer_id']
            ]);

            # 处理商机首要联系人
            if (!empty($businessId)) {
                Db::name('crm_business')->where('business_id', $businessId)->update(['contacts_id' => $data['contacts_id']]);
            }

			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}
	
	//根据IDs获取数组
	public function getDataByStr($idstr)
	{
		$idArr = stringToArray($idstr);
		if (!$idArr) {
			return [];
		}
		$list = Db::name('CrmContacts')->where(['contacts_id' => ['in',$idArr]])->select();
		return $list;
	}

    /**
     * 编辑联系人主表信息
     *
     * @param $param
     * @param string $contacts_id
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
	public function updateDataById($param, $contacts_id = '')
	{
		$userModel = new \app\admin\model\User();
		$dataInfo = $this->getDataById($contacts_id);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		//判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'contacts', 'update');
        if (!in_array($dataInfo['owner_user_id'],$auth_user_ids)) {
            $this->error = '无权操作';
            return false;
        } 		

		$param['contacts_id'] = $contacts_id;
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted','delete_time'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		$fieldModel = new \app\admin\model\Field();
		// 自动验证
		$validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
		$validate = new Validate($validateArr['rule'], $validateArr['message']);

		$result = $validate->check($param);
		if (!$result) {
			$this->error = $validate->getError();
			return false;
		}

        # 处理下次联系时间
        if (!empty($param['next_time'])) $param['next_time'] = strtotime($param['next_time']);

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_contacts');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

        # 处理首要联系人
        $primaryStatus = Db::name('crm_contacts')->where('customer_id', $param['customer_id'])->value('contacts_id');
        if (!empty($param['primary']) && $param['primary'] == 1 && !empty($primaryStatus)) {
            # 设置首要联系人，去除其他首要联系人状态
            Db::name('crm_contacts')->where('customer_id', $param['customer_id'])->update(['primary' => 0]);
        }
        if (!empty($param['customer_id']) && empty($primaryStatus)) {
            # 为客户添加第一个联系人默认设置成首要联系人
            $param['primary'] = 1;
        }

		if ($this->update($param, ['contacts_id' => $contacts_id], true)) {
			//修改记录
			updateActionLog($param['user_id'], 'crm_contacts', $contacts_id, $dataInfo, $param);
			$data = [];
			$data['contacts_id'] = $contacts_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

    /**
     * 联系人数据
     *
     * @param string $id
     * @param int $userId
     * @return Common|array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
   	public function getDataById($id = '', $userId = 0)
   	{   		
   		$map['contacts_id'] = $id;
		$dataInfo = db('crm_contacts')->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = isset($dataInfo['create_user_id']) ? $userModel->getUserById($dataInfo['create_user_id']) : [];
		$dataInfo['owner_user_id_info'] = isset($dataInfo['owner_user_id']) ? $userModel->getUserById($dataInfo['owner_user_id']) : []; 
		$dataInfo['customer_id_info'] = db('crm_customer')->where(['customer_id' => $dataInfo['customer_id']])->field('customer_id,name,mobile,telephone,deal_status')->find();
        $dataInfo['customer_name'] = !empty($dataInfo['customer_id_info']['name']) ? $dataInfo['customer_id_info']['name'] : '';
        $dataInfo['create_user_name'] = !empty($dataInfo['create_user_id_info']['realname']) ? $dataInfo['create_user_id_info']['realname'] : '';
        $dataInfo['owner_user_name'] = !empty($dataInfo['owner_user_id_info']['realname']) ? $dataInfo['owner_user_id_info']['realname'] : '';
        # 关注
        $starId = empty($userId) ? 0 : Db::name('crm_star')->where(['user_id' => $userId, 'target_id' => $id, 'type' => 'crm_contacts'])->value('star_id');
        $dataInfo['star'] = !empty($starId) ? 1 : 0;
        # 处理决策人显示问题
        $dataInfo['decision'] = !empty($dataInfo['decision']) && $dataInfo['decision'] == '是' ? '是' : '';
        # 处理时间格式
        $dataInfo['next_time']   = !empty($dataInfo['next_time'])   ? date('Y-m-d H:i:s', $dataInfo['next_time'])   : null;
        $dataInfo['create_time'] = !empty($dataInfo['create_time']) ? date('Y-m-d H:i:s', $dataInfo['create_time']) : null;
        $dataInfo['update_time'] = !empty($dataInfo['update_time']) ? date('Y-m-d H:i:s', $dataInfo['update_time']) : null;
        $dataInfo['last_time']   = !empty($dataInfo['last_time'])   ? date('Y-m-d H:i:s', $dataInfo['last_time'])   : null;
		return $dataInfo;
   	}

	/**
     * [联系人转移]
     * @author Michael_xu
     * @param ids 联系人ID数组
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @return            
     */	
    public function transferDataById($ids, $owner_user_id, $type = 1, $is_remove)
    {
	    $settingModel = new \app\crm\model\Setting();      	
    	foreach ($ids as $id) {
			$data = [];
	        $data['owner_user_id'] = $owner_user_id;
	        $data['update_time'] = time(); 
	        db('crm_contacts')->where(['contacts_id' => $id])->update($data);
    	}
    	return true;
    }

    /**
     * 设置首要联系人
     *
     * @param $customerId
     * @param $contactsId
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setPrimary($customerId, $contactsId)
    {
        Db::name('crm_contacts')->where('customer_id', $customerId)->update(['primary' => 0]);
        Db::name('crm_contacts')->where(['customer_id' => $customerId, 'contacts_id' => $contactsId])->update(['primary' => 1]);

        return true;
    }

    /**
     * 获取跟进记录联系人
     *
     * @param $customerId
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getContactsList($customerId)
    {
        return Db::name('crm_contacts')->field(['contacts_id', 'name', 'mobile', 'telephone', 'detail_address'])->where('customer_id', $customerId)->order('primary', 'desc')->select();
    }

    /**
     * 获取系统信息
     *
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSystemInfo($id)
    {
        # 联系人
        $contacts = Db::name('crm_contacts')->field(['create_user_id', 'create_time', 'update_time'])->where('contacts_id', $id)->find();
        # 创建人
        $realname = Db::name('admin_user')->where('id', $contacts['create_user_id'])->value('realname');
        # 跟进时间
        $followTime = Db::name('crm_activity')->where(['type' => 1, 'activity_type' => 3, 'activity_type_id' => $id])->order('activity_id', 'desc')->value('update_time');

        return [
            'create_user_name' => $realname,
            'create_time'      => date('Y-m-d H:i:s', $contacts['create_time']),
            'update_time'      => date('Y-m-d H:i:s', $contacts['update_time']),
            'follow_time'      => !empty($followTime) ? date('Y-m-d H:i:s', $followTime) : ''
        ];
    }
}