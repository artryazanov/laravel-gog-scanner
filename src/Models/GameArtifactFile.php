<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameArtifactFile extends Model
{
    protected $table = 'gog_game_artifact_files';
    public $timestamps = false;
    protected $fillable = ['artifact_id','file_id','size','downlink'];
}
