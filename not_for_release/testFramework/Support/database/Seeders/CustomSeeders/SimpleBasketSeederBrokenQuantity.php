<?php

namespace Seeders\CustomSeeders;

use App\Models\Basket;
use App\Models\BasketAttribute;
use App\Models\BasketProduct;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class SimpleBasketSeederBrokenQuantity extends Seeder
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
        $basketProduct->quantity = -1;
        $basketProduct->save();
        $basketAttribute = new BasketAttribute();
        $basketAttribute->basket_product_id = $basketProduct->id;
        $basketAttribute->options_sort_order = 0;
        $basketAttribute->save();

    }
}
