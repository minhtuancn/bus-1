<?php
require_once 'CacheHandler.php';

$expire_threshold = 60;  // 1 min
if (isset($_GET['url']) && strlen($_GET['url']) > 0) {
  $result = Cache_Handler::getURL($_GET['url'], $expire_threshold);
  // result format: {is_cache, time, result, message, [curl_fields...]}
  if (isset($result['result'])) {
    header('Content-Type: text/html');
    echo $result['result'];
    echo "\n<!--\n" . '$result=' . "\n";
    $result['result'] = "...";
    print_r($result);
    echo "-->\n";
    exit();
  } else {
    var_dump($result);
    exit();
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Testing HTTP Cache</title>
</head>
<body>
<form method="GET" target="_blank">
  URL: <input type="text" size="80" name="url">
  <input type="submit" value="GET">
<pre>
  Must enter full url, with no typo, GET parameters allowed.
  Example URL: http://mobile.aata.org/rideguide_m.asp?route=36
  Example URL: http://mobile.aata.org/rideguide_m.asp?route=6
</pre>
<p>In the result page, view the bottom of page source to find other returned values.
</p>
</form>

</body>
</html>