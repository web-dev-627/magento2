<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 06/12/2016
 * Time: 14:43
 */

namespace Notive\Ewarehousing\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderAddressInterface;

/**
 * Class EwarehousingOrder
 * @package Notive\Ewarehousing\Model
 */
class EwarehousingOrder
{
    /**
     * MageOrder to Order
     * @param Order $mageOrder
     * @return array
     */
    public function OrderToArray(Order $mageOrder)
    {
        $billing = $mageOrder->getBillingAddress();
        $shipping = $mageOrder->getShippingAddress();
        $address = $shipping ? $shipping : $billing;
        list($street, $housenumber, $addition) = $this->splitAddress($address);

        $date = date('Y-m-d');
        if ($mageOrder->getData('preferred_delivery_date')) {
            $date = $mageOrder->getData('preferred_delivery_date');
        }

        $data = [
            'date' => $date,
            'reference' => $mageOrder->getRealOrderId(),
            'address' => [
                'addressed_to' => $address->getFirstname().' '.$address->getLastname(),
                'street' => $street,
                'street_number' => $housenumber,
                'number_extention' => $addition,
                'zipcode' => $address->getPostcode(),
                'city' => $address->getCity(),
                'country_code' => $address->getCountryId(),
                'phone_number' => $address->getTelephone(),
                'email' => $address->getEmail(),
            ],
            'order_lines' => [],
        ];

        if (!empty($address->getCompany())) {
            $data['address']['addressed_to'] = $address->getCompany();
            $data['address']['contactperson'] = $address->getFirstname().' '.$address->getLastname();
        }

        /** @var Order\Item $item */
        foreach ($mageOrder->getAllVisibleItems() as $item) {
            if ($item->isDeleted() && $item->getParentItemId()) {
                continue;
            }

            $category = '';
            $imageUrl = '';
            $description = $item->getSku();
            if ($item->getProduct()->getCategory()) {
                $category = $item->getProduct()->getCategory()->getName();
            }
            if ($item->getProduct()->getImage()) {
                $imageUrl = $item->getProduct()->getImage();
            }
            if ($item->getDescription()) {
                $description = $item->getDescription();
            }

            $data['order_lines'][] = [
                'article_code' => $item->getSku(),
                'article_description' => $description,
                'quantity' => (int)$item->getQtyOrdered(),
                'title' => $item->getName(),
                'description' => $description,
                'deep_url' => $item->getProduct()->getProductUrl(),
                'categories' => $category,
                'image_url' => $imageUrl,
            ];
        }
        return $data;
    }

    /**
     * Split the address into street, houseNumber and addition
     * @param OrderAddressInterface $address
     * @return array
     */
    private function splitAddress(OrderAddressInterface $address) {
        $full_street = implode(' ', $address->getStreet());
        $street_number = '';
        $street_number_suffix = '';


        if (preg_match("/^\\s*(.+)\\s+(\\d+)\\s*(\\S*\\s+\\d+\\s*\\S*)$/", $full_street, $street_elements)
            || preg_match("/^\\s*(.+)\\s+(\\d+)\\s*(,\\s*.*)$/", $full_street, $street_elements)
            || preg_match("/^\\s*(.+)\\s+(\\d+)\\s*(.*)$/", $full_street, $street_elements)
        ) {
            $street = $street_elements[1];
            $street_number = $street_elements[2];
            $street_number_suffix = trim($street_elements[3]);
        } elseif (preg_match("/^\\s*(\\d+)(\\S*)\\s+(.*)$/", $full_street, $street_elements)) {
            $street = $street_elements[3];
            $street_number = $street_elements[1];
            $street_number_suffix = $street_elements[2];
        } elseif (preg_match("/^\\s*(.+\\D)\\s*(\\d+)\\s*(\\D+\\s*\\d*\\s*\\S*)$/", $full_street, $street_elements)
            || preg_match("/^\\s*(.+\\D)\\s*(\\d+)\\s*(.*)$/", $full_street, $street_elements)
        ) {
            $street = $street_elements[1];
            $street_number = $street_elements[2];
            $street_number_suffix = trim($street_elements[3]);
        } else {
            $street = $full_street;
        }

        $resultArray = [
            "street" => $street,
            "housenumber" => $street_number,
            "addition" => $street_number_suffix,
        ];

        return array_values($resultArray);
    }
}