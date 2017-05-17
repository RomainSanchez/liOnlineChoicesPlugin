<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of apiActions
 *
 * @author Glenn Cavarlé <glenn.cavarle@libre-informatique.fr>
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
abstract class jsonActions extends sfActions
{

    /**
     * 
     */
    public function preExecute()
    {

        // to be tested only if the module "is_secure"
        if ( $this->getSecurityValue('is_secure', false) ) {
            /* @var $oauthService ApiAuthService */
            $oauthService = $this->getService('oauth_service');

            
            if ( !$oauthService->isAuthenticated($this->getRequest()) ) {
                $this->getResponse()->setStatusCode(ApiHttpStatus::UNAUTHORIZED);
                throw new sfException('Invalid authentication credentials');
            }
            sfContext::getInstance()->getUser()->signIn(
                $oauthService->getToken()->OcApplication->User, true);
        }
        $contentType = $this->getRequest()->getContentType();
        $content = $this->getRequest()->getContent();

        if ( $contentType == 'application/json' && $content ) {
            $jsonParams = json_decode($content, true);
            foreach ( $jsonParams as $k => $v ) {
                $this->getRequest()->setParameter($k, $v);
            }
        }

        //disable layout
        $this->setLayout(false);
        //json response header
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
    }

    /**
     * Create a json response from an array and a status code
     * 
     * @param array|ArrayAccess $data
     * @return string (sfView::NONE)
     */
    protected function createJsonResponse($data, $status = ApiHttpStatus::SUCCESS)
    {
        // type check
        if ( !is_array($data) && !$data instanceof ArrayAccess ) {
            throw new liEvenementException('Argument 1 passed to jsonActions::createJsonResponse() must implement interface ArrayAccess or be an array, ' . (is_object($data) ? get_class($data) : gettype($data)) . ' given.');
        }

        $this->getResponse()->setStatusCode($status);
        return $this->renderText(json_encode($data, JSON_PRETTY_PRINT) . "\n");
    }

    /**
     * Retrieve a service by name
     * The service configurations is in SF_ROOT_DIR/config/services.yml and in SF_PLUGINS_DIR/[plugin]/config/services.yml
     */
    public function getService($aServiceName)
    {
        return $this->getContext()->getContainer()->get($aServiceName);
    }
}
