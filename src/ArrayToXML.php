<?php

namespace ArrayToXML;

use DOMNode;
use Exception;
use DOMDocument;
use DOMException;

class ArrayToXML
{
    protected $dom;
    protected $name_mappers;

    public function __construct($version = null, $encoding = null)
    {
        $this->dom = new DOMDocument($version, $encoding);
        $this->dom->formatOutput = true;
        $this->name_mappers = [
            'default' => function($item_name) {
                return $item_name;
            },
        ];
    }

    /**
     * @param array $input
     * @param array $options
     * @return string
     * @throws Exception
     */
    public static function toXML(array $input, $options = []): string
    {
        [$version, $encoding] = self::defaultDomValues($options);
        $mapper = new ArrayToXML($version, $encoding);
        if (!$mapper->isAssoc($input)) {
            throw new Exception('Root element must be associative');
        }
        if (isset($options['name_mappers'])) {
            foreach ($options['name_mappers'] as $name => $mapper_) {
                $mapper->setNameMapper($name, $mapper_);
            }
        }
        $mapper->map($input);
        if (isset($options['declare']) && $options['declare'] === false) {
            return $mapper->dom->saveXML($mapper->dom->documentElement);
        }
        return $mapper->dom->saveXML();
    }

    /**
     * @param array $input
     * @param array $options
     * @return DOMDocument
     * @throws Exception
     */
    public static function toDOM(array $input, $options = []): DOMDocument
    {
        [$version, $encoding] = self::defaultDomValues($options);
        $mapper = new ArrayToXML($version, $encoding);
        if (!$mapper->isAssoc($input)) {
            throw new Exception('Root element must be associative');
        }
        if (isset($options['name_mappers'])) {
            foreach ($options['name_mappers'] as $name => $mapper_) {
                $mapper->setNameMapper($name, $mapper_);
            }
        }
        $mapper->map($input);
        return $mapper->dom;
    }

    private static function defaultDomValues($options)
    {
        return [
            $options['version'] ?? '1.0',
            $options['encoding'] ?? null,
        ];
    }

    /**
     * @param array $input
     * @param DOMNode $parent
     * @return DOMNode
     */
    public function map(array $input, DOMNode $parent = null): DOMNode
    {
        $parent = $parent ?: $this->dom;

        foreach ($input as $key => $value) {
            if ($value instanceof DOMDocument) {
                $node = $this->dom->importNode($value->documentElement, true);
                $parent->appendChild($node);
            } elseif (is_array($value) && !empty($value) && $this->isAssoc($value)) {
                $element = $this->createElement($key);
                $parent->appendChild($this->map($value, $element));
            } elseif (is_array($value)) {
                list($list_name, $item_name) = $this->splitNames($key);
                $item_name_mapper = $this->name_mappers[$item_name] ?? $this->name_mappers['default'];
                $list = $parent;
                if ($list_name !== '<') {
                    $list = $this->createElement($list_name);
                }
                foreach ($value as $index => $value_) {
                    $mapped_item_name = $item_name_mapper($item_name, $index, $value_);
                    if (is_array($value_)) {
                        $element = $this->createElement($mapped_item_name);
                        $list->appendChild($this->map($value_, $element));
                    } else {
                        $list->appendChild($this->createElement($mapped_item_name, $value_));
                    }
                }
                if ($parent !== $list) {
                    $parent->appendChild($list);
                }
            } else {
                $parent->appendChild($this->createElement($key, $value));
            }
        }
        return $parent;
    }

    /**
     * @param array $array
     * @return bool
     */
    protected function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * @param string $key
     * @param [type] $value
     * @return DOMNode
     * @throws InvalidNameException
     */
    protected function createElement(string $key, $value = null): DOMNode
    {
        list($name, $attrs) = $this->extractAttributes($key);
        try {
            if (is_string($value) && strpos($value, 'cdata:') === 0) {
                $value = substr($value, strlen('cdata:'));
                $element = $this->dom->createElement($name);
                $element->appendChild(
                    $this->dom->createCDATASection($value)
                );
            } else {
                $element = $this->dom->createElement($name, $value);
            }
            if ($attrs) {
                foreach ($attrs as $key => $value) {
                    if (empty($key)) {
                        throw new InvalidNameException('Invalid attribute name', $key);
                    }
                    $element->setAttribute($key, $value);
                }
            }
            return $element;
        } catch (DOMException $e) {
            throw new InvalidNameException('Invalid name: ' . $e->getMessage(), $key, $e);
        }
    }

    /**
     * Split name into parent and child names
     *
     * Names are delimited by a pipe-sign
     *
     * @param string $key
     * @return array [parent_name, child_name]
     */
    protected function splitNames(string $key): array
    {
        $names = explode('|', $key);
        $parent_name = $names[0];
        $flatten = false;
        if (strpos($parent_name, '<') === 0) {
            $parent_name = substr($parent_name, 1);
            $flatten = true;
        }
        $children_name = count($names) > 1 ? $names[1] : null;
        if ($children_name === null) {
            $children_name = preg_replace('~s$~i', '', $parent_name);
        }
        return [
            $flatten ? '<' : $parent_name,
            $children_name
        ];
    }

    /**
     * Splits name and attributes
     *
     * Eg. name@attr1=foo@attr2=bar
     *
     * will return a tuple containing:
     *
     * ["name", ["attr1" => "foo", "attr2" => "bar"]]
     *
     * @param string $name A name optinally containing attribute syntax
     * @return array A tuple with name and attributes
     */
    protected function extractAttributes(string $name)
    {
        $parts = explode('@', $name);
        $name = array_shift($parts);
        return [
            $name,
            array_reduce($parts, function ($attrs, $part) {
                $part_parts = explode('=', $part);
                $name = array_shift($part_parts);
                $attrs[$name] = join('=', $part_parts);
                return $attrs;
            }, [])
        ];
    }

    /**
     * @return DOMDocument
     */
    public function getDocument(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * Apply a function every time a name is encountered. The mapper function
     * takes three params: $item_name, $index, $value
     *
     * @param string $name name of the name to apply function to
     * @param callable $mapper a function that returns a new name
     * @return ArrayToXML returns self for chaining
     */
    public function setNameMapper(string $name, $mapper)
    {
        $this->name_mappers[$name] = $mapper;
        return $this;
    }
}
