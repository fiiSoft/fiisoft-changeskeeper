<?php

namespace FiiSoft\Tools\ChangesKeeper;

use LogicException;
use UnexpectedValueException;

class ChangesKeeper
{
    /** @var array[] */
    protected $changes = [];
    
    /** @var ChangesHistoryGenerator */
    protected $historyGenerator;
    
    /** @var array */
    private $variables = [];
    
    /**
     * @param ChangesHistoryGenerator|null $historyGenerator
     */
    public function __construct(ChangesHistoryGenerator $historyGenerator = null)
    {
        $this->setHistoryGenerator($historyGenerator ?: new ChangesHistoryGenerator());
    }

    /**
     * @param ChangesHistoryGenerator $historyGenerator
     * @return $this fluent interface
     */
    public function setHistoryGenerator(ChangesHistoryGenerator $historyGenerator)
    {
        $this->historyGenerator = $historyGenerator;
        return $this;
    }
    
    /**
     * @return ChangesHistoryGenerator
     */
    public function historyGenerator()
    {
        return $this->historyGenerator;
    }
    
    /**
     * Register variable that can be change by call method change().
     *
     * @param string $name
     * @param mixed $variable reference to variable
     * @return $this fluent interface
     */
    public function register($name, &$variable)
    {
        $this->variables[$name] = &$variable;
        return $this;
    }
    
    /**
     * Change value of previously registered variable and record change.
     *
     * @param string $name
     * @param mixed $newValue
     * @param bool $force
     * @throws UnexpectedValueException
     * @throws LogicException
     * @return $this fluent interface
     */
    public function change($name, $newValue, $force = false)
    {
        if (!array_key_exists($name, $this->variables)) {
            throw new LogicException('Variable "'.$name.'" is not registered to watch');
        }
    
        if ($this->variables[$name] !== $newValue || $force) {
            $this->track($name, $this->variables[$name], $newValue, $force);
            $this->variables[$name] = $newValue;
        }
        
        return $this;
    }
    
    /**
     * Record change of value not related with registered variable.
     *
     * @param string $name
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param bool $force
     * @throws UnexpectedValueException
     * @return $this fluent interface
     */
    public function add($name, $oldValue, $newValue, $force = false)
    {
        if ($oldValue !== $newValue || $force) {
            $this->track($name, $oldValue, $newValue, $force);
        }
        
        return $this;
    }
    
    /**
     * @param string $name
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param bool $force
     * @throws UnexpectedValueException
     * @return void
     */
    private function track($name, $oldValue, $newValue, $force)
    {
        $analysisResult = $force ? 1 : $this->analyse($name, $oldValue, $newValue);
        
        switch ($analysisResult) {
            case -1: //a->b + b->a = {} no change at all
                unset($this->changes[$name]);
            break;
            
            case 0: //a->b + b->c = a->c
                $last = count($this->changes[$name]) - 1;
                $this->changes[$name][$last]['new'] = $newValue;
            break;
            
            case 1: //just add change
                $this->changes[$name][] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            break;
            
            default:
                throw new UnexpectedValueException('Unexpected analysisResult '.print_r($analysisResult, true));
        }
    }
    
    /**
     * @param string $name
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return int -1=remove, 0=replace, 1=add
     */
    private function analyse($name, $oldValue, $newValue)
    {
        if (isset($this->changes[$name])) {
            if ($newValue === $this->changes[$name][0]['old']) {
                return -1; //a->b + b->a = {} no change at all
            }
            
            $last = count($this->changes[$name]) - 1;
            if ($oldValue === $this->changes[$name][$last]['new']) {
                return 0; //a->b + b->c = a->c
            }
        }
        
        return 1; //just add change
    }
    
    /**
     * Get list of recorded changes.
     * Be aware that only last (the newest) value is returned for each chage.
     *
     * @return array
     */
    public function getChanges()
    {
        $result = [];
        
        foreach ($this->changes as $key => $changes) {
            foreach ($changes as $change) {
                $result[$key] = $change['new'];
            }
        }
        
        return $result;
    }
    
    /**
     * Get history of all recorded changes as list of messages.
     *
     * @throws UnexpectedValueException
     * @return array
     */
    public function getHistory()
    {
        return $this->historyGenerator->createHistory($this->changes);
    }
    
    /**
     * Remove all recorded changes for given name.
     *
     * @param string $name
     * @return $this fluent interface
     */
    public function remove($name)
    {
        if (array_key_exists($name, $this->changes)) {
            unset($this->changes[$name]);
        }
        
        return $this;
    }
    
    /**
     * Clear all recorded changes.
     *
     * @return $this fluent interface
     */
    public function clear()
    {
        $this->changes = [];
        return $this;
    }
    
    /**
     * Unregister variable (if registered).
     *
     * @param string $name
     * @return $this fluent interface
     */
    public function unregister($name)
    {
        if (array_key_exists($name, $this->variables)) {
            unset($this->variables[$name]);
        }
        
        return $this;
    }
    
    /**
     * Unregister all registered variables.
     *
     * @return $this fluent interface
     */
    public function unregisterAll()
    {
        $this->variables = [];
        return $this;
    }
}