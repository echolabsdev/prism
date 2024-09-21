<?php

namespace Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestcase;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;
}
