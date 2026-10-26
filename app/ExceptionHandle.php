<?php
// +----------------------------------------------------------------------
// | 全局异常处理（v3.10）
// | 1) 前台 404 定制页：未登录访问不存在页面 → 渲染 template/{语言}/404.html 或内置 404 页
// | 2) 其他异常转交框架默认处理
// +----------------------------------------------------------------------
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var string[]
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     */
    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        // 404：渲染自定义 404 页
        if ($e instanceof HttpException && $e->getStatusCode() === 404) {
            return $this->render404();
        }
        // 其余交框架默认处理
        return parent::render($request, $e);
    }

    protected function render404(): Response
    {
        $status = 404;
        // 尝试当前语言模板目录下的 404.html（前台自定义 404 页）
        try {
            $lang = lang_code();
            $file = root_path() . 'template' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . '404.html';
            if (is_file($file)) {
                $html = \think\facade\View::fetch('', [], [], $file);
                return response((string)$html, $status);
            }
        } catch (\Throwable $e) {
            // 自定义模板异常时回退内置 404
        }
        // 后台路径 404 用简单页，避免套用前台模板
        if (strpos((string)request()->pathinfo(), 'admin') === 0) {
            return response($this->simple404('后台页面不存在或无权访问'), $status);
        }
        try {
            return response((string)\think\facade\View::fetch('error/404'), $status);
        } catch (\Throwable $e) {
            return response($this->simple404('页面不存在'), $status);
        }
    }

    protected function simple404(string $msg): string
    {
        $home = '/';
        return '<!DOCTYPE html><html lang="zh"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404 - 页面不存在</title><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,"Microsoft YaHei",sans-serif;background:#f5f6f8;height:100vh;display:flex;align-items:center;justify-content:center;color:#333}.w{text-align:center}.n{font-size:100px;font-weight:700;color:#2563eb;line-height:1}.t{font-size:20px;margin:12px 0 26px}.b{display:inline-block;padding:10px 30px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none}</style></head><body><div class="w"><div class="n">404</div><div class="t">' . $msg . '</div><a class="b" href="' . $home . '">返回首页</a></div></body></html>';
    }
}
