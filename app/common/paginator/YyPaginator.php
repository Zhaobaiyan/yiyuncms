<?php
// +----------------------------------------------------------------------
// | 易云CMS 前台自定义分页驱动
// | 输出结构：<ul class="pagination"><li>首页</li><li>«</li><li>数字</li><li>»</li><li>尾页</li></ul>
// | 网站设置 → 前台翻页样式可配置：
// |   base.page_mode        full（完整数字，超过阈值显示省略号）/ window（固定滑动窗口）
// |   base.page_window      滑动窗口数字个数（默认 7：第1页 1..7，第2页 2..8）
// |   base.page_first_last  是否显示「首页 / 尾页」按钮（1 显示 / 0 不显示）
// |   base.page_full_limit  full 模式的省略阈值（默认 15 页：超过则 前3 … 后3）
// +----------------------------------------------------------------------
namespace app\common\paginator;

use think\paginator\driver\Bootstrap;

class YyPaginator extends Bootstrap
{
    /**
     * 渲染分页 HTML
     */
    public function render()
    {
        if (!$this->hasPages()) {
            return '';
        }
        $mode      = get_config('base.page_mode', 'full');
        $firstLast = (string)get_config('base.page_first_last', '1') === '1';

        $html = '<ul class="pagination">';
        if ($firstLast) {
            $html .= $this->getFirstButton('首页');
        }
        $html .= $this->getPreviousButton('«');
        $html .= $this->buildLinks($mode);
        $html .= $this->getNextButton('»');
        if ($firstLast) {
            $html .= $this->getLastButton('尾页');
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * 首页按钮（当前在第 1 页时为禁用态）
     */
    protected function getFirstButton(string $text = '首页'): string
    {
        if ($this->currentPage() <= 1) {
            return $this->getDisabledTextWrapper($text);
        }
        return $this->getPageLinkWrapper($this->url(1), $text);
    }

    /**
     * 尾页按钮（当前在最后一页时为禁用态）
     */
    protected function getLastButton(string $text = '尾页'): string
    {
        if ($this->currentPage() >= $this->lastPage) {
            return $this->getDisabledTextWrapper($text);
        }
        return $this->getPageLinkWrapper($this->url($this->lastPage), $text);
    }

    /**
     * 数字页码区
     * @param string $mode full=完整数字(超阈值省略) / window=固定滑动窗口
     */
    protected function buildLinks(string $mode): string
    {
        if ($this->simple) {
            return '';
        }
        $win = max(3, (int)get_config('base.page_window', 7));

        // ---- 固定滑动窗口：第1页 1..N，第2页 2..N+1（边界自动对齐） ----
        if ($mode === 'window') {
            $start = $this->currentPage();
            $end   = min($this->lastPage, $start + $win - 1);
            if ($end - $start + 1 < $win) {
                $start = max(1, $end - $win + 1);
            }
            $html = '';
            for ($p = $start; $p <= $end; $p++) {
                $html .= $this->getPageLinkWrapper($this->url($p), (string)$p);
            }
            return $html;
        }

        // ---- 完整数字：不超过阈值全部显示；超过则 前3 … 后3 ----
        $limit = max(5, (int)get_config('base.page_full_limit', 15));
        if ($this->lastPage <= $limit) {
            $html = '';
            for ($p = 1; $p <= $this->lastPage; $p++) {
                $html .= $this->getPageLinkWrapper($this->url($p), (string)$p);
            }
            return $html;
        }

        $cur  = $this->currentPage();
        $side = 3; // 当前页前后各显示 3 个
        $head = '';
        $mid  = '';
        $tail = '';
        if ($cur <= $side * 2 + 1) {
            // 靠近开头：1..7 … 末页
            for ($p = 1; $p <= $side * 2 + 1; $p++) {
                $head .= $this->getPageLinkWrapper($this->url($p), (string)$p);
            }
            $tail .= $this->getDots() . $this->getPageLinkWrapper($this->url($this->lastPage), (string)$this->lastPage);
        } elseif ($cur > $this->lastPage - $side * 2 - 1) {
            // 靠近结尾：首页 … (末7个)
            $head .= $this->getPageLinkWrapper($this->url(1), '1') . $this->getDots();
            for ($p = $this->lastPage - $side * 2 - 1; $p <= $this->lastPage; $p++) {
                $tail .= $this->getPageLinkWrapper($this->url($p), (string)$p);
            }
        } else {
            // 中间：首页 … (当前±3) … 末页
            $head .= $this->getPageLinkWrapper($this->url(1), '1') . $this->getDots();
            for ($p = $cur - $side; $p <= $cur + $side; $p++) {
                $mid .= $this->getPageLinkWrapper($this->url($p), (string)$p);
            }
            $tail .= $this->getDots() . $this->getPageLinkWrapper($this->url($this->lastPage), (string)$this->lastPage);
        }
        return $head . $mid . $tail;
    }
}
