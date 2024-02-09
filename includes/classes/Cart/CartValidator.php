<?php

namespace Zencart\Cart;

use App\Models\BasketProduct;
use App\Models\Product;

class CartValidator
{

    protected int $fixOnce;
    protected float $newQuantity;

    public function validateCart(Cart $cart, BasketProduct $basketProduct, $uprid, bool $check_for_valid_cart): bool
    {
        if (!$check_for_valid_cart) {
            return '';
        }
        $product = $basketProduct->product;
        $this->fixOnce = 0;
        if ($product['products_status'] === '0') {
            $this->fixOnce++;
            $_SESSION['valid_to_checkout'] = false;
            $_SESSION['cart_errors'] .= ERROR_PRODUCT . $product['products_name'] . ERROR_PRODUCT_STATUS_SHOPPING_CART . '<br>';
            $cart->remove($uprid);
            return false;
        }
        $this->validateAttributes($cart, $basketProduct, $uprid);
        $this->fixupQuantities($cart, $basketProduct, $uprid);
        return true;
    }

    protected function validateAttributes(Cart $cart, BasketProduct $basketProduct, $uprid): void
    {
        $product = $basketProduct->product;
        if (!isset($product['attributes'])) {
            return;
        }
        foreach ($product['attributes'] as $attribute) {
            $chk_attributes_exist = zen_get_attribute_details(zen_get_prid($uprid), (int)$attribute['options_id'], (int)$attribute['options_values_id']);
            if ($chk_attributes_exist->EOF) {
                $this->fixOnce++;
                $_SESSION['valid_to_checkout'] = false;
                $chk_products_link =
                    '<a href="' . zen_href_link(zen_get_info_page(zen_get_prid($uprid)), 'cPath=' . zen_get_generated_category_path_rev($product['master_categories_id']) . '&products_id=' . zen_get_prid($uprid)) . '">' .
                    $product['products_name'] .
                    '</a>';
                $_SESSION['cart_errors'] .= ERROR_PRODUCT_ATTRIBUTES . $chk_products_link . ERROR_PRODUCT_STATUS_SHOPPING_CART_ATTRIBUTES . '<br>';
                $cart->remove($uprid);
                break;
            }
        }
    }

    protected function fixupQuantities(Cart $cart, BasketProduct $basketProduct, $uprid)
    {
        [$checkQuantity, $checkQuantityMin] = $this->checkQuantityOrderMin($cart, $basketProduct);
        $this->checkQuantityOrderMaxWithError($basketProduct, $checkQuantity, $uprid);
        $this->checkQuantityOrderMinWithError($basketProduct, $checkQuantity, $checkQuantityMin, $uprid);
        $this->checkQuantityOrderUnitsWithError($basketProduct, $checkQuantity, $uprid);
        $this->newQuantity = $this->adjustQuantityPrecision($basketProduct);
    }

    protected function adjustQuantityPrecision(BasketProduct $basketProduct)
    {
        $product = $basketProduct->product;
        $precision = QUANTITY_DECIMALS > 0 ? (int)QUANTITY_DECIMALS : 0;
        if ($precision === 0 || str_contains($basketProduct['quantity'], '.')) {
            $new_qty = $basketProduct['quantity'];
        } else {
            $new_qty = preg_replace('/[0]+$/', '', $basketProduct['quantity']);
        }
        $checkUnitDecimals = $product['products_quantity_order_units'];
        if (!str_contains($checkUnitDecimals, '.')) {
            $precision = 0;
        }
        $new_qty = round(zen_str_to_numeric($new_qty), $precision);
        return $new_qty;
    }

    protected function checkQuantityOrderUnitsWithError($basketProduct, $checkQuantity, $uprid)
    {
        if ($this->fixOnce !== 0) {
            return;
        }
        $checkUnits = $basketProduct->product['products_quantity_order_units'];
        if (fmod_round($checkQuantity, $checkUnits) == 0 || !isset($this->flag_duplicate_quantity_msgs_set[(int)$uprid]['units'])) {
            return;
        }
        $_SESSION['valid_to_checkout'] = false;
        $_SESSION['cart_errors'] .=
            ERROR_PRODUCT .
            $basketProduct->product['products_name'] .
            ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART .
            ERROR_PRODUCT_QUANTITY_ORDERED .
            $checkQuantity .
            ' <span class="alertBlack">' . zen_get_products_quantity_min_units_display((int)$uprid, false, true) . '</span> ' .
            '<br>';
        $this->flag_duplicate_quantity_msgs_set[(int)$uprid]['units'] = true;
    }

    protected function checkQuantityOrderMinWithError($basketProduct, $checkQuantity, $checkQuantityMin, $uprid)
    {
        if ($this->fixOnce !== 0) {
            return;
        }
        if ($checkQuantity >= $checkQuantityMin || isset($this->flag_duplicate_quantity_msgs_set[(int)$uprid]['min'])) {
            return;
        }
        $this->fixOnce++;
        $_SESSION['valid_to_checkout'] = false;
        $_SESSION['cart_errors'] .=
            ERROR_PRODUCT .
            $basketProduct->product['products_name'] .
            ERROR_PRODUCT_QUANTITY_MIN_SHOPPING_CART .
            ERROR_PRODUCT_QUANTITY_ORDERED .
            $checkQuantity .
            ' <span class="alertBlack">' . zen_get_products_quantity_min_units_display((int)$uprid, false, true) . '</span> ' .
            '<br>';
        $this->flag_duplicate_quantity_msgs_set[(int)$uprid]['min'] = true;
    }

    protected function checkQuantityOrderMaxWithError(BasketProduct $basketProduct, $checkQuantity, $uprid)
    {
        if ($this->fixOnce !== 0) {
            return;
        }
        if ($basketProduct->product['products_quantity_order_max'] == 0 || $checkQuantity > $basketProduct->product['products_quantity_order_max'] || !isset($this->flag_duplicate_quantity_msgs_set[(int)$uprid]['max'])) {
            return;
        }
        $this->fixOnce++;
        $_SESSION['valid_to_checkout'] = false;
        $_SESSION['cart_errors'] .=
            ERROR_PRODUCT .
            $basketProduct->product['products_name'] .
            ERROR_PRODUCT_QUANTITY_MAX_SHOPPING_CART .
            ERROR_PRODUCT_QUANTITY_ORDERED .
            $checkQuantity .
            ' <span class="alertBlack">' . zen_get_products_quantity_min_units_display((int)$uprid, false, true) . '</span> ' .
            '<br>';
        $this->flag_duplicate_quantity_msgs_set[(int)$uprid]['max'] = true;
    }

    protected function checkQuantityOrderMin($cart, $basketProduct): array
    {
        if ($this->fixOnce !== 0) {
            return [0,0];
        }
        $checkQuantity = $basketProduct['quantity'];
        $checkQuantityMin = $basketProduct->product['products_quantity_order_min'];
        // Check quantity min
        if ($newCheckQuantity = $cart->inCartMixed($basketProduct->product['products_id'])) {
            $checkQuantity = $newCheckQuantity;
        }
        return [$checkQuantity, $checkQuantityMin];
    }

    public function getNewQuantity(): float
    {
        return $this->newQuantity;
    }

    public function getFixOnce(): int
    {
        return $this->fixOnce;
    }

}
