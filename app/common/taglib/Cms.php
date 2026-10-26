<?php
// +----------------------------------------------------------------------
// | 前台 CMS 自定义标签库
// | 模板中通过 {cms:xxx} 调用，数据逻辑在 TagService 中实现
// | 需在 config/view.php 配置 taglib_pre_load 加载本类
// +----------------------------------------------------------------------
namespace app\common\taglib;

use app\common\service\TagService;
use think\template\TagLib;

class Cms extends TagLib
{
    /**
     * 标签定义：attr 为允许属性，close=1 双标签(循环)，close=0 单标签(输出)
     */
    protected $tags = [
        'setting'   => ['attr' => 'name,default', 'close' => 0],
        'list'      => ['attr' => 'catid,num,order,model_id,flag,where,key,item', 'close' => 1],
        'category'  => ['attr' => 'id,parent,type,num,haschild,deep,top,key,item', 'close' => 1],
        'nav'       => ['attr' => 'key,item', 'close' => 1],
        'block'     => ['attr' => 'name,type', 'close' => 0],
        'ad'        => ['attr' => 'name', 'close' => 0],
        'link'      => ['attr' => 'num,key,item', 'close' => 1],
        'crumbs'    => ['attr' => '', 'close' => 1],
        'prevnext'  => ['attr' => '', 'close' => 1],
        'content'   => ['attr' => 'id', 'close' => 0],
        'hot'       => ['attr' => 'num,catid,key,item', 'close' => 1],
        'pagetitle' => ['attr' => 'seotitle,catname', 'close' => 0],
        'catfield'  => ['attr' => 'name,default', 'close' => 0],
    ];

    /**
     * 把标签属性转成合法的 PHP 表达式
     * @param string $value  属性原始值（如 '2' / 'list' / '{$category.id}'）
     * @param bool   $number 是否为数值属性（纯数字输出裸字面量）
     * @return string
     */
    protected function expr($value, $number = false)
    {
        $value = trim($value);
        if ($value === '') {
            return $number ? '0' : "''";
        }
        // 数值属性：纯数字直接输出，否则按变量处理
        if ($number) {
            if (preg_match('/^-?\d+$/', $value)) {
                return $value;
            }
            return $this->varExpr($value);
        }
        // 字符串属性：模板变量 -> 编译为表达式；否则加引号
        if (strpos($value, '{$') !== false) {
            return $this->varExpr($value);
        }
        return "'" . addcslashes($value, "'\\") . "'";
    }

    /**
     * 将 {$var} 编译为 PHP 变量表达式
     */
    protected function varExpr($value)
    {
        $this->tpl->parseVar($value);
        $this->tpl->parseVarFunction($value, false);
        return $value;
    }

    /**
     * 循环变量名（item/key）必须是合法标识符
     */
    protected function varName($value, $default)
    {
        $value = trim($value ?: '');
        if ($value && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            return $value;
        }
        return $default;
    }

    /**
     * {cms:setting name="site.name" default="" /}
     */
    public function tagSetting($tag)
    {
        $name    = $this->expr(isset($tag['name']) ? $tag['name'] : '');
        $default = isset($tag['default']) ? $this->expr($tag['default']) : "''";
        return '<?php echo htmlspecialchars(\app\common\service\TagService::setting(' . $name . ', ' . $default . ') ?? ""); ?>';
    }

