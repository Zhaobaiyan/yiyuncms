<?php
// +----------------------------------------------------------------------
// | 前台标签服务层
// | 所有 {cms:xxx} 标签均在此实现数据逻辑，控制器/模板也可直接调用
// +----------------------------------------------------------------------
namespace app\common\service;

use app\common\model\Ad;
use app\common\model\Block;
use app\common\model\Category;
use app\common\model\Content;
use app\common\model\Link;

class TagService
{
    /**
     * 读取站点配置
     */
    public static function setting($name, $default = '')
    {
        return get_setting($name, $default);
    }

    /**
     * 内容列表（{cms:list}）
     * @param int|string $catid   栏目ID，0为全部，支持逗号分隔
     * @param int        $num     数量
     * @param string     $order   排序 list/views/update/time/rand
     * @param int        $modelId 模型ID，0不限
     * @param string     $flag    推荐标记 recommend/top/hot 或组合
     * @param string     $where   附加SQL条件（谨慎使用）
     */
    public static function lists($catid = 0, $num = 10, $order = 'list', $modelId = 0, $flag = '', $where = '')
    {
        $query = Content::published();

        // 多维扩展字段筛选（GET f_字段标识=值，如 f_area=吉林&f_subject=语文；v3.8.10 多维联动筛选）
        self::applyFilterParams($query);

        if ($catid === 'current' || $catid === '') {
            $catid = self::currentCatId();
        }
        // 未指定条数（num<=0）时：取栏目「分页调取列表数目」（默认10）；标签写了 num 则以代码为准
        if ($num <= 0) {
            $num = 10;
            $cid = is_array($catid) ? reset($catid) : (strpos((string)$catid, ',') !== false ? (int)explode(',', (string)$catid)[0] : (int)$catid);
            if ($cid) {
                $__c = Category::find($cid);
                if ($__c && !empty($__c['pagesize'])) $num = (int)$__c['pagesize'];
            }
        }
        if ($catid) {
            $ids = [];
            foreach (explode(',', (string)$catid) as $c) {
                $ids = array_merge($ids, Category::getChildIds((int)$c));
            }
            $query->whereIn('catid', $ids);
        }
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        if ($flag) {
            foreach (explode(',', $flag) as $f) {
                switch (trim($f)) {
                    case 'top':      $query->where('is_top', 1); break;
                    case 'recommend': $query->where('is_recommend', 1); break;
                    case 'hot':      $query->where('is_hot', 1); break;
                }
            }
        }
        if ($where) {
            $query->whereRaw($where);
        }

        switch ($order) {
            case 'views':  $query->order('views', 'desc'); break;
            case 'update': $query->order('update_time', 'desc'); break;
            case 'time':   $query->order('create_time', 'desc'); break;
            case 'rand':   $query->orderRaw('RAND()'); break;
            case 'list':   $query->order('is_top', 'desc')->order('sort', 'asc')->order('create_time', 'desc'); break;
            default:
                // 多字段排序字符串（如 sort desc, create_time desc, id desc；v3.8.10）
                $sql = self::parseOrder($order);
                if ($sql) { $query->orderRaw($sql); break; }
                $query->order('is_top', 'desc')->order('sort', 'asc')->order('create_time', 'desc');
        }

        $rows = $query->limit($num)->select();
        // 商品价格注入（v3.14 商城演示）：有 yy_shop 记录的行注入 price/original_price/stock/sales；商城开关关闭时不注入（v3.16）
        $shopMap = [];
        if ((int)get_config('shop.open', 1) === 1) {
            try {
                $ids = array_column($rows->toArray(), 'id');
                if ($ids) {
                    $shops = \think\facade\Db::name('shop')->where('lang', lang_code())->whereIn('conid', $ids)->select();
                    foreach ($shops as $s) {
                        $shopMap[(int)$s['conid']] = $s;
                    }
                }
            } catch (\Throwable $e) {
                $shopMap = [];
            }
        }
        return $rows->each(function ($item) use ($shopMap) {
            $item = self::injectPeople($item);
            $s = $shopMap[(int)$item['id']] ?? null;
            if ($s) {
                $item['price']          = (float)$s['price'];
                $item['original_price'] = (float)$s['original_price'];
                $item['stock']          = (int)$s['stock'];
                $item['sales']          = (int)$s['sales'];
                $item['shop_status']    = (int)$s['status'];
            } else {
                $item['price'] = $item['original_price'] = 0;
                $item['stock'] = $item['sales'] = 0;
                $item['shop_status'] = 0;
            }
            return $item;
        });
    }

