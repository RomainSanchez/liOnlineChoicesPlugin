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
     * @var ocApiOAuthService
     */
    protected $oauth;

    /**
     *
     * @var array 
     */
    protected static $HIDDEN_FIELD_MAPPING = [];

    /**
     *
     * @var array 
     */
    protected static $FIELD_MAPPING = [];

    /**
     * 
     * @param ApiOAuthService $oauth
     * @throws liOnlineSaleException
     */
    public function __construct(ApiOAuthService $oauth)
    {
        $this->oauth = $oauth;
        if ( !$oauth->isAuthenticated(sfContext::getInstance()->getRequest()) )
            throw new liOnlineSaleException('[services] API not authenticated.');
        sfContext::getInstance()->getUser()->signIn($oauth->getToken()->OcApplication->User, true);
    }

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

        $formattedEntity = [];
        foreach ($this->getFieldsEquivalents() as $api => $db) {
            // case of "not implemented" fields
            if ($db === NULL) {
                $formattedEntity = $this->_setSourceOnTarget($formattedEntity, explode('.', $api), NULL);
                continue;
            }

            $value = $this->_getSource($record, explode('.', $db));
            $formattedEntity = $this->_setSourceOnTarget($formattedEntity, explode('.', $api), $value);
        }

        return $this->postFormatEntity($formattedEntity);
    }
    
    private function _setSourceOnTarget(array $target, array $api, $source)
    {
        $completeKey = array_shift($api);
        $collection = is_array($source);
        $key = str_replace('[]', '', $completeKey);
        
        if ( count($api) == 0 ) {
            $target[$key] = $source instanceof Doctrine_Record || $source instanceof Doctrine_Collection
                ? $this->getDoctrineFlatData($source)
                : $source;
            return $target;
        }
        
        // init
        if ( !isset($target[$key]) ) {
            $target[$key] = $collection ? [[]] : [];
        }
        
        if ( $collection ) {
            foreach ( $source as $id => $value ) {
                $target[$key][$id] = $this->_setSourceOnTarget($target[$key][$id], $api, $value);
            }
        }
        else {
            $target[$key] = $this->_setSourceOnTarget($target[$key], $api, $source);
        }
        
        return $target;
    }
    
    private function _getSource(Doctrine_Record $record, array $db)
    {
        if ( count($db) == 0 )
            return $record;
        
        $sublevel = array_shift($db);
        $inverse = strpos($sublevel, '!') !== false;
        $sublevel = preg_replace('/^!/', '', $sublevel);
        
        if ( $record->$sublevel instanceof Doctrine_Collection )
        {
            $r = [];
            foreach ( $record->$sublevel as $rec )
                $r[] = $this->_getSource($rec, $db);
            return $r;
        }
        
        return $record->$sublevel instanceof Doctrine_Record || $record->$sublevel instanceof Doctrine_Collection
            ? $this->_getSource($record->$sublevel, $db)
            : ($inverse ? !$record->$sublevel : $record->$sublevel);
    }

    /**
     * Post-process the formatted-as-expected-by-the-API results
     *
     * @param array $entity the pre-formatted entities
     * @return array post-formatted entities
     *
     */
    protected function postFormatEntity(array $entity)
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
                $field = strpos('.', $fields[$criteria]) === false ? $q->getRootAlias() . '.' . $fields[$criteria] . ' ' : $fields[$criteria] . ' ';
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
            throw new liOnlineSaleException('Doctrine_Collection or Doctrine_Record expected, ' . get_class($data) . ' given.');

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
