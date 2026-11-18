<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = ['id', 'title', 'definition'];

    protected function casts(): array
    {
        return ['definition' => 'array'];
    }

    public function stageDefinition(?array $state = null): array
    {
        $definition = $this->definition;
        if ($this->id === 1 && isset($definition['tutorial_stages'])) {
            $stage = min(2, max(0, (int) ($state['tutorial_stage'] ?? 0)));
            $definition = array_replace($definition, $definition['tutorial_stages'][$stage]);
            $definition['tutorial_stage'] = $stage;
            $definition['tutorial_title'] = $definition['title'];
        }
        unset($definition['tutorial_stages']);

        return $definition;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
