<?php
/**
 * 使用定时器将符合条件的用户回收到公海池
 *
 * @author fanqi
 * @since 2021-03-31
 */

namespace app\common\command;

use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\response\Json;
use Workerman\Lib\Timer;
use Workerman\Worker;

class PoolCommand extends Command
{
    protected $timer;
    protected $interval = 10;

    protected function configure()
    {
        $this->setName('pool')
            ->addArgument('status', Argument::REQUIRED, 'start/stop/reload/status/connections')
            ->addOption('d', null, Option::VALUE_NONE, 'daemon（守护进程）方式启动')
            ->setDescription('公海回收定时器');

        // 读取数据库配置文件
        $filename = ROOT_PATH . 'config'.DS.'database.php';
        // 重新加载数据库配置文件
        Config::load($filename, 'database');
    }

    /**
     * 初始化
     *
     * @param Input $input
     * @param Output $output
     */
    protected function init(Input $input, Output $output)
    {
        global $argv;

        $argv[1] = $input->getArgument('status') ? : 'start';

        if ($input->hasOption('d')) {
            $argv[2] = '-d';
        } else {
            unset($argv[2]);
        }
    }

    /**
     * 停止定时器
     */
    public function stop()
    {
        Timer::del($this->timer);
    }

    /**
     * 启动定时器
     */
    public function start()
    {
        $this->timer = Timer::add($this->interval, function () {
            # 只在凌晨12点至6点间执行
            if ((int)date('H') >= 0 && (int)date('H') < 6) {
                # 公海规则
                $ruleList = db('crm_customer_pool_rule')->alias('rule')->field('rule.*')
                    ->join('__CRM_CUSTOMER_POOL__ pool', 'pool.pool_id = rule.pool_id', 'LEFT')->where('pool.status', 1)->select();

                if (!empty($ruleList)) {
                    # 符合公海条件的客户IDS
                    $customerIds = $this->getQueryCondition($ruleList);

                    # 整理客户公海关联数据
                    $poolRelationData = $this->getCustomerPoolRelationData($customerIds);

                    # 整理修改客户数据的条件（进入公海时间，前负责人...）
                    $customerWhere = $this->getCustomerQueryCondition($customerIds);

                    Db::startTrans();
                    try {
                        # 将客户退回公海
                        if (!empty($poolRelationData)) Db::name('crm_customer_pool_relation')->insertAll($poolRelationData);

                        # 修改客户数据
                        if (!empty($customerWhere)) {
                            Db::name('crm_customer')->whereIn('customer_id', $customerWhere)->exp('before_owner_user_id', 'owner_user_id')->update([
                                'ro_user_id' => '',
                                'rw_user_id' => '',
                                'owner_user_id' => 0,
                                'into_pool_time' => time()
                            ]);
                        }

                        # 删除联系人的负责人
                        Db::name('crm_contacts')->whereIn('customer_id', $customerWhere)->update(['owner_user_id' => '']);

                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                    }
                }
            }
        });
    }

    protected function execute(Input $input, Output $output)
    {
        # 动态修改运行时参数
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->init($input, $output);

        # 创建定时器任务
        $task = new Worker();
        $task->name = 'pool';
        $task->count = 1;
        $task->onWorkerStart = [$this, 'start'];
        $task->runAll();
    }

    /**
     * 整理修改客户数据的条件
     *
     * @param array $customerIds 客户ID
     * @author fanqi
     * @since 2021-04-01
     * @return array
     */
    private function getCustomerQueryCondition($customerIds)
    {
        $result = [];

        foreach ($customerIds AS $k1 => $v1) {
            foreach ($v1 AS $k2 => $v2) {
                $result[] = $v2;
            }
        }

        return array_unique($result);
    }

    /**
     * 客户公海关联数据
     *
     * @param array $customerIds 客户ID
     * @author fanqi
     * @since 2021-04-01
     * @return array
     */
    private function getCustomerPoolRelationData($customerIds)
    {
        $result = [];

        # 用于排重
        $repeat = [];

        foreach ($customerIds AS $k1 => $v1) {
            $customerArray = array_unique($v1);
            foreach ($customerArray AS $k2 => $v2) {
                if (!empty($repeat[$k1][$v2])) continue;

                $result[] = [
                    'pool_id' => $k1,
                    'customer_id' => $v2
                ];

                $repeat[$k1][$v2] = $v2;
            }
        }

        return $result;
    }

