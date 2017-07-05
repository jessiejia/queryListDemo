<?php

/**
 * 基于php的QueryList写的练手爬虫
 */
require_once __DIR__ . '/vendor/autoload.php';

/* 获取经纪人的名字\公司\电话 */
//待采集的目标页面,可以是url或者html文本
$page = 'http://shanghai.anjuke.com/tycoon/minhang/';

//采集字段规则
$rules = array(
    'name' => ['.jjr-title a', 'text'],
    'company' => ['.mg-top', 'html',],
    'phone' => ['.jjr-side', 'text'],
    'img' => ['.thumbnail', 'src'],
);
//按rang匹配的对象分组成数组,分别使用rules过滤,即每一个jjr-itemmod最终变成一个数组,如.list>ul>li
$rang = '.jjr-itemmod';

//采集
$ql = \QL\QueryList::Query($page, $rules, $rang);


/**
 * data
 */
$data = $ql->data;

print_r($data);

/**
 * getData()用于对返回数据data改造或下载图片等
 */
$data1 = $ql->getData(
    function ($item) {
        $item['img'] = 'http://xxx.com' . $item['img'];
        //download and save-img


        //多级采集(采集为多维数组)
        $item['company'] = \QL\QueryList::Query(
            $item['company'],
            array('company' => ['a:first', 'text'], 'depart' => ['a:last', 'text'])
        )->data;


        return $item;
    }
);


print_r($data1);

/**
 * 输出结果
 *
 *
 * [0] => Array
 * (
 *      [name] => 卢国华
 *      [company] => Array
 *      (
 *              [0] => Array
 *              (
 *                  [company] => 上海经纪人
 *                  [depart] => 上海经纪人锦绣江南一店3组
 *              )
 *      )
 *      [phone] => 13601928381
 *      [img] => http://xxx.comhttp://pic1.ajkimg.com/display/anjuke/1b33ddf3ee79bcb2370b0ba84ff08107/240x319x0x7/100x133.jpg
 * )
 *
 * [1] => Array
 * (
 *      [name] => 伍鑫
 *      [company] => Array
 *      (
 *          [0] => Array
 *          (
 *              [company] => 慧邦地产
 *              [depart] => 慧邦地产龙柏店
 *           )
 *      )
 *      [phone] => 13795399145
 *      [img] => http://xxx.comhttp://pic1.ajkimg.com/display/anjuke/ce748bdb6a539f8f1cf6e380b34d5526/457x608x15x0/100x133.jpg
 * )
 */


/**
 * setQuery()采集同一页面的区域字段
 */
$rules = array(
    'area' => ['', 'href'],
);
$data2 = $ql->setQuery($rules, '.items-list .elems-l:first>a:not(:first)')->data;

var_dump($data2);
/**
 * .elems-l:first>a:not(:first)  第一个elems-l的儿子们not-first
 * 输出结果
 *
 * array(19) {
 * [0] =>
 * array(1) {
 * 'area' =>
 * string(41) "http://shanghai.anjuke.com/tycoon/pudong/"
 * }
 * [1] =>
 * array(1) {
 * 'area' =>
 * NULL
 * }
 * [2] =>
 * array(1) {
 * 'area' =>
 * string(40) "http://shanghai.anjuke.com/tycoon/xuhui/"
 * }
 * [3] =>
 * array(1) {
 * 'area' =>
 * string(42) "http://shanghai.anjuke.com/tycoon/baoshan/"
 * }
 * [4] =>
 * array(1) {
 * 'area' =>
 * string(44) "http://shanghai.anjuke.com/tycoon/songjiang/"
 * }
 **/


