<?php
/**
 * Part of Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.org
 */
namespace Codex\Codex\Hooks\Github\Console;


use Codex\Codex\Factory;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Laradic\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * This is the CoreListCommand class.
 *
 * @package                   Docit\Core
 * @version                   1.0.0
 * @author                    Robin Radic
 * @license                   MIT License
 * @copyright                 2015, Robin Radic
 * @link                      https://github.com/robinradic
 */
class CodexSyncGithubCommand extends Command
{

    use DispatchesJobs;

    protected $name = 'codex:sync-github';

    protected $description = 'Synchronise all Github projects.';

    /** @var \Codex\Codex\Factory */
    protected $factory;


    public function __construct(Factory $factory)
    {
        parent::__construct();
        $this->factory = $factory;
    }

    public function fire()
    {
        if ( ! $project = $this->argument('project') )
        {
            $githubProjects = [ ];
            $choices        = [ ];
            foreach ( $this->factory->all() as $name => $config )
            {
                if ( $config[ 'use_github' ] )
                {
                    $project          = $this->factory->make($name);
                    $githubProjects[] = $project;
                    $choices[]        = $project->getName();
                }
            }
            $choice  = $this->select('Pick the github enabled project you wish to sync', $choices);
            $project = $choice;
        }
        if ( $this->option('queue') )
        {
            #$this->dispatch(new DocitSyncGithubProject($project));

            \Queue::push('Codex\Codex\Hooks\Github\Commands\CodexSyncGithubProject', [ 'project' => $project ]);
            #return $this->error('Queue Not implemented yet' ); //'Github sync command added to the queue');
        }
        else
        {
            $project = $this->factory->make($project);
            $total   = count($project->github()->getBranchesToSync()) + count($project->github()->getVersionsToSync());
            if ( $total === 0 )
            {
                return $this->info('Nothing to sync. Everything is up-to-date');
            }
            $this->output->progressStart($total);
            $that = $this;
            $project->github()->syncWithProgress(function ($current) use ($that)
            {
                $that->output->progressAdvance();
            });
            $this->output->progressFinish();
            $this->info('Synchronised ' . $total . ' versions/branches');
        }
    }

    public function getOptions()
    {
        return [
            [ 'queue', 'Q', InputOption::VALUE_NONE, 'The stuff' ]
        ];
    }

    public function getArguments()
    {
        return [
            [ 'project', InputArgument::OPTIONAL, 'The project you want to sync' ]
        ];
    }
}
