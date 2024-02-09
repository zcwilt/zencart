<?php

namespace Zencart\Cart;

trait CartActionsTrait
{
    public function actionUpdateProduct($goto, $parameters)
    {
        global $messageStack;
        if ($this->display_debug_messages) {
            $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__, 'caution');
        }

        $change_state = [];
        $this->flag_duplicate_quantity_msgs_set = [];
        $cart_delete = (isset($_POST['cart_delete']) && is_array($_POST['cart_delete'])) ? $_POST['cart_delete'] : [];

        if (empty($_POST['products_id']) || !is_array($_POST['products_id'])) {
            $_POST['products_id'] = [];
        }
        for ($i = 0, $n = count($_POST['products_id']); $i < $n; $i++) {
            $adjust_max = 'false';
            $products_id = $_POST['products_id'][$i];
            if (empty($_POST['cart_quantity'][$i])) {
                $_POST['cart_quantity'][$i] = 0;
            }
            if (!is_numeric($_POST['cart_quantity'][$i]) || $_POST['cart_quantity'][$i] < 0) {
                // adjust quantity when not a value
                $chk_link =
                    '<a href="' . zen_href_link(zen_get_info_page($products_id), 'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($products_id))) . '&products_id=' . $products_id) . '">' .
                    zen_get_products_name($products_id) .
                    '</a>';
                $messageStack->add_session(
                    'header',
                    ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($_POST['cart_quantity'][$i]),
                    'caution'
                );

                $_POST['cart_quantity'][$i] = $this->get_quantity($products_id);
                continue;
            }
            if (in_array($products_id, $cart_delete, false) || empty($_POST['cart_quantity'][$i])) {
                $this->remove($products_id);
            } else {
                $add_max = zen_get_products_quantity_order_max($products_id); // maximum allowed
                $chk_mixed = zen_get_products_quantity_mixed($products_id); // use mixed
                $prid = zen_get_prid($products_id);

                // Adjust in cart quantities for product that have other cart
                //   product dependencies and reduction of product to allow a larger increase
                //   at each product's modification.
                //   This will maximize the maximum product quantities available.
                if ($chk_mixed === true && !array_key_exists($prid, $change_state)) {
                    $change_check = $this->in_cart_product_mixed_changed($products_id, 'decrease'); // Returns full data on products.
                    $change_state[$prid] = $change_check;
                    if (is_array($change_check) && count($change_state[$prid]['decrease']) > 0) {
                        // Verify minuses are good, and affect the items to be changed
                        //  This leaves only increases or 'netzero' to be at play.
                        foreach ($change_state[$prid]['decrease'] as $prod_id) {
                            $attributes = (!empty($_POST['id'][$prod_id]) && is_array($_POST['id'][$prod_id])) ? $_POST['id'][$prod_id] : [];
                            $this_curr_qty = $this->get_quantity($prod_id);
                            $this_new_qty = $this_curr_qty + $change_state[$prid]['changed'][$prod_id];
                            $this->add_cart($prod_id, $this_new_qty, $attributes, false);
                            if ($this->display_debug_messages) {
                                $messageStack->add_session(
                                    'header',
                                    'FUNCTION ' . __FUNCTION__ .
                                    ' Products_id: ' . $products_id .
                                    ' prod_id: ' . $prod_id .
                                    ' this_new_qty: ' . $this_new_qty .
                                    ' this_curr_qty: ' . $this_curr_qty .
                                    ' change_state[zen_get_prid(_POST[products_id][i])][changed][prod_id]: ' .
                                    $change_state[$prid]['changed'][$prod_id] .
                                    ' attributes: ' . print_r($attributes, true) .
                                    ' change_state: ' . print_r($change_state, true) .
                                    ' <br>',
                                    'caution'
                                );
                            }
                        }
                        unset($prod_num, $prod_id, $attributes, $this_curr_qty, $this_new_qty);
                    }
                }
                $cart_qty = $this->in_cart_mixed($products_id); // total currently in cart
                if ($this->display_debug_messages) {
                    $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__ . ' Products_id: ' . $products_id . ' cart_qty: ' . $cart_qty . ' <br>', 'caution');
                }
                $new_qty = $_POST['cart_quantity'][$i]; // new quantity
                $current_qty = $this->get_quantity($products_id); // how many currently in cart for attribute

                $new_qty = $this->adjust_quantity($new_qty, $products_id, 'shopping_cart');

                // -----
                // Adjust new quantity to be the same as what's in stock.
                //
                $chk_current_qty = zen_get_products_stock($products_id);
                if (STOCK_ALLOW_CHECKOUT === 'false' && $new_qty > $chk_current_qty) {
                    $new_qty = $chk_current_qty;
                    $messageStack->add_session('shopping_cart', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($products_id), 'caution');
                }

                if ($add_max == 1 && $cart_qty == 1 && $new_qty != $cart_qty) {
                    // do not add
                    $adjust_max = 'true';
                } elseif ($add_max != 0) {
                    // adjust quantity if needed
                    switch (true) {
                        case ($new_qty == $current_qty): // no change
                            $adjust_max = 'false';
                            $new_qty = $current_qty;
                            break;
                        case ($chk_mixed === false && $new_qty > $add_max):
                            $adjust_max = 'true';
                            $new_qty = $add_max;
                            break;
                        case ($chk_mixed == true && ($add_max - $cart_qty + $new_qty) >= $add_max && $new_qty > $add_max):
                            $adjust_max = 'true';
                            $requested_qty = $new_qty;
                            $alter_qty = $add_max - $cart_qty + $current_qty;
                            $new_qty = ($alter_qty > 0) ? $alter_qty : $current_qty;
                            break;
                        case ($chk_mixed === true && ($cart_qty + $new_qty - $current_qty) > $add_max):
                            $adjust_max = 'true';
                            $requested_qty = $new_qty;
                            $alter_qty = $add_max - $cart_qty + $current_qty;
                            $new_qty = ($alter_qty > 0) ? $alter_qty : $current_qty;
                            break;
                        default:
                            $adjust_max = 'false';
                            break;
                    }

                    // -----
                    // Message the customer regarding the quantity adjustment.
                    //
                    if ($adjust_max === 'true') {
                        $messageStack->add_session('shopping_cart', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($products_id), 'caution');
                    }

                    $this->add_cart($products_id, $new_qty, $_POST['id'][$products_id] ?? [], false);
                } else {
                    // adjust minimum and units
                    $this->add_cart($products_id, $new_qty, $_POST['id'][$products_id] ?? [], false);
                }

                if ($adjust_max === 'true') {
                    if ($this->display_debug_messages) {
                        $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__ . '<br>' . ERROR_MAXIMUM_QTY . zen_get_products_name($products_id) . '<br>requested_qty: ' . $requested_qty . ' current_qty: ' . $current_qty, 'caution');
                    }
                    $messageStack->add_session('shopping_cart', ERROR_MAXIMUM_QTY . zen_get_products_name($products_id), 'caution');
                } else {
                    // display message if all is good and not on shopping_cart page
                    if ($_GET['main_page'] !== FILENAME_SHOPPING_CART) {
                        if (DISPLAY_CART === 'false' && $messageStack->size('shopping_cart') === 0) {
                            $messageStack->add_session('header', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . SUCCESS_ADDED_TO_CART_PRODUCTS, 'success');
                            $this->notify('NOTIFIER_CART_OPTIONAL_SUCCESS_UPDATED_CART', $_POST, $goto, $parameters);
                        } else {
                            zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
                        }
                    }
                }
            }
        }
        zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
    }

    /**
     * Handle AddProduct cart Action
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */
    public function actionAddProduct($goto, $parameters = [])
    {
        global $db, $messageStack;
        if ($this->display_debug_messages) {
            $messageStack->add_session('header', 'A: FUNCTION ' . __FUNCTION__, 'caution');
        }

        $the_list = '';

        if (isset($_POST['products_id']) && is_numeric($_POST['products_id'])) {
            // verify attributes and quantity first
            if ($this->display_debug_messages) {
                $messageStack->add_session('header', 'A2: FUNCTION ' . __FUNCTION__, 'caution');
            }
            $adjust_max = 'false';
            if (isset($_POST['id']) && is_array($_POST['id'])) {
                // -----
                // Check to see if any of the submitted attributes are either a required (but empty) TEXT
                // field or a display-only (e.g. a "Please select") value.  In either case, the
                // customer is sent back to the product's page to provide the required input.
                //
                foreach ($_POST['id'] as $key => $value) {
                    if (zen_get_attributes_valid($_POST['products_id'], $key, $value) === false) {
                        if (str_starts_with($key, TEXT_PREFIX) === true && $value === '') {
                            $selection_text = '';
                            $value_text = ' ' . ltrim(TEXT_INVALID_USER_INPUT, ' ');
                        } else {
                            $selection_text = TEXT_INVALID_SELECTION;
                            $value_text = zen_values_name($value);
                        }
                        $the_list .=
                            TEXT_ERROR_OPTION_FOR .
                            '<span class="alertBlack">' . zen_options_name($key) . '</span>' .
                            $selection_text .
                            '<span class="alertBlack">' . $value_text . '</span>' .
                            '<br>';
                    }
                }
            }

            if (!is_numeric($_POST['cart_quantity']) || $_POST['cart_quantity'] <= 0) {
                // adjust quantity when not a value
                // If use an extra_cart_actions file to prevent processing by this function,
                //   then be sure to set $_POST['shopping_cart_zero_or_less'] to a value other than true
                //   to display success on add to cart and not display the below message.
                if (!isset($_POST['shopping_cart_zero_or_less'])) {
                    $chk_link =
                        '<a href="' . zen_href_link(zen_get_info_page($_POST['products_id']), 'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($_POST['products_id']))) . '&products_id=' . $_POST['products_id']) . '">' .
                        zen_get_products_name($_POST['products_id']) .
                        '</a>';
                    $messageStack->add_session(
                        'header',
                        ERROR_CORRECTIONS_HEADING .
                        ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART .
                        $chk_link . ' ' .
                        PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($_POST['cart_quantity']),
                        'caution'
                    );
                    $_POST['shopping_cart_zero_or_less'] = true;
                }
                $_POST['cart_quantity'] = 0;
            }

            // verify qty to add
            $add_max = zen_get_products_quantity_order_max($_POST['products_id']);
            $cart_qty = $this->in_cart_mixed($_POST['products_id']);
            if ($this->display_debug_messages) {
                $messageStack->add_session('header', 'B: FUNCTION ' . __FUNCTION__ . ' Products_id: ' . $_POST['products_id'] . ' cart_qty: ' . $cart_qty . ' $_POST[cart_quantity]: ' . $_POST['cart_quantity'] . ' <br>', 'caution');
            }
            $new_qty = $_POST['cart_quantity'];

            $new_qty = $this->adjust_quantity($new_qty, $_POST['products_id'], 'shopping_cart');

            // adjust new quantity to be no more than current in stock
            $chk_current_qty = zen_get_products_stock($_POST['products_id']);
            $this->flag_duplicate_msgs_set = false;
            if (STOCK_ALLOW_CHECKOUT === 'false' && ($cart_qty + $new_qty) > $chk_current_qty) {
                $new_qty = $chk_current_qty;
                $messageStack->add_session('shopping_cart', ($this->display_debug_messages ? 'C: FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id']), 'caution');
                $this->flag_duplicate_msgs_set = true;
            }

            if ($add_max == 1 && $cart_qty == 1) {
                // do not add
                $new_qty = 0;
                $adjust_max = 'true';
            } else {
                if (STOCK_ALLOW_CHECKOUT === 'false' && ($new_qty + $cart_qty) > $chk_current_qty) {
                    // adjust new quantity to be no more than current in stock
                    $adjust_new_qty = 'true';
                    $alter_qty = $chk_current_qty - $cart_qty;
                    $new_qty = ($alter_qty > 0) ? $alter_qty : 0;
                    if (!$this->flag_duplicate_msgs_set) {
                        $messageStack->add_session('shopping_cart', ($this->display_debug_messages ? 'D: FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id']), 'caution');
                    }
                }

                // adjust quantity if needed
                if ($add_max != 0 && ($new_qty + $cart_qty) > $add_max) {
                    $adjust_max = 'true';
                    $new_qty = $add_max - $cart_qty;
                }
            }

            if (zen_get_products_quantity_order_max($_POST['products_id']) == 1 && $this->in_cart_mixed($_POST['products_id']) == 1) {
                // do not add
            } else {
                // process normally
                // bof: set error message
                if ($the_list !== '') {
                    $messageStack->add('product_info', ERROR_CORRECTIONS_HEADING . $the_list, 'caution');
                } else {
                    // process normally
                    // iii 030813 added: File uploading: save uploaded files with unique file names
                    $real_ids = $_POST['id'] ?? [];
                    if (isset($_GET['number_of_uploads']) && $_GET['number_of_uploads'] > 0) {
                        /**
                         * Need the upload class for attribute type that allows user uploads.
                         *
                         */
                        include_once DIR_WS_CLASSES . 'upload.php';
                        for ($i = 1, $n = $_GET['number_of_uploads']; $i <= $n; $i++) {
                            $upload_prefix = UPLOAD_PREFIX . $i;
                            $text_prefix = TEXT_PREFIX . ($_POST[$upload_prefix] ?? '');
                            if (isset($_POST[$upload_prefix]) && !empty($_FILES['id']['tmp_name'][$text_prefix]) && (!isset($_POST[$upload_prefix], $_FILES['id']['tmp_name'][$text_prefix]) || $_FILES['id']['tmp_name'][$text_prefix] != 'none')) {
                                $products_options_file = new upload('id');
                                $products_options_file->set_destination(DIR_FS_UPLOADS);
                                $products_options_file->set_output_messages('session');
                                if ($products_options_file->parse($text_prefix)) {
                                    $products_image_extension = substr($products_options_file->filename, strrpos($products_options_file->filename, '.'));
                                    if (zen_is_logged_in()) {
                                        $db->Execute("INSERT INTO " . TABLE_FILES_UPLOADED . " (sesskey, customers_id, files_uploaded_name) VALUES ('" . zen_session_id() . "', " . (int)$_SESSION['customer_id'] . ", '" . zen_db_input($products_options_file->filename) . "')");
                                    } else {
                                        $db->Execute("INSERT INTO " . TABLE_FILES_UPLOADED . " (sesskey, files_uploaded_name) VALUES ('" . zen_session_id() . "', '" . zen_db_input($products_options_file->filename) . "')");
                                    }
                                    $insert_id = $db->Insert_ID();
                                    $real_ids[$text_prefix] = $insert_id . ". " . $products_options_file->filename;
                                    $products_options_file->set_filename($insert_id . $products_image_extension);
                                    if (!($products_options_file->save())) {
                                        break;
                                    }
                                } else {
                                    break;
                                }
                            } else { // No file uploaded -- use previous value
                                $real_ids[$text_prefix] = $_POST[$text_prefix] ?? '';
                                if (!zen_get_attributes_valid($_POST['products_id'], $text_prefix, !empty($_POST[$text_prefix]) ? $_POST[$text_prefix] : '')) {
                                    $the_list .=
                                        TEXT_ERROR_OPTION_FOR .
                                        '<span class="alertBlack">' .
                                        zen_options_name($_POST[$upload_prefix]) .
                                        '</span>' .
                                        TEXT_INVALID_SELECTION .
                                        '<span class="alertBlack">' .
                                        ((int)$_POST[$text_prefix] === (int)PRODUCTS_OPTIONS_VALUES_TEXT_ID) ? TEXT_INVALID_USER_INPUT : zen_values_name($value) .
                                            '</span>' .
                                            '<br>';
                                    $new_qty = 0; // Don't increase the quantity of product in the cart.
                                }
                            }
                        }

                        if ($the_list !== '') {
                            $messageStack->add('product_info', ERROR_CORRECTIONS_HEADING . $the_list, 'caution');
                        }

                        // remove helper param from URI of the upcoming redirect
                        $parameters[] = 'number_of_uploads';
                        unset($_GET['number_of_uploads']);
                    }

                    // do the actual add to cart
                    $this->add_cart($_POST['products_id'], $this->get_quantity(zen_get_uprid($_POST['products_id'], $real_ids)) + $new_qty, $real_ids);
                    // iii 030813 end of changes.
                } // eof: set error message
            } // eof: quantity maximum = 1

            if ($adjust_max == 'true') {
                $messageStack->add_session('shopping_cart', ERROR_MAXIMUM_QTY . zen_get_products_name($_POST['products_id']), 'caution');
                if ($this->display_debug_messages) {
                    $messageStack->add_session('header', 'E: FUNCTION ' . __FUNCTION__ . '<br>' . ERROR_MAXIMUM_QTY . zen_get_products_name($_POST['products_id']), 'caution');
                }
            }
        }
        if (empty($the_list)) { // no errors
            // display message if all is good and not on shopping_cart page
            if (DISPLAY_CART === 'false' && $_GET['main_page'] !== FILENAME_SHOPPING_CART && $messageStack->size('shopping_cart') === 0) {
                if (!isset($_POST['shopping_cart_zero_or_less']) || $_POST['shopping_cart_zero_or_less'] !== true) {
                    $messageStack->add_session('header', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . SUCCESS_ADDED_TO_CART_PRODUCT, 'success');
                    $this->notify('NOTIFIER_CART_OPTIONAL_SUCCESS_PRODUCT_ADDED_TO_CART', $_POST, $goto, $parameters);
                }
                zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
            } else {
                zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
            }
        } else {
            // errors found with attributes - perhaps display an additional message here, using an observer class to add to the messageStack
            $this->notify('NOTIFIER_CART_OPTIONAL_ATTRIBUTE_ERROR_MESSAGE_HOOK', $_POST, $the_list);
        }
    }

    /**
     * Handle BuyNow cart Action
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */
    public function actionBuyNow($goto, $parameters = [])
    {
        global $messageStack;
        if ($this->display_debug_messages) {
            $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__ . ' $_GET[products_id]: ' . $_GET['products_id'], 'caution');
        }

        $this->flag_duplicate_msgs_set = false;
        $allow_into_cart = 'N';
        if (isset($_GET['products_id'])) {
            if (zen_requires_attribute_selection($_GET['products_id'])) {
                zen_redirect(zen_href_link(zen_get_info_page($_GET['products_id']), 'products_id=' . $_GET['products_id']));
            }
            $allow_into_cart = zen_get_products_allow_add_to_cart((int)$_GET['products_id']);
            if ($allow_into_cart === 'Y') {
                $add_max = zen_get_products_quantity_order_max($_GET['products_id']);
                $cart_qty = $this->in_cart_mixed($_GET['products_id']);
                $new_qty = zen_get_buy_now_qty($_GET['products_id']);
                if (!is_numeric($new_qty) || $new_qty < 0) {
                    // adjust quantity when not a value
                    $chk_link =
                        '<a href="' . zen_href_link(zen_get_info_page($_GET['products_id']), 'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($_GET['products_id']))) . '&products_id=' . $_GET['products_id']) . '">' .
                        zen_get_products_name($_GET['products_id']) .
                        '</a>';
                    $messageStack->add_session('header', ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($new_qty), 'caution');
                    $new_qty = 0;
                }
                if ($add_max == 1 && $cart_qty == 1) {
                    // do not add
                    $new_qty = 0;
                } elseif ($add_max != 0 && ($new_qty + $cart_qty > $add_max)) { // adjust quantity if needed
                    $new_qty = $add_max - $cart_qty;
                }

                if ((zen_get_products_quantity_order_max($_GET['products_id']) == 1 && $this->in_cart_mixed($_GET['products_id']) == 1)) {
                    // do not add
                } else {
                    // check for min/max and add that value or 1
                    $this->add_cart($_GET['products_id'], $this->get_quantity($_GET['products_id']) + $new_qty);
                }
            }
        }

        // display message if all is good and not on shopping_cart page
        if (DISPLAY_CART === 'false') {
            if ($_GET['main_page'] !== FILENAME_SHOPPING_CART && $allow_into_cart === 'Y' && $messageStack->size('shopping_cart') === 0) {
                $messageStack->add_session('header', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . SUCCESS_ADDED_TO_CART_PRODUCTS, 'success');
                $this->notify('NOTIFIER_CART_OPTIONAL_SUCCESS_BUYNOW_ADDED_TO_CART', $_GET, $goto, $parameters);
            } elseif ($allow_into_cart !== 'Y') {
                //zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
                $messageStack->add_session('header', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . FAILED_TO_ADD_UNAVAILABLE_PRODUCTS, 'error');
            }
        }

        $exclude[] = 'action';
        zen_redirect(zen_href_link($goto, zen_get_all_get_params($exclude)));
    }

    /**
     * Handle MultipleAddProduct cart Action
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */
    public function actionMultipleAddProduct($goto, $parameters = [])
    {
        global $messageStack;
        if ($this->display_debug_messages) {
            $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__, 'caution');
        }

        $addCount = 0;
        if (!empty($_POST['products_id']) && is_array($_POST['products_id'])) {
            $products_list = $_POST['products_id'];
            foreach ($products_list as $key => $val) {
                $prodId = preg_replace('/[^0-9a-f:.]/', '', (string)$key);
                if (is_numeric($val) && $val > 0) {
                    $adjust_max = false;
                    $qty = $val;
                    $add_max = zen_get_products_quantity_order_max($prodId);
                    $cart_qty = $this->in_cart_mixed($prodId);
                    $new_qty = $this->adjust_quantity($qty, $prodId, 'shopping_cart');

                    // adjust new quantity to be no more than current in stock
                    $chk_current_qty = zen_get_products_stock($prodId);
                    if (STOCK_ALLOW_CHECKOUT === 'false' && $new_qty > $chk_current_qty) {
                        $new_qty = $chk_current_qty;
                        $messageStack->add_session('shopping_cart', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($prodId), 'caution');
                    }

                    if ($add_max == 1 && $cart_qty == 1) {
                        // do not add
                        $adjust_max = 'true';
                    } else {
                        // adjust new quantity to be no more than current in stock
                        if (STOCK_ALLOW_CHECKOUT === 'false' && ($new_qty + $cart_qty) > $chk_current_qty) {
                            $adjust_new_qty = 'true';
                            $alter_qty = $chk_current_qty - $cart_qty;
                            $new_qty = ($alter_qty > 0) ? $alter_qty : 0;
                            $messageStack->add_session('shopping_cart', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($prodId), 'caution');
                        }

                        // adjust quantity if needed
                        if ($add_max != 0 && ($new_qty + $cart_qty) > $add_max) {
                            $adjust_max = 'true';
                            $new_qty = $add_max - $cart_qty;
                        }
                        $this->add_cart($prodId, $this->get_quantity($prodId) + ($new_qty));
                        $addCount++;
                    }
                    if ($adjust_max === 'true') {
                        if ($this->display_debug_messages) {
                            $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__ . '<br>' . ERROR_MAXIMUM_QTY . zen_get_products_name($prodId), 'caution');
                        }
                        $messageStack->add_session('shopping_cart', ERROR_MAXIMUM_QTY . zen_get_products_name($prodId), 'caution');
                    }
                }
                if (!is_numeric($val) || $val < 0) {
                    // adjust quantity when not a value
                    $chk_link =
                        '<a href="' . zen_href_link(zen_get_info_page($prodId), 'cPath=' . (zen_get_generated_category_path_rev(zen_get_products_category_id($prodId))) . '&products_id=' . $prodId) . '">' .
                        zen_get_products_name($prodId) .
                        '</a>';
                    $messageStack->add_session('header', ERROR_CORRECTIONS_HEADING . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . $chk_link . ' ' . PRODUCTS_ORDER_QTY_TEXT . zen_output_string_protected($val), 'caution');
                    $val = 0;
                }
            }

            // display message if all is good and not on shopping_cart page
            if (DISPLAY_CART === 'false') {
                if ($addCount && $_GET['main_page'] !== FILENAME_SHOPPING_CART && $messageStack->size('shopping_cart') === 0) {
                    $messageStack->add_session('header', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . SUCCESS_ADDED_TO_CART_PRODUCTS, 'success');
                    $this->notify('NOTIFIER_CART_OPTIONAL_SUCCESS_MULTIPLE_ADDED_TO_CART', $products_list, $goto, $parameters);
                } else {
                    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
                }
            }
            zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
        }
    }

    /**
     * Handle Notify cart Action
     *
     * @TODO - extract externally
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */
    public function actionNotify($goto, $parameters = ['ignored'])
    {
        global $db;
        if (zen_is_logged_in() && !zen_in_guest_checkout()) {
            $notify = $_GET['products_id'] ?? $_GET['notify'] ?? $_POST['notify'] ?? null;
            if ($notify === null) {
                zen_redirect(zen_href_link($_GET['main_page'], zen_get_all_get_params(['action', 'notify', 'main_page'])));
            }

            if (!is_array($notify)) {
                $notify = [$notify];
            }
            foreach ($notify as $product_id) {
                $sql =
                    "INSERT IGNORE INTO " . TABLE_PRODUCTS_NOTIFICATIONS . "
                        (products_id, customers_id, date_added)
                     VALUES
                        (" . (int)$product_id . ", " . (int)$_SESSION['customer_id'] . ", now())";
                $db->Execute($sql);
            }
            zen_redirect(zen_href_link($_GET['main_page'], zen_get_all_get_params(['action', 'notify', 'main_page'])));
        }

        $_SESSION['navigation']->set_snapshot();
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }

    /**
     * Handle NotifyRemove cart Action
     *
     * @TODO - extract to handle externally
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */
    public function actionNotifyRemove($goto, $parameters = ['ignored'])
    {
        global $db;
        if (zen_is_logged_in() && !zen_in_guest_checkout() && isset($_GET['products_id'])) {
            $sql =
                "DELETE FROM " . TABLE_PRODUCTS_NOTIFICATIONS . "
                  WHERE products_id = " . (int)$_GET['products_id'] . "
                    AND customers_id = " . (int)$_SESSION['customer_id'];
            $db->Execute($sql, 1);
            zen_redirect(zen_href_link($_GET['main_page'], zen_get_all_get_params(['action', 'main_page'])));
        }

        $_SESSION['navigation']->set_snapshot();
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }

    /**
     * Handle CustomerOrder cart Action
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */
    public function actionCustomerOrder($goto, $parameters)
    {
        global $messageStack;
        if ($this->display_debug_messages) {
            $messageStack->add_session('header', 'FUNCTION ' . __FUNCTION__, 'caution');
        }

        if (zen_is_logged_in() && isset($_GET['pid'])) {
            if (zen_has_product_attributes($_GET['pid'])) {
                zen_redirect(zen_href_link(zen_get_info_page($_GET['pid']), 'products_id=' . $_GET['pid']));
            } else {
                $this->add_cart($_GET['pid'], $this->get_quantity($_GET['pid']) + 1);
            }
        }
        // display message if all is good and not on shopping_cart page
        if (DISPLAY_CART === 'false') {
            if ($_GET['main_page'] !== FILENAME_SHOPPING_CART && $messageStack->size('shopping_cart') === 0) {
                $messageStack->add_session('header', ($this->display_debug_messages ? 'FUNCTION ' . __FUNCTION__ . ': ' : '') . SUCCESS_ADDED_TO_CART_PRODUCTS, 'success');
            } else {
                zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
            }
        }
        zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
    }

    /**
     * Handle CartUserAction cart Action
     * This just fires any NOTIFY_CART_USER_ACTION observers.
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */
    public function actionCartUserAction($goto, $parameters)
    {
        $this->notify('NOTIFY_CART_USER_ACTION', null, $goto, $parameters);
    }

    /**
     * Handle RemoveProduct cart Action
     *
     * @param string $goto forward destination
     * @param array $parameters URL parameters to ignore
     */
    public function actionRemoveProduct($goto, $parameters)
    {
        if (!empty($_GET['product_id'])) {
            $this->remove($_GET['product_id']);
        }
        $parameters[] = 'product_id';
        zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
    }


}
