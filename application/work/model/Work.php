<?php
// +----------------------------------------------------------------------
// | Description: 项目管理
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use app\admin\model\Common;

class Work extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
	protected $name = 'work';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;
	protected $insert = ['status' => 1];

    /**
     * 列表
     *
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function getDataList()
	{
		$list = $this->where(['status' => 1])->field('work_id,name,status,create_time')->select();
		return $list ;	
	}

    /**
     * 创建项目
     *
     * @param array $param
     * @return array|bool|float|int|mixed|object|\stdClass|null
     * @throws \think\exception\PDOException
     */
	public function createData($param)
	{
		$this->startTrans();

		try {
		    # 设置项目参数
            if (!empty($param['is_open']) && $param['is_open'] == 1) {
                $ownerUserId = Db::name('admin_user')->where('status', 1)->column('id');
            } else {
                $ownerUserId = $param['owner_user_id'] ? : [];
            }
			$param['owner_user_id'] = $ownerUserId ? arrayToString($ownerUserId) : '';
			$param['status']        = 1;
			# 创建项目
			$this->data($param)->allowField(true)->save();

			# 设置项目下的任务参数
			$workId       = $this->work_id;
			$createTime   = time();
			$createUserId = $param['create_user_id'];
			$taskData = [
			    ['name' => '要做', 'create_time' => $createTime, 'create_user_id' => $createUserId, 'status' => 1, 'work_id' => $workId, 'order_id' => 1],
                ['name' => '在做', 'create_time' => $createTime, 'create_user_id' => $createUserId, 'status' => 1, 'work_id' => $workId, 'order_id' => 2],
                ['name' => '待定', 'create_time' => $createTime, 'create_user_id' => $createUserId, 'status' => 1, 'work_id' => $workId, 'order_id' => 3]
            ];
			# 创建项目下的任务
			Db::name('work_task_class')->insertAll($taskData);

			# 设置项目下的相关成员参数
			$ownerData['work_id']        = $workId;
			$ownerData['create_user_id'] = $createUserId;
			$ownerData['owner_user_id']  = $ownerUserId;
            # 创建项目下的相关成员
			$this->addOwner($ownerData);

            # 提交事务
			$this->commit();

			return $workId;
		} catch(\Exception $e) {
		    # 回滚事务
			$this->rollback();
			# 错误原因
			$this->error = '添加失败';

			return false;
		}
	}

    /**
     * 编辑项目
     *
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
	public function updateDataById($param)
	{
	    $param['update_time'] = time();
	    $userId = $param['user_id'];
	    unset($param['user_id']);
		$map['work_id'] = $param['work_id'];
		$workInfo = $this->where(['work_id' => $param['work_id']])->find();
        # 公有改私有
		if (isset($param['is_open']) && empty($param['is_open']) && $workInfo['is_open'] == 1) {
			$ownerData = [];
			$ownerData['work_id'] = $param['work_id'];
			$ownerData['create_user_id'] = $workInfo['create_user_id'];
			$ownerData['owner_user_id'] = !empty($param['owner_user_id']) ? stringToArray($param['owner_user_id']) : stringToArray($workInfo['create_user_id']);
			$ownerData['is_open'] = $param['is_open'];
			$ownerData['group_id'] = 0;
			$this->addOwner($ownerData);	
		}
        # 私有改公有
		if (isset($param['is_open']) && !empty($param['is_open']) && $workInfo['is_open'] == 0) {
            $ownerUserId = Db::name('admin_user')->where('status', 1)->column('id');
            $ownerData['work_id']        = $param['work_id'];
            $ownerData['create_user_id'] = $workInfo['create_user_id'];
            $ownerData['owner_user_id']  = $ownerUserId;
            $ownerData['is_open']        = $param['is_open'];
            $ownerData['group_id']       = $param['group_id'];
            $this->addOwner($ownerData);
        }
		$resUpdata = $this->where($map)->update($param);
		if ($resUpdata) {
			$logmodel = model('WorkLog');
			$datalog['type'] = 2; //重命名项目
			$datalog['name'] = $param['name']; //项目名
			$datalog['create_user_id'] = $userId;
			$datalog['work_id'] = $param['work_id'];
			$datalog['user_id'] = $userId;
			$ret = $logmodel->workLogAdd($datalog);
			return true;
		} else {
			$this->error = '重命名失败';
			return false;
		}
	}

	/**
     * 删除项目
     * @author yykun
     * @param
     * @return
     */
	public function delWorkById($param)
	{
		$map['work_id'] = $param['work_id'];
		Db::name('Task')->where($map)->delete();
		Db::name('WorkTaskClass')->where($map)->delete();
		$flag = $this->where($map)->delete();
		if ($flag) {
			$logmodel = new \app\work\model\WorkLog();
			$datalog['status'] = 4; //删除项目
			$datalog['create_user_id'] = $param['create_user_id']; 
			$datalog['work_id'] = $param['work_id'];
			$datalog['content'] = '删除了项目';
			$ret = $logmodel->workLogAdd($datalog); 
			return true;
		} else {
			$this->error = '数据不存在或已被删除';
			return false;
		}
	}

	/**
     * 归档项目
     * @author yykun
     * @param
     * @return
     */	
	public function archiveData($param)
	{
		$map['work_id'] = $param['work_id'];
		$flag = $this->where($map)->setField('status',0);
		$this->where($map)->setField('archive_time',time());
		if ($flag) {
			$data = [];
			$data['status'] = 3;
			$data['archive_time'] = time();
			Db::name('task')->where($map)->update($data);
			return true;
		} else {
			$this->error = '归档失败';
			return false;
		}
	}

	/**
     * 归档项目列表
     * @author yykun
     * @param
     * @return
     */	
	public function archiveList($param)
	{
	    $page  = !empty($param['page'])  ? $param['page']  : 1;
	    $limit = !empty($param['limit']) ? $param['limit'] : 15;
	    unset($param['page']);
	    unset($param['limit']);
        //权限
        $map = $this->getWorkWhere($param);		
		$where['status'] = 0;
		$where['ishidden'] = 0;
		$count = $this->where($map)->where($where)->field('work_id,name,color,archive_time')->count();
		$list = $this->where($map)->where($where)->field('work_id,name,color,archive_time')
            ->limit(($page - 1) * $limit, $limit)->select();
		foreach ($list AS $key => $value) {
		    $list[$key]['archive_time'] = !empty($value['archive_time']) ? date('Y-m-d H:i:s', $value['archive_time']) : null;
        }
		return ['count' => $count, 'list' => $list];
	}

	/**
     * 归档恢复
     * @author yykun
     * @param
     * @return
     */	
	public function arRecover($work_id='')
	{
		if (!$work_id) {
			$this->error = '参数错误';
			return false;
		}
		$map['work_id'] =$work_id;
		$map['status'] = 0;
		$this->where($map)->setField('status',1);
		$map['status'] = 3;
		Db::name('Task')->where($map)->setField('status',1);
		return true;
	}

	/**
     * 退出项目
     * @author yykun
     * @param 项目id,会员ID
     * @return
     */
	public function leaveById($work_id,$user_id)
	{
		$workInfo = $this->where(['work_id' => $work_id])->find();
		if ($user_id == $workInfo['create_user_id']) {
			$this->error = '项目创建人不可以退出';
			return false;
		}
		//从项目成员中移除
		db('work_user')->where(['work_id' => $work_id,'user_id' => $user_id])->delete();
		$str = ','.$user_id.',';
		if (in_array($user_id,stringToArray($workInfo['owner_user_id']))) {
			$owner_user_id = str_replace($str,',',$workInfo['owner_user_id']);
			$this->where(['work_id' => $work_id])->update(['owner_user_id' => $owner_user_id]);
		}
		$list = Db::name('Task')->where(['work_id' => $work_id])->select();
		foreach ($list as $key => $value) {
			$data = [];
			if (in_array($user_id,stringToArray($value['owner_user_id']))) {
				$new_own_user_id = str_replace($str,',',$value['owner_user_id']);
				$data['owner_user_id'] = $new_own_user_id;
			}
			if ($value['main_user_id'] == $param['create_user_id']) {
				$data['main_user_id'] = '';
			}
			if ($data) Db::name('Task')->where(['task_id' => $value['task_id']])->update($data);
		}
		return true;
	}

    /**
     * 添加项目成员
     *
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
	public function addOwner($param)
	{
		$workInfo   = $this->get($param['work_id']);
		$oldOwner   = stringToArray($workInfo['owner_user_id']);
		$owner_user = $newOwner = $param['owner_user_id'];
		# 更新项目表中的项目成员
		$this->where(['work_id' => $param['work_id']])->update(['owner_user_id' => arrayToString($newOwner)]);
		# 差集，需删除的
		$del_user_ids   = array_diff($oldOwner, $newOwner);
		$create_user_id = !empty($param['create_user_id']) ? $param['create_user_id'] : '';
		unset($param['create_user_id']);
		$owner_user_arr = db('work_user')->where(['work_id' => $param['work_id']])->column('user_id');
		foreach ($owner_user as $k=>$v) {
			$data = [];
			if (in_array($v,$owner_user_arr)) continue;
			$data['work_id'] = $param['work_id'];
			$data['user_id'] = $v;
			$data['types'] = 0;
			if ($v == $create_user_id) {
				$data['types'] = 1;
				$group_id = 1;
			} else {
				# 默认角色
				$group_id = db('admin_group')->where(['pid' => 5, 'system' => 1])->order('id asc')->value('id');
			}
			$data['group_id'] = $group_id;
			$saveData[] = $data;
		}
		$res = true;
		if ($saveData && !db('work_user')->insertAll($saveData)) {
			$res = false;
		}
		if ($del_user_ids && !db('work_user')->where(['work_id' => $param['work_id'],'user_id' => ['in',$del_user_ids]])->delete()) {
			$res = false;
		}
		return $res;
	}

	/**
     * 删除项目成员
     * @author yykun
     * @param
     * @return
     */	
	public function delOwner($param)
	{
		$work_id = $param['work_id'];
		$workUserInfo = db('work_user')->where(['work_id' => $work_id,'user_id' => $param['owner_user_id']])->find();
		if (!$workUserInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		if ($workUserInfo['types'] == 1) {
			$this->error = '项目负责人不能删除';
			return false;			
		}
		$res = db('work_user')->where(['id' => $workUserInfo['id']])->delete();

		$owner_user_id[] = $param['owner_user_id'];
		$workInfo = $this->get($param['work_id']);
		$oldOwner = stringToArray($workInfo['owner_user_id']);
		$newOwner = array_diff($oldOwner,$owner_user_id);
		$resUpdate = $this->where(['work_id' => $work_id])->update(['owner_user_id' => arrayToString($newOwner)]);
		if (!$res || !$resUpdate){
			$this->error = '删除失败，请重试！';
			return false;
		}
		return true;
	}
	
	/**
     * 项目成员列表
     * @author yykun
     * @param
     * @return
     */	
	public function ownerList($param)
	{	
		if ($param['work_id']) {
			$workInfo = $this->get($param['work_id']);
			if ($workInfo['is_open'] == 1) {
				//公开项目
				$list = db('admin_user')->where(['status' => 1])->field('username,realname,thumb_img,id')->select();
			} else {
				// $exp = new \think\db\Expression('field(types,1,0,2)');
				$list = db('work_user')
					->alias('work')
					->join('__ADMIN_USER__ user', 'user.id = work.user_id', 'LEFT')
					->where(['work.work_id' => $param['work_id']])
					->field('work.*,user.username,user.realname,user.thumb_img')
					->order('work.types desc,user.id asc')
					->select();
			}			
		} else {
			$list = db('admin_user')->where(['status' => 1])->field('username,realname,thumb_img,id')->select();
		}
		if ($list) {
			foreach ($list as $k=>$v) {
				$list[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
				$list[$k]['id'] = $v['user_id'] ? : $v['id'];
			}			
		}
		return $list ? : [];
	}	

	/**
     * 项目权限判断(成员)
     * @author yykun
     * @param
     * @return
     */	 
	public function checkWork($work_id, $user_id)
	{
		$info = $this->get($work_id);
		if (!$info) {
			$this->error = '该项目不存在或已删除';
			return false;
		}
		//私有项目（成员可见）
		$map = function($query) use ($user_id){
                    $query->whereOr(function ($query) use ($user_id) {
                            $query->where(['is_open' => 0,'owner_user_id' => array('like','%,'.$user_id.',%')]);
                        })
                        ->whereOr(function ($query) use ($user_id) {
                            $query->where(['is_open' => 1]);
                        })
                        ->whereOr(function ($query) use ($user_id) {
                            $query->where(['create_user_id' => $user_id]);
                        });
                };
		$resData = db('work')->where(['work_id' => $work_id])->where($map)->find();
		$userMap = function($query) {
	                    $query->where(['types' => 1])
        				->whereOr(['group_id' => 1]);
	                };
		$adminUser = db('work_user')->where(['work_id' => $work_id])->where($userMap)->column('user_id');
		$adminTypes = adminGroupTypes($user_id);
		if (!$resData && !in_array(1,$adminTypes) && !in_array(7,$adminTypes) && !in_array($user_id,$adminUser)) {
			$this->error = '没有权限';
			return false;
		}
		return true;
	}

    /**
     * 判断项目操作权限
     *
     * @param $m
     * @param $c
     * @param $a
     * @param $work_id
     * @param $user_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function isCheck($m, $c, $a, $work_id, $user_id)
	{
		if (!$work_id) return false;
        $adminTypes = adminGroupTypes($user_id);
        $workInfo = Db::name('Work')->where(['work_id' => $work_id])->find();
		if (in_array(1,$adminTypes) || in_array(7,$adminTypes)) {
            return true;
        }
        //创建人有管理权限
		if ($workInfo['create_user_id'] == $user_id) {
    		return true;
    	}             
        if (empty($workInfo['is_open'])) {
        	//私有项目
        	$groupInfo = db('work_user')->where(['work_id' => $work_id,'user_id' => $user_id])->find();
			if ($groupInfo['types'] == 1 && $groupInfo['group_id'] == 1) {
	            return true;
	        }       
			$checkParam = [];
	        $checkParam['user_id'] = $user_id;
	        $checkParam['group_id'] = $groupInfo['group_id'];
	        if (checkWorkPerByAction($m, $c, $a, $checkParam)) {
	        	return true;
	        }	         	
        } else {
        	if ($m == 'work' && $c == 'work' && $a == 'update') {
	        	return false;        		
        	}
        	return true;
        }
        return false;
	}	

	/**
     * 获取项目权限范围
     * @author Michael_xu
     * @return
     */	
    public function getWorkWhere($param)
    {
    	$user_id = $param['user_id'];
    	$adminTypes = adminGroupTypes($user_id);
    	$map = [];
		if (!in_array(1,$adminTypes) && !in_array(7,$adminTypes)) {
	        $map = function($query) use ($user_id){
	            $query->whereOr(function ($query) use ($user_id) {
	                $query->where(['is_open' => 0,'owner_user_id' => array('like','%,'.$user_id.',%')]);
	            })
	            ->whereOr(function ($query) use ($user_id) {
	                $query->where(['is_open' => 1]);
	            })
	            ->whereOr(function ($query) use ($user_id) {
	                $query->where(['create_user_id' => $user_id]);
	            });
	        };
	    }
	    return $map ? : [];    	
    }

	/**
     * 获取项目下权限信息
     * @author Michael_xu
     * @return
     */    
    public function authList($param)
    {
    	$user_id = $param['user_id'];
    	$work_id = $param['work_id'];
        $ruleMap = [];
        $adminTypes = adminGroupTypes($user_id);
        $ruleMap['types'] = ['eq',3];
        $ruleMap['status'] = 1;
        if (!in_array(1,$adminTypes) && !in_array(7,$adminTypes)) {
        	$workInfo = $this->get($work_id);
        	if ($workInfo['is_open'] == 1) {
        		//公开项目
        		if ($workInfo['create_user_id'] !== $user_id) {
        			$ruleMap['name'] = ['not in',['update']];
        		}
        	} else {
        		//私有项目
        		$groupInfo = db('work_user')->where(['work_id' => $work_id,'user_id' => $user_id])->find();
        		if ($groupInfo['types'] !== 1 || $groupInfo['group_id'] !== 1) {
        			$rule_ids = db('admin_group')->where(['id' => $groupInfo['group_id']])->value('rules');
        			$ruleIds = stringToArray($rule_ids);
        			if ($ruleIds) {
						$ruleMap['id'] = array('in', $ruleIds);
        			} else {
        				$ruleMap['id'] = ['eq',3];
        			}
        		}
        	}    
        }
        $newRuleIds = [];
        // 重新设置ruleIds，除去部分已删除或禁用的权限。
        $rules = Db::name('admin_rule')->where($ruleMap)->order('types asc')->select();
        foreach ($rules as $k => $v) {
            $newRuleIds[] = $v['id'];
            $rules[$k]['name'] = strtolower($v['name']);
        }
        $tree = new \com\Tree();
        $rulesList = $tree->list_to_tree($rules, 'id', 'pid', 'child', 0, true, array('pid'));
        //权限数组
        $authList = rulesListToArray($rulesList, $newRuleIds);
        return $authList ? : [];  	
    }

	/**
     * 任务列表统计
     * @author yykun
     * @return
     */    
    public function classList($work_id)
    {
		$classList = Db::name('WorkTaskClass')->where(['status' => 1,'work_id' => $work_id])->order('order_id asc')->select();
        $taskarray = array();
        foreach ($classList as $k => $v) {
            $task_list = [];
            $task_list = db('task')->where(['work_id' => $work_id,'class_id' => $v['class_id'],'is_archive' => 0,'ishidden' => 0])->field('is_archive,status')->select();
            $allTask = 0;
            $undoneTask = 0;
            $doneTask = 0;
            foreach ($task_list as $kk => $vv) {
                $allTask += 1;
                if ($vv['status'] == 1) {
                    $undoneTask += 1; 
                    continue;
                }
                if ($vv['status'] == 5) {
                    $doneTask += 1; 
                    continue;
                }
            }
            $classList[$k]['allTask'] = $allTask ? : 0;
            $classList[$k]['undoneTask'] = $undoneTask ? : 0;
            $classList[$k]['doneTask'] = $doneTask ? : 0;
        }
        return $classList ? : [];	
    } 

	/**
     * 任务标签统计
     * @author yykun
     * @return
     */    
    public function labelList($work_id,$labelIds = array())
    {
    	if ($labelIds) {
    		$labelList = [];
    		$i = 0;
			foreach ($labelIds as $k => $v) {
	            $labledet = [];
	            $task_list = [];
	            $labledet = Db::name('WorkTaskLable')->where(['lable_id' => $v])->find();
	            $task_list = Db::name('Task')->where(['lable_id' => ['like','%,'.$v.',%'],'work_id' => $work_id,'is_archive' => 0,'ishidden' => 0])->field('status,task_id')->select();
	            $allTask = 0;
	            $undoneTask = 0;
	            $doneTask = 0;
	            foreach ($task_list as $kk => $vv) {
	                if ($vv['status'] !== 3) $allTask += 1;
	                if ($vv['status'] == 1) {
	                    $undoneTask += 1;
	                    continue;
	                }
	                if ($vv['status'] == 5) {
	                    $doneTask += 1; 
	                    continue;
	                }
	            }
	            $labelList[$i]['allTask'] = $allTask ? : 0;
	            $labelList[$i]['undoneTask'] = $undoneTask ? : 0;
	            $labelList[$i]['doneTask'] = $doneTask ? : 0;                
	            $labelList[$i]['lablename'] = $labledet['name'];
	            $i++;
	        }
    	}
        return $labelList ? : [];	
    }          
}