<?php

namespace Zencart\Cart;

use App\Models\Attribute;
use App\Models\Basket;
use App\Models\BasketAttribute;
use App\Models\BasketProduct;
use App\Models\Product;
use App\Models\ProductAttribute;
use Zencart\Traits\NotifierManager;
use Zencart\Traits\ObserverManager;

class Cart
{
    use NotifierManager;
    use ObserverManager;

    public static bool $createBrowser = false;
    protected \queryFactory $db;
    protected string $cartType;
    protected Basket $basket;
    protected $free_shipping_weight;
    protected $free_shipping_item;
    protected $free_shipping_price;
    protected $total;
    protected $weight;
    protected $download_count;
    protected $total_before_discounts;
    protected $content_type;
    protected CartPricing $cartPricing;
    protected CartValidator $cartValidator;


    /**
     * Instantiate a new shopping cart object
     *
     * Noted use of $_SESSION['cart'] to suggest which
     * methods/vars should be public
     *
     * Methods
     *
     * $_SESSION['cart']->restore_contents() -> restoreContents()
     * $_SESSION['add_to_cart'] -> addToCart()
     * $_SESSION['cart']->get_products() ->
     * $_SESSION['cart']->reset() -> resetCart()
     * $_SESSION['cart']->in_cart() -> inCart()
     * $_SESSION['cart']->count_contents() -> countContents()
     * $_SESSION['cart']->show_total()
     * $_SESSION['cart']->get_content_type()
     * $_SESSION['cart']->get_quantity()
     * $_SESSION['cart']->gv_only()
     * $_SESSION['cart']->in_cart_mixed()
     * $_SESSION['cart']->show_weight()
     * $_SESSION['cart']->restore_contents()
     * $_SESSION['cart']->free_shipping_items();
     * $_SESSION['cart']->free_shipping_prices()
     *
     * Class Variables
     *
     * The main issues here is the use of $_SESSION['cart']->contents in various places.
     * while we could rewrite those portions then would have to look at the effect on plugins.
     *
     *
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->notify('NOTIFIER_CART_INSTANTIATE_START');
        $this->cartType = $this->setCartType();
        $this->basket = $this->restoreBasket();
        $this->cartPricing = new CartPricing();
        $this->cartValidator = new CartValidator();
        $this->reset();
        $this->notify('NOTIFIER_CART_INSTANTIATE_END');
    }

    public function reset(bool $reset_database = false): void
    {
        $this->notify('NOTIFIER_CART_RESET_START', null, $reset_database);
        $this->total = 0;
        $this->weight = 0;
        $this->download_count = 0;
        $this->total_before_discounts = 0;
        $this->content_type = false;

        // shipping adjustment
        $this->free_shipping_item = 0;
        $this->free_shipping_price = 0;
        $this->free_shipping_weight = 0;

        if ($reset_database) {
            $this->removeCart();
        }
        unset($this->cartID);
        $_SESSION['cartID'] = '';
        $_SESSION['cart_errors'] = '';
        $_SESSION['valid_to_checkout'] = true;
        $this->notify('NOTIFIER_CART_RESET_END');
    }

    public function restoreContents()
    {
        $this->notify('NOTIFIER_CART_RESTORE_CONTENTS_START');
        $this->basket = $this->restoreBasket();
        $this->cartID = $this->generateCartId();
        $this->notify('NOTIFIER_CART_RESTORE_CONTENTS_END');
        $this->cleanup();
    }

    public function addToCart($product_id, $qty = 1, $attributes = [], $notify = true)
    {
        if ($this->canAddAttributeOrQuantity($product_id, $attributes)) {
            $attributes = $this->buildAttributes($product_id, $attributes);
            $qty += $this->in_cart_product_total_quantity($product_id);
        }
        $qty = $this->adjustQtyWhenNotAValue($qty, $product_id);
        $this->notify('NOTIFIER_CART_ADD_CART_START', null, $product_id, $qty, $attributes, $notify);
        $uprid = zen_get_uprid($product_id, $attributes);
        if ($notify) {
            $_SESSION['new_product_id_in_cart'] = $uprid;
        }
        $qty = $this->adjustQuantity($qty, $uprid, 'shopping_cart');
        $this->updateQuantity($uprid, $qty, $attributes);
        $this->cartID = $this->generateCartId();
        $this->notify('NOTIFIER_CART_ADD_CART_END', null, $product_id, $qty, $attributes, $notify);
        $this->cleanup();
    }

    public function inCart(string $uprid): bool
    {
        $this->notify('NOTIFIER_CART_IN_CART_START', null, $uprid);
        $inCart = BasketProduct::where('basket_id', $this->basket->id)->where('product_id', $uprid)->first();
        if ($inCart) {
            $this->notify('NOTIFIER_CART_IN_CART_END_TRUE', null, $uprid);
            return true;
        }
        $this->notify('NOTIFIER_CART_IN_CART_END_FALSE', $uprid);
        return false;
    }

    public function inCartMixed(string $uprid)
    {
        $inCart = BasketProduct::where('basket_id', $this->basket->id)->where('product_id', $uprid)->first();
        if (!$inCart) {
            return 0;
        }
        $product = Product::find((int)$uprid);
        //var_dump($product);
    }
    public function countContents(): float
    {
        $this->notify('NOTIFIER_CART_COUNT_CONTENTS_START');
        $totalItems = BasketProduct::where('basket_id', $this->basket->id)->sum('quantity');
        $this->notify('NOTIFIER_CART_COUNT_CONTENTS_END');
        return $totalItems;

    }

    /**
     * @param bool $check_for_valid_cart
     * @return array
     *
     * @todo note notifier change for NOTIFY_CART_GET_PRODUCTS_NEXT
     */
    public function getProducts(bool $check_for_valid_cart = false): array
    {
        $this->notify('NOTIFIER_CART_GET_PRODUCTS_START', null, $check_for_valid_cart);
        $products_array = [];
        $basketProducts = BasketProduct::where('basket_id', $this->basket->id)->with('basketAttributes')->with('product')->with('product.attributes')->get();
        //var_dump($basketProducts);
        if (count($basketProducts) === 0) {
            return $products_array;
        }
        foreach ($basketProducts as $basketProduct)
        {
            $prid = zen_get_prid($basketProduct->id);
            $uprid = $basketProduct->product_id;
            $this->notify('NOTIFY_CART_GET_PRODUCTS_NEXT', $uprid, $basketProduct);
            $this->cartPricing->calculateProductPricing($basketProduct);
            $action = $this->cartValidator->validateCart($this, $basketProduct, $uprid, $check_for_valid_cart);
            if (!$action) {
                continue;
            }
//            $products_array[] = [
//                'id' => $uprid,
//                'category' => $basketProduct->product['master_categories_id'],
//                'name' => $basketProduct->product['products_name'],
//                'model' => $basketProduct->product['products_model'],
//                'image' => $basketProduct->product['products_image'],
//                'price' => ($basketProduct->product['product_is_free'] === '1') ? 0 : $this->cartPricing->getProductPrice(),
//                'quantity' => $this->cartValidator->getNewQuantity(),
//                'weight' => $basketProduct->product['products_weight'] + $this->attributesWeight($basketProduct),
//
//                'weight_type' => $basketProduct->product['products_weight_type'] ?? null,
//                'dim_type' => $basketProduct->product['products_dim_type'] ?? null,
//                'length' => $basketProduct->product['products_length'] ?? null,
//                'width' => $basketProduct->product['products_width'] ?? null,
//                'height' => $basketProduct->product['products_height'] ?? null,
//                'ready_to_ship' => $basketProduct->product['products_ready_to_ship'] ?? null,
//
//                'final_price' => $this->cartPricing->getProductPrice() + $this->attributesPrice($basketProduct),
//                'onetime_charges' => $this->attributesPriceOnetimeCharges($basketProduct, $this->cartValidator->getNewQuantity()),
//                'tax_class_id' => $basketProduct->product['products_tax_class_id'],
//                'attributes' => $data['attributes'] ?? '',
//                'attributes_values' => $data['attributes_values'] ?? '',
//                'products_priced_by_attribute' => $basketProduct->product['products_priced_by_attribute'],
//                'product_is_free' => $basketProduct->product['product_is_free'],
//                'products_discount_type' => $basketProduct->product['products_discount_type'],
//                'products_discount_type_from' => $basketProduct->product['products_discount_type_from'],
//                'products_virtual' => (int)$basketProduct->product['products_virtual'],
//                'product_is_always_free_shipping' => (int)$basketProduct->product['product_is_always_free_shipping'],
//                'products_quantity_order_min' => (float)$basketProduct->product['products_quantity_order_min'],
//                'products_quantity_order_units' => (float)$basketProduct->product['products_quantity_order_units'],
//                'products_quantity_order_max' => (float)$basketProduct->product['products_quantity_order_max'],
//                'products_quantity_mixed' => (int)$basketProduct->product['products_quantity_mixed'],
//                'products_mixed_discount_quantity' => (int)$basketProduct->product['products_mixed_discount_quantity'],
//            ];
     //       var_dump($products_array);
        }

        $this->notify('NOTIFIER_CART_GET_PRODUCTS_END', null, $products_array);
        return $products_array;
    }
    protected function restoreBasket()
    {
        $basket = $this->getCurrentBasket();
        if ($basket) {
            return $this->getCurrentBasket()->with(['basketProducts'])->with('basketProducts.basketAttributes')->first();
        }
        $column = $this->basketWhereColumn();
        $value = $this->basketWhereValue();
        $basket = new Basket();
        $basket->name = $this->basketName();
        $basket->$column = $value;
        $basket->save();
        return $this->getCurrentBasket()->with(['basketProducts'])->with('basketProducts.basketAttributes')->first();;

    }
    protected function updateQuantity($uprid, $quantity, $attributes)
    {
        if (!$this->inCart($uprid)) {
            return;
        }
        $quantity = $this->adjustQtyWhenNotAValue($quantity, $uprid);
        $this->notify('NOTIFIER_CART_UPDATE_QUANTITY_START', null, $uprid, $quantity, $attributes);
        if (empty($quantity)) {
            return; // nothing needs to be updated if theres no quantity, so we return.
        }
        $chk_current_qty = zen_get_products_stock($uprid);
        if (STOCK_ALLOW_CHECKOUT === 'false' && $quantity > $chk_current_qty) {
            $quantity = $chk_current_qty;
            if (!$this->flag_duplicate_msgs_set) {
                $this->messageStack->add_session('shopping_cart', ($this->display_debug_messages ? '$_GET[main_page]: ' . $_GET['main_page'] . ' FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($uprid), 'caution');
            }
        }

        $prid = zen_get_prid($uprid);
        $basketProduct = BasketProduct::where('product_id', $uprid)->where('basket_id', $this->basket->id);
        $basketProduct->update(['quantity' => $quantity]);
        $this->updateAttributes($basketProduct, $attributes, $prid, $uprid);
        $this->cartID = $this->generateCartId();
        $this->notify('NOTIFIER_CART_UPDATE_QUANTITY_END');
    }

    protected function updateAttributes($basketProduct, $attributes, $prid, $uprid)
    {
        if (!is_array($attributes)) {
            return;
        }
        foreach ($attributes as $option => $value) {
            $attr_value = null;
            $blank_value = false;
            if (is_string($option) && str_starts_with($option, TEXT_PREFIX)) {
                if (trim($value) === '') {
                    $blank_value = true;
                } else {
                    $option = substr($option, strlen(TEXT_PREFIX));
                    $attr_value = stripslashes($value);
                    $value = PRODUCTS_OPTIONS_VALUES_TEXT_ID;
                }
            }
            if ($blank_value === false) {
                $this->updateAttributesBasket($basketProduct, $value, $option, $prid, $uprid, $attr_value);
            }
        }
    }

    protected function updateAttributesBasket($basketProduct, $value, $option, $prid, $uprid)
    {
        if (is_array($value)) {
            foreach ($value as $opt => $val) {
                BasketAttribute::where('basket_product_id', $basketProduct->id)->where('products_options_id', (int)$option . '_chk . (int)$val')->update(['products_options_value_id' => $val]);
            }
        } else {
            BasketAttribute::where('basket_product_id', $basketProduct->id)->where('products_options_id', $option)->update(['products_options_value_id' => (int)$value, 'products_options_value_text' => $attr_value]);
        }

    }
    protected function adjustQuantity($check_qty, $product_id, $messageStackPosition = 'shopping_cart')
    {
        if (empty($messageStackPosition)) {
            $messageStackPosition = 'shopping_cart';
        }
        $precision = QUANTITY_DECIMALS > 0 ? (int)QUANTITY_DECIMALS : 0;
        if ($precision !== 0) {
            if (str_contains((string)$check_qty, '.')) {
                return $check_qty;
            }
            return preg_replace('/[0]+$/', '', $check_qty);
        }
        if ($check_qty != round(zen_str_to_numeric($check_qty), $precision)) {
            $new_qty = round(zen_str_to_numeric($check_qty), $precision);
            $this->messageStack->add_session($messageStackPosition, ERROR_QUANTITY_ADJUSTED . zen_get_products_name($product_id) . ERROR_QUANTITY_CHANGED_FROM . $check_qty . ERROR_QUANTITY_CHANGED_TO . $new_qty, 'caution');
            return $new_qty;
        }
        return $check_qty;
    }


    protected function adjustQtyWhenNotAValue($qty, $product_id): float
    {
        if (is_numeric($qty) && $qty >= 0) {
            return $qty;
        }
        $chk_link =
            '<a href="' .
            zen_href_link(
                zen_get_info_page($product_id),
                'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($product_id))) .
                '&product_id=' . $product_id
            ) .
            '">' .
            zen_get_products_name($product_id) .
            '</a>';

        $this->messageStack->add_session('header', ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($qty), 'caution');
        return 0;
    }

    protected function buildAttributes(int $product_id, array $attributes): array
    {
        $attributes = [];
        $results = ProductAttribute::where('product_id', $product_id)->get();
        foreach ($results as $attribute) {
            $attributes[$attribute['options_id']] = $attribute['options_values_id'];
        }
        return $attributes;
    }


    protected function canAddAttributeOrQuantity($product_id, $attributes): bool
    {
        if (!empty($attributes) || zen_has_product_attributes($product_id, false)) {
            return false;
        }
        if (zen_requires_attribute_selection($product_id)) {
            return false;
        }
        return true;
    }

    protected function attributesWeight(BasketProduct $basketProduct)
    {
        if (!isset($basketProduct->product->attributes)) {
            return 0;
        }
        $this->notify('NOTIFY_CART_ATTRIBUTES_WEIGHT_START', $basketProduct['products_id']);
        $attribute_weight = 0;
        foreach ($basketProduct->basketAttributes as $basketAttribute) {
            $attribute = Attribute::getAttributeDetails((int)$basketAttribute->basketProduct['product_id'], $basketAttribute['options_id'], $basketAttribute['options_values_id'])->first();
            $this->notify('NOTIFY_CART_ATTRIBUTES_WEIGHT_NEXT', $basketProduct['products_id'], $attribute);
            $new_attributes_weight = (zen_get_product_is_always_free_shipping((int)$basketProduct['products_id']) === false) ?
                $attribute['products_attributes_weight'] : 0;
            if ($attribute['products_attributes_weight_prefix'] === '-') {
                $attribute_weight -= $new_attributes_weight;
            } else {
                $attribute_weight += $attribute['products_attributes_weight'];
            }
        }
        return $attribute_weight;
    }

    protected function attributesPrice(BasketProduct $basketProduct)
    {
        $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_START', $basketProduct['products_id']);
        if (!isset($basketProduct->product->attributes)) {
            return 0;
        }
        zen_define_default('ATTRIBUTES_PRICE_FACTOR_FROM_SPECIAL', 1);
        $total_attributes_price = 0;
        $qty = $basketProduct['quantity'];
        foreach ($basketProduct->product->attributes as $attribute) {
            $this->notify('NOTIFY_CART_ATTRIBUTES_PRICE_NEXT', (int)$basketProduct['products_id'], $attribute);
            $attributePrice = 0;
            $new_attributes_price = 0;
            $discount_type_id = '';
            $sale_maker_discount = '';
            $options_values_price = zen_get_retail_or_wholesale_price(
                $attribute['options_values_price'],
                $attribute['options_values_price_w']
            );
            $attributePrice = $this->addAdditionalAttributePrices((int)$basketProduct['products_id'], $attribute, $attributePrice, $options_values_price, $qty);
        }
    }

    protected function addAdditionalAttributePrices($productId, $attribute, $attributePrice, $options_values_price, $qty)
    {
        if ($attribute['product_attribute_is_free'] === '1' && zen_get_products_price_is_free($productId)) {
            return $attributePrice;
        }
        $attributePrice = $this->attributePriceHandleDiscounted($productId, $attribute, $attributePrice, $options_values_price, $qty);
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

    protected function attributePriceHandlePrefixDiscount($productId, $attribute, $attributePrice, $options_values_price, $qty)
    {
        $products_raw_price = zen_get_product_retail_or_wholesale_price($productId);
        $products_raw_attribute_base_price = (zen_get_products_price_is_priced_by_attributes($productId)) ? $products_raw_price : 0.0;
        $new_attributes_price = zen_get_discount_calc($productId, $attribute['products_attributes_id'], zen_str_to_numeric($options_values_price) + $products_raw_attribute_base_price, $qty);
        $new_attributes_price -= $products_raw_attribute_base_price;
        $attributePrice += $new_attributes_price;
        return $attributePrice;
    }
    protected function attributePriceHandlingPricePrefix($productId, $attribute, $attributePrice, $options_values_price, $qty)
    {
        if ($attribute['attributes_discounted'] !== '1') {
            return $attributePrice -= options_values_price;
        }
        $discount_type_id = '';
        $sale_maker_discount = '';
        $new_attributes_price = zen_get_discount_calc($productId, $attribute['products_attributes_id'], $options_values_price, $qty);
        $attributePrice -= $new_attributes_price;
        return $attributePrice;

    }

    public function getCurrentBasket(): ?Basket
    {
        $basketName = $this->basketName();
        $whereColumn = $this->basketWhereColumn();
        $whereValue = $this->basketWhereValue();
        $basket = Basket::where($whereColumn, $whereValue)->where('name', $basketName)->first();
        return $basket;

    }

    protected function removeCart()
    {
        $this->notify('NOTIFIER_CART_REMOVE_START', null, $uprid);
        $this->getCurrentBasket()->delete();
        $this->notify('NOTIFIER_CART_REMOVE_END');
    }

    public function remove($productId)
    {
        $this->notify('NOTIFIER_CART_REMOVE_START', null, $uprid);
        $this->removeProductFromCart(zen_db_input($productId));

        // assign a temporary unique ID to the order contents to prevent hack attempts during the checkout procedure
        $this->cartID = $this->generateCartId();
        $this->notify('NOTIFIER_CART_REMOVE_END');

    }
    protected function removeProductFromCart($productId)
    {
        BasketProduct::where('product_id', $productId)->delete();
    }

    protected function basketWhereColumn(): string
    {
        $columnForWhere = 'customer_id';
        if ($this->getCartType() === 'guest') {
            $columnForWhere = 'unique_id';
        }
        return $columnForWhere;
    }

    protected function basketWhereValue(): string
    {
        $whereValue = session_id();
        if ($this->getCartType() === 'customer') {
            $whereValue = $_SESSION['customer_id'];
        }
        return $whereValue;
    }

    protected function basketName(): string
    {
        return $_SESSION['basket_name'] ?? 'default';
    }

    public function generateCartId($length = 5): string
    {
        return zen_create_random_value($length, 'digits');
    }

    /**
     * Clean up cart contents - removes zero-qty items
     *
     * For various reasons, the quantity of an item in the cart can
     * fall to zero. This method removes from the cart
     * all items that have reached this state. The database-stored cart
     * is also updated where necessary
     *
     * @cart-todo should we do the same for db table
     * @return void
     */
    protected function cleanup()
    {
        $this->notify('NOTIFIER_CART_CLEANUP_START');

        $this->notify('NOTIFIER_CART_CLEANUP_END');
    }

    // getters and setters

    protected function setCartType()
    {
        return isset($_SESSION['customer_id']) ? 'customer' : 'guest';//@todo cart. guest checkout
    }

    public function getCartType()
    {
        return $this->cartType;
    }
}
