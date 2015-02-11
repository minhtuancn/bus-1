<?php
function get_stop_name_map() {
  $stop_name_map = array(
    'Wolverine Tower' => 'stop1',
    'UM Commuter Lot' => 'ambiguous',  // stop2 or stop6
    'Michigan Union' => 'stop3',
    'Central Campus Transit Center' => 'stop4',
    'UM Libraries' => 'stop5',
    'Briarwood Mall - MC Sports' => 'stop7'
  );
  return $stop_name_map;
}

function get_stop_name_reverse_map() {
  $out = array();
  $stop_name_map = get_stop_name_map();
  foreach ($stop_name_map as $k => $v) {
    if ($v != 'ambiguous') {
      $out[$v] = $k;
    }
  }
  $out['stop2'] = 'UM Commuter Lot NB';
  $out['stop6'] = 'UM Commuter Lot SB';
  return $out;
}

// returns an array of arrays, each with the following keys
// [1] next_scheduled_departure  (e.g., "14:21", "9:25")
// [2] next_stop_id  (e.g., "stop1", "stop5")
// [3] minutes_behind  (e.g., 5, 0, or -2)
// [4] bus_id (e.g., 432)
function parse_bus36($html) {
  $stop_name_map = get_stop_name_map();
  $wrapper_left = '/Information Key<\/a><hr \/>';
  $wrapper_right = '<br \/><hr \/><br\/><FORM METHOD=GET/';
  preg_match($wrapper_left . '(.*)' . $wrapper_right, $html, $matches);
  
  $useful_portion = $matches[1];

  $entries = explode('<hr />', $useful_portion);
  $out = array();
  foreach ($entries as $entry) {
    $parsed_entry = array();
    $lines = explode('<br />', $entry);
    // there should be at least 3 lines
    {
      // 1st line:
      // example: 432 Loop 9 min behind
      // example: 432 Loop on time
      // example: 432 Loop 3 min ahead
      $fields = explode(' ', $lines[0]);
      $parsed_entry['bus_id'] = $fields[0];
      if ($fields[2] == 'On') $parsed_entry['minutes_behind'] = 0;
      else if (!isset($fields[4])) {var_dump($fields); exit();}  // should not happen
      else if ($fields[4] == 'behind') $parsed_entry['minutes_behind'] = 0 + $fields[2];
      else $parsed_entry['minutes_behind'] = 0 - $fields[2];
    }
    {
      // 2nd line:
      // example: @ UM Commuter Lot  --> used for disambiguation only
      // 3rd line:
      // example: Michigan Union 2:15
      $fields = explode(' ', $lines[2]);
      $next_stop_name = implode(' ', array_slice($fields, 0, count($fields) - 1));
      $next_stop_id = $stop_name_map[$next_stop_name];
      if ($next_stop_id == 'ambiguous') {
        $prev_stop_name = substr($lines[1], 2);
        $prev_stop_id = $stop_name_map[$prev_stop_name];
        if ($prev_stop_id == 'stop5') $next_stop_id = 'stop6';  // prev_stop is "UM Libraries"
        else $next_stop_id = 'stop2';
      }
      $parsed_entry['next_stop_id'] = $next_stop_id;
      $time_str = $fields[count($fields) - 1];
      date_default_timezone_set('America/New_York');
      $current_hour = date("H");  // 24-hour format
      // If current hour >= 11, then all hours <= 10 will be considered pm hours
      if ($current_hour >= 11) {
        $time_str_fields = explode(':', $time_str);
        if ($time_str_fields[0] <= 10) $time_str_fields[0] += 12;  // converting to 24-hour format
        $time_str = implode(':', $time_str_fields);
      }
      $parsed_entry['next_scheduled_departure'] = $time_str;
    }
    $out[] = $parsed_entry;
  }
  return $out;
}

