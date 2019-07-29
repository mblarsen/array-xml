# array-xml


[![Build status](http://img.shields.io/travis/mblarsen/array-xml.svg)](http://travis-ci.org/mblarsen/array-xml)

> Because DOMDocument and SimpleXML sucks

- Easily build XML with associative arrays
- Succinct syntax for *naming child elements*, *adding attributes*, and more
- You can combine with `DOMDocument` if you really have to

## Examples

- Easy to create attributes
- Children takes name from parent by default

```php
ArrayToXML::toXML(
    'Order@version=2.0' => [
        'ID@type=SKU' => 1234,
        'Lines' => [
            ['item' => 'ABC', 'qty' => 3],
            ['item' => 'DEF', 'qty' => 1],
        ]
    ]
);
```

Yields:

```xml
<?xml version="1.0"?>
<Order version="2.0">
  <ID type="SKU">1234</ID>
  <Lines>
    <Line>
      <item>ABC</item>
      <qty>3</qty>
    </Line>
    <Line>
      <item>DEF</item>
      <qty>1</qty>
    </Line>
  </Lines>
</Order>
```

- Using `|` you can specify a differnt name of the children

```php
ArrayToXML::toXML(
    'Order' => [
        'ID' => 1234,
        'Lines|OrderLine' => [
            ['item' => 'ABC', 'qty' => 3],
            ['item' => 'DEF', 'qty' => 1],
        ]
    ]
);
```

Yields:

```xml
<?xml version="1.0"?>
<Order>
  <ID>1234</ID>
  <Lines>
    <OrderLine>
      <item>ABC</item>
      <qty>3</qty>
    </OrderLine>
    <OrderLine>
      <item>DEF</item>
      <qty>1</qty>
    </OrderLine>
  </Lines>
</Order>
```

- Using `name_mappers` you can use, index and child values to construct the
  child element name and attributes

```php
ArrayToXML::toXML(
    'Order' => [
        'ID' => 1234,
        'Lines' => [
            ['item' => 'ABC', 'qty' => 3],
            ['item' => 'DEF', 'qty' => 1],
        ]
    ],
    [
        'name_mappers' => [
            'Line' => function ($name, $index, $value) {
                return $name . '@number=' . ($index + 1);
            }
        ]
    ]
);
```

Yields:

```xml
<?xml version="1.0"?>
<Order>
  <ID>1234</ID>
  <Lines>
    <Line number="1">
      <item>ABC</item>
      <qty>3</qty>
    </Line>
    <Line number="2">
      <item>DEF</item>
      <qty>1</qty>
    </Line>
  </Lines>
</Order>
```

- "Flatten" the parent element and put it's children in its place using `<`.

```php
ArrayToXML::toXML(
    'Order' => [
        'ID' => 1234,
        '<Lines' => [
            ['item' => 'ABC', 'qty' => 3],
            ['item' => 'DEF', 'qty' => 1],
        ]
    ],
);
```

Yields:

```xml
<?xml version="1.0"?>
<Order>
  <ID>1234</ID>
  <Line number="1">
    <item>ABC</item>
    <qty>3</qty>
  </Line>
  <Line number="2">
    <item>DEF</item>
    <qty>1</qty>
  </Line>
</Order>
```

- Some parts are hard to model using arrays so you can "patch" with a `DOMDocument`

```php
$complex_dom = ...;

ArrayToXML::toXML(
    'Order' => [
        'ID' => 1234,
        '<complexdom' => $complexdom
    ],
);
```

Yields:

```xml
<?xml version="1.0"?>
<Order>
  <ID>1234</ID>
  <Line number="1">
    <item>ABC</item>
    <qty>3</qty>
  </Line>
  <LineComment>Wrap well</LineComment>
  <Line number="2">
    <item>DEF</item>
    <qty>1</qty>
  </Line>
  <LineComment/>
</Order>
```

- Namespaces are just part of the name + an attribute on the root element.

```php
ArrayToXML::toXML(
    'Order@ns=...@ecom=...' => [
        'ns:ID' => 1234,
        '<ecom:Lines' => [
            ['ecom:item' => 'ABC', 'ecom:qty' => 3],
            ['ecom:item' => 'DEF', 'ecom:qty' => 1],
        ]
    ],
);
```

Yields:

```xml
<?xml version="1.0"?>
<ns:Order ns="..." ecom="...">
  <ns:ID>1234</ns:ID>
  <ecom:Line number="1">
    <ecom:item>ABC</ecom:item>
    <ecom:qty>3</ecom:qty>
  </ecom:Line>
  <ecom:Line number="2">
    <ecom:item>DEF</ecom:item>
    <ecom:qty>1</ecom:qty>
  </ecom:Line>
</ns:Order>
```

- In cases you need a `DOMDocument` use `toDOM`

```php
ArrayToXML::toDOM(
    'Order' => [
        'ID' => 1234,
        'Lines' => [
            ['item' => 'ABC', 'qty' => 3],
            ['item' => 'DEF', 'qty' => 1],
        ]
    ]
);
```

- lets you set the version and encoding of the XML

```php
ArrayToXML::toXML(
    'Order' => [
        'ID' => 1234,
        'Lines' => [
            ['item' => 'ABC', 'qty' => 3],
            ['item' => 'DEF', 'qty' => 1],
        ]
    ],
    [
        'version' => '1.2',
        'encoding' => 'utf8'
    ]
);
```


Yields:

```xml
<?xml version="1.2" encoding="utf8"?>
<Order>
  <ID>1234</ID>
  <Lines>
    <Line>
      <item>ABC</item>
      <qty>3</qty>
    </Line>
    <Line>
      <item>DEF</item>
      <qty>1</qty>
    </Line>
  </Lines>
</Order>
```

- Setting `declare` to false will exclude the XML declaration at the top

```php
ArrayToXML::toXML(
    'Order' => [
        'ID' => 1234,
        'Lines' => [
            ['item' => 'ABC', 'qty' => 3],
            ['item' => 'DEF', 'qty' => 1],
        ]
    ],
    [ 'declare' => false ]
);
```

Yields:

```xml
<Order>
  <ID>1234</ID>
  <Lines>
    <Line>
      <item>ABC</item>
      <qty>3</qty>
    </Line>
    <Line>
      <item>DEF</item>
      <qty>1</qty>
    </Line>
  </Lines>
</Order>
```
