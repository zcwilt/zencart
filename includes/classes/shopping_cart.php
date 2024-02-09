<?php
/**
 * Class for managing the Shopping Cart
 *
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2024 Feb 17 Modified in v2.0.0-beta1 $
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class shoppingCart extends \Zencart\Cart\Cart
{
    /**
     * shopping cart contents
     * @var array
     * @deprecated
     */
    public $contents;
    /**
     * shopping cart total price
     * @var float
     */
    public $total;
    /**
     * shopping cart total weight
     * @var float
     */
    public $weight;
    /**
     * cart identifier
     * @var integer
     */
    public $cartID;
    /**
     * overall content type of shopping cart
     * @var string
     */
    protected $content_type;
    /**
     * number of free shipping items in cart
     * @var float|int
     */
    protected $free_shipping_item;
    /**
     * total weight of free shipping items in cart
     * @var float|int
     */
    protected $free_shipping_weight;
    /**
     * total price of free shipping items in cart
     * @var float|int
     */
    protected $free_shipping_price;
    /**
     * total downloads in cart
     * @var float|int
     */
    protected $download_count;
    /**
     * shopping cart total price before Specials, Sales and Discounts
     * @var float|int
     */
    protected $total_before_discounts;
    /**
     * set to TRUE to see debug messages for developer use when troubleshooting add/update cart
     * Then, Logout/Login to reset cart for change
     * @var boolean
     */
    protected $display_debug_messages = false;
    protected $flag_duplicate_msgs_set = false;
    /**
     * array of flag to indicate if quantity ordered is outside product min/max order values
     * @var array
     */
    protected $flag_duplicate_quantity_msgs_set = [];


    /**
     * @return void
     *
     * @deprecated
     */
    public function restore_contents()
    {
        $this->restoreContents();
    }


    /**
     * Add an item to the cart
     *
     * This method is usually called as the result of a user action.
     * As the method name applies it adds an item to the users current cart in memory
     * and if the customer is logged in, also adds to the database stored cart.
     *
     * @param int $product_id the product ID of the item to be added
     * @param float $qty the quantity of the item to be added
     * @param array $attributes any attributes that are attached to the product
     * @param bool $notify whether to add the product to the notify list
     * @return void
     * @deprecated
     * @todo cart debug
     */
    public function add_cart($product_id, $qty = 1, $attributes = [], $notify = true)
    {
        $this->addToCart($product_id, $qty, $attributes, $notify);
    }

    /**
     * Update the quantity of an item already in the cart
     *
     * Changes the current quantity of a certain item in the cart to
     * a new value. Also updates the database stored cart if customer is
     * logged in.
     *
     * @param mixed $uprid product 'uprid' of item to update
     * @param int|float $quantity the quantity to update the item to
     * @param array $attributes product attributes attached to the item
     * @return bool
     * @deprecated
     * @todo cart debug
     */
    function update_quantity($uprid, $quantity = 0, $attributes = [])
    {
        $this->updateQuantity($uprid, $quantity = 0, $attributes = []);
    }


    /**
     * Protected method that removes a uprid from the cart.
     *
     * @param string|int $uprid 'uprid' of product to remove
     * @return void
     * @deprecated
     */
    protected function removeUprid($uprid)
    {
        $this->removeProductFromCart($uprid);
    }

    /**
     * Count total number of items in cart
     *
     * Note this is not just the number of distinct items in the cart,
     * but the number of items adjusted for the quantity of each item in the cart.
     * Example: if we have 2 items in the cart, one with a quantity of 3 and
     * the other with a quantity of 4 our total number of items would be 7
     *
     * @return int|float total number of items in cart
     *
     * @deprecated
     */
    public function count_contents()
    {
        return $this->countContents();
    }

    /**
     * Get the quantity of an item in the cart
     * NOTE: This accepts attribute hash as $products_id, such as: 12:a35de52391fcb3134
     * ... and treats 12 as unique from 12:a35de52391fcb3134
     * To lookup based only on prid (ie: 12 here) regardless of the attribute hash, use another method: in_cart_product_total_quantity()
     *
     * @param int|string $uprid product ID of item to check
     * @return int|float the quantity of the item
     *
     * @deprecated
     */
    public function get_quantity($uprid)
    {
        $this->notify('NOTIFIER_CART_GET_QUANTITY_START', null, $uprid);
        if (isset($this->contents[$uprid])) {
            $this->notify('NOTIFIER_CART_GET_QUANTITY_END_QTY', null, $uprid);
            return $this->contents[$uprid]['qty'];
        }

        $this->notify('NOTIFIER_CART_GET_QUANTITY_END_FALSE', $uprid);
        return 0;
    }

    /**
     * Check whether a product exists in the cart
     *
     * @param mixed $uprid product ID of product to check
     * @return boolean
     * @deprecated
     */
    public function in_cart($uprid)
    {
        $this->inCart($uprid);
    }

    /**
     * Remove a product from the cart
     *
     * @param string|int $uprid product ID of product to remove
     * @return void
     */
    public function remove($uprid)
    {
        $this->remove($uprid);
    }

    /**
     * Remove all products from the cart
     */
    public function remove_all()
    {
        $this->notify('NOTIFIER_CART_REMOVE_ALL_START');
        $this->reset();
        $this->notify('NOTIFIER_CART_REMOVE_ALL_END');
    }

    /**
     * Return a comma separated list of all products in the cart
     * NOTE: Not used in core ZC, but some plugins and shipping modules make use of it as a helper function
     *
     * @return string csv
     */
    public function get_product_id_list()
    {
        if (!is_array($this->contents)) {
            return '';
        }
        return implode(',', array_keys($this->contents));
    }

    /**
     * Calculate cart totals(price and weight)
     *
     * @return int
     */
    public function calculate()
    {
        global $db, $currencies;
        $this->total = 0;
        $this->weight = 0;
        $this->total_before_discounts = 0;
        $decimalPlaces = $currencies->get_decimal_places($_SESSION['currency']);
        // shipping adjustment
        $this->free_shipping_item = 0;
        $this->free_shipping_price = 0;
        $this->free_shipping_weight = 0;
        $this->download_count = 0;
        if (!is_array($this->contents)) {
            return 0;
        }

// By default, Price Factor is based on Price and is called from function zen_get_attributes_price_factor
// Setting a define for ATTRIBUTES_PRICE_FACTOR_FROM_SPECIAL to 1 to calculate the Price Factor from Special rather than Price switches this to be based on Special, if it exists
        zen_define_default('ATTRIBUTES_PRICE_FACTOR_FROM_SPECIAL', 1);
        foreach ($this->contents as $uprid => $data) {
            $total_before_discounts = 0;
            $freeShippingTotal = 0;
            $productTotal = 0;
            $totalOnetimeCharge = 0;
            $totalOnetimeChargeNoDiscount = 0;
            $free_shipping_applied = false;
            $qty = $data['qty'];
            $prid = zen_get_prid($uprid);

            $product = zen_get_product_details($prid);
            if ($product->EOF) {
                $this->removeUprid($uprid);
                continue;
            }

            $product = $product->fields;
            $this->notify('NOTIFY_CART_CALCULATE_PRODUCT_PRICE', $uprid, $product);
            $prid = zen_get_prid($product['products_id']);

            $products_tax = zen_get_tax_rate($product['products_tax_class_id']);

            $products_raw_price = zen_get_retail_or_wholesale_price($product['products_price'], $product['products_price_w']);
            $products_price = $products_raw_price;

            $is_free_shipping = $product['product_is_always_free_shipping'] === '1' || $product['products_virtual'] === '1' || str_starts_with($product['products_model'], 'GIFT');

            // adjusted count for free shipping
            if ($product['product_is_always_free_shipping'] !== '1' && $product['products_virtual'] !== '1') {
                $products_weight = $product['products_weight'];
            } else {
                $products_weight = 0;
            }

            $special_price = zen_get_products_special_price($prid);
            if ($special_price && $product['products_priced_by_attribute'] === '0') {
                $products_price = $special_price;
            } else {
                $special_price = 0;
            }

            if (zen_get_products_price_is_free($uprid)) {
                // no charge
                $products_price = 0;
            }

            // adjust price for discounts when priced by attribute
            if ($product['products_priced_by_attribute'] === '1' && zen_has_product_attributes($prid, false)) {
                $products_price = $special_price ?: $products_raw_price;
            } elseif ($product['products_discount_type'] !== '0') {  // discount qty pricing
                $products_price = zen_get_products_discount_price_qty($prid, $qty);
            }

            // shipping adjustments for Product
            if ($is_free_shipping === true) {
                $free_shipping_applied = true;
                $this->free_shipping_item += $qty;
                $freeShippingTotal += $products_price;
                $this->free_shipping_weight += ($qty * $product['products_weight']);
            }

            $productTotal += $products_price;
            $this->weight += ($qty * $products_weight);

// ****** WARNING NEED TO ADD ATTRIBUTES AND QTY
            // calculate Product Price without Specials, Sales or Discounts
            $total_before_discounts += zen_str_to_numeric($products_raw_price);

            $adjust_downloads = 0;
            // attributes price
            $savedProductTotal = $productTotal;
            $attributesTotal = 0;
            if (isset($this->contents[$uprid]['attributes'])) {
                foreach ($this->contents[$uprid]['attributes'] as $option => $value) {
                    $productTotal = 0;
                    $adjust_downloads++;

                    $attribute_price = zen_get_attribute_details($prid, (int)$option, (int)$value);
                    if ($attribute_price->EOF) {
                        continue;
                    }

                    $this->notify('NOTIFY_CART_CALCULATE_ATTRIBUTE_PRICE', $uprid, $attribute_price->fields);

                    $new_attributes_price = 0;
                    // calculate Product Price without Specials, Sales or Discounts
                    //$new_attributes_price_before_discounts = 0;

                    $discount_type_id = '';
                    $sale_maker_discount = '';

                    // bottom total
                    if ($attribute_price->fields['product_attribute_is_free'] === '1' && zen_get_products_price_is_free($prid)) {
                        // no charge for attribute
                        continue;
                    }

                    // + or blank adds
                    $attributes_id = $attribute_price->fields['products_attributes_id'];
                    $options_values_price = zen_get_retail_or_wholesale_price(
                        $attribute_price->fields['options_values_price'],
                        $attribute_price->fields['options_values_price_w']
                    );
                    if ($attribute_price->fields['price_prefix'] === '-') {
                        // appears to confuse products priced by attributes
                        if ($product['product_is_always_free_shipping'] === '1' || $product['products_virtual'] === '1') {
                            $shipping_attributes_price = zen_get_discount_calc($prid, $attributes_id, $options_values_price, $qty);
                            $freeShippingTotal -= $shipping_attributes_price;
                        }
                        if ($attribute_price->fields['attributes_discounted'] === '1') {
                            // calculate proper discount for attributes
                            $new_attributes_price = zen_get_discount_calc($prid, $attributes_id, $options_values_price, $qty);
                            $productTotal -= $new_attributes_price;
                        } else {
                            $productTotal -= $options_values_price;
                        }
                        // calculate Product Price without Specials, Sales or Discounts
                        $total_before_discounts -= $options_values_price;
                    } else {
                        // appears to confuse products priced by attributes
                        if ($product['product_is_always_free_shipping'] === '1' || $product['products_virtual'] === '1') {
                            $shipping_attributes_price = zen_get_discount_calc($prid, $attributes_id, $options_values_price, $qty);
                            $freeShippingTotal += $shipping_attributes_price;
                        }
                        if ($attribute_price->fields['attributes_discounted'] === '1') {
                            // calculate proper discount for attributes
                            $products_base_price = zen_get_products_price_is_priced_by_attributes($prid) ? $products_raw_price : 0;
                            $new_attributes_price = zen_get_discount_calc($prid, $attributes_id, zen_str_to_numeric($options_values_price) + $products_base_price, $qty);
                            $new_attributes_price -= $products_base_price;
                            $productTotal += $new_attributes_price;
                        } else {
                            $productTotal += zen_str_to_numeric($options_values_price);
                        }
                        // calculate Product Price without Specials, Sales or Discounts
                        $total_before_discounts += zen_str_to_numeric($options_values_price);
                    } // eof: attribute price

                    // adjust for downloads
                    // adjust products price
                    $sql = "SELECT products_attributes_id
                            FROM " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . "
                            WHERE products_attributes_id = $attributes_id";
                    $check_download = $db->Execute($sql, 1);
                    if (!$check_download->EOF) {
                        // count number of downloads
                        $this->download_count += ($check_download->RecordCount() * $qty);
                        // do not count download as free when set to product/download combo
                        if ($free_shipping_applied === false && $adjust_downloads === 1 && $product['product_is_always_free_shipping'] !== '2') {
                            $freeShippingTotal += $products_price;
                            $this->free_shipping_item += $qty;
                        }
                        // adjust for attributes price
                        $freeShippingTotal += $new_attributes_price;
                    }

                    ////////////////////////////////////////////////
                    // calculate additional attribute charges
                    $chk_price = zen_get_products_base_price($uprid);
                    $chk_special = zen_get_products_special_price($uprid, false);
                    // products_options_value_text
                    if (ATTRIBUTES_ENABLED_TEXT_PRICES === 'true' && (string)zen_get_attributes_type($attributes_id) === (string)PRODUCTS_OPTIONS_TYPE_TEXT) {
                        $text_words = zen_get_word_count_price(
                            $this->contents[$uprid]['attributes_values'][$attribute_price->fields['options_id']],
                            $attribute_price->fields['attributes_price_words_free'],
                            $attribute_price->fields['attributes_price_words']
                        );
                        $text_letters = zen_get_letters_count_price(
                            $this->contents[$uprid]['attributes_values'][$attribute_price->fields['options_id']],
                            $attribute_price->fields['attributes_price_letters_free'],
                            $attribute_price->fields['attributes_price_letters']
                        );

                        $productTotal += $text_letters;
                        $productTotal += $text_words;
                        if ($is_free_shipping === true) {
                            $freeShippingTotal += $text_letters;
                            $freeShippingTotal += $text_words;
                        }
                        // calculate Product Price without Specials, Sales or Discounts
                        $total_before_discounts += $text_letters;
                        $total_before_discounts += $text_words;
                    }

                    // attributes_price_factor
                    if ($attribute_price->fields['attributes_price_factor'] > 0) {
                        $added_charge = zen_get_attributes_price_factor(
                            $chk_price,
                            $chk_special,
                            $attribute_price->fields['attributes_price_factor'],
                            $attribute_price->fields['attributes_price_factor_offset']
                        );

                        $productTotal += $added_charge;
                        if ($is_free_shipping === true) {
                            $freeShippingTotal += $added_charge;
                        }
                        // calculate Product Price without Specials, Sales or Discounts
                        $added_charge = zen_get_attributes_price_factor(
                            $chk_price,
                            $chk_price,
                            $attribute_price->fields['attributes_price_factor'],
                            $attribute_price->fields['attributes_price_factor_offset']
                        );
                        $total_before_discounts += $added_charge;
                    }

                    // attributes_qty_prices
                    if (!empty($attribute_price->fields['attributes_qty_prices'])) {
                        $added_charge = zen_get_attributes_qty_prices_onetime($attribute_price->fields['attributes_qty_prices'], $qty);

                        $productTotal += $added_charge;
                        if ($is_free_shipping === true) {
                            $freeShippingTotal += $added_charge;
                        }
                        // calculate Product Price without Specials, Sales or Discounts
                        $added_charge = zen_get_attributes_qty_prices_onetime($attribute_price->fields['attributes_qty_prices'], 1);
                        $total_before_discounts += zen_str_to_numeric($options_values_price) + $added_charge;
                    }

                    //// one time charges
                    // attributes_price_onetime
                    if ($attribute_price->fields['attributes_price_onetime'] > 0) {
                        $totalOnetimeCharge += $attribute_price->fields['attributes_price_onetime'];
                        // calculate Product Price without Specials, Sales or Discounts
                        $totalOnetimeChargeNoDiscount += $attribute_price->fields['attributes_price_onetime'];
                    }

                    // attributes_price_factor_onetime
                    if ($attribute_price->fields['attributes_price_factor_onetime'] > 0) {
                        $chk_price = zen_get_products_base_price($uprid);
                        $chk_special = zen_get_products_special_price($uprid, false);
                        $added_charge = zen_get_attributes_price_factor(
                            $chk_price,
                            $chk_special,
                            $attribute_price->fields['attributes_price_factor_onetime'],
                            $attribute_price->fields['attributes_price_factor_onetime_offset']
                        );

                        $totalOnetimeCharge += $added_charge;
                        // calculate Product Price without Specials, Sales or Discounts
                        $added_charge = zen_get_attributes_price_factor(
                            $chk_price,
                            $chk_price,
                            $attribute_price->fields['attributes_price_factor_onetime'],
                            $attribute_price->fields['attributes_price_factor_onetime_offset']
                        );
                        $totalOnetimeChargeNoDiscount += $added_charge;
                    }

                    // attributes_qty_prices_onetime
                    if (!empty($attribute_price->fields['attributes_qty_prices_onetime'])) {
                        $added_charge = zen_get_attributes_qty_prices_onetime($attribute_price->fields['attributes_qty_prices_onetime'], $qty);
                        $totalOnetimeCharge += $added_charge;
                        // calculate Product Price without Specials, Sales or Discounts
                        $added_charge = zen_get_attributes_qty_prices_onetime($attribute_price->fields['attributes_qty_prices_onetime'], 1);
                        $totalOnetimeChargeNoDiscount += $added_charge;
                    }
                    ////////////////////////////////////////////////

                    $attributesTotal += zen_round($productTotal, $decimalPlaces);
                } // eof foreach
            } // attributes price
            $productTotal = $savedProductTotal + $attributesTotal;

            // attributes weight
            if (isset($this->contents[$uprid]['attributes'])) {
                foreach ($this->contents[$uprid]['attributes'] as $option => $value) {
                    $attribute_weight = zen_get_attribute_details($prid, (int)$option, (int)$value);
                    if ($attribute_weight->EOF) {
                        continue;
                    }

                    $this->notify('NOTIFY_CART_CALCULATE_ATTRIBUTE_WEIGHT', ['products_id' => $uprid, 'options_id' => $option], $attribute_weight->fields);

                    // adjusted count for free shipping
                    if ($product['product_is_always_free_shipping'] !== '1') {
                        $new_attributes_weight = $attribute_weight->fields['products_attributes_weight'];
                    } else {
                        $new_attributes_weight = 0;
                    }

                    // shipping adjustments for Attributes
                    if ($is_free_shipping === true) {
                        if ($attribute_weight->fields['products_attributes_weight_prefix'] === '-') {
                            $this->free_shipping_weight -= ($qty * $attribute_weight->fields['products_attributes_weight']);
                        } else {
                            $this->free_shipping_weight += ($qty * $attribute_weight->fields['products_attributes_weight']);
                        }
                    }

                    // + or blank adds
                    if ($attribute_weight->fields['products_attributes_weight_prefix'] === '-') {
                        $this->weight -= $qty * $new_attributes_weight;
                    } else {
                        $this->weight += $qty * $new_attributes_weight;
                    }
                }
            } // attributes weight

            /*
            // uncomment for odd shipping requirements needing this:

                  // if 0 weight defined as free shipping adjust for functions free_shipping_price and free_shipping_item
                  if ($product['products_weight'] == 0 && ORDER_WEIGHT_ZERO_STATUS === '1' && $is_free_shipping === false) {
                    $freeShippingTotal += $products_price;
                    $this->free_shipping_item += $qty;
                  }
            */

            $this->total += zen_round(zen_add_tax($productTotal, $products_tax), $decimalPlaces) * $qty;
            $this->total += zen_round(zen_add_tax($totalOnetimeCharge, $products_tax), $decimalPlaces);
            $this->free_shipping_price += zen_round(zen_add_tax($freeShippingTotal, $products_tax), $decimalPlaces) * $qty;
            if ($is_free_shipping === true) {
                $this->free_shipping_price += zen_round(zen_add_tax($totalOnetimeCharge, $products_tax), $decimalPlaces);
            }

// ******* WARNING ADD ONE TIME ATTRIBUTES, PRICE FACTOR
            // calculate Product Price without Specials, Sales or Discounts
            $total_before_discounts = $total_before_discounts * $qty;
            $total_before_discounts += $totalOnetimeChargeNoDiscount;
            $this->total_before_discounts += $total_before_discounts;
        }
    }

    /**
     * Calculate price of attributes for a given item
     *
     * @param mixed $uprid the product ID of the item to check
     * @return float the price of the item's attributes
     */
    public function attributes_price($uprid)
    {
        global $db, $currencies;

        $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_START', $uprid);

        if (!isset($this->contents[$uprid]['attributes'])) {
            return 0;
        }

        zen_define_default('ATTRIBUTES_PRICE_FACTOR_FROM_SPECIAL', 1);

        $total_attributes_price = 0;
        $qty = $this->contents[$uprid]['qty'];
        $prid = (int)$uprid;

        foreach ($this->contents[$uprid]['attributes'] as $option => $value) {
            $attribute_details = zen_get_attribute_details($prid, (int)$option, (int)$value);
            if ($attribute_details->EOF) {
                continue;
            }

            $attribute_price = $attribute_details->fields;
            $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_NEXT', $uprid, $attribute_price);

            $attributes_price = 0;
            $new_attributes_price = 0;
            $discount_type_id = '';
            $sale_maker_discount = '';
            $options_values_price = zen_get_retail_or_wholesale_price(
                $attribute_price['options_values_price'],
                $attribute_price['options_values_price_w']
            );

            if ($attribute_price['product_attribute_is_free'] === '1' && zen_get_products_price_is_free($prid)) {
                // no charge
            } else {
                // + or blank adds
                if ($attribute_price['price_prefix'] === '-') {
                    // calculate proper discount for attributes
                    if ($attribute_price['attributes_discounted'] === '1') {
                        $discount_type_id = '';
                        $sale_maker_discount = '';
                        $new_attributes_price = zen_get_discount_calc($prid, $attribute_price['products_attributes_id'], $options_values_price, $qty);
                        $attributes_price -= $new_attributes_price;
                    } else {
                        $attributes_price -= $options_values_price;
                    }
                } elseif ($attribute_price['attributes_discounted'] === '1') {
                    // calculate proper discount for attributes
                    $discount_type_id = '';
                    $sale_maker_discount = '';
                    $products_raw_price = zen_get_product_retail_or_wholesale_price($prid);
                    $products_raw_attribute_base_price = (zen_get_products_price_is_priced_by_attributes($prid)) ? $products_raw_price : 0.0;
                    $new_attributes_price = zen_get_discount_calc($prid, $attribute_price['products_attributes_id'], zen_str_to_numeric($options_values_price) + $products_raw_attribute_base_price, $qty);
                    $new_attributes_price -= $products_raw_attribute_base_price;
                    $attributes_price += $new_attributes_price;
                } else {
                    $attributes_price += zen_str_to_numeric($options_values_price);
                }

                //////////////////////////////////////////////////
                // calculate additional charges
                // products_options_value_text
                if (ATTRIBUTES_ENABLED_TEXT_PRICES === 'true' && (string)zen_get_attributes_type($attribute_price['products_attributes_id']) === (string)PRODUCTS_OPTIONS_TYPE_TEXT) {
                    $text_words = zen_get_word_count_price(
                        $this->contents[$uprid]['attributes_values'][$attribute_price['options_id']],
                        $attribute_price['attributes_price_words_free'],
                        $attribute_price['attributes_price_words']
                    );
                    $text_letters = zen_get_letters_count_price(
                        $this->contents[$uprid]['attributes_values'][$attribute_price['options_id']],
                        $attribute_price['attributes_price_letters_free'],
                        $attribute_price['attributes_price_letters']
                    );
                    $attributes_price += $text_letters + $text_words;
                }

                // attributes_price_factor
                if ($attribute_price['attributes_price_factor'] > 0) {
                    $added_charge = zen_get_attributes_price_factor(
                        zen_get_products_base_price($prid),
                        zen_get_products_special_price($prid, false),
                        $attribute_price['attributes_price_factor'],
                        $attribute_price['attributes_price_factor_offset']
                    );
                    $attributes_price += $added_charge;
                }

                // attributes_qty_prices
                if (!empty($attribute_price['attributes_qty_prices'])) {
                    $added_charge = zen_get_attributes_qty_prices_onetime($attribute_price['attributes_qty_prices'], $this->contents[$uprid]['qty']);
                    $attributes_price += $added_charge;
                }

                //////////////////////////////////////////////////
            }
            // Validate Attributes
            if ($attribute_price['attributes_display_only']) {
                $_SESSION['valid_to_checkout'] = false;
                $_SESSION['cart_errors'] .= zen_get_products_name($prid, $_SESSION['languages_id']) . ERROR_PRODUCT_OPTION_SELECTION . '<br>';
            }
            /*
            //// extra testing not required on text attribute this is done in application_top before it gets to the cart
            if ($attribute_price['attributes_required']) {
            $_SESSION['valid_to_checkout'] = false;
            $_SESSION['cart_errors'] .= zen_get_products_name($attribute_price['products_id'], $_SESSION['languages_id'])  . ERROR_PRODUCT_OPTION_SELECTION . '<br>';
            }
            */
            $total_attributes_price += zen_round($attributes_price, $currencies->get_decimal_places($_SESSION['currency']));
        }

        return $total_attributes_price;
    }

    /**
     * Calculate one-time price of attributes for a given item
     *
     * @param mixed $uprid the product ID of the item to check
     * @param float $qty item quantity
     * @return float the price of the items attributes
     */
    public function attributes_price_onetime_charges($uprid, $qty)
    {
        $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_ONETIME_CHARGES_START', $uprid);

        if (!isset($this->contents[$uprid]['attributes'])) {
            return 0;
        }

        $attributes_price_onetime = 0;
        $prid = (int)$uprid;
        foreach ($this->contents[$uprid]['attributes'] as $option => $value) {
            $attribute_details = zen_get_attribute_details($prid, (int)$option, (int)$value);
            if ($attribute_details->EOF) {
                continue;
            }

            $attribute_price = $attribute_details->fields;
            $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_ONETIME_CHARGES_NEXT', $uprid, $attribute_price);

            if ($attribute_price['product_attribute_is_free'] === '1' && zen_get_products_price_is_free($prid)) {
                // no charge
                continue;
            }

            //////////////////////////////////////////////////
            // calculate additional one time charges
            //// one time charges
            // attributes_price_onetime
            if ($attribute_price['attributes_price_onetime'] > 0) {
                $attributes_price_onetime += $attribute_price['attributes_price_onetime'];
            }

            // attributes_price_factor_onetime
            if ($attribute_price['attributes_price_factor_onetime'] > 0) {
                $added_charge = zen_get_attributes_price_factor(
                    zen_get_products_base_price($prid),
                    zen_get_products_special_price($prid, false),
                    $attribute_price['attributes_price_factor_onetime'],
                    $attribute_price['attributes_price_factor_onetime_offset']
                );

                $attributes_price_onetime += $added_charge;
            }

            // attributes_qty_prices_onetime
            if (!empty($attribute_price['attributes_qty_prices_onetime'])) {
                $added_charge = zen_get_attributes_qty_prices_onetime($attribute_price['attributes_qty_prices_onetime'], $qty);
                $attributes_price_onetime += $added_charge;
            }
            //////////////////////////////////////////////////
        }

        return $attributes_price_onetime;
    }

    /**
     * Calculate weight of attributes for a given item
     *
     * @param mixed $product_id the product ID of the item to check
     * @return float the weight of the items attributes
     */
    public function attributes_weight($uprid)
    {
        if (!isset($this->contents[$uprid]['attributes'])) {
            return 0;
        }

        $this->notify('NOTIFY_CART_ATTRIBUTES_WEIGHT_START', $uprid);

        $attribute_weight = 0;
        $prid = (int)$uprid;
        foreach ($this->contents[$uprid]['attributes'] as $option => $value) {
            $attribute_weight_info = zen_get_attribute_details($prid, (int)$option, (int)$value);
            if ($attribute_weight_info->EOF) {
                continue;
            }

            $this->notify('NOTIFY_CART_ATTRIBUTES_WEIGHT_NEXT', $uprid, $attribute_weight_info->fields);

            $new_attributes_weight = (zen_get_product_is_always_free_shipping($prid) === false) ?
                $attribute_weight_info->fields['products_attributes_weight'] : 0;

            // + or blank adds
            if ($attribute_weight_info->fields['products_attributes_weight_prefix'] === '-') {
                $attribute_weight -= $new_attributes_weight;
            } else {
                $attribute_weight += $attribute_weight_info->fields['products_attributes_weight'];
            }
        }

        return $attribute_weight;
    }

    /**
     * Get all products in the cart
     *
     * @param bool $check_for_valid_cart whether to also check if cart contents are valid
     * @return array|false
     */
    public function get_products(bool $check_for_valid_cart = false)
    {
        global $db;

        $this->notify('NOTIFIER_CART_GET_PRODUCTS_START', null, $check_for_valid_cart);

        if (!is_array($this->contents)) {
            return false;
        }

        $products_array = [];
        foreach ($this->contents as $uprid => $data) {
            $prid = zen_get_prid($uprid);
            $products = zen_get_product_details($prid);
            if ($products->EOF) {
                $this->removeUprid($uprid);
                continue;
            }

            $this->notify('NOTIFY_CART_GET_PRODUCTS_NEXT', $uprid, $products->fields);

            $product = $products->fields;

            $products_raw_price = zen_get_retail_or_wholesale_price($product['products_price'], $product['products_price_w']);
            $products_price = $products_raw_price;

            $special_price = zen_get_products_special_price($prid);
            if ($special_price && $product['products_priced_by_attribute'] === '0') {
                $products_price = $special_price;
            } else {
                $special_price = 0;
            }

            if (zen_get_products_price_is_free($prid)) {
                // no charge
                $products_price = 0;
            }

            // adjust price for discounts when priced by attribute
            if ($product['products_priced_by_attribute'] === '1' && zen_has_product_attributes($prid, false)) {
                if ($special_price) {
                    $products_price = $special_price;
                } else {
                    $products_price = $products_raw_price;
                }
            } elseif ($product['products_discount_type'] !== '0') {  // discount qty pricing
                $products_price = zen_get_products_discount_price_qty($prid, $data['qty']);
            }

            // validate cart contents for checkout

            if ($check_for_valid_cart === true) {
                if (empty($this->flag_duplicate_quantity_msgs_set['keep'])) {
                    $this->flag_duplicate_quantity_msgs_set = [];
                }
                $fix_once = 0;
                // Check products_status if not already
                if ($product['products_status'] === '0') {
                    $fix_once++;
                    $_SESSION['valid_to_checkout'] = false;
                    $_SESSION['cart_errors'] .= ERROR_PRODUCT . $product['products_name'] . ERROR_PRODUCT_STATUS_SHOPPING_CART . '<br>';
                    $this->remove($uprid);
                    continue;
                }

                if (isset($data['attributes'])) {
                    foreach ($data['attributes'] as $option_id => $value_id) {
                        $chk_attributes_exist = zen_get_attribute_details($prid, (int)$option_id, (int)$value_id);
                        if ($chk_attributes_exist->EOF) {
                            $fix_once++;
                            $_SESSION['valid_to_checkout'] = false;
                            $chk_products_link =
                                '<a href="' . zen_href_link(zen_get_info_page($prid), 'cPath=' . zen_get_generated_category_path_rev($product['master_categories_id']) . '&products_id=' . $prid) . '">' .
                                    $product['products_name'] .
                                '</a>';
                            $_SESSION['cart_errors'] .= ERROR_PRODUCT_ATTRIBUTES . $chk_products_link . ERROR_PRODUCT_STATUS_SHOPPING_CART_ATTRIBUTES . '<br>';
                            $this->remove($uprid);
                            break;
                        }
                    }
                }

                // check only if valid products_status
                if ($fix_once === 0) {
                    $check_quantity = $data['qty'];
                    $check_quantity_min = $product['products_quantity_order_min'];
                    // Check quantity min
                    if ($new_check_quantity = $this->in_cart_mixed($prid)) {
                        $check_quantity = $new_check_quantity;
                    }
                }

                // Check Quantity Max if not already an error on Minimum
                if ($fix_once === 0) {
                    if ($product['products_quantity_order_max'] != 0 && $check_quantity > $product['products_quantity_order_max'] && !isset($this->flag_duplicate_quantity_msgs_set[$prid]['max'])) {
                        $fix_once++;
                        $_SESSION['valid_to_checkout'] = false;
                        $_SESSION['cart_errors'] .=
                            ERROR_PRODUCT .
                            $product['products_name'] .
                            ERROR_PRODUCT_QUANTITY_MAX_SHOPPING_CART .
                            ERROR_PRODUCT_QUANTITY_ORDERED .
                            $check_quantity .
                            ' <span class="alertBlack">' . zen_get_products_quantity_min_units_display($prid, false, true) . '</span> ' .
                            '<br>';
                        $this->flag_duplicate_quantity_msgs_set[$prid]['max'] = true;
                    }
                }

                if ($fix_once === 0) {
                    if ($check_quantity < $check_quantity_min && !isset($this->flag_duplicate_quantity_msgs_set[$prid]['min'])) {
                        $fix_once++;
                        $_SESSION['valid_to_checkout'] = false;
                        $_SESSION['cart_errors'] .=
                            ERROR_PRODUCT .
                            $product['products_name'] .
                            ERROR_PRODUCT_QUANTITY_MIN_SHOPPING_CART .
                            ERROR_PRODUCT_QUANTITY_ORDERED .
                            $check_quantity .
                            ' <span class="alertBlack">' . zen_get_products_quantity_min_units_display($prid, false, true) . '</span> ' .
                            '<br>';
                        $this->flag_duplicate_quantity_msgs_set[$prid]['min'] = true;
                    }
                }

                // Check Quantity Units if not already an error on Quantity Minimum
                if ($fix_once === 0) {
                    $check_units = $product['products_quantity_order_units'];
                    if (fmod_round($check_quantity, $check_units) != 0 && !isset($this->flag_duplicate_quantity_msgs_set[$prid]['units'])) {
                        $_SESSION['valid_to_checkout'] = false;
                        $_SESSION['cart_errors'] .=
                            ERROR_PRODUCT .
                            $product['products_name'] .
                            ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART .
                            ERROR_PRODUCT_QUANTITY_ORDERED .
                            $check_quantity .
                            ' <span class="alertBlack">' . zen_get_products_quantity_min_units_display($prid, false, true) . '</span> ' .
                            '<br>';
                        $this->flag_duplicate_quantity_msgs_set[$prid]['units'] = true;
                    }
                }
                // Verify Valid Attributes
            }

            // convert quantity to proper decimals
            $precision = QUANTITY_DECIMALS > 0 ? (int)QUANTITY_DECIMALS : 0;
            if ($precision === 0 || str_contains($data['qty'], '.')) {
                $new_qty = $data['qty'];
            } else {
                $new_qty = preg_replace('/[0]+$/', '', $data['qty']);
            }
            $check_unit_decimals = $product['products_quantity_order_units'];
            if (!str_contains($check_unit_decimals, '.')) {
                $precision = 0;
            }
            $new_qty = round(zen_str_to_numeric($new_qty), $precision);

            $products_array[] = [
                'id' => $uprid,
                'category' => $product['master_categories_id'],
                'name' => $product['products_name'],
                'model' => $product['products_model'],
                'image' => $product['products_image'],
                'price' => ($product['product_is_free'] === '1') ? 0 : $products_price,
                'quantity' => $new_qty,
                'weight' => $product['products_weight'] + $this->attributes_weight($uprid),

                // units as defined in Admin, optionally overridden by what might be defined in products table from older shipping modules
                'weight_units' => $product['products_weight_units'] ?? $product['products_weight_type'] ?? (defined('SHIPPING_WEIGHT_UNITS') ? (string)SHIPPING_WEIGHT_UNITS : null),
                'dim_units' => $product['products_dim_units'] ?? $product['products_dim_type'] ?? (defined('SHIPPING_DIMENSION_UNITS') ? (string)SHIPPING_DIMENSION_UNITS : null),

                'length' => $product['products_length'] ?? null, // float
                'width' => $product['products_width'] ?? null, // float
                'height' => $product['products_height'] ?? null, // float
                'ships_in_own_box' => $product['product_ships_in_own_box'] ?? $product['products_ready_to_ship'] ?? null, // [0,1]

                'final_price' => $products_price + $this->attributes_price($uprid),
                'onetime_charges' => $this->attributes_price_onetime_charges($uprid, $new_qty),
                'tax_class_id' => $product['products_tax_class_id'],
                'attributes' => $data['attributes'] ?? '',
                'attributes_values' => $data['attributes_values'] ?? '',
                'products_priced_by_attribute' => $product['products_priced_by_attribute'],
                'product_is_free' => $product['product_is_free'],
                'products_discount_type' => $product['products_discount_type'],
                'products_discount_type_from' => $product['products_discount_type_from'],
                'products_virtual' => (int)$product['products_virtual'],
                'product_is_always_free_shipping' => (int)$product['product_is_always_free_shipping'],
                'products_quantity_order_min' => (float)$product['products_quantity_order_min'],
                'products_quantity_order_units' => (float)$product['products_quantity_order_units'],
                'products_quantity_order_max' => (float)$product['products_quantity_order_max'],
                'products_quantity_mixed' => (int)$product['products_quantity_mixed'],
                'products_mixed_discount_quantity' => (int)$product['products_mixed_discount_quantity'],
            ];
        }
        $this->notify('NOTIFIER_CART_GET_PRODUCTS_END', null, $products_array);
        return $products_array;
    }

    /**
     * Calculate total price of items in cart
     *
     * @return float Total Price
     */
    public function show_total()
    {
        $this->notify('NOTIFIER_CART_SHOW_TOTAL_START');
        $this->calculate();
        $this->notify('NOTIFIER_CART_SHOW_TOTAL_END');
        return $this->total;
    }

    /**
     * Calculate total price of items in cart before Specials, Sales, Discounts
     *
     * @return float Total Price before Specials, Sales, Discounts
     */
    public function show_total_before_discounts()
    {
        $this->notify('NOTIFIER_CART_SHOW_TOTAL_BEFORE_DISCOUNT_START');
        $this->calculate();
        $this->notify('NOTIFIER_CART_SHOW_TOTAL_BEFORE_DISCOUNT_END');
        return $this->total_before_discounts;
    }

    /**
     * Calculate total weight of items in cart
     *
     * @return float Total Weight
     */
    public function show_weight()
    {
        $this->calculate();
        return $this->weight;
    }

    /**
     * Generate a cart ID, used to ensure contents have not been altered unexpectedly
     *
     * @param int $length length of ID to generate
     * @return string cart ID
     *
     * @deprecated
     */
    public function generate_cart_id($length = 5)
    {
        return $this->generateCartId($length);
    }

    /**
     * Calculate the content type of a cart
     *
     * @param bool $gv_only whether to test for Gift Vouchers only
     * @return string
     */
    public function get_content_type($gv_only = false)
    {
        global $db;

        // legacy compatibility:
        if ($gv_only === 'false') {
            $gv_only = false;
        } elseif ($gv_only === 'true') {
            $gv_only = true;
        }

        $this->content_type = false;
        $gift_voucher = 0;

        if ($this->count_contents() === 0) {
            $this->content_type = 'physical';
        } else {
            foreach ($this->contents as $uprid => $data) {
                $prid = (int)$uprid;
                $free_ship_check = zen_get_product_details($prid);
                $free_ship_check = $free_ship_check->fields;

                if (str_starts_with($free_ship_check['products_model'], 'GIFT')) {
// @TODO - fix GIFT price in cart special/attribute
                    $gift_special = zen_get_products_special_price($prid, true);
                    $gift_pba = zen_get_products_price_is_priced_by_attributes($prid);
                    $gift_price = zen_get_retail_or_wholesale_price($free_ship_check['products_price'], $free_ship_check['products_price_w']);
                    if ($gift_special !== false) {
                        if (!$gift_pba && !empty($gift_special) && (string)$gift_special !== (string)$gift_price) {
                            $gift_voucher += ($gift_special * $data['qty']);
                        } else {
                            $gift_voucher += (zen_str_to_numeric($gift_price) + $this->attributes_price($uprid)) * $data['qty'];
                        }
                    }
                }

                $product_is_virtual = ($free_ship_check['products_virtual'] === '1');

                // product_is_always_free_shipping = 2 is special requires shipping
                // Example: Product with download
                if (isset($data['attributes']) && $free_ship_check['product_is_always_free_shipping'] !== '2') {
                    foreach ($data['attributes'] as $value) {
                        $sql = "SELECT COUNT(*) as total
                                FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                INNER JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad USING (products_attributes_id)
                                WHERE pa.products_id = $prid
                                AND pa.options_values_id = " . (int)$value;

                        $virtual_check = $db->Execute($sql);

                        // -----
                        // If the product has downloads, it's virtual by default.
                        //
                        if ($virtual_check->fields['total'] !== '0') {
                            if ($this->content_type === 'physical') {
                                $this->content_type = 'mixed';
                                return ($gv_only) ? $gift_voucher : $this->content_type;
                            }
                            $this->content_type = 'virtual';
                            continue;
                        }

                        // -----
                        // If the product doesn't have downloads, its virtual setting dictates
                        // whether it's a virtual/physical product.
                        //
                        switch ($this->content_type) {
                            case 'virtual':
                                if ($product_is_virtual === false) {
                                    $this->content_type = 'mixed';
                                    return ($gv_only) ? $gift_voucher : $this->content_type;
                                }
                                break;
                            case 'physical':
                                if ($product_is_virtual === true) {
                                    $this->content_type = 'mixed';
                                    return ($gv_only) ? $gift_voucher : $this->content_type;
                                }
                                $this->content_type = 'physical';
                                break;
                            default:
                                $this->content_type = ($product_is_virtual === true) ? 'virtual' : 'physical';
                                break;
                        }
                    }
                } else {
                    switch ($this->content_type) {
                        case 'virtual':
                            if ($product_is_virtual === false) {
                                $this->content_type = 'mixed';
                                return ($gv_only) ? $gift_voucher : $this->content_type;
                            }
                            break;
                        case 'physical':
                            if ($product_is_virtual === true) {
                                $this->content_type = 'mixed';
                                return ($gv_only) ? $gift_voucher : $this->content_type;
                            }
                            break;
                        default:
                            $this->content_type = ($product_is_virtual === true) ? 'virtual' : 'physical';
                            break;
                    }
                }
            }
        }

        return ($gv_only) ? $gift_voucher : $this->content_type;
    }

    /**
     * Calculate item quantity, bounded by the mixed/min units settings
     *
     * @param int|string $uprid_to_check product id of item to check
     * @return float
     */
    public function in_cart_mixed($uprid_to_check)
    {
        // if nothing is in cart return 0
        if (!is_array($this->contents)) {
            return 0;
        }

        // check if mixed is on
        $product = zen_get_product_details((int)$uprid_to_check);

        // if mixed attributes is off return qty for current attribute selection
        if ($product->fields['products_quantity_mixed'] === '0') {
            return $this->get_quantity($uprid_to_check);
        }

        // compute total quantity regardless of attributes
        $in_cart_mixed_qty = 0;
        $chk_products_id = zen_get_prid($uprid_to_check);

        foreach ($this->contents as $uprid => $data) {
            if (zen_get_prid($uprid) === $chk_products_id) {
                $in_cart_mixed_qty += $data['qty'];
            }
        }

        return $in_cart_mixed_qty;
    }

    /**
     * Calculate item quantity, bounded by the mixed/min units settings
     *
     * @NOTE: NOT USED IN CORE CODE
     *
     * @param int|string $uprid_to_check product id of item to check
     * @return float
     */
    public function in_cart_mixed_discount_quantity($uprid_to_check)
    {
        // if nothing is in cart return 0
        if (!is_array($this->contents)) {
            return 0;
        }

        // check if mixed is on
        $product = zen_get_product_details((int)$uprid_to_check);

        // if mixed attributes is off return qty for current attribute selection
        if ($product->fields['products_mixed_discount_quantity'] === '0') {
            return $this->get_quantity($uprid_to_check);
        }

        // compute total quantity regardless of attributes
        $in_cart_mixed_qty_discount_quantity = 0;
        $chk_products_id = zen_get_prid($uprid_to_check);

        foreach ($this->contents as $uprid => $data) {
            if (zen_get_prid($uprid) === $chk_products_id) {
                $in_cart_mixed_qty_discount_quantity += $data['qty'];
            }
        }
        return $in_cart_mixed_qty_discount_quantity;
    }

    /**
     * Calculate the number of items in a cart based on an abitrary property
     *
     * $check_what is the fieldname example: 'products_is_free'
     * $check_value is the value being tested for - default is 1
     * Syntax: $_SESSION['cart']->in_cart_check('product_is_free','1');
     *
     * @param string $check_what product field to check
     * @param mixed $check_value value to check for
     * @return int number of items matching constraint
     */
    public function in_cart_check($check_what, $check_value = '1')
    {
        // if nothing is in cart return 0
        if (!is_array($this->contents)) {
            return 0;
        }

        // compute total quantity for field
        $in_cart_check_qty = 0;
        foreach ($this->contents as $uprid => $data) {
            // check if field it true
            $product_check = zen_get_product_details(zen_get_prid($uprid));
            if (array_key_exists($check_value, $product_check->fields) && (string)$product_check->fields[$check_what] === (string)$check_value) {
                $in_cart_check_qty += $data['qty'];
            }
        }
        return $in_cart_check_qty;
    }

    /**
     * Check whether cart contains only Gift Vouchers
     *
     * @return float|bool value of Gift Vouchers in cart
     */
    public function gv_only()
    {
        return $this->get_content_type(true);
    }

    /**
     * Return the number of free shipping items in the cart
     *
     * @return float
     */
    public function free_shipping_items()
    {
        $this->calculate();
        return $this->free_shipping_item;
    }

    /**
     * Return the total price of free shipping items in the cart
     *
     * @return float
     */
    public function free_shipping_prices()
    {
        $this->calculate();
        return $this->free_shipping_price;
    }

    /**
     * Return the total weight of free shipping items in the cart
     *
     * @return float
     */
    public function free_shipping_weight()
    {
        $this->calculate();
        return $this->free_shipping_weight;
    }

    /**
     * Return the total number of downloads in the cart
     *
     * @return int|float
     */
    public function download_counts()
    {
        $this->calculate();
        return $this->download_count;
    }

    /**
     * Handle updateProduct cart Action
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */

    /**
     * calculate quantity adjustments based on restrictions
     * USAGE:  $qty = $this->adjust_quantity($qty, (int)$products_id, 'shopping_cart');
     *
     * @param float $check_qty
     * @param int $product_id
     * @param string $messageStackPosition messageStack placement
     * @return float|int
     * @deprecated
     */
    public function adjust_quantity($check_qty, $product_id, $messageStackPosition = 'shopping_cart')
    {
        $qty = $this->adjustQuantity($check_qty, $product_id, $messageStackPosition = 'shopping_cart');
        return $qty;
    }

    /**
     * calculate the number of items in a cart based on an attribute option_id and option_values_id combo
     * USAGE:  $chk_attrib_1_16 = $this->in_cart_check_attrib_quantity(1, 16);
     * USAGE:  $chk_attrib_1_16 = $_SESSION['cart']->in_cart_check_attrib_quantity(1, 16);
     *
     * @param int $check_option_id
     * @param int $check_option_values_id
     * @return float
     */
    public function in_cart_check_attrib_quantity($check_option_id, $check_option_values_id)
    {
        // if nothing is in cart return 0
        if (!is_array($this->contents)) {
            return 0;
        }

        $in_cart_check_qty = 0;
        // get products in cart to check
        $chk_products = $this->get_products();
        foreach ($chk_products as $next_chk) {
            if (is_array($next_chk['attributes'])) {
                foreach ($next_chk['attributes'] as $option => $value) {
                    // these are intentionally loose-comparisons
                    if ($option == $check_option_id && $value == $check_option_values_id) {
                        $in_cart_check_qty += $next_chk['quantity'];
                    }
                }
            }
        }
        return $in_cart_check_qty;
    }

    /**
     * calculate products_id price in cart
     * USAGE:  $product_total_price = $this->in_cart_product_total_price(12);
     * USAGE:  $chk_product_cart_total_price = $_SESSION['cart']->in_cart_product_total_price(12);
     *
     * @param mixed $product_id
     * @return float
     */
    public function in_cart_product_total_price($product_id)
    {
        $products = $this->get_products();
        $in_cart_product_price = 0;

        foreach ($products as $key => $val) {
            if ((int)$product_id === (int)$val['id']) {
                $in_cart_product_price += ($val['final_price'] * $val['quantity']) + $val['onetime_charges'];
            }
        }
        return $in_cart_product_price;
    }

    /**
     * calculate products_id quantity in cart regardless of attributes
     * USAGE:  $product_total_quantity = $this->in_cart_product_total_quantity(12);
     * USAGE:  $chk_product_cart_total_quantity = $_SESSION['cart']->in_cart_product_total_quantity(12);
     *
     * @param mixed $product_id
     * @return int|mixed
     */
    public function in_cart_product_total_quantity($product_id)
    {
        $products = $this->get_products();

        $in_cart_product_quantity = 0;
        foreach ($products as $key => $val) {
            if ((int)$product_id === (int)$val['id']) {
                $in_cart_product_quantity += $val['quantity'];
            }
        }
        return $in_cart_product_quantity;
    }

    /**
     * calculate products_id weight in cart regardless of attributes
     * USAGE:  $product_total_weight = $this->in_cart_product_total_weight(12);
     * USAGE:  $chk_product_cart_total_weight = $_SESSION['cart']->in_cart_product_total_weight(12);
     *
     * @param mixed $product_id
     * @return float
     */
    public function in_cart_product_total_weight($product_id)
    {
        $products = $this->get_products();
        $in_cart_product_weight = 0;
        foreach ($products as $product) {
            if ((int)$product_id === (int)$product['id']) {
                $in_cart_product_weight += $product['weight'] * $product['quantity'];
            }
        }
        return $in_cart_product_weight;
    }

    /**
     * calculate weight in cart for a category without subcategories
     * USAGE:  $category_total_weight_cat = $this->in_cart_product_total_weight_category(9);
     * USAGE:  $chk_category_cart_total_weight_cat = $_SESSION['cart']->in_cart_product_total_weight_category(9);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_weight_category($category_id)
    {
        $products = $this->get_products();
        $in_cart_product_weight = 0;
        foreach ($products as $product) {
            if ($product['category'] === $category_id) {
                $in_cart_product_weight += $product['weight'] * $product['quantity'];
            }
        }
        return $in_cart_product_weight;
    }

    /**
     * calculate price in cart for a category without subcategories
     * USAGE:  $category_total_price_cat = $this->in_cart_product_total_price_category(9);
     * USAGE:  $chk_category_cart_total_price_cat = $_SESSION['cart']->in_cart_product_total_price_category(9);
     *
     * @param int $category_id
     * @return float|int
     */
    public function in_cart_product_total_price_category($category_id)
    {
        $products = $this->get_products();
        $in_cart_product_price = 0;

        foreach ($products as $key => $val) {
            if ((int)$val['category'] === (int)$category_id) {
                $in_cart_product_price += ($val['final_price'] * $val['quantity']) + $val['onetime_charges'];
            }
        }
        return $in_cart_product_price;
    }

    /**
     * calculate quantity in cart for a category without subcategories
     * USAGE:  $category_total_quantity_cat = $this->in_cart_product_total_quantity_category(9);
     * USAGE:  $chk_category_cart_total_quantity_cat = $_SESSION['cart']->in_cart_product_total_quantity_category(9);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_quantity_category($category_id)
    {
        $products = $this->get_products();

        $in_cart_product_quantity = 0;
        foreach ($products as $key => $val) {
            if ((int)$val['category'] === (int)$category_id) {
                $in_cart_product_quantity += $val['quantity'];
            }
        }
        return $in_cart_product_quantity;
    }

    /**
     * calculate weight in cart for a category with or without subcategories
     * USAGE:  $category_total_weight_cat = $this->in_cart_product_total_weight_category_sub(3);
     * USAGE:  $chk_category_cart_total_weight_cat = $_SESSION['cart']->in_cart_product_total_weight_category_sub(3);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_weight_category_sub($category_id)
    {
        if (!zen_has_category_subcategories($category_id)) {
           return $this->in_cart_product_total_weight_category($category_id);
        }

        $subcategories_array = [];
        zen_get_subcategories($subcategories_array, $category_id); // parent categories_id
        $chk_cart_weight = 0;
        foreach ($subcategories_array as $category) {
            $chk_cart_weight += $this->in_cart_product_total_weight_category($category);
        }
        return $chk_cart_weight;
    }

    /**
     * calculate price in cart for a category with or without subcategories
     * USAGE:  $category_total_price_cat = $this->in_cart_product_total_price_category_sub(3);
     * USAGE:  $chk_category_cart_total_price_cat = $_SESSION['cart']->in_cart_product_total_price_category_sub(3);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_price_category_sub($category_id)
    {
        if (!zen_has_category_subcategories($category_id)) {
            return $this->in_cart_product_total_price_category($category_id);
        }

        $subcategories_array = [];
        zen_get_subcategories($subcategories_array, $category_id); // parent categories_id
        $chk_cart_price = 0;
        foreach ($subcategories_array as $category) {
            $chk_cart_price += $this->in_cart_product_total_price_category($category);
        }
        return $chk_cart_price;
    }

    /**
     * calculate quantity in cart for a category with or without subcategories
     * USAGE:  $category_total_quantity_cat = $this->in_cart_product_total_quantity_category_sub(3);
     * USAGE:  $chk_category_cart_total_quantity_cat = $_SESSION['cart']->in_cart_product_total_quantity_category_sub(3);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_quantity_category_sub($category_id)
    {
        if (!zen_has_category_subcategories($category_id)) {
            return $this->in_cart_product_total_quantity_category($category_id);
        }

        $subcategories_array = [];
        zen_get_subcategories($subcategories_array, $category_id); // parent categories_id
        $chk_cart_quantity = 0;
        foreach ($subcategories_array as $category) {
            $chk_cart_quantity += $this->in_cart_product_total_quantity_category($category);
        }
        return $chk_cart_quantity;
    }

    /**
     * calculate shopping cart stats for a products_id to obtain data about submitted (posted) items as compared to what is in the cart.
     * USAGE:  $mix_increase = in_cart_product_mixed_changed($product_id, 'increase');
     * USAGE:  $mix_decrease = in_cart_product_mixed_changed($product_id, 'decrease');
     * USAGE:  $mix_all = in_cart_product_mixed_changed($product_id);
     * USAGE:  $mix_all = in_cart_product_mixed_changed($product_id, 'all'); (Second value anything other than 'increase' or 'decrease')
     *
     * @param int|string $product_id
     * @param bool $chk
     * @return array|bool
     */
    public function in_cart_product_mixed_changed($product_id, $chk = false)
    {
        global $db;

        $pr_id = zen_get_prid($product_id);

        if ($pr_id === 0) {
            return true;
        }

        // check if mixed is on
        $product = zen_get_product_details((int)$pr_id);

        // if mixed attributes is off identify that this product is the last of its kind (which is also the first of its kind).
        if (empty($product->fields['products_quantity_mixed'])) {
            return true;
        }

        $product_changed = [];
        $product_total_change = [$pr_id => 0];
        $product_tracked_changed = [];
        $product_last_changed = [];
        $product_increase = [];
        $product_decrease = [];

        for ($i = 0, $n = count($_POST['products_id']); $i < $n; $i++) {
            $products_id = $_POST['products_id'][$i];
            $prs_id = zen_get_prid($products_id);
            $current_qty = $this->get_quantity($products_id); // $products[$i]['quantity']
            if (!is_numeric($_POST['cart_quantity'][$i]) || $_POST['cart_quantity'][$i] < 0) {
                $_POST['cart_quantity'][$i] = $current_qty; // Default response behavior in cart.
            }
            // Ensure array key exists before use in assignment.
            if (!array_key_exists($prs_id, $product_last_changed)) {
                $product_last_changed[$prs_id] = null;
            }
            if (!array_key_exists($prs_id, $product_total_change)) {
                $product_total_change[$prs_id] = 0;
            }
            if ($_POST['cart_quantity'][$i] != $current_qty) { // identify that quantity changed
                $product_changed[$products_id] = $_POST['cart_quantity'][$i] - $current_qty;  // Identify that the specific product changed and by how much the customer increased it.
                if (array_key_exists($prs_id, $product_total_change)) {
                    $product_total_change[$prs_id] += $product_changed[$products_id];
                } else {
                    $product_total_change[$prs_id] = $product_changed[$products_id];
                }

                switch (true) {
                    case ($chk === 'increase'): // track only increases
                        if ($_POST['cart_quantity'][$i] > $current_qty) {
                            $product_tracked_changed[$products_id] = true;  // Identify that the specific product changed
                            $product_last_changed[$prs_id] = $products_id; // Identify what the last changed product was.
                            $product_increase[] = $products_id;
                        }
                        break;
                    case ($chk === 'decrease'): // track only decreases
                        if ($_POST['cart_quantity'][$i] < $current_qty) {
                            $product_tracked_changed[$products_id] = true;  // Identify that the specific product changed
                            $product_last_changed[$prs_id] = $products_id; // Identify what the last changed product was.
                            $product_decrease[] = $products_id;
                        }
                        break;
                    default: // track the last that had a difference in quantity.
                        $product_tracked_changed[$products_id] = true;  // Identify that the specific product changed
                        $product_last_changed[$prs_id] = $products_id; // Identify what the last changed product was.
                        if ($_POST['cart_quantity'][$i] > $current_qty) {
                            $product_increase[] = $products_id;
                        }
                        if ($_POST['cart_quantity'][$i] < $current_qty) {
                            $product_decrease[] = $products_id;
                        }
                        break;
                }
            }
        }

        $changed_array = [
            'state' => false,
            'changed' => $product_changed,
            'total_change' => $product_total_change[$pr_id],
            'last_changed' => $product_last_changed[$pr_id],
            'increase' => $product_increase,
            'decrease' => $product_decrease,
        ];

        if (array_key_exists($product_id, $product_changed)) {
            if ($product_total_change[$pr_id] == 0) {
                $changed_array['state'] = 'netzero';
                return $changed_array;
            }

            if (array_key_exists($product_id, $product_tracked_changed)) {
                if ($product_last_changed[$pr_id] == $product_id) {
                    $changed_array['state'] = true;
                    return $changed_array;
                }
            }
        }

        return $changed_array;
    }
}
