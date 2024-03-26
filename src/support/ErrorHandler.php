<?php

declare(strict_types=1);

namespace mon\http\support;

use Throwable;
use mon\http\Response;
use Workerman\Protocols\Http\Session;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\ExceptionHandlerInterface;

/**
 * 异常错误处理
 *
 * @author  Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ErrorHandler implements ExceptionHandlerInterface
{
    /**
     * 上报异常信息
     *
     * @param Throwable $e  错误实例
     * @param RequestInterface $request  请求实例
     * @return mixed
     */
    public function report(Throwable $e, RequestInterface $request)
    {
        // TODO 记录日志
    }

    /**
     * 处理错误信息
     *
     * @param Throwable $e      错误实例
     * @param RequestInterface $request  请求实例
     * @param boolean $debug 是否调试模式     
     * @return Response
     */
    public function render(Throwable $e, RequestInterface $request, bool $debug = false): Response
    {
        $content = $debug ? $this->buildHTML($request, $e) : 'Server internal error';
        return new Response(500, [], $content);
    }

    /**
     * 生成错误展示页面
     *
     * @param RequestInterface $request
     * @param Throwable $e
     * @return string
     */
    protected function buildHTML(RequestInterface $request, Throwable $e): string
    {
        $code = $e->getCode();
        $name = get_class($e);
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();
        $trace = $e->getTrace();
        $source = $this->getSourceCode($e);

        // workerman特殊处理session
        $session = $request->session();
        if ($session instanceof Session) {
            $session->all();
        }

        $tables = [
            'GET Data'  => $request->get(),
            'POST Data' => $request->post(),
            'Files'     => $request->file(),
            'Cookies'   => $request->cookie(),
            'Session'   => $session,
        ];

        $headerTmp = $this->buildHead();
        $messgaeTmp = $this->buildMessgae($name, $code, $file, $line, $msg);
        $sourceTmp = $this->buildSource($source);
        $traceTmp = $this->buildTrace($file, $line, $trace);
        $tableTmp = $this->buildTable($tables);
        $jsTmp = $this->buildJS($line);

        $html = <<<HTMLTMP
<!DOCTYPE html>
<html>
{$headerTmp}
<body>
    <div class="exception">
        {$messgaeTmp}
        {$sourceTmp}
        {$traceTmp}
        {$tableTmp}
    </div>
    {$jsTmp}
</body>
</html>
HTMLTMP;

        return $html;
    }

    /**
     * 生成表格
     *
     * @param array $tables
     * @return string
     */
    protected function buildTable(array $tables = []): string
    {
        if (empty($tables)) {
            return '';
        }

        $str = '';
        foreach ($tables as $label => $value) {
            $tbody = '';
            if (empty($value)) {
                $tbody = '<caption>' . $label . '<small>Empty</small></caption>';
            } else {
                $tbody = '<caption>' . $label . '</caption>';
                $tr = '';
                foreach ($value as $key => $val) {
                    $tr .= '<tr><td>' . htmlentities($key) . '</td>';
                    $td = '';
                    if (is_array($val) || is_object($val)) {
                        $td = htmlentities(json_encode($val, JSON_PRETTY_PRINT));
                    } else if (is_bool($val)) {
                        $td = $val ? 'true' : 'false';
                    } else if (is_scalar($val)) {
                        $td = htmlentities($val);
                    } else {
                        $td = 'Resource';
                    }
                    $tr .= '<td>' . $td . '</td></tr>';
                }
                $tbody .= $tr;
            }

            $str .= '<table>' . $tbody . '</table>';
        }

        $html = <<<TABLE
<div class="exception-var">
    <h2>Environment Variables</h2>
    {$str}
</div>
TABLE;
        return $html;
    }

    /**
     * 生成调用堆栈
     *
     * @param string $file
     * @param integer $line
     * @param array $trace
     * @return string
     */
    protected function buildTrace(string $file, int $line, array $trace): string
    {
        $title = sprintf('in %s', $this->parse_file($file, $line));
        $str = '';
        foreach ($trace as $value) {
            $funStr = '';
            if ($value['function']) {
                $funStr = sprintf(
                    'at %s%s%s(%s)',
                    isset($value['class']) ? $this->parse_class($value['class']) : '',
                    isset($value['type'])  ? $value['type'] : '',
                    $value['function'],
                    isset($value['args']) ? $this->parse_args($value['args']) : ''
                );
            }
            $lineStr = '';
            if (isset($value['file']) && isset($value['line'])) {
                $lineStr = sprintf(' in %s', $this->parse_file($value['file'], $value['line']));
            }

            $str .= '<li>' . $funStr . $lineStr . '</li>';
        }

        $html = <<<TRACE
<div class="trace">
    <h2>Call Stack</h2>
    <ol><li>{$title}</li>{$str}</ol>
</div>
TRACE;

        return $html;
    }

    /**
     * 生成错误代码片段
     *
     * @param array $source
     * @return string
     */
    protected function buildSource(array $source = []): string
    {
        if (empty($source)) {
            return '';
        }

        $str = '';
        foreach ($source['source'] as $key => $value) {
            $str .= '<li class="line-' . ($key + $source['first']) . '"><code>' . htmlentities($value) . '</code></li>';
        }

        $html = <<<SOURCE
<div class="source-code">
    <pre class="prettyprint lang-php">
        <ol start="{$source['first']}">{$str}</ol>
    </pre>
</div>
SOURCE;

        return $html;
    }

    /**
     * 生成错误信息
     *
     * @param string $name
     * @param integer $code
     * @param string $file
     * @param integer $line
     * @param string $message
     * @return string
     */
    protected function buildMessgae(string $name, int $code, string $file, int $line, string $message): string
    {
        $infoStr = sprintf('%s in %s', $this->parse_class($name), $this->parse_file($file, $line));
        $msgStr = nl2br(htmlentities($message, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5));

        $html = <<<MESSAGE
<div class="message">
    <div class="info">
        <div><h2>[{$code}]&nbsp;{$infoStr}</h2>
        </div><div><h1>{$msgStr}</h1></div>
    </div>
</div>
MESSAGE;
        return $html;
    }

    /**
     * 生成JS脚本
     *
     * @param integer $line
     * @return string
     */
    protected function buildJS(int $line): string
    {
        return <<<SCRIPT
<script>
var LINE = {$line};

function $(selector, node) {
    var elements;

    node = node || document;
    if (document.querySelectorAll) {
        elements = node.querySelectorAll(selector);
    } else {
        switch (selector.substr(0, 1)) {
            case '#':
                elements = [node.getElementById(selector.substr(1))];
                break;
            case '.':
                if (document.getElementsByClassName) {
                    elements = node.getElementsByClassName(selector.substr(1));
                } else {
                    elements = get_elements_by_class(selector.substr(1), node);
                }
                break;
            default:
                elements = node.getElementsByTagName();
        }
    }
    return elements;

    function get_elements_by_class(search_class, node, tag) {
        var elements = [],
            eles,
            pattern = new RegExp('(^|\\s)' + search_class + '(\\s|$)');

        node = node || document;
        tag = tag || '*';

        eles = node.getElementsByTagName(tag);
        for (var i = 0; i < eles.length; i++) {
            if (pattern.test(eles[i].className)) {
                elements.push(eles[i])
            }
        }

        return elements;
    }
}

$.getScript = function(src, func) {
    var script = document.createElement('script');

    script.async = 'async';
    script.src = src;
    script.onload = func || function() {};

    $('head')[0].appendChild(script);
}

;
(function() {
    var files = $('.toggle');
    var ol = $('ol', $('.prettyprint')[0]);
    var li = $('li', ol[0]);

    // 短路径和长路径变换
    for (var i = 0; i < files.length; i++) {
        files[i].ondblclick = function() {
            var title = this.title;

            this.title = this.innerHTML;
            this.innerHTML = title;
        }
    }

    // 设置出错行
    var err_line = $('.line-' + LINE, ol[0])[0];
    err_line.className = err_line.className + ' line-error';

    $.getScript('//cdn.bootcss.com/prettify/r298/prettify.min.js', function() {
        prettyPrint();

        // 解决Firefox浏览器一个很诡异的问题
        // 当代码高亮后，ol的行号莫名其妙的错位
        // 但是只要刷新li里面的html重新渲染就没有问题了
        if (window.navigator.userAgent.indexOf('Firefox') >= 0) {
            ol[0].innerHTML = ol[0].innerHTML;
        }
    });

})();
</script>
SCRIPT;
    }

    /**
     * 生成HTMl头部信息
     *
     * @param string $title 标题
     * @return string
     */
    protected function buildHead(string $title = '系统发生错误'): string
    {
        return <<<HEAD
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <meta name="robots" content="noindex,nofollow" />
    <style>
        body{color:#333;font:16px Verdana,Helvetica Neue,helvetica,Arial,Microsoft YaHei,sans-serif;margin:0;padding: 20px}
        h1{margin:10px 0 0;font-size:28px;font-weight:500;line-height:32px}
        h2{color:#4288ce;font-weight:400;padding:6px 0;margin:6px 0 0;font-size:18px;border-bottom:1px solid #eee}
        h3{margin:12px;font-size:16px;font-weight:700}
        abbr{cursor:help;text-decoration:underline;text-decoration-style:dotted}
        a{color:#868686;cursor:pointer}
        a:hover{text-decoration:underline}
        .line-error{background:#f8cbcb}
        .echo table{width:100%}
        .echo pre{padding:16px;overflow:auto;font-size:85%;line-height:1.45;background-color:#f7f7f7;border:0;border-radius:3px;font-family:Consolas,Liberation Mono,Menlo,Courier,monospace}
        .echo pre>pre{padding:0;margin:0}
        .exception{margin-top:20px}
        .exception .message{padding:12px;border:1px solid #ddd;border-bottom:0;line-height:18px;font-size:16px;border-top-left-radius:4px;border-top-right-radius:4px;font-family:Consolas,Liberation Mono,Courier,Verdana,微软雅黑}
        .exception .code{float:left;text-align:center;color:#fff;margin-right:12px;padding:16px;border-radius:4px;background:#999}
        .exception .source-code{padding:6px;border:1px solid #ddd;background:#f9f9f9;overflow-x:auto}
        .exception .source-code pre{margin:0}
        .exception .source-code pre ol{margin:0;color:#4288ce;display:block;min-width:100%;box-sizing:border-box;font-size:14px;font-family:Century Gothic,Consolas,Liberation Mono,Courier,Verdana;padding-left:40px}
        .exception .source-code pre li{border-left:1px solid #ddd;height:18px;line-height:18px}
        .exception .source-code pre code{color:#333;height:100%;display:inline-block;border-left:1px solid #fff}
        .exception .source-code pre code,.exception .trace{font-size:14px;font-family:Consolas,Liberation Mono,Courier,Verdana,微软雅黑}
        .exception .trace{padding:6px;border:1px solid #ddd;border-top:0;line-height:16px}
        .exception .trace ol{margin:12px}
        .exception .trace ol li{padding:2px 4px}
        .exception div:last-child{border-bottom-left-radius:4px;border-bottom-right-radius:4px}
        .exception-var table{width:100%;margin:12px 0;box-sizing:border-box;table-layout:fixed;word-wrap:break-word}
        .exception-var table caption{text-align:left;font-size:16px;font-weight:700;padding:6px 0}
        .exception-var table caption small{font-weight:300;display:inline-block;margin-left:10px;color:#ccc}
        .exception-var table tbody{font-size:13px;font-family:Consolas,Liberation Mono,Courier,微软雅黑}
        .exception-var table td{padding:2px 6px;vertical-align:top;word-break:break-all;}
        .exception-var table td:first-child{width:28%;font-weight:700;white-space:nowrap}
        .exception-var table td pre{margin:0}
        .copyright{margin-top:24px;padding:12px 0;border-top:1px solid #eee}
        pre.prettyprint .pln{color:#000}
        pre.prettyprint .str{color:#080}
        pre.prettyprint .kwd{color:#008}
        pre.prettyprint .com{color:#800}
        pre.prettyprint .typ{color:#606}
        pre.prettyprint .lit{color:#066}
        pre.prettyprint .clo,pre.prettyprint .opn,pre.prettyprint .pun{color:#660}
        pre.prettyprint .tag{color:#008}
        pre.prettyprint .atn{color:#606}
        pre.prettyprint .atv{color:#080}
        pre.prettyprint .dec,pre.prettyprint .var{color:#606}
        pre.prettyprint .fun{color:red}
    </style>
</head>
HEAD;
    }

    /**
     * 解析类名
     *
     * @param string $name
     * @return string
     */
    protected function parse_class(string $name): string
    {
        $names = explode('\\', $name);
        return '<abbr title="' . $name . '">' . end($names) . '</abbr>';
    }

    /**
     * 解析文件
     *
     * @param string $file
     * @param integer $line
     * @return string
     */
    protected function parse_file(string $file, int $line): string
    {
        return '<a class="toggle" title="' . "{$file} line {$line}" . '">' . basename($file) . " line {$line}" . '</a>';
    }

    /**
     * 解析参数
     *
     * @param array $args
     * @return string
     */
    protected function parse_args(array $args): string
    {
        $result = [];
        foreach ($args as $key => $item) {
            switch (true) {
                case is_object($item):
                    $value = sprintf('<em>object</em>(%s)', $this->parse_class(get_class($item)));
                    break;
                case is_array($item):
                    if (count($item) > 3) {
                        $value = sprintf('[%s, ...]', $this->parse_args(array_slice($item, 0, 3)));
                    } else {
                        $value = sprintf('[%s]', $this->parse_args($item));
                    }
                    break;
                case is_string($item):
                    if (strlen($item) > 20) {
                        $value = sprintf(
                            '\'<a class="toggle" title="%s">%s...</a>\'',
                            htmlentities($item),
                            htmlentities(substr($item, 0, 20))
                        );
                    } else {
                        $value = sprintf("'%s'", htmlentities($item));
                    }
                    break;
                case is_int($item):
                case is_float($item):
                    $value = $item;
                    break;
                case is_null($item):
                    $value = '<em>null</em>';
                    break;
                case is_bool($item):
                    $value = '<em>' . ($item ? 'true' : 'false') . '</em>';
                    break;
                case is_resource($item):
                    $value = '<em>resource</em>';
                    break;
                default:
                    $value = htmlentities(str_replace("\n", '', var_export(strval($item), true)));
                    break;
            }

            $result[] = is_int($key) ? $value : "'{$key}' => {$value}";
        }

        return implode(', ', $result);
    }

    /**
     * 获取出错文件内容
     * 获取错误的前9行和后9行
     * 
     * @param  Throwable $e
     * @return array 错误文件内容
     */
    protected function getSourceCode(Throwable $e): array
    {
        // 读取前9行和后9行
        $line  = $e->getLine();
        $first = ($line - 9 > 0) ? $line - 9 : 1;

        try {
            $contents = file($e->getFile());
            $source = [
                'first'  => $first,
                'source' => array_slice($contents, $first - 1, 19),
            ];
        } catch (Throwable $e) {
            $source = [];
        }

        return $source;
    }
}
