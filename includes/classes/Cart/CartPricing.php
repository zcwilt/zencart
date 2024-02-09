<?php

namespace Zencart\Cart;

use App\Models\BasketProduct;
use App\Models\Product;

class CartPricing
{
    protected float $productPrice;
    protected float $specialPrice;
    protected float $productRawPrice;

    public function __construct()
    {
        $this->productPrice = 0;
        $this->specialPrice = 0;
        $this->productRawPrice = 0;
    }


    public function calculateProductPricing(BasketProduct $basketProduct)
    {
        $product = $basketProduct->product;
        $prid = zen_get_prid($product['products_id']);
        $this->productPrice = $this->productRawPrice = zen_get_retail_or_wholesale_price($product['products_price'], $product['products_price_w']);
        $this->specialPrice = zen_get_products_special_price($prid);
        if ($this->specialPrice && $product['products_priced_by_attribute'] === '0') {
            $this->productPrice = $this->specialPrice;
        } else {
            $this->specialPrice = 0;
        }
        $this->productPrice = zen_get_products_price_is_free($prid) ? 0 : $this->productPrice;
        $this->adjustWhenPricedByAttribute($product, $prid);
    }

    protected function adjustWhenPricedByAttribute(Product $product, $prid): void
    {
        if ($product['products_priced_by_attribute'] === '1' && zen_has_product_attributes($prid, false)) {
            if ($this->specialPrice) {
                $this->productPrice = $this->specialPrice;
            } else {
                $this->productPrice = $this->productRawPrice;
            }
        } elseif ($product['products_discount_type'] !== '0') {  // discount qty pricing
            $this->productPrice = zen_get_products_discount_price_qty($prid, $product['products_quantity']);
        }
    }

    // getters
    public function getProductPrice(): float
    {
        return $this->productPrice;
    }

    public function getProductRawPrice(): float
    {
        return $this->productRawPrice;
    }

    public function getSpecialPrice(): float
    {
        return $this->specialPrice;
    }
}
