<?php
namespace Codex\Codex\Filters;

use Codex\Codex\Document;
use Codex\Codex\Hook;
use Parsedown;

class ParsedownFilter implements Hook
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
    public function handle(Document $document)
    {
        $document->setContent($this->parsedown->text($document->getContent()));
    }
}
