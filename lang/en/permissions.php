<?php

return [
    'messages' => [
        'created' => 'Permission created successfully.',
        'updated' => 'Permission updated successfully.',
        'deleted' => 'Permission deleted successfully.',
        'forbidden' => 'You do not have permission to perform this action.',
        'system_locked' => 'System permissions (is_system = true) cannot be deleted.',
        'invalid_role' => 'Invalid role.',
        'invalid_permission_ids' => 'permission_ids contains one or more unknown ids.',
        'role_synced' => 'Role permissions updated successfully.',
        'role_mismatch' => 'The role in the body must match the role in the URL.',
    ],
    'labels' => [
        'code' => 'Permission code',
        'resource' => 'Resource',
        'action' => 'Action',
        'name' => 'Display name',
        'description' => 'Description',
        'is_system' => 'System permission',
        'role' => 'Role',
        'permission_ids' => 'Permission list',
    ],
    'roles' => [
        'user' => 'User',
        'staff' => 'Staff',
        'manager' => 'Manager',
    ],
];
