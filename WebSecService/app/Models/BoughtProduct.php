<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoughtProduct extends Model  {

    protected $table = 'bought_products';

	protected $fillable = [
        'user_id',
        'product_id',
        'created_at',
        'updated_at'

    ];
        // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
