<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Restaurant;
use App\User;

class AdminController extends Controller
{
  public function restaurantsIndex(Request $request)
  {
    $datas = Restaurant::where([]);
    $q = $request->query;

    // Sort Restaurant (By TOP Amount pr TOP Transaction Total)
    if ($q->get('topBy')) {
      if ($q->get('topBy') == 'tr_amount') {
        $datas->with([
          'transactions' => function ($q) {
            $q->with([
              'menu'
            ]);
          }
        ]);
      }
      elseif ($q->get('topBy') == 'tr_volume') {
        $datas->withCount('transactions')->orderBy('transactions_count', 'DESC');
      }
    }

    return response([
      "status" => true,
      "data" => $datas->get()
    ]);
  }

  public function usersIndex(Request $request)
  {
    $datas = User::where([]);
    $q = $request->query;

    // Sort By Transactions
    if ($q->get('sort')) {
      if (!$q->get('date_range')) return response([
        "status" => false,
        "message" => "please insert date_range in query"
      ]);
      if ($q->get('sort') == 'tr_amount') {
        $dateRange = explode('-', $q->get('date_range'));
        $datas->with([
          'transactions' => function ($q) use ($dateRange) {
            $q->whereBetween('date', [$dateRange[0], $dateRange[1]]);
          }
        ]);
        $datas->withCount([
          'transactions' => function ($q) use ($dateRange) {
            $q->whereBetween('date', [$dateRange[0], $dateRange[1]]);
          }
        ])->orderBy('transactions_count', 'DESC');
      }
      else return response([
        "status" => false,
        "message" => "invalid sort value"
      ]);
    }

    return response([
      "status" => true,
      "data" => $datas->get()
    ]);
  }

  public function userRecap(Request $request)
  {
    $datas = User::where([]);
    $q = $request->query;

    // Transaction Volume
    if ($q->get('tr_volume')) {
      $tr_operator = $q->get('tr_volume')[0];
      $tr_volume = explode($tr_operator, $q->get('tr_volume'))[1];
      $dateRange = explode('-', $q->get('date_range'));
      $datas->has('transactions', $tr_operator, $tr_volume);
      $datas->with([
        'transactions' => function ($q) use ($dateRange) {
          $q->whereBetween('date', [$dateRange[0], $dateRange[1]]);
        }
      ]);
      $datas->withCount('transactions')->orderBy('transactions_count', 'DESC');
    }
    return response([
      "status" => true,
      "total" => $datas->count()
    ]);
  }

}
