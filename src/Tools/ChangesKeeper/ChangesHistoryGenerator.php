<?php

namespace FiiSoft\Tools\ChangesKeeper;

use Closure;
use DateTimeInterface;
use UnexpectedValueException;

final class ChangesHistoryGenerator
{
    /** @var string[]  */
    private static $words = ['Changed', 'from', 'to'];
    
    /** @var array */
    private $historyMessages = [];
    
    /** @var string[] */
    private $translations = [];
    
    /** @var Closure|null */
    private $messageInterceptor;
    
    /** @var array */
    private $context = [];
    
    /**
     * @param array $historyMessages
     * @param array $translations
     * @param Closure|null $interceptor interceptor of generated messages
     */
    public function __construct(array $historyMessages = [], array $translations = [], Closure $interceptor = null)
    {
        $this->setTranslations($translations);
        $this->setHistoryMessages($historyMessages);
        $this->setMessageInterceptor($interceptor);
    }
    
    /**
     * @param array $historyMessages
     * @return $this fluent interface
     */
    public function setHistoryMessages(array $historyMessages)
    {
        $this->historyMessages = $historyMessages;
        return $this;
    }
    
    /**
     * @param array $translations
     * @return $this fluent interface
     */
    public function setTranslations(array $translations)
    {
        if (empty($translations)) {
            $this->setDefaultTranslations();
        } else {
            if (empty($this->translations)) {
                $this->setDefaultTranslations();
            }
            
            $this->translations = array_merge(
                $this->translations,
                array_intersect_key($translations, $this->translations)
            );
        }
        
        return $this;
    }
    
    /**
     * This closure will be called with five parameters:
     * name of change, old value, new value, generated pieces of message, context.
     *
     * It must return one of:
     *  - string (used as history message),
     *  - array of messages' pieces for further processing,
     *  - false to skip this particular message from history
     *
     * @param Closure|null $interceptor
     * @return $this fluent interface
     */
    public function setMessageInterceptor(Closure $interceptor = null)
    {
        $this->messageInterceptor = $interceptor;
        return $this;
    }
    
    /**
     * @return void
     */
    private function setDefaultTranslations()
    {
        $this->translations = array_combine(self::$words, self::$words);
    }
    
    /**
     * @param array $context
     * @return $this fluent interface
     */
    public function setContext(array $context)
    {
        $this->context = $context;
        return $this;
    }
    
    /**
     * Get history of all recorded changes as list of messages.
     *
     * @param array $changes data in format generated by ChangesKeeper!
     * @throws UnexpectedValueException
     * @return array
     */
    public function createHistory(array $changes)
    {
        $history = [];
        
        foreach ($changes as $key => $subchanges) {
            foreach ($subchanges as $change) {
                $message = $this->generateHistoryMessage($change, $key);
                if (is_string($message)) {
                    $history[] = $message;
                }
            }
        }
        
        return $history;
    }
    
    /**
     * @param array $change
     * @param string $key
     * @throws UnexpectedValueException
     * @return string|false
     */
    private function generateHistoryMessage(array $change, $key)
    {
        $begin = isset($this->historyMessages[$key])
            ? $this->historyMessages[$key]
            : $this->translations['Changed'].' '.$key;
        
        $pieces['begin'] = $begin;
        
        if (isset($change['old'])) {
            $pieces['from'] = $this->translations['from'];
            $pieces['old'] = $this->str($change['old'], $key);
        }
        
        $pieces['to'] = $this->translations['to'];
        $pieces['new'] = $this->str($change['new'], $key);
        
        if ($this->messageInterceptor) {
            $interceptor = $this->messageInterceptor;
            $result = $interceptor($key, $change['old'], $change['new'], $pieces, $this->context);
            
            if (is_array($result)) {
                $pieces = $result;
            } else {
                return $result;
            }
        }
        
        return implode(' ', $pieces);
    }
    
    /**
     * @param mixed $value
     * @param string $key
     * @throws UnexpectedValueException
     * @return string
     */
    private function str($value, $key)
    {
        if (is_string($value)) {
            return '"'.$value.'"';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        
        if ($value === null) {
            return 'NULL';
        }
        
        if (is_array($value)) {
            return implode(',', $value);
        }
        
        if ($value instanceof DateTimeInterface) {
            if ($key === 'date') {
                return $value->format('Y-m-d');
            }
            
            return $value->format('Y-m-d H:i:s');
        }
        
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return '"'.$value.'"';
            }
            
            if (method_exists($value, 'toString')) {
                return '"'.$value->toString().'"';
            }
            
            if (method_exists($value, 'asString')) {
                return '"'.$value->asString().'"';
            }
        }
        
        throw new UnexpectedValueException('Cannot convert argument to string');
    }
}