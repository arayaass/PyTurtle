<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;
use App\Services\CodeMatchService;
use App\Services\GameProgressService;
use App\Services\LessonAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public function index(Request $r, LessonAccessService $access)
    {
        return view('modules.index', $access->forUser($r->user()));
    }

    public function show(Request $r, Lesson $module, LessonAccessService $access)
    {
        $progress = $access->forUser($r->user());
        $lessons = $progress['lessons'];
        $position = $lessons->search(fn (Lesson $lesson) => $lesson->id === $module->id);
        if ($position !== false && ! in_array($module->id, $progress['unlocked'], true)) {
            $message = $access->lockMessage($progress['requiredLesson']);
            if ($r->expectsJson()) {
                abort(403, $message);
            }

            return redirect()->route('modules.show', $progress['requiredLesson'])->with('lesson_locked', $message);
        }

        return view($module->learning_content ? 'modules.show' : 'modules.legacy', array_merge($progress, [
            'lesson' => $module,
            'matchingPairs' => app(CodeMatchService::class)->pairs($module),
            'isCompleted' => $position === false
                ? $r->user()->lessons()->where('lessons.id', $module->id)->exists()
                : in_array($module->id, $progress['done'], true),
            'matchingResult' => $r->user()->codeMatchResults()->where('lesson_id', $module->id)
                ->where('correct_answers', 4)->whereNotNull('completed_at')->orderByDesc('score')->orderByDesc('accuracy')->first(),
            'previous' => $position !== false && $position > 0 ? $lessons[$position - 1] : null,
            'next' => $position !== false ? $lessons->get($position + 1) : null,
        ]));
    }

    public function complete(Request $r, Lesson $module, GameProgressService $service, LessonAccessService $access)
    {
        $access->assertAccessible($r->user(), $module);
        if ($module->learning_content) {
            $quiz = $module->learning_content['quiz'];
            $input = $r->validate(['answer' => ['required', 'integer', Rule::in(array_keys($quiz['options']))]], [
                'answer.required' => 'Pilih salah satu jawaban terlebih dahulu.',
                'answer.integer' => 'Pilih salah satu jawaban yang tersedia.',
                'answer.in' => 'Pilih salah satu jawaban yang tersedia.',
            ]);
            if ((int) $input['answer'] !== $quiz['answer']) {
                if ($r->expectsJson()) {
                    return response()->json([
                        'correct' => false,
                        'title' => 'Jawaban belum tepat',
                        'message' => $quiz['explanation'],
                        'completed' => $access->completionState($r->user(), $module)['completed'],
                        'xp_awarded' => 0,
                    ]);
                }

                return redirect()->to(route('modules.show', $module).'#turtle-quiz')
                    ->withErrors(['answer' => 'Jawaban belum tepat. '.$quiz['explanation']])->withInput();
            }
        }

        $unlockedFeatures = [];
        $firstCompletion = DB::transaction(function () use ($r, $module, $service, $access, &$unlockedFeatures) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $access->assertAccessible($u, $module);
            $before = $access->completedCount($u);
            $alreadyCompleted = $u->lessons()->where('lessons.id', $module->id)->exists();
            $u->lessons()->syncWithoutDetaching([$module->id]);
            $service->syncXp($u);
            $unlockedFeatures = $access->newlyUnlocked($before, $access->completedCount($u));

            return ! $alreadyCompleted;
        });

        $state = $access->completionState($r->user(), $module);
        if ($state['completed'] && $unlockedFeatures) {
            $state['continue_url'] = route('maze', [], false);
            $state['continue_label'] = 'Buka Maze Quest';
        }
        if ($r->expectsJson()) {

            return response()->json([
                'correct' => true,
                'title' => 'Jawaban benar!',
                'message' => ($module->learning_content['quiz']['explanation'] ?? 'Evaluasi berhasil diselesaikan.')
                    .($state['completed'] ? '' : ' Cocokkan keempat keterangan dengan kode agar modul berikutnya terbuka.'),
                'xp_awarded' => $firstCompletion ? 20 : 0,
                'unlocked_features' => $unlockedFeatures,
            ] + $state);
        }

        if ($unlockedFeatures) {
            $r->session()->flash('feature_notice', 'Fitur baru terbuka: '.implode(', ', $unlockedFeatures).'. Untuk Maze, selesaikan level sebelumnya secara berurutan.');
            $r->session()->flash('feature_continue_url', route('maze', [], false));
        }

        return redirect()->to(route('modules.show', $module).'#turtle-quiz')->with('status', $state['completed']
            ? ($firstCompletion ? 'Materi selesai! Kamu mendapat +20 XP.' : 'Materi ini sudah selesai. Kamu bisa membacanya kembali kapan saja.')
            : 'Jawaban benar. Cocokkan keempat keterangan dengan kode untuk membuka modul berikutnya.');
    }
}
