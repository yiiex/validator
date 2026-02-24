<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Yii1x\Validator\Rules\FileRule;
use Yii1x\Validator\Tests\File\FakeUploadedFile;
use Yii1x\Validator\Validator;

final class FileRuleTest extends TestCase
{
    /* ---------- ПОМОЩНИК ---------- */
    private static function makeFile(
        string  $name,
        ?int    $size = null,
        ?string $mime = null,
        int     $error = UPLOAD_ERR_OK
    ): UploadedFileInterface
    {
        return new FakeUploadedFile($name, $size, $mime, $error);
    }

    /* ---------- ВАЛИДНЫЕ ФАЙЛЫ ---------- */

    #[DataProvider('validProvider')]
    public function testValid(
        UploadedFileInterface $file,
        ?array                $types = null,
        ?array                $mimeTypes = null,
        ?int                  $minSize = null,
        ?int                  $maxSize = null
    ): void
    {
        $obj = (object)['file' => $file];
        $rule = new FileRule();
        $rule->types = $types;
        $rule->mimeTypes = $mimeTypes;
        $rule->minSize = $minSize;
        $rule->maxSize = $maxSize;
        $rule->allowEmpty = false;
        $rule->attributes = ['file'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('file'));
    }

    /* ---------- НЕВАЛИДНЫЕ ФАЙЛЫ ---------- */

    #[DataProvider('invalidProvider')]
    public function testInvalid(
        UploadedFileInterface $file,
        string $expectedMessage,
        ?array $types = null,
        ?array $mimeTypes = null,
        ?int $minSize = null,
        ?int $maxSize = null
    ): void {
        $obj  = (object)['file' => $file];
        $rule = new FileRule();
        $rule->types      = $types;
        $rule->mimeTypes  = $mimeTypes;
        $rule->minSize    = $minSize;
        $rule->maxSize    = $maxSize;
        $rule->allowEmpty = false;
        $rule->attributes = ['file'];
        $rule->validator  = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('file'));
        $this->assertStringContainsString($expectedMessage, implode(' ', $rule->validator->getErrors('file')['file']));
    }

    /* ---------- allowEmpty ---------- */

    public function testAllowEmpty(): void
    {
        $obj = (object)['file' => null];
        $rule = new FileRule();
        $rule->allowEmpty = true;
        $rule->attributes = ['file'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('file'));
    }

    /* ---------- MULTIPLE UPLOAD ---------- */

    public function testMaxFilesExceeded(): void
    {
        $files = [
            $this->makeFile('a.txt'),
            $this->makeFile('b.txt'),
        ];
        $obj = (object)['files' => $files];
        $rule = new FileRule();
        $rule->maxFiles = 1;
        $rule->allowEmpty = false;
        $rule->attributes = ['files'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('files'));
        $this->assertStringContainsString('cannot accept more than 1 files', $rule->validator->getErrors('files')['files'][0]);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validProvider(): iterable
    {
        // без ограничений
        yield [self::makeFile('image.jpg', 1024)];

        // по расширению
        yield [self::makeFile('photo.jpg'), ['jpg', 'png']];

        // по MIME
        yield [self::makeFile('report.pdf', 2048, 'application/pdf'), null, ['application/pdf']];

        // по размеру
        yield [self::makeFile('file.txt', 2048), null, null, 1000, 3000];
    }

    public static function invalidProvider(): iterable
    {
        // 1. неправильное расширение
        yield [
            self::makeFile('script.php'),
            'The file "script.php" cannot be uploaded. Only files with these extensions are allowed: jpg, png',
            ['jpg', 'png'],
            null,
            null,
            null
        ];

        // 2. неправильный MIME-тип
        yield [
            self::makeFile('fake.jpg', 100, 'text/plain'),
            'The file "fake.jpg" cannot be uploaded. Only files of these MIME-types are allowed: image/jpeg',
            null,
            ['image/jpeg'],
            null,
            null
        ];

        // 3. файл слишком большой
        yield [
            self::makeFile('big.zip', 5 * 1024 * 1024), // 5 МБ
            'The file "big.zip" is too large. Its size cannot exceed 1048576 bytes.',
            null,
            null,
            null,
            1024 * 1024   // 1 МБ
        ];

        // 4. файл слишком маленький
        yield [
            self::makeFile('tiny.txt', 100),
            'The file "tiny.txt" is too small. Its size cannot be smaller than 2000 bytes.',
            null,
            null,
            2000,
            null
        ];

        // 5. ошибка загрузки
        yield [
            self::makeFile('no_file.txt', null, null, UPLOAD_ERR_NO_FILE),
            'No file "no_file.txt" was uploaded.',
            null,
            null,
            null,
            null
        ];
    }
}