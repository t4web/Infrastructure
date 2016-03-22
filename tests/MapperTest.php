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

    public function setUp()
    {
        $this->columnsAsAttributesMap = [
            'column' => 'attribute'
        ];

        $this->mapper = new Mapper($this->columnsAsAttributesMap);
    }

    public function testConstructor()
    {
        $this->assertAttributeEquals($this->columnsAsAttributesMap, 'columnsAsAttributesMap', $this->mapper);
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
    public function testFromTableRow($row, $expectedRow)
    {
        $result = $this->mapper->fromTableRow($row);

        $this->assertEquals($expectedRow, $result);
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
        $expectedRows = [
            ['attribute' => 'attribute value']
        ];

        $result = $this->mapper->fromTableRows($rows);

        $this->assertSame($expectedRows, $result);
    }
}
