<?php
namespace TYPO3\Setup\Step;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Setup".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3,
	TYPO3\Form\Core\Model\FormDefinition;

/**
 * @FLOW3\Scope("singleton")
 */
abstract class AbstractStep implements \TYPO3\Setup\Step\StepInterface {

	/**
	 * The settings of the TYPO3.Form package
	 *
	 * @var array
	 */
	protected $formSettings;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var array
	 */
	protected $distributionSettings;

	/**
	 * @internal
	 */
	public function initializeObject() {
		$this->formSettings = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Form');
	}

	/**
	 * Sets options of this step
	 *
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * Sets global settings of the FLOW3 distribution
	 *
	 * @param array $distributionSettings
	 * @return void
	 */
	public function setDistributionSettings(array $distributionSettings) {
		$this->distributionSettings = $distributionSettings;
	}

	/**
	 * Get the preset configuration by $presetName, taking the preset hierarchy
	 * (specified by *parentPreset*) into account.
	 *
	 * @param string $presetName name of the preset to get the configuration for
	 * @return array the preset configuration
	 * @throws \TYPO3\Form\Exception\PresetNotFoundException if preset with the name $presetName was not found
	 */
	public function getPresetConfiguration($presetName) {
		if (!isset($this->formSettings['presets'][$presetName])) {
			throw new \TYPO3\Form\Exception\PresetNotFoundException(sprintf('The Preset "%s" was not found underneath TYPO3: Form: presets.', $presetName), 1332170104);
		}
		$preset = $this->formSettings['presets'][$presetName];
		if (isset($preset['parentPreset'])) {
			$parentPreset = $this->getPresetConfiguration($preset['parentPreset']);
			unset($preset['parentPreset']);
			$preset = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($parentPreset, $preset);
		}
		return $preset;
	}

	/**
	 * Returns the form definitions for the step
	 *
	 * @param \Closure $callback closure to be invoked when the form has been submitted successfully
	 * @return \TYPO3\Form\Core\Model\FormDefinition
	 * @api
	 */
	final public function getFormDefinition(\Closure $callback) {
		$fullyQualifiedClassName = get_class($this);
		$formIdentifier = lcfirst(substr($fullyQualifiedClassName, strrpos($fullyQualifiedClassName, '\\') + 1));
		$formConfiguration = $this->getPresetConfiguration('default');
		$formDefinition = new FormDefinition($formIdentifier, $formConfiguration);
		$this->buildForm($formDefinition);

		$closureFinisher = new \TYPO3\Form\Finishers\ClosureFinisher();
		$closureFinisher->setOption('closure', $callback);
		$formDefinition->addFinisher($closureFinisher);

		return $formDefinition;
	}

	/**
	 * @abstract
	 * @param \TYPO3\Form\Core\Model\FormDefinition $formDefinition
	 * @return void
	 * @api
	 */
	abstract protected function buildForm(\TYPO3\Form\Core\Model\FormDefinition $formDefinition);

}