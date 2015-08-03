<?php
namespace Codex\Codex\Filters;

use Codex\Codex\Document;
use Codex\Codex\Hook;
use Symfony\Component\Yaml\Yaml;

class FrontMatterFilter implements Hook
{
    /**
     * Handle the filter.
     *
     * @param \Codex\Codex\Document $document
     * @return array
     */
    public function handle(Document $document)
    {
        $content = $document->getContent();
        $regex = '~^(' . implode('|', array_map('preg_quote', [ '---' ]))
            . "){1}[\r\n|\n]*(.*?)[\r\n|\n]+("
            . implode('|', array_map('preg_quote', [ '---' ]))
            . "){1}[\r\n|\n]*(.*)$~s";
        if ( preg_match($regex, $content, $matches) === 1 )
        {
            $content[ 'frontmatter' ] = Yaml::parse($matches[ 2 ]);
            $content[ 'body' ]        = $matches[ 4 ];
        }

        $document->setContent($content);
    }
}
