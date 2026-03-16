<?php

namespace Laravel\Nova\Fields;

use Brick\Money\Money;
use Symfony\Component\Intl\Currencies;

class Currency extends Number
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'currency-field';

    /**
     * The format the field will be displayed in.
     *
     * @var string
     */
    public $format;

    /**
     * The locale of the field.
     *
     * @var string
     */
    public $locale;

    /**
     * The currency of the value.
     *
     * @var string
     */
    public $currency;

    /**
     * The symbol used by the currency.
     *
     * @var null|string
     */
    public $currencySymbol = null;

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|null  $attribute
     * @param  mixed|null  $resolveCallback
     * @return void
     */
    public function __construct($name, $attribute = null, $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->locale = config('app.locale', 'en');
        $this->currency = config('nova.currency', 'USD');

        $this->step('0.01')
             ->displayUsing(function ($value) {
                 return ! $this->isNullValue($value) ? $this->formatMoney($value) : null;
             });
    }

    /**
     * Format the field's value into Money format.
     *
     * @param  mixed  $value
     * @param  null|string  $currency
     * @param  null|string  $locale
     *
     * @return string
     */
    public function formatMoney($value, $currency = null, $locale = null)
    {
        $money = Money::of($value, $currency ?? $this->currency);

        return $money->formatTo($locale ?? $this->locale);
    }

    /**
     * Set the currency code for the field.
     *
     * @param  string  $currency
     * @return $this
     */
    public function currency($currency)
    {
        $this->currency = strtoupper($currency);

        return $this;
    }

    /**
     * Set the field locale.
     *
     * @param  string  $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the symbol used by the field.
     *
     * @param  string  $symbol
     * @return $this
     */
    public function symbol($symbol)
    {
        $this->currencySymbol = $symbol;

        return $this;
    }

    /**
     * Resolve the symbol used by the currency.
     *
     * @return string
     */
    public function resolveCurrencySymbol()
    {
        if ($this->currencySymbol) {
            return $this->currencySymbol;
        }

        return Currencies::getSymbol($this->currency);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            'currency' => $this->resolveCurrencySymbol(),
        ]);
    }
}
