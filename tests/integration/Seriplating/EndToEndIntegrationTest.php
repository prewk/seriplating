<?php

namespace Prewk\Seriplating;

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

        // Tweak
        $this->tweakRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->tweak = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->tweakRepo, [
            "id" => $t->id("tweaks"),
            "site_id" => $t->inherits("site_id", "id"),
            "tweakable_type" => $t->value(),
            "tweakable_id" => $t->inherits("id"),
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
            "site_id" => $t->inherits("site_id", "id"),
            "sectionable_type" => $t->value(),
            "sectionable_id" => $t->inherits("id"),
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
                ["id" => 3, "site_id" => 1, "name" => "The baz page", "sections" => []],
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
                            "id" => 2,
                            "site_id" => 1,
                            "alias_id" => 2,
                            "menu_id" => 1,
                            "parent_id" => 0,
                            "sort_order" => 1,
                            "menu_items" => [
                                [
                                    "id" => 3,
                                    "site_id" => 1,
                                    "alias_id" => 3,
                                    "menu_id" => 1,
                                    "parent_id" => 2,
                                    "sort_order" => 0,
                                    "menu_items" => [],
                                ],
                            ],
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
                [
                    "id" => 3,
                    "site_id" => 1,
                    "aliasable_type" => "Page",
                    "aliasable_id" => 3,
                    "alias" => "baz",
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

    private function deserialize()
    {
        // Site
        $siteCreate = [
            "name" => "Foo",
            "primary_menu_id" => 0,
            "landing_page_id" => 0,
            "font_preset" => "sans",
            "font_variables" => ["HEADING" => "sans"],
        ];
        $siteCreated = ["id" => 1] + $siteCreate;
        $siteUpdate = [
            "primary_menu_id" => 1,
            "landing_page_id" => 1,
        ];
        $siteUpdated = array_merge($siteCreated, $siteUpdate);
        $this->siteRepo->shouldReceive("create")->once()->with($siteCreate)->andReturn($siteCreated)
            ->shouldReceive("update")->once()->with($siteUpdate)->andReturn($siteUpdated);

        // Color swatch
        $colorSwatchCreate = [
            "site_id" => 1,
            "value" => "#ffff00",
        ];
        $colorSwatchCreated = ["id" => 1] + $colorSwatchCreate;
        $this->colorSwatchRepo->shouldReceive("create")->once()->with($colorSwatchCreate)->andReturn($colorSwatchCreated);

        // Tweak
        $tweakCreate = [
            "site_id" => 1,
            "tweakable_type" => "Site",
            "tweakable_id" => 1,
            "definition" => "heading_block.text_color",
            "data" => [
                "color_swatch_id" => 0,
            ],
        ];
        $tweakCreated = ["id" => 1] + $tweakCreate;
        $tweakUpdate = [
            "data" => [
                "color_swatch_id" => 1,
            ],
        ];
        $tweakUpdated = array_merge($tweakCreated, $tweakUpdate);
        $this->tweakRepo->shouldReceive("create")->once()->with($tweakCreate)->andReturn($tweakCreated)
            ->shouldReceive("update")->once()->with($tweakUpdate)->andReturn($tweakUpdated);

        // Section
        $sectionCreate = [
            "site_id" => 1,
            "sectionable_type" => "Site",
            "sectionable_id" => 1,
            "type" => "menu",
            "name" => "foo",
            "position" => "top",
            "sort_order" => 0,
            "data" => [
                "menu_id" => 0,
            ],
        ];
        $sectionCreated = ["id" => 1];
        $sectionUpdate = [
            "data" => [
                "menu_id" => 1,
            ]
        ];
        $sectionUpdated = array_merge($sectionCreated, $sectionUpdate);
        $this->sectionRepo->shouldReceive("create")->once()->with($sectionCreate)->andReturn($sectionCreated)
            ->shouldReceive("update")->once()->with($sectionUpdate)->andReturn($sectionUpdated);

        // Page
        $pageCreate = [
            "site_id" => 1,
            "name" => "The foo page",
        ];
        $pageCreated = ["id" => 1] + $pageCreate;
        $this->pageRepo->shouldReceive("create")->once()->with($pageCreate)->andReturn($pageCreated);

        // Section
        $sectionCreate = [
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
                                    ["id" => 0],
                                    ["id" => 0],
                                ],
                            ],
                            [
                                "blocks" => [
                                    ["id" => 0],
                                    ["id" => 0],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $sectionCreated = ["id" => 2] + $sectionCreate;
        $sectionUpdate = [
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
            ]
        ];
        $sectionUpdated = array_merge($sectionCreated, $sectionUpdate);
        $this->sectionRepo->shouldReceive("create")->once()->with($sectionCreate)->andReturn($sectionCreated)
            ->shouldReceive("update")->once()->with($sectionUpdate)->andReturn($sectionUpdated);

        // Block
        $blockCreate = [
            "site_id" => 1,
            "type" => "heading",
            "data" => [
                "content" => "<h1>Lorem ipsum</h1>",
            ],
        ];
        $blockCreated1 = ["id" => 1] + $blockCreate;
        $blockCreated2 = ["id" => 2] + $blockCreate;
        $blockCreated3 = ["id" => 3] + $blockCreate;
        $blockCreated4 = ["id" => 4] + $blockCreate;
        $this->blockRepo->shouldReceive("create")->with($blockCreate)->andReturn($blockCreated1)
            ->shouldReceive("create")->with($blockCreate)->andReturn($blockCreated2)
            ->shouldReceive("create")->with($blockCreate)->andReturn($blockCreated3)
            ->shouldReceive("create")->with($blockCreate)->andReturn($blockCreated4);


        return $this->hier->deserialize("sites", $this->serialize());
    }

    public function test_cms_deserialization()
    {
        $proto = $this->deserialize();
    }
}