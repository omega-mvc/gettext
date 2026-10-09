<?php

declare(strict_types=1);

namespace Tests\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Shared base class for the gettext suites.
 *
 * Pest.php bound every test to PHPUnit\Framework\TestCase; this class keeps a
 * single inheritance point now that the suite is plain PHPUnit.
 */
abstract class TestCase extends PHPUnitTestCase
{
}
