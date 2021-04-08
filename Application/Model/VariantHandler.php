<?php

namespace Aggrosoft\DisableNoStockVariants\Application\Model;

class VariantHandler extends VariantHandler_parent {
    protected $_aVariantStocks = [];

    //Cache stocks
    protected function _fillVariantSelections($oVariantList, $iVarSelCnt, &$aFilter, $sActVariantId)
    {
        $this->_aVariantStocks = [];

        // filling selections
        foreach ($oVariantList as $oVariant) {
            $aNames = $this->_getSelections($oVariant->oxarticles__oxvarselect->getRawValue());
            $this->setDotArray($this->_aVariantStocks, trim(implode('.', $aNames)), $oVariant->oxarticles__oxstockflag->value == 4 ? 999999 : $oVariant->oxarticles__oxstock->value);
        }

        return parent::_fillVariantSelections($oVariantList, $iVarSelCnt, $aFilter, $sActVariantId);
    }

    // Disable variant combinations not being on stock
    protected function _applyVariantSelectionsFilter($aSelections, $aFilter) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        list($aSelections, $sMostSuitableVariantId, $blPerfectFit) = parent::_applyVariantSelectionsFilter($aSelections, $aFilter);

        foreach ($aSelections as $sVariantId => &$aLineSelections) {
            $aNames = [];
            $blParentActive = false;
            foreach ($aLineSelections as $iKey => &$aLineVariant) {
                $aNames[] = $aLineVariant['name'];
                $blChildActive = !!$aFilter[$iKey+1];

                // Disable children if parent is active and child has no stock
                if ($blParentActive) {
                    $aStock = $this->getDotArray($this->_aVariantStocks, implode('.', $aNames));
                    if (!is_array($aStock) && $aStock <= 0) {
                        $aLineVariant['disabled'] = true;
                        $aLineVariant['active'] = false;
                    }
                } elseif ($blChildActive) {
                    // Disable parents if child is active and possible child has no stock
                    $aChildLine = $aLineSelections[$iKey+1];
                    $aChildStock = $this->getDotArray($this->_aVariantStocks, implode('.', $aNames) . '.' . $aChildLine['name']);
                    if (!is_array($aChildStock) && $aChildStock <= 0) {
                        $aLineVariant['disabled'] = true;
                        $aLineVariant['active'] = false;
                    }
                } elseif (isset($aLineSelections[$iKey+1])) {
                    // Disable parent if no child has stock at all
                    $aChildStocks = $this->getDotArray($this->_aVariantStocks, implode('.', $aNames), 0);
                    $blHasStock = false;
                    foreach($aChildStocks as $iStock) {
                        if ($iStock > 0) {
                            $blHasStock = true;
                            break;
                        }
                    }
                    if (!$blHasStock) {
                        $aLineVariant['disabled'] = true;
                        $aLineVariant['active'] = false;
                    }
                } else {
                    $aStock = $this->getDotArray($this->_aVariantStocks, implode('.', $aNames));
                    if (!is_array($aStock) && $aStock <= 0) {
                        $aLineVariant['disabled'] = true;
                        $aLineVariant['active'] = false;
                    }
                }

                $blParentActive = in_array($aLineVariant['hash'], $aFilter);
            }
        }

        return [$aSelections, $sMostSuitableVariantId, $blPerfectFit];
    }

    // Helper functions to set/get multidimensional array values with dot notation
    // Thanks laravel ;)
    protected function setDotArray(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    protected function getDotArray($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}