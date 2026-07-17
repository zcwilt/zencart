<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField;

/**
 * Registers core's own zen_cfg_* / zen_get_* rendering functions as typed
 * ConfigFieldRegistry entries, keyed by the legacy function name they wrap so
 * that migrating a `configuration` row's `renderer` column is mechanical.
 * @since ZC v3.0.0
 */
class CoreRegistryBootstrap
{
    public static function register(ConfigFieldRegistry $registry): void
    {
        $registry->registerRenderer('zen_cfg_select_coupon_id', new Renderers\SelectCouponIdRenderer());
        $registry->registerRenderer('zen_cfg_pull_down_country_list', new Renderers\PullDownCountryListRenderer());
        $registry->registerRenderer('zen_cfg_pull_down_country_list_none', new Renderers\PullDownCountryListNoneRenderer());
        $registry->registerRenderer('zen_cfg_pull_down_zone_list', new Renderers\PullDownZoneListRenderer());
        $registry->registerRenderer('zen_cfg_pull_down_tax_classes', new Renderers\PullDownTaxClassesRenderer());
        $registry->registerRenderer('zen_cfg_textarea', new Renderers\TextareaRenderer());
        $registry->registerRenderer('zen_cfg_textarea_small', new Renderers\TextareaSmallRenderer());
        $registry->registerRenderer('zen_cfg_pull_down_htmleditors', new Renderers\PullDownHtmlEditorsRenderer());
        $registry->registerRenderer('zen_cfg_pull_down_exchange_rate_sources', new Renderers\PullDownExchangeRateSourcesRenderer());
        $registry->registerRenderer('zen_cfg_password_input', new Renderers\PasswordInputRenderer());
        $registry->registerRenderer('zen_cfg_select_option', new Renderers\SelectOptionRenderer());
        $registry->registerRenderer('zen_cfg_select_drop_down', new Renderers\SelectDropDownRenderer());
        $registry->registerRenderer('zen_cfg_pull_down_zone_classes', new Renderers\PullDownZoneClassesRenderer());
        $registry->registerRenderer('zen_cfg_pull_down_order_statuses', new Renderers\PullDownOrderStatusesRenderer());
        $registry->registerRenderer('zen_cfg_select_multioption', new Renderers\SelectMultiOptionRenderer());
        $registry->registerRenderer('zen_cfg_select_multioption_pairs', new Renderers\SelectMultiOptionPairsRenderer());
        $registry->registerRenderer('zen_cfg_read_only', new Renderers\ReadOnlyRenderer());

        $registry->registerFormatter('zen_get_country_name', new Formatters\CountryNameFormatter());
        $registry->registerFormatter('zen_cfg_get_zone_name', new Formatters\ZoneNameFormatter());
        $registry->registerFormatter('zen_get_zone_class_title', new Formatters\ZoneClassTitleFormatter());
        $registry->registerFormatter('zen_get_order_status_name', new Formatters\OrderStatusNameFormatter());
        $registry->registerFormatter('zen_get_tax_class_title', new Formatters\TaxClassTitleFormatter());
        $registry->registerFormatter('zen_get_configuration_group_value', new Formatters\ConfigurationGroupValueFormatter());
        $registry->registerFormatter('zen_cfg_password_display', new Formatters\PasswordDisplayFormatter());
        $registry->registerFormatter('currencies->format', new Formatters\CurrenciesFormatFormatter());
    }
}
