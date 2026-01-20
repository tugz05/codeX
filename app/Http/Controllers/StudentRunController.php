<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class StudentRunController extends Controller
{
    protected function ensureEnrolled(Classlist $classlist): void
    {
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403, 'Not enrolled in this class.');
    }

    public function run(Request $request, Classlist $classlist, Activity $activity)
    {
        $this->ensureEnrolled($classlist);
        abort_unless($activity->classlist_id === $classlist->id, 404, 'Activity not in this class.');

        $data = $request->validate([
            'language' => ['required', 'string', 'in:python,java,cpp'],
            'code'     => ['required', 'string', 'max:500000'],
            'stdin'    => ['nullable', 'string', 'max:20000'],
        ]);

        $langId = $this->judge0LanguageId($data['language']);
        if (!$langId) {
            return response()->json([
                'ok' => false,
                'error' => 'Unsupported Judge0 language mapping. Check .env JUDGE0_LANG_* values.',
            ], 422);
        }

        $base = rtrim(env('JUDGE0_BASE_URL', ''), '/');
        if (!$base) {
            return response()->json(['ok' => false, 'error' => 'JUDGE0_BASE_URL not configured'], 500);
        }

        $client = Http::timeout((int) env('RUN_LIMIT_SECONDS', 12))
                      ->acceptJson()
                      ->asJson();

        if (filter_var(env('JUDGE0_USE_RAPIDAPI', false), FILTER_VALIDATE_BOOLEAN)) {
            $client = $client->withHeaders([
                'X-RapidAPI-Key'  => env('JUDGE0_RAPIDAPI_KEY', ''),
                'X-RapidAPI-Host' => parse_url($base, PHP_URL_HOST) ?? 'judge0-ce.p.rapidapi.com',
            ]);
        }

        $createUrl = $base . '/submissions?base64_encoded=false&wait=false';
        $payload = [
            'language_id' => $langId,
            'source_code' => $data['code'],
            'stdin'       => $data['stdin'] ?? '',
        ];

        try {
            // IMPORTANT: Judge0 returns 201 Created with {"token": "..."}
            $createRes = $client->post($createUrl, $payload);
            if (!$createRes->successful()) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'Judge0 create failed ('.$createRes->status().'): ' . $createRes->body(),
                ], 502);
            }

            $token = $createRes->json('token');
            if (!$token) {
                return response()->json(['ok' => false, 'error' => 'Judge0 did not return a token'], 502);
            }

            // Poll for result
            $getUrl    = $base . '/submissions/' . $token . '?base64_encoded=false';
            $maxPollMs = (int) env('RUN_LIMIT_POLL_MS', 10000);
            $sleepMs   = 350;
            $elapsed   = 0;
            $result    = null;
            $statusId  = null; // 1=Queue, 2=Processing, 3=Accepted, others=Finished with error

            while ($elapsed < $maxPollMs) {
                usleep($sleepMs * 1000);
                $elapsed += $sleepMs;

                $res = $client->get($getUrl);
                if (!$res->successful()) {
                    continue; // keep polling
                }

                $result   = $res->json();
                $statusId = $result['status']['id'] ?? null;

                if ($statusId !== 1 && $statusId !== 2) {
                    break; // finished
                }
            }

            if (!$result) {
                return response()->json(['ok' => false, 'error' => 'No result from Judge0'], 504);
            }

            $stdout = (string) ($result['stdout'] ?? '');
            $stderr = (string) ($result['stderr'] ?? ($result['compile_output'] ?? ''));
            $time   = (float)  ($result['time'] ?? 0);
            $statusDesc = $result['status']['description'] ?? '';
            $ok = ($statusId === 3); // 3 = Accepted
            $exit = $ok ? 0 : 1;

            return response()->json([
                'ok'      => $ok,
                'stdout'  => $stdout,
                'stderr'  => $stderr ?: ($ok ? '' : $statusDesc),
                'time_ms' => (int) round($time * 1000),
                'exit'    => $exit,
                'lang'    => $data['language'],
            ], 200, ['Content-Type' => 'application/json; charset=utf-8']);

        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => 'Judge0 request failed.',
                'detail'=> config('app.debug') ? $e->getMessage() : null,
            ], 502);
        }
    }

    private function judge0LanguageId(string $lang): ?int
    {
        return [
            'cpp'    => (int) env('JUDGE0_LANG_CPP', 54),
            'java'   => (int) env('JUDGE0_LANG_JAVA', 62),
            'python' => (int) env('JUDGE0_LANG_PYTHON', 71),
        ][$lang] ?? null;
    }
}
