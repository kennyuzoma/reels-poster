<?php

namespace App\Helpers;

use Spatie\Valuestore\Valuestore;

class SiteSettingsHelper {

    /**
     * @throws \Exception
     */
    static public function get($settingsKey)
    {
        $novaSettings = config('nova-settings-tool');
        $value = Valuestore::make($novaSettings['path'])->get($settingsKey);

        $settingsInfo = GeneralHelper::searchArray('key', $settingsKey, $novaSettings['settings']);

        if (
            empty($value)
            &&
            isset($settingsInfo['required'])
            && (
                $settingsInfo['required'] === TRUE
                && $settingsInfo['type'] != 'toggle'
            )
        ) {
            throw new \Exception('Please set a value for the setting "' . $settingsKey . '".');
        }

        return $value;
    }

    static public function save($settingsKey, $value)
    {
        return Valuestore::make(config('nova-settings-tool.path'))->put($settingsKey, $value);
    }

}
