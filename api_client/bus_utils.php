<?php
function fetch_bus_info() {
  $url = "http://ronxin.people.si.umich.edu/bus/api_server/query.php";
  return file_get_contents($url);
}
