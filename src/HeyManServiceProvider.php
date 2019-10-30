<?php

namespace Imanghafoori\HeyMan;

use Illuminate\Support\Facades\Gate;
use Imanghafoori\HeyMan\Core\Forget;
use Illuminate\Foundation\AliasLoader;
use Imanghafoori\HeyMan\Core\Reaction;
use Illuminate\Support\ServiceProvider;
use Imanghafoori\HeyMan\Facades\HeyMan;
use Imanghafoori\HeyMan\Boot\Singletons;
use Imanghafoori\HeyMan\Core\Situations;
use Imanghafoori\HeyMan\Switching\Consider;
use Imanghafoori\HeyMan\Reactions\Reactions;
use Imanghafoori\HeyMan\Core\ConditionsFacade;
use Imanghafoori\HeyMan\Boot\DebugbarIntergrator;
use Imanghafoori\HeyMan\Plugins\Conditions\Callbacks;
use Imanghafoori\HeyMan\Plugins\Conditions\Authentication;
use Imanghafoori\HeyMan\Plugins\Conditions\Gate as myGate;
use Imanghafoori\HeyMan\Plugins\Conditions\Session as mySession;
use Imanghafoori\HeyMan\Plugins\WatchingStrategies\Routes\RouteActionProvider;
use Imanghafoori\HeyMan\Plugins\WatchingStrategies\Views\ViewSituationProvider;
use Imanghafoori\HeyMan\Plugins\WatchingStrategies\Events\EventSituationProvider;
use Imanghafoori\HeyMan\Plugins\WatchingStrategies\Routes\RouteUrlSituationProvider;
use Imanghafoori\HeyMan\Plugins\WatchingStrategies\Routes\RouteNameSituationProvider;
use Imanghafoori\HeyMan\Plugins\WatchingStrategies\EloquentModels\EloquentSituationProvider;

final class HeyManServiceProvider extends ServiceProvider
{
    public static $situationProviders = [
        ViewSituationProvider::class,
        RouteNameSituationProvider::class,
        RouteUrlSituationProvider::class,
        RouteActionProvider::class,
        EventSituationProvider::class,
        EloquentSituationProvider::class,
    ];

    public function boot()
    {
        app()->booted([resolve(StartGuarding::class), 'start']);

        DebugbarIntergrator::register();

        $this->disableIfIsSeeding();
    }

    public function register()
    {
        Forget::$situation_providers = static::$situationProviders;
        Singletons::make($this->app);
        $this->defineGates();
        $this->registerConditions();
        $this->registerSituationProviders(static::$situationProviders);
        $this->defineReactions();

        AliasLoader::getInstance()->alias('HeyMan', HeyMan::class);

        $this->mergeConfigFrom(__DIR__.'/../config/heyMan.php', 'heyMan');
    }

    private function defineGates()
    {
        Gate::define('heyman.youShouldHaveRole', function ($user, $role) {
            return $user->role == $role;
        });
    }

    private function disableIfIsSeeding()
    {
        if (isset(\Request::server('argv')[1]) && \Request::server('argv')[1] == 'db:seed') {
            HeyMan::turnOff()->eloquentChecks();
        }
    }

    private function registerConditions()
    {
        $cond = app(ConditionsFacade::class);
        $cond->define('youShouldBeGuest', Authentication::class.'@beGuest');
        $cond->define('youShouldBeLoggedIn', Authentication::class.'@loggedIn');

        $cond->define('thisClosureShouldAllow', Callbacks::class.'@closureAllows');
        $cond->define('thisMethodShouldAllow', Callbacks::class.'@methodAllows');
        $cond->define('thisValueShouldAllow', Callbacks::class.'@valueAllows');

        $cond->define('thisGateShouldAllow', myGate::class.'@thisGateShouldAllow');
        $cond->define('youShouldHaveRole', myGate::class.'@youShouldHaveRole');

        $cond->define('sessionShouldHave', mySession::class.'@sessionHas');
    }

    private function registerSituationProviders($providers)
    {
        foreach ($providers as $provider) {
            $provider = new $provider;
            $listener = $provider->getListener();
            $situation = $provider->getSituationProvider();

            app()->singleton($listener);
            app()->singleton($situation);

            Consider::add($provider->getForgetKey(), $listener);
            Situations::add($listener, $situation, $provider->getMethods());
        }
    }

    private function defineReactions()
    {
        $reaction = resolve(Reaction::class);

        $reaction->define('response', Reactions::class.'@response');
        $reaction->define('redirect', Reactions::class.'@redirect');
        $reaction->define('weThrowNew', Reactions::class.'@weThrowNew');
        $reaction->define('abort', Reactions::class.'@abort');
        $reaction->define('weRespondFrom', Reactions::class.'@weRespondFrom');
        $reaction->define('weDenyAccess', Reactions::class.'@weDenyAccess');
        $reaction->define('afterCalling', Reactions::class.'@afterCalling');
        $reaction->define('afterFiringEvent', Reactions::class.'@afterFiringEvent');
    }
}
