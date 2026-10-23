<?php

namespace App\Models\Concerns;

use App\Models\Setting;
use App\Models\Translation;

trait HasTranslations
{
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /**
     * $product->tr('name') -> French if reviewed (or permitted in settings), otherwise English fallback.
     */
    public function tr(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $default = config('locales.default', 'en');

        if ($locale !== $default) {
            $row = $this->translations->where('locale', $locale)->firstWhere('field', $field);
            $usable = $row && filled($row->value)
                && ($row->review_status === 'reviewed' || Setting::get('show_unreviewed_translations') === 'yes');

            if ($usable) {
                return $row->value;
            }
        }

        // English fallback: direct model attribute if exists, else English enrichment row
        if (array_key_exists($field, $this->attributes) && filled($this->attributes[$field])) {
            return $this->attributes[$field];
        }

        return $this->translations->where('locale', $default)->firstWhere('field', $field)?->value;
    }

    public function setTranslation(string $field, string $locale, ?string $value, string $status = 'draft'): void
    {
        $this->translations()->updateOrCreate(
            ['locale' => $locale, 'field' => $field],
            ['value' => $value, 'review_status' => $status]
        );
        $this->unsetRelation('translations');
    }
}
