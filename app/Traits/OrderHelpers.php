<?php

namespace App\Traits;

trait OrderHelpers
{
    public const ORDER_STATUS_DRAFT = 'draft';
    public const ORDER_STATUS_REQUESTED = 'requested';
    public const ORDER_STATUS_DELIVERED = 'delivered';
    public const ORDER_STATUS_DELIVERED_TO_OUTSIDE = 'delivered_to_outside';
    public const ORDER_STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::ORDER_STATUS_DRAFT,
        self::ORDER_STATUS_REQUESTED,
        self::ORDER_STATUS_DELIVERED,
        self::ORDER_STATUS_DELIVERED_TO_OUTSIDE,
        self::ORDER_STATUS_REJECTED,
    ];

    public function isDraft(): bool
    {
        return $this->order_status === self::ORDER_STATUS_DRAFT;
    }

    public function isRequested(): bool
    {
        return $this->order_status === self::ORDER_STATUS_REQUESTED;
    }

    public function isDelivered(): bool
    {
        return $this->order_status === self::ORDER_STATUS_DELIVERED;
    }
    public function isDeliveredToOutside(): bool
    {
        return $this->order_status === self::ORDER_STATUS_DELIVERED_TO_OUTSIDE;
    }
    public function isRejected(): bool
    {
        return $this->order_status === self::ORDER_STATUS_REJECTED;
    }
}
