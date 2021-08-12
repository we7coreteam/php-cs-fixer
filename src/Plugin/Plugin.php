<?php

/**
 * WeEngine Api System
 *
 * (c) We7Team 2019 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\PhpCsFixer\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;

class Plugin implements PluginInterface, EventSubscriberInterface {
	/**
	 * @var \Composer\Composer
	 */
	protected $composer;

	/**
	 * @var \Composer\IO\IOInterface
	 */
	protected $io;

	/**
	 * Apply plugin modifications to Composer
	 *
	 * @param Composer    $composer
	 * @param IOInterface $io
	 */
	public function activate(Composer $composer, IOInterface $io) {
		$this->composer = $composer;
		$this->io = $io;
	}

	public function deactivate(Composer $composer, IOInterface $io) {
	}

	public function uninstall(Composer $composer, IOInterface $io) {
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'post-autoload-dump' => 'installGitPreHook',
		);
	}

	public function installGitPreHook() {
		$config = $this->composer->getConfig();
		$filesystem = new Filesystem();
		$projectDir = dirname($filesystem->normalizePath(realpath(realpath($config->get('vendor-dir')))));
		if (!file_exists($projectDir . '/.php_cs')) {
			$filesystem->copy(dirname(__DIR__) . '/Helper/.php_cs', $projectDir . '/.php_cs');
		}

		$hookDir = $projectDir . '/.git/hooks/';
		if (!file_exists($hookDir)) {
			return true;
		}

		$fixFileName = 'pre-commit-cs-fix';
		if (file_exists($hookDir . $fixFileName)) {
			return true;
		}

		$filesystem->copy(dirname(__DIR__) . '/Helper/pre-commit-cs-fix', $hookDir . $fixFileName);
		$list[] = $hookDir . $fixFileName;

		if (file_exists($hookDir . 'pre-commit')) {
			file_put_contents($hookDir . 'pre-commit', "\n exec " . $hookDir . $fixFileName, FILE_APPEND);
		} else {
			file_put_contents($hookDir . 'pre-commit', "#!/bin/bash \n exec " . $hookDir . $fixFileName);
			$list[] = $hookDir . 'pre-commit';
		}
		$this->changePermission($list);
	}

	public static function install(\Composer\Script\Event $event) {
		$plugin = new static();
		$plugin->activate($event->getComposer(), $event->getIO());
		$plugin->installGitPreHook();
	}

	private function changePermission($list) {
		foreach ($list as $item) {
			try {
				chmod($item, 0777);
			} catch (\Throwable $e) {
				$this->io->writeError('chmod 777 ' . $item . ' fail, Please do it manually');
			}
		}
	}
}