    /**
     * 获取符合公海条件的客户
     *
     * @param array $rules 公海规则数据
     * @author fanqi
     * @since 2021-04-01
     * @return array
     */
    private function getQueryCondition($rules)
    {
        $result = [];

        foreach ($rules AS $k => $v) {
            if (!isset($result[$v['pool_id']])) $result[$v['pool_id']] = [];

            if ($v['type'] == 1) $result[$v['pool_id']] = array_merge($result[$v['pool_id']], $this->getFollowUpQueryResult($v['level_conf'], $v['level'], $v['deal_handle'], $v['business_handle']));
            if ($v['type'] == 2) $result[$v['pool_id']] = array_merge($result[$v['pool_id']], $this->getBusinessQueryResult($v['level_conf'], $v['level'], $v['deal_handle']));
            if ($v['type'] == 3) $result[$v['pool_id']] = array_merge($result[$v['pool_id']], $this->getDealQueryResult($v['level_conf'], $v['level'], $v['business_handle']));
        }

        return $result;
    }

    /**
     * N天内无新建跟进记录的客户
     *
     * @param int $type 类型：1 所有用户，不分级别，2 根据用户级别区分
     * @param Json $levels 级别数据
     * @param int $dealStatus 是否排除成交用户：1 排除，0 不排除
     * @param int $businessStatus 是否排除有商机用户：1 排除，0 不排除
     * @author fanqi
     * @since 2021-04-01
     * @return array
     */
    private function getFollowUpQueryResult($type, $levels, $dealStatus, $businessStatus)
    {
        # 转换格式
        $levels = json_decode($levels, true);

        # 默认条件
        $where = "`customer`.`owner_user_id` > 0";

        # 所有用户，不区分级别
        if ($type == 1) {
            foreach ($levels AS $k1 => $v1) {
                if (!empty($v1['limit_day'])) {
                    $time = time() - 24 * 60 * 60 * $v1['limit_day'];
                    $where .= " AND ((`customer`.`last_time` < ".$time." AND `customer`.`last_time` > `customer`.`obtain_time`) OR (`customer`.`obtain_time` < ".$time." AND `customer`.`obtain_time` > `customer`.`last_time`) OR (`customer`.`obtain_time` < ".$time." AND ISNULL(`customer`.`last_time`)))";
                }
            }
        }

        # 根据用户级别设置条件
        if ($type == 2) {
            foreach ($levels AS $k1 => $v1) {
                if (!empty($v1['level']) && !empty($v1['limit_day'])) {
                    $time = (time() - 24 * 60 * 60 * $v1['limit_day']);
                    if ($k1 == 0) {
                        $where .= " AND ( ((`customer`.`level` = '".$v1['level']."' AND `customer`.`last_time` < ".$time." AND `customer`.`last_time` > `customer`.`obtain_time`) OR (`customer`.`level` = '".$v1['level']."' AND `customer`.`obtain_time` < ".$time." AND `customer`.`obtain_time` > `customer`.`last_time`) OR (`customer`.`level` = '".$v1['level']."' AND `customer`.`obtain_time` < ".$time." AND ISNULL(`customer`.`last_time`)))";
                    } else {
                        $where .= " OR ((`customer`.`level` = '".$v1['level']."' AND `customer`.`last_time` < " . $time . " AND `customer`.`last_time` > `customer`.`obtain_time`) OR (`customer`.`level` = '".$v1['level']."' AND `customer`.`obtain_time` < " . $time . " AND `customer`.`obtain_time` > `customer`.`last_time`) OR (`customer`.`level` = '".$v1['level']."' AND `customer`.`obtain_time` < " . $time . " AND ISNULL(`customer`.`last_time`)))";
                    }
                }
            }

            # 获取最小天数，对于没有设置级别的客户数据使用
            $minLimit = $this->getMinDay($levels);
            $minTime = (time() - 24 * 60 * 60 * $minLimit);

            $where .= " OR ((!`customer`.`level` AND `customer`.`last_time` < ".$minTime." AND `customer`.`last_time` > `customer`.`obtain_time`) OR (!`customer`.`level` AND `customer`.`obtain_time` < ".$minTime." AND `customer`.`obtain_time` > `customer`.`last_time`) OR (!`customer`.`level` AND `customer`.`obtain_time` < ".$minTime." AND ISNULL(`customer`.`last_time`))) )";
        }

        # 选择不进入公海的客户（已成交客户）
        if (!empty($dealStatus)) $where .= " AND (`customer`.`deal_status` <> '已成交' OR ISNULL(`customer`.`deal_status`))";

        # 选择不进入公海的客户（有商机客户)
        if (!empty($businessStatus)) $where .= " AND ISNULL(`business`.`customer_id`)";

        # 锁定的客户不提醒
        $where .= " AND `customer`.`is_lock` = 0";

        # 查询符合条件的客户
        return db('crm_customer')
            ->alias('customer')->join('__CRM_BUSINESS__ business', 'business.customer_id = customer.customer_id', 'LEFT')
            ->where($where)->column('customer.customer_id');
    }

