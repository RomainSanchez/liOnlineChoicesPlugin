<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ocExceptionCatcherFilter
 *
 * @author Glenn Cavarlé <glenn.cavarle@libre-informatique.fr>
 */
class ocExceptionCatcherFilter
{

    public function execute($filterChain)
    {
        try {
           
            $filterChain->execute();
            
        } catch ( ocAuthCredentialsException $e ) {
            OcLogger::log($e->getMessage(), $this);
            $r = $this->getResponse();
            $r->setStatusCode(ApiHttpStatus::UNAUTHORIZED);
            $r->setContent(json_encode(['message' => $e->getMessage()], JSON_PRETTY_PRINT) . "\n");
        } catch ( ocException $e ) {
            OcLogger::log($e->getMessage(), $this);
            $r = $this->getResponse();
            $r->setStatusCode(ApiHttpStatus::SERVICE_UNAVAILABLE);
            $r->setContent(json_encode(['message' => $e->getMessage()], JSON_PRETTY_PRINT) . "\n");
        }
    }

    private function getResponse()
    {
        $response = sfContext::getInstance()->getResponse();
        if ( null === $response ) {
            $response = new sfWebResponse(sfContext::getInstance()->getEventDispatcher());
            sfContext::getInstance()->setResponse($response);
        }
        $response->setHttpHeader('Content-type', 'application/json');
        return $response;
    }
}
