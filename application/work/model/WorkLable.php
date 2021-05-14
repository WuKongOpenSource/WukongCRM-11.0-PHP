<?php
// +----------------------------------------------------------------------
// | Description: 任务标签
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use app\admin\model\Common;
use com\verify\HonrayVerify;
use think\Cache;

class WorkLable extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
	protected $name = 'work_task_lable';
    protected $createTime = 'create_time';
    protected $updateTime = false;
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];

	/**
     * [getDataList 标签列表]
     * @AuthorHTL
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */	    
	public function getDataList($userId)
	{
		$map['l.status'] = 1;
		$count = db('work_task_lable')->alias('l')->where($map)->count();

		if (db('work_lable_order')->where('user_id', $userId)->count() > 0) {
            $map['o.user_id'] = $userId;
            $list = db('work_task_lable')->alias('l')
                ->join('__WORK_LABLE_ORDER__ o', 'o.lable_id = l.lable_id', 'LEFT')
                ->where($map)->order('o.order', 'asc')->select();
        } else {
            $list = db('work_task_lable')->alias('l')->where($map)->select();
        }

		$data['list'] = !empty($list) ? $list : [];
		$data['dataCount'] = $count;

		return $data;
	}
	
	/**
     * 创建标签
     * @author yykun
     * @param
     * @return
     */	
	public function createData($param)
	{
		$this->startTrans();
		try {
			$data['create_time'] = time();
			$data['create_user_id'] = $param['create_user_id'];
			$data['name'] = $param['name'];
			$data['color'] = $param['color'];
			$data['status'] = 1; 
			$this->insert($data);
			$lableId = $this->getLastInsID();
            # 更新排序
            $this->updateLableOrder($lableId, $param['create_user_id']);
			$this->commit();
			return true;
		} catch(\Exception $e) {
			$this->rollback();
			$this->error = '添加失败';
			return false;
		}
	}

    /**
     * 更新标签排序
     *
     * @param int $lableId 标签ID
     * @param int $userId  用户ID
     * @author fanqi
     * @since 2021-03-27
     */
	private function updateLableOrder($lableId, $userId)
    {
        $order = 0;

        $orderList = db('work_lable_order')->where('user_id', $userId)->select();

        foreach ($orderList AS $key => $value) {
            if (!empty($value['order']) && $value['order'] > $order) $order = $value['order'];
        }

        if (!empty($order)) db('work_lable_order')->insert([
            'lable_id' => $lableId,
            'user_id'  => $userId,
            'order'    => $order + 1
        ]);
    }

	/**
     * 编辑标签
     * @author yykun
     * @param
     * @return
     */
	public function updateDataById($param)
	{
		$map['lable_id'] = $param['lable_id'];
		unset($param['lable_id']);
		$flag = $this->where($map)->update($param);
		if ($flag) {
			return true;
		} else {
			$this->error = '操作失败';
			return false;
		}
	}

	/**
     * 删除标签
     * @author yykun
     * @param
     * @return
     */
	public function delDataById($param)
	{
		$map['lable_id'] = $param['lable_id'];
		if (db('task')->where(['lable_id' => ['like','%,'.$param['lable_id'].',%']])->find()) {
			$this->error = '标签已被使用，无法删除！';
			return false;			
		}	
		$this->startTrans();
		try {
			$ret = $this->where($map)->setField('status',0);
			if ($ret) {
				$this->commit();
				return true;
			} else {
				$this->rollback();
				$this->error = '删除失败';
				return false;
			}
		} catch (\Exception $e){
			$this->rollback();
			$this->error = '删除失败';
			return false;
		}		
	}

    /**
     * 任务标签
     *
     * @param $idstr
     * @return array|bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function getDataByStr($idstr)
	{
		$idstr = stringToArray($idstr);
		$list = Db::name('WorkTaskLable')->field('lable_id,name,color')->where(['lable_id' => ['in',$idstr],'status'=>1])->select();
		return $list ? : [];
	}

	/**
     * 任务标签名称
     * @author yykun
     * @param
     * @return
     */
	public function getNameByIds($ids)
	{
		$list = Db::name('WorkTaskLable')->where(['lable_id' => ['in',$ids]])->column('name');
		return $list ? : [];
	}

    /**
     * 标签排序
     *
     * @param array $param user_id 用户ID； labelIds 标签ID
     * @author fanqi
     * @since 2021-03-27
     */
	public function updateOrder($param)
    {
        if (!empty($param['labelIds'])) {
            $data = [];

            foreach ($param['labelIds'] AS $key => $value) {
                $data[] = [
                    'lable_id' => $value,
                    'user_id'  => $param['user_id'],
                    'order'    => $key + 1
                ];
            }

            # 先删除在添加
            db('work_lable_order')->where('user_id', $param['user_id'])->delete();
            db('work_lable_order')->insertAll($data);
        }
    }
}
