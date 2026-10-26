// ---------- 栏目列表内联编辑（别名/排序） ----------
document.addEventListener('click', function (e) {
  var el = e.target;
  if (!el.classList || !el.classList.contains('editable')) return;
  var id = el.getAttribute('data-id'), field = el.getAttribute('data-field');
  var original = el.textContent.trim();
  if (field === 'ename') original = original.replace(/^\//, '');
  var input = document.createElement('input');
  input.className = 'inline-input';
  input.value = original;
  input.style.width = (original.length + 2) + 'em';
  el.replaceWith(input);
  input.focus();
  function commit(save) {
    var val = input.value.trim();
    var show = (field === 'ename' ? '/' : '') + val;
    function restore() {
      // 把原元素放回 DOM 并显示文本，退出输入状态
      input.replaceWith(el);
      el.textContent = show;
    }
    if (!save || val === original) { restore(); return; }
    // 保存后恢复显示（乐观更新）
    yy.post('/admin/category/editField/id/' + id, { field: field, value: val }, {
      callback: function () { restore(); }
    });
    restore();
  }
  input.addEventListener('keydown', function (ev) {
    if (ev.key === 'Enter') { ev.preventDefault(); commit(true); }
    if (ev.key === 'Escape') { commit(false); }
  });
  input.addEventListener('blur', function () { commit(true); });
  input.addEventListener('click', function (ev) { ev.stopPropagation(); });
});

// ---------- 导航/状态快速切换 ----------
document.addEventListener('click', function (e) {
  var el = e.target;
  if (!el.hasAttribute || !el.hasAttribute('data-toggle')) return;
  var field = el.getAttribute('data-toggle'), id = el.getAttribute('data-id');
  var next = el.classList.contains('ok') ? 0 : 1;
  yy.post('/admin/category/editField/id/' + id, { field: field, value: next }, {
    callback: function () {
      // 乐观更新
      var text = field === 'is_nav' ? (next === 1 ? '是' : '否') : (next === 1 ? '显示' : '隐藏');
      el.textContent = text;
      el.className = next === 1 ? 'tag ok' : 'tag gray';
    }
  });
});
// ---------- 批量添加栏目（可视化表单） ----------
var _baCats = [], _baModels = [], _baTpls = { lists: [], shows: [] };
function baLoad(cb) {
  if (_baCats.length) { cb(); return; }
  window.yy.post('/admin/category/batchData', {}, {
    callback: function (res) {
      var d = (res && res.data) || {};
      _baCats = d.cats || [];
      _baModels = d.models || [];
      _baTpls = d.tpls || { lists: [], shows: [] };
      cb();
    }
  });
}
function baRow(row) {
  row = row || {};
  var parentOpts = '<option value="0">顶级栏目</option>';
  (_baCats || []).forEach(function (c) {
    var indent = new Array((c.depth || 0) + 1).join('　');
    parentOpts += '<option value="' + c.id + '"' + (row.parent_id === c.id ? ' selected' : '') + '>' + indent + ((c.depth || 0) ? '└ ' : '') + c.name + '</option>';
  });
  var modelOpts = (_baModels || []).map(function (m) { return '<option value="' + m.id + '"' + (row.model_id === m.id ? ' selected' : '') + '>' + m.title + '</option>'; }).join('');
  var listOpts = (_baTpls.lists || []).map(function (t) { return '<option value="' + t + '"' + (row.list_template === t ? ' selected' : '') + '>' + t + '.html</option>'; }).join('');
  var showOpts = (_baTpls.shows || []).map(function (t) { return '<option value="' + t + '"' + (row.content_template === t ? ' selected' : '') + '>' + t + '.html</option>'; }).join('');
  var div = document.createElement('div');
  div.className = 'ba-row';
  div.style.cssText = 'display:grid;grid-template-columns:1.1fr 1fr 1.2fr .9fr 1fr 1fr 28px;gap:8px;margin-bottom:8px;align-items:center';
  div.innerHTML =
    '<input type="text" class="ba-name" placeholder="栏目名称" value="' + (row.name || '') + '" style="padding:7px;border:1px solid #e2e8f0;border-radius:6px">' +
    '<input type="text" class="ba-ename" placeholder="别名(自动拼音)" value="' + (row.ename || '') + '" style="padding:7px;border:1px solid #e2e8f0;border-radius:6px">' +
    '<select class="ba-parent" style="padding:7px;border:1px solid #e2e8f0;border-radius:6px">' + parentOpts + '</select>' +
    '<select class="ba-model" style="padding:7px;border:1px solid #e2e8f0;border-radius:6px">' + modelOpts + '</select>' +
    '<select class="ba-list" style="padding:7px;border:1px solid #e2e8f0;border-radius:6px">' + listOpts + '</select>' +
    '<select class="ba-show" style="padding:7px;border:1px solid #e2e8f0;border-radius:6px">' + showOpts + '</select>' +
    '<button type="button" class="ba-del" title="删除此行" style="border:none;background:none;color:#dc2626;cursor:pointer;font-size:16px">✕</button>';
  var nameInput = div.querySelector('.ba-name');
  var enameInput = div.querySelector('.ba-ename');
  nameInput.addEventListener('blur', function () {
    if (!enameInput.value && nameInput.value && window.pinyinPro) {
      try {
        var arr = window.pinyinPro.pinyin(nameInput.value, { toneType: 'none', type: 'array' });
        enameInput.value = arr.map(function (s) { return String(s).toLowerCase(); }).join('');
      } catch (e) {}
    }
  });
  div.querySelector('.ba-del').addEventListener('click', function () { div.remove(); });
  return div;
}
function batchAdd() {
  baLoad(function () {
    var mask = document.createElement('div');
    mask.className = 'modal-mask show';
    var modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.cssText = 'width:980px;max-width:97vw';
    var head = document.createElement('div'); head.className = 'm-head';
    head.innerHTML = '批量添加栏目 <span data-a="close" style="float:right;cursor:pointer;opacity:.6">✕</span>';
    var mbody = document.createElement('div'); mbody.className = 'm-body';
    var cols = document.createElement('div');
    cols.className = 'ba-cols';
    cols.style.cssText = 'display:grid;grid-template-columns:1.1fr 1fr 1.2fr .9fr 1fr 1fr 28px;gap:8px;margin-bottom:6px;color:#94a3b8;font-size:12px';
    cols.innerHTML = '<span>栏目名称</span><span>别名(拼音)</span><span>父栏目</span><span>内容模型</span><span>列表模板</span><span>内容模板</span><span></span>';
    var rowsBox = document.createElement('div'); rowsBox.id = 'ba-rows';
    var addBtn = document.createElement('button'); addBtn.type = 'button'; addBtn.className = 'btn sm'; addBtn.textContent = '+ 添加一行';
    addBtn.style.margin = '8px 0 4px';
    mbody.appendChild(cols); mbody.appendChild(rowsBox); mbody.appendChild(addBtn);
    var foot = document.createElement('div'); foot.className = 'm-foot';
    var cancel = document.createElement('button'); cancel.className = 'btn'; cancel.textContent = '取消'; cancel.setAttribute('data-a', 'cancel');
    var ok = document.createElement('button'); ok.className = 'btn primary'; ok.textContent = '批量创建'; ok.setAttribute('data-a', 'ok');
    foot.appendChild(cancel); foot.appendChild(ok);
    modal.appendChild(head); modal.appendChild(mbody); modal.appendChild(foot);
    mask.appendChild(modal);
    document.body.appendChild(mask);
    function addRow() { rowsBox.appendChild(baRow()); }
    addBtn.addEventListener('click', addRow);
    addRow();
    mask.addEventListener('click', function (e) {
      var a = e.target.getAttribute && e.target.getAttribute('data-a');
      if (a === 'close' || a === 'cancel' || e.target === mask) { mask.remove(); return; }
      if (a === 'ok') {
        var rows = [];
        rowsBox.querySelectorAll('.ba-row').forEach(function (r) {
          var name = r.querySelector('.ba-name').value.trim();
          if (!name) return;
          rows.push({
            name: name,
            ename: r.querySelector('.ba-ename').value.trim(),
            parent_id: parseInt(r.querySelector('.ba-parent').value, 10) || 0,
            model_id: parseInt(r.querySelector('.ba-model').value, 10) || 1,
            list_template: r.querySelector('.ba-list').value,
            content_template: r.querySelector('.ba-show').value
          });
        });
        if (!rows.length) { window.yy.toast('请至少填写一个栏目名称', 'err'); return; }
        mask.remove();
        window.yy.post('/admin/category/batchAdd', { rows: JSON.stringify(rows) }, { callback: function () { location.reload(); } });
      }
    });
  });
}

// ---------- 栏目树折叠 / 展开 ----------
function catHideChildren(id) {
  document.querySelectorAll('#cat-tbody tr[data-parent="' + id + '"]').forEach(function (tr) {
    tr.style.display = 'none';
    var t = tr.querySelector('.cat-toggle');
    if (t) { t.setAttribute('data-open', '0'); t.textContent = '＋'; t.classList.remove('open'); }
    catHideChildren(tr.getAttribute('data-id'));
  });
}
document.addEventListener('click', function (e) {
  var el = e.target;
  if (!el.classList || !el.classList.contains('cat-toggle')) return;
  var id = el.getAttribute('data-expand');
  var open = el.getAttribute('data-open') === '1';
  if (open) {
    catHideChildren(id);
    el.setAttribute('data-open', '0'); el.textContent = '＋'; el.classList.remove('open');
  } else {
    document.querySelectorAll('#cat-tbody tr[data-parent="' + id + '"]').forEach(function (tr) {
      tr.style.display = '';
    });
    el.setAttribute('data-open', '1'); el.textContent = '－'; el.classList.add('open');
  }
});
var _collapse = document.getElementById('cat-collapse-all');
var _expand = document.getElementById('cat-expand-all');
if (_collapse) _collapse.addEventListener('click', function () {
  document.querySelectorAll('#cat-tbody tr[data-depth]').forEach(function (tr) {
    if (parseInt(tr.getAttribute('data-depth'), 10) > 0) tr.style.display = 'none';
  });
  document.querySelectorAll('#cat-tbody .cat-toggle').forEach(function (t) { t.setAttribute('data-open', '0'); t.textContent = '＋'; t.classList.remove('open'); });
});
if (_expand) _expand.addEventListener('click', function () {
  document.querySelectorAll('#cat-tbody tr[data-depth]').forEach(function (tr) { tr.style.display = ''; });
  document.querySelectorAll('#cat-tbody .cat-toggle').forEach(function (t) { t.setAttribute('data-open', '1'); t.textContent = '－'; t.classList.add('open'); });
});
