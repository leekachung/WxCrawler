<?php
$start = time();

require "WxCrawler.php";
require "WxMainCrawler.php";

/** 
 * 通过公众号关键字爬取相关公众号列表
 */
$control_times = 0; //控制判断爬取内容次数flag

$main = new WxMainCrawler();

/**
 * 查询公众号列表 目前 并查询个公众号的最近十篇推文 （测试）
 * @var string
 */
$search_keyword = '华广';
$search_wxlist_result = $main->searchWxList($search_keyword);

//轮询爬虫 连续三次爬取不到内容暂停3s
while (!$search_wxlist_result) {
	//当爬取次数超过三次后 还未能爬取到内容 终止爬虫
	++$control_times;
	if ($control_times > 3) {
		echo "优化程序，请稍后\n";
		sleep(1);
		$control_times = 0; //重置flag
	}
	echo ("搜索接口繁忙, 请稍后\n");
	$search_wxlist_result = $main->searchWxList($search_keyword);
}

var_dump($search_wxlist_result);
echo "\n";


//通过公众号id 获取公众号文章列表
//$search_keyword = 'gcuxsh626';
$crawler = new WxCrawler();
foreach ($search_wxlist_result as $key => $value) {
	$gzh_id = false;
	$gzh_id = $main->searchWxList($search_wxlist_result[$key]['gzh_id']);
	while (!$gzh_id) {
		//当爬取次数超过三次后 还未能爬取到内容 终止爬虫
		++$control_times;
		if ($control_times > 3) {
			echo "优化程序，请稍后\n";
			sleep(1);
			$control_times = 0; //重置flag
		}
		echo ("搜索接口繁忙, 请稍后\n");
		$gzh_id = $main->searchWxList($search_wxlist_result[$key]['gzh_id']);
	}
	$request_url = $gzh_id[0]['gzh_link'];

	$article_link = $main->getWxArticleList($request_url);
	while (!$article_link) {
		//当爬取次数超过三次后 还未能爬取到内容 终止爬虫
		++$control_times;
		if ($control_times > 3) {
			echo "优化程序，请稍后\n";
			sleep(1);
			$control_times = 0; //重置flag
		}
		echo ("文章列表接口繁忙, 请稍后\n");
		$article_link = $main->getWxArticleList($request_url);
	}

	//TODO: 获取文章内容 进一步匹配关键词
	// $crawler = new WxCrawler();
	foreach ($article_link as $k => $v) {
		$article = $crawler->crawByUrl($v);
		if (strpos($article['title'], '活动') || strpos($article['content_text'], '活动') 
			&& strpos($article['content_text'], '时间')
			&& strpos($article['content_text'], '地点')) {
			var_dump($article['title'].' - - '.$article['digest']);
			echo "\n";
			echo $v;
			echo "\n";
		}
		// if (strpos($article['content_text'], '可盖章') || strpos($article['content_text'], '加分')) {
		// 	var_dump($article['title'].' - - '.$article['digest']);
		// 	echo "\n";
		// }
		//TODO: 匹配关键词 转化永久链
	}
	echo $key;
}

$h = time() - $start;
echo $h;
die;

/**
 * 获取公众号文章列表
 * @var [type]
 */
$gzh_id = $main->searchWxList($search_keyword);

while (!$gzh_id) {
	//当爬取次数超过三次后 还未能爬取到内容 终止爬虫
	++$control_times;
	if ($control_times > 3) {
		echo "优化程序，请稍后\n";
		sleep(2);
		$control_times = 0; //重置flag
	}
	echo ("搜索接口繁忙, 请稍后\n");
	$gzh_id = $main->searchWxList($search_keyword);
}
$request_url = $gzh_id[0]['gzh_link'];

$article_link = $main->getWxArticleList($request_url);
while (!$article_link) {
	//当爬取次数超过三次后 还未能爬取到内容 终止爬虫
	++$control_times;
	if ($control_times > 3) {
		echo "优化程序，请稍后\n";
		sleep(2);
		$control_times = 0; //重置flag
	}
	echo ("文章列表接口繁忙, 请稍后\n");
	$article_link = $main->getWxArticleList($request_url);
}

/**
 * 获取推文内容
 * @var WxCrawler
 */
$crawler = new WxCrawler();
$crawler->crawByUrl($article_link[0]);
foreach ($article_link as $key => $value) {
	var_dump($crawler->crawByUrl($value)['title'].' - - '.$crawler->crawByUrl($value)['digest']);
	echo "\n";
}
