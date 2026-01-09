<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'status',
        'metadata',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'status' => OrderStatus::class,
        'metadata' => 'json',
    ];

    protected $attributes = [
        'status' => OrderStatus::PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            $payment->uuid = Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function canProcessPayment(): bool
    {
        return $this->status->canProcessPayment();
    }

    public function updateTotal(): self
    {
        $total = $this->items()->sum(DB::raw('quantity * price'));
        $this->total = $total;
        $this->save();

        return $this;
    }

    #[Scope]
    public function info($query)
    {
        // return $query->with('user:id,name,email', 'items:id,order_id,product_name,quantity,price');
        return $query->with('items:id,order_id,product_name,quantity,price');
    }

    #[Scope]
    public function summary($query)
    {
        // return $query->with('user:id,name,email')->withCount('items');
        return $query->withCount('items');
    }

    #[Scope]
    public function status($query, OrderStatus $status)
    {
        return $query->where('status', $status->value);
    }
}
