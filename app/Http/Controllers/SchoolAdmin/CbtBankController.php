<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Ai\AiStyles;
use App\Http\Controllers\Controller;
use App\Models\CbtBank;
use App\Models\CbtQuestion;
use App\Models\SchemeOfWork;
use App\Models\Subject;
use App\Services\AiService;
use App\Services\ImageWriterService;
use App\Services\CbtService;
use App\Services\QuestionWriterService;
use App\Support\SimpleXlsx;
use App\Support\SimpleXlsxReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/** Question banks and the questions in them */
class CbtBankController extends Controller
{
    public function index(Request $request): Response
    {
        $banks = CbtBank::with('subject:id,name')->withCount('questions')->orderBy('name')->get();
        $rows = CbtQuestion::whereIn('cbt_bank_id', $banks->pluck('id'))->get(['cbt_bank_id', 'difficulty', 'type'])->groupBy('cbt_bank_id');
        $levels = $rows->map(fn ($r) => $r->countBy('difficulty'));

        return Inertia::render('SchoolAdmin/Cbt/Banks', [
            'banks' => $banks->map(fn (CbtBank $b) => [
                'id' => $b->id, 'name' => $b->name, 'description' => $b->description, 'subject_id' => $b->subject_id,
                'subject' => $b->subject?->name,
                'questions' => $b->questions_count,
                'theory' => $rows->get($b->id)?->where('type', 'theory')->count() ?? 0,
                'levels' => collect(CbtQuestion::DIFFICULTIES)->mapWithKeys(fn ($d) => [$d => (int) ($levels->get($b->id)?->get($d) ?? 0)]),
            ]),
            'subjects' => $this->subjects(),
            'canManage' => $request->user()->can('cbt.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $bank = CbtBank::create($this->validateBank($request) + ['school_id' => $request->user()->school_id, 'created_by' => $request->user()->id]);

        return redirect("/school/cbt/banks/{$bank->id}")->with('success', 'Question bank created. Now add questions.');
    }

    public function update(Request $request, CbtBank $bank): RedirectResponse
    {
        $bank->update($this->validateBank($request));

        return back()->with('success', 'Question bank saved.');
    }

    public function destroy(CbtBank $bank): RedirectResponse
    {
        if (DB::table('cbt_test_sections')->where('cbt_bank_id', $bank->id)->exists()) {
            return back()->with('error', 'Tests use this bank, so it cannot be deleted. Delete those tests first.');
        }
        $bank->delete();

        return redirect('/school/cbt/banks')->with('success', 'Question bank deleted.');
    }

    public function show(Request $request, CbtBank $bank, AiService $ai): Response
    {
        $filters = [
            'search' => (string) $request->search,
            'topic' => $request->topic ?: null,
            'difficulty' => in_array($request->difficulty, CbtQuestion::DIFFICULTIES, true) ? $request->difficulty : null,
            'type' => in_array($request->type, CbtQuestion::TYPES, true) ? $request->type : null,
        ];
        $questions = $bank->questions()
            ->when($filters['search'], fn ($q, $s) => $q->where('text', 'like', "%{$s}%"))
            ->when($filters['topic'], fn ($q, $t) => $q->where('topic', $t))
            ->when($filters['difficulty'], fn ($q, $d) => $q->where('difficulty', $d))
            ->when($filters['type'], fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('id')->paginate(25)->withQueryString()
            ->through(fn (CbtQuestion $q) => $this->questionRow($q));

        return Inertia::render('SchoolAdmin/Cbt/Bank', [
            'bank' => ['id' => $bank->id, 'name' => $bank->name, 'description' => $bank->description, 'subject_id' => $bank->subject_id, 'total' => $bank->questions()->count(),
                'theory' => $bank->questions()->where('type', 'theory')->count()],
            'questions' => $questions,
            'topics' => $bank->questions()->whereNotNull('topic')->distinct()->orderBy('topic')->pluck('topic'),
            'filters' => $filters,
            'subjects' => $this->subjects(),
            'canManage' => $request->user()->can('cbt.manage'),
            'maxOptions' => CbtQuestion::MAX_OPTIONS,
            'ai' => [
                'shown' => $ai->schoolState($bank->school_id)['platform_ready'],
                'reason' => $ai->unavailable($bank->school_id, 'questions'),
                'level' => $bank->subject?->levelLabel() ?: null,
                'images' => [
                    'reason' => $ai->imageUnavailable($bank->school_id),
                    'cost' => $ai->imageCost(),
                    'styles' => AiStyles::choices('image'),
                    'default_style' => AiStyles::defaultKey('image', $bank->school_id),
                ],
                'styles' => AiStyles::choices('questions'),
                'default_style' => AiStyles::defaultKey('questions', $bank->school_id),
                // Topics from the subject's schemes of work, offered when asking the AI
                'topics' => $bank->subject_id
                    ? SchemeOfWork::where('school_id', $bank->school_id)->where('subject_id', $bank->subject_id)->get(['weeks'])
                        ->flatMap(fn ($s) => collect($s->weeks ?? [])->pluck('topic'))->filter()->unique()->values()
                    : [],
            ],
        ]);
    }

    /** Questions drafted by the AI, returned for the teacher to check. Nothing is saved. */
    public function aiDraft(Request $request, CbtBank $bank, QuestionWriterService $writer): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(CbtQuestion::TYPES)],
            'count' => 'required|integer|min:1|max:'.($request->input('type') === 'theory' ? QuestionWriterService::MAX_THEORY : QuestionWriterService::MAX_OBJECTIVE),
            'topic' => 'required|string|max:120',
            'difficulty' => ['required', Rule::in([...CbtQuestion::DIFFICULTIES, 'mixed'])],
            'level' => 'nullable|string|max:60',
            'parts' => 'nullable|integer|min:0|max:'.QuestionWriterService::MAX_PARTS,
            'marks' => 'nullable|numeric|min:1|max:100',
            'instructions' => 'nullable|string|max:1500',
            'style' => 'nullable|string|max:30',
        ], ['topic.required' => 'Type the topic the questions are about.', 'count.max' => 'Ask for at most :max at a time.']);

        return response()->json(['questions' => $writer->draft($bank, $request->user(), $data)]);
    }

