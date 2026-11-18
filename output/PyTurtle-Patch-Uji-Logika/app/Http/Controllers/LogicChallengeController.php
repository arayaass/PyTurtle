<?php

namespace App\Http\Controllers;

use App\Services\LogicChallengeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogicChallengeController extends Controller
{
    public function index(Request $request, LogicChallengeService $service)
    {
        return view('logic.index', ['challenges' => $service->challenges(), 'done' => DB::table('logic_completions')->where('user_id', $request->user()->id)->pluck('challenge_id')->all()]);
    }

    public function show(Request $request, int $challenge, LogicChallengeService $service)
    {
        $definition = $service->get($challenge);
        unset($definition['answer'], $definition['explanation'], $definition['rule']);

        return view('logic.show', ['challenge' => $challenge, 'definition' => $definition, 'completed' => DB::table('logic_completions')->where('user_id', $request->user()->id)->where('challenge_id', $challenge)->exists()]);
    }

    public function experiment(Request $request, int $challenge, LogicChallengeService $service)
    {
        $data = $request->validate(['angle' => 'required|integer|in:0,90,180,270', 'distance' => 'required|integer|min:1|max:4', 'request_id' => 'required|uuid']);
        $result = $service->experiment($challenge, (int) $data['angle'], (int) $data['distance']);
        DB::table('logic_experiments')->insertOrIgnore(['id' => $data['request_id'], 'user_id' => $request->user()->id, 'challenge_id' => $challenge, 'angle' => $data['angle'], 'distance' => $data['distance'], 'created_at' => now(), 'updated_at' => now()]);
        $record = DB::table('logic_experiments')->where('id', $data['request_id'])->where('user_id', $request->user()->id)->first();
        abort_unless($record && (int) $record->challenge_id === $challenge && (int) $record->angle === (int) $data['angle'] && (int) $record->distance === (int) $data['distance'], 409);

        return response()->json($result + ['experiment_id' => $record->id]);
    }

    public function answer(Request $request, int $challenge, LogicChallengeService $service)
    {
        $definition = $service->get($challenge);
        $data = $request->validate(['experiment_id' => 'required|uuid', 'answer' => 'required|integer|between:0,2']);
        $record = DB::table('logic_experiments')->where('id', $data['experiment_id'])->where('user_id', $request->user()->id)->where('challenge_id', $challenge)->first();
        abort_unless($record, 404);
        $result = $service->experiment($challenge, (int) $record->angle, (int) $record->distance);
        if (! $result['counterexample']) {
            return response()->json(['correct' => false, 'message' => 'Percobaan ini belum membantah pernyataan. Ubah kondisi dan uji lagi.']);
        }
        if ((int) $data['answer'] !== $definition['answer']) {
            return response()->json(['correct' => false, 'message' => 'Contoh bantahanmu sudah tepat, tetapi alasannya belum sesuai. Bandingkan posisi dan arah hasil eksperimen.']);
        }
        DB::table('logic_completions')->insertOrIgnore(['user_id' => $request->user()->id, 'challenge_id' => $challenge, 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['correct' => true, 'message' => $definition['explanation']]);
    }
}