    /**
     * {cms:list catid="2" num="10" order="list" flag="recommend" model_id="1" where="" item="vo"}
     * {/cms:list}
     */
    public function tagList($tag, $content)
    {
        $catid = isset($tag['catid']) ? $this->expr($tag['catid']) : '0';
        $num   = isset($tag['num']) ? $this->expr($tag['num'], true) : '0'; // 未写 num 时按栏目分页条数
        $order = isset($tag['order']) ? $this->expr($tag['order']) : "'list'";
        $model = isset($tag['model_id']) ? $this->expr($tag['model_id'], true) : '0';
        $flag  = isset($tag['flag']) ? $this->expr($tag['flag']) : "''";
        $where = isset($tag['where']) ? $this->expr($tag['where']) : "''";
        $key   = $this->varName(isset($tag['key']) ? $tag['key'] : '', 'k');
        $item  = $this->varName(isset($tag['item']) ? $tag['item'] : '', 'vo');

        $parse = '<?php $__data = \app\common\service\TagService::lists(' . $catid . ', ' . $num . ', ' . $order . ', ' . $model . ', ' . $flag . ', ' . $where . '); $__k = 0; foreach ($__data as $' . $key . ' => $' . $item . '): ?>';
        $parse .= $content;
        $parse .= '<?php $__k++; endforeach; ?>';
        return $parse;
    }

    /**
     * {cms:category parent="0" type="" num="20" haschild="0" item="cat"}
     * {/cms:category}
     */
    public function tagCategory($tag, $content)
    {
        $id       = isset($tag['id']) ? $this->expr($tag['id']) : "''";
        $parent   = isset($tag['parent']) ? $this->expr($tag['parent']) : '0';
        $type     = isset($tag['type']) ? $this->expr($tag['type']) : "''";
        $num      = isset($tag['num']) ? $this->expr($tag['num'], true) : '20';
        $haschild = isset($tag['haschild']) ? $this->expr($tag['haschild'], true) : '0';
        $deep     = isset($tag['deep']) ? $this->expr($tag['deep'], true) : '0';
        $top      = isset($tag['top']) ? $this->expr($tag['top'], true) : '0';
        $key      = $this->varName(isset($tag['key']) ? $tag['key'] : '', 'k');
        $item     = $this->varName(isset($tag['item']) ? $tag['item'] : '', 'cat');

        if ($id !== "''") {
            $parse = '<?php $__data = \app\common\service\TagService::categoryById(' . $id . '); $__k = 0; foreach ($__data as $' . $key . ' => $' . $item . '): ?>';
        } else {
            $parse = '<?php $__data = \app\common\service\TagService::categories(' . $parent . ', ' . $num . ', ' . $type . ', ' . $haschild . ', ' . $deep . ', ' . $top . '); $__k = 0; foreach ($__data as $' . $key . ' => $' . $item . '): ?>';
        }
        $parse .= $content;
        $parse .= '<?php $__k++; endforeach; ?>';
        return $parse;
    }

    /**
     * {cms:nav item="nav"} {/cms:nav}
     */
    public function tagNav($tag, $content)
    {
        $key  = $this->varName(isset($tag['key']) ? $tag['key'] : '', 'k');
        $item = $this->varName(isset($tag['item']) ? $tag['item'] : '', 'nav');
        $parse = '<?php $__data = \app\common\service\TagService::nav(); $__k = 0; foreach ($__data as $' . $key . ' => $' . $item . '): ?>';
        $parse .= $content;
        $parse .= '<?php $__k++; endforeach; ?>';
        return $parse;
    }

    /**
     * {cms:block name="footer_about" /}
     */
    public function tagBlock($tag)
    {
        $name = $this->expr(isset($tag['name']) ? $tag['name'] : '');
        $type = isset($tag['type']) ? $this->expr($tag['type']) : "''";
        return '<?php echo \app\common\service\TagService::block(' . $name . ', ' . $type . '); ?>';
    }

    /**
     * {cms:ad name="home_banner" /}
     */
    public function tagAd($tag)
    {
        $name = $this->expr(isset($tag['name']) ? $tag['name'] : '');
        return '<?php $__ad = \app\common\service\TagService::ad(' . $name . '); if ($__ad) { echo $__ad[\'image\'] ? $__ad[\'image\'] : ($__ad[\'content\'] ?? \'\'); } ?>';
    }

