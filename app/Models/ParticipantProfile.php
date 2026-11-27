<?php

namespace App\Models;

use App\Domain\Screening\EligibilityRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantProfile extends Model
{
    public const SEXES = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    public const CLASSES = ['A' => 'Kelas A', 'B' => 'Kelas B', 'C' => 'Kelas C'];
    public const RESIDENCES = ['rumah_ortu' => 'Rumah orang tua', 'kost' => 'Kost/kontrakan', 'apartemen' => 'Apartemen', 'lainnya' => 'Lainnya'];

    protected $fillable = ['user_id', 'birth_place', 'birth_date', 'sex', 'class_group', 'residence', 'residence_other', 'whatsapp'];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ageOn(\DateTimeInterface $date): int
    {
        return EligibilityRules::age($this->birth_date, $date);
    }

    public function residenceLabel(): string
    {
        return $this->residence === 'lainnya'
            ? ($this->residence_other ?: 'Lainnya')
            : (self::RESIDENCES[$this->residence] ?? $this->residence);
    }
}
