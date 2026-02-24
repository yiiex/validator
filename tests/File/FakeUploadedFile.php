<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests\File;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Подменный объект UploadedFile для тестов
 */
final class FakeUploadedFile implements UploadedFileInterface
{
    public function __construct(
        private string  $clientFilename,
        private ?int    $size = null,
        private ?string $mime = null,
        private int     $error = UPLOAD_ERR_OK
    )
    {
    }

    public function getStream(): \Psr\Http\Message\StreamInterface
    {
        throw new \RuntimeException('Not implemented');
    }

    public function moveTo($targetPath): void
    {
        throw new \RuntimeException('Not implemented');
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->mime;
    }
}