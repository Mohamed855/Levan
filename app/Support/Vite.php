<?php

namespace App\Support;

use Illuminate\Foundation\Vite as BaseVite;

class Vite extends BaseVite
{
    protected function makeTagForChunk($src, $url, $chunk, $manifest)
    {
        if ($this->isRunningHot() && $this->isCssPath($url)) {
            return $this->makeStylesheetTagWithAttributes(
                $url . '?direct',
                $this->resolveStylesheetTagAttributes($src, $url, $chunk, $manifest)
            );
        }

        return parent::makeTagForChunk($src, $url, $chunk, $manifest);
    }
}
