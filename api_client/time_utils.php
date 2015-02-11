<?php
// parameter hhmm: a string representing a time in 24-hour format (e.g., "12:24", "15:22")
// returns: number of minutes elapsed since 00:00
function to_minutes($hhmm) {
  $fields = explode(':', $hhmm);
  return $fields[0] * 60 + $fields[1];
}

// parameter minutes: number of minutes elapsed since 00:00
// returns: a string representing a time in 12-hour format (e.g., "1:25pm", "11:20am")
function to_hhmm($minutes) {
  $suffix = 'am';
  $h = intval($minutes / 60);
  $m = $minutes - $h * 60;
  if ($h > 12) {
    $h -= 12;
    $suffix = 'pm';
  }
  if ($m < 10) $m = '0' . $m;
  return $h . ':' . $m . $suffix;
}

// returns: the current system time in 24-hour format (e.g., "21:26", "9:12")
function get_current_time() {
  date_default_timezone_set('America/New_York');
  return date('H:i');
}

