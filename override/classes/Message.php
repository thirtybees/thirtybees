<?php

class Message extends MessageCore {

    public static function getMessagesByOrderId($idOrder, $private = false, Context $context = null) {
        $m = parent::getMessagesByOrderId($idOrder, $private, $context);
        $o = Db::getInstance()->executeS('
	SELECT ct.*, m.*, e.`firstname` AS efirstname, e.`lastname` AS elastname
	FROM `' . _DB_PREFIX_ . 'customer_thread` ct
	LEFT JOIN `' . _DB_PREFIX_ . 'customer_message` m ON m.`id_customer_thread` = ct.`id_customer_thread`
	LEFT OUTER JOIN `' . _DB_PREFIX_ . 'employee` e ON e.`id_employee` = m.`id_employee`
	WHERE ct.`id_order` = ' . (int) $idOrder . '
	ORDER BY ct.`date_add` DESC'
        );
        return array_merge($o, $m);
    }

}
?>