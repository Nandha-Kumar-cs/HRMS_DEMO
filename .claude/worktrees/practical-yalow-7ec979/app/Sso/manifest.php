<?php

/**
 * MagDyn SSO — HRMS Permissions Manifest
 *
 * Naming convention: resource.action
 *
 * After adding or removing permissions here, run:
 *   php artisan sso:sync-permissions
 *   — or —
 *   php public/sync_permissions.php
 *
 * Do NOT delete permissions that are already assigned to users in the SSO
 * admin panel; remove them from this file only after revoking them there.
 */

return [

    // ── Permissions ──────────────────────────────────────────────────────────

    'permissions' => [

        // Dashboard
        ['name' => 'dashboard.view',              'description' => 'View the HR dashboard'],

        // Employees
        ['name' => 'employees.view',              'description' => 'View employee profiles'],
        ['name' => 'employees.create',            'description' => 'Add new employees'],
        ['name' => 'employees.edit',              'description' => 'Edit employee details'],
        ['name' => 'employees.delete',            'description' => 'Delete employee records'],
        ['name' => 'employees.import',            'description' => 'Bulk-import employees via CSV'],

        // Attendance
        ['name' => 'attendance.view',             'description' => 'View attendance records'],
        ['name' => 'attendance.manage',           'description' => 'Mark / edit attendance'],
        ['name' => 'attendance.import',           'description' => 'Import attendance from CSV'],
        ['name' => 'attendance.report',           'description' => 'View attendance reports'],

        // Leave requests
        ['name' => 'leave-requests.view',         'description' => 'View leave requests'],
        ['name' => 'leave-requests.create',       'description' => 'Submit leave requests'],
        ['name' => 'leave-requests.approve',      'description' => 'Approve or reject leave requests'],

        // Holidays
        ['name' => 'holidays.view',               'description' => 'View holiday calendar'],
        ['name' => 'holidays.manage',             'description' => 'Add, remove, or import holidays'],

        // On-duty & Comp-off
        ['name' => 'on-duties.view',              'description' => 'View on-duty requests'],
        ['name' => 'on-duties.manage',            'description' => 'Create or delete on-duty records'],
        ['name' => 'comp-offs.view',              'description' => 'View comp-off records'],
        ['name' => 'comp-offs.manage',            'description' => 'Grant or avail comp-offs'],

        // Payroll / Salary slips
        ['name' => 'salary-slips.view',           'description' => 'View salary slips'],
        ['name' => 'salary-slips.create',         'description' => 'Generate salary slips'],
        ['name' => 'salary-slips.delete',         'description' => 'Delete salary slips'],
        ['name' => 'salary-slips.calculate',      'description' => 'Run payroll calculations'],

        // Salary components
        ['name' => 'salary-components.manage',    'description' => 'Manage salary component definitions'],

        // Increments & Promotions
        ['name' => 'increments.view',             'description' => 'View increment history'],
        ['name' => 'increments.manage',           'description' => 'Create and update increments'],
        ['name' => 'promotions.view',             'description' => 'View promotion history'],
        ['name' => 'promotions.manage',           'description' => 'Create and update promotions'],

        // Loans & Advances
        ['name' => 'loans.view',                  'description' => 'View loan records'],
        ['name' => 'loans.create',                'description' => 'Disburse new loans'],
        ['name' => 'loans.edit',                  'description' => 'Edit loan details'],
        ['name' => 'loans.delete',                'description' => 'Delete loan records'],

        // Company Assets
        ['name' => 'assets.view',                 'description' => 'View company assets'],
        ['name' => 'assets.manage',               'description' => 'Create, assign, and return assets'],

        // Employee Documents
        ['name' => 'employee-documents.view',     'description' => 'View employee documents'],
        ['name' => 'employee-documents.manage',   'description' => 'Upload or delete employee documents'],

        // Employee Benefits
        ['name' => 'employee-benefits.view',      'description' => 'View employee benefits'],
        ['name' => 'employee-benefits.manage',    'description' => 'Create and update employee benefits'],

        // Bonuses & Incentives
        ['name' => 'employee-bonuses.view',       'description' => 'View employee bonuses'],
        ['name' => 'employee-bonuses.create',     'description' => 'Create bonus / incentive records'],
        ['name' => 'employee-bonuses.approve',    'description' => 'Approve or reject bonus requests'],

        // Letters
        ['name' => 'offer-letters.view',          'description' => 'View offer letters'],
        ['name' => 'offer-letters.create',        'description' => 'Generate offer letters'],
        ['name' => 'confirmation-letters.view',   'description' => 'View confirmation letters'],
        ['name' => 'confirmation-letters.create', 'description' => 'Generate confirmation letters'],
        ['name' => 'increment-letters.view',      'description' => 'View increment letters'],
        ['name' => 'increment-letters.create',    'description' => 'Generate increment letters'],

        // No-due Certificates
        ['name' => 'no-due.view',                 'description' => 'View no-due certificates'],
        ['name' => 'no-due.manage',               'description' => 'Create and approve no-due certificates'],

        // Training
        ['name' => 'training.view',               'description' => 'View training modules and lessons'],
        ['name' => 'training.manage',             'description' => 'Create and manage training content'],
        ['name' => 'training.complete',           'description' => 'Mark training lessons as complete'],

        // Reports
        ['name' => 'reports.view',                'description' => 'View all reports'],
        ['name' => 'reports.benefits',            'description' => 'View benefit reports'],
        ['name' => 'reports.payroll',             'description' => 'View payroll impact reports'],

        // Bank details
        ['name' => 'bank-details.view',           'description' => 'View employee bank details'],
        ['name' => 'bank-details.manage',         'description' => 'Edit employee bank details'],

        // Family members
        ['name' => 'family-members.view',         'description' => 'View employee family members'],
        ['name' => 'family-members.manage',       'description' => 'Edit employee family members'],

        // Activity log
        ['name' => 'activity-logs.view',          'description' => 'View the system activity log'],

        // Settings (admin only)
        ['name' => 'settings.manage',             'description' => 'Manage system settings (entities, departments, designations, leave types, etc.)'],
        ['name' => 'users.manage',                'description' => 'Create and manage HRMS user accounts'],
        ['name' => 'roles.manage',                'description' => 'Manage roles and granular permissions'],
        ['name' => 'mobile-access.manage',        'description' => 'Configure mobile / PWA module access'],
    ],

    // ── Default roles ─────────────────────────────────────────────────────────
    //
    // Only used to seed initial role → permission mappings on first sync.
    // Existing roles in the SSO are NOT overwritten.

    'roles' => [
        [
            'name'        => 'admin',
            'description' => 'Full HRMS access — all modules and settings',
            'permissions' => [
                'dashboard.view',
                'employees.view', 'employees.create', 'employees.edit', 'employees.delete', 'employees.import',
                'attendance.view', 'attendance.manage', 'attendance.import', 'attendance.report',
                'leave-requests.view', 'leave-requests.create', 'leave-requests.approve',
                'holidays.view', 'holidays.manage',
                'on-duties.view', 'on-duties.manage',
                'comp-offs.view', 'comp-offs.manage',
                'salary-slips.view', 'salary-slips.create', 'salary-slips.delete', 'salary-slips.calculate',
                'salary-components.manage',
                'increments.view', 'increments.manage',
                'promotions.view', 'promotions.manage',
                'loans.view', 'loans.create', 'loans.edit', 'loans.delete',
                'assets.view', 'assets.manage',
                'employee-documents.view', 'employee-documents.manage',
                'employee-benefits.view', 'employee-benefits.manage',
                'employee-bonuses.view', 'employee-bonuses.create', 'employee-bonuses.approve',
                'offer-letters.view', 'offer-letters.create',
                'confirmation-letters.view', 'confirmation-letters.create',
                'increment-letters.view', 'increment-letters.create',
                'no-due.view', 'no-due.manage',
                'training.view', 'training.manage', 'training.complete',
                'reports.view', 'reports.benefits', 'reports.payroll',
                'bank-details.view', 'bank-details.manage',
                'family-members.view', 'family-members.manage',
                'activity-logs.view',
                'settings.manage', 'users.manage', 'roles.manage', 'mobile-access.manage',
            ],
        ],
        [
            'name'        => 'manager',
            'description' => 'Manage team attendance, leaves, payroll, and reports',
            'permissions' => [
                'dashboard.view',
                'employees.view', 'employees.create', 'employees.edit',
                'attendance.view', 'attendance.manage', 'attendance.import', 'attendance.report',
                'leave-requests.view', 'leave-requests.create', 'leave-requests.approve',
                'holidays.view',
                'on-duties.view', 'on-duties.manage',
                'comp-offs.view', 'comp-offs.manage',
                'salary-slips.view', 'salary-slips.create', 'salary-slips.calculate',
                'increments.view', 'increments.manage',
                'promotions.view', 'promotions.manage',
                'loans.view', 'loans.create', 'loans.edit',
                'assets.view', 'assets.manage',
                'employee-documents.view', 'employee-documents.manage',
                'employee-benefits.view', 'employee-benefits.manage',
                'employee-bonuses.view', 'employee-bonuses.create', 'employee-bonuses.approve',
                'offer-letters.view', 'offer-letters.create',
                'confirmation-letters.view', 'confirmation-letters.create',
                'increment-letters.view', 'increment-letters.create',
                'no-due.view', 'no-due.manage',
                'training.view', 'training.complete',
                'reports.view', 'reports.benefits', 'reports.payroll',
                'bank-details.view', 'bank-details.manage',
                'family-members.view', 'family-members.manage',
            ],
        ],
        [
            'name'        => 'staff',
            'description' => 'View own records, submit leaves and on-duty requests',
            'permissions' => [
                'dashboard.view',
                'employees.view',
                'attendance.view',
                'leave-requests.view', 'leave-requests.create',
                'holidays.view',
                'on-duties.view', 'on-duties.manage',
                'comp-offs.view',
                'salary-slips.view',
                'increments.view',
                'promotions.view',
                'loans.view',
                'assets.view',
                'employee-documents.view',
                'employee-benefits.view',
                'employee-bonuses.view',
                'offer-letters.view',
                'confirmation-letters.view',
                'increment-letters.view',
                'no-due.view',
                'training.view', 'training.complete',
                'reports.view',
            ],
        ],
    ],

];
