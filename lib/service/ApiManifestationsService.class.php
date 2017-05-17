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

    protected $translationService;
    
    protected static $FIELD_MAPPING = [
        'id'                => ['type' => 'simple', 'value' => 'id'],
        'startsAt'          => ['type' => 'simple', 'value' => 'happens_at'],
        'endsAt'            => ['type' => 'simple', 'value' => 'ends_at'],
        'event_id'          => ['type' => 'simple', 'value' => 'Event.id'],
        'event'             => ['type' => 'collection', 'value' => 'Event.Translation'],
        'metaEvent'         => ['type' => 'collection', 'value' => 'Event.MetaEvent.Translation'],
        'location'          => ['type' => 'sub-record', 'value' => null],
        'location.id'       => ['type' => 'simple', 'value' => 'Location.id'],
        'location.name'     => ['type' => 'simple', 'value' => 'Location.name'],
        'location.address'  => ['type' => 'simple', 'value' => 'Location.address'],
        'location.zip'      => ['type' => 'simple', 'value' => 'Location.postalcode'],
        'location.city'     => ['type' => 'simple', 'value' => 'Location.city'],
        'location.country'  => ['type' => 'simple', 'value' => 'Location.country'],
        //'gauges'            => ['type' => 'collection', 'value' => null],
        'gauges.id'         => ['type' => 'collection.simple', 'value' => 'Gauges.id'],
        'gauges.name'       => ['type' => 'collection.simple', 'value' => 'Gauges.Workspace.name'],
        //'gauges.availableUnits' => ['type' => 'simple', 'value' => 'Gauges.free'],
        //'gauges.prices.id' => ['type' => 'simple', 'value' => 'Gauges.Prices.id'],
        //'gauges.prices.translations' => ['type' => 'simple', 'value' => 'Gauges.Prices.Translation'],
        //'gauges.prices.value' => ['type' => 'simple', 'value' => 'Gauges.Prices.value'],
        //'gauges.prices.currencyCode' => null,
    ];

    /**
     * 
     * @return array
     */
    public function findAll(array $query)
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
        return Doctrine::getTable('Manifestation')->createQuery('root')->andWhere('root.id = ?', 121);
    }
    
    public function getMaxShownAvailableUnits()
    {
        return 10;
    }
    
    protected function postFormatEntity(array $entity, Doctrine_Record $manif)
    {
        return $entity;
        
        // metaEvent
        $entity['metaEvent'] = $this->translationService->reformat($entity['metaEvent']);
        $entity['event'] = $this->translationService->reformat($entity['event']);
        
        // gauges
        $currency = sfConfig::get('project_internals_currency', ['iso' => 978, 'symbol' => '€']);
        foreach ( $entity['gauges'] as $id => $gauge ) {
            // availableUnits
            /*
            $free = $entity['gauges'][$id]['availableUnits'];
            $entity['gauges'][$id]['availableUnits'] = $free > $this->getMaxShownAvailableUnits()
                ? $this->getMaxShownAvailableUnits()
                : $free;
            */
            
            // gauges.prices
            $entity['gauges'][$id]['prices'] = [];
            foreach ( ['PriceManifestations' => $manif, 'PriceGauges' => $manif->Gauges[$id]] as $collection => $object )
            foreach ( $object->$collection as $pm ) { // prices from manifestation
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
    
    public function setTranslationService(ApiTranslationService $i18n)
    {
        $this->translationService = $i18n;
        return $this;
    }
}
