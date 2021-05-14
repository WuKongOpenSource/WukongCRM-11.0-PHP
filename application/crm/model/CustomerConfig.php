<?php
// +----------------------------------------------------------------------
// | Description: 客户扩展设置
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use app\admin\controller\ApiCommon;
use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class CustomerConfig extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'crm_customer_config';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

    /**
     * 列表数据
     *
     * @param $request
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDataList($request)
    {     
		$userModel = new \app\admin\model\User();
    	$structureModel = new \app\admin\model\Structure();    	
		$request = $this->fmtRequest( $request );
        $map = $request['map'] ? : [];
        $order = 'update_time desc'; //排序

        $list = $this
                ->where($map)
                ->page($request['page'], $request['limit'])
                ->order($order)
                ->select(); 
        foreach ($list as $k=>$v) {
            $list[$k]['user_ids_info'] = $userModel->getListByStr($v['user_ids']);
            $list[$k]['structure_ids_info'] = $structureModel->getListByStr($v['structure_ids']);	
        }
        $dataCount = $this->where($map)->count('id');
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;
        return $data;
    }

    /**
     * 保存/编辑相关信息 todo 创建和编辑走一个接口，前端非要这么搞
     *
     * @param array $param
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function createData($param)
	{
	    $id = !empty($param['id']) ? $param['id'] : 0;

		if ($param['value'] <= 0) {
			$this->error = '数量上限必须大于0';
			return false;
		}

        # 验证重复
        if ($this->checkRepeat($param['types'], $param['user_ids'], $param['structure_ids'], $id)) {
            $this->error = '有员工或部门包含在其他的规则里！';
            return false;
        }
		$param['types']         = !empty($param['types'])         ? $param['types']                        : 1;  # 1拥有客户上限2锁定客户上限
		$param['user_ids']      = !empty($param['user_ids'])      ? arrayToString($param['user_ids'])      : ''; # 处理user_id
		$param['structure_ids'] = !empty($param['structure_ids']) ? arrayToString($param['structure_ids']) : ''; # 处理structure_id
        if ($this->allowField(true)->isUpdate(empty($id) ? false : true)->save($param, !empty($id) ? ['id' => $id] : [])) {
			$data['id'] = $this->id;
            # 系统操作日志
            if(!empty($param['id'])){
                $content='编辑员工拥有、锁定客户限制';
                $action='update';
            }else{
                $content='添加员工拥有、锁定客户限制';
                $action='update';
            }
            $user=new ApiCommon();
            $userInfo=$user->userInfo;
            SystemActionLog($userInfo['id'], 'crm_customer','customer', $this->id,  $action,$content , '', '',$content);
            return $data;
		} else {
			$this->error = '创建失败';
			return false;
		}	
	}

    /**
     * 编辑相关信息
     *
     * @param $param
     * @param string $id
     * @return array|bool
     */
	public function updateDataById($param, $id = '')
	{
		if (!$id) {
			$this->error = '参数错误';
			return false;
		}
		if ($param['value'] <= 0) {
			$this->error = '数量上限必须大于0';
			return false;
		}		
		unset($param['id']);
		$param['user_ids'] = is_array($param['user_ids']) ? arrayToString($param['user_ids']) : $param['user_ids']; //处理user_id
		$param['structure_ids'] = is_array($param['structure_ids']) ? arrayToString($param['structure_ids']) : ''; //处理structure_id	

		if ($this->allowField(true)->isUpdate(true)->save($param, ['id' => $id])) {
			$data['id'] = $id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

    /**
     * 相关信息数据
     *
     * @param string $id
     * @return Common|array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
   	public function getDataById($id = '')
   	{   		
   		$map['id'] = $id;
		$dataInfo = $this->where($map)->find();
		return $dataInfo ? : [];
   	}

    /**
     * 验证是否可以持有或锁定客户
     *
     * @param int $userId 用户id
     * @param int $types 类型：类型：1 拥有客户数，2 锁定客户数
     * @param int $isUpdate 是否是更新
     * @param int $addCount 多公海分配或领取使用的参数，代表要领取或分配的客户数量
     * @return bool|int|mixed
     */
    public function checkData($userId, $types, $isUpdate = 0, $addCount = 0)
    {
        # 用户信息
        $userinfo = db('admin_user')->field(['realname', 'structure_id'])->where('id', $userId)->find();
        $username = $userinfo['realname'];
        $structureId = $userinfo['structure_id'];

        # 查询客户配置（拥有，锁定），以用户配置优先
        $customerConfig = db('crm_customer_config')->field(['value', 'is_deal'])->where(['types' => $types, 'user_ids' => ['like', '%,' . $userId . ',%']])->find();
        if (!$customerConfig) $customerConfig = db('crm_customer_config')->field(['value', 'is_deal'])->where(['types' => $types, 'structure_ids' => ['like', '%,' . $structureId . ',%']])->find();

        # 提示标题
        $title = '';
        if ($types == 1) $title = '拥有的客户数量';
        if ($types == 2) $title = '锁定的客户数量';

        if ($customerConfig) {
            if (empty($customerConfig['value'])) {
                $this->error = $title . '超出限制，最大：' . $customerConfig['value'] . ' 个';

                return false;
            }

            # 成交用户是否暂用数量
            $isDeal = !empty($customerConfig['is_deal']) ? 1 : 0;

            # 获取目前拥有或锁定的客户数量
            $customerModel = new Customer();
            $count = $customerModel->getCountByHave($userId, $isDeal, $types);

            $error = false;
            if (empty($addCount)) {
                # 多公海以外的地方调用
                if ($count >= $customerConfig['value']) {
                    $error = true;
                }
                if ($isUpdate == 1 && $types == 1 && $customerConfig['is_deal'] == 1) {
                    //更改成交状态
                    if ($count = $customerConfig['value']) {
                        $error = false;
                    }
                }
                if ($error == true) {
                    $this->error = $username.','.$title.'超出限制：'.$customerConfig['value'].'个';
                    return false;
                }
            } else {
                # 多公海中的领取、分配调用，返回超出的个数
                if ($count + $addCount > $customerConfig['value']) {
                    return ($count + $addCount) - $customerConfig['value'];
                }
            }
        }

        return true;
    }
//   	public function checkData($user_id, $types, $is_update = '')
//   	{
//   		$userModel = new \app\admin\model\User();
//   		$customerModel = new \app\crm\model\Customer();
//   		$userInfo = $userModel->getUserById($user_id);
//   		$dataInfo = $this->where(['types' => $types,'user_ids' => ['like','%,'.$user_id.',%']])->order('update_time desc')->find();
//		if (!$dataInfo) {
//			$dataInfo = $this->where(['types' => $types,'structure_ids' => ['like','%,'.$userInfo['structure_id'].',%']])->find();
//		}
//		switch ($types) {
//			case '1' : $types_title = '拥有的客户数量'; break;
//			case '2' : $types_title = '锁定的客户数量'; break;
//		}
//		if ($dataInfo) {
//			$is_deal = $dataInfo['is_deal'] ? : 0;
//			if (!$dataInfo['value']) {
//				$this->error = $types_title.'超出限制：'.$dataInfo['value'].'个';
//				return false;
//			}
//			//拥有数、锁定数
//			$count = $customerModel->getCountByHave($user_id,$is_deal,$types);
//			$error = false;
//			if ($count >= $dataInfo['value']) {
//				$error = true;
//			}
//			if ($is_update == 1 && $types == 1 && $dataInfo['is_deal'] == 1) {
//				//更改成交状态
//				if ($count = $dataInfo['value']) {
//					$error = false;
//				}
//			}
//			if ($error == true) {
//				$this->error = $userInfo['realname'].','.$types_title.'超出限制：'.$dataInfo['value'].'个';
//				return false;
//			}
//		}
//		return true;
//   	}

    /**
     * 验证拥有/锁定客户数中的员工或部门是否重复添加
     *
     * @param $types
     * @param $users
     * @param $structures
     * @param $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function checkRepeat($types, $users, $structures, $id)
    {
        $userArray      = [];
        $structureArray = [];

        $where['types'] = $types;
        if (!empty($id)) $where['id'] = ['neq', $id];

        $data = db('crm_customer_config')->field(['user_ids', 'structure_ids'])->where($where)->select();

        foreach ($data AS $key => $value) {
            if (!empty($value['user_ids'])) {
                $userArray = array_merge($userArray, explode(',', trim($value['user_ids'], ',')));
            }
            if (!empty($value['structure_ids'])) {
                $structureArray = array_merge($structureArray, explode(',', trim($value['structure_ids'], ',')));
            }
        }

        if (array_intersect($users, $userArray) || array_intersect($structures, $structureArray)) {
            return true;
        }

        return false;
    }
} 		