    /**
     * 解析多字段排序字符串（云优 orderby 兼容，v3.8.10）
     * 支持：orderby="sort desc , create_time desc , id desc" 任意组合与方向；
     * 字段白名单防注入，未知字段忽略；方向 asc/desc（缺省 asc）。
     * 字段映射：create_time→create_time（易云发布时间）、update_time→update_time、
     * click→views、listorder→sort、top/is_top/is_recommend/is_hot 原样。
     * 返回可直接用于 orderRaw 的 SQL；无效输入返回 ''（调用方自行兜底）。
     */
    public static function parseOrder($orderby)
    {
        if (!is_string($orderby) || trim($orderby) === '') return '';
        $map = [
            'id' => 'id', 'sort' => 'sort', 'listorder' => 'sort',
            'create_time' => 'create_time', 'create_time' => 'create_time',
            'update_time' => 'update_time', 'views' => 'views', 'click' => 'views',
            'top' => 'top', 'is_top' => 'is_top',
            'is_recommend' => 'is_recommend', 'is_hot' => 'is_hot',
        ];
        $parts = [];
        foreach (explode(',', $orderby) as $seg) {
            $seg = trim($seg);
            if ($seg === '') continue;
            if (strtolower($seg) === 'rand') { $parts[] = 'RAND()'; continue; }
            $bits = preg_split('/\s+/', $seg);
            $field = strtolower($bits[0] ?? '');
            $dir   = isset($bits[1]) ? strtolower($bits[1]) : 'asc';
            if ($dir !== 'asc' && $dir !== 'desc') $dir = 'asc';
            if (!isset($map[$field])) continue; // 白名单外忽略，防注入
            $parts[] = $map[$field] . ' ' . $dir;
        }
        return implode(', ', $parts);
    }

