<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiEntityService
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
abstract class ApiEntityService implements ApiEntityServiceInterface
{

    
    /**
     * @var array
     * any field and sub-field have to be represented as "field" and "field.sub-field" 
     * any collections is accessed as if it was a single property, the engine does the rest
     * left side: the API representation for datas
     * right side: array containing: 'type' => the type of data expected, 'value' => the path to data in Doctrine_Records
     * type: the type can be 'single', 'collection', null or 'sub-record' (with value null)
     * value: can be null if null is expected
     * for data coming from sub-collection records, the type needs to be set as 'collection.single' for example...
     */
    protected static $HIDDEN_FIELD_MAPPING = [];

    /**
     * @var array
     * any field and sub-field have to be represented as "field" and "field.sub-field" 
     * any collections is accessed as if it was a single property, the engine does the rest
     * left side: the API representation for datas
     * right side: array containing: 'type' => the type of data expected, 'value' => the path to data in Doctrine_Records
     * type: the type can be 'single', 'collection' (is useless standalone), null or 'sub-record' (with value null)
     * for data coming from sub-collection records, the type needs to be set as 'collection.single' for example...
     */
    protected static $FIELD_MAPPING = [];

    

    /**
     *
     * @param Doctrine_Collection|Doctrine_Record $mixed
     *
     * */
    public function getFormattedEntities($mixed)
    {
        $r = [];

        // Doctrine_Record
        if ($mixed instanceof Doctrine_Record)
            $r = $this->getFormattedEntity($mixed);

        // Doctrine_Collection
        if ($mixed instanceof Doctrine_Collection)
            foreach ($mixed as $record)
                $r[] = $this->getFormattedEntity($record);

        return $r;
    }

    public function getFormattedEntity(Doctrine_Record $record)
    {
        if ($record === NULL)
            return [];
        $accessor = new ocPropertyAccessor;
        
        $entity = $accessor->toAPI($record, $this->getFieldsEquivalents());
        
        return $this->postFormatEntity($entity, $record);
    }

    /**
     * Post-process the formatted-as-expected-by-the-API results
     *
     * @param array $entity the pre-formatted entities
     * @return array post-formatted entities
     *
     */
    protected function postFormatEntity(array $entity, Doctrine_Record $record)
    {
        return $entity;
    }

    public function buildQuery(array $query)
    {
        if (!is_array($query['criteria']))
            $query['criteria'] = [];

        $q = $this->buildInitialQuery();

        $this->buildQueryCondition($q, $query['criteria']);
        $this->buildQuerySorting($q, $query['sorting']);
        $this->buildQueryLimit($q, $query['limit']);
        $this->buildQueryPagination($q, $query['page']);

        return $q;
    }

    protected function buildQuerySorting(Doctrine_Query $q, array $sorting = [])
    {
        $orderBy = '';
        foreach ( $sorting as $field => $direction ) {
            if (!in_array($field, $this->getFieldsEquivalents()))
                continue;
            $orderBy .= array_search($field, $this->getFieldsEquivalents()) . ' ' . $direction . ' ';
        }

        return $orderBy ? $q->orderBy($orderBy) : $q;
    }

    protected function buildQueryLimit(Doctrine_Query $q, $limit = NULL)
    {
        if ( $limit !== NULL )
            $q->limit($limit);
        return $q;
    }

    protected function buildQueryPagination(Doctrine_Query $q, $page = 1)
    {
        if ( $page !== NULL )
            $q->offset($page - 1);
        return $q;
    }

    protected function buildQueryCondition(Doctrine_Query $q, array $criterias = [])
    {
        $fields = array_merge($this->getFieldsEquivalents(), $this->getHiddenFieldsEquivalents());
        $operands = $this->getOperandEquivalents();

        foreach ( $criterias as $criteria => $search )
            if ( isset($fields[$criteria]) && isset($search['value']) ) {
                $field = $q->getRootAlias() . '.' . $fields[$criteria]['value'].' ';
                $compare = $operands[$search['type']];
                $args = [$search['value']];
                $dql = '?';

                if ( is_array($compare) ) {
                    $args = $compare[1]($search['value']);
                    if ( is_array($args) ) {
                        $dql = [];
                        foreach ( $args as $arg )
                            $dql[] = '?';
                        $dql = implode(',', $dql);
                    }
                }

                $q->andWhere($field . ' ' . $compare[0] . ' ' . $dql, $args);
            }

        return $q;
    }

    public function countResults(array $query)
    {
        return $this->buildQuery($query)->count();
    }

    public function getOperandEquivalents()
    {
        return [
            'contain' => ['ILIKE', function($s) {
                    return "%$s%";
                }],
            'not contain' => ['NOT ILIKE', function($s) {
                    return "%$s%";
                }],
            'equal' => '=',
            'not equal' => '!=',
            'start with' => ['ILIKE', function($s) {
                    return "$s%";
                }],
            'end with' => ['ILIKE', function($s) {
                    return "%$s";
                }],
            'empty' => ['=', function($s) {
                    return '';
                }],
            'not empty' => ['!=', function($s) {
                    return '';
                }],
            'in' => ['IN', function($s) {
                    return implode(',', $s);
                }],
            'not in' => ['NOT IN', function($s) {
                    return implode(',', $s);
                }],
            'greater' => '>',
            'greater or equal' => '>=',
            'lesser' => '<',
            'lesser or equal' => '<=',
        ];
    }

    private function getDoctrineFlatData($data)
    {
        if (!$data instanceof Doctrine_Collection && !$data instanceof Doctrine_Record)
            throw new liOnlineSaleException('Doctrine_Collection or Doctrine_Record expected, ' . get_class($data) . ' given on line '.__LINE__.' of '.__FILE__.'.');

        $fct = function(Doctrine_Record $rec) {
        
            $arr = [];
            foreach ( $rec->getTable()->getColumns() as $colname => $coldef )
            {
                if ( !is_object($rec->$colname) )
                {
                    $arr[$colname] = $rec->$colname;
                }
            }
            return $arr;
        };

        $res = [];
        if ( $data instanceof Doctrine_Collection ) {
            foreach ( $data as $rec ) {
                $res[] = $fct($rec);
            }
        }
        else {
            $res = $fct($data);
        }
        
        return $res;
    }

    public function getFieldsEquivalents()
    {
        return static::$FIELD_MAPPING;
    }

    public function getHiddenFieldsEquivalents()
    {
        return static::$HIDDEN_FIELD_MAPPING;
    }
}
