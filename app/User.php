<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class User extends Model {
    protected $fillable = [
      'name',
      'location',
      'balance'
    ];

    public function transactions()
    {
      return $this->hasMany(
        'App\Purchase'
      );
    }

    static function storeImport($name, $location, $balance)
    {
      return User::updateOrCreate([
        'name' => $name,
        'location' => $location,
        'balance' => $balance
      ]);
    }

    protected $hidden = [
      'created_at',
      'updated_at'
    ];
}
