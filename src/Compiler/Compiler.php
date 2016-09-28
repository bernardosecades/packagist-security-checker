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

namespace BernardoSecades\Packagist\SecurityChecker\Compiler;

use Phar;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class Compiler
{
    /**
     * Compiles composer into a single phar file
     *
     * @throws RuntimeException
     */
    public function compile()
    {
        $pharFilePath = dirname(__FILE__).'/../../build/packagist-security-checker.phar';

        if (file_exists($pharFilePath)) {
            unlink($pharFilePath);
        }

        $phar = new Phar($pharFilePath, 0, 'packagist-security-checker.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $this
            ->addPHPFiles($phar)
            ->addVendorFiles($phar)
            ->addComposerVendorFiles($phar)
            ->addBin($phar)
            ->addStub($phar)
            ->addLicense($phar);

        $phar->stopBuffering();

        unset($phar);
    }

    /**
     * Add a file into the phar package
     *
     * @param Phar        $phar  Phar object
     * @param SplFileInfo $file  File to add
     * @param bool        $strip strip
     *
     * @return Compiler self Object
     */
    protected function addFile(
        Phar $phar,
        SplFileInfo $file,
        $strip = true
    ) {
        $path = strtr(str_replace(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR, '', $file->getRealPath()), '\\', '/');
        $content = file_get_contents($file);
        if ($strip) {
            $content = php_strip_whitespace($path);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n".$content."\n";
        }

        $phar->addFromString($path, $content);

        return $this;
    }

    /**
     * Add bin into Phar
     *
     * @param Phar $phar Phar
     *
     * @return Compiler self Object
     */
    protected function addBin(Phar $phar)
    {
        $content = file_get_contents(__DIR__.'/../../bin/packagist-security-checker');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/packagist-security-checker', $content);

        return $this;
    }

    protected function addStub(Phar $phar)
    {
        $stub = <<<'EOF'
#!/usr/bin/env php
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

Phar::mapPhar('packagist-security-checker.phar');

require 'phar://packagist-security-checker.phar/bin/packagist-security-checker';

__HALT_COMPILER();
EOF;
        $phar->setStub($stub);

        return $this;
    }

    /**
     * Add php files
     *
     * @param Phar $phar Phar instance
     *
     * @return Compiler self Object
     */
    private function addPHPFiles(Phar $phar)
    {
        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->in(realpath(__DIR__.'/../../src'));

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        return $this;
    }

    /**
     * Add vendor files
     *
     * @param Phar $phar Phar instance
     *
     * @return Compiler self Object
     */
    private function addVendorFiles(Phar $phar)
    {
        $vendorPath = __DIR__.'/../../vendor/';

        $requiredDependencies = [
            realpath($vendorPath.'symfony/'),
            realpath($vendorPath.'doctrine/'),
            realpath($vendorPath.'guzzlehttp'),
            realpath($vendorPath.'psr'),
        ];

        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude(['Tests', 'tests'])
            ->in($requiredDependencies);

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        return $this;
    }

    /**
     * Add composer vendor files
     *
     * @param Phar $phar Phar
     *
     * @return Compiler self Object
     */
    private function addComposerVendorFiles(Phar $phar)
    {
        $vendorPath = __DIR__.'/../../vendor/';

        $this
            ->addFile($phar, new \SplFileInfo($vendorPath.'autoload.php'))
            ->addFile($phar, new \SplFileInfo($vendorPath.'composer/autoload_namespaces.php'))
            ->addFile($phar, new \SplFileInfo($vendorPath.'composer/autoload_psr4.php'))
            ->addFile($phar, new \SplFileInfo($vendorPath.'composer/autoload_classmap.php'))
            ->addFile($phar, new \SplFileInfo($vendorPath.'composer/autoload_real.php'))
            ->addFile($phar, new \SplFileInfo($vendorPath.'composer/autoload_static.php'))
            ->addFile($phar, new \SplFileInfo($vendorPath.'composer/ClassLoader.php'));

        return $this;
    }

    /**
     * Add license
     *
     * @param Phar $phar Phar
     *
     * @return Compiler self Object
     */
    private function addLicense(Phar $phar)
    {
        $this->addFile($phar, new \SplFileInfo(__DIR__.'/../../LICENSE'), false);

        return $this;
    }
}
