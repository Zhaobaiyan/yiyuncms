<?php
// +----------------------------------------------------------------------
// | 云优标签兼容数据层
// | 提供 {yunu:xxx} 标签所需数据，把易云CMS数据映射为云优风格变量
// | （title/etitle/pic/istop/top/click/ftitle/desc/piclist/url 等）
// +----------------------------------------------------------------------
namespace app\common\service;

use app\common\model\Category as CategoryModel;
use app\common\model\Content as ContentModel;
use app\common\model\Block;
use app\common\model\Ad;
use app\common\model\Link;

class YunuService
{
    /**
     * 站点配置（当前语言 setting 优先，全局 config 兜底）
     */
    public static function setting(string $name)
    {
        // 模板下划线写法 → 设置表点键 别名映射（云优模板兼容）
        $alias = [
            'site_title' => 'site.name', 'site_logo' => 'site.logo', 'site_ico' => 'site.ico',
            'site_keywords' => 'site.keywords', 'site_description' => 'site.description',
            'site_url' => 'site.url', 'site_icp' => 'site.icp', 'site_copyright' => 'site.copyright',
            'seo_title' => 'seo.title', 'seo_keywords' => 'seo.keywords', 'seo_description' => 'seo.description',
        ];
        if (isset($alias[$name])) $name = $alias[$name];
        $v = get_setting($name, null);
        if ($v !== null) return $v;
        return get_config($name, '');
    }

    /**
     * 栏目树（云优 nav/catlist 通用）：映射为云优变量
     */
    protected static function mapCategory($cat)
    {
        if (!$cat) return null;
        $c = is_object($cat) ? $cat->toArray() : (array)$cat;
        return [
            'id'       => $c['id'],
            'title'    => $c['name'],
            'etitle'   => $c['ename'],
            'pid'      => $c['parent_id'],
            'subtitle' => isset($c['subtitle']) ? $c['subtitle'] : '',
            'pic'      => !empty($c['image']) ? $c['image'] : '',
            'desc'     => isset($c['description']) ? $c['description'] : '',
            'content'  => isset($c['content']) ? $c['content'] : '',
            'cover'    => isset($c['cover']) ? $c['cover'] : 0,
            'target'   => isset($c['target']) ? $c['target'] : 0,
            'type'     => $c['type'],
            'url'      => url_cat($c),
            'ys_title' => $c['name'],
            'children' => [],
            // 云优模板兼容：{$catlist.child} 子栏目数组（云优键名为 child）
            'child'    => [],
        ];
    }

    /**
     * 导航（顶级 + 子级，云优 {yunu:nav}）
     */
    public static function nav(int $typeid, string $limit)
    {
        $tops = CategoryModel::where('is_nav', 1)->where('parent_id', 0)->order('sort', 'asc')->order('id', 'asc')->select();
        $tree = [];
        foreach ($tops as $top) {
            $node = self::mapCategory($top);
            $subs = CategoryModel::where('parent_id', $top['id'])->order('sort', 'asc')->order('id', 'asc')->select();
            foreach ($subs as $sub) {
                $node['children'][] = self::mapCategory($sub);
            }
            $node['child'] = $node['children']; // 云优兼容键
            $tree[] = $node;
        }
        return self::slice($tree, $limit);
    }

