<?php
// +----------------------------------------------------------------------
// | 前台栏目列表控制器
// | 注：类名用 Lists 而非 List（List 是 PHP 保留字），URL 为 /index/lists/index
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\Category;
use app\common\model\Content;

class Lists extends FrontBase
{
    /**
     * 栏目列表页
     */
    public function index()
    {
        $id   = (int)input('param.id', input('param.catid', 0));
        $page = max(1, (int)input('get.page', 1));

        $cat = Category::find($id);
        if (!$cat) {
            abort(404, '栏目不存在');
        }

        // 单页类型直接渲染内容（补全 show 模板所需字段，避免 Undefined array key）
        if ($cat['type'] === 'single') {
            $tpl = $cat['content_template'] ?: 'show';
            $tplDir = root_path() . 'template' . DIRECTORY_SEPARATOR . lang_tpl_dir();
            if (!is_file($tplDir . DIRECTORY_SEPARATOR . $tpl . '.html')) $tpl = 'show';
            return view('/' . $tpl, [
                'category' => $cat,
                'content'  => [
                    'id'           => (int)$cat['id'],
                    'catid'        => (int)$cat['id'],
                    'model_id'     => (int)($cat['model_id'] ?? 0),
                    'title'        => (string)$cat['name'],
                    'author'       => '',
                    'source'       => '',
                    'keywords'     => (string)($cat['keywords'] ?? ($cat['seo_title'] ?? '')),
                    'desc'         => attach_download_rewrite((string)($cat['description'] ?? '')),
                    'description'  => attach_download_rewrite((string)($cat['description'] ?? '')),
                    'content'      => attach_download_rewrite((string)($cat['content'] ?? '')),
                    'thumb'        => '',
                    'image'        => '',
                    'views'        => 0,
                    'create_time' => (int)($cat['update_time'] ?? time()),
                    'sort'         => (int)($cat['sort'] ?? 0),
                    'status'       => 1,
                    'is_top'       => 0,
                    'is_recommend' => 0,
                    'is_hot'       => 0,
                ],
                'is_single' => true,
                'cur_catid' => $cat['id'],
            ]);
        }
        // 外链 / 跳转
        if ($cat['type'] === 'external') {
            redirect($cat['external_url'] ?: '/')->send();
            exit;
        }
        if ($cat['type'] === 'redirect') {
            $target = redirect_target_url($cat);
            if ($target) {
                redirect($target)->send();
                exit;
            }
            // 无有效目标则回落为列表页（列表可能为空）
        }

        // 列表：聚合子栏目
        $ids  = Category::getChildIds($id);
        $query = Content::published()->whereIn('catid', $ids);
        // 多维扩展字段筛选（GET f_字段标识=值，如 f_area=吉林&f_subject=语文）
        $filters = self::applyFilters($query);
        $list = (clone $query)
            ->order('is_top', 'desc')
            ->order('sort', 'asc')
            ->order('create_time', 'desc')
            ->paginate([
                // 每页条数：栏目设置 pagesize（默认10），栏目列表页/标签可覆盖
                'list_rows' => max(1, (int)($cat['pagesize'] ?: 10)),
                'page'      => $page,
            ]);

        // 列表项描述中的附件链接重写为下载端点
        foreach ($list as &$item) {
            if (!empty($item['description'])) {
                $item['description'] = attach_download_rewrite($item['description']);
            }
        }
        unset($item);

        // 商品价格映射（v3.14 商城演示）：本页内容对应 yy_shop 价格，模板 {$shop_map[$list.id].price}
        // 商城开关关闭时不注入（v3.16）
        $shopMap = [];
        if ((int)get_config('shop.open', 1) === 1) {
            try {
                $ids2 = array_column($list->items(), 'id');
                if ($ids2) {
                    $shops = \think\facade\Db::name('shop')->where('lang', lang_code())->whereIn('conid', $ids2)->select();
                    foreach ($shops as $s) {
                        $shopMap[(int)$s['conid']] = [
                            'price'          => (float)$s['price'],
                            'original_price' => (float)$s['original_price'],
                            'stock'          => (int)$s['stock'],
                            'sales'          => (int)$s['sales'],
                            'status'         => (int)$s['status'],
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $shopMap = [];
            }
        }

        // 列表模板：优先栏目 template（list_xxx），否则默认 list
        $tpl = $cat['template'] ?: 'list';
        $tplDir = root_path() . 'template' . DIRECTORY_SEPARATOR . lang_tpl_dir();
        if (!is_file($tplDir . DIRECTORY_SEPARATOR . $tpl . '.html')) $tpl = 'list';
        return view('/' . $tpl, [
            'category' => $cat,
            'list'     => $list,
            'page'     => $page,
            'cur_catid' => $cat['id'],
            'cid'      => $cat['id'],
            // 查看更多模式：首屏已显示条数（前端按钮 data-offset 初始值）
            'more_offset' => count($list->items()),
            // 多维筛选选中值（模板高亮）
            'filters'     => $filters,
            // 商品价格映射（列表页显示价格）
            'shop_map'    => $shopMap,
        ]);
    }

    /**
     * 查看更多（加载更多）分页：AJAX 返回列表模板整页，前端提取 #yy-list 内容追加
     * 与常规翻页（{$list|raw}）相互独立，模板自行选择使用哪种：
     *   翻页：列表页输出 <div class="pages">{$list|raw}</div>
     *   查看更多：列表容器 <ul ... id="yy-list"> + 底部查看更多按钮（见前台标签调用手册）
     * 参数：cid 栏目ID、offset 已显示条数（首屏条数，前端按钮 data-offset 携带）、size 每次追加条数
     */
    public function more()
    {
        $cid    = (int)input('get.cid', 0);
        $offset = max(0, (int)input('get.offset', 0));
        $size   = max(1, (int)input('get.size', 10));

        $cat = Category::find($cid);
        // 栏目不存在 / 外链 / 跳转栏目不支持查看更多
        if (!$cat || in_array($cat['type'], ['external', 'redirect'], true)) {
            return response('');
        }

        // 与 index 完全一致的列表查询（子栏目聚合 + 排序），从 offset 开始取 size 条
        $ids   = Category::getChildIds($cid);
        $query = Content::published()->whereIn('catid', $ids);
        // 多维扩展字段筛选（与 index 一致，保持查看更多联动）
        self::applyFilters($query);
        $total = (clone $query)->count();

        // 已无更多数据：返回空，前端按钮显示「没有更多了」
        if ($offset >= $total) {
            return response('');
        }

        $list = (clone $query)
            ->order('is_top', 'desc')
            ->order('sort', 'asc')
            ->order('create_time', 'desc')
            ->limit($offset, $size)
            ->select();
        if (empty($list)) {
            return response('');
        }

        foreach ($list as &$item) {
            if (!empty($item['description'])) {
                $item['description'] = attach_download_rewrite($item['description']);
            }
        }
        unset($item);

        $tpl = $cat['template'] ?: 'list';
        $tplDir = root_path() . 'template' . DIRECTORY_SEPARATOR . lang_tpl_dir();
        if (!is_file($tplDir . DIRECTORY_SEPARATOR . $tpl . '.html')) $tpl = 'list';
        return view('/' . $tpl, [
            'category'   => $cat,
            'list'       => $list,
            'page'       => (int)floor($offset / $size) + 1,
            'cur_catid'  => $cat['id'],
            'cid'        => $cat['id'],
            'is_more'    => true,
        ]);
    }

    /**
     * 多维扩展字段筛选：委托 TagService::applyFilterParams（{cms:list}/{yunu:list} 标签与控制器分页统一过滤）
     * 读取 GET f_字段标识=值（如 f_area=吉林&f_subject=语文&f_class=一班），返回选中值数组供模板高亮。
     */
    protected static function applyFilters($query)
    {
        return \app\common\service\TagService::applyFilterParams($query);
    }
}
