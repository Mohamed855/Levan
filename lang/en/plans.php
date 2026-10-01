<?php

declare(strict_types=1);

return [
    'title'          => 'Plans',
    'single'         => 'Plan',
    'create'         => 'Create New Plan',
    'edit'           => 'Edit Plan',
    'show'           => 'Plan Details',
    'code'           => 'Plan Code',
    'plan_type'      => 'Plan Type',
    'color'          => 'Color',
    'limits_mode'    => 'Limits Mode',
    'limited'        => 'Limited',
    'unlimited'      => 'Unlimited',
    'available_from' => 'Available From',
    'admin_notes'    => 'Admin Notes',
    'most_requested' => 'Most Requested',
    'annual_offer'   => 'Annual Offer',
    'errors'         => [
        'cannot_delete_used_plan'       => 'Cannot delete a plan associated with existing or historical subscriptions. Please deactivate the plan instead.',
        'cannot_force_delete_used_plan' => 'Cannot permanently delete a plan associated with subscriptions.',
        'cannot_modify_locked_fields'   => 'Cannot modify code, billing cycle, or limits mode for a plan with subscriptions.',
    ],
    'success' => [
        'created'       => 'Plan created successfully.',
        'updated'       => 'Plan updated successfully.',
        'deleted'       => 'Plan deleted successfully.',
        'status_toggle' => 'Plan status updated successfully.',
        'reordered'     => 'Plan ordering updated successfully.',
    ],
];