function test_parse1() {
  $html = 'ROUTE 36: WOLVERINE TOWER SHUTTLE<br />REAL-TIME BUS INFORMATION<br />AS OF 2:15 PM 2/10/2015<br/><FORM METHOD=GET ACTION="rideguide_m.asp" STYLE="margin:0px"><INPUT TYPE=HIDDEN NAME="route" VALUE="36"><INPUT TYPE="SUBMIT" VALUE="Refresh" BORDER=0></FORM><br/><FONT SIZE=1 FACE="Arial"><a href="#key">Information Key</a><hr />432 Loop 9 min behind<br />@ UM Commuter Lot<br />Michigan Union 2:15<br /><hr />470 Loop 27 min behind<br />@ Wolverine Tower<br />UM Commuter Lot 1:51<br /><hr />472 Loop On time<br />@ UM Libraries<br />UM Commuter Lot 2:03<br /><hr /><br/><FORM METHOD=GET ACTION="rideguide_m.asp" STYLE="margin:0px"><INPUT TYPE=HIDDEN NAME="route" VALUE="36"><INPUT TYPE="SUBMIT" VALUE="Refresh" BORDER=0></FORM><br/><FONT SIZE=1 FACE="Arial"><a name="key"></a><B>Information Key</B><br />Bus #, Direction, Status <br />@ Most Recent Timepoint <br />Next Timepoint, Scheduled Departure';
  print_r(parse_bus36($html));
}

function test_parse2() {
  $html = 'ROUTE 36: WOLVERINE TOWER SHUTTLE<br />REAL-TIME BUS INFORMATION<br />AS OF 6:56 PM 2/10/2015<br/><FORM METHOD=GET ACTION="rideguide_m.asp" STYLE="margin:0px"><INPUT TYPE=HIDDEN NAME="route" VALUE="36"><INPUT TYPE="SUBMIT" VALUE="Refresh" BORDER=0></FORM><br/><FONT SIZE=1 FACE="Arial"><a href="#key">Information Key</a><hr />432 Loop 4 min behind<br />@ UM Commuter Lot<br />Wolverine Tower 6:37<br /><hr />470 Loop 8 min behind<br />@ Michigan Union<br />Central Campus Transit Center 6:50<br /><hr /><br/><FORM METHOD=GET ACTION="rideguide_m.asp" STYLE="margin:0px"><INPUT TYPE=HIDDEN NAME="route" VALUE="36"><INPUT TYPE="SUBMIT" VALUE="Refresh" BORDER=0></FORM><br/><FONT SIZE=1 FACE="Arial"><a name="key"></a><B>Information Key</B><br />Bus #, Direction, Status <br />@ Most Recent Timepoint <br />Next Timepoint, Scheduled Departure';
  print_r(parse_bus36($html));
}

function test_parse3() {
  $html = 'ROUTE 36: WOLVERINE TOWER SHUTTLE<br />REAL-TIME BUS INFORMATION<br />AS OF 7:36 PM 2/10/2015<br/><FORM METHOD=GET ACTION="rideguide_m.asp" STYLE="margin:0px"><INPUT TYPE=HIDDEN NAME="route" VALUE="36"><INPUT TYPE="SUBMIT" VALUE="Refresh" BORDER=0></FORM><br/><FONT SIZE=1 FACE="Arial"><a href="#key">Information Key</a><hr />432 Loop 4 min behind<br />@ UM Commuter Lot<br />Wolverine Tower 6:37<br /><hr />470 Loop 3 min behind<br />@ Central Campus Transit Center<br />UM Libraries 7:33<br /><hr /><br/><FORM METHOD=GET ACTION="rideguide_m.asp" STYLE="margin:0px"><INPUT TYPE=HIDDEN NAME="route" VALUE="36"><INPUT TYPE="SUBMIT" VALUE="Refresh" BORDER=0></FORM><br/><FONT SIZE=1 FACE="Arial"><a name="key"></a><B>Information Key</B><br />Bus #, Direction, Status <br />@ Most Recent Timepoint <br />Next Timepoint, Scheduled Departure';
  print_r(parse_bus36($html));
}

// For testing...
/*
echo '<pre>';
test_parse1();
test_parse2();
test_parse3();
echo '</pre>';
*/
