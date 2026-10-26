# -*- coding: utf-8 -*-
"""
云优CMS模板 → 易云CMS模板 自动转换器
用法: python yunu_tpl_convert.py [云优模板目录] [易云template目录] [输出语言目录]
默认: D:\phpstudy_pro\WWW\chty\template\default\index → F:\豆包下载\CMS\template\cn
规则:
  1. <yunu:xxx attrs> / </yunu:xxx> → {yunu:xxx attrs} / {/yunu:xxx}（无配对闭标签的自闭合标签补 /）
  2. <if condition="X"> / <elseif/> / <else/> / </if> → TP6 {if}/{elseif}/{else /}/{/if}
  3. <eq name="A" value="B"> / <neq> → TP6 {eq name="A" value="B"}/{/eq}
  4. <volist name="nav['child']" id="v"> → {volist name="nav.child" id="v"}
  5. <include file="public:xxx" .../> → {include file="xxx" /}（header/banner/left/footer 映射）
  6. __PUBLIC__ 保留（易云模板引擎自动解析为模板资源入口 /assets/index?path=，资源放 template/{语言}/ 下）
  7. condition/name 内数组访问 $x['y'] → $x.y
  8. $category 字段别名（title→name desc→description pic→image seo_keywords→keywords seo_desc→description etitle→ename）
  9. $content 字段别名（seo_title→title seo_keywords→keywords seo_desc→desc）—— create_time 键名与易云一致，无需转换
  10. |date='...' 单引号 → 双引号
  11. 搜索表单 name="key" → name="kw"
  12. 原生 <?php ?> 与 JS 原样保留
  13. 富文本输出自动补 |raw：{$type.desc}/{$content.desc}/{$category.content} 等
      （避免历史踩坑：ThinkPHP 默认转义富文本，前台显示 &lt;p&gt; 文本）
  14. 资源路径 /uploads/... → /upload/...（图片目录统一）
  15. 留言表单字段名兼容（yunu 的 key 类字段 → 易云 name/tel/email/subject/content）
输出: 转换结果逐文件打印；目标目录自动备份为 template_backup_<ts>
"""
import io, os, re, sys, shutil, datetime, json, json

TPL_DIR = sys.argv[1] if len(sys.argv) > 1 else r'D:\phpstudy_pro\WWW\chty\template\default\index'
OUT_ROOT = sys.argv[2] if len(sys.argv) > 2 else r'F:\豆包下载\CMS\template'
LANG = sys.argv[3] if len(sys.argv) > 3 else 'cn'
OUT_DIR = os.path.join(OUT_ROOT, LANG)
STATIC_SRC = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(TPL_DIR))), 'statics')
# 云优模板目录 D:\...\chty\template\default\index → statics 在 D:\...\chty\statics
STATIC_SRC = r'D:\phpstudy_pro\WWW\chty\statics'
STATIC_DST = OUT_DIR  # 云优 statics → template/{语言}/（模板 __PUBLIC__ 指向模板同级资源）

# 输出文件映射（云优模板名 → 易云模板名）
FILE_MAP = {
    'index_index.html': 'index_index.html',
    'search_index.html': 'search_index.html',
    'tag_index.html': 'tag_index.html',
    'public_header.html': 'header.html',
    'public_footer.html': 'footer.html',
    'public_banner.html': 'public_banner.html',
    'public_left.html': 'public_left.html',
}
# 其余 list_*.html / show_*.html 同名输出

INCLUDE_MAP = {
    'public:header': 'header',
    'public:banner': 'public_banner',
    'public:left': 'public_left',
    'public:footer': 'footer',
}

# 栏目 ID 映射（云优 id → 易云 id），由 tools/yunu/gen_id_map 生成
_ID_MAP = {}
_idmap_path = os.path.join(os.path.dirname(OUT_ROOT), 'tools', 'yunu', 'id_map.json')
if os.path.exists(_idmap_path):
    try:
        _ID_MAP = json.load(io.open(_idmap_path, encoding='utf-8'))
    except Exception:
        _ID_MAP = {}

def _map_id(m):
    key = int(m.group(2))
    return '%s="%s"' % (m.group(1), _ID_MAP.get(str(key), m.group(2)))

