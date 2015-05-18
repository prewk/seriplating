<?php

namespace Prewk\Seriplating\Formatters;

use SeriplatingTestCase;
use SimpleXMLElement;

class XmlFormatterTest extends SeriplatingTestCase
{
    private function getSerialization()
    {
        return json_decode('{"_id":"menus_0","locale":null,"menu_items":[{"_id":"menu_items_1","parent_id":{"_ref":"menu_items_2"},"sort_order":0},{"_id":"menu_items_3","parent_id":{"_ref":"menu_items_2"},"sort_order":1},{"_id":"menu_items_4","parent_id":{"_ref":"menu_items_3"},"sort_order":5}],"data":{"a":1,"b":1.2,"c":"1","d":true,"e":false,"f":[]}}', true);
    }

    public function test_formatSerialized()
    {
        $formatter = new XmlFormatter;

        $xmlString = $formatter->formatSerialized($this->getSerialization());

        $xml = new SimpleXMLElement($xmlString);

        $this->assertEquals("null", (string)$xml->locale->attributes()["scalar"]);
        $this->assertEquals("menus_0", (string)$xml->attributes()["id"]);
        $this->assertEquals("menu_item", (string)$xml->menu_items->attributes()["array"]);
        $this->assertEquals("menu_items_2", (string)$xml->menu_items->menu_item[0]->parent_id->attributes()["ref"]);
    }

    public function test_unformatSerialized()
    {
        $formatter = new XmlFormatter;

        $xmlString = $formatter->formatSerialized($this->getSerialization());
        $serialization = $formatter->unformatSerialized($xmlString);
        
        $this->assertSame($this->getSerialization(), $serialization);
    }
}