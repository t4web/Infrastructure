<?php

namespace T4webDomainTest;

use T4webInfrastructure\Mapper;

class MapperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var array
     */
    private $columnsAsAttributesMap;

    private $factoryMock;

    public function setUp()
    {
        $this->columnsAsAttributesMap = [
            'column' => 'attribute'
        ];
        $this->factoryMock = $this->getMock('T4webDomainInterface\EntityFactoryInterface');

        $this->mapper = new Mapper($this->columnsAsAttributesMap, $this->factoryMock);
    }

    public function testConstructor()
    {
        $this->assertAttributeEquals($this->columnsAsAttributesMap, 'columnsAsAttributesMap', $this->mapper);
        $this->assertAttributeEquals($this->factoryMock, 'entityFactory', $this->mapper);
    }

    public function testToTableRow()
    {
        $entityMock = $this->getMock('T4webDomainInterface\EntityInterface');
        $entityMock->expects($this->once())
            ->method('extract')
            ->with(['attribute'])
            ->will($this->returnValue(['attribute' => 'attribute value']));

        $actualTableRow = $this->mapper->toTableRow($entityMock);

        $expectedTableRow = [
            'column' => 'attribute value'
        ];

        $this->assertEquals($expectedTableRow, $actualTableRow);
    }

    /**
     * @dataProvider providerFromTableRow
     */
    public function testFromTableRow($row, $expectedDataForFactory)
    {
        $entityMock = $this->getMock('T4webDomainInterface\EntityInterface');

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($expectedDataForFactory)
            ->will($this->returnValue($entityMock));

        $entity = $this->mapper->fromTableRow($row);

        $this->assertSame($entityMock, $entity);
    }

    public function providerFromTableRow()
    {
        return [
            [
                ['column' => 'attribute value'],
                ['attribute' => 'attribute value']
            ],
            [
                ['other_column' => 'other attribute value'],
                []
            ],
        ];
    }

    public function testFromTableRows()
    {
        $rows = [
            ['column' => 'attribute value']
        ];
        $expectedDataForFactory = [
            ['attribute' => 'attribute value']
        ];

        $entityMock = $this->getMock('T4webDomainInterface\EntityInterface');

        $this->factoryMock->expects($this->once())
            ->method('createCollection')
            ->with($expectedDataForFactory)
            ->will($this->returnValue($entityMock));

        $entity = $this->mapper->fromTableRows($rows);

        $this->assertSame($entityMock, $entity);
    }
}
