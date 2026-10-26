<?php
// +----------------------------------------------------------------------
// | 内容模型管理控制器（模型增删 + 模型字段配置）
// | 支持为每个模型自定义扩展字段：名称/标签/类型/选项/必填/排序
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\CmsModel;
use app\common\model\ModelField;
use app\common\model\Category;

class Model extends AdminBase
{
    /**
     * 模型列表
     */
    public function index()
    {
        $models = CmsModel::order('id', 'asc')->select();
        foreach ($models as &$m) {
            $extra = ModelField::where('model_id', $m['id'])->count();
            $m['default_count'] = count(self::defaultFields());
            $m['field_extra'] = $extra;
            $m['field_count'] = $m['default_count'] + $extra;
        }
        return view('model/index', ['models' => $models]);
    }

    /**
     * 添加 / 编辑模型
     */
    public function edit($id = 0)
    {
        $id = (int)$id;
        if (request()->isPost()) {
            $name        = trim(input('post.name', ''));
            $title       = trim(input('post.title', ''));
            $description = trim(input('post.description', ''));
            $listTpl     = trim(input('post.list_template', ''));
            $showTpl     = trim(input('post.show_template', ''));
            if ($title === '') return $this->fail('请填写模型名称');
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                return $this->fail('模型标识只能由字母开头、字母数字下划线组成');
            }
            $data = [
                'name' => $name, 'title' => $title, 'description' => $description,
                'list_template' => $listTpl, 'show_template' => $showTpl,
            ];
            if ($id) {
                $m = CmsModel::find($id);
                if (!$m) return $this->fail('模型不存在');
                // 标识唯一校验
                if (CmsModel::where('name', $name)->where('id', '<>', $id)->count()) {
                    return $this->fail('该模型标识已存在');
                }
                $m->save($data);
                AdminLog::record('model_edit', '编辑模型：' . $title);
            } else {
                if (CmsModel::where('name', $name)->count()) {
                    return $this->fail('该模型标识已存在');
                }
                CmsModel::create($data);
                AdminLog::record('model_add', '新增模型：' . $title);
            }
            return $this->ok('保存成功', (string)url('admin/model/index'));
        }
        $info = $id ? CmsModel::find($id) : null;
        return view('model/edit', ['info' => $info]);
    }

    /**
     * 删除模型（被栏目引用则禁止）
     */
    public function delete($id = 0)
    {
        $id = (int)$id;
        if (in_array($id, [1, 2, 3])) {
            return $this->fail('系统内置模型不可删除');
        }
        $m = CmsModel::find($id);
        if (!$m) return $this->fail('模型不存在');
        if (Category::where('model_id', $id)->count()) {
            return $this->fail('有栏目正在使用该模型，请先调整栏目模型');
        }
        if (\app\common\model\Content::where('model_id', $id)->count()) {
            return $this->fail('有内容使用该模型，请先转移或删除内容');
        }
        ModelField::where('model_id', $id)->delete();
        $m->delete();
        AdminLog::record('model_delete', '删除模型：' . $m['title']);
        return $this->ok('删除成功', (string)url('admin/model/index'));
    }

    /**
     * 字段管理（某模型下）
     */
    public function fields($model_id = 0)
    {
        $model = CmsModel::find((int)$model_id);
        if (!$model) return $this->fail('模型不存在');
        $fields = ModelField::where('model_id', $model['id'])->order('sort', 'asc')->order('id', 'asc')->select();
        // 系统默认字段（内置字段，随内容表自带，不可增删）
        return self::_fieldsView($model, $fields);
    }

    /**
     * 系统默认内置字段（随内容表自带，不可增删）
     */
    public static function defaultFields()
    {
        return [
            ['name' => 'title', 'label' => '标题', 'type' => 'text'],
            ['name' => 'ftitle', 'label' => '副标题', 'type' => 'text'],
            ['name' => 'author', 'label' => '作者', 'type' => 'text'],
            ['name' => 'source', 'label' => '来源', 'type' => 'text'],
            ['name' => 'pic', 'label' => '缩略图', 'type' => 'image'],
            ['name' => 'piclist', 'label' => '图片集(多图)', 'type' => 'gallery'],
            ['name' => 'keywords', 'label' => '关键词', 'type' => 'text'],
            ['name' => 'desc', 'label' => '描述', 'type' => 'textarea'],
            ['name' => 'content', 'label' => '正文', 'type' => 'rich'],
            ['name' => 'top', 'label' => '头条', 'type' => 'select'],
            ['name' => 'is_recommend', 'label' => '推荐', 'type' => 'select'],
            ['name' => 'is_hot', 'label' => '热门', 'type' => 'select'],
            ['name' => 'tag', 'label' => '标签', 'type' => 'text'],
            ['name' => 'sort', 'label' => '排序', 'type' => 'number'],
            ['name' => 'update_time', 'label' => '发布时间', 'type' => 'number'],
            ['name' => 'add_time', 'label' => '建立时间', 'type' => 'number'],
            ['name' => 'status', 'label' => '状态(发布/草稿)', 'type' => 'select'],
        ];
    }

    /**
     * 字段管理视图
     */
    private function _fieldsView($model, $fields)
    {
        $defaultFields = self::defaultFields();
        return view('model/fields', ['model' => $model, 'fields' => $fields, 'defaultFields' => $defaultFields]);
    }

    /**
     * 字段增改
     */
    public function field_edit($id = 0)
    {
        $id = (int)$id;
        if (request()->isPost()) {
            $modelId = (int)input('post.model_id', 0);
            $name    = trim(input('post.name', ''));
            $label   = trim(input('post.label', ''));
            $type    = input('post.type', 'text');
            $options = input('post.options', '');
            $required= (int)input('post.required', 0);
            $sort    = (int)input('post.sort', 100);

            if (!$modelId) return $this->fail('缺少模型');
            if ($label === '') return $this->fail('请填写字段标签');
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                return $this->fail('字段名只能由字母开头、字母数字下划线组成');
            }
            if (!isset(ModelField::TYPES[$type])) return $this->fail('字段类型不合法');
            // 下拉/单选/多选需选项
            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                if (trim($options) === '') return $this->fail('该类型请填写选项（每行一个）');
                $optionsArr = array_values(array_filter(array_map('trim', explode("\n", $options))));
                $options = json_encode($optionsArr, JSON_UNESCAPED_UNICODE);
            } else {
                $options = '';
            }
            $data = [
                'model_id' => $modelId, 'name' => $name, 'label' => $label, 'type' => $type,
                'options' => $options, 'required' => $required, 'sort' => $sort,
            ];
            if ($id) {
                $f = ModelField::find($id);
                if (!$f) return $this->fail('字段不存在');
                if (ModelField::where('model_id', $modelId)->where('name', $name)->where('id', '<>', $id)->count()) {
                    return $this->fail('该模型下字段名已存在');
                }
                $f->save($data);
                AdminLog::record('field_edit', '编辑字段：' . $label);
            } else {
                if (ModelField::where('model_id', $modelId)->where('name', $name)->count()) {
                    return $this->fail('该模型下字段名已存在');
                }
                ModelField::create($data);
                AdminLog::record('field_add', '新增字段：' . $label);
            }
            return $this->ok('保存成功', (string)url('admin/model/fields/model_id/' . $modelId));
        }
        $field = $id ? ModelField::find($id) : null;
        $model = CmsModel::find((int)input('get.model_id', $field ? $field['model_id'] : 0));
        return view('model/field_edit', ['field' => $field, 'model' => $model, 'modelId' => $model ? $model['id'] : 0, 'types' => ModelField::TYPES]);
    }

    /**
     * 删除字段
     */
    public function field_delete($id = 0)
    {
        $f = ModelField::find((int)$id);
        if (!$f) return $this->fail('字段不存在');
        $modelId = $f['model_id'];
        $f->delete();
        AdminLog::record('field_delete', '删除字段：' . $f['label']);
        return $this->ok('删除成功', (string)url('admin/model/fields/model_id/' . $modelId));
    }
}
