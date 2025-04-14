<?php

namespace Markfolio;

use League\CommonMark\CommonMarkConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class PageParser
{
    protected $document;

    public function __construct(public string $path)
    {
        $this->document = YamlFrontMatter::parseFile($path);
    }

    public function toViewData(): array
    {
        $html = (new CommonMarkConverter)->convertToHtml($this->document->body());
        $matter = $this->document->matter();

        return [
            'title' => $matter['title'] ?? null,
            'content' => $html,
            'meta' => $matter,
            'layout' => $matter['layout'] ?? config('markfolio.default_layout'),
        ];
    }
}
