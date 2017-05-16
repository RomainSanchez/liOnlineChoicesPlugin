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
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ApiManifestationsService extends ApiEntityService
{

    protected static $FIELD_MAPPING = [
        'id' => 'id',
        'startsAt' => 'happens_at',
        'endsAt' => 'ends_at',
        'metaEvent' => 'Event.MetaEvent.Translation',
        'location.id' => 'Location.id',
        'location.name' => 'Location.name',
        'location.address' => 'Location.address',
        'location.zip' => 'Location.postalcode',
        'location.city' => 'Location.city',
        'location.country' => 'Location.country',
        'gauges.id' => 'Gauges.id',
        'gauges.name' => 'Gauges.Workspace.name',
        'gauges.availableUnits' => 'Gauges.free',
        //'gauges.prices.id' => 'Gauges.Prices.id',
        //'gauges.prices.translations' => 'Gauges.Prices.Translation',
        //'gauges.prices.value' => 'Gauges.Prices.value',
        //'gauges.prices.currencyCode' => null,
    ];

    /**
     * 
     * @return array
     */
    public function findAll($query)
    {
        $q = $this->buildQuery($query);
        $manifestations = $q->execute();

        return $this->getFormattedEntities($manifestations);
    }

    /**
     * 
     * @param int $manif_id
     * @return array | null
     */
    public function findOneById($manif_id)
    {
        $manifDotrineRec = $this->buildQuery([
            'criteria' => [
                'id' => [
                    'value' => 'manif_id',
                    'type'  => 'equal',
                ],
            ]
        ])
        ->fetchOne();

        if (false === $manifDotrineRec)
        {
            return null;
        }

        return $this->getFormattedEntity($manifDotrineRec);
    }

    public function buildInitialQuery()
    {
        return Doctrine::getTable('Manifestation')->createQuery('root');
    }
    
    public function getMaxShownAvailableUnits()
    {
        return 10;
    }
    
    protected function postFormatEntity(array $entity, Doctrine_Record $manif)
    {
        $currency = sfConfig::get('project_internals_currency', ['iso' => 978, 'symbol' => '€']);
        foreach ( $entity['gauges'] as $id => $gauge ) {
            // availableUnits
            $free = $entity['gauges'][$id]['availableUnits'];
            $entity['gauges'][$id]['availableUnits'] = $free > $this->getMaxShownAvailableUnits()
                ? $this->getMaxShownAvailableUnits()
                : $free;
            
            // gauges.prices
            $entity['gauges'][$id]['prices'] = [];
            foreach ( ['manif' => 'PriceManifestations', 'gauge' => 'PriceGauges'] as $object => $collection )
            foreach ( ${$object}->$collection as $pm ) { // prices from manifestation
                $price = [
                    'id' => $pm->price_id,
                    'value' => $pm->value,
                    'currencyCode' => $currency['iso'],
                ];
                $price['translations'] = [];
                if ( $pm->price_id )
                foreach ( $pm->Price->Translation as $i11n ) {
                    $price['translations'][$i11n->lang] = [];
                    $price['translations'][$i11n->lang]['name'] = $i11n->name;
                    $price['translations'][$i11n->lang]['description'] = $i11n->description;
                }
                $entity['gauges'][$id]['prices'][] = $price;
            }
        }
        
        return $entity;
    }
}
