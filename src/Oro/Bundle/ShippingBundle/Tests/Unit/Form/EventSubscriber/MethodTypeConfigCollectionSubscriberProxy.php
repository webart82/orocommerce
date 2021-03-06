<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodTypeConfigCollectionSubscriber;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Symfony\Component\Form\FormFactoryInterface;

class MethodTypeConfigCollectionSubscriberProxy extends MethodTypeConfigCollectionSubscriber implements
    ConfigSubscriberProxyInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function setFactory(FormFactoryInterface$factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMethodRegistry(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
        return $this;
    }
}
