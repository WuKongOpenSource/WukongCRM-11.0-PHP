<?php
/**
 * 发票开户行逻辑类
 *
 * @author qifan
 * @date 2020-12-19
 */

namespace app\crm\model;

use think\Db;

class InvoiceInfoLogic
{
    /**
     * 列表
     *
     * @param $param
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($param)
    {
        $where = [];

        if (!empty($param['customer_id'])) {
            $where['customer_id'] = $param['customer_id'];
        }
        $list = Db::name('crm_invoice_info')->alias('info')
                ->join('admin_user user', 'user.id=info.create_user_id')
                ->field('info.*,user.realname as create_user_name')
                ->where($where)->select();
        $count = Db::name('crm_invoice_info')->alias('info')
                ->join('admin_user user', 'user.id=info.create_user_id')
                ->where($where)->count();
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        return ['count' => $count, 'list' => $list];
    }

    /**
     * 详情
     *
     * @param $infoId
     * @return array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($infoId)
    {
        return Db::name('crm_invoice_info')->field(['customer_id', 'create_user_id', 'create_time', 'update_time'], true)
            ->where('info_id', $infoId)->find();
    }

    /**
     * 创建
     *
     * @param $param
     * @return int|string
     */
    public function save($param)
    {
        $param['create_user_id'] = $param['user_id'];
        $param['create_time'] = time();
        $param['update_time'] = $param['create_time'];
        unset($param['user_id']);

        return Db::name('crm_invoice_info')->insert($param);
    }

    /**
     * 编辑
     *
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update($param)
    {
        $param['update_time'] = time();

        return Db::name('crm_invoice_info')->strict(true)->update($param);
    }

    /**
     * 删除
     *
     * @param $infoId
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($infoId)
    {
        return Db::name('crm_invoice_info')->where('info_id', $infoId)->delete();
    }
}