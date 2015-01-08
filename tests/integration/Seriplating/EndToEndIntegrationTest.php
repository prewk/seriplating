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
            "color_swatches" => $t->collectionOf()->references("color_swatches"),
            "sections" => $t->hasMany("sections"),
        ]);

        // Color swatch
        $this->colorSwatchRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->colorSwatch = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->colorSwatchRepo, [
            "id" => $t->id("color_swatches"),
            "site_id" => $t->inherits("id"),
            "value" => $t->value(),
        ]);

        // @TODO: Need conditions support for the inherits
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
        // @TODO: Add sort order support
        $this->menuItemRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->menuItem = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->menuItemRepo, [
            "id" => $t->id("menu_items"),
            "site_id" => $t->inherits("site_id"),
            "alias_id" => $t->references("aliases"),
            "menu_id" => $t->inherits("id"), // @TODO Makes it impossible to have a menu item as parent in serialization
            "parent_id" => $t->references("menu_items"),
            "sort_order" => $t->value(), // @TODO
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
        ]);

        // Section
        // @TODO: Need conditions support for the inherits
        // @TODO: Add sort order support
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
            "sort_order" => $t->value(), // @TODO
            "data" => $t->conditions("type", [
                "block" => $t->deep([
                    "/\\.blocks\\.\\d+.id$/" => $t->references("blocks"),
                    "/^resources\\.[\\d]+\\.id$/" => $t->references("resources"),
                ]),
                "menu" => [ // @TODO: Untested nesting
                    "menu_id" => $t->references("menus"),
                ],
            ], $t->value()),
            "resources" => $t->hasMany("resources"),
        ]);

        // Block
        // @TODO: Add sort order support
        $this->blockRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->block = new SeriplatingTemplate($genSerializer, $genDeserializer, $this->blockRepo, [
            "id" => $t->id("blocks"),
            "site_id" => $t->inherits("site_id"),
            "type" => $t->value(),
            "resources" => $t->hasMany("resources"),
            "sort_order" => $t->value(), // @TODO
            "data" => $t->conditions("type", [
                "image" => $t->deep([
                    "/^resources\\.[\\d]+\\.id$/" => $t->references("resources"),
                ]),
            ], $t->value()),
        ]);

        // Resource
        $this->resourceRepo = Mockery::mock("Prewk\\Seriplating\\Contracts\\RepositoryInterface");
        $this->resource = new SeriplatingTemplate($genSerializer, $preExDeserializer, $this->resourceRepo, [
            "id" => $t->id("resources"),
        ]);
    }

    public function test_cms()
    {

    }
}