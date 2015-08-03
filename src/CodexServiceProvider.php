<?php
/**
 * Part of  Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.org
 */
namespace Codex\Codex;

use Caffeinated\Beverage\ServiceProvider;
use Codex\Codex\Filters\FrontMatterFilter;
use Codex\Codex\Filters\ParsedownFilter;
use Codex\Codex\Hooks\Github\GithubHookServiceProvider;
use Codex\Codex\Hooks\Filesystems\FilesystemsHookServiceProvider;
use Codex\Codex\Traits\CodexHookProvider;

/**
 * {@inheritdoc}
 */
class CodexServiceProvider extends ServiceProvider
{
    use CodexHookProvider;

    protected $dir = __DIR__;

    protected $configFiles = [ 'codex' ];

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $app = parent::boot();
        /** @var Factory $factory */
        $factory = $this->app->make('codex');
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $app = parent::register();
        $this->app->singleton('codex', 'Codex\Codex\Factory');

        $this->registerFilters();
        $this->registerGithub();
        $this->registerFilesystems();
    }

    protected function registerFilters()
    {
        $this->addCodexHook('document:render', FrontMatterFilter::class);
        $this->addCodexHook('document:render', ParsedownFilter::class);
    }

    protected function registerGithub()
    {
        $this->app->register(GithubHookServiceProvider::class);
    }

    protected function registerFilesystems()
    {
        $this->app->register(FilesystemsHookServiceProvider::class);
    }
}
