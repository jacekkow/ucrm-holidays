<?php

namespace SIPL\UCRM\Holidays;

use Umulmrum\Holiday\Constant\HolidayType;
use Umulmrum\Holiday\Filter\IncludeTypeFilter;
use Umulmrum\Holiday\HolidayCalculator;
use Umulmrum\Holiday\Model\HolidayList;

class HolidaySkipper {
	protected $helper;
	protected $skipDays = null;
	protected $holidays = null;

	function __construct(UcrmHelper $ucrmHelper) {
		$this->helper = $ucrmHelper;
	}

	protected function configure(array $years) {
		$config = $this->helper->getConfig();

		if ($this->skipDays === NULL) {
			$skipDays = [];
			for ($i = 1; $i <= 7; $i++) {
				if ($config['holidays_weekday_' . $i]) {
					$skipDays[] = (string)$i;
				}
			}
			$this->skipDays = $skipDays;
		}
		if ($this->holidays === NULL) {
			$holidays = new HolidayList();
			if ($config['holidays_region']) {
				$calculator = new HolidayCalculator();
				$holidays = $calculator->calculate($config['holidays_region'], $years);
				$holidays->filter(new IncludeTypeFilter(HolidayType::BANK | HolidayType::DAY_OFF));
			}
			$this->holidays = $holidays;
		}
	}

	function processInvoice(string $invoiceId) {
		$crm = $this->helper->getApi();
		$invoiceData = $crm->get('/invoices/' . $invoiceId);
		if ($invoiceData['status'] != 0) {
			// Ignore final invoices
			return FALSE;
		}

		$maturity = $invoiceData['maturityDays'];
		$changed = 0;

		$dueDate = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $invoiceData['dueDate']);
		$dueDateYear = intval($dueDate->format('Y'));
		$this->configure([$dueDateYear, $dueDateYear + 1]);

		while (in_array($dueDate->format('N'), $this->skipDays) || $this->holidays->isHoliday($dueDate)) {
			$maturity += 1;
			$changed += 1;
			$dueDate->add(new \DateInterval('P1D'));
		}

		if ($changed) {
			$crm->patch(
				'/invoices/' . $invoiceId,
				[
					'maturityDays' => $maturity,
				]
			);
			echo 'Invoice ' . $invoiceId . ': maturity adjusted - added ' . $changed . ' day(s)' . "\n";
		} else {
			echo 'Invoice ' . $invoiceId . ': nothing to do' . "\n";
		}
	}
}
