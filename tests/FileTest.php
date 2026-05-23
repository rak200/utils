<?php

declare(strict_types=1);

namespace Rak200\Utils\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Utils\File;
use RuntimeException;

final class FileTest extends TestCase {
    private string $tempDir;
    /** @var list<string> */
    private array $created = [];

    protected function setUp(): void {
        $this->tempDir = sys_get_temp_dir();
    }

    protected function tearDown(): void {
        foreach ($this->created as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $this->created = [];
    }

    private function makeTempPath(string $basename): string {
        $path = $this->tempDir . DIRECTORY_SEPARATOR . $basename;
        $this->created[] = $path;
        return $path;
    }

    public function testWriteAndReadRoundTrip(): void {
        $path = $this->makeTempPath('utils_write_' . uniqid() . '.txt');
        File::write($path, 'hello world');
        $this->assertSame('hello world', File::read($path));
    }

    public function testReadThrowsForMissingFile(): void {
        $this->expectException(RuntimeException::class);
        File::read($this->tempDir . '/no_such_file_' . uniqid());
    }

    public function testAppend(): void {
        $path = $this->makeTempPath('utils_append_' . uniqid() . '.txt');
        File::write($path, 'a');
        File::append($path, 'b');
        $this->assertSame('ab', File::read($path));
    }

    public function testExists(): void {
        $path = $this->makeTempPath('utils_exists_' . uniqid() . '.txt');
        $this->assertFalse(File::exists($path));
        File::write($path, '');
        $this->assertTrue(File::exists($path));
    }

    public function testDeleteIsIdempotent(): void {
        $path = $this->makeTempPath('utils_delete_' . uniqid() . '.txt');
        File::write($path, '');
        File::delete($path);
        $this->assertFalse(File::exists($path));
        File::delete($path);
        $this->assertFalse(File::exists($path));
    }

    public function testPathHelpers(): void {
        $this->assertSame('txt', File::extension('/some/path/file.txt'));
        $this->assertSame('file.txt', File::basename('/some/path/file.txt'));
        $this->assertSame('file', File::basename('/some/path/file.txt', '.txt'));
        $this->assertSame(
            str_replace('\\', '/', '/some/path'),
            str_replace('\\', '/', File::dirname('/some/path/file.txt')),
        );
    }

    public function testSize(): void {
        $path = $this->makeTempPath('utils_size_' . uniqid() . '.txt');
        File::write($path, 'abcde');
        $this->assertSame(5, File::size($path));
    }

    public function testLinesGenerator(): void {
        $path = $this->makeTempPath('utils_lines_' . uniqid() . '.txt');
        File::write($path, "line1\nline2\nline3");
        $lines = iterator_to_array(File::lines($path), false);
        $this->assertSame(['line1', 'line2', 'line3'], $lines);
    }

    public function testTempFile(): void {
        $path = File::tempFile('uti');
        $this->created[] = $path;
        $this->assertTrue(File::exists($path));
        $this->assertStringStartsWith('uti', basename($path));
    }

    public function testCopy(): void {
        $source = $this->makeTempPath('utils_copy_src_' . uniqid() . '.txt');
        $target = $this->makeTempPath('utils_copy_dst_' . uniqid() . '.txt');
        File::write($source, 'payload');
        File::copy($source, $target);
        $this->assertSame('payload', File::read($target));
        $this->assertTrue(File::exists($source));
    }

    public function testMove(): void {
        $source = $this->makeTempPath('utils_move_src_' . uniqid() . '.txt');
        $target = $this->makeTempPath('utils_move_dst_' . uniqid() . '.txt');
        File::write($source, 'payload');
        File::move($source, $target);
        $this->assertSame('payload', File::read($target));
        $this->assertFalse(File::exists($source));
    }

    public function testMimeTypeForText(): void {
        if (!function_exists('finfo_open')) {
            $this->markTestSkipped('fileinfo extension is not available.');
        }
        $path = $this->makeTempPath('utils_mime_' . uniqid() . '.txt');
        File::write($path, "hello\n");
        $this->assertStringStartsWith('text/', File::mimeType($path));
    }
}
