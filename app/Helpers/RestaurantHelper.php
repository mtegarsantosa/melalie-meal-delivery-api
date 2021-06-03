<?php
namespace App\Helpers;

class RestaurantHelper
{

  static function formatBusinessHours($business_hours)
  {
    if (!$business_hours) return false;
    $dayFormat = [
      "Sunday" => "Sun",
      "Monday" => "Mon",
      "Tuesday" => "Tue",
      "Wednesday" => "Wed",
      "Thursday" => "Thu",
      "Friday" => "Fri",
      "Saturday" => "Sat",
      "Tues" => "Tue",
      "Weds" => "Wed",
      "Thur" => "Thu",
      "Thurs" => "Thu"
    ];
    $business_hours = explode(' | ', $business_hours);
    foreach ($business_hours as $key1 => $value1) {
      $business_hours[$key1] = explode(': ', $value1);
    }
    $business_hours_clean = [];
    for ($i=0; $i < count($business_hours) ; $i++) {
      $days = explode(', ', $business_hours[$i][0]);
      if (strpos($days[0], '-')) $days = explode('-', $business_hours[$i][0]);
      foreach ($days as $day) {
        if (strlen($day) > 3) $day = $dayFormat[$day];
        $business_hours_clean[$day] = explode(' - ', $business_hours[$i][1]);
      }
    }
    return $business_hours_clean;
  }
}
