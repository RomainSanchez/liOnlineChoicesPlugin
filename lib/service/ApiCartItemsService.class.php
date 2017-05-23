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
        'unitPrice' => ['type' => 'single', 'value' => 'Price.value'],
        'total' => ['type' => 'null', 'value' => 'null'],
        'vat' => ['type' => 'null', 'value' => 'null'],
        'units' => ['type' => 'null', 'value' => 'null'],
        'unitsTotal' => ['type' => 'null', 'value' => 'null'],
        'adjustments' => ['type' => 'null', 'value' => 'null'],
        'adjustmentsTotal' => ['type' => 'null', 'value' => 'null'],
        'rank' => ['type' => 'single', 'value' => 'rank'],
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
        $dotrineCol = $this->buildQuery($query)
            ->andWhere('root.oc_transaction_id = ?', $cart_id)
            ->execute()
        ;

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
                    'value' => (int)$itemId,
                    'type'  => 'equal',
                ],
            ]
        ];
        $dotrineRec = $this->buildQuery($query)
            ->andWhere('root.oc_transaction_id = ?', (int)$cartId)
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
     * @return array
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
        $priceId = (int)$data['priceId'];

        if (!isset($data['declinationId'])) {
            throw new liOnlineSaleException('Missing declinationId parameter');
        }
        $declinationId = (int)$data['declinationId'];

        if ( !$this->checkGaugeAndPriceAccess($declinationId, $priceId) ) {
            throw new liOnlineSaleException('Invalid value for priceId or declinationId parameter');
        }

        if ( !$this->checkGaugeAvailability($declinationId) ) {
            throw new liOnlineSaleException('Gauge is full or not available');
        }

        $cartItem = $this->buildQuery([])
            ->andWhere('root.price_id = ?', $priceId)
            ->andWhere('root.gauge_id = ?', $declinationId)
            ->andWhere('root.oc_transaction_id = ?', (int)$cartId)
            ->fetchOne()
        ;
        if (!$cartItem) {
            $cartItem = new OcTicket;
            $cartItem->oc_transaction_id = $cartId;
            $cartItem->price_id = $priceId;
            $cartItem->gauge_id = $declinationId;
            $cartItem->save();
        }

        return $this->getFormattedEntity($cartItem);
    }


    public function checkGaugeAvailability($gaugeId)
    {
        $gauge = Doctrine::getTable('gauge')->find($gaugeId);
        if (!$gauge) {
            return false;
        }
        if ($gauge->free <= 0) {
            return false;
        }
        return true;
    }

    /**
     * @param int $gaugeId
     * @param int $priceId
     * @return boolean
     */
    public function checkGaugeAndPriceAccess($gaugeId, $priceId)
    {
        // TODO
        return true;
    }

    /**
     *
     * @param int $cartId
     * @param int $itemId
     * @param array $data
     * @return boolean
     */
    public function updateCartItem($cartId, $itemId, $data)
    {
        // Check existence and access
        $item = $this->findOne($cartId, $itemId);
        if (count($item) == 0) {
            return false;
        }

        // Validate data
        if (!is_array($data)) {
            return false;
        }
        if (isset($data['quantity']) && (int)$data['quantity'] <= 0) {
            return false;
        }

        // Update cart item
        switch($item['type']) {
            case 'ticket':
                $success = $this->updateTicketCartItem($itemId, $data);
                break;
            case 'product':
            case 'pass':
                // TODO: update other kind of cart items (not ticket)
                // TODO ... $success = $this->updatePassCartItem($itemId, $data);
                $success = true;
                break;
            default:
                $success = false;
        }

        return $success;
    }

    /**
     * @param int $itemId
     * @param array $data
     * @return boolean   true if successful, false if failed
     */
    private function updateTicketCartItem($itemId, $data)
    {
        if (isset($data['rank'])) {
            $cartItem = Doctrine::getTable('OcTicket')->find($itemId);
            if (!$cartItem) {
                return false;
            }
            $cartItem->rank = (int)$data['rank'];
            $cartItem->save();
        }
        return true;
    }



    /**
     *
     * @param int $cartId
     * @param int $itemId
     * @return boolean   true if successful, false if failed
     */
    public function deleteCartItem($cartId, $itemId)
    {
        // Check existence and access
        $item = $this->findOne($cartId, $itemId);
        if (count($item) == 0) {
            return false;
        }

        // Update cart item
        switch($item['type']) {
            case 'ticket':
                $success = $this->deleteTicketCartItem($itemId);
                break;
            case 'product':
            case 'pass':
                // TODO: delete other kind of cart items (not ticket)
                // TODO ... $success = $this->deletePassCartItem($itemId);
                $success = false;
                break;
            default:
                $success = false;
        }

        return $success;
    }


    /**
     * @param int $itemId
     * @return boolean   true if successful, false if failed
     */
    private function deleteTicketCartItem($itemId)
    {
        $item = Doctrine::getTable('OcTicket')->find($itemId);
        if (!$item) {
            return false;
        }

        return $item->delete();
    }


    /**
     * @return @return Doctrine_Query
     */
    public function buildInitialQuery()
    {
        return Doctrine_Query::create()
            ->from('OcTicket root')
            ->leftJoin('root.Price Price')
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

        $entity['units'] = [];
        for($i=1; $i<=$entity['quantity']; $i++) {
            $entity['units'][] = [
                'id' => 'XXX', // TODO
                'adjustments' => [],  // TODO
                'adjustmentsTotal' => 0, // TODO
            ];
        }

        $entity['unitsTotal'] = $entity['quantity'] * $entity['unitPrice'];
        foreach($entity['units'] as $unit) {
            $entity['unitsTotal'] += $unit['adjustmentsTotal'];
        }

        $entity['adjustments'] = []; // TODO
        $entity['adjustmentsTotal'] = 0; // TODO
        $entity['total'] = $entity['unitsTotal'] + $entity['adjustmentsTotal'];

        return $entity;
    }
}
