<?php
// Performs an API call (a GET request) to an internally specified URL,
// returns a JSON.
// The JSON is an array of objects, each object representing a bus record.
// Each bus record includes the following keys:
// [1] bus_id: the identifier of the bus. -1 if unknown.
// [2] match_difference: how many minutes does the crawled scheduled 
//     arrival time deviates from the saved record (useful for validating
//     the bus schedules saved internally by the API server. should normally
//     be zero regardless of whether the bus is actually ahead of or behind 
//     schedule).
// [3] time_bus_leaves_league: the estimated time that the bus crosses Michigan
//     League. The time is described in number of minutes past midnight. For
//     example 13:20 is represented as 13*60+20 = 800.
// [4] time_bus_leaves_museum: the estimated time that the bus crosses UM Art
//     Museum. The time is described in the same way as [3].
function fetch_bus_info() {
  $url = "http://ronxin.people.si.umich.edu/bus/api_server/query.php";
  return file_get_contents($url);
}

