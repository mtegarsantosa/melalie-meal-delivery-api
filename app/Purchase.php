<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model {
    protected $fillable = [
      'user_id',
      'menu_id',
      'date'
    ];

    public function menu()
    {
      return $this->belongsTo('App\Menu');
    }
    public function user()
    {
      return $this->belongsTo('App\User');
    }

    static function storeImport($user_id, $menu_id, $date)
    {
      return Purchase::updateOrCreate([
        'user_id' => $user_id,
        'menu_id' => $menu_id,
        'date' => $date
      ]);
    }

    protected $hidden = [
       'laravel_through_key',
       'created_at',
       'updated_at'
    ];

}
