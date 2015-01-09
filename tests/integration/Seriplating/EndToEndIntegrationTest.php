<?php

namespace Prewk\Seriplating;

use Prewk\Seriplating\Contracts\RepositoryInterface;
use Prewk\Seriplating\Special\PreExistingEntityDeserializer;
use Prewk\SeriplatingTemplate;
use SeriplatingTestCase;
use Mockery;

class EndToEndIntegrationTest extends SeriplatingTestCase
{
    private $idResolver;
    private $idFactory;
    private $hier;

    private $site;
    private $siteRepo;

    private $colorSwatch;
    private $colorSwatchRepo;

    private $tweak;
    private $tweakRepo;

    private $menu;
    private $menuRepo;

    private $menuItem;
    private $menuItemRepo;

    private $alias;
    private $aliasRepo;

    private $page;
    private $pageRepo;

    private $section;
    private $sectionRepo;

    private $block;
    private $blockRepo;

    private $resource;
    private $resourceRepo;

    public function setUp()
    {
        $t = new Seriplater(new Rule);
        $this->idResolver = new IdResolver;
        $this->idFactory = new IdFactory;
        $this->hier = new HierarchicalTemplate($this->idResolver);
        $genSerializer = new GenericSerializer($this->idFactory);
        $genDeserializer = new GenericDeserializer($this->idResolver);
        $preExDeserializer = new PreExistingEntityDeserializer($this->idResolver);

        // Site
        $this->siteRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->site = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->siteRepo, [
            "id" => $t->id("sites"),
            "name" => $t->value(),
            "primary_menu_id" => $t->references("menus"),
            "landing_page_id" => $t->references("pages"),
            "font_preset" => $t->value(),
            "font_variables" => $t->value(),
            "color_swatches" => $t->hasMany("color_swatches"),
            "sections" => $t->hasMany("sections"),
            "tweaks" => $t->hasMany("tweaks"),
            "pages" => $t->hasMany("pages"),
            "menus" => $t->hasMany("menus"),
            "aliases" => $t->hasMany("aliases"),
        ]);

