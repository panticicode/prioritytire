<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Product;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::truncate();
        
        $faker = Faker::create();

        $skus = [
            'N12345-99',
            'N12345-88',
            'N12345-77',
            'N12345-66',
        ];

        $datas = [];

        foreach ($skus as $sku) 
        {
            $datas[] = [
                'name'        => $faker->words(3, true),
                'sku'         => $sku,
                'description' => $faker->sentence
            ];
        }

        foreach ($datas as $data) 
        {
            Product::create($data);
        }
    }
}