def convert(src):
    t = src
    # ---- 0) 标签属性数字栏目 ID 映射（cid/typeid/pid）----
    t = re.sub(r'(cid|typeid|pid)="(\d+)"', _map_id, t)
    t = re.sub(r"(cid|typeid|pid)='(\d+)'", lambda m: "%s='%s'" % (m.group(1), _ID_MAP.get(m.group(2), m.group(2))), t)
    # ---- 1) condition/name 内数组访问 → 点语法 ----
    def fix_cond(m):
        inner = m.group(1)
        inner = re.sub(r"\$(\w+)\['(\w+)'\]", r"$\1.\2", inner)
        inner = re.sub(r"\$(\w+)\[\"(\w+)\"\]", r"$\1.\2", inner)
        return 'condition="' + inner + '"'
    t = re.sub(r'condition="([^"]*)"', fix_cond, t)
    def fix_cond_sq(m):
        inner = m.group(1)
        inner = re.sub(r"\$(\w+)\['(\w+)'\]", r"$\1.\2", inner)
        inner = re.sub(r"\$(\w+)\[\"(\w+)\"\]", r"$\1.\2", inner)
        return "condition='" + inner + "'"
    t = re.sub(r"condition='([^']*)'", fix_cond_sq, t)
    def fix_eqname(m):
        name = m.group(2)
        name = re.sub(r"^\$", "", name)
        name = re.sub(r"\['(\w+)'\]", r".\1", name)
        name = re.sub(r'\["(\w+)"\]', r".\1", name)
        return m.group(1) + name + m.group(3)
    t = re.sub(r'(<eq\s+name=")([^"]*)(")', fix_eqname, t)
    t = re.sub(r'(<neq\s+name=")([^"]*)(")', fix_eqname, t)
    # ---- 2) volist name 数组访问 → 点语法，并转换为 TP6 花括号 ----
    def fix_volist(m):
        name = m.group(1)
        v_id = m.group(2)
        cleaned = re.sub(r"^\$", "", name)
        cleaned = re.sub(r"\['(\w+)'\]", r".\1", cleaned)
        cleaned = re.sub(r'\["(\w+)"\]', r".\1", cleaned)
        if '|' in name:
            # 管道函数（如 content.piclist|str2arr='***'）：TP6 volist 不支持，先 assign 到临时变量
            return '{assign name="__vlist" value="$' + cleaned + '" /}{volist name="__vlist" id="' + v_id + '"}'
        return '{volist name="' + cleaned + '" id="' + v_id + '"}'
    t = re.sub(r'<volist\s+name="([^"]*)"\s+id="([^"]+)"[^>]*>', fix_volist, t)
    t = re.sub(r'</volist>', r'{/volist}', t)
    # ---- 3) yunu 标签 ----
    # 自闭合（含 />）
    t = re.sub(r'<yunu:(\w+)([^>]*?)/>', r'{yunu:\1\2 /}', t)
    # 普通开标签（不含 />；注意属性中可能含 > 实体）
    t = re.sub(r'<yunu:(\w+)([^>]*)>', r'{yunu:\1\2}', t)
    # 闭标签
    t = re.sub(r'</yunu:(\w+)>', r'{/yunu:\1}', t)
    # 自闭合 yunu 标签补 /（config/url/position/block/cwkeywords 若被写成无 / 形式）
    for nm in ['config', 'url', 'position', 'block', 'cwkeywords']:
        t = re.sub(r'\{yunu:' + nm + r'([^}/]*)\}(?!\{/yunu:' + nm + r'\})', r'{yunu:' + nm + r'\1 /}', t)
    # ---- 4) if/elseif/else/eq/neq ----
    t = re.sub(r'<if\s+condition="([^"]*)">', r'{if \1}', t)
    t = re.sub(r"<if\s+condition='([^']*)'>", r'{if \1}', t)
    t = re.sub(r'<elseif\s+condition="([^"]*)"/>', r'{elseif \1}', t)
    t = re.sub(r"<elseif\s+condition='([^']*)'/>", r'{elseif \1}', t)
    t = re.sub(r'<else/>', r'{else /}', t)
    t = re.sub(r'<else>', r'{else /}', t)
    t = re.sub(r'</if>', r'{/if}', t)
    t = re.sub(r'<eq\s+([^>]*)>', r'{eq \1}', t)
    t = re.sub(r'</eq>', r'{/eq}', t)
    t = re.sub(r'<neq\s+([^>]*)>', r'{neq \1}', t)
    t = re.sub(r'</neq>', r'{/neq}', t)
    # ---- 5) include ----
    def fix_include(m):
        f = m.group(1).strip()
        mapped = INCLUDE_MAP.get(f, f.replace('public:', ''))
        return '{include file="' + mapped + '" /}'
    t = re.sub(r'<include\s+file="([^"]+)"[^>]*/>', fix_include, t)
    # ---- 6) __PUBLIC__ 保留原样 ----
    # 易云 config/view.php 将 __PUBLIC__ 解析为模板资源入口（/assets/index?path=），
    # 资源物理存放于 template/{语言模板目录}/（css/js/images/fonts 等），无需改写
    # ---- 7) $category 字段别名（数据库列） ----
    t = re.sub(r'\$category\.title\b', '$category.name', t)
    t = re.sub(r'\$category\.desc\b', '$category.description', t)
    t = re.sub(r'\$category\.pic\b', '$category.image', t)
    t = re.sub(r'\$category\.seo_keywords\b', '$category.keywords', t)
    t = re.sub(r'\$category\.seo_desc\b', '$category.description', t)
    t = re.sub(r'\$category\.etitle\b', '$category.ename', t)
    # ---- 8) $content 字段别名（数据库列） ----
    t = re.sub(r'\$content\.seo_title\b', '$content.title', t)
    t = re.sub(r'\$content\.seo_keywords\b', '$content.keywords', t)
    t = re.sub(r'\$content\.seo_desc\b', '$content.desc', t)
    # ---- 8.5) $type / $category 数组访问空值兜底（搜索/标签等无栏目上下文时不报错） ----
    t = re.sub(r"\$type\[('pic'|'url'|'desc'|'title'|'ename'|'id'|'keywords'|'description')\]", r"($type[\1] ?? '')", t)
    t = re.sub(r"\$category\[('id'|'ename'|'title'|'name'|'parent_id'|'keywords'|'description')\]", r"($category[\1] ?? '')", t)

    # ---- 9) date 管道：去掉 TP3 的 ,### 占位（TP6 date 特殊处理自动把变量放第二参数） ----
    t = re.sub(r"\|date='([^']+)',###", r'|date="\1"', t)
    t = re.sub(r'\|date="([^"]+)",###', r'|date="\1"', t)
    # ---- 9.5) 富文本输出自动补 |raw（无任何管道时；有 |str2sub 等截断管道的不动） ----
    # 规则：{$var.desc} / {$var.content} / {$var.description} 无管道 → 补 |raw
    t = re.sub(r'\{(\$\w+\.(?:desc|content|description))\}(?!\|)', r'{\1|raw}', t)
    # ---- 9.6) 资源路径 /uploads/ → /upload/（图片目录统一） ----
    t = re.sub(r'/uploads/(image|file|attach|media)/', r'/upload/\1/', t)
    t = re.sub(r'uploads/image/', 'upload/image/', t)
    # ---- 10) 搜索表单 name="key" → name="kw" ----
    t = re.sub(r'name="key"', 'name="kw"', t)
    t = re.sub(r"name='key'", "name='kw'", t)
    # ---- 10.5) 移除 TP3 token 残留 {$Request.token}（易云留言表单不校验 token） ----
    t = re.sub(r'\s*<input type="hidden" name="__token__" value="\{\\.token\}">\s*', "\n", t)
    return t