    /**
     * 多维扩展字段筛选：读取 GET f_字段标识=值（如 f_area=吉林&f_subject=语文&f_class=一班），     * 对内容模型自定义字段（extend JSON）做精确匹配筛选；返回选中值数组供模板高亮。
     * 字段标识白名单防注入，值走参数绑定；空串 extend 保护。多个维度可组合（多维联动筛选，v3.8.10）。
     * 用于 {cms:list} / {yunu:list} 标签（列表页/首页区块均可）与 Lists 控制器分页。
     */
    public static function applyFilterParams($query)
    {
        $filters = [];
        foreach (request()->get() as $k => $v) {
            if (strpos($k, 'f_') === 0 && $v !== '' && $v !== null) {
                $field = substr($k, 2);
                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
                    $val = (string)$v;
                    $query->whereRaw("extend <> '' AND JSON_CONTAINS(extend, ?, '$.{$field}')", [
                        json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ]);
                    $filters[$field] = $val;
                }
            }
        }
        return $filters;
    }

    /**
     * 注入发稿人/审核人（投稿审核机制，列表循环项可调用 author_name / audit_name）
     */
    protected static function injectPeople($row)
    {
        $people = content_people($row['author_uid'] ?? 0, $row['audit_uid'] ?? 0);
        $row['author_name'] = $people['author_name'];
        $row['audit_name']  = $people['audit_name'];
        // 审核状态：0正常 1待审核 2已驳回；提交次数；最新驳回原因（前台模板可调用）
        $row['audit_status']  = (int)($row['audit_status'] ?? 0);
        $row['submit_count']  = (int)($row['submit_count'] ?? 0);
        $row['reject_reason'] = (string)($row['reject_reason'] ?? '');
        return $row;
    }

    /** 当前栏目上下文（类静态属性，支持静态页生成器逐栏目重置） */
    protected static $curCatId = null;
    protected static $curChain = null;

    /**
     * 重置当前栏目上下文缓存（静态页生成器渲染每个栏目前调用）
     */
    public static function resetContext()
    {
        self::$curCatId = null;
        self::$curChain = null;
    }

    /**
     * 当前栏目ID（列表页 / 详情页自动识别；静态页生成时从 $GLOBALS['yy_cur_catid'] 注入）
     */
    public static function currentCatId()
    {
        if (self::$curCatId !== null) return self::$curCatId;
        self::$curCatId = 0;
        // 静态页生成上下文优先（StaticPage::render 注入）
        if (!empty($GLOBALS['yy_cur_catid'])) {
            self::$curCatId = (int)$GLOBALS['yy_cur_catid'];
            return self::$curCatId;
        }
        $req = request();
        $ctl = strtolower($req->controller());
        if ($ctl === 'lists') {
            self::$curCatId = (int)$req->param('id', $req->param('catid', 0));
        } elseif ($ctl === 'show') {
            $cid = (int)$req->param('id', 0);
            if ($cid) {
                $row = Content::where('status', 1)->where(function ($q) { $q->where('publish_time', 0)->whereOr('publish_time', '<=', time()); })->find($cid);
                self::$curCatId = $row ? (int)$row['catid'] : 0;
            }
        }
        return self::$curCatId;
    }

    /**
     * 当前栏目祖先链（当前 -> ... -> 顶级），用于导航选中高亮
     */
    public static function currentCatChain()
    {
        if (self::$curChain !== null) return self::$curChain;
        self::$curChain = [];
        $cur = self::currentCatId();
        while ($cur > 0) {
            self::$curChain[] = (int)$cur;
            $cat = Category::where('is_show', 1)->find($cur);
            if (!$cat || !(int)$cat['parent_id']) break;
            $cur = (int)$cat['parent_id'];
        }
        return self::$curChain;
    }

    /**
     * 单条栏目（{cms:category id="5"} / id="current" 当前栏目）
     */
    public static function categoryById($id)
    {
        $chain = self::currentCatChain();
        $curId = self::currentCatId();
        if ($id === 'parent') {
            $cur = Category::where('is_show', 1)->find($curId);
            $cat = ($cur && (int)$cur['parent_id']) ? Category::where('is_show', 1)->find((int)$cur['parent_id']) : null;
        } elseif ($id === 'current' || $id === '') {
            $cat = Category::where('is_show', 1)->find($curId);
        } else {
            $cat = Category::where('is_show', 1)->find((int)$id);
        }
        if (!$cat) return [];
        $cat->active = in_array((int)$cat['id'], $chain) ? 1 : 0;
        $cat->active_self = ((int)$cat['id'] === $curId) ? 1 : 0;
        return [$cat];
    }

    /**
     * 栏目列表（{cms:category}）
     * @param int|string $parentId 0顶级 / 指定ID / -1全部 / "current"当前栏目父级
     * @param int        $num      数量
     * @param string     $type     list/single/external
     * @param bool       $hasChild 是否加载子栏目（1层）
     * @param int        $deep     子栏目递归深度（haschild=1 等价 deep=1）
     * @param int        $top      1=取当前栏目的顶级栏目（配合 haschild/deep 输出其子级）
     * 每项附带 active（当前栏目或其祖先=1）/ active_self（仅当前栏目=1）
     */
    public static function categories($parentId = 0, $num = 20, $type = '', $hasChild = false, $deep = 0, $top = 0)
    {
        $chain = self::currentCatChain();
        $curId = self::currentCatId();
        $depth = $hasChild ? 1 : (int)$deep;
        if ($top) {
            // 顶级单条（当前栏目所在顶级），子级按 deep 递归
            $topId = $chain ? (int)end($chain) : 0;
            $topCat = $topId ? Category::where('is_show', 1)->find($topId) : null;
            if (!$topCat) return [];
            $list = [$topCat];
            $parentId = (int)$parentId; // 兼容：top 与 parent 互斥，top 优先
            if ($depth > 0) {
                $topCat->children = Category::where('parent_id', $topCat['id'])->where('is_show', 1)->order('sort', 'asc')->select();
                self::markChain($topCat->children, $chain, $curId);
                self::fillChildren($topCat->children, $depth - 1, $chain, $curId);
            }
            $topCat->active = in_array((int)$topCat['id'], $chain) ? 1 : 0;
            $topCat->active_self = ((int)$topCat['id'] === $curId) ? 1 : 0;
            return $list;
        }
        if ($parentId === 'current') {
            $cur = Category::where('is_show', 1)->find($curId);
            $parentId = $cur ? (int)$cur['parent_id'] : 0;
        }
        $parentId = (int)$parentId;
        $query = Category::where('is_show', 1);
        if ($parentId >= 0) {
            $query->where('parent_id', $parentId);
        }
        if ($type) {
            $query->where('type', $type);
        }
        $query->order('sort', 'asc');
        $list = $query->limit($num)->select();
        foreach ($list as &$cat) {
            $cid = (int)$cat['id'];
            $cat->active = in_array($cid, $chain) ? 1 : 0;
            $cat->active_self = ($cid === $curId) ? 1 : 0;
            if ($depth > 0) {
                $cat->children = Category::where('parent_id', $cid)->where('is_show', 1)->order('sort', 'asc')->select();
                self::markChain($cat->children, $chain, $curId);
                self::fillChildren($cat->children, $depth - 1, $chain, $curId);
            }
        }
        return $list;
    }

    /**
     * 递归填充子栏目并标记选中链
     */
    protected static function fillChildren($list, $depth, $chain, $curId)
    {
        if ($depth <= 0) return;
        foreach ($list as $c) {
            $c->children = Category::where('parent_id', $c['id'])->where('is_show', 1)->order('sort', 'asc')->select();
            self::markChain($c->children, $chain, $curId);
            self::fillChildren($c->children, $depth - 1, $chain, $curId);
        }
    }

    /**
     * 为栏目集合标记选中状态
     */
    protected static function markChain($list, $chain, $curId)
    {
        foreach ($list as $c) {
            $c->active = in_array((int)$c['id'], $chain) ? 1 : 0;
            $c->active_self = ((int)$c['id'] === $curId) ? 1 : 0;
        }
    }

    /**
     * 导航栏目（顶级 + 子栏目）
     */
    public static function nav()
    {
        $chain = self::currentCatChain();
        $curId = self::currentCatId();
        $top = Category::where('is_show', 1)->where('is_nav', 1)->where('parent_id', 0)->order('sort', 'asc')->select();
        foreach ($top as &$cat) {
            $cat->active = in_array((int)$cat['id'], $chain) ? 1 : 0;
            $cat->active_self = ((int)$cat['id'] === $curId) ? 1 : 0;
            $cat->children = Category::where('parent_id', $cat->id)->where('is_show', 1)->order('sort', 'asc')->select();
            self::markChain($cat->children, $chain, $curId);
        }
        return $top;
    }

    /**
     * 自定义块（{cms:block}）
     */
    public static function block($name, $type = '')
    {
        $b = Block::getByName($name);
        if (!$b) return '';
        if ($b['type'] === 'image') {
            $url = (string)$b['image'];
            if ($type === 'url') return $url;
            return '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars((string)$b['title']) . '">';
        }
        return attach_download_rewrite($b['content'] ?? '');
    }

    /**
     * 广告位（{cms:ad}）
     */
    public static function ad($name)
    {
        return Ad::getByName($name);
    }

    /**
     * 友情链接（{cms:link}）
     */
    public static function links($num = 20)
    {
        return Link::where('status', 1)->order('sort', 'asc')->limit($num)->select();
    }

    /**
     * 面包屑（{cms:crumbs}）返回祖先栏目数组
     */
    public static function crumbs($catid)
    {
        return Category::getParents($catid);
    }

    /**
     * 上一篇/下一篇（{cms:prevnext}）
     */
    public static function prevnext($id, $catid)
    {
        $ids  = Category::getChildIds($catid ?: 0);
        $prev = Content::published()->where('id', '<', $id);
        $next = Content::published()->where('id', '>', $id);
        if ($catid) {
            $prev->whereIn('catid', $ids);
            $next->whereIn('catid', $ids);
        }
        return [
            'prev' => $prev->order('id', 'desc')->find(),
            'next' => $next->order('id', 'asc')->find(),
        ];
    }

    /**
     * 内容详情（{cms:content id=""}）
     */
    public static function content($id)
    {
        $row = Content::where('id', $id)->where('status', 1)->find();
        if ($row) {
            $row['content']     = attach_download_rewrite($row['content']);
            $row['description'] = attach_download_rewrite($row['description']);
            // 发稿人/审核人（投稿审核机制，模板可调用 author_name / audit_name）
            $people = content_people($row['author_uid'] ?? 0, $row['audit_uid'] ?? 0);
            $row['author_name'] = $people['author_name'];
            $row['audit_name']  = $people['audit_name'];
            $row['author_uid']  = (int)($row['author_uid'] ?? 0);
            $row['audit_uid']   = (int)($row['audit_uid'] ?? 0);
            $row['audit_time']  = (int)($row['audit_time'] ?? 0);
            $row['audit_status']  = (int)($row['audit_status'] ?? 0);
            $row['submit_count']  = (int)($row['submit_count'] ?? 0);
            $row['reject_reason'] = (string)($row['reject_reason'] ?? '');
        }
        return $row;
    }

    /**
     * 浏览量排行（{cms:hot num=""}）
     */
    public static function hot($num = 10, $catid = 0)
    {
        $query = Content::published()->order('views', 'desc')->limit($num);
        if ($catid) {
            $query->whereIn('catid', Category::getChildIds($catid));
        }
        return $query->select()->each(function ($item) {
            return self::injectPeople($item);
        });
    }

    /**
     * SEO 标题拼接
     */
    public static function pageTitle($seoTitle = '', $catName = '', $siteName = '')
    {
        $site = $siteName ?: get_setting('seo.title', get_setting('site.name', ''));
        if ($seoTitle) return $seoTitle;
        if ($catName)  return $catName . ' - ' . $site;
        return $site;
    }

    /**
     * 栏目自定义字段值（{cms:catfield name=""}）
     * 读取当前栏目（模板中 $category）的 extend 自定义字段
     */
    public static function catfield($catid, $name, $default = '', $type = '')
    {
        if (!$catid || !$name) return $default;
        $cat = \app\common\model\Category::find($catid);
        if (!$cat) return $default;
        $extend = json_decode((string)$cat['extend'], true);
        if (!is_array($extend) || !isset($extend[$name])) {
            return $default;
        }
        $f = $extend[$name];
        $v = isset($f['value']) ? $f['value'] : $default;
        // 图片字段：type="img" 输出带 img 标签，默认输出地址
        if ($type === 'img' && $v !== '' && (($f['type'] ?? '') === 'image')) {
            return '<img src="' . htmlspecialchars($v) . '" alt="">';
        }
        return $v;
    }
}
