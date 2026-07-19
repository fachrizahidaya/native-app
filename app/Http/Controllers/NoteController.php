<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notes = Note::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notes
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        $note = Note::create([
            'user_id' => $request->user()->id,
            ...$data
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note created successfully',
            'data' => $note
        ], 201);
    }

    public function show(Request $request, Note $note): JsonResponse
    {
        $this->authorizeNote($request, $note);

        return response()->json([
            'success' => true,
            'data' => $note
        ]);
    }

    public function update(Request $request, Note $note): JsonResponse
    {
        $this->authorizeNote($request, $note);

        $data = $this->validateData($request);

        $note->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Note updated successfully',
            'data' => $note
        ]);
    }

    public function destroy(Request $request, Note $note): JsonResponse
    {
        $this->authorizeNote($request, $note);

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully'
        ]);
    }

    private function validateData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function authorizeNote(Request $request, Note $note): void
    {
        abort_unless($note->user_id === $request->user()->id, 404);
    }
}
