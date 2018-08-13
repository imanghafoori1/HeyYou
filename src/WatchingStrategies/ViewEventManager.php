<?php

namespace Imanghafoori\HeyMan\WatchingStrategies;

use Imanghafoori\HeyMan\HeyManSwitcher;

class ViewEventManager
{
    private $views = [];

    private $data = [];

    /**
     * ViewEventManager constructor.
     *
     * @param $views
     *
     * @return ViewEventManager
     */
    public function init(array $views): self
    {
        $this->views = $views;

        return $this;
    }

    /**
     * @param $listener
     */
    public function commitChain(callable $listener)
    {
        $switchableListener = app(HeyManSwitcher::class)->wrapForIgnorance($listener, 'view');
        $views = $this->views;
        foreach ($views as $view) {
            $this->data[$view][] = $switchableListener;
        }
    }

    public function start()
    {
        foreach ($this->data as $view => $callbacks) {
            foreach ($callbacks as $cb) {
                view()->creator($view, $cb);
            }
        }
    }
}
