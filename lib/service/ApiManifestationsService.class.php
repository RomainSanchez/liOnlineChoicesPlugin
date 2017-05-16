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
        /*
        'gauges.prices.id' => 'Gauges.Prices.id',
        'gauges.prices.translations' => 'Gauges.Prices.Translation',
        'gauges.prices.value' => 'Gauges.Prices.value',
        'gauges.prices.currencyCode' => null,
        */
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
    
    protected function postFormatEntity(array $entity)
    {
        foreach ( $entity['gauges'] as $id => $gauge ) {
            $free = $entity['gauges'][$id]['availableUnits'];
            $entity['gauges'][$id]['availableUnits'] = $free > $this->getMaxShownAvailableUnits()
                ? $this->getMaxShownAvailableUnits()
                : $free;
        }
        
        return $entity;
    }
}
