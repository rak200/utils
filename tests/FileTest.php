<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\Exception\FilesystemException;
use Rak200\Utils\File;

/**
 * @internal
 *
 * @coversNothing
 */
final class FileTest extends TestCase
{
    private string $tempDir;

    /** @var list<string> */
    private array $created = [];

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir();
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $this->created = [];
    }

    public function testWriteAndReadRoundTrip(): void
    {
        $path = $this->makeTempPath('utils_write_' . uniqid() . '.txt');
        File::write($path, 'hello world');
        $this->assertSame('hello world', File::read($path));
    }

    public function testReadThrowsForMissingFile(): void
    {
        $this->expectException(FilesystemException::class);
        File::read($this->tempDir . '/no_such_file_' . uniqid());
    }

    public function testAppend(): void
    {
        $path = $this->makeTempPath('utils_append_' . uniqid() . '.txt');
        File::write($path, 'a');
        File::append($path, 'b');
        $this->assertSame('ab', File::read($path));
    }

    public function testExists(): void
    {
        $path = $this->makeTempPath('utils_exists_' . uniqid() . '.txt');
        $this->assertFalse(File::exists($path));
        File::write($path, '');
        $this->assertTrue(File::exists($path));
    }

    public function testDeleteIsIdempotent(): void
    {
        $path = $this->makeTempPath('utils_delete_' . uniqid() . '.txt');
        File::write($path, '');
        File::delete($path);
        $this->assertFalse(File::exists($path));
        File::delete($path);
        $this->assertFalse(File::exists($path));
    }

    public function testPathHelpers(): void
    {
        $this->assertSame('txt', File::ext('/some/path/file.txt'));
        $this->assertSame('file.txt', File::basename('/some/path/file.txt'));
        $this->assertSame('file', File::basename('/some/path/file.txt', '.txt'));
        $this->assertSame(
            str_replace('\\', '/', '/some/path'),
            str_replace('\\', '/', File::dirname('/some/path/file.txt')),
        );
    }

    public function testSize(): void
    {
        $path = $this->makeTempPath('utils_size_' . uniqid() . '.txt');
        File::write($path, 'abcde');
        $this->assertSame(5, File::size($path));
    }

    public function testLinesGenerator(): void
    {
        $path = $this->makeTempPath('utils_lines_' . uniqid() . '.txt');
        File::write($path, "line1\nline2\nline3");
        $lines = iterator_to_array(File::lines($path), false);
        $this->assertSame(['line1', 'line2', 'line3'], $lines);
    }

    public function testTempFile(): void
    {
        $path = File::temp('uti');
        $this->created[] = $path;
        $this->assertTrue(File::exists($path));
        $this->assertStringStartsWith('uti', basename($path));
    }

    public function testCopy(): void
    {
        $source = $this->makeTempPath('utils_copy_src_' . uniqid() . '.txt');
        $target = $this->makeTempPath('utils_copy_dst_' . uniqid() . '.txt');
        File::write($source, 'payload');
        File::copy($source, $target);
        $this->assertSame('payload', File::read($target));
        $this->assertTrue(File::exists($source));
    }

    public function testMove(): void
    {
        $source = $this->makeTempPath('utils_move_src_' . uniqid() . '.txt');
        $target = $this->makeTempPath('utils_move_dst_' . uniqid() . '.txt');
        File::write($source, 'payload');
        File::move($source, $target);
        $this->assertSame('payload', File::read($target));
        $this->assertFalse(File::exists($source));
    }

    public function testMimeTypeForText(): void
    {
        if (!function_exists('finfo_open')) {
            $this->markTestSkipped('fileinfo extension is not available.');
        }
        $path = $this->makeTempPath('utils_mime_' . uniqid() . '.txt');
        File::write($path, "hello\n");
        $this->assertStringStartsWith('text/', File::mime($path));
    }

    public function testIsFileIsDir(): void
    {
        $path = $this->makeTempPath('utils_typecheck_' . uniqid() . '.txt');
        File::write($path, 'x');
        $this->assertTrue(File::isFile($path));
        $this->assertFalse(File::isDir($path));
        $this->assertTrue(File::isDir($this->tempDir));
        $this->assertFalse(File::isFile($this->tempDir));
    }

    public function testMkdirCreatesAndIsIdempotent(): void
    {
        $dir = $this->tempDir . DIRECTORY_SEPARATOR . 'utils_mkdir_' . uniqid() . DIRECTORY_SEPARATOR . 'nested';
        File::mkdir($dir);
        $this->assertTrue(File::isDir($dir));
        File::mkdir($dir); // second call must not throw
        @rmdir($dir);
        @rmdir(dirname($dir));
    }

    public function testListReturnsMatchingEntries(): void
    {
        $base = $this->tempDir . DIRECTORY_SEPARATOR . 'utils_list_' . uniqid();
        File::mkdir($base);

        try {
            File::write($base . DIRECTORY_SEPARATOR . 'a.txt', '');
            File::write($base . DIRECTORY_SEPARATOR . 'b.txt', '');
            File::write($base . DIRECTORY_SEPARATOR . 'c.md', '');

            $all = File::list($base);
            $this->assertCount(3, $all);

            $txt = File::list($base, '*.txt');
            $this->assertCount(2, $txt);
            foreach ($txt as $path) {
                $this->assertStringEndsWith('.txt', $path);
            }
        } finally {
            foreach (glob($base . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($base);
        }
    }

    public function testListThrowsForMissingDirectory(): void
    {
        $this->expectException(FilesystemException::class);
        File::list($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_' . uniqid());
    }

    public function testTouchCreatesFile(): void
    {
        $path = $this->makeTempPath('utils_touch_' . uniqid() . '.txt');
        $this->assertFalse(File::exists($path));
        File::touch($path);
        $this->assertTrue(File::isFile($path));
        $this->assertSame('', File::read($path));
    }

    public function testTouchSetsModificationTime(): void
    {
        $path = $this->makeTempPath('utils_touchtime_' . uniqid() . '.txt');
        File::write($path, 'x');
        $time = 1_600_000_000;
        File::touch($path, $time);
        $this->assertSame($time, filemtime($path));
    }

    public function testRealpathResolvesExistingFile(): void
    {
        $path = $this->makeTempPath('utils_realpath_' . uniqid() . '.txt');
        File::write($path, 'x');
        $resolved = File::realpath($path);
        $this->assertTrue(File::isFile($resolved));
        $this->assertSame(File::realpath($path), $resolved); // stable
    }

    public function testRealpathThrowsForMissingPath(): void
    {
        $this->expectException(FilesystemException::class);
        File::realpath($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_' . uniqid());
    }

    public function testWriteAndReadCsvRoundTrip(): void
    {
        $path = $this->makeTempPath('utils_csv_' . uniqid() . '.csv');
        $rows = [['id', 'name'], ['1', 'Ann'], ['2', 'a,b "c"']];
        File::writeCsv($path, $rows);
        $this->assertSame($rows, File::readCsv($path));
    }

    public function testReadCsvSkipsBlankLines(): void
    {
        $path = $this->makeTempPath('utils_csvblank_' . uniqid() . '.csv');
        File::write($path, "a,b\n\nc,d\n");
        $this->assertSame([['a', 'b'], ['c', 'd']], File::readCsv($path));
    }

    public function testReadCsvCustomSeparator(): void
    {
        $path = $this->makeTempPath('utils_csvsep_' . uniqid() . '.csv');
        File::write($path, "a;b;c\n");
        $this->assertSame([['a', 'b', 'c']], File::readCsv($path, ';'));
    }

    public function testReadCsvThrowsForMissingFile(): void
    {
        $this->expectException(FilesystemException::class);
        File::readCsv($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_' . uniqid() . '.csv');
    }

    public function testRenamedMethods(): void
    {
        $this->assertSame('txt', File::ext('dir/file.txt'));
        $temp = File::temp();
        $this->created[] = $temp;
        $this->assertTrue(File::isFile($temp));
    }

    public function testMimeThrowsForMissingFile(): void
    {
        $this->expectException(FilesystemException::class);
        File::mime($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_' . uniqid());
    }

    public function testSizeThrowsForMissingFile(): void
    {
        $this->expectException(FilesystemException::class);
        File::size($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_' . uniqid());
    }

    public function testLinesThrowsForMissingFile(): void
    {
        $this->expectException(FilesystemException::class);
        iterator_to_array(File::lines($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_' . uniqid()));
    }

    public function testCopyThrowsForMissingSource(): void
    {
        $this->expectException(FilesystemException::class);
        File::copy(
            $this->tempDir . DIRECTORY_SEPARATOR . 'no_such_' . uniqid(),
            $this->makeTempPath('utils_copy_dst_' . uniqid() . '.txt'),
        );
    }

    public function testMoveThrowsForMissingSource(): void
    {
        $this->expectException(FilesystemException::class);
        File::move(
            $this->tempDir . DIRECTORY_SEPARATOR . 'no_such_' . uniqid(),
            $this->makeTempPath('utils_move_dst_' . uniqid() . '.txt'),
        );
    }

    public function testWriteThrowsWhenTargetDirIsMissing(): void
    {
        // The parent directory does not exist, so the underlying write fails;
        // @ suppresses the expected warning while we assert the throw.
        $this->expectException(FilesystemException::class);
        @File::write($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_dir_' . uniqid() . DIRECTORY_SEPARATOR . 'f.txt', 'x');
    }

    public function testAppendThrowsWhenTargetDirIsMissing(): void
    {
        $this->expectException(FilesystemException::class);
        @File::append($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_dir_' . uniqid() . DIRECTORY_SEPARATOR . 'f.txt', 'x');
    }

    public function testTouchThrowsWhenParentDirIsMissing(): void
    {
        $this->expectException(FilesystemException::class);
        @File::touch($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_dir_' . uniqid() . DIRECTORY_SEPARATOR . 'f.txt');
    }

    public function testCopyThrowsWhenTargetDirIsMissing(): void
    {
        $src = $this->makeTempPath('utils_copy_src_' . uniqid() . '.txt');
        File::write($src, 'x');
        $this->expectException(FilesystemException::class);
        @File::copy($src, $this->tempDir . DIRECTORY_SEPARATOR . 'no_such_dir_' . uniqid() . DIRECTORY_SEPARATOR . 'f.txt');
    }

    public function testMoveThrowsWhenTargetDirIsMissing(): void
    {
        $src = $this->makeTempPath('utils_move_src_' . uniqid() . '.txt');
        File::write($src, 'x');
        $this->expectException(FilesystemException::class);
        @File::move($src, $this->tempDir . DIRECTORY_SEPARATOR . 'no_such_dir_' . uniqid() . DIRECTORY_SEPARATOR . 'f.txt');
    }

    public function testMkdirThrowsWhenPathIsAnExistingFile(): void
    {
        $path = $this->makeTempPath('utils_mkdir_file_' . uniqid() . '.txt');
        File::write($path, 'x');
        $this->expectException(FilesystemException::class);
        @File::mkdir($path);
    }

    public function testWriteCsvThrowsWhenTargetDirIsMissing(): void
    {
        $this->expectException(FilesystemException::class);
        @File::writeCsv($this->tempDir . DIRECTORY_SEPARATOR . 'no_such_dir_' . uniqid() . DIRECTORY_SEPARATOR . 'o.csv', [['a', 'b']]);
    }

    private function makeTempPath(string $basename): string
    {
        $path = $this->tempDir . DIRECTORY_SEPARATOR . $basename;
        $this->created[] = $path;

        return $path;
    }
}
