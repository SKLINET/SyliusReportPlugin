<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\DataFetcher;

use Doctrine\ORM\Query\Expr\Join;
use Odiseo\SyliusReportPlugin\Filter\QueryFilterInterface;
use Odiseo\SyliusReportPlugin\Form\Type\DataFetcher\ShipmentsTotalType;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;

/**
 *
 */
class ShipmentsTotalDataFetcher extends BaseDataFetcher
{
    private string $orderClass;

    public function __construct(
        QueryFilterInterface $queryFilter,
        string $orderClass
    ) {
        parent::__construct($queryFilter);

        $this->orderClass = $orderClass;
    }

    public function fetch(array $configuration): Data
    {
        $data = new Data();

        //
        $rawData   = $this->getData($configuration);
        $channelId = $configuration['channel'];
        $qm        = $this->queryFilter->getEntityManager();

        if ( ! $channelId) {
            return $data;
        }

        if ([] === $rawData) {
            return $data;
        }

        /** @var ChannelInterface|null $channel */
        $channel = $qm->getRepository(ChannelInterface::class)
                      ->find((int)$channelId);

        // Define additional data that has not been fetch with Query
        foreach ($rawData as $key => $row) {
            $row['currency_code'] = $channel->getBaseCurrency()->getCode();
            //
            $rawData[$key] = $row;
        }

        //
        $data->setData($rawData);

        // Define labels for columns
        $labels = [
            'shipment_method_name',
            'total_costs',
            'currency_code',
        ];
        //
        $data->setLabels($labels);

        return $data;
    }


    protected function setupQueryFilter(array $configuration = []): void
    {
        if ( ! isset($configuration['channel'])) {
            $configuration['channel'] = 1;
        }

        $qb = $this->queryFilter->getQueryBuilder();

        $qb
            ->select(
                'smTranslations.name AS name',
                'SUM(a.amount) AS total_cost'
            )
            ->from(ShippingMethodInterface::class, 'sm')
            ->groupBy('sm.id');

        $qb
            ->join(ShipmentInterface::class, 's', \Doctrine\ORM\Query\Expr\Join::WITH, 's.method = sm')
            ->join(AdjustmentInterface::class, 'a', Join::WITH, 'a.shipment = s AND a.type = :adjustmentShipping')
            ->join(OrderInterface::class, 'o', Join::WITH, 'o = s.order')
            ->leftJoin('sm.translations', 'smTranslations');

        $qb
            ->setParameter('adjustmentShipping', AdjustmentInterface::SHIPPING_ADJUSTMENT);

        // Filter by date
        $this->queryFilter->addDateRange($configuration, 'o.checkoutCompletedAt');

        // Filter by state
        $qb
            ->andWhere('o.state = :orderState')
            ->setParameter('orderState', OrderInterface::STATE_FULFILLED);

        // Filter by channel
        if (isset($configuration['channel'])) {
            $qb
                ->andWhere('o.channel = :channel')
                ->setParameter('channel', $configuration['channel']);
        }

    }

    public function getType(): string
    {
        return ShipmentsTotalType::class;
    }
}