        // Color swatch
        $this->colorSwatchRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->colorSwatch = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->colorSwatchRepo, [
            "id" => $t->id("color_swatches"),
            "site_id" => $t->inherits("id"),
            "value" => $t->value(),
        ]);

        $this->tweakRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->tweak = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->tweakRepo, [
            "id" => $t->id("tweaks"),
            "site_id" => $t->conditions("tweakable_type", [
                "Site" => $t->inherits("id"),
            ], $t->inherits("site_id")),
            "tweakable_type" => $t->value(),
            "tweakable_id" => $t->conditions("tweakable_type", [
                "Site" => $t->references("sites"),
                "Page" => $t->references("pages"),
                "Section" => $t->references("sections"),
                "Block" => $t->references("blocks"),
            ]),
            "definition" => $t->value(),
            "data" => $t->deep([
                "/^color_swatch_id$/" => $t->references("color_swatches"),
            ]),
        ]);

        // Menu
        $this->menuRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->menu = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->menuRepo, [
            "id" => $t->id("menus"),
            "site_id" => $t->inherits("id"),
            "locale" => $t->value(),
            "menu_items" => $t->hasMany("menu_items"),
        ]);

        // Menu item
        $this->menuItemRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->menuItem = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->menuItemRepo, [
            "id" => $t->id("menu_items"),
            "site_id" => $t->inherits("site_id"),
            "alias_id" => $t->references("aliases"),
            "menu_id" => $t->inherits("menu_id", "id"),
            "parent_id" => $t->references("menu_items", 0),
            "sort_order" => $t->increments(),
            "menu_items" => $t->hasMany("menu_items"),
        ]);

        // Alias
        $this->aliasRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->alias = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->aliasRepo, [
            "id" => $t->id("aliases"),
            "site_id" => $t->inherits("id"),
            "aliasable_type" => $t->value(),
            "aliasable_id" => $t->conditions("aliasable_type", [
                "Page" => $t->references("pages"),
                "Section" => $t->references("sections"),
                "Resource" => $t->references("resources"),
            ]),
            "alias" => $t->value(),
        ]);

        // Page
        $this->pageRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->page = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->pageRepo, [
            "id" => $t->id("pages"),
            "site_id" => $t->inherits("id"),
            "name" => $t->value(),
            "sections" => $t->hasMany("sections"),
        ]);

        // Section
        $this->sectionRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->section = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->sectionRepo, [
            "id" => $t->id("sections"),
            "site_id" => $t->conditions("sectionable_type", [
                "Site" => $t->inherits("id"),
            ], $t->inherits("site_id")),
            "sectionable_type" => $t->value(),
            "sectionable_id" => $t->conditions("sectionable_type", [
                "Page" => $t->references("pages"),
                "Site" => $t->references("sites"),
            ]),
            "type" => $t->value(),
            "name" => $t->value(),
            "position" => $t->value(),
            "sort_order" => $t->increments(),
            "data" => $t->conditions("type", [
                "block" => $t->deep([
                    "/\\.blocks\\.\\d+.id$/" => $t->references("blocks"),
                    "/^resources\\.[\\d]+\\.id$/" => $t->references("resources"),
                ]),
                "menu" => [
                    "menu_id" => $t->references("menus"),
                ],
            ], $t->value()),
            "resources" => $t->hasMany("resources"),
            "blocks" => $t->hasMany("blocks"),
        ]);

        // Block
        $this->blockRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->block = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->blockRepo, [
            "id" => $t->id("blocks"),
            "site_id" => $t->inherits("site_id"),
            "type" => $t->value(),
            "resources" => $t->hasMany("resources"),
            "data" => $t->conditions("type", [
                "image" => $t->deep([
                    "/^resources\\.[\\d]+\\.id$/" => $t->references("resources"),
                    "/^current_resource_id$/" => $t->references("resources"),
                ]),
            ], $t->value()),
        ]);

        // Resource
        $this->resourceRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->resource = new SeriplatingTemplate($genSerializer, $preExDeserializer, $this->resourceRepo, [
            "id" => $t->id("resources"),
            "site_id" => $t->inherits("site_id", "id"),
        ]);

        $this->hier
            ->register($this->site)
            ->register($this->colorSwatch)
            ->register($this->tweak)
            ->register($this->section)
            ->register($this->page)
            ->register($this->block)
            ->register($this->resource)
            ->register($this->menu)
            ->register($this->menuItem)
            ->register($this->alias);
    }

    private function serialize()
    {
        $site = [
            "id" => 1,
            "name" => "Foo",
            "primary_menu_id" => 1,
            "landing_page_id" => 1,
            "font_preset" => "sans",
            "font_variables" => ["HEADING" => "sans"],
            "color_swatches" => [
                ["id" => 1, "site_id" => 1, "value" => "#ffff00"],
            ],
            "tweaks" => [
                [
                    "id" => 1,
                    "site_id" => 1,
                    "tweakable_type" => "Site",
                    "tweakable_id" => 1,
                    "definition" => "heading_block.text_color",
                    "data" => [
                        "color_swatch_id" => 1,
                    ],
                ]
            ],
            "sections" => [
                [
                    "id" => 1,
                    "site_id" => 1,
                    "sectionable_type" => "Site",
                    "sectionable_id" => 1,
                    "type" => "menu",
                    "name" => "foo",
                    "position" => "top",
                    "sort_order" => 0,
                    "data" => [
                        "menu_id" => 1,
                    ],
                    "resources" => [],
                    "blocks" => [],
                ],
            ],
            "pages" => [
                ["id" => 1, "site_id" => 1, "name" => "The foo page", "sections" => [
                    [
                        "id" => 2,
                        "site_id" => 1,
                        "sectionable_type" => "Page",
                        "sectionable_id" => 1,
                        "type" => "block",
                        "name" => "foo",
                        "position" => "",
                        "sort_order" => 0,
                        "data" => [
                            "rows" => [
                                [
                                    "columns" => [
                                        [
                                            "blocks" => [
                                                ["id" => 1],
                                                ["id" => 2],
                                            ],
                                        ],
                                        [
                                            "blocks" => [
                                                ["id" => 3],
                                                ["id" => 4],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "resources" => [],
                        "blocks" => [
                            [
                                "id" => 1,
                                "site_id" => 1,
                                "type" => "heading",
                                "resources" => [],
                                "data" => [
                                    "content" => "<h1>Lorem ipsum</h1>",
                                ],
                            ],
                            [
                                "id" => 2,
                                "site_id" => 1,
                                "type" => "heading",
                                "resources" => [],
                                "data" => [
                                    "content" => "<h1>Lorem ipsum</h1>",
                                ],
                            ],
                            [
                                "id" => 3,
                                "site_id" => 1,
                                "type" => "heading",
                                "resources" => [],
                                "data" => [
                                    "content" => "<h1>Lorem ipsum</h1>",
                                ],
                            ],
                            [
                                "id" => 4,
                                "site_id" => 1,
                                "type" => "heading",
                                "resources" => [],
                                "data" => [
                                    "content" => "<h1>Lorem ipsum</h1>",
                                ],
                            ],
                        ]
                    ],
                ]],
                ["id" => 2, "site_id" => 1, "name" => "The bar page", "sections" => [
                    [
                        "id" => 2,
                        "site_id" => 1,
                        "sectionable_type" => "Page",
                        "sectionable_id" => 1,
                        "type" => "block",
                        "name" => "foo",
                        "position" => "",
                        "sort_order" => 0,
                        "data" => [
                            "rows" => [
                                [
                                    "columns" => [
                                        [
                                            "blocks" => [
                                                ["id" => 4],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "resources" => [],
                        "blocks" => [
                            [
                                "id" => 4,
                                "site_id" => 1,
                                "type" => "image",
                                "resources" => [
                                    ["id" => 1, "site_id" => 1],
                                ],
                                "data" => [
                                    "resources" => [
                                        ["id" => 1, "transforms" => []],
                                    ],
                                    "current_resource_id" => 1,
                                ],
                            ],
                        ]
                    ],
                ]],
            ],
            "menus" => [
                [
                    "id" => 1,
                    "site_id" => 1,
                    "locale" => "en-US",
                    "menu_items" => [
                        [
                            "id" => 1,
                            "site_id" => 1,
                            "alias_id" => 1,
                            "menu_id" => 1,
                            "parent_id" => 0,
                            "sort_order" => 0,
                            "menu_items" => [],
                        ],
                        [
                            "id" => 1,
                            "site_id" => 1,
                            "alias_id" => 2,
                            "menu_id" => 1,
                            "parent_id" => 0,
                            "sort_order" => 1,
                            "menu_items" => [],
                        ],
                    ],
                ],
            ],
            "aliases" => [
                [
                    "id" => 1,
                    "site_id" => 1,
                    "aliasable_type" => "Page",
                    "aliasable_id" => 1,
                    "alias" => "foo",
                ],
                [
                    "id" => 2,
                    "site_id" => 1,
                    "aliasable_type" => "Page",
                    "aliasable_id" => 2,
                    "alias" => "bar",
                ],
            ],
        ];

        return $this->hier->serialize("sites", $site);
    }

    public function test_cms_serialization()
    {
        $serialization = $this->serialize();

        $this->assertEquals($serialization["aliases"][0]["_id"], $serialization["menus"][0]["menu_items"][0]["alias_id"]["_ref"]);
        $this->assertEquals($serialization["tweaks"][0]["data"]["color_swatch_id"]["_ref"], $serialization["color_swatches"][0]["_id"]);
        $this->assertEquals($serialization["pages"][1]["sections"][0]["blocks"][0]["data"]["current_resource_id"]["_ref"], $serialization["pages"][1]["sections"][0]["blocks"][0]["resources"][0]["_id"]);
    }
}