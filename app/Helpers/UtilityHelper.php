<?php

namespace App\Helpers;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UtilityHelper
{
    /**
     * Formats a date string into a specified format.
     *
     * This method first detects the format of the provided date string using the `detectDateFormat` method.
     * Once the format is detected, it converts the date string into the desired format using the `Carbon` library.
     *
     * @param string $value The date string to format.
     * @param string $format The target date format (default is 'Y-m-d').
     * @return string The formatted date string.
     * @throws \InvalidArgumentException If the date format cannot be detected.
     */

	protected static function detectDateFormat($date): string|false
    {
        $formats = [
            'd.m.Y',
            'd/m/Y',
            'Y-m-d',
            'm/d/Y',
            'd-m-Y',
            'Y/m/d',
            'm.d.Y',
            'd M Y',
            'M d, Y',
            'D, d M Y',
            'l, d M Y',
            'd F Y',
            'F d, Y',
            'D, d F Y',
            'l, d F Y',
            'd-m-y',
            'd/m/y',
            'm-d-Y',
            'm/d/y',
            'Y.m.d',
            'Y-m-d H:i:s',
            'd.m.Y H:i:s',
            'd/m/Y H:i:s',
            'm/d/Y H:i:s',
            'H:i:s',
            'H:i',
            'g:i A',
            'h:i A',
            'g:i a',
            'h:i a',
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s.uP',
            'Y-m-d\TH:i:sO',
            'Y-m-d\TH:i:s.uO',
            'r',
            'U'
        ];

        foreach ($formats as $format) 
        {
            try {
                $parsed = Carbon::createFromFormat($format, $date, false);
                if ($parsed && $parsed->format($format) === $date) 
                {
                    return $format;
                }
            } catch (\Exception $e) {
                //\Log::Info('Invalid date');
            }
        }

        return false; 
    }

    /**
     * Formats a date string into a specified format.
     *
     * This method first detects the format of the provided date string using the `detectDateFormat` method.
     * Once the format is detected, it converts the date string into the desired format using the `Carbon` library.
     *
     * @param string $value The date string to format.
     * @param string $format The target date format (default is 'Y-m-d').
     * @return string The formatted date string.
     * @throws \InvalidArgumentException If the date format cannot be detected or if the input value is not a string.
     */
    public static function formatDate(string $value, string $format = 'Y-m-d'): string
    {
        try {
            if (!is_string($value)) 
            {
                \Log::info("The value must be a string.");
                throw new \InvalidArgumentException("The value must be a string.");
            }

            $detectFormat = self::detectDateFormat($value);

            if ($detectFormat === false) 
            {
                \Log::error('Invalid date format: ' . $value);
                throw new \InvalidArgumentException("Invalid date format: $value");
            }

            return Carbon::createFromFormat($detectFormat, $value)->format($format);
        } catch (\Exception $e) {
            \Log::error('Error formatting date: ' . $value . ' with format: ' . ($detectFormat ?? 'unknown'));
            throw new \InvalidArgumentException('Error formatting date: ' . $value);
        }
    }
    
    /**
     * Resolves and returns an instance of a model class.
     *
     * This method takes a model or model name as input and returns an instance of the corresponding model class.
     * 
     * - If the provided `$model` is an instance of a `Model`, it constructs the class name based on the model's class name
     *   and returns an instance of that class.
     * - If the provided `$model` is a string (assumed to be a model name), it converts the name to its singular form (if needed)
     *   and then constructs the class name accordingly, returning an instance of the model class.
     *
     * @param mixed $model The model instance or model name.
     * @return Model An instance of the corresponding model class.
     */
    public static function model($model): Model
    {
        if ($model instanceof Model) 
        {
            return App::make('App\\Models\\' . ucfirst(class_basename($model)));
        } 
        else 
        {
            return App::make('App\\Models\\' . ucfirst(Str::singular($model)));
        }
    }
}