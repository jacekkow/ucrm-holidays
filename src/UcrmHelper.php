<?php

namespace SIPL\UCRM\Holidays;

class UcrmHelper {
	protected $rootDirectory;
	protected $api = NULL;
	protected $config = NULL;
	protected $event = NULL;

	function __construct(?string $rootDirectory = NULL) {
		if ($rootDirectory === NULL) {
			$rootDirectory = __DIR__ . '/..';
		}
		$this->rootDirectory = $rootDirectory;
	}

	function getRootDirectory() {
		return $this->rootDirectory;
	}

	function getApi() {
		if ($this->api === NULL) {
			$this->api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create($this->rootDirectory);
		}
		return $this->api;
	}

	function getConfig() {
		if ($this->config === NULL) {
			$configManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create($this->rootDirectory);
			$this->config = $configManager->loadConfig();
		}
		return $this->config;
	}

	function getCurrentEvent() {
		if ($this->event === NULL) {
			try {
				if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
					throw new \RuntimeException('Failed to process event - wrong request method');
				}

				$data = file_get_contents('php://input');
				$data = json_decode($data, TRUE);
				if (!is_array($data)) {
					throw new \RuntimeException('Failed to process event - invalid JSON');
				}
				if (!isset($data['uuid'])) {
					throw new \RuntimeException('Failed to process event - missing UUID');
				}
				if (!ctype_alnum(strtr($data['uuid'], ['-' => '', '_' => '']))) {
					throw new \RuntimeException('Failed to process event - invalid UUID');
				}

				$event = $this->getApi()->get('/webhook-events/' . $data['uuid']);
				if ($event['uuid'] !== $data['uuid']) {
					throw new \RuntimeException('Failed to process event - event not found');
				}

				if (!ctype_digit($event['entityId'])) {
					throw new \RuntimeException('Failed to process event - invalid entity ID');
				}

				$this->event = $event;
			} catch (\Exception $e) {
				$this->event = $e;
			}
		}

		if ($this->event instanceof \Exception) {
			throw $this->event;
		}

		return $this->event;
	}
}
