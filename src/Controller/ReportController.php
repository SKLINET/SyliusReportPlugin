<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\Controller;

use Odiseo\SyliusReportPlugin\DataFetcher\Data;
use Odiseo\SyliusReportPlugin\DataFetcher\DelegatingDataFetcherInterface;
use Odiseo\SyliusReportPlugin\Entity\ReportInterface;
use Odiseo\SyliusReportPlugin\Form\Type\ReportDataFetcherConfigurationType;
use Odiseo\SyliusReportPlugin\Renderer\DelegatingRendererInterface;
use Odiseo\SyliusReportPlugin\Response\CsvResponse;
use Sylius\Bundle\MoneyBundle\Formatter\MoneyFormatterInterface;
use Sylius\Bundle\MoneyBundle\Templating\Helper\FormatMoneyHelperInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Mateusz Zalewski <mateusz.zalewski@lakion.com>
 * @author Łukasz Chruściel <lukasz.chrusciel@lakion.com>
 * @author Fernando Caraballo Ortiz <caraballo.ortiz@gmail.com>
 * @author Diego D'amico <diego@odiseo.com.ar>
 */
class ReportController extends ResourceController
{
    public function renderAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);

        /** @var ReportInterface $report */
        $report = $this->findOr404($configuration);
        $reportDataFetcherConfigurationForm = $this->getReportDataFetcherConfigurationForm($report, $request);
        $report = $reportDataFetcherConfigurationForm->getData();

        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $report);

        if ($configuration->isHtmlRequest()) {
            return $this->render($configuration->getTemplate(ResourceActions::SHOW . '.html'), [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resource' => $report,
                'form' => $reportDataFetcherConfigurationForm->createView(),
                $this->metadata->getName() => $report,
            ]);
        }

        return $this->createRestView($configuration, $report);
    }

    public function exportAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);
        /** @var TranslatorInterface $translator */
        $translator = $this->container->get('translator');

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);

        /** @var ReportInterface $report */
        $report                             = $this->findOr404($configuration);
        $reportDataFetcherConfigurationForm = $this->getReportDataFetcherConfigurationForm($report, $request);
        $report                             = $reportDataFetcherConfigurationForm->getData();

        $dataFetcherConfiguration = $report->getDataFetcherConfiguration();

        $data = $this->getReportDataFetcher()->fetch($report, $dataFetcherConfiguration);

        // Format values
        $formattedData = [];

        foreach ($data->getData() as $key => $row) {
            $formattedData[] = $this->formatRow($row);
        }

        $data->setData($formattedData);

        // Translate labels
        $transLabels = [];

        foreach ($data->getLabels() as $key => $label) {
            $transLabels[] = $translator->trans('odiseo_sylius_report_plugin.ui.table.'.$label);
        }

        $data->setLabels($transLabels);

        // Slugify filename
        $filename = $this->slugify($report->getName());

        $format = $request->query->get('_format');
        switch ($format) {
            case 'json':
                $response = $this->createJsonResponse($filename, $data);
                break;
            case 'csv':
            default:
                $response = $this->createCsvResponse($filename, $data);
                break;
        }

        return $response;
    }

    public function embedAction(ReportInterface $report, array $dataFetcherConfiguration = []): Response
    {
        $data = $this->getReportDataFetcher()->fetch($report, $dataFetcherConfiguration);

        return new Response($this->getReportRenderer()->render($report, $data));
    }

    private function getReportRenderer(): DelegatingRendererInterface
    {
        return $this->container->get('odiseo_sylius_report_plugin.renderer');
    }

    private function getReportDataFetcher(): DelegatingDataFetcherInterface
    {
        return $this->container->get('odiseo_sylius_report_plugin.data_fetcher');
    }

    protected function createJsonResponse(string $filename, Data $data): Response
    {
        $responseData = [];
        foreach ($data->getData() as $key => $value) {
            $responseData[] = [$key, $value];
        }

        $response = new JsonResponse($responseData);

        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename . '.json'));

        if (!$response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', 'text/json');
        }

        return $response;
    }

    protected function createCsvResponse(string $filename, Data $data): Response
    {
        $response = new CsvResponse($data);

        $response->setFilename($filename . '.csv');

        return $response;
    }

    protected function getReportDataFetcherConfigurationForm(ReportInterface $report, Request $request): FormInterface
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = $this->container->get('form.factory');

        $configurationForm = $formFactory->create(ReportDataFetcherConfigurationType::class, $report);

        if ($request->query->has($configurationForm->getName())) {
            $configurationForm->handleRequest($request);
        }

        return $configurationForm;
    }

    private function slugify(string $string): string
    {
        /** @var string $string */
        $string = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);

        return strtolower(trim($string));
    }

    private function formatRow(array $row): array
    {
        /** @var MoneyFormatterInterface $formatMoneyHelper */
        $formatMoneyHelper = $this->container->get('sylius.money_formatter');
        /** @var TranslatorInterface $translator */
        $translator = $this->container->get('translator');
        /** @var string $localeCode */
        $localeCode = $translator->getLocale();
        /** @var string|null $currencyCode */
        $currencyCode = $row['currency_code'] ?? null;

        foreach ($row as $key => $value) {
            switch ($key) {
                case 'total_cost':
                    if($currencyCode) {
                        $value = $formatMoneyHelper->format((int) $value, $currencyCode, $localeCode);
                    }

                    break;
            }

            //
            $row[$key] = $value;
        }

        return $row;
    }
}
