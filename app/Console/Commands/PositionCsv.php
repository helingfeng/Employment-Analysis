<?php

namespace App\Console\Commands;


use App\Crawler\Capture;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class PositionCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:csv-position';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取就业职位信息，并存储为 Csv 文件';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('keyword', null, InputOption::VALUE_REQUIRED, '职位搜索关键词.');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $kd = $this->option('keyword');
        $output = $this->getOutput();
        $output->writeln("正在抓取关键字 {$kd} 相关职位数据...");

        $capture = new Capture();
        $positions = $capture->getPositionListByPage();
        $heading = array_keys(array_first($positions));

        $filename = "positions_{$kd}.csv";
        $fp = fopen(storage_path('app/public') . "/{$filename}", 'w+');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($fp, $heading);
        $p = 1;
        do {
            // 休眠一段时间
            sleep(3);
            $output->writeln("正在抓取第{$p}页.");
            $positions = $capture->getPositionListByPage($p++, $kd);
            foreach ($positions as $position) {
                foreach ($position as &$field) {
                    if (is_array($field)) {
                        $field = implode(',', $field);
                    } else if (!is_string($field)) {
                        $field = json_encode($field, JSON_UNESCAPED_UNICODE);
                    }
                }
                fputcsv($fp, $position);
            }
        } while (!empty($positions));
        fclose($fp);
        $output->writeln("任务已完成，程序自动退出.");

    }
}
