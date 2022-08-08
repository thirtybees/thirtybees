<?php
/**
 * Copyright (C) 2022 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Error;


use Encryptor;

/**
 * class ErrorDescription
 *
 * @since 1.4.0
 */
class ErrorDescriptionCore
{
    /**
     * @var string
     */
    private $phpVersion;


    /**
     * @var string
     */
    private $codeBuildFor;

    /**
     * @var string
     */
    private $codeRevision;


    /**
     * @var string
     */
    protected $errorName;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $sourceType;

    /**
     * @var string
     */
    protected $sourceFile;

    /**
     * @var int
     */
    protected $sourceLine;

    /**
     * @var array
     */
    protected $sourceFileContent = [];

    /**
     * @var string
     */
    protected $realSourceFile;

    /**
     * @var int
     */
    protected $realSourceLine;

    /**
     * @var array
     */
    protected $extraSections = [];

    /**
     * @var array
     */
    protected $stackTrace = [];

    /**
     * @var ErrorDescription
     */
    protected $cause;

    /**
     */
    public function __construct()
    {
        $this->phpVersion = phpversion();
        $this->codeBuildFor = _TB_BUILD_PHP_;
        $this->codeRevision = _TB_REVISION_;
    }


    /**
     * @param string $errorName
     */
    public function setErrorName(string $errorName)
    {
        $this->errorName = $errorName;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getErrorName(): string
    {
        return $this->errorName;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $sourceType
     * @param string $file
     * @param int $line
     * @param array $content
     * @return void
     */
    public function setSource(string $sourceType, string $file, int $line, array $content)
    {
        $this->sourceType = $sourceType;
        $this->sourceFile = $file;
        $this->sourceLine = $line;
        $this->sourceFileContent = $content;
    }

    /**
     * @param string $file
     * @param int $line
     * @return void
     */
    public function setRealSource(string $file, int $line)
    {
        $this->realSourceFile = $file;
        $this->realSourceLine = $line;
    }

    /**
     * @return string
     */
    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    /**
     * @return string
     */
    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    /**
     * @return int
     */
    public function getSourceLine(): int
    {
        return (int)$this->sourceLine;
    }

    /**
     * @return array
     */
    public function getSourceFileContent(): array
    {
        return $this->sourceFileContent;
    }

    public function hasSourceFileContent(): bool
    {
        return !!$this->sourceFileContent;
    }

    /**
     * @param array $extraSections
     * @return void
     */
    public function setExtraSections(array $extraSections)
    {
        $this->extraSections = $extraSections;
    }

    /**
     * @return array
     */
    public function getExtraSections(): array
    {
        return $this->extraSections;
    }

    /**
     * @param array $stacktrace
     * @return void
     */
    public function setStackTrace(array $stacktrace)
    {
        $this->stackTrace = $stacktrace;
    }

    /**
     * @return array
     */
    public function getStackTrace(): array
    {
        return $this->stackTrace;
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return void
     */
    public function setCause(ErrorDescription $errorDescription)
    {
        $this->cause = $errorDescription;
    }

    /**
     * @return ErrorDescription | null
     */
    public function getCause()
    {
        return $this->cause;
    }

    /**
     * @return bool
     */
    public function hasCause()
    {
        return !is_null($this->cause);
    }

    /**
     * Return the content of the Exception
     * @return string content of the exception.
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getExtendedMessage()
    {
        if ($this->getSourceType() === 'smarty') {
            return $this->getErrorName() . ': ' . $this->getMessage() . ' in template file ' . ErrorUtils::getRelativeFile($this->getSourceFile());
        } else {
            return $this->getErrorName() . ': ' . $this->getMessage() . ' at line ' . $this->getSourceLine() . ' in file ' . ErrorUtils::getRelativeFile($this->getSourceFile());
        }
    }

    /**
     * @return string
     */
    public function getTraceAsString()
    {
        $result = '';
        $stackTrace = $this->getStackTrace();
        if ($stackTrace) {
            $total = count($stackTrace) + 1;
            $separatorLen = strlen("$total") + 1;
            $separator = str_repeat(' ', $separatorLen - 1);
            $result .= '#0' . $separator . ErrorUtils::getRelativeFile($this->getSourceFile()) . '(' . $this->getSourceLine() . ")\n";
            $cnt = 1;
            foreach ($stackTrace as $trace) {
                $len = strlen("$cnt");
                $separator = str_repeat(' ', $separatorLen - $len);
                $result .= '#' . $cnt . $separator . $trace['fileName'] . '(' . $trace['line'] . '): ';
                $result .= $trace['class'] . $trace['type'] . $trace['function'] . '(';
                if ($trace['args']) {
                    $args = array_map(function($param) {
                        return strtok($param, "\n");
                    }, $trace['args']);
                    $result .= implode(', ', $args);
                }
                $result .= ')';
                $result .= "\n";
                $cnt++;
            }
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getPhpVersion()
    {
        return $this->phpVersion;
    }

    /**
     * @param string $phpVersion
     */
    public function setPhpVersion($phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }

    /**
     * @return string
     */
    public function getCodeBuildFor(): string
    {
        return $this->codeBuildFor;
    }

    /**
     * @param string $codeBuildFor
     */
    public function setCodeBuildFor(string $codeBuildFor)
    {
        $this->codeBuildFor = $codeBuildFor;
    }

    /**
     * @return string
     */
    public function getCodeRevision(): string
    {
        return $this->codeRevision;
    }

    /**
     * @param string $codeRevision
     */
    public function setCodeRevision(string $codeRevision)
    {
        $this->codeRevision = $codeRevision;
    }

    /**
     * @return string
     */
    public function getRealSourceFile(): string
    {
        return $this->realSourceFile;
    }

    /**
     * @param string $realSourceFile
     */
    public function setRealSourceFile(string $realSourceFile)
    {
        $this->realSourceFile = $realSourceFile;
    }

    /**
     * @return int
     */
    public function getRealSourceLine(): int
    {
        return $this->realSourceLine;
    }

    /**
     * @param int $realSourceLine
     */
    public function setRealSourceLine(int $realSourceLine)
    {
        $this->realSourceLine = $realSourceLine;
    }



    /**
     * @return array
     */
    public function toArray()
    {
        $source = [
            'phpVersion' => $this->getPhpVersion(),
            'codeBuildFor' => $this->getCodeBuildFor(),
            'codeRevision' => $this->getCodeRevision(),
            'type' => $this->getSourceType(),
            'file' => $this->getSourceFile(),
            'line' => $this->getSourceLine(),
            'content' => $this->getSourceFileContent(),
        ];
        if ($this->realSourceFile) {
            $source['realFile'] = $this->realSourceFile;
            $source['realLine'] = $this->realSourceLine;
        }
        $data = [
            'errorName' => $this->getErrorName(),
            'message' => $this->getMessage(),
            'source' => $source,
            'stackTrace' => $this->getStackTrace(),
            'extra' => $this->getExtraSections(),
        ];
        if ($this->cause) {
            $data['cause'] = $this->cause->toArray();
        }
        return $data;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * @return string
     * @throws \PrestaShopException
     */
    public function encrypt()
    {
        return Encryptor::getInstance()->encrypt($this->serialize());
    }

    /**
     * @param $encrypted
     * @return ErrorDescription
     * @throws \PrestaShopException
     */
    public static function decrypt($encrypted)
    {
        $decrypted = Encryptor::getInstance()->decrypt($encrypted);
        if (!$decrypted) {
            throw new \PrestaShopException("Failed to decrypt content");
        }
        $array = json_decode($decrypted, true);
        if (!is_array($array) || !$array) {
            throw new \PrestaShopException("Failed to parse content");
        }
        return static::deserialize($array);
    }

    /**
     * @param $array
     * @return ErrorDescription
     * @throws \PrestaShopException
     */
    public static function deserialize($array)
    {
        $description = new ErrorDescription();
        $description->setPhpVersion(static::getProperty('phpVersion', $array, false, 'unknown'));
        $description->setCodeBuildFor(static::getProperty('codeBuildFor', $array,false, 'unknown'));
        $description->setCodeRevision(static::getProperty('codeRevision', $array, false, 'unknown'));

        $description->setErrorName(static::getProperty('errorName', $array));
        $description->setMessage(static::getProperty('message', $array));
        $source = static::getProperty('source', $array);
        $description->setSource(
            static::getProperty('type', $source),
            static::getProperty('file', $source),
            (int)static::getProperty('line', $source),
            static::getProperty('content', $source)
        );
        $description->setStackTrace(static::getProperty('stackTrace', $array));
        $description->setExtraSections(static::getProperty('extra', $array));
        $cause = static::getProperty('cause', $array, false);
        if ($cause) {
            $description->setCause(static::deserialize($cause));
        }
        return $description;
    }

    /**
     * @param string $name
     * @param array $array
     * @param boolean $required
     * @return mixed
     * @throws \PrestaShopException
     */
    protected static function getProperty($name, $array, $required = true, $default = '')
    {
        if (array_key_exists($name, $array)) {
            return $array[$name];
        }
        if ($required) {
            throw new \PrestaShopException("Missing key '$name'");
        }
        return $default;
    }
}
