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

        $pattern = '/<!---([\w\W]*?)-->/';
        if ( preg_match($pattern, $content, $matches) === 1 )
        {
            $content = preg_replace($pattern, '', $content); // not really required when using html doc tags. But in case it's frontmatter, it should be removed
            $attributes = array_merge_recursive($document->getAttributes(), Yaml::parse($matches[1]));
            $document->setAttributes($attributes);

           # $content[ 'frontmatter' ] = Yaml::parse($matches[ 2 ]);
           # $content[ 'body' ]        = $matches[ 4 ];
        }

        $document->setContent($content);
    }
}
