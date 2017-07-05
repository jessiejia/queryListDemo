<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once './lib/queryMulti.php';

use QL\QueryList;

/**
 * 从51job爬公司分布,方便买房子和租房子选址
 * 结果文件上传到百度地图做麻点
 * 20170701
 */
class QueryListMulti51job extends QueryListMulti
{

    /**
     * 获取urls下标追加到待采集列表
     */
    public function getNextPages($html)
    {
        $rules = [
            'url' => ['', 'href'],
        ];
        $range = '.dw_page .p_in ul li>a';
        $urls = QueryList::Query($html, $rules, $range)/*没有用setQuery的方式,因为QueryList是单例模式,success中嵌套爬子页后html变成子页*/
            ->getData(function ($item) {
                return $item['url'];
            });
        return $urls;
    }


    public function success($html)
    {
        $rules = array(
            'jobname' => ['.t1 span a', 'text'],
            'company' => array('.t2 a', 'text'),
            'area' => array('.t3', 'text'),
            'income' => array('.t4', 'text'),
            'coid' => ['.t2 a', 'href'],
            'joburl' => ['.t1 span a', 'href'],
        );
        $range = '#resultList .el:not(:first)';
        $ql = QueryList::Query($html, $rules, $range, 'UTF-8', 'gbk');
        $this->data = $ql->getData(function ($item) {
            if (preg_match('/(\d)+(?=\.html)/', $item['coid'], $matche)) {
                $item['coid'] = $matche[0];
            }
            $item = str_replace([',', ';'], '', $item);
            return $item;
        });

        /*嵌套抓子页坐标lat,lng,address*/
        foreach ($this->data as &$d) {
            $page = 'http://search.51job.com/jobsearch/bmap/map.php?coid=' . $d['coid'];
            $rules = array(
                'point' => ['script:first()', 'text'],
            );
            \QL\QueryList::Query($page, $rules, '', 'UTF-8', 'gbk')->getData(function ($item) use (&$d) {
                if ($str = array_pop($item)) {
                    if (preg_match('/(\{.*\})/i', $str, $matches) && $matches[0]) {
                        $item = json_decode(preg_replace('/([^{,"]+):(?![^"]+")/', '"$1":', $matches[0]), true);
                        $d = array_merge($d, $item);
                    }
                }
            });
        }
        $this->printCSV($this->data);
        $this->printBaiduMapData();

        return $this->getNextPages($html);
    }

    /**
     * 整理成百度地图麻点数据上传
     * title,address,longitude,latitude,coord_type,,text
     *
     */
    public function printBaiduMapData()
    {
        $_data = [];
        $titles = ['title', 'address', 'longitude', 'latitude', 'coord_type', '', 'income', 'jobname', 'url'];
        array_push($_data, $titles);
        foreach ($this->data as $k => $d) {
            array_push($_data, [
                $d['company'],
                isset($d['address'])?$d['address']:'',
                isset($d['lng'])?$d['lng']:'',
                isset($d['lat'])?$d['lat']:'',
                3,
                '',
                $d['income'],
                $d['jobname'],
                'http://jobs.51job.com/all/co' . $d['coid'] . '.html',
            ]);
        }
        $this->printCSV($_data, __FUNCTION__);
    }

}


(new QueryListMulti51job())->start('http://search.51job.com/list/020000,020000,0000,00,9,99,php,2,1.html?lang=c&stype=1&postchannel=0000&workyear=03&cotype=99&degreefrom=99&jobterm=99&companysize=99&lonlat=0%2C0&radius=-1&ord_field=0&confirmdate=9&fromType=17&dibiaoid=0&address=&line=&specialarea=00&from=&welfare=');












