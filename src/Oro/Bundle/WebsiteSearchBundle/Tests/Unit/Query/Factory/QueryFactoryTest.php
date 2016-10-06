<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Query\Factory\QueryFactory;

class QueryFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryFactory;

    /** @var EngineV2Interface|\PHPUnit_Framework_MockObject_MockBuilder */
    protected $engine;


    public function setUp()
    {
        $this->queryFactory    = $this->getMock(QueryFactoryInterface::class);
        $this->engine          = $this->getMock(EngineV2Interface::class);
    }

    public function testCreate()
    {
        $configForWebsiteSearch = [
            'search_index' => 'website',
            'query' => [
                'select' => [
                    'text.sku'
                ],
                'from' => [
                    'product'
                ]
            ]
        ];

        $configForBackendSearch = [
            'search_index' => null
        ];

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->with($configForBackendSearch);

        $factory = new QueryFactory($this->queryFactory, $this->engine);

        $factory->create($configForBackendSearch);

        $result = $factory->create($configForWebsiteSearch);

        $this->assertInstanceOf(WebsiteSearchQuery::class, $result);
    }
}