<?php

declare(strict_types=1);

namespace setasign\SetaPDF2\Demos;

use Exception;

/**
 * Class SitemapBuilder
 *
 * This class is not directly related to the demos themselves but generates the sitemap.xml for demos.setasign.com.
 *
 * @phpstan-type SitemapEntry array{
 *      loc: string,
 *      lastmod:int|\DateTimeInterface|string,
 *   }
 */
class SitemapBuilder
{
    /**
     * @var string
     */
    private $publicDirectory;

    /**
     * @var null|string
     */
    private $cacheFile;

    public function __construct(string $publicDirectory, ?string $cacheFile = null)
    {
        $this->publicDirectory = $publicDirectory;
        $this->cacheFile = $cacheFile;
    }

    public function build(): string
    {
        $now = new \DateTime();
        if ($this->cacheFile !== null && \file_exists($this->cacheFile)) {
            return \file_get_contents($this->cacheFile);
        }

        $urls = $this->formatSitemapEntries($this->getSitemapEntries());

        $result = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
    <!-- Last update: {$now->format('c')} -->
$urls
</urlset>
XML;

        if ($this->cacheFile !== null) {
            \file_put_contents($this->cacheFile, $result);
        }

        return $result;
    }

    /**
     * @return SitemapEntry
     */
    protected function getSitemapEntries(): array
    {
        $result = [];
        foreach ($this->getSitemapEntriesInDirectory($this->publicDirectory . '/demos', '/', $lastMod) as $entry) {
            $result[] = $entry;
        }

        \array_unshift($result, [
            'loc' => '/',
            'lastmod' => \max(
                \filemtime($this->publicDirectory . '/index.php'),
                $lastMod
            ),
        ]);

        return $result;
    }

    /**
     * @param-out int $lastMod
     * @return \Generator<SitemapEntry>
     */
    protected function getSitemapEntriesInDirectory(string $directory, string $path, &$lastMod): \Generator
    {
        $lastModdedFiles = [];

        if (\file_exists($directory . '/description.html')) {
            $lastModdedFiles[] = $directory . '/description.html';
        }

        $demoDirs = \glob($directory . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        \usort($demoDirs, 'sortDemoPaths');
        foreach ($demoDirs as $demoDir) {
            if (\file_exists($demoDir . '/demo.json')) {
                continue;
            }

            if (\file_exists($demoDir . '/meta.json')) {
                $lastModdedFiles[] = $demoDir . '/meta.json';
            }

            $currentPath = $path . \basename($demoDir) . '/';
            yield from $this->getSitemapEntriesInDirectory($demoDir, $currentPath, $currentLastMod);
            yield [
                'loc' => $currentPath,
                'lastmod' => $currentLastMod
            ];
        }

        $demoPaths = \glob($directory . '/*/demo.json', GLOB_NOSORT);
        \usort($demoPaths, 'sortDemoPaths');
        foreach ($demoPaths as $actualDemo) {
            $demoDir = \dirname($actualDemo);
            $metaData = \json_decode(\file_get_contents($actualDemo), true);

            $lastModdedFiles[] = $actualDemo;

            $currentPath = $path . \basename($demoDir) . '/';
            $demoLastModdedFiles = [
                $actualDemo,
                $demoDir . '/script.php'
            ];
            if (\file_exists($demoDir . '/description.html')) {
                $demoLastModdedFiles[] = $demoDir . '/description.html';
            }

            if (isset($metaData['previewFiles'])) {
                foreach ($metaData['previewFiles'] as $previewFile) {
                    if (\file_exists($demoDir . $previewFile)) {
                        $demoLastModdedFiles[] = $demoDir . $previewFile;
                    }
                }
            }

            yield [
                'loc' => $currentPath,
                'lastmod' => \max(\array_map('\filemtime', $demoLastModdedFiles))
            ];
        }

        if (\count($lastModdedFiles) === 0) {
            return;
        }

        $lastMod = \max(\array_map('\filemtime', $lastModdedFiles));
    }

    /**
     * @param SitemapEntry[] $siteMapEntries
     * @return string
     * @throws Exception
     */
    protected function formatSitemapEntries(array $siteMapEntries): string
    {
        \usort($siteMapEntries, function ($a, $b) {
            return $a['loc'] <=> $b['loc'];
        });

        $basePath = 'https://demos.setasign.com';
        $now = (new \DateTime())->format('c');
        $result = [];
        foreach ($siteMapEntries as $siteMapEntry) {
            if (isset($siteMapEntry['lastmod'])) {
                if ($siteMapEntry['lastmod'] instanceof \DateTimeInterface) {
                    $siteMapEntry['lastmod'] = $siteMapEntry['lastmod']->format('c');
                } elseif (\is_int($siteMapEntry['lastmod'])) {
                    $siteMapEntry['lastmod'] = (new \DateTime('@' . $siteMapEntry['lastmod']))->format('c');
                } else {
                    $siteMapEntry['lastmod'] = (new \DateTime($siteMapEntry['lastmod']))->format('c');
                }
            }

            if (!isset($siteMapEntry['lastmod'])) {
                $siteMapEntry['lastmod'] = $now;
            }

            $result[] = "\t<url>\n"
                . "\t\t<loc>" . $basePath . \htmlentities($siteMapEntry['loc']) . "</loc>\n"
                . "\t\t<lastmod>" . $siteMapEntry['lastmod'] . "</lastmod>\n"
                . "\t</url>";
        }
        return \implode("\n", $result);
    }
}
