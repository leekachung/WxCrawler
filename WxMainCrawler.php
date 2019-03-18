<?php
/**
 * 微信公众号主体爬取类
 */
class WxMainCrawler{
	//微信搜狗链接
	protected $url = "http://weixin.sogou.com/weixin?type=1&s_from=input&query=";
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
	//公众号id
	protected $gzh_id;
	//公众号头像
	protected $gzh_avatar;
	//公众号临时链接
	protected $gzh_link = [];
	//公众号汇总信息
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

	public function __construct($search_text='')
	{
		$this->search_text = $search_text;
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

		var_dump($ip_arr); //test
		return $ip_arr;
	}

	/**
	 * 搜索微信公众号 使用微信搜狗搜索
	 * @author leekachung <leekachung17@gmail.com>
	 * @return [type] [description]
	 */
	public function searchWxList()
	{
		//执行爬虫
		$content = $this->curlLink($this->randomIp());
		echo $content;

		//判断爬取内容是否成功
		preg_match_all('|<label for="seccodeInput">(.*?)<\/label>|is', $content, $error);
		if (!empty($error[0])) {
			return false;
		}

		//正则匹配出公众号id
		preg_match_all('|<label name="em_weixinhao">(.*?)<\/label>|is', $content, $this->gzh_id);
		//正则匹配出公众号头像
		preg_match_all('|<img height="32" width="32" class="shot-img" src="(.*?)\" onerror="errorHeadImage(this)">|is', $content, $this->gzh_avatar);
		return $this->gzh_avatar;
		//正则匹配出临时链接名称
		for ($i=0; $i < count($this->gzh_id[1]); $i++) { 
			preg_match_all('|<a target="_blank" uigs="account_name_'. $i .'" href="(.*?)\">|is',
				 $content, $this->gzh_link[$i]);
			//处理转义字符
			$this->gzh_link[$i] = html_entity_decode($this->gzh_link[$i][1][0]);
			//汇总
			$this->gzh_info[$i] = [
				'gzh_id' => $this->gzh_id[1][$i],
				'gzh_link' => $this->gzh_link[$i]
			];
		}
		
		return $this->gzh_info;
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
		curl_setopt($ch, CURLOPT_URL, $this->url.$this->search_text.$this->url_back_format);
		curl_setopt($ch, CURLOPT_HEADER, $this->curlopt_param['header']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$this->curlopt_param['post'] && curl_setopt($ch, CURLOPT_POST, $this->curlopt_param['post']);
		$this->curlopt_param['post'] && curl_setopt($ch, CURLOPT_POSTFIELDS, $this->curlopt_param['postfields']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$this->curlopt_param['cookiefile'] && curl_setopt($ch, CURLOPT_COOKIEFILE, $this->curlopt_param['cookiefile']);
		$this->curlopt_param['cookiefile'] && curl_setopt($ch, CURLOPT_COOKIEJAR, $this->curlopt_param['cookiefile']);
		//curl_setopt($ch, CURLOPT_REFERER, 'http://weixin.sogou.com/');
		curl_setopt($ch, CURLOPT_PROXY, $ip_arr['ip_port']);
		curl_setopt($ch,CURLOPT_TIMEOUT,30); //允许执行的最长秒数

		$content = curl_exec($ch);
		curl_close($ch);
		unset($ch);

		return $content;
	}

}