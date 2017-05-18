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

    protected static $FIELD_MAPPING = [
        'id'            => ['type' => 'single', 'value' => 'id'],
        'email'         => ['type' => 'single', 'value' => 'contact_email'],
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
        //'password'      => ['type' => 'single', 'value' => 'Contact.password'],
    ];

    /**
     * @var ocApiOAuthService
     */
    protected $oauth;

    /**
     * 
     * @return boolean
     */
    public function isIdentificated()
    {
        $token = $this->getOAuthService()->getToken();
        return $token instanceof OcToken && $token->OcTransaction[0]->oc_professional_id !== NULL;
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
            $transaction = $token->OcTransaction->count() == 0 ? new OcTransaction : $token->OcTransaction[0];

            if ( !$transaction->oc_professional_id )
                $transaction->OcProfessional = new OcProfessional;
            $transaction->OcProfessional->Professional = $pro;

            $transaction->OcToken = $token;

            $transaction->save();
            return true;
        }
        return false;
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
        
        $transaction = this->getOAuthService()->getToken()->OcTransaction[0];
        $transaction->oc_professional_id = NULL;
        $transaction->save();
        
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
        return $this->getOAuthService()->getToken()->OcTransaction[0]->OcProfessional->Professional;
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
    }

    public function getOAuthService()
    {
        return $this->oauth;
    }
}
