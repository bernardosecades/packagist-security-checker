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

namespace BernardoSecades\Packagist\SecurityChecker\Formatter;

use BernardoSecades\Packagist\SecurityChecker\ValueObject\Package;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class TextFormatter extends AbstractFormatter
{
    /**
     * @param OutputInterface $out
     * @param InputInterface  $input
     * @param Package[]       $packages
     * @return mixed
     */
    public function displayReport(OutputInterface $out, InputInterface $input, array $packages)
    {
        $out->writeln("\n<fg=black;bg=cyan>Packagist Security Checker - Report</>\n");

        $totalBugs = 0;
        $report = [];
        /** @var Package $package */
        foreach ($packages as $package) {
            if (!$package->hasPackagist()) {
                $bugColumn = '<comment>-</comment>';
            } elseif ($package->hasBug()) {
                $totalBugs++;
                $bugColumn = '<error>Yes</error>';
            } else {
                $bugColumn = '<info>No</info>';
            }
            $row = [
                $package->getName(),
                $bugColumn,
                $package->getVersion(),
                $package->hasPackagist() ? '<info>Yes</info>' : '<error>No</error>',
                $package->supportSemanticVersioning() ? '<info>Yes</info>' : '<error>No</error>',
                $package->getUrl(),
            ];
            $report[] = $row;
        }

        if ($totalBugs > 0) {
            $table = new Table($out);
            $table
                ->setHeaders(['Package', 'Bugs', 'Current Version', 'Enabled in Packagist', 'Semantic Versioning', 'Url'])
                ->setRows($report)
                ->render();
            $out->writeln(sprintf('<error>You have %d possible bugs, you should update those dependencies if affect your project</error>', $totalBugs));
            $out->writeln('<comment>Example, if your current version is X.Y.Z, you should update at least to X.Y.(MAX-PATCH-VERSION)</comment>');
        } else {
            $out->writeln('<info>Congratulations, you do not have bugs</info>');
        }
    }
}
