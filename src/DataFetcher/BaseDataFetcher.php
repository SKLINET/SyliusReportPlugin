<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\DataFetcher;

use Odiseo\SyliusReportPlugin\Filter\QueryFilterInterface;

/**
 * @author Odiseo Team <team@odiseo.com.ar>
 */
abstract class BaseDataFetcher implements DataFetcherInterface
{
    protected QueryFilterInterface $queryFilter;

    public function __construct(QueryFilterInterface $queryFilter)
    {
        $this->queryFilter = $queryFilter;
    }

    /**
     * Responsible for setup and add filters to the base QueryFilter's Query builder
     */
    abstract protected function setupQueryFilter(array $configuration = []): void;

    /**
     * Responsible for providing raw data to fetch, from the configuration (ie: start date, end date, time period,
     * empty records flag, interval, period format, presentation format, group by).
     */
    protected function getData(array $configuration = []): array
    {
        $this->setupQueryFilter($configuration);

        return $this->queryFilter->getQueryBuilder()->getQuery()->getResult();
    }
}
