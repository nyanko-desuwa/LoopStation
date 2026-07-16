<?php

return [
    'messages' => [
        'created' => 'Reward redeemed successfully.',
        'cancelled' => 'Redemption cancelled. Points refunded.',
        'shipping' => 'Redemption marked as shipping.',
        'fulfilled' => 'Redemption fulfilled successfully.',
        'reward_created' => 'Catalog reward created successfully.',
        'reward_updated' => 'Catalog reward updated successfully.',
        'reward_deleted' => 'Catalog reward deleted successfully.',
        'invalid_method' => 'Invalid fulfillment method.',
        'delivery_fields_required' => 'Delivery requires recipient name, phone and address.',
        'invalid_reward' => 'Reward not found.',
        'reward_locked' => 'Reward is locked and cannot be redeemed.',
        'out_of_stock' => 'Not enough stock for this reward.',
        'not_delivery' => 'Only delivery orders can be marked as shipping.',
        'not_pending' => 'Only pending orders can be processed this way.',
        'already_closed' => 'The redemption is already closed.',
        'not_cancellable' => 'This redemption cannot be cancelled.',
        'spend_description' => 'Redeem: :name x:qty',
        'refund_description' => 'Refund for cancelled redemption #:id',
    ],
    'labels' => [
        'reward_id' => 'Reward',
        'quantity' => 'Quantity',
        'fulfillment_method' => 'Fulfillment method',
        'points_cost' => 'Points cost',
        'stock' => 'Stock',
    ],
    'statuses' => [
        'pending' => 'Pending',
        'shipping' => 'Shipping',
        'fulfilled' => 'Fulfilled',
        'cancelled' => 'Cancelled',
    ],
    'methods' => [
        'pickup' => 'Pickup',
        'delivery' => 'Delivery',
    ],
];
