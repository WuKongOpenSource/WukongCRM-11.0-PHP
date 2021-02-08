<?php
/**
 * 日志规则逻辑类
 *
 * @author qifan
 * @date 2020-12-03
 */

namespace app\admin\logic;

use think\Db;

class DailyRuleLogic
{
    /**
     * 获取日志欢迎语
     *
     * @return array|mixed
     */
    public function welcome()
    {
        $mark = Db::name('admin_oalog_rule')->where('type', 4)->value('mark');

        return !empty($mark) ? unserialize($mark) : [];
    }

    /**
     * 保存欢迎语
     *
     * @param $data
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setWelcome($data)
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (!empty($value)) $result[] = $value;
        }

        $result = serialize($result);

        if (Db::name('admin_oalog_rule')->where('type', 4)->value('id')) {
            return Db::name('admin_oalog_rule')->where('type', 4)->update(['mark' => $result]);
        }

        return Db::name('admin_oalog_rule')->insert([
            'type' => 4,
            'status' => 1,
            'mark' => $result
        ]);
    }

    /**
     * 获取日志规则
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function workLogRule()
    {
        $result[] = $this->getDayLogRule();
        $result[] = $this->getWeekLogRule();
        $result[] = $this->getMonthLogRule();

        return $result;
    }

    /**
     * 设置日志规则
     *
     * @param $param
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setWorkLogRule($param)
    {
        if (!empty($param[0])) $this->setDayLogRule($param[0]);
        if (!empty($param[1])) $this->setWeekLogRule($param[1]);
        if (!empty($param[2])) $this->setMonthLogRule($param[2]);
    }

    /**
     * 设置日规则
     *
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function setDayLogRule($param)
    {
        $data = [
            'type' => 1,
            'userIds' => $param['userIds'],
            'effective_day' => $param['effective_day'],
            'start_time' => $param['start_time'],
            'end_time' => $param['end_time'],
            'status' => $param['status']
        ];

        if (Db::name('admin_oalog_rule')->where('type', 1)->value('id')) {
            return Db::name('admin_oalog_rule')->where('type', 1)->update($data);
        }

        return Db::name('admin_oalog_rule')->insert($data);
    }

    /**
     * 设置周规则
     *
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function setWeekLogRule($param)
    {
        $data = [
            'type' => 2,
            'userIds' => $param['userIds'],
            'start_time' => $param['start_time'],
            'end_time' => $param['end_time'],
            'status' => $param['status']
        ];

        if (Db::name('admin_oalog_rule')->where('type', 2)->value('id')) {
            return Db::name('admin_oalog_rule')->where('type', 2)->update($data);
        }

        return Db::name('admin_oalog_rule')->insert($data);
    }

    /**
     * 设置月规则
     *
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function setMonthLogRule($param)
    {
        $data = [
            'type' => 3,
            'userIds' => $param['userIds'],
            'start_time' => $param['start_time'],
            'end_time' => $param['end_time'],
            'status' => $param['status']
        ];

        if (Db::name('admin_oalog_rule')->where('type', 3)->value('id')) {
            return Db::name('admin_oalog_rule')->where('type', 3)->update($data);
        }

        return Db::name('admin_oalog_rule')->insert($data);
    }

    /**
     * 获取日规则
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getDayLogRule()
    {
        $day = Db::name('admin_oalog_rule')->where('type', 1)->find();

        return [
            'type' => $day['type'],
            'status' => $day['status'],
            'userIds' => $day['userIds'],
            'user' => $this->getUsers($day['userIds']),
            'effective_day' => $day['effective_day'],
            'start_time' => $day['start_time'],
            'end_time' => $day['end_time']
        ];
    }

    /**
     * 获取周规则
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getWeekLogRule()
    {
        $week = Db::name('admin_oalog_rule')->where('type', 2)->find();

        return [
            'type' => $week['type'],
            'status' => $week['status'],
            'userIds' => $week['userIds'],
            'user' => $this->getUsers($week['userIds']),
            'effective_day' => $week['effective_day'],
            'start_time' => $week['start_time'],
            'end_time' => $week['end_time']
        ];
    }

    /**
     * 获取月规则
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getMonthLogRule()
    {
        $month = Db::name('admin_oalog_rule')->where('type', 3)->find();

        return [
            'type' => $month['type'],
            'status' => $month['status'],
            'userIds' => $month['userIds'],
            'user' => $this->getUsers($month['userIds']),
            'effective_day' => $month['effective_day'],
            'start_time' => $month['start_time'],
            'end_time' => $month['end_time']
        ];
    }

    /**
     * 获取用户列表
     *
     * @param $ids
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getUsers($ids)
    {
        return Db::name('admin_user')->field(['id', 'realname'])->whereIn('id', $ids)->select();
    }

    /**
     * 自定义日程类型列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function schedule()
    {
        $list = Db::name('admin_oa_schedule')->where('type', 2)->field(['name,color,schedule_id as id'])->select();
        $data = [];
        $data['list'] = $list;
        return $data;
    }

    /**
     * 设置日程自定义规则
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setSchedule($param)
    {
        if (Db::name('admin_oa_schedule')->where('schedule_id', $param['id'])->value('schedule_id')) {
            $data = [
                'name' => $param['name'],
                'update_time' => time(),
                'color' => $param['color'],
            ];
            return Db::name('admin_oa_schedule')->where( 'schedule_id', $param['id'])->update($data);
        }

    }

    /**
     * 添加自定义日程规则
     * @param $param
     * @return int|string
     */
    public function addSchedule($param)
    {
        $data = [
            'name' => $param['name'],
            'create_time' => time(),
            'color' => $param['color'],
        ];
        return Db::name('admin_oa_schedule')->insert($data);
    }

    /**
     * 删除日程自定义规则
     * @param $param
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delSchedule($param)
    {
        if (Db::name('admin_oa_schedule')->where('schedule_id', $param)->value('schedule_id')) {
            return Db::name('AdminOaSchedule')->where('schedule_id', $param)->delete();
        }
    }
}