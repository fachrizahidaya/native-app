<?php

namespace App\Http\Controllers;

use App\Models\Homework;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HomeworkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $homework = Homework::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $homework,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        $homework = Homework::create([
            'user_id' => $request->user()->id,
            ...$data,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Homework created successfully',
            'data' => $homework,
        ], 201);
    }

    public function show(Request $request, Homework $homework): JsonResponse
    {
        $this->authorizeHomework($request, $homework);

        return response()->json([
            'success' => true,
            'data' => $homework,
        ]);
    }

    public function update(Request $request, Homework $homework): JsonResponse
    {
        $this->authorizeHomework($request, $homework);

        $data = $this->validateData($request);
        $homework->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Homework updated successfully',
            'data' => $homework,
        ]);
    }

    public function destroy(Request $request, Homework $homework): JsonResponse
    {
        $this->authorizeHomework($request, $homework);

        $homework->delete();

        return response()->json([
            'success' => true,
            'message' => 'Homework deleted successfully',
        ]);
    }

    private function validateData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function authorizeHomework(Request $request, Homework $homework): void
    {
        abort_unless($homework->user_id === $request->user()->id, 404);
    }
}
