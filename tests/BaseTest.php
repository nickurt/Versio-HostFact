<?php

namespace nickurt\VersioHostFact\Tests;

require __DIR__.'/../versio/versio.php';

use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    protected $versio;

    public function setUp()
    {
        $this->versio = \Mockery::mock(\Versio::class)->makePartial();
    }

    public function tearDown()
    {
        \Mockery::close();
    }
}