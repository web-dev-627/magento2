<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 28/11/2016
 * Time: 17:12
 */

namespace Notive\Ewarehousing\Model\Config;

use Magento\Sales\Model\Order\Status as StatusModel;
use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * Class OrderStatuses
 * @package Notive\Ewarehousing\Model\Config
 */
class OrderStatuses
{
    const ORDER_STATUS_CODE_SENT = 'notive_ewarehousing_sent';
    const ORDER_STATUS_LABEL_SENT = 'Sent to eWarehousing';
    const ORDER_STATUS_CODE_ERROR = 'notive_ewarehousing_error';
    const ORDER_STATUS_LABEL_ERROR = 'Not sent to eWarehousing';

    /** @var array  */
    private $mageStatus;

    /** @var StatusModel $statusModel */
    private $statusModel;

    /**
     * OrderStatuses constructor.
     * @param Status $status
     * @param StatusModel $statusModel
     */
    public function __construct(Status $status, StatusModel $statusModel)
    {
        $this->mageStatus = $status->toOptionArray();
        $this->statusModel = $statusModel;
    }

    /**
     * Create option array
     * @return array
     */
    public function toOptionArray()
    {
        $optionsArray = [];
        $gotSent = false;
        $gotError = false;
        foreach ($this->mageStatus as $magesStatus) {
            if (empty($magesStatus['value'])) {
                continue;
            }
            if ($magesStatus['value'] == self::ORDER_STATUS_CODE_SENT) {
                $gotSent = true;
            }
            if ($magesStatus['value'] == self::ORDER_STATUS_CODE_ERROR) {
                $gotError = true;
            }
            $optionsArray[] = $magesStatus;
        }
        if (!$gotSent) {
            $optionsArray[] = $this->create(self::ORDER_STATUS_CODE_SENT);
        }
        if (!$gotError) {
            $optionsArray[] = $this->create(self::ORDER_STATUS_CODE_ERROR);
        }
        return $optionsArray;
    }

    /**
     * Create order state
     * @param string $code
     * @return array|bool
     */
    private function create($code)
    {
        if ($code == self::ORDER_STATUS_CODE_SENT) {
            // Create sent state
            $this->statusModel->setData('status', self::ORDER_STATUS_CODE_SENT)->setData('label', self::ORDER_STATUS_LABEL_SENT)->save();
            $this->statusModel->assignState(Order::STATE_PROCESSING, false, true);
            return [
                'value' => self::ORDER_STATUS_CODE_SENT,
                'label' => self::ORDER_STATUS_LABEL_SENT.' (new)',
            ];
        }
        if ($code == self::ORDER_STATUS_CODE_ERROR) {
            // Create error state
            $this->statusModel->setData('status', self::ORDER_STATUS_CODE_ERROR)->setData('label', self::ORDER_STATUS_LABEL_ERROR)->save();
            $this->statusModel->assignState(Order::STATE_HOLDED, false, true);
            return [
                'value' => self::ORDER_STATUS_CODE_ERROR,
                'label' => self::ORDER_STATUS_LABEL_ERROR.' (new)',
            ];
        }
        return false;
    }
}
