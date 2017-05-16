<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of actions
 *
 * @author Glenn Cavarlé <glenn.cavarle@libre-informatique.fr>
 */
class ocApiManifestationsActions extends apiActions
{

    /**
     * 
     * @param sfWebRequest $request
     * @param array $query
     * @return array
     */
    public function getAll(sfWebRequest $request, array $query)
    {
        /* @var $manifService ApiManifestationsService */
        $manifs = $this->getService('manifestations_service');
        $result  = $this->getListWithDecorator($manifs->findAll($query), $query);
        return $this->createJsonResponse($result);
    }
}
