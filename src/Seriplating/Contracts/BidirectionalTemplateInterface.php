<?php

namespace Prewk\Seriplating\Contracts;

interface BidirectionalTemplateInterface
{
    public function getTemplate();

    public function serialize(array $toSerialize);

    public function deserialize(array $toUnserialize, array $inherited = []);
}