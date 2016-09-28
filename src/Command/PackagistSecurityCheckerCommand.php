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

namespace BernardoSecades\Packagist\SecurityChecker\Command;

use BernardoSecades\Packagist\SecurityChecker\Formatter\JsonFormatter;
use BernardoSecades\Packagist\SecurityChecker\Formatter\TextFormatter;
use BernardoSecades\Packagist\SecurityChecker\PackagistSecurityChecker;
use BernardoSecades\Packagist\SecurityChecker\ValueObject\FilterCheck;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class PackagistSecurityCheckerCommand extends Command
{
    const TEXT_FORMAT = 'text';

    const JSON_FORMAT = 'json';

    /** @var  PackagistSecurityChecker */
    protected $checker;

    /**
     * @param PackagistSecurityChecker $packagistSecurityChecker
     */
    public function __construct(PackagistSecurityChecker $packagistSecurityChecker)
    {
        $this->checker = $packagistSecurityChecker;
        parent::__construct();
    }

    /**
     * Configure
     */
    protected function configure()
    {
        $this
            ->setName('packagist:security-check')
            ->setAliases(['security-check', 'sc'])
            ->setDescription('Analize you dependencies in composer.lock and check if there are bugs in packagist')
            ->setHelp('This command allows find bugs in your packagist dependencies.')
            ->addArgument('composer-lock-file', InputArgument::REQUIRED, 'path your composer.lock file')
            ->addOption('packagist-url', null, InputOption::VALUE_OPTIONAL, 'url of your company`s packagist')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Formats enabled: json, text', 'text')
            ->addOption('only-bugs', null, InputOption::VALUE_NONE);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (function_exists('xdebug_disable')) {
            xdebug_disable();
        }

        $packagistUrl = $input->getOption('packagist-url');
        if (null !== $packagistUrl) {
            $this->checker->setPackagistUrl($packagistUrl);
        }

        $composerLockFile = $input->getArgument('composer-lock-file');

        $filter = null;
        if ($input->getOption('only-bugs')) {
            $filter = FilterCheck::BUG;
        }

        $packages = $this->checker->check($composerLockFile, $filter);

        switch ($input->getOption('format')) {
            case self::TEXT_FORMAT:
                $formatter = new TextFormatter();
                break;
            case self::JSON_FORMAT:
                $formatter = new JsonFormatter();
                break;
            default:
                $formatter = new TextFormatter();
                break;
        }

        $formatter->displayReport($output, $input, $packages);

        if ($this->checker->hasBugs()) {
            return 1;
        }

        return 0;
    }
}
