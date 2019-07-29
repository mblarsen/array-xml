<?php
declare(strict_types=1);

use ArrayToXML\ArrayToXML;
use ArrayToXML\InvalidNameException;
use PHPUnit\Framework\TestCase;

final class ArrayToXMLTest extends TestCase
{
    public function testCanCreateSimpleXML(): void
    {
        $xml = ArrayToXML::toXML(['root' => null]);
        $expected = <<<DOCUMENT
        <?xml version="1.0"?>
        <root/>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanSetVersion(): void
    {
        $xml = ArrayToXML::toXML(
            ['root' => null],
            ['version' => '1.2']
        );
        $expected = <<<DOCUMENT
        <?xml version="1.2"?>
        <root/>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanSetEncoding(): void
    {
        $xml = ArrayToXML::toXML(
            ['root' => null],
            ['version' => '1.2', 'encoding' => 'utf8']
        );
        $expected = <<<DOCUMENT
        <?xml version="1.2" encoding="utf8"?>
        <root/>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanSkipDeclaration(): void
    {
        $xml = ArrayToXML::toXML(
            ['root' => null],
            ['declare' => false]
        );
        $expected = <<<DOCUMENT
        <root/>
        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanNameChildren(): void
    {
        $xml = ArrayToXML::toXML([
            'root' => [
                'elements' => [
                    ['id' => 1]
                ]
            ]
        ]);
        $expected = <<<DOCUMENT
        <?xml version="1.0"?>
        <root>
          <elements>
            <element>
              <id>1</id>
            </element>
          </elements>
        </root>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanWrapCDATA(): void
    {
        $xml = ArrayToXML::toXML([
            'root' => [
                'element' => ['id' => 1, 'foo' => 'cdata:bar'],
            ]
        ]);
        $expected = <<<DOCUMENT
        <?xml version="1.0"?>
        <root>
          <element>
            <id>1</id>
            <foo><![CDATA[bar]]></foo>
          </element>
        </root>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanNameChildrenExplicitly(): void
    {
        $xml = ArrayToXML::toXML([
            'root' => [
                'elements|thing' => [
                    ['id' => 1]
                ]
            ]
        ]);
        $expected = <<<DOCUMENT
        <?xml version="1.0"?>
        <root>
          <elements>
            <thing>
              <id>1</id>
            </thing>
          </elements>
        </root>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanNameChildrenUsingMapper(): void
    {
        $mapper = function ($name, $index, $value) {
            return $name . $value['id'];
        };
        $xml = ArrayToXML::toXML(
            [
                'root' => [
                    'elements' => [
                        ['id' => 'One']
                    ]
                ]
            ],
            [
                'name_mappers' => [ 'element' => $mapper ]
            ]
        );
        $expected = <<<DOCUMENT
        <?xml version="1.0"?>
        <root>
          <elements>
            <elementOne>
              <id>One</id>
            </elementOne>
          </elements>
        </root>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanNameChildrenUsingMapperAbusive(): void
    {
        $xml = ArrayToXML::toXML(
            [
                'root' => [
                    '<elements' => [
                        ['id' => 'One'],
                        'Drive safely',
                        ['id' => 'Two'],
                        'Add extra flugel',
                    ]
                ]
            ],
            [
                'name_mappers' => [
                    'element' => function ($name, $index, $value) {
                        if (is_string($value)) {
                            return 'LineComment';
                        }
                        return $name . $value['id'];
                    }
                ]
            ]
        );
        $expected = <<<DOCUMENT
        <?xml version="1.0"?>
        <root>
          <elementOne>
            <id>One</id>
          </elementOne>
          <LineComment>Drive safely</LineComment>
          <elementTwo>
            <id>Two</id>
          </elementTwo>
          <LineComment>Add extra flugel</LineComment>
        </root>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testCanFlattenParent(): void
    {
        $xml = ArrayToXML::toXML([
            'root' => [
                '<thing' => [
                    ['id' => 1],
                    ['id' => 2]
                ]
            ]
        ]);
        $expected = <<<DOCUMENT
        <?xml version="1.0"?>
        <root>
          <thing>
            <id>1</id>
          </thing>
          <thing>
            <id>2</id>
          </thing>
        </root>

        DOCUMENT;
        $this->assertEquals($expected, $xml);
    }

    public function testInvalidName(): void
    {
        try {
            $xml = ArrayToXML::toXML([
                '>root' => null
            ]);
            $this->expectException(InvalidNameException::class);
        } catch (InvalidNameException $e) {
            $this->assertEquals('>root', $e->getName());
        }

        try {
            $xml = ArrayToXML::toXML([
                'root@' => null
            ]);
            $this->expectException(InvalidNameException::class);
        } catch (InvalidNameException $e) {
            $this->assertEquals('', $e->getName());
        }

        try {
            $xml = ArrayToXML::toXML([
                'root@>' => null
            ]);
            $this->expectException(InvalidNameException::class);
        } catch (InvalidNameException $e) {
            $this->assertEquals('>', $e->getName());
        }
    }
}
