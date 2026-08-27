<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Placed = 'placed';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case RiderAssigned = 'rider_assigned';
    case PickedUp = 'picked_up';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
