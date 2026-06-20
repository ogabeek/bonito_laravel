<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Student - Represents a tutoring student
 *
 * Uses UUID for public-facing URLs instead of sequential IDs.
 * Many-to-many with Teachers via student_teacher pivot table.
 *
 * Runtime-computed balance attributes (set by StudentBalanceService::mapBalances,
 * not persisted) — declared so static analysis recognizes them:
 *
 * @property array<int, int> $teacher_ids
 * @property int|null $paid_classes
 * @property int $used_classes
 * @property int|null $class_balance
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'parent_name',
        'email',
        'goal',
        'description',
        'status',       // active, inactive, holiday
        'teacher_notes',
        'materials_url',
        'vacation_starts_on',
        'vacation_ends_on',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'vacation_starts_on' => 'date',
            'vacation_ends_on' => 'date',
        ];
    }

    /**
     * Whether the recorded vacation is still relevant (active or upcoming),
     * i.e. it ends today or later. Past vacations are ignored.
     */
    public function hasPendingVacation(): bool
    {
        return $this->vacation_ends_on !== null
            && $this->vacation_ends_on->gte(now()->startOfDay());
    }

    /**
     * Short "M j – M j" label for the vacation period, or null when unset.
     */
    public function vacationLabel(): ?string
    {
        if ($this->vacation_starts_on === null || $this->vacation_ends_on === null) {
            return null;
        }

        return $this->vacation_starts_on->format('M j').' – '.$this->vacation_ends_on->format('M j');
    }

    // Auto-generate UUID on creation
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($student): void {
            if (empty($student->uuid)) {
                $student->uuid = (string) Str::uuid();
            }
        });
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    // Many-to-many via student_teacher pivot
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }

    // Route model binding uses UUID, not ID
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Eager load with counts to prevent N+1
    public function scopeWithFullDetails($query)
    {
        return $query->withCount('teachers', 'lessons')->with('teachers');
    }

    public function scopeActive($query)
    {
        return $query->where('status', StudentStatus::ACTIVE);
    }

    // Active OR holiday (still enrolled)
    public function scopeEnrolled($query)
    {
        return $query->whereIn('status', [StudentStatus::ACTIVE, StudentStatus::HOLIDAY]);
    }
}
