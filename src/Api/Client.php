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

namespace BernardoSecades\Packagist\SecurityChecker\Api;

use BernardoSecades\Packagist\SecurityChecker\Exception\Packagist\PackagistConnectException;
use BernardoSecades\Packagist\SecurityChecker\ValueObject\Package;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\ResponseInterface;

/**
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class Client
{
    const DEFAULT_PACKAGIST_URL = 'https://packagist.org';

    /** @var ClientInterface  */
    protected $httpClient;

    /** @var  string */
    protected $packagistUrl;

    /**
     * @param ClientInterface|null $httpClient
     * @param string               $packagistUrl
     */
    public function __construct(ClientInterface $httpClient = null, $packagistUrl = self::DEFAULT_PACKAGIST_URL)
    {
        $this->httpClient = null !== $httpClient ? $httpClient : new HttpClient();
        $this->packagistUrl = $packagistUrl;
    }

    /**
     * @param Promise[] $packages
     * @return array
     */
    public function getMultipleRequest(array $packages)
    {
        $promises = $this->getGuzzlePromises($packages);
        $infoPackages = [];
        // Wait till all the requests are finished.
        (new EachPromise($promises, [
            'concurrency' => 10,
            'fulfilled' => function ($infoPackage) use (&$infoPackages) {
                $infoPackages[] = $infoPackage;
            },
        ]))->promise()->wait();

        return $infoPackages;
    }

    /**
     * @param string $packagistUrl
     */
    public function setPackagistUrl($packagistUrl)
    {
        $this->packagistUrl = $packagistUrl;
    }

    /**
     * @return string
     */
    public function getPackagistUrl()
    {
        return $this->packagistUrl;
    }

    /**
     * @param Package[] $packages
     * @return array              Array info packages, each element has ['packages'] data
     * @throws PackagistConnectException
     */
    protected function getGuzzlePromises(array $packages)
    {
        if (self::DEFAULT_PACKAGIST_URL !== $this->getPackagistUrl()) {
            try {
                $this->httpClient->get($this->getPackagistUrl().'/packages/list.json');
            } catch (ConnectException $exception) {
                $message = sprintf('Error connection: %s, only compatible with software https://github.com/composer/packagist', $this->getPackagistUrl());
                throw new PackagistConnectException($message, 0, $exception);
            }
        }

        $result = [];
        foreach ($packages as $package) {
            $result[] =  $this->httpClient->requestAsync('GET', $this->packagistUrl.'/p/'.$package->getName().'.json')
                ->then(function (ResponseInterface $response) {
                    return json_decode($response->getBody(), true);
                });
        }

        return $result;
    }
}
