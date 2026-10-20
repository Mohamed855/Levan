<?php

declare(strict_types=1);

namespace App\Services\Shared\Pdf;

use RuntimeException;

final class PdfFormWidgetLocator
{
    /**
     * @return array<string, list<array{page: int, rect: array{float, float, float, float}, page_size: array{float, float}}>>
     */
    public function locate(string $pdfPath): array
    {
        $objects = $this->objectsFromPath($pdfPath);
        $widgets = [];
        $page = 0;

        foreach ($objects as $object) {
            if (! preg_match('/\/Type\s*\/Page\b/', $object)) {
                continue;
            }

            $page++;
            $pageSize = $this->pageSize($object);

            foreach ($this->annotationReferences($object) as $reference) {
                $widget = $objects[$reference] ?? null;
                if ($widget === null || ! preg_match('/\/Subtype\s*\/Widget\b/', $widget)) {
                    continue;
                }

                $name = $this->fieldName($widget, $objects);
                $rect = $this->rectangle($widget);

                if ($name === null || $rect === null) {
                    continue;
                }

                $widgets[$name][] = [
                    'page' => $page,
                    'rect' => $rect,
                    'page_size' => $pageSize,
                ];
            }
        }

        return $widgets;
    }

    /** @return list<array{float, float}> */
    public function pageSizes(string $pdfPath): array
    {
        $pages = [];

        foreach ($this->objectsFromPath($pdfPath) as $object) {
            if (preg_match('/\/Type\s*\/Page\b/', $object)) {
                $pages[] = $this->pageSize($object);
            }
        }

        return $pages;
    }

    /** @return array<int, string> */
    private function objectsFromPath(string $pdfPath): array
    {
        $contents = file_get_contents($pdfPath);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the PDF form structure.');
        }

        return $this->objects($contents);
    }

    /** @return array<int, string> */
    private function objects(string $contents): array
    {
        preg_match_all('/(\d+)\s+\d+\s+obj\s*(.*?)\s*endobj/s', $contents, $matches, PREG_SET_ORDER);

        $objects = [];
        foreach ($matches as $match) {
            $objects[(int) $match[1]] = $match[2];
        }

        return $objects;
    }

    /** @return list<int> */
    private function annotationReferences(string $page): array
    {
        if (! preg_match('/\/Annots\s*\[(.*?)\]/s', $page, $match)) {
            return [];
        }

        preg_match_all('/(\d+)\s+\d+\s+R/', $match[1], $references);

        return array_map('intval', $references[1]);
    }

    /** @param array<int, string> $objects */
    private function fieldName(string $widget, array $objects): ?string
    {
        if (preg_match('/\/T\s*\(([^)]*)\)/s', $widget, $match)) {
            return $this->decodePdfString($match[1]);
        }

        if (! preg_match('/\/Parent\s+(\d+)\s+\d+\s+R/', $widget, $match)) {
            return null;
        }

        $parent = $objects[(int) $match[1]] ?? null;

        return $parent === null ? null : $this->fieldName($parent, $objects);
    }

    /** @return array{float, float, float, float}|null */
    private function rectangle(string $widget): ?array
    {
        if (! preg_match('/\/Rect\s*\[\s*([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s*\]/', $widget, $match)) {
            return null;
        }

        return array_map('floatval', array_slice($match, 1));
    }

    /** @return array{float, float} */
    private function pageSize(string $page): array
    {
        if (! preg_match('/\/(?:CropBox|MediaBox)\s*\[\s*([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s*\]/', $page, $match)) {
            throw new RuntimeException('A PDF page containing image fields must define a page box.');
        }

        return [
            abs((float) $match[3] - (float) $match[1]),
            abs((float) $match[4] - (float) $match[2]),
        ];
    }

    private function decodePdfString(string $value): string
    {
        return preg_replace('/\\\\([()\\\\])/', '$1', $value) ?? $value;
    }
}
