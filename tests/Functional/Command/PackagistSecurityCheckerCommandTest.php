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

namespace BernardoSecades\Packagist\SecurityChecker\Tests\Functional;

use BernardoSecades\Packagist\SecurityChecker\Command\PackagistSecurityCheckerCommand;
use BernardoSecades\Packagist\SecurityChecker\PackagistSecurityChecker;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class PackagistSecurityCheckerCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var  CommandTester */
    protected $commandTester;

    /** @var  PackagistSecurityCheckerCommand */
    protected $command;

    protected function setUp()
    {
        $app = new Application();
        $app->add(new PackagistSecurityCheckerCommand(new PackagistSecurityChecker()));

        $this->command = $app->find('packagist:security-check');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExcuteCommandWithoutBugsAndOptions()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'composer-lock-file' => $this->getComposerLockPathWithoutBugs(),
            '--format' => 'json',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertRegexp('/"package": "doctrine/', $this->commandTester->getDisplay());
    }

    public function testExcecuteCommandWithoutBugs()
    {
        $this->commandTester->execute([
                'command' => $this->command->getName(),
                'composer-lock-file' => $this->getComposerLockPathWithoutBugs(),
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExcecuteCommandWithBugs()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'composer-lock-file' => $this->getComposerLockPathWithBugs(),
        ]);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    /**
     * @return string
     */
    protected function getComposerLockPathWithBugs()
    {
        return __DIR__.'/../../fixtures/composerWithBugs.lock';
    }

    /**
     * @return string
     */
    protected function getComposerLockPathWithoutBugs()
    {
        return __DIR__.'/../../fixtures/composerWithoutBugs.lock';
    }
}
