<?php
namespace com;

use think\Cache;

/**
 * 定时任务
 *
 * @link https://github.com/yunwuxin/think-cron
 * @author yunwuxin
 */
abstract class Cron
{
    /**
     * 任务周期
     * 分 时 日 月 周
     */
    public $expression = '* * * * *';

    /**
     * 执行时间
     */
    public $expiresAt = 1200;

    /**
     * 过滤方法
     */
    protected $filters = [];

    /**
     * 跳过方法
     */
    protected $rejects = [];

    /** 
     * 任务是否可以重叠执行
     */
    public $withoutOverlapping = false;

    /**
     * 日志目录
     */
    const LOG_PATH = RUNTIME_PATH . 'cron_log';

    public function __construct()
    {
        $this->configure();
    }

    /**
     * 是否到期执行
     * @return bool
     */
    public function isDue()
    {
        $e = explode(' ', $this->expression);
        $e = array_values(array_filter($e));
        if (count($e) == 5) {
            $ex = ['i', 'G', 'j', 'n', 'w'];

            foreach ($ex as $k => $v) {
                if ($e[$k] !== '*' && !in_array((int) date($v), $this->tt($e[$k]))) {
                    $this->msg = "{$v} 不合格";
                    return false;
                }
            }
            return true;
        } else {
            $this->msg = '任务表达式不合法';
            return false;
        }
    }

    /**
     * 简单的cron表达式处理
     *
     * @param string $e
     * @param array $res
     * @return void
     * @author Ymob
     * @datetime 2019-12-19 10:10:21
     */
    protected function tt($e = '', $res = [])
    {
        if (false !== strpos($e, ',')) {
            foreach (array_map('trim', explode(',', $e)) as $temp) {
                $res = $this->tt($temp, $res);
            }
        } else {
            if (\is_numeric($e)) {
                $res[] = (int) $e;
            } elseif (1 == substr_count($e, '-')) {
                list($start, $end) = array_map('trim', explode('-', $e));
                if (is_numeric($start) && is_numeric($end)) {
                    for ($start; $start <= $end; $start++) {
                        $res[] = (int) $start;
                    }
                }
            } elseif (1 == substr_count($e, '/')) {
                list($start, $step) = array_map('trim', explode('/', $e));
                if ((is_numeric($start) || $start == '*') && is_numeric($step)) {
                    if ($start == '*') {
                        $start = 0;
                    }

                    for ($start; $start < 60; $start += $step) {
                        $res[] = (int) $start;
                    }
                }
            }
        }
        return $res;
    }

    /**
     * 配置任务
     */
    protected function configure()
    {
    }

    /**
     * 执行任务
     * @return mixed
     */
    abstract protected function execute();

    final public function run()
    {
        if (!$this->withoutOverlapping && !$this->createMutex()) {
            $this->log('repeated execution, continue.');
            return;
        }
        register_shutdown_function(function () {
            $this->removeMutex();
        });

        try {
            $this->execute();
        } finally {
            $this->removeMutex();
        }
    }

    /**
     * 过滤
     * @return bool
     */
    public function filtersPass()
    {
        foreach ($this->filters as $callback) {
            if (!call_user_func($callback)) {
                return false;
            }
        }
        foreach ($this->rejects as $callback) {
            if (call_user_func($callback)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 任务标识
     */
    public function mutexName()
    {
        return 'task-' . sha1(static::class);
    }

    /**
     * 删除互斥
     */
    protected function removeMutex()
    {
        return Cache::rm($this->mutexName());
    }

    /**
     * 创建互斥
     */
    protected function createMutex()
    {
        $name = $this->mutexName();
        if (!Cache::has($name)) {
            Cache::set($name, true, $this->expiresAt);
            return true;
        }
        return false;
    }

    /**
     * 是否存在互斥
     */
    protected function existsMutex()
    {
        return Cache::has($this->mutexName());
    }

    /**
     * 添加过滤筛选
     */
    public function when(Closure $callback)
    {
        $this->filters[] = $callback;
        return $this;
    }

    /**
     * 添加跳过筛选
     */
    public function skip(Closure $callback)
    {
        $this->rejects[] = $callback;
        return $this;
    }

    /**
     * 允许重叠执行程序
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;
        $this->expiresAt = $expiresAt;
        return $this->skip(function () {
            return $this->existsMutex();
        });
    }

    /**
     * 任务日志
     *
     * @param string $content
     * @datetime 2019-12-19 11:27:01
     */
    public function log($content = 'success')
    {
        if (!file_exists(self::LOG_PATH)) {
            mkdir(self::LOG_PATH);
        }
        $log_file = self::LOG_PATH . DS . str_replace('\\', '-', static::class) . '.log';

        if (!is_string($content)) {
            $content = json_encode($content);
        }

        $time = "[" . date('Y-m-d H:i:s') . "]" . PHP_EOL;
        $content = $time . $content . PHP_EOL . PHP_EOL;

        file_put_contents($log_file, $content, FILE_APPEND);
    }
}
