<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Integration\Test\Unit\Model\ResourceModel\Integration;

use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Integration\Model\ResourceModel\Integration\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Integration\Model\ResourceModel\Integration\Collection
 */
class CollectionTest extends TestCase
{
    /**
     * @var Select|MockObject
     */
    protected $select;

    /**
     * @var Collection|MockObject
     */
    protected $collection;

    protected function setUp(): void
    {
        $this->select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('select')
            ->willReturn($this->select);

        $resource = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->setMethods(['__wakeup', 'getConnection'])
            ->getMockForAbstractClass();
        $resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $objectManagerHelper = new ObjectManager($this);
        $arguments = $objectManagerHelper->getConstructArguments(
            Collection::class,
            ['resource' => $resource]
        );

        $this->collection = $this->getMockBuilder(
            Collection::class
        )->setConstructorArgs($arguments)
            ->setMethods(['addFilter', '_translateCondition', 'getMainTable'])
            ->getMock();
    }

    public function testAddUnsecureUrlsFilter()
    {
        $this->collection->expects($this->at(0))
            ->method('_translateCondition')
            ->with('endpoint', ['like' => 'http:%'])
            ->willReturn('endpoint like \'http:%\'');

        $this->collection->expects($this->at(1))
            ->method('_translateCondition')
            ->with('identity_link_url', ['like' => 'http:%'])
            ->willReturn('identity_link_url like \'http:%\'');

        $this->select->expects($this->once())
            ->method('where')
            ->with(
                '(endpoint like \'http:%\') OR (identity_link_url like \'http:%\')',
                null,
                Select::TYPE_CONDITION
            );

        $this->collection->addUnsecureUrlsFilter();
    }
}
