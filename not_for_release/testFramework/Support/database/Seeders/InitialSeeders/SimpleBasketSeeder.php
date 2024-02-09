<?php

namespace Seeders\InitialSeeders;

use App\Models\Basket;
use App\Models\BasketAttribute;
use App\Models\BasketProduct;
use Illuminate\Database\Seeder;

class SimpleBasketSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        $basket = new Basket();
        $basket->customer_id = 1;
        $basket->save();

        $basketProduct = new BasketProduct();
        $basketProduct->basket_id = $basket->id;
        $basketProduct->product_id = 1;
        $basketProduct->quantity = 1000;
        $basketProduct->save();

        $basketAttribute = new BasketAttribute();
        $basketAttribute->basket_product_id = $basketProduct->id;
        $basketAttribute->options_id = 4;
        $basketAttribute->options_values_id = 1;
        $basketAttribute->options_sort_order = 0;
        $basketAttribute->save();

        $basketProduct = new BasketProduct();
        $basketProduct->basket_id = $basket->id;
        $basketProduct->product_id = 12;
        $basketProduct->quantity = 100;
        $basketProduct->save();

    }
}
