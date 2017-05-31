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
        'id'               => ['type' => 'single', 'value' => 'id'],
        'type'             => ['type' => 'null', 'value' => 'null'],
        'quantity'         => ['type' => 'null', 'value' => 'null'],
        'declination'      => ['type' => 'null', 'value' => 'null'],
        'totalAmount'      => ['type' => 'null', 'value' => 'null'],
        'unitPrice'        => ['type' => 'single', 'value' => 'Price.value'],
        'total'            => ['type' => 'null', 'value' => 'null'],
        'vat'              => ['type' => 'null', 'value' => 'null'],
        'units'            => ['type' => 'null', 'value' => 'null'],
        'unitsTotal'       => ['type' => 'null', 'value' => 'null'],
        'adjustments'      => ['type' => 'null', 'value' => 'null'],
        'adjustmentsTotal' => ['type' => 'null', 'value' => 'null'],
        'rank'             => ['type' => 'single', 'value' => 'rank'],
        'state'            => ['type' => 'single', 'value' => 'accepted'],
     ];

    /**
     * @var ocApiOAuthService
     */
    protected $oauth;

    /**
     * @var ApiManifestationsService
     */
    protected $manifestationsService;

    /**
     * @param ApiOAuthService $service
     */
    public function setOAuthService(ApiOAuthService $service)
    {
        $this->oauth = $service;
    }

    /**
     * @param ApiManifestationsService $service
     */
    public function setManifestationsService(ApiManifestationsService $service)
    {
        $this->manifestationsService = $service;
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
     * @throws ocException
     */
    public function create($cartId, $data)
    {
        // Check type
        if (!isset($data['type'])) {
            throw new ocException('Missing type parameter');
        }
        $type = $data['type'];
        $allowedTypes = ['ticket', 'product', 'pass'];
        if (!in_array($type, $allowedTypes)) {
            throw new ocNotImplementedException(sprintf('Wrong type parameter: %s. Expected one of: ', $type, implode(',', $allowedTypes)));
        }
        if ($type != 'ticket') {
            // TODO...
            throw new ocNotImplementedException('Not implemented yet for type: ' . $type);
        }

        if (!isset($data['priceId'])) {
            throw new ocException('Missing priceId parameter');
        }
        $priceId = (int)$data['priceId'];

        if (!isset($data['declinationId'])) {
            throw new ocException('Missing declinationId parameter');
        }
        $declinationId = (int)$data['declinationId'];

        if ( !$this->checkGaugeAndPriceAccess($declinationId, $priceId) ) {
            throw new ocException('Invalid value for priceId or declinationId parameter');
        }

        if ( !$this->checkGaugeAvailability($declinationId) ) {
            throw new ocException('Gauge is full or not available in your context');
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
        $count = $this->manifestationsService->buildQuery([])
            ->andWhere('g.id = ?', $gaugeId)
            ->andWhere('(FALSE')
            ->orWhere('pmp.id = ?', $priceId)
            ->orWhere('pgp.id = ?', $priceId)
            ->orWhere('FALSE)')
            ->count()
        ;

        return $count > 0;
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

        // Update cart item
        switch($item['type']) {
            case 'ticket':
                $success = $this->updateTicketCartItem($itemId, $data);
                break;
            case 'product':
            case 'pass':
                // TODO: update other kind of cart items (not ticket)
                // TODO ... $success = $this->updatePassCartItem($itemId, $data);
                $success = false;
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
        // Validate data
        if (!is_array($data)) {
            return false;
        }

        $cartItem = Doctrine::getTable('OcTicket')->find($itemId);
        if (!$cartItem) {
            return false;
        }

        if (isset($data['rank'])) {
            if ( (int)$data['rank'] <= 0 ) {
                return false;
            }
            $cartItem->rank = (int)$data['rank'];
        }

        if (isset($data['quantity'])) {
            if ( (int)$data['quantity'] <= 0 ) {
                return false;
            }
            //$cartItem->quantity = 1;  // It is always 1 for ticket
        }

        $cartItem->save();
        return true;
    }

    /**
     * @param int $cartId
     * @param array $data
     * @return boolean   true if successful, false if failed
     */
    public function reorderItems($cartId, $data)
    {
        // Validate data
        if (!is_array($data)) {
            return false;
        }

        $ranks = [];
        $cartItemIds = [];
        foreach($data as $d) {
            if (!isset($d['cartItemId']) || !isset($d['rank'])) {
                return false;
            }
            $ranks[$d['cartItemId']] = (int)$d['rank'];
            $cartItemIds[] = (int)$d['cartItemId'];
        }

        $cartItems = $dotrineCol = $this->buildQuery([])
            ->leftJoin('root.Gauge gau')
            ->andWhere('root.oc_transaction_id = ?', $cartId)
            ->andWhereIn('root.id', $cartItemIds)
            ->execute()
        ;

        // Check if all cart items belong to the same time slot
        $declinationIds = [];
        foreach($cartItems as $item) {
            $declinationIds[] = $item->Gauge->manifestation_id;
        }
        $timeSlotIds = $this->getTimeSlotIds($declinationIds);
        if (count($timeSlotIds) != 1) {
            //return false;
        }

        // Update cart item ranks
        foreach($cartItems as $item) {
            $item->rank = $ranks[$item->id];
            $item->save();
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
     * @param int $cartId
     * @param int $itemId
     * @return boolean
     */
    public function isCartItemEditable($cartId, $itemId)
    {
        // Check existence and access
        $item = $this->findOne($cartId, $itemId);
        if (count($item) == 0) {
            return false;
        }

        // Find time slot used by the cart item
        $timeSlotId = $this->getTimeSlotId($item['declination']['id']);
        if (!$timeSlotId) {
            return false;
        }

        return !$this->isTimeSlotFrozen($cartId, $timeSlotId);
    }

    /**
     * @param int $cartId
     * @param int $declinationId
     * @return boolean
     */
    public function isCartItemCreatable($cartId, $declinationId)
    {

        // Find time slot
        $timeSlotId = $this->getTimeSlotId($declinationId);
        if (!$timeSlotId) {
            return false;
        }

        return !$this->isTimeSlotFrozen($cartId, $timeSlotId);
    }

    /**
     * @param int $cartId
     * @param int $timeSlotId
     * @return boolean
     */
    protected function isTimeSlotFrozen($cartId, $timeSlotId)
    {
        // Find out if there are some cart items with accepted <> "none" for the same cart and time slot
        $q = Doctrine::getTable('ocTicket')->createQuery('tic', true)
            ->leftJoin('tic.Gauge gau')
            ->leftJoin('gau.Manifestation man')
            ->leftJoin('man.OcTimeSlotManifestations tsm')
            ->andWhere('tic.accepted <> ?', 'none')
            ->andWhere('tic.oc_transaction_id = ?', $cartId)
            ->andWhere('tsm.oc_time_slot_id = ?', $timeSlotId)
        ;
        $items = $q->count();

        return $items > 0;
    }


    /**
     * @param int $declinationId
     * @return int | false if not found
     */
    protected function getTimeSlotId($declinationId)
    {
        $q = Doctrine::getTable('ocTimeSlot')->createQuery('ts')
            ->select('ts.id')
            ->leftJoin('ts.OcTimeSlotManifestations tsm')
            ->leftJoin('tsm.Manifestation man')
            ->leftJoin('man.Gauges gau')
            ->andWhere('gau.id = ?', $declinationId)
        ;
        $timeSlot = $q->fetchArray();

        if (!$timeSlot) {
            return false;
        }
        return $timeSlot[0]['id'];
    }

    /**
     * @param array $declinationIds
     * @return array
     */
    protected function getTimeSlotIds($declinationIds)
    {
        $q = Doctrine::getTable('ocTimeSlot')->createQuery('ts')
            ->select('ts.id')
            ->leftJoin('ts.OcTimeSlotManifestations tsm')
            ->leftJoin('tsm.Manifestation man')
            ->leftJoin('man.Gauges gau')
            ->andWhereIn('gau.id', $declinationIds)
        ;
        $timeSlots = $q->fetchArray();
        $timeSlotIds = [];
        foreach($timeSlots as $ts) {
            $timeSlotIds[] = $ts['id'];
        }
        return array_unique($timeSlotIds);
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
