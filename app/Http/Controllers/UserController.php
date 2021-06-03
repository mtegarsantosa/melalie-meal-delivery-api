<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;
use App\Restaurant;
use App\Menu;
use App\Purchase;
use App\Helpers\RestaurantHelper;
use App\Helpers\LocationHelper;
use Carbon\Carbon;

class UserController extends Controller
{
    public function cleanDataMenu($restaurant_name, $menu_name)
    {
      $menu = Menu::where([
        ["name", "=", $menu_name]
      ])->with('restaurant')->whereHas('restaurant', function($q) use($restaurant_name) {
        $q->where('name', '=', $restaurant_name);
      })->first();
      if (!$menu) return false;

      return [
        "menu_id" => $menu->id,
        "restaurant_id" => $menu->restaurant->id
      ];
    }

    public function import()
    {
        set_time_limit(0);
        try {
          $datas = json_decode(file_get_contents('../database/json_import/users.json'), true);
          foreach ($datas as $data) {
            $storedUser = User::storeImport($data["name"], $data["location"], $data["balance"]);
            foreach ($data["purchases"] as $purchase) {
              $menuClean = $this->cleanDataMenu($purchase['restaurant_name'], $purchase['dish']);
              if ($menuClean) {
                Purchase::storeImport($storedUser->id, $menuClean["menu_id"], explode(" UTC", $purchase["date"])[0]);
              }
            }
          }
          return response([
            "status" => true
          ]);
        } catch (\Exception $e) {
          return response([
            "status" => false,
            "message" => $e->getMessage()
          ]);
        }
    }

    public function index(Request $request)
    {
      $datas = User::where([
        ['id', '=', $request->get('id')]
      ]);
      $q = $request->query;

      // Get User with Transactions Data
      if ($q->get('with_trs') && $q->get('with_trs') == 1) {
        $datas->with([
          'transactions' => function ($q) {
            $q->with([
              'menu' => function ($q) {
                $q->with('restaurant');
              }
            ]);
          }
        ]);
      }

      return response([
        "status" => true,
        "data" => $datas->get()
      ]);
    }

