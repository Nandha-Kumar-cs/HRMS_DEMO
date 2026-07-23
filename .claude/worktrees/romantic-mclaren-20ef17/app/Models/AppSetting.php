<?php

namespace App\Models;

/**
 * Thin alias so any code that already imported AppSetting still works.
 * The canonical model is Setting; the canonical helper is AppSettings.
 */
class AppSetting extends Setting
{
    //
}
