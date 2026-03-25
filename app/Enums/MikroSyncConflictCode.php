<?php

namespace App\Enums;

enum MikroSyncConflictCode: string
{
    case DUPLICATE_ORDER_CONFLICT = 'duplicate_order_conflict';
    case MISSING_SUPPLIER_MAPPING = 'missing_supplier_mapping';
    case INVALID_LINE_IDENTITY = 'invalid_line_identity';
    case ENDPOINT_PAYLOAD_MISMATCH = 'endpoint_payload_mismatch';
}
