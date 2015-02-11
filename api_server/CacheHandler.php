<?php
require_once "pdo.php";
require_once "Curl.php";

class Cache_Handler {
	public static $cacheTableName = "http_cache";
	
	/*
	 * Forced to retrieve content from the remote server.
	 * See getURL. 
	 */
	public static function getUrlForceRefresh($url) {
		return self::getURL($url, 0);
	}
	
	/*
	 * Get html content. Try to retrieve from the cache database first.
	 * Cache will never expire.
	 * See getURL.
	 */
	public static function getUrlExpectCache($url) {
		return self::getURL($url, -1);
	}
	
	/*
	 * Get the content at the specified URL.
	 * Will try to retrieve from cached results first.
	 * If cache does not exist or expired, will retrieve from the URL.
	 * The new content will be saved as cache, replacing any previous result of the 
	 * same URL.
	 * 
	 * URLs must be exact match for retrieving cache results.
	 * 
	 * $url needs to be encoded using urlencode() prior to calling.
	 * Returns the html content requested by url.
	 * Return format: {is_cache, time, result, message, [curl_fields...]}
	 *   is_cache: 1 - result is cache, 0 - result is new
	 *   time: time of retrieval
	 *   result: content, i.e., the payload in the curl result, or cached content
	 *   message: detailed error message if failed
	 *   curl_fields: containing 'http_status', 'content_type' (see Curl.php) 
	 * 
	 * Uses access_control to limit call frequency and volumes to the remote server.
	 *
	 * Will not retrieve the actual url if both conditions are met:
	 * (1) url is in the cache server
	 * (2) expireThreshold (ms since last update) is not met 
	 *
	 * $expireThreshold: number of seconds that a cache record is valid for
	 * Set expireThreshold to 0 for forced refresh.
	 * Set expireThreshold to -1 for infinity (cache will never expire).
	 */
	public static function getURL($url, $expireThreshold) {
		$current_time = time();
		if ($expireThreshold != 0) {
			$cached_content = self::getCache($url);
			if ($cached_content != FALSE) {
				$cache_time = $cached_content['time'];
				if (!self::test_if_expires($current_time, $cache_time, $expireThreshold)) {
					return self::prepareResult($cached_content['content'], 1, $cache_time);
				}
			}
		}
		
		$curlResult = Curl::useCurl($url);
		
		$current_time = time();
		self::updateCache($url, $current_time, $curlResult['payload']);
		
		return self::prepareResult($curlResult, 0, $current_time);
	}
	
	public static function removeCacheByURL($url) {
		$table = self::$cacheTableName;
		$sql = "DELETE FROM $table where urlhash = UNHEX(SHA1('{$url}'))";
		$pdo = PDOX::getPDO();
		$pdo->query($sql);
	}
	
	/*
	 * If succeeds, returns {time, content}.
	 * If fails, return FALSE. 
	 */
	private static function getCache($url) {
		$table = self::$cacheTableName;
		$sql = "SELECT time, content FROM {$table} WHERE urlhash = UNHEX(SHA1('{$url}'))";
		$pdo = PDOX::getPDO();
		$stmt = $pdo->query($sql);
		return $result = $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	private static function updateCache($url, $time, $content) {
		$table = self::$cacheTableName;
		$sql = "INSERT INTO $table (urlhash, url, time, content) " 
								 . "values (UNHEX(SHA1(:url)), :url, :time, :content) " 
								 . "on duplicate key update " 
								 . "time = values(time), content = values(content)";
		$pdo = PDOX::getPDO();
		$stmt = $pdo->prepare($sql);
		$stmt->execute(array(':url' => $url, ':time' => $time, ':content' => $content));
								 
	}
	
	private static function test_if_expires($current_time, $cache_time, $expireThreshold) {
		if ($expireThreshold == -1 || $current_time - $cache_time < $expireThreshold) {
			return FALSE;
		}
		return TRUE;
	}
	
	private static function prepareResult($result, $is_cache, $time) {
		$output = array();
		$output['cache'] = $is_cache;
		$output['time'] = $time;
		if ($is_cache === 1) {
			$output['result'] = $result;
		} else {
			foreach ($result as $key => $value) {
				if ($key == "payload") {
					$output['result'] = $value;
				} else {
					$output[$key] = $value;
				}
			}
		}
		return $output;
	}
}