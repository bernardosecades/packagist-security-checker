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

namespace BernardoSecades\Packagist\SecurityChecker\Tests\Unit\ValueObject;

use BernardoSecades\Packagist\SecurityChecker\ValueObject\Package;
use BernardoSecades\Packagist\SecurityChecker\Exception\Packagist\NotFollowSemanticVersioningException;

class PackageTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Package */
    protected $package;

    protected function setUp()
    {
        $this->package = new Package();
    }

    public function testLoadDataFromArray()
    {
        $this->package->fromArray($this->getDataWithSemanticVersioning());

        $this->assertEquals('vendor/package', $this->package->getName());
        $this->assertEquals('description', $this->package->getDescription());
        $this->assertEquals('v1.5.7', $this->package->getVersion());
        $this->assertEquals('1.5.7.0', $this->package->getVersionNormalized());
        $this->assertEquals('https://github.com/vendor/package', $this->package->getUrl());
        $this->assertEquals('library', $this->package->getType());
        $this->assertEquals('v1.5.8', $this->package->getVersionWithNextPatchVersion());
        $this->assertFalse($this->package->hasBug());
        $this->assertFalse($this->package->hasPackagist());
        $this->assertTrue($this->package->supportSemanticVersioning());
    }

    public function testNoSemanticVersioningException()
    {
        $this->expectException(NotFollowSemanticVersioningException::class);
        $this->package->fromArray($this->getDataWithoutSemanticVersioning());
        $this->package->getVersionWithNextPatchVersion();
    }

    /**
     * @return array
     */
    protected function getDataWithSemanticVersioning()
    {
        return [
            'name' => 'vendor/package',
            'description' => 'description',
            'version' => 'v1.5.7',
            'version_normalized' => '1.5.7.0',
            'source' => [
                'type' => 'git',
                'url'  => 'https://github.com/vendor/package',
            ],
            'type' => 'library',
        ];
    }

    /**
     * @return array
     */
    protected function getDataWithoutSemanticVersioning()
    {
        return [
            'name' => 'vendor/package',
            'description' => 'description',
            'version' => 'v0.9',
            'version_normalized' => '0.9.0.0',
            'source' => [
                'type' => 'git',
                'url'  => 'https://github.com/vendor/package',
            ],
            'type' => 'library',
        ];
    }
}
