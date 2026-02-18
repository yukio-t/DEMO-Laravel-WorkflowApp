<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ユーザーが作成したWorkflow一覧
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class, 'created_by');
    }

    /**
     * ユーザーが実行したWorkflowの状態遷移履歴
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workflowHistories(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class, 'acted_by');
    }
}
