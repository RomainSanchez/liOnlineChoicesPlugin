<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiEventsService
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ApiEventsService extends ApiEntityService
{
    protected $translationService;
    protected $oauth;

    protected static $FIELD_MAPPING = [
        'id'              => ['type' => 'single', 'value' => 'id'],
        'metaEvent'       => ['type' => 'sub-record', 'value' => null],
        'metaEvent.id'    => ['type' => 'single', 'value' => 'MetaEvent.id'],
        'metaEvent.translations' => ['type' => 'collection', 'value' => 'MetaEvent.Translation'],
        'category'        => ['type' => 'single', 'value' => 'EventCategory.name'],
        'translations'    => ['type' => 'collection', 'value' => 'Translation'],
        'imageURL'        => ['type' => null, 'value' => null],
        'manifestations'  => ['type' => 'collection', 'value' => null],
    ];
    
    /**
     * @var $manifestationsService
     */
    protected $manifestationsService;
    
    /**
     * 
     * @return array
     */
    public function findAll(array $query)
    {
        $q = $this->buildQuery($query);
        $events = $q->execute();

        return $this->getFormattedEntities($events);
    }


    public function buildInitialQuery()
    {
        return Doctrine::getTable('Event')->createQuery('root')
            ->leftJoin('root.Manifestations Manifestations')
        ;
    }
    
    protected function postFormatEntity(array $entity, Doctrine_Record $record)
    {
        // translations
        $this->translationService
            ->reformat($entity['translations'])
            ->reformat($entity['metaEvent']['translations']);
        
        // imageURL
        sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
        $entity['imageURL'] = url_for('@oc_api_picture?id='.$entity['id']);
        
        // manifestations
        $query = [
            'criteria' => [
                'event.id' => [
                    'type'  => 'equal',
                    'value' => $entity['id'],
                ],
                'happens_at' => [
                    'type'  => 'greater',
                    'value' => date('Y-m-d H:i:s'),
                ],
            ],
            'limit'    => 100,
            'sorting'  => [],
            'page'     => 1,
        ];
        
        $entity['manifestations'] = $this->manifestationsService->findAll($query, /*includeEvents*/ false);
        
        return $entity;
    }

    public function setApiManifestationsService(ApiManifestationsService $manifestations)
    {
        $this->manifestationsService = $manifestations;
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

    public function getOAuthService()
    {
        return $this->oauth;
    }
}
