<?php
namespace app\common\command;

use app\admin\model\Record;
use app\admin\model\User;
use app\crm\model\Customer;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Request;

class ImportFollowRecord extends Command
{
    protected function configure()
    {
        $this->setName('import:record')
            ->addArgument('file_path', null, '导入文件路径')
            ->setDescription('导入跟进记录');
    }

    protected function execute(Input $input, Output $output)
    {
        /**
         * 第三行开始、共六列
         * 
         * 客户名称*	客户号码*	跟进方式*	跟进内容*	跟进人*	 跟进时间
         */
        set_time_limit(0);
        Request::instance()->module('crm');
        Config::load(APP_PATH . '../config/database.php', 'database');

        $file_path = $input->getArgument('file_path');

        $user_list = User::field(['id', 'realname'])->select();
        $user_map = array_column($user_list, 'id', 'realname');

        if (file_exists($file_path)) {

            // 加载导入数据文件
            $objRender = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $objRender->setReadDataOnly(true);
            $ExcelObj = $objRender->load($file_path);

            // 指定工作表
            $sheet = $ExcelObj->getSheet(0);

            // 总行数
            $max_row = $sheet->getHighestRow();
            $max_column = $sheet->getHighestColumn();

            $log_file = RUNTIME_PATH . 'import_record/' . date('Y_m_d_H_i') . '.log';
            $data_file = RUNTIME_PATH . 'import_record/' . date('Y_m_d_H_i') . '.data';
            if (!file_exists(RUNTIME_PATH . 'import_record')) {
                $res = mkdir(RUNTIME_PATH . 'import_record', '0777', true);
                if (!$res) {
                    $output->writeln('Runtime 目录无权限');
                    return;
                }
            }

            $ask = $output->ask($input, '共 ' . ($max_row - 2) . ' 条数据，开始导入(yes OR no)？', 'no');
            if ($ask != 'yes') {
                $output->writeln('已取消');
                return;
            }

            for ($i = 3; $i <= $max_row; $i++) {
                $res = $sheet->rangeToArray("A{$i}:F{$i}")[0];

                $user_id = $user_map[$res[4]] ?? 1;

                $time = strtotime($res[5]);

                $mobile = preg_replace('/[^\d]/', '', $res[1]);

                $customer_id = Customer::where(['name' => $res[0], 'mobile' => $mobile])->value('customer_id');

                if (!$customer_id) {
                    $info = "{$res[0]}@{$res[1]}  未找到客户". PHP_EOL;
                    $output->writeln($info);
                    file_put_contents($log_file, $info . PHP_EOL, FILE_APPEND);

                    file_put_contents($data_file, json_encode($res) . PHP_EOL, FILE_APPEND);

                    continue;
                }

                $data = [
                    'types' => 'crm_customer',
                    'types_id' => $customer_id,
                    'content' => $res[3],
                    'category' => $res[2],
                    'next_time' => 0,
                    'business_ids' => '',
                    'contacts_ids' => '',
                    'create_time' => $time,
                    'update_time' => $time,
                    'create_user_id' => $user_id,
                ];

                if (!Record::insert($data)) {
                    $info = "{$res[0]}@{$res[1]} 写入数据库失败" . PHP_EOL;
                    $output->writeln($info);
                    file_put_contents($log_file, $info . PHP_EOL, FILE_APPEND);

                    file_put_contents($data_file, json_encode($res) . PHP_EOL, FILE_APPEND);
                }

            }

        } else {
            $output->writeln('请输入正确的文件路径');
        }
    }
}
