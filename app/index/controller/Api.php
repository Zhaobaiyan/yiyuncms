<?php
// +----------------------------------------------------------------------
// | 前台轻量 API 控制器（v3.10）
// | 1) 匿名收藏（基于浏览器 cookie 标识，无需登录）+ 收藏数量
// | 2) 公开内容 API：GET /api/content?catid=&limit=&order=  内容列表 JSON
// |    GET /api/content?id=N                         内容详情 JSON
// | 返回统一 {code:1, data:...}
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\Category;
use app\common\model\Content;
use app\common\model\Favorite;
use think\facade\Cookie;
use think\facade\Db;

class Api extends FrontBase
{
    /**
     * 收藏 / 取消收藏：POST /api/favorite  body: id=内容ID
     */
    public function favorite()
    {
        if (!request()->isPost()) return json(['code' => 0, 'msg' => '非法请求']);
        // 收藏开关（v3.16）：关闭则拒绝
        if ((int)get_config('favorite.open', 1) !== 1) {
            return json(['code' => 0, 'msg' => '收藏功能已关闭']);
        }
        $id = (int)input('post.id', 0);
        $content = Content::find($id);
        if (!$content || $content['status'] != 1) {
            return json(['code' => 0, 'msg' => '内容不存在或未公开']);
        }
        // 已登录会员用会员ID，未登录用匿名标识（均存 member_id 字段）
        $uid = session('member_id') ? (string)(int)session('member_id') : $this->anonUid();
        $fav = Favorite::toggle($uid, $id);
        return json([
            'code' => 1,
            'fav'  => $fav,
            'msg'  => $fav ? '收藏成功' : '已取消收藏',
            'count'=> Favorite::where('content_id', $id)->count(),
        ]);
    }

    /**
     * 收藏数量：GET /api/favorite_count?id=N
     */
    public function favoriteCount()
    {
        $id = (int)input('get.id', 0);
        // 收藏开关关闭时数量恒 0（v3.16）
        if ((int)get_config('favorite.open', 1) !== 1) {
            return json(['code' => 1, 'count' => 0]);
        }
        return json([
            'code'  => 1,
            'count' => Favorite::where('content_id', $id)->count(),
        ]);
    }