    /**
     * 栏目列表（云优 {yunu:catlist}）
     * @param string $type  son下级 / self同级 / top顶级 / parent父级的子栏目（侧边栏导航用）
     * @param string $orderby 白名单：sort asc|sort desc|id asc|id desc
     */
    public static function catlist($cid, string $type, string $limit, int $flag, string $orderby = '')
    {
        $cid   = (int)$cid;
        // 注意：必须用静态链承接 Query（new Model() 后分开调用 where/select 会丢失条件）
        $query = CategoryModel::where('is_show', 1);
        if ($type === 'top' || $cid <= 0) {
            $query->where('parent_id', 0);
        } elseif ($type === 'son') {
            $query->where('parent_id', $cid);
        } elseif ($type === 'self') {
            $row = CategoryModel::find($cid);
            if ($row) {
                $query->where('parent_id', $row['parent_id']);
            }
        } elseif ($type === 'parent') {
            // 云优语义：当前栏目所在父级下的子栏目列表（当前是顶级则列出自身子栏目）
            $row = CategoryModel::find($cid);
            if ($row) {
                $query->where('parent_id', $row['parent_id'] ?: $cid);
            }
        }
        if (!$flag) {
            // 云优 flag=0 时过滤单页/隐藏
            $query->where('type', '<>', 'single');
        }
        // 云优语义：getCatlist 固定 orderRaw('sort asc')，模板的 orderby 参数为死参数
        $query->orderRaw('sort asc, id asc');
        $list = $query->select()->toArray();
        $mapped = array_map([__CLASS__, 'mapCategory'], $list);
        // 每条补一级子栏目（云优 {$catlist.child}）
        foreach ($mapped as &$node) {
            $subs = CategoryModel::where('is_show', 1)->where('parent_id', $node['id'])->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
            $node['children'] = array_map([__CLASS__, 'mapCategory'], $subs);
            $node['child']    = $node['children']; // 云优兼容键
        }
        return self::slice($mapped, $limit);
    }

    /**
     * 单栏目（云优 {yunu:type}）
     * @param string $type  ''取自身 / parent取父栏目（无父返回自身）
     */
    public static function oneCategory($typeid, string $type = '')
    {
        $typeid = (int) $typeid;
        if (!$typeid) return null;
        $row = CategoryModel::find($typeid);
        if (!$row) return null;
        if ($type === 'parent' && $row['parent_id']) {
            $parent = CategoryModel::find($row['parent_id']);
            if ($parent) return self::mapCategory($parent);
        }
        return self::mapCategory($row);
    }

    /**
     * 内容映射为云优变量
     */
    protected static function mapContent($con)
    {
        if (!$con) return null;
        $c = is_object($con) ? $con->toArray() : (array)$con;
        $extend = json_decode((string)($c['extend'] ?? ''), true);
        $_piclist = is_array($extend) && !empty($extend['piclist']) ? (array)$extend['piclist'] : [];
        if (!$_piclist && !empty($c['image'])) {
            $_piclist = array_values(array_filter(array_map('trim', explode('***', (string)$c['image']))));
            if (count($_piclist) === 1 && strpos((string)$c['image'], ',') !== false) {
                $_piclist = array_values(array_filter(array_map('trim', explode(',', (string)$c['image']))));
            }
        }
        return [
            'id'          => $c['id'],
            'cid'         => $c['catid'],
            'title'       => $c['title'],
            'ys_title'    => $c['title'],
            'etitle'      => isset($c['etitle']) ? $c['etitle'] : '',
            'ftitle'      => isset($c['short_title']) ? $c['short_title'] : '',
            'pic'         => !empty($c['thumb']) ? $c['thumb'] : '',
            'piclist'     => $_piclist ? implode('***', $_piclist) : '',
            'image'       => !empty($c['thumb']) ? $c['thumb'] : (is_array($extend) && !empty($extend['piclist']) ? (string)(is_array($extend['piclist']) ? reset($extend['piclist']) : $extend['piclist']) : ''),
            'istop'       => isset($c['is_recommend']) ? $c['is_recommend'] : 0,
            'top'         => isset($c['top']) ? $c['top'] : 0,
            'click'       => isset($c['views']) ? $c['views'] : 0,
            'author'      => isset($c['author']) ? $c['author'] : '',
            'desc'        => isset($c['description']) ? $c['description'] : '',
            'content'     => isset($c['content']) ? $c['content'] : '',
            'tag'         => isset($c['tag']) ? $c['tag'] : '',
            'source'      => isset($c['source']) ? $c['source'] : '',
            'create_time' => !empty($c['create_time']) ? $c['create_time'] : 0,
            'publish_time' => !empty($c['create_time']) ? $c['create_time'] : 0,
            'update_time' => $c['update_time'] ?? 0,
            'url'         => url_content($c),
            'keywords'    => isset($c['keywords']) ? $c['keywords'] : '',
        ];
    }

