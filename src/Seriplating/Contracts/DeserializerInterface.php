<?php

namespace Prewk\Seriplating\Contracts;

interface DeserializerInterface
{
    public function deserialize(array $template, RepositoryInterface $repository, array $toUnserialize);
}