    /** A picture the AI makes from a description. Nothing is saved: the browser shows it and the teacher may attach it to the question. */
    public function aiImage(Request $request, CbtBank $bank, ImageWriterService $images): JsonResponse
    {
        $data = $request->validate([
            'description' => 'required|string|min:5|max:600',
            'style' => 'nullable|string|max:30',
        ], ['description.required' => 'Describe what the picture should show.', 'description.min' => 'Describe what the picture should show.']);
        $bank->loadMissing('subject');

        return response()->json($images->make($bank->school_id, $request->user(), $data['description'], $data['style'] ?? null, $bank->subject?->name));
    }

    /** The questions a teacher kept from the AI's drafts, checked like any other question and saved together */
    public function storeMany(Request $request, CbtBank $bank): RedirectResponse
    {
        $request->validate([
            'questions' => 'required|array|min:1|max:'.QuestionWriterService::MAX_OBJECTIVE,
            'questions.*.type' => ['required', Rule::in(CbtQuestion::TYPES)],
            'questions.*.text' => 'required|string|max:5000',
            'questions.*.topic' => 'nullable|string|max:120',
            'questions.*.difficulty' => ['required', Rule::in(CbtQuestion::DIFFICULTIES)],
            'questions.*.marks' => 'required|numeric|min:0.5|max:100',
            'questions.*.explanation' => 'nullable|string|max:2000',
            'questions.*.marking_guide' => 'nullable|string|max:5000',
            'questions.*.options' => 'array|max:'.CbtQuestion::MAX_OPTIONS,
            'questions.*.options.*' => 'required|string|max:1000',
            'questions.*.correct_option' => 'nullable|integer|min:0',
        ], [
            'questions.required' => 'Tick at least one question to keep.',
            'questions.*.text.required' => 'A question has no text.',
            'questions.*.options.*.required' => 'Fill in every option, or remove the empty ones.',
        ]);

        $rows = [];
        foreach ($request->input('questions') as $i => $q) {
            if ($q['type'] === 'objective') {
                $options = array_values($q['options'] ?? []);
                if (count($options) < 2) {
                    throw ValidationException::withMessages(["questions.{$i}.options" => 'Question '.($i + 1).' needs at least two options.']);
                }
                if (! isset($q['correct_option']) || (int) $q['correct_option'] >= count($options)) {
                    throw ValidationException::withMessages(["questions.{$i}.correct_option" => 'Choose the right option for question '.($i + 1).'.']);
                }
                $rows[] = ['type' => 'objective', 'options' => $options, 'correct_option' => (int) $q['correct_option'], 'marking_guide' => null, 'explanation' => $q['explanation'] ?? null];
            } else {
                $rows[] = ['type' => 'theory', 'options' => null, 'correct_option' => null, 'marking_guide' => $q['marking_guide'] ?? null, 'explanation' => null];
            }
            $rows[$i] += ['text' => $q['text'], 'topic' => ($q['topic'] ?? null) ?: null, 'difficulty' => $q['difficulty'], 'marks' => $q['marks']];
        }

        DB::transaction(function () use ($rows, $bank, $request) {
            foreach ($rows as $row) {
                $bank->questions()->create($row + ['school_id' => $bank->school_id, 'created_by' => $request->user()->id]);
            }
        });

        return back()->with('success', count($rows).' question'.(count($rows) === 1 ? '' : 's').' added to the bank.');
    }

