<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiManifestationService
 *
 * @author Glenn Cavarlé <glenn.cavarle@libre-informatique.fr>
 */
class ApiCartsService extends ApiEntityService
{

    protected static $FIELD_MAPPING = [
        'id'       => 'id',
        'type'     => 'type',
        'customer' => 'Professional.id',
        'declination' => null,
        'totalAmount' => null,
        'unitAmount' => null,
        'total' => null,
        'vat' => null,
        'units' => null,
        'units.id' => null,
        'units.adjustments' => null,
        'units.adjustmentsTotal' => null,
        'units._link[pdf]' => null,
        'unitsTotal' => null,
        'adjustments' => null,
        'adjustmentsTotal' => null,
        '_link[product]' => null,
        '_link[order]' => null
    ];

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
            ->from('Transaction root')
            ->leftJoin('root.Professional Professional')
        ;
    }
}
