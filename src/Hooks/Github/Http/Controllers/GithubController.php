<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Codex\Codex\Hooks\Github\Http\Controllers;

use Codex\Codex\Factory;
use GitHub;
use Illuminate\Routing\Controller as BaseController;
use Input;
use Request;

/**
 * Class GithubController
 *
 * @package     Laradic\Docit\Http\Controllers
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class GithubController extends BaseController
{

    protected $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function webhook($type)
    {
        $types = [ 'push' ];
        if ( ! in_array($type, $types) )
        {
            return response('', 403);
        }
        $headers = [
            'delivery'   => Request::header('x-github-delivery'),
            'event'      => Request::header('x-github-event'),
            'user-agent' => Request::header('user-agent'),
            'signature'  => Request::header('x-hub-signature')
        ];
        $payload = Input::all();
        $repo    = strtolower($payload[ 'repository' ][ 'full_name' ]);

        foreach ( $this->factory->all() as $name => $config )
        {
            $project = $this->factory->make($name);
            if ( ! $project->config('use_github') )
            {
                continue;
            }

            $config      = $project->config('github_settings');
            $projectRepo = $config[ 'github_settings' ][ 'owner' ] . '/' . $config[ 'github_settings' ][ 'repository' ];

            if ( $repo !== $projectRepo )
            {
                continue;
            }

            $hash = hash_hmac('sha1', file_get_contents("php://input"), $project->config('github_settings.webhook_secret'));


            if ( $headers[ 'signature' ] === "sha1=$hash" )
            {
                #$project->gitsync()->syncAll();
                \Queue::push('Docit\Core\Commands\DocitSyncGithubProject', [ 'project' => $project->getName() ]);

                return response('', 200);
            }
            else
            {
                return response('Invalid hash', 403);
            }
        }

        return response('', 500);
    }
}
