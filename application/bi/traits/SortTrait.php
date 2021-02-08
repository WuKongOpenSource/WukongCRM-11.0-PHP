<?php
/**
 * 排序帮助类
 *
 * @author qifna
 * @date 2020-12-25
 */

namespace app\bi\traits;

trait SortTrait
{
    /**
     * 数组排序
     *
     * @param $data
     * @param $field
     * @param $sort
     * @return mixed
     */
    public function sortCommon($data, $field, $sort)
    {
        $sortField = array_column($data, $field);

        array_multisort($sortField,$sort == 'desc' ? SORT_DESC : SORT_ASC, $data);

        return $data;
    }
}