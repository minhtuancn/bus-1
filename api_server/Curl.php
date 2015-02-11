<?php
class Curl {
	/**
	 * @param string $url
	 * @return multitype:mixed: {payload, http_status, content_type}.
	 */
	public static function useCurl($url) {
		// TODO: Set up cURL to access status code
		$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/curl_cookie/cookie_store.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/curl_cookie/cookie_store.txt');
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		curl_setopt($ch, CURLOPT_URL, $url);
		$curl_result = curl_exec($ch);
		if ($curl_result === FALSE) {
			throw new Curl_Exception(curl_error($ch));
		}
		$result_array = array();
		$result_array['payload'] = $curl_result; 
		$result_array['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$result_array['content_type'] = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		curl_close($ch);
		return $result_array;
	}
}

class Curl_Exception extends Exception {}
