<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'amount' => $this->amount,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'gateway' => $this->gateway,
            'metadata' => $this->metadata,
            // 'gateway_response' => $this->when(isset($this->gateway_response), $this->gateway_response),
            'processed_at' => $this->when(isset($this->processed_at), toDateTime($this->processed_at)),
            'created_at' => $this->when(isset($this->created_at), toDateTime($this->created_at)),
            'updated_at' => $this->when(isset($this->updated_at), toDateTime($this->updated_at)),
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}
