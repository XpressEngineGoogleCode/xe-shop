<?php

/**
 * Handles database operations for Orders table
 *
 * @author Florin Ercus (dev@xpressengine.org)
 */
class OrderRepository extends BaseRepository
{

    public function getList($module_srl, $member_srl=null)
    {
        $params = array('module_srl'=> $module_srl);
        if ($member_srl) $params = array_merge($params, array('member_srl'=> $member_srl));
        $output = $this->query('getOrdersList', $params);
        foreach ($output->data as $i=>$data) $output->data[$i] = new Order((array) $data);
        return $output;
    }

    public function getRecentOrders($module_srl, $member_srl=null)
    {
        $params = array('module_srl'=> $module_srl);
        if ($member_srl) $params = array_merge($params, array('member_srl'=> $member_srl));
        $output = $this->query('getRecentOrders', $params);
        $orders = array();
        foreach ($output->data as $data) $orders[] = new Order((array) $data);
        return $orders;
    }

    public function getLastOrder($module_srl, $member_srl)
    {
        $params = array(
            'module_srl' => $module_srl ,
            'member_srl' => $member_srl ,
            'list_count' => 1
        );
        $output = $this->query('getRecentOrders', $params);
        $order = new Order((array) $output->data);
        return $order;
    }

    public function insert(Order &$order)
    {
        if ($order->order_srl) throw new Exception('A srl must NOT be specified for the insert operation!');
        $order->order_srl = getNextSequence();
        return $this->query('insertOrder', get_object_vars($order));
    }

    public function update(Order $order)
    {
        if (!is_numeric($order->order_srl)) throw new Exception('You must specify a srl for the updated order');
        return $this->query('updateOrder', get_object_vars($order));
    }

    public function deleteOrders(array $order_srls)
    {
        return $this->query('deleteOrders', array('order_srls' => $order_srls));
    }

    /**
     * Copies Cart properties into a new Order object
     * @param Cart $cart
     *
     * @return Order
     */
    public function getOrderFromCart(Cart $cart)
    {
        return new Order(array(
            'cart_srl' => $cart->cart_srl,
            'module_srl' => $cart->module_srl,
            'member_srl' => $cart->member_srl,
            'client_name' => $cart->getExtra('firstname') . ' ' . $cart->getExtra('lastname'),
            'client_email' => $cart->getExtra('email'),
            'client_company' => $cart->getExtra('company'),
            'billing_address' => (string) $cart->getBillingAddress(),
            'shipping_address' => (string) $cart->getShippingAddress(),
            'payment_method' => $cart->getExtra('payment_method'),
            'shipping_method' => $cart->getExtra('shipping_method'),
            'shipping_cost' => '0', // TODO Add shipping cost
            'total' => $cart->getTotal(),
            'vat' => '0', // TODO Add VAT
            'order_status' => 'Pending', // TODO Add order status
            'ip' => $_SERVER['REMOTE_ADDR']
        ));
    }


    public function insertOrderProduct($order_srl, SimpleProduct $product, $quantity = 1)
    {
        $params = array(
            'order_srl' => $order_srl,
            'product_srl' => $product->product_srl,
            'quantity' => $quantity,
            'member_srl' => $product->member_srl,
            'parent_product_srl' => $product->parent_product_srl,
            'product_type' => $product->product_srl,
            'title' => $product->title,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'sku' => $product->sku,
            'weight' => $product->weight,
            'status' => $product->status,
            'friendly_url' => $product->friendly_url,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'qty' => $product->qty,
            'in_stock' => $product->in_stock,
            'primary_image_filename' => $product->primary_image_filename,
            'regdate' => $product->regdate,
            'last_update' => $product->last_updated
        );
        return $this->query('insertOrderProduct', $params);
    }

    public function deleteOrderProducts($order_srl, array $product_srls=null)
    {
        return $this->query('deleteOrderProducts', array('order_srl' => $order_srl, 'product_srls' => $product_srls));
    }

    public function getOrderBySrl($srl)
    {
        $output = $this->query('getOrderBySrl', array('order_srl'=> $srl));
        if(empty($output->data)){
            return null;
        }else{
            $order = new Order((array) $output->data);
            $this->getOrderShipment($order);
            $this->getOrderInvoice($order);
            return $order;
        }
    }


    public function getOrderByTransactionId($transaction_id)
    {
        $output = $this->query('getOrderByTransactionId', array('transaction_id'=> $transaction_id));
        if(empty($output->data)){
            return null;
        }else{
            $order = new Order((array) $output->data);
            $this->getOrderShipment($order);
            $this->getOrderInvoice($order);
            return $order;
        }
    }

    public function getOrderShipment($order){
        $shopModel = getModel('shop');
        $shipmentRepository = $shopModel->getShipmentRepository();
        $order->shipment = $shipmentRepository->getShipmentByOrderSrl($order->order_srl);
    }

    public function getOrderInvoice($order){
        $shopModel = getModel('shop');
        $invoiceRepository = $shopModel->getInvoiceRepository();
        $order->invoice = $invoiceRepository->getInvoiceByOrderSrl($order->order_srl);
    }

    public function getOrderItems($order)
    {   $shopModel = getModel('shop');
        $productRepository = $shopModel->getProductRepository();
        $args = new stdClass();
        $args->order_srl = $order->order_srl;
        $output = $this->query('getOrderItems',$args,true);
        foreach($output->data as $item){
            $product = $productRepository->getProduct($item->product_srl);
            $product->ordered_qty = $item->quantity;
            $ordered_items[] = $product;
        }
        return $ordered_items;
    }


}