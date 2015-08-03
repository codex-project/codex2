# RADICWORKS codex branch

##### You're probably at the wrong place. This is secret high tech stuff, move along.



### Overview

#### Hooks

`CodexHookProvider` trait gives a class the `addCodexHook($hookPoint, $handler)` method.
Suggested is to do so in a `ServiceProvider` its `register` function, something like CodexServiceProvider is doing.

```php
protected function registerFilters()
{
    $this->addCodexHook('document:render', FrontMatterFilter::class);
    $this->addCodexHook('document:render', ParsedownFilter::class);
}
```
There are several `$hookPoint`s. An important one is `document:render`, which is called when the documents content is outputted.

You could either pass a `\Closure` as `$handler` parameter, or the FQN of a `Hook` class. For example:

```php
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
```

The `handle` method will receive arguments provided by the hook whilst the `constructor` can be used for dependency injection.


Hook points can be implemented everywhere and at any time. 

Hooks will allow to add, alter and remove all kinds of foreseeable features.
 


#### Factory, Project and Document
I left out quite some things from all classes. But my idea is to make Factory a Macroable class. That way hooks can easily add addional methods to the factory class.
 The factory class is bound on `codex`. It manages all hooks as well.
 
##### Usage

Basically, using the the classes to get a markdown document goes something like
```php
app()->register(\Codex\Codex\CodexServiceProvider::class);
$factory  = app('codex');                   // codex is a binding for factory
$project  = $factory->make('themes');       // themes here is a project name
$git      = $project->github();             // can use the github method, added by the hook utilizing the macroable trait
$document = $project->getDocument('index'); //get a markdown document (no ext needed)
$output   = $document->render();            // render it
```

Other stuff that can be done:
```php
app()->register(\Codex\Codex\CodexServiceProvider::class);
$factory  = app('codex');                   // codex is a binding for factory
$project  = $factory->make('themes');       // themes here is a project name
$sortedVersionList = $project->getSortedRefs(); // Sorted version list, usefull for a dropdown 
$project->setRef('1.2.0');                  // Select a different version
$document = $project->getDocument('index'); // get a markdown document (for 1.2.0)
$output   = $document->render();            // Render it
$attribs  = $document->getAttributes();     // The frontmatter yaml stuff as php array 
```
