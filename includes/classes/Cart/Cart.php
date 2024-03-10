<?php

namespace Zencart\Cart;

use App\Models\Attribute;
use App\Models\Basket;
use App\Models\BasketAttribute;
use App\Models\BasketProduct;
use App\Models\Product;
use App\Models\ProductAttribute;
use Zencart\Traits\NotifierManager;

class Cart
{
    use NotifierManager, CartActionsTrait;

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
    protected $cartErrors = [];


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
        $this->cartValidator = new CartValidator($this);
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
//            var_dump('HERE');
            $attributes = $this->buildAttributes($product_id, $attributes);
            $qty += $this->inCartProductTotalQuantity($product_id);
        }
        $qty = $this->adjustQtyWhenNotAValue($qty, $product_id);
//        var_dump($qty);
        $this->notify('NOTIFIER_CART_ADD_CART_START', null, $product_id, $qty, $attributes, $notify);
        $uprid = zen_get_uprid($product_id, $attributes);
        if ($notify) {
            $_SESSION['new_product_id_in_cart'] = $uprid;
        }
        $qty = $this->adjustQuantity($qty, $uprid, 'shopping_cart');
        if (!$this->updateQuantity($uprid, $qty, $attributes)) {
            $this->addNewItemToCart($uprid, $qty, $attributes);
        }
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
        $basketProducts = BasketProduct::where('basket_id', $this->basket->id);
        $inCart = $basketProducts->where('product_id', $uprid)->first();
        if (!$inCart) {
            return 0;
        }
        $basketProducts = $basketProducts->get();
        $product = Product::find((int)$uprid)->first();
        if ($product['products_quantity_mixed'] === '0') {
            return $this->getQuantity($uprid);
        }
        $in_cart_mixed_qty = 0;
        $chk_products_id = zen_get_prid($uprid);

        foreach ($basketProducts as $basketProduct) {
            if (zen_get_prid($basketProduct['product_id']) === $chk_products_id) {
                $in_cart_mixed_qty += $basketProduct['quantity'];
            }
        }
        return $in_cart_mixed_qty;
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
        if (count($basketProducts) === 0) {
            return $products_array;
        }
        foreach ($basketProducts as $basketProduct) {
            $this->cartPricing->buildProductDetail($basketProduct);
            $this->notify('NOTIFY_CART_GET_PRODUCTS_NEXT', $this->cartPricing->getUniqueProductId(), $basketProduct);
            $this->cartPricing->calculateProductPricing($basketProduct);
            $this->cartPricing->calculateAttributePricing($basketProduct);
            $action = $this->cartValidator->validateCart($this, $basketProduct, $this->cartPricing->getUniqueProductId(), $check_for_valid_cart);
            if (!$action) {
                continue;
            }
            $products_array[] = [
                'id' => $this->cartPricing->getUniqueProductId(),
                'category' => $basketProduct->product['master_categories_id'],
                'name' => $basketProduct->product['products_name'],
                'model' => $basketProduct->product['products_model'],
                'image' => $basketProduct->product['products_image'],
                'price' => ($basketProduct->product['product_is_free'] === '1') ? 0 : $this->cartPricing->getProductPrice(),
                'quantity' => $this->cartValidator->getNewQuantity(),
                'weight' => $basketProduct->product['products_weight'] + $this->attributesWeight($basketProduct),

                'weight_type' => $basketProduct->product['products_weight_type'] ?? null,
                'dim_type' => $basketProduct->product['products_dim_type'] ?? null,
                'length' => $basketProduct->product['products_length'] ?? null,
                'width' => $basketProduct->product['products_width'] ?? null,
                'height' => $basketProduct->product['products_height'] ?? null,
                'ready_to_ship' => $basketProduct->product['products_ready_to_ship'] ?? null,

                'final_price' => $this->cartPricing->getProductPrice() + $this->cartPricing->getAttributePrice(),
                'onetime_charges' => $this->cartPricing->getAttributePriceOnetimeCharge(),
                'tax_class_id' => $basketProduct->product['products_tax_class_id'],
                'attributes' => $basketProduct?->basketAttributes ?? '',
//                'attributes_values' => $data['attributes_values'] ?? '',
                'products_priced_by_attribute' => $basketProduct->product['products_priced_by_attribute'],
                'product_is_free' => $basketProduct->product['product_is_free'],
                'products_discount_type' => $basketProduct->product['products_discount_type'],
                'products_discount_type_from' => $basketProduct->product['products_discount_type_from'],
                'products_virtual' => (int)$basketProduct->product['products_virtual'],
                'product_is_always_free_shipping' => (int)$basketProduct->product['product_is_always_free_shipping'],
                'products_quantity_order_min' => (float)$basketProduct->product['products_quantity_order_min'],
                'products_quantity_order_units' => (float)$basketProduct->product['products_quantity_order_units'],
                'products_quantity_order_max' => (float)$basketProduct->product['products_quantity_order_max'],
                'products_quantity_mixed' => (int)$basketProduct->product['products_quantity_mixed'],
                'products_mixed_discount_quantity' => (int)$basketProduct->product['products_mixed_discount_quantity'],
            ];
        }

        $this->notify('NOTIFIER_CART_GET_PRODUCTS_END', null, $products_array);
        return $products_array;
    }

    public function inCartProductTotalQuantity($productId)
    {
        $products = $this->getProducts();

        $in_cart_product_quantity = 0;
        foreach ($products as $key => $val) {
            if ((int)$productId === (int)$val['id']) {
                $in_cart_product_quantity += $val['quantity'];
            }
        }
        return $in_cart_product_quantity;
    }

    protected function addNewItemToCart($uprid, $quantity, $attributes)
    {
        $basketProduct = new BasketProduct();
        $basketProduct->basket_id = $this->basket->id;
        $basketProduct->product_id = $uprid;
        $basketProduct->quantity = $quantity;
        $basketProduct->save();
        if (!is_array($attributes)) {
            return;
        }
        foreach ($attributes as $option => $value) {
            $blank_value = false;
            if (is_string($option) && str_starts_with($option, TEXT_PREFIX) && trim($value) === '') {
                $blank_value = true;
            }
            if ($blank_value) {
                return;
            }
            if (is_array($value)) {
                $this->addNewAttributeArrayValuesToCart($uprid, $value, $option, $basketProduct);
            } else {
                $this->addNewAttributeSingleValueToCart($uprid, $value, $option, $basketProduct);
            }
        }
    }

    protected function addNewAttributeArrayValuesToCart($uprid, $values, $option, $basketProduct)
    {
//        var_dump('addNewAttributeArrayValuesToCart');
        foreach ($values as $opt => $val) {
            $products_options_sort_order = zen_get_attributes_options_sort_order((int)$uprid, $option, $opt);
            $basketAttribute = new BasketAttribute();
            $basketAttribute->basket_product_id = $basketProduct->id;
            $basketAttribute->options_id = $option . '_chk' . $val;
            $basketAttribute->options_values_id = $val;
            $basketAttribute->options_sort_order = $products_options_sort_order;
            $basketAttribute->save();
        }
    }

    protected function addNewAttributeSingleValueToCart($uprid, $value, $option, $basketProduct)
    {
//        var_dump('addNewAttributeSingleValueToCart');
        $products_options_sort_order = zen_get_attributes_options_sort_order((int)$uprid, $option, $value);
        $basketAttribute = new BasketAttribute();
        $basketAttribute->basket_product_id = $basketProduct->id;
        $basketAttribute->options_id = $option;
        $basketAttribute->options_values_id = $value;
        $basketAttribute->options_value_text = '';
        $basketAttribute->options_sort_order = $products_options_sort_order;
        $basketAttribute->save();
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
            return false;
        }
        $quantity = $this->adjustQtyWhenNotAValue($quantity, $uprid);
        //var_dump($quantity);
        $this->notify('NOTIFIER_CART_UPDATE_QUANTITY_START', null, $uprid, $quantity, $attributes);
        if (empty($quantity)) {
            return; // nothing needs to be updated if there's no quantity, so we return.
        }
        $chk_current_qty = zen_get_products_stock($uprid);
        //var_dump($chk_current_qty);
        if (STOCK_ALLOW_CHECKOUT === 'false' && $quantity > $chk_current_qty) {
            $quantity = $chk_current_qty;
            if (!$this->flag_duplicate_msgs_set) {
                $this->messageStack->add_session('shopping_cart', ($this->display_debug_messages ? '$_GET[main_page]: ' . $_GET['main_page'] . ' FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($uprid), 'caution');
            }
        }

        $prid = zen_get_prid($uprid);
        $basketProduct = BasketProduct::where('product_id', $uprid)->where('basket_id', $this->basket->id);
        $basketProduct->update(['quantity' => $quantity]);
        $this->updateAttributes($basketProduct->first(), $attributes, $prid, $uprid);
        $this->cartID = $this->generateCartId();
        $this->notify('NOTIFIER_CART_UPDATE_QUANTITY_END');
        return true;
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
                $this->updateAttributesBasket($basketProduct, $value, $option, $attr_value);
            }
        }
    }

    protected function updateAttributesBasket($basketProduct, $value, $option, $attr_value)
    {
        if (is_array($value)) {
            foreach ($value as $opt => $val) {
                BasketAttribute::where('basket_product_id', $basketProduct->id)->where('options_id', (int)$option . '_chk . (int)$val')->update(['products_options_value_id' => $val]);
            }
        } else {
            //var_dump($basketProduct);
            $attr = BasketAttribute::where('basket_product_id', $basketProduct->id)->where('options_id', $option)->first();
            $attr->update(['options_values_id' => (int)$value, 'options_value_text' => $attr_value]);
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

        $this->setCartErrors(['type' =>'messageStack', 'level' => 'caution', 'detail' => ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($qty)]);
        return 0;
    }

    protected function buildAttributes(int $product_id, array $attributes): array
    {
        $attributes = [];
        $results = ProductAttribute::where('products_id', $product_id)->get();
        //var_dump($results);
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

    public function getCartErrors(): array
    {
        return $this->cartErrors;
    }

    public function setCartErrors(array $cartError): void
    {
        $this->cartErrors[] = $cartError;
    }

}
