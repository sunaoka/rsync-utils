<?php

declare(strict_types=1);

namespace Sunaoka\RsyncUtils;

class ItemizeChangesParser
{
    /**
     * @param string $lines
     *
     * @return RsyncFileObject[]
     */
    public function parse(string $lines): array
    {
        $result = [];
        foreach ($this->readLines($lines) as $line) {
            $file = new RsyncFileObject($line);
            if ($file->isSkipped() === false) {
                $result[] = $file;
            }
        }

        return $result;
    }

    private function readLines(string $lines): array
    {
        $lines = trim($lines);
        $lines = str_replace(["\r\n", "\r"], "\n", $lines);

        return array_filter(explode("\n", $lines));
    }
}
