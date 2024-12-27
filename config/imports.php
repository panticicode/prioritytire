<?php

return [
    'orders' => [
        'label' => 'Import Orders',
        'permission_required' => 'import_orders_access',
        'files' => [
            'file1' => [
                'label' => 'File 1',
                'headers_to_db' => [
                    'order_date' => [
                        'label' => 'Order Date',
                        'type' => 'date',
                        'validation' => ['required', 'date_format:d.m.Y'],
                    ],
                    'channel' => [
                        'label' => 'Channel',
                        'type' => 'string',
                        'validation' => ['required', 'in:PT,Amazon'],
                    ],
                    'sku' => [
                        'label' => 'SKU',
                        'type' => 'string',
                        'validation' => ['required', 'exists:products,sku'],
                    ],
                    'item_description' => [
                        'label' => 'Item Description',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                    'origin' => [
                        'label' => 'Origin',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'so_num' => [
                        'label' => 'SO#',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'cost' => [
                        'label' => 'Cost',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                    'shipping_cost' => [
                        'label' => 'Shipping Cost',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                    'total_price' => [
                        'label' => 'Total Price',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                ],
                'update_or_create' => ['so_num', 'sku']
            ],
        ],
    ],
    'items' => [
        'label' => 'Import Items',
        'permission_required' => 'import_items_access',
        'files' => [
            'file1' => [
                'label' => 'File 1',
                'headers_to_db' => [
                    'item_id' => [
                        'label' => 'Item ID',
                        'type' => 'string',
                        'validation' => ['required', 'unique:items,item_id'],
                    ],
                    'name' => [
                        'label' => 'Name',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'category' => [
                        'label' => 'Category',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'price' => [
                        'label' => 'Price',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                    'stock' => [
                        'label' => 'Stock',
                        'type' => 'integer',
                        'validation' => ['required'],
                    ],
                ],
                'update_or_create' => ['item_id']
            ],
        ],
    ],
    'clients_and_sales' => [
        'label' => 'Import Clients and Sales',
        'permission_required' => 'import_clients_and_sales_access',
        'files' => [
            'clients' => [
                'label' => 'File 1',
                'headers_to_db' => [
                    'client_id' => [
                        'label' => 'Client ID',
                        'type' => 'string',
                        'validation' => ['required', 'unique:clients,client_id'],
                    ],
                    'name' => [
                        'label' => 'Name',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'email' => [
                        'label' => 'Email',
                        'type' => 'string',
                        'validation' => ['required', 'email', 'unique:clients,email'],
                    ],
                    'phone' => [
                        'label' => 'Phone',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                ],
                'update_or_create' => ['client_id', 'email']
            ],
            'sales' => [
                'label' => 'File 2',
                'headers_to_db' => [
                    'sale_id' => [
                        'label' => 'Sale ID',
                        'type' => 'string',
                        'validation' => ['required', 'unique:sales,sale_id'],
                    ],
                    'client_id' => [
                        'label' => 'Client ID',
                        'type' => 'string',
                        'validation' => ['required', 'exists:clients,client_id'],
                    ],
                    'sale_date' => [
                        'label' => 'Sale Date',
                        'type' => 'date',
                        'validation' => ['required', 'date_format:Y-m-d'],
                    ],
                    'total' => [
                        'label' => 'Total',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                ],
                'update_or_create' => ['sale_id']
            ],
        ],
    ],
];