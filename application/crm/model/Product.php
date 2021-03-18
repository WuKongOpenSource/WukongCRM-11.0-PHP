<?php
// +----------------------------------------------------------------------
// | Description: 产品
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use app\admin\model\User as UserModel;
use app\admin\model\File as FileModel;
use think\Request;
use think\Validate;
use traits\model\SoftDelete;

class Product extends Common
{
	use SoftDelete;
	protected $deleteTime = 'delete_time';

	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_product';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

    /**
     * [getDataList 产品list]
     *
     * @param $request
     * @return array
     */
	public function getDataList($request)
    {
    	$userModel = new \app\admin\model\User();
    	$structureModel = new \app\admin\model\Structure();
    	$fieldModel = new \app\admin\model\Field();
		$search = $request['search'];
    	$user_id = $request['user_id'];
    	$is_excel = $request['is_excel']; //导出
    	$scene_id = (int)$request['scene_id'];
		$order_field = $request['order_field'];
    	$order_type = $request['order_type'];
    	$isStatus = !empty($request['is_status']) ? $request['is_status'] : 0;
		unset($request['scene_id']);
		unset($request['search']);
		unset($request['user_id']);
		unset($request['order_field']);
		unset($request['order_type']);
		unset($request['is_status']);

        $request = $this->fmtRequest($request);
        $requestMap = $request['map'] ? : [];

		$sceneModel = new \app\admin\model\Scene();
		if ($scene_id) {
			//自定义场景
			$sceneMap = $sceneModel->getDataById($scene_id, $user_id, 'product') ? : [];
		} else {
			//默认场景
			$sceneMap = $sceneModel->getDefaultData('crm_product', $user_id) ? : [];
		}
		if ($search || $search == '0') {
			//普通筛选
			$sceneMap['name'] = ['condition' => 'contains','value' => $search,'form_type' => 'text','name' => '产品名称'];
		}
		//优先级：普通筛选>高级筛选>场景
		$map = $requestMap ? array_merge($sceneMap, $requestMap) : $sceneMap;
		//高级筛选
		$map = where_arr($map, 'crm', 'product', 'index');
		if (!empty($isStatus)) {
			$map['product.status'] = '上架';
		}
		if (empty($map['product.delete_user_id'])) {
            $map['product.delete_user_id'] = 0;
        }
		//权限
        $a = 'index';
        if ($is_excel) $a = 'excelExport';
        $auth_user_ids = $userModel->getUserByPer('crm', 'product', $a);
		//过滤权限
        if (isset($map['product.owner_user_id']) && $map['product.owner_user_id'][0] != 'like') {
            if (!is_array($map['product.owner_user_id'][1])) {
                $map['product.owner_user_id'][1] = [$map['product.owner_user_id'][1]];
            }
            if (in_array($map['product.owner_user_id'][0], ['neq', 'notin'])) {
                $auth_user_ids = array_diff($auth_user_ids, $map['product.owner_user_id'][1]) ?: [];    //取差集
            } else {
                $auth_user_ids = array_intersect($map['product.owner_user_id'][1], $auth_user_ids) ?: [];    //取交集
            }
            unset($map['product.owner_user_id']);
        }
        $auth_user_ids = array_merge(array_unique(array_filter($auth_user_ids))) ?: ['-1'];
        //负责人
        $authMap['product.owner_user_id'] = ['in', $auth_user_ids];

		//列表展示字段
		$indexField = $fieldModel->getIndexField('crm_product', $user_id, 1) ? : ['name'];
		$userField = $fieldModel->getFieldByFormType('crm_product', 'user'); //人员类型
		$structureField = $fieldModel->getFieldByFormType('crm_product', 'structure');  //部门类型
        $datetimeField = $fieldModel->getFieldByFormType('crm_product', 'datetime'); //日期时间类型
        # 处理人员和部门类型的排序报错问题(前端传来的是包含_name的别名字段)
        $temporaryField = str_replace('_name', '', $order_field);
        if (in_array($temporaryField, $userField) || in_array($temporaryField, $structureField)) {
            $order_field = $temporaryField;
        }
		//排序
		if ($order_type && $order_field) {
			$order = $fieldModel->getOrderByFormtype('crm_product','product',$order_field,$order_type);
		} else {
			$order = 'product.update_time desc';
		}

		$join = [
			['__CRM_PRODUCT_CATEGORY__ product_category', 'product_category.category_id = product.category_id', 'LEFT'],
		];
        $map['product.delete_user_id']=0;
		$list = db('crm_product')->alias('product')
				->join($join)
				->where($map)
				->where($authMap)
        		->limit($request['offset'], $request['length'])
				->field($indexField)
				->field('product.*,product_category.name as category_name')
        		->orderRaw($order)
        		->select();
        $dataCount = db('crm_product')->alias('product')
					 ->where($map)->where($authMap)
				     ->count('product_id');
        foreach ($list as $k=>$v) {
        	$list[$k]['create_user_id_info'] = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
        	$list[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
        	$list[$k]['create_user_name'] = !empty($list[$k]['create_user_id_info']['realname']) ? $list[$k]['create_user_id_info']['realname'] : '';
            $list[$k]['owner_user_name'] = !empty($list[$k]['owner_user_id_info']['realname']) ? $list[$k]['owner_user_id_info']['realname'] : '';
        	foreach ($userField as $key => $val) {
                $usernameField = !empty($v[$val]) ? db('admin_user')->whereIn('id', stringToArray($v[$val]))->column('realname') : [];
                $list[$k][$val.'_name'] = implode($usernameField, ',');
        	}
			foreach ($structureField as $key => $val) {
                $structureNameField = !empty($v[$val]) ? db('admin_structure')->whereIn('id', stringToArray($v[$val]))->column('name') : [];
                $list[$k][$val.'_name'] = implode($structureNameField, ',');
        	}
            foreach ($datetimeField as $key => $val) {
                $list[$k][$val] = !empty($v[$val]) ? date('Y-m-d H:i:s', $v[$val]) : null;
            }
        	//产品类型
			$list[$k]['category_id_info'] = $v['category_name'];
            # 处理日期格式
            $list[$k]['create_time'] = !empty($v['create_time']) ? date('Y-m-d H:i:s', $v['create_time']) : null;
            $list[$k]['update_time'] = !empty($v['update_time']) ? date('Y-m-d H:i:s', $v['update_time']) : null;
		}
		$data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;

        return $data;
    }

	/**
	 * 创建产品主表信息
	 * @author Michael_xu
	 * @param
	 * @return
	 */
	public function createData($param)
	{
		$fieldModel = new \app\admin\model\Field();
		$productCategoryModel = model('ProductCategory');
        $dataInfo = db('crm_product')->where(['name'=>$param['name'],'delete_user_id'=>0])->find();
        if(isset($dataInfo)){
            // 自动验证
            $validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
            $validate = new Validate($validateArr['rule'], $validateArr['message']);
    
            $result = $validate->check($param);
            if (!$result) {
                $this->error = $validate->getError();
                return false;
            }
        }
		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_product');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		//产品分类
		$category_id = $param['category_id'];

		if (is_array($category_id)) {
			$param['category_id'] = $productCategoryModel->getIdByStr($category_id);
			$param['category_str'] = arrayToString($category_id);
		}
        if(!is_int($category_id)){
            $list=db('crm_product_category')->column('category_id','name');
            foreach ($list as $k=>$v){
                if($k==$param['category_id']){
                    $param['category_id']=$v;
                }
            }
        }

		if ($this->data($param)->allowField(true)->isUpdate(false)->save()) {
			updateActionLog($param['create_user_id'], 'crm_product', $this->product_id, '', '', '创建了产品');
			$data = [];
			$data['product_id'] = $this->product_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}
	}

	/**
	 * 编辑产品主表信息
	 * @author Michael_xu
	 * @param
	 * @return
	 */
	public function updateDataById($param, $product_id = '')
	{
        $userModel = new \app\admin\model\User();
		$dataInfo = $this->getDataById($product_id);
		$productCategoryModel = model('ProductCategory');
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		//判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'product', 'update');
        if (!in_array($dataInfo['owner_user_id'], $auth_user_ids)) {
            $this->error = '无权操作';
            return false;
        }

		$param['product_id'] = $product_id;
		//过滤不能修改的字段
		$unUpdateField = ['create_user_id','is_deleted'];
		foreach ($unUpdateField as $v) {
			unset($param[$v]);
		}
		
		$fieldModel = new \app\admin\model\Field();
            // 自动验证
//            $validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
//            $validate = new Validate($validateArr['rule'], $validateArr['message']);
//
//            $result = $validate->check($param);
//            if (!$result) {
//                $this->error = $validate->getError();
//                return false;
//            }
		

		//处理部门、员工、附件、多选类型字段
		$arrFieldAtt = $fieldModel->getArrayField('crm_product');
		foreach ($arrFieldAtt as $k=>$v) {
			$param[$v] = arrayToString($param[$v]);
		}

		//产品分类
		$category_id = $param['category_id'];
//		if (is_array($category_id)) {
//			$param['category_id'] = $productCategoryModel->getIdByStr($category_id);
//			$param['category_str'] = arrayToString($category_id);
//		}
        if(!is_int($category_id)){
            $list=db('crm_product_category')->column('category_id','name');
            $param['category_id']=1;
            foreach ($list as $k=>$v){
                if($k==$category_id){
                    $param['category_id']=$v;
                }
            }
        }
		if ($this->update($param, ['product_id' => $product_id], true)) {
			//修改记录
			updateActionLog($param['user_id'], 'crm_product', $product_id, $dataInfo, $param);
			$data = [];
			$data['product_id'] = $product_id;
			return $data;
		} else {
			$this->rollback();
			$this->error = '编辑失败';
			return false;
		}
	}

    /**
     * 产品数据
     *
     * @param string $id
     * @return Common|array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
   	public function getDataById($id = '')
   	{
   		$map['product_id'] = $id;
   		$map['delete_user_id'] = 0;
		$dataInfo = db('crm_product')->where($map)->find();
		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}

		# 获取封面图片
        $dataInfo['cover_images'] = $this->getProductImages($dataInfo['cover_images']);
		# 获取详情图片
        $dataInfo['details_images'] = $this->getProductImages($dataInfo['details_images']);

		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_id_info'] = $userModel->getUserById($dataInfo['create_user_id']);
        $dataInfo['category_id_info'] = db('crm_product_category')->where(['category_id' => $dataInfo['category_id']])->value('name');
		# 处理日期格式
        $fieldModel = new \app\admin\model\Field();
        $datetimeField = $fieldModel->getFieldByFormType('crm_product', 'datetime'); //日期时间类型
        foreach ($datetimeField as $key => $val) {
            $dataInfo[$val] = !empty($dataInfo[$val]) ? date('Y-m-d H:i:s', $dataInfo[$val]) : null;
        }
        $dataInfo['create_time'] = !empty($dataInfo['create_time']) ? date('Y-m-d H:i:s', $dataInfo['create_time']) : null;
        $dataInfo['update_time'] = !empty($dataInfo['update_time']) ? date('Y-m-d H:i:s', $dataInfo['update_time']) : null;
        return $dataInfo;
   	}

    /**
     * 获取产品图片
     *
     * @param $fileIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
   	private function getProductImages($fileIds)
    {
        $files = Db::name('admin_file')->whereIn('file_id', $fileIds)->select();

        foreach ($files AS $key => $value) {
            $files[$key]['file_path'] = getFullPath($value['file_path']);
            $files[$key]['file_path_thumb'] = getFullPath($value['file_path_thumb']);
            $files[$key]['size'] = format_bytes($value['size']);
        }

        return $files;
    }

	/**
     * 相关产品创建（商机、合同相关产品数据）
     * @param  types 类型
     * @param  param['product'] 产品相关数据
     * @param  price 产品单价
     * @param  sales_price  销售价格
     * @param  num 数量
     * @param  discount 折扣
     * @param  subtotal 小计（折扣后价格）
     * @param  unit 单位
     * @param  total_price 折扣后整单总价
     * @param  discount_rate 整单折扣
     * @param  objId 关联对象ID
     * @return
     */
    public function createObject($types, $param, $objId)
    {
    	switch ($types) {
    		case 'crm_business' : $db = 'crm_business_product'; $rDb = 'crm_business'; $db_id = 'business_id'; break;
    		case 'crm_contract' : $db = 'crm_contract_product'; $rDb = 'crm_contract'; $db_id = 'contract_id'; break;
    		default : $this->error = '参数错误'; return false; break;
    	}

    	$total_price = 0;

    	if ($param['product']) {
			$product = [];
			// 启动事务
			Db::startTrans();
			try {
				foreach ($param['product'] as $key => $value) {
					$discount = 0;
			    	// $discount = ((100 - $value['discount']) > 0) ? (100 - $value['discount'])/100 : 0;	//折扣
					$product[$key]['product_id'] = $value['product_id'];
		    		$product[$key]['price'] = $value['price']; //产品单价
		    		$product[$key]['sales_price'] = $value['sales_price']; //售价
		    		$product[$key]['num'] = $value['num']; //数量
		    		$product[$key]['discount'] = $value['discount']; //折扣
		    		$product[$key]['unit'] = $value['unit'] ? : ''; //单位
		    		$product[$key]['subtotal'] = $value['subtotal'];
		    		// $total_price += $product[$key]['subtotal'] = round(($value['price'] * $value['num']) * $discount); //总价
		    		$product[$key][$db_id] = $objId;
		    	}

		    	//删除
		    	db($db)->where([$db_id => $objId])->delete(); //原数据删除
				//新增
		    	db($db)->insertAll($product);

		    	$rData = [];
				//产品合计
				$rData['discount_rate'] = !empty($param['discount_rate']) ? $param['discount_rate'] : 0.00; //整单折扣
		    	$discount_rate = ((100 - $rData['discount_rate']) > 0) ? (100 - $rData['discount_rate'])/100 : 0;
		    	// $rData['total_price'] = $total_price ? $total_price*$discount_rate : '0.00'; //整单合计
		    	$rData['total_price'] = $param['total_price'] ? : '0.00'; //整单合计
		    	db($rDb)->where([$db_id => $objId])->update($rData);
		    	
		    	// 提交事务
    			Db::commit();
		    	return true;
			} catch (\Exception $e) {
				$this->error = '产品数据创建出错';
				// 回滚事务
			    Db::rollback();
	    		return false;
			}
    	} else {
    		//删除产品信息
    		db($db)->where([$db_id => $objId])->delete();
    		return true;
    	}
    }

    /**
     * [产品统计]
     *
     * @param $param
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function getStatistics($param)
    {
    	$userModel = new \app\admin\model\User();
		$adminModel = new \app\admin\model\Admin();

        $perUserIds = $userModel->getUserByPer('bi', 'product', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];
        $between_time = $whereData['between_time'];
        $where = [];
		//时间段
		$where['contract.create_time'] = ['between', $between_time];
		$where['contract.owner_user_id'] = ['in', $userIds];

		$join = [
			['__CRM_CONTRACT__ contract', 'contract.contract_id = a.contract_id', 'LEFT'],
			['__ADMIN_USER__ user', 'user.id = contract.owner_user_id', 'LEFT'],
			['__CRM_PRODUCT__ product', 'product.product_id = a.product_id', 'LEFT'],
			['__CRM_PRODUCT_CATEGORY__ product_category', 'product_category.category_id = product.category_id', 'LEFT'],
		];

		$sql = db('crm_contract_product')
					 ->alias('a')
					 ->where($where)
				     ->join($join)
				     ->field('a.*,product.name as product_name,contract.customer_id,contract.owner_user_id,contract.name as contract_name,contract.num as contract_num,product_category.name as category_id_info,user.realname,product_category.category_id')
					 ->order('category_id,product_name')
					 ->fetchSql()
					 ->select();
		$list = queryCache($sql);
		foreach ($list as $k=>$v) {
			$customer_info = db('crm_customer')->field('customer_id,name')->where('customer_id = '.$v['customer_id'])->field('customer_id,name')->find(); //客户
			$list[$k]['customer_id_info'] = $customer_info ? : array();
			//合同
			$contract_info  = [];
			$contract_info['contract_id'] = $v['contract_id'];
			$contract_info['name'] = $v['contract_name'];
			$list[$k]['contract_id_info'] = $contract_info ? : array();
			//产品
			$product_info = [];
			$product_info['name'] = $v['product_name'];
			$product_info['product_id'] = $v['product_id'];
			$list[$k]['product_id_info'] = $product_info ? : array();
			//负责人
			$owner_user_id_info = [];
			$owner_user_id_info['realname'] = $v['realname'];
			$list[$k]['owner_user_id_info'] = $owner_user_id_info;
			$list[$k]['owner_user_name'] = $owner_user_id_info['realname'];
		}
        return $list;
    }

	/**
     * [根据产品类别ID，查询父级ID]
     * @author Michael_xu
     * @param
     * @return
     */
	public function getPidStr($category_id, $idArr, $first = '')
	{
		if ($first == 1) $idArr = [];
		$idArr[] = $category_id;
		$pid = db('crm_product_category')->where(['category_id' => $category_id])->value('pid');
		if ($pid) {
			$idArr[] = $pid;
			$this->getPidStr($pid, $idArr);
		}
		$arr = array_reverse($idArr);
		$resStr = ','.implode(',',$arr).',';
		return $resStr;
	}
	
	/**
	 * 删除当前的记录
	 *
	 * @overwrite   重写 traits\model\SoftDelete\delete
	 * @param boolean $force	是否强制删除
     * @return integer
	 * @author Ymob
	 * @datetime 2019-10-24 15:02:22
	 */
    public function delete($force = false)
    {
        if (false === $this->trigger('before_delete', $this)) {
            return false;
        }

        $name = $this->getDeleteTimeField();
        if ($name && !$force) {
            // 软删除
			$this->data[$name] = $this->autoWriteTimestamp($name);
			$this->data['delete_user_id'] = UserModel::userInfo('id');
            $result            = $this->isUpdate()->save();
        } else {
            // 强制删除当前模型数据
            $result = $this->getQuery()->where($this->getWhere())->delete();
        }

        // 关联删除
        if (!empty($this->relationWrite)) {
            foreach ($this->relationWrite as $key => $name) {
                $name   = is_numeric($key) ? $name : $key;
                $result = $this->getRelation($name);
                if ($result instanceof Model) {
                    $result->delete();
                } elseif ($result instanceof Collection || is_array($result)) {
                    foreach ($result as $model) {
                        $model->delete();
                    }
                }
            }
        }

        $this->trigger('after_delete', $this);

        // 清空原始数据
        $this->origin = [];

        return $result;
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
        # 产品
        $product = Db::name('crm_product')->field(['create_user_id', 'create_time', 'update_time'])->where('product_id', $id)->find();
        # 创建人
        $realname = Db::name('admin_user')->where('id', $product['create_user_id'])->value('realname');

        return [
            'create_user_name' => $realname,
            'create_time'      => date('Y-m-d H:i:s', $product['create_time']),
            'update_time'      => date('Y-m-d H:i:s', $product['update_time'])
        ];
    }

    /**
     * 转移
     *
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function transfer($param)
    {
        return Db::name('crm_product')->whereIn('product_id', $param['product_id'])->update(['owner_user_id' => $param['owner_user_id']]);
    }
}
