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
        'id'            => ['type' => 'simple', 'value' => 'id'],
        'email'         => ['type' => 'simple', 'value' => 'contact_email'],
        'firstName'     => ['type' => 'simple', 'value' => 'Contact.firstname'],
        'lastName'      => ['type' => 'simple', 'value' => 'Contact.name'],
        'shortName'     => ['type' => 'simple', 'value' => 'Contact.shortname'],
        'address'       => ['type' => 'simple', 'value' => 'Organism.address'],
        'zip'           => ['type' => 'simple', 'value' => 'Organism.postalcode'],
        'city'          => ['type' => 'simple', 'value' => 'Organism.city'],
        'country'       => ['type' => 'simple', 'value' => 'Organism.country'],
        'phoneNumber'   => ['type' => 'simple', 'value' => 'contact_number'],
        'datesOfBirth'  => ['type' => null    , 'value' => null],
        'locale'        => ['type' => 'simple', 'value' => 'Contact.culture'],
        'uid'           => ['type' => 'simple', 'value' => 'Contact.vcard_uid'],
        'subscribedToNewsletter' => ['type' => 'simple', 'value' => '!contact_email_no_newsletter'],
        //'password'      => ['type' => 'simple', 'value' => 'Contact.password'],
    ];
    
    protected static $HIDDEN_FIELD_MAPPING = [
        'password'      => ['type' => 'simple', 'value' => 'Contact.password'],
    ];
    
    /**
     * 
     * @return boolean
     */
    public function isIdentificated()
    {
        $token = $this->oauth->getToken();
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
        
        if ( $pro = $this->buildQuery($query)->fetchOne() )
        {
            $token = $this->oauth->getToken();
            $transaction = $token->OcTransaction->count() == 0
                ? new OcTransaction
                : $token->OcTransaction[0];
            
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
     * @return NULL|OcProfessional
     */
    public function getIdentifiedProfessional()
    {
        if ( !$this->isIdentificated() )
            return NULL;
        return $this->oauth->getToken()->OcTransaction[0]->OcProfessional->Professional;
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
}
