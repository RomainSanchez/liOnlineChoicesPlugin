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
        'id'            => ['type' => 'single', 'value' => 'id'],
        'items'         => ['type' => 'null', 'value' => 'null'],
        'itemsTotal'    => ['type' => 'null', 'value' => 'null'],
        'total'         => ['type' => 'null', 'value' => 'null'],
        'customer'      => ['type' => 'null', 'value' => 'null'],
        'currencyCode'  => ['type' => 'null', 'value' => 'null'],
        'localeCode'    => ['type' => 'null', 'value' => 'null'],
        'checkoutState' => ['type' => 'single', 'value' => 'checkout_state'],
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
     * @var ApiCustomersService
     */
    protected $customersService;

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
     * @param ApiCustomersService $service
     */
    public function setCustomersService(ApiCustomersService $service)
    {
        $this->customersService = $service;
    }

    /**
     *
     * @param array $query
     * @return array
     */
    public function findAll($query)
    {
        $token = $this->oauth->getToken();
        $q = $this->buildQuery($query)
            ->andWhere('Token.token = ?', $token->token);
        $cartDotrineCol = $q->execute();

        return $this->getFormattedEntities($cartDotrineCol);
    }

    /**
     *
     * @param int $id
     * @return array | null
     */
    public function findOneById($id)
    {
        $token = $this->oauth->getToken();
        $query = [
            'criteria' => [
                'id' => [
                    'value' => $id,
                    'type'  => 'equal',
                ],
            ]
        ];
        $dotrineRec = $this->buildQuery($query)
            ->andWhere('Token.token = ?', $token->token)
            ->fetchOne();

        if (false === $dotrineRec) {
            return new ArrayObject;
        }

        return $this->getFormattedEntity($dotrineRec);
    }

    /**
     * @param array $entity
     * @param Doctrine_Record $record
     * @return array
     */
    protected function postFormatEntity(array $entity, Doctrine_Record $record)
    {
        // customer
        $entity['customer'] = new ArrayObject;
        if ($record->oc_professional_id) {
            $proId = $record->OcProfessional->Professional->id;
            $entity['customer'] = $this->customersService->findOneById($proId);
        }

        // cart items
        $query = [
            'limit'    => 100, // TODO
            'sorting'  => [],
            'page'     => 1,
        ];
        $entity['items'] = $this->cartItemsService->findAll($record->id, $query);

        // totals
        $entity['itemsTotal'] = 0;
        $entity['total'] = 0;
        foreach ($entity['items'] as $item) {
            $entity['itemsTotal'] += $item['totalAmount'];
            $entity['total'] += $item['total'];
        }

        // currency
        $currency = sfConfig::get('project_internals_currency', ['iso' => 978, 'symbol' => '€']);
        $entity['currencyCode'] = $currency['iso'];

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
            ->leftJoin('root.OcProfessional Professional')
            ->leftJoin('root.OcToken Token')
        ;
    }
}
