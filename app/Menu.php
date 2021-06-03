<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model {
    protected $fillable = [
      'restaurant_id',
      'name',
      'price'
    ];

    public function restaurant()
    {
      return $this->belongsTo('App\Restaurant');
    }

    public function purchase()
    {
      return $this->belongsTo('App\Purchase');
    }

    static function storeImport($restaurant_id, $name, $price)
    {
      return Menu::updateOrCreate([
        'restaurant_id' => $restaurant_id,
        'name' => $name,
        'price' => $price
      ]);
    }

    protected $hidden = [
      'laravel_through_key',
      'created_at',
      'updated_at'
    ];
}