    /**
     * {cms:link num="20" item="lk"} {/cms:link}
     */
    public function tagLink($tag, $content)
    {
        $num  = isset($tag['num']) ? $this->expr($tag['num'], true) : '20';
        $key  = $this->varName(isset($tag['key']) ? $tag['key'] : '', 'k');
        $item = $this->varName(isset($tag['item']) ? $tag['item'] : '', 'lk');
        $parse = '<?php $__data = \app\common\service\TagService::links(' . $num . '); $__k = 0; foreach ($__data as $' . $key . ' => $' . $item . '): ?>';
        $parse .= $content;
        $parse .= '<?php $__k++; endforeach; ?>';
        return $parse;
    }

    /**
     * {cms:crumbs item="c"} {/cms:crumbs}
     * 自动读取当前页面的 $category 变量，无需传入 catid
     */
    public function tagCrumbs($tag, $content)
    {
        $key  = $this->varName(isset($tag['key']) ? $tag['key'] : '', 'k');
        $item = $this->varName(isset($tag['item']) ? $tag['item'] : '', 'c');
        $parse = '<?php $__data = \app\common\service\TagService::crumbs((int)(isset($category) ? $category[\'id\'] : 0)); $__k = 0; foreach ($__data as $' . $key . ' => $' . $item . '): ?>';
        $parse .= $content;
        $parse .= '<?php $__k++; endforeach; ?>';
        return $parse;
    }

    /**
     * {cms:prevnext} {$prev.title} / {$next.title} {/cms:prevnext}
     * 自动读取当前页面的 $content 变量，无需传入 id/catid
     */
    public function tagPrevnext($tag, $content)
    {
        $parse = '<?php $__id = (int)(isset($content[\'id\']) ? $content[\'id\'] : 0); $__cat = (int)(isset($content[\'catid\']) ? $content[\'catid\'] : 0); $__pn = \app\common\service\TagService::prevnext($__id, $__cat); $prev = $__pn["prev"]; $next = $__pn["next"]; ?>';
        $parse .= $content;
        return $parse;
    }

    /**
     * {cms:content id="10" /}  之后可用 {$content.title}
     */
    public function tagContent($tag)
    {
        $id = isset($tag['id']) ? $this->expr($tag['id'], true) : '0';
        return '<?php $content = \app\common\service\TagService::content(' . $id . '); ?>';
    }

    /**
     * {cms:hot num="10" catid="0" item="h"} {/cms:hot}
     */
    public function tagHot($tag, $content)
    {
        $num   = isset($tag['num']) ? $this->expr($tag['num'], true) : '10';
        $catid = isset($tag['catid']) ? $this->expr($tag['catid'], true) : '0';
        $key   = $this->varName(isset($tag['key']) ? $tag['key'] : '', 'k');
        $item  = $this->varName(isset($tag['item']) ? $tag['item'] : '', 'h');
        $parse = '<?php $__data = \app\common\service\TagService::hot(' . $num . ', ' . $catid . '); $__k = 0; foreach ($__data as $' . $key . ' => $' . $item . '): ?>';
        $parse .= $content;
        $parse .= '<?php $__k++; endforeach; ?>';
        return $parse;
    }

    /**
     * {cms:catfield name="tel" default="" /}
     * 读取当前栏目（模板中 $category）自定义字段值
     */
    public function tagCatfield($tag)
    {
        $name    = $this->expr(isset($tag['name']) ? $tag['name'] : '');
        $default = isset($tag['default']) ? $this->expr($tag['default']) : "''";
        $type    = isset($tag['type']) ? $this->expr($tag['type']) : "''";
        return '<?php echo \app\common\service\TagService::catfield((int)(isset($category) ? $category[\'id\'] : 0), ' . $name . ', ' . $default . ', ' . $type . '); ?>';
    }

    /**
     * {cms:pagetitle seotitle="" catname="" /}
     */
    public function tagPagetitle($tag)
    {
        $seo = isset($tag['seotitle']) ? $this->expr($tag['seotitle']) : "''";
        $cat = isset($tag['catname']) ? $this->expr($tag['catname']) : "''";
        return '<?php echo htmlspecialchars(\app\common\service\TagService::pageTitle(' . $seo . ', ' . $cat . ')); ?>';
    }
}
