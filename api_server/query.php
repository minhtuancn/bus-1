<?php
// This file serves a JSON. This is the API responder.
// For Route 36 only.
// The returned JSON is an array of objects.
// Each object has the following fields:
// [1] bus_id
// [2] time_bus_leaves_league
// [3] time_bus_leaves_museum
// [4] match_difference: matched_departure_time - crawed_scheduled_departure_time (should always be 0)
require_once 'CacheHandler.php';
require_once 'parse.php';
require_once 'time_utils.php';
require_once 'schedule.php';

// Step 1: retrieve the tracking page through Cache_Handler
$expire_threshold = 60;  // 1 min
$url = 'http://mobile.aata.org/rideguide_m.asp?route=36';
$result = Cache_Handler::getURL($url, $expire_threshold);
// result format: {is_cache, time, result, message, [curl_fields...]}
if (!isset($result['result'])) {
  echo json_encode($result);
  exit();
}
$html = $result['result'];

// Step 2: parse it
$bus_infos = parse_bus36($html);

// Step 3: look up in the schedule table and compile the JSON to return
$out = array();
$schedule = get_schedule();
$last_schedule_row = -1;
$last_id = -1;
$last_behind = -1;
$last_match_difference = -1;
foreach ($bus_infos as $bus) {
  $entry = array();
  $entry['bus_id'] = $bus['bus_id'];
  $bus_minutes_behind_schedule = $bus['minutes_behind'];
  
  
  // find out which row in the schedule is this bus closest to
  $next_stop_id = $bus['next_stop_id'];
  $next_scheduled_departure = $bus['next_scheduled_departure'];
  $to_sort = array();  // key: row_index in schedule, value: difference from crawled scheduled departure
  foreach($schedule as $row_index => $sc) {
    if (!isset($sc[$next_stop_id])) continue;
    $to_sort[$row_index] = abs(to_minutes($sc[$next_stop_id]) - to_minutes($next_scheduled_departure));
  }
  asort($to_sort);
  reset($to_sort);
  $first_key = key($to_sort);  // matched row_index in schedule
  $matched_schedule = $schedule[$first_key];
  $match_difference = to_minutes($matched_schedule[$next_stop_id]) - to_minutes($next_scheduled_departure);
  if (abs($match_difference) >= 5) {
    // There is only one possibily in this situation: the bus will quit service at its last stop (Wolverine or Briarwood).
    // So we should ignore this bus.
    // Look at the difference between last column and the first column on the next "natural contiunous" run.
    // Also note that we ignore the last column in the schedule, whether it is Wolverine Tower or Briarwood Mall.
    continue;
  }
  $entry['match_difference'] = $match_difference;
  complete_bus_info($matched_schedule, $bus_minutes_behind_schedule, $entry);
  $out[] = $entry;

  // Always include the next journey on the schedule (if any, in case all buses are past time point and south bound)
  if ($first_key > $last_schedule_row) {
    $last_schedule_row = $first_key;
    $last_id = $bus['bus_id'];
    $last_behind = $bus_minutes_behind_schedule;
    $last_match_difference = $match_difference;
  }
}
// If there is only one bus running, and the bus has passed our checkpoints, then we also include its next inbound jounary (if any) as a separate bus
if ($last_schedule_row + 1 < count($schedule)) {
  $entry = array();
  $entry['bus_id'] = -1;  // unknown
  $entry['match_difference'] = 0;
  $matched_schedule = $schedule[$last_schedule_row + 1];
  // At the last stop, the bus driver will use the 3~4 min scheduled gap to reduce delayed time.
  if ($last_match_difference > 0) $bus_minutes_behind_schedule -= $last_match_difference; 
  complete_bus_info($matched_schedule, $bus_minutes_behind_schedule, $entry);
  $out[] = $entry;  
}

echo json_encode($out);

function complete_bus_info($matched_schedule, $bus_minutes_behind_schedule, &$entry) {
  $bus_leave_union = to_minutes($matched_schedule['stop3']);
  $bus_leave_library = to_minutes($matched_schedule['stop5']);
  if ($bus_minutes_behind_schedule > 0) {
    $bus_leave_union += $bus_minutes_behind_schedule;
    $bus_leave_library += $bus_minutes_behind_schedule;
  }
  $bus_leave_league = $bus_leave_union + 2;
  $bus_leave_museum = $bus_leave_library + 1;
  $entry['time_bus_leaves_league'] = $bus_leave_league;
  $entry['time_bus_leaves_museum'] = $bus_leave_museum;
}