    /**
     * 内容列表（云优 {yunu:list}）
     * @param int   $pagesize >0 时输出分页
     */
    public static function contentList($cid, string $limit, int $pagesize, string $flag, int $top, string $image, string $orderby, string $keyword, int $titlelen, string $tag = '', bool $needPage = true)
    {
        $query = ContentModel::published();
        // 多维扩展字段筛选（GET f_字段标识=值，与 {cms:list}/Lists 控制器一致；v3.8.10 多维联动筛选）
        TagService::applyFilterParams($query);
        // 栏目（支持逗号分隔）
        if (strpos((string)$cid, ',')) {
            $ids = array_filter(array_map('intval', explode(',', (string)$cid)));
            if ($ids) $query->whereIn('catid', $ids);
        } elseif ($cid > 0) {
            // 云优行为：cid 递归包含全部后代栏目内容（如产品中心含三级栏目内容）
            $ids = CategoryModel::getChildIds($cid);
            $query->whereIn('catid', $ids);
        }
        if ($flag === '1') {
            $query->where('is_recommend', 1);
        }
        if ($top > 0) {
            $query->where('top', $top);
        }
        if ($image === '1') {
            $query->where('thumb', '<>', '');
        } elseif ($image === '0') {
            $query->where('thumb', '');
        }
        if ($keyword !== '') {
            $query->where('title', 'like', '%' . $keyword . '%');
        }
        if ($tag !== '') {
            // 标签聚合：tag 字段逗号/顿号/空格分隔，模糊匹配
            $query->where('tag', 'like', '%' . $tag . '%');
        }
        // orderby 多字段排序（v3.8.10）：支持云优同款 "sort desc , create_time desc , id desc"
        // 字段白名单/方向解析由 TagService::parseOrder 完成；无效输入兜底 id desc
        $orderby = $orderby ?: 'id desc';
        $orderSql = TagService::parseOrder($orderby);
        $query->orderRaw($orderSql ?: 'id desc');

        // 静态构建（build:static）时：分页页号与静态分页路径由 StaticPage 注入全局变量
        $page = 1;
        if (defined('YY_STATIC_BUILD') && YY_STATIC_BUILD) {
            $page = max(1, (int)($GLOBALS['yy_static_page'] ?? 1));
            $staticPath = (string)($GLOBALS['yy_static_path'] ?? '');
            $list = $pagesize > 0 ? $query->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $staticPath]) : $query->limit((int)$limit ?: 10)->select();
        } else {
            $list = $pagesize > 0 ? $query->paginate($pagesize) : $query->limit((int)$limit ?: 10)->select();
        }
        $rows = $pagesize > 0 ? $list->items() : $list->toArray();
        $mapped = array_map([__CLASS__, 'mapContent'], $rows);
        if ($titlelen > 0) {
            foreach ($mapped as &$m) {
                $m['title'] = mb_substr(strip_tags($m['title']), 0, $titlelen, 'utf-8');
            }
        }
        return ['list' => $mapped, 'page' => $pagesize > 0 ? $list->render() : ''];
    }

    /**
     * 单条内容 / 上下条（云优 {yunu:info}）
     */
    public static function oneContent(int $conid, string $type, string $orderby)
    {
        $row = ContentModel::where('id', $conid)->where('status', 1)->where(function ($q) { $q->where('publish_time', 0)->whereOr('publish_time', '<=', time()); })->find();
        if (!$row) return null;
        if ($type === 'prev' || $type === 'next') {
            $order = $orderby ?: 'id desc';
            $q = ContentModel::published()->where('catid', $row['catid']);
            if ($type === 'prev') {
                $q->where('id', '<', $row['id'])->order('id', 'desc');
            } else {
                $q->where('id', '>', $row['id'])->order('id', 'asc');
            }
            $p = $q->find();
            return $p ? self::mapContent($p) : null;
        }
        return self::mapContent($row);
    }

    /**
     * 自定义块（云优 {yunu:block}）
     */
    public static function block(string $name, int $infolen)
    {
        $row = Block::where('name', $name)->find();
        if (!$row) return '';
        $content = $row['content'] ?: '';
        // 图片类型块：内容为空时输出带 img 标签的图片（前台 `{yunu:block name="x"}` 直接展示）
        if ($content === '' && $row['type'] === 'image' && $row['image']) {
            $content = '<img src="' . $row['image'] . '" alt="' . $row['title'] . '">';
        }
        if ($infolen > 0) {
            $content = mb_substr(strip_tags($content), 0, $infolen, 'utf-8');
        }
        return $content;
    }

    /**
     * 广告（云优 {yunu:banner}）
     */
    public static function banner(string $type, string $limit)
    {
        $q = Ad::where('status', 1);
        if ($type !== '') {
            // 云优数字 type（1=PC组 2=WAP组）→ 导入时 name 为 yunu_1 / yunu_2
            $q->where('name', is_numeric($type) ? 'yunu_' . $type : $type);
        }
        $rows = $q->order('sort', 'asc')->order('id', 'asc')->select();
        $out = [];
        foreach ($rows as $r) {
            $a = $r->toArray();
            // 云优 banner 需要 pic/url；易云 Ad 目前用 content 存图片地址
            $pic = !empty($a['image']) ? $a['image'] : $a['content'];
            $out[] = [
                'id' => $a['id'], 'title' => $a['title'], 'pic' => $pic,
                'url' => $a['url'] ?? '', 'type' => $type, 'ys_title' => $a['title'],
            ];
        }
        return self::slice($out, $limit);
    }

    /**
     * 友情链接（云优 {yunu:link}）
     */
    public static function links(string $type, string $limit)
    {
        // 易云 yy_cn_link 无 type 列：云优 type=1 即全部显示，忽略分类过滤
        $rows = Link::order('sort', 'asc')->order('id', 'asc')->select();
        $out = [];
        foreach ($rows as $r) {
            $a = $r->toArray();
            $out[] = ['id' => $a['id'], 'title' => $a['name'], 'url' => $a['url'], 'pic' => $a['logo'] ?: '', 'type' => '1'];
        }
        return self::slice($out, $limit);
    }

    /**
     * 面包屑（云优 {yunu:position}）
     */
    public static function position($cid, string $sname, string $surl, string $delimiter)
    {
        $dl = $delimiter !== '' ? $delimiter : ' &gt;&gt; ';
        $arr = [['name' => '首页', 'url' => url_home()]];
        $cid = (int)$cid;
        if ($cid <= 0) { // 无栏目上下文（静态生成首页等）时仅输出首页
            $html = $arr[0]['name'];
            if ($sname !== '') $html .= $dl . $sname;
            return $html;
        }
        $cur = CategoryModel::find($cid);
        $chain = [];
        $seen = [];
        while ($cur && !isset($seen[$cur['id']])) {
            $seen[$cur['id']] = 1;
            $chain[] = $cur;
            $cur = $cur['parent_id'] ? CategoryModel::find($cur['parent_id']) : null;
        }
        foreach (array_reverse($chain) as $c) {
            $arr[] = ['name' => $c['name'], 'url' => url_cat($c)];
        }
        if ($sname !== '') {
            $arr[] = ['name' => $sname, 'url' => $surl];
        }
        $html = '';
        foreach ($arr as $i => $a) {
            if ($i > 0) $html .= $dl;
            $html .= '<a href="' . $a['url'] . '">' . $a['name'] . '</a>';
        }
        return $html;
    }

    /**
     * limit 截断（支持 "1,5" 区间 或 纯数字）
     */
    protected static function slice(array $list, string $limit): array
    {
        $limit = trim($limit);
        if ($limit === '' || $limit === '0') return $list;
        if (strpos($limit, ',')) {
            $p = explode(',', $limit);
            return array_slice($list, (int)$p[0], isset($p[1]) ? (int)$p[1] - (int)$p[0] + 1 : null);
        }
        return array_slice($list, 0, (int)$limit);
    }
}
