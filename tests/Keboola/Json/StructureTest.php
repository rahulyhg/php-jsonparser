<?php

namespace Keboola\Json;

use Keboola\Json\Test\ParserTestCase;

class StructureTest extends ParserTestCase
{
    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Node path [] does not exist.
     */
    public function testSaveNodeInvalid()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'array']);
        self::assertEquals([], $structure->getData());
    }

    public function testSaveNode()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'prop']), ['nodeType' => 'scalar']);
        self::assertEquals(
            [
                'root' => [
                    'nodeType' => 'array',
                    '[]' => [
                        'nodeType' => 'object',
                        'prop' => [
                            'nodeType' => 'scalar',
                        ],
                    ],
                ],
            ],
            $structure->getData()
        );
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Conflict property nodeType
     */
    public function testSaveNodeReserved1()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => ['object']]);
        $structure->getData();
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Array [] is not an array.
     */
    public function testSaveNodeReserved2()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', '[]']), ['nodeType' => 'scalar']);
        $structure->getData();
    }

    public function testSaveValue()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNodeValue(new NodePath(['root', '[]']), 'headerNames','my-object');
        self::assertEquals(
            [
                'root' => [
                    'nodeType' => 'array',
                    '[]' => [
                        'nodeType' => 'object',
                        'headerNames' => 'my-object'
                    ],
                ],
            ],
            $structure->getData()
        );
    }

    /**
     * @expectedException \Keboola\Json\Exception\InconsistentValueException
     * @expectedExceptionMessage Attempting to overwrite 'headerName' value 'object' with 'my-object'
     */
    public function testSaveValueConflictProp()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', 'obj']), ['headerName' => 'object']);
        $structure->saveNodeValue(new NodePath(['root', 'obj']), 'headerName','my-object');
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Unhandled nodeType change from "object" to "string" in "root.obj"
     */
    public function testSaveValueConflictTypeUpgradeFail()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', 'obj']), ['nodeType' => 'object']);
        $structure->saveNodeValue(new NodePath(['root', 'obj']), 'nodeType','string');
    }

    public function testSaveValueConflictTypeUpgradeAllowed1()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', 'obj']), ['nodeType' => 'null']);
        $structure->saveNodeValue(new NodePath(['root', 'obj']), 'nodeType','string');
        self::assertEquals(
            [
                'root' => [
                    'nodeType' => 'object',
                    'obj' => [
                        'nodeType' => 'string',
                    ],
                ],
            ],
            $structure->getData()
        );
    }

    public function testSaveValueConflictTypeUpgradeAllowed2()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', 'obj']), ['nodeType' => 'object']);
        $structure->saveNodeValue(new NodePath(['root', 'obj']), 'nodeType','null');
        self::assertEquals(
            [
                'root' => [
                    'nodeType' => 'object',
                    'obj' => [
                        'nodeType' => 'object',
                    ],
                ],
            ],
            $structure->getData()
        );
    }

    public function testSaveValueConflictTypeUpgradeArrayAllowed1()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'str']), ['nodeType' => 'scalar']);
        $structure->saveNodeValue(new NodePath(['root', '[]', 'str', '[]']), 'nodeType','scalar');
        $structure->saveNodeValue(new NodePath(['root', '[]', 'str']), 'nodeType','array');
        self::assertEquals(
            [
                'root' => [
                    'nodeType' => 'array',
                    '[]' => [
                        'nodeType' => 'object',
                        'str' => [
                            '[]' => [
                                'nodeType' => 'scalar',
                            ],
                            'nodeType' => 'array',
                        ],
                    ],
                ],
            ],
            $structure->getData()
        );
    }

    public function testSaveValueConflictTypeUpgradeArrayAllowed2()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj', '[]', 'prop']), ['nodeType' => 'scalar']);
        $structure->saveNodeValue(new NodePath(['root', '[]', 'obj']), 'nodeType','object');
        self::assertEquals(
            [
                'root' => [
                    'nodeType' => 'array',
                    '[]' => [
                        'nodeType' => 'object',
                        'obj' => [
                            '[]' => [
                                'nodeType' => 'object',
                                'prop' => [
                                    'nodeType' => 'scalar',
                                ],
                            ],
                            'nodeType' => 'array',
                        ],
                    ],
                ],
            ],
            $structure->getData()
        );
    }

    public function testSaveValueConflictTypeUpgradeArrayAllowed3()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj']), ['nodeType' => 'object', 'headerNames' => 'my-obj']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj', '[]', 'prop']), ['nodeType' => 'scalar']);
        $structure->saveNodeValue(new NodePath(['root', '[]', 'obj']), 'nodeType','array');
        self::assertEquals(
            [
                'root' => [
                    'nodeType' => 'array',
                    '[]' => [
                        'nodeType' => 'object',
                        'obj' => [
                            '[]' => [
                                'nodeType' => 'object',
                                'prop' => [
                                    'nodeType' => 'scalar',
                                ],
                                'headerNames' => 'data',
                            ],
                            'nodeType' => 'array',
                            'headerNames' => 'my-obj',
                        ],
                    ],
                ],
            ],
            $structure->getData()
        );
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Array contents are unknown
     */
    public function testSaveValueConflictTypeUpgradeArrayInvalid()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj']), ['nodeType' => 'object']);
        $structure->saveNodeValue(new NodePath(['root', '[]', 'obj']), 'nodeType','array');
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Unhandled nodeType change from "object" to "array" in "root.[].obj"
     */
    public function testSaveValueConflictTypeUpgradeArrayDisabled()
    {
        $structure = new Structure(false);
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj']), ['nodeType' => 'object']);
        $structure->saveNodeValue(new NodePath(['root', '[]', 'obj']), 'nodeType','array');
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Unhandled nodeType change from "object" to "string" in "root.[].obj"
     */
    public function testSaveValueConflictTypeUpgradeArrayNotAllowed1()
    {
        $structure = new Structure(false);
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'obj']), ['nodeType' => 'object']);
        $structure->saveNodeValue(new NodePath(['root', '[]', 'obj']), 'nodeType','string');
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Data array in 'root.[].str' contains incompatible types 'object' and 'scalar'
     */
    public function testSaveValueConflictTypeUpgradeArrayNotAllowed2()
    {
        $structure = new Structure();
        $structure->saveNode(new NodePath(['root']), ['nodeType' => 'array']);
        $structure->saveNode(new NodePath(['root', '[]']), ['nodeType' => 'object']);
        $structure->saveNode(new NodePath(['root', '[]', 'str']), ['nodeType' => 'scalar']);
        $structure->saveNodeValue(new NodePath(['root', '[]', 'str', '[]']), 'nodeType','object');
        $structure->saveNodeValue(new NodePath(['root', '[]', 'str']), 'nodeType','array');
    }

    public function testLoad()
    {
        $structure = new Structure();
        $data = [
            'root' => [
                'nodeType' => 'object',
                'obj' => [
                    'nodeType' => 'string',
                ],
            ],
        ];
        $structure->load($data);
        self::assertEquals($data, $structure->getData());
    }

    public function testGetNode()
    {
        $structure = new Structure();
        $data = [
            'root' => [
                'nodeType' => 'object',
                'obj' => [
                    'nodeType' => 'string',
                ],
            ],
        ];
        $structure->load($data);
        self::assertEquals(['nodeType' => 'string'], $structure->getNode(new NodePath(['root', 'obj'])));
    }

    public function testGetNodeInvalid()
    {
        $structure = new Structure();
        $data = [
            'root' => [
                'nodeType' => 'object',
                'obj' => [
                    'nodeType' => 'string',
                ],
            ],
        ];
        $structure->load($data);
        self::assertNull($structure->getNode(new NodePath(['root', 'non-existent'])));
    }

    public function testGetNodeProperty()
    {
        $structure = new Structure();
        $data = [
            'root' => [
                'nodeType' => 'object',
                'obj' => [
                    'nodeType' => 'string',
                ],
            ],
        ];
        $structure->load($data);
        self::assertEquals('string', $structure->getNodeProperty(new NodePath(['root', 'obj']), 'nodeType'));
    }

    public function testGetColumnTypes1()
    {
        $structure = new Structure();
        $data = [
            'root' => [
                'nodeType' => 'object',
                'obj' => [
                    'nodeType' => 'object',
                    'prop1' => [
                        'nodeType' => 'array',
                        '[]' => [
                            'nodeType' => 'scalar',
                        ]
                    ],
                    'prop2' => [
                        'nodeType' => 'scalar'
                    ],
                ],
            ],
        ];
        $structure->load($data);
        self::assertEquals(
            ['prop1' => 'array', 'prop2' => 'scalar'],
            $structure->getColumnTypes(new NodePath(['root', 'obj']))
        );
    }

    public function testGetColumnTypes2()
    {
        $structure = new Structure();
        $data = [
            'root' => [
                'nodeType' => 'object',
                'obj' => [
                    'nodeType' => 'object',
                    'prop1' => [
                        'nodeType' => 'array',
                        '[]' => [
                            'nodeType' => 'scalar',
                        ]
                    ],
                    'prop2' => [
                        'nodeType' => 'scalar'
                    ],
                ],
            ],
        ];
        $structure->load($data);
        self::assertEquals(
            ['prop2' => 'scalar'],
            $structure->getColumnTypes(new NodePath(['root', 'obj', 'prop2']))
        );
    }

    public function testGetColumnTypes3()
    {
        $structure = new Structure();
        $data = [
            'root' => [
                'nodeType' => 'object',
                'obj' => [
                    'nodeType' => 'object',
                    'prop1' => [
                        'nodeType' => 'array',
                        '[]' => [
                            'nodeType' => 'scalar'
                        ]
                    ],
                    'prop2' => [
                        'nodeType' => 'scalar'
                    ],
                ],
            ],
        ];
        $structure->load($data);
        self::assertEquals(
            [],
            $structure->getColumnTypes(new NodePath(['root', 'obj', 'prop1']))
        );
    }

    public function testHeaderNames()
    {
        $structure = new Structure();
        $data = [
            'root' => [
                'nodeType' => 'array',
                '[]' => [
                    'nodeType' => 'object',
                    'a very long name of a property of an object which exceeds the length of 60 characters' => [
                        'nodeType' => 'object'
                    ],
                    'some special characters!@##%$*&(^%$#09do' => [
                        'nodeType' => 'scalar',
                    ],
                    'prop2.something' => [
                        'nodeType' => 'scalar'
                    ],
                    'prop2_something' => [
                        'nodeType' => 'scalar'
                    ],
                    'array' => [
                        'nodeType' => 'array',
                        '[]' => [
                            'nodeType' => 'scalar',
                        ],
                    ],
                ],
            ],
        ];
        $structure->load($data);
        $structure->generateHeaderNames();
        self::assertEquals(
            [
                'root' => [
                    '[]' => [
                        'a very long name of a property of an object which exceeds the length of 60 characters' => [
                            'nodeType' => 'object',
                            'headerNames' => 'of_an_object_which_exceeds_the_length_of_60_characters',
                        ],
                        'some special characters!@##%$*&(^%$#09do' => [
                            'nodeType' => 'scalar',
                            'headerNames' => 'some_special_characters_09do'
                        ],
                        'prop2.something' => [
                            'nodeType' => 'scalar',
                            'headerNames' => 'prop2_something',
                        ],
                        'prop2_something' => [
                            'nodeType' => 'scalar',
                            'headerNames' => 'prop2_something_u0',
                        ],
                        'array' => [
                            'nodeType' => 'array',
                            'headerNames' => 'array',
                            '[]' => [
                                'nodeType' => 'scalar',
                                'headerNames' => 'data'
                            ],
                        ],
                        'nodeType' => 'object',
                        'headerNames' => 'data',
                    ],
                    'nodeType' => 'array',
                ],
            ],
            $structure->getData()
        );
    }

    public function testGetTypeFromPath()
    {
        $structure = new Structure();
        self::assertEquals('root_prop', $structure->getTypeFromNodePath(new NodePath(['root', '[]', 'prop'])));
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Undefined data type invalid-type
     */
    public function testLoadInvalid1()
    {
        $structure = new Structure();
        $structure->load([
            'root' => [
                'nodeType' => 'invalid-type',
                '[]' => [
                    'nodeType' => 'scalar',
                ],
            ],
        ]);
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Undefined property invalidProperty
     */
    public function testLoadInvalid2()
    {
        $structure = new Structure();
        $structure->load([
            'root' => [
                'nodeType' => 'array',
                'invalidProperty' => 'array',
                '[]' => [
                    'nodeType' => 'scalar',
                ],
            ],
        ]);
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Array node does not have array.
     */
    public function testLoadInvalid3()
    {
        $structure = new Structure();
        $structure->load([
            'root' => [
                'headerNames' => 'root',
                'nodeType' => 'array',
                'invalidArray' => [
                    'nodeType' => 'scalar',
                ],
            ],
        ]);
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Conflict property nodeType
     */
    public function testLoadInvalid4()
    {
        $structure = new Structure();
        $structure->load([
            'root' => [
                'nodeType' => 'array',
                '[]' => [
                    'nodeType' => 'object',
                    'prop1' => [
                        'nodeType' => 'scalar',
                        'type' => 'parent',
                    ],
                    'prop2' => [
                        'nodeType' => [
                            'invalid-node-type'
                        ]
                    ]
                ],
            ],
        ]);
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Undefined property invalid-property
     */
    public function testLoadInvalid5()
    {
        $structure = new Structure();
        $structure->load([
            'root' => [
                'nodeType' => 'array',
                '[]' => [
                    'nodeType' => 'object',
                    'prop1' => [
                        'nodeType' => 'scalar',
                        'type' => 'parent',
                    ],
                    'prop2' => [
                        'nodeType' => 'object',
                        'invalid-property' => 'fooBar',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @expectedException \Keboola\Json\Exception\JsonParserException
     * @expectedExceptionMessage Node data type is not set.
     */
    public function testLoadInvalid6()
    {
        $structure = new Structure();
        $structure->load([
            'root' => [
                '[]' => [
                    'nodeType' => 'object',
                ],
            ],
        ]);
    }
}