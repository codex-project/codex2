<?php
namespace Codex\Codex\Filters;

use Codex\Codex\Document;
use Codex\Codex\Filter;
use Parsedown;

class ParsedownFilter implements Filter
{
    protected $parsedown;

    public function __construct(Parsedown $parsedown)
    {
        $this->parsedown = $parsedown;
    }

    /**
     * Handle the filter.
     *
     * @param \Codex\Codex\Document $document
     * @return array
     * @internal param \ParsedownExtra $parsedown
     */
    public function handle(Document $document, array $config)
    {
        $document->setContent($this->parsedown->text($document->getContent()));
    }
}
