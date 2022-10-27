<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\DataFetcher;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Odiseo\SyliusReportPlugin\Filter\QueryFilterInterface;
use Odiseo\SyliusReportPlugin\Form\Type\DataFetcher\ShipmentsTotalType;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;

/**
 *
 */
class ShipmentsTotalDataFetcher extends BaseDataFetcher
{

    public function __construct(
        QueryFilterInterface $queryFilter
    ) {
        parent::__construct($queryFilter);
    }

    public function fetch(array $configuration): Data
    {
        $data = new Data();

        //
        $channelId = $configuration['channel'];

        /** @var ChannelInterface|null $channel */
        $channel = $this->getChannelById($channelId);
        $rawData = $this->getRawData($configuration, $channel);

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
        //
    }

    private function getRawData(array $configuration, ?ChannelInterface $channel): array
    {
        /** @var ShippingMethodInterface[] $variants */
        $methods = $this->getShippingMethods($configuration);
        $rawData  = [];

        if ( ! $channel) {
            return $rawData;
        }

        foreach ($methods as $method) {
            $rowData = $this->getRawDataByShippingMethod($method, $configuration);
            //

            $rawData[] = [
                'shipping_method_name'  => $method->getName(),
                'amount_total'  => (int)$rowData['amount_total'],
                'currency_code' => $channel->getBaseCurrency()->getCode(),
            ];
        }

        // Order by amount total
        usort($rawData, function (array $a, array $b) {
            if ($a['amount_total'] == $b['amount_total']) {
                return 0;
            }

            return $a['amount_total'] < $b['amount_total'] ? 1 : -1;
        });

        return $rawData;
    }

    private function getRawDataByShippingMethod(ShippingMethodInterface $shippingMethod, array $configuration): array
    {
        $queryFilter = clone $this->queryFilter;

        // QB
        $qb = clone $queryFilter->getQueryBuilder();

        // Select
        $qb
            ->select(
                'SUM(adj.amount) AS amount_total'
            )
            ->from(AdjustmentInterface::class, 'adj');

        // Joins
        $qb
            ->join('adj.order', 'o')
            ->join('adj.shipment', 'shipment');

        // Filter by shipping method
        $qb
            ->where('shipment.method = :shippingMethod')
            ->setParameter('shippingMethod', $shippingMethod);

        // Filter by date
        $queryFilter->addDateRange($configuration, 'o.checkoutCompletedAt', null, $qb);

        // Filter by order state
        $qb
            ->andWhere('o.state = :orderState')
            ->setParameter('orderState', OrderInterface::STATE_FULFILLED);

        // Filter by channel
        if (isset($configuration['channel'])) {
            $qb
                ->andWhere('o.channel = :channel')
                ->setParameter('channel', $configuration['channel']);
        }

        $data = $qb->getQuery()->getArrayResult();

        return $data[0];
    }

    /**
     * @return ShippingMethodInterface[]|array
     */
    private function getShippingMethods(array $configuration)
    {
        $em = $this->queryFilter->getEntityManager();

        $qb = $em->getRepository(ShippingMethodInterface::class)
                 ->createQueryBuilder('sm');

        return $qb->getQuery()->getResult();
    }

    public function getType(): string
    {
        return ShipmentsTotalType::class;
    }
}
