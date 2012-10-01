<?php
// florin, 9/27/12 3:45 PM 
abstract class Discount
{
    const DISCOUNT_TYPE_FIXED_AMOUNT = 'fixed_amount',
        DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    private $value, $discountValue, $minValueForDiscount, $VATPercent, $calculateBeforeApplyingVAT, $currency;

    abstract public function getName();
    abstract public function getDescription();
    abstract protected function validate($value, $discountValue);
    abstract protected function calculate($value, $discountValue);

    public function __construct($value, $discountValue, $minValueForDiscount=null, $VATPercent=0, $calculateBeforeApplyingVAT=false, $currency=null)
    {
        $this
            ->setValue($value)
            ->setDiscountValue($discountValue)
            ->setMinValueForDiscount($minValueForDiscount)
            ->setVATPercent($VATPercent)
            ->setCalculateBeforeVAT($calculateBeforeApplyingVAT)
            ->setCurrency($currency)
            ->validate($this->getValue(), $this->getDiscountValue());
    }

    public function getValueDiscounted()
    {
        if ($this->getMinValueForDiscount() > $this->getValue()) {
            return $this->getValue();
        }
        return $this->getValue() - $this->getReductionValue();
    }

    public function getReductionValue($flag=false)
    {
        if ($this->getMinValueForDiscount() > $this->getValue()) {
            return 0;
        }
        $value = ($this->calculateBeforeApplyingVAT() ?  $this->getValueWithoutVAT() : $this->getValue());
        return $this->calculate($value, $this->getDiscountValue());
    }

    public function getValueWithoutVAT()
    {
        return $this->getValue() - $this->getVATPercent() / 100 * $this->getValue();
    }

    //region Getters/setters
    public function setDiscountValue($amount)
    {
        $this->discountValue = $amount;
        return $this;
    }
    public function getDiscountValue()
    {
        return $this->discountValue;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    public function getValue()
    {
        return $this->value;
    }

    public function setVATPercent($VATPercent)
    {
        if ($VATPercent < 0 || $VATPercent > 99.9) {
            throw new Exception("Invalit VAT '$VATPercent'");
        }
        $this->VATPercent = $VATPercent;
        return $this;
    }
    public function getVATPercent()
    {
        return $this->VATPercent;
    }

    public function setCalculateBeforeVAT($calculateBeforeApplyingVAT)
    {
        $this->calculateBeforeApplyingVAT = $calculateBeforeApplyingVAT;
        return $this;
    }
    public function calculateBeforeApplyingVAT()
    {
        return $this->calculateBeforeApplyingVAT;
    }

    public function setMinValueForDiscount($minValueForDiscount)
    {
        $this->minValueForDiscount = $minValueForDiscount;
        return $this;
    }
    public function getMinValueForDiscount()
    {
        return $this->minValueForDiscount;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }
    public function getCurrency()
    {
        return $this->currency;
    }
    //endregion

}


class FixedAmountDiscount extends Discount
{

    public function getName()
    {
        return "Fixed Amount Discount";
    }

    public function getDescription()
    {
        return "You get {$this->getDiscountValue()}{$this->getCurrency()} discount when your cart value steps over {$this->getMinValueForDiscount()}{$this->getCurrency()}";
    }

    protected function validate($value, $discountValue)
    {
        if ($value < $discountValue) {
            throw new Exception("{$this->getValue()} should be bigger than the fix amount discount value {$this->getDiscountValue()}");
        }
    }

    protected function calculate($value, $discountValue)
    {
        return $discountValue;
    }

}


class PercentageDiscount extends Discount
{
    public function getName()
    {
        return "Percentage Discount";
    }

    public function getDescription()
    {
        return "{$this->getDiscountValue()}% of your total order gets discounted when you step over {$this->getMinValueForDiscount()}{$this->getCurrency()}";
    }

    protected function validate($value, $discountValue)
    {
        if ($discountValue > 99.9 || $discountValue < 0.1 ) {
            throw new Exception('Discount value should be between 1 and 99');
        }
    }

    protected function calculate($value, $discountValue)
    {
        return $discountValue / 100 * $value;
    }
}