    /**
     * N天内无新建商机的客户
     *
     * @param int $type 类型：1 所有用户，不分级别，2 根据用户级别区分
     * @param Json $levels 级别数据
     * @param int $dealStatus 是否排除成交用户：1 排除，0 不排除
     * @author fanqi
     * @since 2021-04-01
     * @return array|false|string
     */
    private function getBusinessQueryResult($type, $levels, $dealStatus)
    {
        # 转换格式
        $levels = json_decode($levels, true);

        # 默认条件
        $where = "`customer`.`owner_user_id` > 0";

        # 所有用户，不区分级别
        if ($type == 1) {
            foreach ($levels AS $k1 => $v1) {
                if (!empty($v1['limit_day'])) {
                    $time = time() - 24 * 60 * 60 * $v1['limit_day'];
                    $where .= " AND ( (ISNULL(`business`.`customer_id`) AND `customer`.`obtain_time` < ".$time.") OR (`customer`.`obtain_time` < ".$time." AND `customer`.`obtain_time` > `business`.`create_time`) OR (`business`.`create_time` < ".$time." AND `business`.`create_time` > `customer`.`obtain_time`) )";
                }
            }
        }

        # 根据用户级别设置条件
        if ($type == 2) {
            foreach ($levels AS $k1 => $v1) {
                if (!empty($v1['level']) && !empty($v1['limit_day'])) {
                    $time = time() - 24 * 60 * 60 * $v1['limit_day'];
                    if ($k1 == 0) {
                        $where .= " AND ( ((ISNULL(`business`.`customer_id`) AND `customer`.`obtain_time` < ".$time." AND `customer`.`level` = '".$v1['level']."') OR (`customer`.`obtain_time` < ".$time." AND `customer`.`obtain_time` > `business`.`create_time` AND `customer`.`level` = '".$v1['level']."') OR (`business`.`create_time` < ".$time." AND `business`.`create_time` > `customer`.`obtain_time` AND `customer`.`level` = '".$v1['level']."'))";
                    } else {
                        $where .= " OR ((ISNULL(`business`.`customer_id`) AND `customer`.`obtain_time` < ".$time." AND `customer`.`level` = '".$v1['level']."') OR (`customer`.`obtain_time` < ".$time." AND `customer`.`obtain_time` > `business`.`create_time` AND `customer`.`level` = '".$v1['level']."') OR (`business`.`create_time` < ".$time." AND `business`.`create_time` > `customer`.`obtain_time` AND `customer`.`level` = '".$v1['level']."'))";
                    }
                }
            }

            # 获取最小天数，对于没有设置级别的客户数据使用
            $minLimit = $this->getMinDay($levels);
            $minTime = (time() - 24 * 60 * 60 * $minLimit);

            $where .= " OR ((ISNULL(`business`.`customer_id`) AND `customer`.`obtain_time` < ".$minTime." AND !`customer`.`level`) OR (`customer`.`obtain_time` < ".$minTime."  AND `customer`.`obtain_time` > `business`.`create_time`  AND !`customer`.`level`) OR (`business`.`create_time` < ".$minTime." AND `business`.`create_time` > `customer`.`obtain_time` AND !`customer`.`level`)) )";
        }

        # 选择不进入公海的客户（已成交客户）
        if (!empty($dealStatus)) $where .= " AND (`customer`.`deal_status` <> '已成交' OR ISNULL(`customer`.`deal_status`))";

        # 锁定的客户不提醒
        $where .= " AND `customer`.`is_lock` = 0";

        # 查询匹配条件的客户
        return db('crm_customer')->alias('customer')
            ->join('__CRM_BUSINESS__ business', 'business.customer_id = customer.customer_id', 'LEFT')
            ->where($where)->column('customer.customer_id');
    }

