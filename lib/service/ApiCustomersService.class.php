<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiCustomersService
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ApiCustomersService extends ApiEntityService
{
    protected static $HIDDEN_FIELD_MAPPING = [
        'password'      => ['type' => 'single', 'value' => 'Contact.password'],
    ];

    protected static $FIELD_MAPPING = [
        'id'            => ['type' => 'single', 'value' => 'id'],
        'email'         => ['type' => 'single', 'value' => 'contact_email'],
        'otherEmail'    => ['type' => 'single', 'value' => 'Organism.email'],
        'firstName'     => ['type' => 'single', 'value' => 'Contact.firstname'],
        'lastName'      => ['type' => 'single', 'value' => 'Contact.name'],
        'shortName'     => ['type' => 'single', 'value' => 'Contact.shortname'],
        'address'       => ['type' => 'single', 'value' => 'Organism.address'],
        'zip'           => ['type' => 'single', 'value' => 'Organism.postalcode'],
        'city'          => ['type' => 'single', 'value' => 'Organism.city'],
        'country'       => ['type' => 'single', 'value' => 'Organism.country'],
        'phoneNumber'   => ['type' => 'single', 'value' => 'contact_number'],
        'datesOfBirth'  => ['type' => null    , 'value' => null],
        'locale'        => ['type' => 'single', 'value' => 'Contact.culture'],
        'uid'           => ['type' => 'single', 'value' => 'Contact.vcard_uid'],
        'subscribedToNewsletter' => ['type' => 'single', 'value' => '!contact_email_no_newsletter'],
    ];

    /**
     * @var ocApiOAuthService
     */
    protected $oauth;

    /**
     * @return boolean
     */
    public function isIdentificated()
    {
        $token = $this->getOAuthService()->getToken();
        return $token instanceof OcToken && $token->OcTransaction->oc_professional_id !== NULL;
    }

    /**
     *
     * @return NULL|boolean  NULL if no email nor password given, else boolean
     */
    public function identify(array $query)
    {

        // prerequisites
        if (!( isset($query['criteria']['password']) && $query['criteria']['password'] && isset($query['criteria']['password']['value'])
            && isset($query['criteria']['email']) && $query['criteria']['email'] && isset($query['criteria']['email']['value']) ))
            return NULL;

        if ( $pro = $this->buildQuery($query)->fetchOne() ) {

            $token = $this->getOAuthService()->getToken();

            // case of an existing transaction to refresh, because we can have only one transaction by professional
            $transaction = Doctrine::getTable('OcTransaction')->createQuery('t')
                ->leftJoin('t.OcProfessional p')
                ->leftJoin('t.OcToken token')
                ->andWhere('p.professional_id = ?', $pro->id)
                ->orderBy('t.created_at DESC')
                ->fetchOne();
            if ( $transaction instanceof OcTransaction ) {
                $token->OcTransaction->oc_token_id = NULL;
                $token->OcTransaction->save();
                $transaction->OcToken = $token;
                $token->OcTransaction = $transaction;
                $transaction->save();
            }
            // else, create a new transaction
            else {
                if ( !$token->OcTransaction ) {
                     $token->OcTransaction = new OcTransaction;
                }

                $transaction = $token->OcTransaction;

                if ( !$transaction->oc_professional_id ) {
                  $oc_pro = Doctrine::getTable('OcProfessional')->createQuery('ocp')
                    ->andWhere('ocp.professional_id = ?', $pro->id)
                    ->fetchOne();
                    
                  if ( !$oc_pro ) {
                      $transaction->OcProfessional = new OcProfessional;
                  } else {
                    $transaction->OcProfessional = $oc_pro;
                  }
                }

                $transaction->OcProfessional->Professional = $pro;
                $transaction->OcToken = $token;
            }

            $transaction->save();
            return true;
        }
        return false;
    }

    /**
     *
     * @param int $id
     * @return array | null
     */
    public function findOneById($id)
    {
        $dotrineRec = $this->buildQuery([
            'criteria' => [
                'id' => [
                    'value' => $id,
                    'type'  => 'equal',
                ],
            ]
        ])
        ->fetchOne();

        if (false === $dotrineRec) {
            return null;
        }

        return $this->getFormattedEntity($dotrineRec);
    }

    /**
     *
     * @return boolean  true if the logout was possible, false if nobody is identified
     */
    public function logout()
    {
        if ( !$this->isIdentificated() ) {
            return false;
        }

        $token = $this->getOAuthService()->getToken();

        $actualTransaction = $token->OcTransaction;
        $actualTransaction->OcToken = NULL;
        $actualTransaction->save();

        $transaction = new OcTransaction;
        $transaction->oc_token_id = $token->id;
        $transaction->oc_professional_id = NULL;
        $transaction->save();

        return true;
    }

    public function update(array $data)
    {
        $accessor = new liApiPropertyAccessor;
        if ( !$this->isIdentificated() ) {
            return false;
        }
        unset($data['id'], $data['email']);

        $pro = $this->getIdentifiedProfessional();
        $accessor->toRecord($data, $pro, array_merge($this->getFieldsEquivalents(), $this->getHiddenFieldsEquivalents()));
        $pro->save();
        return true;
    }

    /**
     *
     * @return NULL|OcProfessional
     */
    public function getIdentifiedProfessional()
    {
        if ( !$this->isIdentificated() )
            return NULL;
        return $this->getOAuthService()->getToken()->OcTransaction->OcProfessional->Professional;
    }

    /**
     *
     * @return array
     */
    public function getIdentifiedCustomer()
    {
        return $this->getFormattedEntity($this->getIdentifiedProfessional());
    }

    public function buildInitialQuery()
    {
        return Doctrine_Query::create()
                ->from('Professional root')
                ->leftJoin('root.Contact Contact')
                ->leftJoin('root.Organism Organism')
        ;
    }

    public function setOAuthService(ApiOAuthService $service)
    {
        $this->oauth = $service;
        return $this;
    }

    public function getOAuthService()
    {
        return $this->oauth;
    }
    
    public function getBaseEntityName()
    {
        return 'Customer';
    }
}
