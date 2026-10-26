<?php
// +----------------------------------------------------------------------
// | 栏目模型（栏目字段模板）管理控制器
// | 模型本身不存数据，仅定义一组栏目自定义字段；
// | 栏目编辑页选择某栏目模型后自动继承模板字段，可填各自的值
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\CatModel as CatModelModel;
use app\common\model\CatModelField;
use think\facade\Db;

class CatModel extends AdminBase
{
    /**
     * 栏目模型列表
     */
    public function index()
    {
        $models = CatModelModel::order('id', 'asc')->select();
        foreach ($models as &$m) {
            $m['field_count'] = CatModelField::where('model_id', $m['id'])->count();
            $m['used_count']  = $m->usedCount();
        }
        return view('cat_model/index', ['models' => $models]);
    }

    /**
     * 添加 / 编辑栏目模型
     */
    public function edit($id = 0)
    {
        $id = (int)$id;
        if (request()->isPost()) {
            $name  = trim(input('post.name', ''));
            $title = trim(input('post.title', ''));
            if ($title === '') return $this->fail('请填写模型名称');
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                return $this->fail('模型标识只能由字母开头、字母数字下划线组成');
            }
            $data = ['name' => $name, 'title' => $title];
            if ($id) {
                $m = CatModelModel::find($id);
                if (!$m) return $this->fail('模型不存在');
                if (CatModelModel::where('name', $name)->where('id', '<>', $id)->count()) {
                    return $this->fail('该模型标识已存在');
                }
                $m->save($data);
                AdminLog::record('cat_model_edit', '编辑栏目模型：' . $title);
            } else {
                if (CatModelModel::where('name', $name)->count()) {
                    return $this->fail('该模型标识已存在');
                }
                CatModelModel::create($data);
                AdminLog::record('cat_model_add', '新增栏目模型：' . $title);
            }
            return $this->ok('保存成功', '/admin/cat_model/index');
        }
        $info = $id ? CatModelModel::find($id) : null;
        return view('cat_model/edit', ['info' => $info]);
    }

    /**
     * 删除栏目模型（被栏目引用则禁止）
     */
    public function delete($id = 0)
    {
        $id = (int)$id;
        $m = CatModelModel::find($id);
        if (!$m) return $this->fail('模型不存在');
        if ($m->usedCount() > 0) {
            return $this->fail('有栏目正在使用该栏目模型，请先在栏目管理中调整');
        }
        CatModelField::where('model_id', $id)->delete();
        $m->delete();
        AdminLog::record('cat_model_delete', '删除栏目模型：' . $m['title']);
        return $this->ok('删除成功', '/admin/cat_model/index');
    }

    /**
     * 字段管理（某栏目模型下）
     */
    public function fields($model_id = 0)
    {
        $model = CatModelModel::find((int)$model_id);
        if (!$model) return $this->fail('模型不存在');
        $fields = CatModelField::where('model_id', $model['id'])->order('sort', 'asc')->order('id', 'asc')->select();
        return view('cat_model/fields', ['model' => $model, 'fields' => $fields, 'types' => CatModelField::TYPES]);
    }

    /**
     * 某栏目模型的字段定义（栏目编辑页切换下拉时实时加载模板字段用）
     */
    public function fieldsJson($model_id = 0)
    {
        $model_id = (int)$model_id;
        if (!$model_id) return json(['code' => 1, 'data' => []]);
        $fields = CatModelField::where('model_id', $model_id)->order('sort', 'asc')->order('id', 'asc')->select();
        return json(['code' => 1, 'data' => $fields->toArray()]);
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
            $title   = trim(input('post.title', ''));
            $type    = input('post.type', 'text');
            $options = trim(input('post.options', ''));
            $sort    = (int)input('post.sort', 100);

            if (!$modelId) return $this->fail('缺少栏目模型');
            if ($title === '') return $this->fail('请填写字段名称');
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                return $this->fail('字段标识只能由字母开头、字母数字下划线组成');
            }
            if (!isset(CatModelField::TYPES[$type])) return $this->fail('字段类型不合法');
            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                if ($options === '') return $this->fail('该类型请填写选项（英文逗号分隔）');
            } else {
                $options = '';
            }
            $data = [
                'model_id' => $modelId, 'name' => $name, 'title' => $title,
                'type' => $type, 'options' => $options, 'sort' => $sort,
            ];
            if ($id) {
                $f = CatModelField::find($id);
                if (!$f) return $this->fail('字段不存在');
                if (CatModelField::where('model_id', $modelId)->where('name', $name)->where('id', '<>', $id)->count()) {
                    return $this->fail('该模型下字段标识已存在');
                }
                $f->save($data);
                AdminLog::record('cat_field_edit', '编辑栏目模型字段：' . $title);
            } else {
                if (CatModelField::where('model_id', $modelId)->where('name', $name)->count()) {
                    return $this->fail('该模型下字段标识已存在');
                }
                CatModelField::create($data);
                AdminLog::record('cat_field_add', '新增栏目模型字段：' . $title);
            }
            return $this->ok('保存成功', '/admin/cat_model/fields/model_id/' . $modelId);
        }
        $field = $id ? CatModelField::find($id) : null;
        $model = CatModelModel::find((int)input('get.model_id', $field ? $field['model_id'] : 0));
        return view('cat_model/field_edit', [
            'field' => $field, 'model' => $model,
            'modelId' => $model ? $model['id'] : 0,
            'types' => CatModelField::TYPES,
        ]);
    }

    /**
     * 删除字段
     */
    public function field_delete($id = 0)
    {
        $f = CatModelField::find((int)$id);
        if (!$f) return $this->fail('字段不存在');
        $modelId = $f['model_id'];
        $f->delete();
        AdminLog::record('cat_field_delete', '删除栏目模型字段：' . $f['title']);
        return $this->ok('删除成功', '/admin/cat_model/fields/model_id/' . $modelId);
    }
}
