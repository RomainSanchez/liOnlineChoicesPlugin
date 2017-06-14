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
class ocApiCartItemsActions extends apiActions
{

    /**
     * @return ApiEntityService
     */
    public function getMyService()
    {
        return $this->getService('api_cartitems_service');
    }

    /**
     *
     * @param sfWebRequest $request
     * @return array (sfView::NONE)
     */
    public function getOne(sfWebRequest $request)
    {
        $cart_id = $request->getParameter('id', 0);
        $item_id = $request->getParameter('item_id', 0);

        /* @var $cartService ApiCartsService */
        $cartService = $this->getService('api_cartitems_service');
        $result = $cartService->findOne($cart_id, $item_id);

        return $this->createJsonResponse($result);
    }

    /**
     * @param sfWebRequest $request
     * @param array $query
     * @return array (sfView::NONE)
     */
    public function getAll(sfWebRequest $request, array $query)
    {
        $cart_id = $request->getParameter('id', 0);

        /** @var ApiCartItemsService $cartService */
        $cartitemsService = $this->getService('api_cartitems_service');
        $result = $this->getListWithDecorator($cartitemsService->findAll($cart_id, $query), $query);

        return $this->createJsonResponse($result);
    }

    /**
     * @param sfWebRequest $request
     * @param array $query
     * @return array (sfView::NONE)
     */
    public function create(sfWebRequest $request)
    {
        $cart_id = $request->getParameter('id', 0);
        $declination_id = $request->getParameter('declinationId', 0);

        /* @var $cartsService ApiCartsService */
        $cartsService = $this->getService('api_carts_service');
        if (!$cartsService->isCartEditable($cart_id)) {
            throw new liApiException("Cart not found or not editable (id=$cart_id)");
        }

        /* @var $cartItemsService ApiCartItemsService */
        $cartItemsService = $this->getService('api_cartitems_service');
        if (!$cartItemsService->isCartItemCreatable($cart_id, $declination_id)) {
            throw new liApiException('Time slot frozen');
        }
        try {
            $cartItem = $cartItemsService->create($cart_id, $request->getParameter('application/json'));
        } catch (liOnlineSaleException $exc) {
            throw new liApiException($exc->getMessage());
        }

        return $this->createJsonResponse($cartItem);
    }

    /**
     *
     * @param sfWebRequest $request
     * @return array (sfView::NONE)
     */
    public function update(sfWebRequest $request)
    {
        $status = ApiHttpStatus::SUCCESS;
        $message = ApiHttpMessage::UPDATE_SUCCESSFUL;

        $cart_id = $request->getParameter('id', 0);
        $item_id = $request->getParameter('item_id', 0);

        /* @var $cartsService ApiCartsService */
        $cartsService = $this->getService('api_carts_service');
        if (!$cartsService->isCartEditable($cart_id)) {
            throw new liApiException("Cart not found or not editable (id=$cart_id)");
        }

        /* @var $cartItemsService ApiCartItemsService */
        $cartItemsService = $this->getService('api_cartitems_service');
        if (!$cartItemsService->isCartItemEditable($cart_id, $item_id)) {
            throw new liApiException("Cart item not found or not editable (id=$item_id)");
        }
        $isSuccess = $cartItemsService->updateCartItem($cart_id, $item_id, $request->getParameter('application/json'));

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
     *
     * @param sfWebRequest $request
     * @return array (sfView::NONE)
     */
    public function delete(sfWebRequest $request)
    {

        $status = ApiHttpStatus::SUCCESS;
        $message = ApiHttpMessage::DELETE_SUCCESSFUL;

        $cart_id = $request->getParameter('id', 0);
        $item_id = $request->getParameter('item_id', 0);

        /* @var $cartsService ApiCartsService */
        $cartsService = $this->getService('api_carts_service');
        if (!$cartsService->isCartEditable($cart_id)) {
            throw new liApiException("Cart not found or not editable (id=$cart_id)");
        }

        /* @var $cartItemsService ApiCartItemsService */
        $cartItemsService = $this->getService('api_cartitems_service');
        if (!$cartItemsService->isCartItemEditable($cart_id, $item_id)) {
            throw new liApiException("Cart item not found or not editable (id=$item_id)");
        }
        $isSuccess = $cartItemsService->deleteCartItem($cart_id, $item_id);
        if (!$isSuccess) {
            $status = ApiHttpStatus::BAD_REQUEST;
            $message = ApiHttpMessage::DELETE_FAILED;
        }

        return $this->createJsonResponse([
                "code" => $status,
                'message' => $message
                ], $status);
    }


    /**
     * Action for reordering cart items
     * @param sfWebRequest $request
     * @return string (sfView::NONE)
     */
    public function executeReorder(sfWebRequest $request)
    {
        $status = ApiHttpStatus::SUCCESS;
        $message = ApiHttpMessage::UPDATE_SUCCESSFUL;

        $cart_id = $request->getParameter('id', 0);

        /* @var $cartsService ApiCartsService */
        $cartsService = $this->getService('api_carts_service');
        if (!$cartsService->isCartEditable($cart_id)) {
            throw new liApiNotFoundException("Cart not found or not editable (id=$cart_id)");
        }

        /* @var $cartItemsService ApiCartItemsService */
        $cartItemsService = $this->getService('api_cartitems_service');
        $isSuccess = $cartItemsService->reorderItems($cart_id, $request->getParameter('application/json'));

        if (!$isSuccess) {
            $status = ApiHttpStatus::BAD_REQUEST;
            $message = ApiHttpMessage::UPDATE_FAILED;
        }

        return $this->createJsonResponse([
                "code" => $status,
                'message' => $message
                ], $status);
    }


}
