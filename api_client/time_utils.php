<?php
// input: hh:mm or h:mm (in 24-hour format)
// returns: #minutes since midnight
function to_minutes($hhmm) {
  $fields = explode(':', $hhmm);
  return $fields[0] * 60 + $fields[1];
}

// input: #minutes since midnight
// returns: a string in 12-hour format
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

// returns: hh:mm (in 24-hour format)
function get_current_time() {
  date_default_timezone_set('America/New_York');
  return date('H:i');
}