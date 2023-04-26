<?php declare(strict_types=1);
/**
 * MIT License
 *
 * Copyright (c) 2020 8ctopus
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
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * Compile php-parser into phar
 * @note php.ini setting phar.readonly must be set to false
 * parts taken from https://github.com/8ctopus/webp8/blob/ad95489c715c9ba78ee2821799a14479c0449d62/src/Compiler.php
 */

use Symfony\Component\Finder\Finder;

require(__DIR__ .'/../vendor/autoload.php');

$filename = 'php-parser.phar';

// clean up before creating a new phar
if (file_exists($filename))
    unlink($filename);

// create phar
$phar = new \Phar($filename);

$phar->setSignatureAlgorithm(\Phar::SHA1);

// start buffering, mandatory to modify stub
$phar->startBuffering();

// add src files
$finder = new Finder();

$finder->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->name("*.y")
    ->name("*.template")
    ->name("php-parse")
    ->in(__DIR__ . "/../bin")
    ->in(__DIR__ . "/../lib")
    ->in(__DIR__ . "/../grammar");

foreach ($finder as $file)
    $phar->addFile($file->getRealPath(), getRelativeFilePath($file));

// add license
$finder = new Finder();

$finder->files()
    ->ignoreVCS(true)
    ->name('LICENSE')
    ->in(__DIR__ . "/../");

foreach ($finder as $file)
    $phar->addFile($file->getRealPath(), getRelativeFilePath($file));

// entry point
$file = 'bin/php-parse';

// create default "boot" loader
$boot_loader = $phar->createDefaultStub($file);

// add shebang to bootloader
$stub = "#!/usr/bin/env php\n";

$boot_loader = $stub . $boot_loader;

// set bootloader
$phar->setStub($boot_loader);

$phar->stopBuffering();

// compress to gzip
//$phar->compress(Phar::GZ);

echo('Create phar - OK');

/**
 * Get file relative path
 * @param  \SplFileInfo $file
 * @return string
 */
function getRelativeFilePath(SplFileInfo $file): string
{
    $realPath   = $file->getRealPath();
    $pathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    $pos          = strpos($realPath, $pathPrefix);
    $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

    return strtr($relativePath, '\\', '/');
}
