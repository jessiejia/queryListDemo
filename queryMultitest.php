<?php
require_once __DIR__ . '/vendor/autoload.php';

use QL\QueryList;



class QueryListMultiTest
{

    public $startUrl = '';
    private $_baseUrl = '';
    public $nextPages = [];
    public $finishPages = [];
    private $todoUrls = [];

    public function __construct($startUrl)
    {
        $this->startUrl = $startUrl;

        if (($t = parse_url($startUrl)) && !empty($t['host'])) {
            $this->_baseUrl = $t['host'];
        } else {
            $this->exception('请检查初始url');
        }
        $this->nextPages = [md5($startUrl) => $startUrl];
    }

    /**
     * 从列表某页开始爬
     */

    public function start()
    {
        $this->log('==start==');
        while (!empty($this->nextPages)) {
            $this->log("\n\n\n\n\n<<<<<<<<while<<<<<<<<", $this->nextPages);

            $this->todoUrls = $this->nextPages;
            $this->nextPages = [];
            $this->_crwal($this->todoUrls, array(&$this, "successPage"));
            $this->finishPages = array_merge($this->finishPages, $this->todoUrls);
        }

        $this->log('==finishPages==', $this->finishPages);

    }

    private function _crwal($urls, $success, $error=null)
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
            'success' => function ($html) use ($success) {
//                $this->log(,array_slice($html['info'],0,3));
                call_user_func($success, $html['content']);
            },
            'error' => function ($msg) {
                var_dump($msg);
                exit();
            }]);
        $cm->start();
    }

    /**
     * 获取urls下标追加到待采集列表
     */
    private function _getNextPages($ql)
    {
        $rules = [
            'url' => ['', 'href'],
        ];
        $range = '#wrapper .page_nav .pagenew li a';
        $urls = $ql->setQuery($rules, $range)
            ->getData(function ($item) {
                return $item['url'];
            });
        $this->addNextPages($urls);
    }

    public function addNextPages($newurls)
    {
        if (($newurls = array_filter($newurls)) && $newurls) {
            if (($t = parse_url(current($newurls))) && empty($t['host'])) {
                $newurls = array_map(function ($u) {
                    return $this->_baseUrl . $u;
                }, $newurls);
            }
            array_map(function ($u) {
                if (!isset($this->todoUrls[md5($u)]) && !isset($this->nextPages[md5($u)]) && !isset($this->finishPages[md5($u)])) {
                    $this->log('>>>>>>>>addNextPages>>>>>>>>>', [md5($u) => $u]);
                    $this->nextPages = array_merge($this->nextPages, [md5($u) => $u]);
                }
                return $u;
            }, $newurls);
        }
        return True;
    }


    public function successPage($html)
    {
        $rules = array(
            'title' => array('.block .h2>a', 'text'),
            'desc' => array('.block .memo p', 'text')
        );
        $range = '.cate_list>ul li';
        $ql = QueryList::Query($html, $rules, $range);
        $data = $ql->getData(function ($item) {
            return $item;
        });
        //saveDB....


        $this->_getNextPages($ql);
    }


    public function log()
    {
        $msgs = func_get_args();
        foreach ($msgs as $msg) {
            if (is_string($msg)) echo $msg . "\n";
            else var_dump($msg);
        }
    }

    public function exception()
    {
        $this->log(func_get_args());
        exit();
    }
}


(new QueryListMultiTest('http://cms.querylist.cc/plus/list.php?tid=15'))->start();
/**
 * 如何模拟登陆
 */
