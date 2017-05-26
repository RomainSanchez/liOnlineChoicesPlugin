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
        return $this->getService('api_carts_service');
    }

    /**
     *
     * @param sfWebRequest $request
     * @param array $query
     * @return array (sfView::NONE)
     */
    public function getAll(sfWebRequest $request, array $query)
    {
        /* @var $cartService ApiCartsService */
        $cartService = $this->getService('api_carts_service');
        $result = $this->getListWithDecorator($cartService->findAll($query), $query);
        return $this->createJsonResponse($result);
    }

    /**
     * Action for checkout cart complete
     * @param sfWebRequest $request
     * @return string (sfView::NONE)
     */
    public function executeComplete(sfWebRequest $request)
    {
        $status = ApiHttpStatus::NO_CONTENT;
        $message = ApiHttpMessage::UPDATE_SUCCESSFUL;

        $cart_id = $request->getParameter('cart_id', 0);

        /* @var $cartsService ApiCartsService */
        $cartsService = $this->getService('api_carts_service');
        if (!$cartsService->isCartEditable($cart_id)) {
            return $this->createBadRequestResponse(['error' => "Cart not found or not editable (id=$cart_id)"]);
        }

        $isSuccess = $cartsService->updateCart($cart_id, ['checkoutState' => 'new']);

        if (!$isSuccess) {
            $status = ApiHttpStatus::BAD_REQUEST;
            $message = ApiHttpMessage::UPDATE_FAILED;
        }

        return $this->createJsonResponse([
                "code" => $status,
                'message' => $message
                ], $status);
    }

    /**
     * Action for a POST|PUT:/[resource]/id request
     * The specified id has to be retrieved from the $request
     * The id key is defined in routing.yml
     *
     * @param sfWebRequest $request
     * @return string (sfView::NONE)
     */
    public function update(sfWebRequest $request)
    {
        $status = ApiHttpStatus::BAD_REQUEST;
        $message = "Update failed. If you want to complete a checkout: POST /api/v2/checkouts/complete/{cart_id}";

        return $this->createJsonResponse([
                "code" => $status,
                'message' => $message
                ], $status);
    }

    /**
     *
     * @param sfWebRequest $request
     * @return array (sfView::NONE)
     */
    public function delete(sfWebRequest $request)
    {
        $status = ApiHttpStatus::SUCCESS;
        $message = ApiHttpMessage::DELETE_SUCCESSFUL;

        $cart_id = $request->getParameter('cart_id');

        /* @var $cartService ApiCartsService */
        $cartService = $this->getService('api_carts_service');
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