    /**
     * N天内没有成交的客户
     *
     * @param int $type 类型：1 所有用户，不分级别，2 根据用户级别区分
     * @param Json $levels 级别数据
     * @param int $businessStatus 是否排除有商机用户：1 排除，0 不排除
     * @author fanqi
     * @since 2021-04-01
     * @return array|false|string
     */
    private function getDealQueryResult($type, $levels, $businessStatus)
    {
        # 转换格式
        $levels = json_decode($levels, true);

        # 默认条件
        $where = "`customer`.`owner_user_id` > 0";

        # 所有用户，不区分级别
        if ($type == 1) {
            foreach ($levels AS $k1 => $v1) {
                if (!empty($v1['limit_day'])) {
                    $time = time() - 24 * 60 * 60 * $v1['limit_day'];
                    $where .= " AND ( (ISNULL(`contract`.`customer_id`) AND `customer`.`obtain_time` < ".$time.") OR (`customer`.`obtain_time` < ".$time." AND `customer`.`obtain_time` > `contract`.`create_time`) OR (`contract`.`create_time` < ".$time." AND `contract`.`create_time` > `customer`.`obtain_time`) )";
                }
            }
        }

        # 根据用户级别设置条件
        if ($type == 2) {
            foreach ($levels AS $k1 => $v1) {
                if (!empty($v1['level']) && !empty($v1['limit_day'])) {
                    $time = time() - 24 * 60 * 60 * $v1['limit_day'];
                    if ($k1 == 0) {
                        $where .= " AND ( ((ISNULL(`contract`.`customer_id`) AND `customer`.`obtain_time` < ".$time." AND `customer`.`level` = '".$v1['level']."') OR (`customer`.`obtain_time` < ".$time." AND `customer`.`obtain_time` > `contract`.`create_time` AND `customer`.`level` = '".$v1['level']."') OR (`contract`.`create_time` < ".$time." AND `contract`.`create_time` > `customer`.`obtain_time` AND `customer`.`level` = '".$v1['level']."'))";
                    } else {
                        $where .= " OR ((ISNULL(`contract`.`customer_id`) AND `customer`.`obtain_time` < ".$time." AND `customer`.`level` = '".$v1['level']."') OR (`customer`.`obtain_time` < ".$time." AND `customer`.`obtain_time` > `contract`.`create_time` AND `customer`.`level` = '".$v1['level']."') OR (`contract`.`create_time` < ".$time." AND `contract`.`create_time` > `customer`.`obtain_time` AND `customer`.`level` = '".$v1['level']."'))";
                    }
                }
            }

            # 获取最小天数，对于没有设置级别的客户数据使用
            $minLimit = $this->getMinDay($levels);
            $minTime = (time() - 24 * 60 * 60 * $minLimit);

            $where .= " OR ((ISNULL(`contract`.`customer_id`) AND `customer`.`obtain_time` < ".$minTime." AND !`customer`.`level`) OR (`customer`.`obtain_time` < ".$minTime." AND `customer`.`obtain_time` > `contract`.`create_time` AND !`customer`.`level`) OR (`contract`.`create_time` < ".$minTime." AND `contract`.`create_time` > `customer`.`obtain_time` AND !`customer`.`level`)) )";
        }

        # 选择不进入公海的客户（有商机客户）
        if (!empty($businessStatus)) $where .= " AND ISNULL(`business`.`customer_id`)";

        # 锁定的客户不提醒
        $where .= " AND `customer`.`is_lock` = 0";

        # 查询符合条件的客户
        return db('crm_customer')->alias('customer')
            ->join('__CRM_BUSINESS__ business', 'business.customer_id = customer.customer_id', 'LEFT')
            ->join('__CRM_CONTRACT__ contract', 'contract.customer_id = customer.customer_id', 'LEFT')
            ->where($where)->column('customer.customer_id');
    }

    /**
     * 获取公海规则最小数字（最快进入公海天数）
     *
     * @param $data
     * @author fanqi
     * @since 2021-04-19
     * @return int
     */
    private function getMinDay($data)
    {
        $number = 1;

        foreach ($data AS $k1 => $v1) {
            if (empty($number) || $v1['limit_day'] < $number) $number = $v1['limit_day'];
        }

        return $number;
    }
}