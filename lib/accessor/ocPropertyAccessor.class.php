<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ocPropertyAccessor
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ocPropertyAccessor
{
    public function toAPI(Doctrine_Record $record, array $equiv)
    {
        // init
        $entity = [];

        // populate
        foreach ( $equiv as $api => $db ) {
            if ( is_array($db) ) {
                $type = explode('.', $db['type']);
                $lastType = array_pop($type);
                $bool = preg_match('/^!/', $db['value']) !== false;
                $db['value'] = preg_replace('/^!/', '', $db['value']);
<<<<<<< HEAD
                error_log($db['value']);

=======
                
>>>>>>> 89069101ac967631dd394dbe40fa21c9e61c19fe
                switch ( $lastType ) {
                    case 'sub-record':
                        $this->setAPIValue($entity, $api, new ArrayObject, $type);
                        break;
                    case null:
                        $this->setAPIValue($entity, $api, null, $type);
                        break;
                    case 'simple':
                        $this->setAPIValue($entity, $api, $this->getRecordValue($record, $db['value']), $type, $bool);
                        break;
                    case 'collection':
                        $this->setAPIValue($entity, $api, $db['value'] === NULL ? [] : $this->getRecordValue($record, $db['value']), $type, $bool);
                        break;
                }
            }
        }

        return $entity;
    }

    protected function setAPIValue(&$entity, $api, $value, $type = [], $bool = true)
    {
        // init
        $api = is_array($api) ? $api : explode('.', $api);
        $currentType = array_pop($type);

        // get out of here
        if ( !$api ) {
            $entity = $bool ? $value : !$value;
            return $this;
        }

        $key = array_shift($api);
        if ( !isset($entity[$key]) ) {
            $entity[$key] = [];
        }

        if ( $currentType == 'collection' ) {
            foreach ( $value as $k => $v ) {
                $this->setAPIValue($entity[$key][$k], $api, $value[$k], $type);
            }
        }
        else {
            $this->setAPIValue($entity[$key], $api, $value, $type);
        }

        return $this;
    }

    protected function getRecordValue($record, $db)
    {
        // init
        $db = is_array($db) ? $db : explode('.', $db);

        // get out of here
        if ( !$db ) {
            return $this->isDoctrine($record) ? $record->toArray() : $record;
        }

        $key = array_shift($db);
        if ( !$record->$key ) {
            return null;
<<<<<<< HEAD

=======
        }
        
>>>>>>> 89069101ac967631dd394dbe40fa21c9e61c19fe
        // Doctrine_Collection
        if ( $record->$key instanceof Doctrine_Collection ) {
            $r = [];
            foreach ( $record->$key as $i => $rec ) {
                $r[$i] = $this->getRecordValue($rec, $db);
            }
            return $r;
        }

        // Doctrine_Record
        return $this->getRecordValue($record->$key, $db);
    }

    private function isArray($data)
    {
        return $data instanceof ArrayAccess || is_array($data);
    }
    private function isDoctrine($data)
    {
        return $data instanceof Doctrine_Record || $data instanceof Doctrine_Collection;
    }
    private function isCollection($data)
    {
        return $this->isArray($data) || $data instanceof Doctrine_Collection;
    }
    private function getType($mixed)
    {
        return is_object($mixed) ? get_class($mixed) : gettype($mixed);
    }
    private function raiseException($message, $line = 'unknown', $file = __FILE__)
    {
        throw new liEvenementException(str_replace(['%%line%%', '%%file%%'], [$line, $file], $message));
    }
}

