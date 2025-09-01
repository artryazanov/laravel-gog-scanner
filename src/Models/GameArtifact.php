<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameArtifact extends Model
{
    protected $table = 'gog_game_artifacts';
    protected $fillable = [
        'game_id','type','artifact_id','name','os','language','language_full',
        'version','count','total_size','extra_type'
    ];

    public function files(): HasMany
    {
        return $this->hasMany(GameArtifactFile::class, 'artifact_id');
    }
}
