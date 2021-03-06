<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Abstract Form View Helper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class will
 * automatically set the name and value of a form element.
 *
 * @api
 */
abstract class AbstractFormFieldViewHelper extends AbstractFormViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('name', 'string', 'Name of input tag');
		$this->registerArgument('value', 'mixed', 'Value of input tag');
		$this->registerArgument('property', 'string', 'Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.');
	}

	/**
	 * Get the name of this form element.
	 * Either returns arguments['name'], or the correct name for Object Access.
	 *
	 * In case property is something like bla.blubb (hierarchical), then [bla][blubb] is generated.
	 *
	 * @return string Name
	 */
	protected function getName() {
		$name = $this->getNameWithoutPrefix();
		return $this->prefixFieldName($name);
	}

	/**
	 * Shortcut for retrieving the request from the controller context
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Request
	 */
	protected function getRequest() {
		return $this->controllerContext->getRequest();
	}

	/**
	 * Get the name of this form element, without prefix.
	 *
	 * @return string name
	 */
	protected function getNameWithoutPrefix() {
		if ($this->isObjectAccessorMode()) {
			$formObjectName = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName');
			if (!empty($formObjectName)) {
				$propertySegments = explode('.', $this->arguments['property']);
				$propertyPath = '';
				foreach ($propertySegments as $segment) {
					$propertyPath .= '[' . $segment . ']';
				}
				$name = $formObjectName . $propertyPath;
			} else {
				$name = $this->arguments['property'];
			}
		} else {
			$name = $this->arguments['name'];
		}
		if ($this->hasArgument('value') && is_object($this->arguments['value'])) {
			// @todo Use  $this->persistenceManager->isNewObject() once it is implemented
			if (NULL !== $this->persistenceManager->getIdentifierByObject($this->arguments['value'])) {
				$name .= '[__identity]';
			}
		}
		return $name;
	}

	/**
	 * Get the value of this form element.
	 * Either returns arguments['value'], or the correct value for Object Access.
	 *
	 * @param bool $convertObjects whether or not to convert objects to identifiers
	 * @return mixed Value
	 */
	protected function getValue($convertObjects = TRUE) {
		$value = NULL;

		if ($this->hasArgument('value')) {
			$value = $this->arguments['value'];
		} elseif ($this->isObjectAccessorMode()) {
			if ($this->hasMappingErrorOccurred()) {
				$value = $this->getLastSubmittedFormData();
			} else {
				$value = $this->getPropertyValue();
			}
			$this->addAdditionalIdentityPropertiesIfNeeded();
		}

		if ($convertObjects) {
			$value = $this->convertToPlainValue($value);
		}
		return $value;
	}

	/**
	 * Converts an arbitrary value to a plain value
	 *
	 * @param mixed $value The value to convert
	 * @return mixed
	 */
	protected function convertToPlainValue($value) {
		if (is_object($value)) {
			$identifier = $this->persistenceManager->getIdentifierByObject($value);
			if ($identifier !== NULL) {
				$value = $identifier;
			}
		}
		return $value;
	}

	/**
	 * Checks if a property mapping error has occurred in the last request.
	 *
	 * @return bool TRUE if a mapping error occurred, FALSE otherwise
	 */
	protected function hasMappingErrorOccurred() {
		return $this->getRequest()->getOriginalRequest() !== NULL;
	}

	/**
	 * Get the form data which has last been submitted; only returns valid data in case
	 * a property mapping error has occurred. Check with hasMappingErrorOccurred() before!
	 *
	 * @return mixed
	 */
	protected function getLastSubmittedFormData() {
		$propertyPath = rtrim(preg_replace('/(\\]\\[|\\[|\\])/', '.', $this->getNameWithoutPrefix()), '.');
		$value = ObjectAccess::getPropertyPath($this->controllerContext->getRequest()->getOriginalRequest()->getArguments(), $propertyPath);
		return $value;
	}

	/**
	 * Add additional identity properties in case the current property is hierarchical (of the form "bla.blubb").
	 * Then, [bla][__identity] has to be generated as well.
	 *
	 * @return void
	 */
	protected function addAdditionalIdentityPropertiesIfNeeded() {
		if (!$this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject')) {
			return;
		}
		$propertySegments = explode('.', $this->arguments['property']);
		// hierarchical property. If there is no "." inside (thus $propertySegments == 1), we do not need to do anything
		if (count($propertySegments) < 2) {
			return;
		}
		$formObject = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject');
		$objectName = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName');
		// If count == 2 -> we need to go through the for-loop exactly once
		for ($i = 1; $i < count($propertySegments); $i++) {
			$object = ObjectAccess::getPropertyPath($formObject, implode('.', array_slice($propertySegments, 0, $i)));
			$objectName .= '[' . $propertySegments[($i - 1)] . ']';
			$hiddenIdentityField = $this->renderHiddenIdentityField($object, $objectName);
			// Add the hidden identity field to the ViewHelperVariableContainer
			$additionalIdentityProperties = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties');
			$additionalIdentityProperties[$objectName] = $hiddenIdentityField;
			$this->viewHelperVariableContainer->addOrUpdate(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties', $additionalIdentityProperties);
		}
	}

	/**
	 * Get the current property of the object bound to this form.
	 *
	 * @return mixed Value
	 */
	protected function getPropertyValue() {
		if (!$this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject')) {
			return NULL;
		}
		$formObject = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject');
		$propertyName = $this->arguments['property'];
		if (is_array($formObject)) {
			return isset($formObject[$propertyName]) ? $formObject[$propertyName] : NULL;
		}
		return ObjectAccess::getPropertyPath($formObject, $propertyName);
	}

	/**
	 * Internal method which checks if we should evaluate a domain object or just output arguments['name'] and arguments['value']
	 *
	 * @return bool TRUE if we should evaluate the domain object, FALSE otherwise.
	 */
	protected function isObjectAccessorMode() {
		return $this->hasArgument('property') && $this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName');
	}

	/**
	 * Add an CSS class if this view helper has errors
	 *
	 * @return void
	 */
	protected function setErrorClassAttribute() {
		if ($this->hasArgument('class')) {
			$cssClass = $this->arguments['class'] . ' ';
		} else {
			$cssClass = '';
		}

		$mappingResultsForProperty = $this->getMappingResultsForProperty();
		if ($mappingResultsForProperty->hasErrors()) {
			if ($this->hasArgument('errorClass')) {
				$cssClass .= $this->arguments['errorClass'];
			} else {
				$cssClass .= 'error';
			}
			$this->tag->addAttribute('class', $cssClass);
		}
	}

	/**
	 * Get errors for the property and form name of this view helper
	 *
	 * @return \TYPO3\CMS\Extbase\Error\Result Array of errors
	 */
	protected function getMappingResultsForProperty() {
		if (!$this->isObjectAccessorMode()) {
			return new \TYPO3\CMS\Extbase\Error\Result();
		}
		$originalRequestMappingResults = $this->getRequest()->getOriginalRequestMappingResults();
		$formObjectName = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName');
		return $originalRequestMappingResults->forProperty($formObjectName)->forProperty($this->arguments['property']);
	}

	/**
	 * Renders a hidden field with the same name as the element, to make sure the empty value is submitted
	 * in case nothing is selected. This is needed for checkbox and multiple select fields
	 *
	 * @return string the hidden field.
	 */
	protected function renderHiddenFieldForEmptyValue() {
		$hiddenFieldNames = array();
		if ($this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields')) {
			$hiddenFieldNames = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields');
		}
		$fieldName = $this->getName();
		if (substr($fieldName, -2) === '[]') {
			$fieldName = substr($fieldName, 0, -2);
		}
		if (!in_array($fieldName, $hiddenFieldNames)) {
			$hiddenFieldNames[] = $fieldName;
			$this->viewHelperVariableContainer->addOrUpdate(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields', $hiddenFieldNames);
			return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="" />';
		}
		return '';
	}

}
