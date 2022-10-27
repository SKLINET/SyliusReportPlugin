<?php

declare(strict_types=1);

namespace Odiseo\SyliusReportPlugin\DataFetcher;

use Doctrine\Common\Collections\ArrayCollection;
use Odiseo\SyliusReportPlugin\Filter\QueryFilterInterface;
use Odiseo\SyliusReportPlugin\Form\Type\DataFetcher\ProductsSalesTotalType;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;

/**
 *
 */
class ProductsSalesTotalDataFetcher extends BaseDataFetcher
{
    private string $orderClass;

    public function __construct(
        QueryFilterInterface $queryFilter
    ) {
        parent::__construct($queryFilter);
    }

    public function fetch(array $configuration): Data
    {
        $data = new Data();

        $channelId = $configuration['channel'];
        $qm        = $this->queryFilter->getEntityManager();

        if ( ! $channelId) {
            return $data;
        }

        /** @var ChannelInterface|null $channel */
        $channel = $this->getChannelById($channelId);
        $rawData = $this->getRawData($configuration, $channel);

        //
        $data->setData($rawData);

        // Define labels for columns
        $labels = [
            'product_name',
            'variant_name',
            'sales',
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
        /** @var \Sylius\Component\Core\Model\ProductVariantInterface[] $variants */
        $variants = $this->getProductVariants($configuration);
        $rawData  = [];

        if ( ! $channel) {
            return $rawData;
        }

        foreach ($variants as $variant) {
            $rowData = $this->getRawDataByVariant($variant, $configuration);
            //
            $rawData[] = [
                'product_name'  => $variant->getProduct()->getName(),
                'variant_name'  => $variant->getName(),
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

    private function getRawDataByVariant(ProductVariantInterface $productVariant, array $configuration): array
    {
        $queryFilter = clone $this->queryFilter;

        // QB
        $qb = clone $queryFilter->getQueryBuilder();

        // Select
        $qb
            ->select(
                'SUM(oi.total) AS amount_total'
            )
            ->from(OrderItemInterface::class, 'oi');

        // Joins
        $qb
            ->join('oi.order', 'o');

        // Filter by variant
        $qb
            ->where('oi.variant = :variant')
            ->setParameter('variant', $productVariant);

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
     * @return ProductVariantInterface[]|array
     */
    private function getProductVariants(array $configuration)
    {
        $em = $this->queryFilter->getEntityManager();

        $qb = $em->getRepository(ProductVariantInterface::class)
                 ->createQueryBuilder('pv');

        /** @var ProductVariantInterface[]|ArrayCollection $products */
        $products = $configuration['product'] ?? new ArrayCollection();

        $products = $products->map(
            function (\Sylius\Component\Core\Model\ProductInterface $product): int {
                return $product->getId();
            }
        )->toArray();

        if (count($products) > 0) {
            $qb
                ->andWhere('pv.product IN (:products)')
                ->setParameter('products', $products);
        }

        return $qb->getQuery()->getResult();
    }

    public function getType(): string
    {
        return ProductsSalesTotalType::class;
    }
}
