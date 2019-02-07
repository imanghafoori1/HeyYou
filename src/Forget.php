<?php

namespace Imanghafoori\HeyMan;

use Imanghafoori\HeyMan\Normilizers\InputNormalizer;
use Imanghafoori\HeyMan\WatchingStrategies\Events\EventListeners;
use Imanghafoori\HeyMan\WatchingStrategies\Routes\RouteNormalizer;
use Imanghafoori\HeyMan\WatchingStrategies\Views\ViewEventListener;
use Imanghafoori\HeyMan\WatchingStrategies\Routes\RouteEventListener;
use Imanghafoori\HeyMan\WatchingStrategies\EloquentModels\EloquentEventsListener;

/**
 * Class Forget.
 *
 * @method aboutRoute(array|string $routeName)
 * @method aboutAction(array|string $action)
 * @method aboutUrl(array|string $url)
 * @method aboutModel(array|string $model)
 * @method aboutDeleting(array|string $model)
 * @method aboutSaving(array|string $model)
 * @method aboutCreating(array|string $model)
 * @method aboutUpdating(array|string $model)
 * @method aboutFetching(array|string $model)
 */
final class Forget
{
    use InputNormalizer;

    public function aboutView(...$view)
    {
        resolve('heyman.chains')->forgetAbout(ViewEventListener::class, $view);
    }

    public function aboutEvent(...$events)
    {
        resolve('heyman.chains')->forgetAbout(EventListeners::class, $events);
    }

    public function __call($method, $args)
    {
        $args = $this->normalizeInput($args);

        $method = ltrim($method, 'about');

        if (in_array($method, ['Route', 'Action', 'Url'])) {
            $args = resolve(RouteNormalizer::class)->{'normalize'.$method}($args);

            return resolve('heyman.chains')->forgetAbout(RouteEventListener::class, $args);
        }

        $method = str_replace('Fetching', 'retrieved', $method);
        $method = strtolower($method);
        $method = $method == 'model' ? null : $method;
        resolve('heyman.chains')->forgetAbout(EloquentEventsListener::class, $args, $method);
    }
}
