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

use PrestaShopExceptionCore;
use SmartyCustom;
use Throwable;

/**
 * Class ErrorUtilsCore
 */
class ErrorUtilsCore
{
    const FILE_CONTEXT_LINES = 30;

    /**
     * Describe error array
     *
     * @param array $error
     * @return ErrorDescription
     */
    public static function describeError($error)
    {
        $errorDescription = new ErrorDescription();
        $errorDescription->setErrorName('Fatal Error');
        $errorDescription->setMessage($error['message']);

        $smartyTrace = SmartyCustom::$trace;
        $file = $error['file'];
        $line = $error['line'];
        $isTemplate = SmartyCustom::isCompiledTemplate($file);
        if ($isTemplate) {
            $compiledContent = static::readFile($file, $line, static::FILE_CONTEXT_LINES);
            $errorDescription->setRealSource($file, $line, $compiledContent);
            $file = array_pop($smartyTrace);
            $content = static::readFile($file, 0, -1);
            $errorDescription->setSource('smarty', $file, 0, $content);
        } else {
            $content = static::readFile($file, $line, static::FILE_CONTEXT_LINES);
            $errorDescription->setSource('php', $file, $line, $content);
        }

        $stacktrace = [
            1 => [
                'class' => '',
                'function' => '',
                'type' => '',
                'fileType' => $isTemplate ? 'template' : 'php',
                'fileName' => static::getRelativeFile($error['file']),
                'line' => $line,
                'args' => null,
                'fileContent' => $content,
                'suppressed' => false,
            ]
        ];
        $errorDescription->setStackTrace($stacktrace);
        return $errorDescription;
    }

    /**
     * Helper method to describe exception
     *
     * @param Throwable $e
     * @return ErrorDescription
     */
    public static function describeException(Throwable $e)
    {
        $errorDescription = new ErrorDescription();
        $errorDescription->setErrorName(str_replace('PrestaShop', 'ThirtyBees', get_class($e)));
        $errorDescription->setMessage($e->getMessage());

        $smartyTrace = SmartyCustom::$trace;
        $file = $e->getFile();
        $line = $e->getLine();
        if (SmartyCustom::isCompiledTemplate($file)) {
            $compiledContent = static::readFile($file, $line, static::FILE_CONTEXT_LINES);
            $errorDescription->setRealSource($file, $e->getLine(), $compiledContent);
            $file = array_pop($smartyTrace);
            $errorDescription->setSource('smarty', $file, 0, static::readFile($file, 0, -1));
        } else {
            $errorDescription->setSource('php', $file, $line, static::readFile($file, $line, static::FILE_CONTEXT_LINES));
        }

        if ($e instanceof PrestaShopExceptionCore) {
            $traces = $e->getCustomTrace();
            $errorDescription->setExtraSections($e->getExtraSections());
        } else {
            $traces = $e->getTrace();
        }

        $stacktrace = [];

        foreach ($traces as $id => $trace) {
            $class = $trace['class'] ?? '';
            $function = $trace['function'] ?? '';
            $type = $trace['type'] ?? '';
            $fileName = $trace['file'] ?? '';
            $lineNumber = $trace['line'] ?? 0;
            $args = $trace['args'] ?? [];
            $isTemplate = false;
            $showLines = static::FILE_CONTEXT_LINES;
            if ($smartyTrace && SmartyCustom::isCompiledTemplate($fileName)) {
                $isTemplate = true;
                $fileName = array_pop($smartyTrace);
                $lineNumber = 0;
                $showLines = -1;
            }
            $relativeFile = static::getRelativeFile($fileName);
            $nextId = $id + 1;
            $currentFunction = '';
            $currentClass = '';
            if (isset($traces[$nextId]['class'])) {
                $currentClass = $traces[$nextId]['class'];
                $currentFunction = $traces[$nextId]['function'];
            }
            $stacktrace[] = [
                'class' => $class,
                'function' => $function,
                'type' => $type,
                'fileType' => $isTemplate ? 'template' : 'php',
                'fileName' => $relativeFile,
                'line' => $lineNumber,
                'args' => array_map([__CLASS__, 'displayArgument'], $args),
                'fileContent' => static::readFile($fileName, $lineNumber, $showLines),
                'description' => static::describeOperation($class, $function, $args),
                'suppressed' => static::isSuppressed($relativeFile, $currentClass, $currentFunction, $class, $function)
            ];
        }

        $errorDescription->setStackTrace($stacktrace);

        $previous = $e->getPrevious();
        if ($previous) {
            $errorDescription->setCause(static::describeException($previous));
        }

        return $errorDescription;

    }

