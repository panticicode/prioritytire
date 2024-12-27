<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::truncate();
        
        $datas = [
            [
                'name'        => 'Test Product',
                'sku'         => 'N12345-99',
                'description' => 'Test Product Description'
            ],
            [
                'name'        => 'Test Product 2',
                'sku'         => 'N12345-88',
                'description' => 'Test Product Description 2'
            ]
        ];

        foreach($datas as $data)
        {
            Product::create($data);
        }
    }
}
