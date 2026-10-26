<?php
// +----------------------------------------------------------------------
// | 云优标签兼容库
// | 模板中通过 {yunu:xxx} 调用（与云优CMS标签规则一致），数据走 YunuService
// | 已实现：config/nav/catlist/type/list/info/block/banner/link/position/url
// +----------------------------------------------------------------------
namespace app\common\taglib;

use app\common\service\YunuService;
use think\template\TagLib;

class Yunu extends TagLib
{
    protected $tags = [
        'config'    => ['attr' => 'name', 'close' => 0],
        'url'       => ['attr' => 'name,id', 'close' => 0],
        'position'  => ['attr' => 'cid,sname,surl,delimiter', 'close' => 0],
        'nav'       => ['attr' => 'typeid,limit', 'close' => 1],
        'catlist'   => ['attr' => 'cid,type,limit,flag,orderby', 'close' => 1],
        'type'      => ['attr' => 'typeid,type', 'close' => 1],
        'list'      => ['attr' => 'cid,titlelen,orderby,keyword,tag,limit,pagesize,flag,top,image,pagecss', 'close' => 1],
        'info'      => ['attr' => 'conid,type,orderby', 'close' => 1],
        'block'     => ['attr' => 'name,infolen,textflag', 'close' => 0],
        'banner'    => ['attr' => 'type,limit,orderby', 'close' => 1],
        'link'      => ['attr' => 'type,flag,limit,orderby', 'close' => 1],
        'tag'       => ['attr' => 'title,limit', 'close' => 1],
        'cwkeywords'=> ['attr' => '', 'close' => 0],
    ];

    protected function expr($value, $number = false)
    {
        $value = trim($value);
        if ($value === '') return $number ? '0' : "''";
        if ($number) {
            if (preg_match('/^-?\d+$/', $value)) return $value;
            return $this->varExpr($value);
        }
        if (strpos($value, '{$') !== false || (isset($value[0]) && $value[0] === '$')) return $this->varExpr($value);
        return "'" . addcslashes($value, "'\\") . "'";
    }

    protected function varExpr($value)
    {
        $this->tpl->parseVar($value);
        $this->tpl->parseVarFunction($value, false);
        return $value;
    }

    /** {yunu:config name="site.name" /} */
    public function tagConfig($tag)
    {
        $name = isset($tag['name']) ? $this->expr($tag['name']) : "''";
        return '<?php echo \app\common\service\YunuService::setting(' . $name . ') ?? ""; ?>';
    }

    /** {yunu:url name="home" /} */
    public function tagUrl($tag)
    {
        $name = isset($tag['name']) ? trim($tag['name']) : 'home';
        switch ($name) {
            case 'home': $c = 'url_home()'; break;
            case 'homepage': $c = 'url_homepage()'; break;
            case 'search': $c = 'site_url("search", "index")'; break;
            case 'captcha': $c = 'site_url("message", "captcha")'; break;
            case 'form': $c = 'site_url("message", "index")'; break;
            default: $c = 'url_home()';
        }
        return '<?php echo ' . $c . '; ?>';
    }

    /** {yunu:position cid="2" /} */
    public function tagPosition($tag)
    {
        $cid       = isset($tag['cid']) ? $this->expr($tag['cid'], true) : '0';
        $sname     = isset($tag['sname']) ? $this->expr($tag['sname']) : "''";
        $surl      = isset($tag['surl']) ? $this->expr($tag['surl']) : "''";
        $delimiter = isset($tag['delimiter']) ? $this->expr($tag['delimiter']) : "''";
        return '<?php echo \app\common\service\YunuService::position(' . $cid . ', ' . $sname . ', ' . $surl . ', ' . $delimiter . '); ?>';
    }

    /** {yunu:nav typeid="0" limit="10"} {$nav.title} {/yunu:nav} */
    public function tagNav($tag, $content)
    {
        $typeid = isset($tag['typeid']) ? $this->expr($tag['typeid'], true) : '0';
        $limit  = isset($tag['limit']) ? $this->expr($tag['limit']) : "'10'";
        $parse  = '<?php $_y = \app\common\service\YunuService::nav(' . $typeid . ', ' . $limit . '); foreach ($_y as $nav): ?>';
        $parse .= $content;
        $parse .= '<?php endforeach; ?>';
        return $parse;
    }

    /** {yunu:catlist cid="2" type="son" limit="10" flag="1" orderby="sort desc"} {$catlist.title} {/yunu:catlist} */
    public function tagCatlist($tag, $content)
    {
        $cid     = isset($tag['cid']) ? $this->expr($tag['cid'], true) : '0';
        $type    = isset($tag['type']) ? $this->expr($tag['type']) : "'son'";
        $limit   = isset($tag['limit']) ? $this->expr($tag['limit']) : "'10'";
        $flag    = isset($tag['flag']) ? $this->expr($tag['flag'], true) : '1';
        $orderby = isset($tag['orderby']) ? $this->expr($tag['orderby']) : "''";
        $parse = '<?php $_y = \app\common\service\YunuService::catlist(' . $cid . ', ' . $type . ', ' . $limit . ', ' . $flag . ', ' . $orderby . '); foreach ($_y as $catlist): ?>';
        $parse .= $content;
        $parse .= '<?php endforeach; ?>';
        return $parse;
    }

