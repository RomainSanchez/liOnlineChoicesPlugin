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
        return $this->getService('cartitems_service');
    }

    /**
     *
     * @param sfWebRequest $request
     * @return array
     */
    public function getOne(sfWebRequest $request)
    {
        $cart_id = $request->getParameter('id', 0);
        $item_id = $request->getParameter('item_id', 0);

        /* @var $cartService ApiCartsService */
        $cartService = $this->getService('cartitems_service');
        $result = $cartService->findOne($cart_id, $item_id);

        return $this->createJsonResponse($result);
    }

    /**
     * @param sfWebRequest $request
     * @param array $query
     * @return array
     */
    public function getAll(sfWebRequest $request, array $query)
    {
        $cart_id = $request->getParameter('id', 0);

        /** @var ApiCartItemsService $cartService */
        $cartitemsService = $this->getService('cartitems_service');
        $result = $this->getListWithDecorator($cartitemsService->findAll($cart_id, $query), $query);

        return $this->createJsonResponse($result);
    }

    /**
     * @param sfWebRequest $request
     * @param array $query
     * @return array
     */
    public function create(sfWebRequest $request)
    {
        /* @var $cartsService ApiCartsService */
        $cartsService = $this->getService('carts_service');
        $cart_id = $request->getParameter('id', 0);
        $cart = $cartsService->findOneById($cart_id);
        if ( 0 == count($cart) ) {
            return $this->createBadRequestResponse(['error' => 'Cart not found with id=' . $cart_id]);
        }

        /* @var $cartItemsService ApiCartItemsService */
        $cartItemsService = $this->getService('cartitems_service');
        try {
            $cartItem = $cartItemsService->create($cart_id, $request->getPostParameters());
        } catch (liOnlineSaleException $exc) {
            return $this->createBadRequestResponse(['error' => $exc->getMessage()]);
        }

        $result = $cartsService->findOneById($cart_id);
        return $this->createJsonResponse($result);
    }

    /**
     *
     * @param sfWebRequest $request
     * @return array
     */
    public function update(sfWebRequest $request)
    {
        $status = ApiHttpStatus::SUCCESS;
        $message = ApiHttpMessage::UPDATE_SUCCESSFUL;

        $cart_id = $request->getParameter('id', 0);
        $item_id = $request->getParameter('item_id', 0);

        /* @var $cartService ApiCartsService */
        $cartService = $this->getService('cartitems_service');
        $isSuccess = $cartService->updateCartItem($cart_id, $item_id, $request->getPostParameters());

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
     * @return array
     */
    public function delete(sfWebRequest $request)
    {

        $status = ApiHttpStatus::SUCCESS;
        $message = ApiHttpMessage::DELETE_SUCCESSFUL;

        $cart_id = $request->getParameter('id', 0);
        $item_id = $request->getParameter('item_id', 0);

        /* @var $cartItemsService ApiCartItemsService */
        $cartItemsService = $this->getService('cartitems_service');
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
}
