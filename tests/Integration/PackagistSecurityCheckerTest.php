<?php

/**
 * MIT License
 *
 * Copyright (c) 2016 Bernardo Secades
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace BernardoSecades\Packagist\SecurityChecker\Tests\Unit;

use BernardoSecades\Packagist\SecurityChecker\PackagistSecurityChecker;
use BernardoSecades\Packagist\SecurityChecker\ValueObject\FilterCheck;
use BernardoSecades\Packagist\SecurityChecker\ValueObject\Package;
use BernardoSecades\Packagist\SecurityChecker\Exception\File\FileNotFoundException;

class PackagistSecurityCheckerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  PackagistSecurityChecker */
    protected $checkerDefaultClient;

    protected function setUp()
    {
        $this->checkerDefaultClient = new PackagistSecurityChecker();
    }

    public function testFailLoadComposerLock()
    {
        $this->expectException(FileNotFoundException::class);
        $this->checkerDefaultClient->check('no-exist.lock');
    }

    public function testDependenciesWithoutBugs()
    {
        $packages = $this->checkerDefaultClient->check($this->getComposerLockPathWithoutBugs(), FilterCheck::BUG);
        $this->assertCount(0, $packages);
        $this->assertFalse($this->checkerDefaultClient->hasBugs());
    }

    /**
     * @return Package[]
     */
    public function testNumberPackagesCheck()
    {
        $packages = $this->checkerDefaultClient->check($this->getComposerLockPathWithBugs());
        $this->assertCount(56, $packages);
        $this->assertTrue($this->checkerDefaultClient->hasBugs());

        return $packages;
    }

    /**
     * @return Package[]
     */
    public function testNumberPackagesCheckOnlyBugs()
    {
        $packages = $this->checkerDefaultClient->check($this->getComposerLockPathWithBugs(), FilterCheck::BUG);
        $this->assertCount(4, $packages);

        return $packages;
    }

    /**
     * @depends testNumberPackagesCheck
     * @param Package[] $packages
     */
    public function testNotFollowSemanticVersioning(array $packages)
    {
        $this->assertArrayHasKey('phenx/php-font-lib', $packages);
        $this->assertArrayHasKey('phenx/php-svg-lib', $packages);

        $packageA = $packages['phenx/php-font-lib'];
        $packageB = $packages['phenx/php-svg-lib'];
        $this->assertInstanceOf(Package::class, $packageA);
        $this->assertInstanceOf(Package::class, $packageB);

        $this->assertFalse($packageA->supportSemanticVersioning());
        $this->assertFalse($packageB->supportSemanticVersioning());
    }

    /**
     * @depends testNumberPackagesCheck
     * @param Package[] $packages
     */
    public function testPackageNoExistInPackagist(array $packages)
    {
        $this->assertArrayHasKey('doctrine/no-exist', $packages);

        $package = $packages['doctrine/no-exist'];
        $this->assertInstanceOf(Package::class, $package);

        $this->assertFalse($package->hasPackagist());
    }

    /**
     * @depends testNumberPackagesCheckOnlyBugs
     * @param Package[] $packages
     */
    public function testNamePackagesWitBugs(array $packages)
    {
        $this->assertArrayHasKey('psr/log', $packages);
        $this->assertArrayHasKey('symfony/symfony', $packages);
        $this->assertArrayHasKey('twig/twig', $packages);
        $this->assertArrayHasKey('zendframework/zend-diactoros', $packages);

        $logPackage = $packages['psr/log'];
        $this->assertInstanceOf(Package::class, $logPackage);
        $this->assertTrue($logPackage->hasBug());

        $symfonyPackage = $packages['symfony/symfony'];
        $this->assertInstanceOf(Package::class, $symfonyPackage);
        $this->assertTrue($symfonyPackage->hasBug());

        $twigPackage = $packages['twig/twig'];
        $this->assertInstanceOf(Package::class, $twigPackage);
        $this->assertTrue($twigPackage->hasBug());

        $zendPackage = $packages['zendframework/zend-diactoros'];
        $this->assertInstanceOf(Package::class, $zendPackage);
        $this->assertTrue($zendPackage->hasBug());
    }

    /**
     * @return string
     */
    protected function getComposerLockPathWithBugs()
    {
        return __DIR__.'/../fixtures/composerWithBugs.lock';
    }

    /**
     * @return string
     */
    protected function getComposerLockPathWithoutBugs()
    {
        return __DIR__.'/../fixtures/composerWithoutBugs.lock';
    }
}