    /** {yunu:type typeid="2" type="parent"} {$type.title} {/yunu:type}（type: parent取父栏目/son取子栏目列表/sel取自身） */
    public function tagType($tag, $content)
    {
        $typeid = isset($tag['typeid']) ? $this->expr($tag['typeid'], true) : '0';
        $type   = isset($tag['type']) ? $this->expr($tag['type']) : "''";
        $parse  = '<?php $type = \app\common\service\YunuService::oneCategory(' . $typeid . ', ' . $type . '); ?>';
        $parse .= $content;
        return $parse;
    }

    /** {yunu:list cid="2" limit="10" pagesize="0" flag="1" top="1" image="1" orderby="id desc" titlelen="30"} {$list.title} {/yunu:list} */
    public function tagList($tag, $content)
    {
        $cid       = isset($tag['cid']) ? $this->expr($tag['cid'], true) : '0';
        $titlelen  = isset($tag['titlelen']) ? $this->expr($tag['titlelen'], true) : '0';
        $orderby   = isset($tag['orderby']) ? $this->expr($tag['orderby']) : "'id desc'";
        $keyword   = isset($tag['keyword']) ? $this->expr($tag['keyword']) : "''";
        $tagkw     = isset($tag['tag']) ? $this->expr($tag['tag']) : "''";
        $limit     = isset($tag['limit']) ? $this->expr($tag['limit']) : "'10'";
        $pagesize  = isset($tag['pagesize']) ? $this->expr($tag['pagesize'], true) : '0';
        $flag      = isset($tag['flag']) ? $this->expr($tag['flag']) : "''";
        $top       = isset($tag['top']) ? $this->expr($tag['top'], true) : '0';
        $image     = isset($tag['image']) ? $this->expr($tag['image']) : "''";
        $parse     = '<?php $_y = \app\common\service\YunuService::contentList(' . $cid . ', ' . $limit . ', ' . $pagesize . ', ' . $flag . ', ' . $top . ', ' . $image . ', ' . $orderby . ', ' . $keyword . ', ' . $titlelen . ', ' . $tagkw . '); $page = $_y["page"]; foreach ($_y["list"] as $list): ?>';
        $parse .= $content;
        $parse .= '<?php endforeach; ?>';
        return $parse;
    }

    /** {yunu:info conid="5"} {$info.title} {/yunu:info} */
    public function tagInfo($tag, $content)
    {
        $conid  = isset($tag['conid']) ? $this->expr($tag['conid'], true) : '0';
        $type   = isset($tag['type']) ? $this->expr($tag['type']) : "''";
        $order  = isset($tag['orderby']) ? $this->expr($tag['orderby']) : "'id desc'";
        $parse  = '<?php $info = \app\common\service\YunuService::oneContent(' . $conid . ', ' . $type . ', ' . $order . '); ?>';
        $parse .= $content;
        return $parse;
    }

    /** {yunu:block name="footer" /} */
    public function tagBlock($tag)
    {
        $name    = isset($tag['name']) ? $this->expr($tag['name']) : "''";
        $infolen = isset($tag['infolen']) ? $this->expr($tag['infolen'], true) : '0';
        return '<?php echo \app\common\service\YunuService::block(' . $name . ', ' . $infolen . '); ?>';
    }

    /** {yunu:banner type="pc" limit="5"} {$banner.pic} {/yunu:banner} */
    public function tagBanner($tag, $content)
    {
        $type  = isset($tag['type']) ? $this->expr($tag['type']) : "''";
        $limit = isset($tag['limit']) ? $this->expr($tag['limit']) : "'10'";
        $parse = '<?php $_y = \app\common\service\YunuService::banner(' . $type . ', ' . $limit . '); foreach ($_y as $banner): ?>';
        $parse .= $content;
        $parse .= '<?php endforeach; ?>';
        return $parse;
    }

    /** {yunu:link type="友情链接" limit="10"} {$link.title} {/yunu:link} */
    public function tagLink($tag, $content)
    {
        $type  = isset($tag['type']) ? $this->expr($tag['type']) : "''";
        $limit = isset($tag['limit']) ? $this->expr($tag['limit']) : "'10'";
        $parse = '<?php $_y = \app\common\service\YunuService::links(' . $type . ', ' . $limit . '); foreach ($_y as $link): ?>';
        $parse .= $content;
        $parse .= '<?php endforeach; ?>';
        return $parse;
    }

    /** {yunu:tag title="$content['tag']"} {$tag.url}{$tag.title} {/yunu:tag}：文章标签内链 */
    public function tagTag($tag, $content)
    {
        $title = isset($tag['title']) ? trim((string)$tag['title']) : '';
        $limit = isset($tag['limit']) ? max(1, (int)$tag['limit']) : 20;
        if (strpos($title, '{$') !== false || preg_match('/\$[a-zA-Z_]/', $title)) {
            // 模板变量：编译期无法取值，运行时用变量（需 varExpr）；兼容 {$content.tag} 与 $content['tag'] 写法
            $title = $this->varExpr($title);
        } else {
            $title = "'" . addcslashes($title, "'\\") . "'";
        }
        $parse = '<?php $_yt = trim((string)' . $title . '); $_yts = $_yt ? array_slice(array_filter(array_map("trim", preg_split("/[,，、\s]+/u", $_yt))), 0, ' . $limit . ') : []; foreach ($_yts as $_ytag): $tag = ["title" => $_ytag, "url" => "/tag/index?title=" . rawurlencode($_ytag)]; ?>';
        $parse .= $content;
        $parse .= '<?php endforeach; ?>';
        return $parse;
    }

    /** {yunu:cwkeywords /}：内容关键词内链（兼容标签，当前输出空，避免模板报错） */
    public function tagCwkeywords($tag)
    {
        return '<?php echo ""; ?>';
    }
}
