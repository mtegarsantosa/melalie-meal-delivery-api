<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model {
    protected $fillable = [
      'name',
      'location',
      'balance',
      'business_hours'
    ];

    public function menus()
    {
      return $this->hasMany('App\Menu');
    }

    public function transactions()
    {
      return $this->hasManyThrough(
        'App\Purchase', 'App\Menu'
      );
    }

    static function storeImport($name, $location, $balance, $business_hours)
    {
      return Restaurant::updateOrCreate([
        'name' => $name,
        'location' => $location,
        'balance' => $balance,
        'business_hours' => $business_hours
      ]);
    }

    protected $hidden = [
      'created_at',
      'updated_at'
    ];
}
