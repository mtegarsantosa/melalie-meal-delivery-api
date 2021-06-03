<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Restaurant;
use App\Menu;

class RestaurantController extends Controller
{
    public function import()
    {
        set_time_limit(0);
        try {
          $datas = json_decode(file_get_contents('../database/json_import/restaurants.json'), true);
          foreach ($datas as $data) {
            $storeRestaurant = Restaurant::storeImport($data["name"], $data["location"], $data["balance"], $data["business_hours"]);
            foreach ($data["menu"] as $menu) {
              Menu::storeImport($storeRestaurant->id, $menu["name"], $menu["price"]);
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
      $datas = Restaurant::where([
        ['id', '=', $request->get('id')]
      ]);
      $q = $request->query;

      // Get Restaurants with Transactions
      if ($q->get('with_trs') && $q->get('with_trs') == 1) {
        $datas->with([
          'transactions' => function ($q) {
            $q->with('menu', 'user');
          }
        ]);
      }

      return response([
        "status" => true,
        "data" => $datas->get()
      ]);
    }
}
