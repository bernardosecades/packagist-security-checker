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

namespace BernardoSecades\Packagist\SecurityChecker\ValueObject;

use BernardoSecades\Packagist\SecurityChecker\Exception\Packagist\NotFollowSemanticVersioningException;
use Doctrine\Common\Inflector\Inflector;

/**
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class Package
{
    /** @var  string */
    protected $name;

    /** @var  string */
    protected $description;

    /** @var  string */
    protected $version;

    /** @var  string */
    protected $versionNormalized;

    /** @var array  */
    protected $source = [];

    /** @var  string */
    protected $type;

    /** @var  boolean */
    protected $packagist = false;

    /** @var  boolean */
    protected $semanticVersioning = false;

    /** @var  boolean */
    protected $bug = false;

    /**
     * @return boolean
     */
    public function hasBug()
    {
        return $this->bug;
    }

    /**
     * @return boolean
     */
    public function hasPackagist()
    {
        return $this->packagist;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getVersionNormalized()
    {
        return $this->versionNormalized;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return isset($this->source['url']) ? $this->source['url'] : '';
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data)
    {
        foreach ($data as $key => $value) {
            $property = Inflector::camelize($key);
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * @return bool
     */
    public function supportSemanticVersioning()
    {
        return 3 === count(explode('.', $this->getVersion()));
    }

    /**
     * @return string
     */
    public function getVersionWithNextPatchVersion()
    {
        return sprintf('%s.%s.%s', $this->getMajorVersion(), $this->getMinorVersion(), $this->getNextPatchVersion());
    }

    /**
     * @param bool $value
     */
    public function setSemanticVersioning($value)
    {
        $this->semanticVersioning = (bool) $value;
    }

    /**
     * @param bool $hasPackagist
     */
    public function setPackagist($hasPackagist)
    {
        $this->packagist = (bool) $hasPackagist;
    }

    /**
     * @param bool $hasBug
     */
    public function setBug($hasBug)
    {
        $this->bug = (bool) $hasBug;
    }

    /**
     * @return string
     */
    protected function getMajorVersion()
    {
        list($majorVersion, , ) = $this->getSemanticVersioning();

        return $majorVersion;
    }

    /**
     * @return string
     */
    protected function getMinorVersion()
    {
        list(, $minorVersion, ) = $this->getSemanticVersioning();

        return $minorVersion;
    }

    /**
     * @return string
     */
    protected function getPatchVersion()
    {
        list(, , $patchVersion) = $this->getSemanticVersioning();

        return $patchVersion;
    }

    /**
     * @return array Example: [major, minor, patch]
     */
    protected function getSemanticVersioning()
    {
        if (!$this->supportSemanticVersioning()) {
            throw new NotFollowSemanticVersioningException(sprintf('Package %s not follow semantic version', $this->getName()));
        }

        return explode('.', $this->getVersion());
    }

    /**
     * @return string
     */
    protected function getNextPatchVersion()
    {
        return (string) ((int) $this->getPatchVersion()+1);
    }
}
