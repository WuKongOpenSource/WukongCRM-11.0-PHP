<?php
/**
 * 我的关注公共助手
 *
 * @author qifan
 * @date 2020-12-05
 */
namespace app\crm\traits;

use think\Db;

trait StarTrait
{
    /**
     * 设置关注
     *
     * @param $type
     * @param $userId
     * @param $targetId
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStar($type, $userId, $targetId)
    {
        # 查询关注表里是否有数据
        $starId = Db::name('crm_star')->where(function ($query) use ($userId, $targetId, $type) {
            $query->where('user_id', $userId);
            $query->where('target_id', $targetId);
            $query->where('type', $type);
        })->value('star_id');

        # 有数据移出关注
        if ($starId) return $this->deleteStar($starId);

        # 没数据增加关注
        return $this->createStar($type, $userId, $targetId);
    }

    /**
     * 添加我的关注
     *
     * @param $type
     * @param $userId
     * @param $targetId
     * @return int|string
     */
    private function createStar($type, $userId, $targetId)
    {
        return Db::name('crm_star')->insert(['user_id' => $userId, 'target_id' => $targetId, 'type' => $type]);
    }

    /**
     * 删除我的关注
     *
     * @param $starId
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function deleteStar($starId)
    {
        return Db::name('crm_star')->where('star_id', $starId)->delete();
    }
}