<?php
// +----------------------------------------------------------------------
// | 留言板管理控制器
// | 支持随意增加留言板数量，每个留言板拥有独立字段（可增删、可设必填/非必填）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\MessageBoard;
use app\common\model\Message;

class Board extends AdminBase
{
    /**
     * 留言板列表
     */
    public function index()
    {
        $boards = MessageBoard::order('sort', 'asc')->order('id', 'asc')->select();
        $scope = $this->dataScopeBoards();
        if ($scope !== null) {
            $boards = $boards->filter(function ($b) use ($scope) { return in_array((int)$b['id'], $scope); });
        }
        foreach ($boards as &$b) {
            $b['msg_count'] = Message::where('board_id', $b['id'])->count();
        }
        return view('board/index', ['boards' => $boards]);
    }

    /**
     * 添加 / 编辑留言板
     */
    public function edit($id = 0)
    {
        $id = (int)$id;
        if ($id > 0 && !$this->canManageBoard($id)) return $this->fail('无权限操作该留言板');
        if (request()->isPost()) {
            $name        = trim(input('post.name', ''));
            $title       = trim(input('post.title', ''));
            $description = trim(input('post.description', ''));
            $status      = (int)input('post.status', 1);
            $captcha     = (int)input('post.captcha', 0);
            $showPublic  = (int)input('post.show_public', 0);
            $defaultPublic = (int)input('post.default_public', 1);
            $sort        = (int)input('post.sort', 100);
            if ($name === '' || $title === '') return $this->fail('请填写留言板标识和名称');
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                return $this->fail('标识只能由字母开头、字母数字下划线组成');
            }
            $data = ['name' => $name, 'title' => $title, 'description' => $description, 'status' => $status, 'captcha' => $captcha, 'show_public' => $showPublic, 'default_public' => $defaultPublic, 'sort' => $sort];
            if ($id) {
                $b = MessageBoard::find($id);
                if (!$b) return $this->fail('留言板不存在');
                if (MessageBoard::where('name', $name)->where('id', '<>', $id)->count()) {
                    return $this->fail('该标识已存在');
                }
                $b->save($data);
                AdminLog::record('board_edit', '编辑留言板：' . $title);
            } else {
                if (MessageBoard::where('name', $name)->count()) {
                    return $this->fail('该标识已存在');
                }
                MessageBoard::create($data);
                AdminLog::record('board_add', '新增留言板：' . $title);
            }
            return $this->ok('保存成功', (string)url('admin/board/index'));
        }
        $info = $id ? MessageBoard::find($id) : null;
        return view('board/edit', ['info' => $info]);
    }

    /**
     * 留言板字段管理（字段存 board.fields JSON）
     */
    public function fields($id = 0)
    {
        if (!$this->canManageBoard((int)$id)) return $this->fail('无权限操作该留言板');
        $board = MessageBoard::find((int)$id);
        if (!$board) return $this->fail('留言板不存在');
        return view('board/fields', ['board' => $board]);
    }

    /**
     * 留言板字段增改
     */
    public function field_edit($id = 0)
    {
        $id = (int)$id;
        $boardId = (int)input('get.board_id', 0);
        if (!$this->canManageBoard($boardId)) return $this->fail('无权限操作该留言板');
        if (request()->isPost()) {
            $boardId = (int)input('post.board_id', 0);
            $board = MessageBoard::find($boardId);
            if (!$board) return $this->fail('留言板不存在');
            $name     = trim(input('post.name', ''));
            $label    = trim(input('post.label', ''));
            $type     = input('post.type', 'text');
            $required = (int)input('post.required', 0);
            $sort     = (int)input('post.sort', 100);
            $options  = input('post.options', '');
            if ($label === '') return $this->fail('请填写字段标签');
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                return $this->fail('字段名只能由字母开头、字母数字下划线组成');
            }
            if (in_array($type, ['select', 'radio', 'checkbox']) && trim($options) === '') {
                return $this->fail('该类型请填写选项');
            }
            $optionsArr = [];
            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                $optionsArr = array_values(array_filter(array_map('trim', explode("\n", $options))));
            }
            $fields = $board['fields'];
            // 排除当前编辑的字段
            if ($id) {
                $fields = array_values(array_filter($fields, function ($f) use ($id) {
                    return $f['id'] != $id;
                }));
            }
            $new = ['id' => $id ?: (int)(max(array_map(function ($f) { return $f['id']; }, $fields) ?: [0]) + 1),
                    'name' => $name, 'label' => $label, 'type' => $type,
                    'required' => $required, 'sort' => $sort, 'options' => $optionsArr];
            $fields[] = $new;
            usort($fields, function ($a, $b) { return $a['sort'] <=> $b['sort']; });
            $board->save(['fields' => json_encode($fields, JSON_UNESCAPED_UNICODE)]);
            AdminLog::record('board_field', ($id ? '编辑' : '新增') . '留言板字段：' . $label);
            return $this->ok('保存成功', (string)url('admin/board/fields/id/' . $boardId));
        }

        $board = MessageBoard::find($boardId);
        $field = null;
        if ($id && $board) {
            foreach ($board['fields'] as $f) {
                if ($f['id'] == $id) { $field = $f; break; }
            }
        }
        return view('board/field_edit', ['board' => $board, 'boardId' => $board ? $board['id'] : 0, 'field' => $field, 'types' => \app\common\model\ModelField::TYPES]);
    }

    /**
     * 删除留言板字段
     */
    public function field_delete($boardId, $fieldId)
    {
        if (!$this->canManageBoard((int)$boardId)) return $this->fail('无权限操作该留言板');
        $board = MessageBoard::find((int)$boardId);
        if (!$board) return $this->fail('留言板不存在');
        $fields = array_values(array_filter($board['fields'], function ($f) use ($fieldId) {
            return $f['id'] != (int)$fieldId;
        }));
        $board->save(['fields' => json_encode($fields, JSON_UNESCAPED_UNICODE)]);
        return $this->ok('删除成功', (string)url('admin/board/fields/id/' . $boardId));
    }

    /**
     * 删除留言板（其留言一并删除）
     */
    public function delete($id = 0)
    {
        if (!$this->canManageBoard((int)$id)) return $this->fail('无权限操作该留言板');
        $b = MessageBoard::find((int)$id);
        if (!$b) return $this->fail('留言板不存在');
        if ($b['id'] == 1) return $this->fail('默认留言板不可删除');
        Message::where('board_id', $b['id'])->delete();
        $b->delete();
        AdminLog::record('board_delete', '删除留言板：' . $b['title']);
        return $this->ok('删除成功', (string)url('admin/board/index'));
    }

    /**
     * 生成留言板前台调用代码（查看/复制用）
     */
    public function code($id = 0)
    {
        if (!$this->canManageBoard((int)$id)) return $this->fail('无权限操作该留言板');
        $board = MessageBoard::find((int)$id);
        if (!$board) return $this->fail('留言板不存在');
        // 生成的前台代码接口地址：带当前语言前缀（多语言站下提交/查询/列表打到对应语言表）
        $apiBase = lang_code() === default_lang() ? '' : '/' . lang_code();
        $fieldsHtml = '';
        foreach ($board['fields'] as $f) {
            $fieldsHtml .= $this->fieldHtml($f);
        }
        $captchaHtml = '';
        if ((int)$board['captcha'] === 1) {
            $captchaHtml = '  <div class="mf"><label>验证码 <em>*</em></label><input type="text" name="captcha_code" required placeholder="输入右侧验证码"> <img src="' . $apiBase . '/message/captcha" class="msg-captcha" alt="验证码" title="点击刷新" style="cursor:pointer;height:38px;vertical-align:middle" onclick="msgRefresh(this)"></div>' . "\n";
        }
        // 前台是否公开选择：留言板开启后表单显示公开/不公开单选
        $publicHtml = '';
        if ((int)$board['show_public'] === 1) {
            $publicHtml = '  <div class="mf"><label>是否公开</label>'
                . '<label class="mopt"><input type="radio" name="is_public" value="1" checked> 公开</label>'
                . '<label class="mopt"><input type="radio" name="is_public" value="0"> 不公开（仅凭查询码查看回复）</label></div>' . "\n";
        }
        $formId = 'msg_' . $board['name'];
        $fn     = 'msgSubmit' . $board['id'];
        $queryId = 'msgq_' . $board['id'];
        $listId  = 'msgl_' . $board['id'];
        $code = "<!-- 留言板：{$board['title']}（标识 {$board['name']}）——由易云CMS生成，复制到前台模板任意位置即可使用 -->\n"
            . "<form id=\"{$formId}\" class=\"msg-form\" onsubmit=\"return {$fn}(event)\">\n"
            . "  <input type=\"hidden\" name=\"board_id\" value=\"{$board['id']}\">\n"
            . $fieldsHtml
            . $publicHtml
            . $captchaHtml
            . "  <div class=\"mf\"><button type=\"submit\" class=\"msg-btn\">提交留言</button></div>\n"
            . "</form>\n"
            . "<!-- 回复查询：填写提交后获得的查询码即可查看该留言的审核与回复情况 -->\n"
            . "<div class=\"msg-query\">\n"
            . "  <h4>查询回复</h4>\n"
            . "  <div class=\"mf\" style=\"display:flex;gap:8px;align-items:center\">\n"
            . "    <input type=\"text\" id=\"{$queryId}-input\" placeholder=\"输入查询码（如 A7K2M9X4）\" style=\"flex:1\">\n"
            . "    <button type=\"button\" class=\"msg-btn\" onclick=\"msgQuery{$board['id']}()\">查询</button>\n"
            . "  </div>\n"
            . "  <div id=\"{$queryId}-result\" class=\"msg-query-result\" style=\"margin-top:10px\"></div>\n"
            . "</div>\n"
            . "<!-- 公开留言列表：后台回复并标记\"公开\"的留言在此展示 -->\n"
            . "<div class=\"msg-list\">\n"
            . "  <h4>最新留言</h4>\n"
            . "  <ul id=\"{$listId}\" class=\"msg-list-ul\"></ul>\n"
            . "</div>\n"
            . "<script>\n"
            . "function msgRefresh(img){ img.src='" . $apiBase . "/message/captcha?t='+Date.now(); }\n"
            . "function {$fn}(e){\n"
            . "  e.preventDefault(); var fd = new FormData(document.getElementById('{$formId}'));\n"
            . "  fetch('" . $apiBase . "/message/submit',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){\n"
            . "    alert(res.msg); if(res.code===1){ document.getElementById('{$formId}').reset(); var c=document.querySelector('#{$formId} .msg-captcha'); if(c) msgRefresh(c); }\n"
            . "  }).catch(function(){ alert('提交失败，请稍后再试'); });\n"
            . "  return false;\n"
            . "}\n"
            . "// 查询回复情况\n"
            . "function msgQuery{$board['id']}(){\n"
            . "  var inp=document.getElementById('{$queryId}-input'), box=document.getElementById('{$queryId}-result');\n"
            . "  var code=(inp.value||'').trim().toUpperCase(); if(!code){ box.innerHTML='<span style=\"color:#c0392b\">请输入查询码</span>'; return; }\n"
            . "  var fd=new FormData(); fd.append('query_code', code);\n"
            . "  fetch('" . $apiBase . "/message/query',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){\n"
            . "    if(res.code!==1){ box.innerHTML='<span style=\"color:#c0392b\">'+res.msg+'</span>'; return; }\n"
            . "    var d=res.data, html='<div class=\"msg-q-item\"><b>'+d.name+'</b> <span style=\"color:#999;font-size:12px\">'+d.create_time+'</span><br>'+d.content+'<br><span style=\"color:#888\">状态：'+d.status_text+'</span>';\n"
            . "    if(d.reply){ html+='<div style=\"margin-top:6px;padding:8px;background:#f4f8fe;border-radius:6px\"><b>官方回复：</b>'+d.reply+(d.reply_time?' <span style=\"color:#999;font-size:12px\">'+d.reply_time+'</span>':'')+'</div>'; }\n"
            . "    box.innerHTML=html+'</div>';\n"
            . "  }).catch(function(){ box.innerHTML='<span style=\"color:#c0392b\">查询失败，请稍后再试</span>'; });\n"
            . "}\n"
            . "// 加载公开留言列表\n"
            . "fetch('" . $apiBase . "/message/list?board_id={$board['id']}&limit=10').then(function(r){return r.json();}).then(function(res){\n"
            . "  var ul=document.getElementById('{$listId}'); if(!ul) return;\n"
            . "  if(res.code!==1 || !res.data.length){ ul.innerHTML='<li style=\"color:#999\">暂无公开留言</li>'; return; }\n"
            . "  ul.innerHTML=res.data.map(function(m){\n"
            . "    var s='<li style=\"padding:8px 0;border-bottom:1px dashed #e5e9f0\"><b>'+m.name+'</b> <span style=\"color:#999;font-size:12px\">'+m.create_time+'</span><br>'+m.content;\n"
            . "    if(m.reply){ s+='<div style=\"margin-top:4px;color:#2d8cf0\">官方回复：'+m.reply+'</div>'; }\n"
            . "    return s+'</li>';\n"
            . "  }).join('');\n"
            . "}).catch(function(){});\n"
            . "</script>";
        return json(['code' => 1, 'data' => $code]);
    }

    /**
     * 单字段表单 HTML
     */
    protected function fieldHtml($f)
    {
        $req  = (int)$f['required'] === 1 ? ' required' : '';
        $lab  = '<label>' . $f['label'] . ((int)$f['required'] === 1 ? ' <em>*</em>' : '') . '</label>';
        $name = 'name="' . $f['name'] . '"';
        switch ($f['type']) {
            case 'textarea':
                return '  <div class="mf">' . $lab . '<textarea ' . $name . $req . '></textarea></div>' . "\n";
            case 'select':
                $opts = '';
                foreach ($f['options'] as $o) { $opts .= '<option value="' . htmlspecialchars($o) . '">' . htmlspecialchars($o) . '</option>'; }
                return '  <div class="mf">' . $lab . '<select ' . $name . $req . '><option value="">请选择</option>' . $opts . '</select></div>' . "\n";
            case 'radio':
                $r = '';
                foreach ($f['options'] as $o) { $r .= '<label class="mopt"><input type="radio" ' . $name . ' value="' . htmlspecialchars($o) . '"' . $req . '> ' . htmlspecialchars($o) . '</label>'; }
                return '  <div class="mf">' . $lab . $r . '</div>' . "\n";
            case 'checkbox':
                $r = '';
                foreach ($f['options'] as $o) { $r .= '<label class="mopt"><input type="checkbox" ' . $name . ' value="' . htmlspecialchars($o) . '"> ' . htmlspecialchars($o) . '</label>'; }
                return '  <div class="mf">' . $lab . $r . '</div>' . "\n";
            default:
                return '  <div class="mf">' . $lab . '<input type="text" ' . $name . $req . '></div>' . "\n";
        }
    }
}
