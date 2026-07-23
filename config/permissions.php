<?php

// The full catalog of permissions the platform understands. This is the
// single source of truth consumed by PermissionSeeder — modules are not
// yet built (CRM, Sales, etc. are deliberately out of scope for this
// foundation per the brief), but the 'admin' and 'core' modules below are
// real and enforced today. Future modules add their own block here; no
// other file needs to change for a role to be able to grant them, because
// CheckPermission and the role-management endpoints read this catalog
// dynamically rather than hardcoding module/action pairs.

return [

    'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export', 'print'],

    'modules' => [
        'admin' => [
            'label' => 'Administration',
            'actions' => ['view', 'create', 'edit', 'delete'],
            'covers' => ['users', 'roles', 'permissions', 'departments', 'branches', 'companies', 'company_settings'],
        ],
        'dashboard' => [
            'label' => 'Dashboard',
            'actions' => ['view'],
            'covers' => ['dashboard'],
        ],
        'core' => [
            'label' => 'Platform',
            'actions' => ['view', 'export'],
            'covers' => ['audit_logs', 'activity_logs'],
        ],
        'crm' => [
            'label' => 'CRM',
            'actions' => ['view', 'create', 'edit', 'delete', 'export'],
            'covers' => ['leads', 'lead_sources', 'lead_statuses', 'lead_activities', 'customers', 'customer_activities', 'opportunities', 'opportunity_stages', 'opportunity_activities'],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'actions' => ['view', 'create', 'edit', 'delete', 'export'],
            'covers' => ['products', 'product_categories', 'units', 'brands', 'warehouses', 'stock_levels', 'stock_movements', 'stock_transfers', 'stock_adjustments', 'goods_receipts', 'goods_issues'],
        ],
        'purchase' => [
            'label' => 'Purchase',
            'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'covers' => ['suppliers', 'purchase_orders', 'supplier_bills', 'supplier_payments', 'debit_notes', 'purchase_returns'],
        ],
        'sales' => [
            'label' => 'Sales',
            'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export', 'print'],
            'covers' => ['quotations', 'sales_orders', 'sales_invoices', 'delivery_notes', 'customer_payments', 'payment_allocations', 'credit_notes', 'sales_returns'],
        ],
        'accounting' => [
            'label' => 'Accounting',
            'actions' => ['view', 'create', 'edit', 'export'],
            'covers' => ['chart_of_accounts', 'journal_entries'],
        ],
        'reports' => [
            'label' => 'Reports',
            'actions' => ['view', 'create', 'edit', 'delete', 'export'],
            'covers' => [
                'sales_reports', 'purchase_reports', 'inventory_reports', 'accounting_reports',
                'payroll_reports', 'crm_reports', 'cash_flow', 'vat_reports', 'executive_dashboard',
                'kpi_dashboard', 'custom_reports', 'scheduled_reports',
            ],
        ],
        'ai' => [
            'label' => 'AI Assistant',
            'actions' => ['view', 'edit', 'delete'],
            'covers' => [
                'ai_assistant', 'ai_insights', 'ai_suggestions', 'ai_settings',
                'ai_prompt_templates', 'ai_activity_logs',
            ],
        ],
        'hr_payroll' => [
            'label' => 'HR & Payroll',
            'actions' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'covers' => [
                'employees', 'designations', 'shifts', 'holidays', 'attendance',
                'leave_types', 'leave_requests', 'salary_components', 'employee_salary_components',
                'overtime_records', 'payroll_runs', 'payslips', 'job_openings', 'candidates',
                'job_applications', 'performance_review_cycles', 'performance_reviews',
            ],
        ],
        'ess' => [
            'label' => 'Employee Self-Service',
            'actions' => ['view', 'create'],
            'covers' => ['own_profile', 'own_attendance', 'own_payslips', 'own_leave_requests'],
        ],
        // 'sales', 'purchase', 'inventory', 'accounting', 'hr_payroll',
        // 'reports', 'ai_assistant' are intentionally NOT defined yet —
        // CRM Sprint 1 (Lead Management) ships before those modules
        // exist. Adding one is: append a block here + seed its role
        // defaults in RoleProvisioningService — no other code changes.
    ],
];
