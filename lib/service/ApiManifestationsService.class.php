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
    protected $manifestationsService;
    protected $oauth;

    protected static $FIELD_MAPPING = [
        'id'                => ['type' => 'single', 'value' => 'id'],
        'startsAt'          => ['type' => 'single', 'value' => 'happens_at'],
        'endsAt'            => ['type' => 'single', 'value' => 'ends_at'],
        'event_id'          => ['type' => 'single', 'value' => 'Event.id'],
        'event'             => ['type' => 'collection', 'value' => 'Event.Translation'],
        'metaEvent'         => ['type' => 'collection', 'value' => 'Event.MetaEvent.Translation'],
        'location'          => ['type' => 'sub-record', 'value' => null],
        'location.id'       => ['type' => 'single', 'value' => 'Location.id'],
        'location.name'     => ['type' => 'single', 'value' => 'Location.name'],
        'location.address'  => ['type' => 'single', 'value' => 'Location.address'],
        'location.zip'      => ['type' => 'single', 'value' => 'Location.postalcode'],
        'location.city'     => ['type' => 'single', 'value' => 'Location.city'],
        'location.country'  => ['type' => 'single', 'value' => 'Location.country'],
        //'gauges'            => ['type' => 'collection', 'value' => null],
        'gauges.id'         => ['type' => 'collection.single', 'value' => 'Gauges.id'],
        'gauges.name'       => ['type' => 'collection.single', 'value' => 'Gauges.Workspace.name'],
        'gauges.availableUnits' => ['type' => 'collection.single', 'value' => 'Gauges.free'],
        //'gauges.prices.id' => ['type' => 'single', 'value' => 'Gauges.Prices.id'],
        //'gauges.prices.translations' => ['type' => 'single', 'value' => 'Gauges.Prices.Translation'],
        //'gauges.prices.value' => ['type' => 'single', 'value' => 'Gauges.Prices.value'],
        //'gauges.prices.currencyCode' => null,
        //'timeSlots'         => ['type' => 'collection', 'value' => 'OcTimeSlots'],
        'timeSlots.id'      => ['type' => 'collection.single', 'value' => 'OcTimeSlots.id'],
        'timeSlots.name'    => ['type' => 'collection.single', 'value' => 'OcTimeSlots.name'],
        'timeSlots.startsAt'=> ['type' => 'collection.single', 'value' => 'OcTimeSlots.starts_at'],
        'timeSlots.endsAt'  => ['type' => 'collection.single', 'value' => 'OcTimeSlots.ends_at'],
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
                    'value' => $manif_id,
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
        $q = $this->manifestationsService->buildQuery($this->oauth->getToken()->OcApplication->User, NULL, 'root')
            
            // available prices
            ->andWhere('(FALSE')
                ->orWhere('pmpup.price_id IS NOT NULL')
                ->orWhere('pmpwp.price_id IS NOT NULL')
                ->orWhere('pgpup.price_id IS NOT NULL')
                ->orWhere('pgpwp.price_id IS NOT NULL')
                ->orWhere('FALSE)')
            
            // member card linked prices
            ->andWhere('(FALSE')
                ->orWhere('pgp.member_card_linked = FALSE')
                ->orWhere('pmp.member_card_linked = FALSE');
            if ( $proid = $this->oauth->getToken()->OcTransaction->professional_id ) {
              $q->orWhere('pgp.id IN (SELECT mcp1.price_id FROM MemberCardPrice mcp1 LEFT JOIN mcp1.MemberCard mc1 LEFT JOIN mc1.Contact cc1 LEFT JOIN cc1.Professionals pro1 WHERE pro1.id = ?)', $proid)
                ->orWhere('pmp.id IN (SELECT mcp2.price_id FROM MemberCardPrice mcp2 LEFT JOIN mcp2.MemberCard mc2 LEFT JOIN mc2.Contact cc2 LEFT JOIN cc2.Professionals pro2 WHERE pro2.id = ?)', $proid)
            }
              $q->orWhere('FALSE)')
        
        ;
        
        return $q;
    }

    public function getMaxShownAvailableUnits()
    {
        return 10;
    }

    protected function postFormatEntity(array $entity, Doctrine_Record $manif)
    {
        // timestamps
        $this->translationService->reformat($entity);
        $this->translationService->reformat($entity['metaEvent']);
        $this->translationService->reformat($entity['event']);
        foreach ( $entity['timeSlots'] as &$timeSlot ) {
            $this->translationService->reformat($timeSlot);
        }

        // gauges
        $currency = sfConfig::get('project_internals_currency', ['iso' => 978, 'symbol' => '€']);
        foreach ( $entity['gauges'] as $id => $gauge ) {
            // availableUnits
            if ( isset($gauge['availableUnits']) ) {
                $free = $gauge['availableUnits'];
                $entity['gauges'][$id]['availableUnits'] = $free > $this->getMaxShownAvailableUnits()
                    ? $this->getMaxShownAvailableUnits()
                    : $free;
            }

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
                if ( $pm->price_id ) {
                    foreach ( $pm->Price->Translation as $i11n ) {
                        $price['translations'][$i11n->lang] = [];
                        $price['translations'][$i11n->lang]['name'] = $i11n->name;
                        $price['translations'][$i11n->lang]['description'] = $i11n->description;
                    }
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
    public function setOAuthService(ApiOAuthService $service)
    {
        $this->oauth = $service;
    }

    public function setManifestationsService(ApiManifestationsService $service)
    {
        $this->manifestationsService = $service;
    }

    public function getOAuthService()
    {
        return $this->oauth;
    }
}

