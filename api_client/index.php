<?php
require_once 'bus_utils.php';
require_once 'time_utils.php';

$bus_info_json = fetch_bus_info();
$bus_info = json_decode($bus_info_json, true);

$time = get_current_time();
$time_min = to_minutes($time);
$time_12h = to_hhmm($time_min);

$to_sort = array();
$sort_key = 'time_bus_leaves_museum';
foreach($bus_info as $bus) {
  $time_to_leave_nq_for_league = $bus['time_bus_leaves_league'] - 4;
  $time_to_leave_nq_for_museum = $bus['time_bus_leaves_museum'] - 8;
  if ($time_to_leave_nq_for_league >= $time_min) {
    $wait = $time_to_leave_nq_for_league - $time_min + 0;
    $leave_time = to_hhmm($time_to_leave_nq_for_league);
    $sort_val = $bus[$sort_key];
    $bus_name = $bus['bus_id'] > 0 ? "bus #{$bus['bus_id']}" : "the next available bus.";
    $message = "Leave North Quad for Michigan League at $leave_time (in $wait minutes) to catch $bus_name";
    $to_sort[$message] = $sort_val;
  }
  if ($time_to_leave_nq_for_museum >= $time_min) {
    $wait = $time_to_leave_nq_for_museum - $time_min + 0;
    $leave_time = to_hhmm($time_to_leave_nq_for_museum);
    $sort_val = $bus[$sort_key] + 2;  // penalty for walking so long
    $bus_name = $bus['bus_id'] > 0 ? "bus #{$bus['bus_id']}" : "the next available bus.";
    $message = "Leave North Quad for Art Museum at $leave_time (in $wait minutes) to catch $bus_name";
    $to_sort[$message] = $sort_val;
  }
}
asort($to_sort);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Route 36 Smart Assistant</title>
</head>
<body>
<h1>Route 36 Assistant</h1>
<?php
echo "<h2><i>Current time: $time_12h</i></h2>";
if (count($to_sort) > 0) {
  echo "<ul>\n";
  $first = true;
  foreach($to_sort as $message => $sort_val) {
    if ($first) echo "<h2>";
    echo "<li>" . htmlentities($message) . "</li>\n";
    if ($first) echo "</h2>";
    $first = false;
  }
  echo "</ul>\n";
} else {
  echo "<h2>No bus is available now.</h2>";
}
?>
</body>
</html>