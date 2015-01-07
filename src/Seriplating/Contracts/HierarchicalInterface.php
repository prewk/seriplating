<?php

namespace Prewk\Seriplating\Contracts;

interface HierarchicalInterface
{
    public function register(BidirectionalTemplateInterface $serializer);

    public function serialize($entityName, array $unserializedTree);
}