<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Path;
use RuntimeException;

final class PathTest extends TestCase {
    public function testIsAbsoluteForPosix(): void {
        $this->assertTrue(Path::isAbsolute('/a/b'));
        $this->assertTrue(Path::isAbsolute('/'));
    }

    public function testIsAbsoluteForWindows(): void {
        $this->assertTrue(Path::isAbsolute('C:\\Users'));
        $this->assertTrue(Path::isAbsolute('c:/users'));
        $this->assertTrue(Path::isAbsolute('C:'));
        $this->assertTrue(Path::isAbsolute('\\\\server\\share'));
    }

    public function testIsAbsoluteForRelative(): void {
        $this->assertFalse(Path::isAbsolute(''));
        $this->assertFalse(Path::isAbsolute('a/b'));
        $this->assertFalse(Path::isAbsolute('./foo'));
        $this->assertFalse(Path::isAbsolute('../foo'));
        $this->assertFalse(Path::isAbsolute('1:/notletter'));
    }

    public function testJoinConcatenatesSegments(): void {
        $this->assertSame('a/b/c', Path::join('a', 'b', 'c'));
        $this->assertSame('a/b/c', Path::join('a/', '/b/', '/c'));
        $this->assertSame('/a/b', Path::join('/a', 'b'));
    }

    public function testJoinTreatsLeadingSlashAsSeparator(): void {
        $this->assertSame('/a/x/y', Path::join('/a', '/x', 'y'));
    }

    public function testJoinIgnoresEmpty(): void {
        $this->assertSame('a/b', Path::join('a', '', 'b'));
        $this->assertSame('', Path::join('', '', ''));
    }

    public function testJoinNormalisesBackslashes(): void {
        $this->assertSame('a/b/c', Path::join('a\\b', 'c'));
    }

    public function testNormalizeCollapsesSlashes(): void {
        $this->assertSame('/a/b/c', Path::normalize('//a///b//c'));
    }

    public function testNormalizeResolvesDotSegments(): void {
        $this->assertSame('/a/c', Path::normalize('/a/./b/../c'));
        $this->assertSame('a/c', Path::normalize('a/./b/../c'));
    }

    public function testNormalizeStopsAtRootForAbsolute(): void {
        $this->assertSame('/', Path::normalize('/../../..'));
    }

    public function testNormalizeKeepsLeadingDotDotForRelative(): void {
        $this->assertSame('../../x', Path::normalize('../../x'));
        $this->assertSame('..', Path::normalize('a/../..'));
    }

    public function testNormalizeReturnsDotForEmptyRelative(): void {
        $this->assertSame('.', Path::normalize('.'));
        $this->assertSame('.', Path::normalize('a/..'));
    }

    public function testNormalizeConvertsBackslashes(): void {
        $this->assertSame('/a/b', Path::normalize('\\a\\b'));
    }

    public function testNormalizePreservesWindowsDrive(): void {
        $this->assertSame('C:/Users/me', Path::normalize('C:\\Users\\me'));
        $this->assertSame('C:/Users/me', Path::normalize('c:/Users/./me'));
        $this->assertSame('C:/', Path::normalize('C:/../..'));
    }

    public function testRelativeWithCommonAncestor(): void {
        $this->assertSame('../d/e', Path::relative('/a/b/c', '/a/b/d/e'));
        $this->assertSame('..', Path::relative('/a/b/c', '/a/b'));
        $this->assertSame('../../..', Path::relative('/a/b/c/d', '/a'));
    }

    public function testRelativeOfSamePathIsDot(): void {
        $this->assertSame('.', Path::relative('/a/b', '/a/b'));
    }

    public function testRelativeDescendsIntoSubtree(): void {
        $this->assertSame('c/d', Path::relative('/a/b', '/a/b/c/d'));
    }

    public function testRelativeWorksForRelativeInputs(): void {
        $this->assertSame('../d', Path::relative('a/b/c', 'a/b/d'));
    }

    public function testRelativeRejectsMixedAnchors(): void {
        $this->expectException(RuntimeException::class);
        Path::relative('/a/b', 'c/d');
    }

    public function testRelativeRejectsDifferentDrives(): void {
        $this->expectException(RuntimeException::class);
        Path::relative('C:/a', 'D:/a');
    }

    public function testBasename(): void {
        $this->assertSame('file.txt', Path::basename('/a/b/file.txt'));
        $this->assertSame('file.txt', Path::basename('a/b/file.txt'));
        $this->assertSame('file.txt', Path::basename('file.txt'));
        $this->assertSame('file.txt', Path::basename('a/b/file.txt/'));
        $this->assertSame('', Path::basename('/'));
        $this->assertSame('', Path::basename(''));
    }

    public function testBasenameStripsSuffix(): void {
        $this->assertSame('file', Path::basename('/a/file.txt', '.txt'));
        $this->assertSame('archive.tar', Path::basename('archive.tar.gz', '.gz'));
        $this->assertSame('.txt', Path::basename('/a/.txt', '.txt'));
    }

    public function testDirname(): void {
        $this->assertSame('/a/b', Path::dirname('/a/b/file.txt'));
        $this->assertSame('a/b', Path::dirname('a/b/file.txt'));
        $this->assertSame('.', Path::dirname('file.txt'));
        $this->assertSame('/', Path::dirname('/file.txt'));
        $this->assertSame('.', Path::dirname(''));
    }

    public function testDirnameForWindowsDrive(): void {
        $this->assertSame('C:/Users', Path::dirname('C:/Users/me'));
        $this->assertSame('C:/', Path::dirname('C:/file.txt'));
        $this->assertSame('C:', Path::dirname('C:file.txt'));
    }

    public function testExtension(): void {
        $this->assertSame('txt', Path::extension('/a/file.txt'));
        $this->assertSame('gz', Path::extension('archive.tar.gz'));
        $this->assertSame('', Path::extension('README'));
        $this->assertSame('', Path::extension('.hidden'));
        $this->assertSame('', Path::extension(''));
        $this->assertSame('', Path::extension('/a/'));
    }

    public function testFilename(): void {
        $this->assertSame('file', Path::filename('/a/file.txt'));
        $this->assertSame('archive.tar', Path::filename('archive.tar.gz'));
        $this->assertSame('README', Path::filename('README'));
        $this->assertSame('.hidden', Path::filename('.hidden'));
        $this->assertSame('', Path::filename(''));
    }
}
