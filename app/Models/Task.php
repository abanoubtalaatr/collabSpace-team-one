<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\GlobalSearchable;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Task extends Model implements GlobalSearchable, Searchable
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $table = 'tasks';

    public string $searchableType = 'Task';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'status',
    ];

    /**
     * @return array<int, string>
     */
    public static function globalSearchColumns(): array
    {
        return ['name', 'description'];
    }

    /**
     * @return array<int, string>
     */
    public static function globalSearchRelations(): array
    {
        return [
            'project:id,created_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
            'users:id,name,email,email_verified_at,created_at,updated_at',
        ];
    }

    public static function globalSearchType(): string
    {
        return 'task';
    }

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id', 'id', 'id');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
