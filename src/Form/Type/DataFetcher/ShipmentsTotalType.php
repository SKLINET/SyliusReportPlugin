<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\Form\Type\DataFetcher;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Łukasz Chruściel <lukasz.chrusciel@lakion.com>
 * @author Diego D'amico <diego@odiseo.com.ar>
 */
class ShipmentsTotalType extends BaseDataFetcherType
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
    }

    public function getBlockPrefix(): string
    {
        return 'odiseo_sylius_report_data_fetcher_shipments_total';
    }
}
