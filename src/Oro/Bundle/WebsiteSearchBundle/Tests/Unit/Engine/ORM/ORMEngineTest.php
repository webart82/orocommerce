<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\ORM;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\ORMEngine;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolver;

class ORMEngineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var QueryPlaceholderResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $queryPlaceholderResolver;

    /**
     * @var WebsiteSearchIndexRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexRepository;

    /**
     * @var OroEntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var ObjectMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mapper;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var BaseDriver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $driver;

    /**
     * @var array
     */
    private $repositorySearchResults = [];

    protected function setUp()
    {
        $this->query = new Query();
        $this->query->from('*');

        $this->queryPlaceholderResolver = $this->getMockBuilder(QueryPlaceholderResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexRepository = $this->getMockBuilder(WebsiteSearchIndexRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(OroEntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mapper = $this->getMockBuilder(ObjectMapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $this->driver = $this->getMockBuilder(BaseDriver::class)->getMock();

        $this->repositorySearchResults = [
            [
                'item' => [
                    'id' => 1,
                    'entity' => 'Oro\Bundle\ProductBundle\Entity\Product',
                    'alias' => 'orob2b_product_website_1',
                    'recordId' => 1,
                    'title' => '0RT28',
                    'changed' => false,
                    'createdAt' => new \DateTimeImmutable(),
                    'updatedAt' => new \DateTimeImmutable(),
                ],
            ],
            [
                'item' => [
                    'id' => 2,
                    'entity' => 'Oro\Bundle\ProductBundle\Entity\Product',
                    'alias' => 'orob2b_product_website_1',
                    'recordId' => 2,
                    'title' => '1AB92',
                    'changed' => false,
                    'createdAt' => new \DateTimeImmutable(),
                    'updatedAt' => new \DateTimeImmutable(),
                ],
            ],
            [
                'item' => [
                    'id' => 3,
                    'entity' => 'Oro\Bundle\ProductBundle\Entity\Product',
                    'alias' => 'orob2b_product_website_1',
                    'recordId' => 3,
                    'title' => '1GB82',
                    'changed' => false,
                    'createdAt' => new \DateTimeImmutable(),
                    'updatedAt' => new \DateTimeImmutable(),
                ],
            ],
        ];
    }

    protected function tearDown()
    {
        unset(
            $this->query,
            $this->queryPlaceholderResolver,
            $this->indexRepository,
            $this->entityManager,
            $this->mapper,
            $this->registry,
            $this->driver,
            $this->repositorySearchResults
        );
    }

    /**
     * @return ORMEngine
     */
    private function getORMEngine()
    {
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher */
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $engine = new ORMEngine($eventDispatcher, $this->queryPlaceholderResolver);

        return $engine;
    }

    public function testSearch()
    {
        $this->queryPlaceholderResolver->expects($this->once())->method('replace')->willReturn($this->query);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroWebsiteSearchBundle:Item')
            ->willReturn($this->indexRepository);

        $this->registry->expects($this->exactly(4))
            ->method('getManagerForClass')
            ->withConsecutive(
                ['OroWebsiteSearchBundle:Item'],
                ['Oro\Bundle\ProductBundle\Entity\Product'],
                ['Oro\Bundle\ProductBundle\Entity\Product'],
                ['Oro\Bundle\ProductBundle\Entity\Product']
            )
            ->willReturn($this->entityManager);

        $this->mapper->expects($this->exactly(3))->method('mapSelectedData')->willReturn([]);
        $this->mapper->expects($this->exactly(3))->method('getEntityConfig')->willReturn([]);

        $this->indexRepository->expects($this->once())->method('search')->willReturn($this->repositorySearchResults);

        $engine = $this->getORMEngine();
        $engine->setRegistry($this->registry);
        $engine->setDrivers([$this->driver]);
        $engine->setMapper($this->mapper);

        $result = $engine->search($this->query, []);

        $expectedResult = new Result(
            $this->query,
            [
                new Item(
                    $this->entityManager,
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    1,
                    '0RT28',
                    null,
                    [],
                    []
                ),
                new Item(
                    $this->entityManager,
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    2,
                    '1AB92',
                    null,
                    [],
                    []
                ),
                new Item(
                    $this->entityManager,
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    3,
                    '1GB82',
                    null,
                    [],
                    []
                ),
            ],
            3
        );

        $this->assertEquals($expectedResult, $result);
    }

    public function testSearchDriversNotSet()
    {
        $this->queryPlaceholderResolver->expects($this->once())->method('replace')->willReturn($this->query);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroWebsiteSearchBundle:Item')
            ->willReturn($this->indexRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroWebsiteSearchBundle:Item')
            ->willReturn($this->entityManager);

        $this->mapper->expects($this->never())->method('mapSelectedData')->willReturn([]);
        $this->mapper->expects($this->never())->method('getEntityConfig')->willReturn([]);

        $this->indexRepository->expects($this->never())->method('search');

        $engine = $this->getORMEngine();
        $engine->setRegistry($this->registry);
        $engine->setMapper($this->mapper);

        $this->setExpectedException(\RuntimeException::class, 'The required parameter was not set');
        $engine->search($this->query, []);
    }

    public function testSearchMapperNotSet()
    {
        $this->queryPlaceholderResolver->expects($this->once())->method('replace')->willReturn($this->query);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroWebsiteSearchBundle:Item')
            ->willReturn($this->indexRepository);

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive(
                ['OroWebsiteSearchBundle:Item'],
                ['Oro\Bundle\ProductBundle\Entity\Product']
            )
            ->willReturn($this->entityManager);

        $this->mapper->expects($this->never())->method('mapSelectedData')->willReturn([]);
        $this->mapper->expects($this->never())->method('getEntityConfig')->willReturn([]);

        $this->indexRepository->expects($this->once())->method('search')->willReturn($this->repositorySearchResults);

        $engine = $this->getORMEngine();
        $engine->setRegistry($this->registry);
        $engine->setDrivers([$this->driver]);

        $this->setExpectedException(\RuntimeException::class, 'The required parameter was not set');
        $engine->search($this->query, []);
    }

    public function testSearchRegistryNotSet()
    {
        $this->queryPlaceholderResolver->expects($this->once())->method('replace')->willReturn($this->query);

        $this->entityManager->expects($this->never())
            ->method('getRepository');

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->mapper->expects($this->never())->method('mapSelectedData')->willReturn([]);
        $this->mapper->expects($this->never())->method('getEntityConfig')->willReturn([]);

        $this->indexRepository->expects($this->never())->method('search');

        $engine = $this->getORMEngine();
        $engine->setDrivers([$this->driver]);
        $engine->setMapper($this->mapper);

        $this->setExpectedException(\RuntimeException::class, 'The required parameter was not set');
        $engine->search($this->query, []);
    }

    public function testSearchReuseManagerAndRepository()
    {
        $this->queryPlaceholderResolver->expects($this->exactly(2))->method('replace')->willReturn($this->query);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroWebsiteSearchBundle:Item')
            ->willReturn($this->indexRepository);

        $this->registry->expects($this->exactly(7))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->mapper->expects($this->exactly(6))->method('mapSelectedData')->willReturn([]);
        $this->mapper->expects($this->exactly(6))->method('getEntityConfig')->willReturn([]);

        $this->indexRepository->expects($this->exactly(2))
            ->method('search')
            ->willReturn($this->repositorySearchResults);

        $engine = $this->getORMEngine();
        $engine->setRegistry($this->registry);
        $engine->setDrivers([$this->driver]);
        $engine->setMapper($this->mapper);

        $engine->search($this->query, []);
        $engine->search($this->query, []);
    }

    public function testSetDrivers()
    {
        $engine = $this->getORMEngine();

        $this->assertAttributeEquals([], 'drivers', $engine);

        $engine->setDrivers(['driver1', 'driver2']);
        $this->assertAttributeEquals(['driver1', 'driver2'], 'drivers', $engine);
    }

    public function testSetMapper()
    {
        $engine = $this->getORMEngine();

        $this->assertAttributeEquals(null, 'mapper', $engine);

        $engine->setMapper($this->mapper);
        $this->assertAttributeEquals($this->mapper, 'mapper', $engine);
    }

    public function testSetRegistry()
    {
        $engine = $this->getORMEngine();

        $this->assertAttributeEquals(null, 'registry', $engine);

        $engine->setRegistry($this->registry);
        $this->assertAttributeEquals($this->registry, 'registry', $engine);
    }
}