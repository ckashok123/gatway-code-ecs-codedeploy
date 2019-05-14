<?php 
namespace Dimebox\Payment\Api;
 
interface TransactionInterface
{
    /**
     * Returns report status message
     *
     * @api
     * @param string $id transaction id.
     * @return settlement report status.
     */
    public function update($id);
}