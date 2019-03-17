<?php
/**
 * 微信公众号主体爬取类
 */
class WxMainCrawler{
	//微信搜狗链接
	protected $url = "http://weixin.sogou.com/weixin?type=1&s_from=input&query=";
	//返回网页格式
	protected $url_back_format = "&ie=utf8";
	//curl参数
	protected $curlopt_param = [
		'header' => false,
		'post' => 0,
		'postfields' => '',
		'cookiefile' => '',
	];
	//搜索参数
	protected $search_text;

	public function __construct($search_text='')
	{
		$this->search_text = $search_text;
	}

	/**
	 * 构造随机ip
	 * @author leekachung <leekachung17@gmail.com>
	 * @return [type] [description]
	 */
	public function randomIp()
	{
		$ip_long = array(
		array('607649792', '608174079'), //36.56.0.0-36.63.255.255
		array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
		array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
		array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
		array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
		array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
		array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
		array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
		array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
		array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
		);
		$rand_key = mt_rand(0, 9);
		$ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));

		return $ip;
	}

	/**
	 * 搜索微信公众号 使用微信搜狗搜索
	 * @author leekachung <leekachung17@gmail.com>
	 * @return [type] [description]
	 */
	public function searchWxList()
	{
		$content = $this->curlLink($this->randomIp());
		//正则匹配出微信名称
		preg_match_all('/\<img\s*src=\".*?\"\s\/\>/iu',$content,$backmsg);
		return json_encode($backmsg);
	}

	/**
	 * Curl
	 * @author leekachung <leekachung17@gmail.com>
	 * @param  [type] $ip          [description]
	 * @param  [type] $search_text [description]
	 * @return [type]              [description]
	 */
	protected function curlLink($ip)
	{
		//模拟http请求header头
		$header = array("Connection: Keep-Alive","Accept: text/html, application/xhtml+xml, */*", "Pragma: no-cache", "Accept-Language: zh-Hans-CN,zh-Hans;q=0.8,en-US;q=0.5,en;q=0.3","User-Agent: Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; WOW64; Trident/6.0)",'CLIENT-IP:'.$ip,'X-FORWARDED-FOR:'.$ip);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url.$this->search_text.$this->url_back_format);
		curl_setopt($ch, CURLOPT_HEADER, $this->curlopt_param['header']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$this->curlopt_param['post'] && curl_setopt($ch, CURLOPT_POST, $this->curlopt_param['post']);
		$this->curlopt_param['post'] && curl_setopt($ch, CURLOPT_POSTFIELDS, $this->curlopt_param['postfields']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$this->curlopt_param['cookiefile'] && curl_setopt($ch, CURLOPT_COOKIEFILE, $this->curlopt_param['cookiefile']);
		$this->curlopt_param['cookiefile'] && curl_setopt($ch, CURLOPT_COOKIEJAR, $this->curlopt_param['cookiefile']);
		curl_setopt($ch,CURLOPT_TIMEOUT,30); //允许执行的最长秒数
		$content = curl_exec($ch);
		curl_close($ch);
		unset($ch);

		return $content;
	}

}