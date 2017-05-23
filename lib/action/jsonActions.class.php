<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once __DIR__ . '../../../lib/http/ApiHttpStatus.class.php';

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
        $this->authenticate();
        $this->convertJsonToParameters();
        //disable layout
        $this->setLayout(false);
        //json response header
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
    }

    private function authenticate()
    {
        $request = $this->getRequest();
       
        // to be tested only if the module "is_secure"
       // if ( $this->getSecurityValue('is_secure', false) ) {
       
       	
       $route = $request->getRequestParameters()['_sf_route'];
       $secure = isset($route->getOptions()['secure'])? $route->getOptions()['secure'] : true;
       
		    if($secure){
		      /* @var $oauthService ApiAuthService */
		      $oauthService = $this->getService('api_oauth_service');
			
		      //check oauth authentification
		      if ( !$oauthService->authenticate($request) ) {
		          throw new ocAuthCredentialsException('Invalid authentication credentials');
		      }
		      //assign user
		      sfContext::getInstance()->getUser()->signIn(
		          $oauthService->getToken()->OcApplication->User, true);
		  }
    }

    private function convertJsonToParameters()
    {
        $contentType = $this->getRequest()->getContentType();
        $content = $this->getRequest()->getContent();

        if ( $contentType == 'application/json' && $content ) {
            $jsonParams = json_decode($content, true);
            
            $this->getRequest()->setParameter('application/json', $jsonParams);
            foreach ( $jsonParams as $k => $v ) {
                $this->getRequest()->setParameter($k, $v);
            }
        }
        
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
     * Create an empty response with a status code
     * 
     * @param array|ArrayAccess $data
     * @return string (sfView::NONE)
     */
    protected function createEmptyResponse($status = ApiHttpStatus::NO_CONTENT)
    {
        $this->getResponse()->setStatusCode($status);
        return sfView::NONE;
    }

    /**
     * Create an error json response from a message and a status code
     * 
     * @param string $message
     * @return string (sfView::NONE)
     */
    protected function createJsonErrorResponse($message, $status = ApiHttpStatus::SERVICE_UNAVAILABLE)
    {
        return $this->createJsonResponse(['code' => $status, 'message' => $message], $status);
    }

    /**
     * Retrieve a service by name
     * The service configurations is in SF_ROOT_DIR/config/services.yml and in SF_PLUGINS_DIR/[plugin]/config/services.yml
     */
    public function getService($aServiceName)
    {
        return $this->getContext()->getContainer()->get($aServiceName);
    }
    
    /**
     * Retrieve the current service
     *
     * @return ApiEntityService
     */
    public function getMyService()
    {
        throw new ocNotImplementedException('No "getMyService" defined, and no specific get*() defined neither.');
    }
}
