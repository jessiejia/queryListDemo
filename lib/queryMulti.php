<?php
require_once __DIR__ . '/vendor/autoload.php';

use QL\QueryList;


abstract class QueryListMulti
{

    public $startUrl = '';
    private $_baseUrl = '';
    public $todoUrls = [];
    public $finishUrls = [];
    private $doingUrls = [];

    private function _prepareStartUrl($startUrl)
    {
        $this->startUrl = $startUrl;

        if (($t = parse_url($startUrl)) && !empty($t['host'])) {
            $this->_baseUrl = $t['host'];
        } else {
            throw new Exception('请设置初始url');
        }
        $this->todoUrls = [md5($startUrl) => $startUrl];
    }

    /**
     * 从列表某页开始爬
     */

    public function start($startUrl = '')
    {
        $this->_prepareStartUrl($startUrl);
        QueryList::setLog('./ql.'.date('Ymd').'.log');
        self::log('==========start=========');
        do {
            $this->doingUrls = $this->todoUrls;
            $this->todoUrls = [];
            $this->_crwal($this->doingUrls, array(&$this, 'success'), array(&$this, 'error'));
            $this->finishUrls += $this->doingUrls;
        } while (!empty($this->todoUrls));

        self::log('=======finish========');

    }

    /**
     * @param $html
     * @return array urls
     */
    abstract public function success($html);

    /**
     * 读取urls,交给success和error解析
     * @array $urls
     * @function $success
     * @function null $error
     */
    private function _crwal($urls, $successFun)
    {
        $cm = QueryList::run('Multi', [//待采集链接集合
            'list' => array_values($urls),
            'curl' => ['opt' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_AUTOREFERER => true,
            ),
                //设置线程数
                'maxThread' => 100,
                //设置最大尝试数
                'maxTry' => 3],
            //不自动开始线程，默认自动开始
            'start' => false,
            'success' => function ($html) use ($successFun) {
                self::log($html['info']['url']);
                $this->addNextPages(call_user_func($successFun, $html['content']));
            },
            'error' => function ($msg) {
                throw new Exception($msg);
            }]);
        $cm->start();
    }

    /**
     * 将newurls追加到todoUrls
     * @array $newurls
     * @return
     */
    private function addNextPages($newurls)
    {
        if (is_array($newurls) && ($newurls = array_filter($newurls)) && $newurls) {
            if (($t = parse_url(current($newurls))) && empty($t['host'])) {
                $newurls = array_map(function ($u) {
                    return $this->_baseUrl . $u;
                }, $newurls);
            }
            array_map(function ($u) {
                $k = md5($u);
                if (!isset($this->doingUrls[$k]) && !isset($this->finishUrls[$k])) {
                    $this->todoUrls += [$k=> $u];
                }
            }, $newurls);
        }
    }


    /**
     * 二维数组输出到CSV文件
     * @array $data
     * @param string $file
     */
    private $_datetime = '';
    protected function printCSV($data, $file = '')
    {
        if(is_array($data)) {
            $file = ($file ?: 'printdata') . ($this->_datetime = $this->_datetime ?: date('YmdHis')) . '.csv';
            $myfile = fopen($file, "a+") or die("Unable to open file!");
            foreach ($data as $line) {
                $txt = join(',', $line) . "\n";
                echo $txt;
                fwrite($myfile, $txt);
            }
            fclose($myfile);
        }
    }
    /**
     * 貌似有内置的log
     * 自己如何写不同的log等级
     */
    public static function log($message, $level='info')
    {
        QueryList::$logger->$level($message);
    }

}
