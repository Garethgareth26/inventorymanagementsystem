<?php

/**
 * RBAC Capability Map
 *
 * Maps role slugs to the list of capabilities they possess.
 * This is the single source of truth for authorization decisions
 * across the entire application (SAD ADR-003).
 *
 * Adding a new role:
 *  1. Insert a row into the `roles` table.
 *  2. Add an entry to this array with the new role's slug.
 *  No policy class rewrites or schema migrations are required.
 */
return [

    'karyawan' => [
        // Master data management
        'supplier.view',
        'supplier.manage',
        'material.view',
        'material.manage',
        'finished-good.view',
        'finished-good.manage',
        'bom.view',
        'bom.manage',

        // Inventory & stock
        'stock.view',
        'stock.mutate',
        'stock.adjust',

        // Procurement
        'procurement.view',
        'procurement.manage',

        // Production
        'production.view',
        'production.record',

        // Inventory optimization
        'parameter.view',
        'parameter.simulate',
        'parameter.apply',

        // Reports
        'report.view',
        'report.generate',
        'report.download',
    ],

    'owner' => [
        // Master data – read only
        'supplier.view',
        'material.view',
        'finished-good.view',
        'bom.view',

        // Inventory & stock – read only
        'stock.view',

        // Procurement – read only
        'procurement.view',

        // Production – read only
        'production.view',

        // Inventory optimization – simulate only, cannot apply
        'parameter.view',
        'parameter.simulate',

        // Reports – both roles can generate & download (PRD §6.6)
        'report.view',
        'report.generate',
        'report.download',

        // Administration (the one area Owner has write access)
        'user.manage',
        'settings.manage',
    ],

];
