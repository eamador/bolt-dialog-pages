<?php

namespace Bolt\Extension\Eamador\BoltDialogPages\Tests;

use Bolt\Tests\BoltUnitTest;
use Bolt\Extension\Eamador\BoltDialogPages\BoltDialogPagesExtension;

/**
 * Ensure that the ExtensionName extension loads correctly.
 *
 */
class ExtensionTest extends BoltUnitTest
{
    public function testExtensionRegister()
    {
        $app = $this->getApp();
        $extension = new BoltDialogPagesExtension($app);
        $app['extensions']->register( $extension );
        $name = $extension->getName();
        $this->assertSame($name, 'BoltDialogPages');
        $this->assertSame($extension, $app["extensions.$name"]);
    }
}
