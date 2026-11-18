<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;
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
            'isCompleted' => $r->user()->lessons()->where('lessons.id', $module->id)->exists(),
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
                        'completed' => $r->user()->lessons()->where('lessons.id', $module->id)->exists(),
                        'xp_awarded' => 0,
                    ]);
                }

                return redirect()->to(route('modules.show', $module).'#turtle-quiz')
                    ->withErrors(['answer' => 'Jawaban belum tepat. '.$quiz['explanation']])->withInput();
            }
        }

        $firstCompletion = DB::transaction(function () use ($r, $module, $service, $access) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $access->assertAccessible($u, $module);
            $alreadyCompleted = $u->lessons()->where('lessons.id', $module->id)->exists();
            $u->lessons()->syncWithoutDetaching([$module->id]);
            $service->syncXp($u);

            return ! $alreadyCompleted;
        });

        if ($r->expectsJson()) {
            $progress = $access->forUser($r->user());

            return response()->json([
                'correct' => true,
                'title' => 'Jawaban benar!',
                'message' => $module->learning_content['quiz']['explanation'] ?? 'Materi berhasil diselesaikan.',
                'completed' => true,
                'xp_awarded' => $firstCompletion ? 20 : 0,
                'lesson_id' => $module->id,
                'completed_lessons' => count($progress['done']),
                'unlocked_lessons' => $progress['unlocked'],
            ]);
        }

        return redirect()->to(route('modules.show', $module).'#turtle-quiz')->with('status', $firstCompletion ? 'Materi selesai! Kamu mendapat +20 XP.' : 'Materi ini sudah selesai. Kamu bisa membacanya kembali kapan saja.');
    }
}
