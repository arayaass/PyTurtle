<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CodeMatchService;
use App\Services\GameProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CodeMatchController extends Controller
{
    public function show(CodeMatchService $service)
    {
        return view('code-match.show', ['pairs' => $service->pairs()]);
    }

    public function submit(Request $r, CodeMatchService $service, GameProgressService $progress)
    {
        $input = $r->validate(['request_id' => 'required|uuid', 'attempts' => 'required|array|min:4|max:200', 'attempts.*.description' => 'required|in:a,b,c,d', 'attempts.*.snippet' => 'required|in:a,b,c,d', 'user_id' => 'prohibited', 'score' => 'prohibited']);

        return DB::transaction(function () use ($r, $input, $service, $progress) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            if ($old = $u->codeMatchResults()->where('request_id', $input['request_id'])->first()) {
                return response()->json($old->only('score', 'accuracy'));
            }
            $result = $service->grade($input['attempts']);
            abort_unless($result['correct_answers'] === 4, 422, 'Selesaikan keempat pasangan terlebih dahulu.');
            $u->codeMatchResults()->create($result + ['lesson_id' => 4, 'request_id' => $input['request_id'], 'completed_at' => now()]);
            $progress->syncXp($u);

            return response()->json($result);
        });
    }
}
