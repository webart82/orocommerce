<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveBefore;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Remove product prices by unit on ProductUnitPrecision delete.
 */
class ProductUnitPrecisionListener
{
    /**
     * @var string
     */
    protected $productPriceClass;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var QueryHintResolverInterface
     */
    protected $hintResolver;

    /**
     * @param LifecycleEventArgs $event
     */
    public function postRemove(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if ($entity instanceof ProductUnitPrecision) {
            $product = $entity->getProduct();
            $unit = $entity->getUnit();
            // prices are already removed using cascade delete operation
            if (!$product->getId()) {
                return;
            }
            $args = ['unit' => $product, 'product' => $unit];
            $this->eventDispatcher
                ->dispatch(ProductPricesRemoveBefore::NAME, new ProductPricesRemoveBefore($args));
            
            /** @var ProductPriceRepository $repository */
            $repository = $event->getEntityManager()->getRepository($this->productPriceClass);
            $repository->deleteByProductUnit($this->hintResolver, $product, $unit);
            $this->eventDispatcher
                ->dispatch(ProductPricesRemoveAfter::NAME, new ProductPricesRemoveAfter($args));
        }
    }

    /**
     * @param string $productPriceClass
     * @return ProductUnitPrecisionListener
     */
    public function setProductPriceClass($productPriceClass)
    {
        $this->productPriceClass = $productPriceClass;

        return $this;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @return ProductUnitPrecisionListener
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     */
    public function setHintResolver(QueryHintResolverInterface $hintResolver)
    {
        $this->hintResolver = $hintResolver;
    }
}
