<?php

declare(strict_types=1);

namespace setasign\SetaPDF2\Demos;

use setasign\PhpSyntaxHighlighter\Manuals\ManualLinkBuilderInterface;

/**
 * Class ManualsLinkBuilder
 *
 * Used for linking the classes to manuals.setasign.com
 */
class ManualsLinkBuilder implements ManualLinkBuilderInterface
{
    private const MANUAL_LINK = 'https://manuals.setasign.com';

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var null|array
     */
    private $apiData = null;

    public function __construct(string $cacheFile) {
        if (!\is_dir(\dirname($cacheFile))) {
            throw new \InvalidArgumentException('Cache directory does not exist');
        }
        $this->cacheFile = $cacheFile;
    }

    private function getApiData()
    {
        if (\is_array($this->apiData)) {
            return $this->apiData;
        }

        if (\file_exists($this->cacheFile) && (\filectime($this->cacheFile) + 60 * 60 * 24) > \time()) {
            $this->apiData = \json_decode(\file_get_contents($this->cacheFile), true);
        }

        if (!\is_array($this->apiData)) {
            $url = self::MANUAL_LINK . '/_apidoc/export';
            $result = \json_decode(\file_get_contents($url), true);
            if (!\is_array($result) || !isset($result['classes']) || !\is_array($result['aliases'])) {
                \trigger_error('Invalid API response for manual api export', E_USER_WARNING);
                $this->apiData = [];
                return $this->apiData;
            }
            $this->apiData = $result;
            \file_put_contents($this->cacheFile, \json_encode($this->apiData), \LOCK_EX);
        }
        return $this->apiData;
    }

    private function findClass(string $className)
    {
        $apiData = $this->getApiData();
        if (isset($apiData['aliases'][$className])) {
            $className = $apiData['aliases'][$className];
        }
        return $apiData['classes'][$className] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getClassLink(string $className): ?string
    {
        $classData = $this->findClass($className);
        if ($classData === null) {
            return null;
        }
        return self::MANUAL_LINK . $classData['link'];
    }

    /**
     * @inheritDoc
     */
    public function getClassMethodLink(string $className, string $methodName): ?string
    {
        $classData = $this->findClass($className);
        if ($classData === null) {
            return null;
        }
        $methodData = $classData['methods'][$methodName] ?? null;
        if ($methodData === null) {
            return null;
        }

        return self::MANUAL_LINK . $classData['link'] . $methodData['link'];
    }

    /**
     * @inheritDoc
     */
    public function getClassConstantLink(string $className, string $constantName): ?string
    {
        $classData = $this->findClass($className);
        if ($classData === null) {
            return null;
        }
        $constantData = $classData['constants'][$constantName] ?? null;
        if ($constantData === null) {
            return null;
        }

        return self::MANUAL_LINK . $classData['link'] . $constantData['link'];
    }

    /**
     * @inheritDoc
     */
    public function getFunctionLink(string $functionName): ?string
    {
        switch ($functionName) {
            case 'displayFiles':
                return 'https://github.com/Setasign/SetaPDF-Demos/blob/master/bootstrap.php#L27';

            case 'displaySelect':
                return 'https://github.com/Setasign/SetaPDF-Demos/blob/master/bootstrap.php#L102';

            case 'displayText':
                return 'https://github.com/Setasign/SetaPDF-Demos/blob/master/bootstrap.php#L138';
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getFunctionReturnType(string $functionName): ?array
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMethodReturnType(string $className, string $methodName): ?array
    {
        $classData = $this->findClass($className);
        if ($classData === null) {
            return null;
        }
        $methodData = $classData['methods'][$methodName] ?? null;
        if ($methodData === null) {
            return null;
        }

        return $methodData['returnType'];
    }
}