def main():
    if not os.path.isdir(TPL_DIR):
        print('云优模板目录不存在: %s' % TPL_DIR); return
    # 备份现有模板
    ts = datetime.datetime.now().strftime('%Y%m%d%H%M%S')
    bak = os.path.join(OUT_ROOT, LANG + '_backup_' + ts)
    if os.path.isdir(OUT_DIR):
        shutil.copytree(OUT_DIR, bak)
        print('已备份现有模板 → %s' % bak)
    os.makedirs(OUT_DIR, exist_ok=True)
    files = sorted([f for f in os.listdir(TPL_DIR) if f.endswith('.html')])
    print('共 %d 个模板文件' % len(files))
    for f in files:
        src = io.open(os.path.join(TPL_DIR, f), 'r', encoding='utf-8', errors='replace').read()
        out = convert(src)
        out_name = FILE_MAP.get(f, f)
        dst = os.path.join(OUT_DIR, out_name)
        io.open(dst, 'w', encoding='utf-8', newline='').write(out)
        print('  %s → %s  (%d → %d 字符)' % (f, out_name, len(src), len(out)))
    # 资源复制：云优 statics → 易云 template/{输出目录}/（css/js/images/fonts 等，模板 __PUBLIC__ 指向）
    if os.path.isdir(STATIC_SRC):
        os.makedirs(STATIC_DST, exist_ok=True)
        n = 0
        for item in os.listdir(STATIC_SRC):
            s = os.path.join(STATIC_SRC, item)
            d = os.path.join(STATIC_DST, item)
            if os.path.isdir(s):
                shutil.copytree(s, d, dirs_exist_ok=True); n += 1
            elif not os.path.exists(d):
                shutil.copy2(s, d); n += 1
        print('静态资源复制完成 → %s （%d 项）' % (STATIC_DST, n))
    else:
        print('警告：云优 statics 目录不存在 %s（资源需手工复制到 template/{语言}/ 下）' % STATIC_SRC)
    print('完成。输出目录：%s' % OUT_DIR)

if __name__ == '__main__':
    main()
