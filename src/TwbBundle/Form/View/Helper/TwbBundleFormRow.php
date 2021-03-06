<?php
namespace TwbBundle\Form\View\Helper;
class TwbBundleFormRow extends \Zend\Form\View\Helper\FormRow
{

    /**
     * @var string
     */
    private static $formGroupFormat = '<div class="form-group %s">%s</div>';

    /**
     * @var string
     */
    private static $horizontalLayoutFormat = '%s<div class="%s">%s</div>';

    /**
     * @var string
     */
    private static $helpBlockFormat = '<p class="help-block">%s</p>';

    /**
     * The class that is added to element that have errors
     * @var string
     */
    protected $inputErrorClass = '';

    /**
     * @see \Zend\Form\View\Helper\FormRow::render()
     * @param \Zend\Form\ElementInterface $oElement
     * @return string
     */
    public function render(\Zend\Form\ElementInterface $oElement)
    {
    	$sElementType = $oElement->getAttribute('type');

    	//Nothing to do for hidden elements which have no messages
    	if ($sElementType === 'hidden' && !$oElement->getMessages()) {
    		return parent::render($oElement);
    	}

        //Retrieve expected layout
        $sLayout = $oElement->getOption('twb-layout');

        //Partial rendering
        if ($this->partial) {
            return $this->view->render($this->partial, array(
                'element' => $oElement,
                'label' => $this->renderLabel($oElement),
                'labelAttributes' => $this->labelAttributes,
                'labelPosition' => $this->labelPosition,
                'renderErrors' => $this->renderErrors,
            ));
        }

        $sRowClass = '';

        //Validation state
        if (($sValidationState = $oElement->getOption('validation-state'))) $sRowClass .= ' has-' . $sValidationState;

        //"has-error" validation state case
        if (count($oElement->getMessages())){
        	$sRowClass .= ' has-error';
        	//Element have errors
        	if ($sInputErrorClass = $this->getInputErrorClass()) {
        		if ($sElementClass = $oElement->getAttribute('class')) {
                    if (!preg_match('/(\s|^)' . preg_quote($sInputErrorClass, '/') . '(\s|$)/', $sElementClass)) {
                        $oElement->setAttribute('class', trim($sElementClass . ' ' . $sInputErrorClass));
                    }
                } else $oElement->setAttribute('class', $sInputErrorClass);
            }
        }

        //Column size
        if (
            ($sColumSize = $oElement->getOption('column-size'))
            && (
                $sLayout !== \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL
            )
        ) {
            $sRowClass .= ' col-' . $sColumSize;
        }

        //Render element
        $sElementContent = $this->renderElement($oElement);

        //Render form row
        if (in_array($sElementType, array('checkbox')) && $sLayout !== \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL) {
            return $sElementContent . PHP_EOL;
        }
        if ($sElementType === 'submit' && $sLayout === \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_INLINE) {
            return $sElementContent . PHP_EOL;
        }

        return sprintf(
            self::$formGroupFormat,
            $sRowClass,
            $sElementContent
        ) . PHP_EOL;
    }

    /**
     * Render element's label
     * @param \Zend\Form\ElementInterface $oElement
     * @return string
     */
    protected function renderLabel(\Zend\Form\ElementInterface $oElement)
    {
        if (($sLabel = $oElement->getLabel()) && ($oTranslator = $this->getTranslator())) {
            $sLabel = $oTranslator->translate($sLabel, $this->getTranslatorTextDomain());
        }
        return $sLabel;
    }

