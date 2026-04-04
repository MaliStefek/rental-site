<?php

declare(strict_types=1);

namespace App\Enums;

enum AppEvents: string
{
    case CATEGORY_ADDED = 'categoryAdded';
    case CATEGORY_UPDATED = 'categoryUpdated';
    case CATEGORY_DELETED = 'categoryDeleted';

    case TOOL_ADDED = 'toolAdded';
    case TOOL_UPDATED = 'toolUpdated';
    case TOOL_DELETED = 'toolDeleted';

    case INVENTORY_UPDATED = 'inventoryUpdated';

    case RENTAL_UPDATED = 'rentalUpdated';
}