    /**
     * 内容列表 API：GET /api/content?catid=&limit=&order=id desc&keyword=
     */
    public function content()
    {
        $catid   = (int)input('get.catid', 0);
        $limit   = min(50, max(1, (int)input('get.limit', 10)));
        $order   = trim((string)input('get.order', 'id desc'));
        $keyword = trim((string)input('get.keyword', ''));
        if (!preg_match('/^[a-z_]+ (asc|desc)$/i', $order)) $order = 'id desc';

        $query = Content::published();
        if ($catid > 0) {
            $ids = [$catid];
            foreach (Category::getChildIds($catid) as $cid) $ids[] = $cid;
            $query->whereIn('catid', $ids);
        }
        if ($keyword !== '') {
            $query->where('title', 'like', '%' . $keyword . '%');
        }
        $list = $query->order($order)->limit($limit)->select()->toArray();
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                'id'      => $row['id'],
                'title'   => $row['title'],
                'catid'   => $row['catid'],
                'thumb'   => $row['thumb'],
                'desc'    => strip_tags((string)($row['description'] ?? '')),
                'views'   => $row['views'],
                'create_time' => $row['create_time'],
                'url'     => url('show/index', ['id' => $row['id']])->build(),
            ];
        }
        return json(['code' => 1, 'data' => $data]);
    }

    /**
     * 内容详情 API：GET /api/content_detail?id=N
     */
    public function contentDetail()
    {
        $id = (int)input('get.id', 0);
        $row = Content::published()->where('id', $id)->find();
        if (!$row) return json(['code' => 0, 'msg' => '内容不存在或未公开']);
        return json([
            'code' => 1,
            'data' => [
                'id'      => $row['id'],
                'title'   => $row['title'],
                'catid'   => $row['catid'],
                'thumb'   => $row['thumb'],
                'desc'    => strip_tags((string)($row['description'] ?? '')),
                'content' => (string)$row['content'],
                'views'   => $row['views'],
                'create_time' => $row['create_time'],
                'url'     => url('show/index', ['id' => $row['id']])->build(),
            ],
        ]);
    }

    /**
     * 下划线 URL 别名（v3.10）：/api/content_detail、/api/favorite_count
     * 当前环境自动路由按 URL 原样匹配方法名，故提供下划线方法转发驼峰方法
     */
    public function content_detail()
    {
        return $this->contentDetail();
    }


    /**
     * 搜索 API：kw 关键词，catid 栏目（含子栏目），field 0=标题 1=正文 2=标题+正文，page/limit
     * GET /api/search?kw=xx&catid=9&field=2&page=1&limit=10
     */
    public function search()
    {
        $kw    = trim((string)input('get.kw', ''));
        $catid = (int)input('get.catid', 0);
        $field = (int)input('get.field', 2);
        $page  = max(1, (int)input('get.page', 1));
        $limit = max(1, min(100, (int)input('get.limit', 10)));
        if ($kw === '') {
            return json(['code' => 0, 'msg' => '关键词不能为空', 'data' => null]);
        }
        $q = Content::published();
        if ($catid > 0) {
            $ids = Category::getChildIds($catid);
            $q->whereIn('catid', $ids);
        }
        if ($field === 0) {
            $q->where('title', 'like', '%' . $kw . '%');
        } elseif ($field === 1) {
            $q->where('content', 'like', '%' . $kw . '%');
        } else {
            $q->where(function ($qq) use ($kw) {
                $qq->where('title', 'like', '%' . $kw . '%')->whereOr('content', 'like', '%' . $kw . '%');
            });
        }
        $total = (clone $q)->count();
        $rows  = $q->order('id', 'desc')->page($page, $limit)->select();
        return json([
            'code' => 1,
            'msg'  => 'ok',
            'data' => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'list'  => $rows,
            ],
        ]);
    }

    public function favorite_count()
    {
        return $this->favoriteCount();
    }

    /* ==================== v3.26 REST API 扩展（只读端点） ==================== */

    /**
     * 栏目列表 API：GET /api/category_list?pid=0&is_nav=1&limit=50
     * 返回当前语言栏目；pid=-1 时返回全部分层（含子栏目），否则仅返回该层
     */
    public function categoryList()
    {
        $lang  = lang_code();
        $pid   = (int)input('get.pid', 0);
        $isNav = (int)input('get.is_nav', 1);
        $limit = min(100, max(1, (int)input('get.limit', 50)));
        $tbl = preg_match('/^[a-z]{2,5}$/', $lang) ? $lang . '_category' : 'cn_category';
        $q = Db::name($tbl)->where('delete_time', 0);
        if ($pid >= 0) $q->where('parent_id', $pid);
        if ($isNav) $q->where('is_nav', 1);
        $rows = $q->where('is_show', 1)->order('sort', 'asc')->order('id', 'asc')->limit($limit)->select()->toArray();
        $data = [];
        foreach ($rows as $c) {
            $data[] = [
                'id'        => (int)$c['id'],
                'parent_id' => (int)$c['parent_id'],
                'name'      => $c['name'],
                'ename'     => $c['ename'],
                'type'      => $c['type'],
                'is_nav'    => (int)$c['is_nav'],
                'url'       => $c['external_url'] ?: (string)url('lists/index', ['catid' => $c['id']])->build(),
            ];
        }
        return json(['code' => 1, 'data' => $data]);
    }

    /**
     * 商城商品 API：GET /api/shop_list?catid=&limit=&order=id desc
     * 仅返回上架商品，含价格/库存/销量/是否有规格
     */
    public function shopList()
    {
        $lang  = lang_code();
        $catid = (int)input('get.catid', 0);
        $limit = min(50, max(1, (int)input('get.limit', 10)));
        $order = trim((string)input('get.order', 'id desc'));
        if (!preg_match('/^[a-z_]+ (asc|desc)$/i', $order)) $order = 'id desc';
        $tbl = preg_match('/^[a-z]{2,5}$/', $lang) ? $lang . '_content' : 'cn_content';
        $q = Db::name('shop')->alias('s')
            ->field('s.id sid,s.price,s.original_price,s.stock,s.sales,s.specs,c.id conid,c.title,c.thumb,c.catid')
            ->join($tbl . ' c', 'c.id = s.conid')
            ->where('s.lang', $lang)->where('s.status', 1)
            ->where('c.delete_time', 0)->where('c.status', 1);
        if ($catid > 0) {
            $ids = [$catid];
            foreach (Category::getChildIds($catid) as $cid) $ids[] = $cid;
            $q->whereIn('c.catid', $ids);
        }
        $rows = $q->order('s.' . $order)->limit($limit)->select()->toArray();
        $data = [];
        foreach ($rows as $r) {
            $sku = json_decode((string)$r['specs'], true);
            $data[] = [
                'conid'          => (int)$r['conid'],
                'title'          => $r['title'],
                'thumb'          => $r['thumb'],
                'catid'          => (int)$r['catid'],
                'price'          => (float)$r['price'],
                'original_price' => (float)$r['original_price'],
                'stock'          => (int)$r['stock'],
                'sales'          => (int)$r['sales'],
                'has_spec'       => is_array($sku) && !empty($sku['_sku']),
                'url'            => (string)url('show/index', ['id' => $r['conid']])->build(),
            ];
        }
        return json(['code' => 1, 'data' => $data]);
    }

    /**
     * 当前会员信息 API：GET /api/member_info（需登录，返回脱敏资料）
     */
    public function memberInfo()
    {
        $m = session('member');
        if (!$m) return json(['code' => 0, 'msg' => '未登录']);
        return json(['code' => 1, 'data' => [
            'id'       => (int)$m['id'],
            'username' => (string)($m['username'] ?? ''),
            'nickname' => (string)($m['nickname'] ?? ''),
            'email'    => (string)($m['email'] ?? ''),
            'mobile'   => (string)($m['mobile'] ?? ''),
        ]]);
    }

    /**
     * 当前会员收藏列表 API：GET /api/favorite_list?limit=20（需登录）
     */
    public function favoriteList()
    {
        if (!session('member_id')) return json(['code' => 0, 'msg' => '未登录']);
        if ((int)get_config('favorite.open', 1) !== 1) return json(['code' => 1, 'data' => []]);
        $uid   = (int)session('member_id');
        $limit = min(50, max(1, (int)input('get.limit', 20)));
        $lang  = lang_code();
        $tbl = preg_match('/^[a-z]{2,5}$/', $lang) ? $lang . '_content' : 'cn_content';
        $favs = Favorite::where('member_id', (string)$uid)->order('id', 'desc')->limit($limit)->select()->toArray();
        $data = [];
        if ($favs) {
            $ids = array_map('intval', array_column($favs, 'content_id'));
            $cMap = [];
            foreach (Db::name($tbl)->whereIn('id', $ids)->where('delete_time', 0)->where('status', 1)->select()->toArray() as $c) {
                $cMap[$c['id']] = $c;
            }
            foreach ($favs as $f) {
                $c = $cMap[(int)$f['content_id']] ?? null;
                if (!$c) continue;
                $data[] = [
                    'conid' => (int)$c['id'],
                    'title' => $c['title'],
                    'thumb' => $c['thumb'],
                    'catid' => (int)$c['catid'],
                    'url'   => (string)url('show/index', ['id' => $c['id']])->build(),
                ];
            }
        }
        return json(['code' => 1, 'data' => $data]);
    }

    public function category_list()
    {
        return $this->categoryList();
    }

    public function shop_list()
    {
        return $this->shopList();
    }

    public function member_info()
    {
        return $this->memberInfo();
    }

    public function favorite_list()
    {
        return $this->favoriteList();
    }

    /**
     * 匿名用户标识：cookie 持久化
     */
    protected function anonUid()
    {
        $key = 'cms_fav_uid';
        $uid = (string)Cookie::get($key);
        if ($uid === '') {
            $uid = md5(request()->ip() . '|' . microtime(true) . '|' . uniqid('', true));
            Cookie::set($key, $uid, 86400 * 365);
        }
        return $uid;
    }
}
