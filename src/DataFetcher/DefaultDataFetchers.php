<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\DataFetcher;

/**
 * Default data fetchers.
 *
 * @author Mateusz Zalewski <mateusz.zalewski@lakion.com>
 * @author Diego D'amico <diego@odiseo.com.ar>
 */
final class DefaultDataFetchers
{
    /**
     * Number of shipments costs total
     */
    public const SHIPMENTS_TOTAL = 'odiseo_sylius_report_plugin_data_fetcher_shipments_total';

    /**
     * Number of products sales data fetcher
     */
    public const PRODUCTS_SALES_TOTAL = 'odiseo_sylius_report_plugin_data_fetcher_products_sales_total';

    /**
     * User registrations data fetcher
     */
    public const USER_REGISTRATION = 'odiseo_sylius_report_plugin_data_fetcher_user_registration';

    /**
     * Sales total data fetcher
     */
    public const SALES_TOTAL = 'odiseo_sylius_report_plugin_data_fetcher_sales_total';

    /**
     * Number of orders data fetcher
     */
    public const NUMBER_OF_ORDERS = 'odiseo_sylius_report_plugin_data_fetcher_number_of_orders';
}
