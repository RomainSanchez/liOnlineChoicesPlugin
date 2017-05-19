<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiCartItemsService
 *
 * @author Glenn Cavarlé <glenn.cavarle@libre-informatique.fr>
 */
class ApiCartItemsService extends ApiEntityService
{

     protected static $FIELD_MAPPING = [
        'id' => ['type' => 'single', 'value' => 'id'],
        'type' => ['type' => 'null', 'value' => 'null'],
        'quantity' => ['type' => 'null', 'value' => 'null'],
        'declination' => ['type' => 'null', 'value' => 'null'],
        'totalAmount' => ['type' => 'null', 'value' => 'null'],
        'unitAmount' => ['type' => 'null', 'value' => 'null'],
        'total' => ['type' => 'null', 'value' => 'null'],
        'vat' => ['type' => 'null', 'value' => 'null'],
        'units' => ['type' => 'null', 'value' => 'null'],
        'unitsTotal' => ['type' => 'null', 'value' => 'null'],
        'adjustments' => ['type' => 'null', 'value' => 'null'],
        'adjustmentsTotal' => ['type' => 'null', 'value' => 'null'],
     ];

    /**
     * @var ocApiOAuthService
     */
    protected $oauth;

    /**
     * @param ApiOAuthService $service
     */
    public function setOAuthService(ApiOAuthService $service)
    {
        $this->oauth = $service;
    }

    /**
     *
     * @param int $cart_id
     * @param int $query
     * @return array
     */
    public function findAll($cart_id, $query)
    {
        $q = $this->buildQuery($query);
        $dotrineCol = $q->execute();

        return $this->getFormattedEntities($dotrineCol);
    }

    /**
     *
     * @param int $cartId
     * @param int $itemId
     * @return array|null
     */
    public function findOne($cartId, $itemId)
    {
        $token = $this->oauth->getToken();
        $query = [
            'criteria' => [
                'id' => [
                    'value' => $itemId,
                    'type'  => 'equal',
                ],
            ]
        ];
        $dotrineRec = $this->buildQuery($query)
            ->andWhere('root.oc_transaction_id = ?', $cartId)
            ->andWhere('OcToken.token = ?', $token->token)
            ->fetchOne()
        ;

        if (false === $dotrineRec) {
            return new ArrayObject;
        }

        return $this->getFormattedEntity($dotrineRec);
    }

    /**
     *
     * @param int $cartId
     * @param array $data
     * @return OcTicket
     * @throws liOnlineSaleException
     */
    public function create($cartId, $data)
    {
        // Check type
        if (!isset($data['type'])) {
            throw new liOnlineSaleException('Missing type parameter');
        }
        $type = $data['type'];
        $allowedTypes = ['ticket', 'product', 'pass'];
        if (!in_array($type, $allowedTypes)) {
            throw new liOnlineSaleException(sprintf('Wrong type parameter: %s. Expected one of: ', $type, implode(',', $allowedTypes)));
        }
        if ($type != 'ticket') {
            // TODO...
            throw new liOnlineSaleException('Not implemented yet for type: ' . $type);
        }

        if (!isset($data['priceId'])) {
            throw new liOnlineSaleException('Missing priceId parameter');
        }
        $priceId = $data['priceId'];

        if (!isset($data['declinationId'])) {
            throw new liOnlineSaleException('Missing declinationId parameter');
        }
        $declinationId = $data['declinationId'];

        if ( !$this->checkDeclinationAndPriceAccess($priceId, $declinationId) ) {
            throw new liOnlineSaleException('Invalid value for priceId or declinationId parameter');
        }

        $cartItem = new OcTicket;
        $cartItem->oc_transaction_id = $cartId;
        $cartItem->price_id = $priceId;
        $cartItem->gauge_id = $declinationId;
        $cartItem->save();

        return $cartItem;
    }

    /**
     * @param int $priceId
     * @param int $gaugeId
     * @return boolean
     */
    public function checkDeclinationAndPriceAccess($priceId, $gaugeId)
    {
        // TODO
        return true;
    }

    /**
     *
     * @param int $cart_id
     * @param int $item_id
     * @param array $data
     * @return boolean
     */
    public function updateCartItem($cart_id, $item_id, $data)
    {
        return true;
    }

    /**
     *
     * @param int $cartId
     * @param int $itemId
     * @return boolean
     */
    public function deleteCartItem($cartId, $itemId)
    {
        return true;
    }

    public function buildInitialQuery()
    {
        return Doctrine_Query::create()
            ->from('OcTicket root')
            ->leftJoin('root.OcTransaction OcTransaction')
            ->leftJoin('OcTransaction.OcToken OcToken')
        ;
    }

    /**
     * @param array $entity
     * @param Doctrine_Record $record
     * @return array
     */
    protected function postFormatEntity(array $entity, Doctrine_Record $record)
    {
        $entity['type'] = 'ticket';
        $entity['quantity'] = 1;
        $entity['declination'] = [
            'id' => $record->gauge_id,
            'code' => 'TODO',
            'position' => 'TODO',
            'translations' => 'TODO',
        ];
        $entity['unitAmount'] = 33;  // TODO
        $entity['totalAmount'] = $entity['quantity'] * $entity['unitAmount'];

        $entity['vat'] = 'TODO';
        $entity['units'] = 'TODO';
        $entity['unitsTotal'] = 'TODO';
        $entity['adjustments'] = 'TODO';
        $entity['adjustmentsTotal'] = 2; // TODO
        $entity['total'] = $entity['totalAmount'] + $entity['adjustmentsTotal'];

        return $entity;
    }
}
