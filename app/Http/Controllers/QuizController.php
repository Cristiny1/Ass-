<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Category;

class QuizController extends Controller
{
    public function __construct()
    {
        // Require authentication for all routes in this controller
        $this->middleware('auth');

        // Custom middleware to restrict roles (admin or teacher)
        $this->middleware(function ($request, $next) {
            if (!in_array(auth()->user()->role, ['admin', 'teacher'])) {
                return redirect('/login')->with('error', 'Unauthorized access!');
            }
            return $next($request);
        });
    }

    // Show dashboard with quizzes
    public function index()
    {
        $quizzes = Quiz::with(['creator', 'category'])
                       ->orderBy('created_at', 'desc')
                       ->get();

        $categories = Category::orderBy('name')->pluck('name')->toArray();
        $difficultyLevels = ['Beginner', 'Intermediate', 'Advanced'];
        $statusTypes = ['Active', 'Draft', 'Archived'];

        $dashboardTitle = ucfirst(auth()->user()->role) . ' Dashboard';

        return view('dashboard', compact(
            'quizzes', 'categories', 'difficultyLevels', 'statusTypes', 'dashboardTitle'
        ));
    }

    // Show single quiz details
    public function show($id)
    {
        $quiz = Quiz::with(['creator', 'category'])->findOrFail($id);
        return view('quiz.show', compact('quiz'));
    }
}