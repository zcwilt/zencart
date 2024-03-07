<?php

namespace Zencart\Cart;

use App\Models\Attribute;
use App\Models\BasketProduct;
use App\Models\Product;
use Zencart\Traits\NotifierManager;

class CartPricing
{
    use NotifierManager;

    protected float $productPrice;
    protected float $specialPrice;
    protected float $productRawPrice;
    protected string $uniqueProductId;
    protected int $realProductId;
    protected float $attributePrice;
    protected float $attributePriceOnetimeCharge;
    protected  $currencies;


    public function __construct()
    {
        global $currencies;
        $this->productPrice = 0;
        $this->specialPrice = 0;
        $this->productRawPrice = 0;
        $this->attributePrice = 0;
        $this->attributePriceOnetimeCharge = 0;
        $this->currencies = $currencies;
    }


    public function buildProductDetail(BasketProduct $basketProduct)
    {
        $this->uniqueProductId = $basketProduct['product_id'];
        $this->realProductId = (int)$basketProduct['product_id'];
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

    public function calculateAttributePricing(BasketProduct $basketProduct)
    {
        $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_START', $this->getRealProductId());
        if (!isset($basketProduct->product->attributes)) {
            return 0;
        }
        $productId = $this->getRealProductId();
        $quantity = $basketProduct['quantity'];
        zen_define_default('ATTRIBUTES_PRICE_FACTOR_FROM_SPECIAL', 1);
        foreach ($basketProduct->basketAttributes as $basketAttribute) {
            //var_dump($basketAttribute);
            $attribute = Attribute::getAttributeDetails($productId, $basketAttribute['options_id'], $basketAttribute['options_values_id'])->first();
            $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_NEXT', $productId, $attribute);
            $attributePrice = 0;
            $options_values_price = zen_get_retail_or_wholesale_price(
                $attribute['options_values_price'],
                $attribute['options_values_price_w']
            );
            $attributePrice = $this->addAdditionalAttributePrices($productId, $attribute, $attributePrice, $options_values_price, $quantity);

            if ($attribute['attributes_display_only']) {
                $_SESSION['valid_to_checkout'] = false;
                $_SESSION['cart_errors'] .= zen_get_products_name($productId, $_SESSION['languages_id']) . ERROR_PRODUCT_OPTION_SELECTION . '<br>';
            }
            $this->attributePriceOnetimeCharge += $this->calculateAttributePricingOnetimeCharge($attribute, $quantity);
            $this->attributePrice += zen_round($attributePrice, $this->currencies->get_decimal_places($_SESSION['currency']));
        }
    }

    protected function calculateAttributePricingOnetimeCharge($attribute, $quantity)
    {
        $attributePriceOnetime = 0;
        $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_ONETIME_CHARGES_NEXT', $this->getUniqueProductId(), $attribute);
        if ($attribute['product_attribute_is_free'] !== '1' || !zen_get_products_price_is_free($this->getRealProductId())) {
            return 0;
        }
        if ($attribute['attributes_price_onetime'] > 0) {
            $attributePriceOnetime += $attribute['attributes_price_onetime'];
        }
        if ($attribute['attributes_price_factor_onetime'] > 0) {
            $added_charge = zen_get_attributes_price_factor(
                zen_get_products_base_price($this->getRealProductId()),
                zen_get_products_special_price($this->getRealProductId(), false),
                $attribute['attributes_price_factor_onetime'],
                $attribute['attributes_price_factor_onetime_offset']
            );
            $attributePriceOnetime += $added_charge;
        }
        // attributes_qty_prices_onetime
        if (!empty($attribute['attributes_qty_prices_onetime'])) {
            $added_charge = zen_get_attributes_qty_prices_onetime($attribute['attributes_qty_prices_onetime'], $quantity);
            $attributePriceOnetime += $added_charge;
        }
        return $attributePriceOnetime;
    }
    protected function addAdditionalAttributePrices($productId, $attribute, $attributePrice, $options_values_price, $quantity)
    {
        if ($attribute['product_attribute_is_free'] === '1' && zen_get_products_price_is_free($productId)) {
            return $attributePrice;
        }
        $attributePrice = $this->attributePriceHandleDiscounted($productId, $attribute, $attributePrice, $options_values_price, $quantity);
        $attributePrice = $this->attributesPriceHandleTextPricing($attribute, $attributePrice);
        $attributePrice = $this->attributesPriceHandlePriceFactor($attribute, $attributePrice);
        $attributePrice = $this->attributePriceHandleQuantity($attribute, $attributePrice, $quantity);
        return $attributePrice;
    }

    protected function attributesPriceHandlePriceFactor($attribute, $attributePrice)
    {
        if ($attribute['attributes_price_factor'] <= 0) {
            return $attributePrice;
        }
        $added_charge = zen_get_attributes_price_factor(
            zen_get_products_base_price($this->getRealProductId()),
            zen_get_products_special_price($this->getRealProductId(), false),
            $attribute['attributes_price_factor'],
            $attribute['attributes_price_factor_offset']
        );
        $attributePrice += $added_charge;
        return $attributePrice;
    }

    protected function attributePriceHandleQuantity($attribute, $attributePrice, $quantity)
    {
        if (empty($attribute['attributes_qty_prices'])) {
        }
        $added_charge = zen_get_attributes_qty_prices_onetime($attribute['attributes_qty_prices'], $quantity);
        $attributePrice += $added_charge;
        return $attributePrice;
    }

    protected function attributePriceHandleDiscounted($productId, $attribute, $attributePrice, $options_values_price, $qty)
    {
        if ($attribute['price_prefix'] === '-') {
            $attributePrice = $this->attributePriceHandlePricePrefix($productId, $attribute, $attributePrice, $options_values_price, $qty);
        } elseif ($attribute['attributes_discounted'] === '1') {
            $attributePrice = $this->attributePriceHandlePrefixDiscount($productId, $attribute, $attributePrice, $options_values_price, $qty);
        } else {
            $attributePrice += zen_str_to_numeric($options_values_price);
        }
        return $attributePrice;
    }

    protected function attributesPriceHandleTextPricing($attribute, $attributePrice)
    {
        if (ATTRIBUTES_ENABLED_TEXT_PRICES !== 'true' || (string)zen_get_attributes_type($attribute['products_attributes_id']) !== (string)PRODUCTS_OPTIONS_TYPE_TEXT) {
            return $attributePrice;
        }
        $text_words = zen_get_word_count_price(
            $this->contents[$uprid]['attributes_values'][$attribute['options_id']],
            $attribute['attributes_price_words_free'],
            $attribute['attributes_price_words']
        );
        $text_letters = zen_get_letters_count_price(
            $this->contents[$uprid]['attributes_values'][$attribute['options_id']],
            $attribute['attributes_price_letters_free'],
            $attribute['attributes_price_letters']
        );
        $attributePrice += $text_letters + $text_words;
        return $attributePrice;
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

    public function getUniqueProductId(): string
    {
        return $this->uniqueProductId;
    }

    public function getRealProductId(): int
    {
        return $this->realProductId;
    }

    public function getAttributePrice(): float
    {
        return $this->attributePrice;
    }

    public function getAttributePriceOnetimeCharge(): float
    {
        return $this->attributePriceOnetimeCharge;
    }
}