    public function restaurantsIndex(Request $request)
    {
      $datas = Restaurant::where([]);
      $q = $request->query;

      // Open At
      if ($q->get('open_at')) {
        $datetime = explode(' ', $q->get('open_at')); // [date, time, a]
        $day = Carbon::parse($datetime[0])->format('D');
        $restos = Restaurant::get();
        foreach ($restos as $resto) {
          $formatBH = RestaurantHelper::formatBusinessHours($resto->business_hours);
          if ($formatBH) {
            if (!array_key_exists($day, $formatBH)) {
              $resto->isOpen = false;
            }
            else {
              $timeAt = Carbon::parse("{$datetime[1]} {$datetime[2]} {$datetime[0]}");
              $timeOpen = Carbon::parse("{$formatBH[$day][0]} {$datetime[0]}")->timestamp;
              $timeClose = Carbon::parse("{$formatBH[$day][1]} {$datetime[0]}");
              if (Carbon::parse($formatBH[$day][0])->format('a') == 'pm' && Carbon::parse($formatBH[$day][1])->format('a') == 'am') {
                if (Carbon::parse($timeAt)->format('a') == 'am') {
                  $timeAt->addDays(1);
                }
                $timeClose = $timeClose->addDays(1);
              }
              $timeClose = $timeClose->timestamp;
              $timeAt = $timeAt->timestamp;
              if ($timeAt >= $timeOpen && $timeAt <= $timeClose) {
                $resto->isOpen = true;
              }
              else {
                $resto->isOpen = false;
              }
            }
          }
          else $resto->isOpen = false;
        }
        $restos = $restos->where('isOpen', true)->toArray();
        $restos = array_values($restos);
        return response([
          "status" => true,
          "data" => $restos
        ]);
      }

      // Work Hours
      if ($q->get('work_hours')) {
        if (!$q->get('day')) return response([
          "status" => false,
          "message" => "required 'day' query"
        ]);
        $day = $q->get('day');
        $work_hours = explode('-', $q->get('work_hours'));
        $restos = Restaurant::get();
        foreach ($restos as $resto) {
          $formatBH = RestaurantHelper::formatBusinessHours($resto->business_hours);
          if (is_array($formatBH) && array_key_exists($day, $formatBH)) {
            // $resto->work_hours_per_week = 0;
            foreach ($formatBH as $key => $value) {
              $resto->work_hours_per_week += Carbon::parse($formatBH[$key][0])->diffInSeconds($formatBH[$key][1]);
            }
            $resto->work_hours_per_week = $resto->work_hours_per_week/60/60;
            $timeOpen = Carbon::parse($formatBH[$day][0]);
            $timeClose = Carbon::parse($formatBH[$day][1]);
            $resto->work_hours_per_day = $timeOpen->diffInSeconds($timeClose);
            $resto->work_hours_per_day = $resto->work_hours_per_day/60/60;
          }
        }
        $restos = $restos->where('work_hours_per_day', '>=', $work_hours[0])->where('work_hours_per_day', '<=', $work_hours[1])->toArray();
        $restos = array_values($restos);
        return response([
          "status" => true,
          "data" => $restos
        ]);
      }

      // Dishes Total
      if ($q->get('dishes_total')) {
        $dishesTotal = explode('-', $q->get('dishes_total'));
        $datas->with('menus');
        $datas->withCount('menus');
        $datas->having('menus_count', '>', $dishesTotal[0]);
        $datas->having('menus_count', '<', $dishesTotal[1]);
      }

      // Price Range
      if ($q->get('price_range')) {
        $priceRange = explode('-', $q->get('price_range'));
        // count where has price in menus relation
        $datas->with([
          'menus' => function ($q) use ($priceRange) {
            $q->whereBetween('price', $priceRange);
          }
        ]);
      }

      // Filter By Dish/Resto's Name
      if ($q->get('name')) {
        $errTarget = response([
          "status" => false,
          "message" => "please specify target of name (dish/restaurant)"
        ]);
        if (!$q->get('target')) return $errTarget;
        else {
          if ($q->get('target') == 'restaurant') {
            $datas->with('menus');
            $datas->where([
              ['name', 'LIKE', '%'.$q->get('name').'%']
            ]);
          }
          elseif ($q->get('target') == 'dish') {
            $datas->with('menus')->whereHas('menus', function($query) use ($q) {
              $query->where('name', 'LIKE', '%'.$q->get('name').'%');
            });
          }
          else return $errTarget;
        }
      }

      // Sort By
      if ($q->get('sort')) {
        if ($q->get('sort') == 'nearby') {
          $raws = $datas->get();
          $myLocation = User::where([
            ["id", "=", $request->get('id')]
          ])->select('location')->first()->location;
          foreach ($raws as $raw) {
            $raw["distance"] = LocationHelper::haversine(
              explode(',', $myLocation),
              explode(',', $raw["location"])
            );
          }

          $nearby = $raws->sortBy('distance');

          return response([
            "status" => true,
            "data" => $nearby
          ]);

        }
        else {
          return response([
            "status" => false,
            "message" => "invalid sort value"
          ]);
        }
      }

      return response([
        "status" => true,
        "data" => $datas->get()
      ]);
    }

    public function buy(Request $request)
    {
      $menu_id = $request->menu_id;
      $user_id = $request->get('id');

      // Prepare Data
      $menuInfo = Menu::find($menu_id);
      $resto_id = $menuInfo->restaurant_id;
      $menuPrice = $menuInfo->price;
      $myInfo = User::find($user_id);
      $restoInfo = Restaurant::find($resto_id);
      $myBalance = $myInfo->balance;
      $restoBalance = $restoInfo->balance;

      // Logic Exec
      $userBalanceAfterBuy = $myBalance - $menuPrice;
      $restoBalanceAfterBuy = $restoBalance + $menuPrice;
      if ($userBalanceAfterBuy < 0) return response([
        "status" => false,
        "message" => "your balance is not enough"
      ]);

      // Update User Balance
      $myInfo->balance = $userBalanceAfterBuy;
      $myInfo->save();

      // Update Resto Balance
      $restoInfo->balance = $restoBalanceAfterBuy;
      $restoInfo->save();

      Purchase::create([
        "user_id" => $user_id,
        "menu_id" => $menu_id,
        "date" => Carbon::now()
      ]);

      return response([
        "status" => true
      ]);
    }
}
