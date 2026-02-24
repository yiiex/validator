<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

use Psr\Http\Message\UploadedFileInterface;

class FileRule extends AbstractRule
{
    /**
     * @var boolean whether the attribute requires a file to be uploaded or not.
     * Defaults to false, meaning a file is required to be uploaded.
     * When no file is uploaded, the owner attribute is set to null to prevent
     * setting arbitrary values.
     */
    public bool $allowEmpty = false;

    /**
     * @var mixed|null a list of file name extensions that are allowed to be uploaded.
     * This can be either an array or a string consisting of file extension names
     * separated by space or comma (e.g. "gif, jpg").
     * Extension names are case-insensitive. Defaults to null, meaning all file name
     * extensions are allowed.
     */
    public ?array $types = null;

    /**
     * @var mixed|null a list of MIME-types of the file that are allowed to be uploaded.
     * This can be either an array or a string consisting of MIME-types separated
     * by space or comma (e.g. "image/gif, image/jpeg"). MIME-types are
     * case-insensitive. Defaults to null, meaning all MIME-types are allowed.
     * In order to use this property fileinfo PECL extension should be installed.
     * @since 1.1.11
     */
    public ?array $mimeTypes = null;

    /**
     * @var integer|null the minimum number of bytes required for the uploaded file.
     * Defaults to null, meaning no limit.
     * @see tooSmall
     */
    public ?int $minSize = null;

    /**
     * @var integer|null the maximum number of bytes required for the uploaded file.
     * Defaults to null, meaning no limit.
     * Note, the size limit is also affected by 'upload_max_filesize' INI setting
     * and the 'MAX_FILE_SIZE' hidden field value.
     * @see tooLarge
     */
    public ?int $maxSize = null;

    /**
     * @var integer the maximum file count the given attribute can hold.
     * It defaults to 1, meaning single file upload. By defining a higher number,
     * multiple uploads become possible.
     */
    public int $maxFiles = 1;

    /**
     * @var string the error message used when the uploaded file is too large.
     * @see maxSize
     */
    public string $tooLarge = 'The file "{file}" is too large. Its size cannot exceed {limit} bytes.';

    /**
     * @var string the error message used when the uploaded file is too small.
     * @see minSize
     */
    public string $tooSmall = 'The file "{file}" is too small. Its size cannot be smaller than {limit} bytes.';

    /**
     * @var string the error message used when the uploaded file has an extension name
     * that is not listed among {@link types}.
     */
    public string $wrongType = 'The file "{file}" cannot be uploaded. Only files with these extensions are allowed: {extensions}.';

    /**
     * @var string the error message used when the uploaded file has a MIME-type
     * that is not listed among {@link mimeTypes}. In order to use this property
     * fileinfo PECL extension should be installed.
     * @since 1.1.11
     */
    public string $wrongMimeType = 'The file "{file}" cannot be uploaded. Only files of these MIME-types are allowed: {mimeTypes}.';

    /**
     * @var string the error message used if the count of multiple uploads exceeds
     * limit.
     */
    public string $tooMany = '{attribute} cannot accept more than {limit} files.';
    public ?string $message = '{attribute} cannot be blank.';

    /**
     * Set the attribute and then validates using {@link validateFile}.
     * If there is any error, the error message is added to the object.
     * @param object $object the object being validated
     * @param string $attribute the attribute being validated
     */
    protected function validateAttribute(object $object, string $attribute): void
    {
        $files = $object->$attribute;
        $files = is_array($files) ? $files : [$files];

        // быстрая проверка количества
        if (!$files) {
            if ($this->safe) {
                $object->$attribute = null;
            }
            if (!$this->allowEmpty) {
                $this->validator->addError($attribute, $this->message);
            }
            return;
        } elseif (count($files) > $this->maxFiles) {
            $this->validator->addError($attribute, $this->tooMany, [
                '{limit}' => $this->maxFiles,
            ]);
        }
        foreach ($files as $file) {
            if (!$file instanceof UploadedFileInterface) {
                if (!$this->allowEmpty) {
                    $this->validator->addError($attribute, 'Value must be an UploadedFileInterface instance.');
                }
                continue;
            }
            $this->validateFile($file, $attribute);
        }
    }

    protected function validateFile(UploadedFileInterface $file, string $attribute): void
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $message = match ($file->getError()) {
                UPLOAD_ERR_NO_FILE => 'No file "{file}" was uploaded.',
                UPLOAD_ERR_INI_SIZE => 'File "{file}" exceeds upload_max_filesize.',
                UPLOAD_ERR_FORM_SIZE => 'File "{file}" exceeds MAX_FILE_SIZE.',
                UPLOAD_ERR_PARTIAL => 'File "{file}" was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder for file "{file}".',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file "{file}" to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped file "{file}" upload.',
                default => 'Unknown upload error for file "{file}".',
            };
            $this->validator->addError($attribute, $message, ['{file}' => $file->getClientFilename()]);
            return;
        }

        $size = $file->getSize();
        if ($this->maxSize !== null && $size > $this->getMaxBytes()) {
            $this->validator->addError($attribute, $this->tooLarge, [
                '{file}' => $file->getClientFilename(),
                '{limit}' => $this->getMaxBytes(),
            ]);
        }

        if ($this->minSize !== null && $size < $this->minSize) {
            $this->validator->addError($attribute, $this->tooSmall, [
                '{file}' => $file->getClientFilename(),
                '{limit}' => $this->minSize,
            ]);
        }

        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if ($this->types !== null && !in_array($ext, $this->types, true)) {
            $this->validator->addError($attribute, $this->wrongType, [
                '{file}' => $file->getClientFilename(),
                '{extensions}' => implode(', ', $this->types),
            ]);
        }

        $mime = strtolower((string)$file->getClientMediaType());
        if ($this->mimeTypes !== null && !in_array($mime, $this->mimeTypes, true)) {
            $this->validator->addError($attribute, $this->wrongMimeType, [
                '{file}' => $file->getClientFilename(),
                '{mimeTypes}' => implode(', ', $this->mimeTypes),
            ]);
        }
    }

    protected function getMaxBytes(): int
    {
        $uploadLimit = $this->phpIniToBytes(ini_get('upload_max_filesize'));
        $postLimit = $this->phpIniToBytes(ini_get('post_max_size'));
        $systemLimit = min($uploadLimit, $postLimit) ?: PHP_INT_MAX;

        return $this->maxSize === null ? $systemLimit : min($this->maxSize, $systemLimit);
    }

    protected function phpIniToBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $num = (int)$val;

        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }
}
