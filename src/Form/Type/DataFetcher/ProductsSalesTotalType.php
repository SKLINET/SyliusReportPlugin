<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\Form\Type\DataFetcher;

use Symfony\Component\Form\FormBuilderInterface;

class ProductsSalesTotalType extends BaseDataFetcherType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->queryFilterFormBuilder->addDateRange($builder, [
            //
        ]);
        //
        $this->queryFilterFormBuilder->addChannel($builder, [
            'multiple' => false,
            'required' => true,
            'attr'     => [
                'class'       => 'fluid search selection changeSelects',
                'data-hidden' => false,
            ],
        ]);
        // Products
        $this->queryFilterFormBuilder->addProduct($builder);
    }

    public function getBlockPrefix(): string
    {
        return 'odiseo_sylius_report_data_fetcher_products_sales_total';
    }
}
