<?php
namespace Peridot\Core;

/**
 * Suites organize specs and other suites. Maps to describe() and context() style functions.
 *
 * @package Peridot\Core
 */
class Suite extends AbstractSpec
{
    /**
     * Specs belonging to this suite
     *
     * @var array
     */
    protected $specs = [];

    /**
     * Has the suite been halted
     *
     * @var bool
     */
    protected $halted = false;

    /**
     * Add a spec to the suite
     *
     * @param Spec $spec
     */
    public function addSpec(SpecInterface $spec)
    {
        $spec->setParent($this);
        $this->specs[] = $spec;
    }

    /**
     * Return collection of specs
     *
     * @return array
     */
    public function getSpecs()
    {
        return $this->specs;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $setupFn
     */
    public function addSetUpFunction(callable $setupFn)
    {
        $this->setUpFns[] = $setupFn;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $tearDownFn
     */
    public function addTearDownFunction(callable $tearDownFn)
    {
        $this->tearDownFns[] = $tearDownFn;
    }

    /**
     * Run all the specs belonging to the suite
     *
     * @param SpecResult $result
     */
    public function run(SpecResult $result)
    {
        $this->eventEmitter->emit('suite.start', [$this]);

        $this->eventEmitter->on('suite.halt', function () {
            $this->halted = true;
        });

        foreach ($this->specs as $spec) {

            if ($this->halted) {
                break;
            }

            if (!is_null($this->getPending())) {
                $spec->setPending($this->getPending());
            }

            $this->bindCallables($spec);
            $spec->setEventEmitter($this->eventEmitter);
            $spec->run($result);
        }
        $this->eventEmitter->emit('suite.end', [$this]);
    }

    /**
     * Bind the suite's callables to the provided spec
     *
     * @param $spec
     */
    public function bindCallables(SpecInterface $spec)
    {
        foreach ($this->setUpFns as $fn) {
            $spec->addSetUpFunction($fn);
        }
        foreach ($this->tearDownFns as $fn) {
            $spec->addTearDownFunction($fn);
        }
    }
}
