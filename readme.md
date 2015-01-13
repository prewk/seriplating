(De)serialization templater
====================

If you don't know what this does, you probably don't need it and shouldn't use it. :)

First, an insane amount of preparations

````php
<?php
use Prewk\Seriplating\GenericDeserializer;
use Prewk\Seriplating\GenericSerializer;
use Prewk\Seriplating\IdFactory;
use Prewk\Seriplating\IdResolver;
use Prewk\Seriplating\HierarchicalTemplate;
use Prewk\SeriplatingTemplate;
use Prewk\Seriplating\Seriplater;
use Prewk\Seriplating\Rule;

// Boilerplate classes
$t = new Seriplater(new Rule);
$idFactory = new IdFactory;
$idResolver = new IdResolver;
$genericSerializer = new GenericSerializer($idFactory);
$genericDeserializer = new GenericDeserializer($idResolver);
$hierarchy = new HierarchicalTemplate($idResolver);

// Bring your own repositories, implement Prewk\Seriplating\Contracts\RepositoryInterface
$fooRepository = new FooRepository;
$barRepository = new BarRepository;
$bazRepository = new BazRepository;

// A Foo entity has two Bars and a Baz
// Corresponding database tables could be foos, bars, and bazes

// Construct the serialization templates
// ..for Foo
$fooSeriplater = new GenericSerializer($genericSerializer, $genericDeserializer, fooRepository, [
    "id" => $t->id("foos"),
    "setting" => $t->value(),
    "bars" => $t->hasMany("bars"),
]);

// ..for Bar
$barSeriplater = new GenericSerializer($genericSerializer, $genericDeserializer, barRepository, [
    "id" => $t->id("bars"),
    "sort_order" => $t->increments(),
    "baz_id" => $t->references("bazes"),
]);

// ..for Baz
$bazSeriplater = new GenericSerializer($genericSerializer, $genericDeserializer, bazRepository, [
    "id" => $t->id("bazes"),
    "content" => $t->value(),
]);

// Register them in the hierarchical templating class
$hierarchy
    ->register($fooSeriplater)
    ->register($barSeriplater)
    ->register($bazSeriplater);
````

Then, time to serialize

````php
<?php

// Entity built up from your own database content
$entityWithChildren = [
    "id" => 1,
    "setting" => "some value",
    "bars" => [
        [
            "id" => 1,
            "sort_order" => 0,
            "baz_id" => 1,
        ],
        [
            "id" => 2,
            "sort_order" => 1,
            "baz_id" => 1,
        ],
    ],
    "bazes" => [
        [
            "id" => 1,
            "content" => "<p>Lorem ipsum<p>",
        ],
    ]
];

// Serialize
$serialization = $hierarchy->serialize("foos", $entityWithChildren);
````

`$serialization` now contains a self-referential array format that can be converted to XML/whatever.

To deserialize it again:

````php
// Deserialize
$deserialized = $hierarchy->deserialize("foos", $serialization);

// The appropriate provided repositories will now have been called and entities will be created in the db
$fooId = $deserialized["id"];
````

## Available templating rules

* `->value()` A value
* `optional()->value()` An optional value
* `->id($name)` Primary key, `$name` corresponds to the entity name (recommended: db table name)
* `->references($name)` The field refers to a primary id in another entity with the name `$name`
* `->optional()->references($name)` An optional reference field
* `->references($name, $fallback)` If references entity is unresolvable, fallback to the given value (typically 0)
* `->conditions($field, [... conditions ...], $default)` Conditionally pick a rule depending on the value of the given field, with an optional fallback `$default` rule if no other cases matched.
* `->deep([ ... regexp-indexed rules ....])` A regexp-to-rule array which will run against the given field and apply the rule on matches, the rest of the field is included as-is (Like a `->value()`) with array dot notation
* `->hasMany($name)` This field is an array of child entities with their own templating rules registered in the `HierarchicalTemplate`
* `->inherits($field)` Value is inherited from the field `$field` above in the templating hierarchy
* `->inherits($field1, $field2, ...)` Fallbacks, useful if all entites in a hierarchy needs to inherit some `id` from the top, but calls it `foo_id` after the first inherit and downwards
* `->increments()` In a hierarchical deserialization, the parent deserializer will start on `0` and count upwards on every entity
* `->increments($start, $increment)` Start value (default `0`), and increment value (for instance `-1` to decrement instead)

### Conditions

````php
<?php
$template = [
    "id" => $t->id("foos"),
    "type" => $t->value(),
    "refers_to_different_things_depending_on_type" => $t->conditions("type", [
        "Bar" => $t->references("bars"),
        "Baz" => $t->references("bazes"),
        "Qux" => $t->value(),
    ]),
];
````

### Deep

````php
<?php
$entity = [
    "id" => 123,
    "data" => [
        "something" => [
            ["id" => 500, "stuff" => true], // something.0.id = 500
            ["id" => 600, "stuff" => true], // something.1.id = 600
            ["id" => 700, "stuff" => false], // something.2.id = 700
        ],
        "bar_id" => 5,
    ],
];

$template = [
    "id" => $t->id("foos"),
    "data" => $t->deep([
        "/\\.something\\.\d+.id$/" => $t->references("something"),
        "/^bar_id$/" => $t->inherits("id"),
    ])
];
````

### Increments
````php
<?php
$template1 = [
    "id" => $t->id("foos"),
    "bars" => $t->hasMany("bars"),
];
$template2 = [
    "id" => $t->id("bars"),
    "foo_id" => $t->references("foos"),
    "sort_order" => $t->increments(),
];
````

## Known issues

* Self-referential nesting is buggy and should be avoided, instead of arranging entities of the same type as a tree of themselves, keep them in a flat array with proper references
* Conditional inherits, increments, and hasMany may be buggy
* The code is totally spaghetti atm