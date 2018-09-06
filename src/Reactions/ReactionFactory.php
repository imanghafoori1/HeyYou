<?php

namespace Imanghafoori\HeyMan\Reactions;

use Imanghafoori\HeyMan\Chain;

final class ReactionFactory
{
    /**
     * @return \Closure
     */
    public function make(): \Closure
    {
        $reaction = $this->makeReaction();
        $condition = resolve(Chain::class)->condition;

        return function (...$f) use ($condition, $reaction) {
            if (!$condition($f)) {
                $reaction();
            }
        };
    }

    private function makeReaction(): \Closure
    {
        $chain = resolve(Chain::class);
        $responder = resolve(ResponderFactory::class)->make();
        $beforeResponse = $chain->beforeResponse();
        $debug = $chain->debugInfo;

        return function () use ($beforeResponse, $responder, $debug) {
            event('heyman_reaction_is_happening', $debug);
            $beforeResponse();
            $responder();
        };
    }
}
