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

namespace BernardoSecades\Packagist\SecurityChecker;

use BernardoSecades\Packagist\SecurityChecker\Api\Client;
use BernardoSecades\Packagist\SecurityChecker\Exception\Packagist\NotFollowSemanticVersioningException;
use BernardoSecades\Packagist\SecurityChecker\Exception\File\FileNotFoundException;
use BernardoSecades\Packagist\SecurityChecker\ValueObject\Package;
use BernardoSecades\Packagist\SecurityChecker\ValueObject\FilterCheck;

/**
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class PackagistSecurityChecker
{
    /** @var  Client */
    protected $client;

    /** @var bool */
    protected $bugs = false;

    /**
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = is_null($client) ? new Client() : $client;
    }

    /**
     * @param string   $composerFileLock
     * @param int|null $filter
     * @return array Packages Loaded with data from packagist API
     */
    public function check($composerFileLock, $filter = null)
    {
        $packages = $this->getPackagesInstalled($composerFileLock);

        $this->updatePackagesFromAPI($packages);

        if (FilterCheck::BUG === $filter) {
            return array_filter($packages, [$this, 'filterOnlyBugs']);
        }

        return $packages;
    }

    /**
     * @param string $url
     */
    public function setPackagistUrl($url)
    {
        $this->client->setPackagistUrl($url);
    }

    /**
     * @return bool
     */
    public function hasBugs()
    {
        return $this->bugs;
    }

    /**
     * @param array $infoPackage
     * @param Package[] $packages
     * @return null|Package
     */
    protected function searchPackage($infoPackage, $packages)
    {
        $package = null;
        foreach ($packages as $p) {
            if (isset($infoPackage['packages'][$p->getName()][$p->getVersion()])) {
                $package = $p;
                break;
            }
        }

        return $package;
    }

    /**
     * @param Package[] $packages
     * @return Package[]            Return packages array updated from API
     */
    protected function updatePackagesFromAPI(array $packages)
    {
        $infoPackages = $this->client->getMultipleRequest($packages);

        foreach ($infoPackages as $infoPackage) {
            $package = $this->searchPackage($infoPackage, $packages);
            $package->setPackagist(true);
            // Overwrite data from packagist API
            $package->fromArray($infoPackage['packages'][$package->getName()][$package->getVersion()]);

            try {
                $versionWithNextPatchVersion = $package->getVersionWithNextPatchVersion();
                $package->setSemanticVersioning(true);
            } catch (NotFollowSemanticVersioningException $exception) {
                continue;
            }

            if (array_key_exists($versionWithNextPatchVersion, $infoPackage['packages'][$package->getName()])) {
                $this->bugs = true;
                $package->setBug(true);
            }
        }

        return $packages;
    }

    /**
     * @param string $composerLockFile
     * @return array Packages Loaded with data from composer.lock
     */
    protected function getPackagesInstalled($composerLockFile)
    {
        if (false === is_file($composerLockFile)) {
            throw new FileNotFoundException(sprintf('%s file does not exist', $composerLockFile));
        }

        $composerLockRawContent = file_get_contents($composerLockFile);
        $composerLockcontent = json_decode($composerLockRawContent, true);

        $packages = [];
        foreach ($composerLockcontent['packages'] as $infoPackage) {
            $package = new Package();
            $package->fromArray($infoPackage);
            $packages[$package->getName()] = $package;
        }

        return $packages;
    }

    /**
     * @param Package $package
     * @return bool
     */
    protected function filterOnlyBugs(Package $package)
    {
        return $package->hasBug();
    }
}