    /**
     * Method will render argument into string. Similar to var_dump, but will product smaller output
     *
     * @param mixed $variable variable to be rendered
     * @param int $strlen max length of string. If longer then string will be truncated and ... will be added
     * @param int $width maximal number of array items to be rendered
     * @param int $depth maximaln depth that we will traverse
     * @param int $i current depth
     * @param array $objects array of seen objects
     *
     * @return string
     */
    public static function displayArgument($variable, $strlen = 80, $width = 50, $depth = 2, $i = 0, $objects = [])
    {
        $search = ["\0", "\a", "\b", "\f", "\n", "\r", "\t", "\v"];
        $replace = ['\0', '\a', '\b', '\f', '\n', '\r', '\t', '\v'];

        switch (gettype($variable)) {
            case 'boolean':
                return $variable ? 'true' : 'false';
            case 'integer':
            case 'double':
                return (string)$variable;
            case 'resource':
                return '[resource]';
            case 'NULL':
                return 'null';
            case 'unknown type':
                return '???';
            case 'string':
                $len = strlen($variable);
                $variable = str_replace($search, $replace, substr($variable,0,$strlen));
                $variable = substr($variable,0, $strlen);
                if ($len<$strlen) {
                    return '"'.$variable.'"';
                } else {
                    return 'string('.$len.'): "'.$variable.'"...';
                }
            case 'array':
                $len = count($variable);
                if ($i == $depth) {
                    return 'array('.$len.') [...]';
                }
                if (!$len) {
                    return 'array(0) []';
                }
                $string = '';
                $keys = array_keys($variable);
                $spaces = str_repeat(' ',$i*2);
                $string.= "array($len)\n".$spaces.'[';
                $count=0;
                foreach($keys as $key) {
                    if ($count==$width) {
                        $string.= "\n".$spaces."  ...";
                        break;
                    }
                    $string.= "\n".$spaces."  [$key] => ";
                    if (static::isSensitiveParameter($key)) {
                        $string .= static::displayArgument('*******', $strlen, $width, $depth,$i+1, $objects);
                    } else {
                        $string .= static::displayArgument($variable[$key], $strlen, $width, $depth, $i + 1, $objects);
                    }
                    $count++;
                }
                $string.="\n".$spaces.']';
                return $string;
            case 'object':
                $id = array_search($variable, $objects,true);
                if ($id !== false) {
                    return get_class($variable) . '#' . ($id + 1) . ' {...}';
                }
                if ($i==$depth) {
                    return get_class($variable).' {...}';
                }
                $string = '';
                $id = array_push($objects, $variable);
                $array = (array)$variable;
                $spaces = str_repeat(' ',$i*2);
                $string.= get_class($variable)."#$id\n".$spaces.'{';
                $properties = array_keys($array);
                foreach($properties as $property) {
                    $value = $array[$property];
                    $name = preg_replace("/[^a-zA-Z0-9_]/",'', trim($property));
                    $string .= "\n".$spaces."  [$name] => ";
                    if (static::isSensitiveParameter($name)) {
                        $string .= static::displayArgument('*******', $strlen, $width, $depth,$i+1, $objects);
                    } else {
                        $string .= static::displayArgument($value, $strlen, $width, $depth,$i+1, $objects);
                    }
                }
                $string .= "\n".$spaces.'}';
                return $string;
            default:
                return print_r($variable, true);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    protected static function isSensitiveParameter($name)
    {
        $name = strtolower($name ?? '');
        $sensitive = [
            'passwd',
            'password',
            'secret',
            'salt',
            'sensitive',
            'securekey'
        ];
        if (in_array($name, $sensitive)) {
            return true;
        }
        if (in_array(preg_replace('/[^a-z]/', '', $name), $sensitive)) {
            return true;
        }
        return false;
    }

    /**
     * Returns file path relative to thirtybees root
     *
     * @param string|null $file
     * @return string
     */
    public static function getRelativeFile($file)
    {
        if ($file) {
            return ltrim(str_replace([_PS_ROOT_DIR_, '\\'], ['', '/'], $file), '/');
        } else {
            return '';
        }
    }

    /**
     * Helper method to downplay some entries from stacktrace. Some entries from stacktrace
     * will be greyed out, and displayed with smaller font, so it does not distract reader
     * when investigating source of error
     *
     * @param string $relativePath relative path of file
     * @param string $class current classname
     * @param string $function currently evaluating function
     * @param string $calledClass class that's being called
     * @param string $calledFunction function being called
     *
     * @return bool if this entry should be suppressed
     */
    protected static function isSuppressed($relativePath, $class, $function, $calledClass, $calledFunction)
    {
        // suppress any entries that calls following methods
        $suppressCalls = [
            [ 'DispatcherCore',  'dispatch' ],
            [ 'Smarty_Custom_Template', 'fetch' ],
            [ 'ControllerCore', 'run' ]
        ];
        foreach ($suppressCalls as $callable) {
            if ($callable[0] === $calledClass && $callable[1] === $calledFunction) {
                return true;
            }
        }

        // suppress these methods
        $suppressMethods = [
            [ 'DispatcherCore',  'dispatch' ],
            [ 'DbCore', 'execute' ],
            [ 'DbCore', 'query' ],
            [ 'Smarty_Custom_Template', 'fetch' ],
            [ 'ControllerCore', 'run' ],
            [ 'HookCore', 'exec' ],
            [ 'HookCore', 'execWithoutCache' ],
            [ 'HookCore', 'coreCallHook' ]
        ];
        foreach ($suppressMethods as $callable) {
            if ($callable[0] === $class && $callable[1] === $function) {
                return true;
            }
        }

        // suppress any entries if filepath starts with following substring
        $paths = [
            'vendor/',
            'classes/SmartyCustom.php',
            'config/smarty'
        ];
        foreach ($paths as $match) {
            if (strpos($relativePath, $match) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to describe special functions in thirtybees codebase, such as
     * method to include sub-template from within smarty, or smarty function to trigger
     * hook.
     *
     * This makes the stacktrace more readable
     *
     * @param string $class class name
     * @param string $function called function
     * @param array $args parameters passed to $class::$function() method
     *
     * @return string | null
     */
    protected static function describeOperation($class, $function, $args)
    {
        if ($class === 'Smarty_Internal_Template' && $function === 'getSubTemplate') {
            $templateName = isset($args['0']) && is_string($args['0']) ? static::getRelativeFile($args['0']) : '';
            return 'Include sub-template <b>' . $templateName . '</b>';
        }
        if (!$class && $function === 'smartyHook') {
            $hookName = $args[0]['h'] ?? '';
            return 'Execute hook <b>' . $hookName . '</b>';
        }
        return null;
    }


    /**
     * Reads $file from disk, and returns $total lines around $line. Result is an array
     * of arrays, with information about line number in file, if the line is highlighted,
     * and actual line
     *
     * @param string $file input file
     * @param int $line index of line in the file. This line will be highlighted
     * @param int $total total number of lines to read. Pass zero to return all lines
     *
     * @return array
     */
    protected static function readFile($file, $line, $total) {
        $ret = [];
        if (! file_exists($file)) {
            return $ret;
        }
        $lines = file($file);
        if ($lines) {
            if ($total > 0) {
                $third = (int)($total / 3);
                $offset = $line - (2 * $third);
                if ($offset < 0) {
                    $offset = 0;
                }
                $lines = array_slice($lines, $offset, $total);
            } else {
                $offset = 0;
            }

            foreach ($lines as $k => $l) {
                $number = $offset + $k + 1;
                $ret[] = [
                    'number' => $number,
                    'highlighted' => $number === $line,
                    'line' => $l
                ];
            }
        }
        return $ret;
    }

}
