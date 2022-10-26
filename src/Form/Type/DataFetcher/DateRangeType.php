<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\Form\Type\DataFetcher;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

final class DateRangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', DateType::class, [
                'widget' => 'single_text',
                'label' => 'odiseo_sylius_report_plugin.form.time_period.start',
                'html5' => true,
                'required' => false,
            ])
            ->add('end', DateType::class, [
                'widget' => 'single_text',
                'label' => 'odiseo_sylius_report_plugin.form.time_period.end',
                'html5' => true,
                'required' => false,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'odiseo_sylius_report_data_fetcher_date_range';
    }
}
