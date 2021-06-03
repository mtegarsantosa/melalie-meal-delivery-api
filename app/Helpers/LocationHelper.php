<?php
namespace App\Helpers;

class LocationHelper
{
  static function haversine($point1, $point2)
  {
    $earthRadius = 6371;
    $point1Lat = $point1[0];
    $point2Lat =$point2[0];
    $deltaLat = deg2rad($point2Lat - $point1Lat);
    $point1Long =$point1[1];
    $point2Long =$point2[1];
    $deltaLong = deg2rad($point2Long - $point1Long);
    $a = sin($deltaLat/2) * sin($deltaLat/2) + cos(deg2rad($point1Lat)) * cos(deg2rad($point2Lat)) * sin($deltaLong/2) * sin($deltaLong/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    $distance = $earthRadius * $c;
    return $distance;    // in km
  }
}
