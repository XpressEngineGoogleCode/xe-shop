<?php
class Invoice extends BaseItem
{

    public
        $invoice_srl,
        $order_srl,
        $module_srl,
        $order,
        $comments,
        $regdate;

    /** @var InvoiceRepository */
    public $repo;

}