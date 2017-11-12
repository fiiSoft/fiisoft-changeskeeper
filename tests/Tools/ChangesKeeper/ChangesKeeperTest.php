<?php

namespace FiiSoft\Test\Tools\ChangesKeeper;

use FiiSoft\Tools\ChangesKeeper\ChangesKeeper;

class ChangesKeeperTest extends \PHPUnit_Framework_TestCase
{
    public function test_changes_keeper()
    {
        $keeper = new ChangesKeeper();
        
        $var1 = 'ala';
        $var2 = 'ola';
        $ob = new ufdhqpodgkbafk();
        
        $keeper->register('var1', $var1);
        $keeper->register('var2', $var2);
        $keeper->register('prop1', $ob->prop1);
        
        self::assertSame([], $keeper->getChanges());
        
        //step 1
        $expected = [
            'var1' => 'zuzia',
            'var2' => 'monika',
            'prop1' => 6,
        ];
        
        $keeper->change('var1', $expected['var1']);
        $keeper->change('var2', $expected['var2']);
        $keeper->change('prop1', $expected['prop1']);
        
        self::assertSame($expected['var1'], $var1);
        self::assertSame($expected['var2'], $var2);
        self::assertSame($expected['prop1'], $ob->prop1);
        
        self::assertSame($expected, $keeper->getChanges());
        
        //step 2
        $expected['prop2'] = 'duda';
        $keeper->add('prop2', $ob->prop2, $expected['prop2']);
        $ob->prop2 = $expected['prop2'];
        
        self::assertSame($expected, $keeper->getChanges());
        
        //step 3
        $expected['var2'] = 'kasia';
        $keeper->change('var2', $expected['var2']);
        
        self::assertSame($expected['var2'], $var2);
        self::assertSame($expected, $keeper->getChanges());
        
        //step 4
        $keeper->change('var2', 'ola');
        
        unset($expected['var2']);
        self::assertSame('ola', $var2);
        self::assertSame($expected, $keeper->getChanges());
        
        //step 5
        $keeper->add('prop2', $ob->prop2, null);
        $ob->prop2 = null;
        
        unset($expected['prop2']);
        self::assertSame($expected, $keeper->getChanges());
        
        //step 6
        $history = [
            'Changed var1 from "ala" to "zuzia"',
            'Changed prop1 to 6',
        ];
        
        self::assertSame($history, $keeper->getHistory());
        
        //step 7
        $keeper->historyGenerator()
               ->setTranslations(['Changed' => 'Zmiana', 'from' => 'z', 'to' => 'na']);
        
        $history = [
            'Zmiana var1 z "ala" na "zuzia"',
            'Zmiana prop1 na 6',
        ];
    
        self::assertSame($history, $keeper->getHistory());
        
        //step 8
        $keeper->historyGenerator()
               ->setHistoryMessages([
                    'var1' => 'Zmieniona zmienna var1',
                    'prop1' => 'Zmienione pole prop1',
                ]);
        
        $history = [
            'Zmieniona zmienna var1 z "ala" na "zuzia"',
            'Zmienione pole prop1 na 6',
        ];
    
        self::assertSame($history, $keeper->getHistory());
        
        //step 9
        $keeper->clear();
        self::assertSame([], $keeper->getChanges());
        self::assertSame([], $keeper->getHistory());
        
        //step 10
        $object = new kjdoeigfdsdkfj('wow');
        $keeper->change('var2', $object);
        
        $changes = ['var2' => $object];
        self::assertSame($changes, $keeper->getChanges());
        
        $history = [
            'Zmiana var2 z "ola" na "wow"',
        ];
        self::assertSame($history, $keeper->getHistory());
    }
}

class ufdhqpodgkbafk {
    public $prop1;
    public $prop2;
}

class kjdoeigfdsdkfj {
    private $value;
    
    public function __construct($value) {
        $this->value = $value;
    }
    
    public function __toString() {
        return (string) $this->value;
    }
}
