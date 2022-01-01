<?php
require_once(__DIR__ . '/vendor/autoload.php');

try {
	$helper = new \SIPL\UCRM\Holidays\UcrmHelper();
	$event = $helper->getCurrentEvent();

	if (!isset($event['entity'])) {
		throw new Exception('Webhook entity empty!');
	} elseif ($event['entity'] === 'invoice') {
		$skipper = new \SIPL\UCRM\Holidays\HolidaySkipper($helper);
		$skipper->processInvoice($event['entityId']);
	} elseif ($event['entity'] === 'webhook') {
		echo 'Webhook OK!';
		die();
	} else {
		echo 'Nothing to do with entity ' . $event['entity'];
		die();
	}
} catch (Exception $e) {
	header('HTTP/1.1 500 Internal Server Error');
	echo $e;
}
