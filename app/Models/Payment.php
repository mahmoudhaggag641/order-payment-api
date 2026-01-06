<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'amount',
        'status',
        'gateway',
        'gateway_response',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'gateway_response' => 'json',
        'metadata' => 'json',
    ];

    protected $attributes = [
        'status' => PaymentStatus::PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            $payment->uuid = Str::uuid();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function markAsSuccessful(array $response = []): self
    {
        $this->update([
            'status' => PaymentStatus::SUCCESSFUL,
            'gateway_response' => $response,
            'processed_at' => now(),
        ]);

        return $this;
    }

    public function markAsFailed(array $response = []): self
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'gateway_response' => $response,
            'processed_at' => now(),
        ]);

        return $this;
    }

    #[Scope]
    public function status($query, PaymentStatus $status)
    {
        return $query->where('status', $status->value);
    }

    #[Scope]
    public function gateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}
