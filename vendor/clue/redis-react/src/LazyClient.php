<?php

namespace Clue\React\Redis;

use Evenement\EventEmitter;
use React\Stream\Util;
use React\EventLoop\LoopInterface;

/**
 * @internal
 */
class LazyClient extends EventEmitter implements Client
{
    private $target;
    /** @var Factory */
    private $factory;
    private $closed = false;
    private $promise;

    private $loop;
    private $idlePeriod = 60.0;
    private $idleTimer;
    private $pending = 0;

    private $subscribed = array();
    private $psubscribed = array();

    /**
     * @param $target
     */
    public function __construct($target, Factory $factory, LoopInterface $loop)
    {
        $args = array();
        \parse_str(\parse_url($target, \PHP_URL_QUERY), $args);
        if (isset($args['idle'])) {
            $this->idlePeriod = (float)$args['idle'];
        }

        $this->target = $target;
        $this->factory = $factory;
        $this->loop = $loop;
    }

    private function client()
    {
        if ($this->promise !== null) {
            return $this->promise;
        }

        $self = $this;
        $pending =& $this->promise;
        $idleTimer=& $this->idleTimer;
        $subscribed =& $this->subscribed;
        $psubscribed =& $this->psubscribed;
        $loop = $this->loop;
        return $pending = $this->factory->createClient($this->target)->then(function (Client $client) use ($self, &$pending, &$idleTimer, &$subscribed, &$psubscribed, $loop) {
            // connection completed => remember only until closed
            $client->on('close', function () use (&$pending, $self, &$subscribed, &$psubscribed, &$idleTimer, $loop) {
                $pending = null;

                // foward unsubscribe/punsubscribe events when underlying connection closes
                $n = count($subscribed);
                foreach ($subscribed as $channel => $_) {
                    $self->emit('unsubscribe', array($channel, --$n));
                }
                $n = count($psubscribed);
                foreach ($psubscribed as $pattern => $_) {
                    $self->emit('punsubscribe', array($pattern, --$n));
                }
                $subscribed = array();
                $psubscribed = array();

                if ($idleTimer !== null) {
                    $loop->cancelTimer($idleTimer);
                    $idleTimer = null;
                }
            });

            // keep track of all channels and patterns this connection is subscribed to
            $client->on('subscribe', function ($channel) use (&$subscribed) {
                $subscribed[$channel] = true;
            });
            $client->on('psubscribe', function ($pattern) use (&$psubscribed) {
                $psubscribed[$pattern] = true;
            });
            $client->on('unsubscribe', function ($channel) use (&$subscribed) {
                unset($subscribed[$channel]);
            });
            $client->on('punsubscribe', function ($pattern) use (&$psubscribed) {
                unset($psubscribed[$pattern]);
            });

            Util::forwardEvents(
                $client,
                $self,
                array(
                    'message',
                    'subscribe',
                    'unsubscribe',
                    'pmessage',
                    'psubscribe',
                    'punsubscribe',
                )
            );

            return $client;
        }, function (\Exception $e) use (&$pending) {
            // connection failed => discard connection attempt
            $pending = null;

            throw $e;
        });
    }

    public function __call($name, $args)
    {
        if ($this->closed) {
            return \React\Promise\reject(new \RuntimeException('Connection closed'));
        }

        $that = $this;
        return $this->client()->then(function (Client $client) use ($name, $args, $that) {
            $that->awake();
            return \call_user_func_array(array($client, $name), $args)->then(
                function ($result) use ($that) {
                    $that->idle();
                    return $result;
                },
                function ($error) use ($that) {
                    $that->idle();
                    throw $error;
                }
            );
        });
    }

    public function end()
    {
        if ($this->promise === null) {
            $this->close();
        }

        if ($this->closed) {
            return;
        }

        $that = $this;
        return $this->client()->then(function (Client $client) use ($that) {
            $client->on('close', function () use ($that) {
                $that->close();
            });
            $client->end();
        });
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        // either close active connection or cancel pending connection attempt
        if ($this->promise !== null) {
            $this->promise->then(function (Client $client) {
                $client->close();
            });
            if ($this->promise !== null) {
                $this->promise->cancel();
                $this->promise = null;
            }
        }

        if ($this->idleTimer !== null) {
            $this->loop->cancelTimer($this->idleTimer);
            $this->idleTimer = null;
        }

        $this->emit('close');
        $this->removeAllListeners();
    }

    /**
     * @internal
     */
    public function awake()
    {
        ++$this->pending;

        if ($this->idleTimer !== null) {
            $this->loop->cancelTimer($this->idleTimer);
            $this->idleTimer = null;
        }
    }

    /**
     * @internal
     */
    public function idle()
    {
        --$this->pending;

        if ($this->pending < 1 && $this->idlePeriod >= 0 && !$this->subscribed && !$this->psubscribed) {
            $idleTimer =& $this->idleTimer;
            $promise =& $this->promise;
            $idleTimer = $this->loop->addTimer($this->idlePeriod, function () use (&$idleTimer, &$promise) {
                $promise->then(function (Client $client) {
                    $client->close();
                });
                $promise = null;
                $idleTimer = null;
            });
        }
    }
}
