<?php
/**
 * 自动编号（合同、回款、回访、发票）
 *
 * @author qifan
 * @date 2020-12-09
 */

namespace app\crm\traits;

use app\crm\model\NumberSequence;
use think\Db;

trait AutoNumberTrait
{
    private $stringToDate = ['yyyyMMdd' => 'Ymd', 'yyyy' => 'Y', 'yyyyMM' => 'Ym'];

    /**
     * 获取自动编号
     *
     * @param $type 1合同；2回款；3回访；4发票
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAutoNumbers($type)
    {
        $number = '';
        $data   = [];

        # 根据设置重置编号（不想改下面的代码，在这里在写一个，多公海版本出来后，用定时来做）
        $list = Db::name('crm_number_sequence')->where('number_type', $type)->where('status', 0)->select();
        foreach ($list AS $key => $value) {
            if ($value['type'] == 3 && $value['reset'] != 0) {
                # 1：每天；2：每月；3：每年；
                $currentDate = [
                    1 => date('Y-m-d'),
                    2 => date('Y-m'),
                    3 => date('Y')
                ];
                $lastDate = [
                    1 => date('Y-m-d', $value['last_date']),
                    2 => date('Y-m',   $value['last_date']),
                    3 => date('Y',     $value['last_date'])
                ];
                
                if ($currentDate[$value['reset']] != $lastDate[$value['reset']]) {
                    Db::name('crm_number_sequence')->where('number_sequence_id', $value['number_sequence_id'])->update([
                        'last_number' => 1
                    ]);
                }
            }
        }

        $info = Db::name('crm_number_sequence')->where('number_type', $type)->order('sort', 'asc')->where('status', 0)->select();

        foreach ($info AS $key => $value) {
            # 文本
            if ($value['type'] == 1) {
                $number .= $value['value'] . '-';
            }
            # 日期
            if ($value['type'] == 2) {
                $number .= date($this->stringToDate[$value['value']]) . '-';
            }
            # 数字
            if ($value['type'] == 3) {
                $number .= $value['last_number'] . '-';

                # 需要更新的数据
                $data[] = [
                    'number_sequence_id' => $value['number_sequence_id'],
                    'last_number'        => $value['last_number'] + $value['increase_number'],
                    'last_date'          => time()
                ];

            }
        }

        return ['number' => rtrim($number, '-'), 'data' => $data];
    }
}