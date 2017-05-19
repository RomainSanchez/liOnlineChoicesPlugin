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
class ocApiCartsActions extends apiActions
{
    /**
     * @return ApiEntityService
     */
    public function getMyService()
    {
        return $this->getService('carts_service');
    }

    /**
     *
     * @param sfWebRequest $request
     * @param array $query
     * @return array
     */
    public function getAll(sfWebRequest $request, array $query)
    {
        /* @var $cartService ApiCartsService */
        $cartService = $this->getService('carts_service');
        $result = $this->getListWithDecorator($cartService->findAll($query), $query);
        return $this->createJsonResponse($result);
    }

    /**
     *
     * @param sfWebRequest $request
     * @return array
     */
    public function delete(sfWebRequest $request)
    {
        $status = ApiHttpStatus::SUCCESS;
        $message = ApiHttpMessage::DELETE_SUCCESSFUL;

        $cart_id = $request->getParameter('cart_id');

        /* @var $cartService ApiCartsService */
        $cartService = $this->getService('carts_service');
        $isSuccess = $cartService->deleteCart($cart_id);

        if (!$isSuccess) {
            $status = ApiHttpStatus::BAD_REQUEST;
            $message = ApiHttpMessage::DELETE_FAILED;
        }

        return $this->createJsonResponse([
                "code" => $status,
                'message' => $message
                ], $status);
    }
}
