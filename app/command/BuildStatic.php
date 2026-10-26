<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use app\common\service\StaticPage;

/**
 * 命令行一键生成全站静态页（无需登录后台）
 * 用法: php think build:static [语言代码]   —— 缺省生成全部语言
 */
class BuildStatic extends Command
{
    protected function configure()
    {
        $this->setName('build:static')
            ->addArgument('lang', Argument::OPTIONAL, '语言代码，缺省生成全部语言')
            ->setDescription('一键生成全站静态页（可指定语言）');
    }

    protected function execute(Input $input, Output $output)
    {
        $lang = $input->getArgument('lang');
        if ($lang) {
            $count = StaticPage::buildLang((string)$lang);
            $output->writeln("生成完成 [{$lang}]：{$count} 个页面");
        } else {
            $count = StaticPage::all();
            $output->writeln("生成完成 [全部语言]：{$count} 个页面");
        }
        return 0;
    }
}
