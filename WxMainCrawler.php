<?php
/**
 * 微信公众号主体爬取类
 */
class WxMainCrawler{
	//微信搜狗链接
	protected $url = "weixin.sogou.com/weixin?type=1&s_from=input&query=";
	//微信公众号链接
	protected $gzh_url = "mp.weixin.qq.com";
	//返回网页格式
	protected $url_back_format = "&ie=utf8&_sug_=n&_sug_type_=";
	//curl参数
	protected $curlopt_param = [
		'header' => false,
		'post' => 0,
		'postfields' => '',
		'cookiefile' => '',
	];
	//ip代理池返回ip的协议
	protected $protocol = 'http';
	//搜索参数
	protected $search_text;
	//爬取url
	protected $request_url;
	//搜索列表公众号id
	protected $gzh_id;
	//搜索列表公众号头像
	protected $gzh_avatar;
	//搜索列表公众号临时链接
	protected $gzh_link = [];
	//搜索列表公众号汇总信息
	protected $gzh_info = [];
	//客户端代理
	protected $agent = [
		"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; AcooBrowser; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
        "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Acoo Browser; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.0.04506)",
        "Mozilla/4.0 (compatible; MSIE 7.0; AOL 9.5; AOLBuild 4337.35; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
        "Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)",
        "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
        "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
        "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.30)",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN) AppleWebKit/523.15 (KHTML, like Gecko, Safari/419.3) Arora/0.3 (Change: 287 c9dfb30)",
        "Mozilla/5.0 (X11; U; Linux; en-US) AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.6",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2pre) Gecko/20070215 K-Ninja/2.1.1",
        "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9) Gecko/20080705 Firefox/3.0 Kapiko/3.0",
        "Mozilla/5.0 (X11; Linux i686; U;) Gecko/20070322 Kazehakase/0.4.5",
        "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.8) Gecko Fedora/1.9.0.8-1.fc10 Kazehakase/0.5.6",
        "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/535.20 (KHTML, like Gecko) Chrome/19.0.1036.7 Safari/535.20",
        "Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52",
	];

	public function __construct()
	{
	}

	/**
	 * 使用ip代理池
	 * @author leekachung <leekachung17@gmail.com>
	 * @return [type] [description]
	 */
	public function randomIp()
	{
		$ip_pond = file_get_contents("https://proxy.357.im/api/proxies/common?protocol=".
				$this->protocol."&anonymity=anonymous");
		$ip_obj = json_decode($ip_pond)->data;
		$ip_arr = [
			"ip" => $ip_obj->ip,
			"ip_port" => $ip_obj->protocol."://".$ip_obj->ip.":".$ip_obj->port
		];

		echo "本次使用的ip代理：".$ip_arr['ip']." < - - > 完整ip地址：".$ip_arr['ip_port']." \n"; //test
		return $ip_arr;
	}

	/**
	 * 搜索微信公众号 使用微信搜狗搜索
	 * @author leekachung <leekachung17@gmail.com>
	 * @return [type] [description]
	 */
	public function searchWxList($search_keyword)
	{
		$this->search_text = $search_keyword;
		$this->request = $this->protocol."://".$this->url.$this->search_text.$this->url_back_format;
		//执行爬虫
		$content = $this->curlLink($this->randomIp());

		//判断爬取内容是否成功
		preg_match_all('|<label for="seccodeInput">(.*?)<\/label>|is', $content, $error);
		if (!empty($error[0])) {
			return false;
		}

		//正则匹配出公众号id
		preg_match_all('|<label name="em_weixinhao">(.*?)<\/label>|is', $content, $this->gzh_id);
		//正则匹配出公众号头像
		preg_match_all('/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i', $content, $avatar);
		for ($j=0; $j < count($avatar[1]); $j++) { 
			if ($j % 4 == 0 && $j != 0) {
				$this->gzh_avatar[] = substr($avatar[1][$j], 2);
			}
		}
		//正则匹配出临时链接名称
		for ($i=0; $i < count($this->gzh_id[1]); $i++) { 
			preg_match_all('|<a target="_blank" uigs="account_name_'. $i .'" href="(.*?)\">|is',
				 $content, $this->gzh_link[$i]);
			//处理转义字符
			$this->gzh_link[$i] = html_entity_decode($this->gzh_link[$i][1][0]);
			//汇总
			$this->gzh_info[$i] = [
				'gzh_id' => $this->gzh_id[1][$i],
				'gzh_link' => $this->gzh_link[$i],
				'gzh_avatar' => $this->gzh_avatar[$i],
			];
		}
		
		return $this->gzh_info;
	}

	/**
	 * getWxArticleList 获取某个公众号最近十篇推文
	 * @author leekachung <leekachung17@gmail.com>
	 * @param  [type] $gzh_link [description]
	 * @return [type]           [description]
	 */
	public function getWxArticleList($gzh_link)
	{
		$this->request = $gzh_link;
		//执行爬虫
		$content = $this->curlLink($this->randomIp());

		//判断爬取内容是否成功
		preg_match_all('|<div class="weui_cell_hd wap_only">(.*?)<\/div>|is', $content, $error);
		if (!empty($error[0])) {
			return false;
		}

		preg_match_all('/var biz = \"(.*?)\"/', $content, $biz);
		echo $biz[1][0];

		echo "\n";
		preg_match_all('/"content_url":\"(.*?)\"/', $content, $article_list_gather);
		$article_list_gather = $article_list_gather[1];
		foreach ($article_list_gather as $key => $value) {
			//一定要https协议 才可抓取内容 因为http会重定向到https 导致无法获取
			$article_list_gather[$key] = 
				"https://".$this->gzh_url.html_entity_decode($value);
		}
		echo "\n";

		return $article_list_gather;
	}

	/**
	 * Curl
	 * @author leekachung <leekachung17@gmail.com>
	 * @param  [type] $ip          [description]
	 * @param  [type] $search_text [description]
	 * @return [type]              [description]
	 */
	protected function curlLink($ip_arr)
	{
		//模拟http请求header头
		$header = [
			"Connection: Keep-Alive",
			"Accept: text/html, application/xhtml+xml, */*",
			"Pragma: no-cache", 
			"Accept-Language: zh-Hans-CN,zh-Hans;q=0.8,en-US;q=0.5,en;q=0.3",
			'User-Agent:'.$this->agent[rand(0,count($this->agent) - 1)],
			'CLIENT-IP:'.$ip_arr['ip'],
			'X-FORWARDED-FOR:'.$ip_arr['ip']
		];

		//初始化curl
		$ch = curl_init();
		//配置参数
		curl_setopt($ch, CURLOPT_URL, $this->request);
		curl_setopt($ch, CURLOPT_HEADER, $this->curlopt_param['header']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$this->curlopt_param['post'] && curl_setopt($ch, CURLOPT_POST, $this->curlopt_param['post']);
		$this->curlopt_param['post'] && curl_setopt($ch, CURLOPT_POSTFIELDS, $this->curlopt_param['postfields']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$this->curlopt_param['cookiefile'] && curl_setopt($ch, CURLOPT_COOKIEFILE, $this->curlopt_param['cookiefile']);
		$this->curlopt_param['cookiefile'] && curl_setopt($ch, CURLOPT_COOKIEJAR, $this->curlopt_param['cookiefile']);
		//模拟来源
		//curl_setopt($ch, CURLOPT_REFERER, 'http://weixin.sogou.com/');
		curl_setopt($ch, CURLOPT_PROXY, $ip_arr['ip_port']);
		curl_setopt($ch,CURLOPT_TIMEOUT,30); //允许执行的最长秒数

		$content = curl_exec($ch);
		curl_close($ch);
		unset($ch);

		return $content;
	}

}