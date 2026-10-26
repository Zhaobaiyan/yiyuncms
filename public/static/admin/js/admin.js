/* ==========================================================
   易云CMS 后台公共 JS
   ========================================================== */
(function () {
  'use strict';

  // ---------- DOM 就绪工具（admin.js 在 body 顶部/iframe 页 head 上下文加载时 body 可能尚不存在） ----------
  function domReady(fn) {
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); }
    else { fn(); }
  }

  // ---------- Toast 提示 ----------
  var toastBox = null;
  domReady(function () {
    toastBox = document.getElementById('toast');
    if (!toastBox) {
      toastBox = document.createElement('div');
      toastBox.id = 'toast';
      document.body.appendChild(toastBox);
    }
  });
  function toast(msg, type) {
    var el = document.createElement('div');
    el.className = 'toast ' + (type || 'ok');
    el.textContent = msg;
    if (!toastBox) {
      domReady(function () {
        toastBox = document.getElementById('toast') || (function () {
          var b = document.createElement('div'); b.id = 'toast'; document.body.appendChild(b); return b;
        })();
        toastBox.appendChild(el);
      });
    } else {
      toastBox.appendChild(el);
    }
    setTimeout(function () { el.remove(); }, 2600);
  }

  // ---------- 通用 AJAX 提交 ----------
  window.yy = {
    toast: toast,
    post: function (url, data, opts) {
      opts = opts || {};
      var isFd = typeof FormData !== 'undefined' && data instanceof FormData;
      var fd = isFd ? data : new FormData();
      if (!isFd) {
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
      }
      fetch(url, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.code === 1) {
            toast(res.msg || '操作成功', 'ok');
            if (res.url) {
              setTimeout(function () { location.href = res.url; }, opts.delay || 700);
            } else if (opts.callback) {
              opts.callback(res);
            }
          } else {
            toast(res.msg || '操作失败', 'err');
            if (opts.failcb) opts.failcb(res);
          }
        })
        .catch(function (e) { toast('网络错误', 'err'); });
    },
    // 右上角"静态生成"：一键生成全部语言全站静态文件
    buildStatic: function () {
      window.yy.confirm('生成全站静态文件（全部语言）？', function () {
        window.yy.post('/admin/static_page/all', { lang: 'all' }, { callback: function (res) {
          if (res && res.code === 1) { toast(res.msg, 'ok'); }
          else { toast((res && res.msg) || '生成失败，请检查日志', 'err'); }
        } });
      });
    },
    // 确认弹窗
    confirm: function (msg, onOk) {      var mask = document.getElementById('modal-confirm');
      if (!mask) {
        mask = document.createElement('div');
        mask.className = 'modal-mask';
        mask.id = 'modal-confirm';
        mask.innerHTML =
          '<div class="modal">' +
          '<div class="m-head">提示</div>' +
          '<div class="m-body"><div class="cf-msg">确认删除？</div></div>' +
          '<div class="m-foot">' +
          '<button class="btn" data-a="cancel">取消</button>' +
          '<button class="btn danger" data-a="ok">确定</button>' +
          '</div></div>';
        mask.addEventListener('click', function (e) {
          var a = e.target.getAttribute('data-a');
          if (a === 'cancel' || e.target === mask) hide();
          if (a === 'ok') { hide(); if (mask._onOk) mask._onOk(); }
        });
        document.body.appendChild(mask);
      }
      mask.querySelector('.cf-msg').textContent = msg || '确认执行该操作？';
      mask._onOk = onOk;
      mask.classList.add('show');
      function hide() { mask.classList.remove('show'); }
    },
    // 跨语言复制弹窗
    copyLang: function (url, extra, opts) {
      opts = opts || {};
      extra = extra || {};
      var langs = window.yy.adminLangs || [];
      if (!langs.length) { toast('未配置其他语言', 'err'); return; }
      var html = '<div class="lab">目标语言</div><select id="cl-lang" style="width:100%;padding:9px 11px;border:1px solid #e2e8f0;border-radius:8px">';
      langs.forEach(function (l) {
        html += '<option value="' + l.code + '">' + l.name + '（' + l.code + '）</option>';
      });
      html += '</select>';
      if (opts.extraHtml) html += opts.extraHtml;
      var mask = document.createElement('div');
      mask.className = 'modal-mask show';
      mask.innerHTML =
        '<div class="modal">' +
        '<div class="m-head">' + (opts.title || '复制到其他语言') + '</div>' +
        '<div class="m-body">' + html + '</div>' +
        '<div class="m-foot">' +
        '<button class="btn" data-a="cancel">取消</button>' +
        '<button class="btn primary" data-a="ok">复制</button>' +
        '</div></div>';
      mask.addEventListener('click', function (e) {
        var a = e.target.getAttribute('data-a');
        if (a === 'cancel' || e.target === mask) { mask.remove(); return; }
        if (a === 'ok') {
          var data = Object.assign({ target_lang: mask.querySelector('#cl-lang').value }, extra);
          if (opts.build) data = opts.build(mask, data);
          mask.remove();
          yy.post(url, data, { url: opts.url || '' });
        }
      });
      document.body.appendChild(mask);
    },
    // 图片选择器：弹窗上传/选择图片，选中后回调(url)
    pickImage: function (onPick) {
      var mask = document.createElement('div');
      mask.className = 'modal-mask show';
      mask.innerHTML =
        '<div class="modal" style="width:760px;max-width:94vw">' +
        '<div class="m-head">选择图片 <span class="close" data-a="close" style="float:right;cursor:pointer;opacity:.6">✕</span></div>' +
        '<div class="m-body">' +
        '<div class="pi-upload" style="border:1px dashed #cbd5e1;border-radius:8px;padding:14px;text-align:center;margin-bottom:12px">' +
        '  <input type="file" id="pi-file" accept="image/*" style="display:none">' +
        '  <button class="btn primary" id="pi-btn">+ 上传新图片</button>' +
        '  <div id="pi-uploading" style="display:none;margin-top:8px;color:#64748b">上传中…</div>' +
        '  <div id="pi-uploaded" style="display:none;margin-top:10px">' +
        '    <img id="pi-uploaded-img" style="max-height:120px;border-radius:6px">' +
        '    <button class="btn sm primary" id="pi-uploaded-use" style="margin-left:10px">使用此图</button>' +
        '  </div>' +
        '</div>' +
        '<div class="pi-title" style="font-weight:600;margin-bottom:6px;color:#1e293b">已上传图片（点击选择）</div>' +
        '<div id="pi-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:8px;max-height:300px;overflow:auto">' +
        '  <div style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:20px">加载中…</div>' +
        '</div>' +
        '</div></div>';
      document.body.appendChild(mask);

      var grid = mask.querySelector('#pi-grid');
      var uploadedUrl = '';
      function close() { mask.remove(); }
      mask.addEventListener('click', function (e) {
        if (e.target === mask || e.target.getAttribute('data-a') === 'close') close();
      });

      // 上传
      mask.querySelector('#pi-btn').addEventListener('click', function () {
        mask.querySelector('#pi-file').click();
      });
      mask.querySelector('#pi-file').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var up = mask.querySelector('#pi-uploading');
        up.style.display = '';
        var fd = new FormData();
        fd.append('file', file);
        fetch('/admin/upload/index', { method: 'POST', body: fd })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            up.style.display = 'none';
            if (res.errno === 0) {
              uploadedUrl = res.data.url;
              mask.querySelector('#pi-uploaded-img').src = uploadedUrl;
              mask.querySelector('#pi-uploaded').style.display = '';
            } else {
              toast(res.message || '上传失败', 'err');
            }
          })
          .catch(function () { up.style.display = 'none'; toast('上传失败', 'err'); });
      });
      mask.querySelector('#pi-uploaded-use').addEventListener('click', function () {
        if (uploadedUrl) { onPick(uploadedUrl); close(); }
      });

      // 图片库
      fetch('/admin/upload/images')
        .then(function (r) { return r.json(); })
        .then(function (res) {
          var list = (res && res.errno === 0 && res.data.list) || [];
          if (!list.length) {
            grid.innerHTML = '<div style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:20px">暂无图片，请先上传</div>';
            return;
          }
          grid.innerHTML = '';
          list.forEach(function (img) {
            var box = document.createElement('div');
            box.style.cssText = 'border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;cursor:pointer;position:relative;background:#fff';
            box.innerHTML = '<img src="' + img.url + '" style="width:100%;height:70px;object-fit:cover;display:block">';
            box.title = img.url;
            box.addEventListener('click', function () { onPick(img.url); close(); });
            grid.appendChild(box);
          });
        })
        .catch(function () {
          grid.innerHTML = '<div style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:20px">图片列表加载失败</div>';
        });
    },
    // 多图选择器：本地文件可多选批量上传 + 图片库点击多选，回调(urls数组)
    pickImages: function (onPick) {
      var mask = document.createElement('div');
      mask.className = 'modal-mask show';
      mask.innerHTML =
        '<div class="modal" style="width:820px;max-width:94vw">' +
        '<div class="m-head">选择图片（可多选） <span class="close" data-a="close" style="float:right;cursor:pointer;opacity:.6">✕</span></div>' +
        '<div class="m-body">' +
        '<div class="pi-upload" style="border:1px dashed #cbd5e1;border-radius:8px;padding:14px;text-align:center;margin-bottom:12px">' +
        '  <input type="file" id="pi-files" accept="image/*" multiple style="display:none">' +
        '  <button class="btn primary" id="pi-btn">+ 上传新图片（可多选）</button>' +
        '  <span id="pi-uploading" style="display:none;margin-left:10px;color:#64748b">上传中…</span>' +
        '</div>' +
        '<div class="pi-title" style="font-weight:600;margin-bottom:6px;color:#1e293b">已上传图片（点击多选，底部确认）</div>' +
        '<div id="pi-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:8px;max-height:280px;overflow:auto">' +
        '  <div style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:20px">加载中…</div>' +
        '</div>' +
        '<div style="margin-top:12px;display:flex;justify-content:flex-end;align-items:center;gap:10px">' +
        '  <span id="pi-count" style="color:#64748b;font-size:13px">已选 0 张</span>' +
        '  <button class="btn sm primary" id="pi-use">使用选中图片</button>' +
        '</div>' +
        '</div></div>';
      document.body.appendChild(mask);

      var grid = mask.querySelector('#pi-grid');
      var selected = {};
      function countSel() { return Object.keys(selected).length; }
      function updCount() { mask.querySelector('#pi-count').textContent = '已选 ' + countSel() + ' 张'; }
      function close() { mask.remove(); }
      mask.addEventListener('click', function (e) {
        if (e.target === mask || e.target.getAttribute('data-a') === 'close') close();
      });

      // 本地多选批量上传：全部完成后一次性回调 urls
      mask.querySelector('#pi-btn').addEventListener('click', function () {
        mask.querySelector('#pi-files').click();
      });
      mask.querySelector('#pi-files').addEventListener('change', function () {
        var files = Array.prototype.slice.call(this.files);
        if (!files.length) return;
        var up = mask.querySelector('#pi-uploading');
        up.style.display = '';
        var done = 0, urls = [], fail = 0;
        files.forEach(function (file) {
          var fd = new FormData();
          fd.append('file', file);
          fetch('/admin/upload/index', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (res.errno === 0) { urls.push(res.data.url); } else { fail++; }
            })
            .catch(function () { fail++; })
            .then(function () {
              done++;
              if (done === files.length) {
                up.style.display = 'none';
                if (urls.length) { onPick(urls); close(); }
                else if (fail) { toast('上传失败', 'err'); }
              }
            });
        });
      });

      // 图片库多选
      fetch('/admin/upload/images')
        .then(function (r) { return r.json(); })
        .then(function (res) {
          var list = (res && res.errno === 0 && res.data.list) || [];
          if (!list.length) {
            grid.innerHTML = '<div style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:20px">暂无图片，请先上传</div>';
            return;
          }
          grid.innerHTML = '';
          list.forEach(function (img) {
            var box = document.createElement('div');
            box.style.cssText = 'border:2px solid #e2e8f0;border-radius:6px;overflow:hidden;cursor:pointer;position:relative;background:#fff';
            box.innerHTML = '<img src="' + img.url + '" style="width:100%;height:70px;object-fit:cover;display:block">';
            box.title = img.url;
            box.addEventListener('click', function () {
              if (selected[img.url]) { delete selected[img.url]; box.style.borderColor = '#e2e8f0'; }
              else { selected[img.url] = 1; box.style.borderColor = '#2563eb'; }
              updCount();
            });
            grid.appendChild(box);
          });
        })
        .catch(function () {
          grid.innerHTML = '<div style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:20px">图片列表加载失败</div>';
        });

      mask.querySelector('#pi-use').addEventListener('click', function () {
        var urls = Object.keys(selected);
        if (!urls.length) { toast('请先选择图片', 'err'); return; }
        onPick(urls); close();
      });
    },
    // 为图片输入框绑定“选择图片”按钮：inputEl 为文本输入框，btnEl 为触发按钮
    bindImagePicker: function (inputEl, btnEl) {
      if (!inputEl || !btnEl) return;
      var wrap = inputEl.parentElement;
      // 页面自身已带预览容器（如设置页 .img-preview / [data-img-prev]）则不干预，交给页面逻辑
      var hasOwnPreview = wrap && (wrap.querySelector('[data-img-prev]') || wrap.querySelector('.img-preview'));
      function showPreview(url) {
        if (!wrap || hasOwnPreview) return;
        var img = wrap.querySelector('img[data-img-prev]');
        if (!img) { img = wrap.querySelector('img'); }
        if (!img) {
          img = document.createElement('img');
          img.setAttribute('data-img-prev', '1');
          img.style.cssText = 'max-height:60px;border-radius:6px;margin:6px 0 0;vertical-align:middle;display:none;';
          wrap.appendChild(img);
        } else {
          img.setAttribute('data-img-prev', '1');
        }
        if (url) { img.src = url; img.style.display = 'inline-block'; }
        else { img.style.display = 'none'; }
      }
      btnEl.addEventListener('click', function () {
        window.yy.pickImage(function (url) {
          inputEl.value = url;
          inputEl.dispatchEvent(new Event('change'));
        });
      });
      inputEl.addEventListener('change', function () { showPreview(inputEl.value); });
      if (!hasOwnPreview) showPreview(inputEl.value);
    }
  };

  // ---------- 侧边栏子菜单切换 ----------
  domReady(function () {
    document.querySelectorAll('.group-sub-toggle').forEach(function (el) {
      el.addEventListener('click', function () {
        var sub = el.nextElementSibling;
        if (sub) { sub.style.display = sub.style.display === 'none' ? 'block' : 'none'; }
      });
    });
  });

  // ---------- 后台语言切换 ----------
  window.yy.langMenu = function () {
    var d = document.getElementById('lang-drop');
    if (d) d.classList.toggle('open');
  };
  window.yy.setLang = function (code) {
    var fd = new FormData();
    fd.append('code', code);
    fetch('/admin/setting/setLang', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        toast(res.msg || '已切换', 'ok');
        setTimeout(function () { location.reload(); }, 600);
      })
      .catch(function () { toast('网络错误', 'err'); });
  };

  // ---------- 表单确认 ----------
  domReady(function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
      f.addEventListener('submit', function (e) {
        var msg = f.getAttribute('data-confirm');
      if (!window._confirmFlag) {
        e.preventDefault();
        window.yy.confirm(msg, function () {
          window._confirmFlag = true;
          f.submit();
          setTimeout(function () { window._confirmFlag = false; }, 500);
        });
      }
    });
    });
  });

  // ==========================================================
  // v3.19 全平台自适应：侧边栏抽屉（小屏汉堡展开/收起 + 遮罩）
  // ==========================================================
  window.yy.toggleSidebar = function (force) {
    var isOpen = document.body.classList.contains('sidebar-open');
    var to = (typeof force === 'boolean') ? force : !isOpen;
    document.body.classList.toggle('sidebar-open', to);
  };
  // 小屏下点击导航项自动收起侧边栏
  document.querySelectorAll('.sidebar .nav a').forEach(function (a) {
    a.addEventListener('click', function () {
      if (window.innerWidth <= 992) window.yy.toggleSidebar(false);
    });
  });
  // 点按遮罩/按 Esc 关闭
  window.yy.toggleMenuGroup = function (el) {
    var g = el.closest('.nav-group');
    if (g) g.classList.toggle('open');
  };
  window.yy.expandAllMenu = function () {
    document.querySelectorAll('.nav-group').forEach(function (g) { g.classList.add('open'); });
  };
  window.yy.collapseAllMenu = function () {
    document.querySelectorAll('.nav-group').forEach(function (g) { g.classList.remove('open'); });
  };
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') window.yy.toggleSidebar(false);
  });
  // 窗口拉大到桌面尺寸时复位
  window.addEventListener('resize', function () {
    if (window.innerWidth > 992) window.yy.toggleSidebar(false);
  });
})();