    /**
     * Render element
     * @param \Zend\Form\ElementInterface $oElement
     * @throws \DomainException
     * @return string
     */
    protected function renderElement(\Zend\Form\ElementInterface $oElement)
    {
        //Retrieve expected layout
        $sLayout = $oElement->getOption('twb-layout');

        //Render label
        $sLabelOpen = $sLabelClose = $sLabelContent = '';

        if ($sLabelContent = $this->renderLabel($oElement)) {
            //Multicheckbox elements have to be handled differently as the HTML standard does not allow nested labels. The semantic way is to group them inside a fieldset
            $sElementType = $oElement->getAttribute('type');

            //Checkbox & radio elements are a special case, because label is rendered by their own helper
            if ($sElementType === 'checkbox') {
                if (!$oElement->getLabelAttributes() && $this->labelAttributes) $oElement->setLabelAttributes($this->labelAttributes);

                //Render element input
                if ($sLayout !== \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL) {
                    return $this->getElementHelper()->render($oElement);
                }
            }
            //Button element is a special case, because label is always rendered inside it
            elseif ($oElement instanceof \Zend\Form\Element\Button) {
                $sLabelContent = '';
            } else {
                $aLabelAttributes = $oElement->getLabelAttributes() ? : $this->labelAttributes;

                //Validation state
                if ($oElement->getOption('validation-state') || count($oElement->getMessages())) {
                    if (empty($aLabelAttributes['class'])) {
                        $aLabelAttributes['class'] = 'control-label';
                    } elseif (!preg_match('/(\s|^)control-label(\s|$)/', $aLabelAttributes['class'])) {
                        $aLabelAttributes['class'] = trim($aLabelAttributes['class'] . ' control-label');
                    }
                }

                $oLabelHelper = $this->getLabelHelper();
                switch ($sLayout) {
                    //Hide label for "inline" layout
                    case \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_INLINE:
                        if (empty($aLabelAttributes['class'])) {
                            $aLabelAttributes['class'] = 'sr-only';
                        } elseif (!preg_match('/(\s|^)sr-only(\s|$)/', $aLabelAttributes['class'])) {
                            $aLabelAttributes['class'] = trim($aLabelAttributes['class'] . ' sr-only');
                        }
                        break;

                    case \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL:
                        if (empty($aLabelAttributes['class'])) {
                            $aLabelAttributes['class'] = 'control-label';
                        } else {
                            if (!preg_match('/(\s|^)control-label(\s|$)/', $aLabelAttributes['class'])) {
                                $aLabelAttributes['class'] = trim($aLabelAttributes['class'] . ' control-label');
                            }
                        }
                        break;
                }
                if ($aLabelAttributes) {
                    $oElement->setLabelAttributes($aLabelAttributes);
                }

                $sLabelOpen = $oLabelHelper->openTag($oElement->getAttribute('id') ? $oElement : $aLabelAttributes);
                $sLabelClose = $oLabelHelper->closeTag();

                if (!$oElement->getOption('disable_html_escape_label')) {
                    $sLabelContent = $this->getEscapeHtmlHelper()->__invoke($sLabelContent);
                }

            }
        }

        switch ($sLayout) {
            case null:
            case \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_INLINE:
                $sElementContent =  $sLabelOpen . $sLabelContent . $sLabelClose . $this->getElementHelper()->render($oElement) . $this->renderHelpBlock($oElement);

                //Render errors
                if ($this->renderErrors) {
                    $sElementContent .= $this->getElementErrorsHelper()->render($oElement);
                }

                return $sElementContent;

            case \TwbBundle\Form\View\Helper\TwbBundleForm::LAYOUT_HORIZONTAL:
                $sElementContent = $this->getElementHelper()->render($oElement) . $this->renderHelpBlock($oElement);

                //Render errors
                if ($this->renderErrors) {
                    $sElementContent .= $this->getElementErrorsHelper()->render($oElement);
                }

                $sClass = '';

                //Column size
                if ($sColumSize = $oElement->getOption('column-size')) {
                    $sClass .= ' col-' . $sColumSize;
                }

                return sprintf(
                    self::$horizontalLayoutFormat,
                    $sLabelOpen . $sLabelContent . $sLabelClose,
                    $sClass,
                    $sElementContent
                );

            default:
                throw new \DomainException('Layout "' . $sLayout . '" is not valid');
        }
    }

    /**
     * Render element's help block
     * @param \Zend\Form\ElementInterface $oElement
     * @return string
     */
    protected function renderHelpBlock(\Zend\Form\ElementInterface $oElement)
    {
        if (!$sHelpBlock = $oElement->getOption('help-block')) {
            return '';
        }

        $sHelpBlock = ($oTranslator = $this->getTranslator()) ? $oTranslator->translate($sHelpBlock, $this->getTranslatorTextDomain()) : $sHelpBlock;

        // Escape help block content unless we've disabled that
        if (!$oElement->getOption('disable_html_escape_help_block')) {
            $sHelpBlock = $this->getEscapeHtmlHelper()->__invoke($sHelpBlock);
        }

        return sprintf(
            self::$helpBlockFormat,
            $sHelpBlock
        );
    }

    /**
     * This is a super-hacky work-around to make sure this module uses its own view helpers
     * Neilime's library re-aliases all of zend's view helpers, which is great if you're only using his library
     * However, we require access to vanilla zend view helpers so that ztbFormElement still works
     * This type of thing is being done both here and in TwBundleFormElement.php::renderShim() method, because we call these different ways throughout the code base
     * @todo Rearchitect this. Bad.
     *
     * @return FormElement
     */
    protected function getElementHelper()
    {

        $elementHelper = parent::getElementHelper();

        if ($elementHelper && method_exists($this->view, 'plugin')) {

            switch(get_class($elementHelper)) {
                case 'Zend\Form\View\Helper\Form':
                    $elementHelper = $this->view->plugin('bs3_form');
                    break;
                case 'Zend\Form\View\Helper\FormButton':
                    $elementHelper = $this->view->plugin('bs3_form_button');
                    break;
                case 'Zend\Form\View\Helper\FormCheckbox':
                    $elementHelper = $this->view->plugin('bs3_form_checkbox');
                    break;
                case 'Zend\Form\View\Helper\FormCollection':
                    $elementHelper = $this->view->plugin('bs3_form_collection');
                    break;
                case 'Zend\Form\View\Helper\FormElement':
                    $elementHelper = $this->view->plugin('bs3_form_element');
                    break;
                case 'Zend\Form\View\Helper\FormElementErrors':
                    $elementHelper = $this->view->plugin('bs3_form_element_errors');
                    break;
                case 'Zend\Form\View\Helper\FormMultiCheckbox':
                    $elementHelper = $this->view->plugin('bs3_form_multi_checkbox');
                    break;
                case 'Zend\Form\View\Helper\FormRadio':
                    $elementHelper = $this->view->plugin('bs3_form_radio');
                    break;
                case 'Zend\Form\View\Helper\FormRow':
                    $elementHelper = $this->view->plugin('bs3_form_row');
                    break;
                case 'Zend\Form\View\Helper\FormLabel':
                    $elementHelper = $this->view->plugin('bs3_label');
                    break;
            }
            $this->elementHelper = $elementHelper;

        }

        return $elementHelper;
    }
}
