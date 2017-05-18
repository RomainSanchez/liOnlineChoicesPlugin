<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiCartsService
 *
 * @author Glenn Cavarlé <glenn.cavarle@libre-informatique.fr>
 */
class ApiCartsService extends ApiEntityService
{

    protected static $FIELD_MAPPING = [
        'id'            => ['type' => 'simple', 'value' => 'id'],
        'items'         => ['type' => 'todo', 'value' => 'todo'],
        'itemsTotal'    => ['type' => 'todo', 'value' => 'todo'],
        'total'         => ['type' => 'todo', 'value' => 'todo'],
        'customer'      => ['type' => 'todo', 'value' => 'todo'],
        'currencyCode'  => ['type' => 'todo', 'value' => 'todo'],
        'localeCode'    => ['type' => 'todo', 'value' => 'todo'],
        'checkoutState' => ['type' => 'todo', 'value' => 'todo'],
//        'type'     => 'type',
//        'customer' => 'Professional.id',
//        'declination' => null,
//        'totalAmount' => null,
//        'unitAmount' => null,
//        'total' => null,
//        'vat' => null,
//        'units' => null,
//        'units.id' => null,
//        'units.adjustments' => null,
//        'units.adjustmentsTotal' => null,
//        'units._link[pdf]' => null,
//        'unitsTotal' => null,
//        'adjustments' => null,
//        'adjustmentsTotal' => null,
//        '_link[product]' => null,
//        '_link[order]' => null
    ];

    /**
     * @var ocApiOAuthService
     */
    protected $oauth;

    /**
     * @var ApiCartItemsService
     */
    protected $cartItemsService;

    /**
     * @param ApiOAuthService $service
     */
    public function setOAuthService(ApiOAuthService $service)
    {
        $this->oauth = $service;
    }

    /**
     * @param ApiCartItemsService $service
     */
    public function setCartItemsService(ApiCartItemsService $service)
    {
        $this->cartItemsService = $service;
    }

    /**
     *
     * @param array $query
     * @return array
     */
    public function findAll($query)
    {
        $q = $this->buildQuery($query);
        $cartDotrineCol = $q->execute();

        return $this->getFormattedEntities($cartDotrineCol);
    }

    /**
     *
     * @param int $cart_id
     * @return array | null
     */
    public function findOneById($cart_id)
    {
        $cartDotrineRec = $this->buildQuery(
                ['criteria' => ['root.id' => $cart_id]])
            ->fetchOne();

        if (false === $cartDotrineRec) {
            return null;
        }

        return $this->getFormattedEntity($cartDotrineRec);
    }

    /**
     * @param array $entity
     * @param Doctrine_Record $record
     * @return array
     */
    protected function postFormatEntity(array $entity, Doctrine_Record $record)
    {
        // items
        $query = [
            'limit'    => 100,
            'sorting'  => [],
            'page'     => 1,
        ];
        $entity['items'] = $this->cartItemsService->findAll($record->id, $query);

        return $entity;
    }

    /**
     *
     * @param int $cart_id
     * @return boolean
     */
    public function deleteCart($cart_id)
    {
        return true;
    }

    public function buildInitialQuery()
    {
        return Doctrine_Query::create()
            ->from('OcTransaction root')
//            ->leftJoin('root.Professional Professional')
        ;
    }
}
