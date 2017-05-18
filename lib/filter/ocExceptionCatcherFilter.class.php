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
            
        } catch ( ocAuthException $e ) {
        
            OcLogger::log($e->getMessage());
            $r = $this->getResponse();
            $r->setStatusCode(ApiHttpStatus::UNAUTHORIZED);
        
            $r->setContent(json_encode([
                    'code'=> ApiHttpStatus::UNAUTHORIZED,
                    'message' => $e->getMessage()
                ], JSON_PRETTY_PRINT));
        
        } catch ( ocException $e ) {
        
            OcLogger::log($e->getMessage());
            $r = $this->getResponse();
            $r->setStatusCode(ApiHttpStatus::SERVICE_UNAVAILABLE);
        
            $r->setContent(json_encode([
                    'code'=> ApiHttpStatus::SERVICE_UNAVAILABLE ,
                    'message' => $e->getMessage()
                ], JSON_PRETTY_PRINT));
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