    public function storeQuestion(Request $request, CbtBank $bank): RedirectResponse
    {
        $data = $this->validateQuestion($request, null);
        $question = $bank->questions()->create($data + ['school_id' => $bank->school_id, 'created_by' => $request->user()->id]);
        $this->saveImage($request, $question);

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, CbtBank $bank, CbtQuestion $question): RedirectResponse
    {
        abort_unless($question->cbt_bank_id === $bank->id, 404);
        $question->update($this->validateQuestion($request, $question));
        $this->saveImage($request, $question);

        return back()->with('success', 'Question saved. Tests already handed in keep their marks.');
    }

    public function destroyQuestion(CbtBank $bank, CbtQuestion $question): RedirectResponse
    {
        abort_unless($question->cbt_bank_id === $bank->id, 404);
        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    /** Many questions at once: pasted text, or the Excel template */
    public function import(Request $request, CbtBank $bank): RedirectResponse
    {
        $request->validate([
            'pasted' => 'required_without:file|nullable|string|max:200000',
            'file' => 'required_without:pasted|nullable|file|mimes:xlsx|max:5120',
            'topic' => 'nullable|string|max:120',
            'difficulty' => ['nullable', Rule::in(CbtQuestion::DIFFICULTIES)],
        ], ['pasted.required_without' => 'Paste some questions or choose an Excel file.', 'file.mimes' => 'Use the Excel template (.xlsx).']);

        if ($request->hasFile('file')) {
            try {
                [$questions, $problems] = CbtService::parseRows(SimpleXlsxReader::rows($request->file('file')->getRealPath()));
            } catch (Throwable $e) {
                return back()->withErrors(['file' => $e->getMessage()]);
            }
        } else {
            [$questions, $problems] = CbtService::parse($request->pasted);
            $questions = array_map(fn ($q) => $q + ['topic' => $request->topic ?: null, 'difficulty' => $request->difficulty ?: 'medium', 'marks' => 1], $questions);
        }

        if ($problems) {
            return back()->withErrors(self::numbered('import', array_slice($problems, 0, 20)))->with('error', 'Nothing was added. Fix the problems listed and try again.');
        }
        if (! $questions) {
            return back()->withErrors(['import.0' => 'No questions were found.']);
        }

        DB::transaction(function () use ($questions, $bank, $request) {
            foreach ($questions as $q) {
                $bank->questions()->create($q + ['school_id' => $bank->school_id, 'created_by' => $request->user()->id]);
            }
        });

        return back()->with('success', count($questions).' question(s) added.');
    }

    public function template(): HttpResponse
    {
        $bytes = (new SimpleXlsx())->sheet('Questions', [
            CbtService::TEMPLATE_HEADINGS,
            ['What is 7 x 8?', '54', '56', '64', '58', '', 'B', 'Multiplication', 'easy', '1', '7 x 8 = 56'],
            ['Which of these is a prime number?', '21', '27', '29', '33', '', 'C', 'Numbers', 'medium', '1', ''],
        ])->bytes();

        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="question-template.xlsx"',
        ]);
    }

    /** One error per line, so the page can list every problem (Inertia keeps only one message per key) */
    public static function numbered(string $key, array $messages): array
    {
        return collect($messages)->values()->mapWithKeys(fn ($m, $i) => ["{$key}.{$i}" => $m])->all();
    }

    public static function questionRow(CbtQuestion $q): array
    {
        return [
            'id' => $q->id, 'type' => $q->type, 'text' => $q->text, 'options' => $q->options ?? [], 'marking_guide' => $q->marking_guide, 'correct_option' => $q->correct_option, 'topic' => $q->topic,
            'difficulty' => $q->difficulty, 'marks' => $q->marks, 'explanation' => $q->explanation,
            'image' => $q->image_path ? "/cbt/images/{$q->id}" : null,
        ];
    }

    private function subjects()
    {
        return Subject::orderBy('name')->get(['id', 'name'])->map(fn (Subject $s) => ['id' => $s->id, 'name' => $s->name])->values();
    }

    private function validateBank(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', $request->user()->school_id)],
            'description' => 'nullable|string|max:500',
        ]);
    }

    private function validateQuestion(Request $request, ?CbtQuestion $question): array
    {
        $type = $question?->type ?? ($request->input('type') === 'theory' ? 'theory' : 'objective');
        if ($question && $request->filled('type') && $request->input('type') !== $question->type) {
            // Papers already given out rely on the kind of question
            throw ValidationException::withMessages(['type' => 'An objective question cannot become a theory question, or the other way round. Add a new question instead.']);
        }
        $common = [
            'text' => 'required|string|max:5000',
            'topic' => 'nullable|string|max:120',
            'difficulty' => ['required', Rule::in(CbtQuestion::DIFFICULTIES)],
            'marks' => 'required|numeric|min:0.5|max:100',
            'explanation' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'boolean',
        ];

        if ($type === 'theory') {
            $data = $request->validate($common + ['marking_guide' => 'nullable|string|max:5000']);

            return collect($data)->except('image', 'remove_image')->all() + ['type' => 'theory', 'options' => null, 'correct_option' => null];
        }

        $data = $request->validate($common + [
            'options' => 'required|array|min:2|max:'.CbtQuestion::MAX_OPTIONS,
            'options.*' => 'required|string|max:1000',
            'correct_option' => 'required|integer|min:0',
        ], ['options.*.required' => 'Fill in every option, or remove the empty ones.', 'options.min' => 'Give at least two options.']);

        if ($data['correct_option'] >= count($data['options'])) {
            throw ValidationException::withMessages(['correct_option' => 'Choose which option is right.']);
        }

        return collect($data)->except('image', 'remove_image')->all() + ['type' => 'objective', 'marking_guide' => null];
    }

    private function saveImage(Request $request, CbtQuestion $question): void
    {
        if ($request->hasFile('image')) {
            $old = $question->image_path;
            $question->update(['image_path' => $request->file('image')->store("cbt/{$question->school_id}", 'private')]);
            if ($old) {
                Storage::disk('private')->delete($old);
            }
        } elseif ($request->boolean('remove_image') && $question->image_path) {
            Storage::disk('private')->delete($question->image_path);
            $question->update(['image_path' => null]);
        }
    }
}
